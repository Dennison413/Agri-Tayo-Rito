<?php
// app/views/profile/seller/edit-product.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../config/database.php';
require_once __DIR__ . '/../../../models/Product.php';
require_once __DIR__ . '/../../../models/Category.php';

$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'];
$userRole = $_SESSION['user_role'] ?? null;
$userID = $_SESSION['user_id'] ?? null;

if (!$isLoggedIn || $userRole !== 'seller') {
    header('Location: ' . BASE_URL . 'auth/login');
    exit;
}

// Get product ID
$productID = $_GET['id'] ?? null;
if (!$productID) {
    $_SESSION['error'] = 'Product ID not specified';
    header('Location: ' . BASE_URL . 'profile/seller/products');
    exit;
}

// Get seller profile
$db = new Database();
$conn = $db->connect();
$stmt = $conn->prepare("SELECT sellerID FROM seller_profiles WHERE userID = ?");
$stmt->execute([$userID]);
$seller = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$seller) {
    die("Seller profile not found");
}

// Get product details and verify ownership
$productModel = new Product();
$product = $productModel->getProductById($productID);

if (!$product || $product['sellerID'] != $seller['sellerID']) {
    $_SESSION['error'] = 'Product not found or unauthorized';
    header('Location: ' . BASE_URL . 'profile/seller/products');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_product') {
    
    $errors = [];
    
    if (empty($_POST['product_name'])) $errors[] = 'Product name is required';
    if (empty($_POST['categoryID'])) $errors[] = 'Category is required';
    if (empty($_POST['price']) || $_POST['price'] <= 0) $errors[] = 'Valid price is required';
    if (empty($_POST['stock_quantity']) || $_POST['stock_quantity'] < 0) $errors[] = 'Valid stock quantity is required';
    if (empty($_POST['unit'])) $errors[] = 'Unit is required';
    
    if (empty($errors)) {
        $updateData = [
            'product_name' => trim($_POST['product_name']),
            'categoryID' => $_POST['categoryID'],
            'description' => trim($_POST['description'] ?? ''),
            'price' => floatval($_POST['price']),
            'stock_quantity' => intval($_POST['stock_quantity']),
            'low_stock_threshold' => intval($_POST['low_stock_threshold'] ?? 5),
            'unit' => trim($_POST['unit']),
            'is_available' => isset($_POST['is_available']) ? 1 : 0
        ];
        
        $result = $productModel->updateProduct($productID, $seller['sellerID'], $updateData);
        
        if ($result['success']) {
            $_SESSION['success'] = 'Product updated successfully!';
            header('Location: ' . BASE_URL . 'profile/seller/products');
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
    <link rel="icon" type="image/jpg" href="/agri_system/public/images/agri-icon.jpg">
    <title>Agri Tayo Rito</title>
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
            <h1 class="page-title">✏️ Edit Product</h1>
            <p class="page-subtitle">Update product information</p>
        </div>

        <section class="content-section">
            <form method="POST" class="product-form">
                <input type="hidden" name="action" value="update_product">
                
                <div class="form-grid">
                    <!-- Left Column -->
                    <div class="form-column">
                        <h3 class="form-section-title">📦 Basic Information</h3>
                        
                        <div class="form-group">
                            <label for="product_name">Product Name *</label>
                            <input type="text" id="product_name" name="product_name" 
                                   value="<?php echo htmlspecialchars($product['product_name']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="categoryID">Category *</label>
                            <select id="categoryID" name="categoryID" required>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['categoryID']; ?>"
                                            <?php echo ($cat['categoryID'] == $product['categoryID']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['category']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="5"><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div class="form-column">
                        <h3 class="form-section-title">💰 Pricing & Stock</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="price">Price (₱) *</label>
                                <input type="number" id="price" name="price" step="0.01" min="0" 
                                       value="<?php echo $product['price']; ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="unit">Unit *</label>
                                <select id="unit" name="unit" required>
                                    <?php 
                                    $units = ['kg', 'bundle', 'pack', 'pcs', 'head', 'sack'];
                                    foreach ($units as $u): 
                                    ?>
                                        <option value="<?php echo $u; ?>" 
                                                <?php echo ($u == $product['unit']) ? 'selected' : ''; ?>>
                                            <?php echo ucfirst($u); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="stock_quantity">Stock Quantity *</label>
                                <input type="number" id="stock_quantity" name="stock_quantity" min="0" 
                                       value="<?php echo $product['stock_quantity']; ?>" required>
                                <small>Current: <?php echo $product['stock_quantity']; ?> | Reserved: <?php echo $product['reserved_quantity']; ?></small>
                            </div>

                            <div class="form-group">
                                <label for="low_stock_threshold">Low Stock Alert</label>
                                <input type="number" id="low_stock_threshold" name="low_stock_threshold" min="1"
                                       value="<?php echo $product['low_stock_threshold']; ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="is_available" 
                                       <?php echo $product['is_available'] ? 'checked' : ''; ?>>
                                <span>Product is available for sale</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-secondary" onclick="window.history.back()">
                        Cancel
                    </button>
                    <a href="<?php echo BASE_URL; ?>profile/seller/image-upload?product=<?php echo $productID; ?>">
                        <button type="button" class="btn-secondary">
                            🖼️ Manage Images
                        </button>
                    </a>
                    <button type="submit" class="btn-primary">
                        💾 Save Changes
                    </button>
                </div>
            </form>
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
    </style>
</body>
</html>