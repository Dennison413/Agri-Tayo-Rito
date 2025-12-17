<?php
// app/views/marketplace/product.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../models/Product.php';
require_once __DIR__ . '/../../models/Cart.php';
require_once __DIR__ . '/../../helpers/csrf.php';

$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'];
$userRole = $_SESSION['user_role'] ?? 'guest';
$userId = $_SESSION['user_id'] ?? null;

$productID = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($productID <= 0) {
    header('Location: ' . BASE_URL . 'marketplace');
    exit;
}

$productModel = new Product();

try {
    $product = $productModel->getProductById($productID);
    
    if (!$product) {
        $_SESSION['error'] = 'Product not found';
        header('Location: ' . BASE_URL . 'marketplace');
        exit;
    }
    
    if (!$product['is_available']) {
        $_SESSION['error'] = 'This product is no longer available';
        header('Location: ' . BASE_URL . 'marketplace');
        exit;
    }
    
} catch (Exception $e) {
    error_log("Product detail error: " . $e->getMessage());
    $_SESSION['error'] = 'Error loading product';
    header('Location: ' . BASE_URL . 'marketplace');
    exit;
}

$productName = htmlspecialchars($product['product_name']);
$productDesc = htmlspecialchars($product['description'] ?? 'Fresh from local farms');
$productPrice = (float)$product['price'];
$productUnit = htmlspecialchars($product['unit']);
$categoryName = htmlspecialchars($product['category'] ?? 'Products');

$stockQty = (int)($product['stock_quantity'] ?? 0);
$reservedQty = (int)($product['reserved_quantity'] ?? 0);
$availableStock = $stockQty - $reservedQty;
$lowStockThreshold = (int)($product['low_stock_threshold'] ?? 5);

$shopID = (int)$product['shopID'];
$shopName = htmlspecialchars($product['shop_name'] ?? 'Local Farm');
$shopSlug = $product['shop_slug'] ?? '';
$businessName = htmlspecialchars($product['business_name'] ?? $shopName);
$sellerRating = number_format((float)($product['seller_rating'] ?? 0), 1);
$totalProducts = (int)($product['total_products'] ?? 0);

$mainImage = !empty($product['main_image']) 
    ? BASE_URL . ltrim($product['main_image'], '/') 
    : BASE_URL . 'images/placeholder.jpg';

$productImages = $product['images'] ?? [];
if (empty($productImages)) {
    $productImages[] = ['image_path' => $mainImage, 'is_main' => 1];
}

$reviews = $product['reviews'] ?? [];
$totalReviews = count($reviews);
$avgRating = 0;
if ($totalReviews > 0) {
    $totalStars = array_sum(array_column($reviews, 'rating'));
    $avgRating = number_format($totalStars / $totalReviews, 1);
}

$cartCount = 0;
if ($isLoggedIn && $userRole === 'buyer' && $userId) {
    try {
        $cartModel = new Cart();
        $cartCount = $cartModel->getCartCount($userId);
    } catch (Exception $e) {
        error_log("Cart count error: " . $e->getMessage());
    }
}

