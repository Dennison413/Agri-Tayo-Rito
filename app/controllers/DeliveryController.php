<?php
// app/controllers/DeliveryController.php
// LGU Delivery Management - Rider Assignment, Status Updates, Delivery Tracking
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../models/Orders.php';

class DeliveryController 
{
    private $ordersModel;
    private $ridersModel;

    public function __construct() 
    {
        $this->ordersModel = new Orders();
        $this->ridersModel = new DeliveryRiders();
    }

    // ==================== ADMIN: ASSIGN RIDER ====================

    /**
     * Assign rider to order for delivery
     * Flow: Order placed → LGU picks up → Assign rider → Deliver
     */
    public function assignRider() 
    {
        // Check admin authorization
        if (!$this->isLoggedIn() || $_SESSION['user_role'] !== 'admin') {
            $_SESSION['error'] = 'Unauthorized access';
            header('Location: /agri_system/public/auth/login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = 'Invalid request';
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
        }

        try {
            $orderID = intval($_POST['order_id']);
            $riderID = intval($_POST['rider_id']);

            // Validate inputs
            if (!$orderID || !$riderID) {
                $_SESSION['error'] = 'Order ID and Rider ID are required';
                header('Location: ' . $_SERVER['HTTP_REFERER']);
                exit;
            }

            // Get order details
            $order = $this->ordersModel->getOrderById($orderID);

            if (!$order) {
                $_SESSION['error'] = 'Order not found';
                header('Location: /agri_system/public/profile/admin/deliveries');
                exit;
            }

            // Check if order is ready for rider assignment
            if ($order['lgu_delivery_status'] !== 'pending_pickup') {
                $_SESSION['error'] = 'Order is not ready for rider assignment';
                header('Location: /agri_system/public/profile/admin/deliveries');
                exit;
            }

            // Assign rider
            $result = $this->ordersModel->assignRider($orderID, $riderID);

            if ($result) {
                // Update delivery status to picked_up
                $this->ordersModel->updateDeliveryStatus($orderID, 'picked_up');

                $_SESSION['success'] = 'Rider assigned successfully';
            } else {
                $_SESSION['error'] = 'Failed to assign rider';
            }

            header('Location: /agri_system/public/profile/admin/deliveries');
            exit;

        } catch (Exception $e) {
            error_log("Assign rider error: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to assign rider';
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
        }
    }

    // ==================== ADMIN: UPDATE DELIVERY STATUS ====================

    /**
     * Update LGU delivery status
     * Statuses: pending_pickup → picked_up → in_transit → delivered
     */
    public function updateDeliveryStatus() 
    {
        if (!$this->isLoggedIn() || $_SESSION['user_role'] !== 'admin') {
            $_SESSION['error'] = 'Unauthorized access';
            header('Location: /agri_system/public/auth/login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = 'Invalid request';
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
        }

        try {
            $orderID = intval($_POST['order_id']);
            $status = $_POST['delivery_status'];
            $adminID = $_SESSION['user_id'];

            // Validate status
            $validStatuses = ['pending_pickup', 'picked_up', 'in_transit', 'delivered', 'failed'];
            if (!in_array($status, $validStatuses)) {
                $_SESSION['error'] = 'Invalid delivery status';
                header('Location: ' . $_SERVER['HTTP_REFERER']);
                exit;
            }

            // Update delivery status
            $result = $this->ordersModel->updateDeliveryStatus($orderID, $status, $adminID);

            if ($result) {
                // If status is delivered and payment method is COD, mark payment as received
                if ($status === 'delivered') {
                    $order = $this->ordersModel->getOrderById($orderID);
                    if ($order['payment_method_new'] === 'cod' && !$order['payment_received_by_lgu_at']) {
                        $this->ordersModel->markPaymentReceivedByLGU($orderID);
                        $_SESSION['success'] = 'Delivery completed and payment received from buyer. Seller balance credited.';
                    } else {
                        $_SESSION['success'] = 'Delivery status updated successfully';
                    }
                } else {
                    $_SESSION['success'] = 'Delivery status updated successfully';
                }
            } else {
                $_SESSION['error'] = 'Failed to update delivery status';
            }

            header('Location: /agri_system/public/profile/admin/deliveries');
            exit;

        } catch (Exception $e) {
            error_log("Update delivery status error: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to update delivery status';
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
        }
    }

    // ==================== ADMIN: GET PENDING DELIVERIES ====================

    /**
     * Get orders pending LGU pickup
     */
    public function getPendingPickups() 
    {
        $query = "SELECT o.*, 
                        u.full_name as buyer_name, 
                        u.phone as buyer_phone,
                        u.address as buyer_address,
                        GROUP_CONCAT(DISTINCT s.shop_name SEPARATOR ', ') as shops
                 FROM orders o
                 JOIN users u ON o.buyerID = u.userID
                 JOIN order_items oi ON o.orderID = oi.orderID
                 JOIN products p ON oi.productID = p.productID
                 JOIN shops s ON p.shopID = s.shopID
                 WHERE o.lgu_delivery_status = 'pending_pickup'
                 GROUP BY o.orderID
                 ORDER BY o.order_date ASC";

        try {
            $db = new Database();
            $conn = $db->connect();
            $stmt = $conn->query($query);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Get pending pickups error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get orders in transit
     */
    public function getOrdersInTransit() 
    {
        $query = "SELECT o.*, 
                        u.full_name as buyer_name,
                        u.phone as buyer_phone,
                        dr.rider_name,
                        dr.contact_number as rider_phone
                 FROM orders o
                 JOIN users u ON o.buyerID = u.userID
                 LEFT JOIN delivery_riders dr ON o.assigned_rider_id = dr.riderID
                 WHERE o.lgu_delivery_status IN ('picked_up', 'in_transit')
                 ORDER BY o.rider_assigned_at ASC";

        try {
            $db = new Database();
            $conn = $db->connect();
            $stmt = $conn->query($query);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Get in transit orders error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get completed deliveries
     */
    public function getCompletedDeliveries($limit = 50) 
    {
        $query = "SELECT o.*, 
                        u.full_name as buyer_name,
                        dr.rider_name
                 FROM orders o
                 JOIN users u ON o.buyerID = u.userID
                 LEFT JOIN delivery_riders dr ON o.assigned_rider_id = dr.riderID
                 WHERE o.lgu_delivery_status = 'delivered'
                 ORDER BY o.delivered_at DESC
                 LIMIT ?";

        try {
            $db = new Database();
            $conn = $db->connect();
            $stmt = $conn->prepare($query);
            $stmt->bindValue(1, $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Get completed deliveries error: " . $e->getMessage());
            return [];
        }
    }

    // ==================== RIDER MANAGEMENT ====================

    /**
     * Get all active riders
     */
    public function getActiveRiders() 
    {
        return $this->ridersModel->getActiveRiders();
    }

    /**
     * Add new rider
     */
    public function addRider() 
    {
        if (!$this->isLoggedIn() || $_SESSION['user_role'] !== 'admin') {
            $_SESSION['error'] = 'Unauthorized access';
            header('Location: /agri_system/public/auth/login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = 'Invalid request';
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
        }

        try {
            $data = [
                'rider_name' => $_POST['rider_name'] ?? '',
                'contact_number' => $_POST['contact_number'] ?? '',
                'vehicle_type' => $_POST['vehicle_type'] ?? '',
                'vehicle_plate' => $_POST['vehicle_plate'] ?? null
            ];

            // Validate required fields
            if (empty($data['rider_name']) || empty($data['contact_number'])) {
                $_SESSION['error'] = 'Rider name and contact number are required';
                header('Location: ' . $_SERVER['HTTP_REFERER']);
                exit;
            }

            $result = $this->ridersModel->addRider($data);

            if ($result['success']) {
                $_SESSION['success'] = $result['message'];
            } else {
                $_SESSION['error'] = $result['message'];
            }

            header('Location: /agri_system/public/profile/admin/riders');
            exit;

        } catch (Exception $e) {
            error_log("Add rider error: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to add rider';
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
        }
    }

    /**
     * Update rider details
     */
    public function updateRider() 
    {
        if (!$this->isLoggedIn() || $_SESSION['user_role'] !== 'admin') {
            $_SESSION['error'] = 'Unauthorized access';
            header('Location: /agri_system/public/auth/login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = 'Invalid request';
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
        }

        try {
            $riderID = intval($_POST['rider_id']);
            $data = [
                'rider_name' => $_POST['rider_name'] ?? '',
                'contact_number' => $_POST['contact_number'] ?? '',
                'vehicle_type' => $_POST['vehicle_type'] ?? '',
                'vehicle_plate' => $_POST['vehicle_plate'] ?? null
            ];

            $result = $this->ridersModel->updateRider($riderID, $data);

            if ($result) {
                $_SESSION['success'] = 'Rider updated successfully';
            } else {
                $_SESSION['error'] = 'Failed to update rider';
            }

            header('Location: /agri_system/public/profile/admin/riders');
            exit;

        } catch (Exception $e) {
            error_log("Update rider error: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to update rider';
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
        }
    }

    /**
     * Deactivate rider
     */
    public function deactivateRider() 
    {
        if (!$this->isLoggedIn() || $_SESSION['user_role'] !== 'admin') {
            $_SESSION['error'] = 'Unauthorized access';
            header('Location: /agri_system/public/auth/login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = 'Invalid request';
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
        }

        try {
            $riderID = intval($_POST['rider_id']);

            $result = $this->ridersModel->deactivateRider($riderID);

            if ($result) {
                $_SESSION['success'] = 'Rider deactivated successfully';
            } else {
                $_SESSION['error'] = 'Failed to deactivate rider';
            }

            header('Location: /agri_system/public/profile/admin/riders');
            exit;

        } catch (Exception $e) {
            error_log("Deactivate rider error: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to deactivate rider';
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
        }
    }

    /**
     * Get rider statistics
     */
    public function getRiderStats($riderID) 
    {
        return $this->ridersModel->getRiderStats($riderID);
    }

    // ==================== DELIVERY STATISTICS ====================

    /**
     * Get delivery statistics for admin dashboard
     */
    public function getDeliveryStats() 
    {
        $query = "SELECT 
                    COUNT(*) as total_deliveries,
                    SUM(CASE WHEN lgu_delivery_status = 'pending_pickup' THEN 1 ELSE 0 END) as pending_pickup,
                    SUM(CASE WHEN lgu_delivery_status = 'picked_up' THEN 1 ELSE 0 END) as picked_up,
                    SUM(CASE WHEN lgu_delivery_status = 'in_transit' THEN 1 ELSE 0 END) as in_transit,
                    SUM(CASE WHEN lgu_delivery_status = 'delivered' THEN 1 ELSE 0 END) as delivered,
                    SUM(CASE WHEN lgu_delivery_status = 'failed' THEN 1 ELSE 0 END) as failed,
                    SUM(CASE WHEN lgu_delivery_status = 'delivered' AND DATE(delivered_at) = CURDATE() THEN 1 ELSE 0 END) as delivered_today
                 FROM orders";

        try {
            $db = new Database();
            $conn = $db->connect();
            $stmt = $conn->query($query);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Get delivery stats error: " . $e->getMessage());
            return [];
        }
    }

    // ==================== UTILITY METHODS ====================

    /**
     * Check if user is logged in
     */
    private function isLoggedIn() 
    {
        return isset($_SESSION['user_id']) && 
               isset($_SESSION['logged_in']) && 
               $_SESSION['logged_in'] === true;
    }

    /**
     * Format delivery status badge
     */
    public function getStatusBadge($status) 
    {
        $badges = [
            'pending_pickup' => ['label' => 'Pending Pickup', 'class' => 'warning'],
            'picked_up' => ['label' => 'Picked Up', 'class' => 'info'],
            'in_transit' => ['label' => 'In Transit', 'class' => 'primary'],
            'delivered' => ['label' => 'Delivered', 'class' => 'success'],
            'failed' => ['label' => 'Failed', 'class' => 'danger']
        ];

        return $badges[$status] ?? ['label' => 'Unknown', 'class' => 'secondary'];
    }
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller = new DeliveryController();
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'assign_rider':
            $controller->assignRider();
            break;
        case 'update_delivery_status':
            $controller->updateDeliveryStatus();
            break;
        case 'add_rider':
            $controller->addRider();
            break;
        case 'update_rider':
            $controller->updateRider();
            break;
        case 'deactivate_rider':
            $controller->deactivateRider();
            break;
        default:
            $_SESSION['error'] = 'Invalid action';
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
    }
}