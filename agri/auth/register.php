<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../app/controllers/AuthController.php';

$authController = new AuthController($authService);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $authController->handleRegister();
} else {
    $authController->showRegister();
}
?>
