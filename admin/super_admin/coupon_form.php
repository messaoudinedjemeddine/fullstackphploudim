<?php
require_once __DIR__ . '/../../init.php';

if (!defined('CURRENCY_SYMBOL')) {
    define('CURRENCY_SYMBOL', 'DZD');
}

use App\Auth;
use App\Controllers\ProductController;
use function App\__;
use function App\formatDate;

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
$couponId = $_GET['id'] ?? null;
$coupon = null;
$isEdit = false;

if ($couponId) {
    $coupon = $productController->getCouponById((int)$couponId);
    if (!$coupon) {
        $_SESSION['flash_message'] = [
            'type' => 'danger',
            'message' => __('coupon_not_found')
        ];
        redirect('/admin/super_admin/coupons.php');
    }
    $isEdit = true;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $formData = [
            'code' => $_POST['code'] ?? '',
            'discount_type' => $_POST['discount_type'] ?? '',
            'value' => (float)($_POST['discount_value'] ?? 0),
            'min_order' => !empty($_POST['min_order_amount']) ? (float)$_POST['min_order_amount'] : null,
            'max_uses' => !empty($_POST['max_uses']) ? (int)$_POST['max_uses'] : null,
            'valid_from' => $_POST['valid_from'] ?? null,
            'valid_until' => $_POST['valid_until'] ?? null,
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];

        // Validate required fields
        if (empty($formData['code']) || empty($formData['discount_type']) || $formData['value'] <= 0) {
            throw new Exception(__('required_fields_missing'));
        }

        // Validate dates
        if ($formData['valid_from'] && $formData['valid_until'] && strtotime($formData['valid_from']) > strtotime($formData['valid_until'])) {
            throw new Exception(__('invalid_date_range'));
        }

        if ($isEdit) {
            if ($productController->updateCoupon((int)$couponId, $formData)) {
                $_SESSION['flash_message'] = [
                    'type' => 'success',
                    'message' => __('coupon_updated_successfully')
                ];
            }
        } else {
            if ($productController->createCoupon($formData)) {
                $_SESSION['flash_message'] = [
                    'type' => 'success',
                    'message' => __('coupon_created_successfully')
                ];
            }
        }
        
        redirect('/admin/super_admin/coupons.php');
    } catch (Exception $e) {
        $_SESSION['flash_message'] = [
            'type' => 'danger',
            'message' => $e->getMessage()
        ];
    }
}

$pageTitle = $isEdit ? __('edit_coupon') : __('add_new_coupon');
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?= $pageTitle ?></h1>
                <a href="coupons.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i><?= __('back_to_coupons') ?>
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
                        <div class="row g-3">
                            <!-- Code -->
                            <div class="col-md-6">
                                <label for="code" class="form-label"><?= __('code') ?> *</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="code" 
                                       name="code" 
                                       value="<?= htmlspecialchars($coupon['code'] ?? '') ?>" 
                                       required>
                            </div>

                            <!-- Discount Type -->
                            <div class="col-md-6">
                                <label for="discount_type" class="form-label"><?= __('discount_type') ?> *</label>
                                <select class="form-select" 
                                        id="discount_type" 
                                        name="discount_type" 
                                        required>
                                    <option value=""><?= __('select_discount_type') ?></option>
                                    <option value="percentage" <?= ($coupon['discount_type'] ?? '') === 'percentage' ? 'selected' : '' ?>>
                                        <?= __('percentage') ?>
                                    </option>
                                    <option value="fixed" <?= ($coupon['discount_type'] ?? '') === 'fixed' ? 'selected' : '' ?>>
                                        <?= __('fixed_amount') ?>
                                    </option>
                                </select>
                            </div>

                            <!-- Discount Value -->
                            <div class="col-md-6">
                                <label for="discount_value" class="form-label"><?= __('discount_value') ?> *</label>
                                <div class="input-group">
                                    <input type="number" 
                                           class="form-control" 
                                           id="discount_value" 
                                           name="discount_value" 
                                           value="<?= htmlspecialchars($coupon['value'] ?? '') ?>" 
                                           min="0" 
                                           step="0.01" 
                                           required>
                                    <span class="input-group-text" id="discount_type_suffix">%</span>
                                </div>
                            </div>

                            <!-- Min Order Amount -->
                            <div class="col-md-6">
                                <label for="min_order_amount" class="form-label"><?= __('min_order_amount') ?></label>
                                <input type="number" 
                                       class="form-control" 
                                       id="min_order_amount" 
                                       name="min_order_amount" 
                                       value="<?= htmlspecialchars($coupon['min_order'] ?? '') ?>" 
                                       min="0" 
                                       step="0.01">
                            </div>

                            <!-- Max Uses -->
                            <div class="col-md-6">
                                <label for="max_uses" class="form-label"><?= __('max_uses') ?></label>
                                <input type="number" 
                                       class="form-control" 
                                       id="max_uses" 
                                       name="max_uses" 
                                       value="<?= htmlspecialchars($coupon['max_uses'] ?? '') ?>" 
                                       min="0">
                                <div class="form-text"><?= __('leave_empty_for_unlimited') ?></div>
                            </div>

                            <!-- Valid From -->
                            <div class="col-md-6">
                                <label for="valid_from" class="form-label"><?= __('valid_from') ?></label>
                                <input type="datetime-local" 
                                       class="form-control" 
                                       id="valid_from" 
                                       name="valid_from" 
                                       value="<?= $coupon['valid_from'] ? date('Y-m-d\TH:i', strtotime($coupon['valid_from'])) : '' ?>">
                            </div>

                            <!-- Valid Until -->
                            <div class="col-md-6">
                                <label for="valid_until" class="form-label"><?= __('valid_until') ?></label>
                                <input type="datetime-local" 
                                       class="form-control" 
                                       id="valid_until" 
                                       name="valid_until" 
                                       value="<?= $coupon['valid_until'] ? date('Y-m-d\TH:i', strtotime($coupon['valid_until'])) : '' ?>">
                            </div>

                            <!-- Active Status -->
                            <div class="col-12">
                                <div class="form-check">
                                    <input type="checkbox" 
                                           class="form-check-input" 
                                           id="is_active" 
                                           name="is_active" 
                                           <?= ($coupon['is_active'] ?? 1) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="is_active">
                                        <?= __('active') ?>
                                    </label>
                                </div>
                            </div>

                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i><?= $isEdit ? __('update') : __('save') ?>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
// Update discount value suffix based on discount type
document.getElementById('discount_type').addEventListener('change', function() {
    const suffix = this.value === 'percentage' ? '%' : '<?= CURRENCY_SYMBOL ?>';
    document.getElementById('discount_type_suffix').textContent = suffix;
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