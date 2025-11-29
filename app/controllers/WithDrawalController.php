<?php
// app/controllers/WithdrawalController.php
// SECURED: Seller Withdrawal Management with CSRF + Rate Limiting
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../models/Withdrawal.php';
require_once __DIR__ . '/../models/Shop.php';
require_once __DIR__ . '/../helpers/csrf.php';
require_once __DIR__ . '/../helpers/RateLimiter.php';

class WithdrawalController 
{
    private $withdrawalModel;
    private $shopModel;

    public function __construct() 
    {
        $this->withdrawalModel = new Withdrawal();
        $this->shopModel = new Shop();
    }

    // ==================== SELLER: REQUEST WITHDRAWAL ====================

    /**
     * Seller requests withdrawal (cash or ATM) with CSRF + Rate Limiting
     */
    public function requestWithdrawal() 
    {
        // CSRF Validation
        if (!CSRF::validateRequest()) {
            CSRF::handleFailure(false);
            return;
        }

        // Check if seller is logged in
        if (!$this->isLoggedIn() || $_SESSION['user_role'] !== 'seller') {
            $_SESSION['error'] = 'Unauthorized access';
            header('Location: /agri_system/public/auth/login');
            exit;
        }

        // Rate limit withdrawal requests (5 requests per hour per seller)
        $rateCheck = RateLimiter::checkApiRate('withdrawal_request_' . $_SESSION['user_id']);
        if (!$rateCheck['allowed']) {
            $_SESSION['error'] = 'Too many withdrawal requests. Please try again later.';
            header('Location: /agri_system/public/profile/seller/withdrawals');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = 'Invalid request method';
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
        }

        try {
            $userID = $_SESSION['user_id'];

            // Get seller's shop
            $db = new Database();
            $conn = $db->connect();
            
            $stmt = $conn->prepare("SELECT sellerID FROM seller_profiles WHERE userID = ?");
            $stmt->execute([$userID]);
            $seller = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$seller) {
                $_SESSION['error'] = 'Seller profile not found';
                header('Location: ' . $_SERVER['HTTP_REFERER']);
                exit;
            }

            $shop = $this->shopModel->getShopBySeller($seller['sellerID']);

            if (!$shop) {
                $_SESSION['error'] = 'Shop not found';
                header('Location: ' . $_SERVER['HTTP_REFERER']);
                exit;
            }

            // Get form data with sanitization
            $amount = floatval($_POST['amount']);
            $method = filter_var($_POST['withdrawal_method'] ?? 'cash', FILTER_SANITIZE_STRING);
            $atmCard = filter_var($_POST['atm_card_number'] ?? null, FILTER_SANITIZE_STRING);

            // Validate amount
            if ($amount <= 0) {
                $_SESSION['error'] = 'Invalid withdrawal amount';
                header('Location: ' . $_SERVER['HTTP_REFERER']);
                exit;
            }

            // Validate withdrawal method
            if (!in_array($method, ['cash', 'atm'])) {
                $_SESSION['error'] = 'Invalid withdrawal method';
                header('Location: ' . $_SERVER['HTTP_REFERER']);
                exit;
            }

            // Create withdrawal request
            $result = $this->withdrawalModel->createRequest(
                $shop['shopID'],
                $amount,
                $method,
                $atmCard
            );

            if ($result['success']) {
                // Regenerate CSRF token after successful request
                CSRF::regenerateToken();
                
                // Log withdrawal request
                error_log("Withdrawal requested: ShopID={$shop['shopID']}, Amount={$amount}, Method={$method}");
                
                $_SESSION['success'] = $result['message'];
            } else {
                $_SESSION['error'] = $result['message'];
            }

        } catch (Exception $e) {
            error_log("Withdrawal request error: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to process withdrawal request';
        }

        header('Location: /agri_system/public/profile/seller/withdrawals');
        exit;
    }

    // ==================== ADMIN: APPROVE WITHDRAWAL ====================

