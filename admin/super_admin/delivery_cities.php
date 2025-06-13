<?php
require_once __DIR__ . '/../../init.php';

use App\Auth;
use App\Controllers\ProductController;
use function App\__;
use function App\formatPrice;

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

// Handle fee update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['wilaya_code'])) {
    try {
        $wilayaCode = $_POST['wilaya_code'];
        $homeFee = (float)$_POST['home_fee'];
        $deskFee = (float)$_POST['desk_fee'];

        if ($productController->updateDeliveryCityFees($wilayaCode, $homeFee, $deskFee)) {
            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => __('fees_updated_successfully')
            ];
        }
    } catch (Exception $e) {
        $_SESSION['flash_message'] = [
            'type' => 'danger',
            'message' => $e->getMessage()
        ];
    }
    
    redirect('/admin/super_admin/delivery_cities.php');
}

// Get all delivery cities
$cities = $productController->getAllDeliveryCities();

$pageTitle = __('manage_delivery_cities');
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

            <div class="card shadow">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th><?= __('wilaya_code') ?></th>
                                    <th><?= __('wilaya_name') ?></th>
                                    <th><?= __('home_fee') ?></th>
                                    <th><?= __('desk_fee') ?></th>
                                    <th><?= __('actions') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cities as $city): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($city['wilaya_code']) ?></td>
                                        <td><?= htmlspecialchars($city['wilaya_name']) ?></td>
                                        <td>
                                            <span class="fee-display" data-fee-type="home" data-wilaya="<?= $city['wilaya_code'] ?>">
                                                <?= formatPrice($city['home_fee']) ?>
                                            </span>
                                            <form class="fee-edit-form d-none" data-wilaya="<?= $city['wilaya_code'] ?>">
                                                <div class="input-group input-group-sm">
                                                    <input type="number" 
                                                           class="form-control" 
                                                           name="home_fee" 
                                                           value="<?= $city['home_fee'] ?>" 
                                                           step="0.01" 
                                                           min="0" 
                                                           required>
                                                    <button type="submit" class="btn btn-success btn-sm">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-secondary btn-sm cancel-edit">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                            </form>
                                        </td>
                                        <td>
                                            <span class="fee-display" data-fee-type="desk" data-wilaya="<?= $city['wilaya_code'] ?>">
                                                <?= formatPrice($city['desk_fee']) ?>
                                            </span>
                                            <form class="fee-edit-form d-none" data-wilaya="<?= $city['wilaya_code'] ?>">
                                                <div class="input-group input-group-sm">
                                                    <input type="number" 
                                                           class="form-control" 
                                                           name="desk_fee" 
                                                           value="<?= $city['desk_fee'] ?>" 
                                                           step="0.01" 
                                                           min="0" 
                                                           required>
                                                    <button type="submit" class="btn btn-success btn-sm">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-secondary btn-sm cancel-edit">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                            </form>
                                        </td>
                                        <td>
                                            <button class="btn btn-primary btn-sm edit-fees" data-wilaya="<?= $city['wilaya_code'] ?>">
                                                <i class="fas fa-edit me-1"></i><?= __('edit_fees') ?>
                                            </button>
                                            <a href="delivery_desks.php?wilaya_id=<?= $city['wilaya_code'] ?>" class="btn btn-info btn-sm">
                                                <i class="fas fa-store me-1"></i><?= __('manage_desks') ?>
                                            </a>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle edit fees button click
    document.querySelectorAll('.edit-fees').forEach(button => {
        button.addEventListener('click', function() {
            const wilayaCode = this.dataset.wilaya;
            const row = this.closest('tr');
            
            // Hide all fee displays and show edit forms
            row.querySelectorAll('.fee-display').forEach(display => {
                display.classList.add('d-none');
            });
            row.querySelectorAll('.fee-edit-form').forEach(form => {
                form.classList.remove('d-none');
            });
        });
    });

    // Handle cancel edit button click
    document.querySelectorAll('.cancel-edit').forEach(button => {
        button.addEventListener('click', function() {
            const row = this.closest('tr');
            
            // Show all fee displays and hide edit forms
            row.querySelectorAll('.fee-display').forEach(display => {
                display.classList.remove('d-none');
            });
            row.querySelectorAll('.fee-edit-form').forEach(form => {
                form.classList.add('d-none');
            });
        });
    });

    // Handle fee update form submission
    document.querySelectorAll('.fee-edit-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const wilayaCode = this.dataset.wilaya;
            const formData = new FormData();
            formData.append('wilaya_code', wilayaCode);
            formData.append('home_fee', this.querySelector('[name="home_fee"]').value);
            formData.append('desk_fee', this.querySelector('[name="desk_fee"]').value);

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            }).then(() => {
                window.location.reload();
            });
        });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>