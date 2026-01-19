# 🗄️ Database Setup Guide

## Quick Setup (3 Steps)

### Step 1: Create Database from SQL File

Open phpMyAdmin or MySQL command line and run:

```bash
mysql -u root -p < c:\wamp64\www\agri\config\schema.sql
```

Or manually:

1. Open **phpMyAdmin** at `http://localhost/phpmyadmin`
2. Click **"New"** on the left
3. Create database named: `agri_system`
4. Go to **Import** tab
5. Upload file: `config/schema.sql`
6. Click **Import**

---

### Step 2: Add Sample Data

Use the SQL commands below to create test users:

```sql
-- Use the database
USE agri_system;

-- Insert sample FARMER user
INSERT INTO users (email, password, first_name, last_name, phone, role_id, is_active) 
VALUES ('farmer@test.com', '$2y$10$abcdefghijklmnopqrstuvwxyz...', 'John', 'Farmer', '555-1111', 2, 1);

-- Get farmer_id (should be 1 if first user)
SELECT * FROM users WHERE email = 'farmer@test.com';

-- Insert farmer profile
INSERT INTO farmer_profiles (user_id, farm_name, farm_size, crops_grown, certification)
VALUES (1, 'Green Valley Farm', 50, 'Tomatoes, Carrots, Lettuce', 'Organic');

-- Insert sample CONSUMER user
INSERT INTO users (email, password, first_name, last_name, phone, role_id, is_active)
VALUES ('consumer@test.com', '$2y$10$abcdefghijklmnopqrstuvwxyz...', 'Jane', 'Consumer', '555-2222', 3, 1);

-- Insert consumer profile
INSERT INTO consumer_profiles (user_id, address, postal_code)
VALUES (2, '123 Main St', '12345');

-- Insert sample products (from farmer 1)
INSERT INTO products (farmer_id, name, description, category, price, quantity, unit, is_available)
VALUES 
(1, 'Tomato', 'Fresh red tomatoes', 'Vegetables', 3.50, 100, 'lb', 1),
(1, 'Carrot', 'Organic carrots', 'Vegetables', 2.50, 150, 'lb', 1),
(1, 'Lettuce', 'Green lettuce head', 'Vegetables', 1.50, 80, 'head', 1);
```

---

### Step 3: Set Database Credentials

Edit `config/database.php`:

```php
$servername = "localhost";
$username = "root";
$password = "";  // Your MySQL password (empty if none)
$dbname = "agri_system";
```

---

## ⚠️ Important: Hash Passwords

The passwords in SQL above need to be hashed. Use this to generate proper passwords:

```php
<?php
// Generate hashed password
$password = "password123";
$hashed = password_hash($password, PASSWORD_BCRYPT);
echo $hashed;  // Use this value in INSERT
?>
```

**Or use these pre-hashed examples:**

```sql
-- Password: password123
$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36DRjk3u

-- Use in INSERT:
INSERT INTO users (email, password, first_name, last_name, phone, role_id, is_active) 
VALUES ('farmer@test.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36DRjk3u', 'John', 'Farmer', '555-1111', 2, 1);
```

---

## 🚀 Complete Setup Script

Run this entire SQL script in phpMyAdmin to set up everything at once:

```sql
-- Create database
CREATE DATABASE IF NOT EXISTS agri_system;
USE agri_system;

-- Insert roles
INSERT INTO roles (name, description) VALUES
('admin', 'Administrator with full access'),
('farmer', 'Farmer user with product listing capabilities'),
('consumer', 'Consumer user for purchasing products');

-- Insert test farmer
INSERT INTO users (email, password, first_name, last_name, phone, role_id, is_active) 
VALUES ('farmer@test.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36DRjk3u', 'John', 'Farmer', '555-1111', 2, 1);

-- Insert farmer profile
INSERT INTO farmer_profiles (user_id, farm_name, farm_size, crops_grown, certification)
VALUES (1, 'Green Valley Farm', 50, 'Tomatoes, Carrots, Lettuce', 'Organic');

-- Insert test consumer
INSERT INTO users (email, password, first_name, last_name, phone, role_id, is_active)
VALUES ('consumer@test.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36DRjk3u', 'Jane', 'Consumer', '555-2222', 3, 1);

-- Insert consumer profile
INSERT INTO consumer_profiles (user_id, address, postal_code)
VALUES (2, '123 Main St', '12345');

-- Insert test admin
INSERT INTO users (email, password, first_name, last_name, phone, role_id, is_active)
VALUES ('admin@test.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36DRjk3u', 'Admin', 'User', '555-3333', 1, 1);

-- Insert products
INSERT INTO products (farmer_id, name, description, category, price, quantity, unit, is_available) VALUES
(1, 'Tomato', 'Fresh red tomatoes', 'Vegetables', 3.50, 100, 'lb', 1),
(1, 'Carrot', 'Organic carrots', 'Vegetables', 2.50, 150, 'lb', 1),
(1, 'Lettuce', 'Green lettuce head', 'Vegetables', 1.50, 80, 'head', 1),
(1, 'Cucumber', 'Fresh cucumbers', 'Vegetables', 2.00, 60, 'lb', 1),
(1, 'Bell Pepper', 'Red bell peppers', 'Vegetables', 4.00, 50, 'lb', 1);
```

---

## ✅ Verify Installation

After setup, check database was created:

```sql
-- Check database exists
SHOW DATABASES;
USE agri_system;

-- Check tables created
SHOW TABLES;

-- Check users
SELECT * FROM users;

-- Check products
SELECT * FROM products;

-- Check roles
SELECT * FROM roles;
```

---

## 📊 Database Structure

| Table | Purpose |
|-------|---------|
| `roles` | User roles (Admin, Farmer, Consumer) |
| `users` | User accounts with credentials |
| `farmer_profiles` | Farmer-specific information |
| `consumer_profiles` | Consumer-specific information |
| `products` | Products listed by farmers |
| `orders` | Customer orders |
| `order_items` | Items in each order |
| `shopping_carts` | Items in shopping carts |
| `consumer_orders` | Purchase history tracking |
| `password_reset_tokens` | Password reset functionality |
| `login_logs` | Audit trail of logins |

---

## 🔐 Test Credentials

After running the setup script, use these credentials:

### Farmer Login
- **Email:** farmer@test.com
- **Password:** password123
- **Access:** `/farmer/dashboard.php`

### Consumer Login
- **Email:** consumer@test.com
- **Password:** password123
- **Access:** `/consumer/dashboard.php`

### Admin Login
- **Email:** admin@test.com
- **Password:** password123
- **Access:** `/admin/dashboard.php`

---

## 🔍 Troubleshooting

### "Access denied for user 'root'"
- Check your MySQL password in `config/database.php`
- Make sure MySQL service is running

### "Database doesn't exist"
- Run the schema.sql file first
- Check database name is `agri_system`

### "Connection refused"
- Ensure WAMP/LAMP is running
- Check MySQL is started in services
- Verify localhost:3306

### "Foreign key constraint failed"
- Make sure tables are created in correct order
- Check InnoDB engine is used
- Verify all parent records exist before insert

---

## 📝 Next Steps

1. ✅ Create database from schema.sql
2. ✅ Insert sample data
3. ✅ Update database.php with credentials
4. ✅ Test login with provided credentials
5. ✅ Start testing the system!

---

**Database Setup Complete!** 🎉

Now you're ready to test the order management system. Go to:
- **Consumer:** `http://localhost/agri/auth/login.php`
- **Farmer:** `http://localhost/agri/auth/login.php`
