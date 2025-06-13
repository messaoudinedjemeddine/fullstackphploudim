<?php

/**
 * Application Configuration
 * 
 * This file contains global constants used throughout the application.
 */

// Base URL of the application
define('BASE_URL', 'http://localhost/loudimm/');

// Default language (ar = Arabic, en = English, fr = French)
define('DEFAULT_LANG', 'ar');

// Upload directory for product images
define('UPLOAD_DIR', __DIR__ . '/../public/images/products/');

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'ecommerce_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Ensure the upload directory exists
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

// Additional useful constants
define('APP_NAME', 'Loudream E-commerce');
define('APP_VERSION', '1.0.0');
define('APP_ROOT', dirname(__DIR__));
define('PUBLIC_ROOT', APP_ROOT . '/public');
define('TEMPLATES_ROOT', APP_ROOT . '/templates');
define('CACHE_ROOT', APP_ROOT . '/cache');

// Time zone
date_default_timezone_set('Africa/Algiers');

// Error reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Application Configuration
define('APP_ENV', 'development'); // development, production

// Language Configuration
define('AVAILABLE_LANGUAGES', ['en', 'ar', 'fr']);

// Pagination
define('ITEMS_PER_PAGE', 12);

// Upload Configuration
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// Session Configuration

?>