<?php
/**
 * Order Model
 */

class Order {
    private $conn;
    private $table = 'orders';

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Create a new order
     */
    public function create($consumer_id, $farmer_id, $total_amount, $delivery_address = '', $special_instructions = '') {
        $consumer_id = (int)$consumer_id;
        $farmer_id = (int)$farmer_id;
        $total_amount = (float)$total_amount;
        $delivery_address = $this->conn->real_escape_string($delivery_address);
        $special_instructions = $this->conn->real_escape_string($special_instructions);

        // Generate unique order number
        $order_number = 'ORD-' . date('Ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

        $query = "INSERT INTO $this->table 
                  (order_number, consumer_id, farmer_id, total_amount, delivery_address, special_instructions) 
                  VALUES ('$order_number', $consumer_id, $farmer_id, $total_amount, '$delivery_address', '$special_instructions')";

        if ($this->conn->query($query)) {
            return $this->conn->insert_id;
        }
        return false;
    }

    /**
     * Get order by ID
     */
    public function getById($id) {
        $id = (int)$id;
        $query = "SELECT o.*, 
                  u_consumer.first_name as consumer_first_name,
                  u_consumer.last_name as consumer_last_name,
                  u_consumer.email as consumer_email,
                  u_consumer.phone as consumer_phone
                  FROM $this->table o
                  LEFT JOIN users u_consumer ON o.consumer_id = u_consumer.id
                  WHERE o.id = $id";

        $result = $this->conn->query($query);
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return null;
    }

    /**
     * Get orders for a farmer
     */
    public function getByFarmerId($farmer_id, $limit = 50, $offset = 0) {
        $farmer_id = (int)$farmer_id;
        $limit = (int)$limit;
        $offset = (int)$offset;

        $query = "SELECT o.*, 
                  u_consumer.first_name as consumer_first_name,
                  u_consumer.last_name as consumer_last_name,
                  u_consumer.email as consumer_email,
                  u_consumer.phone as consumer_phone
                  FROM $this->table o
                  LEFT JOIN users u_consumer ON o.consumer_id = u_consumer.id
                  WHERE o.farmer_id = $farmer_id
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
     * Get total orders count for farmer
     */
    public function getTotalCountByFarmerId($farmer_id) {
        $farmer_id = (int)$farmer_id;
        $query = "SELECT COUNT(*) as total FROM $this->table WHERE farmer_id = $farmer_id";

        $result = $this->conn->query($query);
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['total'];
        }
        return 0;
    }

    /**
     * Update order status
     */
    public function updateStatus($id, $farmer_id, $status) {
        $id = (int)$id;
        $farmer_id = (int)$farmer_id;
        $status = $this->conn->real_escape_string($status);

        // Verify ownership
        $order = $this->getById($id);
        if (!$order || $order['farmer_id'] != $farmer_id) {
            return false;
        }

        $completed_at = ($status === 'completed') ? 'NOW()' : 'NULL';

        $query = "UPDATE $this->table 
                  SET status = '$status', completed_at = $completed_at 
                  WHERE id = $id";

        return $this->conn->query($query);
    }

    /**
     * Update payment status
     */
    public function updatePaymentStatus($id, $farmer_id, $payment_status) {
        $id = (int)$id;
        $farmer_id = (int)$farmer_id;
        $payment_status = $this->conn->real_escape_string($payment_status);

        // Verify ownership
        $order = $this->getById($id);
        if (!$order || $order['farmer_id'] != $farmer_id) {
            return false;
        }

        $query = "UPDATE $this->table SET payment_status = '$payment_status' WHERE id = $id";
        return $this->conn->query($query);
    }

    /**
     * Get orders by status
     */
    public function getByStatus($farmer_id, $status) {
        $farmer_id = (int)$farmer_id;
        $status = $this->conn->real_escape_string($status);

        $query = "SELECT o.*, 
                  u_consumer.first_name as consumer_first_name,
                  u_consumer.last_name as consumer_last_name
                  FROM $this->table o
                  LEFT JOIN users u_consumer ON o.consumer_id = u_consumer.id
                  WHERE o.farmer_id = $farmer_id AND o.status = '$status'
                  ORDER BY o.created_at DESC";

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
     * Get sales summary
     */
    public function getSalesSummary($farmer_id) {
        $farmer_id = (int)$farmer_id;
        $query = "SELECT 
                  COUNT(DISTINCT id) as total_orders,
                  SUM(total_amount) as total_revenue,
                  COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_orders,
                  COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_orders,
                  COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_orders,
                  COUNT(CASE WHEN payment_status = 'paid' THEN 1 END) as paid_orders
                  FROM $this->table 
                  WHERE farmer_id = $farmer_id";

        $result = $this->conn->query($query);
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return null;
    }

    /**
     * Get monthly sales
     */
    public function getMonthlySales($farmer_id) {
        $farmer_id = (int)$farmer_id;
        $query = "SELECT 
                  DATE_FORMAT(created_at, '%Y-%m') as month,
                  COUNT(*) as order_count,
                  SUM(total_amount) as revenue
                  FROM $this->table
                  WHERE farmer_id = $farmer_id
                  GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                  ORDER BY month DESC
                  LIMIT 12";

        $result = $this->conn->query($query);
        $sales = [];

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sales[] = $row;
            }
        }
        return $sales;
    }

