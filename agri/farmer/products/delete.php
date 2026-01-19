<?php
require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../app/models/Product.php';
require_once __DIR__ . '/../../app/services/ProductService.php';
require_once __DIR__ . '/../../app/controllers/ProductController.php';
require_once __DIR__ . '/../../app/middleware/AuthMiddleware.php';

AuthMiddleware::requireRole(ROLE_FARMER);

require_once __DIR__ . '/../../config/bootstrap.php';
$db = $GLOBALS['conn'] ?? null;
$productModel = new Product($db);
$productService = new ProductService($productModel, $db);
$productController = new ProductController($productService);

$farmer_id = $_SESSION['user_id'];
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$product_id) {
    header("Location: " . BASE_URL . "farmer/products/list.php");
    exit;
}

$productController->handleDeleteProduct($farmer_id, $product_id);
?>
