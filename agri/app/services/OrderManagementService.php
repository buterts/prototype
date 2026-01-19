<?php
/**
 * Order Management Service
 * Handles core order logic: creation, product relationships, and status tracking
 */

class OrderManagementService {
    private $conn;
    private $orderModel;
    private $orderItemModel;
    private $productModel;

    // Order status constants
    const STATUS_PENDING = 'Pending';
    const STATUS_CONFIRMED = 'Confirmed';
    const STATUS_COMPLETED = 'Completed';
    const STATUS_CANCELLED = 'Cancelled';
    
    // Valid status transitions
    const VALID_STATUSES = ['Pending', 'Confirmed', 'Completed', 'Cancelled'];
    
    // Status workflow: what states can transition to what
    const STATUS_TRANSITIONS = [
        'Pending' => ['Confirmed', 'Cancelled'],
        'Confirmed' => ['Completed', 'Cancelled'],
        'Completed' => [],
        'Cancelled' => []
    ];

    // Payment statuses
    const PAYMENT_PENDING = 'Pending';
    const PAYMENT_PAID = 'Paid';
    const PAYMENT_FAILED = 'Failed';
    const VALID_PAYMENT_STATUSES = ['Pending', 'Paid', 'Failed'];

    public function __construct($db, $orderModel, $orderItemModel, $productModel) {
        $this->conn = $db;
        $this->orderModel = $orderModel;
        $this->orderItemModel = $orderItemModel;
        $this->productModel = $productModel;
    }

