<?php
require_once __DIR__ . '/../init.php';

use App\Controllers\ProductController;
use App\Controllers\CartController;
use function App\__;
use function App\format_currency;

// Get controller instances
$productController = ProductController::getInstance();
$cartController = CartController::getInstance();

// Validate product_id
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: /pages/store.php');
    exit;
}

// Get product details
$product = $productController->getProductById($_GET['id']);

// If product not found, redirect to store
if (!$product) {
    header('Location: /pages/store.php');
    exit;
}

// Check if product is out of stock
$isProductOutOfStock = true;
foreach ($product['sizes'] as $size) {
    if ($size['quantity'] > 0) {
        $isProductOutOfStock = false;
        break;
    }
}

// Get delivery cities for the order form
$deliveryCities = $productController->getAllDeliveryCities();

// Set page title
$pageTitle = $product['name_' . $_SESSION['lang']];

include 'includes/header.php';
include 'includes/navbar.php';
?>

<div class="container py-5">
    <div class="row">
        <!-- Product Images -->
        <div class="col-md-6 mb-4" x-data="{ currentImage: '<?= $product['images'][0]['path'] ?? '/assets/images/placeholder.jpg' ?>' }">
            <div class="product-image-main mb-3">
                <img :src="currentImage" alt="<?= $product['name_' . $_SESSION['lang']] ?>" class="img-fluid">
            </div>
            <div class="product-thumbnails d-flex gap-2">
                <?php foreach ($product['images'] as $image): ?>
                    <div class="thumbnail" @click="currentImage = '<?= $image['path'] ?>'">
                        <img src="<?= $image['path'] ?>" alt="" class="img-fluid">
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Product Details -->
        <div class="col-md-6">
            <h1 class="mb-3"><?= $product['name_' . $_SESSION['lang']] ?></h1>
            <p class="text-muted mb-3"><?= __('sku') ?>: <?= $product['sku'] ?></p>

            <!-- Description -->
            <div class="mb-4">
                <?= $product['description_' . $_SESSION['lang']] ?>
            </div>

            <!-- Pricing -->
            <div class="product-price mb-4">
                <?php if ($product['discount_price']): ?>
                    <span class="old-price"><?= format_currency($product['price']) ?></span>
                    <span class="new-price"><?= format_currency($product['discount_price']) ?></span>
                    <span class="discount-badge">
                        -<?= round((($product['price'] - $product['discount_price']) / $product['price']) * 100) ?>%
                    </span>
                <?php else: ?>
                    <span class="price"><?= format_currency($product['price']) ?></span>
                <?php endif; ?>
            </div>

            <!-- Size Selection -->
            <div class="mb-4" x-data="{ selectedSize: null, isProductOutOfStock: <?= $isProductOutOfStock ? 'true' : 'false' ?> }">
                <h5 class="mb-3"><?= __('select_size') ?></h5>
                <div class="size-buttons d-flex gap-2 mb-2">
                    <?php foreach ($product['sizes'] as $size): ?>
                        <button type="button" 
                                class="btn btn-outline-primary"
                                :class="{ 'active': selectedSize === '<?= $size['size'] ?>' }"
                                :disabled="selectedSize === '<?= $size['size'] ?>' || <?= $size['quantity'] <= 0 ? 'true' : 'false' ?>"
                                @click="selectedSize = '<?= $size['size'] ?>'">
                            <?= $size['size'] ?>
                        </button>
                    <?php endforeach; ?>
                </div>

                <!-- Size Chart -->
                <div class="mb-4">
                    <button class="btn btn-link p-0" type="button" data-bs-toggle="collapse" data-bs-target="#sizeChart">
                        <?= __('size_chart') ?>
                    </button>
                    <div class="collapse mt-2" id="sizeChart">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th><?= __('size') ?></th>
                                        <th><?= __('chest_cm') ?></th>
                                        <th><?= __('waist_cm') ?></th>
                                        <th><?= __('hips_cm') ?></th>
                                        <th><?= __('length_cm') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>XS</td>
                                        <td>86-91</td>
                                        <td>66-71</td>
                                        <td>91-96</td>
                                        <td>64</td>
                                    </tr>
                                    <tr>
                                        <td>S</td>
                                        <td>91-96</td>
                                        <td>71-76</td>
                                        <td>96-101</td>
                                        <td>65</td>
                                    </tr>
                                    <tr>
                                        <td>M</td>
                                        <td>96-101</td>
                                        <td>76-81</td>
                                        <td>101-106</td>
                                        <td>66</td>
                                    </tr>
                                    <tr>
                                        <td>L</td>
                                        <td>101-106</td>
                                        <td>81-86</td>
                                        <td>106-111</td>
                                        <td>67</td>
                                    </tr>
                                    <tr>
                                        <td>XL</td>
                                        <td>106-111</td>
                                        <td>86-91</td>
                                        <td>111-116</td>
                                        <td>68</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Payment Options -->
                <div class="mb-4">
                    <button type="button" class="btn btn-link p-0" data-bs-toggle="modal" data-bs-target="#paymentOptionsModal">
                        <?= __('payment_options') ?>
                    </button>
                </div>

                <!-- Action Buttons -->
                <div class="d-grid gap-2">
                    <button type="button" 
                            class="btn btn-primary"
                            :disabled="!selectedSize || isProductOutOfStock"
                            @click="$dispatch('open-cart-modal', { productId: <?= $product['id'] ?>, selectedSize: selectedSize })">
                        <?= __('add_to_cart') ?>
                    </button>
                    <button type="button" 
                            class="btn btn-success"
                            :disabled="!selectedSize || isProductOutOfStock"
                            @click="$dispatch('open-order-form-modal', { productId: <?= $product['id'] ?>, selectedSize: selectedSize })">
                        <?= __('buy_now') ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Payment Options Modal -->
