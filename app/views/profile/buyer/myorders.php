<?php
// public/marketplace/myorders.php
// Buyer's Orders Page
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../models/Orders.php';
require_once __DIR__ . '/../../../helpers/csrf.php';

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'buyer') {
    header('Location: ' . BASE_URL . 'auth/login');
    exit;
}

$buyerID = $_SESSION['user_id'];
$ordersModel = new Orders();

// Get orders
$allOrders = $ordersModel->getOrdersByBuyer($buyerID, 50, 0);

// Group orders by status
$ordersByStatus = [
    'pending' => [],
    'processing' => [],
    'shipped' => [],
    'delivered' => [],
    'cancelled' => []
];

foreach ($allOrders as $order) {
    $ordersByStatus[$order['order_status']][] = $order;
}

// Get order stats
$stats = $ordersModel->getOrderStats($buyerID, 'buyer');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Agri Tayo Rito</title>
    <style>
        .orders-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            padding-top: 20px;
        }

        .orders-header {
            margin-bottom: 30px;
        }

        .orders-header h1 {
            color: #2d5016;
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #2d5016;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 0.85rem;
            color: #666;
        }

        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            overflow-x: auto;
            padding-bottom: 10px;
        }

        .tab-btn {
            padding: 12px 24px;
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s;
            white-space: nowrap;
        }

        .tab-btn:hover {
            border-color: #2d5016;
        }

        .tab-btn.active {
            background: #2d5016;
            color: white;
            border-color: #2d5016;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .order-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
            margin-bottom: 15px;
        }

        .order-number {
            font-weight: 700;
            color: #2d5016;
            font-size: 1.1rem;
        }

        .order-date {
            color: #666;
            font-size: 0.85rem;
        }

        .status-badge {
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-processing {
            background: #cce5ff;
            color: #004085;
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

        .order-items {
            margin-bottom: 15px;
        }

        .order-item {
            display: flex;
            gap: 15px;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .item-image {
            width: 80px;
            height: 80px;
            border-radius: 8px;
            object-fit: cover;
        }

        .item-details {
            flex: 1;
        }

        .item-name {
            font-weight: 600;
            color: #2d5016;
            margin-bottom: 5px;
        }

        .item-shop {
            color: #666;
            font-size: 0.85rem;
            margin-bottom: 5px;
        }

        .item-quantity {
            color: #666;
            font-size: 0.85rem;
        }

        .item-price {
            text-align: right;
            font-weight: 700;
            color: #2d5016;
        }

        .order-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 2px solid #f0f0f0;
        }

        .order-total {
            font-size: 1.2rem;
            font-weight: 700;
            color: #2d5016;
        }

        .order-actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #2d5016;
            color: white;
        }

        .btn-primary:hover {
            background: #1f3810;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .empty-state-icon {
            font-size: 4rem;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            color: #2d5016;
            margin-bottom: 10px;
        }

        .delivery-timeline {
            margin: 15px 0;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 8px;
        }

        .timeline-step {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 0;
            color: #666;
            font-size: 0.85rem;
        }

        .timeline-step.completed {
            color: #2d5016;
            font-weight: 600;
        }

        .timeline-icon {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
        }

        .timeline-step.completed .timeline-icon {
            background: #2d5016;
            color: white;
        }

        @media (max-width: 768px) {
            .orders-container {
                padding: 15px;
                padding-top: 15px;
            }

            .order-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .order-item {
                flex-direction: column;
            }

            .item-image {
                width: 100%;
                height: 150px;
            }

            .order-footer {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .order-actions {
                width: 100%;
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar Navigation -->
    <?php require_once __DIR__ . '/../../marketplace/marketnav.php'; ?>

    <!-- Overlay -->
    <div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="orders-container">
            
            <!-- Header -->
            <div class="orders-header">
                <h1>📦 My Orders</h1>
            </div>

            <!-- Stats -->
            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['total_orders']; ?></div>
                    <div class="stat-label">Total Orders</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['pending']; ?></div>
                    <div class="stat-label">Pending</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['processing']; ?></div>
                    <div class="stat-label">Processing</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['delivered']; ?></div>
                    <div class="stat-label">Delivered</div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="tabs">
                <button class="tab-btn active" onclick="showTab('all')">
                    All (<?php echo count($allOrders); ?>)
                </button>
                <button class="tab-btn" onclick="showTab('pending')">
                    Pending (<?php echo count($ordersByStatus['pending']); ?>)
                </button>
                <button class="tab-btn" onclick="showTab('processing')">
                    Processing (<?php echo count($ordersByStatus['processing']); ?>)
                </button>
                <button class="tab-btn" onclick="showTab('shipped')">
                    Shipped (<?php echo count($ordersByStatus['shipped']); ?>)
                </button>
                <button class="tab-btn" onclick="showTab('delivered')">
                    Delivered (<?php echo count($ordersByStatus['delivered']); ?>)
                </button>
                <button class="tab-btn" onclick="showTab('cancelled')">
                    Cancelled (<?php echo count($ordersByStatus['cancelled']); ?>)
                </button>
            </div>

            <!-- All Orders Tab -->
            <div id="tab-all" class="tab-content active">
                <?php if (empty($allOrders)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">📦</div>
                        <h3>No orders yet</h3>
                        <p>Start shopping in the marketplace!</p>
                        <br>
                        <a href="<?php echo BASE_URL; ?>marketplace" class="btn btn-primary">
                            Go to Marketplace
                        </a>
                    </div>
                <?php else: ?>
                    <?php foreach ($allOrders as $order): 
                        $orderItems = (new OrderItems())->getOrderItems($order['orderID']);
                    ?>
                        <?php echo renderOrderCard($order, $orderItems); ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Status-specific tabs -->
            <?php foreach (['pending', 'processing', 'shipped', 'delivered', 'cancelled'] as $status): ?>
                <div id="tab-<?php echo $status; ?>" class="tab-content">
                    <?php if (empty($ordersByStatus[$status])): ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">📦</div>
                            <h3>No <?php echo $status; ?> orders</h3>
                        </div>
                    <?php else: ?>
                        <?php foreach ($ordersByStatus[$status] as $order): 
                            $orderItems = (new OrderItems())->getOrderItems($order['orderID']);
                        ?>
                            <?php echo renderOrderCard($order, $orderItems); ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');
            
            if (sidebar) sidebar.classList.toggle('active');
            if (overlay) overlay.classList.toggle('active');
        }

        function showTab(tabName) {
            const tabs = document.querySelectorAll('.tab-content');
            tabs.forEach(tab => tab.classList.remove('active'));

            const buttons = document.querySelectorAll('.tab-btn');
            buttons.forEach(btn => btn.classList.remove('active'));

            document.getElementById('tab-' + tabName).classList.add('active');
            event.target.classList.add('active');
        }

        function viewOrderDetails(orderID) {
            window.location.href = '<?php echo BASE_URL; ?>profile/buyer/order-details?id=' + orderID;
        }

        function cancelOrder(orderID) {
            if (confirm('Are you sure you want to cancel this order?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '<?php echo BASE_URL; ?>app/controllers/OrderController.php';

                const csrfField = document.querySelector('[name="csrf_token"]').cloneNode();
                const actionField = document.createElement('input');
                actionField.type = 'hidden';
                actionField.name = 'action';
                actionField.value = 'cancel_order';

                const orderField = document.createElement('input');
                orderField.type = 'hidden';
                orderField.name = 'order_id';
                orderField.value = orderID;

                form.appendChild(csrfField);
                form.appendChild(actionField);
                form.appendChild(orderField);

                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>

    <?php echo CSRF::getTokenField(); ?>
</body>
</html>

<?php
function renderOrderCard($order, $items) {
    $statusClass = 'status-' . $order['order_status'];
    $statusLabel = ucfirst(str_replace('_', ' ', $order['order_status']));
    
    ob_start();
    ?>
    <div class="order-card">
        <div class="order-header">
            <div>
                <div class="order-number">Order #<?php echo $order['orderID']; ?></div>
                <div class="order-date">
                    <?php echo date('F d, Y g:i A', strtotime($order['order_date'])); ?>
                </div>
            </div>
            <span class="status-badge <?php echo $statusClass; ?>">
                <?php echo $statusLabel; ?>
            </span>
        </div>

        <div class="order-items">
            <?php foreach ($items as $item): ?>
                <div class="order-item">
                    <img src="<?php echo BASE_URL . htmlspecialchars($item['primary_image'] ?? 'images/placeholder.jpg'); ?>" 
                         alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                         class="item-image">
                    <div class="item-details">
                        <div class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                        <div class="item-shop">🏪 <?php echo htmlspecialchars($item['shop_name']); ?></div>
                        <div class="item-quantity">Qty: <?php echo $item['quantity']; ?> <?php echo $item['unit']; ?></div>
                    </div>
                    <div class="item-price">
                        ₱<?php echo number_format($item['subtotal'], 2); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($order['order_status'] !== 'cancelled'): ?>
            <div class="delivery-timeline">
                <div class="timeline-step completed">
                    <div class="timeline-icon">✓</div>
                    <span>Order Placed</span>
                </div>
                <div class="timeline-step <?php echo in_array($order['lgu_delivery_status'], ['picked_up', 'in_transit', 'delivered']) ? 'completed' : ''; ?>">
                    <div class="timeline-icon">🚚</div>
                    <span>LGU Pickup</span>
                </div>
                <div class="timeline-step <?php echo in_array($order['lgu_delivery_status'], ['in_transit', 'delivered']) ? 'completed' : ''; ?>">
                    <div class="timeline-icon">📍</div>
                    <span>Out for Delivery</span>
                </div>
                <div class="timeline-step <?php echo $order['lgu_delivery_status'] === 'delivered' ? 'completed' : ''; ?>">
                    <div class="timeline-icon">✓</div>
                    <span>Delivered</span>
                </div>
            </div>
        <?php endif; ?>

        <div class="order-footer">
            <div class="order-total">
                Total: ₱<?php echo number_format($order['total_amount'], 2); ?>
            </div>
            <div class="order-actions">
                <button class="btn btn-primary" onclick="viewOrderDetails(<?php echo $order['orderID']; ?>)">
                    View Details
                </button>
                <?php if ($order['order_status'] === 'pending'): ?>
                    <button class="btn btn-danger" onclick="cancelOrder(<?php echo $order['orderID']; ?>)">
                        Cancel Order
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
?>