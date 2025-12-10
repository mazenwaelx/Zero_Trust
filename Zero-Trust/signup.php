<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZeroTrustBank - Signup</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background:#f3f4f6; font-family:Inter,sans-serif; }
        .app-container {
            max-width:480px; margin:5vh auto;
            background:white; padding:2rem;
            border-radius:1.5rem; box-shadow:0 10px 25px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>

<div class="app-container">

    <header class="text-center mb-8">
        <div class="text-blue-600 text-3xl font-bold">ZeroTrustBank</div>
        <p class="text-gray-500 text-sm">Create Your Account</p>
    </header>

      <form id="signupForm" class="space-y-4" method="POST" action="signup_action.php">
        <!-- EMAIL -->
        <div>
            <label class="text-sm font-medium">Email</label>
            <input type="email" name="email" id="regEmail"
                   class="w-full p-3 border rounded-xl" required>
        </div>

        <!-- USERNAME -->
        <div>
            <label class="text-sm font-medium">Username (Letters Only)</label>
            <input type="text" name="username" id="regUsername"
                   class="w-full p-3 border rounded-xl"
                   pattern="^[A-Za-z ]+$"
                   title="Only letters allowed. No numbers or special characters."
                   required>
        </div>

        <!-- PHONE -->
        <div>
            <label class="text-sm font-medium">Phone (11 digits - Egypt)</label>
            <input type="text" name="phone" id="regPhone"
                   class="w-full p-3 border rounded-xl"
                   maxlength="11"
                   pattern="^(010|011|012|015)[0-9]{8}$"
                   title="Must be 11 digits and start with 010, 011, 012, or 015."
                   required>
        </div>

        <!-- PASSWORD -->
        <div>
            <label class="text-sm font-medium">
                Password (Strong – uppercase, number & special character)
            </label>
            <input type="password" name="password" id="regPassword"
                   class="w-full p-3 border rounded-xl"
                   minlength="8"
                   pattern="^(?=.*[A-Z])(?=.*[0-9])(?=.*[@#^&!%$?]).{8,}$"
                   title="Must contain uppercase, number, and special character (@,#,^,&,!,%,$,?)."
                   required>
        </div>

        <button class="w-full bg-blue-600 text-white p-3 rounded-xl font-semibold">
            Create Account
        </button>
    </form>

    <p class="text-center mt-4 text-sm">
        Already have an account?
        <a href="login.php" class="text-blue-600 font-medium">Login</a>
    </p>
</div>

<script>
    // show backend messages from PHP (via query string)
    const params = new URLSearchParams(window.location.search);
    const msgBox = document.getElementById('messageBox');
    if (params.get('error')) {
        msgBox.textContent = params.get('error');
        msgBox.className = "p-3 rounded-xl mb-4 text-red-700 bg-red-100";
    }
</script>

</div>

</body>
</html>
