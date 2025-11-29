<?php
// app/views/profile/seller/order-details.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../config/database.php';
require_once __DIR__ . '/../../../models/Orders.php';

// Check authentication
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'];
$userRole = $_SESSION['user_role'] ?? null;
$userID = $_SESSION['user_id'] ?? null;

if (!$isLoggedIn || $userRole !== 'seller') {
    header('Location: ' . BASE_URL . 'auth/login');
    exit;
}

// Get order ID from URL
$orderID = $_GET['id'] ?? null;

if (!$orderID) {
    header('Location: ' . BASE_URL . 'profile/seller/orders');
    exit;
}

$db = new Database();
$conn = $db->connect();

// Get seller profile
$stmt = $conn->prepare("SELECT sellerID FROM seller_profiles WHERE userID = ?");
$stmt->execute([$userID]);
$sellerProfile = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sellerProfile) {
    die("Seller profile not found");
}

$sellerID = $sellerProfile['sellerID'];

// Get order details with items
$ordersModel = new Orders();
$order = $ordersModel->getOrderById($orderID);

if (!$order) {
    header('Location: ' . BASE_URL . 'profile/seller/orders');
    exit;
}

// Verify this order contains seller's products
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM order_items oi 
                        JOIN products p ON oi.productID = p.productID 
                        WHERE oi.orderID = ? AND p.sellerID = ?");
$stmt->execute([$orderID, $sellerID]);
$verification = $stmt->fetch(PDO::FETCH_ASSOC);

if ($verification['count'] == 0) {
    header('Location: ' . BASE_URL . 'profile/seller/orders');
    exit;
}

