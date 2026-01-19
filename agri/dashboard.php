<?php
require_once __DIR__ . '/config/bootstrap.php';

// `config/bootstrap.php` initializes the database and creates `$authService`.

// Redirect to login if not authenticated
if (!$authService->isAuthenticated()) {
    header("Location: " . BASE_URL . "index.php");
    exit;
}

$currentUser = $authService->getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Agricultural Marketplace</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>public/css/style.css">
</head>
<body>
    <div class="dashboard-header">
        <h1>Welcome, <?php echo htmlspecialchars($currentUser['first_name']); ?>!</h1>
        <div class="user-menu">
            <span>Role: <?php echo htmlspecialchars($currentUser['role_name']); ?></span>
            <a href="<?php echo BASE_URL; ?>auth/logout.php" class="btn logout-btn">Logout</a>
        </div>
    </div>

    <div style="padding: 40px; max-width: 1200px; margin: 0 auto;">
        <h2>Dashboard</h2>
        <p>You are logged in as <?php echo htmlspecialchars($currentUser['email']); ?></p>

        <?php if ($authService->hasRole(ROLE_FARMER)): ?>
            <div style="background: #f9f9f9; padding: 20px; border-radius: 5px; margin-top: 20px;">
                <h3>Farmer Dashboard</h3>
                <p>Welcome to the Farmer Dashboard. You can manage your products and farm information here.</p>
                <a href="<?php echo BASE_URL; ?>farmer/dashboard.php" style="color: #667eea;">Go to Farmer Dashboard →</a>
            </div>
        <?php endif; ?>

        <?php if ($authService->hasRole(ROLE_CONSUMER)): ?>
            <div style="background: #f9f9f9; padding: 20px; border-radius: 5px; margin-top: 20px;">
                <h3>Consumer Dashboard</h3>
                <p>Browse available products from local farmers and place orders.</p>
                <a href="<?php echo BASE_URL; ?>consumer/dashboard.php" style="color: #667eea;">Go to Consumer Dashboard →</a>
            </div>
        <?php endif; ?>

        <?php if ($authService->hasRole(ROLE_ADMIN)): ?>
            <div style="background: #f9f9f9; padding: 20px; border-radius: 5px; margin-top: 20px;">
                <h3>Admin Dashboard</h3>
                <p>Manage users, roles, and system settings.</p>
                <a href="<?php echo BASE_URL; ?>admin/dashboard.php" style="color: #667eea;">Go to Admin Dashboard →</a>
            </div>
        <?php endif; ?>

        <div style="background: #f9f9f9; padding: 20px; border-radius: 5px; margin-top: 20px;">
            <h3>User Information</h3>
            <ul>
                <li><strong>Email:</strong> <?php echo htmlspecialchars($currentUser['email']); ?></li>
                <li><strong>Full Name:</strong> <?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?></li>
                <li><strong>Role:</strong> <?php echo htmlspecialchars($currentUser['role_name']); ?></li>
                <li><strong>Phone:</strong> <?php echo htmlspecialchars($currentUser['phone'] ?: 'Not provided'); ?></li>
                <li><strong>Member Since:</strong> <?php echo date('F j, Y', strtotime($currentUser['created_at'])); ?></li>
            </ul>
        </div>
    </div>
</body>
</html>
