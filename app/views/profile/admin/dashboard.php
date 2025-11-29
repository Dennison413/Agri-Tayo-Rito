<?php
// app/views/profile/admin/dashboard.php - UPDATED VERSION
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../config/database.php';

// Check if user is logged in and is admin
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'];
$userRole = $_SESSION['user_role'] ?? null;

if (!$isLoggedIn || $userRole !== 'admin') {
    header('Location: ' . BASE_URL . 'auth/login');
    exit;
}

$db = new Database();
$conn = $db->connect();

// Fetch statistics
$stats = [];

// Total users
$stmt = $conn->query("SELECT COUNT(*) as total FROM users");
$stats['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total buyers
$stmt = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'buyer'");
$stats['total_buyers'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total sellers
$stmt = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'seller'");
$stats['total_sellers'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Pending applications
$stmt = $conn->query("SELECT COUNT(*) as total FROM seller_applications WHERE application_status = 'pending'");
$stats['pending_applications'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total products
$stmt = $conn->query("SELECT COUNT(*) as total FROM products");
$stats['total_products'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total orders
$stmt = $conn->query("SELECT COUNT(*) as total FROM orders");
$stats['total_orders'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total revenue (from delivered orders)
$stmt = $conn->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE order_status = 'delivered'");
$stats['total_revenue'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// ✅ NEW: Pending withdrawals
$stmt = $conn->query("SELECT COUNT(*) as total FROM withdrawal_requests WHERE status = 'pending'");
$stats['pending_withdrawals'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// ✅ NEW: Pending pickups
$stmt = $conn->query("SELECT COUNT(*) as total FROM orders WHERE lgu_delivery_status = 'pending_pickup'");
$stats['pending_pickups'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// ✅ NEW: Pending payment confirmations
$stmt = $conn->query("SELECT COUNT(*) as total FROM orders WHERE lgu_delivery_status = 'delivered' AND payment_received_by_lgu_at IS NULL");
$stats['pending_payments'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Recent users (last 5)
$stmt = $conn->query("SELECT userID, full_name, email, role, created_at FROM users ORDER BY created_at DESC LIMIT 5");
$recent_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Recent orders (last 5)
$stmt = $conn->query("SELECT o.orderID, u.full_name, o.total_amount, o.order_status, o.order_date 
                      FROM orders o 
                      JOIN users u ON o.buyerID = u.userID 
                      ORDER BY o.order_date DESC LIMIT 5");
$recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Agri Tayo Rito</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/responsive.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/admin/dashboard.css">
</head>
<body>
    <div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

    <?php 
    // Include unified sidebar component
    require_once __DIR__ . '/admin-nav.php'; 
    ?>

    <!-- Main Content -->
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">Dashboard Overview</h1>
            <p class="page-subtitle">Welcome back, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?>!</p>
        </div>

        <!-- ✅ NEW: Quick Action Alerts -->
        <?php if ($stats['pending_withdrawals'] > 0 || $stats['pending_pickups'] > 0 || $stats['pending_payments'] > 0): ?>
        <section class="alerts-section">
            <?php if ($stats['pending_withdrawals'] > 0): ?>
            <div class="alert-card withdrawal">
                <div class="alert-icon">💰</div>
                <div class="alert-content">
                    <h3><?php echo $stats['pending_withdrawals']; ?> Pending Withdrawal<?php echo $stats['pending_withdrawals'] > 1 ? 's' : ''; ?></h3>
                    <p>Sellers are waiting for withdrawal approval</p>
                </div>
                <button onclick="location.href='<?php echo BASE_URL; ?>profile/admin/withdrawals'" class="alert-btn">Review Now</button>
            </div>
            <?php endif; ?>

            <?php if ($stats['pending_pickups'] > 0): ?>
            <div class="alert-card pickup">
                <div class="alert-icon">📦</div>
                <div class="alert-content">
                    <h3><?php echo $stats['pending_pickups']; ?> Order<?php echo $stats['pending_pickups'] > 1 ? 's' : ''; ?> Awaiting Pickup</h3>
                    <p>Products need to be picked up from sellers</p>
                </div>
                <button onclick="location.href='<?php echo BASE_URL; ?>profile/admin/deliveries'" class="alert-btn">View Orders</button>
            </div>
            <?php endif; ?>

            <?php if ($stats['pending_payments'] > 0): ?>
            <div class="alert-card payment">
                <div class="alert-icon">💵</div>
                <div class="alert-content">
                    <h3><?php echo $stats['pending_payments']; ?> Payment<?php echo $stats['pending_payments'] > 1 ? 's' : ''; ?> to Confirm</h3>
                    <p>Delivered orders need payment confirmation</p>
                </div>
                <button onclick="location.href='<?php echo BASE_URL; ?>profile/admin/deliveries'" class="alert-btn">Confirm Now</button>
            </div>
            <?php endif; ?>
        </section>
        <?php endif; ?>

        <!-- Stats Grid -->
        <section class="stats-section">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">👥</div>
                <div class="stat-info">
                    <h3 class="stat-value"><?php echo number_format($stats['total_users']); ?></h3>
                    <p class="stat-label">Total Users</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">🏪</div>
                <div class="stat-info">
                    <h3 class="stat-value"><?php echo number_format($stats['total_sellers']); ?></h3>
                    <p class="stat-label">Total Sellers</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">📋</div>
                <div class="stat-info">
                    <h3 class="stat-value"><?php echo number_format($stats['pending_applications']); ?></h3>
                    <p class="stat-label">Pending Applications</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);">📦</div>
                <div class="stat-info">
                    <h3 class="stat-value"><?php echo number_format($stats['total_products']); ?></h3>
                    <p class="stat-label">Total Products</p>
                </div>
            </div>

            <!-- ✅ NEW: Pending Withdrawals Stat -->
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);">💸</div>
                <div class="stat-info">
                    <h3 class="stat-value"><?php echo number_format($stats['pending_withdrawals']); ?></h3>
                    <p class="stat-label">Pending Withdrawals</p>
                </div>
            </div>

            <!-- ✅ NEW: Pending Pickups Stat -->
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #ff9a56 0%, #ff6a88 100%);">📦</div>
                <div class="stat-info">
                    <h3 class="stat-value"><?php echo number_format($stats['pending_pickups']); ?></h3>
                    <p class="stat-label">Pending Pickups</p>
                </div>
            </div>
        </section>

        <!-- Recent Activity -->
        <div class="content-grid">
            <section class="content-section">
                <div class="section-header">
                    <h2 class="section-title">Recent Users</h2>
                    <a href="<?php echo BASE_URL; ?>profile/admin/users" class="view-all-link">View All →</a>
                </div>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Joined</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $user['role']; ?>">
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="content-section">
                <div class="section-header">
                    <h2 class="section-title">Recent Orders</h2>
                    <a href="<?php echo BASE_URL; ?>profile/admin/orders" class="view-all-link">View All →</a>
                </div>
                <div class="table-container">
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
                </div>
            </section>
        </div>
    </main>

    <style>
        /* ✅ NEW: Alert Cards Styling */
        .alerts-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .alert-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border-left: 4px solid;
        }

        .alert-card.withdrawal {
            border-left-color: #FFD700;
            background: linear-gradient(to right, #fffbeb 0%, white 100%);
        }

        .alert-card.pickup {
            border-left-color: #667eea;
            background: linear-gradient(to right, #f0f4ff 0%, white 100%);
        }

        .alert-card.payment {
            border-left-color: #16a34a;
            background: linear-gradient(to right, #f0fdf4 0%, white 100%);
        }

        .alert-icon {
            font-size: 2rem;
        }

        .alert-content h3 {
            margin: 0 0 5px 0;
            font-size: 1.1rem;
        }

        .alert-content p {
            margin: 0;
            color: #666;
            font-size: 0.9rem;
        }

        .alert-btn {
            margin-left: auto;
            padding: 10px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            white-space: nowrap;
        }

        .alert-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
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