$csrfToken = CSRF::generateToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/jpg" href="/agri_system/public/images/agri-icon.jpg">
    <title>Agri Tayo Rito</title>
    
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/responsive.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/marketplace/marketplace.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/marketplace/product.css">
    
    <meta name="csrf-token" content="<?php echo $csrfToken; ?>">
    
    <style>
        .product-detail-wrapper {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .product-main {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .product-gallery {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .main-image-container {
            position: relative;
            width: 100%;
            height: 450px;
            border-radius: 12px;
            overflow: hidden;
            background: #f5f5f5;
        }

        .main-image-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .wishlist-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: white;
            border: none;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            font-size: 1.5rem;
            cursor: pointer;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            transition: all 0.3s;
            z-index: 10;
        }

        .wishlist-badge:hover {
            transform: scale(1.1);
        }

        .wishlist-badge.active {
            color: #ff4444;
        }

        .stock-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            padding: 8px 15px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.85rem;
            z-index: 10;
        }

        .stock-badge.low-stock {
            background: #ffc107;
            color: #333;
        }

        .stock-badge.out-of-stock {
            background: #dc3545;
            color: white;
        }

        .thumbnail-gallery {
            display: flex;
            gap: 10px;
            overflow-x: auto;
        }

        .thumbnail {
            width: 80px;
            height: 80px;
            border-radius: 8px;
            cursor: pointer;
            border: 3px solid transparent;
            transition: all 0.3s;
            flex-shrink: 0;
        }

        .thumbnail:hover,
        .thumbnail.active {
            border-color: #2d5016;
        }

        .product-info {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .product-title {
            font-size: 1.8rem;
            color: #2d5016;
            font-weight: 700;
            margin: 0;
        }

        .product-rating {
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e0e0e0;
        }

        .stars {
            color: #ffc107;
            font-size: 1.1rem;
        }

        .rating-text {
            color: #666;
            font-size: 0.9rem;
        }

        .product-price-box {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            padding: 20px;
            border-radius: 12px;
        }

        .price-label {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 8px;
        }

        .product-price {
            font-size: 2.5rem;
            color: #2d5016;
            font-weight: 700;
        }

        .price-unit {
            font-size: 1.2rem;
            color: #666;
            margin-left: 5px;
        }

        .quantity-selector {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .quantity-label {
            font-weight: 600;
            color: #333;
        }

        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .qty-buttons {
            display: flex;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            overflow: hidden;
        }

        .qty-btn {
            width: 45px;
            height: 45px;
            border: none;
            background: white;
            font-size: 1.3rem;
            font-weight: 600;
            cursor: pointer;
            color: #2d5016;
            transition: all 0.3s;
        }

        .qty-btn:hover {
            background: #f5f5f5;
        }

        .qty-display {
            width: 60px;
            text-align: center;
            font-size: 1.1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            border-left: 2px solid #e0e0e0;
            border-right: 2px solid #e0e0e0;
        }

        .stock-info {
            color: #666;
            font-size: 0.9rem;
        }

        .stock-available {
            color: #28a745;
            font-weight: 600;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .btn-add-cart,
        .btn-buy-now {
            flex: 1;
            padding: 15px;
            border-radius: 25px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: none;
        }

        .btn-add-cart {
            background: white;
            border: 2px solid #2d5016;
            color: #2d5016;
        }

        .btn-add-cart:hover {
            background: #2d5016;
            color: white;
            transform: translateY(-2px);
        }

        .btn-buy-now {
            background: linear-gradient(135deg, #2d5016, #4a7c25);
            color: white;
            box-shadow: 0 4px 15px rgba(45,80,22,0.3);
        }

        .btn-buy-now:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(45,80,22,0.4);
        }

        .btn-add-cart:disabled,
        .btn-buy-now:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        .shop-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .shop-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #2d5016, #4a7c25);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
            flex-shrink: 0;
        }

        .shop-info {
            flex: 1;
        }

        .shop-name {
            font-size: 1.3rem;
            font-weight: 700;
            color: #2d5016;
            margin-bottom: 8px;
        }

        .shop-stats {
            display: flex;
            gap: 20px;
            font-size: 0.9rem;
            color: #666;
        }

        .shop-stat {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .btn-visit-shop {
            padding: 12px 30px;
            background: white;
            border: 2px solid #2d5016;
            color: #2d5016;
            border-radius: 25px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            white-space: nowrap;
        }

        .btn-visit-shop:hover {
            background: #2d5016;
            color: white;
        }

        .description-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .section-title {
            font-size: 1.3rem;
            color: #2d5016;
            font-weight: 700;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .description-text {
            color: #555;
            line-height: 1.8;
            font-size: 1rem;
        }

        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 20px;
        }

        .detail-item {
            display: flex;
            gap: 10px;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .detail-label {
            font-weight: 600;
            color: #666;
            min-width: 100px;
        }

        .detail-value {
            color: #333;
        }

        /* Mobile Responsive */
        @media (max-width: 968px) {
            .product-main {
                grid-template-columns: 1fr;
            }

            .main-image-container {
                height: 350px;
            }

            .shop-card {
                flex-direction: column;
                text-align: center;
            }

            .details-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .product-detail-wrapper {
                padding: 15px;
            }

            .product-main {
                padding: 20px;
            }

            .product-title {
                font-size: 1.5rem;
            }

            .product-price {
                font-size: 2rem;
            }

            .main-image-container {
                height: 300px;
            }

            .action-buttons {
                flex-direction: column;
            }

            .thumbnail {
                width: 60px;
                height: 60px;
            }
        }
    </style>
</head>
<body>
    <div class="overlay" id="overlay" onclick="toggleSidebar()"></div>
    <?php if ($isLoggedIn): ?>
        <?php include __DIR__ . '/topmarketnav.php'; ?>
    <?php else: ?>
        <nav class="top-navbar">
            <div class="logo-wrapper">
                <img src="<?php echo BASE_URL; ?>images/logo.jpg" alt="Agri Tayo Rito Logo">
            </div>
            
            <div class="search-container">
                <input type="text" placeholder="Search for fresh products..." class="search-input">
                <button class="search-btn">🔍</button>
            </div>
            
            <div class="navbar-actions">
                <a href="<?php echo BASE_URL; ?>auth/login">
                    <button class="nav-btn" style="border: 2px solid #2d5016; color: #2d5016;">Login</button>
                </a>
                <a href="<?php echo BASE_URL; ?>auth/register">
                    <button class="nav-btn" style="background: #2d5016; color: white;">Sign Up</button>
                </a>
            </div>
        </nav>
    <?php endif; ?>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <div class="product-detail-wrapper">
            <div class="product-main">
                <div class="product-gallery">
                    <div class="main-image-container">
                        <img src="<?php echo $mainImage; ?>" 
                             alt="<?php echo $productName; ?>" 
                             id="mainImage"
                             onerror="this.src='<?php echo BASE_URL; ?>images/placeholder.jpg'">
                        
                        <?php if ($isLoggedIn && $userRole === 'buyer'): ?>
                            <button class="wishlist-badge" 
                                    onclick="toggleWishlist(event, <?php echo $productID; ?>)"
                                    data-product-id="<?php echo $productID; ?>"
                                    title="Add to Wishlist">
                                🤍
                            </button>
                        <?php endif; ?>
                        
                        <?php if ($availableStock <= 0): ?>
                            <div class="stock-badge out-of-stock">Out of Stock</div>
                        <?php elseif ($availableStock <= $lowStockThreshold): ?>
                            <div class="stock-badge low-stock">Only <?php echo $availableStock; ?> left!</div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (count($productImages) > 1): ?>
                        <div class="thumbnail-gallery">
                            <?php foreach ($productImages as $index => $img): 
                                $imgPath = BASE_URL . ltrim($img['image_path'], '/');
                            ?>
                                <img src="<?php echo $imgPath; ?>" 
                                     class="thumbnail <?php echo $index === 0 ? 'active' : ''; ?>" 
                                     onclick="changeMainImage(this)"
                                     alt="Product image <?php echo $index + 1; ?>"
                                     onerror="this.src='<?php echo BASE_URL; ?>images/placeholder.jpg'">
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- PRODUCT INFO -->
                <div class="product-info">
                    <h1 class="product-title"><?php echo $productName; ?></h1>
                   
                    <div class="product-rating">
                        <div class="stars">
                            <?php for ($i = 0; $i < 5; $i++): ?>
                                <?php echo $i < floor($avgRating) ? '⭐' : '☆'; ?>
                            <?php endfor; ?>
                        </div>
                        <span class="rating-text"><?php echo $avgRating; ?> (<?php echo $totalReviews; ?> reviews)</span>
                    </div>

                    <div class="product-price-box">
                        <div class="price-label">Price</div>
                        <div>
                            <span class="product-price">₱<?php echo number_format($productPrice, 2); ?></span>
                            <span class="price-unit">per <?php echo $productUnit; ?></span>
                        </div>
                    </div>

                    <div class="quantity-selector">
                        <div class="quantity-label">Quantity:</div>
                        <div class="quantity-controls">
                            <div class="qty-buttons">
                                <button class="qty-btn" onclick="updateQty(-1)">−</button>
                                <div class="qty-display" id="qtyDisplay">1</div>
                                <button class="qty-btn" onclick="updateQty(1)">+</button>
                            </div>
                            <div class="stock-info">
                                <?php if ($availableStock > 0): ?>
                                    <span class="stock-available">✓ <?php echo $availableStock; ?> <?php echo $productUnit; ?> available</span>
                                <?php else: ?>
                                    <span style="color: #dc3545;">✗ Out of stock</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="action-buttons">
                        <?php if (!$isLoggedIn): ?>
                            <button class="btn-add-cart" onclick="promptLogin(event)">
                                <span>🔒</span>
                                <span>Login to Purchase</span>
                            </button>
                        <?php elseif ($userRole === 'buyer'): ?>
                            <?php if ($availableStock > 0): ?>
                                <button class="btn-add-cart" onclick="addToCartFromDetail()">
                                    <span>🛒</span>
                                    <span>Add to Cart</span>
                                </button>
                                <button class="btn-buy-now" onclick="buyNowFromDetail()">
                                    <span>Buy Now</span>
                                    <span>→</span>
                                </button>
                            <?php else: ?>
                                <button class="btn-add-cart" disabled>
                                    <span>✗</span>
                                    <span>Out of Stock</span>
                                </button>
                            <?php endif; ?>
                        <?php else: ?>
                            <button class="btn-add-cart" disabled style="opacity: 0.6;">
                                <span>👁️</span>
                                <span>View Only (<?php echo ucfirst($userRole); ?>)</span>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- SHOP CARD -->
            <div class="shop-card">
                <div class="shop-avatar">🌾</div>
                <div class="shop-info">
                    <div class="shop-name"><?php echo $businessName; ?></div>
                    <div class="shop-stats">
                        <div class="shop-stat">
                            <span>⭐</span>
                            <span><?php echo $sellerRating; ?> Rating</span>
                        </div>
                        <div class="shop-stat">
                            <span>📦</span>
                            <span><?php echo $totalProducts; ?> Products</span>
                        </div>
                    </div>
                </div>
                <?php if (!empty($shopSlug)): ?>
                    <a href="<?php echo BASE_URL; ?>marketplace/shop?slug=<?php echo $shopSlug; ?>">
                        <button class="btn-visit-shop">Visit Shop</button>
                    </a>
                <?php endif; ?>
            </div>

            <!-- DESCRIPTION -->
            <div class="description-card">
                <h2 class="section-title">
                    <span>📋</span>
                    Product Description
                </h2>
                <p class="description-text"><?php echo nl2br($productDesc); ?></p>
                
                <div class="details-grid">
                    <div class="detail-item">
                        <div class="detail-label">Category:</div>
                        <div class="detail-value"><?php echo $categoryName; ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Unit:</div>
                        <div class="detail-value"><?php echo $productUnit; ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Available:</div>
                        <div class="detail-value"><?php echo $availableStock; ?> <?php echo $productUnit; ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Shop:</div>
                        <div class="detail-value"><?php echo $shopName; ?></div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- SIDEBAR -->
    <?php if ($isLoggedIn): ?>
        <?php include __DIR__ . '/marketnav.php'; ?>
    <?php endif; ?>

    <script>
        window.productData = {
            productID: <?php echo $productID; ?>,
            productName: <?php echo json_encode($productName); ?>,
            productPrice: <?php echo $productPrice; ?>,
            availableStock: <?php echo $availableStock; ?>,
            isLoggedIn: <?php echo $isLoggedIn ? 'true' : 'false'; ?>,
            userRole: <?php echo json_encode($userRole); ?>,
            csrfToken: <?php echo json_encode($csrfToken); ?>,
            baseUrl: <?php echo json_encode(BASE_URL); ?>
        };

        let currentQty = 1;

        function updateQty(change) {
            const newQty = currentQty + change;
            if (newQty >= 1 && newQty <= window.productData.availableStock) {
                currentQty = newQty;
                document.getElementById('qtyDisplay').textContent = currentQty;
            }
        }

        function changeMainImage(thumbnail) {
            document.getElementById('mainImage').src = thumbnail.src;
            document.querySelectorAll('.thumbnail').forEach(t => t.classList.remove('active'));
            thumbnail.classList.add('active');
        }

        async function addToCartFromDetail() {
            const btn = event.target.closest('button');
            btn.disabled = true;
            btn.textContent = '⏳ Adding...';

            try {
                const response = await fetch(window.productData.baseUrl + 'app/controllers/CartController.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        action: 'add',
                        productID: window.productData.productID,
                        quantity: currentQty,
                        csrf_token: window.productData.csrfToken
                    })
                });

                const result = await response.json();

                if (result.success) {
                    showNotification('✅ Added to cart!', 'success');
                    setTimeout(() => {
                        btn.textContent = '🛒 Add to Cart';
                        btn.disabled = false;
                    }, 1000);
                } else {
                    showNotification(result.message || 'Failed to add item', 'error');
                    btn.textContent = '🛒 Add to Cart';
                    btn.disabled = false;
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('Network error occurred', 'error');
                btn.textContent = '🛒 Add to Cart';
                btn.disabled = false;
            }
        }

        function buyNowFromDetail() {
            // Store buy now data in session
            sessionStorage.setItem('buyNowItem', JSON.stringify({
                productID: window.productData.productID,
                quantity: currentQty,
                buyNow: true
            }));

            window.location.href = window.productData.baseUrl + 'item-handling/checkout';
        }

        function showNotification(message, type = 'success') {
            const notification = document.createElement('div');
            notification.className = `notification-toast notification-${type}`;
            notification.textContent = message;
            document.body.appendChild(notification);

            setTimeout(() => notification.classList.add('show'), 10);

            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        function promptLogin(event) {
            event.stopPropagation();
            if (confirm('Please login to add items to your cart.\n\nWould you like to login now?')) {
                sessionStorage.setItem('redirectAfterLogin', window.location.href);
                window.location.href = window.productData.baseUrl + 'auth/login';
            }
        }
    </script>

    <script src="<?php echo BASE_URL; ?>js/marketplace.js"></script>
</body>
</html>