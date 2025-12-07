<?php
// app/views/marketplace/shop.php
// Buyer's perspective of seller shop - REWRITTEN & FIXED
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../models/Shop.php';
require_once __DIR__ . '/../../models/Product.php';
require_once __DIR__ . '/../../helpers/csrf.php';

// ==================== USER AUTHENTICATION ====================
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'];
$userRole = $_SESSION['user_role'] ?? 'guest';
$userId = $_SESSION['user_id'] ?? null;

// ==================== GET SHOP SLUG ====================
$shopSlug = $_GET['slug'] ?? '';

if (empty($shopSlug)) {
    $_SESSION['error'] = 'Shop not specified';
    header('Location: ' . BASE_URL . 'marketplace');
    exit;
}

// ==================== FETCH SHOP DATA USING MODELS ====================
$shopModel = new Shop();
$productModel = new Product();

try {
    // ✅ Use Shop model to get shop details
    $shop = $shopModel->getShopBySlug($shopSlug);
    
    if (!$shop) {
        $_SESSION['error'] = 'Shop not found';
        header('Location: ' . BASE_URL . 'marketplace');
        exit;
    }
    
    $shopID = (int)$shop['shopID'];
    
    // ✅ Use Product model to get shop products
    $products = $productModel->getProductsByShop($shopID, false);
    
    // ✅ Get shop statistics
    $shopStats = $shopModel->getShopStats($shopID);
    
} catch (Exception $e) {
    error_log("Shop page error: " . $e->getMessage());
    $_SESSION['error'] = 'Error loading shop';
    header('Location: ' . BASE_URL . 'marketplace');
    exit;
}

// ==================== EXTRACT & SANITIZE SHOP DATA ====================
$shopName = htmlspecialchars($shop['shop_name']);
$shopDescription = htmlspecialchars($shop['shop_description'] ?? 'Welcome to our shop!');
$businessName = htmlspecialchars($shop['business_name'] ?? $shopName);
$farmLocation = htmlspecialchars($shop['farm_location'] ?? 'Local Farm');
$shopLogo = $shop['shop_logo'] ?? null;
$shopBanner = $shop['shop_banner'] ?? null;

// Shop statistics
$rating = number_format((float)($shop['rating'] ?? 0), 1);
$totalProducts = (int)($shopStats['products']['total_products'] ?? count($products));
$totalOrders = (int)($shop['total_orders'] ?? 0);
$joinedYear = date('Y', strtotime($shop['created_at'] ?? 'now'));

// ==================== GET CART COUNT (IF BUYER) ====================
$cartCount = 0;
if ($isLoggedIn && $userRole === 'buyer' && $userId) {
    try {
        require_once __DIR__ . '/../../models/Cart.php';
        $cartModel = new Cart();
        $cartCount = $cartModel->getCartCount($userId);
    } catch (Exception $e) {
        error_log("Cart count error: " . $e->getMessage());
    }
}

// ==================== CSRF TOKEN ====================
$csrfToken = CSRF::generateToken();

