<?php
require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../app/models/User.php';
require_once __DIR__ . '/../../app/models/Product.php';
require_once __DIR__ . '/../../app/models/ShoppingCart.php';
require_once __DIR__ . '/../../app/middleware/AuthMiddleware.php';

AuthMiddleware::requireRole(ROLE_CONSUMER);

require_once __DIR__ . '/../../config/bootstrap.php';
$db = $GLOBALS['conn'] ?? null;
$conn = $db;
$userModel = new User($conn);
$productModel = new Product($conn);
$cartModel = new ShoppingCart($conn);

$consumer_id = $_SESSION['user_id'];
$currentUser = $userModel->findById($consumer_id);

// Get filters
$category = isset($_GET['category']) ? $_GET['category'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 12;

// Build query
$query = "SELECT p.*, u.first_name, u.last_name, fp.farm_name
          FROM products p
          LEFT JOIN users u ON p.farmer_id = u.id
          LEFT JOIN farmer_profiles fp ON u.id = fp.user_id
          WHERE p.is_available = 1 AND p.quantity > 0";

if ($category) {
    $category = $conn->real_escape_string($category);
    $query .= " AND p.category = '$category'";
}

if ($search) {
    $search = $conn->real_escape_string($search);
    $query .= " AND (p.name LIKE '%$search%' OR p.description LIKE '%$search%')";
}

$query .= " ORDER BY p.created_at DESC";

// Get total count
$countResult = $conn->query($query);
$total = $countResult->num_rows;
$total_pages = ceil($total / $per_page);

// Add pagination
$offset = ($page - 1) * $per_page;
$query .= " LIMIT $per_page OFFSET $offset";

$result = $conn->query($query);
$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

// Get categories for filter
$catQuery = "SELECT DISTINCT category FROM products WHERE is_available = 1 ORDER BY category";
$catResult = $conn->query($catQuery);
$categories = [];
while ($row = $catResult->fetch_assoc()) {
    $categories[] = $row['category'];
}

// Get cart count
$cartCount = $cartModel->getCartItemCount($consumer_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Products - Agricultural Marketplace</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>public/css/style.css">
    <style>
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .product-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
        }

        .product-image {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 48px;
        }

        .product-info {
            padding: 15px;
        }

        .product-name {
            font-weight: bold;
            margin: 0 0 5px 0;
            font-size: 16px;
        }

        .product-farm {
            color: #999;
            font-size: 12px;
            margin: 0 0 10px 0;
        }

        .product-price {
            color: #667eea;
            font-size: 20px;
            font-weight: bold;
            margin: 10px 0;
        }

        .product-quantity {
            color: #666;
            font-size: 12px;
            margin: 5px 0;
        }

        .add-to-cart-btn {
            width: 100%;
            padding: 10px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.3s;
        }

        .add-to-cart-btn:hover {
            background: #5568d3;
        }

        .filters-sidebar {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .filter-group {
            margin-bottom: 20px;
        }

        .filter-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }

        .filter-group input,
        .filter-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .search-bar {
            margin-bottom: 20px;
        }

        .cart-badge {
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 12px;
        }

        .filters-container {
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .products-container {
            flex: 1;
        }

        @media (max-width: 768px) {
            .filters-container {
                grid-template-columns: 1fr;
            }

            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-header">
        <h1>🛒 Marketplace</h1>
        <div class="user-menu">
            <a href="<?php echo BASE_URL; ?>consumer/dashboard.php">Dashboard</a>
            <a href="<?php echo BASE_URL; ?>consumer/cart/view.php" style="position: relative;">
                Cart
                <?php if ($cartCount > 0): ?>
                    <span class="cart-badge"><?php echo $cartCount; ?></span>
                <?php endif; ?>
            </a>
            <a href="<?php echo BASE_URL; ?>auth/logout.php" class="btn logout-btn">Logout</a>
        </div>
    </div>

    <div style="padding: 40px; max-width: 1400px; margin: 0 auto;">
        <h2>Browse Available Products</h2>

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

        <!-- Search and Filters -->
        <div class="filters-container">
            <div class="filters-sidebar">
                <h3>Filters</h3>
                <form method="GET" style="margin-top: 15px;">
                    <div class="filter-group">
                        <label for="search">Search:</label>
                        <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search products...">
                    </div>

                    <div class="filter-group">
                        <label for="category">Category:</label>
                        <select id="category" name="category">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $category === $cat ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary">Search</button>
                    <a href="<?php echo BASE_URL; ?>consumer/products/browse.php" class="btn" style="background: #6c757d; color: white; text-decoration: none; text-align: center; margin-top: 10px; display: block;">Clear</a>
                </form>
            </div>

            <!-- Products Grid -->
            <div class="products-container">
                <?php if (count($products) > 0): ?>
                    <p style="color: #666; margin-bottom: 20px;">Showing <?php echo count($products); ?> of <?php echo $total; ?> products</p>
                    
                    <div class="products-grid">
                        <?php foreach ($products as $product): ?>
                            <div class="product-card">
                                <div class="product-image">
                                    <?php
                                    $icons = [
                                        'Vegetables' => '🥬',
                                        'Fruits' => '🍎',
                                        'Grains' => '🌾',
                                        'Dairy' => '🥛',
                                        'Meat' => '🥩',
                                        'Honey' => '🍯',
                                        'Spices' => '🌶️'
                                    ];
                                    echo $icons[$product['category']] ?? '🥕';
                                    ?>
                                </div>
                                <div class="product-info">
                                    <p class="product-name"><?php echo htmlspecialchars($product['name']); ?></p>
                                    <p class="product-farm">
                                        📍 <?php echo htmlspecialchars($product['farm_name'] ?: 'Farm'); ?>
                                    </p>
                                    <p class="product-price">$<?php echo number_format($product['price'], 2); ?></p>
                                    <p class="product-quantity">
                                        Available: <?php echo $product['quantity']; ?> <?php echo htmlspecialchars($product['unit']); ?>
                                    </p>
                                    <div style="display: flex; gap: 10px; margin-top: 10px;">
                                        <button class="add-to-cart-btn" onclick="addToCart(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['name']); ?>')">
                                            Add to Cart
                                        </button>
                                        <a href="<?php echo BASE_URL; ?>consumer/products/farm-profile.php?farmer_id=<?php echo $product['farmer_id']; ?>" style="flex: 1; padding: 10px; background: #f0f0f0; color: #333; border: none; border-radius: 5px; text-decoration: none; text-align: center; font-weight: bold; cursor: pointer;">
                                            Farm
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div style="text-align: center; margin-top: 30px;">
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <a href="?page=<?php echo $i; ?>&category=<?php echo urlencode($category); ?>&search=<?php echo urlencode($search); ?>" style="
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
                    <div style="background: #f9f9f9; padding: 40px; text-align: center; border-radius: 8px;">
                        <p style="color: #999; font-size: 16px;">No products found. Try adjusting your search or filters.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function addToCart(productId, productName) {
            const quantity = prompt(`Enter quantity for "${productName}":`, '1');
            
            if (quantity === null) return;
            
            const qty = parseInt(quantity);
            if (isNaN(qty) || qty <= 0) {
                alert('Please enter a valid quantity');
                return;
            }

            fetch('<?php echo BASE_URL; ?>consumer/cart/add.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'product_id=' + productId + '&quantity=' + qty
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    // Update cart badge
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => console.error('Error:', error));
        }
    </script>
</body>
</html>
