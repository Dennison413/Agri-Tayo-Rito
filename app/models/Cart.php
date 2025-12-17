<?php
// app/models/Cart.php
require_once __DIR__ . '/../../config/database.php';

class Cart 
{
    private $conn;
    private $table = 'cart';

    public function __construct() 
    {
        $database = new Database();
        $this->conn = $database->connect();
    }

    // get cart items grouped by shop
    public function getCartItemsGroupedByShop($buyerID) 
    {
        $query = "
            SELECT 
                c.cartID,
                c.productID,
                c.quantity,
                c.added_at,
                p.product_name,
                p.price,
                p.unit,
                p.stock_quantity,
                p.reserved_quantity,
                p.is_available,
                p.shopID,
                s.shop_name,
                s.shop_slug,
                pi.image_path as primary_image, 
                (p.price * c.quantity) as item_subtotal
            FROM {$this->table} c
            INNER JOIN products p ON c.productID = p.productID
            INNER JOIN shops s ON p.shopID = s.shopID
            LEFT JOIN product_images pi ON p.productID = pi.productID AND pi.is_main = 1
            WHERE c.buyerID = :buyerID AND p.is_available = 1
            ORDER BY s.shop_name, c.added_at DESC
        ";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':buyerID', $buyerID, PDO::PARAM_INT);
        $stmt->execute();
        
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Group by shop
        $grouped = [];
        foreach ($items as $item) {
            $shopID = $item['shopID'];
            if (!isset($grouped[$shopID])) {
                $grouped[$shopID] = [
                    'shop_name' => $item['shop_name'],
                    'shop_slug' => $item['shop_slug'],
                    'items' => [],
                    'shop_subtotal' => 0
                ];
            }
            $grouped[$shopID]['items'][] = $item;
            $grouped[$shopID]['shop_subtotal'] += $item['item_subtotal'];
        }
        
        return $grouped;
    }

