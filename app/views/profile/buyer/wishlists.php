<?php
// app/views/profile/buyer/wishlists.php
// FIXED: Wishlist page with correct schema queries

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../config/database.php';
require_once __DIR__ . '/../../../models/Cart.php';
require_once __DIR__ . '/../../../helpers/csrf.php';

// Check if user is logged in and is a buyer
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'];
$userRole = $_SESSION['user_role'] ?? null;

if (!$isLoggedIn || $userRole !== 'buyer') {
    header('Location: ' . BASE_URL . 'auth/login');
    exit;
}

$userId = $_SESSION['user_id'];

$db = new Database();
$conn = $db->connect();

// FIXED: Query using correct schema (product_images with image_order = 0)
$sql = "
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
    LEFT JOIN product_images pi ON p.productID = pi.productID AND pi.image_order = 0
    WHERE w.buyerID = :buyerId
    ORDER BY w.added_at DESC
";

try {
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':buyerId', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $wishlistItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching wishlist: " . $e->getMessage());
    $wishlistItems = [];
}

// Get cart count for navigation badge
$cartCount = 0;
try {
    $cartModel = new Cart();
    $cartCount = $cartModel->getCartCount($userId);
} catch (Exception $e) {
    error_log("Error getting cart count: " . $e->getMessage());
}

// Generate CSRF token for page
$csrf_token = CSRF::generateToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist - Agri Tayo Rito</title>

    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrf_token); ?>">

    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/responsive.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/marketplace/marketplace.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/marketplace/wishlist.css">

    <style>
        /* Loading overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9998;
        }
        .loading-overlay.active { display: flex; }
        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #2d5016;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Notification styles */
        .notification {
            position: fixed;
            right: 20px;
            bottom: 20px;
            padding: 12px 20px;
            border-radius: 8px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
            transform: translateY(100px);
            opacity: 0;
            transition: all 0.3s ease;
            z-index: 9999;
            max-width: 400px;
            font-size: 14px;
        }
        .notification.show { 
            transform: translateY(0); 
            opacity: 1; 
        }
        .notification-success { 
            background: #d4edda; 
            color: #155724; 
            border: 1px solid #c3e6cb;
        }
        .notification-error { 
            background: #f8d7da; 
            color: #721c24; 
            border: 1px solid #f5c6cb;
        }
        .notification-info { 
            background: #d1ecf1; 
            color: #0c5460; 
            border: 1px solid #bee5eb;
        }

        /* Card removal animation */
        @keyframes fadeOutUp {
            from { 
                opacity: 1; 
                transform: translateY(0) scale(1); 
            }
            to { 
                opacity: 0; 
                transform: translateY(-20px) scale(0.9); 
            }
        }
        .wishlist-card.removing {
            animation: fadeOutUp 0.3s ease forwards;
        }

        /* Button loading state */
        .btn-loading {
            position: relative;
            pointer-events: none;
            opacity: 0.6;
        }
        .btn-loading::after {
            content: '';
            position: absolute;
            width: 16px;
            height: 16px;
            top: 50%;
            left: 50%;
            margin-left: -8px;
            margin-top: -8px;
            border: 2px solid #fff;
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }
    </style>
