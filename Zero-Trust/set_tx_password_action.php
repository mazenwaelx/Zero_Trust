<?php
session_start();
require 'db.php';
require 'activity_logger.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: set_tx_password.php');
    exit;
}

$userId = $_SESSION['user_id'];
$txPassword = trim($_POST['tx_password'] ?? '');
$confirmPassword = trim($_POST['confirm_password'] ?? '');

function backWithError(string $msg): void {
    header("Location: set_tx_password.php?error=" . urlencode($msg));
    exit;
}

// Validate
if (strlen($txPassword) !== 6 || !ctype_digit($txPassword)) {
    backWithError("Transaction password must be exactly 6 digits.");
}

if ($txPassword !== $confirmPassword) {
    backWithError("Passwords do not match.");
}

try {
    // Hash and save
    $hash = password_hash($txPassword, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("UPDATE Users SET TransactionPasswordHash = ? WHERE Id = ?");
    $stmt->execute([$hash, $userId]);
    
    // Log transaction password set
    logActivity($userId, 'tx_password_set', 'Transaction password created', 'success');
    
    header("Location: send_money.php?success=Transaction password set successfully");
    exit;
    
} catch (Throwable $e) {
    error_log("TX PASSWORD SET ERROR: " . $e->getMessage());
    backWithError("Error setting password: " . $e->getMessage());
}