    // get cart items
    public function getCartItems($buyerID) 
    {
        $query = "
            SELECT 
                c.cartID,
                c.productID,
                c.quantity,
                c.added_at,
                p.product_name,
                p.price,
                p.unit,
                p.stock_quantity,
                p.reserved_quantity,
                p.is_available,
                p.shopID,
                pi.image_path as primary_image,
                (p.price * c.quantity) as item_subtotal
            FROM {$this->table} c
            INNER JOIN products p ON c.productID = p.productID
            LEFT JOIN product_images pi ON p.productID = pi.productID AND pi.is_main = 1
            WHERE c.buyerID = :buyerID
            ORDER BY c.added_at DESC
        ";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':buyerID', $buyerID, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // add item to cart with stock validation
    public function addToCart($buyerID, $productID, $quantity = 1) 
    {
        try {
            // Check product availability and stock
            $productQuery = "SELECT stock_quantity, reserved_quantity, is_available, product_name 
                           FROM products WHERE productID = ?";
            $stmt = $this->conn->prepare($productQuery);
            $stmt->execute([$productID]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$product || !$product['is_available']) {
                return [
                    'success' => false,
                    'message' => 'Product is not available'
                ];
            }

            $availableStock = $product['stock_quantity'] - $product['reserved_quantity'];
            
            // Check if item already exists in cart
            $checkQuery = "SELECT cartID, quantity FROM {$this->table} 
                          WHERE buyerID = ? AND productID = ?";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->execute([$buyerID, $productID]);
            
            if ($existing = $checkStmt->fetch(PDO::FETCH_ASSOC)) {
                $newQuantity = $existing['quantity'] + $quantity;
                
                // Validate total quantity against available stock
                if ($newQuantity > $availableStock) {
                    return [
                        'success' => false,
                        'message' => "Only {$availableStock} items available in stock"
                    ];
                }
                
                $updateQuery = "UPDATE {$this->table} 
                               SET quantity = ? 
                               WHERE cartID = ?";
                $updateStmt = $this->conn->prepare($updateQuery);
                $result = $updateStmt->execute([$newQuantity, $existing['cartID']]);
                
                return [
                    'success' => $result,
                    'message' => $result ? 'Cart updated successfully' : 'Failed to update cart'
                ];
            } else {
                // validate quantity against available stock
                if ($quantity > $availableStock) {
                    return [
                        'success' => false,
                        'message' => "Only {$availableStock} items available in stock"
                    ];
                }
                
                // insert new item - triggers will validate cart limits
                $insertQuery = "INSERT INTO {$this->table} 
                               (buyerID, productID, quantity) 
                               VALUES (?, ?, ?)";
                $insertStmt = $this->conn->prepare($insertQuery);
                $result = $insertStmt->execute([$buyerID, $productID, $quantity]);
                
                return [
                    'success' => $result,
                    'message' => $result ? 'Added to cart successfully' : 'Failed to add to cart'
                ];
            }
        } catch (PDOException $e) {
            error_log("Add to cart error: " . $e->getMessage());
            
            // handle trigger errors (cart limits)
            if (strpos($e->getMessage(), 'Cart limit reached') !== false) {
                return ['success' => false, 'message' => 'Cart limit reached: Maximum 80 different items allowed'];
            } elseif (strpos($e->getMessage(), 'Cart quantity limit') !== false) {
                return ['success' => false, 'message' => 'Cart quantity limit: Maximum 200 total items allowed'];
            } elseif (strpos($e->getMessage(), 'Item quantity limit') !== false) {
                return ['success' => false, 'message' => 'Item quantity limit: Maximum 20 per product'];
            }
            
            return ['success' => false, 'message' => 'Failed to add to cart'];
        }
    }

    // update cart item quantity with stock validation
    public function updateCartQuantity($buyerID, $productID, $quantity) 
    {
        if ($quantity <= 0) {
            return $this->removeFromCart($buyerID, $productID);
        }

        try {
            $productQuery = "SELECT stock_quantity, reserved_quantity FROM products WHERE productID = ?";
            $stmt = $this->conn->prepare($productQuery);
            $stmt->execute([$productID]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$product) {
                return ['success' => false, 'message' => 'Product not found'];
            }

            $availableStock = $product['stock_quantity'] - $product['reserved_quantity'];
            if ($quantity > $availableStock) {
                return [
                    'success' => false,
                    'message' => "Only {$availableStock} items available"
                ];
            }

            $query = "UPDATE {$this->table} 
                     SET quantity = ? 
                     WHERE buyerID = ? AND productID = ?";
            
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([$quantity, $buyerID, $productID]);
            
            return [
                'success' => $result,
                'message' => $result ? 'Quantity updated' : 'Failed to update'
            ];
        } catch (PDOException $e) {
            error_log("Update cart error: " . $e->getMessage());
            
            if (strpos($e->getMessage(), 'Cart quantity limit') !== false) {
                return ['success' => false, 'message' => 'Cart quantity limit exceeded'];
            } elseif (strpos($e->getMessage(), 'Item quantity limit') !== false) {
                return ['success' => false, 'message' => 'Maximum 20 items per product'];
            }
            
            return ['success' => false, 'message' => 'Failed to update cart'];
        }
    }

    // remove item from cart
    public function removeFromCart($buyerID, $productID) 
    {
        $query = "DELETE FROM {$this->table} 
                 WHERE buyerID = ? AND productID = ?";
        
        $stmt = $this->conn->prepare($query);
        $result = $stmt->execute([$buyerID, $productID]);
        
        return [
            'success' => $result,
            'message' => $result ? 'Item removed from cart' : 'Failed to remove item'
        ];
    }

    // clear entire cart
    public function clearCart($buyerID) 
    {
        $query = "DELETE FROM cart WHERE buyerID = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$buyerID]);
    }

    // get cart item count (number of different products)
    public function getCartCount($buyerID) 
    {
        $query = "SELECT COUNT(*) as total FROM {$this->table} 
                 WHERE buyerID = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$buyerID]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['total'] ?? 0);
    }

    // get cart total amount
    public function getCartTotal($buyerID) 
    {
        $query = "SELECT SUM(c.quantity * p.price) as total
                 FROM cart c
                 JOIN products p ON c.productID = p.productID
                 WHERE c.buyerID = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$buyerID]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['total'] ?? 0;
    }

    public function getCartTotalQuantity($buyerID) 
    {
        // Sum all quantities
        $query = "SELECT SUM(quantity) as total FROM {$this->table} 
                 WHERE buyerID = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$buyerID]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['total'] ?? 0);
    }

    // validate cart before checkout
    public function validateCartForCheckout($buyerID) 
    {
        $query = "SELECT 
                    c.cartID,
                    c.productID,
                    c.quantity,
                    p.product_name,
                    p.stock_quantity,
                    p.reserved_quantity,
                    p.is_available,
                    (p.stock_quantity - p.reserved_quantity) as available_quantity
                 FROM cart c
                 JOIN products p ON c.productID = p.productID
                 WHERE c.buyerID = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$buyerID]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $errors = [];
        $valid = true;
        
        foreach ($items as $item) {
            // check if product is available
            if (!$item['is_available']) {
                $errors[] = "{$item['product_name']} is no longer available";
                $valid = false;
                continue;
            }
            
            // check if sufficient stock
            if ($item['available_quantity'] < $item['quantity']) {
                $errors[] = "Insufficient stock for {$item['product_name']}. Only {$item['available_quantity']} available";
                $valid = false;
            }
        }
        
        return [
            'valid' => $valid,
            'errors' => $errors
        ];
    }

    // get wishlist items
    public function getWishlistItems($buyerID) 
    {
        $query = "
            SELECT 
                w.wishlistID,
                w.productID,
                w.added_at,
                p.product_name, 
                p.description, 
                p.price, 
                p.unit,
                p.stock_quantity,
                p.reserved_quantity,
                p.is_available,
                p.shopID,
                c.category AS category_name,
                s.shop_name,
                s.shop_slug,
                pi.image_path AS primary_image
            FROM wishlist w
            INNER JOIN products p ON w.productID = p.productID
            INNER JOIN categories c ON p.categoryID = c.categoryID
            INNER JOIN shops s ON p.shopID = s.shopID
            LEFT JOIN product_images pi ON p.productID = pi.productID AND pi.is_main = 1
            WHERE w.buyerID = ?
            ORDER BY w.added_at DESC
        ";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$buyerID]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get wishlist items error: " . $e->getMessage());
            return [];
        }
    }

    // wishlist methods
    public function addToWishlist($buyerID, $productID) 
    {
        try {
            $checkQuery = "SELECT wishlistID FROM wishlist 
                          WHERE buyerID = ? AND productID = ?";
            $stmt = $this->conn->prepare($checkQuery);
            $stmt->execute([$buyerID, $productID]);
            
            if ($stmt->fetch()) {
                return [
                    'success' => false,
                    'message' => 'Item already in wishlist'
                ];
            }

            $insertQuery = "INSERT INTO wishlist (buyerID, productID) VALUES (?, ?)";
            $insertStmt = $this->conn->prepare($insertQuery);
            $result = $insertStmt->execute([$buyerID, $productID]);
            
            return [
                'success' => $result,
                'message' => $result ? 'Added to wishlist' : 'Failed to add to wishlist'
            ];
        } catch (PDOException $e) {
            error_log("Add to wishlist error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to add to wishlist'];
        }
    }

    public function removeFromWishlist($buyerID, $productID) 
    {
        $query = "DELETE FROM wishlist WHERE buyerID = ? AND productID = ?";
        $stmt = $this->conn->prepare($query);
        $result = $stmt->execute([$buyerID, $productID]);
        
        return [
            'success' => $result,
            'message' => $result ? 'Removed from wishlist' : 'Failed to remove'
        ];
    }

    public function isInWishlist($buyerID, $productID) 
    {
        $query = "SELECT wishlistID FROM wishlist 
                 WHERE buyerID = ? AND productID = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$buyerID, $productID]);
        
        return $stmt->fetch() !== false;
    }

    public function getWishlistCount($buyerID) 
    {
        $query = "SELECT COUNT(*) as total FROM wishlist WHERE buyerID = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$buyerID]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['total'] ?? 0);
    }

    public function moveToCart($buyerID, $productID) 
    {
        try {
            $addResult = $this->addToCart($buyerID, $productID, 1);
            
            if ($addResult['success']) {
                $this->removeFromWishlist($buyerID, $productID);
                return [
                    'success' => true,
                    'message' => 'Moved to cart successfully'
                ];
            }
            
            return $addResult;
        } catch (Exception $e) {
            error_log("Move to cart error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to move to cart'];
        }
    }

    public function clearAll($buyerID)
    {
        try {
            $query = "DELETE FROM wishlist WHERE buyerID = ?";
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([$buyerID]);
        } catch (PDOException $e) {
            error_log("Clear wishlist error: " . $e->getMessage());
            return false;
        }
    }
}
?>