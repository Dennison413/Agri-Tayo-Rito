<?php
// app/views/marketplace/topmarketnav.php
// SECURED: Top Navigation Bar with Role-Based Display
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    return; // Don't show for guests (they have their own nav in marketplace.php)
}

$userRole = $_SESSION['user_role'] ?? 'buyer';
$userId = $_SESSION['user_id'] ?? null;

// Get cart count for buyers only
$cartCount = 0;
if ($userRole === 'buyer' && $userId) {
    try {
        require_once __DIR__ . '/../../models/Cart.php';
        $cartModel = new Cart();
        $cartCount = $cartModel->getCartCount($userId);
        
        // Store in GLOBALS for sidebar use
        $GLOBALS['cartCount'] = $cartCount;
    } catch (Exception $e) {
        error_log("Cart count error: " . $e->getMessage());
    }
}
?>
<!-- TOP NAVBAR (LOGGED-IN USERS) -->
<nav class="top-navbar">
    <!-- LOGO -->
    <div class="logo-wrapper">
        <img src="<?php echo BASE_URL; ?>images/main-logo.jpg" 
             alt="Agri Tayo Rito Logo" 
             onclick="window.location.href='<?php echo BASE_URL; ?>marketplace'"
             style="cursor: pointer;">
    </div>

    <!-- SEARCH BAR -->
    <div class="search-container">
        <form method="GET" action="<?php echo BASE_URL; ?>marketplace" id="topSearchForm" style="display: flex; width: 100%; align-items: center;">
            <input type="text" 
                   name="search"
                   placeholder="Search fresh products..." 
                   class="search-input" 
                   id="searchInput"
                   value="<?php echo htmlspecialchars($_GET['search'] ?? '', ENT_QUOTES); ?>">
            <button type="submit" class="search-btn" title="Search">🔍</button>
        </form>
    </div>

    <!-- ACTION BUTTONS -->
    <div class="navbar-actions">
        
        <!-- SELLER: GO LIVE BUTTON -->
        <?php if ($userRole === 'seller'): ?>
            <a href="<?php echo BASE_URL; ?>marketplace/livestream" title="Start Live Stream" style="text-decoration: none;">
                <button class="nav-btn go-live-btn">
                    <span class="video-icon">🔴</span>
                    <span>Go Live</span>
                </button>
            </a>
        <?php endif; ?>
        
        <!-- MESSAGES (ALL ROLES) -->
        <a href="<?php echo BASE_URL; ?>marketplace/messages" title="Messages" style="text-decoration: none;">
            <button class="nav-btn message-btn">
                💬
                <span class="badge" id="messageBadge">0</span>
            </button>
        </a>
        
        <!-- CART (BUYERS ONLY) -->
        <?php if ($userRole === 'buyer'): ?>
            <a href="<?php echo BASE_URL; ?>item-handling/cart" title="Shopping Cart" style="text-decoration: none;">
                <button class="nav-btn cart-btn">
                    🛒
                    <span class="badge" id="cartBadge"><?php echo $cartCount; ?></span>
                </button>
            </a>
        <?php endif; ?>
        
        <!-- ADMIN: DASHBOARD LINK -->
        <?php if ($userRole === 'admin'): ?>
            <a href="<?php echo BASE_URL; ?>profile/admin/dashboard" title="Admin Dashboard" style="text-decoration: none;">
                <button class="nav-btn admin-btn">
                    ⚙️ Dashboard
                </button>
            </a>
        <?php endif; ?>
    </div>
</nav>

<style>
/* Top Navbar Link Fix */
.top-navbar a {
    text-decoration: none !important;
}

.navbar-actions a {
    text-decoration: none !important;
}

/* Admin button style */
.admin-btn {
    background: #2d5016;
    color: white;
}
.admin-btn:hover {
    background: #1f3810;
}

/* Search form fix - ensure button stays at end */
.search-container form {
    display: flex;
    width: 100%;
    align-items: center;
    gap: 5px;
}

.search-input {
    flex: 1;
}

.search-btn {
    flex-shrink: 0;
}
</style>