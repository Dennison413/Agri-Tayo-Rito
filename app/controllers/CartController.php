<?php
/**
 * CartController.php - Fixed Version
 * Handles AJAX cart operations with CSRF validation
 */

// Initialize session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load dependencies
require_once __DIR__ . '/../models/Cart.php';
require_once __DIR__ . '/../helpers/csrf.php';
require_once __DIR__ . '/../helpers/RateLimiter.php';

class CartController 
{
    private $cartModel;

    public function __construct() 
    {
        $this->cartModel = new Cart();
    }

    /**
     * Handle AJAX cart operations with CSRF validation
     */
    public function handleAjaxRequest() 
    {
        // Set JSON response headers
        header('Content-Type: application/json');
        header('Access-Control-Allow-Credentials: true');

        // Check API rate limit (60 requests per minute)
        $rateCheck = RateLimiter::checkApiRate('cart');
        if (!$rateCheck['allowed']) {
            $this->jsonResponse(false, 'Too many requests. Please wait a moment.', [
                'error_code' => 'RATE_LIMIT_EXCEEDED',
                'retry_after' => $rateCheck['retry_after'] ?? 1
            ]);
            return;
        }

        // Check authentication
        if (!$this->isLoggedIn()) {
            $this->jsonResponse(false, 'Please login first', [
                'redirect' => '/agri_system/public/auth/login'
            ]);
            return;
        }

        // Check if user is a buyer
        if ($_SESSION['user_role'] !== 'buyer') {
            $this->jsonResponse(false, 'Only buyers can access cart');
            return;
        }

        // Get JSON input
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->jsonResponse(false, 'Invalid request data');
            return;
        }

        // CSRF validation using existing class
        if (!CSRF::validateJsonRequest()) {
            CSRF::handleFailure(true);
            return;
        }

        $action = $data['action'] ?? null;

        if (!$action) {
            $this->jsonResponse(false, 'No action specified');
            return;
        }

        $buyerID = $_SESSION['user_id'];

