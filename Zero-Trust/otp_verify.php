<?php
session_start();
require 'db.php';
require 'device_fingerprint.php';
require 'location_check.php';
require 'activity_logger.php';

if (!isset($_SESSION['pending_user_id'], $_POST['otp'])) {
    header("Location: signup.php");
    exit;
}

$pendingId = (int)$_SESSION['pending_user_id'];
$code      = trim($_POST['otp']);

function backWithError($msg) {
    header("Location: otp.php?error=" . urlencode($msg));
    exit;
}

try {
    // Note: Temporary blocking is handled per-action
    // No permanent blocking check needed here

    /* -----------------------------------------------
       1) Validate OTP for signup only
    -----------------------------------------------*/
    $stmt = $pdo->prepare("
        SELECT `Id`, `ExpiresAt`, `IsUsed`
        FROM otps
        WHERE `PendingUserId` = ?
          AND `Code` = ?
          AND `Purpose`='signup'
        ORDER BY `CreatedAt` DESC
        LIMIT 1
    ");
    $stmt->execute([$pendingId, $code]);
    $otp = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$otp) backWithError("Invalid OTP.");
    if ($otp['IsUsed']) backWithError("OTP already used.");

    $now = new DateTime("now", new DateTimeZone("UTC"));
    $exp = new DateTime($otp['ExpiresAt'], new DateTimeZone("UTC"));

    if ($now > $exp) backWithError("OTP expired. Signup again.");

    /* -----------------------------------------------
       2) Mark OTP as used
    -----------------------------------------------*/
    $upd = $pdo->prepare("UPDATE otps SET `IsUsed`=1 WHERE `Id`=?");
    $upd->execute([$otp['Id']]);

    /* -----------------------------------------------
       3) Fetch Pending user
    -----------------------------------------------*/
    $stmt = $pdo->prepare("SELECT * FROM PendingUsers WHERE Id=?");
    $stmt->execute([$pendingId]);
    $pending = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pending) backWithError("Signup data not found.");

    /* -----------------------------------------------
       4) Move → Users table
    -----------------------------------------------*/
    $stmt = $pdo->prepare("
        INSERT INTO Users (Email, Username, Phone, PasswordHash, IsVerified, CreatedAt)
        VALUES (?, ?, ?, ?, 1, UTC_TIMESTAMP())
    ");
    $stmt->execute([
        $pending['Email'],
        $pending['Username'],
        $pending['Phone'],
        $pending['PasswordHash']
    ]);
    $newUserId = $pdo->lastInsertId();

    /* -----------------------------------------------
       5) Remove pending user
    -----------------------------------------------*/
    $del = $pdo->prepare("DELETE FROM PendingUsers WHERE Id=?");
    $del->execute([$pendingId]);

    /* -----------------------------------------------
       6) ZERO TRUST: Trust device & log first login
    -----------------------------------------------*/
    $deviceFingerprint = getDeviceFingerprint();
    $ipAddress = getUserIP();
    
    // Trust this device for future logins
    trustDevice($pdo, $newUserId, $deviceFingerprint);
    
    // Log successful first login
    logLoginAttempt($pdo, $newUserId, $ipAddress, true);

    /* -----------------------------------------------
       7) Start 5-minute session 
    -----------------------------------------------*/
    unset($_SESSION['pending_user_id'], $_SESSION['otp_email']);

    $_SESSION['user_id']       = $newUserId;
    $_SESSION['session_start_time'] = time();
    $_SESSION['expire_after']  = 300;
    $_SESSION['device_fingerprint'] = $deviceFingerprint;
    $_SESSION['ip_address'] = $ipAddress;

    // Log successful signup
    logActivity($newUserId, 'signup_completed', "Email: {$pending['Email']}, Username: {$pending['Username']}", 'success');

    header("Location: dashboard.php");
    exit;

} catch (Throwable $e) {
    error_log("OTP VERIFY ERR: " . $e->getMessage());
    backWithError("Unexpected error: " . $e->getMessage());
}
