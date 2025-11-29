<?php
// app/controllers/WishlistController.php
// SECURED: Wishlist Management with CSRF + Rate Limiting

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../models/Cart.php';            // contains Cart and Wishlist classes
require_once __DIR__ . '/../helpers/csrf.php';
require_once __DIR__ . '/../helpers/RateLimiter.php';
require_once __DIR__ . '/../config/database.php';

class WishlistController
{
    private $wishlistModel;

    public function __construct()
    {
        $this->wishlistModel = new Wishlist();
    }

    private function jsonResponse(array $payload, int $httpStatus = 200)
    {
        http_response_code($httpStatus);
        header('Content-Type: application/json; charset=utf-8');
        // If cross-origin with credentials is needed, set Access-Control-Allow-Origin to exact origin
        header('Access-Control-Allow-Credentials: true');
        echo json_encode($payload);
        exit;
    }

    public function handleAjaxRequest()
    {
        // Rate limiting
        $rateCheck = RateLimiter::checkApiRate('wishlist');
        if (!$rateCheck['allowed']) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Too many requests. Please slow down.',
                'error_code' => 'RATE_LIMIT_EXCEEDED'
            ], 429);
        }

        // Authentication
        if (!$this->isLoggedIn()) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Please login first',
                'redirect' => BASE_URL . 'auth/login'
            ], 401);
        }

        // Role check (compatibility for both session keys)
        $role = $_SESSION['role'] ?? $_SESSION['user_role'] ?? null;
        if ($role !== 'buyer') {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Only buyers can access wishlist'
            ], 403);
        }

        // Decode input
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            // fallback to POST/GET
            $data = $_POST + $_GET;
        }

        $action = $data['action'] ?? null;
        if (!$action) {
            $this->jsonResponse(['success' => false, 'message' => 'No action specified'], 400);
        }

        // State-changing actions require CSRF and AJAX
        $stateActions = ['add', 'remove', 'toggle', 'move_to_cart', 'clear_all', 'add_all_to_cart'];
        if (in_array($action, $stateActions, true)) {
            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                     strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
            if (!$isAjax) {
                $this->jsonResponse(['success' => false, 'message' => 'AJAX request required for this action'], 400);
            }

            if (!CSRF::validateJsonRequest()) {
                // CSRF::handleFailure(true) might exit. If not, return JSON.
                if (function_exists('CSRF::handleFailure')) {
                    CSRF::handleFailure(true);
                }
                $this->jsonResponse(['success' => false, 'message' => 'CSRF validation failed'], 403);
            }
        }

        $buyerID = $_SESSION['user_id'];

        try {
            switch ($action) {
                case 'add':
                    $this->addToWishlist($buyerID, $data);
                    break;

                case 'remove':
                    $this->removeFromWishlist($buyerID, $data);
                    break;

                case 'toggle':
                    $this->toggleWishlist($buyerID, $data);
                    break;

                case 'move_to_cart':
                    $this->moveToCart($buyerID, $data);
                    break;

                case 'check':
                    $this->checkWishlist($buyerID, $data);
                    break;

                case 'get_count':
                    $this->getWishlistCount($buyerID);
                    break;

                case 'get_items':
                    $this->getWishlistItems($buyerID);
                    break;

                case 'clear_all':
                    $this->clearAllWishlist($buyerID);
                    break;

                case 'add_all_to_cart':
                    $this->addAllToCart($buyerID);
                    break;

                default:
                    $this->jsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
                    break;
            }
        } catch (Exception $e) {
            error_log("WishlistController error: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'An error occurred', 'error' => $e->getMessage()], 500);
        }
    }

    private function addToWishlist($buyerID, $data)
    {
        $productID = isset($data['productID']) ? filter_var($data['productID'], FILTER_VALIDATE_INT) : null;
        if ($productID === false || $productID === null) {
            $this->jsonResponse(['success' => false, 'message' => 'Product ID required'], 400);
        }

        $result = $this->wishlistModel->addToWishlist($buyerID, (int)$productID);
        $count = $this->wishlistModel->getWishlistCount($buyerID);

        $this->jsonResponse([
            'success' => (bool)$result['success'],
            'message' => $result['message'] ?? 'Operation completed',
            'wishlistCount' => $count,
            'inWishlist' => (bool)$result['success'],
            'csrf_token' => CSRF::generateToken()
        ], $result['success'] ? 200 : 400);
    }

    private function removeFromWishlist($buyerID, $data)
    {
        // Accept wishlistID or productID
        $productID = null;

        if (!empty($data['wishlistID'])) {
            // look up wishlistID -> productID (direct DB because model may not have helper)
            $db = new Database();
            $conn = $db->connect();
            $stmt = $conn->prepare("SELECT productID FROM wishlist WHERE wishlistID = ? AND buyerID = ? LIMIT 1");
            $stmt->execute([(int)$data['wishlistID'], $buyerID]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) $productID = (int)$row['productID'];
        }

        if (!$productID) {
            $productID = isset($data['productID']) ? filter_var($data['productID'], FILTER_VALIDATE_INT) : null;
        }

        if ($productID === false || $productID === null) {
            $this->jsonResponse(['success' => false, 'message' => 'Product ID required'], 400);
        }

        $result = $this->wishlistModel->removeFromWishlist($buyerID, (int)$productID);
        $count = $this->wishlistModel->getWishlistCount($buyerID);

        $this->jsonResponse([
            'success' => (bool)$result['success'],
            'message' => $result['message'] ?? 'Operation completed',
            'wishlistCount' => $count,
            'csrf_token' => CSRF::generateToken()
        ], $result['success'] ? 200 : 400);
    }

    private function toggleWishlist($buyerID, $data)
    {
        $productID = isset($data['productID']) ? filter_var($data['productID'], FILTER_VALIDATE_INT) : null;
        if ($productID === false || $productID === null) {
            $this->jsonResponse(['success' => false, 'message' => 'Product ID required'], 400);
        }

        $inWishlist = $this->wishlistModel->isInWishlist($buyerID, (int)$productID);

        if ($inWishlist) {
            $result = $this->wishlistModel->removeFromWishlist($buyerID, (int)$productID);
            $inWishlist = false;
        } else {
            $result = $this->wishlistModel->addToWishlist($buyerID, (int)$productID);
            $inWishlist = (bool)$result['success'];
        }

        $count = $this->wishlistModel->getWishlistCount($buyerID);

        $this->jsonResponse([
            'success' => (bool)$result['success'],
            'message' => $result['message'] ?? 'Operation completed',
            'wishlistCount' => $count,
            'inWishlist' => $inWishlist,
            'csrf_token' => CSRF::generateToken()
        ], $result['success'] ? 200 : 400);
    }

    private function moveToCart($buyerID, $data)
    {
        // Accept wishlistID or productID
        $productID = null;
        $wishlistID = $data['wishlistID'] ?? null;

        if (!empty($wishlistID)) {
            $db = new Database();
            $conn = $db->connect();
            $stmt = $conn->prepare("SELECT productID FROM wishlist WHERE wishlistID = ? AND buyerID = ? LIMIT 1");
            $stmt->execute([(int)$wishlistID, $buyerID]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) $productID = (int)$row['productID'];
        }

        if (!$productID) {
            $productID = isset($data['productID']) ? filter_var($data['productID'], FILTER_VALIDATE_INT) : null;
        }

        if ($productID === false || $productID === null) {
            $this->jsonResponse(['success' => false, 'message' => 'Product ID required'], 400);
        }

        $result = $this->wishlistModel->moveToCart($buyerID, (int)$productID);

        // Get updated counts
        $wishlistCount = $this->wishlistModel->getWishlistCount($buyerID);
        $cartModel = new Cart();
        $cartCount = $cartModel->getCartCount($buyerID);

        $this->jsonResponse([
            'success' => (bool)$result['success'],
            'message' => $result['message'] ?? 'Operation completed',
            'wishlistCount' => $wishlistCount,
            'cartCount' => $cartCount,
            'csrf_token' => CSRF::generateToken()
        ], $result['success'] ? 200 : 400);
    }

    private function checkWishlist($buyerID, $data)
    {
        $productID = isset($data['productID']) ? filter_var($data['productID'], FILTER_VALIDATE_INT) : null;
        if ($productID === false || $productID === null) {
            $this->jsonResponse(['success' => false, 'message' => 'Product ID required'], 400);
        }

        $inWishlist = $this->wishlistModel->isInWishlist($buyerID, (int)$productID);

        $this->jsonResponse(['success' => true, 'inWishlist' => $inWishlist]);
    }

    private function getWishlistCount($buyerID)
    {
        $count = $this->wishlistModel->getWishlistCount($buyerID);
        $this->jsonResponse(['success' => true, 'wishlistCount' => $count]);
    }

    private function getWishlistItems($buyerID)
    {
        $items = $this->wishlistModel->getWishlistItems($buyerID);
        $count = $this->wishlistModel->getWishlistCount($buyerID);
        $this->jsonResponse(['success' => true, 'items' => $items, 'count' => $count]);
    }

    private function clearAllWishlist($buyerID)
    {
        // Use model if method exists; otherwise delete directly
        if (method_exists($this->wishlistModel, 'clearAll')) {
            $ok = $this->wishlistModel->clearAll($buyerID);
        } else {
            $db = new Database();
            $conn = $db->connect();
            $stmt = $conn->prepare("DELETE FROM wishlist WHERE buyerID = ?");
            $ok = $stmt->execute([$buyerID]);
        }

        $this->jsonResponse([
            'success' => (bool)$ok,
            'message' => $ok ? 'Wishlist cleared' : 'Failed to clear wishlist',
            'csrf_token' => CSRF::generateToken()
        ], $ok ? 200 : 400);
    }

    private function addAllToCart($buyerID)
    {
        $items = $this->wishlistModel->getWishlistItems($buyerID);
        $cartModel = new Cart();

        $success = 0;
        $fails = 0;

        // Try to add each available item to cart (1 unit). If add succeeds remove from wishlist.
        foreach ($items as $item) {
            if (!empty($item['is_available']) && $item['is_available'] && !empty($item['stock_quantity']) && $item['stock_quantity'] > 0) {
                $result = $cartModel->addToCart($buyerID, (int)$item['productID'], 1);
                if (!empty($result['success'])) {
                    $this->wishlistModel->removeFromWishlist($buyerID, (int)$item['productID']);
                    $success++;
                } else {
                    $fails++;
                }
            } else {
                $fails++;
            }
        }

        $this->jsonResponse([
            'success' => true,
            'added' => $success,
            'failed' => $fails,
            'csrf_token' => CSRF::generateToken()
        ]);
    }

    private function isLoggedIn()
    {
        return isset($_SESSION['user_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
}

// Only handle AJAX direct requests
if (php_sapi_name() !== 'cli' && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {

    $controller = new WishlistController();
    $controller->handleAjaxRequest();
}
