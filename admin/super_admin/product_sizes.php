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

// Check if product ID is provided
$productId = $_GET['id'] ?? null;
if (!$productId) {
    $_SESSION['flash_message'] = [
        'type' => 'danger',
        'message' => __('product_not_found')
    ];
    redirect('/admin/super_admin/products.php');
}

// Get product details
$product = $productController->getProductById((int)$productId);
if (!$product) {
    $_SESSION['flash_message'] = [
        'type' => 'danger',
        'message' => __('product_not_found')
    ];
    redirect('/admin/super_admin/products.php');
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'add':
                if ($productController->addProductSize($productId, $_POST['size'], (int)$_POST['quantity'])) {
                    $_SESSION['flash_message'] = [
                        'type' => 'success',
                        'message' => __('size_added_successfully')
                    ];
                }
                break;
                
            case 'update':
                if ($productController->updateProductSize((int)$_POST['size_id'], (int)$_POST['quantity'])) {
                    $_SESSION['flash_message'] = [
                        'type' => 'success',
                        'message' => __('size_updated_successfully')
                    ];
                }
                break;
                
            case 'delete':
                if ($productController->deleteProductSize((int)$_POST['size_id'])) {
                    $_SESSION['flash_message'] = [
                        'type' => 'success',
                        'message' => __('size_deleted_successfully')
                    ];
                }
                break;
        }
    } catch (Exception $e) {
        $_SESSION['flash_message'] = [
            'type' => 'danger',
            'message' => $e->getMessage()
        ];
    }
    
    redirect("/admin/super_admin/product_sizes.php?id=$productId");
}

$pageTitle = __('manage_sizes') . ' - ' . $product['name_' . $_SESSION['lang']];
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?= $pageTitle ?></h1>
                <a href="products.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i><?= __('back_to_products') ?>
                </a>
            </div>

            <?php if (isset($_SESSION['flash_message'])): ?>
                <div class="alert alert-<?= $_SESSION['flash_message']['type'] ?> alert-dismissible fade show" role="alert">
                    <?= $_SESSION['flash_message']['message'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['flash_message']); ?>
            <?php endif; ?>

            <!-- Add New Size Form -->
            <div class="card shadow mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><?= __('add_new_size') ?></h5>
                </div>
                <div class="card-body">
                    <form method="POST" class="row g-3">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="col-md-4">
                            <label for="size" class="form-label"><?= __('size') ?> *</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="size" 
                                   name="size" 
                                   required>
                        </div>
                        
                        <div class="col-md-4">
                            <label for="quantity" class="form-label"><?= __('quantity') ?> *</label>
                            <input type="number" 
                                   class="form-control" 
                                   id="quantity" 
                                   name="quantity" 
                                   min="0" 
                                   required>
                        </div>
                        
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i><?= __('add_size') ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Existing Sizes Table -->
            <div class="card shadow">
                <div class="card-header">
                    <h5 class="mb-0"><?= __('existing_sizes') ?></h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th><?= __('size') ?></th>
                                    <th><?= __('quantity') ?></th>
                                    <th><?= __('actions') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($product['sizes'] as $size): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($size['size']) ?></td>
                                        <td>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="update">
                                                <input type="hidden" name="size_id" value="<?= $size['id'] ?>">
                                                <div class="input-group input-group-sm" style="width: 120px;">
                                                    <input type="number" 
                                                           class="form-control" 
                                                           name="quantity" 
                                                           value="<?= $size['quantity'] ?>" 
                                                           min="0">
                                                    <button type="submit" class="btn btn-outline-primary">
                                                        <i class="fas fa-save"></i>
                                                    </button>
                                                </div>
                                            </form>
                                        </td>
                                        <td>
                                            <button type="button" 
                                                    class="btn btn-sm btn-danger" 
                                                    onclick="confirmDelete(<?= $size['id'] ?>)">
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
                <?= __('delete_size_confirmation') ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <?= __('cancel') ?>
                </button>
                <form method="POST" id="deleteForm">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="size_id" id="deleteSizeId">
                    <button type="submit" class="btn btn-danger">
                        <?= __('delete') ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(sizeId) {
    document.getElementById('deleteSizeId').value = sizeId;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>