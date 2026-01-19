<?php
/**
 * Application Constants
 */

// Base URL
define('BASE_URL', 'http://localhost/agri/');

// Session timeout (in minutes)
define('SESSION_TIMEOUT', 30);

// Password hashing
define('PASSWORD_ALGO', PASSWORD_DEFAULT);

// JWT Secret (for API tokens if needed)
define('JWT_SECRET', 'your_secret_key_here_change_in_production');

// User Roles
define('ROLE_FARMER', 'farmer');
define('ROLE_CONSUMER', 'consumer');
define('ROLE_ADMIN', 'admin');

// HTTP Status Codes
define('HTTP_OK', 200);
define('HTTP_CREATED', 201);
define('HTTP_BAD_REQUEST', 400);
define('HTTP_UNAUTHORIZED', 401);
define('HTTP_FORBIDDEN', 403);
define('HTTP_NOT_FOUND', 404);

// Messages
define('MSG_SUCCESS', 'Operation successful');
define('MSG_ERROR', 'An error occurred');
define('MSG_INVALID_CREDENTIALS', 'Invalid email or password');
define('MSG_EMAIL_EXISTS', 'Email already registered');
define('MSG_USER_NOT_FOUND', 'User not found');
define('MSG_UNAUTHORIZED', 'Unauthorized access');
?>
