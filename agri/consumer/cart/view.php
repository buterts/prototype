<?php
require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../app/models/Product.php';
require_once __DIR__ . '/../../app/models/ShoppingCart.php';
require_once __DIR__ . '/../../app/services/CartService.php';
require_once __DIR__ . '/../../app/middleware/AuthMiddleware.php';

AuthMiddleware::requireRole(ROLE_CONSUMER);

require_once __DIR__ . '/../../config/bootstrap.php';
$db = $GLOBALS['conn'] ?? null;
$conn = $db;
$productModel = new Product($conn);
$cartModel = new ShoppingCart($conn);
$cartService = new CartService($cartModel, $productModel, $conn);

$consumer_id = $_SESSION['user_id'];

// Handle remove
if (isset($_POST['remove'])) {
    $cart_id = (int)$_POST['remove'];
    $cartService->removeFromCart($consumer_id, $cart_id);
    header("Location: " . BASE_URL . "consumer/cart/view.php");
    exit;
}

// Handle update quantity
if (isset($_POST['update'])) {
    $cart_id = (int)$_POST['update'];
    $quantity = (int)$_POST['quantity_' . $cart_id];
    
    // Get cart item to get farmer_id and product_id
    $query = "SELECT farmer_id, product_id FROM shopping_carts WHERE id = $cart_id AND consumer_id = $consumer_id";
    $result = $conn->query($query);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $cartService->updateQuantity($consumer_id, $row['farmer_id'], $row['product_id'], $quantity);
    }
    
    header("Location: " . BASE_URL . "consumer/cart/view.php");
    exit;
}

// Get cart
$cartItems = $cartModel->getCartByConsumer($consumer_id);
$cartTotal = $cartModel->getCartTotal($consumer_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>public/css/style.css">
</head>
<body>
    <div class="dashboard-header">
        <h1>🛒 Shopping Cart</h1>
        <div class="user-menu">
            <a href="<?php echo BASE_URL; ?>consumer/products/browse.php">Continue Shopping</a>
            <a href="<?php echo BASE_URL; ?>auth/logout.php" class="btn logout-btn">Logout</a>
        </div>
    </div>

    <div style="padding: 40px; max-width: 1200px; margin: 0 auto;">
        <?php if (count($cartItems) > 0): ?>
            <div style="display: grid; grid-template-columns: 1fr 300px; gap: 30px;">
                <!-- Cart Items -->
                <div>
                    <h2>Your Cart (<?php echo count($cartItems); ?> items)</h2>
                    
                    <?php
                    // Group items by farmer
                    $groupedByFarmer = [];
                    foreach ($cartItems as $item) {
                        $farmer_id = $item['farmer_id'];
                        if (!isset($groupedByFarmer[$farmer_id])) {
                            $groupedByFarmer[$farmer_id] = [
                                'farmer_id' => $farmer_id,
                                'items' => []
                            ];
                        }
                        $groupedByFarmer[$farmer_id]['items'][] = $item;
                    }
                    ?>

                    <?php foreach ($groupedByFarmer as $farmerGroup): ?>
                        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px;">
                            <h3 style="margin-top: 0;">Farmer #<?php echo $farmerGroup['farmer_id']; ?></h3>
                            
                            <form method="POST">
                                <?php foreach ($farmerGroup['items'] as $item): ?>
                                    <div style="display: grid; grid-template-columns: 1fr 100px 100px 100px; gap: 15px; align-items: center; padding: 15px; border-bottom: 1px solid #eee;">
                                        <div>
                                            <p style="margin: 0; font-weight: bold;"><?php echo htmlspecialchars($item['name']); ?></p>
                                            <p style="margin: 5px 0 0 0; color: #999; font-size: 12px;">
                                                <?php echo htmlspecialchars($item['category']); ?> • $<?php echo number_format($item['price'], 2); ?>/<?php echo htmlspecialchars($item['unit']); ?>
                                            </p>
                                        </div>
                                        
                                        <div>
                                            <input type="number" name="quantity_<?php echo $item['id']; ?>" value="<?php echo $item['quantity']; ?>" min="1" style="width: 80px; padding: 5px;">
                                        </div>

                                        <div style="text-align: right;">
                                            <p style="margin: 0; font-weight: bold;">$<?php echo number_format($item['quantity'] * $item['price'], 2); ?></p>
                                        </div>

                                        <div style="text-align: right;">
                                            <button type="submit" name="update" value="<?php echo $item['id']; ?>" class="btn" style="background: #28a745; color: white; padding: 5px 10px; font-size: 12px; margin-bottom: 5px;">Update</button>
                                            <button type="submit" name="remove" value="<?php echo $item['id']; ?>" class="btn" style="background: #dc3545; color: white; padding: 5px 10px; font-size: 12px;" onclick="return confirm('Remove item?');">Remove</button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Cart Summary -->
                <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); height: fit-content;">
                    <h3 style="margin-top: 0;">Order Summary</h3>
                    
                    <div style="padding: 15px 0; border-bottom: 2px solid #eee;">
                        <p style="display: flex; justify-content: space-between; margin: 0;">
                            <span>Subtotal:</span>
                            <span>$<?php echo number_format($cartTotal, 2); ?></span>
                        </p>
                        <p style="display: flex; justify-content: space-between; margin: 10px 0 0 0; color: #999; font-size: 12px;">
                            <span>Shipping:</span>
                            <span>TBD</span>
                        </p>
                    </div>

                    <p style="display: flex; justify-content: space-between; margin: 15px 0; font-size: 18px; font-weight: bold;">
                        <span>Total:</span>
                        <span>$<?php echo number_format($cartTotal, 2); ?></span>
                    </p>

                    <a href="<?php echo BASE_URL; ?>consumer/cart/checkout.php" class="btn btn-primary" style="width: 100%; text-align: center; padding: 15px;">
                        Proceed to Checkout
                    </a>

                    <a href="<?php echo BASE_URL; ?>consumer/products/browse.php" class="btn" style="width: 100%; text-align: center; padding: 15px; background: #6c757d; color: white; text-decoration: none; margin-top: 10px;">
                        Continue Shopping
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div style="background: #f9f9f9; padding: 40px; text-align: center; border-radius: 8px;">
                <p style="color: #999; font-size: 16px; margin-bottom: 20px;">Your cart is empty.</p>
                <a href="<?php echo BASE_URL; ?>consumer/products/browse.php" class="btn btn-primary">Start Shopping</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
