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

// Check if this is an edit operation
$categoryId = $_GET['id'] ?? null;
$category = null;
$isEdit = false;

if ($categoryId) {
    $category = $productController->getCategoryById((int)$categoryId);
    if (!$category) {
        $_SESSION['flash_message'] = [
            'type' => 'danger',
            'message' => __('category_not_found')
        ];
        redirect('/admin/super_admin/categories.php');
    }
    $isEdit = true;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $formData = [
            'name_en' => $_POST['name_en'] ?? '',
            'name_fr' => $_POST['name_fr'] ?? '',
            'name_ar' => $_POST['name_ar'] ?? '',
            'slug' => $_POST['slug'] ?? '',
            'description_en' => $_POST['description_en'] ?? '',
            'description_fr' => $_POST['description_fr'] ?? '',
            'description_ar' => $_POST['description_ar'] ?? ''
        ];

        // Validate required fields
        if (empty($formData['name_en']) || empty($formData['slug'])) {
            throw new Exception(__('required_fields_missing'));
        }

        if ($isEdit) {
            if ($productController->updateCategory((int)$categoryId, $formData)) {
                $_SESSION['flash_message'] = [
                    'type' => 'success',
                    'message' => __('category_updated_successfully')
                ];
            }
        } else {
            if ($productController->createCategory($formData)) {
                $_SESSION['flash_message'] = [
                    'type' => 'success',
                    'message' => __('category_created_successfully')
                ];
            }
        }
        
        redirect('/admin/super_admin/categories.php');
    } catch (Exception $e) {
        $_SESSION['flash_message'] = [
            'type' => 'danger',
            'message' => $e->getMessage()
        ];
    }
}

$pageTitle = $isEdit ? __('edit_category') : __('add_new_category');
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?= $pageTitle ?></h1>
                <a href="categories.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i><?= __('back_to_categories') ?>
                </a>
            </div>

            <?php if (isset($_SESSION['flash_message'])): ?>
                <div class="alert alert-<?= $_SESSION['flash_message']['type'] ?> alert-dismissible fade show" role="alert">
                    <?= $_SESSION['flash_message']['message'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['flash_message']); ?>
            <?php endif; ?>

            <div class="card shadow">
                <div class="card-body">
                    <form method="POST" class="needs-validation" novalidate>
                        <!-- Names Section -->
                        <div class="mb-4">
                            <h5 class="mb-3"><?= __('category_names') ?></h5>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="name_en" class="form-label"><?= __('name_en') ?> *</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="name_en" 
                                           name="name_en" 
                                           value="<?= htmlspecialchars($category['name_en'] ?? '') ?>" 
                                           required>
                                </div>
                                <div class="col-md-4">
                                    <label for="name_fr" class="form-label"><?= __('name_fr') ?></label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="name_fr" 
                                           name="name_fr" 
                                           value="<?= htmlspecialchars($category['name_fr'] ?? '') ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="name_ar" class="form-label"><?= __('name_ar') ?></label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="name_ar" 
                                           name="name_ar" 
                                           value="<?= htmlspecialchars($category['name_ar'] ?? '') ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Slug -->
                        <div class="mb-4">
                            <label for="slug" class="form-label"><?= __('slug') ?> *</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="slug" 
                                   name="slug" 
                                   value="<?= htmlspecialchars($category['slug'] ?? '') ?>" 
                                   required>
                            <div class="form-text"><?= __('slug_help_text') ?></div>
                        </div>

                        <!-- Descriptions Section -->
                        <div class="mb-4">
                            <h5 class="mb-3"><?= __('category_descriptions') ?></h5>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="description_en" class="form-label"><?= __('description_en') ?></label>
                                    <textarea class="form-control" 
                                              id="description_en" 
                                              name="description_en" 
                                              rows="4"><?= htmlspecialchars($category['description_en'] ?? '') ?></textarea>
                                </div>
                                <div class="col-md-4">
                                    <label for="description_fr" class="form-label"><?= __('description_fr') ?></label>
                                    <textarea class="form-control" 
                                              id="description_fr" 
                                              name="description_fr" 
                                              rows="4"><?= htmlspecialchars($category['description_fr'] ?? '') ?></textarea>
                                </div>
                                <div class="col-md-4">
                                    <label for="description_ar" class="form-label"><?= __('description_ar') ?></label>
                                    <textarea class="form-control" 
                                              id="description_ar" 
                                              name="description_ar" 
                                              rows="4"><?= htmlspecialchars($category['description_ar'] ?? '') ?></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
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
// Auto-generate slug from English name
document.getElementById('name_en').addEventListener('input', function() {
    const slugInput = document.getElementById('slug');
    if (!slugInput.value) { // Only auto-generate if slug is empty
        slugInput.value = this.value
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
    }
});

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