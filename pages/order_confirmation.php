<?php
require_once '../includes/header.php';

$order_id = $_GET['order_id'] ?? 0;

if (!$order_id) {
    header('Location: index.php');
    exit;
}

// Fetch order details
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: index.php');
    exit;
}

// Fetch order items
$stmt = $pdo->prepare("
    SELECT oi.*, p.name as product_name, p.image 
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll();

// Clear cart after successful order
unset($_SESSION['cart']);
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="text-center mb-5">
                <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                <h2 class="text-success"><?php echo $lang['thank_you'] ?? 'Thank You!'; ?></h2>
                <p class="lead"><?php echo $lang['order_placed'] ?? 'Your order has been placed successfully'; ?></p>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5><?php echo $lang['order_confirmation'] ?? 'Order Confirmation'; ?></h5>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6><?php echo $lang['order_number'] ?? 'Order Number'; ?>:</h6>
                            <p class="h5 text-primary">#<?php echo $order['id']; ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6>Order Date:</h6>
                            <p><?php echo date('F j, Y \a\t g:i A', strtotime($order['created_at'])); ?></p>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6>Customer Information:</h6>
                            <p>
                                <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong><br>
                                <?php echo htmlspecialchars($order['phone']); ?><br>
                                <?php echo htmlspecialchars($order['address']); ?><br>
                                <?php echo htmlspecialchars($order['city']); ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6>Order Status:</h6>
                            <span class="badge bg-warning fs-6"><?php echo ucfirst($order['status']); ?></span>
                        </div>
                    </div>
                    
                    <h6>Order Items:</h6>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Size</th>
                                    <th>Quantity</th>
                                    <th>Price</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order_items as $item): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php if ($item['image']): ?>
                                                <img src="../public/images/products/<?php echo $item['image']; ?>" alt="Product" class="me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                            <?php endif; ?>
                                            <?php echo htmlspecialchars($item['product_name']); ?>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($item['size'] ?: 'N/A'); ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td>$<?php echo number_format($item['price'], 2); ?></td>
                                    <td>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="row justify-content-end">
                        <div class="col-md-4">
                            <table class="table table-sm">
                                <tr>
                                    <td><?php echo $lang['subtotal'] ?? 'Subtotal'; ?>:</td>
                                    <td class="text-end">$<?php echo number_format($order['subtotal'], 2); ?></td>
                                </tr>
                                <?php if ($order['delivery_fee'] > 0): ?>
                                <tr>
                                    <td><?php echo $lang['delivery_fee'] ?? 'Delivery Fee'; ?>:</td>
                                    <td class="text-end">$<?php echo number_format($order['delivery_fee'], 2); ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if ($order['discount'] > 0): ?>
                                <tr>
                                    <td><?php echo $lang['discount'] ?? 'Discount'; ?>:</td>
                                    <td class="text-end text-success">-$<?php echo number_format($order['discount'], 2); ?></td>
                                </tr>
                                <?php endif; ?>
                                <tr class="fw-bold">
                                    <td><?php echo $lang['total'] ?? 'Total'; ?>:</td>
                                    <td class="text-end">$<?php echo number_format($order['total'], 2); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-4">
                <a href="index.php" class="btn btn-primary"><?php echo $lang['continue_shopping'] ?? 'Continue Shopping'; ?></a>
                <button class="btn btn-outline-primary" onclick="window.print()">Print Order</button>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .navbar, .footer, .btn {
        display: none !important;
    }
}
</style>

<?php require_once '../includes/footer.php'; ?>