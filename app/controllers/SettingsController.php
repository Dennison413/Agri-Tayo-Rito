<?php
// app/controllers/SettingsController.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Admin.php';
require_once __DIR__ . '/../helpers/csrf.php';
require_once __DIR__ . '/../helpers/RateLimiter.php';

class SettingsController 
{
    private $userModel;
    private $adminModel;

    public function __construct() 
    {
        $this->userModel = new User();
        $this->adminModel = new Admin();
    }

    // password management
    public function changePassword() 
    {
        if (!CSRF::validateRequest()) {
            CSRF::handleFailure(false);
            return;
        }

        $this->requireLogin();

        $rateCheck = RateLimiter::checkApiRate('password_change_' . $_SESSION['user_id']);
        if (!$rateCheck['allowed']) {
            $_SESSION['error'] = 'Too many password change attempts. Please try again later.';
            header('Location: /agri_system/public/profile/settings');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = 'Invalid request';
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
        }

        try {
            $userID = $_SESSION['user_id'];
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                $_SESSION['error'] = 'All password fields are required';
                header('Location: /agri_system/public/profile/settings');
                exit;
            }

            if ($newPassword !== $confirmPassword) {
                $_SESSION['error'] = 'New passwords do not match';
                header('Location: /agri_system/public/profile/settings');
                exit;
            }

            if (strlen($newPassword) < 8) {
                $_SESSION['error'] = 'Password must be at least 8 characters long';
                header('Location: /agri_system/public/profile/settings');
                exit;
            }

            $user = $this->userModel->getUserById($userID);

            if (!$user) {
                $_SESSION['error'] = 'User not found';
                header('Location: /agri_system/public/auth/login');
                exit;
            }

            if (!password_verify($currentPassword, $user['password_hash'])) {
                $_SESSION['error'] = 'Current password is incorrect';
                header('Location: /agri_system/public/profile/settings');
                exit;
            }

            $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);

