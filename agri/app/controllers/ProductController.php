<?php
/**
 * Product Controller
 */

class ProductController {
    private $productService;

    public function __construct($productService) {
        $this->productService = $productService;
    }

    /**
     * Show products list
     */
    public function listProducts($farmer_id, $page = 1) {
        return $this->productService->listProducts($farmer_id, $page);
    }

    /**
     * Show add product form
     */
    public function showAddProductForm() {
        return 'farmer/products/add';
    }

    /**
     * Handle add product
     */
    public function handleAddProduct($farmer_id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . BASE_URL . "farmer/products/list.php");
            exit;
        }

        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';
        $category = $_POST['category'] ?? '';
        $price = $_POST['price'] ?? '';
        $quantity = $_POST['quantity'] ?? '';
        $unit = $_POST['unit'] ?? 'kg';

        $result = $this->productService->addProduct($farmer_id, $name, $description, $category, $price, $quantity, $unit);

        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
            header("Location: " . BASE_URL . "farmer/products/list.php");
            exit;
        } else {
            $_SESSION['error'] = $result['message'];
            header("Location: " . BASE_URL . "farmer/products/add.php");
            exit;
        }
    }

    /**
     * Show edit product form
     */
    public function showEditProductForm($farmer_id, $product_id) {
        $result = $this->productService->getProduct($farmer_id, $product_id);
        
        if (!$result['success']) {
            return null;
        }

        return $result['data'];
    }

    /**
     * Handle edit product
     */
    public function handleEditProduct($farmer_id, $product_id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . BASE_URL . "farmer/products/list.php");
            exit;
        }

        $data = [];
        if (isset($_POST['name'])) $data['name'] = $_POST['name'];
        if (isset($_POST['description'])) $data['description'] = $_POST['description'];
        if (isset($_POST['category'])) $data['category'] = $_POST['category'];
        if (isset($_POST['price'])) $data['price'] = $_POST['price'];
        if (isset($_POST['quantity'])) $data['quantity'] = $_POST['quantity'];
        if (isset($_POST['unit'])) $data['unit'] = $_POST['unit'];

        $result = $this->productService->updateProduct($farmer_id, $product_id, $data);

        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
            header("Location: " . BASE_URL . "farmer/products/list.php");
            exit;
        } else {
            $_SESSION['error'] = $result['message'];
            header("Location: " . BASE_URL . "farmer/products/edit.php?id=" . $product_id);
            exit;
        }
    }

    /**
     * Handle delete product
     */
    public function handleDeleteProduct($farmer_id, $product_id) {
        $result = $this->productService->deleteProduct($farmer_id, $product_id);

        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
        } else {
            $_SESSION['error'] = $result['message'];
        }

        header("Location: " . BASE_URL . "farmer/products/list.php");
        exit;
    }

    /**
     * Search products
     */
    public function searchProducts($farmer_id) {
        $keyword = $_GET['q'] ?? '';

        if (empty($keyword)) {
            header("Location: " . BASE_URL . "farmer/products/list.php");
            exit;
        }

        return $this->productService->searchProducts($farmer_id, $keyword);
    }

    /**
     * Get product statistics
     */
    public function getProductStats($farmer_id) {
        return $this->productService->getProductStats($farmer_id);
    }
}
?>
