<?php
session_start();
require 'auth.php';
require 'db.php';

$userId = $_SESSION['user_id'];

// Check if user has set transaction password
$stmt = $pdo->prepare("SELECT TransactionPasswordHash, Email FROM Users WHERE Id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (empty($user['TransactionPasswordHash'])) {
    header('Location: set_tx_password.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Money - Secure Wallet</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family:'Inter', sans-serif; background:#f3f4f6; }
        .container { max-width:480px; margin:auto; }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center">

<div class="container bg-white p-8 rounded-2xl shadow-xl">
    <h2 class="text-2xl font-bold mb-6 text-gray-800">Send Money</h2>

    <?php if (!empty($_GET['error'])): ?>
        <div class="p-3 bg-red-100 text-red-700 rounded-xl mb-4">
            <?= htmlspecialchars($_GET['error']) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($_GET['success'])): ?>
        <div class="p-3 bg-green-100 text-green-700 rounded-xl mb-4">
            <?= htmlspecialchars($_GET['success']) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="send_money_action.php" class="space-y-4">
        <div>
            <label class="block text-sm text-gray-700 mb-1">Recipient Email</label>
            <input type="email" name="recipient_email" class="w-full p-3 border rounded-xl" required>
        </div>

        <div>
            <label class="block text-sm text-gray-700 mb-1">Amount (EGP)</label>
            <input type="number" name="amount" min="1" step="0.01" class="w-full p-3 border rounded-xl" required>
        </div>

        <div>
            <label class="block text-sm text-gray-700 mb-1">6-Digit Transaction Password</label>
            <input type="password" name="tx_password" maxlength="6" pattern="[0-9]{6}" class="w-full p-3 border rounded-xl" required>
        </div>

        <button class="w-full bg-green-500 hover:bg-green-600 p-3 text-white rounded-xl font-semibold">
            Send OTP to Confirm
        </button>
    </form>

    <a href="dashboard.php" class="block mt-4 text-center p-3 bg-gray-100 rounded-xl font-semibold">Back to Dashboard</a>
</div>

</body>
</html>
