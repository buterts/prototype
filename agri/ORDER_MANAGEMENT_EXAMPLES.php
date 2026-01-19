<?php
/**
 * Order Management System - Usage Examples & Integration Guide
 * 
 * This file demonstrates how to use the OrderManagementService
 * for creating orders, managing status transitions, and tracking relationships
 */

// ============================================================================
// EXAMPLE 1: Creating a New Order with Items
// ============================================================================

/*
require_once 'config/init.php';
require_once 'app/models/Order.php';
require_once 'app/models/OrderItem.php';
require_once 'app/models/Product.php';
require_once 'app/services/OrderManagementService.php';

$conn = require_once 'config/database.php';

$orderModel = new Order($conn);
$orderItemModel = new OrderItem($conn);
$productModel = new Product($conn);

$orderService = new OrderManagementService($conn, $orderModel, $orderItemModel, $productModel);

// Prepare order items
$items = [
    [
        'product_id' => 1,
        'quantity' => 2,
        'price' => 19.99
    ],
    [
        'product_id' => 3,
        'quantity' => 5,
        'price' => 8.50
    ]
];

// Prepare fulfillment options
$options = [
    'fulfillment_type' => 'Delivery',
    'delivery_address' => '123 Main Street, Springfield, IL 62701',
    'special_instructions' => 'Leave at porch if not home'
];

// Create order
$result = $orderService->createOrder(
    $consumer_id = 5,           // Consumer placing order
    $farmer_id = 2,             // Farmer fulfilling order
    $items,
    $options
);

if ($result['success']) {
    echo "Order created successfully!";
    echo "Order ID: " . $result['order_id'];
    echo "Total Amount: $" . $result['total_amount'];
    echo "Items: " . $result['items_count'];
} else {
    echo "Failed to create order";
}
*/

// ============================================================================
// EXAMPLE 2: Getting Order Details with All Related Items
// ============================================================================

/*
$order_id = 1;
$orderDetails = $orderService->getOrderWithItems($order_id);

if ($orderDetails) {
    $order = $orderDetails['order'];
    $items = $orderDetails['items'];
    
    echo "Order #" . $order['order_number'];
    echo "Status: " . $order['status'];
    echo "Total: $" . $order['total_amount'];
    echo "Items:";
    
    foreach ($items as $item) {
        echo "- " . $item['name'] . " x" . $item['quantity'] . " @ $" . $item['unit_price'];
    }
}
*/

// ============================================================================
// EXAMPLE 3: Updating Order Status with Validation
// ============================================================================

/*
// Valid status transitions:
// Pending -> Confirmed or Cancelled
// Confirmed -> Completed or Cancelled
// Completed -> (no transitions)
// Cancelled -> (no transitions)

$result = $orderService->updateOrderStatus(
    $order_id = 1,
    $farmer_id = 2,
    $new_status = 'Confirmed'
);

if ($result['success']) {
    echo $result['message'];
    echo "Order transitioned from: " . $result['from_status'];
    echo "Order transitioned to: " . $result['to_status'];
} else {
    echo "Status update failed: " . $result['message'];
}
*/

// ============================================================================
// EXAMPLE 4: Status Workflow - Full Order Lifecycle
// ============================================================================

/*
// Step 1: Consumer places order (Status: Pending)
$orderResult = $orderService->createOrder(
    $consumer_id = 5,
    $farmer_id = 2,
    $items = [['product_id' => 1, 'quantity' => 2, 'price' => 19.99]],
    $options = ['fulfillment_type' => 'Delivery', 'delivery_address' => '123 Main St']
);
$order_id = $orderResult['order_id'];

// Step 2: Farmer confirms order (Status: Pending -> Confirmed)
$confirmResult = $orderService->updateOrderStatus($order_id, $farmer_id, 'Confirmed');

// Step 3: Farmer marks payment received
$paymentResult = $orderService->updatePaymentStatus($order_id, $farmer_id, 'Paid');

// Step 4: Farmer completes order after delivery/pickup (Status: Confirmed -> Completed)
$completeResult = $orderService->updateOrderStatus($order_id, $farmer_id, 'Completed');

// Full lifecycle complete!
*/

// ============================================================================
// EXAMPLE 5: Viewing Order Timeline
// ============================================================================

/*
$timeline = $orderService->getOrderTimeline($order_id = 1);

foreach ($timeline as $event) {
    echo $event['date'] . " - " . $event['status'];
    echo "Description: " . $event['description'];
    echo "---";
}

// Output:
// 2024-01-09 10:30:00 - Created
// Description: Order placed
// ---
// 2024-01-09 11:15:00 - Confirmed
// Description: Order confirmed by farmer
// ---
// 2024-01-09 15:45:00 - Payment Received
// Description: Payment confirmed
// ---
// 2024-01-09 17:00:00 - Completed
// Description: Order completed and delivered
*/