        // Handle actions
        try {
            switch ($action) {
                case 'add':
                    $this->addToCart($buyerID, $data);
                    break;
                    
                case 'update':
                    $this->updateQuantity($buyerID, $data);
                    break;
                    
                case 'remove':
                    $this->removeFromCart($buyerID, $data);
                    break;
                    
                case 'get_cart':
                    $this->getCart($buyerID);
                    break;
                    
                case 'get_count':
                    $this->getCartCount($buyerID);
                    break;

                case 'validate_checkout':
                    $this->validateCheckout($buyerID);
                    break;

                case 'clear_cart':
                    $this->clearEntireCart($buyerID);
                    break;
                    
                default:
                    $this->jsonResponse(false, 'Invalid action');
                    break;
            }
        } catch (Exception $e) {
            error_log("CartController error: " . $e->getMessage());
            $this->jsonResponse(false, 'An error occurred. Please try again.', [
                'error_details' => $e->getMessage()
            ]);
        }
    }

    /**
     * Add item to cart
     */
    private function addToCart($buyerID, $data) 
    {
        $productID = filter_var($data['productID'] ?? null, FILTER_VALIDATE_INT);
        $quantity = filter_var($data['quantity'] ?? 1, FILTER_VALIDATE_INT);

        if (!$productID || !$quantity || $quantity < 1) {
            $this->jsonResponse(false, 'Invalid product or quantity');
            return;
        }

        if ($quantity > 20) {
            $this->jsonResponse(false, 'Maximum 20 items per product');
            return;
        }

        $result = $this->cartModel->addToCart($buyerID, $productID, $quantity);
        $cartCount = $this->cartModel->getCartCount($buyerID);

        $this->jsonResponse($result['success'], $result['message'], [
            'cartCount' => $cartCount,
            'csrf_token' => CSRF::generateToken()
        ]);
    }

    /**
     * Update cart item quantity
     */
    private function updateQuantity($buyerID, $data) 
    {
        $productID = filter_var($data['productID'] ?? null, FILTER_VALIDATE_INT);
        $quantity = filter_var($data['quantity'] ?? null, FILTER_VALIDATE_INT);

        if (!$productID || $quantity === null || $quantity === false) {
            $this->jsonResponse(false, 'Product ID and quantity are required');
            return;
        }

        if ($quantity < 0) {
            $this->jsonResponse(false, 'Quantity cannot be negative');
            return;
        }

        if ($quantity > 20) {
            $this->jsonResponse(false, 'Maximum 20 items per product allowed');
            return;
        }

        $result = $this->cartModel->updateCartQuantity($buyerID, $productID, $quantity);
        $cartCount = $this->cartModel->getCartCount($buyerID);

        // Get updated cart total
        $cartTotal = $this->cartModel->getCartTotal($buyerID);

        $this->jsonResponse($result['success'], $result['message'], [
            'cartCount' => $cartCount,
            'cartTotal' => $cartTotal,
            'newQuantity' => $quantity,
            'csrf_token' => CSRF::generateToken()
        ]);
    }

    /**
     * Remove item from cart
     */
    private function removeFromCart($buyerID, $data) 
    {
        $productID = filter_var($data['productID'] ?? null, FILTER_VALIDATE_INT);

        if (!$productID) {
            $this->jsonResponse(false, 'Product ID is required');
            return;
        }

        $result = $this->cartModel->removeFromCart($buyerID, $productID);
        $cartCount = $this->cartModel->getCartCount($buyerID);
        $cartTotal = $this->cartModel->getCartTotal($buyerID);

        $this->jsonResponse($result['success'], $result['message'], [
            'cartCount' => $cartCount,
            'cartTotal' => $cartTotal,
            'csrf_token' => CSRF::generateToken()
        ]);
    }

    /**
     * Get cart items grouped by shop
     */
    private function getCart($buyerID) 
    {
        $items = $this->cartModel->getCartItemsGroupedByShop($buyerID);
        $count = $this->cartModel->getCartCount($buyerID);
        $total = $this->cartModel->getCartTotal($buyerID);

        $this->jsonResponse(true, 'Cart retrieved successfully', [
            'items' => $items,
            'count' => $count,
            'total' => number_format($total, 2),
            'csrf_token' => CSRF::generateToken()
        ]);
    }

    /**
     * Get cart item count only
     */
    private function getCartCount($buyerID) 
    {
        $count = $this->cartModel->getCartCount($buyerID);

        $this->jsonResponse(true, 'Count retrieved', [
            'count' => $count
        ]);
    }

    /**
     * Validate cart before checkout
     */
    private function validateCheckout($buyerID) 
    {
        // Check if cart is not empty
        $cartCount = $this->cartModel->getCartCount($buyerID);
        
        if ($cartCount === 0) {
            $this->jsonResponse(false, 'Your cart is empty');
            return;
        }

        // Validate stock availability
        $validation = $this->cartModel->validateCartForCheckout($buyerID);

        if ($validation['valid']) {
            $this->jsonResponse(true, 'Cart is valid for checkout', [
                'csrf_token' => CSRF::generateToken()
            ]);
        } else {
            $this->jsonResponse(false, 'Some items in your cart have stock issues', [
                'errors' => $validation['errors']
            ]);
        }
    }

    /**
     * Clear entire cart
     */
    private function clearEntireCart($buyerID) 
    {
        $result = $this->cartModel->clearCart($buyerID);
        
        $this->jsonResponse($result, $result ? 'Cart cleared successfully' : 'Failed to clear cart', [
            'csrf_token' => CSRF::generateToken()
        ]);
    }

    /**
     * Get cart for checkout page (non-AJAX)
     * Used by checkout.php
     */
    public function getCartForCheckout($buyerID) 
    {
        $validation = $this->cartModel->validateCartForCheckout($buyerID);

        if (!$validation['valid']) {
            return [
                'valid' => false,
                'errors' => $validation['errors']
            ];
        }

        $items = $this->cartModel->getCartItemsGroupedByShop($buyerID);
        $total = $this->cartModel->getCartTotal($buyerID);

        return [
            'valid' => true,
            'items' => $items,
            'total' => $total,
            'shipping_fee' => 0 // LGU handles delivery - zero shipping
        ];
    }

    /**
     * Clear cart (public method for after checkout)
     */
    public function clearCart($buyerID) 
    {
        return $this->cartModel->clearCart($buyerID);
    }

    /**
     * Check if user is logged in
     */
    private function isLoggedIn() 
    {
        return isset($_SESSION['user_id']) && 
               isset($_SESSION['logged_in']) && 
               $_SESSION['logged_in'] === true;
    }

    /**
     * Send JSON response
     */
    private function jsonResponse($success, $message, $data = []) 
    {
        $response = array_merge([
            'success' => $success,
            'message' => $message
        ], $data);

        echo json_encode($response);
        exit;
    }
}

// IMPORTANT: Handle AJAX requests directly
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller = new CartController();
    $controller->handleAjaxRequest();
    exit;
}