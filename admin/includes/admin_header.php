<?php
require_once __DIR__ . '/../../init.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'admin/auth/login.php');
    exit;
}

// Get current user
$currentUser = App\Auth::user();

// Check if user has admin role
$allowedRoles = ['super_admin', 'admin', 'delivery_agent', 'call_agent'];
if (!$currentUser || !in_array($currentUser['role'], $allowedRoles)) {
    header('Location: ' . BASE_URL . 'admin/auth/login.php?error=unauthorized');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Admin Dashboard'; ?> - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>public/css/admin.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo BASE_URL; ?>admin/index.php"><?php echo APP_NAME; ?> Admin</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <?php if (in_array($currentUser['role'], ['super_admin', 'admin'])): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>admin/index.php">Dashboard</a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if ($currentUser['role'] === 'super_admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>admin/super_admin/users.php">Users</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>admin/super_admin/products.php">Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>admin/super_admin/categories.php">Categories</a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if (in_array($currentUser['role'], ['super_admin', 'delivery_agent'])): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>admin/delivery/orders.php">Delivery Orders</a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if (in_array($currentUser['role'], ['super_admin', 'call_agent'])): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>admin/call_center/orders.php">Call Center</a>
                    </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($currentUser['username']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>admin/profile.php">Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>admin/auth/logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4">
        <?php
        // Display flash messages if any
        if (isset($_SESSION['flash_message'])) {
            $type = $_SESSION['flash_message']['type'];
            $message = $_SESSION['flash_message']['message'];
            unset($_SESSION['flash_message']);
            echo "<div class='alert alert-{$type} alert-dismissible fade show' role='alert'>";
            echo htmlspecialchars($message);
            echo "<button type='button' class='btn-close' data-bs-dismiss='alert'></button>";
            echo "</div>";
        }
        ?>
    </div>
</body>
</html> 