// ============================================================================
// EXAMPLE 6: Filtering Orders with Status and Date Range
// ============================================================================

/*
$filters = [
    'status' => 'Confirmed',
    'date_from' => '2024-01-01',
    'date_to' => '2024-01-31',
    'payment_status' => 'Paid'
];

$orders = $orderService->getFilteredOrders(
    $farmer_id = 2,
    $filters,
    $limit = 20,
    $offset = 0
);

foreach ($orders as $order) {
    echo "Order: " . $order['order_number'];
    echo "Status: " . $order['status'];
    echo "Payment: " . $order['payment_status'];
    echo "Total: $" . $order['total_amount'];
    echo "Items: " . $order['item_count'];
}
*/

// ============================================================================
// EXAMPLE 7: Order Statistics & Analytics
// ============================================================================

/*
$stats = $orderService->getOrderStatistics($farmer_id = 2);

echo "Total Orders: " . $stats['total_orders'];
echo "Total Revenue: $" . $stats['total_revenue'];
echo "Average Order Value: $" . $stats['avg_order_value'];
echo "Highest Order: $" . $stats['highest_order'];
echo "Completed Orders: " . $stats['completed_orders'];
echo "Pending Orders: " . $stats['pending_orders'];
echo "Confirmed Orders: " . $stats['confirmed_orders'];
echo "Cancelled Orders: " . $stats['cancelled_orders'];
echo "Paid Orders: " . $stats['paid_orders'];
echo "Pending Payment: " . $stats['pending_payment_orders'];
*/

// ============================================================================
// EXAMPLE 8: Product-Order Relationships (What Products Are Selling)
// ============================================================================

/*
$productRelationships = $orderService->getProductOrderRelationships($farmer_id = 2);

foreach ($productRelationships as $product) {
    echo "Product: " . $product['name'];
    echo "Category: " . $product['category'];
    echo "Orders: " . $product['orders_count'];
    echo "Total Sold: " . $product['total_quantity_sold'] . " " . $product['unit'];
    echo "Revenue: $" . $product['total_revenue'];
    echo "Avg per Order: " . $product['avg_quantity_per_order'];
    echo "Price Range: $" . $product['min_price'] . " - $" . $product['max_price'];
    echo "---";
}

// Output:
// Product: Fresh Tomatoes
// Category: Vegetables
// Orders: 15
// Total Sold: 45 lbs
// Revenue: $180.00
// Avg per Order: 3 lbs
// Price Range: $3.99 - $4.50
*/

// ============================================================================
// EXAMPLE 9: Cancelling an Order with Inventory Restoration
// ============================================================================

/*
// Cancelling an order automatically restores inventory
$cancelResult = $orderService->cancelOrder(
    $order_id = 5,
    $farmer_id = 2,
    $reason = 'Customer requested cancellation'
);

if ($cancelResult['success']) {
    echo "Order cancelled successfully";
    echo "Inventory has been restored automatically";
} else {
    echo "Cannot cancel: " . $cancelResult['message'];
}

// Note: Only orders in Pending or Confirmed status can be cancelled
// Completed or already Cancelled orders cannot be cancelled again
*/

// ============================================================================
// EXAMPLE 10: Validating Order Before Processing
// ============================================================================

/*
$validation = $orderService->validateOrderForProcessing($order_id = 1);

if ($validation['valid']) {
    echo "Order is valid and ready to process";
} else {
    echo "Order validation failed:";
    foreach ($validation['errors'] as $error) {
        echo "- " . $error;
    }
}

// Validation checks:
// ✓ Order exists
// ✓ Order has items
// ✓ All items have valid product references
// ✓ Required fulfillment info present
*/

// ============================================================================
// EXAMPLE 11: Payment Status Management
// ============================================================================

/*
// Update payment status (must be Pending, Paid, or Failed)
$paymentResult = $orderService->updatePaymentStatus(
    $order_id = 1,
    $farmer_id = 2,
    $payment_status = 'Paid'
);

if ($paymentResult['success']) {
    echo "Payment status updated to: " . $paymentResult['payment_status'];
}

// Valid payment statuses:
// - Pending: Payment not yet received
// - Paid: Payment confirmed
// - Failed: Payment failed or refunded
*/

// ============================================================================
// EXAMPLE 12: Consumer Order History
// ============================================================================

/*
$consumerOrders = $orderModel->getByConsumerId(
    $consumer_id = 5,
    $limit = 10,
    $offset = 0
);

$totalOrders = $orderModel->getConsumerOrderCount($consumer_id = 5);
$totalSpending = $orderModel->getConsumerTotalSpending($consumer_id = 5);

echo "Total Orders: " . $totalOrders;
echo "Total Spending: $" . $totalSpending;

foreach ($consumerOrders as $order) {
    echo "Order: " . $order['order_number'];
    echo "Status: " . $order['status'];
    echo "Amount: $" . $order['total_amount'];
    echo "Date: " . $order['created_at'];
}
*/

