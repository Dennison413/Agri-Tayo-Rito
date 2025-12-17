<?php
// app/models/Product.php
require_once __DIR__ . '/../../config/database.php';

class Product 
{
    private $conn;
    private $table = 'products';
    private $imagesTable = 'product_images';
    
    // image upload configuration
    private $uploadPath = '/uploads/products/';
    private $maxFileSize = 20971520; // 20MB
    private $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];

    public function __construct() 
    {
        $database = new Database();
        $this->conn = $database->connect();
    }
    
    // create new product (for a seller)
    public function createProduct($sellerID, $data) 
    {
        try {
            $shopCheck = "SELECT shopID FROM shops WHERE sellerID = ? AND is_active = 1";
            $stmt = $this->conn->prepare($shopCheck);
            $stmt->execute([$sellerID]);
            $shop = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$shop) {
                return ['success' => false, 'message' => 'No active shop found for seller'];
            }

            $query = "INSERT INTO {$this->table} 
                     (shopID, sellerID, categoryID, product_name, description, 
                      price, stock_quantity, low_stock_threshold, unit, is_available) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([
                $shop['shopID'],
                $sellerID,
                $data['categoryID'],
                $data['product_name'],
                $data['description'] ?? null,
                $data['price'],
                $data['stock_quantity'] ?? 0,
                $data['low_stock_threshold'] ?? 5,
                $data['unit'],
                $data['is_available'] ?? 1
            ]);

            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Product created successfully',
                    'productID' => $this->conn->lastInsertId()
                ];
            }

        } catch (PDOException $e) {
            error_log("Create product error: " . $e->getMessage());
        }

        return ['success' => false, 'message' => 'Failed to create product'];
    }

    // update product details
    public function updateProduct($productID, $sellerID, $data) 
    {
        // Verify ownership
        if (!$this->verifyProductOwnership($productID, $sellerID)) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $fields = [];
        $params = [];

        $allowedFields = ['product_name', 'description', 'price', 'stock_quantity', 
                         'low_stock_threshold', 'unit', 'categoryID', 'is_available'];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $params[] = $data[$field];
            }
        }

        if (empty($fields)) {
            return ['success' => false, 'message' => 'No fields to update'];
        }

        $query = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE productID = ?";
        $params[] = $productID;

        try {
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute($params);

            if ($result) {
                return ['success' => true, 'message' => 'Product updated successfully'];
            }
        } catch (PDOException $e) {
            error_log("Update product error: " . $e->getMessage());
        }

        return ['success' => false, 'message' => 'Failed to update product'];
    }

    // delete product (soft delete)
    public function deleteProduct($productID, $sellerID) 
    {
        if (!$this->verifyProductOwnership($productID, $sellerID)) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $query = "UPDATE {$this->table} SET is_available = 0 WHERE productID = ?";
        
        try {
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([$productID]);

            if ($result) {
                return ['success' => true, 'message' => 'Product deleted successfully'];
            }
        } catch (PDOException $e) {
            error_log("Delete product error: " . $e->getMessage());
        }

        return ['success' => false, 'message' => 'Failed to delete product'];
    }

    // get product by ID with all details
    public function getProductById($productID) 
    {
        $query = "SELECT p.*, 
                        c.category,
                        s.shop_name, s.shop_slug,
                        sp.business_name, sp.rating as seller_rating,
                        (p.stock_quantity - p.reserved_quantity) as available_stock
                 FROM {$this->table} p
                 JOIN categories c ON p.categoryID = c.categoryID
                 JOIN shops s ON p.shopID = s.shopID
                 JOIN seller_profiles sp ON p.sellerID = sp.sellerID
                 WHERE p.productID = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$productID]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($product) {
            $product['images'] = $this->getProductImages($productID);
            $product['main_image'] = $this->getMainImage($productID);
            
            // Get reviews (comment-only, no ratings)
            $product['reviews'] = $this->getProductReviews($productID);
            $product['review_count'] = $this->getProductReviewCount($productID);
        }

        return $product;
    }

    // get all products with filters, sorting, and pagination
    public function getAllProducts($filters = [], $limit = 20, $offset = 0) 
    {
        $query = "SELECT p.*, 
                        c.category,
                        s.shop_name, s.shop_slug,
                        pi.image_path as main_image,
                        (p.stock_quantity - p.reserved_quantity) as available_stock
                FROM {$this->table} p
                JOIN categories c ON p.categoryID = c.categoryID
                JOIN shops s ON p.shopID = s.shopID
                LEFT JOIN {$this->imagesTable} pi ON p.productID = pi.productID AND pi.is_main = 1
                WHERE p.is_available = 1";
        
        $params = [];

        // Category filter
        if (!empty($filters['categoryID'])) {
            $query .= " AND p.categoryID = ?";
            $params[] = $filters['categoryID'];
        }

        // Shop filter
        if (!empty($filters['shopID'])) {
            $query .= " AND p.shopID = ?";
            $params[] = $filters['shopID'];
        }

        // Search filter
        if (!empty($filters['search'])) {
            $query .= " AND (p.product_name LIKE ? OR p.description LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        // Price range filter
        if (!empty($filters['min_price'])) {
            $query .= " AND p.price >= ?";
            $params[] = $filters['min_price'];
        }
        if (!empty($filters['max_price'])) {
            $query .= " AND p.price <= ?";
            $params[] = $filters['max_price'];
        }

        // In stock only
        if (!empty($filters['in_stock'])) {
            $query .= " AND (p.stock_quantity - p.reserved_quantity) > 0";
        }

        // Sorting
        $sortOptions = [
            'newest' => 'p.created_at DESC',
            'price_low' => 'p.price ASC',
            'price_high' => 'p.price DESC',
            'name' => 'p.product_name ASC'
        ];
        
        $sortBy = $filters['sort'] ?? 'newest';
        $query .= " ORDER BY " . ($sortOptions[$sortBy] ?? $sortOptions['newest']);

        $query .= " LIMIT ? OFFSET ?";

        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $index => $value) {
            $stmt->bindValue($index + 1, $value);
        }
        
        $stmt->bindValue(count($params) + 1, $limit, PDO::PARAM_INT);
        $stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // get products by seller
    public function getProductsBySeller($sellerID, $includeInactive = false) 
    {
        $query = "SELECT p.*, 
                        c.category,
                        pi.image_path as main_image,
                        (p.stock_quantity - p.reserved_quantity) as available_stock
                 FROM {$this->table} p
                 JOIN categories c ON p.categoryID = c.categoryID
                 LEFT JOIN {$this->imagesTable} pi ON p.productID = pi.productID AND pi.is_main = 1
                 WHERE p.sellerID = ?";
        
        if (!$includeInactive) {
            $query .= " AND p.is_available = 1";
        }

        $query .= " ORDER BY p.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$sellerID]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // get products by shop
    public function getProductsByShop($shopID, $includeInactive = false) 
    {
        $query = "SELECT p.*, 
                        c.category,
                        pi.image_path as main_image,
                        (p.stock_quantity - p.reserved_quantity) as available_stock
                 FROM {$this->table} p
                 JOIN categories c ON p.categoryID = c.categoryID
                 LEFT JOIN {$this->imagesTable} pi ON p.productID = pi.productID AND pi.is_main = 1
                 WHERE p.shopID = ?";
        
        if (!$includeInactive) {
            $query .= " AND p.is_available = 1";
        }

        $query .= " ORDER BY p.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$shopID]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // stock management - update stock quantity
    public function updateStock($productID, $sellerID, $newQuantity, $changeType = 'restock') 
    {
        if (!$this->verifyProductOwnership($productID, $sellerID)) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        try {
            $this->conn->beginTransaction();

            $currentStock = $this->getProductById($productID)['stock_quantity'];
            $change = $newQuantity - $currentStock;

            $query = "UPDATE {$this->table} SET stock_quantity = ? WHERE productID = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$newQuantity, $productID]);

            $this->logInventoryChange($productID, $changeType, $change, $newQuantity, $sellerID);

            $this->conn->commit();

            return [
                'success' => true,
                'message' => 'Stock updated successfully',
                'new_quantity' => $newQuantity
            ];

        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Update stock error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update stock'];
        }
    }

    // log inventory changes
    private function logInventoryChange($productID, $changeType, $quantityChange, $quantityAfter, $userID = null) 
    {
        $query = "INSERT INTO inventory_logs 
                 (productID, change_type, quantity_change, quantity_after, created_by) 
                 VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$productID, $changeType, $quantityChange, $quantityAfter, $userID]);
    }

    // get low stock products for a seller
    public function getLowStockProducts($sellerID) 
    {
        $query = "SELECT p.*, 
                        (p.stock_quantity - p.reserved_quantity) as available_stock
                 FROM {$this->table} p
                 WHERE p.sellerID = ? 
                   AND p.is_available = 1
                   AND (p.stock_quantity - p.reserved_quantity) <= p.low_stock_threshold
                 ORDER BY available_stock ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$sellerID]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // get main image for a product
    public function getMainImage($productID) 
    {
        $query = "SELECT image_path FROM {$this->imagesTable} 
                 WHERE productID = ? AND is_main = 1 
                 LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$productID]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? $result['image_path'] : '/images/placeholder.jpg';
    }

    // get all product images (main + gallery)
    public function getProductImages($productID) 
    {
        $query = "SELECT * FROM {$this->imagesTable} 
                 WHERE productID = ? 
                 ORDER BY is_main DESC, image_order ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$productID]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // get only gallery images (excluding main)
    public function getGalleryImages($productID) 
    {
        $query = "SELECT * FROM {$this->imagesTable} 
                 WHERE productID = ? AND is_main = 0 
                 ORDER BY image_order ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$productID]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // add product image
    public function addProductImage($productID, $sellerID, $imagePath, $isMain = false, $imageOrder = null) 
    {
        if (!$this->verifyProductOwnership($productID, $sellerID)) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        try {
            if ($imageOrder === null) {
                $orderQuery = "SELECT COALESCE(MAX(image_order), 0) + 1 as next_order 
                              FROM {$this->imagesTable} WHERE productID = ?";
                $orderStmt = $this->conn->prepare($orderQuery);
                $orderStmt->execute([$productID]);
                $orderResult = $orderStmt->fetch(PDO::FETCH_ASSOC);
                $imageOrder = $orderResult['next_order'];
            }

            $query = "INSERT INTO {$this->imagesTable} 
                     (productID, image_path, is_main, image_order) 
                     VALUES (?, ?, ?, ?)";
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([$productID, $imagePath, $isMain ? 1 : 0, $imageOrder]);

            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Image added successfully',
                    'imageID' => $this->conn->lastInsertId()
                ];
            }
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'already has a main image') !== false) {
                return ['success' => false, 'message' => 'Product already has a main image'];
            }
            if (strpos($e->getMessage(), 'Maximum 5 images') !== false) {
                return ['success' => false, 'message' => 'Maximum 5 images allowed (1 main + 4 gallery)'];
            }
            error_log("Add product image error: " . $e->getMessage());
        }

        return ['success' => false, 'message' => 'Failed to add image'];
    }

    // update main image
    public function updateMainImage($productID, $sellerID, $newMainImageID) 
    {
        if (!$this->verifyProductOwnership($productID, $sellerID)) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        try {
            $this->conn->beginTransaction();

            $unsetQuery = "UPDATE {$this->imagesTable} 
                          SET is_main = 0 
                          WHERE productID = ? AND is_main = 1";
            $unsetStmt = $this->conn->prepare($unsetQuery);
            $unsetStmt->execute([$productID]);

            $setQuery = "UPDATE {$this->imagesTable} 
                        SET is_main = 1 
                        WHERE imageID = ? AND productID = ?";
            $setStmt = $this->conn->prepare($setQuery);
            $setStmt->execute([$newMainImageID, $productID]);

            $this->conn->commit();

            return ['success' => true, 'message' => 'Main image updated successfully'];

        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Update main image error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update main image'];
        }
    }

    // delete product image
    public function deleteProductImage($imageID, $sellerID) 
    {
        $query = "SELECT pi.image_path, pi.is_main, p.productID 
                 FROM {$this->imagesTable} pi
                 JOIN {$this->table} p ON pi.productID = p.productID
                 WHERE pi.imageID = ? AND p.sellerID = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$imageID, $sellerID]);
        $image = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$image) {
            return ['success' => false, 'message' => 'Image not found or unauthorized'];
        }

        if ($image['is_main']) {
            $countQuery = "SELECT COUNT(*) as count FROM {$this->imagesTable} WHERE productID = ?";
            $countStmt = $this->conn->prepare($countQuery);
            $countStmt->execute([$image['productID']]);
            $count = $countStmt->fetch(PDO::FETCH_ASSOC)['count'];

            if ($count <= 1) {
                return ['success' => false, 'message' => 'Cannot delete the only image.'];
            }

            $promoteQuery = "UPDATE {$this->imagesTable} 
                           SET is_main = 1 
                           WHERE productID = ? AND is_main = 0 
                           ORDER BY image_order ASC 
                           LIMIT 1";
            $promoteStmt = $this->conn->prepare($promoteQuery);
            $promoteStmt->execute([$image['productID']]);
        }

        $deleteQuery = "DELETE FROM {$this->imagesTable} WHERE imageID = ?";
        $deleteStmt = $this->conn->prepare($deleteQuery);
        $result = $deleteStmt->execute([$imageID]);

        if ($result) {
            $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/agri_system/public' . $image['image_path'];
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }

            return ['success' => true, 'message' => 'Image deleted successfully'];
        }

        return ['success' => false, 'message' => 'Failed to delete image'];
    }

    // get reviews for a product
    public function getProductReviews($productID, $limit = 10, $offset = 0) 
    {
        $query = "SELECT r.*, u.full_name, u.avatar
                 FROM reviews r
                 JOIN users u ON r.buyerID = u.userID
                 WHERE r.productID = ?
                 ORDER BY r.review_date DESC
                 LIMIT ? OFFSET ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(1, $productID, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->bindValue(3, $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // get total review count for a product
    public function getProductReviewCount($productID) 
    {
        $query = "SELECT COUNT(*) as count FROM reviews WHERE productID = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$productID]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['count'] ?? 0;
    }

    // verify product ownership
    private function verifyProductOwnership($productID, $sellerID) 
    {
        $query = "SELECT productID FROM {$this->table} WHERE productID = ? AND sellerID = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$productID, $sellerID]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }

    // check product availability for order
    public function checkAvailability($productID, $requestedQuantity) 
    {
        $product = $this->getProductById($productID);
        
        if (!$product) {
            return ['available' => false, 'message' => 'Product not found'];
        }

        if (!$product['is_available']) {
            return ['available' => false, 'message' => 'Product is currently unavailable'];
        }

        $availableStock = $product['stock_quantity'] - $product['reserved_quantity'];
        
        if ($requestedQuantity > $availableStock) {
            return [
                'available' => false, 
                'message' => "Only {$availableStock} items available in stock"
            ];
        }

        return ['available' => true];
    }

    // Get seller's product statistics for dashboard
    public function getSellerProductStats($sellerID) 
    {
        $query = "SELECT 
                    COUNT(*) as total_products,
                    SUM(CASE WHEN is_available = 1 THEN 1 ELSE 0 END) as active_products,
                    SUM(CASE WHEN (stock_quantity - reserved_quantity) <= low_stock_threshold THEN 1 ELSE 0 END) as low_stock_count,
                    SUM(CASE WHEN (stock_quantity - reserved_quantity) = 0 THEN 1 ELSE 0 END) as out_of_stock_count
                 FROM {$this->table}
                 WHERE sellerID = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$sellerID]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

class Review 
{
    private $conn;
    private $table = 'reviews';

    public function __construct() 
    {
        $database = new Database();
        $this->conn = $database->connect();
    }

    // create review (comment only, no rating)
    public function createReview($buyerID, $data) 
    {
        try {
            // Verify buyer has received the product in an order
            $verifyQuery = "SELECT oi.orderItemID 
                           FROM order_items oi
                           JOIN orders o ON oi.orderID = o.orderID
                           WHERE o.buyerID = ? 
                             AND oi.productID = ? 
                             AND o.order_status = 'delivered'
                           LIMIT 1";
            $stmt = $this->conn->prepare($verifyQuery);
            $stmt->execute([$buyerID, $data['productID']]);
            
            if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                return [
                    'success' => false, 
                    'message' => 'You can only review products you have purchased and received'
                ];
            }

            // Check if review already exists
            $checkQuery = "SELECT reviewID FROM {$this->table} 
                          WHERE buyerID = ? AND productID = ? AND orderID = ?";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->execute([$buyerID, $data['productID'], $data['orderID']]);
            
            if ($checkStmt->fetch(PDO::FETCH_ASSOC)) {
                return [
                    'success' => false,
                    'message' => 'You have already reviewed this product for this order'
                ];
            }

            $query = "INSERT INTO {$this->table} 
                     (productID, buyerID, orderID, review_text, is_verified_purchase) 
                     VALUES (?, ?, ?, ?, 1)";
            
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([
                $data['productID'],
                $buyerID,
                $data['orderID'],
                $data['review_text'] ?? null
            ]);

            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Review submitted successfully',
                    'reviewID' => $this->conn->lastInsertId()
                ];
            }

        } catch (PDOException $e) {
            error_log("Create review error: " . $e->getMessage());
        }

        return ['success' => false, 'message' => 'Failed to submit review'];
    }

    // update review
    public function updateReview($reviewID, $buyerID, $data) 
    {
        if (!$this->verifyReviewOwnership($reviewID, $buyerID)) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        if (empty($data['review_text'])) {
            return ['success' => false, 'message' => 'Review text is required'];
        }

        $query = "UPDATE {$this->table} SET review_text = ? WHERE reviewID = ?";

        try {
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([$data['review_text'], $reviewID]);

            if ($result) {
                return ['success' => true, 'message' => 'Review updated successfully'];
            }
        } catch (PDOException $e) {
            error_log("Update review error: " . $e->getMessage());
        }

        return ['success' => false, 'message' => 'Failed to update review'];
    }

    // delete review
    public function deleteReview($reviewID, $buyerID) 
    {
        if (!$this->verifyReviewOwnership($reviewID, $buyerID)) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        $query = "DELETE FROM {$this->table} WHERE reviewID = ?";
        
        try {
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([$reviewID]);

            if ($result) {
                return ['success' => true, 'message' => 'Review deleted successfully'];
            }
        } catch (PDOException $e) {
            error_log("Delete review error: " . $e->getMessage());
        }

        return ['success' => false, 'message' => 'Failed to delete review'];
    }

    // get review by ID
    public function getReviewById($reviewID) 
    {
        $query = "SELECT r.*, 
                        u.full_name, u.avatar,
                        p.product_name
                 FROM {$this->table} r
                 JOIN users u ON r.buyerID = u.userID
                 JOIN products p ON r.productID = p.productID
                 WHERE r.reviewID = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$reviewID]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // get reviews for a product
    public function getProductReviews($productID, $filters = [], $limit = 10, $offset = 0) 
    {
        $query = "SELECT r.*, 
                        u.full_name, u.avatar
                 FROM {$this->table} r
                 JOIN users u ON r.buyerID = u.userID
                 WHERE r.productID = ?";
        
        $params = [$productID];

        if (isset($filters['verified_only']) && $filters['verified_only']) {
            $query .= " AND r.is_verified_purchase = 1";
        }

        $sortOptions = [
            'newest' => 'r.review_date DESC',
            'oldest' => 'r.review_date ASC'
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

    // get reviews by buyer
    public function getBuyerReviews($buyerID, $limit = 20, $offset = 0) 
    {
        $query = "SELECT r.*, 
                        p.product_name, p.productID,
                        pi.image_path as product_image,
                        s.shop_name
                 FROM {$this->table} r
                 JOIN products p ON r.productID = p.productID
                 LEFT JOIN product_images pi ON p.productID = pi.productID AND pi.image_order = 0
                 JOIN shops s ON p.shopID = s.shopID
                 WHERE r.buyerID = ?
                 ORDER BY r.review_date DESC
                 LIMIT ? OFFSET ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(1, $buyerID, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->bindValue(3, $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // get reviews for seller's products
    public function getSellerReviews($sellerID, $limit = 20, $offset = 0) 
    {
        $query = "SELECT r.*, 
                        u.full_name, u.avatar,
                        p.product_name, p.productID
                 FROM {$this->table} r
                 JOIN users u ON r.buyerID = u.userID
                 JOIN products p ON r.productID = p.productID
                 WHERE p.sellerID = ?
                 ORDER BY r.review_date DESC
                 LIMIT ? OFFSET ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(1, $sellerID, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->bindValue(3, $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // check if buyer can review a product for a specific order
    public function canReview($buyerID, $productID, $orderID) 
    {
        // Check if order is delivered
        $orderQuery = "SELECT o.orderID
                      FROM orders o
                      JOIN order_items oi ON o.orderID = oi.orderID
                      WHERE o.buyerID = ? 
                        AND oi.productID = ?
                        AND o.orderID = ?
                        AND o.order_status = 'delivered'
                      LIMIT 1";
        $stmt = $this->conn->prepare($orderQuery);
        $stmt->execute([$buyerID, $productID, $orderID]);
        
        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            return [
                'can_review' => false,
                'message' => 'Order must be delivered before reviewing'
            ];
        }

        // Check if already reviewed
        $reviewQuery = "SELECT reviewID FROM {$this->table} 
                       WHERE buyerID = ? AND productID = ? AND orderID = ?";
        $reviewStmt = $this->conn->prepare($reviewQuery);
        $reviewStmt->execute([$buyerID, $productID, $orderID]);
        
        if ($reviewStmt->fetch(PDO::FETCH_ASSOC)) {
            return [
                'can_review' => false,
                'message' => 'You have already reviewed this product'
            ];
        }

        return ['can_review' => true];
    }

    // get products awaiting review by buyer
    public function getProductsAwaitingReview($buyerID) 
    {
        $query = "SELECT DISTINCT 
                        p.productID, p.product_name,
                        pi.image_path as product_image,
                        s.shop_name,
                        o.orderID, o.delivered_at
                 FROM orders o
                 JOIN order_items oi ON o.orderID = oi.orderID
                 JOIN products p ON oi.productID = p.productID
                 LEFT JOIN product_images pi ON p.productID = pi.productID AND pi.image_order = 0
                 JOIN shops s ON p.shopID = s.shopID
                 WHERE o.buyerID = ?
                   AND o.order_status = 'delivered'
                   AND NOT EXISTS (
                       SELECT 1 FROM {$this->table} r 
                       WHERE r.buyerID = o.buyerID 
                         AND r.productID = p.productID 
                         AND r.orderID = o.orderID
                   )
                 ORDER BY o.delivered_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$buyerID]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // get total review count for a product
    public function getProductReviewCount($productID) 
    {
        $query = "SELECT COUNT(*) as count FROM {$this->table} WHERE productID = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$productID]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['count'] ?? 0;
    }

    // get a buyer's review for a specific product and order
    public function getBuyerProductReview($buyerID, $productID, $orderID = null) 
    {
        $query = "SELECT r.*, p.product_name 
                 FROM {$this->table} r
                 JOIN products p ON r.productID = p.productID
                 WHERE r.buyerID = ? AND r.productID = ?";
        
        $params = [$buyerID, $productID];
        
        if ($orderID !== null) {
            $query .= " AND r.orderID = ?";
            $params[] = $orderID;
        }
        
        $query .= " LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Get recent reviews across all products
    public function getRecentReviews($limit = 10) 
    {
        $query = "SELECT r.*, 
                        u.full_name, u.avatar,
                        p.product_name, p.productID,
                        pi.image_path as product_image,
                        s.shop_name
                 FROM {$this->table} r
                 JOIN users u ON r.buyerID = u.userID
                 JOIN products p ON r.productID = p.productID
                 LEFT JOIN product_images pi ON p.productID = pi.productID AND pi.image_order = 0
                 JOIN shops s ON p.shopID = s.shopID
                 WHERE r.review_text IS NOT NULL
                 ORDER BY r.review_date DESC
                 LIMIT ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // verify review ownership by buyer
    private function verifyReviewOwnership($reviewID, $buyerID) 
    {
        $query = "SELECT reviewID FROM {$this->table} WHERE reviewID = ? AND buyerID = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$reviewID, $buyerID]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }

    // check if product has reviews
    public function hasReviews($productID) 
    {
        $query = "SELECT COUNT(*) as count FROM {$this->table} WHERE productID = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$productID]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['count'] > 0;
    }
}
?>