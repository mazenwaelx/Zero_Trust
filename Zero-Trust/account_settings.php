<?php require 'auth.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings - Secure Wallet</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen flex items-center justify-center bg-gray-100" style="font-family:Inter">

<div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-md">
    <h2 class="text-2xl font-bold mb-6 text-gray-800">Account Settings</h2>
    <p class="text-sm text-gray-600 mb-4">Changes require OTP verification.</p>

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

    <form method="POST" action="account_settings_action.php" class="space-y-4">
        <div>
            <label class="block text-sm text-gray-700 mb-1">New Username (optional)</label>
            <input type="text" name="new_username" class="w-full p-3 border rounded-xl">
        </div>

        <div>
            <label class="block text-sm text-gray-700 mb-1">New Password (optional)</label>
            <input type="password" name="new_password" class="w-full p-3 border rounded-xl">
        </div>

        <div>
            <label class="block text-sm text-gray-700 mb-1">Current Password (required)</label>
            <input type="password" name="current_password" class="w-full p-3 border rounded-xl" required>
        </div>

        <button class="w-full bg-blue-500 hover:bg-blue-600 text-white p-3 rounded-xl font-semibold">
            Send OTP to Confirm
        </button>
    </form>

    <a href="dashboard.php" class="block mt-4 text-center p-3 bg-gray-100 rounded-xl font-semibold">Back to Dashboard</a>
</div>

<!-- Inactivity Timer -->
<script src="inactivity_timer.js"></script>

</body>
</html>