// ============================================================================
// DATABASE SCHEMA REQUIREMENTS
// ============================================================================

/*
-- Orders table structure
CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_number VARCHAR(20) UNIQUE NOT NULL,
    consumer_id INT NOT NULL,
    farmer_id INT NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    status ENUM('Pending', 'Confirmed', 'Completed', 'Cancelled') DEFAULT 'Pending',
    payment_status ENUM('Pending', 'Paid', 'Failed') DEFAULT 'Pending',
    fulfillment_type ENUM('Delivery', 'Pickup') DEFAULT 'Delivery',
    delivery_address TEXT,
    pickup_date DATE,
    special_instructions TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    confirmed_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (consumer_id) REFERENCES users(id),
    FOREIGN KEY (farmer_id) REFERENCES users(id),
    INDEX idx_consumer (consumer_id),
    INDEX idx_farmer (farmer_id),
    INDEX idx_status (status),
    INDEX idx_payment_status (payment_status),
    INDEX idx_created_at (created_at)
);

-- Order items table structure
CREATE TABLE order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10, 2) NOT NULL,
    subtotal DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id),
    INDEX idx_order (order_id),
    INDEX idx_product (product_id)
);
*/

// ============================================================================
// STATUS WORKFLOW DIAGRAM
// ============================================================================

/*
        ┌─────────────────────────────────────────┐
        │    Order Created (Pending)              │
        │  Consumer places order in shopping cart │
        │  - All inventory verified               │
        │  - Inventory quantities reserved        │
        └──────────┬──────────────────────────────┘
                   │
            ┌──────▼──────┐
            │   PENDING   │
            └──────┬──────┘
                   │
      ┌────────────┼────────────┐
      │            │            │
      │    YES     │     NO     │
      │ Confirm    │  Cancel    │
      │            │            │
      ▼            ▼            ▼
   ┌─────────┐  ┌──────────┐  ┌──────────────┐
   │CONFIRMED│  │CANCELLED │  │CANCELLED     │
   └────┬────┘  │(inventory│  │(inventory    │
        │       │ restored)│  │ restored)    │
        │       └──────────┘  └──────────────┘
        │
        │
        ▼
   ┌─────────────────────┐
   │  Payment Processing │
   │   (Auto or Manual)  │
   └──────────┬──────────┘
              │
        ┌─────┴─────┐
        │           │
        │  PAID     │  FAILED
        │           │
        ▼           ▼
    ┌─────────┐  ┌─────────┐
    │ READY   │  │ RETRY   │
    │ TO SHIP │  │PAYMENT  │
    └────┬────┘  └─────────┘
         │
         │
         ▼
    ┌──────────────────────┐
    │     Complete         │
    │ Delivered/Picked up  │
    │   Inventory Reduced  │
    └──────────┬───────────┘
               │
               ▼
         ┌──────────┐
         │COMPLETED │
         └──────────┘
*/

// ============================================================================
// KEY FEATURES
// ============================================================================

/*
✓ Transaction-based order creation (atomic operations)
✓ Automatic inventory management
✓ Status workflow validation
✓ Payment status tracking
✓ Fulfillment type handling (Delivery/Pickup)
✓ Order timeline/history
✓ Order cancellation with inventory restoration
✓ Product-order relationship tracking
✓ Order statistics and analytics
✓ Date range filtering
✓ Comprehensive error handling
✓ Order validation before processing
✓ Consumer and farmer specific queries
*/

// ============================================================================
// ERROR HANDLING
// ============================================================================

/*
When order creation fails, you get:
{
    "success": false,
    "message": "Human-readable error message"
}

Possible errors:
- "Invalid item structure"
- "Invalid quantity for product X"
- "Product X not found or doesn't belong to farmer"
- "Product {name} is not available"
- "Insufficient inventory for {name}. Available: X, Requested: Y"
- "Failed to create order"
- "Failed to create order item"
- "Failed to update inventory"

When status update fails, you get similar error array with:
- "Invalid status"
- "Order not found"
- "Unauthorized: You do not own this order"
- "Cannot transition from X to Y. Allowed transitions: Z"
- "Failed to update order status"
*/

// ============================================================================
// INTEGRATION WITH EXISTING SYSTEM
// ============================================================================

/*
The OrderManagementService integrates with:
- Order Model: CRUD operations and queries
- OrderItem Model: Item management and retrieval
- Product Model: Inventory checking and updating
- Database Connection: Transaction management

Usage in controllers:
1. Initialize service with models
2. Call appropriate service methods
3. Handle success/error responses
4. Return JSON or redirect
*/

?>
