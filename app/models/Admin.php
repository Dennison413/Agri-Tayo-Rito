<?php
// app/models/Admin.php
require_once __DIR__ . '/../../config/database.php';

class Admin 
{
    private $conn;

    public function __construct() 
    {
        $database = new Database();
        $this->conn = $database->connect();
    }

    // dashboard analytics
    public function getDashboardStats() 
    {
        $stats = [];

        // User Statistics
        $userQuery = "SELECT 
                        COUNT(*) as total_users,
                        SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_users,
                        SUM(CASE WHEN role = 'buyer' THEN 1 ELSE 0 END) as total_buyers,
                        SUM(CASE WHEN role = 'seller' THEN 1 ELSE 0 END) as total_sellers,
                        SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as new_today
                     FROM users";
        $stmt = $this->conn->query($userQuery);
        $stats['users'] = $stmt->fetch(PDO::FETCH_ASSOC);

        // Order Statistics
        $orderQuery = "SELECT 
                        COUNT(*) as total_orders,
                        SUM(CASE WHEN order_status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
                        SUM(CASE WHEN order_status = 'processing' THEN 1 ELSE 0 END) as processing_orders,
                        SUM(CASE WHEN order_status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
                        SUM(CASE WHEN DATE(order_date) = CURDATE() THEN 1 ELSE 0 END) as orders_today,
                        SUM(total_amount) as total_revenue,
                        SUM(CASE WHEN DATE(order_date) = CURDATE() THEN total_amount ELSE 0 END) as revenue_today
                      FROM orders";
        $stmt = $this->conn->query($orderQuery);
        $stats['orders'] = $stmt->fetch(PDO::FETCH_ASSOC);

        // LGU Delivery Statistics
        $deliveryQuery = "SELECT 
                            SUM(CASE WHEN lgu_delivery_status = 'pending_pickup' THEN 1 ELSE 0 END) as pending_pickup,
                            SUM(CASE WHEN lgu_delivery_status = 'picked_up' THEN 1 ELSE 0 END) as picked_up,
                            SUM(CASE WHEN lgu_delivery_status = 'in_transit' THEN 1 ELSE 0 END) as in_transit,
                            SUM(CASE WHEN lgu_delivery_status = 'delivered' THEN 1 ELSE 0 END) as delivered_total,
                            SUM(CASE WHEN lgu_delivery_status = 'delivered' AND DATE(delivered_at) = CURDATE() THEN 1 ELSE 0 END) as delivered_today
                         FROM orders";
        $stmt = $this->conn->query($deliveryQuery);
        $stats['deliveries'] = $stmt->fetch(PDO::FETCH_ASSOC);

        // Product Statistics
        $productQuery = "SELECT 
                            COUNT(*) as total_products,
                            SUM(CASE WHEN is_available = 1 THEN 1 ELSE 0 END) as active_products,
                            SUM(CASE WHEN (stock_quantity - reserved_quantity) <= low_stock_threshold THEN 1 ELSE 0 END) as low_stock
                        FROM products";
        $stmt = $this->conn->query($productQuery);
        $stats['products'] = $stmt->fetch(PDO::FETCH_ASSOC);

        // Pending Actions (things admin needs to review)
        $pendingQuery = "SELECT 
                            (SELECT COUNT(*) FROM seller_applications WHERE application_status = 'pending') as pending_applications,
                            (SELECT COUNT(*) FROM account_deletion_requests WHERE status = 'pending') as pending_deletions,
                            (SELECT COUNT(*) FROM withdrawal_requests WHERE status = 'pending') as pending_withdrawals";
        $stmt = $this->conn->query($pendingQuery);
        $stats['pending_actions'] = $stmt->fetch(PDO::FETCH_ASSOC);

        // Active Riders
        $riderQuery = "SELECT 
                        COUNT(*) as total_riders,
                        SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_riders,
                        SUM(total_deliveries) as total_deliveries_all_time
                      FROM delivery_riders";
        $stmt = $this->conn->query($riderQuery);
        $stats['riders'] = $stmt->fetch(PDO::FETCH_ASSOC);

        return $stats;
    }

    // Get revenue analytics over time
    public function getRevenueAnalytics($startDate = null, $endDate = null) 
    {
        $startDate = $startDate ?? date('Y-m-01'); // First day of current month
        $endDate = $endDate ?? date('Y-m-d'); // Today

        $query = "SELECT 
                    DATE(order_date) as date,
                    COUNT(*) as orders_count,
                    SUM(total_amount) as daily_revenue,
                    SUM(CASE WHEN payment_method_new = 'cod' THEN total_amount ELSE 0 END) as cod_revenue,
                    SUM(CASE WHEN payment_method_new IN ('gcash', 'paymaya') THEN total_amount ELSE 0 END) as online_revenue
                 FROM orders
                 WHERE DATE(order_date) BETWEEN ? AND ?
                   AND order_status != 'cancelled'
                 GROUP BY DATE(order_date)
                 ORDER BY date ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$startDate, $endDate]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get top selling products
    public function getTopSellingProducts($limit = 10) 
    {
        $query = "SELECT 
                    p.productID,
                    p.product_name,
                    s.shop_name,
                    SUM(oi.quantity) as total_sold,
                    SUM(oi.subtotal) as total_revenue,
                    COUNT(DISTINCT oi.orderID) as order_count
                 FROM order_items oi
                 JOIN products p ON oi.productID = p.productID
                 JOIN shops s ON p.shopID = s.shopID
                 JOIN orders o ON oi.orderID = o.orderID
                 WHERE o.order_status = 'delivered'
                 GROUP BY p.productID
                 ORDER BY total_sold DESC
                 LIMIT ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // get top performing shops
    public function getTopShops($limit = 10) 
    {
        $query = "SELECT 
                    s.shopID,
                    s.shop_name,
                    s.shop_slug,
                    s.rating,
                    s.total_orders,
                    s.total_earned,
                    sp.business_name,
                    u.full_name as seller_name
                 FROM shops s
                 JOIN seller_profiles sp ON s.sellerID = sp.sellerID
                 JOIN users u ON sp.userID = u.userID
                 WHERE s.is_active = 1
                 ORDER BY s.total_earned DESC
                 LIMIT ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ==================== USER MANAGEMENT ====================

    // Get all users with filters and pagination
    public function getAllUsers($filters = [], $limit = 50, $offset = 0) 
    {
        $query = "SELECT userID, email, full_name, phone, municipality, role, is_active, created_at 
                 FROM users WHERE 1=1";
        $params = [];

        // Role filter
        if (!empty($filters['role'])) {
            $query .= " AND role = ?";
            $params[] = $filters['role'];
        }

        // Active status filter
        if (isset($filters['is_active'])) {
            $query .= " AND is_active = ?";
            $params[] = $filters['is_active'];
        }

        // Search filter
        if (!empty($filters['search'])) {
            $query .= " AND (full_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $query .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get user activity log
    public function getUserActivity($userID, $limit = 20) 
    {
        // This combines various user activities
        $activities = [];

        // Orders
        $orderQuery = "SELECT 'order' as type, orderID as id, order_date as date, 
                             CONCAT('Order #', orderID, ' - ', order_status) as description
                      FROM orders WHERE buyerID = ? 
                      ORDER BY order_date DESC LIMIT ?";
        $stmt = $this->conn->prepare($orderQuery);
        $stmt->bindValue(1, $userID, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        $activities = array_merge($activities, $stmt->fetchAll(PDO::FETCH_ASSOC));

        // Sort all activities by date
        usort($activities, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });

        return array_slice($activities, 0, $limit);
    }

    // Activate or Ban User
    public function toggleUserStatus($userID, $adminID, $reason = null) 
    {
        // Get current status
        $checkQuery = "SELECT is_active, role FROM users WHERE userID = ?";
        $stmt = $this->conn->prepare($checkQuery);
        $stmt->execute([$userID]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return ['success' => false, 'message' => 'User not found'];
        }

        // Can't ban admins
        if ($user['role'] === 'admin') {
            return ['success' => false, 'message' => 'Cannot modify admin accounts'];
        }

        $newStatus = $user['is_active'] ? 0 : 1;
        $updateQuery = "UPDATE users SET is_active = ? WHERE userID = ?";
        
        try {
            $stmt = $this->conn->prepare($updateQuery);
            $result = $stmt->execute([$newStatus, $userID]);

            if ($result) {
                $action = $newStatus ? 'activated' : 'deactivated';
                return [
                    'success' => true,
                    'message' => "User {$action} successfully",
                    'new_status' => $newStatus
                ];
            }
        } catch (PDOException $e) {
            error_log("Toggle user status error: " . $e->getMessage());
        }

        return ['success' => false, 'message' => 'Failed to update user status'];
    }

    // ==================== ACCOUNT DELETION REQUESTS ====================

    // Get all pending deletion requests
    public function getPendingDeletionRequests() 
    {
        $query = "SELECT adr.*, u.full_name, u.email, u.role 
                 FROM account_deletion_requests adr
                 JOIN users u ON adr.userID = u.userID
                 WHERE adr.status = 'pending'
                 ORDER BY adr.requested_at ASC";
        $stmt = $this->conn->query($query);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Approve deletion request
    public function approveDeletionRequest($requestID, $adminID) 
    {
        try {
            $this->conn->beginTransaction();

            // Get request details
            $query = "SELECT userID FROM account_deletion_requests WHERE requestID = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$requestID]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$request) {
                return ['success' => false, 'message' => 'Request not found'];
            }

            // Update request status
            $updateQuery = "UPDATE account_deletion_requests 
                           SET status = 'approved', 
                               reviewed_by = ?, 
                               reviewed_at = NOW() 
                           WHERE requestID = ?";
            $stmt = $this->conn->prepare($updateQuery);
            $stmt->execute([$adminID, $requestID]);

            // Delete user (CASCADE will handle related records)
            $userModel = new User();
            $userModel->deleteUser($request['userID']);

            $this->conn->commit();

            return ['success' => true, 'message' => 'Account deletion approved and processed'];

        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Approve deletion error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to process deletion'];
        }
    }

    // Reject deletion request
    public function rejectDeletionRequest($requestID, $adminID, $reason = null) 
    {
        $query = "UPDATE account_deletion_requests 
                 SET status = 'rejected', 
                     reviewed_by = ?, 
                     reviewed_at = NOW() 
                 WHERE requestID = ?";
        
        try {
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([$adminID, $requestID]);

            if ($result) {
                return ['success' => true, 'message' => 'Deletion request rejected'];
            }
        } catch (PDOException $e) {
            error_log("Reject deletion error: " . $e->getMessage());
        }

        return ['success' => false, 'message' => 'Failed to reject request'];
    }

    // ==================== CATEGORY MANAGEMENT ====================

    // Add new category
    public function addCategory($categoryName, $imageUrl = null) 
    {
        $categoryModel = new Category();
        return $categoryModel->createCategory($categoryName);
    }

    // Update category
    public function updateCategory($categoryID, $newName) 
    {
        $categoryModel = new Category();
        return $categoryModel->updateCategory($categoryID, $newName);
    }

    // Delete category
    public function deleteCategory($categoryID) 
    {
        $categoryModel = new Category();
        return $categoryModel->deleteCategory($categoryID);
    }

    // ==================== DELIVERY & ORDER MANAGEMENT ====================

    // Get orders pending pickup
    public function getOrdersPendingPickup() 
    {
        $query = "SELECT o.*, u.full_name as buyer_name, u.phone as buyer_phone,
                        GROUP_CONCAT(DISTINCT s.shop_name SEPARATOR ', ') as shops
                 FROM orders o
                 JOIN users u ON o.buyerID = u.userID
                 JOIN order_items oi ON o.orderID = oi.orderID
                 JOIN products p ON oi.productID = p.productID
                 JOIN shops s ON p.shopID = s.shopID
                 WHERE o.lgu_delivery_status = 'pending_pickup'
                 GROUP BY o.orderID
                 ORDER BY o.order_date ASC";
        $stmt = $this->conn->query($query);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Assign rider to order
    public function assignRiderToOrder($orderID, $riderID, $adminID) 
    {
        $ordersModel = new Orders();
        $result = $ordersModel->assignRider($orderID, $riderID);

        if ($result) {
            // Update delivery status to picked_up
            $ordersModel->updateDeliveryStatus($orderID, 'picked_up', $adminID);
            
            return ['success' => true, 'message' => 'Rider assigned successfully'];
        }

        return ['success' => false, 'message' => 'Failed to assign rider'];
    }

    // Mark payment received from buyer
    public function receivePaymentFromBuyer($orderID, $adminID) 
    {
        $ordersModel = new Orders();
        $result = $ordersModel->markPaymentReceivedByLGU($orderID);

        if ($result) {
            return ['success' => true, 'message' => 'Payment received and credited to seller'];
        }

        return ['success' => false, 'message' => 'Failed to process payment'];
    }

    // ==================== SYSTEM SETTINGS ====================

    // Get specific system setting
    public function getSystemSetting($key) 
    {
        $query = "SELECT * FROM system_settings WHERE setting_key = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$key]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Get all system settings
    public function getAllSystemSettings() 
    {
        $query = "SELECT * FROM system_settings ORDER BY settingID ASC";
        $stmt = $this->conn->query($query);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Update system setting
    public function updateSystemSetting($key, $value) 
    {
        $query = "UPDATE system_settings SET setting_value = ? WHERE setting_key = ?";
        
        try {
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([$value, $key]);

            if ($result) {
                return ['success' => true, 'message' => 'Setting updated successfully'];
            }
        } catch (PDOException $e) {
            error_log("Update setting error: " . $e->getMessage());
        }

        return ['success' => false, 'message' => 'Failed to update setting'];
    }

    // ==================== REPORTS ====================

    // Sales report
    public function generateSalesReport($startDate, $endDate) 
    {
        $query = "SELECT 
                    DATE(o.order_date) as date,
                    COUNT(DISTINCT o.orderID) as total_orders,
                    COUNT(DISTINCT o.buyerID) as unique_buyers,
                    SUM(o.total_amount) as revenue,
                    SUM(CASE WHEN o.payment_method_new = 'cod' THEN o.total_amount ELSE 0 END) as cod_amount,
                    SUM(CASE WHEN o.payment_method_new IN ('gcash', 'paymaya') THEN o.total_amount ELSE 0 END) as online_amount,
                    AVG(o.total_amount) as avg_order_value
                 FROM orders o
                 WHERE DATE(o.order_date) BETWEEN ? AND ?
                   AND o.order_status != 'cancelled'
                 GROUP BY DATE(o.order_date)
                 ORDER BY date ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$startDate, $endDate]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Seller earnings report
    public function generateSellerEarningsReport($startDate, $endDate) 
    {
        $query = "SELECT 
                    s.shopID,
                    s.shop_name,
                    sp.business_name,
                    u.full_name as seller_name,
                    COUNT(DISTINCT o.orderID) as total_orders,
                    SUM(oi.subtotal) as total_earnings,
                    s.balance as current_balance,
                    s.total_withdrawn
                 FROM shops s
                 JOIN seller_profiles sp ON s.sellerID = sp.sellerID
                 JOIN users u ON sp.userID = u.userID
                 LEFT JOIN products p ON s.shopID = p.shopID
                 LEFT JOIN order_items oi ON p.productID = oi.productID
                 LEFT JOIN orders o ON oi.orderID = o.orderID 
                    AND DATE(o.order_date) BETWEEN ? AND ?
                    AND o.order_status = 'delivered'
                 WHERE s.is_active = 1
                 GROUP BY s.shopID
                 ORDER BY total_earnings DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$startDate, $endDate]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Delivery performance report
    public function getDeliveryPerformance($startDate = null, $endDate = null) 
    {
        $startDate = $startDate ?? date('Y-m-01');
        $endDate = $endDate ?? date('Y-m-d');

        $query = "SELECT 
                    COUNT(*) as total_deliveries,
                    AVG(TIMESTAMPDIFF(HOUR, rider_assigned_at, delivered_at)) as avg_delivery_hours,
                    SUM(CASE WHEN TIMESTAMPDIFF(HOUR, rider_assigned_at, delivered_at) <= 24 THEN 1 ELSE 0 END) as same_day_deliveries,
                    SUM(CASE WHEN lgu_delivery_status = 'failed' THEN 1 ELSE 0 END) as failed_deliveries
                 FROM orders
                 WHERE DATE(order_date) BETWEEN ? AND ?
                   AND lgu_delivery_status = 'delivered'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$startDate, $endDate]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function confirmOnlinePaymentReceived($orderID, $adminID) 
    {
        // For GCash/Paymaya orders
        $ordersModel = new Orders();
        $order = $ordersModel->getOrderById($orderID);
        
        if ($order['payment_method_new'] !== 'cod') {
            // Confirm LGU received the online payment
            return $ordersModel->markPaymentReceivedByLGU($orderID);
        }
        
        return ['success' => false, 'message' => 'This is not an online payment order'];
    }
}
