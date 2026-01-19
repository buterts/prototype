<?php
require_once __DIR__ . '/../../config/bootstrap.php';
$db = $GLOBALS['conn'] ?? null;
$conn = $db;

AuthMiddleware::requireRole(ROLE_CONSUMER);

$orderModel = new Order($conn);
$orderItemModel = new OrderItem($conn);
$productModel = new Product($conn);
$orderManagementService = new OrderManagementService($conn, $orderModel, $orderItemModel, $productModel);

$consumer_id = $_SESSION['user_id'];
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$order_id) {
    header("Location: " . BASE_URL . "consumer/orders/list.php");
    exit;
}

// Get order
$query = "SELECT o.*, u.first_name as farmer_first_name, u.last_name as farmer_last_name FROM orders o 
          JOIN users u ON o.farmer_id = u.id 
          WHERE o.id = $order_id AND o.consumer_id = $consumer_id";
$result = $conn->query($query);

if (!$result || $result->num_rows === 0) {
    header("Location: " . BASE_URL . "consumer/orders/list.php");
    exit;
}

$order = $result->fetch_assoc();
// Construct farmer name for display
$order['farmer_name'] = ($order['farmer_first_name'] ?? '') . ' ' . ($order['farmer_last_name'] ?? '');

// Get order items
$items_query = "SELECT oi.*, p.name, p.unit, oi.unit_price as price FROM order_items oi 
               JOIN products p ON oi.product_id = p.id 
               WHERE oi.order_id = $order_id
               ORDER BY oi.id";
$items_result = $conn->query($items_query);
$items = [];
if ($items_result && $items_result->num_rows > 0) {
    while ($row = $items_result->fetch_assoc()) {
        $items[] = $row;
    }
}

// Get timeline from OrderManagementService
$timeline = $orderManagementService->getOrderTimeline($order_id);

