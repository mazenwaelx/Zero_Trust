<?php
session_start();
require 'auth.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Set Transaction Password</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen flex items-center justify-center bg-gray-100" style="font-family:Inter">

<div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-md">
    <h2 class="text-2xl font-bold mb-6 text-gray-800">Set Transaction Password</h2>
    <p class="text-sm text-gray-600 mb-4">Create a 6-digit password for money transfers</p>

    <?php if (!empty($_GET['error'])): ?>
        <div class="p-3 bg-red-100 text-red-700 rounded-xl mb-4">
            <?= htmlspecialchars($_GET['error']) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="set_tx_password_action.php" class="space-y-4">
        <div>
            <label class="block text-sm text-gray-700 mb-1">6-Digit Password</label>
            <input type="password" name="tx_password" maxlength="6" minlength="6" 
                   pattern="[0-9]{6}" class="w-full p-3 border rounded-xl" required>
        </div>

        <div>
            <label class="block text-sm text-gray-700 mb-1">Confirm Password</label>
            <input type="password" name="confirm_password" maxlength="6" minlength="6" 
                   pattern="[0-9]{6}" class="w-full p-3 border rounded-xl" required>
        </div>

        <button class="w-full bg-blue-500 hover:bg-blue-600 p-3 text-white rounded-xl font-semibold">
            Save Password
        </button>
    </form>

    <a href="dashboard.php" class="block mt-4 text-center p-3 bg-gray-100 rounded-xl font-semibold">Back to Dashboard</a>
</div>

</body>
</html>
