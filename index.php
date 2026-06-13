<?php
/**
 * IDMA SMS/LMS - Main Entry Point
 */

session_start();

require_once 'config/database.php';
require_once 'config/constants.php';
require_once 'src/helpers/Functions.php';
require_once 'src/helpers/Security.php';

// Initialize application
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$base_path = '/idma-sms-lms';
$route = str_replace($base_path, '', $request_uri);
$route = trim($route, '/');

// Check if user is authenticated
$is_authenticated = isset($_SESSION['user_id']) && isset($_SESSION['role']);
$user_role = $_SESSION['role'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;

// Public routes (no authentication required)
$public_routes = ['', 'index', 'login', 'register', 'forgot-password', 'reset-password', 'api/auth'];

// Route handling
if (empty($route) || $route === 'index' || $route === 'index.php') {
    if ($is_authenticated) {
        // Redirect based on role
        switch ($user_role) {
            case ROLE_ADMIN:
                header('Location: ' . APP_URL . '/admin/dashboard');
                exit;
            case ROLE_LECTURER:
                header('Location: ' . APP_URL . '/lecturer/dashboard');
                exit;
            case ROLE_STUDENT:
                header('Location: ' . APP_URL . '/student/dashboard');
                exit;
            case ROLE_FINANCE:
                header('Location: ' . APP_URL . '/finance/dashboard');
                exit;
            case ROLE_ADMISSIONS:
                header('Location: ' . APP_URL . '/admissions/dashboard');
                exit;
        }
    }
    include 'public/pages/home.php';
} elseif ($route === 'login') {
    if ($is_authenticated) {
        header('Location: ' . APP_URL);
        exit;
    }
    include 'public/pages/login.php';
} elseif ($route === 'register') {
    include 'public/pages/register.php';
} elseif ($route === 'logout') {
    session_destroy();
    header('Location: ' . APP_URL . '/login');
    exit;
} elseif (strpos($route, 'admin/') === 0) {
    if (!$is_authenticated || $user_role !== ROLE_ADMIN) {
        header('HTTP/1.1 403 Forbidden');
        die('Access Denied');
    }
    $controller = 'admin/' . str_replace('admin/', '', $route);
    if (file_exists($controller . '.php')) {
        include $controller . '.php';
    } else {
        header('HTTP/1.1 404 Not Found');
        include 'public/pages/404.php';
    }
} elseif (strpos($route, 'lecturer/') === 0) {
    if (!$is_authenticated || $user_role !== ROLE_LECTURER) {
        header('HTTP/1.1 403 Forbidden');
        die('Access Denied');
    }
    $controller = 'lecturer/' . str_replace('lecturer/', '', $route);
    if (file_exists($controller . '.php')) {
        include $controller . '.php';
    } else {
        header('HTTP/1.1 404 Not Found');
        include 'public/pages/404.php';
    }
} elseif (strpos($route, 'student/') === 0) {
    if (!$is_authenticated || $user_role !== ROLE_STUDENT) {
        header('HTTP/1.1 403 Forbidden');
        die('Access Denied');
    }
    $controller = 'student/' . str_replace('student/', '', $route);
    if (file_exists($controller . '.php')) {
        include $controller . '.php';
    } else {
        header('HTTP/1.1 404 Not Found');
        include 'public/pages/404.php';
    }
} elseif (strpos($route, 'finance/') === 0) {
    if (!$is_authenticated || $user_role !== ROLE_FINANCE) {
        header('HTTP/1.1 403 Forbidden');
        die('Access Denied');
    }
    $controller = 'finance/' . str_replace('finance/', '', $route);
    if (file_exists($controller . '.php')) {
        include $controller . '.php';
    } else {
        header('HTTP/1.1 404 Not Found');
        include 'public/pages/404.php';
    }
} elseif (strpos($route, 'admissions/') === 0) {
    if (!$is_authenticated || $user_role !== ROLE_ADMISSIONS) {
        header('HTTP/1.1 403 Forbidden');
        die('Access Denied');
    }
    $controller = 'admissions/' . str_replace('admissions/', '', $route);
    if (file_exists($controller . '.php')) {
        include $controller . '.php';
    } else {
        header('HTTP/1.1 404 Not Found');
        include 'public/pages/404.php';
    }
} elseif (strpos($route, 'api/') === 0) {
    header('Content-Type: application/json');
    $api_route = str_replace('api/', '', $route);
    $api_file = 'src/api/' . $api_route . '.php';
    if (file_exists($api_file)) {
        include $api_file;
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'API endpoint not found']);
    }
} else {
    header('HTTP/1.1 404 Not Found');
    include 'public/pages/404.php';
}

?>