</head>
<body>
    <div class="overlay" id="overlay" onclick="toggleSidebar()"></div>
    
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner"></div>
    </div>

    <!-- Top Navigation -->
    <?php include BASE_PATH . '/app/views/marketplace/topmarketnav.php'; ?>

    <main class="main-content">
        <div class="wishlist-container">
            <!-- Header Section -->
            <div class="wishlist-header">
                <div class="header-content">
                    <h1 class="page-title">
                        <span class="title-icon">❤️</span>
                        My Wishlist
                    </h1>
                    <p class="page-subtitle" id="wishlistSubtitle">
                        <?php echo count($wishlistItems); ?> item<?php echo count($wishlistItems) !== 1 ? 's' : ''; ?> saved
                    </p>
                </div>

                <?php if (count($wishlistItems) > 0): ?>
                    <div class="header-actions">
                        <button class="action-btn clear-all-btn" id="clearAllBtn">
                            <span>🗑️</span> Clear All
                        </button>
                        <button class="action-btn add-all-cart-btn" id="addAllCartBtn">
                            <span>🛒</span> Add All to Cart
                        </button>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Wishlist Items -->
            <?php if (count($wishlistItems) > 0): ?>
                <div class="wishlist-grid" id="wishlistGrid">
                    <?php foreach ($wishlistItems as $item): 
                        $availableStock = max(0, $item['stock_quantity'] - ($item['reserved_quantity'] ?? 0));
                        $isAvailable = $item['is_available'] && $availableStock > 0;
                    ?>
                        <div class="wishlist-card" 
                             data-wishlist-id="<?php echo (int)$item['wishlistID']; ?>" 
                             data-product-id="<?php echo (int)$item['productID']; ?>">
                            
                            <!-- Product Image -->
                            <div class="wishlist-image">
                                <img src="<?php echo htmlspecialchars($item['primary_image'] ?: BASE_URL . 'images/default-product.jpg'); ?>" 
                                     alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                     loading="lazy">
                                
                                <?php if (!$isAvailable): ?>
                                    <div class="out-of-stock-badge">Out of Stock</div>
                                <?php endif; ?>
                                
                                <button class="remove-btn" 
                                        title="Remove from wishlist" 
                                        aria-label="Remove from wishlist"
                                        data-action="remove">
                                    ✕
                                </button>
                            </div>

                            <!-- Product Info -->
                            <div class="wishlist-info">
                                <div class="product-category">
                                    <?php echo htmlspecialchars($item['category_name']); ?>
                                </div>
                                
                                <h3 class="wishlist-product-name">
                                    <?php echo htmlspecialchars($item['product_name']); ?>
                                </h3>
                                
                                <p class="wishlist-description">
                                    <?php echo htmlspecialchars(substr($item['description'] ?? '', 0, 100)); ?>
                                    <?php if (strlen($item['description'] ?? '') > 100): ?>...<?php endif; ?>
                                </p>

                                <div class="wishlist-shop-info">
                                    🏪 <?php echo htmlspecialchars($item['shop_name']); ?>
                                </div>

                                <div class="wishlist-footer">
                                    <div class="price-section">
                                        <span class="wishlist-price">₱<?php echo number_format($item['price'], 2); ?></span>
                                        <span class="price-unit">/<?php echo htmlspecialchars($item['unit']); ?></span>
                                    </div>

                                    <?php if ($isAvailable): ?>
                                        <button class="wishlist-add-cart-btn" 
                                                data-action="add-to-cart">
                                            Add to Cart
                                        </button>
                                    <?php else: ?>
                                        <button class="wishlist-add-cart-btn disabled" disabled>
                                            Unavailable
                                        </button>
                                    <?php endif; ?>
                                </div>

                                <?php if ($isAvailable && $availableStock <= 10): ?>
                                    <div class="stock-warning">
                                        ⚠️ Only <?php echo (int)$availableStock; ?> left in stock
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <!-- Empty State -->
                <div class="empty-wishlist" id="emptyWishlist">
                    <div class="empty-icon">💔</div>
                    <h2 class="empty-title">Your wishlist is empty</h2>
                    <p class="empty-message">Start adding products you love to your wishlist!</p>
                    <a href="<?php echo BASE_URL; ?>marketplace" class="browse-btn">
                        Browse Products
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Bottom Navigation -->
    <?php include BASE_PATH . '/app/views/marketplace/marketnav.php'; ?>

    <!-- JavaScript Configuration -->
    <script>
        const BASE_URL = "<?php echo rtrim(BASE_URL, '/'); ?>";
        const WISHLIST_API_URL = BASE_URL + "/app/controllers/WishlistController.php";
        const USER_ID = <?php echo (int)$userId; ?>;
    </script>

    <!-- Wishlist JavaScript -->
    <script src="<?php echo BASE_URL; ?>public/js/wishlist.js"></script>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');
            if (sidebar) {
                sidebar.classList.toggle('active');
                overlay.classList.toggle('active');
            }
        }
    </script>
</body>
</html>