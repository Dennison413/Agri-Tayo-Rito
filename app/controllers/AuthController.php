<?php
// app/controllers/AuthController.php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../helpers/csrf.php';
require_once __DIR__ . '/../helpers/RateLimiter.php';
require_once __DIR__ . '/../helpers/SessionSecurity.php';

class AuthController 
{
    private $userModel;

    public function __construct() 
    {
        $this->userModel = new User();
    }

    // Registration without CSRF check (for new users who don't have a session yet)
    public function register($data) 
    {
        $rateLimitCheck = RateLimiter::checkRegistrationAttempts();
        if (!$rateLimitCheck['allowed']) {
            return [
                'success' => false,
                'message' => "Too many registration attempts. Please try again in {$rateLimitCheck['retry_after']} minutes."
            ];
        }

        $requiredFields = ['email', 'password', 'full_name'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                return [
                    'success' => false,
                    'message' => ucfirst(str_replace('_', ' ', $field)) . ' is required'
                ];
            }
        }
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => 'Invalid email format'
            ];
        }
        if (strlen($data['password']) < 8) {
            return [
                'success' => false,
                'message' => 'Password must be at least 8 characters long'
            ];
        }
        if (isset($data['confirm_password']) && $data['password'] !== $data['confirm_password']) {
            return [
                'success' => false,
                'message' => 'Passwords do not match'
            ];
        }
        $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        $data['role'] = 'buyer';
        try {
            $result = $this->userModel->createUser($data);
            
            if ($result['success']) {
                RateLimiter::recordRegistrationAttempt();
                error_log("New user registered: Email={$data['email']}, UserID={$result['userID']}, IP={$_SERVER['REMOTE_ADDR']}");

                return [
                    'success' => true,
                    'message' => 'Registration successful! Please login.',
                    'userID' => $result['userID']
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $result['message'] ?? 'Registration failed. Please try again.'
                ];
            }
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred during registration'
            ];
        }
    }
    
    public function login($email, $password) 
    {
        if (empty($email) || empty($password)) {
            return [
                'success' => false,
                'message' => 'Email and password are required'
            ];
        }

        $rateLimitCheck = RateLimiter::checkLoginAttempts($email);
        if (!$rateLimitCheck['allowed']) {
            return [
                'success' => false,
                'message' => "Too many login attempts. Please try again in {$rateLimitCheck['retry_after']} minutes."
            ];
        }

        $user = $this->userModel->getUserByEmail($email);

        if (!$user) {
            RateLimiter::recordFailedLogin($email);
            return [
                'success' => false,
                'message' => 'Invalid email or password'
            ];
        }

        if (!$user['is_active']) {
            return [
                'success' => false,
                'message' => 'Your account has been deactivated. Please contact support.'
            ];
        }

        if (!password_verify($password, $user['password_hash'])) {
            RateLimiter::recordFailedLogin($email);
            return [
                'success' => false,
                'message' => 'Invalid email or password'
            ];
        }

        RateLimiter::resetLoginAttempts($email);
        SessionSecurity::regenerate();

        $_SESSION['user_id'] = $user['userID'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['username'] = $user['full_name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
        $_SESSION['phone'] = $user['phone'];
        $_SESSION['avatar'] = $user['avatar'];

        CSRF::regenerateToken();

        error_log("User logged in: UserID={$user['userID']}, Role={$user['role']}, IP={$_SERVER['REMOTE_ADDR']}");

        return [
            'success' => true,
            'message' => 'Login successful',
            'role' => $user['role'],
            'user' => [
                'userID' => $user['userID'],
                'email' => $user['email'],
                'full_name' => $user['full_name'],
                'role' => $user['role']
            ]
        ];
    }

    public function logout() 
    {
        if (isset($_SESSION['user_id'])) {
            error_log("User logged out: UserID={$_SESSION['user_id']}, IP={$_SERVER['REMOTE_ADDR']}");
        }
        SessionSecurity::destroy();
        header('Location: /agri_system/public/auth/login');
        exit;
    }

    public function isLoggedIn() 
    {
        return isset($_SESSION['user_id']) && 
               isset($_SESSION['logged_in']) && 
               $_SESSION['logged_in'] === true;
    }

    public function getCurrentUser() 
    {
        if ($this->isLoggedIn()) {
            return $this->userModel->getUserById($_SESSION['user_id']);
        }
        return null;
    }

    public function requireLogin() 
    {
        if (!$this->isLoggedIn()) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            header('Location: /agri_system/public/auth/login');
            exit;
        }
    }

    public function requireRole($allowedRoles) 
    {
        $this->requireLogin();
        
        if (!is_array($allowedRoles)) {
            $allowedRoles = [$allowedRoles];
        }

        if (!in_array($_SESSION['user_role'], $allowedRoles)) {
            switch ($_SESSION['user_role']) {
                case 'admin':
                    header('Location: /agri_system/public/profile/admin/dashboard');
                    break;
                case 'seller':
                    header('Location: /agri_system/public/profile/seller/dashboard');
                    break;
                case 'buyer':
                default:
                    header('Location: /agri_system/public/marketplace');
                    break;
            }
            exit;
        }
    }

    public function changePassword($userID, $currentPassword, $newPassword) 
    {
        if (strlen($newPassword) < 8) {
            return [
                'success' => false,
                'message' => 'New password must be at least 8 characters long'
            ];
        }

        $user = $this->userModel->getUserById($userID);
        
        if (!$user) {
            return [
                'success' => false,
                'message' => 'User not found'
            ];
        }

        if (!password_verify($currentPassword, $user['password_hash'])) {
            return [
                'success' => false,
                'message' => 'Current password is incorrect'
            ];
        }

        $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);

        if ($this->userModel->updatePassword($userID, $newPasswordHash)) {
            error_log("Password changed: UserID={$userID}, IP={$_SERVER['REMOTE_ADDR']}");
            SessionSecurity::regenerate();

            return [
                'success' => true,
                'message' => 'Password changed successfully'
            ];
        }

        return [
            'success' => false,
            'message' => 'Failed to change password'
        ];
    }

    public function redirectAfterLogin() 
    {
        if (isset($_SESSION['redirect_after_login'])) {
            $redirect = $_SESSION['redirect_after_login'];
            unset($_SESSION['redirect_after_login']);
            header("Location: $redirect");
            exit;
        }

        switch ($_SESSION['user_role']) {
            case 'admin':
                header('Location: /agri_system/public/profile/admin/dashboard');
                break;
            case 'seller':
                header('Location: /agri_system/public/profile/seller/dashboard');
                break;
            case 'buyer':
            default:
                header('Location: /agri_system/public/marketplace');
                break;
        }
        exit;
    }

    public function validateSession() 
    {
        if (!$this->isLoggedIn()) {
            return false;
        }

        $user = $this->userModel->getUserById($_SESSION['user_id']);
        
        if (!$user || !$user['is_active']) {
            $this->logout();
            return false;
        }

        $_SESSION['username'] = $user['full_name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['avatar'] = $user['avatar'];

        return true;
    }

    public function requestAccountDeletion($userID, $reason = null) 
    {
        try {
            $db = new Database();
            $conn = $db->connect();

            $checkQuery = "SELECT requestID FROM account_deletion_requests 
                          WHERE userID = ? AND status = 'pending'";
            $stmt = $conn->prepare($checkQuery);
            $stmt->execute([$userID]);

            if ($stmt->fetch()) {
                return [
                    'success' => false,
                    'message' => 'You already have a pending deletion request'
                ];
            }

            $insertQuery = "INSERT INTO account_deletion_requests 
                           (userID, reason, status, requested_at) 
                           VALUES (?, ?, 'pending', NOW())";
            $insertStmt = $conn->prepare($insertQuery);
            $result = $insertStmt->execute([$userID, $reason]);

            if ($result) {
                error_log("Account deletion requested: UserID={$userID}");
                return [
                    'success' => true,
                    'message' => 'Account deletion request submitted. Admin will review shortly.'
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to submit deletion request'
            ];

        } catch (Exception $e) {
            error_log("Account deletion request error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred'
            ];
        }
    }

    public function getRoleDisplayName($role) 
    {
        $roleNames = [
            'buyer' => 'Buyer',
            'seller' => 'Seller',
            'admin' => 'Administrator'
        ];
        return $roleNames[$role] ?? 'Unknown';
    }

    public function isAdmin() 
    {
        return $this->isLoggedIn() && $_SESSION['user_role'] === 'admin';
    }

    public function isSeller() 
    {
        return $this->isLoggedIn() && $_SESSION['user_role'] === 'seller';
    }

    public function isBuyer() 
    {
        return $this->isLoggedIn() && $_SESSION['user_role'] === 'buyer';
    }
}
?>