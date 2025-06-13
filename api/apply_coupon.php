<?php

require_once __DIR__ . '/../init.php';

use App\Controllers\CartController;

// Set JSON response header
header('Content-Type: application/json');

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error_message' => 'Method not allowed'
    ]);
    exit;
}

// Get and decode JSON request body
$requestData = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (!isset($requestData['coupon_code']) || !isset($requestData['cart_total'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error_message' => 'Missing required fields: coupon_code and cart_total'
    ]);
    exit;
}

// Validate cart_total is numeric and positive
if (!is_numeric($requestData['cart_total']) || $requestData['cart_total'] <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error_message' => 'Invalid cart total'
    ]);
    exit;
}

try {
    // Get CartController instance
    $cartController = CartController::getInstance();
    
    // Apply coupon
    $result = $cartController->applyCoupon(
        $requestData['coupon_code'],
        (float)$requestData['cart_total']
    );
    
    // Return the result directly as it's already in the correct format
    echo json_encode($result);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error_message' => 'An error occurred while applying the coupon'
    ]);
}
?>