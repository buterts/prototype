<?php
require_once __DIR__ . '/../config/bootstrap.php';
$db = $GLOBALS['conn'] ?? null;
$conn = $db;

// Require consumer role
AuthMiddleware::requireRole(ROLE_CONSUMER);
$userModel = new User($conn);
$cartModel = new ShoppingCart($conn);
$orderModel = new Order($conn);

$currentUser = $userModel->findById($_SESSION['user_id']);
$consumer_id = $_SESSION['user_id'];

// Get cart item count
$cartCount = $cartModel->getCartItemCount($consumer_id);

// Get recent orders
$ordersQuery = "SELECT o.*, u.first_name, u.last_name FROM orders o 
               JOIN users u ON o.farmer_id = u.id
               WHERE o.consumer_id = $consumer_id 
               ORDER BY o.created_at DESC 
               LIMIT 5";
$ordersResult = $conn->query($ordersQuery);
$recentOrders = [];
if ($ordersResult && $ordersResult->num_rows > 0) {
    while ($row = $ordersResult->fetch_assoc()) {
        $recentOrders[] = $row;
    }
}

// Get total orders and spending
$statsQuery = "SELECT COUNT(*) as total_orders, SUM(total_amount) as total_spent FROM orders WHERE consumer_id = $consumer_id";
$statsResult = $conn->query($statsQuery);
$stats = $statsResult ? $statsResult->fetch_assoc() : ['total_orders' => 0, 'total_spent' => 0];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consumer Dashboard - Agricultural Marketplace</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>public/css/style.css">
</head>
<body>
    <div class="dashboard-header">
        <h1>👋 Welcome, <?php echo htmlspecialchars($currentUser['first_name']); ?></h1>
        <div class="user-menu">
            <a href="<?php echo BASE_URL; ?>consumer/products/browse.php">Shop</a>
            <a href="<?php echo BASE_URL; ?>consumer/cart/view.php" style="position: relative;">
                Cart
                <?php if ($cartCount > 0): ?>
                    <span class="cart-badge"><?php echo $cartCount; ?></span>
                <?php endif; ?>
            </a>
            <a href="<?php echo BASE_URL; ?>auth/logout.php" class="btn logout-btn">Logout</a>
        </div>
    </div>

    <div style="padding: 40px; max-width: 1200px; margin: 0 auto;">
        <h2>Dashboard</h2>
        <p>Manage your shopping and orders in one place.</p>

        <!-- Statistics -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 30px 0;">
            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center;">
                <div style="font-size: 36px; color: #667eea; font-weight: bold;"><?php echo $stats['total_orders'] ?? 0; ?></div>
                <p style="margin: 10px 0 0 0; color: #999;">Total Orders</p>
            </div>
            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center;">
                <div style="font-size: 36px; color: #28a745; font-weight: bold;">$<?php echo number_format($stats['total_spent'] ?? 0, 2); ?></div>
                <p style="margin: 10px 0 0 0; color: #999;">Total Spent</p>
            </div>
            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center;">
                <div style="font-size: 36px; color: #ffc107; font-weight: bold;"><?php echo $cartCount; ?></div>
                <p style="margin: 10px 0 0 0; color: #999;">Items in Cart</p>
            </div>
        </div>

        <!-- Quick Actions -->
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin: 30px 0;">
            <h3 style="margin-top: 0;">Quick Actions</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <a href="<?php echo BASE_URL; ?>consumer/products/browse.php" style="display: block; padding: 15px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 4px; text-align: center; font-weight: bold;">🔍 Browse Products</a>
                <a href="<?php echo BASE_URL; ?>consumer/cart/view.php" style="display: block; padding: 15px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 4px; text-align: center; font-weight: bold;">🛒 View Cart</a>
                <a href="<?php echo BASE_URL; ?>consumer/orders/list.php" style="display: block; padding: 15px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 4px; text-align: center; font-weight: bold;">📦 My Orders</a>
            </div>
        </div>

        <!-- Recent Orders -->
        <?php if (count($recentOrders) > 0): ?>
            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin: 30px 0;">
                <h3 style="margin-top: 0;">Recent Orders</h3>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 2px solid #eee;">
                            <th style="padding: 10px; text-align: left;">Order #</th>
                            <th style="padding: 10px; text-align: left;">Farmer</th>
                            <th style="padding: 10px; text-align: left;">Amount</th>
                            <th style="padding: 10px; text-align: left;">Status</th>
                            <th style="padding: 10px; text-align: left;">Date</th>
                            <th style="padding: 10px; text-align: left;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentOrders as $order): ?>
                            <tr style="border-bottom: 1px solid #eee;">
                                <td style="padding: 10px;"><?php echo htmlspecialchars($order['order_number']); ?></td>
                                <td style="padding: 10px;"><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></td>
                                <td style="padding: 10px;">$<?php echo number_format($order['total_amount'], 2); ?></td>
                                <td style="padding: 10px;">
                                    <span style="display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: bold; color: white; background: <?php 
                                        echo match($order['status']) {
                                            'Pending' => '#ffc107',
                                            'Confirmed' => '#17a2b8',
                                            'Processing' => '#0069d9',
                                            'Shipped' => '#28a745',
                                            'Delivered' => '#28a745',
                                            'Cancelled' => '#dc3545',
                                            default => '#666'
                                        };
                                    ?>;">
                                        <?php echo htmlspecialchars($order['status']); ?>
                                    </span>
                                </td>
                                <td style="padding: 10px;"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                <td style="padding: 10px;">
                                    <a href="<?php echo BASE_URL; ?>consumer/orders/view.php?id=<?php echo $order['id']; ?>" style="color: #667eea; text-decoration: none;">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- Profile Section -->
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin: 30px 0;">
            <h3 style="margin-top: 0;">Your Profile</h3>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($currentUser['email']); ?></p>
            <p><strong>Name:</strong> <?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?></p>
            <a href="<?php echo BASE_URL; ?>consumer/edit-profile.php" style="display: inline-block; margin-top: 15px; background: #667eea; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none;">Edit Profile</a>
        </div>
    </div>
</body>
</html>
