<?php
// app/views/marketplace/topmarketnav.php
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    return;
}

$userRole = $_SESSION['user_role'] ?? 'buyer';
$userId = $_SESSION['user_id'] ?? null;

$cartCount = 0;
if ($userRole === 'buyer' && $userId) {
    try {
        require_once __DIR__ . '/../../models/Cart.php';
        $cartModel = new Cart();
        $cartCount = $cartModel->getCartCount($userId);

        $GLOBALS['cartCount'] = $cartCount;
    } catch (Exception $e) {
        error_log("Cart count error: " . $e->getMessage());
    }
}
?>
<nav class="top-navbar">
    <div class="logo-wrapper">
        <img src="<?php echo BASE_URL; ?>images/main-logo.jpg"
            alt="Agri Tayo Rito Logo"
            onclick="window.location.href='<?php echo BASE_URL; ?>marketplace'"
            style="cursor: pointer;">
    </div>

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

    <div class="navbar-actions">
        <a href="<?php echo BASE_URL; ?>marketplace/messages" title="Messages" style="text-decoration: none;">
            <button class="nav-btn message-btn">
                💬
                <span class="badge" id="messageBadge">0</span>
            </button>
        </a>

        <!-- Cart (buyers only) -->
        <?php if ($userRole === 'buyer'): ?>
            <a href="<?php echo BASE_URL; ?>item-handling/cart" title="Shopping Cart" style="text-decoration: none;">
                <button class="nav-btn cart-btn">
                    🛒
                    <span class="badge" id="cartBadge"><?php echo $cartCount; ?></span>
                </button>
            </a>
        <?php endif; ?>

        <!-- Admin Dasboard Link -->
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
    .top-navbar a {
        text-decoration: none !important;
    }

    .navbar-actions a {
        text-decoration: none !important;
    }

    .admin-btn {
        background: #2d5016;
        color: white;
    }

    .admin-btn:hover {
        background: #1f3810;
    }

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