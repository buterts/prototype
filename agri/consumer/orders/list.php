<?php
require_once __DIR__ . '/../../config/bootstrap.php';
$db = $GLOBALS['conn'] ?? null;
$conn = $db;

AuthMiddleware::requireRole(ROLE_CONSUMER);
$consumer_id = $_SESSION['user_id'];

// Get consumer's orders
$query = "SELECT o.*, COUNT(oi.id) as item_count 
          FROM orders o 
          LEFT JOIN order_items oi ON o.id = oi.order_id
          WHERE o.consumer_id = $consumer_id 
          GROUP BY o.id
          ORDER BY o.created_at DESC";

$result = $conn->query($query);
$orders = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
}

// Status colors
$statusColors = [
    'Pending' => '#ffc107',
    'Confirmed' => '#17a2b8',
    'Processing' => '#0069d9',
    'Shipped' => '#28a745',
    'Delivered' => '#28a745',
    'Cancelled' => '#dc3545'
];

$paymentColors = [
    'Pending' => '#ffc107',
    'Paid' => '#28a745',
    'Failed' => '#dc3545'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>public/css/style.css">
    <style>
        .orders-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px;
        }

        .filters {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .filters select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
        }

        .order-card {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transition: box-shadow 0.3s ease;
        }

        .order-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .order-header {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 150px;
            gap: 20px;
            align-items: start;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .order-info {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .order-info label {
            font-size: 12px;
            color: #999;
            text-transform: uppercase;
        }

        .order-info p {
            margin: 0;
            font-weight: bold;
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            color: white;
        }

        .order-items {
            margin-top: 15px;
        }

        .order-item {
            display: grid;
            grid-template-columns: 2fr 100px 100px 100px;
            gap: 15px;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
            align-items: center;
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .order-item-name {
            font-weight: 500;
        }

        .order-item-qty {
            text-align: center;
            color: #666;
            font-size: 14px;
        }

        .order-item-price {
            text-align: right;
            font-weight: bold;
        }

        .order-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        .order-total {
            font-size: 16px;
            font-weight: bold;
        }

        .order-actions {
            display: flex;
            gap: 10px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 40px;
            background: #f9f9f9;
            border-radius: 8px;
        }

        @media (max-width: 768px) {
            .order-header {
                grid-template-columns: 1fr;
            }

            .order-item {
                grid-template-columns: 1fr;
            }

            .order-item-qty,
            .order-item-price {
                text-align: left;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-header">
        <h1>📦 My Orders</h1>
        <div class="user-menu">
            <a href="<?php echo BASE_URL; ?>consumer/products/browse.php">Shop</a>
            <a href="<?php echo BASE_URL; ?>consumer/cart/view.php">Cart</a>
            <a href="<?php echo BASE_URL; ?>consumer/dashboard.php">Dashboard</a>
            <a href="<?php echo BASE_URL; ?>auth/logout.php" class="btn logout-btn">Logout</a>
        </div>
    </div>

    <div class="orders-container">
        <?php if (count($orders) > 0): ?>
            <p style="color: #666; margin-bottom: 20px;">You have <?php echo count($orders); ?> order(s)</p>

            <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div class="order-info">
                            <label>Order #</label>
                            <p><?php echo htmlspecialchars($order['order_number']); ?></p>
                        </div>
                        <div class="order-info">
                            <label>Date</label>
                            <p><?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></p>
                        </div>
                        <div class="order-info">
                            <label>Total</label>
                            <p>$<?php echo number_format($order['total_amount'], 2); ?></p>
                        </div>
                        <div class="order-info">
                            <label>Status</label>
                            <span class="badge" style="background: <?php echo $statusColors[$order['status']] ?? '#666'; ?>;">
                                <?php echo htmlspecialchars($order['status']); ?>
                            </span>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 15px 0;">
                        <div class="order-info">
                            <label>Fulfillment</label>
                            <p><?php echo htmlspecialchars($order['fulfillment_type']); ?></p>
                        </div>
                        <div class="order-info">
                            <label>Payment Status</label>
                            <span class="badge" style="background: <?php echo $paymentColors[$order['payment_status']] ?? '#666'; ?>;">
                                <?php echo htmlspecialchars($order['payment_status']); ?>
                            </span>
                        </div>
                    </div>

                    <?php if ($order['fulfillment_type'] === 'Delivery' && !empty($order['delivery_address'])): ?>
                        <div style="background: #f9f9f9; padding: 10px; border-radius: 4px; margin: 10px 0; font-size: 14px;">
                            <strong>Delivery Address:</strong><br>
                            <?php echo nl2br(htmlspecialchars($order['delivery_address'])); ?>
                        </div>
                    <?php elseif ($order['fulfillment_type'] === 'Pickup' && !empty($order['pickup_date'])): ?>
                        <div style="background: #f9f9f9; padding: 10px; border-radius: 4px; margin: 10px 0; font-size: 14px;">
                            <strong>Pickup Date:</strong> <?php echo date('M d, Y', strtotime($order['pickup_date'])); ?>
                        </div>
                    <?php endif; ?>

                    <div class="order-items">
                        <p style="margin: 0 0 10px 0; font-weight: bold; color: #666; font-size: 14px;">Items (<?php echo $order['item_count']; ?>)</p>
                        <?php
                        // Get items for this order
                        $items_query = "SELECT oi.*, p.name, p.unit FROM order_items oi 
                                       JOIN products p ON oi.product_id = p.id 
                                       WHERE oi.order_id = {$order['id']}";
                        $items_result = $conn->query($items_query);
                        if ($items_result && $items_result->num_rows > 0) {
                            while ($item = $items_result->fetch_assoc()) {
                                echo '<div class="order-item">';
                                echo '<span class="order-item-name">' . htmlspecialchars($item['name']) . ' (' . htmlspecialchars($item['unit']) . ')</span>';
                                echo '<span class="order-item-qty">Qty: ' . $item['quantity'] . '</span>';
                                if (isset($item['price'])) {
                                    echo '<span class="order-item-price">$' . number_format($item['price'], 2) . '</span>';
                                    echo '<span class="order-item-price">$' . number_format($item['quantity'] * $item['price'], 2) . '</span>';
                                }
                                echo '</div>';
                            }
                        }
                        ?>
                    </div>

                    <div class="order-footer">
                        <div class="order-total">
                            Total: $<?php echo number_format($order['total_amount'], 2); ?>
                        </div>
                        <div class="order-actions">
                            <a href="<?php echo BASE_URL; ?>consumer/orders/view.php?id=<?php echo $order['id']; ?>" class="btn" style="background: #0069d9; color: white; text-decoration: none; padding: 8px 16px; border-radius: 4px;">
                                View Details
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <p style="color: #999; font-size: 16px; margin-bottom: 20px;">You haven't placed any orders yet.</p>
                <a href="<?php echo BASE_URL; ?>consumer/products/browse.php" class="btn btn-primary">Start Shopping</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
