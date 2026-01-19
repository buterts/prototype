<?php
require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../app/models/User.php';
require_once __DIR__ . '/../../app/models/Product.php';
require_once __DIR__ . '/../../app/services/ProductService.php';
require_once __DIR__ . '/../../app/controllers/ProductController.php';
require_once __DIR__ . '/../../app/middleware/AuthMiddleware.php';

// Require farmer role
AuthMiddleware::requireRole(ROLE_FARMER);

require_once __DIR__ . '/../../config/bootstrap.php';
$db = $GLOBALS['conn'] ?? null;
$userModel = new User($db);
$productModel = new Product($db);
$productService = new ProductService($productModel, $db);
$productController = new ProductController($productService);

$farmer_id = $_SESSION['user_id'];
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

$result = $productController->listProducts($farmer_id, $page);
$products = $result['data'];
$pagination = $result['pagination'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - Farmer Dashboard</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>public/css/style.css">
</head>
<body>
    <div class="dashboard-header">
        <h1>My Products</h1>
        <div class="user-menu">
            <a href="<?php echo BASE_URL; ?>farmer/dashboard.php">Dashboard</a>
            <a href="<?php echo BASE_URL; ?>auth/logout.php" class="btn logout-btn">Logout</a>
        </div>
    </div>

    <div style="padding: 40px; max-width: 1400px; margin: 0 auto;">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($_SESSION['success']); ?>
                <?php unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($_SESSION['error']); ?>
                <?php unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h2>Products (<?php echo $pagination['total']; ?>)</h2>
            <a href="<?php echo BASE_URL; ?>farmer/products/add.php" style="background: #28a745; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none; font-weight: bold;">
                ➕ Add New Product
            </a>
        </div>

        <?php if (count($products) > 0): ?>
            <div style="background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); overflow: hidden;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f5f5f5; border-bottom: 2px solid #ddd;">
                            <th style="text-align: left; padding: 15px;">Product Name</th>
                            <th style="text-align: left; padding: 15px;">Category</th>
                            <th style="text-align: right; padding: 15px;">Price</th>
                            <th style="text-align: center; padding: 15px;">Quantity</th>
                            <th style="text-align: center; padding: 15px;">Status</th>
                            <th style="text-align: center; padding: 15px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr style="border-bottom: 1px solid #eee;">
                                <td style="padding: 15px;">
                                    <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                    <br>
                                    <small style="color: #999;">
                                        <?php echo htmlspecialchars(substr($product['description'], 0, 50)); ?>...
                                    </small>
                                </td>
                                <td style="padding: 15px;"><?php echo htmlspecialchars($product['category']); ?></td>
                                <td style="padding: 15px; text-align: right;">$<?php echo number_format($product['price'], 2); ?></td>
                                <td style="padding: 15px; text-align: center;">
                                    <?php echo $product['quantity']; ?> <?php echo htmlspecialchars($product['unit']); ?>
                                </td>
                                <td style="padding: 15px; text-align: center;">
                                    <span style="padding: 5px 10px; border-radius: 3px; font-size: 12px; font-weight: bold;
                                        <?php echo $product['is_available'] ? 'background: #28a745; color: white;' : 'background: #dc3545; color: white;'; ?>">
                                        <?php echo $product['is_available'] ? 'Available' : 'Unavailable'; ?>
                                    </span>
                                </td>
                                <td style="padding: 15px; text-align: center;">
                                    <a href="<?php echo BASE_URL; ?>farmer/products/edit.php?id=<?php echo $product['id']; ?>" style="color: #007bff; text-decoration: none; margin-right: 15px;">Edit</a>
                                    <a href="<?php echo BASE_URL; ?>farmer/products/delete.php?id=<?php echo $product['id']; ?>" style="color: #dc3545; text-decoration: none;" onclick="return confirm('Are you sure?');">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($pagination['total_pages'] > 1): ?>
                <div style="text-align: center; margin-top: 30px;">
                    <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                        <a href="?page=<?php echo $i; ?>" style="
                            display: inline-block;
                            padding: 10px 15px;
                            margin: 0 5px;
                            background: <?php echo $i === $page ? '#667eea' : '#f0f0f0'; ?>;
                            color: <?php echo $i === $page ? 'white' : '#333'; ?>;
                            text-decoration: none;
                            border-radius: 3px;
                        ">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div style="background: #f9f9f9; padding: 30px; text-align: center; border-radius: 8px;">
                <p style="color: #999; font-size: 16px;">No products found. <a href="<?php echo BASE_URL; ?>farmer/products/add.php" style="color: #667eea;">Add your first product</a></p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
