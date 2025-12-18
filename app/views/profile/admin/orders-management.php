<?php
// app/views/profile/admin/orders-management.php (correct file name)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../config/database.php';

$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'];
$userRole = $_SESSION['user_role'] ?? null;

if (!$isLoggedIn || $userRole !== 'admin') {
    header('Location: ' . BASE_URL . 'auth/login');
    exit;
}

$db = new Database();
$conn = $db->connect();

// Get filter parameters
$statusFilter = $_GET['status'] ?? 'all';
$paymentFilter = $_GET['payment'] ?? 'all';
$searchTerm = $_GET['search'] ?? '';

// Build query
$query = "SELECT o.*, u.full_name as buyer_name, u.email as buyer_email, u.phone,
          GROUP_CONCAT(DISTINCT p.product_name SEPARATOR ', ') as products,
          dr.rider_name
          FROM orders o
          JOIN users u ON o.buyerID = u.userID
          LEFT JOIN order_items oi ON o.orderID = oi.orderID
          LEFT JOIN products p ON oi.productID = p.productID
          LEFT JOIN delivery_riders dr ON o.assigned_rider_id = dr.riderID
          WHERE 1=1";

$params = [];

if ($statusFilter !== 'all') {
    $query .= " AND o.order_status = ?";
    $params[] = $statusFilter;
}

if ($paymentFilter !== 'all') {
    $query .= " AND o.payment_method_new = ?";
    $params[] = $paymentFilter;
}

