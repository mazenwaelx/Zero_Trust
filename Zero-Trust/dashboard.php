<?php require 'auth.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Secure Wallet</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background:#f3f4f6; }
        .container { max-width:480px; margin:auto; }
        .btn-primary { background:#3b82f6; color:white; padding:.75rem 1rem; border-radius:.75rem; }
        .btn-primary:hover { background:#2563eb; }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center">

<div class="container bg-white p-8 rounded-2xl shadow-xl">


    <h2 class="text-2xl font-bold mb-4 text-gray-800">Welcome, <span class="text-blue-600">User</span></h2>
    <p class="text-sm text-gray-500 mb-6">Your secure digital wallet dashboard.</p>

    <div class="space-y-4">
        <a href="send_money.php" class="block text-center btn-primary">Send Money</a>
        <a href="account_settings.php" class="block text-center p-3 bg-yellow-500 hover:bg-yellow-600 text-white rounded-xl font-semibold">Account Settings</a>
        <a href="logout.php" class="block text-center p-3 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-xl font-semibold">Log Out</a>
    </div>
</div>

<script>
// Session timeout: 5 minutes = 300 seconds from login (hidden countdown)
const SESSION_TIMEOUT = 300;
const sessionStartTime = <?php echo $_SESSION['session_start_time']; ?>;

function checkSessionTimeout() {
    const now = Math.floor(Date.now() / 1000);
    const elapsed = now - sessionStartTime;
    const remaining = SESSION_TIMEOUT - elapsed;
    
    if (remaining <= 0) {
        // Session expired - redirect to login
        window.location.href = 'login.php?timeout=1';
    }
}

// Check session timeout every second (hidden from user)
setInterval(checkSessionTimeout, 1000);
checkSessionTimeout();


</script>

<!-- Inactivity Timer -->
<script src="inactivity_timer.js"></script>

</body>
</html>
