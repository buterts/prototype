#!/bin/bash
# Database Setup Script for Agricultural Marketplace

echo "======================================"
echo "Agricultural Marketplace - DB Setup"
echo "======================================"
echo ""

# Check if MySQL is installed
if ! command -v mysql &> /dev/null; then
    echo "❌ MySQL is not installed or not in PATH"
    echo "Please ensure MySQL is installed and add it to your PATH"
    exit 1
fi

echo "✓ MySQL found"
echo ""

# Prompt for MySQL credentials
read -p "Enter MySQL username (default: root): " DB_USER
DB_USER=${DB_USER:-root}

read -sp "Enter MySQL password (press Enter if none): " DB_PASS
echo ""

# Create database and import schema
echo "📦 Creating database..."

if [ -z "$DB_PASS" ]; then
    mysql -u "$DB_USER" < config/schema.sql
else
    mysql -u "$DB_USER" -p"$DB_PASS" < config/schema.sql
fi

if [ $? -eq 0 ]; then
    echo "✓ Database created successfully"
else
    echo "❌ Failed to create database"
    exit 1
fi

echo ""
echo "👥 Adding sample data..."

SAMPLE_DATA="
USE agri_system;

-- Insert test farmer
INSERT INTO users (email, password, first_name, last_name, phone, role_id, is_active) 
VALUES ('farmer@test.com', '\$2y\$10\$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36DRjk3u', 'John', 'Farmer', '555-1111', 2, 1);

INSERT INTO farmer_profiles (user_id, farm_name, farm_size, crops_grown, certification)
VALUES (1, 'Green Valley Farm', 50, 'Tomatoes, Carrots, Lettuce', 'Organic');

-- Insert test consumer
INSERT INTO users (email, password, first_name, last_name, phone, role_id, is_active)
VALUES ('consumer@test.com', '\$2y\$10\$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36DRjk3u', 'Jane', 'Consumer', '555-2222', 3, 1);

INSERT INTO consumer_profiles (user_id, address, postal_code)
VALUES (2, '123 Main St', '12345');

-- Insert test admin
INSERT INTO users (email, password, first_name, last_name, phone, role_id, is_active)
VALUES ('admin@test.com', '\$2y\$10\$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36DRjk3u', 'Admin', 'User', '555-3333', 1, 1);

-- Insert products
INSERT INTO products (farmer_id, name, description, category, price, quantity, unit, is_available) VALUES
(1, 'Tomato', 'Fresh red tomatoes', 'Vegetables', 3.50, 100, 'lb', 1),
(1, 'Carrot', 'Organic carrots', 'Vegetables', 2.50, 150, 'lb', 1),
(1, 'Lettuce', 'Green lettuce head', 'Vegetables', 1.50, 80, 'head', 1),
(1, 'Cucumber', 'Fresh cucumbers', 'Vegetables', 2.00, 60, 'lb', 1),
(1, 'Bell Pepper', 'Red bell peppers', 'Vegetables', 4.00, 50, 'lb', 1);
"

if [ -z "$DB_PASS" ]; then
    echo "$SAMPLE_DATA" | mysql -u "$DB_USER"
else
    echo "$SAMPLE_DATA" | mysql -u "$DB_USER" -p"$DB_PASS"
fi

if [ $? -eq 0 ]; then
    echo "✓ Sample data added"
else
    echo "⚠ Warning: Some sample data may not have been added"
fi

echo ""
echo "======================================"
echo "✅ Database setup complete!"
echo "======================================"
echo ""
echo "📋 Test Credentials:"
echo "   Farmer:   farmer@test.com / password123"
echo "   Consumer: consumer@test.com / password123"
echo "   Admin:    admin@test.com / password123"
echo ""
echo "🌐 Access the system at:"
echo "   http://localhost/agri/auth/login.php"
echo ""
