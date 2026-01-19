<?php
require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../app/models/Product.php';
require_once __DIR__ . '/../../app/models/ShoppingCart.php';
require_once __DIR__ . '/../../app/models/Order.php';
require_once __DIR__ . '/../../app/models/OrderItem.php';
require_once __DIR__ . '/../../app/services/CartService.php';
require_once __DIR__ . '/../../app/services/OrderService.php';
require_once __DIR__ . '/../../app/services/OrderManagementService.php';
require_once __DIR__ . '/../../app/middleware/AuthMiddleware.php';

AuthMiddleware::requireRole(ROLE_CONSUMER);

require_once __DIR__ . '/../../config/bootstrap.php';
$db = $GLOBALS['conn'] ?? null;
$conn = $db;
$cartModel = new ShoppingCart($conn);
$orderModel = new Order($conn);
$orderItemModel = new OrderItem($conn);
$productModel = new Product($conn);

// Use OrderManagementService for transaction-based order creation
$orderService = new OrderService($orderModel, $orderItemModel, $conn);
$orderManagementService = new OrderManagementService($conn, $orderModel, $orderItemModel, $productModel);

$consumer_id = $_SESSION['user_id'];

// Get cart items
$cartItems = $cartModel->getCartByConsumer($consumer_id);
if (count($cartItems) === 0) {
    header("Location: " . BASE_URL . "consumer/cart/view.php");
    exit;
}

$cartTotal = $cartModel->getCartTotal($consumer_id);

