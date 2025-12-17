<?php
// app/views/components/admin-nav.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$current_page = basename($_SERVER['PHP_SELF'], '.php');

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
}
?>

<!-- Top Navigation Bar -->
<nav class="top-navbar">
    <div class="logo-wrapper">
        <img src="<?php echo BASE_URL; ?>images/main-logo.jpg" alt="Logo">
        <span class="logo">Admin Panel</span>
    </div>
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
        <button class="nav-btn" onclick="toggleAdminSidebar()">
            <span class="menu-icon">☰</span>
        </button>
    </div>
</nav>

<aside class="admin-sidebar" id="adminSidebar">
    
    <div class="sidebar-header">
        <div class="sidebar-logo">🛡️</div>
        <div class="sidebar-title">
            <h2>Agri Tayo Rito</h2>
            <p>Fresh from Farm to Table</p>
        </div>
        <div class="sidebar-actions">
            <button class="sidebar-action-btn" onclick="toggleAdminSidebar()">✕</button>
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
            <p class="nav-section-title">MAIN MENU</p>
            <button class="nav-item <?php echo $current_page === 'category' || $current_page === 'categories' ? 'active' : ''; ?>" 
                    onclick="location.href='<?php echo BASE_URL; ?>marketplace'">
                <span class="nav-icon-menu">🍅</span>
                <span>View Marketplace</span>
            </button>
            
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
        </div>

        <div class="nav-section">
            <p class="nav-section-title">SETTINGS</p>
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

<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleAdminSidebar()"></div>

<style>
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
    gap: 20px;
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
    display: none;
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

.admin-sidebar {
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
    scrollbar-width: none;
    -ms-overflow-style: none;
}

.admin-sidebar::-webkit-scrollbar {
    display: none;
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
    color: #ffffffff;
}

.sidebar-title p {
    margin: 5px 0 0 0;
    font-size: 0.85rem;
    color: #ffffffff;
}

.sidebar-actions {
    margin-left: auto;
    display: none;
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

.sidebar-overlay {
    display: none;
}

@media (max-width: 768px) {
    .navbar-actions {
        display: block;
    }
    
    .admin-sidebar {
        left: -280px;
        transition: left 0.3s ease;
        top: 70px;
    }
    
    .admin-sidebar.active {
        left: 0;
    }
    .sidebar-actions {
        display: block;
    }
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
    
    .search-wrapper {
        display: none;
    }
}
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
function toggleAdminSidebar() {
    const sidebar = document.getElementById('adminSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    sidebar.classList.toggle('active');
    overlay.classList.toggle('active');
}

document.addEventListener('DOMContentLoaded', function() {
    const overlay = document.getElementById('sidebarOverlay');
    if (overlay) {
        overlay.addEventListener('click', toggleAdminSidebar);
    }
});

function performSearch() {
    const searchTerm = document.getElementById('adminSearch')?.value.toLowerCase();
    if (!searchTerm) return;
    
    const currentPage = '<?php echo $current_page; ?>';
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