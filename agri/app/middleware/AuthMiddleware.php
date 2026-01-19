<?php
/**
 * Authorization Middleware
 */

class AuthMiddleware {
    /**
     * Check if user is authenticated
     */
    public static function requireAuth() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
            header("Location: " . BASE_URL . "index.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
            exit;
        }
    }

    /**
     * Require specific role
     */
    public static function requireRole($role) {
        self::requireAuth();

        if ($_SESSION['role'] !== $role) {
            header("HTTP/1.0 403 Forbidden");
            include BASE_URL . 'public/views/errors/403.php';
            exit;
        }
    }

    /**
     * Require any of multiple roles
     */
    public static function requireAnyRole($roles) {
        self::requireAuth();

        if (!in_array($_SESSION['role'], $roles)) {
            header("HTTP/1.0 403 Forbidden");
            include BASE_URL . 'public/views/errors/403.php';
            exit;
        }
    }

    /**
     * Check if user is a farmer
     */
    public static function isFarmer() {
        return isset($_SESSION['role']) && $_SESSION['role'] === ROLE_FARMER;
    }

    /**
     * Check if user is a consumer
     */
    public static function isConsumer() {
        return isset($_SESSION['role']) && $_SESSION['role'] === ROLE_CONSUMER;
    }

    /**
     * Check if user is admin
     */
    public static function isAdmin() {
        return isset($_SESSION['role']) && $_SESSION['role'] === ROLE_ADMIN;
    }

    /**
     * Get current user ID
     */
    public static function getUserId() {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Get current user role
     */
    public static function getUserRole() {
        return $_SESSION['role'] ?? null;
    }

    /**
     * Check if own resource
     */
    public static function isOwner($resource_user_id) {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        return $_SESSION['user_id'] == $resource_user_id;
    }
}
?>
