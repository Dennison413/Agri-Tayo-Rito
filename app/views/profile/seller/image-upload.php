<?php
// app/views/profile/seller/image-upload.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../config/database.php';
require_once __DIR__ . '/../../../models/Product.php';

$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'];
$userRole = $_SESSION['user_role'] ?? null;
$userID = $_SESSION['user_id'] ?? null;

if (!$isLoggedIn || $userRole !== 'seller') {
    header('Location: ' . BASE_URL . 'auth/login');
    exit;
}

$db = new Database();
$conn = $db->connect();

$stmt = $conn->prepare("SELECT sellerID FROM seller_profiles WHERE userID = ?");
$stmt->execute([$userID]);
$sellerProfile = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sellerProfile) {
    die("Seller profile not found");
}

$sellerID = $sellerProfile['sellerID'];

// Get seller's products
$stmt = $conn->prepare("SELECT productID, product_name FROM products WHERE sellerID = ? ORDER BY product_name ASC");
$stmt->execute([$sellerID]);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get product ID from URL
$selectedProductID = $_GET['product'] ?? null;
$productImages = [];
if ($selectedProductID) {
    $stmt = $conn->prepare("SELECT * FROM product_images WHERE productID = ? ORDER BY is_main DESC, image_order ASC");
    $stmt->execute([$selectedProductID]);
    $productImages = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// handle set main image
if (isset($_GET['action']) && $_GET['action'] === 'set_main' && isset($_GET['imageID'])) {
    $imageID = (int)$_GET['imageID'];
    $productID = (int)$_GET['productID'];
    
    $productModel = new Product();
    $result = $productModel->updateMainImage($productID, $sellerID, $imageID);
    
    if ($result['success']) {
        $_SESSION['success'] = 'Main image updated successfully';
    } else {
        $_SESSION['error'] = $result['message'];
    }
    
    header('Location: ' . BASE_URL . 'profile/seller/image-upload?product=' . $productID);
    exit;
}

// handle delete image
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['imageID'])) {
    $imageID = (int)$_GET['imageID'];
    $productID = (int)$_GET['productID'];
    
    $productModel = new Product();
    $result = $productModel->deleteProductImage($imageID, $sellerID);
    
    if ($result['success']) {
        $_SESSION['success'] = 'Image deleted successfully';
    } else {
        $_SESSION['error'] = $result['message'];
    }
    
    header('Location: ' . BASE_URL . 'profile/seller/image-upload?product=' . $productID);
    exit;
}

