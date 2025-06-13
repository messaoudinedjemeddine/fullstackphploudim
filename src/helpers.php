<?php
// Helper functions for the e-commerce application

namespace App;

/**
 * Sanitize input data
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email address
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number
 */
function isValidPhone($phone) {
    return preg_match('/^[\+]?[0-9\s\-\(\)]{10,20}$/', $phone);
}

/**
 * Generate random string
 */
function generateRandomString($length = 10) {
    return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);
}

/**
 * Format currency
 */
function formatCurrency($amount, $currency = 'USD') {
    return number_format($amount, 2);
}

/**
 * Calculate discount
 */
function calculateDiscount($originalPrice, $discountPercent) {
    return $originalPrice * ($discountPercent / 100);
}

/**
 * Get file extension
 */
function getFileExtension($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

/**
 * Validate image file
 */
function isValidImage($file) {
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $extension = getFileExtension($file['name']);
    
    if (!in_array($extension, $allowedTypes)) {
        return false;
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        return false;
    }
    
    return true;
}

/**
 * Upload image file
 */
function uploadImage($file, $uploadDir) {
    if (!isValidImage($file)) {
        return false;
    }
    
    $extension = getFileExtension($file['name']);
    $filename = uniqid() . '.' . $extension;
    $uploadPath = $uploadDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        return $filename;
    }
    
    return false;
}

/**
 * Resize image
 */
function resizeImage($source, $destination, $maxWidth, $maxHeight) {
    $imageInfo = getimagesize($source);
    if (!$imageInfo) {
        return false;
    }
    
    $width = $imageInfo[0];
    $height = $imageInfo[1];
    $type = $imageInfo[2];
    
    // Calculate new dimensions
    $ratio = min($maxWidth / $width, $maxHeight / $height);
    $newWidth = $width * $ratio;
    $newHeight = $height * $ratio;
    
    // Create image resource
    switch ($type) {
        case IMAGETYPE_JPEG:
            $sourceImage = imagecreatefromjpeg($source);
            break;
        case IMAGETYPE_PNG:
            $sourceImage = imagecreatefrompng($source);
            break;
        case IMAGETYPE_GIF:
            $sourceImage = imagecreatefromgif($source);
            break;
        default:
            return false;
    }
    
    // Create new image
    $newImage = imagecreatetruecolor($newWidth, $newHeight);
    
    // Preserve transparency for PNG and GIF
    if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
        imagealphablending($newImage, false);
        imagesavealpha($newImage, true);
        $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
        imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
    }
    
    // Resize image
    imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    
    // Save image
    switch ($type) {
        case IMAGETYPE_JPEG:
            imagejpeg($newImage, $destination, 90);
            break;
        case IMAGETYPE_PNG:
            imagepng($newImage, $destination);
            break;
        case IMAGETYPE_GIF:
            imagegif($newImage, $destination);
            break;
    }
    
    // Clean up
    imagedestroy($sourceImage);
    imagedestroy($newImage);
    
    return true;
}

/**
 * Generate slug from string
 */
function generateSlug($string) {
    $slug = strtolower(trim($string));
    $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    return trim($slug, '-');
}

/**
 * Paginate array
 */
function paginate($array, $page, $perPage) {
    $offset = ($page - 1) * $perPage;
    return array_slice($array, $offset, $perPage);
}

/**
 * Get pagination info
 */
function getPaginationInfo($totalItems, $currentPage, $itemsPerPage) {
    $totalPages = ceil($totalItems / $itemsPerPage);
    $startItem = ($currentPage - 1) * $itemsPerPage + 1;
    $endItem = min($currentPage * $itemsPerPage, $totalItems);
    
    return [
        'total_items' => $totalItems,
        'total_pages' => $totalPages,
        'current_page' => $currentPage,
        'items_per_page' => $itemsPerPage,
        'start_item' => $startItem,
        'end_item' => $endItem,
        'has_previous' => $currentPage > 1,
        'has_next' => $currentPage < $totalPages
    ];
}

/**
 * Format date
 */
function formatDate(?string $date): string {
    if (!$date) return '-';
    return date('Y-m-d', strtotime($date));
}

/**
 * Time ago function
 */
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    if ($time < 31536000) return floor($time/2592000) . ' months ago';
    
    return floor($time/31536000) . ' years ago';
}

/**
 * Truncate text
 */
function truncateText($text, $length = 100, $suffix = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    
    return substr($text, 0, $length) . $suffix;
}

/**
 * Generate breadcrumbs
 */
function generateBreadcrumbs($items) {
    $breadcrumbs = '<nav aria-label="breadcrumb"><ol class="breadcrumb">';
    
    foreach ($items as $index => $item) {
        $isLast = $index === count($items) - 1;
        
        if ($isLast) {
            $breadcrumbs .= '<li class="breadcrumb-item active">' . htmlspecialchars($item['title']) . '</li>';
        } else {
            $breadcrumbs .= '<li class="breadcrumb-item"><a href="' . htmlspecialchars($item['url']) . '">' . htmlspecialchars($item['title']) . '</a></li>';
        }
    }
    
    $breadcrumbs .= '</ol></nav>';
    return $breadcrumbs;
}

