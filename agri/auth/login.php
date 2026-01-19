<?php
require_once __DIR__ . '/../config/bootstrap.php';

$authController = new AuthController($authService);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $authController->handleLogin();
} else {
    $authController->showLogin();
}
?>
