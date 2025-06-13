document.addEventListener('DOMContentLoaded', () => {

    // --- Custom Cursor Logic ---
    const cursorDot = document.getElementById('custom-cursor-dot');
    const cursorOutline = document.getElementById('custom-cursor-outline');

    let mouseX = 0;
    let mouseY = 0;
    let cursorDotX = 0;
    let cursorDotY = 0;
    let cursorOutlineX = 0;
    let cursorOutlineY = 0;

    // Store mouse coordinates
    window.addEventListener('mousemove', (e) => {
        mouseX = e.clientX;
        mouseY = e.clientY;
    });

    // Animation loop for smooth cursor movement
    function animateCursors() {
        // Smoothly move the inner dot
        cursorDotX += (mouseX - cursorDotX) * 0.2; // Adjust 0.2 for smoothness
        cursorDotY += (mouseY - cursorDotY) * 0.2;
        cursorDot.style.left = `${cursorDotX}px`;
        cursorDot.style.top = `${cursorDotY}px`;

        // Smoothly move the outer outline (with more delay)
        cursorOutlineX += (mouseX - cursorOutlineX) * 0.1; // Adjust 0.1 for more delay
        cursorOutlineY += (mouseY - cursorOutlineY) * 0.1;
        cursorOutline.style.left = `${cursorOutlineX}px`;
        cursorOutline.style.top = `${cursorOutlineY}px`;

        requestAnimationFrame(animateCursors);
    }

    animateCursors(); // Start the animation loop

    // Cursor hover effect logic
    const interactiveElements = document.querySelectorAll(
        'a, button, .btn, input[type="submit"], input[type="button"], input[type="text"], input[type="email"], textarea, select, [data-bs-toggle="modal"], [data-bs-toggle="collapse"], .product-card'
        // Add more specific selectors for elements you want the cursor to react to
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

    // --- Hero Section Animated Text (Basic Cycling) ---
    const heroTextElement = document.querySelector('.hero-animated-text');
    if (heroTextElement) {
        // Get translated phrases from data-attributes set in index.php
        const phrases = [];
        if (heroTextElement.dataset.phrase1) phrases.push(heroTextElement.dataset.phrase1);
        if (heroTextElement.dataset.phrase2) phrases.push(heroTextElement.dataset.phrase2);
        // Add more phrases if you add more data-phrase-X attributes

        let currentPhraseIndex = 0;

        function cycleHeroText() {
            if (phrases.length === 0) return;

            // Reset animation to trigger it again
            heroTextElement.style.animation = 'none';
            void heroTextElement.offsetWidth; // Trigger reflow
            heroTextElement.style.animation = null; // Re-apply original animation styles

            heroTextElement.textContent = phrases[currentPhraseIndex];
            currentPhraseIndex = (currentPhraseIndex + 1) % phrases.length;
        }

        // Initialize with first phrase and start cycling after animation completes once
        // The CSS animation handles the initial fade-in.
        if (phrases.length > 0) {
            heroTextElement.textContent = phrases[0];
            // Start cycling after the initial animation duration (8 seconds)
            // You might adjust this or use animationend event for precise timing
            setInterval(cycleHeroText, 8000); 
        }
    }


    // --- General AJAX Helper ---

    /**
     * Sends an AJAX request.
     * @param {string} url The API endpoint URL.
     * @param {string} method HTTP method (GET, POST).
     * @param {object} [data=null] Data to send (for POST requests).
     * @returns {Promise<object>} Promise resolving with JSON response.
     */
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
            const jsonResponse = await response.json(); // Always try to parse JSON
            
            if (!response.ok) {
                // If HTTP status is not 2xx, throw an error with more details
                const error = new Error(jsonResponse.message || 'An unknown error occurred');
                error.status = response.status;
                error.response = jsonResponse;
                throw error;
            }
            return jsonResponse;
        } catch (error) {
            console.error('AJAX request failed:', error);
            // Re-throw or return a structured error for the caller to handle
            throw error; 
        }
    }


    // --- Alpine.js related functions (Dispatched events from product.php, cart.php) ---

    // Example for `updateDeliveryOptions()` used on product.php & cart.php
    // This function needs to be globally available or set on window object if not directly in Alpine x-data
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
                `../api/get_delivery_options.php?wilaya_code=${wilayaCode}`, 
                'GET'
            );

            if (response.success) {
                this.deliveryFee = (deliveryType === 'home') ? parseFloat(response.home_fee) : parseFloat(response.desk_fee);
                this.pickupDesks = (deliveryType === 'desk') ? response.pickup_desks : [];
                // If switching to desk but no desks, reset selected desk
                if (deliveryType === 'desk' && this.pickupDesks.length > 0 && !this.selectedPickupDeskId) {
                    this.selectedPickupDeskId = this.pickupDesks[0].id; // Auto-select first desk
                } else if (deliveryType === 'desk' && this.pickupDesks.length === 0) {
                     this.selectedPickupDeskId = ''; // No desks available
                } else if (deliveryType === 'home') {
                    this.selectedPickupDeskId = ''; // Clear desk if home delivery
                }
                
                this.grandTotal = this.currentCartTotal + this.deliveryFee;
            } else {
                console.error('Failed to get delivery options:', response.message);
                this.deliveryFee = 0;
                this.grandTotal = this.currentCartTotal;
                this.pickupDesks = [];
                this.selectedPickupDeskId = '';
            }
        } catch (error) {
            console.error('Error fetching delivery options:', error);
            this.deliveryFee = 0;
            this.grandTotal = this.currentCartTotal;
            this.pickupDesks = [];
            this.selectedPickupDeskId = '';
        }
    };

    // Example for `updateCartItem()` used on cart.php
    window.updateCartItem = async function(productId, size, newQuantity) {
        if (newQuantity < 0) newQuantity = 0; // Prevent negative quantity

        try {
            const response = await sendAjaxRequest(
                '../api/update_cart_item.php',
                'POST',
                { productId: productId, size: size, newQuantity: newQuantity }
            );

            if (response.success) {
                // Find and update the item in the local Alpine.js cartItems array
                const itemIndex = this.cartItems.findIndex(item => item.product_id == productId && item.size == size);
                if (itemIndex !== -1) {
                    if (newQuantity === 0) {
                        this.cartItems.splice(itemIndex, 1); // Remove item if quantity is 0
                    } else {
                        this.cartItems[itemIndex].quantity = newQuantity;
                        // Assuming your API returns updated subtotal for the item
                        // this.cartItems[itemIndex].subtotal = response.updated_subtotal; 
                    }
                }
                this.cartTotal = response.new_cart_total;
                // Re-calculate grand total, also re-apply coupon and delivery if present
                this.grandTotal = this.cartTotal + this.deliveryFee - this.discountAmount;

                // Optionally, re-fetch full cart from API if local update is complex
                // await window.fetchCart(); 

            } else {
                alert('Failed to update cart: ' + response.message);
                // Revert UI quantity if update failed
            }
        } catch (error) {
            alert('Error updating cart: ' + error.message);
        }
    };

    // Example for `removeCartItem()` used on cart.php
    window.removeCartItem = async function(productId, size) {
        if (!confirm('Are you sure you want to remove this item?')) return;

        try {
            const response = await sendAjaxRequest(
                '../api/remove_from_cart.php',
                'POST',
                { productId: productId, size: size }
            );

            if (response.success) {
                this.cartItems = this.cartItems.filter(item => !(item.product_id == productId && item.size == size));
                this.cartTotal = response.new_cart_total;
                this.grandTotal = this.cartTotal + this.deliveryFee - this.discountAmount;
                // Optionally, re-fetch full cart from API if local update is complex
                // await window.fetchCart(); 
            } else {
                alert('Failed to remove item: ' + response.message);
            }
        } catch (error) {
            alert('Error removing item: ' + error.message);
        }
    };

    // Example for `applyCoupon()` used on cart.php
    window.applyCoupon = async function() {
        this.showCouponError = false; // Reset error message

        try {
            const response = await sendAjaxRequest(
                '../api/apply_coupon.php',
                'POST',
                { coupon_code: this.couponCode, cart_total: this.cartTotal }
            );

            if (response.success) {
                this.discountAmount = parseFloat(response.discount_amount);
                this.grandTotal = this.cartTotal + this.deliveryFee - this.discountAmount;
                alert('Coupon applied successfully!');
            } else {
                this.showCouponError = true;
                alert('Coupon error: ' + response.message);
                this.discountAmount = 0; // Reset discount if error
                this.grandTotal = this.cartTotal + this.deliveryFee; // Re-calculate without discount
            }
        } catch (error) {
            this.showCouponError = true;
            alert('Error applying coupon: ' + error.message);
            this.discountAmount = 0;
            this.grandTotal = this.cartTotal + this.deliveryFee;
        }
    };

    // Example for `submitOrderForm()` used on product.php (Buy Now modal) and cart.php (Checkout modal)
    window.submitOrderForm = async function(event) {
        event.preventDefault(); // Prevent default form submission

        const form = event.target;
        const formData = new FormData(form);
        const data = {};
        for (const [key, value] of formData.entries()) {
            data[key] = value;
        }
        
        // Add cart items from Alpine.js state or passed from product page
        if (this.cartItems) { // From cart.php
            data.cart_items = this.cartItems;
        } else if (this.productId && this.selectedSize) { // From product.php 'Buy Now'
            data.cart_items = [{
                product_id: this.productId,
                size: this.selectedSize,
                quantity: 1 // Assuming 1 for Buy Now
            }];
        } else {
            alert("No items in cart for order.");
            return;
        }

        data.wilaya_code = this.selectedWilayaCode;
        data.delivery_type = this.selectedDeliveryType;
        data.pickup_desk_id = this.selectedPickupDeskId || null;
        data.coupon_code = this.couponCode || null;


        try {
            const response = await sendAjaxRequest(
                '../api/process_order.php',
                'POST',
                data
            );

            if (response.success) {
                alert('Order placed successfully! Order ID: ' + response.order_id);
                // Store order ID in session or pass via URL for confirmation page
                window.location.href = `order_confirmation.php?order_id=${response.order_id}`;
            } else {
                alert('Order failed: ' + response.message);
            }
        } catch (error) {
            alert('Error processing order: ' + error.message);
        }
    };

});