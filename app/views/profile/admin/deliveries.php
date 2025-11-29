<?php
// app/views/profile/admin/deliveries.php
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

// Get delivery statistics
$stats = [];

$stmt = $conn->query("SELECT COUNT(*) as total FROM orders WHERE lgu_delivery_status = 'pending_pickup'");
$stats['pending_pickup'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM orders WHERE lgu_delivery_status = 'picked_up'");
$stats['picked_up'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM orders WHERE lgu_delivery_status = 'in_transit'");
$stats['in_transit'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM orders WHERE lgu_delivery_status = 'delivered' AND payment_received_by_lgu_at IS NULL");
$stats['awaiting_payment'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Get all orders with delivery tracking
$query = "SELECT o.*, u.full_name as buyer_name, u.phone, dr.rider_name
          FROM orders o
          JOIN users u ON o.buyerID = u.userID
          LEFT JOIN delivery_riders dr ON o.assigned_rider_id = dr.riderID
          WHERE o.lgu_delivery_status != 'delivered' OR o.payment_received_by_lgu_at IS NULL
          ORDER BY 
            CASE o.lgu_delivery_status
              WHEN 'pending_pickup' THEN 1
              WHEN 'picked_up' THEN 2
              WHEN 'in_transit' THEN 3
              WHEN 'delivered' THEN 4
            END,
            o.order_date DESC";

$stmt = $conn->query($query);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get available riders
$riders = $conn->query("SELECT * FROM delivery_riders WHERE is_active = 1")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LGU Delivery Management - Admin</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/responsive.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/admin/dashboard.css">
</head>
<body>
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <div class="overlay" id="overlay" onclick="toggleSidebar()"></div>
    
    <?php 
    // Include unified sidebar component
    require_once __DIR__ . '/admin-nav.php'; 
    ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">🚚 LGU Delivery Management</h1>
            <p class="page-subtitle">Track and manage deliveries handled by LGU</p>
        </div>

        <!-- Delivery Stats -->
        <section class="stats-section">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">📦</div>
                <div class="stat-info">
                    <h3 class="stat-value"><?php echo number_format($stats['pending_pickup']); ?></h3>
                    <p class="stat-label">Pending Pickup from Sellers</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">📥</div>
                <div class="stat-info">
                    <h3 class="stat-value"><?php echo number_format($stats['picked_up']); ?></h3>
                    <p class="stat-label">Picked Up - Ready for Delivery</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">🚚</div>
                <div class="stat-info">
                    <h3 class="stat-value"><?php echo number_format($stats['in_transit']); ?></h3>
                    <p class="stat-label">Out for Delivery</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #2d5016 0%, #4a7c25 100%);">💵</div>
                <div class="stat-info">
                    <h3 class="stat-value"><?php echo number_format($stats['awaiting_payment']); ?></h3>
                    <p class="stat-label">Awaiting Payment Confirmation</p>
                </div>
            </div>
        </section>

        <!-- Orders Table -->
        <section class="orders-section">
            <div class="section-header">
                <h2 class="section-title">Active Deliveries</h2>
                <span class="badge"><?php echo count($orders); ?> orders</span>
            </div>

            <?php if (count($orders) > 0): ?>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Buyer</th>
                            <th>Amount</th>
                            <th>Payment</th>
                            <th>Delivery Status</th>
                            <th>Rider</th>
                            <th>Order Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>#<?php echo $order['orderID']; ?></td>
                            <td>
                                <div><strong><?php echo htmlspecialchars($order['buyer_name']); ?></strong></div>
                                <small><?php echo htmlspecialchars($order['phone']); ?></small>
                            </td>
                            <td class="amount">₱<?php echo number_format($order['total_amount'], 2); ?></td>
                            <td>
                                <span class="payment-badge payment-<?php echo $order['payment_method_new']; ?>">
                                    <?php echo strtoupper($order['payment_method_new']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge delivery-<?php echo $order['lgu_delivery_status']; ?>">
                                    <?php echo str_replace('_', ' ', ucfirst($order['lgu_delivery_status'])); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($order['rider_name']): ?>
                                    <span class="rider-info">🏍️ <?php echo htmlspecialchars($order['rider_name']); ?></span>
                                <?php else: ?>
                                    <span class="text-muted">Not assigned</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                            <td>
                                <button onclick="showDeliveryActions(<?php echo $order['orderID']; ?>, '<?php echo $order['lgu_delivery_status']; ?>')" 
                                        class="btn-action">Update</button>
                                <?php if ($order['lgu_delivery_status'] === 'delivered' && !$order['payment_received_by_lgu_at']): ?>
                                    <button onclick="confirmPayment(<?php echo $order['orderID']; ?>)" 
                                            class="btn-payment">Confirm Payment</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="no-data">
                <p>✅ All deliveries completed and payments confirmed!</p>
            </div>
            <?php endif; ?>
        </section>
    </main>

    <!-- Delivery Update Modal -->
    <div id="deliveryModal" class="modal">
        <div class="modal-content">
            <h3>Update Delivery Status</h3>
            <form method="POST" action="<?php echo BASE_URL; ?>api/delivery-status">
                <input type="hidden" name="order_id" id="update_order_id">
                
                <div class="form-group">
                    <label>Delivery Status *</label>
                    <select name="delivery_status" id="delivery_status" required>
                        <option value="pending_pickup">Pending Pickup</option>
                        <option value="picked_up">Picked Up</option>
                        <option value="in_transit">In Transit</option>
                        <option value="delivered">Delivered</option>
                        <option value="failed">Failed Delivery</option>
                    </select>
                </div>

                <div class="form-group" id="rider_group">
                    <label>Assign Rider</label>
                    <select name="rider_id">
                        <option value="">No rider</option>
                        <?php foreach ($riders as $rider): ?>
                            <option value="<?php echo $rider['riderID']; ?>">
                                <?php echo htmlspecialchars($rider['rider_name']); ?> (<?php echo htmlspecialchars($rider['vehicle_type']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <input type="hidden" name="action" value="update_delivery_status">
                
                <div class="modal-actions">
                    <button type="button" onclick="closeModal()" class="btn-cancel">Cancel</button>
                    <button type="submit" class="btn-confirm">Update Status</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Payment Confirmation Modal -->
    <div id="paymentModal" class="modal">
        <div class="modal-content">
            <h3>Confirm Payment Received</h3>
            <p>Confirm that payment has been received from the buyer. This will credit the seller's balance.</p>
            <form method="POST" action="<?php echo BASE_URL; ?>app/controllers/OrderController.php">
                <input type="hidden" name="order_id" id="payment_order_id">
                <input type="hidden" name="action" value="confirm_payment_received">
                
                <div class="modal-actions">
                    <button type="button" onclick="closeModal()" class="btn-cancel">Cancel</button>
                    <button type="submit" class="btn-confirm">Confirm Payment Received</button>
                </div>
            </form>
        </div>
    </div>

    <style>
        .payment-badge {
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: bold;
        }

        .payment-cod {
            background: #dcfce7;
            color: #166534;
        }

        .payment-gcash, .payment-paymaya {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 500;
            white-space: nowrap;
        }

        .delivery-pending_pickup {
            background: #fef3c7;
            color: #92400e;
        }

        .delivery-picked_up {
            background: #dbeafe;
            color: #1e40af;
        }

        .delivery-in_transit {
            background: #e0e7ff;
            color: #4338ca;
        }

        .delivery-delivered {
            background: #dcfce7;
            color: #166534;
        }

        .delivery-failed {
            background: #fee2e2;
            color: #991b1b;
        }

        .btn-action {
            padding: 8px 15px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            margin-right: 5px;
        }

        .btn-payment {
            padding: 8px 15px;
            background: #16a34a;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        .rider-info {
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 12px;
            max-width: 500px;
            width: 90%;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
        }

        .modal-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .btn-cancel {
            flex: 1;
            padding: 12px;
            background: #e5e7eb;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }

        .btn-confirm {
            flex: 1;
            padding: 12px;
            background: #16a34a;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
        }

        .amount {
            font-weight: bold;
            color: #2d5016;
        }
    </style>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        }

        function showDeliveryActions(orderId, currentStatus) {
            document.getElementById('update_order_id').value = orderId;
            document.getElementById('delivery_status').value = currentStatus;
            document.getElementById('deliveryModal').classList.add('active');
        }

        function confirmPayment(orderId) {
            document.getElementById('payment_order_id').value = orderId;
            document.getElementById('paymentModal').classList.add('active');
        }

        function closeModal() {
            document.querySelectorAll('.modal').forEach(m => m.classList.remove('active'));
        }
    </script>
</body>
</html>