$pageTitle = $shopName . ' - Agri Tayo Rito';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/responsive.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/marketplace/marketplace.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/marketplace/shop.css">
    
    <meta name="csrf-token" content="<?php echo $csrfToken; ?>">
    
    <style>
        /* Enhanced Shop Page Styles */
        .shop-page-wrapper {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .shop-header-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .shop-cover {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, #2d5016, #4a7c25);
            position: relative;
            overflow: hidden;
        }

        .shop-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .shop-info-section {
            padding: 20px 30px;
        }

        .shop-profile-row {
            display: flex;
            align-items: flex-start;
            gap: 25px;
            margin-top: -50px;
            flex-wrap: wrap;
        }

        .shop-avatar-large {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, #2d5016, #4a7c25);
            border: 5px solid white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            flex-shrink: 0;
            overflow: hidden;
        }

        .shop-avatar-large img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .shop-details-main {
            flex: 1;
            padding-top: 60px;
            min-width: 250px;
        }

        .shop-name-main {
            font-size: 2rem;
            font-weight: 700;
            color: #2d5016;
            margin-bottom: 8px;
        }

        .shop-location {
            color: #666;
            font-size: 0.95rem;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .shop-description-text {
            color: #555;
            line-height: 1.6;
            font-size: 0.95rem;
        }

        .shop-actions-row {
            display: flex;
            gap: 10px;
            padding-top: 60px;
        }

        .btn-follow-shop,
        .btn-message-shop {
            padding: 12px 25px;
            border-radius: 25px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            font-size: 0.9rem;
        }

        .btn-follow-shop {
            background: #2d5016;
            color: white;
        }

        .btn-follow-shop:hover {
            background: #4a7c25;
            transform: scale(1.05);
        }

        .btn-follow-shop.following {
            background: white;
            border: 2px solid #2d5016;
            color: #2d5016;
        }

        .btn-message-shop {
            background: white;
            border: 2px solid #2d5016;
            color: #2d5016;
        }

        .btn-message-shop:hover {
            background: #f5f5f5;
        }

        .shop-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            padding: 25px 30px;
            background: #f8f9fa;
            border-radius: 12px;
            margin-top: 20px;
        }

        .stat-box {
            text-align: center;
        }

        .stat-value-large {
            font-size: 2rem;
            font-weight: 700;
            color: #2d5016;
            display: block;
            margin-bottom: 5px;
        }

        .stat-label-large {
            font-size: 0.9rem;
            color: #666;
        }

        .shop-products-section {
            margin-top: 30px;
        }

        .section-header-shop {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            gap: 15px;
            flex-wrap: wrap;
        }

        .section-title-shop {
            font-size: 1.5rem;
            color: #2d5016;
            font-weight: 700;
        }

        .filter-tabs {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .filter-tab {
            padding: 10px 20px;
            border: 2px solid #e0e0e0;
            background: white;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
            font-size: 0.9rem;
            color: #666;
        }

        .filter-tab:hover,
        .filter-tab.active {
            border-color: #2d5016;
            background: #2d5016;
            color: white;
        }

        .products-grid-shop {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 20px;
        }

        .no-products-message {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .no-products-icon {
            font-size: 4rem;
            margin-bottom: 15px;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .shop-page-wrapper {
                padding: 15px;
            }

            .shop-cover {
                height: 150px;
            }

            .shop-info-section {
                padding: 15px 20px;
            }

            .shop-profile-row {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }

            .shop-avatar-large {
                width: 100px;
                height: 100px;
                font-size: 3rem;
            }

            .shop-details-main {
                padding-top: 20px;
            }

            .shop-name-main {
                font-size: 1.5rem;
            }

            .shop-actions-row {
                padding-top: 20px;
                width: 100%;
            }

            .btn-follow-shop,
            .btn-message-shop {
                flex: 1;
            }

            .shop-stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
                padding: 20px;
            }

            .products-grid-shop {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
            }

            .section-header-shop {
                flex-direction: column;
                align-items: flex-start;
            }

            .filter-tabs {
                width: 100%;
                overflow-x: auto;
                flex-wrap: nowrap;
                padding-bottom: 10px;
            }

            .filter-tab {
                white-space: nowrap;
                flex-shrink: 0;
            }
        }

        @media (max-width: 480px) {
            .shop-stats-grid {
                grid-template-columns: 1fr;
            }

            .stat-value-large {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

    <!-- TOP NAVIGATION -->
    <?php if ($isLoggedIn): ?>
        <?php include __DIR__ . '/topmarketnav.php'; ?>
    <?php else: ?>
        <nav class="top-navbar">
            <div class="logo-wrapper">
                <img src="<?php echo BASE_URL; ?>images/logo.jpg" alt="Agri Tayo Rito Logo">
            </div>
            
            <div class="search-container">
                <form method="GET" action="<?php echo BASE_URL; ?>marketplace">
                    <input type="text" 
                           name="search"
                           placeholder="Search for fresh products..." 
                           class="search-input">
                    <button type="submit" class="search-btn">🔍</button>
                </form>
            </div>
            
            <div class="navbar-actions">
                <a href="<?php echo BASE_URL; ?>auth/login">
                    <button class="nav-btn" style="border: 2px solid #2d5016; color: #2d5016;">Login</button>
                </a>
                <a href="<?php echo BASE_URL; ?>auth/register">
                    <button class="nav-btn" style="background: #2d5016; color: white;">Sign Up</button>
                </a>
            </div>
        </nav>
    <?php endif; ?>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <div class="shop-page-wrapper">
            
            <!-- BREADCRUMB -->
            <div class="product-breadcrumb" style="margin-bottom: 20px;">
                <a href="<?php echo BASE_URL; ?>marketplace" class="breadcrumb-link">Home</a>
                <span class="breadcrumb-separator">›</span>
                <a href="<?php echo BASE_URL; ?>marketplace" class="breadcrumb-link">Shops</a>
                <span class="breadcrumb-separator">›</span>
                <span><?php echo $shopName; ?></span>
            </div>

            <!-- SHOP HEADER -->
            <div class="shop-header-card">
                <div class="shop-cover">
                    <?php if ($shopBanner): ?>
                        <img src="<?php echo BASE_URL . ltrim($shopBanner, '/'); ?>" alt="<?php echo $shopName; ?> Banner">
                    <?php endif; ?>
                </div>
                
                <div class="shop-info-section">
                    <div class="shop-profile-row">
                        <div class="shop-avatar-large">
                            <?php if ($shopLogo): ?>
                                <img src="<?php echo BASE_URL . ltrim($shopLogo, '/'); ?>" alt="<?php echo $shopName; ?>">
                            <?php else: ?>
                                🌾
                            <?php endif; ?>
                        </div>
                        
                        <div class="shop-details-main">
                            <h1 class="shop-name-main"><?php echo $shopName; ?></h1>
                            <div class="shop-location">
                                <span>📍</span>
                                <span><?php echo $farmLocation; ?></span>
                            </div>
                            <p class="shop-description-text"><?php echo $shopDescription; ?></p>
                        </div>
                        
                        <div class="shop-actions-row">
                            <button class="btn-follow-shop" onclick="followShop()" id="followBtn">
                                <span>+ Follow</span>
                            </button>
                            <button class="btn-message-shop" onclick="messageShop()">
                                <span>💬 Message</span>
                            </button>
                        </div>
                    </div>
                    
                    <!-- SHOP STATS -->
                    <div class="shop-stats-grid">
                        <div class="stat-box">
                            <span class="stat-value-large"><?php echo $rating; ?></span>
                            <span class="stat-label-large">⭐ Rating</span>
                        </div>
                        <div class="stat-box">
                            <span class="stat-value-large"><?php echo $totalProducts; ?></span>
                            <span class="stat-label-large">📦 Products</span>
                        </div>
                        <div class="stat-box">
                            <span class="stat-value-large"><?php echo $totalOrders; ?></span>
                            <span class="stat-label-large">✓ Orders</span>
                        </div>
                        <div class="stat-box">
                            <span class="stat-value-large"><?php echo $joinedYear; ?></span>
                            <span class="stat-label-large">📅 Joined</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PRODUCTS SECTION -->
            <div class="shop-products-section">
                <div class="section-header-shop">
                    <h2 class="section-title-shop">Shop Products (<?php echo count($products); ?>)</h2>
                    
                    <div class="filter-tabs">
                        <button class="filter-tab active" onclick="filterProducts('all')">All</button>
                        <button class="filter-tab" onclick="filterProducts('Vegetables')">Vegetables</button>
                        <button class="filter-tab" onclick="filterProducts('Fruits')">Fruits</button>
                        <button class="filter-tab" onclick="filterProducts('Seeds')">Seeds</button>
                        <button class="filter-tab" onclick="filterProducts('Saplings')">Saplings</button>
                    </div>
                </div>

                <?php if (empty($products)): ?>
                    <div class="no-products-message">
                        <div class="no-products-icon">📦</div>
                        <h3>No Products Yet</h3>
                        <p>This shop hasn't listed any products yet. Check back soon!</p>
                        <a href="<?php echo BASE_URL; ?>marketplace" style="margin-top: 20px; display: inline-block;">
                            <button class="btn-follow-shop">Browse Other Shops</button>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="products-grid-shop" id="productsGrid">
                        <?php foreach ($products as $product): 
                            $productId = (int)$product['productID'];
                            $productName = htmlspecialchars($product['product_name']);
                            $productPrice = number_format($product['price'], 2);
                            $productUnit = htmlspecialchars($product['unit']);
                            $categoryName = htmlspecialchars($product['category'] ?? 'Products');
                            
                            $stockQty = (int)($product['stock_quantity'] ?? 0);
                            $reservedQty = (int)($product['reserved_quantity'] ?? 0);
                            $availableStock = $stockQty - $reservedQty;
                            
                            $mainImage = !empty($product['main_image']) 
                                ? BASE_URL . ltrim($product['main_image'], '/') 
                                : BASE_URL . 'images/placeholder.jpg';
                            
                            $canPurchase = $availableStock > 0;
                        ?>
                            <div class="product-card" 
                                 data-category="<?php echo $categoryName; ?>"
                                 onclick="viewProduct(<?php echo $productId; ?>)">
                                
                                <div class="product-image">
                                    <img src="<?php echo $mainImage; ?>" 
                                         alt="<?php echo $productName; ?>"
                                         loading="lazy"
                                         onerror="this.src='<?php echo BASE_URL; ?>images/placeholder.jpg'">
                                    
                                    <?php if ($isLoggedIn && $userRole === 'buyer'): ?>
                                        <button class="wishlist-btn" 
                                                onclick="toggleWishlist(event, <?php echo $productId; ?>)" 
                                                data-product-id="<?php echo $productId; ?>"
                                                title="Add to Wishlist">
                                            🤍
                                        </button>
                                    <?php endif; ?>
                                    
                                    <?php if (!$canPurchase): ?>
                                        <div class="out-of-stock-badge">Out of Stock</div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="product-info">
                                    <h3 class="product-name"><?php echo $productName; ?></h3>
                                    
                                    <div class="product-footer">
                                        <div class="product-price-section">
                                            <span class="product-price">₱<?php echo $productPrice; ?></span>
                                            <span class="product-unit">/<?php echo $productUnit; ?></span>
                                        </div>
                                        
                                        <?php if (!$isLoggedIn): ?>
                                            <button class="add-to-cart-btn" onclick="promptLogin(event)">
                                                🔒 Login
                                            </button>
                                        <?php elseif ($userRole === 'buyer'): ?>
                                            <?php if ($canPurchase): ?>
                                                <button class="add-to-cart-btn" 
                                                        onclick="addToCart(event, <?php echo $productId; ?>)">
                                                    🛒 Add
                                                </button>
                                            <?php else: ?>
                                                <button class="add-to-cart-btn" disabled>
                                                    ✗ Out
                                                </button>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <button class="add-to-cart-btn" onclick="viewProduct(<?php echo $productId; ?>)">
                                                👁️ View
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- SIDEBAR -->
    <?php if ($isLoggedIn): ?>
        <?php include __DIR__ . '/marketnav.php'; ?>
    <?php endif; ?>

    <script>
        window.shopData = {
            shopID: <?php echo $shopID; ?>,
            shopName: <?php echo json_encode($shopName); ?>,
            isLoggedIn: <?php echo $isLoggedIn ? 'true' : 'false'; ?>,
            userRole: <?php echo json_encode($userRole); ?>,
            userId: <?php echo $userId ?? 'null'; ?>,
            csrfToken: <?php echo json_encode($csrfToken); ?>,
            baseUrl: '<?php echo BASE_URL; ?>'
        };

        console.log('🏪 Shop Page Loaded:', window.shopData.shopName);

        function filterProducts(category) {
            const products = document.querySelectorAll('.product-card');
            const tabs = document.querySelectorAll('.filter-tab');
            
            tabs.forEach(tab => tab.classList.remove('active'));
            event.target.classList.add('active');
            
            let visibleCount = 0;
            products.forEach(product => {
                if (category === 'all' || product.dataset.category === category) {
                    product.style.display = 'block';
                    visibleCount++;
                } else {
                    product.style.display = 'none';
                }
            });

            console.log(`Filtered to ${category}: ${visibleCount} products visible`);
        }

        function followShop() {
            if (!window.shopData.isLoggedIn) {
                if (confirm('Please login to follow this shop.\n\nWould you like to login now?')) {
                    window.location.href = window.shopData.baseUrl + 'auth/login';
                }
                return;
            }
            
            const btn = document.getElementById('followBtn');
            btn.classList.toggle('following');
            
            if (btn.classList.contains('following')) {
                btn.innerHTML = '<span>✓ Following</span>';
                showNotification('You are now following ' + window.shopData.shopName + '!', 'success');
            } else {
                btn.innerHTML = '<span>+ Follow</span>';
                showNotification('Unfollowed ' + window.shopData.shopName, 'info');
            }
        }

        function messageShop() {
            if (!window.shopData.isLoggedIn) {
                if (confirm('Please login to message this shop.\n\nWould you like to login now?')) {
                    window.location.href = window.shopData.baseUrl + 'auth/login';
                }
                return;
            }
            
            showNotification('💬 Message feature coming soon!', 'info');
        }

        function viewProduct(productId) {
            window.location.href = window.shopData.baseUrl + 'marketplace/product?id=' + productId;
        }

        function showNotification(message, type = 'success') {
            const notification = document.createElement('div');
            notification.className = `notification-toast notification-${type}`;
            notification.textContent = message;
            document.body.appendChild(notification);

            setTimeout(() => notification.classList.add('show'), 10);

            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        function promptLogin(event) {
            event.stopPropagation();
            if (confirm('Please login to add items to your cart.\n\nWould you like to login now?')) {
                sessionStorage.setItem('redirectAfterLogin', window.location.href);
                window.location.href = window.shopData.baseUrl + 'auth/login';
            }
        }
    </script>

    <script src="<?php echo BASE_URL; ?>js/marketplace.js"></script>
</body>
</html>