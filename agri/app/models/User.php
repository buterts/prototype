<?php
/**
 * User Model
 */

class User {
    private $conn;
    private $table = 'users';

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Create a new user
     */
    public function create($email, $password, $first_name, $last_name, $role_id, $phone = '', $location = '') {
        $email = $this->conn->real_escape_string($email);
        $first_name = $this->conn->real_escape_string($first_name);
        $last_name = $this->conn->real_escape_string($last_name);
        $phone = $this->conn->real_escape_string($phone);
        $location = $this->conn->real_escape_string($location);
        
        // Hash password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        $query = "INSERT INTO $this->table 
                  (email, password, first_name, last_name, phone, location, role_id) 
                  VALUES ('$email', '$password_hash', '$first_name', '$last_name', '$phone', '$location', '$role_id')";
        
        if ($this->conn->query($query)) {
            return $this->conn->insert_id;
        }
        return false;
    }

    /**
     * Find user by email
     */
    public function findByEmail($email) {
        $email = $this->conn->real_escape_string($email);
        $query = "SELECT u.*, r.name as role_name 
                  FROM $this->table u
                  LEFT JOIN roles r ON u.role_id = r.id
                  WHERE u.email = '$email' AND u.is_active = 1";
        
        $result = $this->conn->query($query);
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return null;
    }

    /**
     * Find user by ID
     */
    public function findById($id) {
        $id = (int)$id;
        $query = "SELECT u.*, r.name as role_name 
                  FROM $this->table u
                  LEFT JOIN roles r ON u.role_id = r.id
                  WHERE u.id = $id AND u.is_active = 1";
        
        $result = $this->conn->query($query);
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return null;
    }

    /**
     * Verify password
     */
    public function verifyPassword($user, $password) {
        return password_verify($password, $user['password']);
    }

    /**
     * Check if email exists
     */
    public function emailExists($email) {
        $email = $this->conn->real_escape_string($email);
        $query = "SELECT id FROM $this->table WHERE email = '$email'";
        $result = $this->conn->query($query);
        return $result->num_rows > 0;
    }

    /**
     * Update last login
     */
    public function updateLastLogin($user_id) {
        $user_id = (int)$user_id;
        $query = "UPDATE $this->table SET last_login = NOW() WHERE id = $user_id";
        return $this->conn->query($query);
    }

    /**
     * Get user role
     */
    public function getRole($user_id) {
        $user_id = (int)$user_id;
        $query = "SELECT r.name FROM $this->table u
                  LEFT JOIN roles r ON u.role_id = r.id
                  WHERE u.id = $user_id";
        
        $result = $this->conn->query($query);
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['name'];
        }
        return null;
    }

    /**
     * Update user profile
     */
    public function update($user_id, $data) {
        $user_id = (int)$user_id;
        $updates = [];
        
        if (isset($data['first_name'])) {
            $updates[] = "first_name = '" . $this->conn->real_escape_string($data['first_name']) . "'";
        }
        if (isset($data['last_name'])) {
            $updates[] = "last_name = '" . $this->conn->real_escape_string($data['last_name']) . "'";
        }
        if (isset($data['phone'])) {
            $updates[] = "phone = '" . $this->conn->real_escape_string($data['phone']) . "'";
        }
        if (isset($data['location'])) {
            $updates[] = "location = '" . $this->conn->real_escape_string($data['location']) . "'";
        }
        if (isset($data['bio'])) {
            $updates[] = "bio = '" . $this->conn->real_escape_string($data['bio']) . "'";
        }
        
        if (empty($updates)) {
            return false;
        }
        
        $query = "UPDATE $this->table SET " . implode(", ", $updates) . " WHERE id = $user_id";
        return $this->conn->query($query);
    }

    /**
     * Get users by role
     */
    public function getUsersByRole($role_name, $limit = 10, $offset = 0) {
        $role_name = $this->conn->real_escape_string($role_name);
        $limit = (int)$limit;
        $offset = (int)$offset;
        
        $query = "SELECT u.id, u.email, u.first_name, u.last_name, u.phone, u.created_at, r.name as role_name
                  FROM $this->table u
                  LEFT JOIN roles r ON u.role_id = r.id
                  WHERE r.name = '$role_name' AND u.is_active = 1
                  ORDER BY u.created_at DESC
                  LIMIT $limit OFFSET $offset";
        
        $result = $this->conn->query($query);
        $users = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
        }
        return $users;
    }
}
?>
