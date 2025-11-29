<?php
// app/views/profile/seller/dashboard.php - FIXED VERSION
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../config/database.php';

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

// ✅ FIXED: Get seller's shop and balance
$stmt = $conn->prepare("SELECT s.shopID, s.balance, s.total_earned, s.total_withdrawn, s.atm_card_number 
                        FROM shops s WHERE s.sellerID = ?");
$stmt->execute([$sellerID]);
$shopBalance = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch seller statistics
$stats = [];

// Total products
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM products WHERE sellerID = ?");
$stmt->execute([$sellerID]);
$stats['total_products'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Active products
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM products WHERE sellerID = ? AND is_available = 1");
$stmt->execute([$sellerID]);
$stats['active_products'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total orders
$stmt = $conn->prepare("SELECT COUNT(DISTINCT o.orderID) as total 
                        FROM orders o 
                        JOIN order_items oi ON o.orderID = oi.orderID 
                        JOIN products p ON oi.productID = p.productID 
                        WHERE p.sellerID = ?");
$stmt->execute([$sellerID]);
$stats['total_orders'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Pending orders
$stmt = $conn->prepare("SELECT COUNT(DISTINCT o.orderID) as total 
                        FROM orders o 
                        JOIN order_items oi ON o.orderID = oi.orderID 
                        JOIN products p ON oi.productID = p.productID 
                        WHERE p.sellerID = ? AND o.order_status = 'pending'");
$stmt->execute([$sellerID]);
$stats['pending_orders'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total revenue (from delivered orders)
$stmt = $conn->prepare("SELECT COALESCE(SUM(oi.subtotal), 0) as total 
                        FROM orders o 
                        JOIN order_items oi ON o.orderID = oi.orderID 
                        JOIN products p ON oi.productID = p.productID 
                        WHERE p.sellerID = ? AND o.order_status = 'delivered'");
$stmt->execute([$sellerID]);
$stats['total_revenue'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Low stock products
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM products WHERE sellerID = ? AND stock_quantity <= 10 AND is_available = 1");
$stmt->execute([$sellerID]);
$stats['low_stock'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// ✅ FIXED: Recent products - using image_path and image_order
$stmt = $conn->prepare("SELECT p.productID, p.product_name, p.price, p.stock_quantity, p.is_available, c.category, pi.image_path as imageURL
                        FROM products p
                        JOIN categories c ON p.categoryID = c.categoryID
                        LEFT JOIN product_images pi ON p.productID = pi.productID AND pi.image_order = 0
                        WHERE p.sellerID = ?
                        ORDER BY p.created_at DESC LIMIT 5");
$stmt->execute([$sellerID]);
$recent_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Recent orders (last 5)
$stmt = $conn->prepare("SELECT DISTINCT o.orderID, o.total_amount, o.order_status, o.order_date, u.full_name
                        FROM orders o
                        JOIN order_items oi ON o.orderID = oi.orderID
                        JOIN products p ON oi.productID = p.productID
                        JOIN users u ON o.buyerID = u.userID
                        WHERE p.sellerID = ?
                        ORDER BY o.order_date DESC LIMIT 5");
$stmt->execute([$sellerID]);
$recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Dashboard - Agri Tayo Rito</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/responsive.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/seller/dashboard.css">
</head>
<body>
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

    <!-- Sidebar -->
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
                <button class="nav-item active" onclick="location.href='<?php echo BASE_URL; ?>profile/seller/dashboard'">
                    <span class="nav-icon-menu">📊</span>
                    <span>Dashboard</span>
                </button>
                <button class="nav-item" onclick="location.href='<?php echo BASE_URL; ?>profile/seller/products'">
                    <span class="nav-icon-menu">📦</span>
                    <span>My Products</span>
                </button>
                <button class="nav-item" onclick="location.href='<?php echo BASE_URL; ?>profile/seller/orders'">
                    <span class="nav-icon-menu">🛒</span>
                    <span>Orders</span>
                    <?php if ($stats['pending_orders'] > 0): ?>
                        <span class="nav-badge"><?php echo $stats['pending_orders']; ?></span>
                    <?php endif; ?>
                </button>
                <!-- ✅ NEW: Withdrawals Link -->
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
            <h1 class="page-title">Seller Dashboard</h1>
            <p class="page-subtitle">Welcome back, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Seller'); ?>!</p>
        </div>

        <!-- ✅ NEW: Balance Alert (if balance > 100) -->
        <?php if ($shopBalance && $shopBalance['balance'] >= 100): ?>
        <section class="balance-alert">
            <div class="alert-icon">💰</div>
            <div class="alert-content">
                <h3>You have ₱<?php echo number_format($shopBalance['balance'], 2); ?> available for withdrawal!</h3>
                <p>Request a withdrawal to get your earnings</p>
            </div>
            <button onclick="location.href='<?php echo BASE_URL; ?>profile/seller/withdrawals'" class="alert-btn">
                Request Withdrawal →
            </button>
        </section>
        <?php endif; ?>

        <!-- Quick Actions -->
        <section class="quick-actions">
            <button class="action-btn" onclick="location.href='<?php echo BASE_URL; ?>profile/seller/products?action=add'">
                <span class="action-icon">➕</span>
                <span>Add Product</span>
            </button>
            <button class="action-btn" onclick="location.href='<?php echo BASE_URL; ?>profile/seller/image-upload'">
                <span class="action-icon">🖼️</span>
                <span>Upload Images</span>
            </button>
            <button class="action-btn" onclick="location.href='<?php echo BASE_URL; ?>profile/seller/orders'">
                <span class="action-icon">📋</span>
                <span>View Orders</span>
            </button>
            <button class="action-btn" onclick="location.href='<?php echo BASE_URL; ?>profile/seller/withdrawals'">
                <span class="action-icon">💰</span>
                <span>Withdrawals</span>
            </button>
        </section>

        <!-- Stats Grid -->
        <section class="stats-section">
            <!-- ✅ NEW: Balance Card -->
            <?php if ($shopBalance): ?>
            <div class="stat-card balance-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);">💰</div>
                <div class="stat-info">
                    <h3 class="stat-value">₱<?php echo number_format($shopBalance['balance'], 2); ?></h3>
                    <p class="stat-label">Available Balance</p>
                    <a href="<?php echo BASE_URL; ?>profile/seller/withdrawals" class="stat-link">Request Withdrawal →</a>
                </div>
            </div>

            <!-- ✅ NEW: Total Earned Card -->
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #2d5016 0%, #4a7c25 100%);">📈</div>
                <div class="stat-info">
                    <h3 class="stat-value">₱<?php echo number_format($shopBalance['total_earned'], 2); ?></h3>
                    <p class="stat-label">Total Earned</p>
                </div>
            </div>
            <?php endif; ?>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">📦</div>
                <div class="stat-info">
                    <h3 class="stat-value"><?php echo number_format($stats['total_products']); ?></h3>
                    <p class="stat-label">Total Products</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">✅</div>
                <div class="stat-info">
                    <h3 class="stat-value"><?php echo number_format($stats['active_products']); ?></h3>
                    <p class="stat-label">Active Products</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">🛒</div>
                <div class="stat-info">
                    <h3 class="stat-value"><?php echo number_format($stats['total_orders']); ?></h3>
                    <p class="stat-label">Total Orders</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">⏳</div>
                <div class="stat-info">
                    <h3 class="stat-value"><?php echo number_format($stats['pending_orders']); ?></h3>
                    <p class="stat-label">Pending Orders</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #ff9a56 0%, #ff6a88 100%);">⚠️</div>
                <div class="stat-info">
                    <h3 class="stat-value"><?php echo number_format($stats['low_stock']); ?></h3>
                    <p class="stat-label">Low Stock Items</p>
                </div>
            </div>
        </section>

        <!-- ✅ NEW: ATM Card Info (if issued) -->
        <?php if ($shopBalance && !empty($shopBalance['atm_card_number'])): ?>
        <section class="card-info-section">
            <div class="card-info-box">
                <div class="card-icon">💳</div>
                <div class="card-details">
                    <h3>Your ATM/ID Card</h3>
                    <p class="card-number">Card Number: <strong><?php echo htmlspecialchars($shopBalance['atm_card_number']); ?></strong></p>
                    <p class="card-note">Present this card at LGU office for instant cash withdrawal</p>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- Recent Activity -->
        <div class="content-grid">
            <section class="content-section">
                <div class="section-header">
                    <h2 class="section-title">Recent Products</h2>
                    <a href="<?php echo BASE_URL; ?>profile/seller/products" class="view-all-link">View All →</a>
                </div>
                <div class="products-list">
                    <?php if (count($recent_products) > 0): ?>
                        <?php foreach ($recent_products as $product): ?>
                            <div class="product-item">
                                <div class="product-image-small">
                                    <img src="<?php echo BASE_URL . htmlspecialchars($product['imageURL'] ?: 'images/default-product.jpg'); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                                </div>
                                <div class="product-details">
                                    <h4 class="product-name-small"><?php echo htmlspecialchars($product['product_name']); ?></h4>
                                    <p class="product-category"><?php echo htmlspecialchars($product['category']); ?></p>
                                    <p class="product-price-small">₱<?php echo number_format($product['price'], 2); ?></p>
                                </div>
                                <div class="product-status">
                                    <span class="stock-badge <?php echo $product['stock_quantity'] <= 10 ? 'low-stock' : ''; ?>">
                                        Stock: <?php echo $product['stock_quantity']; ?>
                                    </span>
                                    <span class="status-badge <?php echo $product['is_available'] ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo $product['is_available'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="text-align: center; color: #666; padding: 20px;">No products yet. Start adding products to your shop!</p>
                    <?php endif; ?>
                </div>
            </section>

            <section class="content-section">
                <div class="section-header">
                    <h2 class="section-title">Recent Orders</h2>
                    <a href="<?php echo BASE_URL; ?>profile/seller/orders" class="view-all-link">View All →</a>
                </div>
                <div class="table-container">
                    <?php if (count($recent_orders) > 0): ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_orders as $order): ?>
                                    <tr>
                                        <td>#<?php echo $order['orderID']; ?></td>
                                        <td><?php echo htmlspecialchars($order['full_name']); ?></td>
                                        <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $order['order_status']; ?>">
                                                <?php echo ucfirst($order['order_status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p style="text-align: center; color: #666; padding: 20px;">No orders yet.</p>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </main>

    <style>
        /* ✅ NEW: Balance Alert Styling */
        .balance-alert {
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            color: white;
            padding: 20px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
        }

        .balance-alert .alert-icon {
            font-size: 2.5rem;
        }

        .balance-alert .alert-content h3 {
            margin: 0 0 5px 0;
            font-size: 1.2rem;
        }

        .balance-alert .alert-content p {
            margin: 0;
            opacity: 0.9;
        }

        .balance-alert .alert-btn {
            margin-left: auto;
            padding: 12px 24px;
            background: white;
            color: #F59E0B;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            white-space: nowrap;
        }

        /* ✅ NEW: Balance Card Highlight */
        .stat-card.balance-card {
            grid-column: span 2;
            background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
            border: 2px solid #FFD700;
        }

        .stat-link {
            display: inline-block;
            margin-top: 8px;
            color: #F59E0B;
            font-size: 0.9rem;
            text-decoration: none;
            font-weight: 600;
        }

        .stat-link:hover {
            text-decoration: underline;
        }

        /* ✅ NEW: Card Info Section */
        .card-info-section {
            margin-bottom: 30px;
        }

        .card-info-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 20px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .card-icon {
            font-size: 3rem;
        }

        .card-details h3 {
            margin: 0 0 10px 0;
        }

        .card-number {
            font-size: 1.1rem;
            margin: 5px 0;
        }

        .card-note {
            opacity: 0.9;
            font-size: 0.9rem;
            margin: 5px 0 0 0;
        }
    </style>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        }
    </script>
</body>
</html>