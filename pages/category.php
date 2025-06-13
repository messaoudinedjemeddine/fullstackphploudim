<?php

require_once __DIR__ . '/../init.php';

use App\Controllers\ProductController;
use function App\__;
use function App\format_currency;

// Get product controller instance
$productController = ProductController::getInstance();

// Validate category_id
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: /pages/store.php');
    exit;
}

// Get category details
$category = $productController->getCategoryById($_GET['id']);

// If category not found, redirect to store
if (!$category) {
    header('Location: /pages/store.php');
    exit;
}

// Set page title
$pageTitle = __('category') . ': ' . $category['name_' . $_SESSION['lang']];

// Get filter parameters from GET
$filters = [
    'category_id' => $category['id'],
    'search' => $_GET['search'] ?? null,
    'page' => max(1, $_GET['page'] ?? 1),
    'per_page' => 12
];

// Get products for this category
$products = $productController->getAllProducts($filters);

include __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <!-- Category Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="mb-4"><?= $category['name_' . $_SESSION['lang']] ?></h1>
            <?php if ($category['description_' . $_SESSION['lang']]): ?>
                <p class="lead"><?= $category['description_' . $_SESSION['lang']] ?></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Search Bar -->
    <div class="row mb-4">
        <div class="col-md-6">
            <form action="" method="GET" class="d-flex">
                <input type="hidden" name="id" value="<?= $category['id'] ?>">
                <div class="input-group">
                    <input type="text" class="form-control" name="search" 
                           value="<?= htmlspecialchars($filters['search'] ?? '') ?>"
                           placeholder="<?= __('search_in_category') ?>">
                    <button class="btn btn-primary" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Products Grid -->
    <div class="row">
        <?php if (empty($products['items'])): ?>
            <div class="col-12">
                <div class="alert alert-info">
                    <?= __('no_products_found') ?>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($products['items'] as $product): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="product-card">
                        <a href="/pages/product.php?id=<?= $product['id'] ?>" class="product-link">
                            <div class="product-image">
                                <img src="<?= $product['images'][0]['path'] ?? '/assets/images/placeholder.jpg' ?>" 
                                     alt="<?= $product['name_' . $_SESSION['lang']] ?>"
                                     class="img-fluid">
                                <?php if ($product['total_quantity'] <= 0): ?>
                                    <div class="out-of-stock-overlay">
                                        <span><?= __('out_of_stock') ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="product-info p-3">
                                <h3 class="product-title"><?= $product['name_' . $_SESSION['lang']] ?></h3>
                                <div class="product-price">
                                    <?php if ($product['discount_price']): ?>
                                        <span class="old-price"><?= format_currency($product['price']) ?></span>
                                        <span class="new-price"><?= format_currency($product['discount_price']) ?></span>
                                        <span class="discount-badge">
                                            -<?= round((($product['price'] - $product['discount_price']) / $product['price']) * 100) ?>%
                                        </span>
                                    <?php else: ?>
                                        <span class="price"><?= format_currency($product['price']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($products['total_pages'] > 1): ?>
        <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-center">
                <?php if ($products['current_page'] > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?= http_build_query(array_merge($filters, ['page' => $products['current_page'] - 1])) ?>">
                            <?= __('previous') ?>
                        </a>
                    </li>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $products['total_pages']; $i++): ?>
                    <li class="page-item <?= $i === $products['current_page'] ? 'active' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($filters, ['page' => $i])) ?>">
                            <?= $i ?>
                        </a>
                    </li>
                <?php endfor; ?>

                <?php if ($products['current_page'] < $products['total_pages']): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?= http_build_query(array_merge($filters, ['page' => $products['current_page'] + 1])) ?>">
                            <?= __('next') ?>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>