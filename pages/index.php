<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../init.php';

use App\Controllers\ProductController;
use function App\__;
use function App\format_currency;

$pageTitle = __('home');

// Get product controller instance
$productController = ProductController::getInstance();

// Fetch new arrivals (latest 8 products)
$newArrivals = $productController->getAllProducts([
    'limit' => 8,
    'order_by' => 'created_at',
    'order_dir' => 'DESC'
]);

// Fetch discounted products
$discountedProducts = $productController->getAllProducts([
    'has_discount' => true,
    'limit' => 8
]);

// Fetch all categories
$categories = $productController->getAllCategories();

include __DIR__ . '/../includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section position-relative overflow-hidden">
    <video class="hero-video" autoplay loop muted playsinline>
        <source src="<?php echo BASE_URL; ?>public/videos/hero.mp4" type="video/mp4">
    </video>
    <div class="hero-overlay d-flex align-items-center">
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    <h1 class="display-3 text-white mb-4" x-data="{ text: 'LOUDREAM' }" x-text="text"></h1>
                    <p class="lead text-white mb-4" x-data="{ text: '<?= __('hero_subtitle') ?>' }" x-text="text"></p>
                    <a href="<?php echo BASE_URL; ?>pages/store.php" class="btn btn-primary btn-lg"><?= __('shop_now') ?></a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- New Arrivals Section -->
<section class="new-arrivals py-5" 
         x-data="{ show: false }" 
         x-intersect="show = true" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform translate-y-12"
         x-transition:enter-end="opacity-100 transform translate-y-0">
    <div class="container">
        <h2 class="section-title text-center mb-5"><?= __('new_arrivals') ?></h2>
        <div class="row">
            <?php foreach ($newArrivals['items'] as $product): ?>
                <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
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
                                        <span class="old-price"><?= format_currency($product['price'] ?? 0) ?></span>
                                        <span class="new-price"><?= format_currency($product['discount_price'] ?? 0) ?></span>
                                        <span class="discount-badge">
                                            -<?= round((($product['price'] - $product['discount_price']) / $product['price']) * 100) ?>%
                                        </span>
                                    <?php else: ?>
                                        <span class="price"><?= format_currency($product['price'] ?? 0) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Sales Section -->
<section class="sales py-5 bg-light" 
         x-data="{ show: false }" 
         x-intersect="show = true" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform translate-y-12"
         x-transition:enter-end="opacity-100 transform translate-y-0">
    <div class="container">
        <h2 class="section-title text-center mb-5"><?= __('sales') ?></h2>
        <div class="row">
            <?php foreach ($discountedProducts['items'] as $product): ?>
                <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
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
                                    <span class="old-price"><?= format_currency($product['price'] ?? 0) ?></span>
                                    <span class="new-price"><?= format_currency($product['discount_price'] ?? 0) ?></span>
                                    <span class="discount-badge">
                                        -<?= round((($product['price'] - $product['discount_price']) / $product['price']) * 100) ?>%
                                    </span>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Categories Section -->
<section class="categories py-5" 
         x-data="{ show: false }" 
         x-intersect="show = true" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform translate-y-12"
         x-transition:enter-end="opacity-100 transform translate-y-0">
    <div class="container">
        <h2 class="section-title text-center mb-5"><?= __('categories') ?></h2>
        <div class="row justify-content-center">
            <?php foreach ($categories as $category): ?>
                <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                    <a href="<?php echo BASE_URL; ?>pages/category.php?id=<?= $category['id'] ?>" class="category-card">
                        <div class="category-image">
                            <img src="<?= isset($category['image_path']) ? BASE_URL . 'public/images/categories/' . $category['image_path'] : BASE_URL . 'public/images/category-placeholder.jpg' ?>" 
                                 alt="<?= $category['name_' . $_SESSION['lang']] ?>"
                                 class="img-fluid">
                        </div>
                        <h3 class="category-title text-center mt-3">
                            <?= $category['name_' . $_SESSION['lang']] ?>
                        </h3>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- About Us Section -->
<section class="about-us py-5 bg-light" 
         x-data="{ show: false }" 
         x-intersect="show = true" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform translate-y-12"
         x-transition:enter-end="opacity-100 transform translate-y-0">
    <div class="container">
        <h2 class="section-title text-center mb-5"><?= __('about_us') ?></h2>
        <div class="row align-items-center">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <img src="<?php echo BASE_URL; ?>public/images/about-us.jpg" alt="About Us" class="img-fluid rounded">
            </div>
            <div class="col-lg-6">
                <h3 class="mb-4"><?= __('about_us_title') ?></h3>
                <p class="lead mb-4"><?= __('about_us_text') ?></p>
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="feature">
                            <i class="fas fa-shipping-fast mb-3"></i>
                            <h4><?= __('fast_delivery') ?></h4>
                            <p><?= __('fast_delivery_text') ?></p>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="feature">
                            <i class="fas fa-medal mb-3"></i>
                            <h4><?= __('quality_products') ?></h4>
                            <p><?= __('quality_products_text') ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>