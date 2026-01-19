-- ============================================
-- COMPLETE DATABASE SETUP - Copy & Paste All
-- ============================================
-- Use this if you prefer to manually run SQL in phpMyAdmin

-- Make sure this runs on the agri_system database
-- In phpMyAdmin: Select or create database 'agri_system' first

-- ============================================
-- CREATE ALL TABLES (already in schema.sql)
-- ============================================

-- Run schema.sql first from: config/schema.sql
-- OR import it via phpMyAdmin Import tab

-- ============================================
-- INSERT SAMPLE DATA
-- ============================================

-- Insert Roles (if not already inserted)
INSERT INTO roles (name, description) VALUES
('admin', 'Administrator with full access'),
('farmer', 'Farmer user with product listing capabilities'),
('consumer', 'Consumer user for purchasing products')
ON DUPLICATE KEY UPDATE name=name;

-- ============================================
-- FARMER TEST ACCOUNT
-- ============================================
-- Email: farmer@test.com
-- Password: password123

INSERT INTO users (email, password, first_name, last_name, phone, role_id, is_active) 
VALUES ('farmer@test.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36DRjk3u', 'John', 'Farmer', '555-1111', 2, 1)
ON DUPLICATE KEY UPDATE first_name='John';

-- Get the farmer user ID (should be 1 or 2)
-- SELECT * FROM users WHERE email = 'farmer@test.com';

INSERT INTO farmer_profiles (user_id, farm_name, farm_size, crops_grown, certification)
SELECT id, 'Green Valley Farm', 50, 'Tomatoes, Carrots, Lettuce', 'Organic'
FROM users WHERE email = 'farmer@test.com'
ON DUPLICATE KEY UPDATE farm_name='Green Valley Farm';

-- ============================================
-- CONSUMER TEST ACCOUNT
-- ============================================
-- Email: consumer@test.com
-- Password: password123

INSERT INTO users (email, password, first_name, last_name, phone, role_id, is_active)
VALUES ('consumer@test.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36DRjk3u', 'Jane', 'Consumer', '555-2222', 3, 1)
ON DUPLICATE KEY UPDATE first_name='Jane';

INSERT INTO consumer_profiles (user_id, address, postal_code)
SELECT id, '123 Main Street', '12345'
FROM users WHERE email = 'consumer@test.com'
ON DUPLICATE KEY UPDATE address='123 Main Street';

-- ============================================
-- ADMIN TEST ACCOUNT
-- ============================================
-- Email: admin@test.com
-- Password: password123

INSERT INTO users (email, password, first_name, last_name, phone, role_id, is_active)
VALUES ('admin@test.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36DRjk3u', 'Admin', 'User', '555-3333', 1, 1)
ON DUPLICATE KEY UPDATE first_name='Admin';

-- ============================================
-- SAMPLE PRODUCTS
-- ============================================

-- Get farmer ID for inserting products
-- SELECT id FROM users WHERE email = 'farmer@test.com';
-- Use that ID in the farmer_id field below

INSERT INTO products (farmer_id, name, description, category, price, quantity, unit, is_available) 
SELECT u.id, 'Tomato', 'Fresh red tomatoes', 'Vegetables', 3.50, 100, 'lb', 1
FROM users u WHERE u.email = 'farmer@test.com'
ON DUPLICATE KEY UPDATE quantity=100;

INSERT INTO products (farmer_id, name, description, category, price, quantity, unit, is_available) 
SELECT u.id, 'Carrot', 'Organic carrots', 'Vegetables', 2.50, 150, 'lb', 1
FROM users u WHERE u.email = 'farmer@test.com'
ON DUPLICATE KEY UPDATE quantity=150;

INSERT INTO products (farmer_id, name, description, category, price, quantity, unit, is_available) 
SELECT u.id, 'Lettuce', 'Green lettuce head', 'Vegetables', 1.50, 80, 'head', 1
FROM users u WHERE u.email = 'farmer@test.com'
ON DUPLICATE KEY UPDATE quantity=80;

INSERT INTO products (farmer_id, name, description, category, price, quantity, unit, is_available) 
SELECT u.id, 'Cucumber', 'Fresh cucumbers', 'Vegetables', 2.00, 60, 'lb', 1
FROM users u WHERE u.email = 'farmer@test.com'
ON DUPLICATE KEY UPDATE quantity=60;

INSERT INTO products (farmer_id, name, description, category, price, quantity, unit, is_available) 
SELECT u.id, 'Bell Pepper', 'Red bell peppers', 'Vegetables', 4.00, 50, 'lb', 1
FROM users u WHERE u.email = 'farmer@test.com'
ON DUPLICATE KEY UPDATE quantity=50;

-- ============================================
-- VERIFY DATA
-- ============================================

-- Run these queries to verify everything was inserted:

-- Check roles
-- SELECT * FROM roles;

-- Check users
-- SELECT id, email, first_name, last_name, role_id FROM users;

-- Check farmer profile
-- SELECT * FROM farmer_profiles;

-- Check consumer profile
-- SELECT * FROM consumer_profiles;

-- Check products
-- SELECT p.id, p.name, p.price, p.quantity, u.first_name as farmer 
-- FROM products p
-- JOIN users u ON p.farmer_id = u.id;

-- ============================================
-- DONE!
-- ============================================
-- You now have a complete database setup with:
-- ✓ 3 Users (Farmer, Consumer, Admin)
-- ✓ 5 Sample Products
-- ✓ Ready to test the order management system
--
-- Test URLs:
-- http://localhost/agri/auth/login.php
--
-- Test Credentials:
-- Farmer:   farmer@test.com / password123
-- Consumer: consumer@test.com / password123
-- Admin:    admin@test.com / password123
-- ============================================
