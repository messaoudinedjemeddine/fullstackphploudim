<?php

require_once __DIR__ . '/../init.php';

use App\Controllers\ProductController;

// Set JSON response header
header('Content-Type: application/json');

// Check if wilaya_code is present
if (!isset($_GET['wilaya_code'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Wilaya code is required'
    ]);
    exit;
}

$wilayaCode = (int)$_GET['wilaya_code'];

// Validate wilaya code
if ($wilayaCode <= 0 || $wilayaCode > 58) { // Algeria has 58 wilayas
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid wilaya code'
    ]);
    exit;
}

try {
    // Get ProductController instance
    $productController = ProductController::getInstance();
    
    // Get wilaya details
    $wilaya = $productController->getDeliveryCityByCode($wilayaCode);
    
    if (!$wilaya) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Wilaya not found'
        ]);
        exit;
    }
    
    // Get delivery desks for the wilaya
    $desks = $productController->getDeliveryDesksByWilaya($wilaya['id']);
    
    // Format desk data with translations
    $formattedDesks = array_map(function($desk) {
        return [
            'id' => $desk['id'],
            'name' => [
                'en' => $desk['name_en'],
                'fr' => $desk['name_fr'],
                'ar' => $desk['name_ar']
            ],
            'address' => [
                'en' => $desk['address_en'],
                'fr' => $desk['address_fr'],
                'ar' => $desk['address_ar']
            ]
        ];
    }, $desks);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'wilaya' => [
            'code' => $wilaya['wilaya_code'],
            'name' => [
                'en' => $wilaya['name_en'],
                'fr' => $wilaya['name_fr'],
                'ar' => $wilaya['name_ar']
            ]
        ],
        'delivery_fees' => [
            'home' => (float)$wilaya['home_fee'],
            'desk' => (float)$wilaya['desk_fee']
        ],
        'pickup_desks' => $formattedDesks
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while fetching delivery options'
    ]);
}
?>