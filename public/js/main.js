// --- Custom Cursor Logic ---
document.addEventListener('DOMContentLoaded', () => {
    // Custom cursor elements (if present)
    const cursorDot = document.getElementById('custom-cursor-dot');
    const cursorOutline = document.getElementById('custom-cursor-outline');
    let mouseX = 0, mouseY = 0, cursorDotX = 0, cursorDotY = 0, cursorOutlineX = 0, cursorOutlineY = 0;
    if (cursorDot && cursorOutline) {
        window.addEventListener('mousemove', (e) => {
            mouseX = e.clientX;
            mouseY = e.clientY;
        });
        function animateCursors() {
            cursorDotX += (mouseX - cursorDotX) * 0.2;
            cursorDotY += (mouseY - cursorDotY) * 0.2;
            cursorDot.style.left = `${cursorDotX}px`;
            cursorDot.style.top = `${cursorDotY}px`;
            cursorOutlineX += (mouseX - cursorOutlineX) * 0.1;
            cursorOutlineY += (mouseY - cursorOutlineY) * 0.1;
            cursorOutline.style.left = `${cursorOutlineX}px`;
            cursorOutline.style.top = `${cursorOutlineY}px`;
            requestAnimationFrame(animateCursors);
        }
        animateCursors();
        // Hover effect
        const interactiveElements = document.querySelectorAll(
            'a, button, .btn, input[type="submit"], input[type="button"], input[type="text"], input[type="email"], textarea, select, [data-bs-toggle="modal"], [data-bs-toggle="collapse"], .product-card'
        );
        interactiveElements.forEach(element => {
            element.addEventListener('mouseenter', () => {
                cursorDot.classList.add('hovered');
                cursorOutline.classList.add('hovered');
            });
            element.addEventListener('mouseleave', () => {
                cursorDot.classList.remove('hovered');
                cursorOutline.classList.remove('hovered');
            });
        });
    }

    // --- Hero Section Animated Text (Basic Cycling) ---
    const heroTextElement = document.querySelector('.hero-animated-text');
    if (heroTextElement) {
        const phrases = [];
        if (heroTextElement.dataset.phrase1) phrases.push(heroTextElement.dataset.phrase1);
        if (heroTextElement.dataset.phrase2) phrases.push(heroTextElement.dataset.phrase2);
        let currentPhraseIndex = 0;
        function cycleHeroText() {
            if (phrases.length === 0) return;
            // Reset animation
            heroTextElement.style.animation = 'none';
            void heroTextElement.offsetWidth;
            heroTextElement.style.animation = null;
            heroTextElement.textContent = phrases[currentPhraseIndex];
            currentPhraseIndex = (currentPhraseIndex + 1) % phrases.length;
        }
        if (phrases.length > 0) {
            heroTextElement.textContent = phrases[0];
            setInterval(cycleHeroText, 8000);
        }
    }
});

// --- General AJAX Helper ---
async function sendAjaxRequest(url, method, data = null) {
    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
    };
    if (data) {
        options.body = JSON.stringify(data);
    }
    try {
        const response = await fetch(url, options);
        const jsonResponse = await response.json();
        if (!response.ok) {
            const error = new Error(jsonResponse.message || 'An unknown error occurred');
            error.status = response.status;
            error.response = jsonResponse;
            throw error;
        }
        return jsonResponse;
    } catch (error) {
        console.error('AJAX request failed:', error);
        throw error;
    }
}

