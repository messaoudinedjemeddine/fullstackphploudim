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

// Handle user deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user_id'])) {
    $deleteUserId = (int)$_POST['delete_user_id'];
    
    if (Auth::deleteUser($deleteUserId)) {
        $_SESSION['flash_message'] = [
            'type' => 'success',
            'message' => __('user_deleted_successfully')
        ];
    } else {
        $_SESSION['flash_message'] = [
            'type' => 'danger',
            'message' => __('error_deleting_user')
        ];
    }
    
    // Redirect to prevent form resubmission
    redirect('/admin/super_admin/users.php');
}

// Get all users
$users = Auth::getAllUsers();

$pageTitle = __('manage_users');
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?= __('manage_users') ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="user_form.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i><?= __('add_new_user') ?>
                    </a>
                </div>
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
                        <table class="table table-bordered table-hover" id="usersTable">
                            <thead>
                                <tr>
                                    <th><?= __('id') ?></th>
                                    <th><?= __('username') ?></th>
                                    <th><?= __('full_name') ?></th>
                                    <th><?= __('role') ?></th>
                                    <th><?= __('email') ?></th>
                                    <th><?= __('phone') ?></th>
                                    <th><?= __('actions') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($user['id']) ?></td>
                                        <td><?= htmlspecialchars($user['username']) ?></td>
                                        <td><?= htmlspecialchars($user['full_name']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $user['role'] === 'super_admin' ? 'danger' : 
                                                ($user['role'] === 'call_agent' ? 'warning' : 
                                                ($user['role'] === 'delivery_agent' ? 'info' : 'secondary')) ?>">
                                                <?= __($user['role']) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($user['email']) ?></td>
                                        <td><?= htmlspecialchars($user['phone']) ?></td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="user_form.php?id=<?= $user['id'] ?>" 
                                                   class="btn btn-sm btn-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" 
                                                        class="btn btn-sm btn-danger delete-user" 
                                                        data-user-id="<?= $user['id'] ?>"
                                                        data-username="<?= htmlspecialchars($user['username']) ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel"><?= __('confirm_delete') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?= __('confirm_delete_user') ?> <span id="deleteUsername"></span>?
            </div>
            <div class="modal-footer">
                <form method="POST" id="deleteForm">
                    <input type="hidden" name="delete_user_id" id="deleteUserId">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <?= __('cancel') ?>
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <?= __('delete') ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize delete confirmation modal
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    
    // Handle delete button clicks
    document.querySelectorAll('.delete-user').forEach(button => {
        button.addEventListener('click', function() {
            const userId = this.dataset.userId;
            const username = this.dataset.username;
            
            document.getElementById('deleteUserId').value = userId;
            document.getElementById('deleteUsername').textContent = username;
            deleteModal.show();
        });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>