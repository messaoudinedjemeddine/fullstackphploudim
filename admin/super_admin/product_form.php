<?php
require_once __DIR__ . '/../../init.php';

use App\Auth;
use App\Controllers\ProductController;
use function App\__;

// Check if user is logged in and is a super admin
if (!Auth::check() || !Auth::checkRole('super_admin')) {
    $_SESSION['flash_message'] = [
        'type' => 'danger',
        'message' => __('unauthorized_access')
    ];
    redirect('/admin/index.php');
}

// Initialize ProductController
$productController = new ProductController();

$productId = $_GET['id'] ?? null;
$product = null;
$isEdit = false;

if ($productId) {
    $product = $productController->getProductById((int)$productId);
    if (!$product) {
        $_SESSION['flash_message'] = [
            'type' => 'danger',
            'message' => __('product_not_found')
        ];
        redirect('/admin/super_admin/products.php');
    }
    $isEdit = true;
}

// Get all categories for dropdown
$categories = $productController->getAllCategories();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'name_en' => $_POST['name_en'] ?? '',
        'name_fr' => $_POST['name_fr'] ?? '',
        'name_ar' => $_POST['name_ar'] ?? '',
        'description_en' => $_POST['description_en'] ?? '',
        'description_fr' => $_POST['description_fr'] ?? '',
        'description_ar' => $_POST['description_ar'] ?? '',
        'price' => $_POST['price'] ?? 0,
        'discount_price' => !empty($_POST['discount_price']) ? $_POST['discount_price'] : null,
        'sku' => $_POST['sku'] ?? '',
        'category_id' => $_POST['category_id'] ?? null,
        'is_active' => isset($_POST['is_active']) ? 1 : 0
    ];

    // Validate required fields
    $requiredFields = ['name_en', 'price', 'sku', 'category_id'];
    $missingFields = [];
    foreach ($requiredFields as $field) {
        if (empty(trim($formData[$field]))) {
            $missingFields[] = $field;
        }
    }

    if (!empty($missingFields)) {
        $_SESSION['flash_message'] = [
            'type' => 'danger',
            'message' => __('required_fields_missing') . ': ' . implode(', ', $missingFields)
        ];
    } else {
        try {
            $imageIdsToDelete = isset($_POST['delete_images']) ? $_POST['delete_images'] : [];
            
            if ($isEdit) {
                if ($productController->updateProduct($productId, $formData, $_FILES['images'] ?? null, $imageIdsToDelete)) {
                    $_SESSION['flash_message'] = [
                        'type' => 'success',
                        'message' => __('product_updated_successfully')
                    ];
                    redirect('/admin/super_admin/products.php');
                }
            } else {
                if ($productController->createProduct($formData, $_FILES['images'] ?? null, [])) {
                    $_SESSION['flash_message'] = [
                        'type' => 'success',
                        'message' => __('product_created_successfully')
                    ];
                    redirect('/admin/super_admin/products.php');
                }
            }
        } catch (Exception $e) {
            $_SESSION['flash_message'] = [
                'type' => 'danger',
                'message' => $e->getMessage()
            ];
        }
    }
}

