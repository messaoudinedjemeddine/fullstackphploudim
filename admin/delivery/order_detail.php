<?php
require_once __DIR__ . '/../../init.php';

use App\Auth;
use App\Controllers\OrderController;
use function App\__;
use function App\formatPrice;
use function App\formatDate;

// Check if user is logged in and is a delivery agent or super admin
if (!Auth::check() || (!Auth::checkRole('delivery_agent') && !Auth::checkRole('super_admin'))) {
    $_SESSION['flash_message'] = [
        'type' => 'danger',
        'message' => __('unauthorized_access')
    ];
    redirect('/admin/index.php');
}

// Check if order ID is provided
if (!isset($_GET['id'])) {
    $_SESSION['flash_message'] = [
        'type' => 'danger',
        'message' => __('order_not_found')
    ];
    redirect('orders.php');
}

// Initialize OrderController
$orderController = new OrderController();

// Get order details
$order = $orderController->getOrderById($_GET['id']);
if (!$order) {
    $_SESSION['flash_message'] = [
        'type' => 'danger',
        'message' => __('order_not_found')
    ];
    redirect('orders.php');
}

// Handle delivery status update
if (isset($_POST['update_delivery_status_submit'])) {
    $newStatus = $_POST['order_status'] ?? '';
    $deliveryNote = $_POST['delivery_note'] ?? '';
    
    if ($orderController->updateOrderStatus($order['id'], $newStatus, null, $deliveryNote)) {
        $_SESSION['flash_message'] = [
            'type' => 'success',
            'message' => __('delivery_status_updated')
        ];
    } else {
        $_SESSION['flash_message'] = [
            'type' => 'danger',
            'message' => __('error_updating_delivery_status')
        ];
    }
    redirect("order_detail.php?id={$order['id']}");
}

$pageTitle = __('order_details') . ' #' . $order['id'];
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?= $pageTitle ?></h1>
                <a href="orders.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> <?= __('back_to_orders') ?>
                </a>
            </div>

            <?php if (isset($_SESSION['flash_message'])): ?>
                <div class="alert alert-<?= $_SESSION['flash_message']['type'] ?> alert-dismissible fade show" role="alert">
                    <?= $_SESSION['flash_message']['message'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['flash_message']); ?>
            <?php endif; ?>

            <div class="row g-3">
                <!-- Client Information -->
                <div class="col-md-6">
                    <div class="card shadow h-100">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><?= __('client_information') ?></h5>
                        </div>
                        <div class="card-body">
                            <dl class="row mb-0">
                                <dt class="col-sm-4"><?= __('full_name') ?></dt>
                                <dd class="col-sm-8"><?= htmlspecialchars($order['client_name']) ?></dd>

                                <dt class="col-sm-4"><?= __('phone') ?></dt>
                                <dd class="col-sm-8"><?= htmlspecialchars($order['phone']) ?></dd>

                                <dt class="col-sm-4"><?= __('wilaya') ?></dt>
                                <dd class="col-sm-8"><?= htmlspecialchars($order['wilaya_name']) ?></dd>

                                <dt class="col-sm-4"><?= __('delivery_type') ?></dt>
                                <dd class="col-sm-8"><?= __($order['delivery_type']) ?></dd>

                                <dt class="col-sm-4"><?= $order['delivery_type'] === 'home' ? __('address') : __('pickup_desk') ?></dt>
                                <dd class="col-sm-8"><?= htmlspecialchars($order['delivery_type'] === 'home' ? $order['address'] : $order['desk_name']) ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <!-- Delivery Status -->
                <div class="col-md-6">
                    <div class="card shadow h-100">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><?= __('delivery_status') ?></h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                
                                <div class="mb-3">
                                    <label for="order_status" class="form-label"><?= __('delivery_status') ?></label>
                                    <select class="form-select" id="order_status" name="order_status" required>
                                        <option value="ready" <?= $order['status'] === 'ready' ? 'selected' : '' ?>><?= __('ready') ?></option>
                                        <option value="not_ready" <?= $order['status'] === 'not_ready' ? 'selected' : '' ?>><?= __('not_ready') ?></option>
                                        <option value="delivered" <?= $order['status'] === 'delivered' ? 'selected' : '' ?>><?= __('delivered') ?></option>
                                        <option value="returned" <?= $order['status'] === 'returned' ? 'selected' : '' ?>><?= __('returned') ?></option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="delivery_note" class="form-label"><?= __('delivery_note') ?></label>
                                    <textarea class="form-control" id="delivery_note" name="delivery_note" rows="3"><?= htmlspecialchars($order['delivery_note'] ?? '') ?></textarea>
                                </div>

                                <button type="submit" name="update_delivery_status_submit" class="btn btn-primary">
                                    <?= __('save_delivery_status') ?>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Order Items -->
                <div class="col-12">
                    <div class="card shadow">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><?= __('order_items') ?></h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th><?= __('product_name') ?></th>
                                            <th><?= __('size') ?></th>
                                            <th><?= __('quantity') ?></th>
                                            <th><?= __('price') ?></th>
                                            <th><?= __('subtotal') ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($order['items'] as $item): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($item['product_name']) ?></td>
                                                <td><?= htmlspecialchars($item['size']) ?></td>
                                                <td><?= $item['quantity'] ?></td>
                                                <td><?= formatPrice($item['price']) ?></td>
                                                <td><?= formatPrice($item['price'] * $item['quantity']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="4" class="text-end"><strong><?= __('subtotal') ?></strong></td>
                                            <td><?= formatPrice($order['subtotal']) ?></td>
                                        </tr>
                                        <tr>
                                            <td colspan="4" class="text-end"><strong><?= __('delivery_fee') ?></strong></td>
                                            <td><?= formatPrice($order['delivery_fee']) ?></td>
                                        </tr>
                                        <?php if ($order['discount'] > 0): ?>
                                            <tr>
                                                <td colspan="4" class="text-end"><strong><?= __('discount') ?></strong></td>
                                                <td>-<?= formatPrice($order['discount']) ?></td>
                                            </tr>
                                        <?php endif; ?>
                                        <tr>
                                            <td colspan="4" class="text-end"><strong><?= __('grand_total') ?></strong></td>
                                            <td><strong><?= formatPrice($order['total']) ?></strong></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>