<?php
// app/controllers/OrderController.php
// FIXED: Order Management with proper JSON responses
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../models/Orders.php';
require_once __DIR__ . '/../models/Cart.php';
require_once __DIR__ . '/../helpers/csrf.php';

class OrderController
{
    private $ordersModel;
    private $cartModel;

    public function __construct()
    {
        $this->ordersModel = new Orders();
        $this->cartModel = new Cart();
    }

    public function handleCheckoutRequest()
    {
        // Set JSON response headers FIRST
        header('Content-Type: application/json');
        header('Access-Control-Allow-Credentials: true');

        // Check authentication
        if (!$this->isLoggedIn() || $_SESSION['user_role'] !== 'buyer') {
            $this->jsonResponse(false, 'Please login as a buyer', [
                'redirect' => '/agri_system/public/auth/login'
            ]);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $this->getCheckoutData();
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleCheckoutPost();
        } else {
            $this->jsonResponse(false, 'Invalid request method');
        }
    }

    private function getCheckoutData()
    {
        $buyerID = $_SESSION['user_id'];

        try {
            $selectedProductIDsString = $_GET['product_ids'] ?? '';
            $selectedProductIDs = $selectedProductIDsString ? array_map('intval', explode(',', $selectedProductIDsString)) : [];

            if (empty($selectedProductIDs)) {
                $this->jsonResponse(false, 'No products selected for checkout', [
                    'redirect' => '/agri_system/public/item-handling/cart'
                ]);
                return;
            }

            // ✅ Store selected product IDs in session for later use
            $_SESSION['checkout_product_ids'] = $selectedProductIDs;
            error_log("Stored checkout product IDs in session: " . json_encode($selectedProductIDs));
            $allCartItems = $this->cartModel->getCartItemsGroupedByShop($buyerID);

            if (empty($allCartItems)) {
                $this->jsonResponse(false, 'Your cart is empty', [
                    'redirect' => '/agri_system/public/item-handling/cart'
                ]);
                return;
            }

            $selectedItems = [];
            $subtotal = 0;
            $itemCount = 0;

            foreach ($allCartItems as $shopID => $shop) {
                foreach ($shop['items'] as $item) {
                    if (in_array($item['productID'], $selectedProductIDs)) {
                        $selectedItems[] = [
                            'productID' => $item['productID'],
                            'product_name' => $item['product_name'],
                            'price' => number_format($item['price'], 2, '.', ''),
                            'quantity' => $item['quantity'],
                            'unit' => $item['unit'],
                            'shop_name' => $shop['shop_name'],
                            'shopID' => $shopID,
                            'primary_image' => $item['primary_image'] ?? '/images/placeholder.jpg',
                            'item_total' => number_format($item['price'] * $item['quantity'], 2, '.', '')
                        ];

                        $subtotal += ($item['price'] * $item['quantity']);
                        $itemCount += $item['quantity'];
                    }
                }
            }

            if (empty($selectedItems)) {
                $this->jsonResponse(false, 'Selected items not found in cart', [
                    'redirect' => '/agri_system/public/item-handling/cart'
                ]);
                return;
            }

            require_once __DIR__ . '/../models/User.php';
            require_once __DIR__ . '/../models/UserAddress.php';

            $userModel = new User();
            $addressModel = new UserAddress();

            $user = $userModel->getUserById($buyerID);

            if (!$user) {
                $this->jsonResponse(false, 'User not found');
                return;
            }

            $userAddresses = $addressModel->getUserAddresses($buyerID);
            foreach ($userAddresses as &$addr) {
                $addr['full_name'] = $user['full_name'];
                $addr['phone'] = $user['phone'] ?? '';
            }
            unset($addr);

            $defaultAddress = $addressModel->getDefaultAddress($buyerID);
            if ($defaultAddress) {
                $defaultAddress['full_name'] = $user['full_name'];
                $defaultAddress['phone'] = $user['phone'] ?? '';
            }

            $shippingFee = 0;
            $total = $subtotal;

            $this->jsonResponse(true, 'Checkout data retrieved', [
                'items' => $selectedItems,
                'itemCount' => $itemCount,
                'subtotal' => number_format($subtotal, 2, '.', ''),
                'shippingFee' => number_format($shippingFee, 2, '.', ''),
                'total' => number_format($total, 2, '.', ''),
                'user' => [
                    'full_name' => $user['full_name'],
                    'email' => $user['email'],
                    'phone' => $user['phone'] ?? ''
                ],
                'addresses' => $userAddresses,
                'defaultAddress' => $defaultAddress,
                'csrf_token' => CSRF::generateToken()
            ]);
        } catch (Exception $e) {
            error_log("Get checkout data error: " . $e->getMessage());
            $this->jsonResponse(false, 'Failed to load checkout data: ' . $e->getMessage());
        }
    }

    private function handleCheckoutPost()
    {
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->jsonResponse(false, 'Invalid request data');
            return;
        }

        // ✅ FIX: More lenient CSRF validation with detailed logging
        $csrfToken = $data['csrf_token'] ?? '';

        if (empty($csrfToken)) {
            error_log("CSRF token missing from request");
            $this->jsonResponse(false, 'Security token missing');
            return;
        }

        if (!isset($_SESSION['csrf_token'])) {
            error_log("CSRF token not found in session");
            $this->jsonResponse(false, 'Session expired. Please refresh and try again.');
            return;
        }

