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

// Use OrderManagementService for enhanced functionality
$orderService = new OrderService($orderModel, $orderItemModel, $db);
$orderManagementService = new OrderManagementService($db, $orderModel, $orderItemModel, $productModel);
$orderController = new OrderController($orderService);

$farmer_id = $_SESSION['user_id'];
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$order_id) {
    header("Location: " . BASE_URL . "farmer/orders/list.php");
    exit;
}

$result = $orderController->showOrderDetails($farmer_id, $order_id);

if (!$result['success']) {
    $_SESSION['error'] = $result['message'];
    header("Location: " . BASE_URL . "farmer/orders/list.php");
    exit;
}

$order = $result['data']['order'];
$items = $result['data']['items'];

// Get order timeline for visual status progression
$timeline = $orderManagementService->getOrderTimeline($order_id);

// Get full order details with enhanced data
$orderDetails = $orderModel->getOrderDetails($order_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - Farmer Dashboard</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>public/css/style.css">
</head>
<body>
    <div class="dashboard-header">
        <h1>Order Details</h1>
        <div class="user-menu">
            <a href="<?php echo BASE_URL; ?>farmer/dashboard.php">Dashboard</a>
            <a href="<?php echo BASE_URL; ?>auth/logout.php" class="btn logout-btn">Logout</a>
        </div>
    </div>

    <div style="padding: 40px; max-width: 1000px; margin: 0 auto;">
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

        <!-- Order Header -->
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                <div>
                    <p style="margin: 0 0 5px 0; color: #999; font-weight: bold;">Order Number</p>
                    <p style="margin: 0; font-size: 18px; font-weight: bold;"><?php echo htmlspecialchars($order['order_number']); ?></p>
                </div>
                <div>
                    <p style="margin: 0 0 5px 0; color: #999; font-weight: bold;">Order Date</p>
                    <p style="margin: 0; font-size: 16px;"><?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></p>
                </div>
                <div>
                    <p style="margin: 0 0 5px 0; color: #999; font-weight: bold;">Total Amount</p>
                    <p style="margin: 0; font-size: 18px; font-weight: bold; color: #667eea;">$<?php echo number_format($order['total_amount'], 2); ?></p>
                </div>
            </div>
        </div>

        <!-- Customer Information -->
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px;">
            <h3>Customer Information</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 15px;">
                <div>
                    <p style="margin: 0 0 5px 0; color: #999; font-weight: bold;">Name</p>
                    <p style="margin: 0;">
                        <?php echo htmlspecialchars($order['consumer_first_name'] . ' ' . $order['consumer_last_name']); ?>
                    </p>
                </div>
                <div>
                    <p style="margin: 0 0 5px 0; color: #999; font-weight: bold;">Email</p>
                    <p style="margin: 0;"><?php echo htmlspecialchars($order['consumer_email']); ?></p>
                </div>
                <div>
                    <p style="margin: 0 0 5px 0; color: #999; font-weight: bold;">Phone</p>
                    <p style="margin: 0;"><?php echo htmlspecialchars($order['consumer_phone'] ?: 'N/A'); ?></p>
                </div>
            </div>
            <?php if ($order['delivery_address']): ?>
                <div style="margin-top: 15px;">
                    <p style="margin: 0 0 5px 0; color: #999; font-weight: bold;">Delivery Address</p>
                    <p style="margin: 0;"><?php echo htmlspecialchars($order['delivery_address']); ?></p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Order Items -->
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px;">
            <h3>Order Items</h3>
            <table style="width: 100%; border-collapse: collapse; margin-top: 15px;">
                <thead>
                    <tr style="background: #f5f5f5; border-bottom: 2px solid #ddd;">
                        <th style="text-align: left; padding: 10px;">Product</th>
                        <th style="text-align: center; padding: 10px;">Category</th>
                        <th style="text-align: right; padding: 10px;">Unit Price</th>
                        <th style="text-align: center; padding: 10px;">Quantity</th>
                        <th style="text-align: right; padding: 10px;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr style="border-bottom: 1px solid #eee;">
                            <td style="padding: 10px;"><?php echo htmlspecialchars($item['name']); ?></td>
                            <td style="padding: 10px; text-align: center;"><?php echo htmlspecialchars($item['category']); ?></td>
                            <td style="padding: 10px; text-align: right;">$<?php echo number_format($item['unit_price'], 2); ?></td>
                            <td style="padding: 10px; text-align: center;"><?php echo $item['quantity']; ?></td>
                            <td style="padding: 10px; text-align: right; font-weight: bold;">$<?php echo number_format($item['subtotal'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Order Timeline (NEW) -->
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px;">
            <h3>Order Timeline</h3>
            <?php if ($timeline && count($timeline) > 0): ?>
                <div style="margin-top: 20px;">
                    <?php foreach ($timeline as $index => $event): ?>
                        <div style="display: flex; margin-bottom: 20px; position: relative;">
                            <!-- Timeline marker -->
                            <div style="width: 40px; text-align: center; flex-shrink: 0;">
                                <div style="width: 16px; height: 16px; background: #667eea; border-radius: 50%; margin: 0 auto; position: relative; z-index: 2; border: 3px solid white;"></div>
                                <?php if ($index < count($timeline) - 1): ?>
                                    <div style="width: 2px; height: 40px; background: #ddd; margin: 0 auto; position: relative; top: -8px;"></div>
                                <?php endif; ?>
                            </div>
                            <!-- Timeline content -->
                            <div style="margin-left: 20px; flex-grow: 1;">
                                <p style="margin: 0 0 5px 0; font-weight: bold; color: #333;">
                                    <?php echo htmlspecialchars($event['status']); ?>
                                </p>
                                <p style="margin: 0 0 5px 0; color: #999; font-size: 14px;">
                                    <?php echo date('M d, Y H:i', strtotime($event['date'])); ?>
                                </p>
                                <p style="margin: 0; color: #666; font-size: 14px;">
                                    <?php echo htmlspecialchars($event['description']); ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="color: #999;">No status history available.</p>
            <?php endif; ?>
        </div>

        <!-- Order Status & Actions -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <!-- Update Order Status -->
            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <h3>Update Order Status</h3>
                <?php
                // Determine available status transitions based on current status
                $currentStatus = strtolower($order['status']);
                $validTransitions = [];
                
                if ($currentStatus === 'pending') {
                    $validTransitions = ['confirmed' => 'Confirmed', 'cancelled' => 'Cancelled'];
                } elseif ($currentStatus === 'confirmed') {
                    $validTransitions = ['completed' => 'Completed', 'cancelled' => 'Cancelled'];
                }
                // Completed and Cancelled have no transitions
                ?>
                
                <form method="POST" action="<?php echo BASE_URL; ?>farmer/orders/update-status.php?id=<?php echo $order['id']; ?>" style="margin-top: 15px;">
                    <div class="form-group">
                        <label for="status">
                            Current Status: <span style="background: #667eea; color: white; padding: 3px 8px; border-radius: 3px; font-weight: bold;">
                                <?php echo ucfirst($currentStatus); ?>
                            </span>
                        </label>
                        
                        <?php if (count($validTransitions) > 0): ?>
                            <select id="status" name="status" required style="margin-top: 10px;">
                                <option value="">-- Select New Status --</option>
                                <?php foreach ($validTransitions as $key => $label): ?>
                                    <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-primary" style="margin-top: 10px;">Update Status</button>
                        <?php else: ?>
                            <p style="color: #999; font-size: 14px; margin-top: 10px;">
                                This order cannot transition to any other status.
                            </p>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Update Payment Status -->
            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <h3>Payment Status</h3>
                <form method="POST" action="<?php echo BASE_URL; ?>farmer/orders/update-payment.php?id=<?php echo $order['id']; ?>" style="margin-top: 15px;">
                    <div class="form-group">
                        <label for="payment_status">
                            Current Status: <span style="background: <?php echo strtolower($order['payment_status']) === 'paid' ? '#28a745' : '#ffc107'; ?>; color: white; padding: 3px 8px; border-radius: 3px; font-weight: bold;">
                                <?php echo ucfirst($order['payment_status']); ?>
                            </span>
                        </label>
                        <select id="payment_status" name="payment_status" required style="margin-top: 10px;">
                            <option value="unpaid" <?php echo strtolower($order['payment_status']) === 'unpaid' ? 'selected' : ''; ?>>Unpaid</option>
                            <option value="paid" <?php echo strtolower($order['payment_status']) === 'paid' ? 'selected' : ''; ?>>Paid</option>
                            <option value="refunded" <?php echo strtolower($order['payment_status']) === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary" style="margin-top: 10px;">Update Payment</button>
                </form>
            </div>
        </div>

        <div style="margin-top: 30px;">
            <a href="<?php echo BASE_URL; ?>farmer/orders/list.php" class="btn" style="background: #6c757d; color: white; text-decoration: none; padding: 10px 20px; border-radius: 5px;">← Back to Orders</a>
        </div>
    </div>
</body>
</html>
