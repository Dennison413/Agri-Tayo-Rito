<?php
// app/models/Orders.php
require_once __DIR__ . '/../../config/database.php';

class Orders 
{
    private $conn;
    private $table = 'orders';

    public function __construct() 
    {
        $database = new Database();
        $this->conn = $database->connect();
    }

    // create new order
    public function createOrder($buyerID, $orderData, $cartItems) 
    {
        try {
            require_once __DIR__ . '/Cart.php';
            $cartModel = new Cart();
            $validation = $cartModel->validateCartForCheckout($buyerID);

            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'message' => 'Cart validation failed: ' . implode(', ', $validation['errors'])
                ];
            }

            error_log("=== STARTING ORDER CREATION ===");
            error_log("Buyer ID: $buyerID");
            error_log("Total Amount: " . $orderData['total_amount']);
            error_log("Cart Items Count: " . count($cartItems));

            // Start transaction
            $this->conn->beginTransaction();
            error_log("Transaction started");

            // Insert order
            $query = "INSERT INTO {$this->table} 
                     (buyerID, total_amount, delivery_address, delivery_municipality, 
                      delivery_province, delivery_postal_code, payment_method_new, 
                      shipping_fee, notes) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([
                $buyerID,
                $orderData['total_amount'],
                $orderData['delivery_address'],
                $orderData['delivery_municipality'],
                $orderData['delivery_province'],
                $orderData['delivery_postal_code'],
                $orderData['payment_method'],
                0.00,
                $orderData['notes'] ?? null
            ]);

            if (!$result) {
                throw new Exception("Failed to insert order");
            }

            $orderID = $this->conn->lastInsertId();
            error_log("Order created with ID: $orderID");

            $orderedProductIDs = [];

            foreach ($cartItems as $item) {
                $itemSubtotal = $item['quantity'] * $item['price'];
                
                $itemQuery = "INSERT INTO order_items 
                             (orderID, productID, quantity, unit_price, subtotal) 
                             VALUES (?, ?, ?, ?, ?)";
                
                $itemStmt = $this->conn->prepare($itemQuery);
                $itemResult = $itemStmt->execute([
                    $orderID,
                    $item['productID'],
                    $item['quantity'],
                    $item['price'],
                    $itemSubtotal
                ]);

                if (!$itemResult) {
                    throw new Exception("Failed to add order item: " . $item['product_name']);
                }

                $orderedProductIDs[] = $item['productID'];
                
                error_log("Added item: {$item['product_name']} x {$item['quantity']}");
            }

            // handle online payment status
            if (in_array($orderData['payment_method'], ['gcash', 'paymaya'])) {
                $updatePayment = "UPDATE {$this->table} 
                                 SET payment_status = 'paid' 
                                 WHERE orderID = ?";
                $this->conn->prepare($updatePayment)->execute([$orderID]);
                error_log("Payment status set to 'paid' for online payment");
            }

            if (!empty($orderedProductIDs)) {
                $placeholders = implode(',', array_fill(0, count($orderedProductIDs), '?'));
                $clearCartQuery = "DELETE FROM cart 
                                  WHERE buyerID = ? 
                                  AND productID IN ($placeholders)";
                
                $clearStmt = $this->conn->prepare($clearCartQuery);
                
                $params = array_merge([$buyerID], $orderedProductIDs);
                $clearStmt->execute($params);
                
                error_log("Cleared " . count($orderedProductIDs) . " ordered items from cart for buyer $buyerID");
                error_log("Product IDs removed: " . implode(', ', $orderedProductIDs));
            }

            $this->conn->commit();
            error_log("Transaction committed successfully");

