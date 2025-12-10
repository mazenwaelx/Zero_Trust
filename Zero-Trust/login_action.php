<?php
session_start();
require 'db.php';
require 'email_config.php';
require 'device_fingerprint.php';
require 'location_check.php';
require 'activity_logger.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

$email    = trim($_POST['email']    ?? '');
$password = trim($_POST['password'] ?? '');

function backWithError(string $msg): void {
    $msg = urlencode($msg);
    header("Location: login.php?error={$msg}");
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    backWithError('Invalid email.');
}

try {
    $stmt = $pdo->prepare("SELECT Id, PasswordHash, IsVerified, PasswordAttempts, LastPasswordAttempt, PasswordBlockedUntil FROM Users WHERE Email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check if user exists
    if (!$user) {
        backWithError('Incorrect email or password.');
    }

    // Check if account is temporarily blocked
    if ($user['PasswordBlockedUntil']) {
        $blockedUntil = new DateTime($user['PasswordBlockedUntil'], new DateTimeZone('UTC'));
        $now = new DateTime('now', new DateTimeZone('UTC'));
        
        if ($now < $blockedUntil) {
            $remainingSeconds = $blockedUntil->getTimestamp() - $now->getTimestamp();
            $remainingMinutes = floor($remainingSeconds / 60);
            $remainingSecs = $remainingSeconds % 60;
            logActivity($user['Id'], 'login_attempt_blocked', "Email: $email, Account temporarily blocked", 'blocked');
            backWithError("You are blocked from logging in for {$remainingMinutes} minutes and {$remainingSecs} seconds due to failed password attempts.");
        } else {
            // Block expired - reset attempts
            $stmt = $pdo->prepare("UPDATE Users SET PasswordAttempts = 0, PasswordBlockedUntil = NULL, LastPasswordAttempt = NULL WHERE Id = ?");
            $stmt->execute([$user['Id']]);
            $user['PasswordAttempts'] = 0;
            $user['PasswordBlockedUntil'] = null;
        }
    }

    // Check if password is correct
    $passwordCorrect = !empty($user['PasswordHash']) && password_verify($password, $user['PasswordHash']);

    if (!$passwordCorrect) {
        // Increment password attempts
        // Allow 3 trials total, then BLOCK FOR 5 MINUTES
        $newAttempts = (int)$user['PasswordAttempts'] + 1;
        
        if ($newAttempts >= 3) {
            // BLOCK FOR 2 MINUTES after 3 failed attempts
            $blockedUntil = (new DateTime('now', new DateTimeZone('UTC')))
                ->modify('+2 minutes')
                ->format('Y-m-d H:i:s');
            
            $stmt = $pdo->prepare("UPDATE Users SET PasswordAttempts = ?, LastPasswordAttempt = UTC_TIMESTAMP(), PasswordBlockedUntil = ? WHERE Id = ?");
            $stmt->execute([$newAttempts, $blockedUntil, $user['Id']]);
            
            // Log the temporary blocking
            logActivity($user['Id'], 'password_blocked_temp', "Email: $email, Failed password attempts: $newAttempts, Blocked for 2 minutes", 'blocked');
            
            backWithError('You have exceeded the password attempt limit (3 attempts). You are blocked from logging in for 2 minutes.');
        } else {
            // Update attempt count
            $stmt = $pdo->prepare("UPDATE Users SET PasswordAttempts = ?, LastPasswordAttempt = UTC_TIMESTAMP() WHERE Id = ?");
            $stmt->execute([$newAttempts, $user['Id']]);
            
            // Log failed attempt
            $remaining = 3 - $newAttempts;
            logActivity($user['Id'], 'login_failed_password', "Email: $email, Attempt: $newAttempts/3, $remaining remaining", 'failed');
            
            backWithError("Incorrect password. You have $remaining attempt(s) remaining before 2-minute block.");
        }
    }

    // Password is correct - reset attempts and clear any block
    if ($user['PasswordAttempts'] > 0 || $user['PasswordBlockedUntil']) {
        $stmt = $pdo->prepare("UPDATE Users SET PasswordAttempts = 0, LastPasswordAttempt = NULL, PasswordBlockedUntil = NULL WHERE Id = ?");
        $stmt->execute([$user['Id']]);
    }

    if (!$user['IsVerified']) {
        backWithError('Account not verified yet. Please sign up again.');
    }

    $userId = (int)$user['Id'];

    // Note: Temporary blocking for password attempts is handled above
    // ===== ZERO TRUST: Check device and location =====
    $deviceFingerprint = getDeviceFingerprint();
    $ipAddress = getUserIP();
    
    $isKnownDevice = isKnownDevice($pdo, $userId, $deviceFingerprint);
    $isKnownIP = isKnownLocation($pdo, $userId, $ipAddress);
    
    // Store for later verification
    $_SESSION['device_fingerprint'] = $deviceFingerprint;
    $_SESSION['ip_address'] = $ipAddress;
    $_SESSION['is_new_device'] = !$isKnownDevice;
    $_SESSION['is_new_location'] = !$isKnownIP;

    // Generate OTP for login (1 min)
    $otpCode   = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $expiresAt = (new DateTime('+1 minute'))->format('Y-m-d H:i:s');

    $stmt = $pdo->prepare("
        INSERT INTO otps (`UserId`, `Code`, `Purpose`, `ExpiresAt`, `IsUsed`, `CreatedAt`)
        VALUES (?, ?, 'login', ?, 0, NOW())
    ");
    $stmt->execute([$userId, $otpCode, $expiresAt]);

    if (!sendOtpEmail($email, $otpCode)) {
        backWithError('Could not send OTP email. Please try again.');
    }

    // store pending login info
    $_SESSION['pending_user_id'] = $userId;
    $_SESSION['otp_purpose']     = 'login';
    $_SESSION['otp_email']       = $email;
    $_SESSION['last_otp_sent_time'] = time(); // Track when OTP was sent
    
    // Reset OTP resend tracking for new login attempt
    $stmt = $pdo->prepare("DELETE FROM OTPResendTracking WHERE Email = ? AND Purpose = 'login'");
    $stmt->execute([$email]);

    // Log login attempt
    logActivity($userId, 'login_otp_sent', "Email: $email", 'pending');

    header('Location: otp.php');
    exit;

} catch (Throwable $e) {
    error_log('Login error: ' . $e->getMessage());
    // Temporarily show the actual error for debugging
    backWithError('Error: ' . $e->getMessage());
}
