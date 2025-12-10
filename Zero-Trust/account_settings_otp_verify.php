<?php
session_start();
require 'db.php';
require 'activity_logger.php';

if (!isset($_SESSION['user_id'], $_SESSION['pending_settings'], $_POST['otp'])) {
    header("Location: account_settings.php");
    exit;
}

$userId = $_SESSION['user_id'];
$code = trim($_POST['otp']);
$settings = $_SESSION['pending_settings'];

function backWithError($msg) {
    header("Location: otp.php?error=" . urlencode($msg));
    exit;
}

try {
    // Note: Temporary blocking is handled per-action
    // No permanent blocking check needed here

    // Validate OTP
    $stmt = $pdo->prepare("
        SELECT `Id`, `ExpiresAt`, `IsUsed`
        FROM otps
        WHERE `UserId` = ?
          AND `Code` = ?
          AND `Purpose`='account_settings'
        ORDER BY `CreatedAt` DESC
        LIMIT 1
    ");
    $stmt->execute([$userId, $code]);
    $otp = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$otp) backWithError("Invalid OTP.");
    if ($otp['IsUsed']) backWithError("OTP already used.");

    $now = new DateTime("now", new DateTimeZone("UTC"));
    $exp = new DateTime($otp['ExpiresAt'], new DateTimeZone("UTC"));

    if ($now > $exp) backWithError("OTP expired.");

    // Mark OTP as used
    $upd = $pdo->prepare("UPDATE otps SET `IsUsed`=1 WHERE `Id`=?");
    $upd->execute([$otp['Id']]);

    // Apply changes
    $updates = [];
    $params = [];
    
    if (!empty($settings['new_username'])) {
        $updates[] = "Username = ?";
        $params[] = $settings['new_username'];
    }
    
    if (!empty($settings['new_password'])) {
        $updates[] = "PasswordHash = ?";
        $params[] = password_hash($settings['new_password'], PASSWORD_DEFAULT);
    }
    
    if (!empty($updates)) {
        $params[] = $userId;
        $sql = "UPDATE Users SET " . implode(", ", $updates) . " WHERE Id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }
    
    // Log successful account update
    $changes = [];
    if (!empty($settings['new_username'])) $changes[] = "username to '{$settings['new_username']}'";
    if (!empty($settings['new_password'])) $changes[] = "password";
    logActivity($userId, 'account_settings_completed', "Updated: " . implode(", ", $changes), 'success');
    
    // Clear session data
    unset($_SESSION['pending_settings'], $_SESSION['otp_purpose'], $_SESSION['otp_email']);

    header("Location: account_settings.php?success=Account updated successfully");
    exit;

} catch (Throwable $e) {
    error_log("ACCOUNT SETTINGS OTP VERIFY ERR: " . $e->getMessage());
    backWithError("Unexpected error: " . $e->getMessage());
}
