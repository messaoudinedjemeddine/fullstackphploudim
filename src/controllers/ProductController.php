<?php

namespace App\Controllers;

use App\Database;
use PDO;
use PDOException;

class ProductController {
    private static $instance = null;
    private $pdo;
    
    private function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
    }
    
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    // Product Methods
    public function getAllProducts(array $filters = []): array {
        $sql = "SELECT p.*, c.name_en as category_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($filters['category_id'])) {
            $sql .= " AND p.category_id = :category_id";
            $params[':category_id'] = $filters['category_id'];
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (p.name_en LIKE :search OR p.name_fr LIKE :search OR p.name_ar LIKE :search)";
            $params[':search'] = "%{$filters['search']}%";
        }
        
        $sql .= " ORDER BY p.id DESC";
        
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getProductById(int $productId): ?array {
        $sql = "SELECT p.*, 
                GROUP_CONCAT(DISTINCT pi.id, ':', pi.image_path, ':', pi.is_primary) as images,
                GROUP_CONCAT(DISTINCT ps.id, ':', ps.size, ':', ps.quantity) as sizes
                FROM products p
                LEFT JOIN product_images pi ON p.id = pi.product_id
                LEFT JOIN product_sizes ps ON p.id = ps.product_id
                WHERE p.id = :id
                GROUP BY p.id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $productId]);
        
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$product) return null;
        
        // Process images
        $product['images'] = array_map(function($img) {
            list($id, $path, $isPrimary) = explode(':', $img);
            return [
                'id' => $id,
                'path' => $path,
                'is_primary' => (bool)$isPrimary
            ];
        }, explode(',', $product['images']));
        
        // Process sizes
        $product['sizes'] = array_map(function($size) {
            list($id, $size, $quantity) = explode(':', $size);
            return [
                'id' => $id,
                'size' => $size,
                'quantity' => (int)$quantity
            ];
        }, explode(',', $product['sizes']));
        
        return $product;
    }
    
    public function createProduct(array $data, array $imageFiles, array $sizesData): int {
        try {
            $this->pdo->beginTransaction();
            
            // Insert product
            $sql = "INSERT INTO products (category_id, name_en, name_fr, name_ar, description_en, 
                    description_fr, description_ar, price, discount_price, is_active) 
                    VALUES (:category_id, :name_en, :name_fr, :name_ar, :description_en, 
                    :description_fr, :description_ar, :price, :discount_price, :is_active)";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':category_id' => $data['category_id'],
                ':name_en' => $data['name_en'],
                ':name_fr' => $data['name_fr'],
                ':name_ar' => $data['name_ar'],
                ':description_en' => $data['description_en'],
                ':description_fr' => $data['description_fr'],
                ':description_ar' => $data['description_ar'],
                ':price' => $data['price'],
                ':discount_price' => $data['discount_price'] ?? null,
                ':is_active' => $data['is_active'] ?? 1
            ]);
            
            $productId = $this->pdo->lastInsertId();
            
            // Handle image uploads
            foreach ($imageFiles as $index => $file) {
                $filename = uniqid() . '_' . basename($file['name']);
                $path = UPLOAD_DIR . '/products/' . $filename;
                
                if (move_uploaded_file($file['tmp_name'], $path)) {
                    $sql = "INSERT INTO product_images (product_id, image_path, is_primary) 
                            VALUES (:product_id, :image_path, :is_primary)";
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute([
                        ':product_id' => $productId,
                        ':image_path' => 'products/' . $filename,
                        ':is_primary' => $index === 0 ? 1 : 0
                    ]);
                }
            }
            
            // Handle sizes
            foreach ($sizesData as $size) {
                $sql = "INSERT INTO product_sizes (product_id, size, quantity) 
                        VALUES (:product_id, :size, :quantity)";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([
                    ':product_id' => $productId,
                    ':size' => $size['size'],
                    ':quantity' => $size['quantity']
                ]);
            }
            
            $this->pdo->commit();
            return $productId;
            
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
    
    public function updateProduct(int $productId, array $data, array $imageFiles = [], 
                                array $existingImageIdsToDelete = [], array $sizesData = []): bool {
        try {
            $this->pdo->beginTransaction();
            
            // Update product
            $sql = "UPDATE products SET 
                    category_id = :category_id,
                    name_en = :name_en,
                    name_fr = :name_fr,
                    name_ar = :name_ar,
                    description_en = :description_en,
                    description_fr = :description_fr,
                    description_ar = :description_ar,
                    price = :price,
                    discount_price = :discount_price,
                    is_active = :is_active
                    WHERE id = :id";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':id' => $productId,
                ':category_id' => $data['category_id'],
                ':name_en' => $data['name_en'],
                ':name_fr' => $data['name_fr'],
                ':name_ar' => $data['name_ar'],
                ':description_en' => $data['description_en'],
                ':description_fr' => $data['description_fr'],
                ':description_ar' => $data['description_ar'],
                ':price' => $data['price'],
                ':discount_price' => $data['discount_price'] ?? null,
                ':is_active' => $data['is_active'] ?? 1
            ]);
            
            // Handle new image uploads
            foreach ($imageFiles as $file) {
                $filename = uniqid() . '_' . basename($file['name']);
                $path = UPLOAD_DIR . '/products/' . $filename;
                
                if (move_uploaded_file($file['tmp_name'], $path)) {
                    $sql = "INSERT INTO product_images (product_id, image_path, is_primary) 
                            VALUES (:product_id, :image_path, 0)";
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute([
                        ':product_id' => $productId,
                        ':image_path' => 'products/' . $filename
                    ]);
                }
            }
            
            // Delete specified images
            if (!empty($existingImageIdsToDelete)) {
                // Get image paths before deletion
                $sql = "SELECT image_path FROM product_images WHERE id IN (" . 
                       implode(',', array_fill(0, count($existingImageIdsToDelete), '?')) . ")";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($existingImageIdsToDelete);
                $images = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                // Delete from database
                $sql = "DELETE FROM product_images WHERE id IN (" . 
                       implode(',', array_fill(0, count($existingImageIdsToDelete), '?')) . ")";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($existingImageIdsToDelete);
                
                // Delete files
                foreach ($images as $image) {
                    $path = UPLOAD_DIR . '/' . $image;
                    if (file_exists($path)) {
                        unlink($path);
                    }
                }
            }
            
            // Update sizes
            if (!empty($sizesData)) {
                // Delete existing sizes
                $sql = "DELETE FROM product_sizes WHERE product_id = :product_id";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([':product_id' => $productId]);
                
                // Insert new sizes
                foreach ($sizesData as $size) {
                    $sql = "INSERT INTO product_sizes (product_id, size, quantity) 
                            VALUES (:product_id, :size, :quantity)";
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute([
                        ':product_id' => $productId,
                        ':size' => $size['size'],
                        ':quantity' => $size['quantity']
                    ]);
                }
            }
            
            $this->pdo->commit();
            return true;
            
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
    
    public function deleteProduct(int $productId): bool {
        try {
            $this->pdo->beginTransaction();
            
            // Get image paths
            $sql = "SELECT image_path FROM product_images WHERE product_id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $productId]);
            $images = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Delete images from database
            $sql = "DELETE FROM product_images WHERE product_id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $productId]);
            
            // Delete sizes
            $sql = "DELETE FROM product_sizes WHERE product_id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $productId]);
            
            // Delete product
            $sql = "DELETE FROM products WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $productId]);
            
            // Delete image files
            foreach ($images as $image) {
                $path = UPLOAD_DIR . '/' . $image;
                if (file_exists($path)) {
                    unlink($path);
                }
            }
            
            $this->pdo->commit();
            return true;
            
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
    
    public function setPrimaryImage(int $productId, int $imageId): bool {
        try {
            $this->pdo->beginTransaction();
            
            // Reset all images to non-primary
            $sql = "UPDATE product_images SET is_primary = 0 WHERE product_id = :product_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':product_id' => $productId]);
            
            // Set the new primary image
            $sql = "UPDATE product_images SET is_primary = 1 
                    WHERE id = :image_id AND product_id = :product_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':image_id' => $imageId,
                ':product_id' => $productId
            ]);
            
            $this->pdo->commit();
            return true;
            
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
    
    // Category Methods
    public function getAllCategories(): array {
        $sql = "SELECT * FROM categories ORDER BY name_en";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getCategoryById(int $categoryId): ?array {
        $sql = "SELECT * FROM categories WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $categoryId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    
    public function createCategory(array $data): int {
        $sql = "INSERT INTO categories (name_en, name_fr, name_ar) 
                VALUES (:name_en, :name_fr, :name_ar)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':name_en' => $data['name_en'],
            ':name_fr' => $data['name_fr'],
            ':name_ar' => $data['name_ar']
        ]);
        return $this->pdo->lastInsertId();
    }
    
    public function updateCategory(int $categoryId, array $data): bool {
        $sql = "UPDATE categories SET 
                name_en = :name_en,
                name_fr = :name_fr,
                name_ar = :name_ar
                WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':id' => $categoryId,
            ':name_en' => $data['name_en'],
            ':name_fr' => $data['name_fr'],
            ':name_ar' => $data['name_ar']
        ]);
    }
    
    public function deleteCategory(int $categoryId): bool {
        $sql = "DELETE FROM categories WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':id' => $categoryId]);
    }
    
    // Coupon Methods
    public function getAllCoupons(): array {
        $sql = "SELECT * FROM coupons ORDER BY created_at DESC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getCouponById(int $couponId): ?array {
        $sql = "SELECT * FROM coupons WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $couponId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    
    public function createCoupon(array $data): int {
        $sql = "INSERT INTO coupons (code, discount_percent, min_purchase, max_discount, 
                start_date, end_date, is_active) 
                VALUES (:code, :discount_percent, :min_purchase, :max_discount, 
                :start_date, :end_date, :is_active)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':code' => $data['code'],
            ':discount_percent' => $data['discount_percent'],
            ':min_purchase' => $data['min_purchase'],
            ':max_discount' => $data['max_discount'],
            ':start_date' => $data['start_date'],
            ':end_date' => $data['end_date'],
            ':is_active' => $data['is_active'] ?? 1
        ]);
        return $this->pdo->lastInsertId();
    }
    
    public function updateCoupon(int $couponId, array $data): bool {
        $sql = "UPDATE coupons SET 
                code = :code,
                discount_percent = :discount_percent,
                min_purchase = :min_purchase,
                max_discount = :max_discount,
                start_date = :start_date,
                end_date = :end_date,
                is_active = :is_active
                WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':id' => $couponId,
            ':code' => $data['code'],
            ':discount_percent' => $data['discount_percent'],
            ':min_purchase' => $data['min_purchase'],
            ':max_discount' => $data['max_discount'],
            ':start_date' => $data['start_date'],
            ':end_date' => $data['end_date'],
            ':is_active' => $data['is_active'] ?? 1
        ]);
    }
    
    public function deleteCoupon(int $couponId): bool {
        $sql = "DELETE FROM coupons WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':id' => $couponId]);
    }
    
    // Delivery Methods
    public function getAllDeliveryCities(): array {
        $sql = "SELECT * FROM delivery_cities ORDER BY wilaya_name_en";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function updateDeliveryCityFees(int $wilayaCode, float $homeFee, float $deskFee): bool {
        $sql = "UPDATE delivery_cities SET 
                home_fee = :home_fee,
                desk_fee = :desk_fee
                WHERE wilaya_code = :wilaya_code";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':wilaya_code' => $wilayaCode,
            ':home_fee' => $homeFee,
            ':desk_fee' => $deskFee
        ]);
    }
    
    public function getDeliveryDesksByWilaya(int $wilayaId): array {
        $sql = "SELECT * FROM delivery_desks WHERE wilaya_id = :wilaya_id AND is_active = 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':wilaya_id' => $wilayaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getDeliveryDeskById(int $deskId): ?array {
        $sql = "SELECT * FROM delivery_desks WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $deskId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    
    public function createDeliveryDesk(array $data): int {
        $sql = "INSERT INTO delivery_desks (wilaya_id, name_en, name_fr, name_ar, address_en, 
                address_fr, address_ar, phone, is_active) 
                VALUES (:wilaya_id, :name_en, :name_fr, :name_ar, :address_en, 
                :address_fr, :address_ar, :phone, :is_active)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':wilaya_id' => $data['wilaya_id'],
            ':name_en' => $data['name_en'],
            ':name_fr' => $data['name_fr'],
            ':name_ar' => $data['name_ar'],
            ':address_en' => $data['address_en'],
            ':address_fr' => $data['address_fr'],
            ':address_ar' => $data['address_ar'],
            ':phone' => $data['phone'],
            ':is_active' => $data['is_active'] ?? 1
        ]);
        return $this->pdo->lastInsertId();
    }
    
    public function updateDeliveryDesk(int $deskId, array $data): bool {
        $sql = "UPDATE delivery_desks SET 
                wilaya_id = :wilaya_id,
                name_en = :name_en,
                name_fr = :name_fr,
                name_ar = :name_ar,
                address_en = :address_en,
                address_fr = :address_fr,
                address_ar = :address_ar,
                phone = :phone,
                is_active = :is_active
                WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':id' => $deskId,
            ':wilaya_id' => $data['wilaya_id'],
            ':name_en' => $data['name_en'],
            ':name_fr' => $data['name_fr'],
            ':name_ar' => $data['name_ar'],
            ':address_en' => $data['address_en'],
            ':address_fr' => $data['address_fr'],
            ':address_ar' => $data['address_ar'],
            ':phone' => $data['phone'],
            ':is_active' => $data['is_active'] ?? 1
        ]);
    }
    
    public function deleteDeliveryDesk(int $deskId): bool {
        $sql = "DELETE FROM delivery_desks WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':id' => $deskId]);
    }
    
    public function getDeliveryCityByCode(int $wilayaCode): ?array {
        $sql = "SELECT * FROM delivery_cities WHERE wilaya_code = :wilaya_code";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':wilaya_code' => $wilayaCode]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function addProductSize(int $productId, string $size, int $quantity): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO product_sizes (product_id, size, quantity) 
                VALUES (?, ?, ?)
            ");
            return $stmt->execute([$productId, $size, $quantity]);
        } catch (PDOException $e) {
            error_log("Error adding product size: " . $e->getMessage());
            return false;
        }
    }

    public function updateProductSize(int $sizeId, int $quantity): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE product_sizes 
                SET quantity = ? 
                WHERE id = ?
            ");
            return $stmt->execute([$quantity, $sizeId]);
        } catch (PDOException $e) {
            error_log("Error updating product size: " . $e->getMessage());
            return false;
        }
    }

    public function deleteProductSize(int $sizeId): bool
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM product_sizes WHERE id = ?");
            return $stmt->execute([$sizeId]);
        } catch (PDOException $e) {
            error_log("Error deleting product size: " . $e->getMessage());
            return false;
        }
    }

    public function getDeliveryCityById(int $wilayaId): ?array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM delivery_cities 
                WHERE wilaya_code = ?
            ");
            $stmt->execute([$wilayaId]);
            return $stmt->fetch() ?: null;
        } catch (PDOException $e) {
            error_log("Error fetching delivery city: " . $e->getMessage());
            return null;
        }
    }
}
?>