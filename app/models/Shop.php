<?php
// app/models/Shop.php
// Shop Management, Seller Profiles, Withdrawals, and Transactions
require_once __DIR__ . '/../../config/database.php';

class Shop 
{
    private $conn;
    private $table = 'shops';
    private $sellerProfilesTable = 'seller_profiles';

    public function __construct() 
    {
        $database = new Database();
        $this->conn = $database->connect();
    }

    // Create new shop (called after seller application is approved)
    public function createShop($sellerID, $data) 
    {
        try {
            // Check if seller already has a shop
            $checkQuery = "SELECT shopID FROM {$this->table} WHERE sellerID = ?";
            $stmt = $this->conn->prepare($checkQuery);
            $stmt->execute([$sellerID]);
            
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Seller already has a shop'];
            }

            // Generate unique shop slug
            $shopSlug = $this->generateShopSlug($data['shop_name']);

            $query = "INSERT INTO {$this->table} 
                     (sellerID, shop_name, shop_slug, shop_description, farm_location, 
                      business_hours, contact_number, is_verified, is_active) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, 1, 1)";
            
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([
                $sellerID,
                $data['shop_name'],
                $shopSlug,
                $data['shop_description'] ?? null,
                $data['farm_location'] ?? null,
                $data['business_hours'] ?? null,
                $data['contact_number'] ?? null
            ]);

