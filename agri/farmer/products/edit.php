<?php
require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../app/models/Product.php';
require_once __DIR__ . '/../../app/services/ProductService.php';
require_once __DIR__ . '/../../app/controllers/ProductController.php';
require_once __DIR__ . '/../../app/middleware/AuthMiddleware.php';

AuthMiddleware::requireRole(ROLE_FARMER);

require_once __DIR__ . '/../../config/bootstrap.php';
$db = $GLOBALS['conn'] ?? null;
$conn = $db;
$productModel = new Product($conn);
$productService = new ProductService($productModel, $conn);
$productController = new ProductController($productService);

$farmer_id = $_SESSION['user_id'];
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$product_id) {
    header("Location: " . BASE_URL . "farmer/products/list.php");
    exit;
}

$product = $productController->showEditProductForm($farmer_id, $product_id);

if (!$product) {
    $_SESSION['error'] = 'Product not found';
    header("Location: " . BASE_URL . "farmer/products/list.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - Farmer Dashboard</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>public/css/style.css">
</head>
<body>
    <div class="dashboard-header">
        <h1>Edit Product</h1>
        <div class="user-menu">
            <a href="<?php echo BASE_URL; ?>farmer/dashboard.php">Dashboard</a>
            <a href="<?php echo BASE_URL; ?>auth/logout.php" class="btn logout-btn">Logout</a>
        </div>
    </div>

    <div style="padding: 40px; max-width: 800px; margin: 0 auto;">
        <form method="POST" action="<?php echo BASE_URL; ?>farmer/products/process-edit.php?id=<?php echo $product['id']; ?>" style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <div class="form-group">
                <label for="name">Product Name *</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
            </div>

            <div class="form-group">
                <label for="category">Category *</label>
                <select id="category" name="category" required>
                    <option value="Vegetables" <?php echo $product['category'] === 'Vegetables' ? 'selected' : ''; ?>>Vegetables</option>
                    <option value="Fruits" <?php echo $product['category'] === 'Fruits' ? 'selected' : ''; ?>>Fruits</option>
                    <option value="Grains" <?php echo $product['category'] === 'Grains' ? 'selected' : ''; ?>>Grains</option>
                    <option value="Dairy" <?php echo $product['category'] === 'Dairy' ? 'selected' : ''; ?>>Dairy</option>
                    <option value="Meat" <?php echo $product['category'] === 'Meat' ? 'selected' : ''; ?>>Meat</option>
                    <option value="Honey" <?php echo $product['category'] === 'Honey' ? 'selected' : ''; ?>>Honey</option>
                    <option value="Spices" <?php echo $product['category'] === 'Spices' ? 'selected' : ''; ?>>Spices</option>
                    <option value="Other" <?php echo $product['category'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="price">Price ($) *</label>
                    <input type="number" id="price" name="price" step="0.01" min="0" value="<?php echo $product['price']; ?>" required>
                </div>

                <div class="form-group">
                    <label for="quantity">Quantity *</label>
                    <input type="number" id="quantity" name="quantity" min="0" value="<?php echo $product['quantity']; ?>" required>
                </div>

                <div class="form-group">
                    <label for="unit">Unit *</label>
                    <select id="unit" name="unit" required>
                        <option value="kg" <?php echo $product['unit'] === 'kg' ? 'selected' : ''; ?>>kg</option>
                        <option value="lb" <?php echo $product['unit'] === 'lb' ? 'selected' : ''; ?>>lb</option>
                        <option value="g" <?php echo $product['unit'] === 'g' ? 'selected' : ''; ?>>g</option>
                        <option value="l" <?php echo $product['unit'] === 'l' ? 'selected' : ''; ?>>l</option>
                        <option value="ml" <?php echo $product['unit'] === 'ml' ? 'selected' : ''; ?>>ml</option>
                        <option value="pieces" <?php echo $product['unit'] === 'pieces' ? 'selected' : ''; ?>>pieces</option>
                        <option value="dozen" <?php echo $product['unit'] === 'dozen' ? 'selected' : ''; ?>>dozen</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="5"><?php echo htmlspecialchars($product['description']); ?></textarea>
            </div>

            <div class="form-group">
                <label for="is_available">
                    <input type="checkbox" id="is_available" name="is_available" value="1" <?php echo $product['is_available'] ? 'checked' : ''; ?>>
                    Product is available for sale
                </label>
            </div>

            <div style="display: flex; gap: 10px; margin-top: 30px;">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="<?php echo BASE_URL; ?>farmer/products/list.php" class="btn" style="background: #6c757d; color: white; text-decoration: none; text-align: center;">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>