        if ($csrfToken !== $_SESSION['csrf_token']) {
            error_log("CSRF token mismatch. Provided: $csrfToken, Expected: {$_SESSION['csrf_token']}");
            $this->jsonResponse(false, 'Invalid security token. Please refresh and try again.');
            return;
        }

        $action = $data['action'] ?? null;

        if ($action === 'place_order') {
            $this->placeOrderAjax($data);
        } else {
            $this->jsonResponse(false, 'Invalid action');
        }
    }

    private function placeOrderAjax($data)
    {
        $buyerID = $_SESSION['user_id'];

        try {
            // ✅ FIX: Get selected product IDs from session
            $selectedProductIDs = isset($_SESSION['checkout_product_ids'])
                ? $_SESSION['checkout_product_ids']
                : [];

            if (empty($selectedProductIDs)) {
                $this->jsonResponse(false, 'No items selected for checkout');
                return;
            }

            error_log("=== PLACE ORDER DEBUG ===");
            error_log("Selected product IDs from session: " . json_encode($selectedProductIDs));

            // ✅ Get ALL cart items first
            $allCartItems = $this->cartModel->getCartItemsGroupedByShop($buyerID);

            if (empty($allCartItems)) {
                $this->jsonResponse(false, 'Your cart is empty');
                return;
            }

            // ✅ Filter to ONLY selected items
            $selectedCartItems = [];
            foreach ($allCartItems as $shop) {
                foreach ($shop['items'] as $item) {
                    if (in_array($item['productID'], $selectedProductIDs)) {
                        $selectedCartItems[] = $item;
                        error_log("✅ Including product: " . $item['product_name'] . " (ID: " . $item['productID'] . ")");
                    }
                }
            }

            if (empty($selectedCartItems)) {
                $this->jsonResponse(false, 'Selected items not found in cart');
                return;
            }

            error_log("Total selected items for order: " . count($selectedCartItems));

            // Validate selected items
            $validation = $this->cartModel->validateCartForCheckout($buyerID);
            if (!$validation['valid']) {
                $this->jsonResponse(false, 'Some items are no longer available', [
                    'errors' => $validation['errors']
                ]);
                return;
            }

            // Validate required fields
            $required = ['address_id', 'payment_method'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    $this->jsonResponse(false, ucfirst(str_replace('_', ' ', $field)) . ' is required');
                    return;
                }
            }

            // Validate payment method
            if (!in_array($data['payment_method'], ['cod', 'gcash', 'paymaya'])) {
                $this->jsonResponse(false, 'Invalid payment method');
                return;
            }

            // Get selected address
            require_once __DIR__ . '/../models/UserAddress.php';
            $addressModel = new UserAddress();
            $selectedAddress = $addressModel->getAddress($data['address_id'], $buyerID);

            if (!$selectedAddress) {
                $this->jsonResponse(false, 'Invalid address selected');
                return;
            }

            // Prepare order data
            $orderData = [
                'delivery_address' => $selectedAddress['address'],
                'delivery_municipality' => $selectedAddress['municipality'],
                'delivery_province' => $selectedAddress['province'],
                'delivery_postal_code' => $selectedAddress['postal_code'],
                'payment_method' => $data['payment_method'],
                'notes' => filter_var($data['notes'] ?? '', FILTER_SANITIZE_STRING)
            ];

            // ✅ Calculate total ONLY from selected items
            $totalAmount = 0;
            foreach ($selectedCartItems as $item) {
                $totalAmount += ($item['price'] * $item['quantity']);
            }
            $orderData['total_amount'] = $totalAmount;

            error_log("Order total amount: ₱" . number_format($totalAmount, 2));

            // ✅ Create order with ONLY selected items
            $result = $this->ordersModel->createOrder($buyerID, $orderData, $selectedCartItems);

            if ($result['success']) {
                // ✅ Clear the selected product IDs from session
                unset($_SESSION['checkout_product_ids']);

                // Regenerate CSRF token
                CSRF::regenerateToken();

                $this->jsonResponse(true, 'Order placed successfully!', [
                    'orderID' => $result['orderID'],
                    'redirect' => '/agri_system/public/marketplace/myorders?order=' . $result['orderID'],
                    'csrf_token' => CSRF::generateToken()
                ]);
            } else {
                $this->jsonResponse(false, $result['message']);
            }
        } catch (Exception $e) {
            error_log("Place order error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            $this->jsonResponse(false, 'Failed to place order: ' . $e->getMessage());
        }
    }

    private function jsonResponse($success, $message, $data = [])
    {
        $response = array_merge([
            'success' => $success,
            'message' => $message
        ], $data);

        echo json_encode($response);
        exit;
    }

    private function isLoggedIn()
    {
        return isset($_SESSION['user_id']) &&
            isset($_SESSION['logged_in']) &&
            $_SESSION['logged_in'] === true;
    }

    // Other methods for non-AJAX requests...
    public function getBuyerOrders($buyerID, $limit = 10, $offset = 0)
    {
        return $this->ordersModel->getOrdersByBuyer($buyerID, $limit, $offset);
    }

    public function getOrderDetails($orderID, $userID, $role)
    {
        $order = $this->ordersModel->getOrderById($orderID);
        if (!$order) return null;

        if ($role === 'buyer' && $order['buyerID'] != $userID) {
            return null;
        }

        return $order;
    }

    public function getOrderStats($userID, $role)
    {
        return $this->ordersModel->getOrderStats($userID, $role);
    }
}

// ✅ REMOVED: Don't handle POST here - let the API endpoint handle it
// This file should only define the class, not execute code