            // Update password
            if ($this->userModel->updatePassword($userID, $newPasswordHash)) {
                CSRF::regenerateToken();
                
                error_log("Password changed: UserID={$userID}, IP={$_SERVER['REMOTE_ADDR']}");
                
                $_SESSION['success'] = 'Password changed successfully';
            } else {
                $_SESSION['error'] = 'Failed to change password';
            }

        } catch (Exception $e) {
            error_log("Change password error: " . $e->getMessage());
            $_SESSION['error'] = 'An error occurred';
        }

        header('Location: /agri_system/public/profile/settings');
        exit;
    }

    // request account deletion with CSRF validation
    public function requestAccountDeletion() 
    {
        if (!CSRF::validateRequest()) {
            CSRF::handleFailure(false);
            return;
        }

        $this->requireLogin();

        $rateCheck = RateLimiter::checkApiRate('deletion_request_' . $_SESSION['user_id']);
        if (!$rateCheck['allowed']) {
            $_SESSION['error'] = 'Too many deletion requests. Please try again later.';
            header('Location: /agri_system/public/profile/settings');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = 'Invalid request';
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
        }

        try {
            $userID = $_SESSION['user_id'];
            $reason = filter_var($_POST['deletion_reason'] ?? '', FILTER_SANITIZE_STRING);
            $confirmEmail = filter_var($_POST['confirm_email'] ?? '', FILTER_SANITIZE_EMAIL);

            if ($confirmEmail !== $_SESSION['email']) {
                $_SESSION['error'] = 'Email confirmation does not match';
                header('Location: /agri_system/public/profile/settings');
                exit;
            }
            $db = new Database();
            $conn = $db->connect();

            $checkQuery = "SELECT requestID FROM account_deletion_requests 
                          WHERE userID = ? AND status = 'pending'";
            $stmt = $conn->prepare($checkQuery);
            $stmt->execute([$userID]);

            if ($stmt->fetch()) {
                $_SESSION['error'] = 'You already have a pending deletion request';
                header('Location: /agri_system/public/profile/settings');
                exit;
            }

            $insertQuery = "INSERT INTO account_deletion_requests 
                           (userID, reason, status, requested_at) 
                           VALUES (?, ?, 'pending', NOW())";
            $insertStmt = $conn->prepare($insertQuery);
            $result = $insertStmt->execute([$userID, $reason]);

            if ($result) {
                CSRF::regenerateToken();
                
                error_log("Account deletion requested: UserID={$userID}, IP={$_SERVER['REMOTE_ADDR']}");
                
                $_SESSION['success'] = 'Account deletion request submitted. Admin will review your request.';
            } else {
                $_SESSION['error'] = 'Failed to submit deletion request';
            }

        } catch (Exception $e) {
            error_log("Account deletion request error: " . $e->getMessage());
            $_SESSION['error'] = 'An error occurred';
        }

        header('Location: /agri_system/public/profile/settings');
        exit;
    }

    public function cancelDeletionRequest() 
    {
        if (!CSRF::validateRequest()) {
            CSRF::handleFailure(false);
            return;
        }

        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = 'Invalid request';
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
        }

        try {
            $userID = $_SESSION['user_id'];
            $requestID = intval($_POST['request_id']);

            $db = new Database();
            $conn = $db->connect();

            $query = "DELETE FROM account_deletion_requests 
                     WHERE requestID = ? AND userID = ? AND status = 'pending'";
            $stmt = $conn->prepare($query);
            $result = $stmt->execute([$requestID, $userID]);

            if ($result && $stmt->rowCount() > 0) {
                CSRF::regenerateToken();
                $_SESSION['success'] = 'Deletion request cancelled';
            } else {
                $_SESSION['error'] = 'Request not found or already processed';
            }

        } catch (Exception $e) {
            error_log("Cancel deletion request error: " . $e->getMessage());
            $_SESSION['error'] = 'An error occurred';
        }

        header('Location: /agri_system/public/profile/settings');
        exit;
    }

    public function getDeletionRequestStatus($userID) 
    {
        try {
            $db = new Database();
            $conn = $db->connect();

            $query = "SELECT * FROM account_deletion_requests 
                     WHERE userID = ? 
                     ORDER BY requested_at DESC 
                     LIMIT 1";
            $stmt = $conn->prepare($query);
            $stmt->execute([$userID]);

            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("Get deletion request error: " . $e->getMessage());
            return null;
        }
    }

    // admin system settings
    public function getAllSettings() 
    {
        $this->requireRole('admin');
        return $this->adminModel->getAllSystemSettings();
    }
    public function getSetting($key) 
    {
        return $this->adminModel->getSystemSetting($key);
    }
    public function updateSystemSetting() 
    {
        if (!CSRF::validateRequest()) {
            CSRF::handleFailure(false);
            return;
        }

        $this->requireRole('admin');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = 'Invalid request';
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
        }

        try {
            $settingKey = filter_var($_POST['setting_key'] ?? '', FILTER_SANITIZE_STRING);
            $settingValue = filter_var($_POST['setting_value'] ?? '', FILTER_SANITIZE_STRING);

            if (empty($settingKey)) {
                $_SESSION['error'] = 'Setting key is required';
                header('Location: /agri_system/public/profile/admin/settings');
                exit;
            }

            $result = $this->adminModel->updateSystemSetting($settingKey, $settingValue);

            if ($result['success']) {
                CSRF::regenerateToken();
                $_SESSION['success'] = $result['message'];
            } else {
                $_SESSION['error'] = $result['message'];
            }

        } catch (Exception $e) {
            error_log("Update system setting error: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to update setting';
        }

        header('Location: /agri_system/public/profile/admin/settings');
        exit;
    }

    
    public function updateMultipleSettings() 
    {
        if (!CSRF::validateRequest()) {
            CSRF::handleFailure(false);
            return;
        }

        $this->requireRole('admin');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = 'Invalid request';
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
        }

        try {
            $settings = $_POST['settings'] ?? [];

            if (empty($settings)) {
                $_SESSION['error'] = 'No settings to update';
                header('Location: /agri_system/public/profile/admin/settings');
                exit;
            }

            $successCount = 0;
            foreach ($settings as $key => $value) {
                $result = $this->adminModel->updateSystemSetting($key, $value);
                if ($result['success']) {
                    $successCount++;
                }
            }

            if ($successCount > 0) {
                CSRF::regenerateToken();
                $_SESSION['success'] = "Updated {$successCount} settings successfully";
            } else {
                $_SESSION['error'] = 'Failed to update settings';
            }

        } catch (Exception $e) {
            error_log("Update multiple settings error: " . $e->getMessage());
            $_SESSION['error'] = 'An error occurred';
        }

        header('Location: /agri_system/public/profile/admin/settings');
        exit;
    }

    // user settings
    public function updatePreferences() 
    {
        if (!CSRF::validateRequest()) {
            CSRF::handleFailure(false);
            return;
        }

        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = 'Invalid request';
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
        }

        try {
            if (isset($_POST['theme'])) {
                $theme = $_POST['theme'];
                if (in_array($theme, ['light', 'dark'])) {
                    $_SESSION['theme'] = $theme;
                    setcookie('theme', $theme, time() + (86400 * 365), '/'); // 1 year
                }
            }

            if (isset($_POST['language'])) {
                $language = $_POST['language'];
                if (in_array($language, ['en', 'fil'])) {
                    $_SESSION['language'] = $language;
                    setcookie('language', $language, time() + (86400 * 365), '/');
                }
            }

            if (isset($_POST['notifications'])) {
                $_SESSION['notifications_enabled'] = $_POST['notifications'] === 'on';
            }

            CSRF::regenerateToken();
            $_SESSION['success'] = 'Preferences updated successfully';

        } catch (Exception $e) {
            error_log("Update preferences error: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to update preferences';
        }

        header('Location: /agri_system/public/profile/settings');
        exit;
    }

    private function isLoggedIn() 
    {
        return isset($_SESSION['user_id']) && 
               isset($_SESSION['logged_in']) && 
               $_SESSION['logged_in'] === true;
    }

    private function requireLogin() 
    {
        if (!$this->isLoggedIn()) {
            $_SESSION['error'] = 'Please login first';
            header('Location: /agri_system/public/auth/login');
            exit;
        }
    }

    private function requireRole($role) 
    {
        $this->requireLogin();

        if ($_SESSION['user_role'] !== $role) {
            $_SESSION['error'] = 'Unauthorized access';
            header('Location: /agri_system/public/marketplace');
            exit;
        }
    }

    public function getCurrentTheme() 
    {
        return $_SESSION['theme'] ?? $_COOKIE['theme'] ?? 'light';
    }
    public function getCurrentLanguage() 
    {
        return $_SESSION['language'] ?? $_COOKIE['language'] ?? 'en';
    }
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller = new SettingsController();
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'change_password':
            $controller->changePassword();
            break;
        case 'request_deletion':
            $controller->requestAccountDeletion();
            break;
        case 'cancel_deletion':
            $controller->cancelDeletionRequest();
            break;
        case 'update_setting':
            $controller->updateSystemSetting();
            break;
        case 'update_settings':
            $controller->updateMultipleSettings();
            break;
        case 'update_preferences':
            $controller->updatePreferences();
            break;
        default:
            $_SESSION['error'] = 'Invalid action';
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
    }
}