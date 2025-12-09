<?php
session_start();
require 'activity_logger.php';

$userId = $_SESSION['user_id'] ?? null;

if ($userId) {
    logActivity($userId, 'logout', 'User logged out', 'success');
}

session_unset();
session_destroy();
header('Location: login.php');
exit;
