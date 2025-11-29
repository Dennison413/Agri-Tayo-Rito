<?php
// app/views/profile/seller/my-products.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../config/database.php';
require_once __DIR__ . '/../../../models/Product.php';
require_once __DIR__ . '/../../../models/Category.php';

// Check if user is logged in and is seller
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'];
$userRole = $_SESSION['user_role'] ?? null;
$userID = $_SESSION['user_id'] ?? null;

if (!$isLoggedIn || $userRole !== 'seller') {
    header('Location: ' . BASE_URL . 'auth/login');
    exit;
}

$db = new Database();
$conn = $db->connect();

// Get seller profile ID
$stmt = $conn->prepare("SELECT sellerID FROM seller_profiles WHERE userID = ?");
$stmt->execute([$userID]);
$sellerProfile = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sellerProfile) {
    die("Seller profile not found. Please contact administrator.");
}

$sellerID = $sellerProfile['sellerID'];

// Initialize models
$productModel = new Product();
$categoryModel = new Category();

// Get all products for this seller
$products = $productModel->getProductsBySeller($sellerID, true);
$categories = $categoryModel->getAllCategories();

// Get product statistics
$stats = $productModel->getSellerProductStats($sellerID);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Products - Agri Tayo Rito</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/responsive.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/seller/dashboard.css">
</head>
<body>
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <div class="overlay" id="overlay" onclick="toggleSidebar()"></div>
    
    <!-- Top Navigation -->
    <nav class="top-navbar">
        <div class="logo-wrapper">
            <img src="<?php echo BASE_URL; ?>images/logo.jpg" alt="Logo">
            <span class="logo">Seller Panel</span>
        </div>
        <div class="navbar-actions">
            <button class="nav-btn" onclick="toggleSidebar()">
                <span class="menu-icon">☰</span>
            </button>
        </div>
    </nav>

    <!-- Sidebar (same as dashboard.php) -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">🪴</div>
            <div class="sidebar-title">
                <h2>Seller Dashboard</h2>
                <p><?php echo htmlspecialchars($_SESSION['username'] ?? 'Seller'); ?></p>
            </div>
            <div class="sidebar-actions">
                <button class="sidebar-action-btn" onclick="toggleSidebar()">✕</button>
            </div>
        </div>

        <div class="user-info">
            <div class="user-avatar">👤</div>
            <div class="user-details">
                <h3><?php echo htmlspecialchars($_SESSION['username'] ?? 'Seller'); ?></h3>
                <p>Seller Account</p>
            </div>
        </div>

        <nav class="nav-menu">
            <div class="nav-section">
                <p class="nav-section-title">Main Menu</p>
                <button class="nav-item" onclick="location.href='<?php echo BASE_URL; ?>profile/seller/dashboard'">
                    <span class="nav-icon-menu">📊</span>
                    <span>Dashboard</span>
                </button>
                <button class="nav-item active" onclick="location.href='<?php echo BASE_URL; ?>profile/seller/products'">
                    <span class="nav-icon-menu">📦</span>
                    <span>My Products</span>
                </button>
                <button class="nav-item" onclick="location.href='<?php echo BASE_URL; ?>profile/seller/orders'">
                    <span class="nav-icon-menu">🛒</span>
                    <span>Orders</span>
                </button>
                <button class="nav-item" onclick="location.href='<?php echo BASE_URL; ?>profile/seller/withdrawals'">
                    <span class="nav-icon-menu">💰</span>
                    <span>Withdrawals</span>
                </button>
                <button class="nav-item" onclick="location.href='<?php echo BASE_URL; ?>profile/seller/image-upload'">
                    <span class="nav-icon-menu">🖼️</span>
                    <span>Product Images</span>
                </button>
            </div>

            <div class="nav-section">
                <p class="nav-section-title">Settings</p>
                <button class="nav-item" onclick="location.href='<?php echo BASE_URL; ?>profile/seller/profile-info'">
                    <span class="nav-icon-menu">🏬</span>
                    <span>Shop Profile</span>
                </button>
            </div>
        </nav>

        <div class="sidebar-footer">
            <button class="logout-btn" onclick="location.href='<?php echo BASE_URL; ?>auth/logout'">
                <span>🚪</span>
                <span>Logout</span>
            </button>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">📦 My Products</h1>
            <button class="btn-primary" onclick="openAddProductModal()">
                ➕ Add New Product
            </button>
        </div>

        <!-- Product Stats -->
        <section class="stats-section">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">📦</div>
                <div class="stat-info">
                    <h3 class="stat-value"><?php echo number_format($stats['total_products']); ?></h3>
                    <p class="stat-label">Total Products</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #2d5016 0%, #4a7c25 100%);">✅</div>
                <div class="stat-info">
                    <h3 class="stat-value"><?php echo number_format($stats['active_products']); ?></h3>
                    <p class="stat-label">Active Products</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #ff9a56 0%, #ff6a88 100%);">⚠️</div>
                <div class="stat-info">
                    <h3 class="stat-value"><?php echo number_format($stats['low_stock_count']); ?></h3>
                    <p class="stat-label">Low Stock</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">❌</div>
                <div class="stat-info">
                    <h3 class="stat-value"><?php echo number_format($stats['out_of_stock_count']); ?></h3>
                    <p class="stat-label">Out of Stock</p>
                </div>
            </div>
        </section>

        <!-- Filter Options -->
        <section class="filter-section">
            <div class="filter-group">
                <label>Category:</label>
                <select id="categoryFilter" onchange="filterProducts()">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['categoryID']; ?>">
                            <?php echo htmlspecialchars($category['category']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label>Status:</label>
                <select id="statusFilter" onchange="filterProducts()">
                    <option value="">All Status</option>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>

            <div class="filter-group">
                <label>Stock:</label>
                <select id="stockFilter" onchange="filterProducts()">
                    <option value="">All Stock Levels</option>
                    <option value="low">Low Stock</option>
                    <option value="out">Out of Stock</option>
                </select>
            </div>
        </section>

        <!-- Products Table -->
        <section class="content-section">
            <div class="table-container">
                <?php if (count($products) > 0): ?>
                <table class="data-table" id="productsTable">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Product Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Available</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr data-category="<?php echo $product['categoryID']; ?>" 
                                data-status="<?php echo $product['is_available']; ?>"
                                data-stock="<?php echo $product['available_stock']; ?>">
                                <td>
                                    <img src="<?php echo BASE_URL . ($product['main_image'] ?? 'images/default-product.jpg'); ?>" 
                                         alt="<?php echo htmlspecialchars($product['product_name']); ?>"
                                         class="product-thumbnail">
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($product['product_name']); ?></strong>
                                    <br><small><?php echo htmlspecialchars($product['unit']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($product['category']); ?></td>
                                <td>₱<?php echo number_format($product['price'], 2); ?></td>
                                <td>
                                    <span class="stock-badge <?php echo $product['available_stock'] <= 10 ? 'low-stock' : ''; ?>">
                                        <?php echo $product['available_stock']; ?> / <?php echo $product['stock_quantity']; ?>
                                    </span>
                                </td>
                                <td><?php echo $product['available_stock']; ?></td>
                                <td>
                                    <span class="status-badge <?php echo $product['is_available'] ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo $product['is_available'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-icon" onclick="editProduct(<?php echo $product['productID']; ?>)" title="Edit">
                                            ✏️
                                        </button>
                                        <button class="btn-icon" onclick="toggleProductStatus(<?php echo $product['productID']; ?>, <?php echo $product['is_available']; ?>)" title="Toggle Status">
                                            <?php echo $product['is_available'] ? '👁️' : '🚫'; ?>
                                        </button>
                                        <button class="btn-icon" onclick="manageImages(<?php echo $product['productID']; ?>)" title="Manage Images">
                                            🖼️
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="empty-state">
                    <p>📦 No products yet</p>
                    <button class="btn-primary" onclick="openAddProductModal()">Add Your First Product</button>
                </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <style>
        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .filter-group label {
            font-weight: 600;
            font-size: 0.9rem;
        }

        .filter-group select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            min-width: 180px;
        }

        .product-thumbnail {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }

        .action-buttons {
            display: flex;
            gap: 5px;
        }

        .btn-icon {
            background: none;
            border: none;
            font-size: 1.2rem;
            cursor: pointer;
            padding: 5px;
        }

        .btn-icon:hover {
            transform: scale(1.2);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-state p {
            font-size: 1.2rem;
            color: #666;
            margin-bottom: 20px;
        }
    </style>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        }

        function filterProducts() {
            const categoryFilter = document.getElementById('categoryFilter').value;
            const statusFilter = document.getElementById('statusFilter').value;
            const stockFilter = document.getElementById('stockFilter').value;
            
            const rows = document.querySelectorAll('#productsTable tbody tr');
            
            rows.forEach(row => {
                let show = true;
                
                // Category filter
                if (categoryFilter && row.dataset.category !== categoryFilter) {
                    show = false;
                }
                
                // Status filter
                if (statusFilter && row.dataset.status !== statusFilter) {
                    show = false;
                }
                
                // Stock filter
                if (stockFilter) {
                    const stock = parseInt(row.dataset.stock);
                    if (stockFilter === 'low' && stock > 10) show = false;
                    if (stockFilter === 'out' && stock > 0) show = false;
                }
                
                row.style.display = show ? '' : 'none';
            });
        }

        function openAddProductModal() {
            window.location.href = '<?php echo BASE_URL; ?>profile/seller/products?action=add';
        }

        function editProduct(productID) {
            window.location.href = `<?php echo BASE_URL; ?>profile/seller/products?action=edit&id=${productID}`;
        }

        function toggleProductStatus(productID, currentStatus) {
            if (confirm('Are you sure you want to ' + (currentStatus ? 'deactivate' : 'activate') + ' this product?')) {
                window.location.href = `<?php echo BASE_URL; ?>app/controllers/ProductController.php?action=toggle_status&id=${productID}`;
            }
        }

        function manageImages(productID) {
            window.location.href = `<?php echo BASE_URL; ?>profile/seller/image-upload?product=${productID}`;
        }
    </script>
</body>
</html>