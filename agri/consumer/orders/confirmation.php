<?php
require_once __DIR__ . '/../../config/bootstrap.php';

AuthMiddleware::requireRole(ROLE_CONSUMER);

$success = isset($_GET['success']) && $_GET['success'] === '1';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $success ? 'Order Confirmed' : 'Order Error'; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>public/css/style.css">
    <style>
        .confirmation-container {
            max-width: 600px;
            margin: 60px auto;
            padding: 40px;
            text-align: center;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .confirmation-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        .confirmation-message {
            color: #2e7d32;
            font-size: 18px;
            margin: 20px 0;
            line-height: 1.6;
        }

        .error-message {
            color: #c41c3b;
        }

        .confirmation-buttons {
            margin-top: 30px;
            display: flex;
            gap: 10px;
            justify-content: center;
        }
    </style>
</head>
<body>
    <div class="dashboard-header">
        <h1>🏡 Agricultural Marketplace</h1>
        <div class="user-menu">
            <a href="<?php echo BASE_URL; ?>consumer/dashboard.php">Dashboard</a>
            <a href="<?php echo BASE_URL; ?>auth/logout.php" class="btn logout-btn">Logout</a>
        </div>
    </div>

    <div class="confirmation-container">
        <?php if ($success): ?>
            <div class="confirmation-icon">✅</div>
            <h2 style="color: #2e7d32; margin: 0;">Order Placed Successfully!</h2>
            <div class="confirmation-message">
                <p>Thank you for your order! Your order has been placed and sent to the farmers.</p>
                <p>You can track your orders in your dashboard and they will be updated as farmers prepare and ship your items.</p>
            </div>

            <div class="confirmation-buttons">
                <a href="<?php echo BASE_URL; ?>consumer/orders/list.php" class="btn btn-primary">View My Orders</a>
                <a href="<?php echo BASE_URL; ?>consumer/products/browse.php" class="btn" style="background: #6c757d; color: white; text-decoration: none;">Continue Shopping</a>
            </div>
        <?php else: ?>
            <div class="confirmation-icon">❌</div>
            <h2 style="color: #c41c3b; margin: 0;">Order Error</h2>
            <div class="confirmation-message error-message">
                <p>There was an error processing your order. Please try again.</p>
            </div>

            <div class="confirmation-buttons">
                <a href="<?php echo BASE_URL; ?>consumer/cart/checkout.php" class="btn btn-primary">Back to Checkout</a>
                <a href="<?php echo BASE_URL; ?>consumer/cart/view.php" class="btn" style="background: #6c757d; color: white; text-decoration: none;">View Cart</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
