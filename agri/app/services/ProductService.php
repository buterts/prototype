<?php
/**
 * Product Service
 */

class ProductService {
    private $productModel;
    private $conn;

    public function __construct($productModel, $db) {
        $this->productModel = $productModel;
        $this->conn = $db;
    }

    /**
     * Add new product
     */
    public function addProduct($farmer_id, $name, $description, $category, $price, $quantity, $unit = 'kg') {
        // Validate input
        if (empty($name) || empty($category) || empty($price) || empty($quantity)) {
            return ['success' => false, 'message' => 'All fields are required'];
        }

        if ($price <= 0 || $quantity < 0) {
            return ['success' => false, 'message' => 'Price must be positive and quantity cannot be negative'];
        }

        $product_id = $this->productModel->create($farmer_id, $name, $description, $category, $price, $quantity, $unit);

        if (!$product_id) {
            return ['success' => false, 'message' => 'Failed to add product'];
        }

        return ['success' => true, 'message' => 'Product added successfully', 'product_id' => $product_id];
    }

    /**
     * Update product
     */
    public function updateProduct($farmer_id, $product_id, $data) {
        $product = $this->productModel->getById($product_id);

        if (!$product) {
            return ['success' => false, 'message' => 'Product not found'];
        }

        if ($product['farmer_id'] != $farmer_id) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        if (!$this->productModel->update($product_id, $farmer_id, $data)) {
            return ['success' => false, 'message' => 'Failed to update product'];
        }

        return ['success' => true, 'message' => 'Product updated successfully'];
    }

    /**
     * Delete product
     */
    public function deleteProduct($farmer_id, $product_id) {
        $product = $this->productModel->getById($product_id);

        if (!$product) {
            return ['success' => false, 'message' => 'Product not found'];
        }

        if ($product['farmer_id'] != $farmer_id) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        // Check if product has orders
        $query = "SELECT COUNT(*) as count FROM order_items WHERE product_id = " . (int)$product_id;
        $result = $this->conn->query($query);
        $row = $result->fetch_assoc();

        if ($row['count'] > 0) {
            return ['success' => false, 'message' => 'Cannot delete product with existing orders'];
        }

        if (!$this->productModel->delete($product_id, $farmer_id)) {
            return ['success' => false, 'message' => 'Failed to delete product'];
        }

        return ['success' => true, 'message' => 'Product deleted successfully'];
    }

    /**
     * Get product details
     */
    public function getProduct($farmer_id, $product_id) {
        $product = $this->productModel->getById($product_id);

        if (!$product) {
            return ['success' => false, 'message' => 'Product not found'];
        }

        if ($product['farmer_id'] != $farmer_id) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        return ['success' => true, 'data' => $product];
    }

    /**
     * List farmer's products
     */
    public function listProducts($farmer_id, $page = 1, $per_page = 50) {
        $offset = ($page - 1) * $per_page;
        $products = $this->productModel->getByFarmerId($farmer_id, $per_page, $offset);
        $total = $this->productModel->getTotalCountByFarmerId($farmer_id);

        return [
            'success' => true,
            'data' => $products,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $per_page,
                'total_pages' => ceil($total / $per_page)
            ]
        ];
    }

    /**
     * Search products
     */
    public function searchProducts($farmer_id, $keyword) {
        if (empty($keyword)) {
            return ['success' => false, 'message' => 'Search keyword is required'];
        }

        $products = $this->productModel->search($farmer_id, $keyword);

        return ['success' => true, 'data' => $products];
    }

    /**
     * Get product statistics
     */
    public function getProductStats($farmer_id) {
        $total = $this->productModel->getTotalCountByFarmerId($farmer_id);
        $products = $this->productModel->getByFarmerId($farmer_id, 1000);

        $available_count = 0;
        $total_quantity = 0;
        $total_value = 0;

        foreach ($products as $product) {
            if ($product['is_available'] && $product['quantity'] > 0) {
                $available_count++;
            }
            $total_quantity += $product['quantity'];
            $total_value += $product['price'] * $product['quantity'];
        }

        return [
            'success' => true,
            'data' => [
                'total_products' => $total,
                'available_products' => $available_count,
                'total_quantity' => $total_quantity,
                'total_inventory_value' => number_format($total_value, 2)
            ]
        ];
    }
}
?>
