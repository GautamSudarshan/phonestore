<?php
require_once 'api/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Khalti Configuration - REPLACE WITH YOUR ACTUAL KEYS
define('KHALTI_PUBLIC_KEY', 'test_public_key_dc74e0fd57cb46cd93832aee0a507256'); // Replace with your key
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Phone Store</title>
    <link rel="stylesheet" href="css/style.css">
    
    <!-- Khalti Checkout SDK -->
    <script src="https://khalti.s3.ap-south-1.amazonaws.com/KPG/dist/2020.12.17.0.0.0/khalti-checkout.iffe.js"></script>
    
    <style>
        .checkout-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 2rem;
        }

        .checkout-grid {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 2rem;
        }

        .checkout-section {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .section-title {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            color: #333;
            border-bottom: 2px solid #667eea;
            padding-bottom: 0.5rem;
        }

        .order-item {
            display: flex;
            gap: 1rem;
            padding: 1rem;
            border-bottom: 1px solid #e0e0e0;
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .order-item-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
            flex-shrink: 0;
        }

        .order-item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 8px;
        }

        .order-item-details {
            flex: 1;
        }

        .order-item-brand {
            color: #667eea;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .order-item-model {
            font-weight: 700;
            margin: 0.3rem 0;
        }

        .order-item-qty {
            color: #666;
            font-size: 0.9rem;
        }

        .order-item-price {
            font-weight: bold;
            color: #27ae60;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 0.8rem 0;
            border-bottom: 1px solid #e0e0e0;
        }

        .summary-row:last-child {
            border-bottom: none;
            font-size: 1.4rem;
            font-weight: bold;
            color: #667eea;
            padding-top: 1rem;
            border-top: 2px solid #667eea;
        }

        .payment-btn {
            width: 100%;
            padding: 1.2rem;
            background: linear-gradient(135deg, #5d2e8e 0%, #9c27b0 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.2rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s;
            margin-top: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .payment-btn:hover {
            transform: translateY(-2px);
        }

        .payment-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .khalti-logo {
            height: 24px;
        }

        @media (max-width: 768px) {
            .checkout-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <nav>
            <a href="index.php" class="logo">📱 PhoneStore</a>
            <div class="nav-links">
                <a href="index.php">Home</a>
                <a href="my-orders.php">My Orders</a>
                <span style="opacity: 0.8;">Hello, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</span>
                <button class="login-btn" onclick="logout()">Logout</button>
            </div>
        </nav>
    </header>

    <div class="checkout-container">
        <h1>Checkout</h1>

        <div id="alertMessage"></div>

        <div class="checkout-grid">
            <!-- Shipping Information -->
            <div>
                <div class="checkout-section">
                    <h2 class="section-title">Shipping Information</h2>
                    <form id="shippingForm">
                        <div class="form-group">
                            <label for="phone">Phone Number *</label>
                            <input type="tel" id="phone" name="phone" required placeholder="Enter your phone number">
                        </div>
                        <div class="form-group">
                            <label for="address">Delivery Address *</label>
                            <textarea id="address" name="address" required placeholder="Enter your complete delivery address"></textarea>
                        </div>
                    </form>
                </div>

                <div class="checkout-section" style="margin-top: 2rem;">
                    <h2 class="section-title">Order Items</h2>
                    <div id="orderItems">
                        <div class="loading">Loading...</div>
                    </div>
                </div>
            </div>

            <!-- Order Summary -->
            <div>
                <div class="checkout-section" style="position: sticky; top: 100px;">
                    <h2 class="section-title">Order Summary</h2>
                    <div id="orderSummary">
                        <div class="summary-row">
                            <span>Subtotal</span>
                            <span id="subtotal">NPR 0.00</span>
                        </div>
                        <div class="summary-row">
                            <span>Delivery Charge</span>
                            <span>Free</span>
                        </div>
                        <div class="summary-row">
                            <span>Total Amount</span>
                            <span id="total">NPR 0.00</span>
                        </div>
                    </div>

                    <button class="payment-btn" id="paymentBtn" onclick="initiatePayment()">
                        <span>Pay with</span>
                        <img src="https://web.khalti.com/static/img/logo1.png" alt="Khalti" class="khalti-logo">
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let cartData = [];
        let totalAmount = 0;
        let orderId = null;

        // Khalti Configuration
        const khaltiConfig = {
            publicKey: "<?php echo KHALTI_PUBLIC_KEY; ?>",
            productIdentity: "phone_store_order",
            productName: "Phone Store Order",
            productUrl: "<?php echo BASE_URL; ?>",
            paymentPreference: [
                "KHALTI",
                "EBANKING",
                "MOBILE_BANKING",
                "CONNECT_IPS",
                "SCT"
            ],
            eventHandler: {
                onSuccess(payload) {
                    console.log("Payment Success:", payload);
                    verifyPayment(payload);
                },
                onError(error) {
                    console.log("Payment Error:", error);
                    showAlert('Payment failed. Please try again.', 'error');
                },
                onClose() {
                    console.log('Payment widget closed');
                }
            }
        };

        const khaltiCheckout = new KhaltiCheckout(khaltiConfig);

        // Load cart on page load
        document.addEventListener('DOMContentLoaded', () => {
            loadCart();
        });

        // Load cart items
        async function loadCart() {
            try {
                const response = await fetch('api/cart.php');
                const data = await response.json();

                if (data.success) {
                    if (data.data.count === 0) {
                        window.location.href = 'cart.php';
                        return;
                    }

                    cartData = data.data.items;
                    totalAmount = data.data.total;
                    displayOrderItems(cartData);
                    displaySummary(totalAmount);
                } else {
                    showAlert('Failed to load cart', 'error');
                }
            } catch (error) {
                showAlert('Error loading cart', 'error');
                console.error(error);
            }
        }

        // Display order items
        function displayOrderItems(items) {
            const container = document.getElementById('orderItems');
            container.innerHTML = items.map(item => `
                <div class="order-item">
                    <div class="order-item-image">
                        ${item.image ? `<img src="${item.image_url}" alt="${item.model}">` : '📱'}
                    </div>
                    <div class="order-item-details">
                        <div class="order-item-brand">${item.brand}</div>
                        <div class="order-item-model">${item.model}</div>
                        <div class="order-item-qty">Quantity: ${item.quantity}</div>
                    </div>
                    <div class="order-item-price">
                        NPR ${formatPrice(item.subtotal)}
                    </div>
                </div>
            `).join('');
        }

        // Display summary
        function displaySummary(total) {
            document.getElementById('subtotal').textContent = `NPR ${formatPrice(total)}`;
            document.getElementById('total').textContent = `NPR ${formatPrice(total)}`;
        }

        // Initiate payment
        async function initiatePayment() {
            const phone = document.getElementById('phone').value.trim();
            const address = document.getElementById('address').value.trim();

            // Validation
            if (!phone || !address) {
                showAlert('Please fill in all shipping information', 'error');
                return;
            }

            if (cartData.length === 0) {
                showAlert('Your cart is empty', 'error');
                return;
            }

            // Disable button
            const btn = document.getElementById('paymentBtn');
            btn.disabled = true;
            btn.innerHTML = 'Creating order...';

            try {
                // Create order first
                const formData = new FormData();
                formData.append('action', 'create');
                formData.append('shipping_address', address);
                formData.append('phone_number', phone);

                const response = await fetch('api/order.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    orderId = data.data.order_id;
                    
                    // Open Khalti payment
                    khaltiCheckout.show({
                        amount: totalAmount * 100, // Amount in paisa (multiply by 100)
                        mobile: phone,
                        productIdentity: data.data.order_number,
                        productName: `Order #${data.data.order_number}`
                    });

                    btn.disabled = false;
                    btn.innerHTML = '<span>Pay with</span><img src="https://web.khalti.com/static/img/logo1.png" alt="Khalti" class="khalti-logo">';
                } else {
                    showAlert(data.message, 'error');
                    btn.disabled = false;
                    btn.innerHTML = '<span>Pay with</span><img src="https://web.khalti.com/static/img/logo1.png" alt="Khalti" class="khalti-logo">';
                }
            } catch (error) {
                showAlert('Error creating order', 'error');
                console.error(error);
                btn.disabled = false;
                btn.innerHTML = '<span>Pay with</span><img src="https://web.khalti.com/static/img/logo1.png" alt="Khalti" class="khalti-logo">';
            }
        }

        // Verify payment after success
        async function verifyPayment(payload) {
            try {
                const formData = new FormData();
                formData.append('action', 'update_payment');
                formData.append('order_id', orderId);
                formData.append('khalti_token', payload.token);
                formData.append('khalti_idx', payload.idx);
                formData.append('amount', payload.amount / 100); // Convert back to rupees

                const response = await fetch('api/order.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    showAlert('Payment successful! Redirecting...', 'success');
                    setTimeout(() => {
                        window.location.href = 'my-orders.php';
                    }, 2000);
                } else {
                    showAlert('Payment verification failed', 'error');
                }
            } catch (error) {
                showAlert('Error verifying payment', 'error');
                console.error(error);
            }
        }

        // Format price
        function formatPrice(price) {
            return parseFloat(price).toLocaleString('en-NP', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        // Show alert
        function showAlert(message, type) {
            const alertDiv = document.getElementById('alertMessage');
            alertDiv.innerHTML = `<div class="alert alert-${type}">${message}</div>`;
            window.scrollTo(0, 0);
        }

        // Logout
        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                const formData = new FormData();
                formData.append('action', 'logout');

                fetch('api/auth.php', {
                    method: 'POST',
                    body: formData
                }).then(() => {
                    window.location.href = 'index.php';
                });
            }
        }
    </script>
</body>
</html>