/**
 * Log activity
 */
function logActivity($message, $level = 'info') {
    $logFile = '../logs/' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] [$level] $message" . PHP_EOL;
    
    if (!file_exists(dirname($logFile))) {
        mkdir(dirname($logFile), 0755, true);
    }
    
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

/**
 * Send email (basic implementation)
 */
function sendEmail($to, $subject, $message, $headers = '') {
    if (empty($headers)) {
        $headers = "From: " . APP_NAME . " <noreply@example.com>\r\n";
        $headers .= "Reply-To: noreply@example.com\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    }
    
    return mail($to, $subject, $message, $headers);
}

/**
 * Generate order number
 */
function generateOrderNumber($orderId) {
    return 'ORD-' . date('Ymd') . '-' . str_pad($orderId, 6, '0', STR_PAD_LEFT);
}

/**
 * Calculate shipping cost
 */
function calculateShipping($weight, $distance, $shippingMethod = 'standard') {
    $baseCost = 5.00;
    $weightCost = $weight * 0.50;
    $distanceCost = $distance * 0.10;
    
    $multiplier = 1;
    switch ($shippingMethod) {
        case 'express':
            $multiplier = 2;
            break;
        case 'overnight':
            $multiplier = 3;
            break;
    }
    
    return ($baseCost + $weightCost + $distanceCost) * $multiplier;
}

/**
 * Validate coupon
 */
function validateCoupon($code, $cartTotal, $pdo) {
    $stmt = $pdo->prepare("SELECT * FROM coupons WHERE code = ? AND is_active = 1");
    $stmt->execute([$code]);
    $coupon = $stmt->fetch();
    
    if (!$coupon) {
        return ['valid' => false, 'error' => 'Invalid coupon code'];
    }
    
    // Check expiry
    if ($coupon['expires_at'] && strtotime($coupon['expires_at']) < time()) {
        return ['valid' => false, 'error' => 'Coupon has expired'];
    }
    
    // Check usage limit
    if ($coupon['usage_limit'] && $coupon['used_count'] >= $coupon['usage_limit']) {
        return ['valid' => false, 'error' => 'Coupon usage limit reached'];
    }
    
    // Check minimum order amount
    if ($cartTotal < $coupon['min_order_amount']) {
        return ['valid' => false, 'error' => 'Minimum order amount not met'];
    }
    
    // Calculate discount
    $discount = 0;
    if ($coupon['type'] === 'percentage') {
        $discount = ($cartTotal * $coupon['value']) / 100;
    } else {
        $discount = $coupon['value'];
    }
    
    $discount = min($discount, $cartTotal);
    
    return [
        'valid' => true,
        'discount' => $discount,
        'coupon' => $coupon
    ];
}

/**
 * Get client IP address
 */
function getClientIP() {
    $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    
    foreach ($ipKeys as $key) {
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

/**
 * Translation helper function
 * 
 * @param string $key The translation key
 * @return string The translated string or the key if translation not found
 */
function __(string $key): string {
    static $translations = [];

    // Get current language from session or use default
    $lang = $_SESSION['lang'] ?? DEFAULT_LANG;

    // Load translations if not already loaded
    if (!isset($translations[$lang])) {
        $langFile = __DIR__ . "/../lang/{$lang}.php";
        
        // Fallback to default language if current language file doesn't exist
        if (!file_exists($langFile)) {
            $lang = DEFAULT_LANG;
            $langFile = __DIR__ . "/../lang/{$lang}.php";
        }

        // Load translations
        if (file_exists($langFile)) {
            $translations[$lang] = require $langFile;
        } else {
            $translations[$lang] = [];
        }
    }

    // Return translation if exists, otherwise return the key
    return $translations[$lang][$key] ?? $key;
}

/**
 * Format a number as currency
 * 
 * @param float $amount
 * @param string $currency
 * @return string
 */
function format_currency(float $amount, string $currency = 'DZD'): string {
    return number_format($amount, 2, '.', ' ') . ' ' . $currency;
}

/**
 * Format a date according to the current language
 * 
 * @param string $date
 * @param string $format
 * @return string
 */
function format_date(string $date, string $format = 'Y-m-d'): string {
    $timestamp = strtotime($date);
    return date($format, $timestamp);
}

/**
 * Generate a CSRF token
 * 
 * @return string
 */
function csrf_token(): string {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Check if a CSRF token is valid
 * 
 * @param string $token
 * @return bool
 */
function csrf_check(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Redirect to a URL
 * 
 * @param string $url
 * @return void
 */
function redirect(string $url): void {
    header("Location: " . BASE_URL . ltrim($url, '/'));
    exit;
}

/**
 * Get current URL
 * 
 * @return string
 */
function current_url(): string {
    return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . 
           "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
}

/**
 * Check if the current URL matches a pattern
 * 
 * @param string $pattern
 * @return bool
 */
function url_matches(string $pattern): bool {
    $current = parse_url(current_url(), PHP_URL_PATH);
    return fnmatch($pattern, $current);
}

function formatPrice(float $amount): string {
    return number_format($amount, 2) . ' ' . (defined('CURRENCY_SYMBOL') ? CURRENCY_SYMBOL : 'DZD');
}
?>