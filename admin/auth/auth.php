<?php
require_once __DIR__ . '/../../init.php';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        header('Location: ' . BASE_URL . 'admin/auth/login.php?error=invalid_request');
        exit;
    }
    
    // Attempt login
    if (App\Auth::login($username, $password)) {
        $user = App\Auth::user();
        
        // Check if user has admin role
        $allowedRoles = ['super_admin', 'admin', 'delivery_agent', 'call_agent'];
        if (!$user || !in_array($user['role'], $allowedRoles)) {
            App\Auth::logout();
            header('Location: ' . BASE_URL . 'admin/auth/login.php?error=unauthorized');
            exit;
        }
        
        // Redirect based on role
        switch ($user['role']) {
            case 'super_admin':
            case 'admin':
                header('Location: ' . BASE_URL . 'admin/index.php');
                break;
            case 'delivery_agent':
                header('Location: ' . BASE_URL . 'admin/delivery/orders.php');
                break;
            case 'call_agent':
                header('Location: ' . BASE_URL . 'admin/call_center/orders.php');
                break;
            default:
                App\Auth::logout();
                header('Location: ' . BASE_URL . 'admin/auth/login.php?error=unauthorized');
        }
        exit;
    } else {
        header('Location: ' . BASE_URL . 'admin/auth/login.php?error=invalid_credentials');
        exit;
    }
}

// If not POST request, redirect to login page
header('Location: ' . BASE_URL . 'admin/auth/login.php');
exit; 