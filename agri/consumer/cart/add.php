<?php
require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../app/models/Product.php';
require_once __DIR__ . '/../../app/models/ShoppingCart.php';
require_once __DIR__ . '/../../app/services/CartService.php';
require_once __DIR__ . '/../../app/middleware/AuthMiddleware.php';

header('Content-Type: application/json');

AuthMiddleware::requireAuth();

if ($_SESSION['role'] !== ROLE_CONSUMER) {
    echo json_encode(['success' => false, 'message' => 'Only consumers can add to cart']);
    exit;
}

$dbPath = __DIR__ . '/../../config/bootstrap.php';
require_once $dbPath;
$db = $GLOBALS['conn'] ?? null;
$conn = $db;
$productModel = new Product($conn);
$cartModel = new ShoppingCart($conn);
$cartService = new CartService($cartModel, $productModel, $conn);

$consumer_id = $_SESSION['user_id'];
$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

if (!$product_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid product']);
    exit;
}

$result = $cartService->addToCart($consumer_id, $product_id, $quantity);
echo json_encode($result);
?>
