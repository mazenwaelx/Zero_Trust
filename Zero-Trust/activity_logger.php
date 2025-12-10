<?php

/**
 * Log user activity to file
 * 
 * @param int $userId User ID
 * @param string $action Action performed (e.g., 'login', 'send_money', 'signup')
 * @param string $details Additional details about the action
 * @param string $status Status of action ('success', 'failed', 'pending')
 */
function logActivity(int $userId, string $action, string $details = '', string $status = 'success'): void {
    $logDir = __DIR__ . '/logs';
    
    // Create logs directory if it doesn't exist
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logFile = $logDir . '/activity_log.txt';
    
    // Get additional context
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $timestamp = date('Y-m-d H:i:s');
    
    // Format log entry
    $logEntry = sprintf(
        "[%s] UserID: %d | Action: %s | Status: %s | IP: %s | Details: %s | UserAgent: %s\n",
        $timestamp,
        $userId,
        $action,
        strtoupper($status),
        $ipAddress,
        $details,
        $userAgent
    );
    
    // Write to log file
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

/**
 * Log activity for guest/unauthenticated actions
 */
function logGuestActivity(string $action, string $details = '', string $status = 'success'): void {
    $logDir = __DIR__ . '/logs';
    
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logFile = $logDir . '/activity_log.txt';
    
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $timestamp = date('Y-m-d H:i:s');
    
    $logEntry = sprintf(
        "[%s] UserID: GUEST | Action: %s | Status: %s | IP: %s | Details: %s | UserAgent: %s\n",
        $timestamp,
        $action,
        strtoupper($status),
        $ipAddress,
        $details,
        $userAgent
    );
    
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}
