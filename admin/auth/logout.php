<?php
require_once __DIR__ . '/../../init.php';

// Logout the user
App\Auth::logout();

// Redirect to login page
header('Location: ' . BASE_URL . 'admin/auth/login.php');
exit;