            if ($result) {
                $shopID = $this->conn->lastInsertId();
                
                // Update seller_profiles with shopID
                $updateProfileQuery = "UPDATE {$this->sellerProfilesTable} 
                                      SET shopID = ? WHERE sellerID = ?";
                $updateStmt = $this->conn->prepare($updateProfileQuery);
                $updateStmt->execute([$shopID, $sellerID]);

                return [
                    'success' => true,
                    'message' => 'Shop created successfully',
                    'shopID' => $shopID,
                    'shop_slug' => $shopSlug
                ];
            }

        } catch (PDOException $e) {
            error_log("Create shop error: " . $e->getMessage());
        }

        return ['success' => false, 'message' => 'Failed to create shop'];
    }

    /**
     * Update shop details
     */
    public function updateShop($shopID, $sellerID, $data) 
    {
        // Verify ownership
        if (!$this->verifyShopOwnership($shopID, $sellerID)) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $fields = [];
        $params = [];

        $allowedFields = ['shop_name', 'shop_description', 'farm_location', 
                         'business_hours', 'contact_number'];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $params[] = $data[$field];
            }
        }

        // If shop_name is being updated, regenerate slug
        if (isset($data['shop_name'])) {
            $newSlug = $this->generateShopSlug($data['shop_name'], $shopID);
            $fields[] = "shop_slug = ?";
            $params[] = $newSlug;
        }

        if (empty($fields)) {
            return ['success' => false, 'message' => 'No fields to update'];
        }

        $query = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE shopID = ?";
        $params[] = $shopID;

        try {
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute($params);

            if ($result) {
                return ['success' => true, 'message' => 'Shop updated successfully'];
            }
        } catch (PDOException $e) {
            error_log("Update shop error: " . $e->getMessage());
        }

        return ['success' => false, 'message' => 'Failed to update shop'];
    }

    /**
     * Deactivate shop (soft delete)
     */
    public function deactivateShop($shopID, $sellerID) 
    {
        if (!$this->verifyShopOwnership($shopID, $sellerID)) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $query = "UPDATE {$this->table} SET is_active = 0 WHERE shopID = ?";
        
        try {
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([$shopID]);

            if ($result) {
                // Also deactivate all products in this shop
                $productQuery = "UPDATE products SET is_available = 0 WHERE shopID = ?";
                $productStmt = $this->conn->prepare($productQuery);
                $productStmt->execute([$shopID]);

                return ['success' => true, 'message' => 'Shop deactivated successfully'];
            }
        } catch (PDOException $e) {
            error_log("Deactivate shop error: " . $e->getMessage());
        }

        return ['success' => false, 'message' => 'Failed to deactivate shop'];
    }

    /**
     * Reactivate shop
     */
    public function reactivateShop($shopID, $sellerID) 
    {
        if (!$this->verifyShopOwnership($shopID, $sellerID)) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $query = "UPDATE {$this->table} SET is_active = 1 WHERE shopID = ?";
        
        try {
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([$shopID]);

            if ($result) {
                return ['success' => true, 'message' => 'Shop reactivated successfully'];
            }
        } catch (PDOException $e) {
            error_log("Reactivate shop error: " . $e->getMessage());
        }

        return ['success' => false, 'message' => 'Failed to reactivate shop'];
    }

    // ==================== SHOP RETRIEVAL ====================

    /**
     * Get shop by ID with full details
     */
    public function getShopById($shopID) 
    {
        $query = "SELECT s.*, 
                        sp.business_name, sp.business_description, sp.rating as seller_rating,
                        u.full_name as seller_name, u.email as seller_email, u.phone as seller_phone
                 FROM {$this->table} s
                 JOIN {$this->sellerProfilesTable} sp ON s.sellerID = sp.sellerID
                 JOIN users u ON sp.userID = u.userID
                 WHERE s.shopID = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$shopID]);
        $shop = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($shop) {
            // Get shop statistics
            $shop['stats'] = $this->getShopStats($shopID);
            
            // Get recent products
            $productModel = new Product();
            $shop['recent_products'] = $productModel->getProductsByShop($shopID, false);
        }

        return $shop;
    }

    /**
     * Get shop by slug (for public shop pages)
     */
    public function getShopBySlug($shopSlug) 
    {
        $query = "SELECT s.*, 
                        sp.business_name, sp.business_description, sp.rating as seller_rating,
                        u.full_name as seller_name
                 FROM {$this->table} s
                 JOIN {$this->sellerProfilesTable} sp ON s.sellerID = sp.sellerID
                 JOIN users u ON sp.userID = u.userID
                 WHERE s.shop_slug = ? AND s.is_active = 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$shopSlug]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get shop by seller ID
     */
    public function getShopBySeller($sellerID) 
    {
        $query = "SELECT s.*,
                        sp.business_name, sp.rating as seller_rating
                 FROM {$this->table} s
                 JOIN {$this->sellerProfilesTable} sp ON s.sellerID = sp.sellerID
                 WHERE s.sellerID = ? 
                 LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$sellerID]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get all active shops with filters
     */
    public function getAllShops($filters = [], $limit = 20, $offset = 0) 
    {
        $query = "SELECT s.*, 
                        sp.business_name, sp.rating as seller_rating
                 FROM {$this->table} s
                 JOIN {$this->sellerProfilesTable} sp ON s.sellerID = sp.sellerID
                 WHERE s.is_active = 1";
        
        $params = [];

        // Search filter
        if (!empty($filters['search'])) {
            $query .= " AND (s.shop_name LIKE ? OR sp.business_name LIKE ? OR s.shop_description LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        // Verified shops only
        if (!empty($filters['verified_only'])) {
            $query .= " AND s.is_verified = 1";
        }

        // Sorting
        $sortOptions = [
            'newest' => 's.created_at DESC',
            'rating' => 's.rating DESC',
            'popular' => 's.total_orders DESC',
            'name' => 's.shop_name ASC'
        ];
        
        $sortBy = $filters['sort'] ?? 'newest';
        $query .= " ORDER BY " . ($sortOptions[$sortBy] ?? $sortOptions['newest']);

        $query .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get shops with balance info (for admin)
     */
    public function getAllShopsWithBalance() 
    {
        $query = "SELECT s.*, 
                        sp.business_name,
                        u.full_name as seller_name, u.email as seller_email
                 FROM {$this->table} s
                 JOIN {$this->sellerProfilesTable} sp ON s.sellerID = sp.sellerID
                 JOIN users u ON sp.userID = u.userID
                 WHERE s.is_active = 1
                 ORDER BY s.balance DESC";
        
        $stmt = $this->conn->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ==================== FINANCIAL MANAGEMENT ====================

    /**
     * Get shop balance details
     */
    public function getBalance($shopID) 
    {
        $query = "SELECT balance, total_earned, total_withdrawn 
                 FROM {$this->table} 
                 WHERE shopID = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$shopID]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get shop by ATM card number (for cash withdrawal verification)
     */
    public function getShopByCardNumber($cardNumber) 
    {
        $query = "SELECT s.*, 
                        sp.business_name,
                        u.full_name as seller_name, u.userID
                 FROM {$this->table} s
                 JOIN {$this->sellerProfilesTable} sp ON s.sellerID = sp.sellerID
                 JOIN users u ON sp.userID = u.userID
                 WHERE s.atm_card_number = ? 
                 LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$cardNumber]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Issue ATM card to shop
     * Called by admin when giving seller their exclusive card
     */
    public function issueATMCard($shopID, $cardNumber) 
    {
        // Check if card number already exists
        $checkQuery = "SELECT shopID FROM {$this->table} WHERE atm_card_number = ?";
        $checkStmt = $this->conn->prepare($checkQuery);
        $checkStmt->execute([$cardNumber]);
        
        if ($checkStmt->fetch()) {
            return [
                'success' => false,
                'message' => 'Card number already issued to another shop'
            ];
        }

        $query = "UPDATE {$this->table} 
                 SET atm_card_number = ?, 
                     atm_card_issued_at = NOW() 
                 WHERE shopID = ?";
        
        try {
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([$cardNumber, $shopID]);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'ATM card issued successfully',
                    'card_number' => $cardNumber
                ];
            }
        } catch (PDOException $e) {
            error_log("ATM card issue error: " . $e->getMessage());
        }
        
        return ['success' => false, 'message' => 'Failed to issue ATM card'];
    }

    /**
     * Get transaction history for shop
     */
    public function getTransactionHistory($shopID, $limit = 50, $offset = 0) 
    {
        $query = "SELECT st.*, u.full_name as processed_by_name
                 FROM seller_transactions st
                 LEFT JOIN users u ON st.processed_by = u.userID
                 WHERE st.shopID = ?
                 ORDER BY st.created_at DESC
                 LIMIT ? OFFSET ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(1, $shopID, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->bindValue(3, $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ==================== SHOP STATISTICS ====================

    /**
     * Get comprehensive shop statistics
     */
    public function getShopStats($shopID) 
    {
        $stats = [];

        // Product statistics
        $productQuery = "SELECT 
                            COUNT(*) as total_products,
                            SUM(CASE WHEN is_available = 1 THEN 1 ELSE 0 END) as active_products,
                            SUM(CASE WHEN (stock_quantity - reserved_quantity) <= low_stock_threshold THEN 1 ELSE 0 END) as low_stock_count
                        FROM products
                        WHERE shopID = ?";
        $stmt = $this->conn->prepare($productQuery);
        $stmt->execute([$shopID]);
        $stats['products'] = $stmt->fetch(PDO::FETCH_ASSOC);

        // Order statistics
        $orderQuery = "SELECT 
                        COUNT(DISTINCT o.orderID) as total_orders,
                        SUM(CASE WHEN o.order_status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
                        SUM(CASE WHEN o.order_status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
                        SUM(oi.subtotal) as total_revenue,
                        AVG(oi.subtotal) as avg_order_value
                      FROM orders o
                      JOIN order_items oi ON o.orderID = oi.orderID
                      JOIN products p ON oi.productID = p.productID
                      WHERE p.shopID = ?";
        $stmt = $this->conn->prepare($orderQuery);
        $stmt->execute([$shopID]);
        $stats['orders'] = $stmt->fetch(PDO::FETCH_ASSOC);

        // Review statistics
        $reviewQuery = "SELECT 
                          COUNT(*) as total_reviews,
                          AVG(r.rating) as average_rating
                       FROM reviews r
                       JOIN products p ON r.productID = p.productID
                       WHERE p.shopID = ?";
        $stmt = $this->conn->prepare($reviewQuery);
        $stmt->execute([$shopID]);
        $stats['reviews'] = $stmt->fetch(PDO::FETCH_ASSOC);

        // Financial statistics
        $financialQuery = "SELECT balance, total_earned, total_withdrawn
                          FROM {$this->table}
                          WHERE shopID = ?";
        $stmt = $this->conn->prepare($financialQuery);
        $stmt->execute([$shopID]);
        $stats['financials'] = $stmt->fetch(PDO::FETCH_ASSOC);

        return $stats;
    }

    /**
     * Get seller dashboard analytics
     */
    public function getSellerDashboard($sellerID) 
    {
        $shop = $this->getShopBySeller($sellerID);
        
        if (!$shop) {
            return null;
        }

        $dashboard = [];
        $dashboard['shop'] = $shop;
        $dashboard['stats'] = $this->getShopStats($shop['shopID']);

        // Recent orders
        $orderQuery = "SELECT o.*, u.full_name as buyer_name
                      FROM orders o
                      JOIN order_items oi ON o.orderID = oi.orderID
                      JOIN products p ON oi.productID = p.productID
                      JOIN users u ON o.buyerID = u.userID
                      WHERE p.shopID = ?
                      GROUP BY o.orderID
                      ORDER BY o.order_date DESC
                      LIMIT 10";
        $stmt = $this->conn->prepare($orderQuery);
        $stmt->execute([$shop['shopID']]);
        $dashboard['recent_orders'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Low stock products
        $productModel = new Product();
        $dashboard['low_stock_products'] = $productModel->getLowStockProducts($sellerID);

        // Recent reviews
        $reviewModel = new Review();
        $dashboard['recent_reviews'] = $reviewModel->getSellerReviews($sellerID, 5);

        return $dashboard;
    }

    // ==================== SHOP FOLLOWERS ====================

    /**
     * Follow shop
     */
    public function followShop($shopID, $userID) 
    {
        try {
            // Check if already following
            $checkQuery = "SELECT followerID FROM shop_followers 
                          WHERE shopID = ? AND userID = ?";
            $stmt = $this->conn->prepare($checkQuery);
            $stmt->execute([$shopID, $userID]);
            
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Already following this shop'];
            }

            $query = "INSERT INTO shop_followers (shopID, userID) VALUES (?, ?)";
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([$shopID, $userID]);

            if ($result) {
                return ['success' => true, 'message' => 'Successfully followed shop'];
            }
        } catch (PDOException $e) {
            error_log("Follow shop error: " . $e->getMessage());
        }

        return ['success' => false, 'message' => 'Failed to follow shop'];
    }

    /**
     * Unfollow shop
     */
    public function unfollowShop($shopID, $userID) 
    {
        $query = "DELETE FROM shop_followers WHERE shopID = ? AND userID = ?";
        
        try {
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([$shopID, $userID]);

            if ($result) {
                return ['success' => true, 'message' => 'Successfully unfollowed shop'];
            }
        } catch (PDOException $e) {
            error_log("Unfollow shop error: " . $e->getMessage());
        }

        return ['success' => false, 'message' => 'Failed to unfollow shop'];
    }

    /**
     * Check if user is following shop
     */
    public function isFollowing($shopID, $userID) 
    {
        $query = "SELECT followerID FROM shop_followers 
                 WHERE shopID = ? AND userID = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$shopID, $userID]);
        
        return $stmt->fetch() !== false;
    }

    /**
     * Get shop followers
     */
    public function getShopFollowers($shopID, $limit = 50) 
    {
        $query = "SELECT u.userID, u.full_name, u.avatar, sf.followed_at
                 FROM shop_followers sf
                 JOIN users u ON sf.userID = u.userID
                 WHERE sf.shopID = ?
                 ORDER BY sf.followed_at DESC
                 LIMIT ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(1, $shopID, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get follower count
     */
    public function getFollowerCount($shopID) 
    {
        $query = "SELECT COUNT(*) as count FROM shop_followers WHERE shopID = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$shopID]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['count'] ?? 0;
    }

    // ==================== UTILITY METHODS ====================

    /**
     * Verify shop ownership by seller
     */
    private function verifyShopOwnership($shopID, $sellerID) 
    {
        $query = "SELECT shopID FROM {$this->table} WHERE shopID = ? AND sellerID = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$shopID, $sellerID]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }

    /**
     * Generate unique shop slug from shop name
     */
    private function generateShopSlug($shopName, $excludeShopID = null) 
    {
        // Convert to lowercase and replace spaces with hyphens
        $slug = strtolower(trim($shopName));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');

        // Check if slug exists
        $originalSlug = $slug;
        $counter = 1;

        while (true) {
            $checkQuery = "SELECT shopID FROM {$this->table} WHERE shop_slug = ?";
            $params = [$slug];

            if ($excludeShopID) {
                $checkQuery .= " AND shopID != ?";
                $params[] = $excludeShopID;
            }

            $stmt = $this->conn->prepare($checkQuery);
            $stmt->execute($params);

            if (!$stmt->fetch()) {
                break;
            }

            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Update shop rating (called after new review)
     */
    public function updateShopRating($shopID) 
    {
        $query = "UPDATE {$this->table} s
                 SET s.rating = (
                     SELECT AVG(r.rating)
                     FROM reviews r
                     JOIN products p ON r.productID = p.productID
                     WHERE p.shopID = s.shopID
                 ),
                 s.total_reviews = (
                     SELECT COUNT(*)
                     FROM reviews r
                     JOIN products p ON r.productID = p.productID
                     WHERE p.shopID = s.shopID
                 )
                 WHERE s.shopID = ?";
        
        try {
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([$shopID]);
        } catch (PDOException $e) {
            error_log("Update shop rating error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get featured shops (top rated, active)
     */
    public function getFeaturedShops($limit = 6) 
    {
        $query = "SELECT s.*, 
                        sp.business_name
                 FROM {$this->table} s
                 JOIN {$this->sellerProfilesTable} sp ON s.sellerID = sp.sellerID
                 WHERE s.is_active = 1 
                   AND s.is_verified = 1
                   AND s.rating >= 4.0
                   AND s.total_products > 0
                 ORDER BY s.rating DESC, s.total_orders DESC
                 LIMIT ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function requestWithdrawal($shopID, $amount, $method = 'cash', $atmCard = null) 
    {
        $withdrawalModel = new Withdrawal();
        return $withdrawalModel->createRequest($shopID, $amount, $method, $atmCard);
    }
    public function getDetailedBalance($shopID) 
    {
        $balance = $this->getBalance($shopID);
        
        // Calculate pending earnings (orders not yet received by LGU)
        $pendingQuery = "SELECT SUM(o.total_amount) as pending_earnings
                        FROM orders o
                        JOIN order_items oi ON o.orderID = oi.orderID
                        JOIN products p ON oi.productID = p.productID
                        WHERE p.shopID = ?
                        AND o.order_status = 'delivered'
                        AND o.payment_received_by_lgu_at IS NULL";
        $stmt = $this->conn->prepare($pendingQuery);
        $stmt->execute([$shopID]);
        $pending = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get pending withdrawals
        $withdrawalModel = new Withdrawal();
        $withdrawalStats = $withdrawalModel->getShopWithdrawalStats($shopID);
        
        return [
            'available_balance' => $balance['balance'],
            'total_earned' => $balance['total_earned'],
            'total_withdrawn' => $balance['total_withdrawn'],
            'pending_earnings' => $pending['pending_earnings'] ?? 0,
            'pending_withdrawal' => $withdrawalStats['pending_amount']
        ];
    }
}

