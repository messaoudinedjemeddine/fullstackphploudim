<?php

require_once __DIR__ . '/../init.php';

use App\Controllers\CartController;
use App\Controllers\ProductController;
use function App\__;
use function App\format_currency;

// Get controller instances
$cartController = CartController::getInstance();
$productController = ProductController::getInstance();

// Get cart contents and total
$cartItems = $cartController->getCartContents();
$cartTotal = $cartController->calculateTotal();

// Get delivery cities for the order form
$deliveryCities = $productController->getAllDeliveryCities();

// Set page title
$pageTitle = __('your_cart');

include __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <h1 class="mb-4"><?= __('your_cart') ?></h1>

    <div x-data="{ 
        cartItems: <?= json_encode($cartItems) ?>, 
        cartTotal: <?= $cartTotal ?>, 
        deliveryFee: 0, 
        couponCode: '', 
        discountAmount: 0, 
        grandTotal: <?= $cartTotal ?>, 
        showCouponError: false, 
        wilayas: <?= json_encode($deliveryCities) ?>, 
        selectedWilayaCode: '', 
        deliveryTypes: ['home', 'desk'], 
        selectedDeliveryType: 'home', 
        pickupDesks: [], 
        selectedPickupDeskId: '',
        
        async updateCartItem(productId, size, quantity) {
            try {
                const response = await fetch('/api/update_cart_item.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ product_id: productId, size: size, quantity: quantity })
                });
                const data = await response.json();
                if (data.success) {
                    this.cartItems = data.cart_items;
                    this.cartTotal = data.cart_total;
                    this.updateGrandTotal();
                }
            } catch (error) {
                console.error('Error updating cart:', error);
            }
        },
        
        async removeCartItem(productId, size) {
            try {
                const response = await fetch('/api/remove_from_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ product_id: productId, size: size })
                });
                const data = await response.json();
                if (data.success) {
                    this.cartItems = data.cart_items;
                    this.cartTotal = data.cart_total;
                    this.updateGrandTotal();
                }
            } catch (error) {
                console.error('Error removing item:', error);
            }
        },
        
        async applyCoupon() {
            if (!this.couponCode) return;
            
            try {
                const response = await fetch('/api/apply_coupon.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ coupon_code: this.couponCode })
                });
                const data = await response.json();
                if (data.success) {
                    this.discountAmount = data.discount_amount;
                    this.showCouponError = false;
                    this.updateGrandTotal();
                } else {
                    this.showCouponError = true;
                    this.discountAmount = 0;
                    this.updateGrandTotal();
                }
            } catch (error) {
                console.error('Error applying coupon:', error);
            }
        },
        
        async updateDeliveryOptions() {
            if (!this.selectedWilayaCode) return;
            
            try {
                const response = await fetch(`/api/get_delivery_options.php?wilaya_code=${this.selectedWilayaCode}&delivery_type=${this.selectedDeliveryType}`);
                const data = await response.json();
                if (data.success) {
                    this.deliveryFee = data.delivery_fee;
                    this.pickupDesks = data.pickup_desks || [];
                    this.updateGrandTotal();
                }
            } catch (error) {
                console.error('Error fetching delivery options:', error);
            }
        },
        
        updateGrandTotal() {
            this.grandTotal = this.cartTotal - this.discountAmount + this.deliveryFee;
        }
    }">
        <div class="row">
            <!-- Cart Items -->
            <div class="col-lg-8">
                <template x-if="cartItems.length === 0">
                    <div class="alert alert-info">
                        <?= __('cart_empty') ?>
                        <a href="/pages/store.php" class="alert-link"><?= __('continue_shopping') ?></a>
                    </div>
                </template>

                <template x-if="cartItems.length > 0">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th><?= __('product') ?></th>
                                    <th><?= __('size') ?></th>
                                    <th><?= __('quantity') ?></th>
                                    <th><?= __('price') ?></th>
                                    <th><?= __('subtotal') ?></th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="item in cartItems" :key="item.productId + '_' + item.size">
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img :src="item.image" :alt="item.name" class="img-thumbnail me-3" style="width: 80px;">
                                                <div>
                                                    <h6 class="mb-0" x-text="item.name"></h6>
                                                    <small class="text-muted" x-text="'SKU: ' + item.sku"></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td x-text="item.size"></td>
                                        <td>
                                            <div class="input-group" style="width: 120px;">
                                                <button class="btn btn-outline-secondary" type="button" 
                                                        @click="updateCartItem(item.productId, item.size, item.quantity - 1)"
                                                        :disabled="item.quantity <= 1">-</button>
                                                <input type="number" class="form-control text-center" 
                                                       x-model="item.quantity" min="1" 
                                                       @change="updateCartItem(item.productId, item.size, item.quantity)">
                                                <button class="btn btn-outline-secondary" type="button" 
                                                        @click="updateCartItem(item.productId, item.size, item.quantity + 1)">+</button>
                                            </div>
                                        </td>
                                        <td x-text="format_currency(item.price)"></td>
                                        <td x-text="format_currency(item.price * item.quantity)"></td>
                                        <td>
                                            <button class="btn btn-link text-danger" 
                                                    @click="removeCartItem(item.productId, item.size)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </template>
            </div>

            <!-- Order Summary -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-4"><?= __('order_summary') ?></h5>

                        <!-- Coupon Section -->
                        <div class="mb-4">
                            <div class="input-group">
                                <input type="text" class="form-control" 
                                       x-model="couponCode" 
                                       :class="{ 'is-invalid': showCouponError }"
                                       placeholder="<?= __('enter_coupon') ?>">
                                <button class="btn btn-outline-primary" 
                                        @click="applyCoupon()"
                                        :disabled="!couponCode">
                                    <?= __('apply') ?>
                                </button>
                            </div>
                            <div class="invalid-feedback" x-show="showCouponError">
                                <?= __('invalid_coupon') ?>
                            </div>
                        </div>

                        <!-- Summary Details -->
                        <div class="d-flex justify-content-between mb-2">
                            <span><?= __('subtotal') ?></span>
                            <span x-text="format_currency(cartTotal)"></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2" x-show="discountAmount > 0">
                            <span><?= __('discount') ?></span>
                            <span class="text-success" x-text="'-' + format_currency(discountAmount)"></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span><?= __('delivery_fee') ?></span>
                            <span x-text="format_currency(deliveryFee)"></span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between fw-bold">
                            <span><?= __('total') ?></span>
                            <span x-text="format_currency(grandTotal)"></span>
                        </div>

                        <!-- Checkout Button -->
                        <button type="button" 
                                class="btn btn-primary w-100 mt-4"
                                @click="$dispatch('open-order-form-modal', { 
                                    currentCartTotal: grandTotal,
                                    wilayaCode: selectedWilayaCode,
                                    deliveryType: selectedDeliveryType
                                })"
                                :disabled="cartItems.length === 0">
                            <?= __('proceed_to_checkout') ?>
                        </button>
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
                    currentCartTotal: 0, 
                    grandTotal: 0, 
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
                                <template x-for="wilaya in wilayas" :key="wilaya.wilaya_code">
                                    <option :value="wilaya.wilaya_code" x-text="wilaya.name_<?= $_SESSION['lang'] ?>"></option>
                                </template>
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

<?php include __DIR__ . '/../includes/footer.php'; ?>