<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../app/models/User.php';
require_once __DIR__ . '/../app/models/FarmerProfile.php';
require_once __DIR__ . '/../app/models/Product.php';
require_once __DIR__ . '/../app/models/Order.php';
require_once __DIR__ . '/../app/models/OrderItem.php';
require_once __DIR__ . '/../app/services/ProductService.php';
require_once __DIR__ . '/../app/services/OrderService.php';
require_once __DIR__ . '/../app/services/OrderManagementService.php';
require_once __DIR__ . '/../app/controllers/ProductController.php';
require_once __DIR__ . '/../app/controllers/OrderController.php';
require_once __DIR__ . '/../app/middleware/AuthMiddleware.php';

// Require farmer role
AuthMiddleware::requireRole(ROLE_FARMER);

require_once __DIR__ . '/../config/bootstrap.php';

// Use the connection initialized by bootstrap
$db = $GLOBALS['conn'] ?? null;

$userModel = new User($db);
$farmerModel = new FarmerProfile($db);
$productModel = new Product($db);
$orderModel = new Order($db);
$orderItemModel = new OrderItem($db);

$productService = new ProductService($productModel, $db);
$orderService = new OrderService($orderModel, $orderItemModel, $db);
$orderManagementService = new OrderManagementService($db, $orderModel, $orderItemModel, $productModel);

$orderController = new OrderController($orderService);

$currentUser = $userModel->findById($_SESSION['user_id']);
$farmerProfile = $farmerModel->getByUserId($_SESSION['user_id']);
$farmer_id = $_SESSION['user_id'];

// Get statistics using both services
$productStats = $productService->getProductStats($farmer_id);
$salesSummary = $orderService->getSalesSummary($farmer_id);
$recentOrders = $orderService->getRecentOrders($farmer_id, 5);
$pendingOrders = $orderService->getPendingOrdersCount($farmer_id);

