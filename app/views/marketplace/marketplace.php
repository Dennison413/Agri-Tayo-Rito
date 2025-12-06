<?php
// app/views/marketplace/marketplace.php
// Production-Ready Marketplace with Role-Based Access
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../models/Product.php';
require_once __DIR__ . '/../../models/Category.php';
require_once __DIR__ . '/../../models/Cart.php';
require_once __DIR__ . '/../../helpers/csrf.php';

// ==================== USER AUTHENTICATION ====================
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'];
$userRole = $_SESSION['user_role'] ?? 'guest';
$userId = $_SESSION['user_id'] ?? null;

// ==================== INITIALIZE MODELS ====================
$productModel = new Product();
$categoryModel = new Category();

// ==================== GET COUNTS (BUYERS ONLY) ====================
$cartCount = 0;
$wishlistCount = 0;

if ($isLoggedIn && $userRole === 'buyer' && $userId) {
    try {
        $cartModel = new Cart();
        $wishlistModel = new Cart();
        
        $cartCount = $cartModel->getCartCount($userId);
        $wishlistCount = $wishlistModel->getWishlistCount($userId);
    } catch (Exception $e) {
        error_log("Error loading counts: " . $e->getMessage());
    }
}

// ==================== FILTERS FROM URL ====================
$filters = [
    'search' => $_GET['search'] ?? null,
    'categoryID' => $_GET['category'] ?? null,
    'min_price' => $_GET['min_price'] ?? null,
    'max_price' => $_GET['max_price'] ?? null,
    'in_stock' => true, // Always show only in-stock items
    'sort' => $_GET['sort'] ?? 'newest'
];

// Remove empty filters
$filters = array_filter($filters, function($value) {
    return $value !== null && $value !== '';
});

$limit = 20;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// ==================== FETCH DATA ====================
try {
    $products = $productModel->getAllProducts($filters, $limit, $offset);
    $categories = $categoryModel->getAllCategories();
    
    error_log("✅ Marketplace loaded: " . count($products) . " products, " . count($categories) . " categories");
    
} catch (Exception $e) {
    error_log("❌ Marketplace error: " . $e->getMessage());
    $products = [];
    $categories = [];
}

// ==================== CSRF TOKEN ====================
$csrfToken = CSRF::generateToken();

// ==================== CURRENT CATEGORY NAME ====================
$currentCategoryName = 'All Products';
if (!empty($filters['categoryID'])) {
    foreach ($categories as $cat) {
        if ($cat['categoryID'] == $filters['categoryID']) {
            $currentCategoryName = htmlspecialchars($cat['category']);
            break;
        }
    }
}

