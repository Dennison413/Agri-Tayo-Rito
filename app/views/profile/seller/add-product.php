<?php
// app/views/profile/seller/add-product.php
// Complete product creation form with inline image upload
// FIXED: Properly handle seller profile and shop lookup

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../config/database.php';
require_once __DIR__ . '/../../../models/Category.php';

// Check authentication
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'];
$userRole = $_SESSION['user_role'] ?? null;
$userID = $_SESSION['user_id'] ?? null;

if (!$isLoggedIn || $userRole !== 'seller') {
    header('Location: ' . BASE_URL . 'auth/login');
    exit;
}

// Get seller profile
$db = new Database();
$conn = $db->connect();

// First, get the seller profile
$stmt = $conn->prepare("SELECT sellerID FROM seller_profiles WHERE userID = ?");
$stmt->execute([$userID]);
$sellerProfile = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sellerProfile) {
    die("Seller profile not found. Please contact administrator.");
}

$sellerID = $sellerProfile['sellerID'];

// Then, get the shop for this seller
$stmt = $conn->prepare("SELECT shopID, shop_name, is_active FROM shops WHERE sellerID = ?");
$stmt->execute([$sellerID]);
$shop = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$shop) {
    die("No shop found for your account. Please contact administrator to set up your shop.");
}

if (!$shop['is_active']) {
    die("Your shop is currently inactive. Please contact administrator.");
}

$seller = [
    'sellerID' => $sellerID,
    'shopID' => $shop['shopID']
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_product') {
    
    // Validate inputs
    $errors = [];
    
    if (empty($_POST['product_name'])) $errors[] = 'Product name is required';
    if (empty($_POST['categoryID'])) $errors[] = 'Category is required';
    if (empty($_POST['price']) || $_POST['price'] <= 0) $errors[] = 'Valid price is required';
    if (empty($_POST['stock_quantity']) || $_POST['stock_quantity'] < 0) $errors[] = 'Valid stock quantity is required';
    if (empty($_POST['unit'])) $errors[] = 'Unit is required';
    
    if (empty($errors)) {
        require_once __DIR__ . '/../../../models/Product.php';
        $productModel = new Product();
        
        $productData = [
            'categoryID' => $_POST['categoryID'],
            'product_name' => trim($_POST['product_name']),
            'description' => trim($_POST['description'] ?? ''),
            'price' => floatval($_POST['price']),
            'stock_quantity' => intval($_POST['stock_quantity']),
            'low_stock_threshold' => intval($_POST['low_stock_threshold'] ?? 5),
            'unit' => trim($_POST['unit']),
            'is_available' => isset($_POST['is_available']) ? 1 : 0
        ];
        
        $result = $productModel->createProduct($seller['sellerID'], $productData);
        
        if ($result['success']) {
            $_SESSION['success'] = 'Product created successfully! Now upload images.';
            header('Location: ' . BASE_URL . 'profile/seller/image-upload?product=' . $result['productID']);
            exit;
        } else {
            $_SESSION['error'] = $result['message'];
        }
    } else {
        $_SESSION['error'] = implode('<br>', $errors);
    }
}

