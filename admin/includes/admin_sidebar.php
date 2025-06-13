<?php
use function App\__;
use App\Auth;
?>
<div class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <!-- Dashboard Link - Always Visible -->
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>" 
                   href="<?php echo BASE_URL; ?>admin/index.php">
                    <i class="fas fa-tachometer-alt me-2"></i>
                    <?= __('dashboard') ?>
                </a>
            </li>

            <?php if (Auth::checkRole('super_admin')): ?>
                <!-- Super Admin Navigation -->
                <li class="nav-item">
                    <a class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'super_admin/products.php') !== false ? 'active' : '' ?>" 
                       href="<?php echo BASE_URL; ?>admin/super_admin/products.php">
                        <i class="fas fa-box me-2"></i>
                        <?= __('manage_products') ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'super_admin/categories.php') !== false ? 'active' : '' ?>" 
                       href="<?php echo BASE_URL; ?>admin/super_admin/categories.php">
                        <i class="fas fa-tags me-2"></i>
                        <?= __('manage_categories') ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'super_admin/coupons.php') !== false ? 'active' : '' ?>" 
                       href="<?php echo BASE_URL; ?>admin/super_admin/coupons.php">
                        <i class="fas fa-ticket-alt me-2"></i>
                        <?= __('manage_coupons') ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'super_admin/users.php') !== false ? 'active' : '' ?>" 
                       href="<?php echo BASE_URL; ?>admin/super_admin/users.php">
                        <i class="fas fa-users me-2"></i>
                        <?= __('manage_users') ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'super_admin/delivery_cities.php') !== false ? 'active' : '' ?>" 
                       href="<?php echo BASE_URL; ?>admin/super_admin/delivery_cities.php">
                        <i class="fas fa-truck me-2"></i>
                        <?= __('manage_delivery') ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'super_admin/dashboard_stats.php') !== false ? 'active' : '' ?>" 
                       href="<?php echo BASE_URL; ?>admin/super_admin/dashboard_stats.php">
                        <i class="fas fa-chart-bar me-2"></i>
                        <?= __('statistics') ?>
                    </a>
                </li>
            <?php endif; ?>

            <?php if (Auth::checkRole('call_agent')): ?>
                <!-- Call Center Agent Navigation -->
                <li class="nav-item">
                    <a class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'call_center/orders.php') !== false ? 'active' : '' ?>" 
                       href="<?php echo BASE_URL; ?>admin/call_center/orders.php">
                        <i class="fas fa-phone-alt me-2"></i>
                        <?= __('view_new_orders') ?>
                    </a>
                </li>
            <?php endif; ?>

            <?php if (Auth::checkRole('delivery_agent')): ?>
                <!-- Delivery Agent Navigation -->
                <li class="nav-item">
                    <a class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'delivery/orders.php') !== false ? 'active' : '' ?>" 
                       href="<?php echo BASE_URL; ?>admin/delivery/orders.php">
                        <i class="fas fa-shipping-fast me-2"></i>
                        <?= __('view_confirmed_orders') ?>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
        <ul class="nav flex-column mt-4">
            <li class="nav-item">
                <a class="nav-link" href="<?php echo BASE_URL; ?>admin/auth/logout.php">
                    <i class="fas fa-sign-out-alt me-2"></i>
                    <?= __('logout') ?>
                </a>
            </li>
        </ul>
    </div>
</div>

<style>
.sidebar {
    position: fixed;
    top: 0;
    bottom: 0;
    left: 0;
    z-index: 100;
    padding: 48px 0 0;
    box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
}

.sidebar .nav-link {
    font-weight: 500;
    color: #333;
    padding: 0.5rem 1rem;
    margin: 0.2rem 0;
    border-radius: 0.25rem;
}

.sidebar .nav-link:hover {
    color: #007bff;
    background-color: rgba(0, 123, 255, 0.1);
}

.sidebar .nav-link.active {
    color: #007bff;
    background-color: rgba(0, 123, 255, 0.1);
}

.sidebar .nav-link i {
    width: 1.25rem;
    text-align: center;
}

@media (max-width: 767.98px) {
    .sidebar {
        position: static;
        height: auto;
        padding-top: 0;
    }
}
</style>