<?php
/**
 * Shopping Cart Model
 */

class ShoppingCart {
    private $conn;
    private $table = 'shopping_carts';

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Add item to cart
     */
    public function addItem($consumer_id, $farmer_id, $product_id, $quantity = 1) {
        $consumer_id = (int)$consumer_id;
        $farmer_id = (int)$farmer_id;
        $product_id = (int)$product_id;
        $quantity = (int)$quantity;

        // Check if item already in cart
        $query = "SELECT id FROM $this->table 
                  WHERE consumer_id = $consumer_id AND farmer_id = $farmer_id AND product_id = $product_id";
        $result = $this->conn->query($query);

        if ($result && $result->num_rows > 0) {
            // Update quantity
            return $this->updateQuantity($consumer_id, $farmer_id, $product_id, $quantity);
        } else {
            // Insert new item
            $query = "INSERT INTO $this->table 
                      (consumer_id, farmer_id, product_id, quantity)
                      VALUES ($consumer_id, $farmer_id, $product_id, $quantity)";
            
            return $this->conn->query($query) ? true : false;
        }
    }

    /**
     * Get cart items for consumer
     */
    public function getCartByConsumer($consumer_id) {
        $consumer_id = (int)$consumer_id;
        $query = "SELECT sc.*, p.name, p.price, p.category, p.unit, p.quantity as available_qty
                  FROM $this->table sc
                  LEFT JOIN products p ON sc.product_id = p.id
                  WHERE sc.consumer_id = $consumer_id
                  ORDER BY sc.farmer_id, sc.added_at DESC";

        $result = $this->conn->query($query);
        $cart = [];

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $cart[] = $row;
            }
        }
        return $cart;
    }

    /**
     * Get cart items grouped by farmer
     */
    public function getCartByFarmer($consumer_id) {
        $consumer_id = (int)$consumer_id;
        $query = "SELECT 
                    u.id as farmer_id,
                    u.first_name,
                    u.last_name,
                    u.email,
                    fp.farm_name,
                    GROUP_CONCAT(
                        JSON_OBJECT(
                            'id', sc.id,
                            'product_id', sc.product_id,
                            'product_name', p.name,
                            'quantity', sc.quantity,
                            'price', p.price,
                            'unit', p.unit,
                            'available_qty', p.quantity,
                            'subtotal', sc.quantity * p.price
                        )
                    ) as items
                  FROM $this->table sc
                  LEFT JOIN products p ON sc.product_id = p.id
                  LEFT JOIN users u ON p.farmer_id = u.id
                  LEFT JOIN farmer_profiles fp ON u.id = fp.user_id
                  WHERE sc.consumer_id = $consumer_id
                  GROUP BY p.farmer_id
                  ORDER BY u.id";

        $result = $this->conn->query($query);
        $groupedCart = [];

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $groupedCart[] = $row;
            }
        }
        return $groupedCart;
    }

    /**
     * Remove item from cart
     */
    public function removeItem($consumer_id, $cart_id) {
        $consumer_id = (int)$consumer_id;
        $cart_id = (int)$cart_id;

        // Verify ownership
        $query = "DELETE FROM $this->table 
                  WHERE id = $cart_id AND consumer_id = $consumer_id";
        
        return $this->conn->query($query);
    }

    /**
     * Update item quantity
     */
    public function updateQuantity($consumer_id, $farmer_id, $product_id, $quantity) {
        $consumer_id = (int)$consumer_id;
        $farmer_id = (int)$farmer_id;
        $product_id = (int)$product_id;
        $quantity = (int)$quantity;

        if ($quantity <= 0) {
            // Remove if quantity is 0 or less
            return $this->removeItemByProduct($consumer_id, $farmer_id, $product_id);
        }

        $query = "UPDATE $this->table 
                  SET quantity = $quantity
                  WHERE consumer_id = $consumer_id AND farmer_id = $farmer_id AND product_id = $product_id";
        
        return $this->conn->query($query);
    }

    /**
     * Remove item by product ID
     */
    public function removeItemByProduct($consumer_id, $farmer_id, $product_id) {
        $consumer_id = (int)$consumer_id;
        $farmer_id = (int)$farmer_id;
        $product_id = (int)$product_id;

        $query = "DELETE FROM $this->table 
                  WHERE consumer_id = $consumer_id AND farmer_id = $farmer_id AND product_id = $product_id";
        
        return $this->conn->query($query);
    }

    /**
     * Clear cart for consumer
     */
    public function clearCart($consumer_id) {
        $consumer_id = (int)$consumer_id;
        $query = "DELETE FROM $this->table WHERE consumer_id = $consumer_id";
        return $this->conn->query($query);
    }

    /**
     * Get cart item count
     */
    public function getCartItemCount($consumer_id) {
        $consumer_id = (int)$consumer_id;
        $query = "SELECT COUNT(*) as count FROM $this->table WHERE consumer_id = $consumer_id";
        
        $result = $this->conn->query($query);
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['count'];
        }
        return 0;
    }

    /**
     * Get cart total
     */
    public function getCartTotal($consumer_id) {
        $consumer_id = (int)$consumer_id;
        $query = "SELECT SUM(sc.quantity * p.price) as total
                  FROM $this->table sc
                  LEFT JOIN products p ON sc.product_id = p.id
                  WHERE sc.consumer_id = $consumer_id";
        
        $result = $this->conn->query($query);
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['total'] ?? 0;
        }
        return 0;
    }

    /**
     * Clear farmer-specific cart items
     */
    public function clearFarmerCart($consumer_id, $farmer_id) {
        $consumer_id = (int)$consumer_id;
        $farmer_id = (int)$farmer_id;

        $query = "DELETE FROM $this->table 
                  WHERE consumer_id = $consumer_id AND farmer_id = $farmer_id";
        
        return $this->conn->query($query);
    }
}
?>