$pageTitle = $isEdit ? __('edit_product') : __('add_new_product');
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?= $pageTitle ?></h1>
            </div>

            <?php if (isset($_SESSION['flash_message'])): ?>
                <div class="alert alert-<?= $_SESSION['flash_message']['type'] ?> alert-dismissible fade show" role="alert">
                    <?= $_SESSION['flash_message']['message'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['flash_message']); ?>
            <?php endif; ?>

            <div class="card shadow mb-4">
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                        <!-- Names -->
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <label for="name_en" class="form-label"><?= __('name_en') ?> *</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="name_en" 
                                       name="name_en" 
                                       value="<?= htmlspecialchars($product['name_en'] ?? '') ?>"
                                       required>
                            </div>
                            <div class="col-md-4">
                                <label for="name_fr" class="form-label"><?= __('name_fr') ?></label>
                                <input type="text" 
                                       class="form-control" 
                                       id="name_fr" 
                                       name="name_fr" 
                                       value="<?= htmlspecialchars($product['name_fr'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="name_ar" class="form-label"><?= __('name_ar') ?></label>
                                <input type="text" 
                                       class="form-control" 
                                       id="name_ar" 
                                       name="name_ar" 
                                       value="<?= htmlspecialchars($product['name_ar'] ?? '') ?>">
                            </div>
                        </div>

                        <!-- Descriptions -->
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <label for="description_en" class="form-label"><?= __('description_en') ?></label>
                                <textarea class="form-control" 
                                          id="description_en" 
                                          name="description_en" 
                                          rows="4"><?= htmlspecialchars($product['description_en'] ?? '') ?></textarea>
                            </div>
                            <div class="col-md-4">
                                <label for="description_fr" class="form-label"><?= __('description_fr') ?></label>
                                <textarea class="form-control" 
                                          id="description_fr" 
                                          name="description_fr" 
                                          rows="4"><?= htmlspecialchars($product['description_fr'] ?? '') ?></textarea>
                            </div>
                            <div class="col-md-4">
                                <label for="description_ar" class="form-label"><?= __('description_ar') ?></label>
                                <textarea class="form-control" 
                                          id="description_ar" 
                                          name="description_ar" 
                                          rows="4"><?= htmlspecialchars($product['description_ar'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <!-- Price and SKU -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <label for="price" class="form-label"><?= __('price') ?> *</label>
                                <input type="number" 
                                       class="form-control" 
                                       id="price" 
                                       name="price" 
                                       value="<?= htmlspecialchars($product['price'] ?? '') ?>"
                                       step="0.01"
                                       min="0"
                                       required>
                            </div>
                            <div class="col-md-3">
                                <label for="discount_price" class="form-label"><?= __('discount_price') ?></label>
                                <input type="number" 
                                       class="form-control" 
                                       id="discount_price" 
                                       name="discount_price" 
                                       value="<?= htmlspecialchars($product['discount_price'] ?? '') ?>"
                                       step="0.01"
                                       min="0">
                            </div>
                            <div class="col-md-3">
                                <label for="sku" class="form-label"><?= __('sku') ?> *</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="sku" 
                                       name="sku" 
                                       value="<?= htmlspecialchars($product['sku'] ?? '') ?>"
                                       required>
                            </div>
                            <div class="col-md-3">
                                <label for="category_id" class="form-label"><?= __('category') ?> *</label>
                                <select class="form-select" id="category_id" name="category_id" required>
                                    <option value=""><?= __('select_category') ?></option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= $category['id'] ?>" 
                                                <?= ($product['category_id'] ?? '') == $category['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($category['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Status -->
                        <div class="mb-4">
                            <div class="form-check">
                                <input type="checkbox" 
                                       class="form-check-input" 
                                       id="is_active" 
                                       name="is_active" 
                                       <?= ($product['is_active'] ?? 1) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="is_active"><?= __('active') ?></label>
                            </div>
                        </div>

                        <!-- Images -->
                        <div class="mb-4">
                            <label class="form-label"><?= __('product_images') ?></label>
                            
                            <?php if ($isEdit && !empty($product['images'])): ?>
                                <div class="row mb-3">
                                    <?php foreach ($product['images'] as $image): ?>
                                        <div class="col-md-2 mb-2">
                                            <div class="card">
                                                <img src="<?= htmlspecialchars($image['url']) ?>" 
                                                     class="card-img-top" 
                                                     alt="<?= __('product_image') ?>">
                                                <div class="card-body p-2">
                                                    <div class="form-check">
                                                        <input type="checkbox" 
                                                               class="form-check-input" 
                                                               name="delete_images[]" 
                                                               value="<?= $image['id'] ?>"
                                                               id="delete_image_<?= $image['id'] ?>">
                                                        <label class="form-check-label" for="delete_image_<?= $image['id'] ?>">
                                                            <?= __('delete') ?>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <input type="file" 
                                   class="form-control" 
                                   name="images[]" 
                                   accept="image/*" 
                                   multiple>
                            <small class="form-text text-muted">
                                <?= __('image_upload_help') ?>
                            </small>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="products.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i><?= __('back') ?>
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i><?= $isEdit ? __('update') : __('save') ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
// Form validation
(function() {
    'use strict';
    
    const forms = document.querySelectorAll('.needs-validation');
    
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            form.classList.add('was-validated');
        }, false);
    });
})();
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>