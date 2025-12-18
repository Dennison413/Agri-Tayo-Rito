<?php
// app/views/profile/admin/manage-users.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../config/database.php';

$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'];
$userRole = $_SESSION['user_role'] ?? null;

if (!$isLoggedIn || $userRole !== 'admin') {
    header('Location: ' . BASE_URL . 'auth/login');
    exit;
}

$db = new Database();
$conn = $db->connect();

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'toggle_status') {
        $userID = intval($_POST['user_id']);
        $currentStatus = intval($_POST['current_status']);
        $newStatus = $currentStatus === 1 ? 0 : 1;
        
        $stmt = $conn->prepare("UPDATE users SET is_active = ? WHERE userID = ?");
        $result = $stmt->execute([$newStatus, $userID]);
        
        if ($result) {
            $_SESSION['success'] = $newStatus === 1 ? 'User activated successfully' : 'User deactivated successfully';
        } else {
            $_SESSION['error'] = 'Failed to update user status';
        }
    }
    elseif ($_POST['action'] === 'approve_deletion') {
        $requestID = intval($_POST['request_id']);
        $userID = intval($_POST['user_id']);
        
        try {
            $conn->beginTransaction();
            
            // Update request status
            $stmt = $conn->prepare("UPDATE account_deletion_requests SET status = 'approved', reviewed_by = ?, reviewed_at = NOW() WHERE requestID = ?");
            $stmt->execute([$_SESSION['user_id'], $requestID]);
            
            // Deactivate user account
            $stmt = $conn->prepare("UPDATE users SET is_active = 0 WHERE userID = ?");
            $stmt->execute([$userID]);
            
            $conn->commit();
            $_SESSION['success'] = 'Account deletion request approved';
        } catch (Exception $e) {
            $conn->rollBack();
            $_SESSION['error'] = 'Failed to process deletion request';
        }
    }
    elseif ($_POST['action'] === 'reject_deletion') {
        $requestID = intval($_POST['request_id']);
        
        $stmt = $conn->prepare("UPDATE account_deletion_requests SET status = 'rejected', reviewed_by = ?, reviewed_at = NOW() WHERE requestID = ?");
        $result = $stmt->execute([$_SESSION['user_id'], $requestID]);
        
        if ($result) {
            $_SESSION['success'] = 'Deletion request rejected';
        } else {
            $_SESSION['error'] = 'Failed to reject deletion request';
        }
    }
    
    header('Location: ' . BASE_URL . 'profile/admin/users');
    exit;
}

// Get filter parameters
$roleFilter = $_GET['role'] ?? 'all';
$statusFilter = $_GET['status'] ?? 'all';
$provinceFilter = $_GET['province'] ?? '';
$municipalityFilter = $_GET['municipality'] ?? '';
$searchTerm = $_GET['search'] ?? '';

// Build query for users
$query = "SELECT u.*, 
          CASE WHEN sp.sellerID IS NOT NULL THEN s.shop_name ELSE NULL END as shop_name,
          CASE WHEN sp.sellerID IS NOT NULL THEN s.balance ELSE NULL END as shop_balance,
          ua.municipality, ua.province, ua.postal_code
          FROM users u
          LEFT JOIN seller_profiles sp ON u.userID = sp.userID
          LEFT JOIN shops s ON sp.shopID = s.shopID
          LEFT JOIN user_addresses ua ON u.userID = ua.userID
          WHERE 1=1";

$params = [];

if ($roleFilter !== 'all') {
    $query .= " AND u.role = ?";
    $params[] = $roleFilter;
}

if ($statusFilter !== 'all') {
    $query .= " AND u.is_active = ?";
    $params[] = $statusFilter === 'active' ? 1 : 0;
}

if ($provinceFilter) {
    $query .= " AND ua.province LIKE ?";
    $params[] = "%$provinceFilter%";
}

if ($municipalityFilter) {
    $query .= " AND ua.municipality LIKE ?";
    $params[] = "%$municipalityFilter%";
}

