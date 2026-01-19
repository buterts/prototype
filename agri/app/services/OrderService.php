<?php
/**
 * Order Service
 */

class OrderService {
    private $orderModel;
    private $orderItemModel;
    private $conn;

    public function __construct($orderModel, $orderItemModel, $db) {
        $this->orderModel = $orderModel;
        $this->orderItemModel = $orderItemModel;
        $this->conn = $db;
    }

    /**
     * Get order with items
     */
    public function getOrderWithItems($farmer_id, $order_id) {
        $order = $this->orderModel->getById($order_id);

        if (!$order) {
            return ['success' => false, 'message' => 'Order not found'];
        }

        if ($order['farmer_id'] != $farmer_id) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $items = $this->orderItemModel->getByOrderId($order_id);

        return [
            'success' => true,
            'data' => [
                'order' => $order,
                'items' => $items
            ]
        ];
    }

    /**
     * List farmer's orders
     */
    public function listOrders($farmer_id, $page = 1, $per_page = 20) {
        $offset = ($page - 1) * $per_page;
        $orders = $this->orderModel->getByFarmerId($farmer_id, $per_page, $offset);
        $total = $this->orderModel->getTotalCountByFarmerId($farmer_id);

        return [
            'success' => true,
            'data' => $orders,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $per_page,
                'total_pages' => ceil($total / $per_page)
            ]
        ];
    }

    /**
     * Update order status
     */
    public function updateOrderStatus($farmer_id, $order_id, $status) {
        if (empty($status)) {
            return ['success' => false, 'message' => 'Status is required'];
        }

        $valid_statuses = ['pending', 'confirmed', 'processing', 'ready_for_pickup', 'completed', 'cancelled'];
        if (!in_array($status, $valid_statuses)) {
            return ['success' => false, 'message' => 'Invalid status'];
        }

        $order = $this->orderModel->getById($order_id);
        if (!$order) {
            return ['success' => false, 'message' => 'Order not found'];
        }

        if (!$this->orderModel->updateStatus($order_id, $farmer_id, $status)) {
            return ['success' => false, 'message' => 'Failed to update order status'];
        }

        return ['success' => true, 'message' => 'Order status updated successfully'];
    }

    /**
     * Update payment status
     */
    public function updatePaymentStatus($farmer_id, $order_id, $payment_status) {
        if (empty($payment_status)) {
            return ['success' => false, 'message' => 'Payment status is required'];
        }

        $valid_statuses = ['unpaid', 'paid', 'refunded'];
        if (!in_array($payment_status, $valid_statuses)) {
            return ['success' => false, 'message' => 'Invalid payment status'];
        }

        if (!$this->orderModel->updatePaymentStatus($order_id, $farmer_id, $payment_status)) {
            return ['success' => false, 'message' => 'Failed to update payment status'];
        }

        return ['success' => true, 'message' => 'Payment status updated successfully'];
    }

    /**
     * Get orders by status
     */
    public function getOrdersByStatus($farmer_id, $status) {
        $valid_statuses = ['pending', 'confirmed', 'processing', 'ready_for_pickup', 'completed', 'cancelled'];
        if (!in_array($status, $valid_statuses)) {
            return ['success' => false, 'message' => 'Invalid status'];
        }

        $orders = $this->orderModel->getByStatus($farmer_id, $status);

        return ['success' => true, 'data' => $orders];
    }

    /**
     * Get sales summary
     */
    public function getSalesSummary($farmer_id) {
        $summary = $this->orderModel->getSalesSummary($farmer_id);

        if (!$summary) {
            return [
                'success' => true,
                'data' => [
                    'total_orders' => 0,
                    'total_revenue' => 0,
                    'completed_orders' => 0,
                    'pending_orders' => 0,
                    'cancelled_orders' => 0,
                    'paid_orders' => 0
                ]
            ];
        }

        return ['success' => true, 'data' => $summary];
    }

    /**
     * Get monthly sales
     */
    public function getMonthlySales($farmer_id) {
        $sales = $this->orderModel->getMonthlySales($farmer_id);

        return ['success' => true, 'data' => $sales];
    }

    /**
     * Get pending orders count
     */
    public function getPendingOrdersCount($farmer_id) {
        $orders = $this->orderModel->getByStatus($farmer_id, 'pending');
        return count($orders);
    }

    /**
     * Get recent orders
     */
    public function getRecentOrders($farmer_id, $limit = 10) {
        $orders = $this->orderModel->getByFarmerId($farmer_id, $limit, 0);
        return ['success' => true, 'data' => $orders];
    }
}
?>
