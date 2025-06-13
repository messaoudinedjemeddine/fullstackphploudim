<?php
require_once __DIR__ . '/../../init.php';

use App\Auth;
use App\Controllers\OrderController;
use function App\__;
use function App\formatPrice;
use function App\formatDate;

// Check if user is logged in and is a call agent or super admin
if (!Auth::check() || (!Auth::checkRole('call_agent') && !Auth::checkRole('super_admin'))) {
    $_SESSION['flash_message'] = [
        'type' => 'danger',
        'message' => __('unauthorized_access')
    ];
    redirect('/admin/index.php');
}

// Initialize OrderController
$orderController = new OrderController();

// Get orders with status 'pending' or 'no_answer'
$orders = $orderController->getOrders(['status' => ['pending', 'no_answer']]);

$pageTitle = __('view_new_orders');
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

            <div class="card shadow">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th><?= __('order_id') ?></th>
                                    <th><?= __('client_name') ?></th>
                                    <th><?= __('phone') ?></th>
                                    <th><?= __('wilaya') ?></th>
                                    <th><?= __('total_amount') ?></th>
                                    <th><?= __('order_status') ?></th>
                                    <th><?= __('actions') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($orders)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center"><?= __('no_orders_found') ?></td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td>#<?= $order['id'] ?></td>
                                            <td><?= htmlspecialchars($order['client_name']) ?></td>
                                            <td><?= htmlspecialchars($order['phone']) ?></td>
                                            <td><?= htmlspecialchars($order['wilaya_name']) ?></td>
                                            <td><?= formatPrice($order['total']) ?></td>
                                            <td>
                                                <span class="badge bg-<?= $order['status'] === 'pending' ? 'warning' : 'secondary' ?>">
                                                    <?= __($order['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="order_detail.php?id=<?= $order['id'] ?>" 
                                                   class="btn btn-sm btn-primary">
                                                    <?= __('view_details') ?>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>