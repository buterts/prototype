<?php
require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../app/models/Order.php';
require_once __DIR__ . '/../../app/models/OrderItem.php';
require_once __DIR__ . '/../../app/models/Product.php';
require_once __DIR__ . '/../../app/services/OrderService.php';
require_once __DIR__ . '/../../app/services/OrderManagementService.php';
require_once __DIR__ . '/../../app/controllers/OrderController.php';
require_once __DIR__ . '/../../app/middleware/AuthMiddleware.php';

// Require farmer role
AuthMiddleware::requireRole(ROLE_FARMER);

require_once __DIR__ . '/../../config/bootstrap.php';
$db = $GLOBALS['conn'] ?? null;

$orderModel = new Order($db);
$orderItemModel = new OrderItem($db);
$productModel = new Product($db);

$orderService = new OrderService($orderModel, $orderItemModel, $db);
$orderManagementService = new OrderManagementService($db, $orderModel, $orderItemModel, $productModel);
$orderController = new OrderController($orderService);

$farmer_id = $_SESSION['user_id'];
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : null;
$payment_filter = isset($_GET['payment']) ? $_GET['payment'] : null;

// Get order statistics for dashboard cards
$stats = $orderManagementService->getOrderStatistics($farmer_id);

// Get filtered orders
$filters = [];
if ($status_filter && $status_filter !== 'all') {
    $filters['status'] = $status_filter;
}
if ($payment_filter && $payment_filter !== 'all') {
    $filters['payment_status'] = $payment_filter;
}

$filteredOrders = $orderManagementService->getFilteredOrders($farmer_id, $filters, 10, ($page - 1) * 10);

$result = $orderController->listOrders($farmer_id, $page);
$pagination = $result['pagination'];
$orders = $result['data'];

