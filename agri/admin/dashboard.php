<?php
require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../app/middleware/AuthMiddleware.php';

// Require admin role
AuthMiddleware::requireRole(ROLE_ADMIN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Agricultural Marketplace</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>public/css/style.css">
</head>
<body>
    <div class="dashboard-header">
        <h1>Admin Dashboard</h1>
        <div class="user-menu">
            <span><?php echo htmlspecialchars($_SESSION['first_name']); ?></span>
            <a href="<?php echo BASE_URL; ?>auth/logout.php" class="btn logout-btn">Logout</a>
        </div>
    </div>

    <div style="padding: 40px; max-width: 1200px; margin: 0 auto;">
        <h2>System Administration</h2>

        <div style="background: #f9f9f9; padding: 20px; border-radius: 5px; margin-top: 20px;">
            <h3>Admin Functions</h3>
            <ul style="list-style: none;">
                <li><a href="#" style="color: #667eea;">👥 Manage Users</a></li>
                <li><a href="#" style="color: #667eea;">🔑 Manage Roles</a></li>
                <li><a href="#" style="color: #667eea;">📊 System Reports</a></li>
                <li><a href="#" style="color: #667eea;">⚙️ System Settings</a></li>
                <li><a href="#" style="color: #667eea;">🔍 Audit Logs</a></li>
            </ul>
        </div>
    </div>
</body>
</html>
