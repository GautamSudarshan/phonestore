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
    <title>My Orders - Phone Store</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .orders-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 2rem;
        }

        .order-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f0f0f0;
            margin-bottom: 1rem;
        }

        .order-number {
            font-size: 1.1rem;
            font-weight: bold;
            color: #667eea;
        }

        .order-date {
            color: #666;
            font-size: 0.9rem;
        }

        .order-status {
            display: inline-block;
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-processing {
            background: #cfe2ff;
            color: #084298;
        }

        .status-shipped {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status-delivered {
            background: #d4edda;
            color: #155724;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .order-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .detail-item {
            padding: 0.5rem;
        }

        .detail-label {
            color: #666;
            font-size: 0.85rem;
            margin-bottom: 0.3rem;
        }

        .detail-value {
            font-weight: 600;
            color: #333;
        }

        .order-total {
            font-size: 1.3rem;
            font-weight: bold;
            color: #27ae60;
        }

        .view-details-btn {
            padding: 0.6rem 1.5rem;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.3s;
        }

        .view-details-btn:hover {
            background: #5568d3;
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

    <div class="orders-container">
        <h1>My Orders</h1>

        <div id="ordersContainer">
            <div class="loading">
                <div class="spinner"></div>
                <p>Loading orders...</p>
            </div>
        </div>
    </div>

    <!-- Order Details Modal -->
    <div id="orderModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Order Details</h2>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body" id="orderDetails">
                <!-- Order details will be loaded here -->
            </div>
        </div>
    </div>

    <script>
        // Load orders on page load
        document.addEventListener('DOMContentLoaded', () => {
            loadOrders();
            updateCartCount();
        });

        // Load user orders
        async function loadOrders() {
            try {
                const response = await fetch('api/order.php?action=my_orders');
                const data = await response.json();

                if (data.success) {
                    displayOrders(data.data.orders);
                } else {
                    showError('Failed to load orders');
                }
            } catch (error) {
                showError('Error loading orders');
                console.error(error);
            }
        }

        // Display orders
        function displayOrders(orders) {
            const container = document.getElementById('ordersContainer');

            if (orders.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-state-icon">📦</div>
                        <h3>No orders yet</h3>
                        <p>Start shopping to see your orders here!</p>
                        <button class="btn btn-primary" onclick="window.location.href='index.php'" style="margin-top: 1rem; padding: 1rem 2rem;">
                            Browse Phones
                        </button>
                    </div>
                `;
                return;
            }

            container.innerHTML = orders.map(order => `
                <div class="order-card">
                    <div class="order-header">
                        <div>
                            <div class="order-number">${order.order_number}</div>
                            <div class="order-date">${formatDate(order.created_at)}</div>
                        </div>
                        <span class="order-status status-${order.order_status}">
                            ${order.order_status}
                        </span>
                    </div>
                    <div class="order-details">
                        <div class="detail-item">
                            <div class="detail-label">Payment Status</div>
                            <div class="detail-value">${capitalizeFirst(order.payment_status)}</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Total Amount</div>
                            <div class="detail-value order-total">NPR ${formatPrice(order.total_amount)}</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Phone Number</div>
                            <div class="detail-value">${order.phone_number}</div>
                        </div>
                        <div class="detail-item" style="display: flex; align-items: flex-end;">
                            <button class="view-details-btn" onclick="viewOrderDetails(${order.id})">
                                View Details
                            </button>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        // View order details
        async function viewOrderDetails(orderId) {
            try {
                const response = await fetch(`api/order.php?order_id=${orderId}`);
                const data = await response.json();

                if (data.success) {
                    const order = data.data.order;
                    document.getElementById('orderDetails').innerHTML = `
                        <div style="margin-bottom: 2rem;">
                            <h3>Order: ${order.order_number}</h3>
                            <p style="color: #666;">Placed on: ${formatDate(order.created_at)}</p>
                        </div>

                        <div style="background: #f8f9fa; padding: 1rem; border-radius: 8px; margin-bottom: 2rem;">
                            <h4 style="margin-bottom: 1rem;">Shipping Details</h4>
                            <p><strong>Phone:</strong> ${order.phone_number}</p>
                            <p><strong>Address:</strong> ${order.shipping_address}</p>
                        </div>

                        <div style="margin-bottom: 2rem;">
                            <h4 style="margin-bottom: 1rem;">Order Items</h4>
                            ${order.items.map(item => `
                                <div style="display: flex; gap: 1rem; padding: 1rem; border-bottom: 1px solid #e0e0e0;">
                                    <div style="width: 80px; height: 80px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; font-size: 2rem;">
                                        ${item.image ? `<img src="${item.image_url}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 8px;">` : '📱'}
                                    </div>
                                    <div style="flex: 1;">
                                        <div style="font-weight: bold;">${item.brand} ${item.model}</div>
                                        <div style="color: #666;">Quantity: ${item.quantity}</div>
                                        <div style="color: #27ae60; font-weight: bold;">NPR ${formatPrice(item.price)}</div>
                                    </div>
                                    <div style="font-weight: bold; color: #27ae60;">
                                        NPR ${formatPrice(item.price * item.quantity)}
                                    </div>
                                </div>
                            `).join('')}
                        </div>

                        <div style="background: #f8f9fa; padding: 1rem; border-radius: 8px;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                <span>Order Status:</span>
                                <span class="order-status status-${order.order_status}">${order.order_status}</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                <span>Payment Status:</span>
                                <strong>${capitalizeFirst(order.payment_status)}</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between; font-size: 1.3rem; font-weight: bold; color: #27ae60; margin-top: 1rem; padding-top: 1rem; border-top: 2px solid #e0e0e0;">
                                <span>Total:</span>
                                <span>NPR ${formatPrice(order.total_amount)}</span>
                            </div>
                        </div>
                    `;
                    document.getElementById('orderModal').style.display = 'block';
                }
            } catch (error) {
                alert('Error loading order details');
                console.error(error);
            }
        }

        // Close modal
        function closeModal() {
            document.getElementById('orderModal').style.display = 'none';
        }

        // Update cart count
        async function updateCartCount() {
            try {
                const response = await fetch('api/cart.php');
                const data = await response.json();

                if (data.success) {
                    document.getElementById('cartCount').textContent = data.data.count;
                }
            } catch (error) {
                console.error('Error updating cart count:', error);
            }
        }

        // Format date
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        // Format price
        function formatPrice(price) {
            return parseFloat(price).toLocaleString('en-NP', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        // Capitalize first letter
        function capitalizeFirst(str) {
            return str.charAt(0).toUpperCase() + str.slice(1);
        }

        // Show error
        function showError(message) {
            document.getElementById('ordersContainer').innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon">❌</div>
                    <h3>Error</h3>
                    <p>${message}</p>
                </div>
            `;
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

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('orderModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>