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

// Check if wilaya_id is present
if (!isset($_GET['wilaya_id'])) {
    $_SESSION['flash_message'] = [
        'type' => 'danger',
        'message' => __('wilaya_not_specified')
    ];
    redirect('/admin/super_admin/delivery_cities.php');
}

$wilayaId = (int)$_GET['wilaya_id'];
$wilaya = $productController->getDeliveryCityById($wilayaId);

if (!$wilaya) {
    $_SESSION['flash_message'] = [
        'type' => 'danger',
        'message' => __('wilaya_not_found')
    ];
    redirect('/admin/super_admin/delivery_cities.php');
}

// Handle delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_desk_id'])) {
    try {
        $deleteDeskId = (int)$_POST['delete_desk_id'];
        if ($productController->deleteDeliveryDesk($deleteDeskId)) {
            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => __('desk_deleted_successfully')
            ];
        }
    } catch (Exception $e) {
        $_SESSION['flash_message'] = [
            'type' => 'danger',
            'message' => $e->getMessage()
        ];
    }
    
    redirect('/admin/super_admin/delivery_desks.php?wilaya_id=' . $wilayaId);
}

// Get all delivery desks for this wilaya
$desks = $productController->getDeliveryDesksByWilaya($wilayaId);

$pageTitle = __('manage_delivery_desks') . ' - ' . $wilaya['wilaya_name'];
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?= $pageTitle ?></h1>
                <div>
                    <a href="delivery_cities.php" class="btn btn-secondary me-2">
                        <i class="fas fa-arrow-left me-2"></i><?= __('back_to_cities') ?>
                    </a>
                    <a href="delivery_desk_form.php?wilaya_id=<?= $wilayaId ?>" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i><?= __('add_new_desk') ?>
                    </a>
                </div>
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
                                    <th><?= __('id') ?></th>
                                    <th><?= __('desk_name') ?></th>
                                    <th><?= __('address') ?></th>
                                    <th><?= __('actions') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($desks as $desk): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($desk['id']) ?></td>
                                        <td><?= htmlspecialchars($desk['name']) ?></td>
                                        <td><?= htmlspecialchars($desk['address']) ?></td>
                                        <td>
                                            <a href="delivery_desk_form.php?id=<?= $desk['id'] ?>" class="btn btn-primary btn-sm">
                                                <i class="fas fa-edit me-1"></i><?= __('edit') ?>
                                            </a>
                                            <button type="button" 
                                                    class="btn btn-danger btn-sm delete-desk" 
                                                    data-desk-id="<?= $desk['id'] ?>"
                                                    data-desk-name="<?= htmlspecialchars($desk['name']) ?>">
                                                <i class="fas fa-trash me-1"></i><?= __('delete') ?>
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
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel"><?= __('confirm_delete') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><?= __('delete_desk_confirmation') ?></p>
                <p class="fw-bold" id="deskName"></p>
            </div>
            <div class="modal-footer">
                <form method="POST">
                    <input type="hidden" name="delete_desk_id" id="deleteDeskId">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= __('cancel') ?></button>
                    <button type="submit" class="btn btn-danger"><?= __('delete') ?></button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize delete modal
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    
    // Handle delete button clicks
    document.querySelectorAll('.delete-desk').forEach(button => {
        button.addEventListener('click', function() {
            const deskId = this.dataset.deskId;
            const deskName = this.dataset.deskName;
            
            document.getElementById('deleteDeskId').value = deskId;
            document.getElementById('deskName').textContent = deskName;
            
            deleteModal.show();
        });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>