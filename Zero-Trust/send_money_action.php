<?php
session_start();
require 'db.php';
require 'email_config.php';
require 'activity_logger.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: send_money.php');
    exit;
}

$userId = $_SESSION['user_id'];
$recipientEmail = trim($_POST['recipient_email'] ?? '');
$amount = floatval($_POST['amount'] ?? 0);
$txPassword = trim($_POST['tx_password'] ?? '');

function backWithError(string $msg): void {
    header("Location: send_money.php?error=" . urlencode($msg));
    exit;
}

// Validate
if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
    backWithError("Invalid recipient email.");
}

if ($amount <= 0) {
    backWithError("Amount must be greater than 0.");
}

if (strlen($txPassword) !== 6 || !ctype_digit($txPassword)) {
    backWithError("Transaction password must be 6 digits.");
}

try {
    // Get user data
    $stmt = $pdo->prepare("SELECT Email, TransactionPasswordHash FROM Users WHERE Id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        backWithError("User not found.");
    }
    
    // Verify transaction password
    if (!password_verify($txPassword, $user['TransactionPasswordHash'])) {
        backWithError("Incorrect transaction password.");
    }
    
    // Generate OTP
    $otp = str_pad((string)random_int(0, 999999), 6, "0", STR_PAD_LEFT);
    
    $expiresAt = (new DateTime('now', new DateTimeZone('UTC')))
        ->modify('+2 minutes')
        ->format('Y-m-d H:i:s');
    
    $stmt = $pdo->prepare("
        INSERT INTO otps (`UserId`, `Code`, `Purpose`, `ExpiresAt`, `IsUsed`, `CreatedAt`)
        VALUES (?, ?, 'send_money', ?, 0, UTC_TIMESTAMP())
    ");
    $stmt->execute([$userId, $otp, $expiresAt]);
    
    // Send OTP email
    $result = sendOtpEmail($user['Email'], $otp);
    
    if (!$result) {
        backWithError("Failed to send OTP email.");
    }
    
    // Store transaction details in session
    $_SESSION['pending_transaction'] = [
        'recipient_email' => $recipientEmail,
        'amount' => $amount,
        'user_id' => $userId
    ];
    $_SESSION['otp_purpose'] = 'send_money';
    $_SESSION['otp_email'] = $user['Email'];
    $_SESSION['last_otp_sent_time'] = time(); // Track when OTP was sent
    
    // Reset OTP resend tracking for new send money attempt
    $stmt = $pdo->prepare("DELETE FROM OTPResendTracking WHERE Email = ? AND Purpose = 'send_money'");
    $stmt->execute([$user['Email']]);
    
    // Log send money attempt
    logActivity($userId, 'send_money_initiated', "To: $recipientEmail, Amount: $amount EGP", 'pending');
    
    header("Location: otp.php");
    exit;
    
} catch (Throwable $e) {
    error_log("SEND MONEY ERROR: " . $e->getMessage());
    backWithError("Error: " . $e->getMessage());
}