            return [
                'success' => true,
                'message' => 'Order placed successfully',
                'orderID' => $orderID
            ];

        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
                error_log("Transaction rolled back");
            }
            
            error_log("❌ Create order error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            return [
                'success' => false,
                'message' => 'Failed to create order: ' . $e->getMessage()
            ];
        }
    }

    // reserve stock for order items
    private function reserveStock($productID, $quantity) 
    {
        $query = "UPDATE products 
                 SET reserved_quantity = reserved_quantity + ? 
                 WHERE productID = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$quantity, $productID]);
    }

    // release reserved stock when order is cancelled
    public function releaseReservedStock($orderID) 
    {
        $query = "UPDATE products p
                 INNER JOIN order_items oi ON p.productID = oi.productID
                 SET p.reserved_quantity = GREATEST(0, p.reserved_quantity - oi.quantity)
                 WHERE oi.orderID = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$orderID]);
    }

    // deduct actual stock when order is delivered
    public function deductStock($orderID) 
    {
        $query = "UPDATE products p
                 INNER JOIN order_items oi ON p.productID = oi.productID
                 SET p.stock_quantity = GREATEST(0, p.stock_quantity - oi.quantity),
                     p.reserved_quantity = GREATEST(0, p.reserved_quantity - oi.quantity)
                 WHERE oi.orderID = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$orderID]);
    }

    // get order by ID with items
    public function getOrderById($orderID) 
    {
        $query = "SELECT o.*, u.full_name as buyer_name, u.email as buyer_email, u.phone as buyer_phone
                 FROM {$this->table} o
                 JOIN users u ON o.buyerID = u.userID
                 WHERE o.orderID = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$orderID]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($order) {
            $orderItemsModel = new OrderItems();
            $order['items'] = $orderItemsModel->getOrderItems($orderID);
        }

        return $order;
    }

    // get orders (buyer)
    public function getOrdersByBuyer($buyerID, $limit = 10, $offset = 0) 
    {
        $query = "SELECT * FROM {$this->table} 
                 WHERE buyerID = ? 
                 ORDER BY order_date DESC 
                 LIMIT ? OFFSET ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(1, $buyerID, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->bindValue(3, $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // get orders (seller)
    public function getOrdersBySeller($sellerID, $limit = 10, $offset = 0) 
    {
        $query = "SELECT DISTINCT o.*, u.full_name as buyer_name
                 FROM {$this->table} o
                 JOIN order_items oi ON o.orderID = oi.orderID
                 JOIN products p ON oi.productID = p.productID
                 JOIN users u ON o.buyerID = u.userID
                 WHERE p.sellerID = ?
                 ORDER BY o.order_date DESC
                 LIMIT ? OFFSET ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(1, $sellerID, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->bindValue(3, $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // get all orders (admin)
    public function getAllOrders($status = null, $limit = 50, $offset = 0) 
    {
        $query = "SELECT o.*, u.full_name as buyer_name, u.phone as buyer_phone,
                        dr.rider_name, dr.contact_number as rider_phone
                 FROM {$this->table} o
                 JOIN users u ON o.buyerID = u.userID
                 LEFT JOIN delivery_riders dr ON o.assigned_rider_id = dr.riderID";
        
        if ($status) {
            $query .= " WHERE o.order_status = ?";
        }
        
        $query .= " ORDER BY o.order_date DESC LIMIT ? OFFSET ?";
        
        $stmt = $this->conn->prepare($query);
        
        if ($status) {
            $stmt->bindValue(1, $status, PDO::PARAM_STR);
            $stmt->bindValue(2, $limit, PDO::PARAM_INT);
            $stmt->bindValue(3, $offset, PDO::PARAM_INT);
        } else {
            $stmt->bindValue(1, $limit, PDO::PARAM_INT);
            $stmt->bindValue(2, $offset, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // update order status
    public function updateOrderStatus($orderID, $status) 
    {
        $query = "UPDATE {$this->table} SET order_status = ? WHERE orderID = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$status, $orderID]);
    }

    // update payment status
    public function updatePaymentStatus($orderID, $status) 
    {
        $query = "UPDATE {$this->table} SET payment_status = ? WHERE orderID = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$status, $orderID]);
    }

    // update delivery status
    public function updateDeliveryStatus($orderID, $status, $adminID = null) 
    {
        try {
            $this->conn->beginTransaction();

            $query = "UPDATE {$this->table} SET lgu_delivery_status = ?";
            $params = [$status];

            if ($status === 'picked_up') {
                $query .= ", picked_up_at = NOW(), order_status = 'processing'";
            } 
            elseif ($status === 'delivered') {
                $query .= ", delivered_at = NOW(), order_status = 'delivered'";
                $this->deductStock($orderID);
            }

            $query .= " WHERE orderID = ?";
            $params[] = $orderID;

            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute($params);

            if ($status === 'delivered') {
                $order = $this->getOrderById($orderID);
                if ($order['payment_method_new'] === 'cod') {
                    $this->markPaymentReceivedByLGU($orderID);
                }
            }

            $this->conn->commit();
            return $result;

        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Update delivery status error: " . $e->getMessage());
            return false;
        }
    }

    // mark payment as received by LGU
    public function markPaymentReceivedByLGU($orderID) 
    {
        $query = "UPDATE {$this->table} 
                 SET payment_received_by_lgu_at = NOW() 
                 WHERE orderID = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$orderID]);
    }

    // assign rider to order
    public function assignRider($orderID, $riderID) 
    {
        $query = "UPDATE {$this->table} 
                 SET assigned_rider_id = ?, rider_assigned_at = NOW() 
                 WHERE orderID = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$riderID, $orderID]);
    }

    // cancel order
    public function cancelOrder($orderID, $reason = null) 
    {
        try {
            $this->conn->beginTransaction();

            $this->releaseReservedStock($orderID);

            $query = "UPDATE {$this->table} 
                     SET order_status = 'cancelled', 
                         notes = CONCAT(COALESCE(notes, ''), '\nCancellation reason: ', ?)
                     WHERE orderID = ?";
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([$reason, $orderID]);

            $this->conn->commit();
            return $result;

        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Cancel order error: " . $e->getMessage());
            return false;
        }
    }

    // get order statistics
    public function getOrderStats($userID = null, $role = 'buyer') 
    {
        if ($role === 'buyer') {
            $query = "SELECT 
                        COUNT(*) as total_orders,
                        SUM(CASE WHEN order_status = 'pending' THEN 1 ELSE 0 END) as pending,
                        SUM(CASE WHEN order_status = 'processing' THEN 1 ELSE 0 END) as processing,
                        SUM(CASE WHEN order_status = 'shipped' THEN 1 ELSE 0 END) as shipped,
                        SUM(CASE WHEN order_status = 'delivered' THEN 1 ELSE 0 END) as delivered,
                        SUM(CASE WHEN order_status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
                     FROM {$this->table}
                     WHERE buyerID = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$userID]);
        } elseif ($role === 'seller') {
            $query = "SELECT 
                        COUNT(DISTINCT o.orderID) as total_orders,
                        SUM(CASE WHEN o.order_status = 'pending' THEN 1 ELSE 0 END) as pending,
                        SUM(CASE WHEN o.order_status = 'processing' THEN 1 ELSE 0 END) as processing,
                        SUM(CASE WHEN o.order_status = 'shipped' THEN 1 ELSE 0 END) as shipped,
                        SUM(CASE WHEN o.order_status = 'delivered' THEN 1 ELSE 0 END) as delivered,
                        SUM(oi.subtotal) as total_sales
                     FROM {$this->table} o
                     JOIN order_items oi ON o.orderID = oi.orderID
                     JOIN products p ON oi.productID = p.productID
                     WHERE p.sellerID = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$userID]);
        } else {
            $query = "SELECT 
                        COUNT(*) as total_orders,
                        SUM(CASE WHEN order_status = 'pending' THEN 1 ELSE 0 END) as pending,
                        SUM(CASE WHEN order_status = 'processing' THEN 1 ELSE 0 END) as processing,
                        SUM(CASE WHEN lgu_delivery_status = 'pending_pickup' THEN 1 ELSE 0 END) as pending_pickup,
                        SUM(CASE WHEN lgu_delivery_status = 'in_transit' THEN 1 ELSE 0 END) as in_transit,
                        SUM(total_amount) as total_revenue
                     FROM {$this->table}";
            $stmt = $this->conn->query($query);
        }

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

class OrderItems 
{
    private $conn;
    private $table = 'order_items';

    public function __construct() 
    {
        $database = new Database();
        $this->conn = $database->connect();
    }

    // add order item
    public function addOrderItem($orderID, $productID, $quantity, $unitPrice) 
    {
        $subtotal = $quantity * $unitPrice;
        
        $query = "INSERT INTO {$this->table} 
                 (orderID, productID, quantity, unit_price, subtotal) 
                 VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$orderID, $productID, $quantity, $unitPrice, $subtotal]);
    }

    // uses is_main to get primary image
    public function getOrderItems($orderID) 
{
    $query = "SELECT oi.*, 
                    p.product_name, 
                    p.unit, 
                    s.shop_name, 
                    s.shopID,
                    (SELECT pi.image_path 
                     FROM product_images pi 
                     WHERE pi.productID = p.productID 
                     AND pi.is_main = 1 
                     LIMIT 1) as primary_image
             FROM {$this->table} oi
             JOIN products p ON oi.productID = p.productID
             JOIN shops s ON p.shopID = s.shopID
             WHERE oi.orderID = ?
             ORDER BY oi.orderItemID";
    
    $stmt = $this->conn->prepare($query);
    $stmt->execute([$orderID]);
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($results as $item) {
        error_log("Order Item: {$item['product_name']}, Image: " . ($item['primary_image'] ?? 'NULL'));
    }
    
    return $results;
}
}

class DeliveryRiders 
{
    private $conn;
    private $table = 'delivery_riders';

    public function __construct() 
    {
        $database = new Database();
        $this->conn = $database->connect();
    }

    // get active riders
    public function getActiveRiders() 
    {
        $query = "SELECT * FROM {$this->table} 
                 WHERE is_active = 1 
                 ORDER BY rider_name ASC";
        $stmt = $this->conn->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRiderById($riderID) 
    {
        $query = "SELECT * FROM {$this->table} WHERE riderID = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$riderID]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function addRider($data) 
    {
        $query = "INSERT INTO {$this->table} 
                 (rider_name, contact_number, vehicle_type, vehicle_plate) 
                 VALUES (?, ?, ?, ?)";
        
        try {
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([
                $data['rider_name'],
                $data['contact_number'],
                $data['vehicle_type'],
                $data['vehicle_plate'] ?? null
            ]);
            
            return [
                'success' => $result,
                'message' => $result ? 'Rider added successfully' : 'Failed to add rider',
                'riderID' => $result ? $this->conn->lastInsertId() : null
            ];
        } catch (PDOException $e) {
            error_log("Add rider error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to add rider'];
        }
    }

    public function updateRider($riderID, $data) 
    {
        $query = "UPDATE {$this->table} SET ";
        $fields = [];
        $params = [];

        foreach ($data as $key => $value) {
            $fields[] = "$key = ?";
            $params[] = $value;
        }

        $query .= implode(', ', $fields) . " WHERE riderID = ?";
        $params[] = $riderID;

        try {
            $stmt = $this->conn->prepare($query);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Update rider error: " . $e->getMessage());
            return false;
        }
    }

    public function deactivateRider($riderID) 
    {
        $query = "UPDATE {$this->table} SET is_active = 0 WHERE riderID = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$riderID]);
    }

    public function getRiderStats($riderID) 
    {
        $query = "SELECT 
                    dr.total_deliveries,
                    COUNT(o.orderID) as active_deliveries,
                    SUM(CASE WHEN o.lgu_delivery_status = 'delivered' 
                        AND o.delivered_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
                        THEN 1 ELSE 0 END) as deliveries_last_30_days
                 FROM {$this->table} dr
                 LEFT JOIN orders o ON dr.riderID = o.assigned_rider_id 
                    AND o.lgu_delivery_status IN ('picked_up', 'in_transit')
                 WHERE dr.riderID = ?
                 GROUP BY dr.riderID";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$riderID]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>