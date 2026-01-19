<?php
require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../app/models/Product.php';
require_once __DIR__ . '/../../app/middleware/AuthMiddleware.php';

AuthMiddleware::requireRole(ROLE_CONSUMER);

require_once __DIR__ . '/../../config/bootstrap.php';
$db = $GLOBALS['conn'] ?? null;
$conn = $db;

// Accept both 'id' and 'farmer_id' parameters
$farmer_id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_GET['farmer_id']) ? (int)$_GET['farmer_id'] : 0);

if (!$farmer_id) {
    header("Location: " . BASE_URL . "consumer/products/browse.php");
    exit;
}

// Get farmer profile
$farmer_query = "SELECT u.*, fp.farm_name, fp.location, fp.bio, fp.phone FROM users u 
                 LEFT JOIN farmer_profiles fp ON u.id = fp.user_id 
                 WHERE u.id = $farmer_id AND u.role_id = (SELECT id FROM roles WHERE name = '" . ROLE_FARMER . "')";

$farmer_result = $conn->query($farmer_query);
if (!$farmer_result || $farmer_result->num_rows === 0) {
    header("Location: " . BASE_URL . "consumer/products/browse.php");
    exit;
}

$farmer = $farmer_result->fetch_assoc();

// Get farmer's products
$productModel = new Product($conn);
$products = $productModel->getByFarmerId($farmer_id);
$available_products = array_filter($products, function($p) { return $p['is_available']; });

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 12;
$total_products = count($available_products);
$total_pages = ceil($total_products / $per_page);
$offset = ($page - 1) * $per_page;
$paginated_products = array_slice($available_products, $offset, $per_page);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($farmer['farm_name'] ?? $farmer['name']); ?> - Farm Profile</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>public/css/style.css">
    <style>
        .farm-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 60px 40px;
            text-align: center;
        }

        .farm-header h1 {
            margin: 0 0 20px 0;
            font-size: 36px;
        }

        .farm-header p {
            margin: 10px 0;
            font-size: 16px;
            opacity: 0.9;
        }

        .farm-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px;
        }

        .info-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }

        .info-card label {
            display: block;
            font-size: 12px;
            color: #999;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .info-card p {
            margin: 0;
            font-weight: bold;
            font-size: 16px;
            color: #333;
        }

        .farm-details {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 40px;
        }

        .about-section {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 40px;
        }

        .about-section h2 {
            margin: 0 0 15px 0;
        }

        .about-section p {
            color: #666;
            line-height: 1.6;
        }

        .products-section {
            margin-bottom: 40px;
        }

        .products-section h2 {
            margin: 0 0 20px 0;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .product-card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            flex-direction: column;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .product-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            text-align: center;
            font-size: 32px;
            min-height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .product-body {
            padding: 15px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .product-name {
            font-weight: bold;
            margin-bottom: 8px;
            color: #333;
        }

        .product-category {
            font-size: 12px;
            color: #999;
            margin-bottom: 10px;
        }

        .product-price {
            font-size: 18px;
            font-weight: bold;
            color: #28a745;
            margin-bottom: 8px;
        }

        .product-quantity {
            font-size: 12px;
            color: #666;
            margin-bottom: 15px;
            margin-top: auto;
        }

        .product-actions {
            display: flex;
            gap: 10px;
        }

        .btn-cart {
            flex: 1;
            padding: 10px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.3s ease;
        }

        .btn-cart:hover {
            background: #218838;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin: 30px 0;
        }

        .pagination a,
        .pagination span {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: #333;
        }

        .pagination a:hover {
            background: #f0f0f0;
        }

        .pagination .active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .empty-state {
            background: white;
            padding: 60px 40px;
            text-align: center;
            border-radius: 8px;
        }

        .back-btn {
            display: inline-block;
            margin-bottom: 20px;
            color: #667eea;
            text-decoration: none;
            font-weight: bold;
        }

        .back-btn:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .farm-header {
                padding: 40px 20px;
            }

            .farm-info,
            .farm-details {
                padding: 20px;
            }

            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-header">
        <h1>🏡 Agricultural Marketplace</h1>
        <div class="user-menu">
            <a href="<?php echo BASE_URL; ?>consumer/products/browse.php">Shop</a>
            <a href="<?php echo BASE_URL; ?>consumer/cart/view.php">Cart</a>
            <a href="<?php echo BASE_URL; ?>consumer/dashboard.php">Dashboard</a>
            <a href="<?php echo BASE_URL; ?>auth/logout.php" class="btn logout-btn">Logout</a>
        </div>
    </div>

    <!-- Farm Header -->
    <div class="farm-header">
        <h1>🌾 <?php echo htmlspecialchars($farmer['farm_name'] ?? $farmer['name']); ?></h1>
        <p>Farm Owner: <?php echo htmlspecialchars($farmer['name']); ?></p>
    </div>

    <!-- Farm Info -->
    <div class="farm-info">
        <div class="info-card">
            <label>Location</label>
            <p><?php echo htmlspecialchars($farmer['location'] ?? 'Not specified'); ?></p>
        </div>
        <div class="info-card">
            <label>Total Products</label>
            <p><?php echo count($available_products); ?></p>
        </div>
        <div class="info-card">
            <label>Contact</label>
            <p><?php echo htmlspecialchars($farmer['phone'] ?? 'Not provided'); ?></p>
        </div>
        <div class="info-card">
            <label>Email</label>
            <p><?php echo htmlspecialchars($farmer['email']); ?></p>
        </div>
    </div>

    <!-- About Section -->
    <div class="farm-details">
        <?php if (!empty($farmer['bio'])): ?>
            <div class="about-section">
                <h2>About This Farm</h2>
                <p><?php echo nl2br(htmlspecialchars($farmer['bio'])); ?></p>
            </div>
        <?php endif; ?>

        <!-- Products Section -->
        <div class="products-section">
            <a href="<?php echo BASE_URL; ?>consumer/products/browse.php" class="back-btn">← Back to All Products</a>
            
            <h2>🥬 Farm Products (<?php echo count($available_products); ?>)</h2>

            <?php if (count($available_products) > 0): ?>
                <div class="products-grid">
                    <?php foreach ($paginated_products as $product): ?>
                        <div class="product-card">
                            <div class="product-header">
                                <?php
                                // Simple category to emoji mapping
                                $categoryEmojis = [
                                    'Vegetables' => '🥬',
                                    'Fruits' => '🍎',
                                    'Grains' => '🌾',
                                    'Dairy' => '🧈',
                                    'Meat' => '🍗',
                                    'Spices' => '🧂'
                                ];
                                $emoji = $categoryEmojis[$product['category']] ?? '🌽';
                                echo $emoji;
                                ?>
                            </div>
                            <div class="product-body">
                                <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                                <div class="product-category"><?php echo htmlspecialchars($product['category']); ?></div>
                                <div class="product-price">$<?php echo number_format($product['price'], 2); ?>/<?php echo htmlspecialchars($product['unit']); ?></div>
                                <div class="product-quantity">
                                    Stock: <?php echo $product['quantity']; ?> <?php echo htmlspecialchars($product['unit']); ?>
                                </div>
                                <div class="product-actions">
                                    <button class="btn-cart" onclick="addToCart(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['name']); ?>')">
                                        Add to Cart
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?id=<?php echo $farmer_id; ?>&page=1">« First</a>
                            <a href="?id=<?php echo $farmer_id; ?>&page=<?php echo $page - 1; ?>">‹ Previous</a>
                        <?php endif; ?>

                        <?php
                        $start = max(1, $page - 2);
                        $end = min($total_pages, $page + 2);
                        for ($i = $start; $i <= $end; $i++) {
                            if ($i === $page) {
                                echo '<span class="active">' . $i . '</span>';
                            } else {
                                echo '<a href="?id=' . $farmer_id . '&page=' . $i . '">' . $i . '</a>';
                            }
                        }
                        ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?id=<?php echo $farmer_id; ?>&page=<?php echo $page + 1; ?>">Next ›</a>
                            <a href="?id=<?php echo $farmer_id; ?>&page=<?php echo $total_pages; ?>">Last »</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-state">
                    <p style="color: #999; font-size: 16px;">This farm currently has no available products.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        async function addToCart(product_id, product_name) {
            const quantity = prompt(`How many units of "${product_name}" would you like to add?`, '1');
            if (quantity === null) return;

            const qty = parseInt(quantity);
            if (isNaN(qty) || qty < 1) {
                alert('Please enter a valid quantity');
                return;
            }

            try {
                const response = await fetch('<?php echo BASE_URL; ?>consumer/cart/add.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `product_id=${product_id}&quantity=${qty}`
                });

                const result = await response.json();
                if (result.success) {
                    alert(`${product_name} added to cart!`);
                    // Optionally update cart count in header
                } else {
                    alert(result.message || 'Failed to add to cart');
                }
            } catch (error) {
                alert('Error adding to cart: ' + error.message);
            }
        }
    </script>
</body>
</html>
