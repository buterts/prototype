<?php
/**
 * Cart Service
 */

class CartService {
    private $cartModel;
    private $productModel;
    private $conn;

    public function __construct($cartModel, $productModel, $db) {
        $this->cartModel = $cartModel;
        $this->productModel = $productModel;
        $this->conn = $db;
    }

    /**
     * Add product to cart
     */
    public function addToCart($consumer_id, $product_id, $quantity = 1) {
        if ($quantity <= 0) {
            return ['success' => false, 'message' => 'Quantity must be greater than 0'];
        }

        $product = $this->productModel->getById($product_id);

        if (!$product) {
            return ['success' => false, 'message' => 'Product not found'];
        }

        if (!$product['is_available']) {
            return ['success' => false, 'message' => 'Product is not available'];
        }

        if ($product['quantity'] < $quantity) {
            return ['success' => false, 'message' => 'Insufficient stock. Available: ' . $product['quantity']];
        }

        $farmer_id = $product['farmer_id'];

        if (!$this->cartModel->addItem($consumer_id, $farmer_id, $product_id, $quantity)) {
            return ['success' => false, 'message' => 'Failed to add item to cart'];
        }

        return [
            'success' => true,
            'message' => 'Product added to cart successfully',
            'cart_count' => $this->cartModel->getCartItemCount($consumer_id)
        ];
    }

    /**
     * View cart
     */
    public function getCart($consumer_id) {
        $cartItems = $this->cartModel->getCartByConsumer($consumer_id);
        $total = $this->cartModel->getCartTotal($consumer_id);

        return [
            'success' => true,
            'data' => $cartItems,
            'total' => $total,
            'count' => count($cartItems)
        ];
    }

    /**
     * Get cart grouped by farmer
     */
    public function getCartGroupedByFarmer($consumer_id) {
        $cartByFarmer = $this->cartModel->getCartByFarmer($consumer_id);
        $groupedCart = [];

        foreach ($cartByFarmer as $farmerData) {
            $items = [];
            if (!empty($farmerData['items'])) {
                $itemsArray = explode('}, {', $farmerData['items']);
                foreach ($itemsArray as $index => $itemJson) {
                    // Clean up JSON format
                    if ($index > 0) $itemJson = '{' . $itemJson;
                    if ($index < count($itemsArray) - 1) $itemJson = $itemJson . '}';
                    
                    $item = json_decode($itemJson, true);
                    if ($item) {
                        $items[] = $item;
                    }
                }
            }

            $groupedCart[] = [
                'farmer_id' => $farmerData['farmer_id'],
                'farm_name' => $farmerData['farm_name'],
                'farmer_email' => $farmerData['email'],
                'items' => $items,
                'subtotal' => array_sum(array_map(function($item) { return $item['subtotal']; }, $items))
            ];
        }

        $total = array_sum(array_map(function($f) { return $f['subtotal']; }, $groupedCart));

        return [
            'success' => true,
            'data' => $groupedCart,
            'total' => $total,
            'count' => count(array_merge(...array_map(function($f) { return $f['items']; }, $groupedCart)))
        ];
    }

    /**
     * Remove item from cart
     */
    public function removeFromCart($consumer_id, $cart_id) {
        if (!$this->cartModel->removeItem($consumer_id, $cart_id)) {
            return ['success' => false, 'message' => 'Failed to remove item'];
        }

        return [
            'success' => true,
            'message' => 'Item removed from cart',
            'cart_count' => $this->cartModel->getCartItemCount($consumer_id)
        ];
    }

    /**
     * Update quantity
     */
    public function updateQuantity($consumer_id, $farmer_id, $product_id, $quantity) {
        if ($quantity < 0) {
            return ['success' => false, 'message' => 'Invalid quantity'];
        }

        $product = $this->productModel->getById($product_id);

        if (!$product) {
            return ['success' => false, 'message' => 'Product not found'];
        }

        if ($quantity > 0 && $product['quantity'] < $quantity) {
            return ['success' => false, 'message' => 'Insufficient stock'];
        }

        if (!$this->cartModel->updateQuantity($consumer_id, $farmer_id, $product_id, $quantity)) {
            return ['success' => false, 'message' => 'Failed to update quantity'];
        }

        return [
            'success' => true,
            'message' => 'Quantity updated',
            'cart_count' => $this->cartModel->getCartItemCount($consumer_id)
        ];
    }

    /**
     * Clear cart
     */
    public function clearCart($consumer_id) {
        if (!$this->cartModel->clearCart($consumer_id)) {
            return ['success' => false, 'message' => 'Failed to clear cart'];
        }

        return ['success' => true, 'message' => 'Cart cleared'];
    }
}
?>
