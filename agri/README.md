# Agricultural Marketplace - Authentication Module

A complete authentication system for an agricultural marketplace with role-based access control (RBAC) for Farmers and Consumers.

## Features

### Authentication
- **User Registration**: Secure registration with email verification setup
- **User Login**: Session-based authentication with password hashing
- **User Logout**: Secure session cleanup
- **Password Security**: Password hashing with PHP's `password_hash()`

### Role-Based Access Control (RBAC)
- **Three Roles**: Admin, Farmer, Consumer
- **Role-Specific Profiles**: Extended user information for each role
- **Access Middleware**: Protect routes based on user roles
- **Session Management**: Automatic session timeout

### Farmer Features
- Farm profile management
- Track farm size and crops
- Farming practices and certifications
- Product listing capabilities

### Consumer Features
- Consumer profile management
- Dietary preferences and restrictions
- Address and delivery information
- Product browsing and ordering

### Security Features
- CSRF token generation and validation
- SQL injection prevention with prepared statements
- Password strength validation
- Email format validation
- Security headers (XSS, Clickjacking protection)
- Login attempt logging
- Session timeout protection

## Project Structure

```
agri/
├── config/
│   ├── database.php          # Database configuration
│   ├── constants.php         # Application constants and roles
│   ├── init.php             # Application initialization
│   ├── bootstrap.php        # Bootstrap file for autoloading
│   └── schema.sql           # Database schema
├── app/
│   ├── controllers/
│   │   └── AuthController.php
│   ├── models/
│   │   ├── User.php
│   │   ├── FarmerProfile.php
│   │   └── ConsumerProfile.php
│   ├── services/
│   │   └── AuthService.php
│   └── middleware/
│       ├── AuthMiddleware.php
│       └── SecurityMiddleware.php
├── auth/
│   ├── login.php
│   ├── register.php
│   └── logout.php
├── public/
│   ├── views/
│   │   ├── login.php
│   │   ├── register.php
│   │   └── errors/403.php
│   ├── css/
│   │   └── style.css
│   └── js/
├── farmer/
│   └── dashboard.php
├── consumer/
│   └── dashboard.php
├── admin/
│   └── dashboard.php
├── index.php                # Home page
└── dashboard.php            # User dashboard
```

## Database Tables

1. **roles** - User roles (admin, farmer, consumer)
2. **users** - Main user information
3. **farmer_profiles** - Farmer-specific data
4. **consumer_profiles** - Consumer-specific data
5. **password_reset_tokens** - Password reset functionality
6. **login_logs** - Audit trail for logins/logouts

## Installation

### 1. Database Setup

```sql
-- Run the schema.sql file to create database and tables
mysql -u root < config/schema.sql
```

Or manually:
1. Create a new database named `agri_system`
2. Run the queries in `config/schema.sql`

### 2. Configuration

Update `config/database.php` with your database credentials:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'your_password');
define('DB_NAME', 'agri_system');
```

### 3. File Permissions

Ensure the following directories are writable:
- `public/` (for file uploads if needed)
- Session storage directory

## Usage

### User Registration

1. Navigate to `/register.php`
2. Fill in the registration form
3. Select role (Farmer or Consumer)
4. Create an account

### User Login

1. Navigate to `/index.php` or `/`
2. Enter email and password
3. Redirects to role-specific dashboard

### Role-Based Access

#### Protecting Routes

For Farmer-only pages:
```php
<?php
require_once __DIR__ . '/../config/bootstrap.php';
AuthMiddleware::requireRole(ROLE_FARMER);
// Your code here
?>
```

For multiple roles:
```php
AuthMiddleware::requireAnyRole([ROLE_FARMER, ROLE_ADMIN]);
```

#### Checking Permissions

```php
if (AuthMiddleware::isFarmer()) {
    // Farmer-specific code
}

if (AuthMiddleware::isConsumer()) {
    // Consumer-specific code
}

if (AuthMiddleware::isAdmin()) {
    // Admin-specific code
}
```

## API Reference

### AuthService

#### `register()`
Registers a new user with validation.

```php
$result = $authService->register($email, $password, $confirm_password, $first_name, $last_name, $role_name);
```

#### `login()`
Authenticates a user and creates session.

```php
$result = $authService->login($email, $password);
```

#### `logout()`
Destroys user session.

```php
$authService->logout();
```

#### `isAuthenticated()`
Checks if user is logged in.

```php
if ($authService->isAuthenticated()) { ... }
```

### User Model

#### `findByEmail()`
```php
$user = $userModel->findByEmail('user@example.com');
```

#### `findById()`
```php
$user = $userModel->findById(1);
```

#### `create()`
```php
$userId = $userModel->create($email, $password, $first_name, $last_name, $role_id);
```

## Security Considerations

1. **Passwords**: Never store plaintext passwords; always use `password_hash()`
2. **Sessions**: Use `session_start()` at the beginning and secure session configuration
3. **SQL Injection**: Use `real_escape_string()` or prepared statements
4. **CSRF**: Implement and validate CSRF tokens
5. **HTTPS**: Always use HTTPS in production
6. **Headers**: Security headers are set via `SecurityMiddleware`

## Testing

### Test Registration
1. Go to `http://localhost/agri/register.php`
2. Register as a Farmer or Consumer
3. Check database for new user record

### Test Login
1. Go to `http://localhost/agri/index.php`
2. Login with credentials
3. Verify redirect to appropriate dashboard

### Test Role Access
1. Login as Farmer
2. Try to access `/consumer/dashboard.php` (should fail)
3. Login as Consumer
4. Verify no access to Farmer-specific pages

## Environment Variables

For production, create a `.env` file:

```
DB_HOST=localhost
DB_USER=root
DB_PASS=secure_password
DB_NAME=agri_system
JWT_SECRET=your_jwt_secret
SESSION_TIMEOUT=30
```

## Troubleshooting

### "Connection failed" error
- Check database credentials in `config/database.php`
- Verify MySQL is running
- Check database exists

### "Invalid email or password"
- Ensure user exists in database
- Verify password is correct
- Check user's `is_active` flag

### "Access Denied"
- User doesn't have required role
- Check `roles` table for role assignment
- Verify session is active

## Future Enhancements

1. Email verification on registration
2. Password reset functionality
3. Two-factor authentication
4. OAuth integration (Google, Facebook)
5. API token authentication
6. Rate limiting on login attempts
7. User profile images
8. Email notifications

## License

This project is part of the Agricultural Marketplace system.
