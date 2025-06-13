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

// Initialize ProductController using singleton pattern
$productController = ProductController::getInstance();

// Handle product deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product_id'])) {
    $productId = (int)$_POST['delete_product_id'];
    
    if ($productController->deleteProduct($productId)) {
        $_SESSION['flash_message'] = [
            'type' => 'success',
            'message' => __('product_deleted_successfully')
        ];
    } else {
        $_SESSION['flash_message'] = [
            'type' => 'danger',
            'message' => __('error_deleting_product')
        ];
    }
    
    redirect('/admin/super_admin/products.php');
}

// Get all products
$products = $productController->getAllProducts();

$pageTitle = __('manage_products');
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?= __('manage_products') ?></h1>
                <a href="product_form.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i><?= __('add_new_product') ?>
                </a>
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
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th><?= __('image') ?></th>
                                    <th><?= __('name') ?></th>
                                    <th><?= __('category') ?></th>
                                    <th><?= __('price') ?></th>
                                    <th><?= __('discount_price') ?></th>
                                    <th><?= __('sku') ?></th>
                                    <th><?= __('status') ?></th>
                                    <th><?= __('actions') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($products)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center"><?= __('no_products_found') ?></td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($products as $product): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($product['id']) ?></td>
                                            <td>
                                                <?php if (!empty($product['image_path'])): ?>
                                                    <img src="<?= BASE_URL . $product['image_path'] ?>" 
                                                         alt="<?= htmlspecialchars($product['name_en']) ?>" 
                                                         class="img-thumbnail" 
                                                         style="width: 50px; height: 50px; object-fit: cover;">
                                                <?php else: ?>
                                                    <div class="bg-light text-center" style="width: 50px; height: 50px;">
                                                        <i class="fas fa-image text-muted"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($product['name_en']) ?></td>
                                            <td><?= htmlspecialchars($product['category_name'] ?? '-') ?></td>
                                            <td><?= number_format($product['price'], 2) ?></td>
                                            <td>
                                                <?php if (!empty($product['discount_price'])): ?>
                                                    <?= number_format($product['discount_price'], 2) ?>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($product['sku']) ?></td>
                                            <td>
                                                <span class="badge bg-<?= $product['is_active'] ? 'success' : 'danger' ?>">
                                                    <?= $product['is_active'] ? __('active') : __('inactive') ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="product_form.php?id=<?= $product['id'] ?>" 
                                                       class="btn btn-sm btn-primary" 
                                                       title="<?= __('edit') ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="product_sizes.php?id=<?= $product['id'] ?>" 
                                                       class="btn btn-sm btn-info" 
                                                       title="<?= __('manage_sizes') ?>">
                                                        <i class="fas fa-ruler"></i>
                                                    </a>
                                                    <button type="button" 
                                                            class="btn btn-sm btn-danger" 
                                                            onclick="confirmDelete(<?= $product['id'] ?>)" 
                                                            title="<?= __('delete') ?>">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
function confirmDelete(productId) {
    if (confirm('<?= __('confirm_delete_product') ?>')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'products.php';
        
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'delete_product_id';
        input.value = productId;
        
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>