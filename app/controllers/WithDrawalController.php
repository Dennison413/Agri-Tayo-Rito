<?php
// app/controllers/WithDrawalController.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config/config.php';
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

    // seller: request withdrawal
    public function requestWithdrawal() 
    {
        header('Content-Type: application/json');

        if (!$this->isLoggedIn() || $_SESSION['user_role'] !== 'seller') {
            echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
            exit;
        }

        if (!CSRF::validateRequest()) {
            echo json_encode(['success' => false, 'message' => 'Security validation failed']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            exit;
        }

        try {
            $userID = $_SESSION['user_id'];

            $db = new Database();
            $conn = $db->connect();
            
            $stmt = $conn->prepare("SELECT sellerID FROM seller_profiles WHERE userID = ?");
            $stmt->execute([$userID]);
            $seller = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$seller) {
                echo json_encode(['success' => false, 'message' => 'Seller profile not found']);
                exit;
            }

            $shop = $this->shopModel->getShopBySeller($seller['sellerID']);
            if (!$shop) {
                echo json_encode(['success' => false, 'message' => 'Shop not found']);
                exit;
            }

            $amount = floatval($_POST['amount']);
            $method = filter_var($_POST['withdrawal_method'], FILTER_SANITIZE_STRING);
            $atmCard = filter_var($_POST['atm_card_number'] ?? null, FILTER_SANITIZE_STRING);

            if ($amount <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid amount']);
                exit;
            }

            $result = $this->withdrawalModel->createRequest($shop['shopID'], $amount, $method, $atmCard);

            if ($result['success']) {
                CSRF::regenerateToken();
            }

            echo json_encode($result);

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error processing request']);
        }
        exit;
    }

    // admin: approve withdrawal
    public function approveWithdrawal() 
    {
        if (!CSRF::validateRequest()) {
            $_SESSION['error'] = "Security validation failed";
            header('Location: ' . BASE_URL . 'profile/admin/withdrawals');
            exit;
        }

        if (!$this->isLoggedIn() || $_SESSION['user_role'] !== 'admin') {
            $_SESSION['error'] = "Unauthorized";
            header('Location: ' . BASE_URL . 'auth/login');
            exit;
        }

        try {
            $withdrawalID = intval($_POST['withdrawal_id']);
            $adminID = $_SESSION['user_id'];
            $notes = filter_var($_POST['notes'] ?? '', FILTER_SANITIZE_STRING);

            $result = $this->withdrawalModel->approveWithdrawal($withdrawalID, $adminID, $notes);

            if ($result['success']) {
                CSRF::regenerateToken();
                $_SESSION['success'] = $result['message'];
            } else {
                $_SESSION['error'] = $result['message'];
            }

        } catch (Exception $e) {
            $_SESSION['error'] = "Failed to approve withdrawal";
        }

        header('Location: ' . BASE_URL . 'profile/admin/withdrawals');
        exit;
    }

    // admin: reject withdrawal
    public function rejectWithdrawal() 
    {
        if (!CSRF::validateRequest()) {
            $_SESSION['error'] = "Security validation failed";
            header('Location: ' . BASE_URL . 'profile/admin/withdrawals');
            exit;
        }

        if (!$this->isLoggedIn() || $_SESSION['user_role'] !== 'admin') {
            $_SESSION['error'] = "Unauthorized";
            header('Location: ' . BASE_URL . 'auth/login');
            exit;
        }

        try {
            $withdrawalID = intval($_POST['withdrawal_id']);
            $adminID = $_SESSION['user_id'];
            $reason = filter_var($_POST['rejection_reason'] ?? '', FILTER_SANITIZE_STRING);

            $result = $this->withdrawalModel->rejectWithdrawal($withdrawalID, $adminID, $reason);

            if ($result['success']) {
                CSRF::regenerateToken();
                $_SESSION['success'] = $result['message'];
            } else {
                $_SESSION['error'] = $result['message'];
            }

        } catch (Exception $e) {
            $_SESSION['error'] = "Failed to reject withdrawal";
        }

        header('Location: ' . BASE_URL . 'profile/admin/withdrawals');
        exit;
    }

    // admin: search shop by card number
    public function searchByCard() 
    {
        header('Content-Type: application/json');

        if (!$this->isLoggedIn() || $_SESSION['user_role'] !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $cardNumber = $_POST['card_number'] ?? '';
        $csrfToken = $_POST['csrf_token'] ?? '';

        if (!CSRF::validateToken($csrfToken)) {
            echo json_encode(['success' => false, 'message' => 'Security validation failed']);
            exit;
        }

        if (!$cardNumber) {
            echo json_encode(['success' => false, 'message' => 'Card number required']);
            exit;
        }

        try {
            $shop = $this->shopModel->getShopByCardNumber($cardNumber);
            if (!$shop) {
                echo json_encode(['success' => false, 'message' => 'Card not found']);
                exit;
            }

            echo json_encode([
                'success' => true,
                'shop' => $shop,
                'csrf_token' => CSRF::generateToken()
            ]);

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Search failed']);
        }
        exit;
    }

    // admin: process cash withdrawal
    public function processCashWithdrawal() 
    {
        header('Content-Type: application/json');

        if (!$this->isLoggedIn() || $_SESSION['user_role'] !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!CSRF::validateToken($csrfToken)) {
            echo json_encode(['success' => false, 'message' => 'Security validation failed']);
            exit;
        }

        try {
            $shopID = intval($_POST['shop_id']);
            $amount = floatval($_POST['amount']);

            $create = $this->withdrawalModel->createRequest($shopID, $amount, 'cash', null);

            if (!$create['success']) {
                echo json_encode($create);
                exit;
            }

            $approve = $this->withdrawalModel->approveWithdrawal(
                $create['withdrawal_id'], 
                $_SESSION['user_id'],
                "Cash withdrawal processed"
            );

            echo json_encode($approve);

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Processing failed']);
        }
        exit;
    }

    // admin: get withdrawals by shop (history)
    public function getWithdrawalsByShop()
    {
        header("Content-Type: application/json");

        if (!$this->isLoggedIn() || $_SESSION['user_role'] !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $shopID = intval($_POST['shop_id'] ?? 0);
        $csrf = $_POST['csrf_token'] ?? '';

        if (!CSRF::validateToken($csrf)) {
            echo json_encode(['success' => false, 'message' => 'Security validation failed']);
            exit;
        }

        try {
            $withdrawals = $this->withdrawalModel->getWithdrawalsByShop($shopID);
            $stats = $this->withdrawalModel->getShopWithdrawalStats($shopID);

            echo json_encode([
                'success' => true,
                'withdrawals' => $withdrawals,
                'stats' => $stats,
                'csrf_token' => CSRF::generateToken()
            ]);

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Failed to load history']);
        }
        exit;
    }

    private function isLoggedIn() 
    {
        return !empty($_SESSION['logged_in']);
    }
}

// handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $controller = new WithdrawalController();
    $action = $_POST['action'] ?? null;

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

        case 'get_withdrawals_by_shop': 
            $controller->getWithdrawalsByShop();
            break;

        default:
            header("Content-Type: application/json");
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            exit;
    }
}

?>