// Status colors
$statusColors = [
    'Pending' => '#ffc107',
    'Confirmed' => '#17a2b8',
    'Completed' => '#28a745',
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
    <title>Order Details</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>public/css/style.css">
    <style>
        .order-details-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 40px;
        }

        .order-header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }

        .header-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .header-col {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .header-col label {
            font-size: 12px;
            color: #999;
            text-transform: uppercase;
        }

        .header-col p {
            margin: 0;
            font-weight: bold;
            font-size: 16px;
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            color: white;
            width: fit-content;
        }

        .timeline {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }

        .timeline-item {
            display: flex;
            gap: 20px;
            padding: 20px 0;
            border-left: 2px solid #e0e0e0;
            padding-left: 20px;
            margin-left: 10px;
        }

        .timeline-item:first-child {
            border-left-color: #28a745;
        }

        .timeline-marker {
            width: 20px;
            height: 20px;
            background: #e0e0e0;
            border-radius: 50%;
            margin-left: -30px;
            margin-top: 5px;
        }

        .timeline-item:first-child .timeline-marker {
            background: #28a745;
        }

        .timeline-content {
            flex: 1;
        }

        .timeline-date {
            font-size: 12px;
            color: #999;
        }

        .timeline-status {
            font-weight: bold;
            color: #333;
        }

        .order-items {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }

        .item-row {
            display: grid;
            grid-template-columns: 2fr 100px 100px 100px;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
            align-items: center;
        }

        .item-row:last-child {
            border-bottom: none;
        }

        .item-name {
            font-weight: 500;
        }

        .item-qty,
        .item-price {
            text-align: right;
            color: #666;
        }

        .item-total {
            text-align: right;
            font-weight: bold;
        }

        .order-summary {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            max-width: 350px;
            margin-left: auto;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .summary-row:last-child {
            border-bottom: none;
            padding-top: 10px;
            padding-bottom: 0;
            font-size: 16px;
            font-weight: bold;
        }

        .back-link {
            margin-bottom: 20px;
        }

        .back-link a {
            color: #0069d9;
            text-decoration: none;
        }

        .back-link a:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .order-details-container {
                padding: 20px;
            }

            .header-row {
                grid-template-columns: 1fr;
            }

            .item-row {
                grid-template-columns: 1fr;
            }

            .item-qty,
            .item-price {
                text-align: left;
            }

            .order-summary {
                max-width: 100%;
                margin-left: 0;
                margin-top: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-header">
        <h1>📦 Order Details</h1>
        <div class="user-menu">
            <a href="<?php echo BASE_URL; ?>consumer/orders/list.php">My Orders</a>
            <a href="<?php echo BASE_URL; ?>consumer/dashboard.php">Dashboard</a>
            <a href="<?php echo BASE_URL; ?>auth/logout.php" class="btn logout-btn">Logout</a>
        </div>
    </div>

    <div class="order-details-container">
        <div class="back-link">
            <a href="<?php echo BASE_URL; ?>consumer/orders/list.php">← Back to Orders</a>
        </div>

        <!-- Order Header -->
        <div class="order-header">
            <div class="header-row">
                <div class="header-col">
                    <label>Order Number</label>
                    <p><?php echo htmlspecialchars($order['order_number']); ?></p>
                </div>
                <div class="header-col">
                    <label>Order Date</label>
                    <p><?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></p>
                </div>
                <div class="header-col">
                    <label>Farmer</label>
                    <p><?php echo htmlspecialchars($order['farmer_name']); ?></p>
                </div>
                <div class="header-col">
                    <label>Status</label>
                    <span class="badge" style="background: <?php echo $statusColors[$order['status']] ?? '#666'; ?>;">
                        <?php echo htmlspecialchars($order['status']); ?>
                    </span>
                </div>
            </div>

            <div class="header-row">
                <div class="header-col">
                    <label>Fulfillment Type</label>
                    <p><?php echo htmlspecialchars($order['fulfillment_type']); ?></p>
                </div>
                <div class="header-col">
                    <label>Payment Status</label>
                    <span class="badge" style="background: <?php echo $paymentColors[$order['payment_status']] ?? '#666'; ?>;">
                        <?php echo htmlspecialchars($order['payment_status']); ?>
                    </span>
                    <?php if (strtolower($order['payment_status']) === 'unpaid'): ?>
                        <a href="<?php echo BASE_URL; ?>consumer/orders/pay.php?id=<?php echo $order['id']; ?>" style="display: inline-block; margin-top: 8px; background: #667eea; color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 12px; font-weight: bold;">Pay Now</a>
                    <?php endif; ?>
                </div>
                <div class="header-col">
                    <label>Total Amount</label>
                    <p>$<?php echo number_format($order['total_amount'], 2); ?></p>
                </div>
            </div>

            <?php if ($order['fulfillment_type'] === 'Delivery' && !empty($order['delivery_address'])): ?>
                <div style="background: #f9f9f9; padding: 15px; border-radius: 4px; margin-top: 15px;">
                    <label style="font-weight: bold; font-size: 12px; color: #999; text-transform: uppercase; display: block; margin-bottom: 10px;">Delivery Address</label>
                    <p style="margin: 0; white-space: pre-wrap;">
                        <?php echo htmlspecialchars($order['delivery_address']); ?>
                    </p>
                </div>
            <?php elseif ($order['fulfillment_type'] === 'Pickup' && !empty($order['pickup_date'])): ?>
                <div style="background: #f9f9f9; padding: 15px; border-radius: 4px; margin-top: 15px;">
                    <label style="font-weight: bold; font-size: 12px; color: #999; text-transform: uppercase; display: block; margin-bottom: 10px;">Pickup Date</label>
                    <p style="margin: 0;">
                        <?php echo date('l, F j, Y', strtotime($order['pickup_date'])); ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Order Timeline -->
        <div class="timeline">
            <h3 style="margin: 0 0 20px 0;">Order Status Timeline</h3>
            <?php foreach ($timeline as $event): ?>
                <div class="timeline-item">
                    <div class="timeline-marker"></div>
                    <div class="timeline-content">
                        <div class="timeline-status"><?php echo htmlspecialchars($event['status']); ?></div>
                        <div class="timeline-date"><?php echo date('M d, Y h:i A', strtotime($event['date'])); ?></div>
                        <p style="margin: 5px 0 0 0; color: #666; font-size: 14px;"><?php echo htmlspecialchars($event['description']); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Order Items and Summary -->
        <div style="display: grid; grid-template-columns: 1fr 350px; gap: 20px;">
            <!-- Order Items -->
            <div class="order-items">
                <h3 style="margin: 0 0 20px 0;">Order Items (<?php echo count($items); ?>)</h3>
                <div class="item-row" style="padding: 10px 0; margin-bottom: 10px; border-bottom: 2px solid #333;">
                    <div style="font-weight: bold;">Product</div>
                    <div style="font-weight: bold; text-align: right;">Qty</div>
                    <div style="font-weight: bold; text-align: right;">Unit Price</div>
                    <div style="font-weight: bold; text-align: right;">Total</div>
                </div>

                <?php foreach ($items as $item): ?>
                    <div class="item-row">
                        <div class="item-name">
                            <?php echo htmlspecialchars($item['name']); ?> (<?php echo htmlspecialchars($item['unit']); ?>)
                        </div>
                        <div class="item-qty"><?php echo $item['quantity']; ?></div>
                        <div class="item-price">$<?php echo number_format($item['price'], 2); ?></div>
                        <div class="item-total">$<?php echo number_format($item['quantity'] * $item['price'], 2); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Order Summary -->
            <div class="order-summary">
                <h3 style="margin: 0 0 20px 0;">Summary</h3>
                <div class="summary-row">
                    <span>Subtotal:</span>
                    <span>$<?php echo number_format($order['total_amount'], 2); ?></span>
                </div>
                <div class="summary-row">
                    <span>Shipping:</span>
                    <span>Included</span>
                </div>
                <div class="summary-row">
                    <span>Total:</span>
                    <span>$<?php echo number_format($order['total_amount'], 2); ?></span>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
