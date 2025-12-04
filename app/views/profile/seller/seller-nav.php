<?php
// app/views/profile/seller/seller-nav.php
// Reusable navigation component for all seller pages
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get pending orders count for badge
$pending_count = 0;
if (isset($stats) && isset($stats['pending_orders'])) {
    $pending_count = $stats['pending_orders'];
}
?>

<!-- Top Navigation Bar -->
<nav class="top-navbar">
    <div class="logo-wrapper">
        <img src="<?php echo BASE_URL; ?>images/logo.jpg" alt="Logo">
        <span class="logo">Seller Panel</span>
    </div>
    <div class="navbar-actions">
        <button class="nav-btn" onclick="toggleSellerSidebar()">
            <span class="menu-icon">☰</span>
        </button>
    </div>
</nav>

<!-- Sidebar Navigation -->
<aside class="seller-sidebar" id="sellerSidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">🪴</div>
        <div class="sidebar-title">
            <h2>Seller Dashboard</h2>
            <p><?php echo htmlspecialchars($_SESSION['username'] ?? 'Seller'); ?></p>
        </div>
        <div class="sidebar-actions">
            <button class="sidebar-action-btn" onclick="toggleSellerSidebar()">✕</button>
        </div>
    </div>

    <div class="user-info">
        <div class="user-avatar">👤</div>
        <div class="user-details">
            <h3><?php echo htmlspecialchars($_SESSION['username'] ?? 'Seller'); ?></h3>
            <p>Seller Account</p>
        </div>
    </div>

    <nav class="nav-menu">
        <div class="nav-section">
            <p class="nav-section-title">MAIN MENU</p>
            <button class="nav-item" onclick="location.href='<?php echo BASE_URL; ?>marketplace'">
                <span class="nav-icon-menu">🍅</span>
                <span>View Marketplace</span>
            </button>
            <button class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>" onclick="location.href='<?php echo BASE_URL; ?>profile/seller/dashboard'">
                <span class="nav-icon-menu">📊</span>
                <span>Dashboard</span>
            </button>
            <button class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'products.php') ? 'active' : ''; ?>" onclick="location.href='<?php echo BASE_URL; ?>profile/seller/products'">
                <span class="nav-icon-menu">📦</span>
                <span>My Products</span>
            </button>
            <button class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'orders.php') ? 'active' : ''; ?>" onclick="location.href='<?php echo BASE_URL; ?>profile/seller/orders'">
                <span class="nav-icon-menu">🛒</span>
                <span>Orders</span>
                <?php if ($pending_count > 0): ?>
                    <span class="nav-badge"><?php echo $pending_count; ?></span>
                <?php endif; ?>
            </button>
            <button class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'withdrawals.php') ? 'active' : ''; ?>" onclick="location.href='<?php echo BASE_URL; ?>profile/seller/withdrawals'">
                <span class="nav-icon-menu">💰</span>
                <span>Withdrawals</span>
            </button>
            <button class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'image-upload.php') ? 'active' : ''; ?>" onclick="location.href='<?php echo BASE_URL; ?>profile/seller/image-upload'">
                <span class="nav-icon-menu">🖼️</span>
                <span>Product Images</span>
            </button>
        </div>

        <div class="nav-section">
            <p class="nav-section-title">SETTINGS</p>
            <button class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'profile-info.php') ? 'active' : ''; ?>" onclick="location.href='<?php echo BASE_URL; ?>profile/seller/profile-info'">
                <span class="nav-icon-menu">🏬</span>
                <span>Shop Profile</span>
            </button>
        </div>
    </nav>

    <div class="sidebar-footer">
        <button class="logout-btn" onclick="location.href='<?php echo BASE_URL; ?>auth/logout'">
            <span>🚪</span>
            <span>Logout</span>
        </button>
    </div>
</aside>

<!-- Overlay for mobile -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSellerSidebar()"></div>

<style>
/* ============================================
   SELLER NAVIGATION STYLES - RESPONSIVE
   ============================================ */

