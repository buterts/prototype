<?php
/**
 * Product Model
 */

class Product {
    private $conn;
    private $table = 'products';

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Create a new product
     */
    public function create($farmer_id, $name, $description, $category, $price, $quantity, $unit = 'kg') {
        $farmer_id = (int)$farmer_id;
        $name = $this->conn->real_escape_string($name);
        $description = $this->conn->real_escape_string($description);
        $category = $this->conn->real_escape_string($category);
        $price = (float)$price;
        $quantity = (int)$quantity;
        $unit = $this->conn->real_escape_string($unit);

        $query = "INSERT INTO $this->table 
                  (farmer_id, name, description, category, price, quantity, unit) 
                  VALUES ($farmer_id, '$name', '$description', '$category', $price, $quantity, '$unit')";

        if ($this->conn->query($query)) {
            return $this->conn->insert_id;
        }
        return false;
    }

    /**
     * Get product by ID
     */
    public function getById($id) {
        $id = (int)$id;
        $query = "SELECT * FROM $this->table WHERE id = $id";
        
        $result = $this->conn->query($query);
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return null;
    }

    /**
     * Get all products for a farmer
     */
    public function getByFarmerId($farmer_id, $limit = 50, $offset = 0) {
        $farmer_id = (int)$farmer_id;
        $limit = (int)$limit;
        $offset = (int)$offset;

        $query = "SELECT * FROM $this->table 
                  WHERE farmer_id = $farmer_id 
                  ORDER BY created_at DESC 
                  LIMIT $limit OFFSET $offset";

        $result = $this->conn->query($query);
        $products = [];
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $products[] = $row;
            }
        }
        return $products;
    }

    /**
     * Get total products count for farmer
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
     * Update product
     */
    public function update($id, $farmer_id, $data) {
        $id = (int)$id;
        $farmer_id = (int)$farmer_id;
        $updates = [];

        // Verify ownership
        $product = $this->getById($id);
        if (!$product || $product['farmer_id'] != $farmer_id) {
            return false;
        }

        if (isset($data['name'])) {
            $updates[] = "name = '" . $this->conn->real_escape_string($data['name']) . "'";
        }
        if (isset($data['description'])) {
            $updates[] = "description = '" . $this->conn->real_escape_string($data['description']) . "'";
        }
        if (isset($data['category'])) {
            $updates[] = "category = '" . $this->conn->real_escape_string($data['category']) . "'";
        }
        if (isset($data['price'])) {
            $updates[] = "price = " . (float)$data['price'];
        }
        if (isset($data['quantity'])) {
            $updates[] = "quantity = " . (int)$data['quantity'];
        }
        if (isset($data['unit'])) {
            $updates[] = "unit = '" . $this->conn->real_escape_string($data['unit']) . "'";
        }
        if (isset($data['is_available'])) {
            $updates[] = "is_available = " . ($data['is_available'] ? 1 : 0);
        }

        if (empty($updates)) {
            return false;
        }

        $query = "UPDATE $this->table SET " . implode(", ", $updates) . " WHERE id = $id";
        return $this->conn->query($query);
    }

    /**
     * Delete product
     */
    public function delete($id, $farmer_id) {
        $id = (int)$id;
        $farmer_id = (int)$farmer_id;

        // Verify ownership
        $product = $this->getById($id);
        if (!$product || $product['farmer_id'] != $farmer_id) {
            return false;
        }

        $query = "DELETE FROM $this->table WHERE id = $id";
        return $this->conn->query($query);
    }

    /**
     * Search products
     */
    public function search($farmer_id, $keyword) {
        $farmer_id = (int)$farmer_id;
        $keyword = $this->conn->real_escape_string($keyword);

        $query = "SELECT * FROM $this->table 
                  WHERE farmer_id = $farmer_id 
                  AND (name LIKE '%$keyword%' OR description LIKE '%$keyword%' OR category LIKE '%$keyword%')
                  ORDER BY created_at DESC";

        $result = $this->conn->query($query);
        $products = [];
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $products[] = $row;
            }
        }
        return $products;
    }

    /**
     * Update product quantity
     */
    public function updateQuantity($id, $quantity) {
        $id = (int)$id;
        $quantity = (int)$quantity;

        $query = "UPDATE $this->table SET quantity = quantity + $quantity WHERE id = $id";
        return $this->conn->query($query);
    }

    /**
     * Get available products
     */
    public function getAvailableByFarmerId($farmer_id) {
        $farmer_id = (int)$farmer_id;
        $query = "SELECT * FROM $this->table 
                  WHERE farmer_id = $farmer_id AND is_available = 1 AND quantity > 0
                  ORDER BY name ASC";

        $result = $this->conn->query($query);
        $products = [];
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $products[] = $row;
            }
        }
        return $products;
    }
}
?>
