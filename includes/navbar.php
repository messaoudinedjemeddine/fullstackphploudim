<?php
// Get cart count
$cart_count = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += $item['quantity'];
    }
}

// Use BASE_URL for all links
require_once __DIR__ . '/../config/app.php';

// Language switcher logic
$current_page_name = basename($_SERVER['PHP_SELF']); // e.g., "index.php" or "product.php"
$current_page_dir = dirname($_SERVER['PHP_SELF']); // e.g., "/loudimm/pages"
$base_page_path = BASE_URL . trim($current_page_dir, '/') . '/' . $current_page_name;

$query_params = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
parse_str($query_params, $params);
unset($params['lang']); // Remove existing lang param

// For English
$english_link = $base_page_path . '?' . http_build_query(array_merge($params, ['lang' => 'en']));
// For Arabic
$arabic_link = $base_page_path . '?' . http_build_query(array_merge($params, ['lang' => 'ar']));
// For French
$french_link = $base_page_path . '?' . http_build_query(array_merge($params, ['lang' => 'fr']));

$lang = $_SESSION['lang'] ?? 'en';
try {
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY name_$lang");
    $categories = $stmt->fetchAll();
} catch (Exception $e) {
    echo "<div style='color:red'>Categories query failed: " . htmlspecialchars($e->getMessage()) . "</div>";
    $categories = [];
}
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="<?php echo BASE_URL; ?>pages/index.php">
            <i class="fas fa-store"></i> <?php echo APP_NAME; ?>
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>pages/index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>pages/store.php">Store</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="categoriesDropdown" role="button" data-bs-toggle="dropdown">
                        Categories
                    </a>
                    <ul class="dropdown-menu">
                        <?php
                        foreach ($categories as $category):
                        ?>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/category.php?id=<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name_' . $lang]); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </li>
            </ul>
            
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="languageDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-globe"></i> Language
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?php echo $english_link; ?>">English</a></li>
                        <li><a class="dropdown-item" href="<?php echo $arabic_link; ?>">العربية</a></li>
                        <li><a class="dropdown-item" href="<?php echo $french_link; ?>">Français</a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link position-relative" href="<?php echo BASE_URL; ?>pages/cart.php">
                        <i class="fas fa-shopping-cart"></i> Cart
                        <?php if ($cart_count > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?php echo $cart_count; ?>
                        </span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>admin/auth/login.php">
                        <i class="fas fa-user-shield"></i> Admin Login
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>pages/index.php#about">About Us</a>
                </li>
            </ul>
        </div>
    </div>
</nav>