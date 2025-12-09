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
    <!-- Inactivity Warning (hidden by default) -->
    <div id="inactivity-warning" class="hidden mb-6 p-4 bg-red-50 border-2 border-red-300 rounded-xl animate-pulse">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-bold text-red-900">⚠️ Inactivity Detected!</p>
                <p class="text-xs text-red-600">You will be logged out in <span id="inactivity-timer" class="font-bold">30</span> seconds</p>
            </div>
            <button onclick="dismissWarning()" class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-semibold hover:bg-red-700">
                I'm Here
            </button>
        </div>
        <div class="mt-2 w-full bg-red-200 rounded-full h-2">
            <div id="inactivity-progress" class="bg-red-600 h-2 rounded-full transition-all duration-1000" style="width: 100%"></div>
        </div>
    </div>

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

// Inactivity detection: 5 seconds before warning, 30 seconds warning countdown
const INACTIVITY_DELAY = 5; // Show warning after 5 seconds of inactivity
const WARNING_COUNTDOWN = 30; // 30 seconds countdown before logout
let inactivityTimer;
let countdownInterval;
let inactivityStartTime;
const warningBox = document.getElementById('inactivity-warning');
const timerDisplay = document.getElementById('inactivity-timer');
const progressBar = document.getElementById('inactivity-progress');

function showInactivityWarning() {
    // Show warning box
    warningBox.classList.remove('hidden');
    
    // Start countdown from 30 seconds
    inactivityStartTime = Date.now();
    
    countdownInterval = setInterval(function() {
        const elapsed = Math.floor((Date.now() - inactivityStartTime) / 1000);
        const remaining = WARNING_COUNTDOWN - elapsed;
        
        if (remaining <= 0) {
            clearInterval(countdownInterval);
            // Redirect to login
            window.location.href = 'login.php?error=' + encodeURIComponent('You were logged out due to inactivity.');
            return;
        }
        
        // Update display
        timerDisplay.textContent = remaining;
        
        // Update progress bar
        const percentage = (remaining / WARNING_COUNTDOWN) * 100;
        progressBar.style.width = percentage + '%';
    }, 1000);
}

function hideInactivityWarning() {
    // Hide warning box
    warningBox.classList.add('hidden');
    
    // Clear countdown
    if (countdownInterval) {
        clearInterval(countdownInterval);
    }
    
    // Reset display
    timerDisplay.textContent = '30';
    progressBar.style.width = '100%';
}

function resetInactivityTimer() {
    // Clear existing timer
    clearTimeout(inactivityTimer);
    
    // Hide warning if showing
    hideInactivityWarning();
    
    // Set new timer - show warning after 5 seconds of inactivity
    inactivityTimer = setTimeout(function() {
        // Show warning and start 30-second countdown
        showInactivityWarning();
    }, INACTIVITY_DELAY * 1000);
}

function dismissWarning() {
    // User clicked "I'm Here" button
    resetInactivityTimer();
}

// Events that count as activity (removed mousemove to detect inactivity better)
const activityEvents = ['mousedown', 'keypress', 'scroll', 'touchstart', 'click'];

// Reset timer on any activity
activityEvents.forEach(function(eventName) {
    document.addEventListener(eventName, function() {
        resetInactivityTimer();
    }, true);
});

// Start the inactivity timer when page loads
resetInactivityTimer();

// Debug: Log when timer starts
console.log('Inactivity timer started. Will show warning after 30 seconds of no activity.');
</script>

</body>
</html>