    /**
     * Create a new order with items in a transaction
     * 
     * @param int $consumer_id - Consumer placing the order
     * @param int $farmer_id - Farmer fulfilling the order
     * @param array $items - Array of items: ['product_id' => int, 'quantity' => int, 'price' => float]
     * @param array $options - Optional: fulfillment_type, delivery_address, pickup_date
     * @return array|false - ['success' => true, 'order_id' => int] or false
     */
    public function createOrder($consumer_id, $farmer_id, $items, $options = []) {
        // Validate inputs
        if (!$consumer_id || !$farmer_id || empty($items)) {
            return false;
        }

        // Begin transaction
        $this->conn->begin_transaction();

        try {
            // Calculate total amount and validate items
            $total_amount = 0;
            $validated_items = [];

            foreach ($items as $item) {
                // Validate item structure
                if (!isset($item['product_id'], $item['quantity'], $item['price'])) {
                    throw new Exception("Invalid item structure");
                }

                $product_id = (int)$item['product_id'];
                $quantity = (int)$item['quantity'];
                $unit_price = (float)$item['price'];

                // Validate quantity
                if ($quantity <= 0) {
                    throw new Exception("Invalid quantity for product $product_id");
                }

                // Check product exists and belongs to farmer
                $product = $this->productModel->getById($product_id);
                if (!$product || $product['farmer_id'] != $farmer_id) {
                    throw new Exception("Product $product_id not found or doesn't belong to farmer");
                }

                // Check product is available
                if (!$product['is_available']) {
                    throw new Exception("Product {$product['name']} is not available");
                }

                // Check inventory
                if ($product['quantity'] < $quantity) {
                    throw new Exception("Insufficient inventory for {$product['name']}. Available: {$product['quantity']}, Requested: $quantity");
                }

                $subtotal = $quantity * $unit_price;
                $total_amount += $subtotal;

                $validated_items[] = [
                    'product_id' => $product_id,
                    'quantity' => $quantity,
                    'unit_price' => $unit_price,
                    'product' => $product
                ];
            }

            // Prepare order data
            $delivery_address = isset($options['delivery_address']) ? $this->conn->real_escape_string($options['delivery_address']) : '';
            $special_instructions = isset($options['special_instructions']) ? $this->conn->real_escape_string($options['special_instructions']) : '';
            $fulfillment_type = isset($options['fulfillment_type']) ? $this->conn->real_escape_string($options['fulfillment_type']) : 'Delivery';
            $pickup_date = isset($options['pickup_date']) ? $this->conn->real_escape_string($options['pickup_date']) : null;

            // Create order
            $order_id = $this->orderModel->create($consumer_id, $farmer_id, $total_amount, $delivery_address, $special_instructions);
            
            if (!$order_id) {
                throw new Exception("Failed to create order");
            }

            // Update order with fulfillment details
            $this->updateOrderFulfillment($order_id, $fulfillment_type, $delivery_address, $pickup_date);

            // Create order items and update inventory
            foreach ($validated_items as $item) {
                $order_item_id = $this->orderItemModel->create(
                    $order_id,
                    $item['product_id'],
                    $item['quantity'],
                    $item['unit_price']
                );

                if (!$order_item_id) {
                    throw new Exception("Failed to create order item");
                }

                // Reduce product quantity
                $new_quantity = $item['product']['quantity'] - $item['quantity'];
                if (!$this->productModel->updateQuantity($item['product_id'], $new_quantity)) {
                    throw new Exception("Failed to update inventory");
                }
            }

            // Commit transaction
            $this->conn->commit();

            return [
                'success' => true,
                'order_id' => $order_id,
                'total_amount' => $total_amount,
                'items_count' => count($validated_items)
            ];

        } catch (Exception $e) {
            // Rollback transaction on error
            $this->conn->rollback();
            error_log("Order creation error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update order fulfillment details
     * 
     * @param int $order_id
     * @param string $fulfillment_type - 'Delivery' or 'Pickup'
     * @param string $delivery_address
     * @param string $pickup_date - Format: YYYY-MM-DD
     */
    private function updateOrderFulfillment($order_id, $fulfillment_type, $delivery_address = '', $pickup_date = null) {
        $order_id = (int)$order_id;
        $fulfillment_type = $this->conn->real_escape_string($fulfillment_type);
        $delivery_address = $this->conn->real_escape_string($delivery_address);
        $pickup_date_sql = $pickup_date ? "'{$this->conn->real_escape_string($pickup_date)}'" : "NULL";

        $query = "UPDATE orders 
                  SET fulfillment_type = '$fulfillment_type',
                      delivery_address = '$delivery_address',
                      pickup_date = $pickup_date_sql
                  WHERE id = $order_id";

        return $this->conn->query($query);
    }

    /**
     * Get all order items with product details
     * 
     * @param int $order_id
     * @return array - Order items with full product information
     */
    public function getOrderWithItems($order_id) {
        $order = $this->orderModel->getById($order_id);
        
        if (!$order) {
            return null;
        }

        $items = $this->orderItemModel->getByOrderId($order_id);

        return [
            'order' => $order,
            'items' => $items,
            'item_count' => count($items),
            'total_items_quantity' => $this->orderItemModel->getTotalItemsCount($order_id)
        ];
    }

    /**
     * Update order status with validation
     * 
     * @param int $order_id
     * @param int $farmer_id - For ownership verification
     * @param string $new_status - New status
     * @return array|false - ['success' => true, 'message' => string] or false
     */
    public function updateOrderStatus($order_id, $farmer_id, $new_status) {
        $order_id = (int)$order_id;
        $farmer_id = (int)$farmer_id;
        $new_status = $this->conn->real_escape_string($new_status);

        // Validate new status
        if (!in_array($new_status, self::VALID_STATUSES)) {
            return ['success' => false, 'message' => 'Invalid status'];
        }

        // Get current order
        $order = $this->orderModel->getById($order_id);
        if (!$order) {
            return ['success' => false, 'message' => 'Order not found'];
        }

        // Verify farmer ownership
        if ($order['farmer_id'] != $farmer_id) {
            return ['success' => false, 'message' => 'Unauthorized: You do not own this order'];
        }

        // Validate status transition
        $current_status = $order['status'];
        if (!$this->isValidStatusTransition($current_status, $new_status)) {
            $allowed = implode(', ', self::STATUS_TRANSITIONS[$current_status] ?? []);
            return [
                'success' => false, 
                'message' => "Cannot transition from '$current_status' to '$new_status'. Allowed transitions: $allowed"
            ];
        }

        // Update status
        if ($this->orderModel->updateStatus($order_id, $farmer_id, $new_status)) {
            return [
                'success' => true,
                'message' => "Order status updated to '$new_status'",
                'from_status' => $current_status,
                'to_status' => $new_status
            ];
        }

        return ['success' => false, 'message' => 'Failed to update order status'];
    }

    /**
     * Validate if status transition is allowed
     * 
     * @param string $from_status
     * @param string $to_status
     * @return bool
     */
    private function isValidStatusTransition($from_status, $to_status) {
        if ($from_status === $to_status) {
            return true; // Same status is allowed
        }

        if (!isset(self::STATUS_TRANSITIONS[$from_status])) {
            return false;
        }

        return in_array($to_status, self::STATUS_TRANSITIONS[$from_status]);
    }

    /**
     * Update payment status with validation
     * 
     * @param int $order_id
     * @param int $farmer_id
     * @param string $payment_status
     * @return array|false
     */
    public function updatePaymentStatus($order_id, $farmer_id, $payment_status) {
        $order_id = (int)$order_id;
        $farmer_id = (int)$farmer_id;
        $payment_status = $this->conn->real_escape_string($payment_status);

        // Validate payment status
        if (!in_array($payment_status, self::VALID_PAYMENT_STATUSES)) {
            return ['success' => false, 'message' => 'Invalid payment status'];
        }

        // Get order
        $order = $this->orderModel->getById($order_id);
        if (!$order) {
            return ['success' => false, 'message' => 'Order not found'];
        }

        // Verify ownership
        if ($order['farmer_id'] != $farmer_id) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        // Update payment status
        if ($this->orderModel->updatePaymentStatus($order_id, $farmer_id, $payment_status)) {
            return [
                'success' => true,
                'message' => "Payment status updated to '$payment_status'",
                'payment_status' => $payment_status
            ];
        }

        return ['success' => false, 'message' => 'Failed to update payment status'];
    }

    /**
     * Get order status history/timeline
     * 
     * @param int $order_id
     * @return array - Formatted timeline events
     */
    public function getOrderTimeline($order_id) {
        $order = $this->orderModel->getById($order_id);
        if (!$order) {
            return [];
        }

        $timeline = [];

        // Order created
        $timeline[] = [
            'date' => $order['created_at'],
            'status' => 'Created',
            'description' => 'Order placed',
            'type' => 'created'
        ];

        // Order confirmed
        if ($order['status'] !== 'pending') {
            $timeline[] = [
                'date' => $order['updated_at'], // Use updated_at for when status changed
                'status' => 'Confirmed',
                'description' => 'Order confirmed by farmer',
                'type' => 'confirmed'
            ];
        }

        // Payment received
        if ($order['payment_status'] === 'Paid') {
            $timeline[] = [
                'date' => $order['created_at'], // Could be enhanced with payment timestamp
                'status' => 'Payment Received',
                'description' => 'Payment confirmed',
                'type' => 'payment'
            ];
        }

        // Order completed
        if ($order['status'] === 'Completed' && $order['completed_at']) {
            $timeline[] = [
                'date' => $order['completed_at'],
                'status' => 'Completed',
                'description' => 'Order completed and delivered/ready for pickup',
                'type' => 'completed'
            ];
        }

        // Order cancelled
        if ($order['status'] === 'Cancelled') {
            $timeline[] = [
                'date' => $order['updated_at'] ?? $order['created_at'],
                'status' => 'Cancelled',
                'description' => 'Order was cancelled',
                'type' => 'cancelled'
            ];
        }

        return $timeline;
    }

    /**
     * Get orders with filters and pagination
     * 
     * @param int $farmer_id
     * @param array $filters - ['status' => '', 'payment_status' => '', 'date_from' => '', 'date_to' => '']
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getFilteredOrders($farmer_id, $filters = [], $limit = 20, $offset = 0) {
        $farmer_id = (int)$farmer_id;
        $limit = (int)$limit;
        $offset = (int)$offset;

        $where = "WHERE o.farmer_id = $farmer_id";

        // Filter by status
        if (!empty($filters['status']) && in_array($filters['status'], self::VALID_STATUSES)) {
            $status = $this->conn->real_escape_string($filters['status']);
            $where .= " AND o.status = '$status'";
        }

        // Filter by payment status
        if (!empty($filters['payment_status']) && in_array($filters['payment_status'], self::VALID_PAYMENT_STATUSES)) {
            $payment_status = $this->conn->real_escape_string($filters['payment_status']);
            $where .= " AND o.payment_status = '$payment_status'";
        }

        // Filter by date range
        if (!empty($filters['date_from'])) {
            $date_from = $this->conn->real_escape_string($filters['date_from']);
            $where .= " AND DATE(o.created_at) >= '$date_from'";
        }
        if (!empty($filters['date_to'])) {
            $date_to = $this->conn->real_escape_string($filters['date_to']);
            $where .= " AND DATE(o.created_at) <= '$date_to'";
        }

        $query = "SELECT o.*, 
                  u_consumer.first_name as consumer_first_name,
                  u_consumer.last_name as consumer_last_name,
                  u_consumer.email as consumer_email,
                  COUNT(oi.id) as item_count
                  FROM orders o
                  LEFT JOIN users u_consumer ON o.consumer_id = u_consumer.id
                  LEFT JOIN order_items oi ON o.id = oi.order_id
                  $where
                  GROUP BY o.id
                  ORDER BY o.created_at DESC
                  LIMIT $limit OFFSET $offset";

        $result = $this->conn->query($query);
        $orders = [];

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $orders[] = $row;
            }
        }

        return $orders;
    }

    /**
     * Get order statistics
     * 
     * @param int $farmer_id
     * @return array - Statistics about orders
     */
    public function getOrderStatistics($farmer_id) {
        $farmer_id = (int)$farmer_id;

        $query = "SELECT 
                  COUNT(*) as total_orders,
                  SUM(total_amount) as total_revenue,
                  AVG(total_amount) as avg_order_value,
                  MAX(total_amount) as highest_order,
                  MIN(total_amount) as lowest_order,
                  COUNT(CASE WHEN status = 'Completed' THEN 1 END) as completed_orders,
                  COUNT(CASE WHEN status = 'Pending' THEN 1 END) as pending_orders,
                  COUNT(CASE WHEN status = 'Confirmed' THEN 1 END) as confirmed_orders,
                  COUNT(CASE WHEN status = 'Cancelled' THEN 1 END) as cancelled_orders,
                  COUNT(CASE WHEN payment_status = 'Paid' THEN 1 END) as paid_orders,
                  COUNT(CASE WHEN payment_status = 'Pending' THEN 1 END) as pending_payment_orders
                  FROM orders 
                  WHERE farmer_id = $farmer_id";

        $result = $this->conn->query($query);
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }

        return null;
    }

    /**
     * Get product-order relationship details
     * Shows which products are in orders and how many
     * 
     * @param int $farmer_id
     * @return array - Product sales data
     */
    public function getProductOrderRelationships($farmer_id) {
        $farmer_id = (int)$farmer_id;

        $query = "SELECT 
                  p.id,
                  p.name,
                  p.category,
                  p.unit,
                  COUNT(DISTINCT oi.order_id) as orders_count,
                  SUM(oi.quantity) as total_quantity_sold,
                  SUM(oi.quantity * oi.unit_price) as total_revenue,
                  AVG(oi.quantity) as avg_quantity_per_order,
                  MIN(oi.unit_price) as min_price,
                  MAX(oi.unit_price) as max_price
                  FROM products p
                  LEFT JOIN order_items oi ON p.id = oi.product_id
                  LEFT JOIN orders o ON oi.order_id = o.id
                  WHERE p.farmer_id = $farmer_id
                  GROUP BY p.id, p.name, p.category, p.unit
                  ORDER BY total_revenue DESC";

        $result = $this->conn->query($query);
        $relationships = [];

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $relationships[] = $row;
            }
        }

        return $relationships;
    }

