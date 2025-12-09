<?php
session_start();

// Check if OTP session exists
if (!isset($_SESSION['otp_purpose'], $_SESSION['otp_email'])) {
    header('Location: login.php');
    exit;
}

// For signup, we need pending_user_id
if ($_SESSION['otp_purpose'] === 'signup' && !isset($_SESSION['pending_user_id'])) {
    header('Location: signup.php');
    exit;
}

// For login, we need pending_user_id (set by login_action.php)
if ($_SESSION['otp_purpose'] === 'login' && !isset($_SESSION['pending_user_id'])) {
    header('Location: login.php');
    exit;
}

// For send_money, account_settings we need user_id
if (in_array($_SESSION['otp_purpose'], ['send_money', 'account_settings']) && !isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$email = $_SESSION['otp_email'];
$purpose = $_SESSION['otp_purpose'];

// Get resend tracking info
$resendCount = 0;
$canResend = false;
$secondsUntilResend = 0;

// Check if we have a session timestamp for last OTP sent
if (!isset($_SESSION['last_otp_sent_time'])) {
    // First time in this session - set it to now
    $_SESSION['last_otp_sent_time'] = time();
    $canResend = false;
    $secondsUntilResend = 15;
} else {
    $timeSinceLastOTP = time() - $_SESSION['last_otp_sent_time'];
    if ($timeSinceLastOTP >= 15) {
        $canResend = true;
        $secondsUntilResend = 0;
    } else {
        $canResend = false;
        $secondsUntilResend = 15 - $timeSinceLastOTP;
    }
}

// Get resend count from database
try {
    require 'db.php';
    
    $stmt = $pdo->prepare("
        SELECT ResendCount, BlockedUntil
        FROM OTPResendTracking
        WHERE Email = ? AND Purpose = ?
        ORDER BY CreatedAt DESC
        LIMIT 1
    ");
    $stmt->execute([$email, $purpose]);
    $tracking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($tracking) {
        // Check if blocked
        if ($tracking['BlockedUntil']) {
            $blockedUntil = strtotime($tracking['BlockedUntil']);
            if (time() < $blockedUntil) {
                // Still blocked
                $canResend = false;
                $secondsUntilResend = $blockedUntil - time();
            } else {
                // Block expired - reset count
                $resendCount = 0;
            }
        } else {
            $resendCount = (int)$tracking['ResendCount'];
        }
    }
} catch (Exception $e) {
    error_log("OTP tracking error: " . $e->getMessage());
}

$remainingResends = 3 - $resendCount;

?>
<!DOCTYPE html>
<html>
<head>
    <title>Verify OTP</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-[Inter]">

<div class="max-w-xl mx-auto mt-16 bg-white p-8 rounded-2xl shadow-xl">

    <h1 class="text-2xl font-bold mb-4">Verify Your OTP</h1>

    <p class="text-gray-600 mb-4">
        Code sent to <strong><?php echo htmlspecialchars($email) ?></strong><br>
        Expires in <span id="timer" class="text-red-600 font-bold">2:00</span>
    </p>

    <?php if (!empty($_GET['error'])): ?>
        <div class="p-3 bg-red-100 text-red-700 rounded mb-4">
            <?= htmlspecialchars($_GET['error']) ?>
        </div>
    <?php endif; ?>

    <?php 
    // Route to correct verification based on purpose
    $purpose = $_SESSION['otp_purpose'] ?? 'signup';
    
    if ($purpose === 'login') {
        $action = 'login_otp_verify.php';
    } elseif ($purpose === 'send_money') {
        $action = 'send_money_otp_verify.php';
    } elseif ($purpose === 'account_settings') {
        $action = 'account_settings_otp_verify.php';
    } else {
        $action = 'otp_verify.php';
    }
    ?>
    <?php if (!empty($_GET['success'])): ?>
        <div class="p-3 bg-green-100 text-green-700 rounded mb-4">
            <?= htmlspecialchars($_GET['success']) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= $action ?>" class="space-y-4">
        <input maxlength="6" name="otp"
               class="w-full text-center text-xl p-3 border rounded-xl"
               placeholder="Enter 6-digit code" required>

        <button class="w-full bg-blue-600 text-white p-3 rounded-xl font-semibold">
            Verify
        </button>
    </form>

    <!-- Resend OTP Section -->
    <?php if ($resendCount < 3): ?>
        <div class="mt-4 pt-4 border-t">
            <p class="text-sm text-gray-600 mb-2">
                Didn't receive the code? 
                <span class="font-semibold text-red-600">(<?= $remainingResends ?> resend(s) remaining)</span>
            </p>
            
            <?php if ($canResend): ?>
                <form method="POST" action="resend_otp.php">
                    <button type="submit" class="w-full bg-gray-600 text-white p-2 rounded-xl font-semibold hover:bg-gray-700">
                        Resend OTP
                    </button>
                </form>
            <?php else: ?>
                <button disabled class="w-full bg-gray-300 text-gray-600 p-2 rounded-xl font-semibold cursor-not-allowed">
                    Resend available in <span id="resend-timer"><?= $secondsUntilResend ?></span>s
                </button>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="mt-4 pt-4 border-t">
            <p class="text-sm text-red-600 font-semibold">
                ⚠️ Maximum resend attempts reached. Next attempt will block you for 2 minutes.
            </p>
        </div>
    <?php endif; ?>

</div>

<script>
let sec = 120;
const t = document.getElementById("timer");

setInterval(() => {
    sec--;
    if (sec <= 0) {
        t.textContent = "Expired";
        return;
    }
    t.textContent = Math.floor(sec/60) + ":" + String(sec%60).padStart(2, "0");
}, 1000);

<?php if (!$canResend && $secondsUntilResend > 0): ?>
// Resend countdown timer
let resendSeconds = <?= $secondsUntilResend ?>;
const resendTimer = document.getElementById("resend-timer");

const resendInterval = setInterval(() => {
    resendSeconds--;
    if (resendSeconds <= 0) {
        clearInterval(resendInterval);
        window.location.reload();
    } else {
        resendTimer.textContent = resendSeconds;
    }
}, 1000);
<?php endif; ?>
</script>

</body>
</html>
