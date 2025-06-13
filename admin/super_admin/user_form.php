<?php
require_once __DIR__ . '/../../init.php';

use App\Auth;
use function App\__;

// Check if user is logged in and is a super admin
if (!Auth::check() || !Auth::checkRole('super_admin')) {
    $_SESSION['flash_message'] = [
        'type' => 'danger',
        'message' => __('unauthorized_access')
    ];
    redirect('/admin/index.php');
}

$userId = $_GET['id'] ?? null;
$user = null;
$isEdit = false;

if ($userId) {
    $user = Auth::getUserById((int)$userId);
    if (!$user) {
        $_SESSION['flash_message'] = [
            'type' => 'danger',
            'message' => __('user_not_found')
        ];
        redirect('/admin/super_admin/users.php');
    }
    $isEdit = true;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userData = [
        'username' => $_POST['username'] ?? '',
        'full_name' => $_POST['full_name'] ?? '',
        'email' => $_POST['email'] ?? '',
        'phone' => $_POST['phone'] ?? '',
        'role' => $_POST['role'] ?? 'customer'
    ];

    // Validate required fields
    $requiredFields = ['username', 'full_name', 'email', 'role'];
    $missingFields = [];
    foreach ($requiredFields as $field) {
        if (empty(trim($userData[$field]))) {
            $missingFields[] = $field;
        }
    }

    // Password validation for new users or password change
    if (!$isEdit || !empty($_POST['password'])) {
        if (empty($_POST['password'])) {
            $missingFields[] = 'password';
        } elseif ($_POST['password'] !== $_POST['confirm_password']) {
            $_SESSION['flash_message'] = [
                'type' => 'danger',
                'message' => __('passwords_do_not_match')
            ];
        } else {
            $userData['password'] = $_POST['password'];
        }
    }

    if (!empty($missingFields)) {
        $_SESSION['flash_message'] = [
            'type' => 'danger',
            'message' => __('required_fields_missing') . ': ' . implode(', ', $missingFields)
        ];
    } else {
        try {
            if ($isEdit) {
                if (Auth::updateUser($userId, $userData)) {
                    $_SESSION['flash_message'] = [
                        'type' => 'success',
                        'message' => __('user_updated_successfully')
                    ];
                    redirect('/admin/super_admin/users.php');
                }
            } else {
                if (Auth::createUser($userData)) {
                    $_SESSION['flash_message'] = [
                        'type' => 'success',
                        'message' => __('user_created_successfully')
                    ];
                    redirect('/admin/super_admin/users.php');
                }
            }
        } catch (Exception $e) {
            $_SESSION['flash_message'] = [
                'type' => 'danger',
                'message' => $e->getMessage()
            ];
        }
    }
}

$pageTitle = $isEdit ? __('edit_user') : __('add_new_user');
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?= $pageTitle ?></h1>
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
                    <form method="POST" class="needs-validation" novalidate>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="username" class="form-label"><?= __('username') ?> *</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="username" 
                                       name="username" 
                                       value="<?= htmlspecialchars($user['username'] ?? '') ?>"
                                       <?= $isEdit ? 'readonly' : 'required' ?>>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="full_name" class="form-label"><?= __('full_name') ?> *</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="full_name" 
                                       name="full_name" 
                                       value="<?= htmlspecialchars($user['full_name'] ?? '') ?>"
                                       required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label"><?= __('email') ?> *</label>
                                <input type="email" 
                                       class="form-control" 
                                       id="email" 
                                       name="email" 
                                       value="<?= htmlspecialchars($user['email'] ?? '') ?>"
                                       required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label"><?= __('phone') ?></label>
                                <input type="tel" 
                                       class="form-control" 
                                       id="phone" 
                                       name="phone" 
                                       value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">
                                    <?= $isEdit ? __('new_password') : __('password') ?> 
                                    <?= $isEdit ? '' : '*' ?>
                                </label>
                                <input type="password" 
                                       class="form-control" 
                                       id="password" 
                                       name="password"
                                       <?= $isEdit ? '' : 'required' ?>>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="confirm_password" class="form-label">
                                    <?= $isEdit ? __('confirm_new_password') : __('confirm_password') ?> 
                                    <?= $isEdit ? '' : '*' ?>
                                </label>
                                <input type="password" 
                                       class="form-control" 
                                       id="confirm_password" 
                                       name="confirm_password"
                                       <?= $isEdit ? '' : 'required' ?>>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="role" class="form-label"><?= __('role') ?> *</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="customer" <?= ($user['role'] ?? '') === 'customer' ? 'selected' : '' ?>>
                                    <?= __('customer') ?>
                                </option>
                                <option value="call_agent" <?= ($user['role'] ?? '') === 'call_agent' ? 'selected' : '' ?>>
                                    <?= __('call_agent') ?>
                                </option>
                                <option value="delivery_agent" <?= ($user['role'] ?? '') === 'delivery_agent' ? 'selected' : '' ?>>
                                    <?= __('delivery_agent') ?>
                                </option>
                                <option value="super_admin" <?= ($user['role'] ?? '') === 'super_admin' ? 'selected' : '' ?>>
                                    <?= __('super_admin') ?>
                                </option>
                            </select>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="users.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i><?= __('back') ?>
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i><?= $isEdit ? __('update') : __('save') ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
// Form validation
(function() {
    'use strict';
    
    const forms = document.querySelectorAll('.needs-validation');
    
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            form.classList.add('was-validated');
        }, false);
    });
})();
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>