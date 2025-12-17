<?php
session_start();

// Redirect to dashboard if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Hosting - Login/Register</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <div class="form-container">
            <div class="tabs">
                <button class="tab-button active" onclick="switchTab('login')">Login</button>
                <button class="tab-button" onclick="switchTab('register')">Register</button>
            </div>

            <!-- Login Form -->
            <div id="login" class="tab-content active">
                <h2>Login to Your Account</h2>
                <form method="POST" action="auth/login.php">
                    <div class="form-group">
                        <label for="login-email">Email:</label>
                        <input type="email" id="login-email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="login-password">Password:</label>
                        <input type="password" id="login-password" name="password" required>
                    </div>
                    <button type="submit" class="btn">Login</button>
                </form>
            </div>

            <!-- Register Form -->
            <div id="register" class="tab-content">
                <h2>Create New Account</h2>
                <form method="POST" action="auth/register.php">
                    <div class="form-group">
                        <label for="register-username">Username:</label>
                        <input type="text" id="register-username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="register-email">Email:</label>
                        <input type="email" id="register-email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="register-password">Password:</label>
                        <input type="password" id="register-password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label for="register-confirm">Confirm Password:</label>
                        <input type="password" id="register-confirm" name="confirm_password" required>
                    </div>
                    <button type="submit" class="btn">Register</button>
                </form>
            </div>

            <?php
            if (isset($_GET['error'])) {
                echo '<div class="error-message">' . htmlspecialchars($_GET['error']) . '</div>';
            }
            if (isset($_GET['success'])) {
                echo '<div class="success-message">' . htmlspecialchars($_GET['success']) . '</div>';
            }
            ?>
        </div>
    </div>

    <script src="js/script.js"></script>
</body>
</html>