    /**
     * Cancel an order
     * 
     * @param int $order_id
     * @param int $farmer_id
     * @param string $reason - Optional cancellation reason
     * @return array|false
     */
    public function cancelOrder($order_id, $farmer_id, $reason = '') {
        $order_id = (int)$order_id;
        $farmer_id = (int)$farmer_id;

        // Get order
        $order = $this->orderModel->getById($order_id);
        if (!$order) {
            return ['success' => false, 'message' => 'Order not found'];
        }

        // Verify ownership
        if ($order['farmer_id'] != $farmer_id) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        // Check if already completed or cancelled
        if (in_array($order['status'], ['Completed', 'Cancelled'])) {
            return ['success' => false, 'message' => "Cannot cancel order with status: {$order['status']}"];
        }

        // Begin transaction for inventory restoration
        $this->conn->begin_transaction();

        try {
            // Get order items
            $items = $this->orderItemModel->getByOrderId($order_id);

            // Restore inventory
            foreach ($items as $item) {
                $product = $this->productModel->getById($item['product_id']);
                if ($product) {
                    $new_quantity = $product['quantity'] + $item['quantity'];
                    if (!$this->productModel->updateQuantity($item['product_id'], $new_quantity)) {
                        throw new Exception("Failed to restore inventory for product {$item['product_id']}");
                    }
                }
            }

            // Update order status
            if (!$this->orderModel->updateStatus($order_id, $farmer_id, 'Cancelled')) {
                throw new Exception("Failed to update order status");
            }

            $this->conn->commit();

            return [
                'success' => true,
                'message' => 'Order cancelled successfully and inventory restored',
                'order_id' => $order_id
            ];

        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Order cancellation error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to cancel order'];
        }
    }

    /**
     * Validate order can be processed
     * 
     * @param int $order_id
     * @return array ['valid' => bool, 'errors' => []]
     */
    public function validateOrderForProcessing($order_id) {
        $order = $this->orderModel->getById($order_id);
        $errors = [];

        if (!$order) {
            return ['valid' => false, 'errors' => ['Order not found']];
        }

        // Check order has items
        $items = $this->orderItemModel->getByOrderId($order_id);
        if (empty($items)) {
            $errors[] = 'Order has no items';
        }

        // Check all items have valid product references
        foreach ($items as $item) {
            if (!$item['product_id'] || !$item['quantity']) {
                $errors[] = 'Invalid item data';
            }
        }

        // Check required fulfillment info
        if ($order['fulfillment_type'] === 'Delivery' && empty($order['delivery_address'])) {
            $errors[] = 'Delivery address is required';
        }

        if ($order['fulfillment_type'] === 'Pickup' && empty($order['pickup_date'])) {
            $errors[] = 'Pickup date is required';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'order_id' => $order_id,
            'status' => $order['status']
        ];
    }
}
?>
