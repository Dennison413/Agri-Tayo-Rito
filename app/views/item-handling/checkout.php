<?php
// app/views/item-handling/checkout.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../helpers/csrf.php';

$authController = new AuthController();
$authController->requireLogin();
$authController->requireRole('buyer');

$csrfToken = CSRF::generateToken();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
    <link rel="icon" type="image/jpg" href="/agri_system/public/images/agri-icon.jpg">
    <title>Agri Tayo Rito</title>

    <link rel="stylesheet" href="<?= BASE_URL ?>css/marketplace/marketplace.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/responsive.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/item-handling/checkout.css">

    <script>
        const BASE_URL = "<?= BASE_URL ?>";
        const CHECKOUT_API_URL = "<?= BASE_URL ?>api/checkout.php";
    </script>
</head>

<body>

    <?php require_once __DIR__ . '/../marketplace/marketnav.php'; ?>

    <main class="checkout-main-content">
        <div id="checkoutLoading" class="loading-state">
            <div class="spinner"></div>
            <p>Loading checkout...</p>
        </div>

        <div id="checkoutError" class="error-state" style="display: none;">
            <div class="error-icon">⚠️</div>
            <h2>Unable to Load Checkout</h2>
            <p id="errorMessage">Something went wrong</p>
            <a href="<?= BASE_URL ?>item-handling/cart" class="btn-primary">Back to Cart</a>
        </div>

        <div id="checkoutContainer" class="checkout-container" style="display: none;">

            <div class="checkout-main">

                <div class="checkout-breadcrumb">
                    <a href="<?= BASE_URL ?>marketplace" class="breadcrumb-link">Home</a>
                    <span class="breadcrumb-separator">››</span>
                    <a href="<?= BASE_URL ?>item-handling/cart" class="breadcrumb-link">Cart</a>
                    <span class="breadcrumb-separator">››</span>
                    <span>Checkout</span>
                </div>

                <div class="checkout-card delivery-address-card">
                    <h3><span class="card-icon">📦</span> Delivery Information</h3>

                    <div id="savedAddressesList" class="saved-addresses-list">
                        <div class="loading-addresses">Loading addresses...</div>
                    </div>

                    <button class="add-address-btn" onclick="showAddAddressForm()">
                        <span>+</span> Add New Address
                    </button>

                    <div id="addAddressForm" class="add-address-form">
                        <h4>📍 Add New Delivery Address</h4>
                        <div class="form-grid">
                            <div class="form-group full-width">
                                <label for="new_address">Complete Address *</label>
                                <textarea id="new_address" rows="3" required class="form-input"
                                    placeholder="House/Unit No., Street, Barangay"></textarea>
                            </div>

                            <div class="form-group">
                                <label for="new_municipality">Municipality *</label>
                                <input type="text" id="new_municipality" required class="form-input"
                                    placeholder="e.g., San Pablo City">
                            </div>

                            <div class="form-group">
                                <label for="new_province">Province *</label>
                                <input type="text" id="new_province" required class="form-input"
                                    placeholder="e.g., Laguna">
                            </div>

                            <div class="form-group full-width">
                                <label for="new_postal_code">Postal Code *</label>
                                <input type="text" id="new_postal_code" required class="form-input"
                                    placeholder="e.g., 4000">
                            </div>

                            <div class="form-actions full-width">
                                <button type="button" onclick="cancelAddAddress()" class="btn-cancel">Cancel</button>
                                <button type="button" onclick="saveNewAddress()" class="btn-save">Save Address</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="checkout-card order-items-card">
                    <div class="order-items-header">
                        <h3><span class="card-icon">📋</span> Order Items</h3>
                        <span class="items-count" id="itemsCountText">0 items</span>
                    </div>

                    <div id="orderItemsList">
                        <div class="loading-addresses">Loading items...</div>
                    </div>
                </div>

                <div class="checkout-card payment-card">
                    <h3><span class="card-icon">💳</span> Payment Method</h3>

                    <div class="payment-options">
                        <label class="payment-option selected">
                            <input type="radio" name="payment_method" value="cod" class="payment-radio"
                                onchange="selectPayment(this)" checked>
                            <span class="payment-icon">💵</span>
                            <div class="payment-info">
                                <div class="payment-name">Cash on Delivery</div>
                                <div class="payment-description">Pay when you receive your order</div>
                            </div>
                        </label>

                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="gcash" class="payment-radio"
                                onchange="selectPayment(this)">
                            <span class="payment-icon">📱</span>
                            <div class="payment-info">
                                <div class="payment-name">GCash</div>
                                <div class="payment-description">Pay securely with GCash</div>
                            </div>
                        </label>

                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="paymaya" class="payment-radio"
                                onchange="selectPayment(this)">
                            <span class="payment-icon">💳</span>
                            <div class="payment-info">
                                <div class="payment-name">PayMaya</div>
                                <div class="payment-description">Pay securely with PayMaya</div>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="checkout-card notes-card">
                    <h3><span class="card-icon">📝</span> Delivery Notes (Optional)</h3>
                    <textarea id="notes" name="notes" rows="3" placeholder="Add special instructions for delivery..."
                        class="form-input" style="width: 100%;"></textarea>
                </div>

            </div>

            <!-- Right Column: Order Summary Sidebar -->
            <aside class="order-summary-sidebar">
                <h3>Order Summary</h3>

                <div class="summary-item">
                    <span>Items (<span id="totalItemsCount">0</span>):</span>
                    <span class="summary-value" id="subtotalDisplay">₱0.00</span>
                </div>

                <div class="summary-item subtotal">
                    <span>Shipping Fee:</span>
                    <span class="summary-value" id="shippingFeeDisplay">₱0.00</span>
                </div>

                <div class="summary-total">
                    <span>Total:</span>
                    <span id="totalDisplay">₱0.00</span>
                </div>

                <button type="button" id="placeOrderBtn" class="place-order-btn" onclick="placeOrder()">
                    <span>Place Order</span>
                    <span>→</span>
                </button>

                <div class="order-note">
                    ℹ️ By placing your order, you agree to our terms and conditions.
                </div>
            </aside>

        </div>
    </main>

    <script src="<?= BASE_URL ?>js/checkout.js?v=<?= time() ?>"></script>
</body>

</html>