# Consumer Module - API Documentation & Integration Guide

## 🔌 AJAX Endpoints

### Add to Cart
**Endpoint**: `POST /consumer/cart/add.php`

**Authentication**: Required (Consumer role)

**Parameters**:
```
product_id: integer (required)
quantity: integer (required, min: 1)
```

**Request Example**:
```javascript
const formData = new FormData();
formData.append('product_id', 5);
formData.append('quantity', 2);

fetch('/agri/consumer/cart/add.php', {
    method: 'POST',
    body: formData
})
.then(res => res.json())
.then(data => {
    if (data.success) {
        console.log('Added to cart! Total items:', data.cart_count);
    } else {
        alert(data.message);
    }
});
```

**Response Success**:
```json
{
    "success": true,
    "message": "Product added to cart successfully",
    "cart_count": 5,
    "cart_total": 145.50
}
```

**Response Error**:
```json
{
    "success": false,
    "message": "Product out of stock or invalid product"
}
```

**Status Codes**:
- 200: Success
- 401: Not authenticated
- 403: Not a consumer

---

## 📄 Form Endpoints

### View Cart
**URL**: `GET /consumer/cart/view.php`

**Authentication**: Required (Consumer role)

**Query Parameters**: None

**Methods**:
- `POST` with `remove=<cart_id>` - Remove item
- `POST` with `update=<cart_id>&quantity_<cart_id>=<qty>` - Update quantity

**Response**: HTML page with cart items grouped by farmer

---

### Checkout
**URL**: `POST /consumer/cart/checkout.php`

**Authentication**: Required (Consumer role)

**Form Parameters**:
```
fulfillment_type: "Delivery" | "Pickup" (required)
delivery_address: string (required if Delivery)
pickup_date: date (YYYY-MM-DD, required if Pickup)
```

**Processing**:
1. Validates fulfillment type
2. Validates required fields based on type
3. Groups cart items by farmer
4. Creates one order per farmer
5. Adds order items
6. Updates product quantities
7. Clears cart
8. Redirects to confirmation page

**Redirect on Success**: `/consumer/orders/confirmation.php?success=1`

**Redirect on Error**: `/consumer/cart/checkout.php` (with error message)

---

### Place Order
**Endpoint**: `POST /consumer/cart/checkout.php`

**Data Flow**:
```
Cart Items → Group by Farmer
           ↓
       For Each Farmer:
           ↓
       Create Order Record
           ↓
       Add Order Items
           ↓
       Update Product Quantities
           ↓
       Clear Cart
           ↓
       Redirect to Confirmation
```

**Order Creation SQL**:
```sql
INSERT INTO orders (
    consumer_id,
    farmer_id,
    order_number,
    total_amount,
    status,
    payment_status,
    fulfillment_type,
    delivery_address,
    pickup_date,
    created_at
) VALUES (...)
```

---

## 🔍 Data Retrieval Endpoints

### Get Cart Items
**File**: `app/models/ShoppingCart.php`

**Method**: `getCartByConsumer($consumer_id)`

**Returns**: Array of cart items with:
```php
[
    [
        'id' => integer,
        'consumer_id' => integer,
        'farmer_id' => integer,
        'product_id' => integer,
        'quantity' => integer,
        'name' => string,
        'category' => string,
        'unit' => string,
        'price' => decimal,
        'farmer_name' => string
    ],
    ...
]
```

**Usage**:
```php
$cartModel = new ShoppingCart($conn);
$items = $cartModel->getCartByConsumer($consumer_id);
```

---

### Get Cart Grouped by Farmer
**Method**: `CartService::getCartGroupedByFarmer($consumer_id)`

**Returns**: Array grouped by farmer_id:
```php
[
    farmer_id => [
        'farmer_id' => integer,
        'items' => [ ... ]
    ],
    ...
]
```

---

### Get Cart Total
**Method**: `ShoppingCart::getCartTotal($consumer_id)`

**Returns**: Decimal total of all items

---

### Get Orders for Consumer
**Query**:
```sql
SELECT o.*, COUNT(oi.id) as item_count
FROM orders o
LEFT JOIN order_items oi ON o.id = oi.order_id
WHERE o.consumer_id = ?
ORDER BY o.created_at DESC
GROUP BY o.id
```

---

### Get Order Details
**Query**:
```sql
SELECT o.*, u.first_name, u.last_name
FROM orders o
JOIN users u ON o.farmer_id = u.id
WHERE o.id = ? AND o.consumer_id = ?
```

