<?php
require_once __DIR__ . '/../../init.php';

use App\Auth;
use function App\__;

// Handle different actions
$action = $_GET['action'] ?? 'login';

switch ($action) {
    case 'login':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Handle login submission
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            
            if ($auth->login($username, $password)) {
                // Check if user is admin
                if ($auth->checkRole('admin')) {
                    redirect('/admin/index.php');
                } else {
                    $auth->logout();
                    redirect('/admin/auth/auth.php?error=not_admin');
                }
            } else {
                redirect('/admin/auth/auth.php?error=invalid_credentials');
            }
        } else {
            // Display login form
            if ($auth->check() && $auth->checkRole('admin')) {
                redirect('/admin/index.php');
            }
            
            $pageTitle = __('admin_login');
            require_once __DIR__ . '/../includes/admin_header.php';
            ?>
            
            <div class="container mt-5">
                <div class="row justify-content-center">
                    <div class="col-md-6 col-lg-4">
                        <div class="card shadow">
                            <div class="card-body">
                                <h2 class="text-center mb-4"><?php echo __('admin_login'); ?></h2>
                                
                                <?php if (isset($_GET['error'])): ?>
                                    <div class="alert alert-danger">
                                        <?php
                                        switch ($_GET['error']) {
                                            case 'invalid_credentials':
                                                echo __('error_invalid_credentials');
                                                break;
                                            case 'not_admin':
                                                echo __('error_not_admin');
                                                break;
                                            default:
                                                echo __('error_unknown');
                                        }
                                        ?>
                                    </div>
                                <?php endif; ?>
                                
                                <form method="POST" action="auth.php?action=login">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                    <div class="mb-3">
                                        <label for="username" class="form-label"><?php echo __('username'); ?></label>
                                        <input type="text" class="form-control" id="username" name="username" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="password" class="form-label"><?php echo __('password'); ?></label>
                                        <input type="password" class="form-control" id="password" name="password" required>
                                    </div>
                                    
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary"><?php echo __('login'); ?></button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php
            require_once __DIR__ . '/../includes/admin_footer.php';
        }
        break;
        
    case 'logout':
        $auth->logout();
        redirect('/admin/auth/auth.php');
        break;
        
    default:
        redirect('/admin/auth/auth.php');
}