// ==================== PAGE TITLE ====================
$pageTitle = 'Marketplace';
if (!empty($filters['search'])) {
    $pageTitle = 'Search: ' . htmlspecialchars($filters['search']);
} elseif (!empty($currentCategoryName) && $currentCategoryName !== 'All Products') {
    $pageTitle = $currentCategoryName;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Agri Tayo Rito</title>
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/responsive.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/marketplace/marketplace.css">
    
    <!-- CSRF Token for AJAX -->
    <meta name="csrf-token" content="<?php echo $csrfToken; ?>">
    
    <!-- Preload Important Resources -->
    <link rel="preconnect" href="<?php echo BASE_URL; ?>">
</head>
<body>
    <!-- Overlay for Mobile Sidebar -->
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
                           class="search-input" 
                           id="searchInput"
                           value="<?php echo htmlspecialchars($filters['search'] ?? ''); ?>">
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
        
        <!-- ==================== FEATURED CATEGORIES ==================== -->
        <section class="featured-section">
            <h2 class="section-title">Browse by Category</h2>
            
            <?php if (!empty($categories)): ?>
                <div class="category-slider">
                    <button class="slider-arrow prev-arrow" onclick="slideCategories('prev')" aria-label="Previous">◀</button>
                    
                    <div class="category-slider-wrapper">
                        <div class="category-slider-track" id="categoryTrack">
                            <!-- CATEGORY CARDS FROM DATABASE -->
                            <?php foreach ($categories as $cat): 
                                $catID = (int)$cat['categoryID'];
                                $catName = htmlspecialchars($cat['category']);
                                $catImage = !empty($cat['image_url']) 
                                    ? BASE_URL . ltrim($cat['image_url'], '/') 
                                    : BASE_URL . 'images/all-products.jpg';
                                $isActive = !empty($filters['categoryID']) && $filters['categoryID'] == $catID;
                            ?>
                                <div class="category-card <?php echo $isActive ? 'active' : ''; ?>" 
                                     onclick="filterByCategory(<?php echo $catID; ?>)">
                                    <div class="category-image">
                                        <img src="<?php echo $catImage; ?>" 
                                             alt="<?php echo $catName; ?>"
                                             onerror="this.src='<?php echo BASE_URL; ?>images/all-products.jpg'">
                                    </div>
                                    <div class="category-overlay">
                                        <h3 class="category-name"><?php echo $catName; ?></h3>
                                        <p class="category-count">Explore</p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                             <!-- ALL PRODUCTS CARD -->
                            <div class="category-card <?php echo empty($filters['categoryID']) ? 'active' : ''; ?>" 
                                 onclick="filterByCategory(null)">
                                <div class="category-image">
                                    <img src="<?php echo BASE_URL; ?>images/categories/all-products.jpg" 
                                         alt="All Products"
                                         onerror="this.src='<?php echo BASE_URL; ?>images/all-products.jpg'">
                                </div>
                                <div class="category-overlay">
                                    <h3 class="category-name">All Products</h3>
                                    <p class="category-count">View Everything</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <button class="slider-arrow next-arrow" onclick="slideCategories('next')" aria-label="Next">▶</button>
                </div>
            <?php else: ?>
                <div class="no-data-message">
                    <p>📦 No categories available yet. Check back soon!</p>
                </div>
            <?php endif; ?>
        </section>

        <!-- ==================== PRODUCTS GRID ==================== -->
        <section class="products-section">
            
            <!-- SECTION HEADER -->
            <div class="section-header">
                <div>
                    <h2 class="section-title"><?php echo $pageTitle; ?></h2>
                    <?php if (!empty($filters['search'])): ?>
                        <p class="search-results-text">
                            Showing results for "<strong><?php echo htmlspecialchars($filters['search']); ?></strong>"
                        </p>
                    <?php endif; ?>
                </div>
                
                <!-- SORT DROPDOWN -->
                <div class="filter-controls">
                    <form method="GET" action="<?php echo BASE_URL; ?>marketplace" id="sortForm">
                        <!-- Preserve existing filters -->
                        <?php if (!empty($filters['search'])): ?>
                            <input type="hidden" name="search" value="<?php echo htmlspecialchars($filters['search']); ?>">
                        <?php endif; ?>
                        <?php if (!empty($filters['categoryID'])): ?>
                            <input type="hidden" name="category" value="<?php echo (int)$filters['categoryID']; ?>">
                        <?php endif; ?>
                        
                        <select name="sort" class="filter-select" onchange="this.form.submit()">
                            <option value="newest" <?php echo ($filters['sort'] ?? '') === 'newest' ? 'selected' : ''; ?>>
                                Latest Products
                            </option>
                            <option value="price_low" <?php echo ($filters['sort'] ?? '') === 'price_low' ? 'selected' : ''; ?>>
                                Price: Low to High
                            </option>
                            <option value="price_high" <?php echo ($filters['sort'] ?? '') === 'price_high' ? 'selected' : ''; ?>>
                                Price: High to Low
                            </option>
                            <option value="name" <?php echo ($filters['sort'] ?? '') === 'name' ? 'selected' : ''; ?>>
                                Name A-Z
                            </option>
                        </select>
                    </form>
                </div>
            </div>

            <!-- PRODUCTS GRID -->
            <div class="products-grid" id="productsGrid">
                <?php if (empty($products)): ?>
                    <!-- NO PRODUCTS MESSAGE -->
                    <div class="no-products">
                        <div class="no-products-icon">📦</div>
                        <h3>No Products Found</h3>
                        <?php if (!empty($filters['search'])): ?>
                            <p>We couldn't find any products matching "<strong><?php echo htmlspecialchars($filters['search']); ?></strong>"</p>
                            <a href="<?php echo BASE_URL; ?>marketplace" class="btn-primary">
                                View All Products
                            </a>
                        <?php else: ?>
                            <p>No products available in this category right now.</p>
                        <?php endif; ?>
                    </div>
                    
                <?php else: ?>
                    <!-- PRODUCT CARDS -->
                    <?php foreach ($products as $product): 
                        // Extract product data
                        $productId = (int)$product['productID'];
                        $productName = htmlspecialchars($product['product_name']);
                        $productDesc = htmlspecialchars(substr($product['description'] ?? 'Fresh from local farms', 0, 80));
                        $productPrice = number_format($product['price'], 2);
                        $productUnit = htmlspecialchars($product['unit']);
                        
                        // Calculate available stock
                        $stockQty = (int)($product['stock_quantity'] ?? 0);
                        $reservedQty = (int)($product['reserved_quantity'] ?? 0);
                        $availableStock = $stockQty - $reservedQty;
                        
                        // Get image
                        $mainImage = !empty($product['main_image']) 
                            ? BASE_URL . ltrim($product['main_image'], '/') 
                            : BASE_URL . 'images/placeholder.jpg';
                        
                        // Shop info
                        $shopName = htmlspecialchars($product['shop_name'] ?? 'Local Farm');
                        $shopSlug = $product['shop_slug'] ?? '';
                        
                        // Availability
                        $isAvailable = (int)($product['is_available'] ?? 0);
                        $lowStockThreshold = (int)($product['low_stock_threshold'] ?? 5);
                        $canPurchase = $isAvailable && $availableStock > 0;
                    ?>
                        <div class="product-card" 
                             data-product-id="<?php echo $productId; ?>"
                             onclick="viewProduct(<?php echo $productId; ?>)">
                            
                            <!-- PRODUCT IMAGE -->
                            <div class="product-image">
                                <img src="<?php echo $mainImage; ?>" 
                                     alt="<?php echo $productName; ?>"
                                     loading="lazy"
                                     onerror="this.src='<?php echo BASE_URL; ?>images/placeholder.jpg'">
                                
                                <!-- WISHLIST BUTTON (Buyers Only) -->
                                <?php if ($isLoggedIn && $userRole === 'buyer'): ?>
                                    <button class="wishlist-btn" 
                                            onclick="toggleWishlist(event, <?php echo $productId; ?>)" 
                                            data-product-id="<?php echo $productId; ?>"
                                            title="Add to Wishlist">
                                        🤍
                                    </button>
                                <?php endif; ?>
                                
                                <!-- STOCK BADGES -->
                                <?php if (!$canPurchase): ?>
                                    <div class="badge badge-out-of-stock">Out of Stock</div>
                                <?php elseif ($availableStock <= $lowStockThreshold): ?>
                                    <div class="badge badge-low-stock">Only <?php echo $availableStock; ?> left!</div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- PRODUCT INFO -->
                            <div class="product-info">
                                <!-- Shop Name -->
                                <div class="product-shop">
                                    <?php if (!empty($shopSlug)): ?>
                                        <a href="<?php echo BASE_URL; ?>marketplace/shop/<?php echo $shopSlug; ?>" 
                                           onclick="event.stopPropagation();" 
                                           class="shop-link">
                                            🏪 <?php echo $shopName; ?>
                                        </a>
                                    <?php else: ?>
                                        🏪 <?php echo $shopName; ?>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Product Name -->
                                <h3 class="product-name" title="<?php echo $productName; ?>">
                                    <?php echo $productName; ?>
                                </h3>
                                
                                <!-- Product Description -->
                                <p class="product-description">
                                    <?php echo $productDesc; ?>
                                    <?php if (strlen($product['description'] ?? '') > 80): ?>...<?php endif; ?>
                                </p>
                                
                                <!-- FOOTER -->
                                <div class="product-footer">
                                    <!-- Price -->
                                    <div class="product-price-section">
                                        <span class="product-price">₱<?php echo $productPrice; ?></span>
                                        <span class="product-unit">/<?php echo $productUnit; ?></span>
                                    </div>
                                    
                                    <!-- ACTION BUTTON -->
                                    <?php if (!$isLoggedIn): ?>
                                        <!-- GUEST: Prompt Login -->
                                        <button class="add-to-cart-btn" onclick="promptLogin(event)">
                                            🔒 Login to Buy
                                        </button>
                                        
                                    <?php elseif ($userRole === 'buyer'): ?>
                                        <!-- BUYER: Add to Cart -->
                                        <?php if ($canPurchase): ?>
                                            <button class="add-to-cart-btn" 
                                                    onclick="addToCart(event, <?php echo $productId; ?>)"
                                                    data-product-id="<?php echo $productId; ?>">
                                                🛒 Add to Cart
                                            </button>
                                        <?php else: ?>
                                            <button class="add-to-cart-btn" disabled>
                                                ❌ Out of Stock
                                            </button>
                                        <?php endif; ?>
                                        
                                    <?php else: ?>
                                        <!-- SELLER/ADMIN: View Only -->
                                        <button class="add-to-cart-btn" onclick="viewProduct(<?php echo $productId; ?>)">
                                            👁️ View Details
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- PAGINATION -->
            <?php if (count($products) >= $limit): ?>
                <div class="pagination-container">
                    <a href="?<?php echo http_build_query(array_merge($filters, ['page' => max(1, $page - 1)])); ?>" 
                       class="btn-pagination <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        ← Previous
                    </a>
                    <span class="page-info">Page <?php echo $page; ?></span>
                    <a href="?<?php echo http_build_query(array_merge($filters, ['page' => $page + 1])); ?>" 
                       class="btn-pagination">
                        Next →
                    </a>
                </div>
            <?php endif; ?>
            
        </section>
    </main>

    <!-- ==================== SIDEBAR NAVIGATION ==================== -->
    <?php if ($isLoggedIn): ?>
        <?php include __DIR__ . '/marketnav.php'; ?>
    <?php endif; ?>

    <!-- ==================== JAVASCRIPT DATA ==================== -->
    <script>
        // Pass PHP data to JavaScript
        window.marketplaceData = {
            isLoggedIn: <?php echo $isLoggedIn ? 'true' : 'false'; ?>,
            userRole: <?php echo json_encode($userRole); ?>,
            userId: <?php echo $userId ?? 'null'; ?>,
            cartCount: <?php echo $cartCount; ?>,
            wishlistCount: <?php echo $wishlistCount; ?>,
            csrfToken: <?php echo json_encode($csrfToken); ?>,
            baseUrl: <?php echo json_encode(BASE_URL); ?>,
            currentCategory: <?php echo json_encode($filters['categoryID'] ?? null); ?>,
            currentSort: <?php echo json_encode($filters['sort'] ?? 'newest'); ?>,
            currentPage: <?php echo $page; ?>
        };
        
        console.log('🌾 Agri Tayo Rito Marketplace Loaded');
        console.log('👤 User Role:', window.marketplaceData.userRole);
        console.log('📦 Products:', <?php echo count($products); ?>);
        console.log('🏷️ Categories:', <?php echo count($categories); ?>);
    </script>
    
    <!-- ==================== MARKETPLACE JAVASCRIPT ==================== -->
    <script src="<?php echo BASE_URL; ?>js/marketplace.js"></script>
</body>
</html>