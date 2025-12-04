<?php
// notifications.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../../config/config.php';

$userRole = $_SESSION['user_role'] ?? 'buyer';
$userId = $_SESSION['user_id'] ?? null;

// Mock notifications - replace with database queries
$notifications = [
    // Buyer notifications
    [
        'id' => 1,
        'type' => 'order_accepted',
        'icon' => '✅',
        'title' => 'Order Accepted',
        'message' => 'Your order #12345 has been accepted by Fresh Farm Store',
        'time' => '2 hours ago',
        'read' => false,
        'roles' => ['buyer']
    ],
    [
        'id' => 2,
        'type' => 'tracking',
        'icon' => '🚚',
        'title' => 'Order Shipped',
        'message' => 'Your parcel has been picked up by LGU delivery logistics',
        'time' => '5 hours ago',
        'read' => false,
        'roles' => ['buyer']
    ],
    [
        'id' => 3,
        'type' => 'message',
        'icon' => '💬',
        'title' => 'New Message',
        'message' => 'Fresh Farm Store sent you a message',
        'time' => '1 day ago',
        'read' => true,
        'roles' => ['buyer', 'seller']
    ],
    // Seller notifications
    [
        'id' => 4,
        'type' => 'new_order',
        'icon' => '🛍️',
        'title' => 'New Order',
        'message' => 'You received a new order #12346 from Juan Cruz',
        'time' => '30 minutes ago',
        'read' => false,
        'roles' => ['seller']
    ],
    [
        'id' => 5,
        'type' => 'order_cancelled',
        'icon' => '❌',
        'title' => 'Order Cancelled',
        'message' => 'Order #12340 has been cancelled by the buyer',
        'time' => '3 hours ago',
        'read' => false,
        'roles' => ['seller']
    ],
    // Admin notifications
    [
        'id' => 6,
        'type' => 'new_application',
        'icon' => '📝',
        'title' => 'New Seller Application',
        'message' => 'Maria Santos submitted a seller application',
        'time' => '1 hour ago',
        'read' => false,
        'roles' => ['admin']
    ],
    [
        'id' => 7,
        'type' => 'report',
        'icon' => '🚨',
        'title' => 'User Reported',
        'message' => 'A user has been reported for suspicious activity',
        'time' => '4 hours ago',
        'read' => false,
        'roles' => ['admin']
    ],
    [
        'id' => 8,
        'type' => 'deletion_request',
        'icon' => '🗑️',
        'title' => 'Account Deletion Request',
        'message' => 'Pedro Reyes requested account deletion',
        'time' => '6 hours ago',
        'read' => true,
        'roles' => ['admin']
    ]
];

