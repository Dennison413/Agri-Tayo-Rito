<?php
// app/views/components/admin-nav.php
// Unified navbar component for all admin pages

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get current page for active state
$current_page = basename($_SERVER['PHP_SELF'], '.php');

// Get pending counts for badges
$db = new Database();
$conn = $db->connect();

$pending_applications = 0;
$pending_withdrawals = 0;
$pending_pickups = 0;

try {
    $stmt = $conn->query("SELECT COUNT(*) as total FROM seller_applications WHERE application_status = 'pending'");
    $pending_applications = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $conn->query("SELECT COUNT(*) as total FROM withdrawal_requests WHERE status = 'pending'");
    $pending_withdrawals = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $conn->query("SELECT COUNT(*) as total FROM orders WHERE lgu_delivery_status = 'pending_pickup'");
    $pending_pickups = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
} catch (Exception $e) {
    // Silently fail if tables don't exist
}
?>

<div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

<!-- Top Navigation -->
<nav class="top-navbar">
    <div class="logo-wrapper">
        <img src="<?php echo BASE_URL; ?>images/logo.jpg" alt="Logo">
        <span class="logo">Agri Tayo Rito</span>
    </div>
    
    <!-- Search Bar (shows on relevant pages) -->
    <?php if (in_array($current_page, ['users', 'manage-users', 'riders'])): ?>
    <div class="search-wrapper">
        <input type="text" 
               id="adminSearch" 
               class="search-input" 
               placeholder="<?php echo $current_page === 'riders' ? 'Search riders by name, contact, vehicle...' : 'Search users by name, email, ID...'; ?>"
               onkeyup="performSearch()">
        <span class="search-icon">🔍</span>
    </div>
    <?php endif; ?>
    
    <div class="navbar-actions">
        <button class="nav-btn" onclick="toggleSidebar()">
            <span class="menu-icon">☰</span>
        </button>
    </div>
</nav>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">🛡️</div>
        <div class="sidebar-title">
            <h2>Admin Dashboard</h2>
            <p><?php echo htmlspecialchars($_SESSION['username'] ?? 'Administrator'); ?></p>
        </div>
        <div class="sidebar-actions">
            <button class="sidebar-action-btn" onclick="toggleSidebar()">✕</button>
        </div>
    </div>

    <div class="user-info">
        <div class="user-avatar">👤</div>
        <div class="user-details">
            <h3><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></h3>
            <p>Administrator</p>
        </div>
    </div>

    <nav class="nav-menu">
        <div class="nav-section">
            <p class="nav-section-title">Main Menu</p>
            
            <button class="nav-item <?php echo $current_page === 'dashboard' ? 'active' : ''; ?>" 
                    onclick="location.href='<?php echo BASE_URL; ?>profile/admin/dashboard'">
                <span class="nav-icon-menu">📊</span>
                <span>Dashboard</span>
            </button>
            
            <button class="nav-item <?php echo $current_page === 'deliveries' ? 'active' : ''; ?>" 
                    onclick="location.href='<?php echo BASE_URL; ?>profile/admin/deliveries'">
                <span class="nav-icon-menu">🚚</span>
                <span>LGU Deliveries</span>
                <?php if ($pending_pickups > 0): ?>
                    <span class="nav-badge"><?php echo $pending_pickups; ?></span>
                <?php endif; ?>
            </button>
            
            <button class="nav-item <?php echo $current_page === 'withdrawals' ? 'active' : ''; ?>" 
                    onclick="location.href='<?php echo BASE_URL; ?>profile/admin/withdrawals'">
                <span class="nav-icon-menu">💰</span>
                <span>Withdrawals</span>
                <?php if ($pending_withdrawals > 0): ?>
                    <span class="nav-badge"><?php echo $pending_withdrawals; ?></span>
                <?php endif; ?>
            </button>
            
            <button class="nav-item <?php echo $current_page === 'cards' ? 'active' : ''; ?>" 
                    onclick="location.href='<?php echo BASE_URL; ?>profile/admin/cards'">
                <span class="nav-icon-menu">💳</span>
                <span>ATM Cards</span>
            </button>
            
            <button class="nav-item <?php echo $current_page === 'applications' ? 'active' : ''; ?>" 
                    onclick="location.href='<?php echo BASE_URL; ?>profile/admin/applications'">
                <span class="nav-icon-menu">📋</span>
                <span>Seller Applications</span>
                <?php if ($pending_applications > 0): ?>
                    <span class="nav-badge"><?php echo $pending_applications; ?></span>
                <?php endif; ?>
            </button>
            
            <button class="nav-item <?php echo $current_page === 'riders' ? 'active' : ''; ?>" 
                    onclick="location.href='<?php echo BASE_URL; ?>profile/admin/riders'">
                <span class="nav-icon-menu">🏍️</span>
                <span>Riders</span>
            </button>
            
            <button class="nav-item <?php echo $current_page === 'users' || $current_page === 'manage-users' ? 'active' : ''; ?>" 
                    onclick="location.href='<?php echo BASE_URL; ?>profile/admin/users'">
                <span class="nav-icon-menu">👥</span>
                <span>Manage Users</span>
            </button>
            
            <button class="nav-item <?php echo $current_page === 'products' ? 'active' : ''; ?>" 
                    onclick="location.href='<?php echo BASE_URL; ?>profile/admin/products'">
                <span class="nav-icon-menu">📦</span>
                <span>All Products</span>
            </button>
            
            <button class="nav-item <?php echo $current_page === 'category' || $current_page === 'categories' ? 'active' : ''; ?>" 
                    onclick="location.href='<?php echo BASE_URL; ?>profile/admin/category'">
                <span class="nav-icon-menu">🏷️</span>
                <span>Categories</span>
            </button>
            
            <button class="nav-item <?php echo $current_page === 'orders' || $current_page === 'orders-management' ? 'active' : ''; ?>" 
                    onclick="location.href='<?php echo BASE_URL; ?>profile/admin/orders'">
                <span class="nav-icon-menu">🛒</span>
                <span>All Orders</span>
            </button>
        </div>

        <div class="nav-section">
            <p class="nav-section-title">Settings</p>
            <button class="nav-item <?php echo $current_page === 'settings' ? 'active' : ''; ?>" 
                    onclick="location.href='<?php echo BASE_URL; ?>settings'">
                <span class="nav-icon-menu">⚙️</span>
                <span>System Settings</span>
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

