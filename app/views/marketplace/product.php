<?php
// app/views/marketplace/product.php
// Complete Product Detail Page with Backend Integration
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../models/Product.php';
require_once __DIR__ . '/../../models/Cart.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../helpers/csrf.php';

// ==================== USER AUTHENTICATION ====================
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'];
$userRole = $_SESSION['user_role'] ?? 'guest';
$userId = $_SESSION['user_id'] ?? null;

// ==================== GET PRODUCT ID ====================
$productID = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($productID <= 0) {
    header('Location: ' . BASE_URL . 'marketplace');
    exit;
}

// ==================== INITIALIZE MODELS ====================
$productModel = new Product();
$reviewModel = new Review();

// ==================== FETCH PRODUCT DATA ====================
try {
    $product = $productModel->getProductById($productID);
    
    if (!$product) {
        $_SESSION['error'] = 'Product not found';
        header('Location: ' . BASE_URL . 'marketplace');
        exit;
    }
    
    // Check if product is available
    if (!$product['is_available']) {
        $_SESSION['error'] = 'This product is no longer available';
        header('Location: ' . BASE_URL . 'marketplace');
        exit;
    }
    
} catch (Exception $e) {
    error_log("Product detail error: " . $e->getMessage());
    $_SESSION['error'] = 'Error loading product';
    header('Location: ' . BASE_URL . 'marketplace');
    exit;
}

// ==================== EXTRACT PRODUCT DATA ====================
$productName = htmlspecialchars($product['product_name']);
$productDesc = htmlspecialchars($product['description'] ?? 'Fresh from local farms');
$productPrice = (float)$product['price'];
$productUnit = htmlspecialchars($product['unit']);
$categoryName = htmlspecialchars($product['category'] ?? 'Products');

// Stock calculations
$stockQty = (int)($product['stock_quantity'] ?? 0);
$reservedQty = (int)($product['reserved_quantity'] ?? 0);
$availableStock = $stockQty - $reservedQty;
$lowStockThreshold = (int)($product['low_stock_threshold'] ?? 5);

// Shop information
$shopID = (int)$product['shopID'];
$shopName = htmlspecialchars($product['shop_name'] ?? 'Local Farm');
$shopSlug = $product['shop_slug'] ?? '';
$businessName = htmlspecialchars($product['business_name'] ?? $shopName);
$sellerRating = number_format((float)($product['seller_rating'] ?? 0), 1);

// Images
$mainImage = !empty($product['main_image']) 
    ? BASE_URL . ltrim($product['main_image'], '/') 
    : BASE_URL . 'images/placeholder.jpg';

$productImages = $product['images'] ?? [];
if (empty($productImages)) {
    $productImages[] = ['image_path' => $mainImage, 'is_main' => 1];
}

// Reviews
$reviews = $product['reviews'] ?? [];
$reviewStats = $product['review_stats'] ?? [
    'total_reviews' => 0,
    'average_rating' => 0,
    'five_star' => 0,
    'four_star' => 0,
    'three_star' => 0,
    'two_star' => 0,
    'one_star' => 0
];

$totalReviews = (int)$reviewStats['total_reviews'];
$avgRating = number_format((float)$reviewStats['average_rating'], 1);

// ==================== GET CART COUNT (BUYERS ONLY) ====================
$cartCount = 0;
if ($isLoggedIn && $userRole === 'buyer' && $userId) {
    try {
        $cartModel = new Cart();
        $cartCount = $cartModel->getCartCount($userId);
    } catch (Exception $e) {
        error_log("Cart count error: " . $e->getMessage());
    }
}

// ==================== CSRF TOKEN ====================
$csrfToken = CSRF::generateToken();

// ==================== PAGE TITLE ====================
$pageTitle = $productName . ' - Agri Tayo Rito';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/responsive.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/marketplace/marketplace.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/marketplace/product.css">
    
    <!-- CSRF Token -->
    <meta name="csrf-token" content="<?php echo $csrfToken; ?>">
    
    <!-- Open Graph Meta Tags for Sharing -->
    <meta property="og:title" content="<?php echo $productName; ?>">
    <meta property="og:description" content="<?php echo substr($productDesc, 0, 160); ?>">
    <meta property="og:image" content="<?php echo $mainImage; ?>">
    <meta property="og:type" content="product">
