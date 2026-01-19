<?php
/**
 * Authentication Controller
 */

class AuthController {
    private $authService;

    public function __construct($authService) {
        $this->authService = $authService;
    }

    /**
     * Show login page
     */
    public function showLogin() {
        if ($this->authService->isAuthenticated()) {
            header("Location: " . BASE_URL . "dashboard.php");
            exit;
        }
        include __DIR__ . '/../../public/views/login.php';
    }

    /**
     * Handle login request
     */
    public function handleLogin() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . BASE_URL . "index.php");
            exit;
        }

        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        $result = $this->authService->login($email, $password);

        if ($result['success']) {
            // Redirect based on role
            switch ($result['role']) {
                case ROLE_FARMER:
                    header("Location: " . BASE_URL . "farmer/dashboard.php");
                    break;
                case ROLE_CONSUMER:
                    header("Location: " . BASE_URL . "consumer/dashboard.php");
                    break;
                case ROLE_ADMIN:
                    header("Location: " . BASE_URL . "admin/dashboard.php");
                    break;
                default:
                    header("Location: " . BASE_URL . "dashboard.php");
            }
            exit;
        } else {
            $_SESSION['login_error'] = $result['message'];
            header("Location: " . BASE_URL . "index.php");
            exit;
        }
    }

    /**
     * Show registration page
     */
    public function showRegister() {
        if ($this->authService->isAuthenticated()) {
            header("Location: " . BASE_URL . "dashboard.php");
            exit;
        }
        include __DIR__ . '/../../public/views/register.php';
    }

    /**
     * Handle registration request
     */
    public function handleRegister() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . BASE_URL . "register.php");
            exit;
        }

        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $first_name = $_POST['first_name'] ?? '';
        $last_name = $_POST['last_name'] ?? '';
        $role = $_POST['role'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $location = $_POST['location'] ?? '';

        $result = $this->authService->register($email, $password, $confirm_password, $first_name, $last_name, $role, $phone, $location);

        if ($result['success']) {
            $_SESSION['register_success'] = 'Registration successful! Please log in.';
            header("Location: " . BASE_URL . "index.php");
            exit;
        } else {
            $_SESSION['register_error'] = $result['message'];
            header("Location: " . BASE_URL . "register.php");
            exit;
        }
    }

    /**
     * Handle logout
     */
    public function handleLogout() {
        $this->authService->logout();
        header("Location: " . BASE_URL . "index.php");
        exit;
    }
}
?>
