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

// Handle order status update
if (isset($_POST['update_order_status_submit'])) {
    $newStatus = $_POST['order_status'] ?? '';
    $observationNotes = $_POST['observation_notes'] ?? '';
    
    if ($orderController->updateOrderStatus($order['id'], $newStatus, $observationNotes)) {
        $_SESSION['flash_message'] = [
            'type' => 'success',
            'message' => __('order_status_updated')
        ];
    } else {
        $_SESSION['flash_message'] = [
            'type' => 'danger',
            'message' => __('error_updating_order_status')
        ];
    }
    redirect("order_detail.php?id={$order['id']}");
}

// Handle payment status update
if (isset($_POST['update_payment_status_submit'])) {
    $newPaymentStatus = $_POST['payment_status'] ?? '';
    
    if ($orderController->updateOrderPaymentStatus($order['id'], $newPaymentStatus)) {
        $_SESSION['flash_message'] = [
            'type' => 'success',
            'message' => __('payment_status_updated')
        ];
    } else {
        $_SESSION['flash_message'] = [
            'type' => 'danger',
            'message' => __('error_updating_payment_status')
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

                                <dt class="col-sm-4"><?= __('email') ?></dt>
                                <dd class="col-sm-8"><?= htmlspecialchars($order['email']) ?></dd>

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

                <!-- Order Status -->
                <div class="col-md-6">
                    <div class="card shadow h-100">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><?= __('order_status') ?></h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" class="mb-4">
                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                
                                <div class="mb-3">
                                    <label for="order_status" class="form-label"><?= __('order_status') ?></label>
                                    <select class="form-select" id="order_status" name="order_status" required>
                                        <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>><?= __('pending') ?></option>
                                        <option value="confirmed" <?= $order['status'] === 'confirmed' ? 'selected' : '' ?>><?= __('confirmed') ?></option>
                                        <option value="canceled" <?= $order['status'] === 'canceled' ? 'selected' : '' ?>><?= __('canceled') ?></option>
                                        <option value="no_answer" <?= $order['status'] === 'no_answer' ? 'selected' : '' ?>><?= __('no_answer') ?></option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="observation_notes" class="form-label"><?= __('observation_notes') ?></label>
                                    <textarea class="form-control" id="observation_notes" name="observation_notes" rows="3"><?= htmlspecialchars($order['observation_notes'] ?? '') ?></textarea>
                                </div>

                                <button type="submit" name="update_order_status_submit" class="btn btn-primary">
                                    <?= __('save_status') ?>
                                </button>
                            </form>

                            <hr>

                            <form method="POST">
                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                
                                <div class="mb-3">
                                    <label for="payment_status" class="form-label"><?= __('payment_status') ?></label>
                                    <select class="form-select" id="payment_status" name="payment_status" required>
                                        <option value="pending" <?= $order['payment_status'] === 'pending' ? 'selected' : '' ?>><?= __('pending') ?></option>
                                        <option value="paid" <?= $order['payment_status'] === 'paid' ? 'selected' : '' ?>><?= __('paid') ?></option>
                                        <option value="refunded" <?= $order['payment_status'] === 'refunded' ? 'selected' : '' ?>><?= __('refunded') ?></option>
                                    </select>
                                </div>

                                <button type="submit" name="update_payment_status_submit" class="btn btn-primary">
                                    <?= __('save_payment_status') ?>
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