<?php
/**
 * Farmer Profile Model
 */

class FarmerProfile {
    private $conn;
    private $table = 'farmer_profiles';

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Create farmer profile
     */
    public function create($user_id, $farm_name, $farm_size = null, $crops_grown = '', $farming_practices = '', $certification = '') {
        $user_id = (int)$user_id;
        $farm_name = $this->conn->real_escape_string($farm_name);
        $farm_size = $farm_size ? (float)$farm_size : 'NULL';
        $crops_grown = $this->conn->real_escape_string($crops_grown);
        $farming_practices = $this->conn->real_escape_string($farming_practices);
        $certification = $this->conn->real_escape_string($certification);

        $query = "INSERT INTO $this->table 
                  (user_id, farm_name, farm_size, crops_grown, farming_practices, certification) 
                  VALUES ($user_id, '$farm_name', $farm_size, '$crops_grown', '$farming_practices', '$certification')";

        return $this->conn->query($query) ? $this->conn->insert_id : false;
    }

    /**
     * Get farmer profile by user ID
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
     * Update farmer profile
     */
    public function update($user_id, $data) {
        $user_id = (int)$user_id;
        $updates = [];

        if (isset($data['farm_name'])) {
            $updates[] = "farm_name = '" . $this->conn->real_escape_string($data['farm_name']) . "'";
        }
        if (isset($data['farm_size'])) {
            $updates[] = "farm_size = " . (float)$data['farm_size'];
        }
        if (isset($data['crops_grown'])) {
            $updates[] = "crops_grown = '" . $this->conn->real_escape_string($data['crops_grown']) . "'";
        }
        if (isset($data['farming_practices'])) {
            $updates[] = "farming_practices = '" . $this->conn->real_escape_string($data['farming_practices']) . "'";
        }
        if (isset($data['certification'])) {
            $updates[] = "certification = '" . $this->conn->real_escape_string($data['certification']) . "'";
        }

        if (empty($updates)) {
            return false;
        }

        $query = "UPDATE $this->table SET " . implode(", ", $updates) . " WHERE user_id = $user_id";
        return $this->conn->query($query);
    }
}
?>