if ($searchTerm) {
    $query .= " AND (o.orderID LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)";
    $searchParam = "%$searchTerm%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$query .= " GROUP BY o.orderID ORDER BY o.order_date DESC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats = [];
$stmt = $conn->query("SELECT COUNT(*) as total FROM orders");
$stats['total'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM orders WHERE order_status = 'pending'");
$stats['pending'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM orders WHERE order_status = 'processing'");
$stats['processing'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM orders WHERE order_status = 'shipped'");
$stats['shipped'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM orders WHERE order_status = 'delivered'");
$stats['delivered'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM orders WHERE order_status = 'cancelled'");
$stats['cancelled'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conn->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE order_status = 'delivered'");
$stats['revenue'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/jpg" href="/agri_system/public/images/agri-icon.jpg">
    <title>Orders Management - Agri Tayo Rito</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/responsive.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/admin/dashboard.css">
    <style>
        .filters-section {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .filter-group label {
            font-weight: 600;
            color: #374151;
            font-size: 0.9rem;
        }

        .filter-group select,
        .filter-group input {
            padding: 10px 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: border-color 0.3s;
        }

        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: #667eea;
        }

        .filter-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .btn-filter {
            padding: 10px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: transform 0.3s;
        }

        .btn-filter:hover {
            transform: translateY(-2px);
        }

        .btn-reset {
            padding: 10px 20px;
            background: #e5e7eb;
            color: #374151;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }

        .order-status {
            padding: 5px 12px;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .order-status.pending {
            background: #fef3c7;
            color: #92400e;
        }

        .order-status.processing {
            background: #dbeafe;
            color: #1e40af;
        }

        .order-status.shipped {
            background: #e0e7ff;
            color: #4338ca;
        }

        .order-status.delivered {
            background: #dcfce7;
            color: #166534;
        }

        .order-status.cancelled {
            background: #fee2e2;
            color: #991b1b;
        }

        .payment-badge {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .payment-badge.cod {
            background: #dcfce7;
            color: #166534;
        }

        .payment-badge.gcash,
        .payment-badge.paymaya {
            background: #dbeafe;
            color: #1e40af;
        }

        .btn-view-details {
            padding: 6px 12px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 500;
            white-space: nowrap;
        }

        .btn-view-details:hover {
            background: #5568d3;
        }

        .modal-section {
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e5e7eb;
        }

        .modal-section:last-child {
            border-bottom: none;
        }

        .modal-section h4 {
            margin: 0 0 15px 0;
            color: #1f2937;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .info-label {
            font-size: 0.85rem;
            color: #6b7280;
            font-weight: 500;
        }

        .info-value {
            font-size: 0.95rem;
            color: #1f2937;
            font-weight: 600;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .items-table th,
        .items-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        .items-table th {
            background: #f9fafb;
            font-weight: 600;
            color: #374151;
            font-size: 0.9rem;
        }

        .items-table td {
            font-size: 0.9rem;
        }

        .total-row {
            background: #f9fafb;
            font-weight: 700;
            color: #2d5016;
        }

        @media (max-width: 768px) {
            .filters-grid {
                grid-template-columns: 1fr;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <div class="overlay" id="overlay" onclick="toggleSidebar()"></div>
    
    <?php require_once __DIR__ . '/admin-nav.php'; ?>

    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">📦 Orders Management</h1>
            <p class="page-subtitle">View and manage all customer orders</p>
        </div>

        <!-- Statistics -->
        <section class="stats-section">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">📊</div>
                <div class="stat-info">
                    <h3 class="stat-value"><?php echo number_format($stats['total']); ?></h3>
                    <p class="stat-label">Total Orders</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);">⏳</div>
                <div class="stat-info">
                    <h3 class="stat-value"><?php echo number_format($stats['pending']); ?></h3>
                    <p class="stat-label">Pending</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);">🔄</div>
                <div class="stat-info">
                    <h3 class="stat-value"><?php echo number_format($stats['processing']); ?></h3>
                    <p class="stat-label">Processing</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);">🚚</div>
                <div class="stat-info">
                    <h3 class="stat-value"><?php echo number_format($stats['shipped']); ?></h3>
                    <p class="stat-label">Shipped</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">✅</div>
                <div class="stat-info">
                    <h3 class="stat-value"><?php echo number_format($stats['delivered']); ?></h3>
                    <p class="stat-label">Delivered</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #2d5016 0%, #4a7c25 100%);">💰</div>
                <div class="stat-info">
                    <h3 class="stat-value">₱<?php echo number_format($stats['revenue'], 2); ?></h3>
                    <p class="stat-label">Total Revenue</p>
                </div>
            </div>
        </section>

        <!-- Filters -->
        <section class="filters-section">
            <h3 style="margin: 0 0 20px 0; color: #1f2937;">🔍 Filter Orders</h3>
            <form method="GET" action="">
                <div class="filters-grid">
                    <div class="filter-group">
                        <label>Order Status</label>
                        <select name="status">
                            <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Status</option>
                            <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="processing" <?php echo $statusFilter === 'processing' ? 'selected' : ''; ?>>Processing</option>
                            <option value="shipped" <?php echo $statusFilter === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                            <option value="delivered" <?php echo $statusFilter === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                            <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Payment Method</label>
                        <select name="payment">
                            <option value="all" <?php echo $paymentFilter === 'all' ? 'selected' : ''; ?>>All Methods</option>
                            <option value="cod" <?php echo $paymentFilter === 'cod' ? 'selected' : ''; ?>>Cash on Delivery</option>
                            <option value="gcash" <?php echo $paymentFilter === 'gcash' ? 'selected' : ''; ?>>GCash</option>
                            <option value="paymaya" <?php echo $paymentFilter === 'paymaya' ? 'selected' : ''; ?>>PayMaya</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Search Order</label>
                        <input type="text" name="search" placeholder="Order ID, buyer name, email..." 
                               value="<?php echo htmlspecialchars($searchTerm); ?>">
                    </div>
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn-filter">🔍 Apply Filters</button>
                    <button type="button" onclick="location.href='<?php echo BASE_URL; ?>profile/admin/orders'" class="btn-reset">🔄 Reset</button>
                </div>
            </form>
        </section>

        <!-- Orders Table -->
        <section class="table-section">
            <div class="section-header">
                <h2 class="section-title">All Orders</h2>
                <span class="badge"><?php echo count($orders); ?> orders</span>
            </div>

            <?php if (count($orders) > 0): ?>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Buyer</th>
                            <th>Products</th>
                            <th>Amount</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><strong>#<?php echo $order['orderID']; ?></strong></td>
                            <td>
                                <div><strong><?php echo htmlspecialchars($order['buyer_name']); ?></strong></div>
                                <small style="color: #6b7280;"><?php echo htmlspecialchars($order['buyer_email']); ?></small>
                            </td>
                            <td>
                                <div style="max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    <?php echo htmlspecialchars($order['products'] ?? 'N/A'); ?>
                                </div>
                            </td>
                            <td class="amount">₱<?php echo number_format($order['total_amount'], 2); ?></td>
                            <td>
                                <span class="payment-badge <?php echo $order['payment_method_new']; ?>">
                                    <?php echo strtoupper($order['payment_method_new']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="order-status <?php echo $order['order_status']; ?>">
                                    <?php echo ucfirst($order['order_status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                            <td>
                                <button onclick="viewOrderDetails(<?php echo $order['orderID']; ?>)" class="btn-view-details">
                                    👁️ View
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="no-data">
                <p>📭 No orders found matching your filters</p>
            </div>
            <?php endif; ?>
        </section>
    </main>

    <!-- Order Details Modal -->
    <div id="orderModal" class="modal">
        <div class="modal-content large">
            <h3>📦 Order Details</h3>
            <div id="orderDetailsContent">
                <p style="text-align: center; padding: 40px;">Loading...</p>
            </div>
            <div class="modal-actions">
                <button type="button" onclick="closeModal()" class="btn-cancel">Close</button>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');
            if (sidebar && overlay) {
                sidebar.classList.toggle('active');
                overlay.classList.toggle('active');
            }
        }

        function viewOrderDetails(orderID) {
            document.getElementById('orderModal').classList.add('active');
            document.getElementById('orderDetailsContent').innerHTML = '<p style="text-align: center; padding: 40px;">🔄 Loading order details...</p>';
            
            fetch('<?php echo BASE_URL; ?>api/get-order-details?order_id=' + orderID)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayOrderDetails(data.order);
                    } else {
                        document.getElementById('orderDetailsContent').innerHTML = 
                            '<p style="text-align: center; padding: 40px; color: #dc2626;">❌ Failed to load order details</p>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('orderDetailsContent').innerHTML = 
                        '<p style="text-align: center; padding: 40px; color: #dc2626;">❌ Network error</p>';
                });
        }

        function displayOrderDetails(order) {
            const statusClass = order.order_status;
            const paymentClass = order.payment_method_new;
            
            let itemsHTML = '';
            order.items.forEach(item => {
                itemsHTML += `
                    <tr>
                        <td>${item.product_name}</td>
                        <td>${item.quantity}</td>
                        <td>₱${parseFloat(item.unit_price).toFixed(2)}</td>
                        <td>₱${parseFloat(item.subtotal).toFixed(2)}</td>
                    </tr>
                `;
            });
            
            const html = `
                <div class="modal-section">
                    <h4>👤 Customer Information</h4>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Name</span>
                            <span class="info-value">${order.buyer_name}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Email</span>
                            <span class="info-value">${order.buyer_email}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Phone</span>
                            <span class="info-value">${order.phone || 'N/A'}</span>
                        </div>
                    </div>
                </div>

                <div class="modal-section">
                    <h4>📍 Delivery Information</h4>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Address</span>
                            <span class="info-value">${order.delivery_address}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Municipality</span>
                            <span class="info-value">${order.delivery_municipality}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Province</span>
                            <span class="info-value">${order.delivery_province}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Postal Code</span>
                            <span class="info-value">${order.delivery_postal_code}</span>
                        </div>
                        ${order.rider_name ? `
                        <div class="info-item">
                            <span class="info-label">Assigned Rider</span>
                            <span class="info-value">🏍️ ${order.rider_name}</span>
                        </div>
                        ` : ''}
                    </div>
                </div>

                <div class="modal-section">
                    <h4>📦 Order Items</h4>
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Qty</th>
                                <th>Price</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${itemsHTML}
                            <tr class="total-row">
                                <td colspan="3">TOTAL</td>
                                <td>₱${parseFloat(order.total_amount).toFixed(2)}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="modal-section">
                    <h4>💳 Payment & Status</h4>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Payment Method</span>
                            <span class="info-value">
                                <span class="payment-badge ${paymentClass}">${order.payment_method_new.toUpperCase()}</span>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Order Status</span>
                            <span class="info-value">
                                <span class="order-status ${statusClass}">${order.order_status.charAt(0).toUpperCase() + order.order_status.slice(1)}</span>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Order Date</span>
                            <span class="info-value">${new Date(order.order_date).toLocaleString()}</span>
                        </div>
                        ${order.notes ? `
                        <div class="info-item">
                            <span class="info-label">Notes</span>
                            <span class="info-value">${order.notes}</span>
                        </div>
                        ` : ''}
                    </div>
                </div>
            `;
            
            document.getElementById('orderDetailsContent').innerHTML = html;
        }

        function closeModal() {
            document.querySelectorAll('.modal').forEach(m => m.classList.remove('active'));
        }

        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeModal();
                }
            });
        });
    </script>
</body>
</html>