---

### Get Order Items
**Query**:
```sql
SELECT oi.*, p.name, p.unit
FROM order_items oi
JOIN products p ON oi.product_id = p.id
WHERE oi.order_id = ?
ORDER BY oi.id
```

---

## 🎯 JavaScript Integration Examples

### Add to Cart with Quantity Prompt
```javascript
async function addToCart(productId, productName) {
    const quantity = prompt(`How many units of "${productName}"?`, '1');
    if (quantity === null) return;

    const qty = parseInt(quantity);
    if (isNaN(qty) || qty < 1) {
        alert('Please enter a valid quantity');
        return;
    }

    try {
        const response = await fetch('/agri/consumer/cart/add.php', {
            method: 'POST',
            body: new URLSearchParams({
                product_id: productId,
                quantity: qty
            })
        });

        const data = await response.json();
        if (data.success) {
            alert(`${productName} added to cart!`);
            updateCartBadge(data.cart_count);
        } else {
            alert(data.message || 'Failed to add to cart');
        }
    } catch (error) {
        alert('Error: ' + error.message);
    }
}
```

### Update Cart Badge
```javascript
function updateCartBadge(count) {
    const badge = document.querySelector('.cart-badge');
    if (badge) {
        badge.textContent = count;
        if (count === 0) badge.style.display = 'none';
    }
}
```

### Form Validation
```javascript
function validateCheckout() {
    const type = document.querySelector('input[name="fulfillment_type"]:checked').value;
    
    if (type === 'Delivery') {
        const address = document.getElementById('delivery_address').value.trim();
        if (!address) {
            alert('Please enter a delivery address');
            return false;
        }
    } else if (type === 'Pickup') {
        const date = document.getElementById('pickup_date').value;
        if (!date) {
            alert('Please select a pickup date');
            return false;
        }
        const selectedDate = new Date(date);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        if (selectedDate < today) {
            alert('Pickup date must be in the future');
            return false;
        }
    }
    return true;
}
```

### Dynamic Form Display
```javascript
function toggleFulfillmentDetails() {
    const type = document.querySelector('input[name="fulfillment_type"]:checked').value;
    
    const deliveryDetails = document.getElementById('delivery-details');
    const pickupDetails = document.getElementById('pickup-details');

    if (type === 'Delivery') {
        deliveryDetails.classList.add('active');
        pickupDetails.classList.remove('active');
        document.getElementById('delivery_address').required = true;
        document.getElementById('pickup_date').required = false;
    } else {
        deliveryDetails.classList.remove('active');
        pickupDetails.classList.add('active');
        document.getElementById('delivery_address').required = false;
        document.getElementById('pickup_date').required = true;
    }
}
```

---

## 🔐 Security Considerations

### Input Validation
- **product_id**: Integer validation with (int) cast
- **quantity**: Positive integer validation
- **delivery_address**: Non-empty string, max 500 characters
- **pickup_date**: Date format validation (YYYY-MM-DD)
- **fulfillment_type**: ENUM validation against allowed values

### SQL Injection Prevention
```php
// Using real_escape_string for string parameters
$address = $conn->real_escape_string($_POST['delivery_address']);
```

### Authentication Checks
```php
// All endpoints require authentication
AuthMiddleware::requireRole(ROLE_CONSUMER);

// Verify consumer owns the cart/order
if ($cart['consumer_id'] !== $_SESSION['user_id']) {
    exit('Unauthorized');
}
```

### Database Constraints
- Foreign key constraints on farmer_id, product_id
- Unique constraint on shopping_carts: (consumer_id, farmer_id, product_id)
- NOT NULL constraints on required fields

---

## 📊 Database Transactions

### Add to Cart Transaction
```php
BEGIN;
    CHECK IF PRODUCT EXISTS AND IS AVAILABLE
    CHECK IF QUANTITY AVAILABLE
    INSERT OR UPDATE shopping_carts
COMMIT;
```

### Place Order Transaction
```php
BEGIN;
    FOR EACH FARMER IN CART:
        CREATE ORDER
        INSERT ORDER ITEMS
        UPDATE PRODUCT QUANTITIES
    DELETE FROM shopping_carts WHERE consumer_id = ?
COMMIT;
```

---

## 🔄 State Management

### Cart State
- Location: Database (`shopping_carts` table)
- Persistence: Persistent across sessions
- Clearing: On order placement or explicit removal
- Grouping: By farmer_id for checkout

