<?php
/**
 * Order Controller
 */

class OrderController {
    private $orderService;

    public function __construct($orderService) {
        $this->orderService = $orderService;
    }

    /**
     * Show orders list
     */
    public function listOrders($farmer_id, $page = 1) {
        return $this->orderService->listOrders($farmer_id, $page);
    }

    /**
     * Show order details
     */
    public function showOrderDetails($farmer_id, $order_id) {
        return $this->orderService->getOrderWithItems($farmer_id, $order_id);
    }

    /**
     * Handle status update
     */
    public function handleStatusUpdate($farmer_id, $order_id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . BASE_URL . "farmer/orders/list.php");
            exit;
        }

        $status = $_POST['status'] ?? '';

        $result = $this->orderService->updateOrderStatus($farmer_id, $order_id, $status);

        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
            header("Location: " . BASE_URL . "farmer/orders/view.php?id=" . $order_id);
            exit;
        } else {
            $_SESSION['error'] = $result['message'];
            header("Location: " . BASE_URL . "farmer/orders/view.php?id=" . $order_id);
            exit;
        }
    }

    /**
     * Handle payment status update
     */
    public function handlePaymentStatusUpdate($farmer_id, $order_id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . BASE_URL . "farmer/orders/list.php");
            exit;
        }

        $payment_status = $_POST['payment_status'] ?? '';

        $result = $this->orderService->updatePaymentStatus($farmer_id, $order_id, $payment_status);

        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
            header("Location: " . BASE_URL . "farmer/orders/view.php?id=" . $order_id);
            exit;
        } else {
            $_SESSION['error'] = $result['message'];
            header("Location: " . BASE_URL . "farmer/orders/view.php?id=" . $order_id);
            exit;
        }
    }

    /**
     * Get orders by status
     */
    public function getOrdersByStatus($farmer_id, $status) {
        return $this->orderService->getOrdersByStatus($farmer_id, $status);
    }

    /**
     * Get sales summary
     */
    public function getSalesSummary($farmer_id) {
        return $this->orderService->getSalesSummary($farmer_id);
    }

    /**
     * Get monthly sales
     */
    public function getMonthlySales($farmer_id) {
        return $this->orderService->getMonthlySales($farmer_id);
    }

    /**
     * Get pending orders count
     */
    public function getPendingOrdersCount($farmer_id) {
        return $this->orderService->getPendingOrdersCount($farmer_id);
    }

    /**
     * Get recent orders
     */
    public function getRecentOrders($farmer_id) {
        return $this->orderService->getRecentOrders($farmer_id);
    }
}
?>
