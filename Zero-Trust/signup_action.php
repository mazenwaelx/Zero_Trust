<?php
session_start();

// DEBUG — confirm file loads
echo "<div style='padding:10px;background:#d1ffd1;color:#006600;font-size:18px;'>signup_action.php LOADED</div>";

require 'db.php';
require 'email_config.php';   // this must show "email_config.php LOADED"
require 'activity_logger.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: signup.php');
    exit;
}

$email    = trim($_POST['email']    ?? '');
$username = trim($_POST['username'] ?? '');
$phone    = trim($_POST['phone']    ?? '');
$password = trim($_POST['password'] ?? '');

function backWithError(string $msg): void {
    header("Location: signup.php?error=" . urlencode($msg));
    exit;
}

/* ---------------------------
   1) Validate inputs
---------------------------*/
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    backWithError("Invalid email format.");
}
if (!preg_match('/^[A-Za-z ]+$/', $username)) {
    backWithError("Username must contain letters only.");
}
if (!preg_match('/^(010|011|012|015)[0-9]{8}$/', $phone)) {
    backWithError("Phone must be 11 digits Egyptian format.");
}
if (!preg_match('/^(?=.*[A-Z])(?=.*\d)(?=.*[@#^&!%$?]).{8,}$/', $password)) {
    backWithError("Weak password. Must include uppercase, number, special char.");
}

try {

    /* -----------------------------------------------
       2) Delete old failed attempts
    -----------------------------------------------*/
    $del = $pdo->prepare("DELETE FROM PendingUsers WHERE Email = ?");
    $del->execute([$email]);

    /* -----------------------------------------------
       3) Insert into PendingUsers
    -----------------------------------------------*/
    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("
        INSERT INTO PendingUsers (Email, Username, Phone, PasswordHash, CreatedAt)
        VALUES (?, ?, ?, ?, UTC_TIMESTAMP())
    ");
    $stmt->execute([$email, $username, $phone, $hash]);
    $pendingId = $pdo->lastInsertId();

    /* -----------------------------------------------
       4) Generate OTP (2 minutes)
    -----------------------------------------------*/
    $otp = str_pad((string)random_int(0, 999999), 6, "0", STR_PAD_LEFT);

    $expiresAt = (new DateTime('now', new DateTimeZone('UTC')))
        ->modify('+2 minutes')
        ->format('Y-m-d H:i:s');

    $stmt = $pdo->prepare("
        INSERT INTO otps (`PendingUserId`, `UserId`, `Code`, `Purpose`, `ExpiresAt`, `IsUsed`, `CreatedAt`)
        VALUES (:pendingId, NULL, :code, 'signup', :expiresAt, 0, UTC_TIMESTAMP())
    ");
    $stmt->execute([
        ':pendingId' => $pendingId,
        ':code' => $otp,
        ':expiresAt' => $expiresAt
    ]);

    /* -----------------------------------------------
       5) Send OTP Email — DEBUG SHOWS ERRORS
    -----------------------------------------------*/

    echo "<div style='padding:10px;background:#fff3cd;color:#856404;border:1px solid #ffeeba;margin-top:20px;'>
            DEBUG: Sending OTP email...
          </div>";

    $result = sendOtpEmail($email, $otp);

    if (!$result) {
        echo "<h2 style='color:red;'>sendOtpEmail() returned FALSE — EMAIL FAILED</h2>";
        exit; // STOP HERE so we can see PHPMailer error
    }

    echo "<h2 style='color:green;'>OTP email SEND SUCCESS (this should go to OTP page)</h2>";

    /* -----------------------------------------------
       6) Store session for OTP
    -----------------------------------------------*/
    $_SESSION['pending_user_id'] = $pendingId;
    $_SESSION['otp_purpose']     = 'signup';
    $_SESSION['otp_email']       = $email;
    $_SESSION['last_otp_sent_time'] = time(); // Track when OTP was sent
    
    // Reset OTP resend tracking for new signup attempt
    $stmt = $pdo->prepare("DELETE FROM OTPResendTracking WHERE Email = ? AND Purpose = 'signup'");
    $stmt->execute([$email]);

    // Log signup attempt
    logGuestActivity('signup_initiated', "Email: $email, Username: $username", 'pending');

    header("Location: otp.php");
    exit;

} catch (Throwable $e) {
    echo "<h1 style='color:red;'>FATAL SIGNUP ERROR:</h1>";
    echo "<pre>{$e->getMessage()}</pre>";
    exit;
}
