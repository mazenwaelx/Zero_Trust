<?php
echo "<div style='color:green;font-size:20px;'>email_config.php LOADED → OK</div>";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/phpmailer/src/Exception.php';
require __DIR__ . '/phpmailer/src/PHPMailer.php';
require __DIR__ . '/phpmailer/src/SMTP.php';

const OTP_FROM_EMAIL = 'mazenwael5115@gmail.com';
const OTP_FROM_NAME  = 'ZeroTrust';
const OTP_FROM_PASS  = 'brvp liyx umbk dukq';  // Your 16-char Gmail App Password

function sendOtpEmail(string $toEmail, string $otpCode): bool
{
    $mail = new PHPMailer(true);

    try {
        // Gmail SMTP
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = OTP_FROM_EMAIL;
        $mail->Password   = OTP_FROM_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        // Sender and recipient
        $mail->setFrom(OTP_FROM_EMAIL, OTP_FROM_NAME);
        $mail->addAddress($toEmail);

        // Email content
        $mail->isHTML(true);
        $mail->Subject = 'Your ZeroTrustBank OTP Code';
        $mail->Body    = "
            <p>Your OTP code is:</p>
            <h2 style='font-size:32px; letter-spacing:5px;'>{$otpCode}</h2>
            <p>This code expires in <strong>2 minutes</strong>.</p>
        ";
        $mail->AltBody = "Your OTP code is: {$otpCode} (expires in 2 minutes).";

        return $mail->send();
    } catch (Exception $e) {

        echo "<pre style='color:red; font-size:16px;'>";
        echo "MAIL ERROR:<br>";
        echo $mail->ErrorInfo;
        echo "</pre>";

        return false;
    }
}
