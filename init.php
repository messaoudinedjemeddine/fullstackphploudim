<?php
/**
 * Global Initializer
 * 
 * This file should be included at the top of every PHP page and API endpoint.
 * It handles session management, database connection, autoloading, and basic configuration.
 */

ini_set('session.cookie_lifetime', 86400); // 24 hours
ini_set('session.gc_maxlifetime', 86400);

// Start session
session_start();

// Load Composer's autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Load application configuration
require_once __DIR__ . '/config/app.php';

// Load helper functions
require_once __DIR__ . '/src/helpers.php';

// Initialize database connection
try {
    $db = new \App\Database(DB_HOST, DB_NAME, DB_USER, DB_PASS);
    $GLOBALS['pdo'] = $db->getConnection();
} catch (\Exception $e) {
    error_log("Database Connection Error: " . $e->getMessage());
    die("Database connection failed. Please try again later.");
}

// Language detection and setup
$allowedLanguages = ['en', 'fr', 'ar'];

// Check if language is set in GET parameter
if (isset($_GET['lang']) && in_array($_GET['lang'], $allowedLanguages)) {
    $_SESSION['lang'] = $_GET['lang'];
}

// Set default language if not set in session
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = DEFAULT_LANG;
}

// Error reporting setup
if (defined('APP_ENV') && APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Set default timezone
date_default_timezone_set('Africa/Algiers');

// Set default character encoding
mb_internal_encoding('UTF-8');

// Security headers
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

// CSRF Protection
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !\App\csrf_check($_POST['csrf_token'])) {
        die('CSRF token validation failed');
    }
}

// Function to get database connection
function db() {
    return $GLOBALS['pdo'];
}

// Function to get current language
function current_lang() {
    return $_SESSION['lang'] ?? DEFAULT_LANG;
}

// Function to switch language
function switch_lang($lang) {
    if (in_array($lang, ['en', 'fr', 'ar'])) {
        $_SESSION['lang'] = $lang;
        return true;
    }
    return false;
}

// Function to check if request is AJAX
function is_ajax() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

// Function to get client IP address
function get_client_ip() {
    $ipaddress = '';
    if (isset($_SERVER['HTTP_CLIENT_IP']))
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_X_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    else if(isset($_SERVER['REMOTE_ADDR']))
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    else
        $ipaddress = 'UNKNOWN';
    return $ipaddress;
}

// Initialize authentication
$auth = new \App\Auth();

// Handle language switching
if (isset($_GET['lang']) && in_array($_GET['lang'], AVAILABLE_LANGUAGES)) {
    $_SESSION['language'] = $_GET['lang'];
}

// Set current language
$current_lang = $_SESSION['language'] ?? DEFAULT_LANG;

// Load language file
$lang = [];
$lang_file = "lang/$current_lang.php";
if (file_exists($lang_file)) {
    $lang = include $lang_file;
}

// Define global functions that might be needed across the application

/**
 * Redirect to a URL
 */
function redirect($url, $permanent = false) {
    $status_code = $permanent ? 301 : 302;
    header("Location: $url", true, $status_code);
    exit;
}

/**
 * Check if user is logged in (for admin)
 */
function requireLogin() {
    global $auth;
    if (!$auth->check()) {
        redirect('/admin/auth/login.php');
    }
}

/**
 * Check if user has required role
 */
function requireRole($roles) {
    global $auth;
    requireLogin();
    
    if (is_string($roles)) {
        $roles = [$roles];
    }
    
    foreach ($roles as $role) {
        if (!$auth->checkRole($role)) {
            http_response_code(403);
            die('Access denied');
        }
    }
}

/**
 * Get current user
 */
function getCurrentUser() {
    global $auth;
    return $auth->user();
}

/**
 * Flash message system
 */
function setFlashMessage($message, $type = 'info') {
    $_SESSION['flash_message'] = [
        'message' => $message,
        'type' => $type
    ];
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

/**
 * CSRF Protection
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get base URL
 */
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $script = $_SERVER['SCRIPT_NAME'];
    $path = dirname($script);
    
    return $protocol . '://' . $host . $path;
}

/**
 * Asset URL helper
 */
function asset($path) {
    return getBaseUrl() . '/public/' . ltrim($path, '/');
}

/**
 * URL helper
 */
function url($path = '') {
    return getBaseUrl() . '/' . ltrim($path, '/');
}

/**
 * Include template with variables
 */
function includeTemplate($template, $variables = []) {
    extract($variables);
    include $template;
}

/**
 * JSON response helper
 */
function jsonResponse($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Validate required POST fields
 */
function validateRequired($fields) {
    $missing = [];
    foreach ($fields as $field) {
        if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
            $missing[] = $field;
        }
    }
    return $missing;
}

/**
 * Clean input data
 */
function cleanInput($data) {
    if (is_array($data)) {
        return array_map('cleanInput', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Get user's IP address
 */
function getUserIP() {
    $ip_keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    
    foreach ($ip_keys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                    return $ip;
                }
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Set global variables that templates might need
$GLOBALS['current_lang'] = $current_lang;
$GLOBALS['lang'] = $lang;
$GLOBALS['pdo'] = $pdo;
$GLOBALS['auth'] = $auth;

// Log the page visit (optional)
if (APP_ENV === 'development') {
    $page = $_SERVER['REQUEST_URI'] ?? 'unknown';
    $ip = getUserIP();
    log_activity("Page visit: $page from IP: $ip");
}

// Authentication helper functions
function is_logged_in() {
    global $auth;
    return $auth->check();
}

function can_access($requiredRole) {
    global $auth;
    return $auth->checkRole($requiredRole);
}

function current_user() {
    global $auth;
    return $auth->user();
}

// Activity logging function
function log_activity($action, $details = '') {
    if (!is_logged_in()) return;
    
    $user = current_user();
    $ip = get_client_ip();
    $timestamp = date('Y-m-d H:i:s');
    
    $log = sprintf(
        "[%s] User %s (ID: %d) performed action: %s. Details: %s. IP: %s\n",
        $timestamp,
        $user['username'],
        $user['id'],
        $action,
        $details,
        $ip
    );
    
    error_log($log, 3, __DIR__ . '/logs/activity.log');
}
?>