<div class="modal fade" id="paymentOptionsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= __('payment_options') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="payment-methods">
                    <div class="payment-method mb-3">
                        <h6><?= __('cash_on_delivery') ?></h6>
                        <p class="text-muted"><?= __('cash_on_delivery_desc') ?></p>
                    </div>
                    <div class="payment-method mb-3">
                        <h6><?= __('cib') ?></h6>
                        <p class="text-muted"><?= __('cib_desc') ?></p>
                    </div>
                    <div class="payment-method mb-3">
                        <h6><?= __('edahabia') ?></h6>
                        <p class="text-muted"><?= __('edahabia_desc') ?></p>
                    </div>
                    <div class="payment-method">
                        <h6><?= __('bank_transfer') ?></h6>
                        <p class="text-muted"><?= __('bank_transfer_desc') ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Order Form Modal -->
<div class="modal fade" id="orderFormModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= __('complete_order') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="orderForm" x-data="{ 
                    wilayaCode: '', 
                    deliveryType: 'home', 
                    deliveryFee: 0, 
                    currentCartTotal: <?= $product['discount_price'] ?? $product['price'] ?>, 
                    grandTotal: <?= $product['discount_price'] ?? $product['price'] ?>, 
                    pickupDesks: [], 
                    selectedPickupDeskId: '',
                    async updateDeliveryOptions() {
                        if (!this.wilayaCode) return;
                        try {
                            const response = await fetch(`/api/get_delivery_options.php?wilaya_code=${this.wilayaCode}&delivery_type=${this.deliveryType}`);
                            const data = await response.json();
                            if (data.success) {
                                this.deliveryFee = data.delivery_fee;
                                this.pickupDesks = data.pickup_desks || [];
                                this.grandTotal = this.currentCartTotal + this.deliveryFee;
                            }
                        } catch (error) {
                            console.error('Error fetching delivery options:', error);
                        }
                    }
                }">
                    <div class="row g-3">
                        <!-- Personal Information -->
                        <div class="col-md-6">
                            <label class="form-label"><?= __('full_name') ?></label>
                            <input type="text" class="form-control" name="full_name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?= __('phone') ?></label>
                            <input type="tel" class="form-control" name="phone" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?= __('email') ?></label>
                            <input type="email" class="form-control" name="email">
                        </div>

                        <!-- Delivery Information -->
                        <div class="col-md-6">
                            <label class="form-label"><?= __('wilaya') ?></label>
                            <select class="form-select" name="wilaya_code" x-model="wilayaCode" @change="updateDeliveryOptions()" required>
                                <option value=""><?= __('select_wilaya') ?></option>
                                <?php foreach ($deliveryCities as $city): ?>
                                    <option value="<?= $city['wilaya_code'] ?>">
                                        <?= $city['name_' . $_SESSION['lang']] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Delivery Type -->
                        <div class="col-12">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="delivery_type" 
                                       value="home" x-model="deliveryType" @change="updateDeliveryOptions()" required>
                                <label class="form-check-label"><?= __('home_delivery') ?></label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="delivery_type" 
                                       value="desk" x-model="deliveryType" @change="updateDeliveryOptions()" required>
                                <label class="form-check-label"><?= __('pickup_desk') ?></label>
                            </div>
                        </div>

                        <!-- Address or Pickup Desk -->
                        <div class="col-12" x-show="deliveryType === 'home'">
                            <label class="form-label"><?= __('address') ?></label>
                            <textarea class="form-control" name="address" rows="3" required></textarea>
                        </div>

                        <div class="col-12" x-show="deliveryType === 'desk'">
                            <label class="form-label"><?= __('pickup_desk') ?></label>
                            <select class="form-select" name="pickup_desk_id" x-model="selectedPickupDeskId" required>
                                <option value=""><?= __('select_pickup_desk') ?></option>
                                <template x-for="desk in pickupDesks" :key="desk.id">
                                    <option :value="desk.id" x-text="desk.name"></option>
                                </template>
                            </select>
                        </div>

                        <!-- Order Summary -->
                        <div class="col-12 mt-4">
                            <h6><?= __('order_summary') ?></h6>
                            <div class="d-flex justify-content-between mb-2">
                                <span><?= __('subtotal') ?></span>
                                <span x-text="format_currency(currentCartTotal)"></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span><?= __('delivery_fee') ?></span>
                                <span x-text="format_currency(deliveryFee)"></span>
                            </div>
                            <div class="d-flex justify-content-between fw-bold">
                                <span><?= __('total') ?></span>
                                <span x-text="format_currency(grandTotal)"></span>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= __('cancel') ?></button>
                <button type="submit" form="orderForm" class="btn btn-primary"><?= __('place_order') ?></button>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>