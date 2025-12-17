<?php
// app/views/profile/admin/cards.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../config/database.php';
require_once __DIR__ . '/../../../models/Shop.php';

$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'];
$userRole = $_SESSION['user_role'] ?? null;

if (!$isLoggedIn || $userRole !== 'admin') {
    header('Location: ' . BASE_URL . 'auth/login');
    exit;
}

$db = new Database();
$conn = $db->connect();
$shopModel = new Shop();

// handle card issuance
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'issue_card') {
    $shopID = intval($_POST['shop_id']);
    $cardNumber = $_POST['card_number'];
    
    $result = $shopModel->issueATMCard($shopID, $cardNumber);
    
    if ($result['success']) {
        $_SESSION['success'] = $result['message'];
    } else {
        $_SESSION['error'] = $result['message'];
    }
    
    header('Location: ' . BASE_URL . 'profile/admin/cards');
    exit;
}

// Get all shops
$allShops = $conn->query("SELECT s.*, sp.business_name, u.full_name as seller_name, u.email
                          FROM shops s
                          JOIN seller_profiles sp ON s.sellerID = sp.sellerID
                          JOIN users u ON sp.userID = u.userID
                          WHERE s.is_active = 1
                          ORDER BY s.atm_card_number IS NULL DESC, s.shop_name ASC")->fetchAll(PDO::FETCH_ASSOC);

$stats = [
    'total_shops' => count($allShops),
    'with_cards' => count(array_filter($allShops, fn($s) => !empty($s['atm_card_number']))),
    'without_cards' => count(array_filter($allShops, fn($s) => empty($s['atm_card_number'])))
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/jpg" href="/agri_system/public/images/agri-icon.jpg">
    <title>Agri Tayo Rito</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/responsive.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/admin/dashboard.css">
</head>
<body>
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <div class="overlay" id="overlay" onclick="toggleSidebar()"></div>
    
    <nav class="top-navbar">
        <div class="logo-wrapper">
            <img src="<?php echo BASE_URL; ?>images/logo.jpg" alt="Logo">
            <span class="logo">Admin Panel</span>
        </div>
        <div class="navbar-actions">
            <button class="nav-btn" onclick="toggleSidebar()">
                <span class="menu-icon">☰</span>
            </button>
        </div>
    </nav>

    <?php 
    // Include unified sidebar component
    require_once __DIR__ . '/admin-nav.php'; 
    ?>

    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">💳 ATM Card Management</h1>
            <p class="page-subtitle">Issue and manage seller ATM/ID cards for withdrawals</p>
        </div>

        <!-- Stats -->
        <section class="stats-section">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">🏪</div>
                <div class="stat-info">
                    <h3 class="stat-value"><?php echo number_format($stats['total_shops']); ?></h3>
                    <p class="stat-label">Total Shops</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #2d5016 0%, #4a7c25 100%);">✅</div>
                <div class="stat-info">
                    <h3 class="stat-value"><?php echo number_format($stats['with_cards']); ?></h3>
                    <p class="stat-label">Cards Issued</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">⚠️</div>
                <div class="stat-info">
                    <h3 class="stat-value"><?php echo number_format($stats['without_cards']); ?></h3>
                    <p class="stat-label">Pending Card Issuance</p>
                </div>
            </div>
        </section>

        <!-- Card Info Box -->
        <section class="info-section">
            <div class="info-card">
                <h3>📌 About Seller ATM/ID Cards</h3>
                <ul>
                    <li>These cards are issued by LGU to sellers for withdrawal purposes</li>
                    <li>Sellers can present their card at LGU office for instant cash withdrawal</li>
                    <li>Card numbers are unique and linked to seller's shop account</li>
                    <li>Sellers can also check their balance online using their card number</li>
                </ul>
            </div>
        </section>

        <!-- Shops List -->
        <section class="shops-section">
            <div class="section-header">
                <h2 class="section-title">All Shops</h2>
            </div>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Shop ID</th>
                            <th>Shop Name</th>
                            <th>Seller</th>
                            <th>Email</th>
                            <th>ATM Card Status</th>
                            <th>Issued Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allShops as $shop): ?>
                        <tr>
                            <td><?php echo $shop['shopID']; ?></td>
                            <td><?php echo htmlspecialchars($shop['shop_name']); ?></td>
                            <td><?php echo htmlspecialchars($shop['seller_name']); ?></td>
                            <td><?php echo htmlspecialchars($shop['email']); ?></td>
                            <td>
                                <?php if ($shop['atm_card_number']): ?>
                                    <span class="card-issued">
                                        💳 <?php echo htmlspecialchars($shop['atm_card_number']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="card-not-issued">❌ Not Issued</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($shop['atm_card_issued_at']): ?>
                                    <?php echo date('M d, Y', strtotime($shop['atm_card_issued_at'])); ?>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!$shop['atm_card_number']): ?>
                                    <button onclick="showIssueCardModal(<?php echo $shop['shopID']; ?>, '<?php echo htmlspecialchars($shop['shop_name']); ?>')" 
                                            class="btn-issue">Issue Card</button>
                                <?php else: ?>
                                    <span class="text-muted">Card Active</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <!-- Issue Card Modal -->
    <div id="issueCardModal" class="modal">
        <div class="modal-content">
            <h3>Issue ATM Card</h3>
            <p>Issue an ATM/ID card to <strong id="shop_name_display"></strong></p>
            
            <form method="POST">
                <input type="hidden" name="shop_id" id="issue_shop_id">
                
                <div class="form-group">
                    <label>Card Number *</label>
                    <input type="text" name="card_number" placeholder="e.g., LGU-SPB-001-2024" required 
                           pattern="[A-Za-z0-9-]+" title="Only letters, numbers, and hyphens allowed">
                    <small>Format: LGU-SPB-XXX-YYYY or custom format</small>
                </div>

                <input type="hidden" name="action" value="issue_card">
                
                <div class="modal-actions">
                    <button type="button" onclick="closeModal()" class="btn-cancel">Cancel</button>
                    <button type="submit" class="btn-confirm">Issue Card</button>
                </div>
            </form>
        </div>
    </div>

    <style>
        .info-section {
            margin-bottom: 30px;
        }

        .info-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .info-card h3 {
            margin: 0 0 15px 0;
        }

        .info-card ul {
            margin: 0;
            padding-left: 20px;
        }

        .info-card li {
            margin-bottom: 8px;
            opacity: 0.95;
        }

        .card-issued {
            padding: 5px 12px;
            background: #dcfce7;
            color: #166534;
            border-radius: 6px;
            font-weight: 500;
            display: inline-block;
        }

        .card-not-issued {
            padding: 5px 12px;
            background: #fee2e2;
            color: #991b1b;
            border-radius: 6px;
            display: inline-block;
        }

        .btn-issue {
            padding: 8px 16px;
            background: #16a34a;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
        }

        .btn-issue:hover {
            background: #15803d;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 12px;
            max-width: 500px;
            width: 90%;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
        }

        .form-group small {
            display: block;
            margin-top: 5px;
            color: #666;
            font-size: 0.85rem;
        }

        .modal-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .btn-cancel {
            flex: 1;
            padding: 12px;
            background: #e5e7eb;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }

        .btn-confirm {
            flex: 1;
            padding: 12px;
            background: #16a34a;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
        }

        .text-muted {
            color: #9ca3af;
        }
    </style>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        }

        function showIssueCardModal(shopId, shopName) {
            document.getElementById('issue_shop_id').value = shopId;
            document.getElementById('shop_name_display').textContent = shopName;
            document.getElementById('issueCardModal').classList.add('active');
        }

        function closeModal() {
            document.querySelectorAll('.modal').forEach(m => m.classList.remove('active'));
        }
    </script>
</body>
</html>