// Apply manual filtering if needed for UI
if ($status_filter && $status_filter !== 'all') {
    $orders = array_filter($orders, function($o) use ($status_filter) {
        return $o['status'] === $status_filter;
    });
}
if ($payment_filter && $payment_filter !== 'all') {
    $orders = array_filter($orders, function($o) use ($payment_filter) {
        return $o['payment_status'] === $payment_filter;
    });
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders - Farmer Dashboard</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>public/css/style.css">
</head>
<body>
    <div class="dashboard-header">
        <h1>Incoming Orders</h1>
        <div class="user-menu">
            <a href="<?php echo BASE_URL; ?>farmer/dashboard.php">Dashboard</a>
            <a href="<?php echo BASE_URL; ?>auth/logout.php" class="btn logout-btn">Logout</a>
        </div>
    </div>

    <div style="padding: 40px; max-width: 1400px; margin: 0 auto;">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($_SESSION['success']); ?>
                <?php unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($_SESSION['error']); ?>
                <?php unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <h2>Order Management</h2>

        <!-- Statistics Cards -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 30px 0;">
            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 4px solid #667eea;">
                <p style="margin: 0 0 5px 0; color: #999; font-size: 12px;">Total Orders</p>
                <p style="margin: 0; font-size: 28px; font-weight: bold; color: #667eea;">
                    <?php echo $stats['total_orders'] ?? 0; ?>
                </p>
            </div>
            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 4px solid #ffc107;">
                <p style="margin: 0 0 5px 0; color: #999; font-size: 12px;">Pending</p>
                <p style="margin: 0; font-size: 28px; font-weight: bold; color: #ffc107;">
                    <?php echo $stats['pending_orders'] ?? 0; ?>
                </p>
            </div>
            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 4px solid #17a2b8;">
                <p style="margin: 0 0 5px 0; color: #999; font-size: 12px;">Confirmed</p>
                <p style="margin: 0; font-size: 28px; font-weight: bold; color: #17a2b8;">
                    <?php echo $stats['confirmed_orders'] ?? 0; ?>
                </p>
            </div>
            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 4px solid #28a745;">
                <p style="margin: 0 0 5px 0; color: #999; font-size: 12px;">Completed</p>
                <p style="margin: 0; font-size: 28px; font-weight: bold; color: #28a745;">
                    <?php echo $stats['completed_orders'] ?? 0; ?>
                </p>
            </div>
            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 4px solid #667eea;">
                <p style="margin: 0 0 5px 0; color: #999; font-size: 12px;">Total Revenue</p>
                <p style="margin: 0; font-size: 28px; font-weight: bold; color: #667eea;">
                    $<?php echo number_format($stats['total_revenue'] ?? 0, 2); ?>
                </p>
            </div>
        </div>

        <!-- Filters -->
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin: 20px 0;">
            <h3 style="margin-top: 0;">Filters</h3>
            <form method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <div>
                    <label for="status">Order Status:</label>
                    <select name="status" id="status">
                        <option value="all" <?php echo (!$status_filter || $status_filter === 'all') ? 'selected' : ''; ?>>All Statuses</option>
                        <option value="Pending" <?php echo $status_filter === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="Confirmed" <?php echo $status_filter === 'Confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                        <option value="Completed" <?php echo $status_filter === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="Cancelled" <?php echo $status_filter === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <div>
                    <label for="payment">Payment Status:</label>
                    <select name="payment" id="payment">
                        <option value="all" <?php echo (!$payment_filter || $payment_filter === 'all') ? 'selected' : ''; ?>>All Payments</option>
                        <option value="Pending" <?php echo $payment_filter === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="Paid" <?php echo $payment_filter === 'Paid' ? 'selected' : ''; ?>>Paid</option>
                        <option value="Failed" <?php echo $payment_filter === 'Failed' ? 'selected' : ''; ?>>Failed</option>
                    </select>
                </div>
                <div style="display: flex; align-items: flex-end; gap: 10px;">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="<?php echo BASE_URL; ?>farmer/orders/list.php" class="btn" style="background: #6c757d; color: white; text-decoration: none;">Clear</a>
                </div>
            </form>
        </div>

        <h3>Orders (<?php echo count($orders); ?>)</h3>

        <?php if (count($orders) > 0): ?>
            <div style="background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); overflow: hidden; margin-top: 20px;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f5f5f5; border-bottom: 2px solid #ddd;">
                            <th style="text-align: left; padding: 15px;">Order #</th>
                            <th style="text-align: left; padding: 15px;">Customer</th>
                            <th style="text-align: right; padding: 15px;">Amount</th>
                            <th style="text-align: center; padding: 15px;">Order Status</th>
                            <th style="text-align: center; padding: 15px;">Payment</th>
                            <th style="text-align: center; padding: 15px;">Date</th>
                            <th style="text-align: center; padding: 15px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr style="border-bottom: 1px solid #eee;">
                                <td style="padding: 15px;">
                                    <strong><?php echo htmlspecialchars($order['order_number']); ?></strong>
                                </td>
                                <td style="padding: 15px;">
                                    <strong><?php echo htmlspecialchars($order['consumer_first_name'] . ' ' . $order['consumer_last_name']); ?></strong>
                                    <br>
                                    <small style="color: #999;"><?php echo htmlspecialchars($order['consumer_email']); ?></small>
                                </td>
                                <td style="padding: 15px; text-align: right; font-weight: bold;">
                                    $<?php echo number_format($order['total_amount'], 2); ?>
                                </td>
                                <td style="padding: 15px; text-align: center;">
                                    <span style="padding: 5px 10px; border-radius: 3px; font-size: 12px; font-weight: bold;
                                        <?php
                                        $status_colors = [
                                            'pending' => 'background: #ffc107; color: white;',
                                            'confirmed' => 'background: #17a2b8; color: white;',
                                            'processing' => 'background: #007bff; color: white;',
                                            'ready_for_pickup' => 'background: #28a745; color: white;',
                                            'completed' => 'background: #6c757d; color: white;',
                                            'cancelled' => 'background: #dc3545; color: white;'
                                        ];
                                        echo $status_colors[$order['status']] ?? '';
                                        ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                                    </span>
                                </td>
                                <td style="padding: 15px; text-align: center;">
                                    <span style="padding: 5px 10px; border-radius: 3px; font-size: 12px; font-weight: bold;
                                        <?php
                                        $payment_colors = [
                                            'unpaid' => 'background: #dc3545; color: white;',
                                            'paid' => 'background: #28a745; color: white;',
                                            'refunded' => 'background: #6c757d; color: white;'
                                        ];
                                        echo $payment_colors[$order['payment_status']] ?? '';
                                        ?>">
                                        <?php echo ucfirst($order['payment_status']); ?>
                                    </span>
                                </td>
                                <td style="padding: 15px; text-align: center;">
                                    <?php echo date('M d, Y', strtotime($order['created_at'])); ?>
                                </td>
                                <td style="padding: 15px; text-align: center;">
                                    <a href="<?php echo BASE_URL; ?>farmer/orders/view.php?id=<?php echo $order['id']; ?>" style="color: #667eea; text-decoration: none;">View Details</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($pagination['total_pages'] > 1): ?>
                <div style="text-align: center; margin-top: 30px;">
                    <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                        <a href="?page=<?php echo $i; ?>" style="
                            display: inline-block;
                            padding: 10px 15px;
                            margin: 0 5px;
                            background: <?php echo $i === $page ? '#667eea' : '#f0f0f0'; ?>;
                            color: <?php echo $i === $page ? 'white' : '#333'; ?>;
                            text-decoration: none;
                            border-radius: 3px;
                        ">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div style="background: #f9f9f9; padding: 30px; text-align: center; border-radius: 8px; margin-top: 20px;">
                <p style="color: #999; font-size: 16px;">No orders yet.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
