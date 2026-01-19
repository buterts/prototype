<?php
/**
 * Order Item Model
 */

class OrderItem {
    private $conn;
    private $table = 'order_items';

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Create order item
     */
    public function create($order_id, $product_id, $quantity, $unit_price) {
        $order_id = (int)$order_id;
        $product_id = (int)$product_id;
        $quantity = (int)$quantity;
        $unit_price = (float)$unit_price;
        $subtotal = $quantity * $unit_price;

        $query = "INSERT INTO $this->table 
                  (order_id, product_id, quantity, unit_price, subtotal) 
                  VALUES ($order_id, $product_id, $quantity, $unit_price, $subtotal)";

        return $this->conn->query($query) ? $this->conn->insert_id : false;
    }

    /**
     * Get items for an order
     */
    public function getByOrderId($order_id) {
        $order_id = (int)$order_id;
        $query = "SELECT oi.*, p.name, p.category
                  FROM $this->table oi
                  LEFT JOIN products p ON oi.product_id = p.id
                  WHERE oi.order_id = $order_id
                  ORDER BY oi.created_at ASC";

        $result = $this->conn->query($query);
        $items = [];

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $items[] = $row;
            }
        }
        return $items;
    }

    /**
     * Get total items count for order
     */
    public function getTotalItemsCount($order_id) {
        $order_id = (int)$order_id;
        $query = "SELECT SUM(quantity) as total_items FROM $this->table WHERE order_id = $order_id";

        $result = $this->conn->query($query);
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['total_items'] ?? 0;
        }
        return 0;
    }

    /**
     * Delete order item
     */
    public function deleteByOrderId($order_id) {
        $order_id = (int)$order_id;
        $query = "DELETE FROM $this->table WHERE order_id = $order_id";
        return $this->conn->query($query);
    }
}
?>
