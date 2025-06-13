<?php
require_once __DIR__ . '/../../init.php';

use App\Auth;
use App\Controllers\ProductController;
use function App\__;
use function App\formatPrice;
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

// Handle delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_coupon_id'])) {
    try {
        if ($productController->deleteCoupon((int)$_POST['delete_coupon_id'])) {
            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => __('coupon_deleted_successfully')
            ];
        }
    } catch (Exception $e) {
        $_SESSION['flash_message'] = [
            'type' => 'danger',
            'message' => $e->getMessage()
        ];
    }
    
    redirect('/admin/super_admin/coupons.php');
}

// Get all coupons
$coupons = $productController->getAllCoupons();

$pageTitle = __('manage_coupons');
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?= $pageTitle ?></h1>
                <a href="coupon_form.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i><?= __('add_new_coupon') ?>
                </a>
            </div>

            <?php if (isset($_SESSION['flash_message'])): ?>
                <div class="alert alert-<?= $_SESSION['flash_message']['type'] ?> alert-dismissible fade show" role="alert">
                    <?= $_SESSION['flash_message']['message'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['flash_message']); ?>
            <?php endif; ?>

            <!-- Coupons Table -->
            <div class="card shadow">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th><?= __('code') ?></th>
                                    <th><?= __('discount_type') ?></th>
                                    <th><?= __('value') ?></th>
                                    <th><?= __('min_order') ?></th>
                                    <th><?= __('max_uses') ?></th>
                                    <th><?= __('uses_count') ?></th>
                                    <th><?= __('valid_from') ?></th>
                                    <th><?= __('valid_until') ?></th>
                                    <th><?= __('status') ?></th>
                                    <th><?= __('actions') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($coupons as $coupon): ?>
                                    <tr>
                                        <td><?= $coupon['id'] ?></td>
                                        <td>
                                            <span class="badge bg-light text-dark">
                                                <?= htmlspecialchars($coupon['code']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?= $coupon['discount_type'] === 'percentage' 
                                                ? __('percentage') 
                                                : __('fixed_amount') ?>
                                        </td>
                                        <td>
                                            <?php if ($coupon['discount_type'] === 'percentage'): ?>
                                                <?= $coupon['value'] ?>%
                                            <?php else: ?>
                                                <?= formatPrice($coupon['value']) ?>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= formatPrice($coupon['min_order']) ?></td>
                                        <td><?= $coupon['max_uses'] ?: __('unlimited') ?></td>
                                        <td><?= $coupon['uses_count'] ?></td>
                                        <td><?= formatDate($coupon['valid_from']) ?></td>
                                        <td><?= formatDate($coupon['valid_until']) ?></td>
                                        <td>
                                            <span class="badge <?= $coupon['is_active'] ? 'bg-success' : 'bg-secondary' ?>">
                                                <?= $coupon['is_active'] ? __('active') : __('inactive') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="coupon_form.php?id=<?= $coupon['id'] ?>" 
                                               class="btn btn-sm btn-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" 
                                                    class="btn btn-sm btn-danger" 
                                                    onclick="confirmDelete(<?= $coupon['id'] ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= __('confirm_delete') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <?= __('delete_coupon_confirmation') ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <?= __('cancel') ?>
                </button>
                <form method="POST" id="deleteForm">
                    <input type="hidden" name="delete_coupon_id" id="deleteCouponId">
                    <button type="submit" class="btn btn-danger">
                        <?= __('delete') ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(couponId) {
    document.getElementById('deleteCouponId').value = couponId;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>