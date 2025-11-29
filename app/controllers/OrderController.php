<?php
// app/controllers/OrderController.php
// SECURED: Order Management with CSRF Protection
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../models/Orders.php';
require_once __DIR__ . '/../models/Cart.php';
require_once __DIR__ . '/../helpers/csrf.php';
require_once __DIR__ . '/../helpers/RateLimiter.php';

class OrderController
{
    private $ordersModel;
    private $cartModel;

    public function __construct()
    {
        $this->ordersModel = new Orders();
        $this->cartModel = new Cart();
    }

    /**
     * Handle AJAX checkout requests
     * NEW METHOD - Add this to OrderController class
     */
    public function handleCheckoutRequest()
    {
        // Set JSON response headers
        header('Content-Type: application/json');
        header('Access-Control-Allow-Credentials: true');

        // Check authentication
        if (!$this->isLoggedIn() || $_SESSION['user_role'] !== 'buyer') {
            $this->jsonResponse(false, 'Please login as a buyer', [
                'redirect' => '/agri_system/public/auth/login'
            ]);
            return;
        }

        // Get request method
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            // Get checkout data
            $this->getCheckoutData();
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Handle checkout actions
            $this->handleCheckoutPost();
        } else {
            $this->jsonResponse(false, 'Invalid request method');
        }
    }
    /**
     * Get checkout data (cart items + user details)
     * ✅ FIXED: Now filters by selected product IDs
     */
    private function getCheckoutData()
    {
        $buyerID = $_SESSION['user_id'];

        try {
            // ✅ Get selected product IDs from query string
            $selectedProductIDsString = $_GET['product_ids'] ?? '';
            $selectedProductIDs = $selectedProductIDsString ? array_map('intval', explode(',', $selectedProductIDsString)) : [];

            if (empty($selectedProductIDs)) {
                $this->jsonResponse(false, 'No products selected for checkout', [
                    'redirect' => '/agri_system/public/item-handling/cart'
                ]);
                return;
            }

            // Get ALL cart items first
            $allCartItems = $this->cartModel->getCartItemsGroupedByShop($buyerID);

            if (empty($allCartItems)) {
                $this->jsonResponse(false, 'Your cart is empty', [
                    'redirect' => '/agri_system/public/item-handling/cart'
                ]);
                return;
            }

            // ✅ Filter to get ONLY selected items
            $selectedItems = [];
            $selectedShops = [];
            $subtotal = 0;
            $itemCount = 0;

            foreach ($allCartItems as $shopID => $shop) {
                foreach ($shop['items'] as $item) {
                    if (in_array($item['productID'], $selectedProductIDs)) {
                        // Add to selected items
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

            // ✅ Check if we found the selected items
            if (empty($selectedItems)) {
                $this->jsonResponse(false, 'Selected items not found in cart', [
                    'redirect' => '/agri_system/public/item-handling/cart'
                ]);
                return;
            }

            // Get user details
            require_once __DIR__ . '/../models/User.php';
            require_once __DIR__ . '/../models/UserAddress.php';

            $userModel = new User();
            $addressModel = new UserAddress();

            $user = $userModel->getUserById($buyerID);

            if (!$user) {
                $this->jsonResponse(false, 'User not found');
                return;
            }

            // Get all user addresses for selection
            $userAddresses = $addressModel->getUserAddresses($buyerID);

            // Add user's full_name and phone to each address for display
            foreach ($userAddresses as &$addr) {
                $addr['full_name'] = $user['full_name'];
                $addr['phone'] = $user['phone'] ?? '';
            }
            unset($addr);

            // Get default address (most recent)
            $defaultAddress = $addressModel->getDefaultAddress($buyerID);
            if ($defaultAddress) {
                $defaultAddress['full_name'] = $user['full_name'];
                $defaultAddress['phone'] = $user['phone'] ?? '';
            }

            // Calculate totals
            $shippingFee = 0; // LGU handles delivery
            $total = $subtotal;

            // ✅ Return filtered items only
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
            $this->jsonResponse(false, 'Failed to load checkout data');
        }
    }
    /**
     * Handle POST requests (place order)
     */
    private function handleCheckoutPost()
    {
        // Get JSON input
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->jsonResponse(false, 'Invalid request data');
            return;
        }

        // CSRF validation
        if (!CSRF::validateJsonRequest()) {
            CSRF::handleFailure(true);
            return;
        }

        $action = $data['action'] ?? null;

        if ($action === 'place_order') {
            $this->placeOrderAjax($data);
        } else {
            $this->jsonResponse(false, 'Invalid action');
        }
    }

    /**
     * Place order via AJAX
     */
    private function placeOrderAjax($data)
    {
        $buyerID = $_SESSION['user_id'];

        try {
            // Get cart items
            $cartItems = $this->cartModel->getCartItemsGroupedByShop($buyerID);

            if (empty($cartItems)) {
                $this->jsonResponse(false, 'Your cart is empty');
                return;
            }

            // Validate cart
            $validation = $this->cartModel->validateCartForCheckout($buyerID);
            if (!$validation['valid']) {
                $this->jsonResponse(false, 'Some items are no longer available', [
                    'errors' => $validation['errors']
                ]);
                return;
            }

            // Validate form data
            $required = ['address_id', 'payment_method']; // Changed from full address fields to just address_id
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

            // Get the selected address
            require_once __DIR__ . '/../models/UserAddress.php';
            $addressModel = new UserAddress();
            $selectedAddress = $addressModel->getAddress($data['address_id'], $buyerID);

            if (!$selectedAddress) {
                $this->jsonResponse(false, 'Invalid address selected');
                return;
            }

            // Prepare order data using selected address
            $orderData = [
                'delivery_address' => $selectedAddress['address'],
                'delivery_municipality' => $selectedAddress['municipality'],
                'delivery_province' => $selectedAddress['province'],
                'delivery_postal_code' => $selectedAddress['postal_code'],
                'payment_method' => $data['payment_method'],
                'notes' => filter_var($data['notes'] ?? '', FILTER_SANITIZE_STRING)
            ];
            // Calculate total
            $totalAmount = $this->cartModel->getCartTotal($buyerID);
            $orderData['total_amount'] = $totalAmount;

            // Flatten cart items
            $flatCartItems = [];
            foreach ($cartItems as $shop) {
                foreach ($shop['items'] as $item) {
                    $flatCartItems[] = $item;
                }
            }

            // Create order
            $result = $this->ordersModel->createOrder($buyerID, $orderData, $flatCartItems);

            if ($result['success']) {
                // Regenerate CSRF token
                CSRF::regenerateToken();

                $this->jsonResponse(true, 'Order placed successfully!', [
                    'orderID' => $result['orderID'],
                    'redirect' => '/agri_system/public/profile/buyer/orders?order=' . $result['orderID'],
                    'csrf_token' => CSRF::generateToken()
                ]);
            } else {
                $this->jsonResponse(false, $result['message']);
            }
        } catch (Exception $e) {
            error_log("Place order error: " . $e->getMessage());
            $this->jsonResponse(false, 'Failed to place order. Please try again.');
        }
    }

    /**
     * Send JSON response helper
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
    // ==================== BUYER: PLACE ORDER ====================

    public function placeOrder()
    {
        // CSRF Validation
        if (!CSRF::validateRequest()) {
            CSRF::handleFailure(false);
            return;
        }

        // Check authentication
        if (!$this->isLoggedIn() || $_SESSION['user_role'] !== 'buyer') {
            $_SESSION['error'] = 'Unauthorized access';
            header('Location: /agri_system/public/auth/login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = 'Invalid request method';
            header('Location: /agri_system/public/item-handling/cart');
            exit;
        }

        $buyerID = $_SESSION['user_id'];

        try {
            // Get cart items for checkout
            $cartItems = $this->cartModel->getCartItemsGroupedByShop($buyerID);

            if (empty($cartItems)) {
                $_SESSION['error'] = 'Your cart is empty';
                header('Location: /agri_system/public/item-handling/cart');
                exit;
            }

            // Validate cart before checkout
            $validation = $this->cartModel->validateCartForCheckout($buyerID);
            if (!$validation['valid']) {
                $_SESSION['error'] = 'Some items are no longer available: ' . implode(', ', $validation['errors']);
                header('Location: /agri_system/public/item-handling/cart');
                exit;
            }

            $addressID = filter_var($_POST['address_id'] ?? null, FILTER_VALIDATE_INT);

            if (!$addressID) {
                $_SESSION['error'] = 'Please select a delivery address';
                header('Location: /agri_system/public/item-handling/checkout');
                exit;
            }

            // Get the selected address
            require_once __DIR__ . '/../models/UserAddress.php';
            $addressModel = new UserAddress();
            $selectedAddress = $addressModel->getAddress($addressID, $buyerID);

            if (!$selectedAddress) {
                $_SESSION['error'] = 'Invalid address selected';
                header('Location: /agri_system/public/item-handling/checkout');
                exit;
            }

            $orderData = [
                'delivery_address' => $selectedAddress['address'],
                'delivery_municipality' => $selectedAddress['municipality'],
                'delivery_province' => $selectedAddress['province'],
                'delivery_postal_code' => $selectedAddress['postal_code'],
                'payment_method' => filter_var($_POST['payment_method'] ?? 'cod', FILTER_SANITIZE_STRING),
                'notes' => filter_var($_POST['notes'] ?? null, FILTER_SANITIZE_STRING)
            ];

            // Validate payment method
            if (!in_array($orderData['payment_method'], ['cod', 'gcash', 'paymaya'])) {
                $_SESSION['error'] = 'Invalid payment method';
                header('Location: /agri_system/public/item-handling/checkout');
                exit;
            }

            // Calculate total from cart
            $totalAmount = $this->cartModel->getCartTotal($buyerID);
            $orderData['total_amount'] = $totalAmount;

            // Flatten cart items for order creation
            $flatCartItems = [];
            foreach ($cartItems as $shop) {
                foreach ($shop['items'] as $item) {
                    $flatCartItems[] = $item;
                }
            }

            // Create order
            $result = $this->ordersModel->createOrder($buyerID, $orderData, $flatCartItems);

            if ($result['success']) {
                // Regenerate CSRF token after order
                CSRF::regenerateToken();

                $_SESSION['success'] = 'Order placed successfully! Waiting for LGU pickup and delivery.';
                header('Location: /agri_system/public/profile/buyer/orders?order=' . $result['orderID']);
            } else {
                $_SESSION['error'] = $result['message'];
                header('Location: /agri_system/public/item-handling/checkout');
            }
            exit;
        } catch (Exception $e) {
            error_log("Order placement error: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to place order. Please try again.';
            header('Location: /agri_system/public/item-handling/checkout');
            exit;
        }
    }

    // ==================== BUYER: VIEW ORDERS ====================

    public function getBuyerOrders($buyerID, $limit = 10, $offset = 0)
    {
        return $this->ordersModel->getOrdersByBuyer($buyerID, $limit, $offset);
    }

    public function getOrderDetails($orderID, $userID, $role)
    {
        $order = $this->ordersModel->getOrderById($orderID);

        if (!$order) {
            return null;
        }

        // Verify access rights
        if ($role === 'buyer' && $order['buyerID'] != $userID) {
            return null;
        }

        if ($role === 'seller') {
            $hasAccess = false;
            foreach ($order['items'] as $item) {
                if ($item['sellerID'] == $userID) {
                    $hasAccess = true;
                    break;
                }
            }
            if (!$hasAccess) {
                return null;
            }
        }

        return $order;
    }

    // ==================== BUYER: CANCEL ORDER ====================

    public function cancelOrder()
    {
        // CSRF Validation
        if (!CSRF::validateRequest()) {
            CSRF::handleFailure(false);
            return;
        }

        if (!$this->isLoggedIn()) {
            $_SESSION['error'] = 'Please login first';
            header('Location: /agri_system/public/auth/login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = 'Invalid request';
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
        }

        try {
            $orderID = filter_var($_POST['order_id'], FILTER_VALIDATE_INT);
            $reason = filter_var($_POST['cancellation_reason'] ?? 'Buyer requested cancellation', FILTER_SANITIZE_STRING);

            if (!$orderID) {
                $_SESSION['error'] = 'Invalid order ID';
                header('Location: /agri_system/public/profile/buyer/orders');
                exit;
            }

            $order = $this->ordersModel->getOrderById($orderID);

            if (!$order) {
                $_SESSION['error'] = 'Order not found';
                header('Location: /agri_system/public/profile/buyer/orders');
                exit;
            }

            // Verify ownership
            if ($order['buyerID'] != $_SESSION['user_id']) {
                $_SESSION['error'] = 'Unauthorized access';
                header('Location: /agri_system/public/profile/buyer/orders');
                exit;
            }

            // Can only cancel pending orders
            if ($order['order_status'] !== 'pending') {
                $_SESSION['error'] = 'Cannot cancel order that is already being processed';
                header('Location: /agri_system/public/profile/buyer/orders');
                exit;
            }

            $result = $this->ordersModel->cancelOrder($orderID, $reason);

            if ($result) {
                CSRF::regenerateToken();
                $_SESSION['success'] = 'Order cancelled successfully';
            } else {
                $_SESSION['error'] = 'Failed to cancel order';
            }

            header('Location: /agri_system/public/profile/buyer/orders');
            exit;
        } catch (Exception $e) {
            error_log("Cancel order error: " . $e->getMessage());
            $_SESSION['error'] = 'An error occurred';
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
        }
    }

    // ==================== ADMIN: LGU PAYMENT PROCESSING ====================

    public function confirmPaymentReceived()
    {
        // CSRF Validation
        if (!CSRF::validateRequest()) {
            CSRF::handleFailure(false);
            return;
        }

        // Check admin authorization
        if (!$this->isLoggedIn() || $_SESSION['user_role'] !== 'admin') {
            $_SESSION['error'] = 'Unauthorized access';
            header('Location: /agri_system/public/auth/login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = 'Invalid request';
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
        }

        try {
            $orderID = filter_var($_POST['order_id'], FILTER_VALIDATE_INT);

            if (!$orderID) {
                $_SESSION['error'] = 'Invalid order ID';
                header('Location: /agri_system/public/profile/admin/orders');
                exit;
            }

            $order = $this->ordersModel->getOrderById($orderID);

            if (!$order) {
                $_SESSION['error'] = 'Order not found';
                header('Location: /agri_system/public/profile/admin/orders');
                exit;
            }

            if ($order['payment_received_by_lgu_at']) {
                $_SESSION['error'] = 'Payment already processed';
                header('Location: /agri_system/public/profile/admin/orders');
                exit;
            }

            $result = $this->ordersModel->markPaymentReceivedByLGU($orderID);

            if ($result) {
                CSRF::regenerateToken();
                $_SESSION['success'] = 'Payment confirmed! Seller balance has been credited.';
            } else {
                $_SESSION['error'] = 'Failed to confirm payment';
            }

            header('Location: /agri_system/public/profile/admin/orders');
            exit;
        } catch (Exception $e) {
            error_log("Payment confirmation error: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to process payment';
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
        }
    }

    // ==================== ADMIN: UPDATE ORDER STATUS ====================

    public function updateOrderStatus()
    {
        // CSRF Validation
        if (!CSRF::validateRequest()) {
            CSRF::handleFailure(false);
            return;
        }

        if (!$this->isLoggedIn() || $_SESSION['user_role'] !== 'admin') {
            $_SESSION['error'] = 'Unauthorized access';
            header('Location: /agri_system/public/auth/login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = 'Invalid request';
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
        }

        try {
            $orderID = filter_var($_POST['order_id'], FILTER_VALIDATE_INT);
            $status = filter_var($_POST['order_status'], FILTER_SANITIZE_STRING);

            if (!$orderID) {
                $_SESSION['error'] = 'Invalid order ID';
                header('Location: ' . $_SERVER['HTTP_REFERER']);
                exit;
            }

            // Validate status
            $validStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
            if (!in_array($status, $validStatuses)) {
                $_SESSION['error'] = 'Invalid order status';
                header('Location: ' . $_SERVER['HTTP_REFERER']);
                exit;
            }

            $result = $this->ordersModel->updateOrderStatus($orderID, $status);

            if ($result) {
                CSRF::regenerateToken();
                $_SESSION['success'] = 'Order status updated successfully';
            } else {
                $_SESSION['error'] = 'Failed to update order status';
            }

            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
        } catch (Exception $e) {
            error_log("Update order status error: " . $e->getMessage());
            $_SESSION['error'] = 'An error occurred';
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
        }
    }

    // ==================== SELLER: VIEW ORDERS ====================

    public function getSellerOrders($sellerID, $limit = 10, $offset = 0)
    {
        return $this->ordersModel->getOrdersBySeller($sellerID, $limit, $offset);
    }

    // ==================== ADMIN: VIEW ALL ORDERS ====================

    public function getAllOrders($status = null, $limit = 50, $offset = 0)
    {
        return $this->ordersModel->getAllOrders($status, $limit, $offset);
    }

    // ==================== ORDER STATISTICS ====================

    public function getOrderStats($userID, $role)
    {
        return $this->ordersModel->getOrderStats($userID, $role);
    }

    // ==================== UTILITY METHODS ====================

    public function formatOrderStatus($status)
    {
        $statusLabels = [
            'pending' => ['label' => 'Pending', 'class' => 'warning'],
            'processing' => ['label' => 'Processing', 'class' => 'info'],
            'shipped' => ['label' => 'Shipped', 'class' => 'primary'],
            'delivered' => ['label' => 'Delivered', 'class' => 'success'],
            'cancelled' => ['label' => 'Cancelled', 'class' => 'danger']
        ];

        return $statusLabels[$status] ?? ['label' => 'Unknown', 'class' => 'secondary'];
    }

    public function formatDeliveryStatus($status)
    {
        $statusLabels = [
            'pending_pickup' => ['label' => 'Waiting for Pickup', 'class' => 'warning'],
            'picked_up' => ['label' => 'Picked Up by LGU', 'class' => 'info'],
            'in_transit' => ['label' => 'Out for Delivery', 'class' => 'primary'],
            'delivered' => ['label' => 'Delivered', 'class' => 'success'],
            'failed' => ['label' => 'Delivery Failed', 'class' => 'danger']
        ];

        return $statusLabels[$status] ?? ['label' => 'Unknown', 'class' => 'secondary'];
    }

    public function formatPaymentMethod($method)
    {
        $methods = [
            'cod' => 'Cash on Delivery',
            'gcash' => 'GCash',
            'paymaya' => 'PayMaya'
        ];

        return $methods[$method] ?? 'Unknown';
    }

    private function isLoggedIn()
    {
        return isset($_SESSION['user_id']) &&
            isset($_SESSION['logged_in']) &&
            $_SESSION['logged_in'] === true;
    }

    public function getDeliveryTimeline($order)
    {
        $timeline = [
            'order_placed' => [
                'status' => 'completed',
                'date' => $order['order_date'],
                'label' => 'Order Placed'
            ],
            'lgu_pickup' => [
                'status' => $order['lgu_delivery_status'] !== 'pending_pickup' ? 'completed' : 'pending',
                'date' => $order['picked_up_at'] ?? null,
                'label' => 'Picked Up by LGU'
            ],
            'out_for_delivery' => [
                'status' => $order['lgu_delivery_status'] === 'in_transit' || $order['lgu_delivery_status'] === 'delivered' ? 'completed' : 'pending',
                'date' => $order['rider_assigned_at'] ?? null,
                'label' => 'Out for Delivery'
            ],
            'delivered' => [
                'status' => $order['lgu_delivery_status'] === 'delivered' ? 'completed' : 'pending',
                'date' => $order['delivered_at'] ?? null,
                'label' => 'Delivered'
            ]
        ];

        return $timeline;
    }
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller = new OrderController();
    $action = filter_var($_POST['action'] ?? '', FILTER_SANITIZE_STRING);

    switch ($action) {
        case 'place_order':
            $controller->placeOrder();
            break;
        case 'cancel_order':
            $controller->cancelOrder();
            break;
        case 'confirm_payment':
            $controller->confirmPaymentReceived();
            break;
        case 'update_status':
            $controller->updateOrderStatus();
            break;
        default:
            $_SESSION['error'] = 'Invalid action';
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
    }
}
