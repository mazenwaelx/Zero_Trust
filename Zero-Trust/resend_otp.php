<?php
session_start();
require 'db.php';
require 'email_config.php';
require 'activity_logger.php';

if (!isset($_SESSION['otp_purpose'], $_SESSION['otp_email'])) {
    header('Location: login.php');
    exit;
}

$email = $_SESSION['otp_email'];
$purpose = $_SESSION['otp_purpose'];

function backWithError($msg) {
    header("Location: otp.php?error=" . urlencode($msg));
    exit;
}

try {
    // Get user info
    $stmt = $pdo->prepare("SELECT Id, OTPBlockedUntil FROM Users WHERE Email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check if user is temporarily blocked from OTP
    if ($user && $user['OTPBlockedUntil']) {
        $blockedUntil = new DateTime($user['OTPBlockedUntil'], new DateTimeZone('UTC'));
        $now = new DateTime('now', new DateTimeZone('UTC'));
        
        if ($now < $blockedUntil) {
            $remainingSeconds = $blockedUntil->getTimestamp() - $now->getTimestamp();
            $remainingMinutes = floor($remainingSeconds / 60);
            $remainingSecs = $remainingSeconds % 60;
            backWithError("You are blocked from requesting OTP for {$remainingMinutes} minutes and {$remainingSecs} seconds due to excessive requests.");
        } else {
            // Block expired - reset
            $stmt = $pdo->prepare("UPDATE Users SET OTPBlockedUntil = NULL WHERE Id = ?");
            $stmt->execute([$user['Id']]);
        }
    }
    
    // Get or create resend tracking record
    $stmt = $pdo->prepare("
        SELECT Id, ResendCount, FirstOTPSentAt, CreatedAt, BlockedUntil
        FROM OTPResendTracking
        WHERE Email = ? AND Purpose = ?
        ORDER BY CreatedAt DESC
        LIMIT 1
    ");
    $stmt->execute([$email, $purpose]);
    $tracking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$tracking) {
        // First resend - create tracking record with count = 1
        $stmt = $pdo->prepare("
            INSERT INTO OTPResendTracking (Email, Purpose, ResendCount, FirstOTPSentAt, CreatedAt)
            VALUES (?, ?, 1, NOW(), NOW())
        ");
        $stmt->execute([$email, $purpose]);
        $resendCount = 1; // This is the first resend
        $firstSentAt = time();
    } else {
        // Check if block expired
        if ($tracking['BlockedUntil']) {
            $blockedUntil = strtotime($tracking['BlockedUntil']);
            if (time() < $blockedUntil) {
                $remaining = $blockedUntil - time();
                $minutes = floor($remaining / 60);
                $seconds = $remaining % 60;
                backWithError("You are blocked from requesting OTP for {$minutes} minutes and {$seconds} seconds.");
            } else {
                // Block expired - reset tracking
                $stmt = $pdo->prepare("
                    UPDATE OTPResendTracking 
                    SET ResendCount = 0, BlockedUntil = NULL 
                    WHERE Id = ?
                ");
                $stmt->execute([$tracking['Id']]);
                $resendCount = 0;
                $firstSentAt = time();
            }
        } else {
            $resendCount = (int)$tracking['ResendCount'];
            
            // Check if 15 seconds have passed since last OTP (using session time)
            if (isset($_SESSION['last_otp_sent_time'])) {
                $timeSinceLastOTP = time() - $_SESSION['last_otp_sent_time'];
                if ($timeSinceLastOTP < 15) {
                    $remaining = 15 - $timeSinceLastOTP;
                    backWithError("Please wait $remaining seconds before requesting a new OTP.");
                }
            }
            
            // Check if already used 3 resends (max allowed)
            if ($resendCount >= 3) {
                // BLOCK FOR 2 MINUTES
                $blockedUntil = (new DateTime('now', new DateTimeZone('UTC')))
                    ->modify('+2 minutes')
                    ->format('Y-m-d H:i:s');
                
                $stmt = $pdo->prepare("
                    UPDATE OTPResendTracking 
                    SET BlockedUntil = ? 
                    WHERE Id = ?
                ");
                $stmt->execute([$blockedUntil, $tracking['Id']]);
                
                // Also block in Users table
                if ($user) {
                    $stmt = $pdo->prepare("UPDATE Users SET OTPBlockedUntil = ? WHERE Id = ?");
                    $stmt->execute([$blockedUntil, $user['Id']]);
                    logActivity($user['Id'], 'otp_blocked_temp', "Email: $email, Blocked for 2 minutes after 3 resend attempts", 'blocked');
                } else {
                    logGuestActivity('otp_blocked_temp', "Email: $email, Blocked for 2 minutes after 3 resend attempts", 'blocked');
                }
                
                backWithError("You have exceeded the OTP resend limit (3 attempts). You are blocked for 2 minutes.");
            }
            
            // Increment resend count BEFORE using it
            $resendCount++; // Increment first
            $stmt = $pdo->prepare("
                UPDATE OTPResendTracking 
                SET ResendCount = ? 
                WHERE Id = ?
            ");
            $stmt->execute([$resendCount, $tracking['Id']]);
        }
    }
    
    // Invalidate all previous OTPs for this purpose
    if ($purpose === 'signup' && isset($_SESSION['pending_user_id'])) {
        $stmt = $pdo->prepare("
            UPDATE otps 
            SET `IsUsed` = 1 
            WHERE `PendingUserId` = ? AND `Purpose` = ? AND `IsUsed` = 0
        ");
        $stmt->execute([$_SESSION['pending_user_id'], $purpose]);
    } else if ($purpose === 'login' && isset($_SESSION['pending_user_id'])) {
        $stmt = $pdo->prepare("
            UPDATE otps 
            SET `IsUsed` = 1 
            WHERE `UserId` = ? AND `Purpose` = ? AND `IsUsed` = 0
        ");
        $stmt->execute([$_SESSION['pending_user_id'], $purpose]);
    } else if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("
            UPDATE otps 
            SET `IsUsed` = 1 
            WHERE `UserId` = ? AND `Purpose` = ? AND `IsUsed` = 0
        ");
        $stmt->execute([$_SESSION['user_id'], $purpose]);
    }
    
    // Generate new OTP
    $otp = str_pad((string)random_int(0, 999999), 6, "0", STR_PAD_LEFT);
    $expiresAt = (new DateTime('now', new DateTimeZone('UTC')))
        ->modify('+2 minutes')
        ->format('Y-m-d H:i:s');
    
    // Insert new OTP based on purpose
    if ($purpose === 'signup' && isset($_SESSION['pending_user_id'])) {
        $stmt = $pdo->prepare("
            INSERT INTO otps (`PendingUserId`, `UserId`, `Code`, `Purpose`, `ExpiresAt`, `IsUsed`, `CreatedAt`)
            VALUES (?, NULL, ?, ?, ?, 0, UTC_TIMESTAMP())
        ");
        $stmt->execute([$_SESSION['pending_user_id'], $otp, $purpose, $expiresAt]);
    } else if ($purpose === 'login' && isset($_SESSION['pending_user_id'])) {
        $stmt = $pdo->prepare("
            INSERT INTO otps (`UserId`, `Code`, `Purpose`, `ExpiresAt`, `IsUsed`, `CreatedAt`)
            VALUES (?, ?, ?, ?, 0, UTC_TIMESTAMP())
        ");
        $stmt->execute([$_SESSION['pending_user_id'], $otp, $purpose, $expiresAt]);
    } else if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("
            INSERT INTO otps (`UserId`, `Code`, `Purpose`, `ExpiresAt`, `IsUsed`, `CreatedAt`)
            VALUES (?, ?, ?, ?, 0, UTC_TIMESTAMP())
        ");
        $stmt->execute([$_SESSION['user_id'], $otp, $purpose, $expiresAt]);
    }
    
    // Send OTP email
    if (!sendOtpEmail($email, $otp)) {
        backWithError("Failed to send OTP email. Please try again.");
    }
    
    // Update session timestamp
    $_SESSION['last_otp_sent_time'] = time();
    
    // Log resend
    if ($user) {
        logActivity($user['Id'], 'otp_resent', "Purpose: $purpose, Resend count: $resendCount/3", 'success');
    } else {
        logGuestActivity('otp_resent', "Email: $email, Purpose: $purpose, Resend count: $resendCount/3", 'success');
    }
    
    $remaining = 3 - $resendCount;
    header("Location: otp.php?success=" . urlencode("New OTP sent! You have $remaining resend(s) remaining."));
    exit;
    
} catch (Throwable $e) {
    error_log("RESEND OTP ERROR: " . $e->getMessage());
    backWithError("Error: " . $e->getMessage());
}
