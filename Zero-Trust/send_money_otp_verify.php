<?php
session_start();
require 'db.php';
require 'activity_logger.php';

if (!isset($_SESSION['user_id'], $_SESSION['pending_transaction'], $_POST['otp'])) {
    header("Location: send_money.php");
    exit;
}

$userId = $_SESSION['user_id'];
$code = trim($_POST['otp']);
$transaction = $_SESSION['pending_transaction'];

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
          AND `Purpose`='send_money'
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

    // TODO: Process actual money transfer here
    // For now, just simulate success
    
    // Log successful transfer
    logActivity($userId, 'send_money_completed', "To: {$transaction['recipient_email']}, Amount: {$transaction['amount']} EGP", 'success');
    
    // Clear session data
    unset($_SESSION['pending_transaction'], $_SESSION['otp_purpose'], $_SESSION['otp_email']);

    header("Location: send_money.php?success=Money sent successfully to " . urlencode($transaction['recipient_email']));
    exit;

} catch (Throwable $e) {
    error_log("SEND MONEY OTP VERIFY ERR: " . $e->getMessage());
    backWithError("Unexpected error: " . $e->getMessage());
}