// Get only seller's items from this order
$stmt = $conn->prepare("SELECT oi.*, p.product_name, p.unit, pi.image_path
                        FROM order_items oi
                        JOIN products p ON oi.productID = p.productID
                        LEFT JOIN product_images pi ON p.productID = pi.productID AND pi.image_order = 0
                        WHERE oi.orderID = ? AND p.sellerID = ?");
$stmt->execute([$orderID, $sellerID]);
$sellerItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate seller's total from this order
$sellerTotal = array_sum(array_column($sellerItems, 'subtotal'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?php echo $orderID; ?> - Agri Tayo Rito</title>
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
                <button class="nav-item" onclick="location.href='<?php echo BASE_URL; ?>profile/seller/dashboard'">
                    <span class="nav-icon-menu">📊</span>
                    <span>Dashboard</span>
                </button>
                <button class="nav-item" onclick="location.href='<?php echo BASE_URL; ?>profile/seller/products'">
                    <span class="nav-icon-menu">📦</span>
                    <span>My Products</span>
                </button>
                <button class="nav-item active" onclick="location.href='<?php echo BASE_URL; ?>profile/seller/orders'">
                    <span class="nav-icon-menu">🛒</span>
                    <span>Orders</span>
                </button>
                <button class="nav-item" onclick="location.href='<?php echo BASE_URL; ?>profile/seller/withdrawals'">
                    <span class="nav-icon-menu">💰</span>
                    <span>Withdrawals</span>
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
        <!-- Back Button -->
        <div class="page-header">
            <button class="btn-back" onclick="window.history.back()">
                ← Back to Orders
            </button>
            <h1 class="page-title">Order #<?php echo $orderID; ?></h1>
        </div>

        <!-- Order Status Header -->
        <section class="order-status-header">
            <div class="status-info">
                <span class="status-badge status-<?php echo $order['order_status']; ?>">
                    <?php echo ucfirst($order['order_status']); ?>
                </span>
                <p class="order-date">
                    Ordered on <?php echo date('F d, Y \a\t h:i A', strtotime($order['order_date'])); ?>
                </p>
            </div>
        </section>

        <div class="content-grid-2">
            <!-- Order Items -->
            <section class="content-section">
                <div class="section-header">
                    <h2 class="section-title">Your Items in This Order</h2>
                </div>

                <div class="order-items-list">
                    <?php foreach ($sellerItems as $item): ?>
                    <div class="order-item">
                        <div class="item-image">
                            <img src="<?php echo BASE_URL . ($item['image_path'] ?? 'images/default-product.jpg'); ?>" 
                                 alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                        </div>
                        <div class="item-details">
                            <h4><?php echo htmlspecialchars($item['product_name']); ?></h4>
                            <p class="item-unit"><?php echo htmlspecialchars($item['unit']); ?></p>
                            <p class="item-quantity">Quantity: <strong><?php echo $item['quantity']; ?></strong></p>
                            <p class="item-price">₱<?php echo number_format($item['unit_price'], 2); ?> each</p>
                        </div>
                        <div class="item-total">
                            <strong>₱<?php echo number_format($item['subtotal'], 2); ?></strong>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Seller's Total -->
                <div class="order-summary">
                    <div class="summary-row">
                        <span>Your Items Total:</span>
                        <strong>₱<?php echo number_format($sellerTotal, 2); ?></strong>
                    </div>
                </div>
            </section>

            <!-- Order Information Sidebar -->
            <aside class="order-sidebar">
                <!-- Customer Information -->
                <div class="info-card">
                    <h3 class="info-title">👤 Customer Information</h3>
                    <div class="info-content">
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($order['buyer_name']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($order['buyer_email']); ?></p>
                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['buyer_phone']); ?></p>
                    </div>
                </div>

                <!-- Delivery Information -->
                <div class="info-card">
                    <h3 class="info-title">📍 Delivery Address</h3>
                    <div class="info-content">
                        <p><?php echo nl2br(htmlspecialchars($order['delivery_address'])); ?></p>
                        <p><?php echo htmlspecialchars($order['delivery_municipality']); ?>, 
                           <?php echo htmlspecialchars($order['delivery_province']); ?> 
                           <?php echo htmlspecialchars($order['delivery_postal_code']); ?></p>
                    </div>
                </div>

                <!-- Payment Information -->
                <div class="info-card">
                    <h3 class="info-title">💳 Payment Information</h3>
                    <div class="info-content">
                        <p><strong>Method:</strong> 
                            <span class="payment-badge payment-<?php echo $order['payment_method_new']; ?>">
                                <?php echo strtoupper($order['payment_method_new']); ?>
                            </span>
                        </p>
                        <p><strong>Payment Status:</strong> 
                            <span class="status-badge status-<?php echo $order['payment_status']; ?>">
                                <?php echo ucfirst($order['payment_status']); ?>
                            </span>
                        </p>
                        <p><strong>Order Total:</strong> ₱<?php echo number_format($order['total_amount'], 2); ?></p>
                    </div>
                </div>

                <!-- Delivery Status -->
                <div class="info-card">
                    <h3 class="info-title">🚚 Delivery Status</h3>
                    <div class="info-content">
                        <p><strong>LGU Status:</strong> 
                            <span class="status-badge status-<?php echo $order['lgu_delivery_status']; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $order['lgu_delivery_status'])); ?>
                            </span>
                        </p>
                        
                        <?php if ($order['picked_up_at']): ?>
                            <p><strong>Picked Up:</strong> 
                                <?php echo date('M d, Y h:i A', strtotime($order['picked_up_at'])); ?>
                            </p>
                        <?php endif; ?>
                        
                        <?php if ($order['delivered_at']): ?>
                            <p><strong>Delivered:</strong> 
                                <?php echo date('M d, Y h:i A', strtotime($order['delivered_at'])); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Notes -->
                <?php if ($order['notes']): ?>
                <div class="info-card">
                    <h3 class="info-title">📝 Order Notes</h3>
                    <div class="info-content">
                        <p><?php echo nl2br(htmlspecialchars($order['notes'])); ?></p>
                    </div>
                </div>
                <?php endif; ?>
            </aside>
        </div>
    </main>

    <style>
        .btn-back {
            background: white;
            border: 1px solid #ddd;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }

        .btn-back:hover {
            background: #f5f5f5;
        }

        .order-status-header {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
        }

        .status-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .order-date {
            color: #666;
            margin: 0;
        }

        .content-grid-2 {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }

        @media (max-width: 1024px) {
            .content-grid-2 {
                grid-template-columns: 1fr;
            }
        }

        .order-items-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .order-item {
            display: flex;
            gap: 15px;
            padding: 15px;
            background: #f9fafb;
            border-radius: 8px;
        }

        .item-image {
            width: 80px;
            height: 80px;
            flex-shrink: 0;
        }

        .item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 8px;
        }

        .item-details {
            flex: 1;
        }

        .item-details h4 {
            margin: 0 0 5px 0;
            font-size: 1rem;
        }

        .item-unit, .item-quantity, .item-price {
            margin: 3px 0;
            font-size: 0.9rem;
            color: #666;
        }

        .item-total {
            display: flex;
            align-items: center;
            font-size: 1.1rem;
            color: #2d5016;
        }

        .order-summary {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 10px;
            font-size: 1.1rem;
        }

        .order-sidebar {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .info-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .info-title {
            margin: 0 0 15px 0;
            font-size: 1rem;
            color: #333;
        }

        .info-content p {
            margin: 8px 0;
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .payment-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .payment-cod {
            background: #fff3cd;
            color: #856404;
        }

        .payment-gcash, .payment-paymaya {
            background: #d1ecf1;
            color: #0c5460;
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