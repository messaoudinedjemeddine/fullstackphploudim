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
if (!isset($requestData['product_id']) || !isset($requestData['size'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error_message' => 'Missing required fields: product_id and size'
    ]);
    exit;
}

// Validate product_id is numeric and positive
if (!is_numeric($requestData['product_id']) || $requestData['product_id'] <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error_message' => 'Invalid product ID'
    ]);
    exit;
}

try {
    // Get CartController instance
    $cartController = CartController::getInstance();
    
    // Remove item from cart
    $removed = $cartController->removeItem(
        (int)$requestData['product_id'],
        $requestData['size']
    );
    
    if (!$removed) {
        echo json_encode([
            'success' => false,
            'error_message' => 'Item not found in cart'
        ]);
        exit;
    }
    
    // Get updated cart total
    $newCartTotal = $cartController->getCartTotal();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'new_cart_total' => $newCartTotal
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error_message' => 'An error occurred while removing item from cart'
    ]);
}
?>