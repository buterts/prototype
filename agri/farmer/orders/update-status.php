<?php
require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../app/models/Order.php';
require_once __DIR__ . '/../../app/models/OrderItem.php';
require_once __DIR__ . '/../../app/services/OrderService.php';
require_once __DIR__ . '/../../app/controllers/OrderController.php';
require_once __DIR__ . '/../../app/middleware/AuthMiddleware.php';

AuthMiddleware::requireRole(ROLE_FARMER);

require_once __DIR__ . '/../../config/bootstrap.php';
$db = $GLOBALS['conn'] ?? null;
$orderModel = new Order($db);
$orderItemModel = new OrderItem($db);
$orderService = new OrderService($orderModel, $orderItemModel, $db);
$orderController = new OrderController($orderService);

$farmer_id = $_SESSION['user_id'];
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$order_id) {
    header("Location: " . BASE_URL . "farmer/orders/list.php");
    exit;
}

$orderController->handleStatusUpdate($farmer_id, $order_id);
?>