// Filter notifications by role
$filteredNotifications = array_filter($notifications, function($notif) use ($userRole) {
    return in_array($userRole, $notif['roles']);
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Agri Tayo Rito</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/marketplace/marketplace.css">
    <style>
        .notifications-container {
            max-width: 800px;
            margin: 0 auto;
        }

        .notifications-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .notifications-title {
            font-size: 1.8rem;
            color: #2d5016;
            font-weight: 700;
        }

        .mark-all-read {
            background: none;
            border: 2px solid #2d5016;
            color: #2d5016;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .mark-all-read:hover {
            background: #2d5016;
            color: white;
        }

        .notifications-list {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .notification-item {
            display: flex;
            gap: 15px;
            padding: 15px 20px;
            border-bottom: 1px solid #e0e0e0;
            transition: all 0.3s;
            cursor: pointer;
            position: relative;
        }

        .notification-item:last-child {
            border-bottom: none;
        }

        .notification-item:hover {
            background: #f8f9fa;
        }

        .notification-item.unread {
            background: #f0f7ed;
        }

        .notification-item.unread::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: #2d5016;
        }

        .notification-icon {
            font-size: 2rem;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f0f7ed;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .notification-content {
            flex: 1;
        }

        .notification-title {
            font-size: 1rem;
            color: #2d5016;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .notification-message {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 5px;
        }

        .notification-time {
            font-size: 0.75rem;
            color: #999;
        }

        .notification-actions {
            display: flex;
            flex-direction: column;
            gap: 8px;
            justify-content: center;
        }

        .notification-action-btn {
            background: none;
            border: 1px solid #e0e0e0;
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 0.75rem;
            cursor: pointer;
            transition: all 0.3s;
            white-space: nowrap;
        }

        .notification-action-btn:hover {
            background: #2d5016;
            color: white;
            border-color: #2d5016;
        }

        .no-notifications {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .no-notifications-icon {
            font-size: 4rem;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        .filter-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            overflow-x: auto;
            padding-bottom: 5px;
        }

        .filter-tab {
            background: white;
            border: 2px solid #e0e0e0;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            white-space: nowrap;
        }

        .filter-tab.active {
            background: #2d5016;
            color: white;
            border-color: #2d5016;
        }

        @media (max-width: 768px) {
            .notifications-title {
                font-size: 1.4rem;
            }

            .notification-item {
                padding: 12px 15px;
            }

            .notification-icon {
                width: 40px;
                height: 40px;
                font-size: 1.5rem;
            }

            .notification-actions {
                display: none;
            }

            .mark-all-read {
                font-size: 0.75rem;
                padding: 6px 12px;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/marketnav.php'; ?>

    <div class="main-content">
        <div class="notifications-container">
            <div class="notifications-header">
                <h1 class="notifications-title">Notifications</h1>
                <button class="mark-all-read" onclick="markAllAsRead()">Mark all as read</button>
            </div>

            <div class="filter-tabs">
                <button class="filter-tab active" data-filter="all">All</button>
                <button class="filter-tab" data-filter="unread">Unread</button>
                <button class="filter-tab" data-filter="order">Orders</button>
                <button class="filter-tab" data-filter="message">Messages</button>
            </div>

            <div class="notifications-list">
                <?php if (empty($filteredNotifications)): ?>
                    <div class="no-notifications">
                        <div class="no-notifications-icon">🔔</div>
                        <h3>No notifications yet</h3>
                        <p>We'll notify you when something important happens</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($filteredNotifications as $notif): ?>
                        <div class="notification-item <?php echo !$notif['read'] ? 'unread' : ''; ?>" 
                             data-id="<?php echo $notif['id']; ?>"
                             onclick="markAsRead(<?php echo $notif['id']; ?>)">
                            <div class="notification-icon"><?php echo $notif['icon']; ?></div>
                            <div class="notification-content">
                                <div class="notification-title"><?php echo $notif['title']; ?></div>
                                <div class="notification-message"><?php echo $notif['message']; ?></div>
                                <div class="notification-time"><?php echo $notif['time']; ?></div>
                            </div>
                            <div class="notification-actions">
                                <button class="notification-action-btn" onclick="event.stopPropagation(); viewDetails(<?php echo $notif['id']; ?>)">View</button>
                                <button class="notification-action-btn" onclick="event.stopPropagation(); deleteNotification(<?php echo $notif['id']; ?>)">Delete</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

    <script>
        // Filter tabs
        document.querySelectorAll('.filter-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                
                const filter = this.dataset.filter;
                // Add filtering logic here
                console.log('Filter by:', filter);
            });
        });

        function markAsRead(id) {
            const item = document.querySelector(`[data-id="${id}"]`);
            if (item) {
                item.classList.remove('unread');
            }
            // Add AJAX call to update database
            console.log('Mark as read:', id);
        }

        function markAllAsRead() {
            document.querySelectorAll('.notification-item').forEach(item => {
                item.classList.remove('unread');
            });
            showToast('All notifications marked as read');
        }

        function viewDetails(id) {
            console.log('View details:', id);
            // Redirect to relevant page based on notification type
        }

        function deleteNotification(id) {
            const item = document.querySelector(`[data-id="${id}"]`);
            if (item) {
                item.style.opacity = '0';
                setTimeout(() => item.remove(), 300);
            }
            showToast('Notification deleted');
        }

        function showToast(message) {
            const toast = document.createElement('div');
            toast.className = 'notification-toast show';
            toast.textContent = message;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
    </script>
</body>
</html>