// Get categories
$categoryModel = new Category();
$categories = $categoryModel->getAllCategories();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Product - Agri Tayo Rito</title>
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

    <main class="main-content">
        <div class="page-header">
            <button class="btn-back" onclick="window.history.back()">
                ← Back to Products
            </button>
            <h1 class="page-title">➕ Add New Product</h1>
            <p class="page-subtitle">Fill in the details to add your product</p>
        </div>

        <section class="content-section">
            <form method="POST" class="product-form" id="addProductForm">
                <input type="hidden" name="action" value="create_product">
                
                <div class="form-grid">
                    <!-- Left Column -->
                    <div class="form-column">
                        <h3 class="form-section-title">📦 Basic Information</h3>
                        
                        <div class="form-group">
                            <label for="product_name">Product Name *</label>
                            <input type="text" id="product_name" name="product_name" 
                                   placeholder="e.g., Fresh Pechay" required>
                        </div>

                        <div class="form-group">
                            <label for="categoryID">Category *</label>
                            <select id="categoryID" name="categoryID" required>
                                <option value="">-- Select Category --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['categoryID']; ?>">
                                        <?php echo htmlspecialchars($cat['category']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="5" 
                                      placeholder="Describe your product..."></textarea>
                            <small>Provide details about quality, origin, or special features</small>
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div class="form-column">
                        <h3 class="form-section-title">💰 Pricing & Stock</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="price">Price (₱) *</label>
                                <input type="number" id="price" name="price" step="0.01" min="0" required>
                            </div>

                            <div class="form-group">
                                <label for="unit">Unit *</label>
                                <select id="unit" name="unit" required>
                                    <option value="">-- Select Unit --</option>
                                    <option value="kg">Kilogram (kg)</option>
                                    <option value="bundle">Bundle</option>
                                    <option value="pack">Pack</option>
                                    <option value="pcs">Pieces (pcs)</option>
                                    <option value="head">Head</option>
                                    <option value="sack">Sack</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="stock_quantity">Initial Stock *</label>
                                <input type="number" id="stock_quantity" name="stock_quantity" min="0" required>
                            </div>

                            <div class="form-group">
                                <label for="low_stock_threshold">Low Stock Alert</label>
                                <input type="number" id="low_stock_threshold" name="low_stock_threshold" 
                                       value="5" min="1">
                                <small>Alert when stock reaches this level</small>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="is_available" checked>
                                <span>Make product available immediately</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-secondary" onclick="window.history.back()">
                        Cancel
                    </button>
                    <button type="submit" class="btn-primary">
                        ✅ Create Product
                    </button>
                </div>
            </form>
        </section>

        <section class="content-section info-box">
            <h3>📸 Next Step: Add Images</h3>
            <p>After creating your product, you'll be redirected to upload product images (up to 5 images).</p>
            <ul>
                <li>✅ First image becomes the main product image</li>
                <li>✅ Accepted formats: JPG, PNG, WEBP</li>
                <li>✅ Maximum size: 5MB per image</li>
            </ul>
        </section>
    </main>

    <style>
        .product-form {
            background: white;
            padding: 30px;
            border-radius: 12px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        @media (max-width: 968px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }

        .form-section-title {
            margin: 0 0 20px 0;
            color: #2d5016;
            font-size: 1.2rem;
            padding-bottom: 10px;
            border-bottom: 2px solid #e5e7eb;
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
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
        }

        .form-group small {
            display: block;
            margin-top: 5px;
            color: #666;
            font-size: 0.85rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            padding: 12px;
            background: #f9fafb;
            border-radius: 8px;
        }

        .checkbox-label input[type="checkbox"] {
            width: auto;
            cursor: pointer;
        }

        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
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
            background: linear-gradient(135deg, #2d5016 0%, #4a7c25 100%);
            color: white;
        }

        .btn-secondary {
            background: #e5e7eb;
            color: #333;
        }

        .btn-back {
            background: white;
            border: 1px solid #ddd;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }

        .info-box {
            background: linear-gradient(135deg, #ecfccb 0%, #d9f99d 100%);
            border-left: 4px solid #4a7c25;
        }

        .info-box h3 {
            margin: 0 0 10px 0;
            color: #2d5016;
        }

        .info-box ul {
            margin: 10px 0;
            padding-left: 20px;
        }

        .info-box li {
            margin: 5px 0;
            color: #4a7c25;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 500;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }
    </style>

    <script>
        // Form validation
        document.getElementById('addProductForm').addEventListener('submit', function(e) {
            const price = parseFloat(document.getElementById('price').value);
            const stock = parseInt(document.getElementById('stock_quantity').value);
            
            if (price <= 0) {
                e.preventDefault();
                alert('Price must be greater than 0');
                return false;
            }
            
            if (stock < 0) {
                e.preventDefault();
                alert('Stock quantity cannot be negative');
                return false;
            }
        });
    </script>
</body>
</html>