### Order State
- Status Workflow: Pending → Confirmed → Processing → Shipped → Delivered
- Payment States: Pending → Paid / Failed
- Fulfillment Types: Delivery, Pickup
- Timeline Events: created_at, confirmed_at, shipped_at, delivered_at

---

## 🧪 Testing API Endpoints

### Test Add to Cart
```bash
curl -X POST http://localhost/agri/consumer/cart/add.php \
  -d "product_id=1&quantity=2" \
  -b "PHPSESSID=<session_id>"
```

### Test Checkout
```bash
curl -X POST http://localhost/agri/consumer/cart/checkout.php \
  -d "fulfillment_type=Delivery&delivery_address=123 Main St" \
  -b "PHPSESSID=<session_id>"
```

---

## 📱 Mobile Integration

### Responsive Design
- Mobile-first CSS with flexible grids
- Touch-friendly buttons (min 44px)
- Readable typography at all sizes
- Form inputs optimized for mobile

### AJAX Best Practices
- No page reloads for cart operations
- Progress indicators for slow networks
- Error handling with user-friendly messages
- Graceful degradation without JavaScript

---

## 🚀 Performance Optimization

### Database Queries
- Use indexes on foreign keys
- Pagination for large result sets
- Select only needed columns
- Join optimization for related data

### Caching Opportunities
- Cache category list (rarely changes)
- Cache farmer profiles (good for 1 hour)
- Cache product availability (update on order)

### AJAX Optimization
- Debounce search input
- Cancel previous requests on new search
- Lazy load images on farm profile

---

## 🔗 Integration Points

### With Other Modules

**Authentication Module**:
- Uses `AuthMiddleware::requireRole()`
- Uses `$_SESSION['user_id']` for consumer_id
- Checks `ROLE_CONSUMER` constant

**Farmer Module**:
- Displays farmer data from `farmer_profiles`
- Uses products from `products` table
- Creates orders farmwise

**Admin Module** (Future):
- Access to all orders
- Reporting and analytics
- System configuration

---

## 📝 Error Handling

### HTTP Status Codes
- 200: Success
- 400: Bad request (invalid input)
- 401: Unauthorized (not authenticated)
- 403: Forbidden (wrong role)
- 404: Not found (resource doesn't exist)
- 500: Server error

### Error Response Format
```json
{
    "success": false,
    "message": "Human-readable error message",
    "error_code": "PRODUCT_NOT_FOUND"
}
```

### Session Error Handling
```php
try {
    // Database operation
} catch (Exception $e) {
    $_SESSION['error'] = 'An error occurred: ' . $e->getMessage();
    header("Location: " . BASE_URL . "consumer/cart/view.php");
    exit;
}
```

---

## 📚 Code Examples

### Complete Add to Cart Flow
```javascript
// 1. Get quantity from user
const quantity = prompt('How many?', '1');
if (!quantity) return;

// 2. Prepare request
const formData = new FormData();
formData.append('product_id', productId);
formData.append('quantity', parseInt(quantity));

// 3. Send to server
const response = await fetch('/agri/consumer/cart/add.php', {
    method: 'POST',
    body: formData
});

// 4. Handle response
const data = await response.json();
if (data.success) {
    // Update UI
    updateCartCount(data.cart_count);
    showSuccessMessage(`Added to cart!`);
} else {
    // Show error
    showErrorMessage(data.message);
}
```

### Complete Checkout Flow
```php
// 1. Validate input
$type = $_POST['fulfillment_type'];
if (!in_array($type, ['Delivery', 'Pickup'])) exit('Invalid type');

// 2. Get cart items
$items = $cartModel->getCartByConsumer($consumer_id);

// 3. Group by farmer
$grouped = [];
foreach ($items as $item) {
    $grouped[$item['farmer_id']][] = $item;
}

// 4. Create orders
foreach ($grouped as $farmer_id => $farmerItems) {
    $order_id = $orderModel->create([
        'consumer_id' => $consumer_id,
        'farmer_id' => $farmer_id,
        'total_amount' => calculateTotal($farmerItems),
        'fulfillment_type' => $type,
        // ... other fields
    ]);
    
    // 5. Add items
    foreach ($farmerItems as $item) {
        addOrderItem($order_id, $item);
    }
}

// 6. Clear cart and redirect
$cartModel->clearCart($consumer_id);
header("Location: " . BASE_URL . "consumer/orders/confirmation.php?success=1");
```

---

**Version**: 1.0.0  
**Last Updated**: [Current Date]  
**Status**: Production Ready
