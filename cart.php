<?php
require_once 'api/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Phone Store</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .cart-container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 2rem;
        }

        .cart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .cart-item {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            display: flex;
            gap: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .cart-item-image {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 10px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: white;
            flex-shrink: 0;
        }

        .cart-item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 10px;
        }

        .cart-item-details {
            flex: 1;
        }

        .cart-item-brand {
            color: #667eea;
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .cart-item-model {
            font-size: 1.3rem;
            font-weight: 700;
            color: #333;
            margin: 0.5rem 0;
        }

        .cart-item-specs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .cart-item-price {
            font-size: 1.4rem;
            font-weight: bold;
            color: #27ae60;
        }

        .cart-item-actions {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: flex-end;
        }

        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: #f8f9fa;
            padding: 0.5rem;
            border-radius: 8px;
        }

        .qty-btn {
            background: #667eea;
            color: white;
            border: none;
            width: 35px;
            height: 35px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.2rem;
            font-weight: bold;
            transition: background 0.3s;
        }

        .qty-btn:hover {
            background: #5568d3;
        }

        .qty-display {
            min-width: 40px;
            text-align: center;
            font-weight: bold;
            font-size: 1.1rem;
        }

        .remove-btn {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.3s;
        }

        .remove-btn:hover {
            background: #c0392b;
        }

        .cart-summary {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 100px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e0e0e0;
        }

        .summary-row:last-child {
            border-bottom: none;
            font-size: 1.3rem;
            font-weight: bold;
        }

        .checkout-btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s;
            margin-top: 1rem;
        }

        .checkout-btn:hover {
            transform: translateY(-2px);
        }

        .continue-shopping {
            width: 100%;
            padding: 1rem;
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: 0.5rem;
        }

        .cart-layout {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }

        @media (max-width: 768px) {
            .cart-layout {
                grid-template-columns: 1fr;
            }

            .cart-item {
                flex-direction: column;
            }

            .cart-item-actions {
                flex-direction: row;
                justify-content: space-between;
                width: 100%;
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
                <button class="cart-btn" onclick="window.location.href='cart.php'">
                    🛒 Cart
                    <span class="cart-count" id="cartCount">0</span>
                </button>
                <button class="login-btn" onclick="logout()">Logout</button>
            </div>
        </nav>
    </header>

    <div class="cart-container">
        <div class="cart-header">
            <h1>Shopping Cart</h1>
        </div>

        <div id="alertMessage"></div>

        <div class="cart-layout">
            <!-- Cart Items -->
            <div id="cartItems">
                <div class="loading">
                    <div class="spinner"></div>
                    <p>Loading cart...</p>
                </div>
            </div>

            <!-- Cart Summary -->
            <div class="cart-summary" id="cartSummary" style="display: none;">
                <h2>Order Summary</h2>
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span id="subtotal">NPR 0.00</span>
                </div>
                <div class="summary-row">
                    <span>Shipping</span>
                    <span>Free</span>
                </div>
                <div class="summary-row">
                    <span>Total</span>
                    <span id="total">NPR 0.00</span>
                </div>
                <button class="checkout-btn" onclick="proceedToCheckout()">
                    Proceed to Checkout 🛍️
                </button>
                <button class="continue-shopping" onclick="window.location.href='index.php'">
                    Continue Shopping
                </button>
            </div>
        </div>
    </div>

    <script>
        let cartData = [];

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
                    cartData = data.data.items;
                    displayCart(cartData, data.data.total);
                    document.getElementById('cartCount').textContent = data.data.count;
                } else {
                    showError('Failed to load cart');
                }
            } catch (error) {
                showError('Error loading cart');
                console.error(error);
            }
        }

        // Display cart items
        function displayCart(items, total) {
            const container = document.getElementById('cartItems');
            const summary = document.getElementById('cartSummary');

            if (items.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-state-icon">🛒</div>
                        <h3>Your cart is empty</h3>
                        <p>Add some phones to get started!</p>
                        <button class="btn btn-primary" onclick="window.location.href='index.php'" style="margin-top: 1rem; padding: 1rem 2rem;">
                            Browse Phones
                        </button>
                    </div>
                `;
                summary.style.display = 'none';
                return;
            }

            container.innerHTML = items.map(item => `
                <div class="cart-item">
                    <div class="cart-item-image">
                        ${item.image ? `<img src="${item.image_url}" alt="${item.model}">` : '📱'}
                    </div>
                    <div class="cart-item-details">
                        <div class="cart-item-brand">${item.brand}</div>
                        <div class="cart-item-model">${item.model}</div>
                        <div class="cart-item-specs">
                            <span class="spec-badge">Stock: ${item.stock}</span>
                        </div>
                        <div class="cart-item-price">NPR ${formatPrice(item.price)}</div>
                    </div>
                    <div class="cart-item-actions">
                        <div class="quantity-controls">
                            <button class="qty-btn" onclick="updateQuantity(${item.cart_id}, ${item.quantity - 1})">-</button>
                            <span class="qty-display">${item.quantity}</span>
                            <button class="qty-btn" onclick="updateQuantity(${item.cart_id}, ${item.quantity + 1})">+</button>
                        </div>
                        <button class="remove-btn" onclick="removeItem(${item.cart_id})">Remove</button>
                    </div>
                </div>
            `).join('');

            // Update summary
            document.getElementById('subtotal').textContent = `NPR ${formatPrice(total)}`;
            document.getElementById('total').textContent = `NPR ${formatPrice(total)}`;
            summary.style.display = 'block';
        }

        // Update quantity
        async function updateQuantity(cartId, newQuantity) {
            if (newQuantity < 1) return;

            try {
                const formData = new FormData();
                formData.append('action', 'update');
                formData.append('cart_id', cartId);
                formData.append('quantity', newQuantity);

                const response = await fetch('api/cart.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    loadCart();
                } else {
                    showAlert(data.message, 'error');
                }
            } catch (error) {
                showAlert('Error updating quantity', 'error');
                console.error(error);
            }
        }

        // Remove item
        async function removeItem(cartId) {
            if (!confirm('Remove this item from cart?')) return;

            try {
                const formData = new FormData();
                formData.append('action', 'remove');
                formData.append('cart_id', cartId);

                const response = await fetch('api/cart.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    showAlert('Item removed from cart', 'success');
                    loadCart();
                } else {
                    showAlert(data.message, 'error');
                }
            } catch (error) {
                showAlert('Error removing item', 'error');
                console.error(error);
            }
        }

        // Proceed to checkout
        function proceedToCheckout() {
            if (cartData.length === 0) {
                showAlert('Your cart is empty', 'error');
                return;
            }
            window.location.href = 'checkout.php';
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
            setTimeout(() => {
                alertDiv.innerHTML = '';
            }, 3000);
        }

        // Show error
        function showError(message) {
            document.getElementById('cartItems').innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon">❌</div>
                    <h3>Error</h3>
                    <p>${message}</p>
                </div>
            `;
        }
    </script>
</body>
</html>