<?php

namespace App\Controllers;

use App\Database;
use PDO;
use PDOException;
use function App\format_currency;

class CartController {
    private static $instance = null;
    private $pdo;
    private $productController;
    
    private function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
        $this->productController = ProductController::getInstance();
        
        // Initialize cart if not exists
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
    }
    
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getCartContents(): array {
        $cart = $_SESSION['cart'];
        $enrichedCart = [];
        
        foreach ($cart as $key => $item) {
            $product = $this->productController->getProductById($item['product_id']);
            if ($product) {
                $enrichedCart[$key] = [
                    'product_id' => $item['product_id'],
                    'size' => $item['size'],
                    'quantity' => $item['quantity'],
                    'name_en' => $product['name_en'],
                    'name_fr' => $product['name_fr'],
                    'name_ar' => $product['name_ar'],
                    'price' => $product['price'],
                    'discount_price' => $product['discount_price'],
                    'image_path' => $product['images'][0]['path'] ?? null
                ];
            }
        }
        
        return $enrichedCart;
    }
    
    public function addItem(int $productId, string $size, int $quantity): bool|string {
        // Get product details
        $product = $this->productController->getProductById($productId);
        if (!$product) {
            return 'Product not found';
        }
        
        // Check if product is active
        if (!$product['is_active']) {
            return 'Product is not available';
        }
        
        // Check stock
        $sizeFound = false;
        $availableQuantity = 0;
        foreach ($product['sizes'] as $productSize) {
            if ($productSize['size'] === $size) {
                $sizeFound = true;
                $availableQuantity = $productSize['quantity'];
                break;
            }
        }
        
        if (!$sizeFound) {
            return 'Invalid size';
        }
        
        // Check if we have enough stock
        $currentQuantity = 0;
        $cartKey = "{$productId}_{$size}";
        if (isset($_SESSION['cart'][$cartKey])) {
            $currentQuantity = $_SESSION['cart'][$cartKey]['quantity'];
        }
        
        if ($currentQuantity + $quantity > $availableQuantity) {
            return 'Not enough stock available';
        }
        
        // Add/update item in cart
        $_SESSION['cart'][$cartKey] = [
            'product_id' => $productId,
            'size' => $size,
            'quantity' => $currentQuantity + $quantity
        ];
        
        return true;
    }
    
    public function updateItemQuantity(int $productId, string $size, int $newQuantity): bool|string {
        $cartKey = "{$productId}_{$size}";
        
        if ($newQuantity === 0) {
            return $this->removeItem($productId, $size);
        }
        
        if ($newQuantity < 0) {
            return 'Invalid quantity';
        }
        
        // Check if item exists in cart
        if (!isset($_SESSION['cart'][$cartKey])) {
            return 'Item not found in cart';
        }
        
        // Get product details for stock check
        $product = $this->productController->getProductById($productId);
        if (!$product) {
            return 'Product not found';
        }
        
        // Check stock
        $availableQuantity = 0;
        foreach ($product['sizes'] as $productSize) {
            if ($productSize['size'] === $size) {
                $availableQuantity = $productSize['quantity'];
                break;
            }
        }
        
        if ($newQuantity > $availableQuantity) {
            return 'Not enough stock available';
        }
        
        // Update quantity
        $_SESSION['cart'][$cartKey]['quantity'] = $newQuantity;
        return true;
    }
    
    public function removeItem(int $productId, string $size): bool {
        $cartKey = "{$productId}_{$size}";
        if (isset($_SESSION['cart'][$cartKey])) {
            unset($_SESSION['cart'][$cartKey]);
            return true;
        }
        return false;
    }
    
    public function getCartTotal(): float {
        $total = 0;
        $cart = $this->getCartContents();
        
        foreach ($cart as $item) {
            $price = $item['discount_price'] ?? $item['price'];
            $total += $price * $item['quantity'];
        }
        
        return $total;
    }
    
    public function applyCoupon(string $couponCode, float $cartTotal): array {
        try {
            // Get coupon details
            $sql = "SELECT * FROM coupons WHERE code = :code AND is_active = 1 
                    AND start_date <= CURRENT_TIMESTAMP 
                    AND (end_date IS NULL OR end_date >= CURRENT_TIMESTAMP)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':code' => $couponCode]);
            $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$coupon) {
                return [
                    'success' => false,
                    'error_message' => 'Invalid or expired coupon'
                ];
            }
            
            // Check minimum order amount
            if ($cartTotal < $coupon['min_purchase']) {
                return [
                    'success' => false,
                    'error_message' => sprintf(
                        'Minimum order amount of %s required',
                        format_currency($coupon['min_purchase'])
                    )
                ];
            }
            
            // Check usage limit
            if ($coupon['max_uses'] > 0) {
                $sql = "SELECT COUNT(*) FROM orders WHERE coupon_id = :coupon_id";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([':coupon_id' => $coupon['id']]);
                $usageCount = $stmt->fetchColumn();
                
                if ($usageCount >= $coupon['max_uses']) {
                    return [
                        'success' => false,
                        'error_message' => 'Coupon usage limit reached'
                    ];
                }
            }
            
            // Calculate discount
            $discountAmount = 0;
            if ($coupon['discount_type'] === 'percentage') {
                $discountAmount = $cartTotal * ($coupon['discount_value'] / 100);
                if ($coupon['max_discount'] > 0) {
                    $discountAmount = min($discountAmount, $coupon['max_discount']);
                }
            } else {
                $discountAmount = min($coupon['discount_value'], $cartTotal);
            }
            
            return [
                'success' => true,
                'discount_amount' => $discountAmount,
                'coupon_id' => $coupon['id']
            ];
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'error_message' => 'Error processing coupon'
            ];
        }
    }
    
    public function clearCart(): void {
        $_SESSION['cart'] = [];
    }

    public function calculateTotal(): float
    {
        $total = 0;
        $cart = $this->getCartContents();
        
        foreach ($cart as $item) {
            $total += $item['price'] * $item['quantity'];
        }
        
        return $total;
    }
}
?>