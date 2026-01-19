<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Agricultural Marketplace</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>public/css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-form">
            <h1>Login</h1>
            
            <?php if (isset($_SESSION['login_error'])): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($_SESSION['login_error']); ?>
                    <?php unset($_SESSION['login_error']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['register_success'])): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($_SESSION['register_success']); ?>
                    <?php unset($_SESSION['register_success']); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo BASE_URL; ?>auth/login.php">
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Login</button>
                </div>
            </form>

            <p class="auth-link">
                Don't have an account? <a href="<?php echo BASE_URL; ?>register.php">Register here</a>
            </p>
        </div>
    </div>
</body>
</html>
