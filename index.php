<?php
require_once 'api/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phone Store - Buy Latest Smartphones</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- Header -->
    <header>
        <nav>
            <a href="index.php" class="logo">📱 PhoneStore</a>
            <div class="nav-links">
                <a href="index.php">Home</a>
                <?php if (isLoggedIn()): ?>
                    <a href="my-orders.php">My Orders</a>
                    <span style="opacity: 0.8;">Hello, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</span>
                    <button class="cart-btn" onclick="window.location.href='cart.php'">
                        🛒 Cart
                        <span class="cart-count" id="cartCount">0</span>
                    </button>
                    <button class="login-btn" onclick="logout()">Logout</button>
                <?php else: ?>
                    <button class="login-btn" onclick="window.location.href='login.php'">Login</button>
                    <button class="login-btn" onclick="window.location.href='register.php'">Register</button>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <!-- Main Content -->
    <div class="container">
        <h1>Latest Smartphones</h1>
        
        <!-- Filters -->
        <div class="filters">
            <div class="filter-row">
                <div class="filter-group">
                    <label>Search</label>
                    <input type="text" id="searchInput" placeholder="Search phones...">
                </div>
                <div class="filter-group">
                    <label>Brand</label>
                    <select id="brandFilter">
                        <option value="">All Brands</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Min Price (NPR)</label>
                    <input type="number" id="minPrice" placeholder="0">
                </div>
                <div class="filter-group">
                    <label>Max Price (NPR)</label>
                    <input type="number" id="maxPrice" placeholder="200000">
                </div>
                <div class="filter-group">
                    <button class="filter-btn" onclick="applyFilters()">Apply Filters</button>
                </div>
            </div>
        </div>

        <!-- Products Grid -->
        <div id="productsContainer" class="products-grid">
            <div class="loading">
                <div class="spinner"></div>
                <p>Loading phones...</p>
            </div>
        </div>
    </div>

    <!-- Product Details Modal -->
    <div id="productModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Product Details</h2>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body" id="productDetails">
                <!-- Product details will be loaded here -->
            </div>
        </div>
    </div>

    <script>
        let allPhones = [];
        let cartCount = 0;

        // Load phones on page load
        document.addEventListener('DOMContentLoaded', () => {
            loadPhones();
            loadBrands();
            updateCartCount();
        });

        // Load all phones
        async function loadPhones() {
            try {
                const response = await fetch('api/products.php');
                const data = await response.json();

                if (data.success) {
                    allPhones = data.data.phones;
                    displayPhones(allPhones);
                } else {
                    showError('Failed to load phones');
                }
            } catch (error) {
                showError('Error loading phones');
                console.error(error);
            }
        }

        // Load brands for filter
        async function loadBrands() {
            try {
                const response = await fetch('api/products.php?action=brands');
                const data = await response.json();

                if (data.success) {
                    const select = document.getElementById('brandFilter');
                    data.data.brands.forEach(brand => {
                        const option = document.createElement('option');
                        option.value = brand;
                        option.textContent = brand;
                        select.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('Error loading brands:', error);
            }
        }

        // Display phones
        function displayPhones(phones) {
            const container = document.getElementById('productsContainer');

            if (phones.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-state-icon">📱</div>
                        <h3>No phones found</h3>
                        <p>Try adjusting your filters</p>
                    </div>
                `;
                return;
            }

            container.innerHTML = phones.map(phone => `
                <div class="product-card" onclick="showProductDetails(${phone.id})">
                    <div class="product-image">
                        ${phone.image ? `<img src="${phone.image_url}" alt="${phone.model}">` : '📱'}
                    </div>
                    <div class="product-info">
                        <div class="product-brand">${phone.brand}</div>
                        <div class="product-model">${phone.model}</div>
                        <div class="product-specs">
                            <span class="spec-badge">${phone.ram}</span>
                            <span class="spec-badge">${phone.storage}</span>
                            <span class="spec-badge">${phone.battery}</span>
                        </div>
                        <div class="product-price">NPR ${formatPrice(phone.price)}</div>
                        <div class="product-actions">
                            <button class="btn btn-primary" onclick="event.stopPropagation(); showProductDetails(${phone.id})">
                                View Details
                            </button>
                            <button class="btn btn-success" onclick="event.stopPropagation(); addToCart(${phone.id})">
                                Add to Cart
                            </button>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        // Show product details
        async function showProductDetails(phoneId) {
            try {
                const response = await fetch(`api/products.php?id=${phoneId}`);
                const data = await response.json();

                if (data.success) {
                    const phone = data.data.phone;
                    document.getElementById('productDetails').innerHTML = `
                        <div style="text-align: center;">
                            <div class="product-image" style="height: 300px; margin-bottom: 2rem;">
                                ${phone.image ? `<img src="${phone.image_url}" alt="${phone.model}" style="max-height: 100%; border-radius: 10px;">` : '📱'}
                            </div>
                            <div class="product-brand" style="font-size: 1rem;">${phone.brand}</div>
                            <h2 style="margin: 1rem 0;">${phone.model}</h2>
                            <p style="color: #666; margin-bottom: 2rem;">${phone.description || 'No description available'}</p>
                            
                            <div style="text-align: left; background: #f8f9fa; padding: 1.5rem; border-radius: 10px; margin-bottom: 2rem;">
                                <h3 style="margin-bottom: 1rem;">Specifications</h3>
                                <table style="width: 100%;">
                                    <tr><td><strong>RAM:</strong></td><td>${phone.ram}</td></tr>
                                    <tr><td><strong>Storage:</strong></td><td>${phone.storage}</td></tr>
                                    <tr><td><strong>Screen:</strong></td><td>${phone.screen_size}</td></tr>
                                    <tr><td><strong>Battery:</strong></td><td>${phone.battery}</td></tr>
                                    <tr><td><strong>Front Camera:</strong></td><td>${phone.camera_front}</td></tr>
                                    <tr><td><strong>Rear Camera:</strong></td><td>${phone.camera_rear}</td></tr>
                                    <tr><td><strong>Processor:</strong></td><td>${phone.processor}</td></tr>
                                    <tr><td><strong>OS:</strong></td><td>${phone.os}</td></tr>
                                    <tr><td><strong>Color:</strong></td><td>${phone.color}</td></tr>
                                    <tr><td><strong>Stock:</strong></td><td>${phone.stock} units</td></tr>
                                </table>
                            </div>
                            
                            <div class="product-price" style="font-size: 2rem; margin-bottom: 1rem;">
                                NPR ${formatPrice(phone.price)}
                            </div>
                            
                            <button class="btn btn-success" style="width: 100%; padding: 1rem; font-size: 1.2rem;" onclick="addToCart(${phone.id})">
                                Add to Cart 🛒
                            </button>
                        </div>
                    `;
                    document.getElementById('productModal').style.display = 'block';
                }
            } catch (error) {
                console.error('Error loading product details:', error);
            }
        }

        // Close modal
        function closeModal() {
            document.getElementById('productModal').style.display = 'none';
        }

        // Add to cart
        async function addToCart(phoneId) {
            <?php if (!isLoggedIn()): ?>
                alert('Please login to add items to cart');
                window.location.href = 'login.php';
                return;
            <?php endif; ?>

            try {
                const formData = new FormData();
                formData.append('action', 'add');
                formData.append('phone_id', phoneId);
                formData.append('quantity', 1);

                const response = await fetch('api/cart.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    alert('✅ Added to cart!');
                    updateCartCount();
                } else {
                    alert('❌ ' + data.message);
                }
            } catch (error) {
                alert('Error adding to cart');
                console.error(error);
            }
        }

        // Update cart count
        async function updateCartCount() {
            <?php if (isLoggedIn()): ?>
            try {
                const response = await fetch('api/cart.php');
                const data = await response.json();

                if (data.success) {
                    document.getElementById('cartCount').textContent = data.data.count;
                }
            } catch (error) {
                console.error('Error updating cart count:', error);
            }
            <?php endif; ?>
        }

        // Apply filters
        function applyFilters() {
            const search = document.getElementById('searchInput').value.toLowerCase();
            const brand = document.getElementById('brandFilter').value;
            const minPrice = parseFloat(document.getElementById('minPrice').value) || 0;
            const maxPrice = parseFloat(document.getElementById('maxPrice').value) || 999999;

            const filtered = allPhones.filter(phone => {
                const matchSearch = search === '' || 
                    phone.model.toLowerCase().includes(search) || 
                    phone.brand.toLowerCase().includes(search);
                const matchBrand = brand === '' || phone.brand === brand;
                const matchPrice = phone.price >= minPrice && phone.price <= maxPrice;

                return matchSearch && matchBrand && matchPrice;
            });

            displayPhones(filtered);
        }

        // Real-time search
        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('searchInput').addEventListener('input', applyFilters);
        });

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

        // Show error
        function showError(message) {
            document.getElementById('productsContainer').innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon">❌</div>
                    <h3>Error</h3>
                    <p>${message}</p>
                </div>
            `;
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('productModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>