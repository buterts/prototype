<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Agricultural Marketplace</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>public/css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-form wide-form">
            <h1>Register</h1>

            <?php if (isset($_SESSION['register_error'])): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($_SESSION['register_error']); ?>
                    <?php unset($_SESSION['register_error']); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo BASE_URL; ?>auth/register.php">
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name:</label>
                        <input type="text" id="first_name" name="first_name" required>
                    </div>

                    <div class="form-group">
                        <label for="last_name">Last Name:</label>
                        <input type="text" id="last_name" name="last_name" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="phone">Phone (Optional):</label>
                        <input type="tel" id="phone" name="phone">
                    </div>

                    <div class="form-group">
                        <label for="location">Location (Optional):</label>
                        <input type="text" id="location" name="location">
                    </div>
                </div>

                <div class="form-group">
                    <label for="role">Role:</label>
                    <select id="role" name="role" required onchange="toggleRoleFields()">
                        <option value="">Select a role</option>
                        <option value="<?php echo ROLE_FARMER; ?>">Farmer</option>
                        <option value="<?php echo ROLE_CONSUMER; ?>">Consumer</option>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Password:</label>
                        <input type="password" id="password" name="password" required>
                        <small>At least 8 characters with uppercase, lowercase, and numbers</small>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm Password:</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Register</button>
                </div>
            </form>

            <p class="auth-link">
                Already have an account? <a href="<?php echo BASE_URL; ?>index.php">Login here</a>
            </p>
        </div>
    </div>

    <script>
        function toggleRoleFields() {
            const role = document.getElementById('role').value;
            // Can be extended to show role-specific fields
        }
    </script>
</body>
</html>
