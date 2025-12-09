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
    header('Location: account_settings.php');
    exit;
}

$userId = $_SESSION['user_id'];
$newUsername = trim($_POST['new_username'] ?? '');
$newPassword = trim($_POST['new_password'] ?? '');
$currentPassword = trim($_POST['current_password'] ?? '');

function backWithError(string $msg): void {
    header("Location: account_settings.php?error=" . urlencode($msg));
    exit;
}

// Must change at least one thing
if (empty($newUsername) && empty($newPassword)) {
    backWithError("Please provide at least one field to update.");
}

// Validate new username if provided
if (!empty($newUsername) && !preg_match('/^[A-Za-z ]+$/', $newUsername)) {
    backWithError("Username must contain letters only.");
}

// Validate new password if provided
if (!empty($newPassword) && !preg_match('/^(?=.*[A-Z])(?=.*\d)(?=.*[@#^&!%$?]).{8,}$/', $newPassword)) {
    backWithError("Weak password. Must include uppercase, number, special char.");
}

try {
    // Verify current password
    $stmt = $pdo->prepare("SELECT Email, PasswordHash FROM Users WHERE Id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        backWithError("User not found.");
    }
    
    if (!password_verify($currentPassword, $user['PasswordHash'])) {
        backWithError("Incorrect current password.");
    }
    
    // Generate OTP
    $otp = str_pad((string)random_int(0, 999999), 6, "0", STR_PAD_LEFT);
    
    $expiresAt = (new DateTime('now', new DateTimeZone('UTC')))
        ->modify('+2 minutes')
        ->format('Y-m-d H:i:s');
    
    $stmt = $pdo->prepare("
        INSERT INTO otps (`UserId`, `Code`, `Purpose`, `ExpiresAt`, `IsUsed`, `CreatedAt`)
        VALUES (?, ?, 'account_settings', ?, 0, UTC_TIMESTAMP())
    ");
    $stmt->execute([$userId, $otp, $expiresAt]);
    
    // Send OTP email
    $result = sendOtpEmail($user['Email'], $otp);
    
    if (!$result) {
        backWithError("Failed to send OTP email.");
    }
    
    // Store changes in session
    $_SESSION['pending_settings'] = [
        'new_username' => $newUsername,
        'new_password' => $newPassword,
        'user_id' => $userId
    ];
    $_SESSION['otp_purpose'] = 'account_settings';
    $_SESSION['otp_email'] = $user['Email'];
    $_SESSION['last_otp_sent_time'] = time(); // Track when OTP was sent
    
    // Reset OTP resend tracking for new account settings attempt
    $stmt = $pdo->prepare("DELETE FROM OTPResendTracking WHERE Email = ? AND Purpose = 'account_settings'");
    $stmt->execute([$user['Email']]);
    
    // Log account settings change attempt
    $changes = [];
    if (!empty($newUsername)) $changes[] = "username";
    if (!empty($newPassword)) $changes[] = "password";
    logActivity($userId, 'account_settings_initiated', "Changing: " . implode(", ", $changes), 'pending');
    
    header("Location: otp.php");
    exit;
    
} catch (Throwable $e) {
    error_log("ACCOUNT SETTINGS ERROR: " . $e->getMessage());
    backWithError("Error: " . $e->getMessage());
}
