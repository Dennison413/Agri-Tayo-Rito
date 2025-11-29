<?php
// app/models/Cart.php
// Buyer's Cart and Wishlist Management Model
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

    // Get cart items grouped by shop
    // Returns items organized by shopID for checkout display
     
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
            LEFT JOIN product_images pi ON p.productID = pi.productID AND pi.image_order = 0
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

    // Get cart items (flat list)
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
            LEFT JOIN product_images pi ON p.productID = pi.productID AND pi.image_order = 0
            WHERE c.buyerID = :buyerID
            ORDER BY c.added_at DESC
        ";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':buyerID', $buyerID, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Add item to cart with stock validation
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
                // Validate quantity against available stock
                if ($quantity > $availableStock) {
                    return [
                        'success' => false,
                        'message' => "Only {$availableStock} items available in stock"
                    ];
                }
                
                // Insert new item - triggers will validate cart limits
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
            
            // Handle trigger errors (cart limits)
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

    // Update cart item quantity with stock validation
    public function updateCartQuantity($buyerID, $productID, $quantity) 
    {
        if ($quantity <= 0) {
            return $this->removeFromCart($buyerID, $productID);
        }

        try {
            // Check available stock
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
            
            // Handle trigger errors
            if (strpos($e->getMessage(), 'Cart quantity limit') !== false) {
                return ['success' => false, 'message' => 'Cart quantity limit exceeded'];
            } elseif (strpos($e->getMessage(), 'Item quantity limit') !== false) {
                return ['success' => false, 'message' => 'Maximum 20 items per product'];
            }
            
            return ['success' => false, 'message' => 'Failed to update cart'];
        }
    }

    // Remove item from cart
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

    // Clear entire cart
    public function clearCart($buyerID) 
    {
        $query = "DELETE FROM {$this->table} WHERE buyerID = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$buyerID]);
    }

    // Get cart item count
    public function getCartCount($buyerID) 
    {
        $query = "SELECT SUM(quantity) as total FROM {$this->table} 
                 WHERE buyerID = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$buyerID]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['total'] ?? 0);
    }

    // Get cart total amount
    public function getCartTotal($buyerID) 
    {
        $query = "SELECT SUM(c.quantity * p.price) as total 
                 FROM {$this->table} c
                 JOIN products p ON c.productID = p.productID
                 WHERE c.buyerID = ? AND p.is_available = 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$buyerID]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (float)($result['total'] ?? 0);
    }

    // Validate cart before checkout
    // Checks stock availability for all items
    public function validateCartForCheckout($buyerID) 
    {
        $query = "
            SELECT 
                c.productID,
                c.quantity,
                p.product_name,
                p.stock_quantity,
                p.reserved_quantity,
                p.is_available
            FROM {$this->table} c
            JOIN products p ON c.productID = p.productID
            WHERE c.buyerID = ?
        ";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$buyerID]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $errors = [];
        foreach ($items as $item) {
            if (!$item['is_available']) {
                $errors[] = "{$item['product_name']} is no longer available";
            } else {
                $available = $item['stock_quantity'] - $item['reserved_quantity'];
                if ($item['quantity'] > $available) {
                    $errors[] = "{$item['product_name']}: Only {$available} available (you have {$item['quantity']} in cart)";
                }
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}

class Wishlist 
{
    private $conn;
    private $table = 'wishlist';

    public function __construct() 
    {
        $database = new Database();
        $this->conn = $database->connect();
    }

    // Get wishlist items for buyer
    public function getWishlistItems($buyerID) 
    {
        $query = "
            SELECT 
                w.wishlistID,
                w.productID,
                w.added_at,
                p.product_name,
                p.price,
                p.unit,
                p.stock_quantity,
                p.is_available,
                p.shopID,
                s.shop_name,
                pi.image_path as primary_image
            FROM {$this->table} w
            INNER JOIN products p ON w.productID = p.productID
            INNER JOIN shops s ON p.shopID = s.shopID
            LEFT JOIN product_images pi ON p.productID = pi.productID AND pi.image_order = 0
            WHERE w.buyerID = :buyerID
            ORDER BY w.added_at DESC
        ";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':buyerID', $buyerID, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Add to wishlist
    public function addToWishlist($buyerID, $productID) 
    {
        try {
            // Check if already in wishlist
            $checkQuery = "SELECT wishlistID FROM {$this->table} 
                          WHERE buyerID = ? AND productID = ?";
            $stmt = $this->conn->prepare($checkQuery);
            $stmt->execute([$buyerID, $productID]);
            
            if ($stmt->fetch()) {
                return [
                    'success' => false,
                    'message' => 'Item already in wishlist'
                ];
            }

            $insertQuery = "INSERT INTO {$this->table} (buyerID, productID) VALUES (?, ?)";
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

    // Remove from wishlist
    public function removeFromWishlist($buyerID, $productID) 
    {
        $query = "DELETE FROM {$this->table} WHERE buyerID = ? AND productID = ?";
        $stmt = $this->conn->prepare($query);
        $result = $stmt->execute([$buyerID, $productID]);
        
        return [
            'success' => $result,
            'message' => $result ? 'Removed from wishlist' : 'Failed to remove'
        ];
    }

    // Check if item is in wishlist
    public function isInWishlist($buyerID, $productID) 
    {
        $query = "SELECT wishlistID FROM {$this->table} 
                 WHERE buyerID = ? AND productID = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$buyerID, $productID]);
        
        return $stmt->fetch() !== false;
    }

    // Get wishlist item count
    public function getWishlistCount($buyerID) 
    {
        $query = "SELECT COUNT(*) as total FROM {$this->table} WHERE buyerID = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$buyerID]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['total'] ?? 0);
    }

    // Move item from wishlist to cart
    public function moveToCart($buyerID, $productID) 
    {
        try {
            $cartModel = new Cart();
            $addResult = $cartModel->addToCart($buyerID, $productID, 1);
            
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
    // inside class Wishlist { ... }

/**
 * Clear entire wishlist for a buyer
 * @param int $buyerID
 * @return bool
 */
public function clearAll($buyerID)
{
    try {
        $query = "DELETE FROM {$this->table} WHERE buyerID = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$buyerID]);
    } catch (PDOException $e) {
        error_log("Clear wishlist error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get a wishlist row by wishlistID (scoped to buyer)
 * @param int $buyerID
 * @param int $wishlistID
 * @return array|false
 */
public function getWishlistItemByID($buyerID, $wishlistID)
{
    try {
        $query = "SELECT * FROM {$this->table} WHERE buyerID = ? AND wishlistID = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$buyerID, $wishlistID]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Get wishlist item by id error: " . $e->getMessage());
        return false;
    }
}

}
?>