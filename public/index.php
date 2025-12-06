<?php
session_start();

define('BASE_PATH', dirname(__DIR__));
define('PUBLIC_PATH', __DIR__);

require_once BASE_PATH . '/config/database.php';

$url = $_GET['url'] ?? '';
$url = str_replace('agri_system/public/index.php', '', $url);
$url = str_replace('agri_system/public/', '', $url);
$url = trim($url, '/');

$routes = [
    '' => BASE_PATH . '/app/views/landing.php',
    
   'api/address' => BASE_PATH . '/public/api/address.php',
    'api/update-avatar' => BASE_PATH . '/public/api/update-avatar.php',
    'api/upload-avatar' => BASE_PATH . '/public/api/upload-avatar.php',
    'api/upload-cover' => BASE_PATH . '/public/api/upload-cover.php',
    'api/cart' => BASE_PATH . '/app/controllers/CartController.php',
    'api/checkout' => BASE_PATH . '/app/controllers/OrderController.php',
    'api/delivery-status' => BASE_PATH . '/app/controllers/OrderController.php',

    // ✅ FIX: Add profile API routes
    'profile/update-avatar.php' => BASE_PATH . '/public/profile/update-avatar.php',
    'profile/upload-avatar.php' => BASE_PATH . '/public/profile/upload-avatar.php',
    'profile/upload-cover.php' => BASE_PATH . '/public/profile/upload-cover.php',

    // Auth
    'auth/login' => BASE_PATH . '/app/views/auth/login.php',
    'auth/logout' => BASE_PATH . '/app/views/auth/logout.php',
    'auth/pass-reset' => BASE_PATH . '/app/views/auth/pass-reset.php',
    'auth/register' => BASE_PATH . '/app/views/auth/register.php',
    'auth/debug-otp' => BASE_PATH . '/app/views/auth/debug-otp.php',
    
    // Marketplace (can be accessed without login)
    'marketplace' => BASE_PATH . '/app/views/marketplace/marketplace.php',
    'marketplace/product' => BASE_PATH . '/app/views/marketplace/product.php',
    'marketplace/livestream' => BASE_PATH . '/app/views/marketplace/livestream.php',
    'marketplace/notifications' => BASE_PATH . '/app/views/marketplace/notifications.php',
    'marketplace/messages' => BASE_PATH . '/app/views/marketplace/messages.php',
    'marketplace/wishlist' => BASE_PATH . '/app/views/profile/buyer/wishlists.php',
    'marketplace/myorders' => BASE_PATH . '/app/views/profile/buyer/myorders.php',
    'profile/apply' => BASE_PATH . '/app/views/profile/buyer/apply-seller.php',

    // Item Handling (require login)
    'item-handling/cart' => BASE_PATH . '/app/views/item-handling/cart.php',
    'item-handling/checkout' => BASE_PATH . '/app/views/item-handling/checkout.php',
    
    // Admin Dashboard Directory
    'profile/admin/dashboard' => BASE_PATH . '/app/views/profile/admin/dashboard.php',
    'profile/admin/applications' => BASE_PATH . '/app/views/profile/admin/applications.php',
    'profile/admin/category' => BASE_PATH . '/app/views/profile/admin/categories.php',
    'profile/admin/deliveries' => BASE_PATH . '/app/views/profile/admin/deliveries.php', 
    'profile/admin/withdrawals' => BASE_PATH . '/app/views/profile/admin/withdrawals.php',
    'profile/admin/cards' => BASE_PATH . '/app/views/profile/admin/cards.php',
    'profile/admin/riders' => BASE_PATH . '/app/views/profile/admin/riders.php',
    'profile/admin/users' => BASE_PATH . '/app/views/profile/admin/manage-users.php',
    
    // User Profile (general)
    'profile/user' => BASE_PATH . '/app/views/marketplace/profile.php',
    
    // Seller Routes
    'profile/seller/dashboard' => BASE_PATH . '/app/views/profile/seller/dashboard.php',
    'profile/seller/profile-info' => BASE_PATH . '/app/views/profile/seller/shop-profile.php',
    'profile/seller/products' => BASE_PATH . '/app/views/profile/seller/my-products.php',
    'profile/seller/orders' => BASE_PATH . '/app/views/profile/seller/orders.php',
    'profile/seller/order-details' => BASE_PATH . '/app/views/profile/seller/order-details.php', 
    'profile/seller/withdrawals' => BASE_PATH . '/app/views/profile/seller/withdrawals.php', 
    'profile/seller/image-upload' => BASE_PATH . '/app/views/profile/seller/image-upload.php', 

    // Devs pages
    'devs/about' => BASE_PATH . '/app/views/devs/about.php',
    'devs/edhub' => BASE_PATH . '/app/views/devs/edhub.php',
    'devs/terms' => BASE_PATH . '/app/views/devs/terms.php',
    'devs/privacy' => BASE_PATH . '/app/views/devs/privacy.php',
    'devs/contacts' => BASE_PATH . '/app/views/devs/contacts.php',

    // Settings
    'settings/account' => BASE_PATH . '/app/views/settings/custom.php',
    'settings/language-settings' => BASE_PATH . '/app/views/settings/language.php',
    'settings' => BASE_PATH . '/app/views/settings/settings.php',

    // Controllers Routes
    'withdrawal' => BASE_PATH . '/app/controllers/WithDrawalController.php',
];

if (isset($routes[$url])) {
    if (file_exists($routes[$url])) {
        require_once $routes[$url];
    } else {
        http_response_code(500);
        echo "File not found: " . htmlspecialchars($routes[$url]);
        error_log("Route file missing: " . $routes[$url]);
    }
} else {
    http_response_code(404);
    echo "404 - Page not found. URL: '" . htmlspecialchars($url) . "'";
    error_log("404 error for URL: " . $url);
}