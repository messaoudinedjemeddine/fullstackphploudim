<?php
require_once __DIR__ . '/../../init.php';

// If user is already logged in, redirect to appropriate dashboard
if (isset($_SESSION['user_id'])) {
    $user = App\Auth::user();
    if ($user) {
        switch ($user['role']) {
            case 'super_admin':
            case 'admin':
                header('Location: ' . BASE_URL . 'admin/index.php');
                break;
            case 'delivery_agent':
                header('Location: ' . BASE_URL . 'admin/delivery/orders.php');
                break;
            case 'call_agent':
                header('Location: ' . BASE_URL . 'admin/call_center/orders.php');
                break;
            default:
                header('Location: ' . BASE_URL . 'admin/auth/login.php?error=unauthorized');
        }
        exit;
    }
}

$pageTitle = 'Admin Login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .login-container {
            max-width: 400px;
            margin: 100px auto;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .card-header {
            background-color: #fff;
            border-bottom: none;
            text-align: center;
            padding: 20px;
        }
        .card-body {
            padding: 30px;
        }
        .form-control {
            border-radius: 5px;
            padding: 10px 15px;
        }
        .btn-primary {
            padding: 10px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="card">
                <div class="card-header">
                    <h3 class="mb-0"><?php echo APP_NAME; ?> Admin</h3>
                </div>
                <div class="card-body">
                    <?php if (isset($_GET['error'])): ?>
                        <div class="alert alert-danger">
                            <?php
                            switch ($_GET['error']) {
                                case 'invalid_credentials':
                                    echo 'Invalid username or password';
                                    break;
                                case 'unauthorized':
                                    echo 'You are not authorized to access this area';
                                    break;
                                default:
                                    echo 'An error occurred. Please try again.';
                            }
                            ?>
                        </div>
                    <?php endif; ?>

                    <form action="auth.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Login</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 