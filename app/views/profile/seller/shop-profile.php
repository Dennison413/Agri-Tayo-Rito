<?php
// app/views/profile/seller/shop-profile.php - Seller's Shop Settings Page
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../config/database.php';
require_once __DIR__ . '/../../../models/Shop.php';

// Check authentication
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'];
$userRole = $_SESSION['user_role'] ?? null;
$userID = $_SESSION['user_id'] ?? null;

if (!$isLoggedIn || $userRole !== 'seller') {
    header('Location: ' . BASE_URL . 'auth/login');
    exit;
}

$db = new Database();
$conn = $db->connect();

// Get seller profile
$stmt = $conn->prepare("SELECT sellerID FROM seller_profiles WHERE userID = ?");
$stmt->execute([$userID]);
$sellerProfile = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sellerProfile) {
    die("Seller profile not found");
}

$sellerID = $sellerProfile['sellerID'];

// Get shop details
$shopModel = new Shop();
$shop = $shopModel->getShopBySeller($sellerID);

if (!$shop) {
    die("Shop not found");
}

// Get shop statistics
$shopStats = $shopModel->getShopStats($shop['shopID']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop Profile - Agri Tayo Rito</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/responsive.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/seller/dashboard.css">
</head>
<body>
    <?php include 'seller-nav.php'; ?>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <!-- Main Content -->
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">🏬 Shop Profile</h1>
            <p class="page-subtitle">Manage your shop information</p>
        </div>

        <!-- Shop Statistics -->
        <section class="stats-section">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">⭐</div>
                <div class="stat-info">
                    <h3 class="stat-value"><?php echo number_format($shop['rating'], 1); ?></h3>
                    <p class="stat-label">Shop Rating</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">📦</div>
                <div class="stat-info">
                    <h3 class="stat-value"><?php echo number_format($shopStats['products']['total_products']); ?></h3>
                    <p class="stat-label">Total Products</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #2d5016 0%, #4a7c25 100%);">🛒</div>
                <div class="stat-info">
                    <h3 class="stat-value"><?php echo number_format($shop['total_orders']); ?></h3>
                    <p class="stat-label">Total Orders</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">📝</div>
                <div class="stat-info">
                    <h3 class="stat-value"><?php echo number_format($shop['total_reviews']); ?></h3>
                    <p class="stat-label">Total Reviews</p>
                </div>
            </div>
        </section>

        <!-- Shop Information Form -->
        <section class="content-section">
            <div class="section-header">
                <h2 class="section-title">Shop Information</h2>
            </div>

            <form method="POST" action="<?php echo BASE_URL; ?>app/controllers/ShopController.php" class="shop-form">
                <input type="hidden" name="action" value="update_shop">
                <input type="hidden" name="shopID" value="<?php echo $shop['shopID']; ?>">

                <div class="form-row">
                    <div class="form-group">
                        <label for="shop_name">Shop Name *</label>
                        <input type="text" 
                               id="shop_name" 
                               name="shop_name" 
                               value="<?php echo htmlspecialchars($shop['shop_name']); ?>" 
                               required>
                    </div>

                    <div class="form-group">
                        <label for="shop_slug">Shop URL Slug</label>
                        <input type="text" 
                               id="shop_slug" 
                               value="<?php echo htmlspecialchars($shop['shop_slug']); ?>" 
                               readonly 
                               style="background: #f5f5f5;">
                        <small>Generated automatically from shop name</small>
                    </div>
                </div>

                <div class="form-group">
                    <label for="shop_description">Shop Description</label>
                    <textarea id="shop_description" 
                              name="shop_description" 
                              rows="4"><?php echo htmlspecialchars($shop['shop_description'] ?? ''); ?></textarea>
                    <small>Tell customers about your farm/shop (max 500 characters)</small>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="farm_location">Farm/Shop Location</label>
                        <input type="text" 
                               id="farm_location" 
                               name="farm_location" 
                               value="<?php echo htmlspecialchars($shop['farm_location'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="contact_number">Contact Number</label>
                        <input type="tel" 
                               id="contact_number" 
                               name="contact_number" 
                               value="<?php echo htmlspecialchars($shop['contact_number'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="business_hours">Business Hours</label>
                    <input type="text" 
                           id="business_hours" 
                           name="business_hours" 
                           value="<?php echo htmlspecialchars($shop['business_hours'] ?? ''); ?>"
                           placeholder="e.g., Mon-Sat: 8:00 AM - 5:00 PM">
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary">
                        💾 Save Changes
                    </button>
                    <button type="button" class="btn-secondary" onclick="window.history.back()">
                        Cancel
                    </button>
                </div>
            </form>
        </section>

        <!-- Shop Status Section -->
        <section class="content-section">
            <div class="section-header">
                <h2 class="section-title">Shop Status</h2>
            </div>

            <div class="status-info-card">
                <div class="status-row">
                    <span>Shop Status:</span>
                    <span class="status-badge <?php echo $shop['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                        <?php echo $shop['is_active'] ? '✅ Active' : '🚫 Inactive'; ?>
                    </span>
                </div>

                <div class="status-row">
                    <span>Verification:</span>
                    <span class="status-badge <?php echo $shop['is_verified'] ? 'status-active' : 'status-pending'; ?>">
                        <?php echo $shop['is_verified'] ? '✅ Verified' : '⏳ Pending Verification'; ?>
                    </span>
                </div>

                <div class="status-row">
                    <span>Member Since:</span>
                    <strong><?php echo date('F Y', strtotime($shop['created_at'])); ?></strong>
                </div>

                <?php if (!empty($shop['atm_card_number'])): ?>
                <div class="status-row">
                    <span>ATM Card:</span>
                    <strong><?php echo htmlspecialchars($shop['atm_card_number']); ?></strong>
                </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <style>
        .shop-form {
            background: white;
            padding: 30px;
            border-radius: 12px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 0.95rem;
        }

        .form-group small {
            display: block;
            margin-top: 5px;
            color: #666;
            font-size: 0.85rem;
        }

        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .btn-primary, .btn-secondary {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            font-size: 1rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-secondary {
            background: #e5e7eb;
            color: #333;
        }

        .status-info-card {
            background: white;
            padding: 30px;
            border-radius: 12px;
        }

        .status-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .status-row:last-child {
            border-bottom: none;
        }
    </style>
</body>
</html>