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
if (!isset($requestData['product_id']) || !isset($requestData['size']) || !isset($requestData['new_quantity'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error_message' => 'Missing required fields: product_id, size, and new_quantity'
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

// Validate new_quantity is numeric and positive
if (!is_numeric($requestData['new_quantity']) || $requestData['new_quantity'] <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error_message' => 'Invalid quantity'
    ]);
    exit;
}

try {
    // Get CartController instance
    $cartController = CartController::getInstance();
    
    // Update item quantity
    $result = $cartController->updateItemQuantity(
        (int)$requestData['product_id'],
        $requestData['size'],
        (int)$requestData['new_quantity']
    );
    
    if (!$result['success']) {
        echo json_encode($result);
        exit;
    }
    
    // Get updated cart contents to calculate totals
    $cartContents = $cartController->getCartContents();
    $updatedItem = null;
    $newCartTotal = 0;
    
    foreach ($cartContents as $item) {
        if ($item['product_id'] == $requestData['product_id'] && $item['size'] == $requestData['size']) {
            $updatedItem = $item;
        }
        $newCartTotal += ($item['discount_price'] ?? $item['price']) * $item['quantity'];
    }
    
    // Return success response with updated totals
    echo json_encode([
        'success' => true,
        'updated_subtotal' => $updatedItem ? ($updatedItem['discount_price'] ?? $updatedItem['price']) * $updatedItem['quantity'] : 0,
        'new_cart_total' => $newCartTotal
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error_message' => 'An error occurred while updating the cart'
    ]);
}
?>