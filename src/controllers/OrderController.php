<?php

namespace App\Controllers;

use App\Database;
use PDO;
use PDOException;

class OrderController {
    private static $instance = null;
    private $pdo;
    private $cartController;
    private $productController;
    
    private function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
        $this->cartController = CartController::getInstance();
        $this->productController = ProductController::getInstance();
    }
    
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function createOrder(array $formData, array $cartItems): array {
        try {
            // Validate form data
            $validationResult = $this->validateOrderData($formData);
            if (!$validationResult['success']) {
                return $validationResult;
            }
            
            // Final stock check
            foreach ($cartItems as $item) {
                $product = $this->productController->getProductById($item['product_id']);
                if (!$product) {
                    return [
                        'success' => false,
                        'error_message' => 'Product not found'
                    ];
                }
                
                $sizeFound = false;
                foreach ($product['sizes'] as $size) {
                    if ($size['size'] === $item['size']) {
                        $sizeFound = true;
                        if ($size['quantity'] < $item['quantity']) {
                            return [
                                'success' => false,
                                'error_message' => sprintf(
                                    'Insufficient stock for %s (Size: %s)',
                                    $product['name_en'],
                                    $item['size']
                                )
                            ];
                        }
                        break;
                    }
                }
                
                if (!$sizeFound) {
                    return [
                        'success' => false,
                        'error_message' => 'Invalid product size'
                    ];
                }
            }
            
            // Calculate delivery fee
            $deliveryFee = $this->calculateDeliveryFee(
                $formData['wilaya_code'],
                $formData['delivery_type']
            );
            
            // Calculate cart total
            $cartTotal = $this->cartController->getCartTotal();
            
            // Apply coupon if provided
            $discountAmount = 0;
            $couponId = null;
            if (!empty($formData['coupon_code'])) {
                $couponResult = $this->cartController->applyCoupon(
                    $formData['coupon_code'],
                    $cartTotal
                );
                
                if ($couponResult['success']) {
                    $discountAmount = $couponResult['discount_amount'];
                    $couponId = $couponResult['coupon_id'];
                }
            }
            
            // Calculate total amount
            $totalAmount = $cartTotal + $deliveryFee - $discountAmount;
            
            // Start transaction
            $this->pdo->beginTransaction();
            
            // Insert order
            $sql = "INSERT INTO orders (
                        full_name, phone, email, wilaya_code, delivery_type,
                        address, pickup_desk_id, coupon_id, cart_total,
                        delivery_fee, discount_amount, total_amount,
                        payment_status, order_status
                    ) VALUES (
                        :full_name, :phone, :email, :wilaya_code, :delivery_type,
                        :address, :pickup_desk_id, :coupon_id, :cart_total,
                        :delivery_fee, :discount_amount, :total_amount,
                        'pending', 'pending'
                    )";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':full_name' => $formData['full_name'],
                ':phone' => $formData['phone'],
                ':email' => $formData['email'] ?? null,
                ':wilaya_code' => $formData['wilaya_code'],
                ':delivery_type' => $formData['delivery_type'],
                ':address' => $formData['delivery_type'] === 'home' ? $formData['address'] : null,
                ':pickup_desk_id' => $formData['delivery_type'] === 'desk' ? $formData['pickup_desk_id'] : null,
                ':coupon_id' => $couponId,
                ':cart_total' => $cartTotal,
                ':delivery_fee' => $deliveryFee,
                ':discount_amount' => $discountAmount,
                ':total_amount' => $totalAmount
            ]);
            
            $orderId = $this->pdo->lastInsertId();
            
            // Insert order items and update stock
            foreach ($cartItems as $item) {
                // Insert order item
                $sql = "INSERT INTO order_items (
                            order_id, product_id, size, quantity, price
                        ) VALUES (
                            :order_id, :product_id, :size, :quantity, :price
                        )";
                
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([
                    ':order_id' => $orderId,
                    ':product_id' => $item['product_id'],
                    ':size' => $item['size'],
                    ':quantity' => $item['quantity'],
                    ':price' => $item['discount_price'] ?? $item['price']
                ]);
                
                // Update stock
                $sql = "UPDATE product_sizes 
                        SET quantity = quantity - :quantity 
                        WHERE product_id = :product_id AND size = :size";
                
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([
                    ':quantity' => $item['quantity'],
                    ':product_id' => $item['product_id'],
                    ':size' => $item['size']
                ]);
            }
            
            // Update coupon usage if applicable
            if ($couponId) {
                $sql = "UPDATE coupons 
                        SET uses_count = uses_count + 1 
                        WHERE id = :id";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([':id' => $couponId]);
            }
            
            $this->pdo->commit();
            $this->cartController->clearCart();
            
            return [
                'success' => true,
                'order_id' => $orderId,
                'total_amount' => $totalAmount
            ];
            
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            return [
                'success' => false,
                'error_message' => 'Error creating order'
            ];
        }
    }
    
    public function getOrderById(int $orderId): ?array {
        $sql = "SELECT o.*, 
                GROUP_CONCAT(
                    oi.id, ':', oi.product_id, ':', oi.size, ':', 
                    oi.quantity, ':', oi.price
                ) as items,
                c.code as coupon_code,
                c.discount_type,
                c.discount_value
                FROM orders o
                LEFT JOIN order_items oi ON o.id = oi.order_id
                LEFT JOIN coupons c ON o.coupon_id = c.id
                WHERE o.id = :id
                GROUP BY o.id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            return null;
        }
        
        // Process order items
        $order['items'] = array_map(function($item) {
            list($id, $productId, $size, $quantity, $price) = explode(':', $item);
            $product = $this->productController->getProductById($productId);
            return [
                'id' => $id,
                'product_id' => $productId,
                'size' => $size,
                'quantity' => $quantity,
                'price' => $price,
                'product_name' => $product ? $product['name_en'] : null,
                'product_image' => $product ? ($product['images'][0]['path'] ?? null) : null
            ];
        }, explode(',', $order['items']));
        
        return $order;
    }
    
    public function getOrders(array $filters = [], string $userRole = null, int $userId = null): array {
        $sql = "SELECT o.*, 
                COUNT(oi.id) as item_count,
                GROUP_CONCAT(DISTINCT oi.product_id) as product_ids
                FROM orders o
                LEFT JOIN order_items oi ON o.id = oi.order_id
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($filters['order_status'])) {
            $sql .= " AND o.order_status = :order_status";
            $params[':order_status'] = $filters['order_status'];
        }
        
        if (!empty($filters['payment_status'])) {
            $sql .= " AND o.payment_status = :payment_status";
            $params[':payment_status'] = $filters['payment_status'];
        }
        
        if (!empty($filters['wilaya_code'])) {
            $sql .= " AND o.wilaya_code = :wilaya_code";
            $params[':wilaya_code'] = $filters['wilaya_code'];
        }
        
        if ($userRole === 'customer' && $userId) {
            $sql .= " AND o.user_id = :user_id";
            $params[':user_id'] = $userId;
        }
        
        $sql .= " GROUP BY o.id ORDER BY o.created_at DESC";
        
        if (!empty($filters['limit'])) {
            $sql .= " LIMIT :limit";
            $params[':limit'] = $filters['limit'];
            
            if (!empty($filters['offset'])) {
                $sql .= " OFFSET :offset";
                $params[':offset'] = $filters['offset'];
            }
        }
        
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function updateOrderStatus(int $orderId, string $newStatus, 
                                    string $observationNotes = null, 
                                    string $deliveryNote = null): bool {
        $sql = "UPDATE orders SET 
                order_status = :status";
        
        $params = [
            ':id' => $orderId,
            ':status' => $newStatus
        ];
        
        if ($observationNotes !== null) {
            $sql .= ", observation_notes = :observation_notes";
            $params[':observation_notes'] = $observationNotes;
        }
        
        if ($deliveryNote !== null) {
            $sql .= ", delivery_note = :delivery_note";
            $params[':delivery_note'] = $deliveryNote;
        }
        
        $sql .= " WHERE id = :id";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }
    
    public function updateOrderPaymentStatus(int $orderId, string $newPaymentStatus): bool {
        $sql = "UPDATE orders SET payment_status = :status WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':id' => $orderId,
            ':status' => $newPaymentStatus
        ]);
    }
    
    public function getStatistics(string $role = 'super_admin', int $agentId = null): array {
        $stats = [];
        
        // Total orders by status
        $sql = "SELECT order_status, COUNT(*) as count 
                FROM orders 
                WHERE 1=1";
        
        if ($role === 'agent' && $agentId) {
            $sql .= " AND agent_id = :agent_id";
        }
        
        $sql .= " GROUP BY order_status";
        
        $stmt = $this->pdo->prepare($sql);
        if ($role === 'agent' && $agentId) {
            $stmt->bindValue(':agent_id', $agentId);
        }
        $stmt->execute();
        $stats['orders_by_status'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Revenue over time (last 30 days)
        $sql = "SELECT DATE(created_at) as date, 
                COUNT(*) as order_count,
                SUM(total_amount) as revenue
                FROM orders 
                WHERE created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)";
        
        if ($role === 'agent' && $agentId) {
            $sql .= " AND agent_id = :agent_id";
        }
        
        $sql .= " GROUP BY DATE(created_at) ORDER BY date";
        
        $stmt = $this->pdo->prepare($sql);
        if ($role === 'agent' && $agentId) {
            $stmt->bindValue(':agent_id', $agentId);
        }
        $stmt->execute();
        $stats['revenue_trend'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Top selling products
        $sql = "SELECT p.id, p.name_en, 
                COUNT(oi.id) as order_count,
                SUM(oi.quantity) as total_quantity
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                JOIN orders o ON oi.order_id = o.id
                WHERE o.created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)";
        
        if ($role === 'agent' && $agentId) {
            $sql .= " AND o.agent_id = :agent_id";
        }
        
        $sql .= " GROUP BY p.id, p.name_en
                  ORDER BY total_quantity DESC
                  LIMIT 10";
        
        $stmt = $this->pdo->prepare($sql);
        if ($role === 'agent' && $agentId) {
            $stmt->bindValue(':agent_id', $agentId);
        }
        $stmt->execute();
        $stats['top_products'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $stats;
    }
    
    private function validateOrderData(array $data): array {
        $requiredFields = ['full_name', 'phone', 'wilaya_code', 'delivery_type'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                return [
                    'success' => false,
                    'error_message' => "Missing required field: $field"
                ];
            }
        }
        
        if ($data['delivery_type'] === 'home' && empty($data['address'])) {
            return [
                'success' => false,
                'error_message' => 'Address is required for home delivery'
            ];
        }
        
        if ($data['delivery_type'] === 'desk' && empty($data['pickup_desk_id'])) {
            return [
                'success' => false,
                'error_message' => 'Pickup desk is required for desk delivery'
            ];
        }
        
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'error_message' => 'Invalid email format'
            ];
        }
        
        return ['success' => true];
    }
    
    private function calculateDeliveryFee(int $wilayaCode, string $deliveryType): float {
        $sql = "SELECT home_fee, desk_fee 
                FROM delivery_cities 
                WHERE wilaya_code = :wilaya_code";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':wilaya_code' => $wilayaCode]);
        $fees = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$fees) {
            return 0;
        }
        
        return $deliveryType === 'home' ? $fees['home_fee'] : $fees['desk_fee'];
    }
    
    public function getDeliveryWilayas(): array {
        try {
            $stmt = $this->pdo->query("
                SELECT DISTINCT dc.id, dc.wilaya_code, dc.name 
                FROM delivery_cities dc
                ORDER BY dc.wilaya_code
            ");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error fetching delivery wilayas: " . $e->getMessage());
            return [];
        }
    }
}
?>