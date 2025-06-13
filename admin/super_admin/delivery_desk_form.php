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
$deskId = $_GET['id'] ?? null;
$wilayaId = $_GET['wilaya_id'] ?? null;
$desk = null;
$isEdit = false;

if ($deskId) {
    $desk = $productController->getDeliveryDeskById((int)$deskId);
    if (!$desk) {
        $_SESSION['flash_message'] = [
            'type' => 'danger',
            'message' => __('desk_not_found')
        ];
        redirect('/admin/super_admin/delivery_cities.php');
    }
    $wilayaId = $desk['wilaya_code'];
    $isEdit = true;
} elseif (!$wilayaId) {
    $_SESSION['flash_message'] = [
        'type' => 'danger',
        'message' => __('wilaya_not_specified')
    ];
    redirect('/admin/super_admin/delivery_cities.php');
}

// Get wilaya details
$wilaya = $productController->getDeliveryCityById((int)$wilayaId);
if (!$wilaya) {
    $_SESSION['flash_message'] = [
        'type' => 'danger',
        'message' => __('wilaya_not_found')
    ];
    redirect('/admin/super_admin/delivery_cities.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $formData = [
            'wilaya_code' => (int)$wilayaId,
            'name_en' => $_POST['desk_name_en'] ?? '',
            'name_fr' => $_POST['desk_name_fr'] ?? '',
            'name_ar' => $_POST['desk_name_ar'] ?? '',
            'address_en' => $_POST['address_en'] ?? '',
            'address_fr' => $_POST['address_fr'] ?? '',
            'address_ar' => $_POST['address_ar'] ?? ''
        ];

        // Validate required fields
        if (empty($formData['name_en']) || empty($formData['address_en'])) {
            throw new Exception(__('required_fields_missing'));
        }

        if ($isEdit) {
            if ($productController->updateDeliveryDesk((int)$deskId, $formData)) {
                $_SESSION['flash_message'] = [
                    'type' => 'success',
                    'message' => __('desk_updated_successfully')
                ];
            }
        } else {
            if ($productController->createDeliveryDesk($formData)) {
                $_SESSION['flash_message'] = [
                    'type' => 'success',
                    'message' => __('desk_created_successfully')
                ];
            }
        }
        
        redirect('/admin/super_admin/delivery_desks.php?wilaya_id=' . $wilayaId);
    } catch (Exception $e) {
        $_SESSION['flash_message'] = [
            'type' => 'danger',
            'message' => $e->getMessage()
        ];
    }
}

$pageTitle = $isEdit ? __('edit_delivery_desk') : __('add_new_delivery_desk');
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?= $pageTitle ?></h1>
                <a href="delivery_desks.php?wilaya_id=<?= $wilayaId ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i><?= __('back_to_desks') ?>
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
                        <input type="hidden" name="wilaya_id" value="<?= $wilayaId ?>">
                        
                        <div class="row g-3">
                            <!-- English Name -->
                            <div class="col-md-4">
                                <label for="desk_name_en" class="form-label"><?= __('desk_name') ?> (<?= __('english') ?>) *</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="desk_name_en" 
                                       name="desk_name_en" 
                                       value="<?= htmlspecialchars($desk['name_en'] ?? '') ?>" 
                                       required>
                            </div>

                            <!-- French Name -->
                            <div class="col-md-4">
                                <label for="desk_name_fr" class="form-label"><?= __('desk_name') ?> (<?= __('french') ?>)</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="desk_name_fr" 
                                       name="desk_name_fr" 
                                       value="<?= htmlspecialchars($desk['name_fr'] ?? '') ?>">
                            </div>

                            <!-- Arabic Name -->
                            <div class="col-md-4">
                                <label for="desk_name_ar" class="form-label"><?= __('desk_name') ?> (<?= __('arabic') ?>)</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="desk_name_ar" 
                                       name="desk_name_ar" 
                                       value="<?= htmlspecialchars($desk['name_ar'] ?? '') ?>">
                            </div>

                            <!-- English Address -->
                            <div class="col-md-4">
                                <label for="address_en" class="form-label"><?= __('address') ?> (<?= __('english') ?>) *</label>
                                <textarea class="form-control" 
                                          id="address_en" 
                                          name="address_en" 
                                          rows="3" 
                                          required><?= htmlspecialchars($desk['address_en'] ?? '') ?></textarea>
                            </div>

                            <!-- French Address -->
                            <div class="col-md-4">
                                <label for="address_fr" class="form-label"><?= __('address') ?> (<?= __('french') ?>)</label>
                                <textarea class="form-control" 
                                          id="address_fr" 
                                          name="address_fr" 
                                          rows="3"><?= htmlspecialchars($desk['address_fr'] ?? '') ?></textarea>
                            </div>

                            <!-- Arabic Address -->
                            <div class="col-md-4">
                                <label for="address_ar" class="form-label"><?= __('address') ?> (<?= __('arabic') ?>)</label>
                                <textarea class="form-control" 
                                          id="address_ar" 
                                          name="address_ar" 
                                          rows="3"><?= htmlspecialchars($desk['address_ar'] ?? '') ?></textarea>
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