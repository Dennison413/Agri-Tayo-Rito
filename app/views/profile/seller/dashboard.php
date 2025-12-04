<?php
// app/views/profile/seller/dashboard.php - UPDATED WITH SIDEBAR
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

// Get seller's shop and balance
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

// Recent products - using image_path and image_order
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
    <?php include 'seller-nav.php'; ?>

    <!-- Main Content -->
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">Seller Dashboard</h1>
            <p class="page-subtitle">Welcome back, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Seller'); ?>!</p>
        </div>

        <!-- Balance Alert (if balance >= 100) -->
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

        <!-- Quick Actions - NOW SQUARE GRID -->
        <section class="quick-actions">
            <button class="action-btn-square" onclick="location.href='<?php echo BASE_URL; ?>profile/seller/products?action=add'">
                <span class="action-icon-large">➕</span>
                <span class="action-text">Add Product</span>
            </button>
            <button class="action-btn-square" onclick="location.href='<?php echo BASE_URL; ?>profile/seller/image-upload'">
                <span class="action-icon-large">🖼️</span>
                <span class="action-text">Upload Images</span>
            </button>
            <button class="action-btn-square" onclick="location.href='<?php echo BASE_URL; ?>profile/seller/orders'">
                <span class="action-icon-large">📋</span>
                <span class="action-text">View Orders</span>
            </button>
            <button class="action-btn-square" onclick="location.href='<?php echo BASE_URL; ?>profile/seller/withdrawals'">
                <span class="action-icon-large">💰</span>
                <span class="action-text">Withdrawals</span>
            </button>
        </section>

        <!-- Stats Grid -->
        <section class="stats-section">
            <!-- Balance Card -->
            <?php if ($shopBalance): ?>
            <div class="stat-card balance-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);">💰</div>
                <div class="stat-info">
                    <h3 class="stat-value">₱<?php echo number_format($shopBalance['balance'], 2); ?></h3>
                    <p class="stat-label">Available Balance</p>
                    <a href="<?php echo BASE_URL; ?>profile/seller/withdrawals" class="stat-link">Request Withdrawal →</a>
                </div>
            </div>

            <!-- Total Earned Card -->
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

        <!-- ATM Card Info (if issued) -->
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
        /* ============================================
           DASHBOARD-SPECIFIC STYLES
           ============================================ */
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f9fafb;
        }

        /* Page Header */
        .page-header {
            margin-bottom: 30px;
        }

        .page-title {
            font-size: 2rem;
            color: #1f2937;
            margin-bottom: 5px;
        }

        .page-subtitle {
            color: #6b7280;
            font-size: 1rem;
        }

        /* Balance Alert */
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

        .alert-icon {
            font-size: 2.5rem;
        }

        .alert-content {
            flex: 1;
        }

        .alert-content h3 {
            margin: 0 0 5px 0;
            font-size: 1.2rem;
        }

        .alert-content p {
            margin: 0;
            opacity: 0.9;
        }

        .alert-btn {
            padding: 12px 24px;
            background: white;
            color: #F59E0B;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            white-space: nowrap;
            transition: transform 0.3s;
        }

        .alert-btn:hover {
            transform: translateY(-2px);
        }

        /* Quick Actions - SQUARE GRID */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .action-btn-square {
            aspect-ratio: 1;
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 20px;
        }

        .action-btn-square:hover {
            border-color: #4a7c25;
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(74, 124, 37, 0.2);
        }

        .action-icon-large {
            font-size: 3rem;
        }

        .action-text {
            font-weight: 600;
            color: #1f2937;
            font-size: 1rem;
            text-align: center;
        }

        /* Stats Section */
        .stats-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card.balance-card {
            grid-column: span 2;
            background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
            border: 2px solid #FFD700;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            flex-shrink: 0;
        }

        .stat-info {
            flex: 1;
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #6b7280;
            font-size: 0.9rem;
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

        /* Card Info Section */
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

        /* Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 30px;
        }

        .content-section {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e5e7eb;
        }

        .section-title {
            font-size: 1.3rem;
            color: #1f2937;
        }

        .view-all-link {
            color: #4a7c25;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .view-all-link:hover {
            text-decoration: underline;
        }

        /* Products List */
        .products-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .product-item {
            display: flex;
            gap: 15px;
            padding: 15px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            transition: box-shadow 0.3s;
        }

        .product-item:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .product-image-small {
            width: 80px;
            height: 80px;
            border-radius: 8px;
            overflow: hidden;
            flex-shrink: 0;
        }

        .product-image-small img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-details {
            flex: 1;
        }

        .product-name-small {
            font-size: 1rem;
            color: #1f2937;
            margin-bottom: 5px;
        }

        .product-category {
            color: #6b7280;
            font-size: 0.85rem;
            margin-bottom: 5px;
        }

        .product-price-small {
            color: #2d5016;
            font-weight: bold;
            font-size: 1.1rem;
        }

        .product-status {
            display: flex;
            flex-direction: column;
            gap: 8px;
            align-items: flex-end;
        }

        .stock-badge {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.8rem;
            background: #e5e7eb;
            color: #4b5563;
            font-weight: 600;
        }

        .stock-badge.low-stock {
            background: #fee2e2;
            color: #dc2626;
        }

        .status-badge {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-active {
            background: #d1fae5;
            color: #065f46;
        }

        .status-inactive {
            background: #fee2e2;
            color: #dc2626;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-shipped {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-delivered {
            background: #d1fae5;
            color: #065f46;
        }

        /* Table */
        .table-container {
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table thead {
            background: #f9fafb;
        }

        .data-table th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #4b5563;
            font-size: 0.9rem;
        }

        .data-table td {
            padding: 12px;
            border-top: 1px solid #e5e7eb;
            color: #1f2937;
        }

        .data-table tbody tr:hover {
            background: #f9fafb;
        }

        /* ============================================
           MOBILE RESPONSIVE
           ============================================ */
        @media (max-width: 768px) {
            .page-title {
                font-size: 1.5rem;
            }

            .balance-alert {
                flex-direction: column;
                text-align: center;
            }

            .alert-btn {
                width: 100%;
            }

            /* Square grid on mobile - 2 columns */
            .quick-actions {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
            }

            .stat-card.balance-card {
                grid-column: span 1;
            }

            .stats-section {
                grid-template-columns: 1fr;
            }

            .content-grid {
                grid-template-columns: 1fr;
            }

            .card-info-box {
                flex-direction: column;
                text-align: center;
            }

            .product-item {
                flex-direction: column;
            }

            .product-status {
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
            }
        }
    </style>
</body>
</html>