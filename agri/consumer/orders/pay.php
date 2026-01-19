<?php
require_once __DIR__ . '/../../config/bootstrap.php';

// Require consumer role
AuthMiddleware::requireRole(ROLE_CONSUMER);

$db = $GLOBALS['conn'] ?? null;
$orderModel = new Order($db);

$consumer_id = $_SESSION['user_id'];
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$order_id) {
    header("Location: " . BASE_URL . "consumer/orders/list.php");
    exit;
}

// Get order
$order = $orderModel->getById($order_id);
if (!$order || $order['consumer_id'] != $consumer_id) {
    header("Location: " . BASE_URL . "consumer/orders/list.php");
    exit;
}

$error = '';
$success = '';

// If order is already paid, redirect back
if ($order['payment_status'] === 'paid') {
    $_SESSION['info'] = 'This order has already been paid.';
    header("Location: " . BASE_URL . "consumer/orders/view.php?id=" . $order_id);
    exit;
}

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_method = $_POST['payment_method'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Validate payment method
    $valid_methods = ['credit_card', 'debit_card', 'bank_transfer', 'cash', 'gcash'];
    if (!in_array($payment_method, $valid_methods)) {
        $error = 'Invalid payment method selected';
    } elseif (empty($password)) {
        $error = 'Password is required to confirm payment';
    } else {
        // Verify password
        $userModel = new User($db);
        $user = $userModel->findById($consumer_id);
        
        if (!$userModel->verifyPassword($user, $password)) {
            $error = 'Incorrect password. Payment not processed.';
        } else {
            // Password verified - process payment
            $payment_method = $db->real_escape_string($payment_method);
            $update_query = "UPDATE orders SET payment_status = 'paid', payment_method = '$payment_method' WHERE id = $order_id";
            if ($db->query($update_query)) {
                $success = 'Payment processed successfully! Your order is now confirmed as paid.';
                $_SESSION['success'] = 'Payment received! Thank you for your purchase.';
                header("Location: " . BASE_URL . "consumer/orders/view.php?id=" . $order_id);
                exit;
            } else {
                $error = 'Failed to process payment. Please try again.';
            }
        }
    }
}

$paymentColors = [
    'unpaid' => '#ff6b6b',
    'paid' => '#28a745',
    'refunded' => '#17a2b8'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - Consumer Orders</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>public/css/style.css">
    <style>
        .payment-container {
            max-width: 600px;
            margin: 40px auto;
            padding: 30px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .order-summary {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 30px;
            border-left: 4px solid #667eea;
        }
        .order-summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        .order-summary-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        .payment-methods {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 30px;
        }
        .payment-method {
            border: 2px solid #ddd;
            padding: 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
        }
        .payment-method:hover {
            border-color: #667eea;
            background: #f0f4ff;
        }
        .payment-method input[type="radio"] {
            display: none;
        }
        .payment-method input[type="radio"]:checked + label,
        .payment-method.selected {
            border-color: #667eea;
            background: #f0f4ff;
            font-weight: bold;
        }
        .payment-method label {
            display: block;
            cursor: pointer;
            margin: 0;
        }
        .payment-icon {
            font-size: 24px;
            margin-bottom: 5px;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .btn-submit {
            background: #667eea;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            width: 100%;
            transition: background 0.3s;
        }
        .btn-submit:hover {
            background: #5568d3;
        }
        .btn-submit:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <div class="dashboard-header">
        <h1>Payment</h1>
        <div class="user-menu">
            <a href="<?php echo BASE_URL; ?>consumer/orders/view.php?id=<?php echo $order_id; ?>">Back to Order</a>
            <a href="<?php echo BASE_URL; ?>auth/logout.php" class="btn logout-btn">Logout</a>
        </div>
    </div>

    <div class="payment-container">
        <?php if ($error): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Order Summary -->
        <div class="order-summary">
            <h3 style="margin-top: 0;">Order Summary</h3>
            <div class="order-summary-row">
                <strong>Order Number:</strong>
                <span><?php echo htmlspecialchars($order['order_number']); ?></span>
            </div>
            <div class="order-summary-row">
                <strong>Order Date:</strong>
                <span><?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></span>
            </div>
            <div class="order-summary-row">
                <strong>Status:</strong>
                <span style="background: <?php echo $paymentColors[$order['status']] ?? '#666'; ?>; color: white; padding: 3px 8px; border-radius: 3px;">
                    <?php echo htmlspecialchars(ucfirst($order['status'])); ?>
                </span>
            </div>
            <div class="order-summary-row">
                <strong>Amount Due:</strong>
                <span style="font-size: 18px; font-weight: bold; color: #667eea;">
                    $<?php echo number_format($order['total_amount'], 2); ?>
                </span>
            </div>
        </div>

        <!-- Payment Form -->
        <form method="POST">
            <h3>Select Payment Method</h3>
            
            <div class="payment-methods">
                <div class="payment-method">
                    <input type="radio" id="credit_card" name="payment_method" value="credit_card" required>
                    <label for="credit_card">
                        <div class="payment-icon">💳</div>
                        Credit Card
                    </label>
                </div>

                <div class="payment-method">
                    <input type="radio" id="debit_card" name="payment_method" value="debit_card" required>
                    <label for="debit_card">
                        <div class="payment-icon">💳</div>
                        Debit Card
                    </label>
                </div>

                <div class="payment-method">
                    <input type="radio" id="bank_transfer" name="payment_method" value="bank_transfer" required>
                    <label for="bank_transfer">
                        <div class="payment-icon">🏦</div>
                        Bank Transfer
                    </label>
                </div>

                <div class="payment-method">
                    <input type="radio" id="gcash" name="payment_method" value="gcash" required>
                    <label for="gcash">
                        <div class="payment-icon">📱</div>
                        GCash
                    </label>
                </div>

                <div class="payment-method">
                    <input type="radio" id="cash" name="payment_method" value="cash" required>
                    <label for="cash">
                        <div class="payment-icon">💵</div>
                        Cash on Pickup
                    </label>
                </div>
            </div>

            <div class="form-group" style="background: #f8f9fa; padding: 20px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #667eea;">
                <label for="password" style="font-weight: bold; display: block; margin-bottom: 10px;">
                    🔐 Confirm Payment with Password
                </label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                <small style="color: #666; display: block; margin-top: 8px;">Your password is required for security purposes.</small>
            </div>

            <div style="background: #fff3cd; padding: 15px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #ffc107;">
                <strong>⚠️ Demo Mode:</strong> This is a demo payment system. In production, you would be redirected to a payment gateway (Stripe, PayPal, etc.).
            </div>

            <button type="submit" class="btn-submit">Process Payment</button>
        </form>

        <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #999; font-size: 14px;">
            <p>Your payment information is secure and encrypted.</p>
            <a href="<?php echo BASE_URL; ?>consumer/orders/view.php?id=<?php echo $order_id; ?>" style="color: #667eea; text-decoration: none;">← Back to Order</a>
        </div>
    </div>
</body>
</html>