// handle image upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_images') {
    $productModel = new Product();
    
    $productID = $_POST['productID'] ?? null;
    
    if (!$productID) {
        $_SESSION['error'] = 'Product ID required';
        header('Location: ' . BASE_URL . 'profile/seller/image-upload');
        exit;
    }
    
    $stmt = $conn->prepare("SELECT productID FROM products WHERE productID = ? AND sellerID = ?");
    $stmt->execute([$productID, $sellerID]);
    if (!$stmt->fetch()) {
        $_SESSION['error'] = 'Unauthorized';
        header('Location: ' . BASE_URL . 'profile/seller/image-upload');
        exit;
    }

    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM product_images WHERE productID = ?");
    $stmt->execute([$productID]);
    $currentCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if (!isset($_FILES['images']) || empty($_FILES['images']['name'][0])) {
        $_SESSION['error'] = 'No images uploaded';
        header('Location: ' . BASE_URL . 'profile/seller/image-upload?product=' . $productID);
        exit;
    }
    
    $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/agri_system/public/uploads/products/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $maxFileSize = 5242880; // 5MB
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    $maxImages = 5;
    
    $files = $_FILES['images'];
    $fileCount = count($files['name']);
    
    if (($currentCount + $fileCount) > $maxImages) {
        $_SESSION['error'] = "Cannot upload {$fileCount} images. Maximum {$maxImages} images allowed (currently have {$currentCount})";
        header('Location: ' . BASE_URL . 'profile/seller/image-upload?product=' . $productID);
        exit;
    }
    
    $uploadedCount = 0;
    $errors = [];
    
    for ($i = 0; $i < $fileCount; $i++) {
        $fileName = $files['name'][$i];
        $fileTmp = $files['tmp_name'][$i];
        $fileSize = $files['size'][$i];
        $fileError = $files['error'][$i];
        $fileType = $files['type'][$i];
        
        if ($fileError !== UPLOAD_ERR_OK) {
            $errors[] = "{$fileName}: Upload error";
            continue;
        }
        
        if ($fileSize > $maxFileSize) {
            $errors[] = "{$fileName}: File too large (max 5MB)";
            continue;
        }
        
        if (!in_array($fileType, $allowedTypes)) {
            $errors[] = "{$fileName}: Invalid file type";
            continue;
        }
        
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $newFileName = 'product_' . $productID . '_' . uniqid() . '.' . $extension;
        $targetPath = $uploadDir . $newFileName;
        
        if (move_uploaded_file($fileTmp, $targetPath)) {
            $relativePath = '/uploads/products/' . $newFileName;
            $isMain = ($currentCount == 0 && $i == 0) ? 1 : 0;
            
            $result = $productModel->addProductImage($productID, $sellerID, $relativePath, $isMain, null);
            
            if ($result['success']) {
                $uploadedCount++;
                $currentCount++;
            } else {
                unlink($targetPath);
                $errors[] = "{$fileName}: " . $result['message'];
            }
        } else {
            $errors[] = "{$fileName}: Failed to move file";
        }
    }
    
    if ($uploadedCount > 0) {
        $_SESSION['success'] = "{$uploadedCount} image(s) uploaded successfully";
    }
    if (!empty($errors)) {
        $_SESSION['error'] = implode('<br>', $errors);
    }
    
    header('Location: ' . BASE_URL . 'profile/seller/image-upload?product=' . $productID);
    exit;
}
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
            <h1 class="page-title">🖼️ Product Images</h1>
            <p class="page-subtitle">Manage your product images</p>
        </div>

        <!-- Product Selection -->
        <section class="content-section">
            <div class="section-header">
                <h2 class="section-title">Select Product</h2>
            </div>
            
            <div class="form-group">
                <select id="productSelect" class="form-select" onchange="selectProduct(this.value)">
                    <option value="">-- Select a product --</option>
                    <?php foreach ($products as $product): ?>
                        <option value="<?php echo $product['productID']; ?>" 
                                <?php echo ($selectedProductID == $product['productID']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($product['product_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </section>

        <?php if ($selectedProductID): ?>
        <!-- Upload Section -->
        <section class="content-section">
            <div class="section-header">
                <h2 class="section-title">Upload New Images</h2>
            </div>
            
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="upload_images">
                <input type="hidden" name="productID" value="<?php echo $selectedProductID; ?>">
                
                <div class="upload-area">
                    <input type="file" name="images[]" id="imageFiles" multiple accept="image/*" required>
                    <label for="imageFiles" class="upload-label">
                        <div class="upload-icon">📁</div>
                        <p>Click to select images or drag and drop</p>
                        <small>Maximum 5 images total, 5MB each (JPG, PNG, WEBP)</small>
                    </label>
                </div>

                <div id="imagePreview" class="image-preview-grid"></div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary">
                        ⬆️ Upload Images
                    </button>
                </div>
            </form>
        </section>

        <!-- Current Images -->
        <section class="content-section">
            <div class="section-header">
                <h2 class="section-title">Current Images</h2>
                <p class="section-subtitle"><?php echo count($productImages); ?> of 5 image(s) uploaded</p>
            </div>

            <?php if (count($productImages) > 0): ?>
                <div class="images-grid">
                    <?php foreach ($productImages as $index => $image): ?>
                        <div class="image-card">
                            <img src="<?php echo BASE_URL . htmlspecialchars($image['image_path']); ?>" 
                                 alt="Product Image <?php echo $index + 1; ?>"
                                 onerror="this.src='<?php echo BASE_URL; ?>images/placeholder.jpg'">
                            <div class="image-card-overlay">
                                <span class="image-order-badge"><?php echo $image['is_main'] ? '⭐ Main' : 'Image #' . ($index + 1); ?></span>
                                <div class="image-actions">
                                    <?php if (!$image['is_main']): ?>
                                        <button class="btn-icon-small" onclick="setMainImage(<?php echo $image['imageID']; ?>, <?php echo $selectedProductID; ?>)" title="Set as Main">
                                            ⭐ Set Main
                                        </button>
                                    <?php endif; ?>
                                    <button class="btn-icon-small btn-danger" onclick="deleteImage(<?php echo $image['imageID']; ?>, <?php echo $selectedProductID; ?>)" title="Delete">
                                        🗑️ Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <p>📷 No images uploaded yet</p>
                    <p class="text-muted">Upload images to showcase your product</p>
                </div>
            <?php endif; ?>
        </section>
        <?php else: ?>
        <section class="content-section">
            <div class="empty-state">
                <p>📦 Please select a product to manage images</p>
            </div>
        </section>
        <?php endif; ?>
    </main>

    <style>
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

        .form-select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
        }

        .upload-area {
            position: relative;
            border: 2px dashed #ddd;
            border-radius: 12px;
            padding: 40px;
            text-align: center;
            transition: all 0.3s;
            background: #f9fafb;
            margin-bottom: 20px;
        }

        .upload-area:hover {
            border-color: #4a7c25;
            background: #f0f9ff;
        }

        #imageFiles {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            opacity: 0;
            cursor: pointer;
        }

        .upload-label {
            cursor: pointer;
        }

        .upload-icon {
            font-size: 3rem;
            margin-bottom: 10px;
        }

        .image-preview-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .images-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
        }

        .image-card {
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            aspect-ratio: 1;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .image-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .image-card-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 10px;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .image-card:hover .image-card-overlay {
            opacity: 1;
        }

        .image-order-badge {
            background: #4a7c25;
            color: white;
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            align-self: flex-start;
        }

        .image-actions {
            display: flex;
            gap: 8px;
            flex-direction: column;
            justify-content: center;
        }

        .btn-icon-small {
            background: white;
            border: none;
            padding: 8px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .btn-icon-small.btn-danger {
            background: #ef4444;
            color: white;
        }

        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .btn-primary {
            padding: 12px 30px;
            background: linear-gradient(135deg, #2d5016 0%, #4a7c25 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
        }

        .section-subtitle {
            color: #666;
            font-size: 0.9rem;
            margin: 5px 0 0 0;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-state p {
            font-size: 1.2rem;
            color: #666;
            margin-bottom: 10px;
        }

        .text-muted {
            color: #999;
            font-size: 0.9rem;
        }

        @media (max-width: 480px) {
            .images-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }
        }
    </style>

    <script>
        function selectProduct(productID) {
            if (productID) {
                window.location.href = `<?php echo BASE_URL; ?>profile/seller/image-upload?product=${productID}`;
            } else {
                window.location.href = `<?php echo BASE_URL; ?>profile/seller/image-upload`;
            }
        }

        function setMainImage(imageID, productID) {
            if (confirm('Set this as the main product image?')) {
                window.location.href = `<?php echo BASE_URL; ?>profile/seller/image-upload?action=set_main&imageID=${imageID}&productID=${productID}`;
            }
        }

        function deleteImage(imageID, productID) {
            if (confirm('Are you sure you want to delete this image?')) {
                window.location.href = `<?php echo BASE_URL; ?>profile/seller/image-upload?action=delete&imageID=${imageID}&productID=${productID}`;
            }
        }

        // Image preview
        document.getElementById('imageFiles').addEventListener('change', function(e) {
            const previewContainer = document.getElementById('imagePreview');
            previewContainer.innerHTML = '';
            
            const files = Array.from(e.target.files);
            
            if (files.length > 5) {
                alert('Maximum 5 images allowed');
                e.target.value = '';
                return;
            }
            
            files.forEach(file => {
                if (file.size > 5 * 1024 * 1024) {
                    alert(`${file.name} is larger than 5MB`);
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    const div = document.createElement('div');
                    div.className = 'image-card';
                    div.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
                    previewContainer.appendChild(div);
                };
                reader.readAsDataURL(file);
            });
        });
    </script>
</body>
</html>