<style>
/* ============================================
   ADMIN SIDEBAR STYLES
   ============================================ */

/* Overlay */
.overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 999;
}

.overlay.active {
    display: block;
}

/* Top Navbar */
.top-navbar {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    height: 70px;
    background: #2d5016;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 30px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    z-index: 1000;
    gap: 20px;
}

.logo-wrapper {
    display: flex;
    align-items: center;
    gap: 15px;
}

.logo-wrapper img {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    object-fit: cover;
}

.logo {
    font-size: 1.3rem;
    font-weight: 700;
    color: white;
}

.nav-btn {
    background: rgba(255, 255, 255, 0.1);
    border: none;
    padding: 10px 15px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s;
}

.nav-btn:hover {
    background: rgba(255, 255, 255, 0.2);
}

.menu-icon {
    color: white;
    font-size: 1.5rem;
}

/* Search Bar Styling */
.search-wrapper {
    flex: 1;
    max-width: 500px;
    position: relative;
}

.search-input {
    width: 100%;
    padding: 12px 45px 12px 15px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-radius: 25px;
    background: rgba(255, 255, 255, 0.15);
    color: white;
    font-size: 0.95rem;
    transition: all 0.3s;
}

.search-input::placeholder {
    color: rgba(255, 255, 255, 0.7);
}

.search-input:focus {
    outline: none;
    background: rgba(255, 255, 255, 0.25);
    border-color: rgba(255, 255, 255, 0.5);
}

.search-icon {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 1.2rem;
    pointer-events: none;
}

/* Sidebar */
.sidebar {
    position: fixed;
    top: 0;
    right: -400px;
    width: 350px;
    height: 100vh;
    background: white;
    box-shadow: -5px 0 25px rgba(0, 0, 0, 0.15);
    z-index: 1001;
    transition: right 0.3s ease;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
}

.sidebar.active {
    right: 0;
}

/* Sidebar Header */
.sidebar-header {
    background: #2d5016;
    color: white;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    position: relative;
}

.sidebar-logo {
    font-size: 2rem;
}