</head>
<body>
    <!-- Overlay for Mobile -->
    <div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

    <!-- ==================== TOP NAVIGATION ==================== -->
    <?php if ($isLoggedIn): ?>
        <?php include __DIR__ . '/topmarketnav.php'; ?>
    <?php else: ?>
        <!-- GUEST NAVIGATION -->
        <nav class="top-navbar">
            <div class="logo-wrapper">
                <img src="<?php echo BASE_URL; ?>images/logo.jpg" alt="Agri Tayo Rito Logo">
            </div>
            
            <div class="search-container">
                <form method="GET" action="<?php echo BASE_URL; ?>marketplace" id="searchForm">
                    <input type="text" 
                           name="search"
                           placeholder="Search for fresh products..." 
                           class="search-input">
                    <button type="submit" class="search-btn" title="Search">🔍</button>
                </form>
            </div>
            
            <div class="navbar-actions">
                <a href="<?php echo BASE_URL; ?>auth/login">
                    <button class="nav-btn" style="border: 2px solid #2d5016; color: #2d5016;">
                        Login
                    </button>
                </a>
                <a href="<?php echo BASE_URL; ?>auth/register">
                    <button class="nav-btn" style="background: #2d5016; color: white;">
                        Sign Up
                    </button>
                </a>
            </div>
        </nav>
    <?php endif; ?>

    <!-- ==================== MAIN CONTENT ==================== -->
    <main class="main-content">
        <div class="product-detail-container">
            
            <!-- ==================== BREADCRUMB ==================== -->
            <div class="product-breadcrumb">
                <a href="<?php echo BASE_URL; ?>marketplace" class="breadcrumb-link">Home</a>
                <span class="breadcrumb-separator">›</span>
                <a href="<?php echo BASE_URL; ?>marketplace?category=<?php echo $product['categoryID']; ?>" 
                   class="breadcrumb-link">
                    <?php echo $categoryName; ?>
                </a>
                <span class="breadcrumb-separator">›</span>
                <span><?php echo $productName; ?></span>
            </div>

            <!-- ==================== MAIN PRODUCT SECTION ==================== -->
            <div class="product-main-section">
                
                <!-- IMAGE GALLERY -->
                <div class="product-gallery">
                    <div class="main-image-container">
                        <img src="<?php echo $mainImage; ?>" 
                             alt="<?php echo $productName; ?>" 
                             class="main-product-image" 
                             id="mainImage"
                             onerror="this.src='<?php echo BASE_URL; ?>images/placeholder.jpg'">
                        
                        <?php if ($isLoggedIn && $userRole === 'buyer'): ?>
                            <button class="wishlist-badge" 
                                    onclick="toggleProductWishlist(<?php echo $productID; ?>)"
                                    data-product-id="<?php echo $productID; ?>"
                                    title="Add to Wishlist">
                                🤍
                            </button>
                        <?php endif; ?>
                        
                        <!-- Stock Badge -->
                        <?php if ($availableStock <= 0): ?>
                            <div class="badge badge-out-of-stock">Out of Stock</div>
                        <?php elseif ($availableStock <= $lowStockThreshold): ?>
                            <div class="badge badge-low-stock">Only <?php echo $availableStock; ?> left!</div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Thumbnail Images -->
                    <?php if (count($productImages) > 1): ?>
                        <div class="thumbnail-container">
                            <?php foreach ($productImages as $index => $img): 
                                $imgPath = BASE_URL . ltrim($img['image_path'], '/');
                                $isMainImg = (int)$img['is_main'];
                            ?>
                                <img src="<?php echo $imgPath; ?>" 
                                     class="thumbnail-image <?php echo $isMainImg ? 'active' : ''; ?>" 
                                     onclick="changeMainImage(this)"
                                     alt="Product image <?php echo $index + 1; ?>"
                                     onerror="this.src='<?php echo BASE_URL; ?>images/placeholder.jpg'">
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- ==================== PRODUCT INFO ==================== -->
                <div class="product-info-section">
                    <h1 class="product-title"><?php echo $productName; ?></h1>
                    
                    <!-- Rating & Reviews -->
                    <div class="product-rating">
                        <div class="stars">
                            <?php 
                            $fullStars = floor($avgRating);
                            $emptyStars = 5 - $fullStars;
                            
                            for ($i = 0; $i < $fullStars; $i++): ?>
                                <span class="star-filled">⭐</span>
                            <?php endfor; ?>
                            
                            <?php for ($i = 0; $i < $emptyStars; $i++): ?>
                                <span class="star-empty">⭐</span>
                            <?php endfor; ?>
                        </div>
                        <span class="rating-text">
                            <?php echo $avgRating; ?> (<?php echo $totalReviews; ?> reviews)
                        </span>
                        <span class="sold-count">| In Stock: <?php echo $availableStock; ?></span>
                    </div>

                    <!-- Price Section -->
                    <div class="product-price-section">
                        <div class="price-label">Price</div>
                        <div>
                            <span class="product-price-large">₱<?php echo number_format($productPrice, 2); ?></span>
                            <span class="price-unit">per <?php echo $productUnit; ?></span>
                        </div>
                    </div>

                    <!-- Quantity Selection -->
                    <div class="quantity-section">
                        <div class="option-label">Quantity:</div>
                        <div class="quantity-controls">
                            <button class="qty-btn" onclick="updateProductQty(-1)">−</button>
                            <div class="qty-display" id="productQty">1</div>
                            <button class="qty-btn" onclick="updateProductQty(1)">+</button>
                        </div>
                        <div class="stock-info">
                            <?php if ($availableStock > 0): ?>
                                <span class="stock-available">✓</span> 
                                <?php echo $availableStock; ?> <?php echo $productUnit; ?> available
                            <?php else: ?>
                                <span class="stock-unavailable">✗</span> Out of stock
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- ==================== ACTION BUTTONS ==================== -->
                    <div class="action-buttons">
                        <?php if (!$isLoggedIn): ?>
                            <!-- GUEST: Prompt Login -->
                            <button class="btn-add-cart" onclick="promptLogin(event)">
                                <span>🔒</span>
                                <span>Login to Purchase</span>
                            </button>
                            
                        <?php elseif ($userRole === 'buyer'): ?>
                            <!-- BUYER: Add to Cart & Buy Now -->
                            <?php if ($availableStock > 0): ?>
                                <button class="btn-add-cart" 
                                        onclick="addToCartFromDetail(<?php echo $productID; ?>)"
                                        data-product-id="<?php echo $productID; ?>">
                                    <span>🛒</span>
                                    <span>Add to Cart</span>
                                </button>
                                <button class="btn-buy-now" onclick="buyNow(<?php echo $productID; ?>)">
                                    <span>Buy Now</span>
                                    <span>→</span>
                                </button>
                            <?php else: ?>
                                <button class="btn-add-cart" disabled>
                                    <span>✗</span>
                                    <span>Out of Stock</span>
                                </button>
                            <?php endif; ?>
                            
                        <?php else: ?>
                            <!-- SELLER/ADMIN: View Only -->
                            <button class="btn-add-cart" disabled style="opacity: 0.6;">
                                <span>👁️</span>
                                <span>View Only (<?php echo ucfirst($userRole); ?>)</span>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- ==================== SELLER SECTION ==================== -->
            <div class="seller-section">
                <div class="seller-avatar">🌾</div>
                <div class="seller-info">
                    <div class="seller-name"><?php echo $businessName; ?></div>
                    <div class="seller-stats">
                        <div class="seller-stat">
                            <span class="stat-value"><?php echo $sellerRating; ?></span>
                            <span class="stat-label">Rating</span>
                        </div>
                        <div class="seller-stat">
                            <span class="stat-value"><?php echo $product['total_products'] ?? 0; ?></span>
                            <span class="stat-label">Products</span>
                        </div>
                    </div>
                </div>
                <?php if (!empty($shopSlug)): ?>
                    <a href="<?php echo BASE_URL; ?>marketplace/shop/<?php echo $shopSlug; ?>">
                        <button class="btn-visit-shop">Visit Shop</button>
                    </a>
                <?php endif; ?>
            </div>

            <!-- ==================== DESCRIPTION SECTION ==================== -->
            <div class="description-section">
                <h2 class="section-title">
                    <span>📋</span>
                    Product Description
                </h2>
                <p class="description-text">
                    <?php echo nl2br($productDesc); ?>
                </p>
                
                <div class="details-table">
                    <div class="details-row">
                        <div class="details-label">Category:</div>
                        <div class="details-value"><?php echo $categoryName; ?></div>
                    </div>
                    <div class="details-row">
                        <div class="details-label">Shop:</div>
                        <div class="details-value"><?php echo $shopName; ?></div>
                    </div>
                    <div class="details-row">
                        <div class="details-label">Unit:</div>
                        <div class="details-value"><?php echo $productUnit; ?></div>
                    </div>
                    <div class="details-row">
                        <div class="details-label">Available Stock:</div>
                        <div class="details-value"><?php echo $availableStock; ?> <?php echo $productUnit; ?></div>
                    </div>
                    <?php if (!empty($product['created_at'])): ?>
                        <div class="details-row">
                            <div class="details-label">Listed Date:</div>
                            <div class="details-value">
                                <?php echo date('F j, Y', strtotime($product['created_at'])); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ==================== REVIEWS SECTION ==================== -->
            <div class="reviews-section">
                <div class="reviews-header">
                    <h2 class="section-title">
                        <span>⭐</span>
                        Customer Reviews
                    </h2>
                </div>

                <?php if ($totalReviews > 0): ?>
                    <!-- Reviews List -->
                    <?php foreach ($reviews as $review): 
                        $reviewerName = htmlspecialchars($review['full_name'] ?? 'Anonymous');
                        $reviewRating = (int)$review['rating'];
                        $reviewText = htmlspecialchars($review['review_text'] ?? '');
                        $reviewDate = date('F j, Y', strtotime($review['review_date']));
                    ?>
                        <div class="review-card">
                            <div class="review-header">
                                <div>
                                    <div class="reviewer-name"><?php echo $reviewerName; ?></div>
                                    <div class="stars">
                                        <?php for ($i = 0; $i < $reviewRating; $i++): ?>
                                            <span class="star-filled">⭐</span>
                                        <?php endfor; ?>
                                        <?php for ($i = $reviewRating; $i < 5; $i++): ?>
                                            <span class="star-empty">⭐</span>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <div class="review-date"><?php echo $reviewDate; ?></div>
                            </div>
                            <?php if (!empty($reviewText)): ?>
                                <p class="review-text"><?php echo nl2br($reviewText); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    
                <?php else: ?>
                    <!-- No Reviews Message -->
                    <div class="no-reviews">
                        <p>No reviews yet. Be the first to review this product!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- ==================== SIDEBAR NAVIGATION ==================== -->
    <?php if ($isLoggedIn): ?>
        <?php include __DIR__ . '/marketnav.php'; ?>
    <?php endif; ?>

    <!-- ==================== JAVASCRIPT DATA ==================== -->
    <script>
        // Pass PHP data to JavaScript
        window.productData = {
            productID: <?php echo $productID; ?>,
            productName: <?php echo json_encode($productName); ?>,
            productPrice: <?php echo $productPrice; ?>,
            productUnit: <?php echo json_encode($productUnit); ?>,
            availableStock: <?php echo $availableStock; ?>,
            isLoggedIn: <?php echo $isLoggedIn ? 'true' : 'false'; ?>,
            userRole: <?php echo json_encode($userRole); ?>,
            userId: <?php echo $userId ?? 'null'; ?>,
            csrfToken: <?php echo json_encode($csrfToken); ?>,
            baseUrl: <?php echo json_encode(BASE_URL); ?>,
            cartCount: <?php echo $cartCount; ?>
        };
        
        console.log('🛍️ Product Detail Loaded:', window.productData.productName);
    </script>
    
    <!-- ==================== JAVASCRIPT FILES ==================== -->
    <script src="<?php echo BASE_URL; ?>js/marketplace.js"></script>
    <script src="<?php echo BASE_URL; ?>js/marketplace/items.js"></script>
</body>
</html>