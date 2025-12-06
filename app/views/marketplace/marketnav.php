<?php
// app/views/marketplace/marketnav.php
// Role-Based Sidebar Navigation
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    // Don't show sidebar for guests
    return;
}

$userRole = $_SESSION['user_role'] ?? 'buyer';
$username = $_SESSION['username'] ?? 'User';
$userId = $_SESSION['user_id'] ?? null;
$avatar = $_SESSION['avatar'] ?? BASE_URL . 'images/avatars/avt1.jpg';

// Get wishlist count for buyers
$wishlistCount = 0;
if ($userRole === 'buyer' && $userId) {
    try {
        require_once __DIR__ . '/../../models/Cart.php';
        $cartModel = new Cart();
        $wishlistCount = $cartModel->getWishlistCount($userId);
    } catch (Exception $e) {
        error_log("Wishlist count error: " . $e->getMessage());
    }
}

// Define navigation items based on role
$navItems = [];

switch ($userRole) {
    case 'buyer':
        $navItems = [
            'main' => [
                ['icon' => '🏠', 'label' => 'Marketplace', 'url' => BASE_URL . 'marketplace'],
                ['icon' => '🛒', 'label' => 'My Cart', 'url' => BASE_URL . 'item-handling/cart', 'badge' => 'cart'],
                ['icon' => '📦', 'label' => 'My Orders', 'url' => BASE_URL . 'marketplace/myorders'],
                ['icon' => '❤️', 'label' => 'Wishlist', 'url' => BASE_URL . 'marketplace/wishlist', 'badge' => 'wishlist'],
            ],
            'account' => [
                ['icon' => '👤', 'label' => 'My Profile', 'url' => BASE_URL . 'profile/user'],
                ['icon' => '📝', 'label' => 'Apply as Seller', 'url' => BASE_URL . 'profile/apply'],
                ['icon' => '⚙️', 'label' => 'Settings', 'url' => BASE_URL . 'settings'],
            ]
        ];
        break;

    case 'seller':
        $navItems = [
            'main' => [
                ['icon' => '🏠', 'label' => 'Marketplace', 'url' => BASE_URL . 'marketplace'],
                ['icon' => '📊', 'label' => 'Dashboard', 'url' => BASE_URL . 'profile/seller/dashboard'],
                ['icon' => '📦', 'label' => 'My Products', 'url' => BASE_URL . 'profile/seller/products'],
                ['icon' => '🛍️', 'label' => 'Orders', 'url' => BASE_URL . 'profile/seller/orders'],
                ['icon' => '💰', 'label' => 'Earnings', 'url' => BASE_URL . 'profile/seller/transactions'],
            ],
            'account' => [
                ['icon' => '🏪', 'label' => 'My Shop', 'url' => BASE_URL . 'profile/seller/shop'],
                ['icon' => '⚙️', 'label' => 'Settings', 'url' => BASE_URL . 'profile/settings'],
            ]
        ];
        break;

    case 'admin':
        $navItems = [
            'main' => [
                ['icon' => '🏠', 'label' => 'Marketplace', 'url' => BASE_URL . 'marketplace'],
                ['icon' => '📊', 'label' => 'Dashboard', 'url' => BASE_URL . 'profile/admin/dashboard'],
                ['icon' => '👥', 'label' => 'Users', 'url' => BASE_URL . 'profile/admin/users'],
                ['icon' => '📝', 'label' => 'Applications', 'url' => BASE_URL . 'profile/admin/applications'],
                ['icon' => '🚚', 'label' => 'Deliveries', 'url' => BASE_URL . 'profile/admin/deliveries'],
            ],
            'account' => [
                ['icon' => '⚙️', 'label' => 'Settings', 'url' => BASE_URL . 'profile/admin/settings'],
            ]
        ];
        break;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/marketplace/marketplace.css">
    <style>
        .sidebar .nav-link,
        .sidebar a {
            text-decoration: none !important;
        }
    </style>
</head>

<body>
    <!-- SIDEBAR -->
    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">

        <!-- SIDEBAR HEADER -->
        <div class="sidebar-header">
            <span class="sidebar-logo">🌾</span>
            <div class="sidebar-title">
                <h2>Agri Tayo Rito</h2>
                <p>Farm Fresh Marketplace</p>
            </div>
        </div>

        <!-- USER INFO -->
        <div class="user-info">
            <div class="user-avatar">
                <img src="<?php echo htmlspecialchars($avatar); ?>" alt="<?php echo htmlspecialchars($username); ?>">
            </div>
            <div class="user-details">
                <h3><?php echo htmlspecialchars($username); ?></h3>
                <p class="user-role-badge"><?php echo ucfirst($userRole); ?></p>
            </div>
        </div>

        <!-- NAVIGATION MENU -->
        <nav class="nav-menu">
            <!-- MAIN MENU -->
            <?php if (!empty($navItems['main'])): ?>
                <div class="nav-section">
                    <div class="nav-section-title">MAIN MENU</div>
                    <?php foreach ($navItems['main'] as $item): ?>
                        <a href="<?php echo $item['url']; ?>" class="nav-link" style="text-decoration: none !important;">
                            <button class="nav-item">
                                <span class="nav-icon"><?php echo $item['icon']; ?></span>
                                <span class="nav-label"><?php echo $item['label']; ?></span>

                                <!-- Badge for cart/wishlist -->
                                <?php if (isset($item['badge'])): ?>
                                    <?php if ($item['badge'] === 'cart' && isset($GLOBALS['cartCount']) && $GLOBALS['cartCount'] > 0): ?>
                                        <span class="nav-badge cart-badge"><?php echo $GLOBALS['cartCount']; ?></span>
                                    <?php elseif ($item['badge'] === 'wishlist' && $wishlistCount > 0): ?>
                                        <span class="nav-badge wishlist-badge"><?php echo $wishlistCount; ?></span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </button>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- ACCOUNT SECTION -->
            <?php if (!empty($navItems['account'])): ?>
                <div class="nav-section">
                    <div class="nav-section-title">ACCOUNT</div>
                    <?php foreach ($navItems['account'] as $item): ?>
                        <a href="<?php echo $item['url']; ?>" class="nav-link" style="text-decoration: none !important;">
                            <button class="nav-item">
                                <span class="nav-icon"><?php echo $item['icon']; ?></span>
                                <span class="nav-label"><?php echo $item['label']; ?></span>
                            </button>
                        </a>
                    <?php endforeach; ?>

                    <!-- Help & Support -->
                    <a href="<?php echo BASE_URL; ?>devs/contacts" class="nav-link" style="text-decoration: none !important;">
                        <button class="nav-item">
                            <span class="nav-icon">❓</span>
                            <span class="nav-label">Help & Support</span>
                        </button>
                    </a>

                    <!-- About -->
                    <a href="<?php echo BASE_URL; ?>devs/about" class="nav-link" style="text-decoration: none !important;">
                        <button class="nav-item">
                            <span class="nav-icon">ℹ️</span>
                            <span class="nav-label">About Us</span>
                        </button>
                    </a>
                </div>
            <?php endif; ?>
        </nav>

        <!-- LOGOUT BUTTON -->
        <div class="sidebar-footer">
            <form action="<?php echo BASE_URL; ?>auth/logout" method="POST" style="margin: 0;">
                <?php echo CSRF::getTokenField(); ?>
                <button type="submit" class="logout-btn">
                    <span class="nav-icon">🚪</span>
                    <span>Logout</span>
                </button>
            </form>
        </div>
    </aside>

    <!-- BOTTOM NAVIGATION (MOBILE) -->
    <nav class="bottom-nav">
        <a href="<?php echo BASE_URL; ?>marketplace" class="bottom-nav-link" style="text-decoration: none;">
            <div class="bottom-nav-btn active">
                <span class="bottom-nav-icon">🏠</span>
                <span class="bottom-nav-label">Home</span>
            </div>
        </a>

        <a href="<?php echo BASE_URL; ?>marketplace/notifications" class="bottom-nav-link" style="text-decoration: none;">
            <div class="bottom-nav-btn">
                <span class="bottom-nav-icon">🔔</span>
                <span class="bottom-nav-label">Notifications</span>
            </div>
        </a>

        <a href="<?php echo $navItems['account'][0]['url']; ?>" class="bottom-nav-link" style="text-decoration: none;">
            <div class="bottom-nav-btn">
                <span class="bottom-nav-icon">👤</span>
                <span class="bottom-nav-label">Profile</span>
            </div>
        </a>

        <div class="bottom-nav-btn" onclick="toggleSidebar()" style="cursor: pointer;">
            <span class="bottom-nav-icon">☰</span>
            <span class="bottom-nav-label">Menu</span>
        </div>
    </nav>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');

            if (sidebar) sidebar.classList.toggle('active');
            if (overlay) overlay.classList.toggle('active');
        }
    </script>
</body>

</html>