    /**
     * Get orders by consumer ID
     */
    public function getByConsumerId($consumer_id, $limit = 50, $offset = 0) {
        $consumer_id = (int)$consumer_id;
        $limit = (int)$limit;
        $offset = (int)$offset;

        $query = "SELECT o.*, 
                  u_farmer.first_name as farmer_first_name,
                  u_farmer.last_name as farmer_last_name
                  FROM $this->table o
                  LEFT JOIN users u_farmer ON o.farmer_id = u_farmer.id
                  WHERE o.consumer_id = $consumer_id
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
     * Get order count for consumer
     */
    public function getConsumerOrderCount($consumer_id) {
        $consumer_id = (int)$consumer_id;
        $query = "SELECT COUNT(*) as total FROM $this->table WHERE consumer_id = $consumer_id";
        
        $result = $this->conn->query($query);
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['total'];
        }
        return 0;
    }

    /**
     * Get total spending for consumer
     */
    public function getConsumerTotalSpending($consumer_id) {
        $consumer_id = (int)$consumer_id;
        $query = "SELECT SUM(total_amount) as total_spent FROM $this->table WHERE consumer_id = $consumer_id";
        
        $result = $this->conn->query($query);
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['total_spent'] ?? 0;
        }
        return 0;
    }

    /**
     * Get orders by status for consumer
     */
    public function getConsumerOrdersByStatus($consumer_id, $status) {
        $consumer_id = (int)$consumer_id;
        $status = $this->conn->real_escape_string($status);

        $query = "SELECT o.* FROM $this->table o
                  WHERE o.consumer_id = $consumer_id AND o.status = '$status'
                  ORDER BY o.created_at DESC";

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
     * Confirm an order (status: Pending -> Confirmed)
     */
    public function confirmOrder($id) {
        $id = (int)$id;
        $query = "UPDATE $this->table 
                  SET status = 'Confirmed', confirmed_at = NOW() 
                  WHERE id = $id AND status = 'Pending'";

        return $this->conn->query($query);
    }

    /**
     * Complete an order (status: Confirmed -> Completed)
     */
    public function completeOrder($id) {
        $id = (int)$id;
        $query = "UPDATE $this->table 
                  SET status = 'Completed', completed_at = NOW() 
                  WHERE id = $id AND status = 'Confirmed'";

        return $this->conn->query($query);
    }

    /**
     * Get order with all details
     */
    public function getOrderDetails($id) {
        $id = (int)$id;
        $query = "SELECT o.*, 
                  u_consumer.first_name as consumer_first_name,
                  u_consumer.last_name as consumer_last_name,
                  u_consumer.email as consumer_email,
                  u_consumer.phone as consumer_phone,
                  u_farmer.first_name as farmer_first_name,
                  u_farmer.last_name as farmer_last_name,
                  u_farmer.email as farmer_email,
                  fp.farm_name
                  FROM $this->table o
                  LEFT JOIN users u_consumer ON o.consumer_id = u_consumer.id
                  LEFT JOIN users u_farmer ON o.farmer_id = u_farmer.id
                  LEFT JOIN farmer_profiles fp ON u_farmer.id = fp.user_id
                  WHERE o.id = $id";

        $result = $this->conn->query($query);
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return null;
    }

    /**
     * Search orders by order number
     */
    public function searchByOrderNumber($order_number) {
        $order_number = $this->conn->real_escape_string($order_number);
        $query = "SELECT o.* FROM $this->table o
                  WHERE o.order_number LIKE '%$order_number%'
                  ORDER BY o.created_at DESC
                  LIMIT 10";

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
     * Get pending orders for a farmer
     */
    public function getPendingOrdersForFarmer($farmer_id) {
        $farmer_id = (int)$farmer_id;
        $query = "SELECT o.*, COUNT(oi.id) as item_count
                  FROM $this->table o
                  LEFT JOIN order_items oi ON o.id = oi.order_id
                  WHERE o.farmer_id = $farmer_id AND o.status = 'Pending'
                  GROUP BY o.id
                  ORDER BY o.created_at ASC";

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
     * Get revenue for date range
     */
    public function getRevenueForDateRange($farmer_id, $date_from, $date_to) {
        $farmer_id = (int)$farmer_id;
        $date_from = $this->conn->real_escape_string($date_from);
        $date_to = $this->conn->real_escape_string($date_to);

        $query = "SELECT 
                  COUNT(*) as order_count,
                  SUM(total_amount) as revenue,
                  AVG(total_amount) as avg_order_value
                  FROM $this->table
                  WHERE farmer_id = $farmer_id 
                  AND DATE(created_at) >= '$date_from'
                  AND DATE(created_at) <= '$date_to'
                  AND status != 'Cancelled'";

        $result = $this->conn->query($query);
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return null;
    }
}
?>