    /**
     * Admin approves withdrawal with CSRF validation
     */
    public function approveWithdrawal() 
    {
        // CSRF Validation
        if (!CSRF::validateRequest()) {
            CSRF::handleFailure(false);
            return;
        }

        // Check if admin is logged in
        if (!$this->isLoggedIn() || $_SESSION['user_role'] !== 'admin') {
            $_SESSION['error'] = 'Unauthorized access';
            header('Location: /agri_system/public/auth/login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = 'Invalid request method';
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
        }

        try {
            $withdrawalID = intval($_POST['withdrawal_id']);
            $adminID = $_SESSION['user_id'];
            $notes = filter_var($_POST['notes'] ?? null, FILTER_SANITIZE_STRING);

            $result = $this->withdrawalModel->approveWithdrawal($withdrawalID, $adminID, $notes);

            if ($result['success']) {
                // Regenerate CSRF token
                CSRF::regenerateToken();
                
                // Log approval
                error_log("Withdrawal approved: WithdrawalID={$withdrawalID}, AdminID={$adminID}");
                
                $_SESSION['success'] = $result['message'];
            } else {
                $_SESSION['error'] = $result['message'];
            }

        } catch (Exception $e) {
            error_log("Withdrawal approval error: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to approve withdrawal';
        }

        header('Location: /agri_system/public/profile/admin/withdrawals');
        exit;
    }

    // ==================== ADMIN: REJECT WITHDRAWAL ====================

    /**
     * Admin rejects withdrawal request with CSRF
     */
    public function rejectWithdrawal() 
    {
        // CSRF Validation
        if (!CSRF::validateRequest()) {
            CSRF::handleFailure(false);
            return;
        }

        // Check if admin is logged in
        if (!$this->isLoggedIn() || $_SESSION['user_role'] !== 'admin') {
            $_SESSION['error'] = 'Unauthorized access';
            header('Location: /agri_system/public/auth/login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = 'Invalid request method';
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
        }

        try {
            $withdrawalID = intval($_POST['withdrawal_id']);
            $adminID = $_SESSION['user_id'];
            $reason = filter_var($_POST['rejection_reason'] ?? 'No reason provided', FILTER_SANITIZE_STRING);

            $result = $this->withdrawalModel->rejectWithdrawal($withdrawalID, $adminID, $reason);

            if ($result['success']) {
                // Regenerate CSRF token
                CSRF::regenerateToken();
                
                // Log rejection
                error_log("Withdrawal rejected: WithdrawalID={$withdrawalID}, AdminID={$adminID}, Reason={$reason}");
                
                $_SESSION['success'] = $result['message'];
            } else {
                $_SESSION['error'] = $result['message'];
            }

        } catch (Exception $e) {
            error_log("Withdrawal rejection error: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to reject withdrawal';
        }

        header('Location: /agri_system/public/profile/admin/withdrawals');
        exit;
    }

    // ==================== ADMIN: SEARCH BY CARD ====================

    /**
     * Admin searches shop by ATM card number with CSRF for AJAX
     */
    public function searchByCard() 
    {
        // Set JSON headers
        header('Content-Type: application/json');

        // Check if admin is logged in
        if (!$this->isLoggedIn() || $_SESSION['user_role'] !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        // CSRF validation for JSON
        if (!CSRF::validateJsonRequest()) {
            echo json_encode([
                'success' => false, 
                'message' => 'Security validation failed',
                'error_code' => 'CSRF_VALIDATION_FAILED'
            ]);
            exit;
        }

        // Rate limit card searches (20 per minute)
        $rateCheck = RateLimiter::checkApiRate('card_search_' . $_SESSION['user_id']);
        if (!$rateCheck['allowed']) {
            echo json_encode([
                'success' => false, 
                'message' => 'Too many search requests',
                'error_code' => 'RATE_LIMIT_EXCEEDED'
            ]);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            exit;
        }

        try {
            // Get JSON input
            $rawInput = file_get_contents('php://input');
            $data = json_decode($rawInput, true);

            $cardNumber = filter_var($data['card_number'] ?? '', FILTER_SANITIZE_STRING);

            if (empty($cardNumber)) {
                echo json_encode(['success' => false, 'message' => 'Card number required']);
                exit;
            }

            $shop = $this->shopModel->getShopByCardNumber($cardNumber);

            if ($shop) {
                echo json_encode([
                    'success' => true,
                    'shop' => [
                        'shopID' => $shop['shopID'],
                        'shop_name' => $shop['shop_name'],
                        'business_name' => $shop['business_name'],
                        'seller_name' => $shop['seller_name'],
                        'balance' => number_format($shop['balance'], 2),
                        'balance_raw' => $shop['balance'],
                        'total_earned' => number_format($shop['total_earned'], 2),
                        'total_withdrawn' => number_format($shop['total_withdrawn'], 2),
                        'atm_card_number' => $shop['atm_card_number']
                    ],
                    'csrf_token' => CSRF::generateToken()
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Card number not found or not issued yet'
                ]);
            }

        } catch (Exception $e) {
            error_log("Card search error: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Search failed'
            ]);
        }
        exit;
    }

    // ==================== ADMIN: PROCESS CASH WITHDRAWAL ====================

    /**
     * Admin processes cash withdrawal directly with CSRF
     */
    public function processCashWithdrawal() 
    {
        // Set JSON headers
        header('Content-Type: application/json');

        // Check if admin is logged in
        if (!$this->isLoggedIn() || $_SESSION['user_role'] !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        // CSRF validation for JSON
        if (!CSRF::validateJsonRequest()) {
            echo json_encode([
                'success' => false, 
                'message' => 'Security validation failed',
                'error_code' => 'CSRF_VALIDATION_FAILED'
            ]);
            exit;
        }

        // Rate limit cash withdrawals (10 per hour)
        $rateCheck = RateLimiter::checkApiRate('cash_withdrawal_' . $_SESSION['user_id']);
        if (!$rateCheck['allowed']) {
            echo json_encode([
                'success' => false, 
                'message' => 'Too many withdrawal requests',
                'error_code' => 'RATE_LIMIT_EXCEEDED'
            ]);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            exit;
        }

        try {
            // Get JSON or POST data
            $shopID = intval($_POST['shop_id'] ?? 0);
            $amount = floatval($_POST['amount'] ?? 0);
            $cardNumber = filter_var($_POST['card_number'] ?? null, FILTER_SANITIZE_STRING);
            $adminID = $_SESSION['user_id'];

            // Validate inputs
            if (!$shopID || $amount <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid shop or amount']);
                exit;
            }

            // Create and immediately approve withdrawal request
            $createResult = $this->withdrawalModel->createRequest($shopID, $amount, 'cash', $cardNumber);

            if (!$createResult['success']) {
                echo json_encode($createResult);
                exit;
            }

            // Approve withdrawal immediately
            $approveResult = $this->withdrawalModel->approveWithdrawal(
                $createResult['withdrawal_id'], 
                $adminID, 
                'Cash withdrawal processed at LGU office'
            );

            // Log cash withdrawal
            error_log("Cash withdrawal processed: ShopID={$shopID}, Amount={$amount}, AdminID={$adminID}");

            // Add new CSRF token to response
            $approveResult['csrf_token'] = CSRF::generateToken();

            echo json_encode($approveResult);

        } catch (Exception $e) {
            error_log("Process cash withdrawal error: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Failed to process withdrawal'
            ]);
        }
        exit;
    }

    // ==================== SELLER: GET WITHDRAWAL HISTORY ====================

    /**
     * Get seller's withdrawal history
     */
    public function getSellerWithdrawals($shopID, $limit = 20) 
    {
        return $this->withdrawalModel->getWithdrawalsByShop($shopID, $limit);
    }

    // ==================== ADMIN: GET PENDING WITHDRAWALS ====================

    /**
     * Get all pending withdrawal requests (admin)
     */
    public function getPendingWithdrawals() 
    {
        return $this->withdrawalModel->getPendingWithdrawals();
    }

    // ==================== UTILITY METHODS ====================

    /**
     * Format withdrawal status badge
     */
    public function getStatusBadge($status) 
    {
        $badges = [
            'pending' => ['label' => 'Pending', 'class' => 'warning'],
            'approved' => ['label' => 'Approved', 'class' => 'info'],
            'completed' => ['label' => 'Completed', 'class' => 'success'],
            'rejected' => ['label' => 'Rejected', 'class' => 'danger']
        ];

        return $badges[$status] ?? ['label' => 'Unknown', 'class' => 'secondary'];
    }

    /**
     * Format withdrawal method
     */
    public function formatMethod($method) 
    {
        $methods = [
            'cash' => 'Cash (At LGU Office)',
            'atm' => 'ATM Card'
        ];

        return $methods[$method] ?? 'Unknown';
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
}

// Handle requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller = new WithdrawalController();
    $action = $_POST['action'] ?? '';

    // Check for JSON requests (search_by_card, process_cash_withdrawal)
    if (empty($action)) {
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);
        $action = $data['action'] ?? '';
    }

    switch ($action) {
        case 'request_withdrawal':
            $controller->requestWithdrawal();
            break;
        case 'approve_withdrawal':
            $controller->approveWithdrawal();
            break;
        case 'reject_withdrawal':
            $controller->rejectWithdrawal();
            break;
        case 'search_by_card':
            $controller->searchByCard();
            break;
        case 'process_cash_withdrawal':
            $controller->processCashWithdrawal();
            break;
        default:
            $_SESSION['error'] = 'Invalid action';
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
    }
}