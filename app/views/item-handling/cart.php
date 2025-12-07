<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../controllers/CartController.php';
require_once __DIR__ . '/../../models/Cart.php';
require_once __DIR__ . '/../../models/Product.php';
require_once __DIR__ . '/../../models/Category.php';
require_once __DIR__ . '/../../helpers/csrf.php';

$auth = new AuthController();
$auth->requireLogin();
$auth->requireRole('buyer');

$buyerID = $_SESSION['user_id'];

$cartModel = new Cart();
$cartItemsGrouped = $cartModel->getCartItemsGroupedByShop($buyerID);

// Generate CSRF token
$csrfToken = CSRF::generateToken();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
    <title>My Cart - Agri Tayo Rito</title>

    <link rel="stylesheet" href="<?= BASE_URL ?>css/marketplace/marketplace.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/responsive.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/item-handling/cart.css">
    <style>
        .cart-item-image[src*="placeholder.jpg"] {
            background: linear-gradient(135deg, #e0e0e0 0%, #f5f5f5 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .cart-item-image[src*="placeholder.jpg"]::before {
            content: "📦";
            font-size: 3rem;
        }
    </style>
    <script>
        const BASE_URL = "<?= BASE_URL ?>";
        // FIXED: Point to the API router
        const CART_CONTROLLER_URL = "<?= BASE_URL ?>api/cart.php";
    </script>
</head>

<body>

    <?php require_once __DIR__ . '/../marketplace/marketnav.php'; ?>

    <?php if (empty($cartItemsGrouped)): ?>
        <!-- Empty Cart State -->
        <main class="main-content">
            <div class="cart-container">
                <div class="cart-items-container">
                    <div class="empty-cart">
                        <div class="empty-cart-icon">🛒</div>
                        <h3>Your cart is empty</h3>
                        <p>Browse our marketplace and add items to your cart!</p>
                        <a href="<?= BASE_URL ?>marketplace" class="shop-now-btn">Start Shopping</a>
                    </div>
                </div>
            </div>
        </main>

    <?php else: ?>

        <!-- Cart with Items -->
        <main class="main-content">
            <div class="cart-breadcrumb">
                <a href="<?= BASE_URL ?>marketplace" class="breadcrumb-link">Home</a>
                <span class="breadcrumb-separator">›</span>
                <span>Cart</span>
            </div>
            <br>
            <div class="cart-container">


                <!-- Cart Items Section -->
                <div class="cart-items-container">

                    <!-- Cart Header with Select All -->
                    <div class="cart-header">
                        <h2>🛒 My Shopping Cart</h2>
                        <div class="select-all-container">
                            <input type="checkbox"
                                id="selectAllCheckbox"
                                class="select-all-checkbox"
                                onchange="selectAllItems()">
                            <label for="selectAllCheckbox">Select All</label>
                        </div>
                    </div>

                    <!-- Cart Content -->
                    <div id="cartContent">
                        <?php foreach ($cartItemsGrouped as $shopID => $shop): ?>

                            <!-- Shop Group -->
                            <div class="shop-section">

                                <!-- Shop Header -->
                                <div class="shop-header">
                                    <span class="shop-icon">🏪</span>
                                    <div class="shop-name">
                                        <?= htmlspecialchars($shop['shop_name']) ?>
                                    </div>
                                </div>

                                <!-- Shop Items -->
                                <?php foreach ($shop['items'] as $item): ?>
                                    <?php
                                    $itemTotal = $item['price'] * $item['quantity'];
                                    $availableStock = $item['stock_quantity'] - $item['reserved_quantity'];
                                    ?>

                                    <!-- Cart Item Card -->
                                    <div class="cart-item-card"
                                        data-product-id="<?= $item['productID'] ?>"
                                        data-price="<?= number_format($item['price'], 2, '.', '') ?>">

                                        <!-- Checkbox -->
                                        <input type="checkbox"
                                            class="item-checkbox"
                                            onchange="calculateCartTotal()">

                                        <!-- Product Image -->
                                        <?php
                                        // Properly construct the full image URL
                                        if (!empty($item['primary_image'])) {
                                            // If path starts with /, remove it to avoid double slashes
                                            $imagePath = ltrim($item['primary_image'], '/');
                                            $imageUrl = BASE_URL . $imagePath;
                                        } else {
                                            // Fallback to placeholder
                                            $imageUrl = BASE_URL . 'images/placeholder.jpg';
                                        }
                                        ?>
                                        <img src="<?= htmlspecialchars($imageUrl) ?>"
                                            alt="<?= htmlspecialchars($item['product_name']) ?>"
                                            class="cart-item-image"
                                            onerror="this.src='<?= BASE_URL ?>images/placeholder.jpg'">

                                        <!-- Product Info -->
                                        <div class="cart-item-info">
                                            <h3 class="cart-item-name">
                                                <?= htmlspecialchars($item['product_name']) ?>
                                            </h3>
                                            <p class="cart-item-price">
                                                ₱<?= number_format($item['price'], 2) ?> / <?= htmlspecialchars($item['unit']) ?>
                                            </p>
                                            <p class="cart-item-stock" style="font-size: 0.85rem; color: <?= $availableStock < 10 ? '#ff9800' : '#666' ?>;">
                                                <?php if ($availableStock > 0): ?>
                                                    <?= $availableStock ?> available
                                                <?php else: ?>
                                                    Out of stock
                                                <?php endif; ?>
                                            </p>

                                            <!-- Quantity Controls -->
                                            <div class="cart-qty-controls">
                                                <button class="cart-qty-btn"
                                                    onclick="updateQty(<?= $item['productID'] ?>, -1)"
                                                    aria-label="Decrease quantity">−</button>
                                                <span class="cart-qty-number" id="qty-<?= $item['productID'] ?>">
                                                    <?= $item['quantity'] ?>
                                                </span>
                                                <button class="cart-qty-btn"
                                                    onclick="updateQty(<?= $item['productID'] ?>, 1)"
                                                    aria-label="Increase quantity">+</button>
                                            </div>
                                        </div>

                                        <!-- Item Actions -->
                                        <div class="cart-item-actions">
                                            <div class="cart-item-total">
                                                ₱<?= number_format($itemTotal, 2) ?>
                                            </div>
                                            <button class="remove-item-btn"
                                                onclick="removeItem(<?= $item['productID'] ?>)"
                                                aria-label="Remove item"
                                                title="Remove from cart">
                                                🗑️
                                            </button>
                                        </div>

                                    </div>
                                <?php endforeach; ?>

                            </div>
                        <?php endforeach; ?>
                    </div>

                </div>

                <!-- Order Summary Sidebar -->
                <div class="order-summary">
                    <h3>Order Summary</h3>

                    <div class="summary-row">
                        <span>Selected Items:</span>
                        <span id="selectedItemsCount">0</span>
                    </div>

                    <div class="summary-row">
                        <span>Subtotal:</span>
                        <span id="subtotalAmount">₱0.00</span>
                    </div>

                    <div class="summary-row">
                        <span>Shipping Fee:</span>
                        <span id="shippingFee">₱0.00</span>
                    </div>

                    <div class="summary-row total">
                        <span>Total:</span>
                        <span id="cartGrandTotal">₱0.00</span>
                    </div>

                    <!-- Checkout Button -->
                    <button class="checkout-button"
                        id="checkoutBtn"
                        onclick="goToCheckout()"
                        disabled>
                        Proceed to Checkout →
                    </button>

                    <!-- Continue Shopping Button -->
                    <button class="continue-shopping"
                        onclick="window.location.href='<?= BASE_URL ?>marketplace'">
                        Continue Shopping
                    </button>
                </div>

            </div>
        </main>

    <?php endif; ?>

    <!-- Load Cart JavaScript -->
    <script src="<?= BASE_URL ?>js/cart.js?v=<?= time() ?>"></script>

</body>

</html>