<?php
// app/controllers/SellerApplications.php
// Handle Seller Application Submissions
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../models/SellerApplication.php';
require_once __DIR__ . '/../helpers/csrf.php';

class SellerApplicationController 
{
    private $applicationModel;

    public function __construct() 
    {
        $this->applicationModel = new SellerApplication();
    }

    public function getMyApplication() 
    {
        if (!$this->isLoggedIn()) {
            return null;
        }

        $userID = $_SESSION['user_id'];
        return $this->applicationModel->getApplicationByUser($userID);
    }

    public function showApplicationForm() 
    {
        if (!$this->isLoggedIn() || $_SESSION['user_role'] !== 'buyer') {
            $_SESSION['error'] = 'Only buyers can apply to become sellers';
            header('Location: ' . BASE_URL . 'marketplace');
            exit;
        }

        $userID = $_SESSION['user_id'];

        $canApply = $this->applicationModel->canUserApply($userID);

        if (!$canApply['can_apply']) {
            $_SESSION['error'] = $canApply['message'];
            header('Location: ' . BASE_URL . 'marketplace');
            exit;
        }

        $existingApp = $this->applicationModel->getApplicationByUser($userID);

        include __DIR__ . '/../../public/marketplace/apply-seller.php';
    }

    // application submissions
    public function submitApplication() 
    {
        if (!CSRF::validateRequest()) {
            CSRF::handleFailure(false);
            return;
        }

        if (!$this->isLoggedIn() || $_SESSION['user_role'] !== 'buyer') {
            $_SESSION['error'] = 'Unauthorized access';
            header('Location: ' . BASE_URL . 'auth/login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = 'Invalid request';
            header('Location: ' . BASE_URL . 'profile/apply');
            exit;
        }

        $userID = $_SESSION['user_id'];

        try {
            $requiredFields = ['business_name', 'business_address'];
            foreach ($requiredFields as $field) {
                if (empty($_POST[$field])) {
                    $_SESSION['error'] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
                    header('Location: ' . BASE_URL . 'profile/apply');
                    exit;
                }
            }

            $data = [
                'business_name' => filter_var($_POST['business_name'], FILTER_SANITIZE_STRING),
                'business_address' => filter_var($_POST['business_address'], FILTER_SANITIZE_STRING)
            ];

            $permitFile = null;
            if (isset($_FILES['business_permit']) && $_FILES['business_permit']['error'] === UPLOAD_ERR_OK) {
                $permitFile = $_FILES['business_permit'];
            }

            $result = $this->applicationModel->submitApplication($userID, $data, $permitFile);

            if ($result['success']) {
                CSRF::regenerateToken();
                $_SESSION['success'] = $result['message'];
                header('Location: ' . BASE_URL . 'profile/apply');
            } else {
                $_SESSION['error'] = $result['message'];
                header('Location: ' . BASE_URL . 'profile/apply');
            }
            exit;

        } catch (Exception $e) {
            error_log("Application submission error: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to submit application. Please try again.';
            header('Location: ' . BASE_URL . 'profile/apply');
            exit;
        }
    }

    public function checkApplicationStatus() 
    {
        header('Content-Type: application/json');

        if (!$this->isLoggedIn()) {
            echo json_encode(['can_apply' => false, 'message' => 'Not logged in']);
            exit;
        }

        $userID = $_SESSION['user_id'];
        $result = $this->applicationModel->canUserApply($userID);

        echo json_encode($result);
        exit;
    }

    private function isLoggedIn() 
    {
        return isset($_SESSION['user_id']) && 
               isset($_SESSION['logged_in']) && 
               $_SESSION['logged_in'] === true;
    }
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller = new SellerApplicationController();
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'submit_application':
            $controller->submitApplication();
            break;
        default:
            $_SESSION['error'] = 'Invalid action';
            header('Location: ' . BASE_URL . 'profile/apply');
            exit;
    }
}