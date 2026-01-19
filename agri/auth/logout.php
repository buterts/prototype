<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../app/controllers/AuthController.php';

$authController = new AuthController($authService);
$authController->handleLogout();
?>
