<?php
/**
 * Initialize Application
 */

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Load configuration files
require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/database.php';

// Session timeout check
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT * 60)) {
        session_unset();
        session_destroy();
        header("Location: " . BASE_URL . "index.php?timeout=1");
        exit;
    }
    $_SESSION['last_activity'] = time();
}
?>