if ($searchTerm) {
    $query .= " AND (u.userID LIKE ? OR u.full_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $searchParam = "%$searchTerm%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$query .= " GROUP BY u.userID ORDER BY u.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats = [];
$stmt = $conn->query("SELECT COUNT(*) as total FROM users");
$stats['total'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'buyer'");
$stats['buyers'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'seller'");
$stats['sellers'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM users WHERE is_active = 1");
$stats['active'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM users WHERE is_active = 0");
$stats['inactive'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Get deletion requests
$deletionRequests = $conn->query("SELECT adr.*, u.full_name, u.email, u.role 
                                   FROM account_deletion_requests adr
                                   JOIN users u ON adr.userID = u.userID
                                   WHERE adr.status = 'pending'
                                   ORDER BY adr.requested_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Get unique provinces and municipalities for filter
$provinces = $conn->query("SELECT DISTINCT province FROM user_addresses WHERE province IS NOT NULL ORDER BY province")->fetchAll(PDO::FETCH_COLUMN);
$municipalities = $conn->query("SELECT DISTINCT municipality FROM user_addresses WHERE municipality IS NOT NULL ORDER BY municipality")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/jpg" href="/agri_system/public/images/agri-icon.jpg">
    <title>User Management - Agri Tayo Rito</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/responsive.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/admin/dashboard.css">
    <style>
        .user-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }

        .user-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .user-card.inactive {
            opacity: 0.7;
            background: #f9fafb;
        }

        .user-header {
            display: flex;
            gap: 15px;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .user-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #e5e7eb;
        }

        .user-info-header {
            flex: 1;
        }

        .user-info-header h3 {
            margin: 0 0 5px 0;
            font-size: 1.1rem;
        }

        .user-role-badge {
            padding: 4px 12px;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }

        .user-role-badge.buyer {
            background: #dbeafe;
            color: #1e40af;
        }

        .user-role-badge.seller {
            background: #dcfce7;
            color: #166534;
        }

        .user-role-badge.admin {
            background: #f3e8ff;
            color: #6b21a8;
        }

        .user-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            margin-bottom: 15px;
        }

        .user-detail-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .detail-label {
            font-size: 0.8rem;
            color: #6b7280;
            font-weight: 500;
        }

        .detail-value {
            font-size: 0.9rem;
            color: #1f2937;
            font-weight: 600;
        }

        .user-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-view {
            flex: 1;
            padding: 8px 16px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .btn-toggle-status {
            flex: 1;
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .btn-toggle-status.activate {
            background: #dcfce7;
            color: #166534;
        }

        .btn-toggle-status.deactivate {
            background: #fee2e2;
            color: #991b1b;
        }

        .deletion-requests-section {
            background: #fff7ed;
            border-left: 4px solid #f59e0b;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
        }

        .deletion-card {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            border-left: 4px solid #dc2626;
        }

        .deletion-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .deletion-actions {
            display: flex;
            gap: 10px;
        }

        .btn-approve-deletion {
            padding: 8px 16px;
            background: #16a34a;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
        }

        .btn-reject-deletion {
            padding: 8px 16px;
            background: #dc2626;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
        }

        .users-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        @media (max-width: 768px) {
            .users-grid {
                grid-template-columns: 1fr;
            }

            .user-details {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <div class="overlay" id="overlay" onclick="toggleSidebar()"></div>
    
    <?php require_once __DIR__ . '/admin-nav.php'; ?>

    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">👥 User Management</h1>
            <p class="page-subtitle">Manage all users, buyers, and sellers</p>
        </div>

        <!-- Statistics -->
        <section class="stats-section">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">👥</div>
                <div class="stat-info">
                    <h3 class="stat-value"><?php echo number_format($stats['total']); ?></h3>
                    <p class="stat-label">Total Users</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);">🛒</div>
                <div class="stat-info">
                    <h3 class="stat-value"><?php echo number_format($stats['buyers']); ?></h3>
                    <p class="stat-label">Buyers</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">🏪</div>
                <div class="stat-info">
                    <h3 class="stat-value"><?php echo number_format($stats['sellers']); ?></h3>
                    <p class="stat-label">Sellers</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #2d5016 0%, #4a7c25 100%);">✅</div>
                <div class="stat-info">
                    <h3 class="stat-value"><?php echo number_format($stats['active']); ?></h3>
                    <p class="stat-label">Active Users</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">⛔</div>
                <div class="stat-info">
                    <h3 class="stat-value"><?php echo number_format($stats['inactive']); ?></h3>
                    <p class="stat-label">Inactive Users</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">⚠️</div>
                <div class="stat-info">
                    <h3 class="stat-value"><?php echo count($deletionRequests); ?></h3>
                    <p class="stat-label">Deletion Requests</p>
                </div>
            </div>
        </section>

        <!-- Deletion Requests -->
        <?php if (count($deletionRequests) > 0): ?>
        <section class="deletion-requests-section">
            <h3 style="margin: 0 0 20px 0; color: #92400e;">⚠️ Account Deletion Requests (<?php echo count($deletionRequests); ?>)</h3>
            
            <?php foreach ($deletionRequests as $request): ?>
            <div class="deletion-card">
                <div class="deletion-header">
                    <div>
                        <strong><?php echo htmlspecialchars($request['full_name']); ?></strong>
                        <span class="user-role-badge <?php echo $request['role']; ?>"><?php echo ucfirst($request['role']); ?></span>
                        <div style="font-size: 0.85rem; color: #6b7280; margin-top: 5px;">
                            <?php echo htmlspecialchars($request['email']); ?> | 
                            Requested: <?php echo date('M d, Y h:i A', strtotime($request['requested_at'])); ?>
                        </div>
                    </div>
                </div>
                
                <?php if ($request['reason']): ?>
                <div style="margin: 10px 0; padding: 10px; background: #f9fafb; border-radius: 6px;">
                    <strong style="font-size: 0.85rem; color: #6b7280;">Reason:</strong>
                    <p style="margin: 5px 0 0 0; font-size: 0.9rem;"><?php echo htmlspecialchars($request['reason']); ?></p>
                </div>
                <?php endif; ?>
                
                <div class="deletion-actions">
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="request_id" value="<?php echo $request['requestID']; ?>">
                        <input type="hidden" name="user_id" value="<?php echo $request['userID']; ?>">
                        <input type="hidden" name="action" value="approve_deletion">
                        <button type="submit" class="btn-approve-deletion" onclick="return confirm('Approve this deletion request? This will deactivate the user account.')">
                            ✓ Approve
                        </button>
                    </form>
                    
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="request_id" value="<?php echo $request['requestID']; ?>">
                        <input type="hidden" name="action" value="reject_deletion">
                        <button type="submit" class="btn-reject-deletion" onclick="return confirm('Reject this deletion request?')">
                            ✕ Reject
                        </button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </section>
        <?php endif; ?>

        <!-- Filters -->
        <section class="filters-section">
            <h3 style="margin: 0 0 20px 0; color: #1f2937;">🔍 Filter Users</h3>
            <form method="GET" action="">
                <div class="filters-grid">
                    <div class="filter-group">
                        <label>User Role</label>
                        <select name="role">
                            <option value="all" <?php echo $roleFilter === 'all' ? 'selected' : ''; ?>>All Roles</option>
                            <option value="buyer" <?php echo $roleFilter === 'buyer' ? 'selected' : ''; ?>>Buyers</option>
                            <option value="seller" <?php echo $roleFilter === 'seller' ? 'selected' : ''; ?>>Sellers</option>
                            <option value="admin" <?php echo $roleFilter === 'admin' ? 'selected' : ''; ?>>Admins</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Status</option>
                            <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Province</label>
                        <select name="province">
                            <option value="">All Provinces</option>
                            <?php foreach ($provinces as $province): ?>
                                <option value="<?php echo htmlspecialchars($province); ?>" <?php echo $provinceFilter === $province ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($province); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Municipality</label>
                        <select name="municipality">
                            <option value="">All Municipalities</option>
                            <?php foreach ($municipalities as $municipality): ?>
                                <option value="<?php echo htmlspecialchars($municipality); ?>" <?php echo $municipalityFilter === $municipality ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($municipality); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Search User</label>
                        <input type="text" name="search" placeholder="ID, name, email, phone..." 
                               value="<?php echo htmlspecialchars($searchTerm); ?>">
                    </div>
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn-filter">🔍 Apply Filters</button>
                    <button type="button" onclick="location.href='<?php echo BASE_URL; ?>profile/admin/users'" class="btn-reset">🔄 Reset</button>
                </div>
            </form>
        </section>

        <!-- Users Grid -->
        <section class="users-section">
            <div class="section-header">
                <h2 class="section-title">All Users</h2>
                <span class="badge"><?php echo count($users); ?> users</span>
            </div>

            <?php if (count($users) > 0): ?>
            <div class="users-grid">
                <?php foreach ($users as $user): ?>
                <div class="user-card <?php echo $user['is_active'] ? '' : 'inactive'; ?>">
                    <div class="user-header">
                        <img src="<?php echo BASE_URL . ($user['profile_image'] ?? $user['avatar'] ?? 'images/avatars/avt1.jpg'); ?>" 
                             alt="Avatar" class="user-avatar">
                        <div class="user-info-header">
                            <h3><?php echo htmlspecialchars($user['full_name']); ?></h3>
                            <span class="user-role-badge <?php echo $user['role']; ?>">
                                <?php echo ucfirst($user['role']); ?>
                            </span>
                            <?php if (!$user['is_active']): ?>
                                <span class="status-badge inactive" style="margin-left: 5px;">Inactive</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="user-details">
                        <div class="user-detail-item">
                            <span class="detail-label">User ID</span>
                            <span class="detail-value">#<?php echo $user['userID']; ?></span>
                        </div>
                        <div class="user-detail-item">
                            <span class="detail-label">Email</span>
                            <span class="detail-value"><?php echo htmlspecialchars($user['email']); ?></span>
                        </div>
                        <?php if ($user['phone']): ?>
                        <div class="user-detail-item">
                            <span class="detail-label">Phone</span>
                            <span class="detail-value"><?php echo htmlspecialchars($user['phone']); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ($user['municipality']): ?>
                        <div class="user-detail-item">
                            <span class="detail-label">Location</span>
                            <span class="detail-value"><?php echo htmlspecialchars($user['municipality'] . ', ' . $user['province']); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ($user['shop_name']): ?>
                        <div class="user-detail-item">
                            <span class="detail-label">Shop</span>
                            <span class="detail-value"><?php echo htmlspecialchars($user['shop_name']); ?></span>
                        </div>
                        <div class="user-detail-item">
                            <span class="detail-label">Balance</span>
                            <span class="detail-value">₱<?php echo number_format($user['shop_balance'] ?? 0, 2); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="user-detail-item">
                            <span class="detail-label">Joined</span>
                            <span class="detail-value"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></span>
                        </div>
                    </div>

                    <div class="user-actions">
                        <button onclick="viewUserDetails(<?php echo $user['userID']; ?>)" class="btn-view">
                            👁️ View Details
                        </button>
                        <form method="POST" style="flex: 1;">
                            <input type="hidden" name="user_id" value="<?php echo $user['userID']; ?>">
                            <input type="hidden" name="current_status" value="<?php echo $user['is_active']; ?>">
                            <input type="hidden" name="action" value="toggle_status">
                            <button type="submit" class="btn-toggle-status <?php echo $user['is_active'] ? 'deactivate' : 'activate'; ?>"
                                    onclick="return confirm('<?php echo $user['is_active'] ? 'Deactivate' : 'Activate'; ?> this user?')">
                                <?php echo $user['is_active'] ? '⛔ Deactivate' : '✅ Activate'; ?>
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="no-data">
                <p>📭 No users found matching your filters</p>
            </div>
            <?php endif; ?>
        </section>

        <!-- Users Table -->
        <section class="table-section">
            <div class="section-header">
                <h2 class="section-title">Users List</h2>
            </div>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th>Joined</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td>#<?php echo $user['userID']; ?></td>
                            <td><strong><?php echo htmlspecialchars($user['full_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <span class="user-role-badge <?php echo $user['role']; ?>">
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                            </td>
                            <td><?php echo $user['municipality'] ? htmlspecialchars($user['municipality'] . ', ' . $user['province']) : '-'; ?></td>
                            <td>
                                <?php if ($user['is_active']): ?>
                                    <span class="status-badge active">Active</span>
                                <?php else: ?>
                                    <span class="status-badge inactive">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <!-- User Details Modal -->
    <div id="userModal" class="modal">
        <div class="modal-content large">
            <h3>👤 User Details</h3>
            <div id="userDetailsContent">
                <p style="text-align: center; padding: 40px;">Loading...</p>
            </div>
            <div class="modal-actions">
                <button type="button" onclick="closeModal()" class="btn-cancel">Close</button>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');
            if (sidebar && overlay) {
                sidebar.classList.toggle('active');
                overlay.classList.toggle('active');
            }
        }

        function viewUserDetails(userID) {
            document.getElementById('userModal').classList.add('active');
            document.getElementById('userDetailsContent').innerHTML = '<p style="text-align: center; padding: 40px;">🔄 Loading user details...</p>';
            
            // You can implement an API endpoint to fetch detailed user info
            // For now, we'll just show a message
            document.getElementById('userDetailsContent').innerHTML = '<p style="text-align: center; padding: 40px;">User details view - Implement API endpoint for full details</p>';
        }

        function closeModal() {
            document.querySelectorAll('.modal').forEach(m => m.classList.remove('active'));
        }

        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeModal();
                }
            });
        });
    </script>
</body>
</html>