<?php

require_once __DIR__ . '/../init.php';

use App\Controllers\ProductController;
use function App\__;
use function App\format_currency;

$pageTitle = __('store');

// Get product controller instance
$productController = ProductController::getInstance();

// Get all categories for filters
$categories = $productController->getAllCategories();

// Get filter parameters from GET
$filters = [
    'category_id' => $_GET['category_id'] ?? null,
    'min_price' => $_GET['min_price'] ?? null,
    'max_price' => $_GET['max_price'] ?? null,
    'on_sale' => isset($_GET['on_sale']) ? true : null,
    'search' => $_GET['search'] ?? null,
    'page' => max(1, $_GET['page'] ?? 1),
    'per_page' => 12
];

// Get products with filters
$products = $productController->getAllProducts($filters);

include __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <!-- Filters Sidebar -->
        <div class="col-lg-3 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><?= __('filters') ?></h5>
                </div>
                <div class="card-body">
                    <form action="store.php" method="GET" id="filterForm">
                        <!-- Search -->
                        <div class="mb-4">
                            <label for="search" class="form-label"><?= __('search') ?></label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="search" name="search" 
                                       value="<?= htmlspecialchars($filters['search'] ?? '') ?>"
                                       placeholder="<?= __('search_placeholder') ?>">
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Categories -->
                        <div class="mb-4">
                            <h6 class="mb-3"><?= __('categories') ?></h6>
                            <?php foreach ($categories as $category): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" 
                                           name="category_id[]" 
                                           value="<?= $category['id'] ?>"
                                           id="category_<?= $category['id'] ?>"
                                           <?= in_array($category['id'], (array)($filters['category_id'] ?? [])) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="category_<?= $category['id'] ?>">
                                        <?= $category['name_' . $_SESSION['lang']] ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Price Range -->
                        <div class="mb-4">
                            <h6 class="mb-3"><?= __('price_range') ?></h6>
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="number" class="form-control" name="min_price" 
                                           placeholder="<?= __('min_price') ?>"
                                           value="<?= $filters['min_price'] ?? '' ?>">
                                </div>
                                <div class="col-6">
                                    <input type="number" class="form-control" name="max_price" 
                                           placeholder="<?= __('max_price') ?>"
                                           value="<?= $filters['max_price'] ?? '' ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Sale Filter -->
                        <div class="mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" 
                                       name="on_sale" 
                                       id="on_sale"
                                       <?= $filters['on_sale'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="on_sale">
                                    <?= __('on_sale_only') ?>
                                </label>
                            </div>
                        </div>

                        <!-- Apply Filters Button -->
                        <button type="submit" class="btn btn-primary w-100">
                            <?= __('apply_filters') ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Products Grid -->
        <div class="col-lg-9">
            <h1 class="mb-4"><?= __('all_products') ?></h1>

            <?php if (empty($products['items'])): ?>
                <div class="alert alert-info">
                    <?= __('no_products_found') ?>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($products['items'] as $product): ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="product-card">
                                <a href="<?php echo BASE_URL; ?>pages/product.php?id=<?= $product['id'] ?>" class="product-link">
                                    <div class="product-image">
                                        <img src="<?= isset($product['images'][0]['path']) ? BASE_URL . 'public/images/products/' . $product['images'][0]['path'] : BASE_URL . 'public/images/placeholder.jpg' ?>" 
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
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>