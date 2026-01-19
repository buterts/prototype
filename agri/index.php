<?php
require_once __DIR__ . '/config/init.php';
require_once __DIR__ . '/app/models/User.php';
require_once __DIR__ . '/app/models/FarmerProfile.php';
require_once __DIR__ . '/app/models/ConsumerProfile.php';
require_once __DIR__ . '/app/services/AuthService.php';
require_once __DIR__ . '/app/middleware/AuthMiddleware.php';

// Redirect to dashboard if already authenticated
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agricultural Marketplace</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>public/css/style.css">
</head>
<body>
    <div style="min-height: 100vh; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center;">
        <div style="text-align: center; color: white; padding: 40px;">
            <h1 style="font-size: 48px; margin-bottom: 20px;">Agricultural Marketplace</h1>
            <p style="font-size: 18px; margin-bottom: 30px;">Connect Farmers and Consumers</p>
            
            <div style="display: flex; gap: 20px; justify-content: center;">
                <a href="<?php echo BASE_URL; ?>auth/login.php" style="background: white; color: #667eea; padding: 15px 40px; border-radius: 5px; text-decoration: none; font-weight: 600; transition: transform 0.3s;">
                    Login
                </a>
                <a href="<?php echo BASE_URL; ?>auth/register.php" style="background: rgba(255,255,255,0.2); color: white; padding: 15px 40px; border-radius: 5px; text-decoration: none; font-weight: 600; border: 2px solid white; transition: background-color 0.3s;">
                    Register
                </a>
            </div>
        </div>
    </div>
</body>
</html>
