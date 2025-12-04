<?php
// app/views/profile/seller/image-upload.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../config/database.php';

// Check if user is logged in and is seller
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'];
$userRole = $_SESSION['user_role'] ?? null;
$userID = $_SESSION['user_id'] ?? null;

if (!$isLoggedIn || $userRole !== 'seller') {
    header('Location: ' . BASE_URL . 'auth/login');
    exit;
}

$db = new Database();
$conn = $db->connect();

// Get seller profile ID
$stmt = $conn->prepare("SELECT sellerID FROM seller_profiles WHERE userID = ?");
$stmt->execute([$userID]);
$sellerProfile = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sellerProfile) {
    die("Seller profile not found. Please contact administrator.");
}

$sellerID = $sellerProfile['sellerID'];

// Get seller's products for image upload
$stmt = $conn->prepare("SELECT productID, product_name FROM products WHERE sellerID = ? ORDER BY product_name ASC");
$stmt->execute([$sellerID]);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get product ID from URL if specified
$selectedProductID = $_GET['product'] ?? null;

// Get images for selected product
$productImages = [];
if ($selectedProductID) {
    $stmt = $conn->prepare("SELECT * FROM product_images WHERE productID = ? ORDER BY image_order ASC");
    $stmt->execute([$selectedProductID]);
    $productImages = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Images - Agri Tayo Rito</title>
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
            
            <form method="POST" action="<?php echo BASE_URL; ?>app/controllers/ProductController.php" enctype="multipart/form-data">
                <input type="hidden" name="action" value="upload_images">
                <input type="hidden" name="productID" value="<?php echo $selectedProductID; ?>">
                
                <div class="upload-area">
                    <input type="file" name="images[]" id="imageFiles" multiple accept="image/*" required>
                    <label for="imageFiles" class="upload-label">
                        <div class="upload-icon">📁</div>
                        <p>Click to select images or drag and drop</p>
                        <small>Maximum 5 images, 5MB each (JPG, PNG, WEBP)</small>
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
                <p class="section-subtitle"><?php echo count($productImages); ?> image(s) uploaded</p>
            </div>

            <?php if (count($productImages) > 0): ?>
                <div class="images-grid">
                    <?php foreach ($productImages as $index => $image): ?>
                        <div class="image-card">
                            <img src="<?php echo BASE_URL . htmlspecialchars($image['image_path']); ?>" 
                                 alt="Product Image <?php echo $index + 1; ?>">
                            <div class="image-card-overlay">
                                <span class="image-order-badge"><?php echo $image['image_order'] == 0 ? 'Main' : '#' . ($image['image_order'] + 1); ?></span>
                                <div class="image-actions">
                                    <?php if ($image['image_order'] != 0): ?>
                                        <button class="btn-icon-small" onclick="setMainImage(<?php echo $image['imageID']; ?>)" title="Set as Main">
                                            ⭐
                                        </button>
                                    <?php endif; ?>
                                    <button class="btn-icon-small btn-danger" onclick="deleteImage(<?php echo $image['imageID']; ?>)" title="Delete">
                                        🗑️
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
            justify-content: center;
        }

        .btn-icon-small {
            background: white;
            border: none;
            padding: 8px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
        }

        .btn-icon-small.btn-danger {
            background: #ef4444;
        }

        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 20px;
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

        @media (max-width: 768px) {
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

        function setMainImage(imageID) {
            if (confirm('Set this as the main product image?')) {
                window.location.href = `<?php echo BASE_URL; ?>app/controllers/ProductImageController.php?action=set_main&imageID=${imageID}&productID=<?php echo $selectedProductID; ?>`;
            }
        }

        function deleteImage(imageID) {
            if (confirm('Are you sure you want to delete this image?')) {
                window.location.href = `<?php echo BASE_URL; ?>app/controllers/ProductImageController.php?action=delete&imageID=${imageID}&productID=<?php echo $selectedProductID; ?>`;
            }
        }

        // Image preview functionality
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