<?php
/**
 * Authentication Service
 */

class AuthService {
    private $userModel;
    private $farmerModel;
    private $consumerModel;
    private $conn;

    public function __construct($userModel, $farmerModel, $consumerModel, $db) {
        $this->userModel = $userModel;
        $this->farmerModel = $farmerModel;
        $this->consumerModel = $consumerModel;
        $this->conn = $db;
    }

    /**
     * Register a new user
     */
    public function register($email, $password, $confirm_password, $first_name, $last_name, $role_name, $phone = '', $location = '') {
        // Validate input
        if (empty($email) || empty($password) || empty($first_name) || empty($last_name)) {
            return ['success' => false, 'message' => 'All fields are required'];
        }

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email format'];
        }

        // Check password match
        if ($password !== $confirm_password) {
            return ['success' => false, 'message' => 'Passwords do not match'];
        }

        // Check password strength
        if (strlen($password) < 8) {
            return ['success' => false, 'message' => 'Password must be at least 8 characters long'];
        }

        // Check if email already exists
        if ($this->userModel->emailExists($email)) {
            return ['success' => false, 'message' => 'Email already registered'];
        }

        // Get role ID
        $role_id = $this->getRoleId($role_name);
        if (!$role_id) {
            return ['success' => false, 'message' => 'Invalid role'];
        }

        // Create user
        $user_id = $this->userModel->create($email, $password, $first_name, $last_name, $role_id, $phone, $location);
        
        if (!$user_id) {
            return ['success' => false, 'message' => 'Registration failed'];
        }

        // Create role-specific profile
        if ($role_name === ROLE_FARMER) {
            $this->farmerModel->create($user_id, $first_name . "'s Farm");
        } elseif ($role_name === ROLE_CONSUMER) {
            $this->consumerModel->create($user_id, '', '', $location);
        }

        return ['success' => true, 'message' => 'Registration successful', 'user_id' => $user_id];
    }

    /**
     * Login user
     */
    public function login($email, $password) {
        if (empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'Email and password required'];
        }

        // Find user by email
        $user = $this->userModel->findByEmail($email);
        
        if (!$user) {
            return ['success' => false, 'message' => MSG_INVALID_CREDENTIALS];
        }

        // Verify password
        if (!$this->userModel->verifyPassword($user, $password)) {
            return ['success' => false, 'message' => MSG_INVALID_CREDENTIALS];
        }

        // Update last login
        $this->userModel->updateLastLogin($user['id']);

        // Log login attempt
        $this->logLogin($user['id']);

        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];
        $_SESSION['role'] = $user['role_name'];
        $_SESSION['last_activity'] = time();

        return [
            'success' => true,
            'message' => 'Login successful',
            'user_id' => $user['id'],
            'role' => $user['role_name']
        ];
    }

    /**
     * Logout user
     */
    public function logout() {
        if (isset($_SESSION['user_id'])) {
            $this->logLogout($_SESSION['user_id']);
            session_unset();
            session_destroy();
            return ['success' => true, 'message' => 'Logout successful'];
        }
        return ['success' => false, 'message' => 'No user logged in'];
    }

    /**
     * Check if user is authenticated
     */
    public function isAuthenticated() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    /**
     * Check user role
     */
    public function hasRole($role) {
        if (!$this->isAuthenticated()) {
            return false;
        }
        return $_SESSION['role'] === $role;
    }

    /**
     * Check multiple roles
     */
    public function hasAnyRole($roles) {
        if (!$this->isAuthenticated()) {
            return false;
        }
        return in_array($_SESSION['role'], $roles);
    }

    /**
     * Get current user
     */
    public function getCurrentUser() {
        if (!$this->isAuthenticated()) {
            return null;
        }
        return $this->userModel->findById($_SESSION['user_id']);
    }

    /**
     * Get role ID by name
     */
    private function getRoleId($role_name) {
        $role_name = $this->conn->real_escape_string($role_name);
        $query = "SELECT id FROM roles WHERE name = '$role_name'";
        $result = $this->conn->query($query);
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['id'];
        }
        return null;
    }

    /**
     * Log login attempt
     */
    private function logLogin($user_id) {
        $user_id = (int)$user_id;
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $ip_address = $this->conn->real_escape_string($ip_address);
        $user_agent = $this->conn->real_escape_string($user_agent);
        
        $query = "INSERT INTO login_logs (user_id, ip_address, user_agent) 
                  VALUES ($user_id, '$ip_address', '$user_agent')";
        
        return $this->conn->query($query);
    }

    /**
     * Log logout
     */
    private function logLogout($user_id) {
        $user_id = (int)$user_id;
        $query = "UPDATE login_logs SET logout_time = NOW() 
                  WHERE user_id = $user_id AND logout_time IS NULL 
                  ORDER BY login_time DESC LIMIT 1";
        
        return $this->conn->query($query);
    }
}
?>