// --- Alpine.js Related Global Functions ---
window.updateDeliveryOptions = async function() {
    const wilayaCode = this.selectedWilayaCode;
    const deliveryType = this.selectedDeliveryType;
    if (!wilayaCode || !deliveryType) {
        this.deliveryFee = 0;
        this.grandTotal = this.currentCartTotal;
        this.pickupDesks = [];
        this.selectedPickupDeskId = '';
        return;
    }
    try {
        const response = await sendAjaxRequest(
            '../api/get_delivery_options.php?wilaya_code=' + wilayaCode + '&delivery_type=' + deliveryType,
            'GET'
        );
        if (response.success) {
            this.deliveryFee = (deliveryType === 'home') ? parseFloat(response.home_fee) : parseFloat(response.desk_fee);
            this.pickupDesks = (deliveryType === 'desk') ? response.pickup_desks : [];
            if (deliveryType === 'desk' && this.pickupDesks.length > 0 && !this.selectedPickupDeskId) {
                this.selectedPickupDeskId = this.pickupDesks[0].id;
            } else if (deliveryType === 'desk' && this.pickupDesks.length === 0) {
                this.selectedPickupDeskId = '';
            } else if (deliveryType === 'home') {
                this.selectedPickupDeskId = '';
            }
            this.grandTotal = this.currentCartTotal + this.deliveryFee;
        } else {
            this.deliveryFee = 0;
            this.grandTotal = this.currentCartTotal;
            this.pickupDesks = [];
            this.selectedPickupDeskId = '';
        }
    } catch (error) {
        this.deliveryFee = 0;
        this.grandTotal = this.currentCartTotal;
        this.pickupDesks = [];
        this.selectedPickupDeskId = '';
    }
};

window.updateCartItem = async function(productId, size, newQuantity) {
    if (newQuantity < 0) newQuantity = 0;
    try {
        const response = await sendAjaxRequest(
            '../api/update_cart_item.php',
            'POST',
            { productId, size, newQuantity }
        );
        if (response.success) {
            const itemIndex = this.cartItems.findIndex(item => item.product_id == productId && item.size == size);
            if (itemIndex !== -1) {
                if (newQuantity === 0) {
                    this.cartItems.splice(itemIndex, 1);
                } else {
                    this.cartItems[itemIndex].quantity = newQuantity;
                }
            }
            this.cartTotal = response.cart_total;
            this.grandTotal = response.grand_total;
        }
    } catch (error) {
        // Optionally show error to user
    }
};

window.removeCartItem = async function(productId, size) {
    try {
        const response = await sendAjaxRequest(
            '../api/remove_from_cart.php',
            'POST',
            { productId, size }
        );
        if (response.success) {
            this.cartItems = this.cartItems.filter(item => !(item.product_id == productId && item.size == size));
            this.cartTotal = response.cart_total;
            this.grandTotal = response.grand_total;
        }
    } catch (error) {
        // Optionally show error to user
    }
};

window.applyCoupon = async function() {
    if (!this.couponCode) return;
    try {
        const response = await sendAjaxRequest(
            '../api/apply_coupon.php',
            'POST',
            { coupon_code: this.couponCode }
        );
        if (response.success) {
            this.discountAmount = response.discount_amount;
            this.showCouponError = false;
            this.grandTotal = this.cartTotal - this.discountAmount + (this.deliveryFee || 0);
        } else {
            this.showCouponError = true;
            this.discountAmount = 0;
            this.grandTotal = this.cartTotal + (this.deliveryFee || 0);
        }
    } catch (error) {
        this.showCouponError = true;
        this.discountAmount = 0;
        this.grandTotal = this.cartTotal + (this.deliveryFee || 0);
    }
};

window.submitOrderForm = async function(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    const data = {};
    for (const [key, value] of formData.entries()) {
        data[key] = value;
    }
    // Assume cartItems is available in Alpine.js context
    data.cart_items = this.cartItems || [];
    try {
        const response = await sendAjaxRequest(
            '../api/process_order.php',
            'POST',
            data
        );
        if (response.success) {
            alert('Order placed successfully!');
            window.location.href = 'order_confirmation.php?order_id=' + response.order_id;
        } else {
            alert(response.error_message || 'Order failed.');
        }
    } catch (error) {
        alert(error.message || 'Order failed.');
    }
}; 