<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'db.php';

// User must be logged in
if (!isset($_SESSION['user_id'])) {
    // Save the page they were trying to access
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php?error=" . urlencode("Please login first."));
    exit;
}

// Note: Temporary blocking is handled per-action (password attempts, OTP resends)
// No permanent blocking checks needed here

// SESSION TIME LIMIT (5 minutes = 300 seconds from login)
$expireAfter = 300;

// Check if session_start_time exists
if (isset($_SESSION['session_start_time'])) {
    $elapsed = time() - $_SESSION['session_start_time'];

    if ($elapsed > $expireAfter) {
        // Save the page they were on
        $currentPage = $_SERVER['REQUEST_URI'];
        
        // Destroy session
        session_unset();
        session_destroy();
        
        // Start new session to store redirect
        session_start();
        $_SESSION['redirect_after_login'] = $currentPage;

        // Redirect user with timeout message
        header("Location: login.php?timeout=1");
        exit;
    }
}
?>
