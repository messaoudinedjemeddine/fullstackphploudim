<?php
require_once __DIR__ . '/../../init.php';

use App\Auth;
use App\Controllers\OrderController;
use function App\__;
use function App\formatPrice;

// Check if user is logged in and is a super admin
if (!Auth::check() || !Auth::checkRole('super_admin')) {
    $_SESSION['flash_message'] = [
        'type' => 'danger',
        'message' => __('unauthorized_access')
    ];
    redirect('/admin/index.php');
}

// Initialize OrderController using singleton pattern
$orderController = OrderController::getInstance();

// Get statistics data
$stats = $orderController->getStatistics();

$pageTitle = __('statistics_dashboard');
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?= $pageTitle ?></h1>
            </div>

            <!-- Key Metrics Cards -->
            <div class="row g-3 mb-4">
                <!-- Total Orders -->
                <div class="col-md-3">
                    <div class="card bg-primary text-white h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?= __('total_orders') ?></h5>
                            <h2 class="card-text"><?= array_sum($stats['orders_by_status']) ?></h2>
                        </div>
                    </div>
                </div>

                <!-- Total Revenue -->
                <div class="col-md-3">
                    <div class="card bg-success text-white h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?= __('total_revenue') ?></h5>
                            <h2 class="card-text"><?= formatPrice(array_sum(array_column($stats['revenue_monthly'], 'revenue'))) ?></h2>
                        </div>
                    </div>
                </div>

                <!-- Pending Orders -->
                <div class="col-md-3">
                    <div class="card bg-warning text-dark h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?= __('pending_orders') ?></h5>
                            <h2 class="card-text"><?= $stats['orders_by_status']['pending'] ?? 0 ?></h2>
                        </div>
                    </div>
                </div>

                <!-- Delivered Orders -->
                <div class="col-md-3">
                    <div class="card bg-info text-white h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?= __('delivered_orders') ?></h5>
                            <h2 class="card-text"><?= $stats['orders_by_status']['delivered'] ?? 0 ?></h2>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <!-- Orders by Status Chart -->
                <div class="col-md-6">
                    <div class="card shadow h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?= __('orders_by_status') ?></h5>
                            <canvas id="ordersStatusChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Monthly Revenue Chart -->
                <div class="col-md-6">
                    <div class="card shadow h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?= __('monthly_revenue') ?></h5>
                            <canvas id="revenueChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Top Selling Products -->
                <div class="col-12">
                    <div class="card shadow">
                        <div class="card-body">
                            <h5 class="card-title"><?= __('top_selling_products') ?></h5>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th><?= __('product_name') ?></th>
                                            <th><?= __('quantity_sold') ?></th>
                                            <th><?= __('total_revenue') ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($stats['top_selling_products'] as $product): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($product['name']) ?></td>
                                                <td><?= $product['quantity_sold'] ?></td>
                                                <td><?= formatPrice($product['total_revenue']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Orders by Status Chart
    new Chart(document.getElementById('ordersStatusChart'), {
        type: 'pie',
        data: {
            labels: <?= json_encode(array_keys($stats['orders_by_status'])) ?>,
            datasets: [{
                data: <?= json_encode(array_values($stats['orders_by_status'])) ?>,
                backgroundColor: [
                    '#ffc107', // Pending
                    '#0dcaf0', // Confirmed
                    '#198754', // Delivered
                    '#dc3545'  // Cancelled
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Monthly Revenue Chart
    new Chart(document.getElementById('revenueChart'), {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column($stats['revenue_monthly'], 'month')) ?>,
            datasets: [{
                label: '<?= __('revenue') ?>',
                data: <?= json_encode(array_column($stats['revenue_monthly'], 'revenue')) ?>,
                borderColor: '#198754',
                tension: 0.1,
                fill: false
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '<?= CURRENCY_SYMBOL ?>' + value;
                        }
                    }
                }
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>