// Get enhanced analytics from OrderManagementService
$orderStats = $orderManagementService->getOrderStatistics($farmer_id);
$productRelationships = $orderManagementService->getProductOrderRelationships($farmer_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farmer Dashboard - Agricultural Marketplace</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>public/css/style.css">
</head>
<body>
    <div class="dashboard-header">
        <h1>Farmer Dashboard</h1>
        <div class="user-menu">
            <span><?php echo htmlspecialchars($currentUser['first_name']); ?></span>
            <a href="<?php echo BASE_URL; ?>auth/logout.php" class="btn logout-btn">Logout</a>
        </div>
    </div>

    <div style="padding: 40px; max-width: 1400px; margin: 0 auto;">
        <h2>Welcome, <?php echo htmlspecialchars($currentUser['first_name']); ?>!</h2>

        <!-- Stats Cards -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 30px 0;">
            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 4px solid #667eea;">
                <h3 style="margin: 0 0 10px 0; color: #666; font-size: 14px;">Total Products</h3>
                <p style="margin: 0; font-size: 32px; font-weight: bold; color: #667eea;">
                    <?php echo $productStats['success'] ? $productStats['data']['total_products'] : 0; ?>
                </p>
                <p style="margin: 5px 0 0 0; color: #999; font-size: 12px;">
                    <?php echo $productStats['success'] ? $productStats['data']['available_products'] : 0; ?> available
                </p>
            </div>

            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 4px solid #28a745;">
                <h3 style="margin: 0 0 10px 0; color: #666; font-size: 14px;">Total Orders</h3>
                <p style="margin: 0; font-size: 32px; font-weight: bold; color: #28a745;">
                    <?php echo $salesSummary['data']['total_orders'] ?? 0; ?>
                </p>
                <p style="margin: 5px 0 0 0; color: #999; font-size: 12px;">
                    <?php echo $pendingOrders; ?> pending
                </p>
            </div>

            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 4px solid #ffc107;">
                <h3 style="margin: 0 0 10px 0; color: #666; font-size: 14px;">Total Revenue</h3>
                <p style="margin: 0; font-size: 32px; font-weight: bold; color: #ffc107;">
                    $<?php echo number_format($salesSummary['data']['total_revenue'] ?? 0, 2); ?>
                </p>
                <p style="margin: 5px 0 0 0; color: #999; font-size: 12px;">
                    <?php echo $salesSummary['data']['completed_orders'] ?? 0; ?> completed
                </p>
            </div>

            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 4px solid #dc3545;">
                <h3 style="margin: 0 0 10px 0; color: #666; font-size: 14px;">Inventory Value</h3>
                <p style="margin: 0; font-size: 32px; font-weight: bold; color: #dc3545;">
                    $<?php echo $productStats['success'] ? $productStats['data']['total_inventory_value'] : '0.00'; ?>
                </p>
                <p style="margin: 5px 0 0 0; color: #999; font-size: 12px;">
                    <?php echo $productStats['success'] ? $productStats['data']['total_quantity'] : 0; ?> items
                </p>
            </div>
        </div>

        <!-- Quick Actions -->
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin: 30px 0;">
            <h3>Quick Actions</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-top: 15px;">
                <a href="<?php echo BASE_URL; ?>farmer/products/list.php" style="display: block; text-align: center; padding: 15px; background: #667eea; color: white; border-radius: 5px; text-decoration: none; transition: background 0.3s;">
                    📦 View Products
                </a>
                <a href="<?php echo BASE_URL; ?>farmer/products/add.php" style="display: block; text-align: center; padding: 15px; background: #28a745; color: white; border-radius: 5px; text-decoration: none; transition: background 0.3s;">
                    ➕ Add Product
                </a>
                <a href="<?php echo BASE_URL; ?>farmer/orders/list.php" style="display: block; text-align: center; padding: 15px; background: #ffc107; color: white; border-radius: 5px; text-decoration: none; transition: background 0.3s;">
                    🛒 View Orders
                </a>
                <a href="<?php echo BASE_URL; ?>farmer/edit-profile.php" style="display: block; text-align: center; padding: 15px; background: #17a2b8; color: white; border-radius: 5px; text-decoration: none; transition: background 0.3s;">
                    ⚙️ Edit Profile
                </a>
            </div>
        </div>

        <!-- Recent Orders -->
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin: 30px 0;">
            <h3>Recent Orders</h3>
            <?php if ($recentOrders['success'] && count($recentOrders['data']) > 0): ?>
                <table style="width: 100%; border-collapse: collapse; margin-top: 15px;">
                    <thead>
                        <tr style="border-bottom: 2px solid #ddd;">
                            <th style="text-align: left; padding: 10px;">Order #</th>
                            <th style="text-align: left; padding: 10px;">Consumer</th>
                            <th style="text-align: left; padding: 10px;">Amount</th>
                            <th style="text-align: left; padding: 10px;">Status</th>
                            <th style="text-align: left; padding: 10px;">Date</th>
                            <th style="text-align: left; padding: 10px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentOrders['data'] as $order): ?>
                            <tr style="border-bottom: 1px solid #eee;">
                                <td style="padding: 10px;"><?php echo htmlspecialchars($order['order_number']); ?></td>
                                <td style="padding: 10px;">
                                    <?php echo htmlspecialchars($order['consumer_first_name'] . ' ' . $order['consumer_last_name']); ?>
                                </td>
                                <td style="padding: 10px;">$<?php echo number_format($order['total_amount'], 2); ?></td>
                                <td style="padding: 10px;">
                                    <span style="padding: 5px 10px; border-radius: 3px; font-size: 12px; font-weight: bold;
                                        <?php
                                        $status_colors = [
                                            'pending' => 'background: #ffc107; color: white;',
                                            'confirmed' => 'background: #17a2b8; color: white;',
                                            'processing' => 'background: #007bff; color: white;',
                                            'ready_for_pickup' => 'background: #28a745; color: white;',
                                            'completed' => 'background: #6c757d; color: white;',
                                            'cancelled' => 'background: #dc3545; color: white;'
                                        ];
                                        echo $status_colors[$order['status']] ?? '';
                                        ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                                    </span>
                                </td>
                                <td style="padding: 10px;"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                <td style="padding: 10px;">
                                    <a href="<?php echo BASE_URL; ?>farmer/orders/view.php?id=<?php echo $order['id']; ?>" style="color: #667eea; text-decoration: none;">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="color: #999;">No orders yet.</p>
            <?php endif; ?>
        </div>

        <!-- Product Sales Analytics -->
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin: 30px 0;">
            <h3>Product Sales Performance</h3>
            <?php if ($productRelationships && count($productRelationships) > 0): ?>
                <div style="overflow-x: auto; margin-top: 15px;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #f5f5f5; border-bottom: 2px solid #ddd;">
                                <th style="text-align: left; padding: 10px;">Product Name</th>
                                <th style="text-align: center; padding: 10px;">Times Ordered</th>
                                <th style="text-align: center; padding: 10px;">Total Qty Sold</th>
                                <th style="text-align: right; padding: 10px;">Total Revenue</th>
                                <th style="text-align: center; padding: 10px;">Avg per Order</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // Sort by revenue descending and show top 5
                            usort($productRelationships, function($a, $b) {
                                return $b['total_revenue'] <=> $a['total_revenue'];
                            });
                            
                            foreach (array_slice($productRelationships, 0, 5) as $product): 
                            ?>
                                <tr style="border-bottom: 1px solid #eee;">
                                    <td style="padding: 10px;"><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td style="padding: 10px; text-align: center;">
                                        <strong><?php echo $product['orders_count']; ?></strong>
                                    </td>
                                    <td style="padding: 10px; text-align: center;">
                                        <?php echo $product['total_quantity_sold']; ?>
                                    </td>
                                    <td style="padding: 10px; text-align: right; font-weight: bold;">
                                        $<?php echo number_format($product['total_revenue'], 2); ?>
                                    </td>
                                    <td style="padding: 10px; text-align: center;">
                                        <?php echo number_format($product['avg_quantity_per_order'], 1); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <a href="<?php echo BASE_URL; ?>farmer/orders/list.php" style="display: inline-block; margin-top: 15px; color: #667eea; text-decoration: none; font-weight: bold;">
                    View All Orders →
                </a>
            <?php else: ?>
                <p style="color: #999;">No product sales data available yet.</p>
            <?php endif; ?>
        </div>

        <!-- Farm Information -->
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin: 30px 0;">
            <h3>Farm Information</h3>
            <?php if ($farmerProfile): ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 15px;">
                    <div>
                        <p style="margin: 0 0 5px 0; color: #666; font-weight: bold;">Farm Name</p>
                        <p style="margin: 0; color: #333;"><?php echo htmlspecialchars($farmerProfile['farm_name']); ?></p>
                    </div>
                    <div>
                        <p style="margin: 0 0 5px 0; color: #666; font-weight: bold;">Farm Size</p>
                        <p style="margin: 0; color: #333;"><?php echo htmlspecialchars($farmerProfile['farm_size'] ?: 'Not specified'); ?> acres</p>
                    </div>
                    <div>
                        <p style="margin: 0 0 5px 0; color: #666; font-weight: bold;">Crops Grown</p>
                        <p style="margin: 0; color: #333;"><?php echo htmlspecialchars($farmerProfile['crops_grown'] ?: 'Not specified'); ?></p>
                    </div>
                    <div>
                        <p style="margin: 0 0 5px 0; color: #666; font-weight: bold;">Certification</p>
                        <p style="margin: 0; color: #333;"><?php echo htmlspecialchars($farmerProfile['certification'] ?: 'Not certified'); ?></p>
                    </div>
                </div>
                <a href="<?php echo BASE_URL; ?>farmer/edit-profile.php" style="display: inline-block; margin-top: 15px; background: #667eea; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none;">Edit Farm Information</a>
            <?php else: ?>
                <p>Farm profile not found. Please complete your farm profile.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