.sidebar-title h2 {
    margin: 0;
    font-size: 1.2rem;
}

.sidebar-title p {
    margin: 5px 0 0 0;
    opacity: 0.9;
    font-size: 0.9rem;
}

.sidebar-actions {
    position: absolute;
    right: 15px;
    top: 15px;
}

.sidebar-action-btn {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    width: 35px;
    height: 35px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 1.2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.sidebar-action-btn:hover {
    background: rgba(255, 255, 255, 0.3);
}

/* User Info */
.user-info {
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    border-bottom: 2px solid #f0f0f0;
}

.user-avatar {
    width: 55px;
    height: 55px;
    background: #2d5016;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
}

.user-details h3 {
    margin: 0;
    font-size: 1.1rem;
    color: #333;
}

.user-details p {
    margin: 5px 0 0 0;
    color: #666;
    font-size: 0.9rem;
}

/* Navigation Menu */
.nav-menu {
    flex: 1;
    padding: 10px 0;
    overflow-y: auto;
}

.nav-section {
    margin-bottom: 10px;
}

.nav-section-title {
    padding: 15px 20px 10px 20px;
    margin: 0;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    color: #999;
    letter-spacing: 0.5px;
}

.nav-item {
    width: 100%;
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 20px;
    background: none;
    border: none;
    border-left: 3px solid transparent;
    cursor: pointer;
    transition: all 0.2s;
    position: relative;
    text-align: left;
    font-size: 0.95rem;
    color: #555;
}

.nav-item:hover {
    background: #f8f9fa;
    border-left-color: #2d5016;
    color: #2d5016;
}

.nav-item.active {
    background: rgba(45, 80, 22, 0.1);
    border-left-color: #2d5016;
    color: #2d5016;
    font-weight: 600;
}

.nav-icon-menu {
    font-size: 1.3rem;
    width: 25px;
    text-align: center;
}

.nav-badge {
    margin-left: auto;
    background: #ef4444;
    color: white;
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
}

/* Sidebar Footer */
.sidebar-footer {
    padding: 20px;
    border-top: 2px solid #f0f0f0;
}

.logout-btn {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 14px;
    background: #ef4444;
    color: white;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    font-weight: 600;
    font-size: 1rem;
    transition: all 0.3s;
    box-shadow: 0 4px 10px rgba(239, 68, 68, 0.3);
}

.logout-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 15px rgba(239, 68, 68, 0.4);
}

.logout-btn span:first-child {
    font-size: 1.2rem;
}

/* Scrollbar Styling */
.sidebar::-webkit-scrollbar {
    width: 6px;
}

.sidebar::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.sidebar::-webkit-scrollbar-thumb {
    background: #ccc;
    border-radius: 3px;
}

.sidebar::-webkit-scrollbar-thumb:hover {
    background: #999;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .sidebar {
        width: 300px;
        right: -300px;
    }
    
    .top-navbar {
        padding: 0 15px;
    }
    
    .logo {
        font-size: 1.1rem;
    }
    
    .search-wrapper {
        display: none; /* Hide search on mobile */
    }
}

@media (max-width: 480px) {
    .sidebar {
        width: 280px;
        right: -280px;
    }
    
    .logo-wrapper img {
        width: 35px;
        height: 35px;
    }
}
</style>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    sidebar.classList.toggle('active');
    overlay.classList.toggle('active');
}

// Close sidebar when clicking outside
document.addEventListener('DOMContentLoaded', function() {
    const overlay = document.getElementById('overlay');
    if (overlay) {
        overlay.addEventListener('click', toggleSidebar);
    }
});

// Search functionality
function performSearch() {
    const searchTerm = document.getElementById('adminSearch').value.toLowerCase();
    const currentPage = '<?php echo $current_page; ?>';
    
    // Get all table rows based on current page
    let rows;
    if (currentPage === 'riders') {
        rows = document.querySelectorAll('.data-table tbody tr, .rider-card');
    } else {
        rows = document.querySelectorAll('.data-table tbody tr, .user-card');
    }
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        if (text.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
    
    // Also search in cards if they exist
    const cards = document.querySelectorAll('.rider-card, .user-card, .application-card');
    cards.forEach(card => {
        const text = card.textContent.toLowerCase();
        if (text.includes(searchTerm)) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
}
</script>