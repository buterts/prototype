<?php
/**
 * Consumer Profile Model
 */

class ConsumerProfile {
    private $conn;
    private $table = 'consumer_profiles';

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Create consumer profile
     */
    public function create($user_id, $preferences = '', $dietary_restrictions = '', $address = '', $postal_code = '') {
        $user_id = (int)$user_id;
        $preferences = $this->conn->real_escape_string($preferences);
        $dietary_restrictions = $this->conn->real_escape_string($dietary_restrictions);
        $address = $this->conn->real_escape_string($address);
        $postal_code = $this->conn->real_escape_string($postal_code);

        $query = "INSERT INTO $this->table 
                  (user_id, preferences, dietary_restrictions, address, postal_code) 
                  VALUES ($user_id, '$preferences', '$dietary_restrictions', '$address', '$postal_code')";

        return $this->conn->query($query) ? $this->conn->insert_id : false;
    }

    /**
     * Get consumer profile by user ID
     */
    public function getByUserId($user_id) {
        $user_id = (int)$user_id;
        $query = "SELECT * FROM $this->table WHERE user_id = $user_id";
        
        $result = $this->conn->query($query);
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return null;
    }

    /**
     * Update consumer profile
     */
    public function update($user_id, $data) {
        $user_id = (int)$user_id;
        $updates = [];

        if (isset($data['preferences'])) {
            $updates[] = "preferences = '" . $this->conn->real_escape_string($data['preferences']) . "'";
        }
        if (isset($data['dietary_restrictions'])) {
            $updates[] = "dietary_restrictions = '" . $this->conn->real_escape_string($data['dietary_restrictions']) . "'";
        }
        if (isset($data['address'])) {
            $updates[] = "address = '" . $this->conn->real_escape_string($data['address']) . "'";
        }
        if (isset($data['postal_code'])) {
            $updates[] = "postal_code = '" . $this->conn->real_escape_string($data['postal_code']) . "'";
        }

        if (empty($updates)) {
            return false;
        }

        $query = "UPDATE $this->table SET " . implode(", ", $updates) . " WHERE user_id = $user_id";
        return $this->conn->query($query);
    }
}
?>
