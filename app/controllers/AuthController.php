<?php
// app/controllers/AuthController.php
// SECURED: Authentication with CSRF + Rate Limiting + Session Security
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

    // User login with CSRF + Rate Limiting
    public function login($email, $password) 
    {
        // Validate input
        if (empty($email) || empty($password)) {
            return [
                'success' => false,
                'message' => 'Email and password are required'
            ];
        }

        // Check rate limiting
        $rateLimitCheck = RateLimiter::checkLoginAttempts($email);
        if (!$rateLimitCheck['allowed']) {
            return [
                'success' => false,
                'message' => "Too many login attempts. Please try again in {$rateLimitCheck['retry_after']} minutes."
            ];
        }

        // Get user from database
        $user = $this->userModel->getUserByEmail($email);

        if (!$user) {
            RateLimiter::recordFailedLogin($email);
            return [
                'success' => false,
                'message' => 'Invalid email or password'
            ];
        }

        // Check if account is active
        if (!$user['is_active']) {
            return [
                'success' => false,
                'message' => 'Your account has been deactivated. Please contact support.'
            ];
        }

        // Verify password
        if (!password_verify($password, $user['password_hash'])) {
            RateLimiter::recordFailedLogin($email);
            return [
                'success' => false,
                'message' => 'Invalid email or password'
            ];
        }

        // SUCCESS: Reset rate limiter
        RateLimiter::resetLoginAttempts($email);

        // Regenerate session ID to prevent session fixation
        SessionSecurity::regenerate();

        // Set session variables
        $_SESSION['user_id'] = $user['userID'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['username'] = $user['full_name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();

        // Store additional user info
        $_SESSION['phone'] = $user['phone'];
        $_SESSION['avatar'] = $user['avatar'];

        // Regenerate CSRF token after login
        CSRF::regenerateToken();

        // Log login activity
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

    // UPDATED: Simplified registration - only requires name, email, password
    public function register($data) 
    {
        // Check registration rate limit
        $rateLimitCheck = RateLimiter::checkRegistrationAttempts();
        if (!$rateLimitCheck['allowed']) {
            return [
                'success' => false,
                'message' => "Too many registration attempts. Please try again in {$rateLimitCheck['retry_after']} minutes."
            ];
        }

        // Validate ONLY required fields: email, password, full_name
        $requiredFields = ['email', 'password', 'full_name'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                return [
                    'success' => false,
                    'message' => ucfirst(str_replace('_', ' ', $field)) . ' is required'
                ];
            }
        }

        // Validate email format
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => 'Invalid email format'
            ];
        }

        // Check password length
        if (strlen($data['password']) < 8) {
            return [
                'success' => false,
                'message' => 'Password must be at least 8 characters long'
            ];
        }

        // Validate password confirmation if provided
        if (isset($data['confirm_password']) && $data['password'] !== $data['confirm_password']) {
            return [
                'success' => false,
                'message' => 'Passwords do not match'
            ];
        }

        // Optional: Validate phone number if provided
        if (!empty($data['phone'])) {
            $phone = preg_replace('/[^0-9]/', '', $data['phone']);
            if (strlen($phone) !== 11 || substr($phone, 0, 2) !== '09') {
                return [
                    'success' => false,
                    'message' => 'Invalid phone number. Please use format: 09XXXXXXXXX'
                ];
            }
        }

        // Hash password
        $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // Set default role as buyer
        $data['role'] = 'buyer';

        // Create user
        try {
            $result = $this->userModel->createUser($data);
            
            if ($result['success']) {
                // Record registration attempt
                RateLimiter::recordRegistrationAttempt();

                // Log registration
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

    // User logout
    public function logout() 
    {
        // Log logout activity
        if (isset($_SESSION['user_id'])) {
            error_log("User logged out: UserID={$_SESSION['user_id']}, IP={$_SERVER['REMOTE_ADDR']}");
        }

        // Destroy session securely
        SessionSecurity::destroy();

        header('Location: /agri_system/public/auth/login');
        exit;
    }

    // Check if user is logged in
    public function isLoggedIn() 
    {
        return isset($_SESSION['user_id']) && 
               isset($_SESSION['logged_in']) && 
               $_SESSION['logged_in'] === true;
    }

    // Get current logged-in user details
    public function getCurrentUser() 
    {
        if ($this->isLoggedIn()) {
            return $this->userModel->getUserById($_SESSION['user_id']);
        }
        return null;
    }

    // Require user to be logged in
    public function requireLogin() 
    {
        if (!$this->isLoggedIn()) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            header('Location: /agri_system/public/auth/login');
            exit;
        }
    }

    // Require specific role(s)
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

    // Change password with CSRF validation
    public function changePassword($userID, $currentPassword, $newPassword) 
    {
        // Validate new password length
        if (strlen($newPassword) < 8) {
            return [
                'success' => false,
                'message' => 'New password must be at least 8 characters long'
            ];
        }

        // Get user
        $user = $this->userModel->getUserById($userID);
        
        if (!$user) {
            return [
                'success' => false,
                'message' => 'User not found'
            ];
        }

        // Verify current password
        if (!password_verify($currentPassword, $user['password_hash'])) {
            return [
                'success' => false,
                'message' => 'Current password is incorrect'
            ];
        }

        // Hash new password
        $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);

        // Update password
        if ($this->userModel->updatePassword($userID, $newPasswordHash)) {
            // Log password change
            error_log("Password changed: UserID={$userID}, IP={$_SERVER['REMOTE_ADDR']}");

            // Regenerate session after password change
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

    // Redirect after login based on user role
    public function redirectAfterLogin() 
    {
        // Check if there's a stored redirect URL
        if (isset($_SESSION['redirect_after_login'])) {
            $redirect = $_SESSION['redirect_after_login'];
            unset($_SESSION['redirect_after_login']);
            header("Location: $redirect");
            exit;
        }

        // Default redirects based on role
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

    // Check if user has active session on page load
    public function validateSession() 
    {
        if (!$this->isLoggedIn()) {
            return false;
        }

        // Verify user still exists and is active
        $user = $this->userModel->getUserById($_SESSION['user_id']);
        
        if (!$user || !$user['is_active']) {
            // User was deleted or deactivated, destroy session
            $this->logout();
            return false;
        }

        // Update session data if user info changed
        $_SESSION['username'] = $user['full_name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['avatar'] = $user['avatar'];

        return true;
    }

    // Request account deletion with CSRF
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

    // Helper methods
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