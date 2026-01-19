<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../app/models/User.php';
require_once __DIR__ . '/../app/models/FarmerProfile.php';
require_once __DIR__ . '/../app/models/ConsumerProfile.php';
require_once __DIR__ . '/../app/models/Product.php';
require_once __DIR__ . '/../app/models/Order.php';
require_once __DIR__ . '/../app/models/OrderItem.php';
require_once __DIR__ . '/../app/models/ShoppingCart.php';
require_once __DIR__ . '/../app/services/AuthService.php';
require_once __DIR__ . '/../app/services/OrderService.php';
require_once __DIR__ . '/../app/services/OrderManagementService.php';
require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/middleware/SecurityMiddleware.php';

// Instantiate database connection and then models/services
// Prefer an existing mysqli connection if available; otherwise require the database file
if (isset($conn) && ($conn instanceof mysqli)) {
	$db = $conn;
} elseif (isset($GLOBALS['conn']) && ($GLOBALS['conn'] instanceof mysqli)) {
	$db = $GLOBALS['conn'];
} else {
	// This will return the mysqli connection from config/database.php
	$db = require __DIR__ . '/../config/database.php';
}

// Fallback safety: if $db is not a mysqli instance, throw a clear error
if (!($db instanceof mysqli)) {
	trigger_error('Database connection not initialized correctly in bootstrap.php', E_USER_ERROR);
}

$userModel = new User($db);
$farmerModel = new FarmerProfile($db);
$consumerModel = new ConsumerProfile($db);
$authService = new AuthService($userModel, $farmerModel, $consumerModel, $db);

// Set security headers
SecurityMiddleware::setSecurityHeaders();
?>