/* Top Navigation Bar */
.top-navbar {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    height: 70px;
    background: linear-gradient(135deg, #2d5016 0%, #4a7c25 100%);
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0 20px;
    z-index: 1000;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.logo-wrapper {
    display: flex;
    align-items: center;
    gap: 12px;
}

.logo-wrapper img {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid white;
}

.logo {
    color: white;
    font-size: 1.5rem;
    font-weight: bold;
}

.navbar-actions {
    display: none; /* Hidden on desktop */
}

.nav-btn {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    padding: 10px 15px;
    border-radius: 8px;
    cursor: pointer;
    transition: background 0.3s;
}

.nav-btn:hover {
    background: rgba(255, 255, 255, 0.3);
}

.menu-icon {
    color: white;
    font-size: 1.5rem;
}

/* Sidebar - Always visible on desktop */
.seller-sidebar {
    position: fixed;
    left: 0;
    top: 70px;
    bottom: 0;
    width: 280px;
    background: white;
    box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
    overflow-y: auto;
    z-index: 999;
    display: flex;
    flex-direction: column;
    /* Hide scrollbar for Chrome, Safari and Opera */
    scrollbar-width: none; /* Firefox */
    -ms-overflow-style: none; /* IE and Edge */
}

.seller-sidebar::-webkit-scrollbar {
    display: none; /* Chrome, Safari, Opera */
}

.sidebar-header {
    padding: 20px;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    align-items: center;
    gap: 12px;
}

.sidebar-logo {
    font-size: 2rem;
}

.sidebar-title h2 {
    margin: 0;
    font-size: 1.2rem;
    color: #1f2937;
}

.sidebar-title p {
    margin: 5px 0 0 0;
    font-size: 0.85rem;
    color: #6b7280;
}

.sidebar-actions {
    margin-left: auto;
    display: none; /* Hidden on desktop */
}

.sidebar-action-btn {
    background: none;
    border: none;
    font-size: 1.5rem;
    color: #6b7280;
    cursor: pointer;
    padding: 5px;
}

.user-info {
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    border-bottom: 1px solid #e5e7eb;
}

.user-avatar {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.user-details h3 {
    margin: 0;
    font-size: 1rem;
    color: #1f2937;
}

.user-details p {
    margin: 5px 0 0 0;
    font-size: 0.85rem;
    color: #6b7280;
}

.nav-menu {
    flex: 1;
    padding: 20px 0;
}

.nav-section {
    margin-bottom: 20px;
}

.nav-section-title {
    padding: 0 20px;
    margin: 0 0 10px 0;
    font-size: 0.75rem;
    font-weight: 600;
    color: #9ca3af;
    letter-spacing: 0.5px;
}

.nav-item {
    width: 100%;
    padding: 12px 20px;
    background: none;
    border: none;
    display: flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
    transition: all 0.3s;
    color: #4b5563;
    font-size: 0.95rem;
    text-align: left;
    position: relative;
}

.nav-item:hover {
    background: #f3f4f6;
    color: #2d5016;
}

.nav-item.active {
    background: linear-gradient(135deg, #ecfccb 0%, #d9f99d 100%);
    color: #2d5016;
    font-weight: 600;
    border-left: 4px solid #4a7c25;
}

.nav-icon-menu {
    font-size: 1.3rem;
}

.nav-badge {
    margin-left: auto;
    background: #ef4444;
    color: white;
    font-size: 0.75rem;
    padding: 2px 8px;
    border-radius: 10px;
    font-weight: 600;
}

.sidebar-footer {
    padding: 20px;
    border-top: 1px solid #e5e7eb;
}

.logout-btn {
    width: 100%;
    padding: 12px;
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: transform 0.3s;
}

.logout-btn:hover {
    transform: translateY(-2px);
}

/* Overlay - hidden on desktop */
.sidebar-overlay {
    display: none;
}

/* ============================================
   MOBILE RESPONSIVE (768px and below)
   ============================================ */
@media (max-width: 768px) {
    /* Show hamburger menu */
    .navbar-actions {
        display: block;
    }
    
    /* Hide sidebar by default on mobile */
    .seller-sidebar {
        left: -280px;
        transition: left 0.3s ease;
        top: 70px;
    }
    
    /* Show sidebar when active */
    .seller-sidebar.active {
        left: 0;
    }
    
    /* Show close button in sidebar on mobile */
    .sidebar-actions {
        display: block;
    }
    
    /* Show overlay when sidebar is active */
    .sidebar-overlay {
        display: none;
        position: fixed;
        top: 70px;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 998;
    }
    
    .sidebar-overlay.active {
        display: block;
    }
}

/* ============================================
   MAIN CONTENT ADJUSTMENT
   ============================================ */
/* This should be applied to your main content wrapper */
.main-content {
    margin-left: 280px;
    margin-top: 70px;
    padding: 30px;
    min-height: calc(100vh - 70px);
}

@media (max-width: 768px) {
    .main-content {
        margin-left: 0;
    }
}
</style>

<script>
function toggleSellerSidebar() {
    const sidebar = document.getElementById('sellerSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    sidebar.classList.toggle('active');
    overlay.classList.toggle('active');
}
</script>