// Handle form submission - create orders using OrderManagementService
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fulfillment_type = isset($_POST['fulfillment_type']) ? $_POST['fulfillment_type'] : '';
    
    if (!in_array($fulfillment_type, ['Delivery', 'Pickup'])) {
        $error = "Invalid fulfillment type";
    } else {
        // Group items by farmer
        $groupedByFarmer = [];
        foreach ($cartItems as $item) {
            $farmer_id = $item['farmer_id'];
            if (!isset($groupedByFarmer[$farmer_id])) {
                $groupedByFarmer[$farmer_id] = [];
            }
            $groupedByFarmer[$farmer_id][] = $item;
        }

        $orders_created = 0;
        $error = null;

        foreach ($groupedByFarmer as $farmer_id => $items) {
            // Prepare items array for OrderManagementService::createOrder()
            $orderItems = [];
            foreach ($items as $item) {
                $orderItems[] = [
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price']
                ];
            }

            // Prepare options based on fulfillment type
            $options = [
                'fulfillment_type' => $fulfillment_type
            ];

            if ($fulfillment_type === 'Delivery') {
                $delivery_address = isset($_POST['delivery_address']) ? trim($_POST['delivery_address']) : '';
                if (empty($delivery_address)) {
                    $error = "Delivery address is required";
                    break;
                }
                $options['delivery_address'] = $delivery_address;
            } elseif ($fulfillment_type === 'Pickup') {
                $pickup_date = isset($_POST['pickup_date']) ? trim($_POST['pickup_date']) : '';
                if (empty($pickup_date)) {
                    $error = "Pickup date is required";
                    break;
                }
                $options['pickup_date'] = $pickup_date;
            }

            // Create order with transaction support
            $result = $orderManagementService->createOrder(
                $consumer_id,
                $farmer_id,
                $orderItems,
                $options
            );

            if ($result && $result['success']) {
                $orders_created++;
            } else {
                $error = $result['message'] ?? "Failed to create order for farmer ID $farmer_id";
                break;
            }
        }

        if ($error === null && $orders_created === count($groupedByFarmer)) {
            // Clear cart after successful orders
            $cartModel->clearCart($consumer_id);
            $_SESSION['orders_created'] = $orders_created;
            header("Location: " . BASE_URL . "consumer/orders/confirmation.php?success=1");
            exit;
        } else if ($error === null) {
            $error = "Failed to create all orders";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>public/css/style.css">
    <style>
        .checkout-container {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 30px;
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px;
        }

        .fulfillment-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .fulfillment-option {
            border: 2px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin: 10px 0;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .fulfillment-option:hover {
            border-color: #28a745;
            background: #f9fff9;
        }

        .fulfillment-option input[type="radio"] {
            margin-right: 10px;
        }

        .fulfillment-details {
            margin-top: 15px;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 4px;
            display: none;
        }

        .fulfillment-details.active {
            display: block;
        }

        .fulfillment-details textarea,
        .fulfillment-details input[type="date"] {
            width: 100%;
            padding: 10px;
            margin-top: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: Arial, sans-serif;
        }

        @media (max-width: 768px) {
            .checkout-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-header">
        <h1>📋 Checkout</h1>
        <div class="user-menu">
            <a href="<?php echo BASE_URL; ?>consumer/products/browse.php">Continue Shopping</a>
            <a href="<?php echo BASE_URL; ?>auth/logout.php" class="btn logout-btn">Logout</a>
        </div>
    </div>

    <div class="checkout-container">
        <!-- Main Content -->
        <div>
            <form method="POST" onsubmit="return validateForm()">
                <!-- Fulfillment Options -->
                <div class="fulfillment-section">
                    <h2>📦 Fulfillment Options</h2>

                    <label class="fulfillment-option">
                        <input type="radio" name="fulfillment_type" value="Delivery" onchange="toggleFulfillmentDetails()" checked>
                        <strong>Delivery</strong>
                        <p style="margin: 5px 0 0 0; color: #666; font-size: 14px;">We'll deliver your order to your address</p>
                    </label>

                    <div id="delivery-details" class="fulfillment-details active">
                        <label for="delivery_address" style="display: block; font-weight: bold;">Delivery Address</label>
                        <textarea name="delivery_address" id="delivery_address" placeholder="Enter your full delivery address..." required></textarea>
                    </div>

                    <label class="fulfillment-option">
                        <input type="radio" name="fulfillment_type" value="Pickup" onchange="toggleFulfillmentDetails()">
                        <strong>Pickup</strong>
                        <p style="margin: 5px 0 0 0; color: #666; font-size: 14px;">Pick up your order from the farm</p>
                    </label>

                    <div id="pickup-details" class="fulfillment-details">
                        <label for="pickup_date" style="display: block; font-weight: bold;">Preferred Pickup Date</label>
                        <input type="date" name="pickup_date" id="pickup_date">
                    </div>
                </div>

                <!-- Order Items Summary -->
                <div class="fulfillment-section">
                    <h2>📦 Order Items</h2>
                    
                    <?php
                    $groupedByFarmer = [];
                    foreach ($cartItems as $item) {
                        $farmer_id = $item['farmer_id'];
                        if (!isset($groupedByFarmer[$farmer_id])) {
                            $groupedByFarmer[$farmer_id] = [];
                        }
                        $groupedByFarmer[$farmer_id][] = $item;
                    }
                    ?>

                    <?php foreach ($groupedByFarmer as $farmer_id => $items): ?>
                        <div style="margin-bottom: 20px; padding: 15px; background: #f9f9f9; border-radius: 4px;">
                            <p style="margin: 0 0 10px 0; font-weight: bold;">Farmer #<?php echo $farmer_id; ?></p>
                            <?php foreach ($items as $item): ?>
                                <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee; font-size: 14px;">
                                    <span><?php echo htmlspecialchars($item['name']); ?> x<?php echo $item['quantity']; ?></span>
                                    <span>$<?php echo number_format($item['quantity'] * $item['price'], 2); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if (isset($error)): ?>
                    <div style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: 4px; margin-bottom: 20px;">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 15px; font-size: 16px; cursor: pointer; border: none;">
                    Place Order
                </button>

                <a href="<?php echo BASE_URL; ?>consumer/cart/view.php" class="btn" style="width: 100%; padding: 15px; font-size: 16px; text-align: center; background: #6c757d; color: white; text-decoration: none; margin-top: 10px; display: block;">
                    Back to Cart
                </a>
            </form>
        </div>

        <!-- Order Summary Sidebar -->
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); height: fit-content;">
            <h3 style="margin-top: 0;">Order Summary</h3>
            
            <div style="padding: 15px 0; border-bottom: 2px solid #eee;">
                <p style="display: flex; justify-content: space-between; margin: 0;">
                    <span>Items (<?php echo count($cartItems); ?>):</span>
                    <span>$<?php echo number_format($cartTotal, 2); ?></span>
                </p>
            </div>

            <p style="display: flex; justify-content: space-between; margin: 15px 0 0 0; font-size: 18px; font-weight: bold;">
                <span>Total:</span>
                <span>$<?php echo number_format($cartTotal, 2); ?></span>
            </p>

            <div style="background: #e8f5e9; padding: 15px; border-radius: 4px; margin-top: 15px;">
                <p style="margin: 0; font-size: 12px; color: #2e7d32;">
                    <strong>ℹ️ Note:</strong> Your cart contains items from <?php echo count($groupedByFarmer); ?> farmer(s). A separate order will be created for each farmer with the selected fulfillment option.
                </p>
            </div>
        </div>
    </div>

    <script>
        function toggleFulfillmentDetails() {
            const fulfillmentType = document.querySelector('input[name="fulfillment_type"]:checked').value;
            
            const deliveryDetails = document.getElementById('delivery-details');
            const pickupDetails = document.getElementById('pickup-details');

            if (fulfillmentType === 'Delivery') {
                deliveryDetails.classList.add('active');
                pickupDetails.classList.remove('active');
                document.getElementById('delivery_address').required = true;
                document.getElementById('pickup_date').required = false;
            } else {
                deliveryDetails.classList.remove('active');
                pickupDetails.classList.add('active');
                document.getElementById('delivery_address').required = false;
                document.getElementById('pickup_date').required = true;
            }
        }

        function validateForm() {
            const fulfillmentType = document.querySelector('input[name="fulfillment_type"]:checked').value;
            
            if (fulfillmentType === 'Delivery') {
                const address = document.getElementById('delivery_address').value.trim();
                if (!address) {
                    alert('Please enter a delivery address');
                    return false;
                }
            } else {
                const date = document.getElementById('pickup_date').value;
                if (!date) {
                    alert('Please select a pickup date');
                    return false;
                }
                // Validate date is not in the past
                const selectedDate = new Date(date);
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                if (selectedDate < today) {
                    alert('Pickup date must be in the future');
                    return false;
                }
            }
            return true;
        }
    </script>
</body>
</html>
