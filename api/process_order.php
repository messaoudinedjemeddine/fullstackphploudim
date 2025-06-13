<?php

require_once __DIR__ . '/../init.php';

use App\Controllers\OrderController;
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
$requiredFields = [
    'full_name',
    'phone',
    'wilaya_code',
    'delivery_type'
];

foreach ($requiredFields as $field) {
    if (!isset($requestData[$field]) || empty($requestData[$field])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error_message' => "Missing required field: $field"
        ]);
        exit;
    }
}

// Validate delivery type specific fields
if ($requestData['delivery_type'] === 'home' && empty($requestData['address'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error_message' => 'Address is required for home delivery'
    ]);
    exit;
}

if ($requestData['delivery_type'] === 'desk' && empty($requestData['pickup_desk_id'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error_message' => 'Pickup desk is required for desk delivery'
    ]);
    exit;
}

try {
    // Get CartController instance
    $cartController = CartController::getInstance();
    
    // Get cart contents
    $cartItems = $cartController->getCartContents();
    
    // Check if cart is empty
    if (empty($cartItems)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error_message' => 'Cart is empty'
        ]);
        exit;
    }
    
    // Get OrderController instance
    $orderController = OrderController::getInstance();
    
    // Create order
    $result = $orderController->createOrder($requestData, $cartItems);
    
    if (!$result['success']) {
        echo json_encode($result);
        exit;
    }
    
    // Store order ID in session for confirmation page
    $_SESSION['last_order_id'] = $result['order_id'];
    
    // Return success response
    echo json_encode([
        'success' => true,
        'order_id' => $result['order_id'],
        'total_amount' => $result['total_amount']
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error_message' => 'An error occurred while processing the order'
    ]);
}
?>