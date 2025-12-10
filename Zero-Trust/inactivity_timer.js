// Inactivity Timer - 5 seconds detection, 30 seconds warning
// Include this file on all protected pages

// Inactivity detection: 5 seconds before warning, 30 seconds warning countdown
const INACTIVITY_DELAY = 5; // Show warning after 5 seconds of inactivity
const WARNING_COUNTDOWN = 30; // 30 seconds countdown before logout
let inactivityTimer;
let countdownInterval;
let inactivityStartTime;

// Create warning box if it doesn't exist
function createWarningBox() {
    if (document.getElementById('inactivity-warning')) return;
    
    const warningHTML = `
        <div id="inactivity-warning" class="hidden fixed top-4 left-1/2 transform -translate-x-1/2 z-50 w-full max-w-md mx-4">
            <div class="bg-red-50 border-2 border-red-300 rounded-xl p-4 animate-pulse shadow-lg">
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
        </div>
    `;
    
    document.body.insertAdjacentHTML('afterbegin', warningHTML);
}

function showInactivityWarning() {
    createWarningBox();
    
    const warningBox = document.getElementById('inactivity-warning');
    const timerDisplay = document.getElementById('inactivity-timer');
    const progressBar = document.getElementById('inactivity-progress');
    
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
    const warningBox = document.getElementById('inactivity-warning');
    const timerDisplay = document.getElementById('inactivity-timer');
    const progressBar = document.getElementById('inactivity-progress');
    
    if (!warningBox) return;
    
    // Hide warning box
    warningBox.classList.add('hidden');
    
    // Clear countdown
    if (countdownInterval) {
        clearInterval(countdownInterval);
    }
    
    // Reset display
    if (timerDisplay) timerDisplay.textContent = '30';
    if (progressBar) progressBar.style.width = '100%';
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

// Initialize inactivity timer when page loads
document.addEventListener('DOMContentLoaded', function() {
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
    console.log('Inactivity timer started. Will show warning after 5 seconds of no activity.');
});