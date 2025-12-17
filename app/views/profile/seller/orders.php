<?php
// app/views/profile/seller/orders.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../config/database.php';
require_once __DIR__ . '/../../../models/Orders.php';

$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'];
$userRole = $_SESSION['user_role'] ?? null;
$userID = $_SESSION['user_id'] ?? null;

if (!$isLoggedIn || $userRole !== 'seller') {
    header('Location: ' . BASE_URL . 'auth/login');
    exit;
}

$db = new Database();
$conn = $db->connect();

$stmt = $conn->prepare("SELECT sellerID FROM seller_profiles WHERE userID = ?");
$stmt->execute([$userID]);
$sellerProfile = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sellerProfile) {
    die("Seller profile not found");
}

$sellerID = $sellerProfile['sellerID'];
$ordersModel = new Orders();
$orders = $ordersModel->getOrdersBySeller($sellerID, 50, 0);
$stats = $ordersModel->getOrderStats($sellerID, 'seller');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/jpg" href="/agri_system/public/images/agri-icon.jpg">
    <title>Agri Tayo Rito</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/responsive.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/seller/dashboard.css">
</head>
<body>
    <?php include 'seller-nav.php'; ?>

    <!-- Main Content -->
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">🛒 Orders</h1>
            <p class="page-subtitle">Manage your orders</p>
        </div>

        <!-- Order Stats -->
        <section class="stats-section">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">📦</div>
                <div class="stat-info">
                    <h3 class="stat-value"><?php echo number_format($stats['total_orders']); ?></h3>
                    <p class="stat-label">Total Orders</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">⏳</div>
                <div class="stat-info">
                    <h3 class="stat-value"><?php echo number_format($stats['pending']); ?></h3>
                    <p class="stat-label">Pending</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">🚚</div>
                <div class="stat-info">
                    <h3 class="stat-value"><?php echo number_format($stats['processing']); ?></h3>
                    <p class="stat-label">Processing</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #2d5016 0%, #4a7c25 100%);">✅</div>
                <div class="stat-info">
                    <h3 class="stat-value"><?php echo number_format($stats['delivered'] ?? 0); ?></h3>
                    <p class="stat-label">Delivered</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);">💰</div>
                <div class="stat-info">
                    <h3 class="stat-value">₱<?php echo number_format($stats['total_sales'], 2); ?></h3>
                    <p class="stat-label">Total Sales</p>
                </div>
            </div>
        </section>

        <!-- Filter Options -->
        <section class="filter-section">
            <div class="filter-group">
                <label>Status:</label>
                <select id="statusFilter" onchange="filterOrders()">
                    <option value="">All Orders</option>
                    <option value="pending">Pending</option>
                    <option value="processing">Processing</option>
                    <option value="shipped">Shipped</option>
                    <option value="delivered">Delivered</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
        </section>

        <!-- Orders List -->
        <section class="content-section">
            <div class="section-header">
                <h2 class="section-title">All Orders</h2>
            </div>
            
            <div class="table-container">
                <?php if (count($orders) > 0): ?>
                <table class="data-table" id="ordersTable">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Items</th>
                            <th>Total Amount</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr data-status="<?php echo $order['order_status']; ?>">
                                <td><strong>#<?php echo $order['orderID']; ?></strong></td>
                                <td>
                                    <?php echo date('M d, Y', strtotime($order['order_date'])); ?>
                                    <br>
                                    <small><?php echo date('h:i A', strtotime($order['order_date'])); ?></small>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($order['buyer_name']); ?></strong>
                                </td>
                                <td>
                                    <button class="btn-link" onclick="viewOrderDetails(<?php echo $order['orderID']; ?>)">
                                        View Items
                                    </button>
                                </td>
                                <td><strong>₱<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                                <td>
                                    <span class="payment-badge payment-<?php echo $order['payment_method_new']; ?>">
                                        <?php echo strtoupper($order['payment_method_new']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $order['order_status']; ?>">
                                        <?php echo ucfirst($order['order_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn-icon" onclick="viewOrderDetails(<?php echo $order['orderID']; ?>)" title="View Details">
                                        👁️
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="empty-state">
                    <p>📦 No orders yet</p>
                    <p class="text-muted">Orders will appear here once customers purchase your products</p>
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
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
            max-width: 200px;
        }

        .filter-group label {
            font-weight: 600;
            font-size: 0.9rem;
        }

        .filter-group select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
        }

        .btn-link {
            background: none;
            border: none;
            color: #667eea;
            cursor: pointer;
            text-decoration: underline;
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

        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-state p {
            font-size: 1.2rem;
            color: #666;
            margin-bottom: 10px;
        }

        .text-muted {
            color: #999;
            font-size: 0.9rem;
        }
    </style>

    <script>
        function filterOrders() {
            const statusFilter = document.getElementById('statusFilter').value;
            const rows = document.querySelectorAll('#ordersTable tbody tr');
            
            rows.forEach(row => {
                const show = !statusFilter || row.dataset.status === statusFilter;
                row.style.display = show ? '' : 'none';
            });
        }

        function viewOrderDetails(orderID) {
            window.location.href = `<?php echo BASE_URL; ?>profile/seller/order-details?id=${orderID}`;
        }
    </script>
</body>
</html>