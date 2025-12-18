<?php
// app/controllers/ProfileController.php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../helpers/csrf.php';
require_once __DIR__ . '/../../config/database.php';

class ProfileController
{
    private $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    // user profile
    public function getProfile()
    {
        $this->requireLogin();

        $userID = $_SESSION['user_id'];
        $user = $this->userModel->getUserById($userID);

        if (!$user) {
            $_SESSION['error'] = 'User not found';
            header('Location: /agri_system/public/auth/login');
            exit;
        }

        return $user;
    }

    public function getUserById($userID)
    {
        return $this->userModel->getUserById($userID);
    }

    // user statistics
    public function getUserStats($userID)
    {
        try {
            $db = new Database();
            $conn = $db->connect();

            // Get total orders
            $ordersQuery = "SELECT COUNT(*) as total_orders FROM orders WHERE buyerID = ?";
            $stmt = $conn->prepare($ordersQuery);
            $stmt->execute([$userID]);
            $ordersResult = $stmt->fetch(PDO::FETCH_ASSOC);

            // Get completed orders
            $completedQuery = "SELECT COUNT(*) as completed_orders 
                              FROM orders 
                              WHERE buyerID = ? AND order_status = 'delivered'";
            $stmt = $conn->prepare($completedQuery);
            $stmt->execute([$userID]);
            $completedResult = $stmt->fetch(PDO::FETCH_ASSOC);

            // Get wishlist count
            $wishlistQuery = "SELECT COUNT(*) as wishlist_count FROM wishlist WHERE buyerID = ?";
            $stmt = $conn->prepare($wishlistQuery);
            $stmt->execute([$userID]);
            $wishlistResult = $stmt->fetch(PDO::FETCH_ASSOC);

            // Get reviews count
            $reviewsQuery = "SELECT COUNT(*) as reviews_count FROM reviews WHERE buyerID = ?";
            $stmt = $conn->prepare($reviewsQuery);
            $stmt->execute([$userID]);
            $reviewsResult = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'total_orders' => $ordersResult['total_orders'] ?? 0,
                'completed_orders' => $completedResult['completed_orders'] ?? 0,
                'wishlist_count' => $wishlistResult['wishlist_count'] ?? 0,
                'reviews_count' => $reviewsResult['reviews_count'] ?? 0
            ];
        } catch (Exception $e) {
            error_log("Get user stats error: " . $e->getMessage());
            return [
                'total_orders' => 0,
                'completed_orders' => 0,
                'wishlist_count' => 0,
                'reviews_count' => 0
            ];
        }
    }

    // recent orders
    public function getRecentOrders($userID, $limit = 5)
    {
        try {
            $db = new Database();
            $conn = $db->connect();

            $query = "SELECT 
                        o.orderID,
                        o.order_date as created_at,
                        o.total_amount,
                        o.order_status as status,
                        GROUP_CONCAT(p.product_name SEPARATOR ', ') as product_name,
                        MIN(pi.image_path) as image
                      FROM orders o
                      JOIN order_items oi ON o.orderID = oi.orderID
                      JOIN products p ON oi.productID = p.productID
                      LEFT JOIN product_images pi ON p.productID = pi.productID AND pi.is_main = 1
                      WHERE o.buyerID = ?
                      GROUP BY o.orderID
                      ORDER BY o.order_date DESC
                      LIMIT ?";

            $stmt = $conn->prepare($query);
            $stmt->execute([$userID, $limit]);
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($orders as &$order) {
                if (empty($order['image'])) {
                    $order['image'] = '/agri_system/public/images/placeholder-product.jpg';
                } else {
                    $order['image'] = '/agri_system/public' . $order['image'];
                }
            }

            return $orders;
        } catch (Exception $e) {
            error_log("Get recent orders error: " . $e->getMessage());
            return [];
        }
    }

    // format date for display
    public function formatDate($dateString)
    {
        if (empty($dateString)) {
            return 'N/A';
        }

        try {
            $date = new DateTime($dateString);
            return $date->format('F j, Y');
        } catch (Exception $e) {
            return 'Invalid date';
        }
    }

    // format phone number
    public function formatPhone($phone)
    {
        if (empty($phone)) {
            return 'Not provided';
        }

        if (strlen($phone) === 11 && substr($phone, 0, 2) === '09') {
            return substr($phone, 0, 4) . '-' . substr($phone, 4, 3) . '-' . substr($phone, 7);
        }

        return $phone;
    }

    // get full address
    public function getFullAddress($user)
    {
        $parts = [];

        if (!empty($user['address'])) {
            $parts[] = $user['address'];
        }

        if (!empty($user['municipality'])) {
            $parts[] = $user['municipality'];
        }

        if (!empty($user['province'])) {
            $parts[] = $user['province'];
        }

        if (!empty($user['postal_code'])) {
            $parts[] = $user['postal_code'];
        }

        return !empty($parts) ? implode(', ', $parts) : 'Not provided';
    }

    public function updateProfile()
    {
        if (!CSRF::validateRequest()) {
            CSRF::handleFailure(false);
            return;
        }

        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = 'Invalid request method';
            header('Location: /agri_system/public/profile/user');
            exit;
        }

        $userID = $_SESSION['user_id'];

        $data = [
            'full_name' => filter_var(trim($_POST['full_name'] ?? ''), FILTER_SANITIZE_STRING),
            'phone' => filter_var(trim($_POST['phone'] ?? ''), FILTER_SANITIZE_STRING)
        ];

        if (empty($data['full_name']) || empty($data['phone'])) {
            $_SESSION['error'] = 'Full name and phone are required';
            header('Location: /agri_system/public/profile/user');
            exit;
        }

        $phone = preg_replace('/[^0-9]/', '', $data['phone']);
        if (strlen($phone) !== 11 || substr($phone, 0, 2) !== '09') {
            $_SESSION['error'] = 'Invalid phone number format. Use: 09XXXXXXXXX';
            header('Location: /agri_system/public/profile/user');
            exit;
        }

        if ($this->userModel->updateUser($userID, $data)) {
            $_SESSION['username'] = $data['full_name'];
            $_SESSION['phone'] = $data['phone'];

            CSRF::regenerateToken();
            $_SESSION['success'] = 'Profile updated successfully';
        } else {
            $_SESSION['error'] = 'Failed to update profile';
        }

        header('Location: /agri_system/public/profile/user');
        exit;
    }

    // password management
    public function updatePassword()
    {
        if (!CSRF::validateRequest()) {
            CSRF::handleFailure(false);
            return;
        }

        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = 'Invalid request method';
            header('Location: /agri_system/public/profile/security');
            exit;
        }

        $userID = $_SESSION['user_id'];
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $_SESSION['error'] = 'All password fields are required';
            header('Location: /agri_system/public/profile/security');
            exit;
        }

        if ($newPassword !== $confirmPassword) {
            $_SESSION['error'] = 'New passwords do not match';
            header('Location: /agri_system/public/profile/security');
            exit;
        }

        if (strlen($newPassword) < 8) {
            $_SESSION['error'] = 'Password must be at least 8 characters long';
            header('Location: /agri_system/public/profile/security');
            exit;
        }

        $user = $this->userModel->getUserById($userID);
        if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
            $_SESSION['error'] = 'Current password is incorrect';
            header('Location: /agri_system/public/profile/security');
            exit;
        }

        $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        if ($this->userModel->updatePassword($userID, $newPasswordHash)) {
            CSRF::regenerateToken();
            $_SESSION['success'] = 'Password updated successfully';
        } else {
            $_SESSION['error'] = 'Failed to update password';
        }

        header('Location: /agri_system/public/profile/security');
        exit;
    }

    // avatars
    public function updateAvatar()
    {
        $this->requireLogin();

        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid request method'
            ]);
            exit;
        }

        if (!CSRF::validateJsonRequest() && !CSRF::validateRequest()) {
            echo json_encode([
                'success' => false,
                'message' => 'Security token validation failed',
                'error_code' => 'CSRF_VALIDATION_FAILED'
            ]);
            exit;
        }

        $userID = $_SESSION['user_id'];
        if (isset($_FILES['avatar_upload']) && $_FILES['avatar_upload']['error'] === UPLOAD_ERR_OK) {
            $this->handleCustomAvatarUpload($userID);
        } else {
            $this->handlePresetAvatarSelection($userID);
        }
    }

    private function handlePresetAvatarSelection($userID)
    {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        $selectedAvatar = filter_var($data['avatar'] ?? '', FILTER_SANITIZE_STRING);

        if (!$this->userModel->isValidAvatar($selectedAvatar)) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid avatar selected'
            ]);
            exit;
        }

        if ($this->userModel->updateAvatar($userID, $selectedAvatar)) {
            $_SESSION['avatar'] = $selectedAvatar;

            echo json_encode([
                'success' => true,
                'message' => 'Avatar updated successfully',
                'avatar' => $selectedAvatar,
                'csrf_token' => CSRF::generateToken()
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to update avatar'
            ]);
        }
        exit;
    }

    private function handleCustomAvatarUpload($userID)
    {
        $file = $_FILES['avatar_upload'];

        $user = $this->userModel->getUserById($userID);
        $oldAvatar = $user['avatar'] ?? null;

        $validation = $this->userModel->validateAvatarUpload($file);

        if (!$validation['valid']) {
            echo json_encode([
                'success' => false,
                'message' => $validation['error']
            ]);
            exit;
        }

        $result = $this->userModel->uploadCustomAvatar($userID, $file, $oldAvatar);

        if ($result['success']) {
            $_SESSION['avatar'] = $result['avatarPath'];

            echo json_encode([
                'success' => true,
                'message' => 'Avatar uploaded successfully',
                'avatar' => $result['avatarPath'],
                'csrf_token' => CSRF::generateToken()
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => $result['error']
            ]);
        }
        exit;
    }

    public function getAvailableAvatars()
    {
        return $this->userModel->getAvailableAvatars();
    }

    public function getAvatarPath($avatar)
    {
        if (empty($avatar)) {
            return '/agri_system/public/images/avatars/avt1.jpg';
        }

        if (strpos($avatar, '/images/avatars/') === 0) {
            return '/agri_system/public' . $avatar;
        }

        if (strpos($avatar, '/uploads/profiles/') === 0) {
            return '/agri_system/public' . $avatar;
        }

        return '/agri_system/public/images/avatars/avt1.jpg';
    }

    public function addAddress()
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

        $userID = $_SESSION['user_id'];

        $data = [
            'address_label' => filter_var($_POST['address_label'] ?? 'Home', FILTER_SANITIZE_STRING),
            'full_name' => filter_var($_POST['full_name'] ?? $_SESSION['username'], FILTER_SANITIZE_STRING),
            'phone' => filter_var($_POST['phone'] ?? '', FILTER_SANITIZE_STRING),
            'address_line1' => filter_var($_POST['address_line1'] ?? '', FILTER_SANITIZE_STRING),
            'address_line2' => filter_var($_POST['address_line2'] ?? '', FILTER_SANITIZE_STRING),
            'barangay' => filter_var($_POST['barangay'] ?? '', FILTER_SANITIZE_STRING),
            'municipality' => filter_var($_POST['municipality'] ?? '', FILTER_SANITIZE_STRING),
            'province' => filter_var($_POST['province'] ?? '', FILTER_SANITIZE_STRING),
            'postal_code' => filter_var($_POST['postal_code'] ?? '', FILTER_SANITIZE_STRING),
            'is_default' => isset($_POST['is_default']) ? 1 : 0
        ];

        $required = ['full_name', 'phone', 'address_line1', 'barangay', 'municipality', 'province', 'postal_code'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $_SESSION['error'] = 'All address fields are required';
                header('Location: ' . $_SERVER['HTTP_REFERER']);
                exit;
            }
        }

        $phone = preg_replace('/[^0-9]/', '', $data['phone']);
        if (strlen($phone) !== 11 || substr($phone, 0, 2) !== '09') {
            $_SESSION['error'] = 'Invalid phone number format. Use: 09XXXXXXXXX';
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
        }

        try {
            $db = new Database();
            $conn = $db->connect();

            if ($data['is_default']) {
                $updateQuery = "UPDATE user_addresses SET is_default = 0 WHERE userID = ?";
                $stmt = $conn->prepare($updateQuery);
                $stmt->execute([$userID]);
            }

            $query = "INSERT INTO user_addresses 
                     (userID, address_label, full_name, phone, address_line1, address_line2, 
                      barangay, municipality, province, postal_code, is_default) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $conn->prepare($query);
            $result = $stmt->execute([
                $userID,
                $data['address_label'],
                $data['full_name'],
                $data['phone'],
                $data['address_line1'],
                $data['address_line2'],
                $data['barangay'],
                $data['municipality'],
                $data['province'],
                $data['postal_code'],
                $data['is_default']
            ]);

            if ($result) {
                CSRF::regenerateToken();
                $_SESSION['success'] = 'Address added successfully';
            } else {
                $_SESSION['error'] = 'Failed to add address';
            }
        } catch (Exception $e) {
            error_log("Add address error: " . $e->getMessage());
            $_SESSION['error'] = 'An error occurred';
        }

        header('Location: /agri_system/public/profile/buyer/addresses');
        exit;
    }

    public function updateAddress()
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

        $userID = $_SESSION['user_id'];
        $addressID = filter_var($_POST['address_id'], FILTER_VALIDATE_INT);

        if (!$addressID) {
            $_SESSION['error'] = 'Invalid address ID';
            header('Location: /agri_system/public/profile/buyer/addresses');
            exit;
        }

        $data = [
            'address_label' => filter_var($_POST['address_label'] ?? 'Home', FILTER_SANITIZE_STRING),
            'full_name' => filter_var($_POST['full_name'] ?? '', FILTER_SANITIZE_STRING),
            'phone' => filter_var($_POST['phone'] ?? '', FILTER_SANITIZE_STRING),
            'address_line1' => filter_var($_POST['address_line1'] ?? '', FILTER_SANITIZE_STRING),
            'address_line2' => filter_var($_POST['address_line2'] ?? '', FILTER_SANITIZE_STRING),
            'barangay' => filter_var($_POST['barangay'] ?? '', FILTER_SANITIZE_STRING),
            'municipality' => filter_var($_POST['municipality'] ?? '', FILTER_SANITIZE_STRING),
            'province' => filter_var($_POST['province'] ?? '', FILTER_SANITIZE_STRING),
            'postal_code' => filter_var($_POST['postal_code'] ?? '', FILTER_SANITIZE_STRING),
            'is_default' => isset($_POST['is_default']) ? 1 : 0
        ];

        $required = ['full_name', 'phone', 'address_line1', 'barangay', 'municipality', 'province', 'postal_code'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $_SESSION['error'] = 'All address fields are required';
                header('Location: /agri_system/public/profile/buyer/addresses');
                exit;
            }
        }

        $phone = preg_replace('/[^0-9]/', '', $data['phone']);
        if (strlen($phone) !== 11 || substr($phone, 0, 2) !== '09') {
            $_SESSION['error'] = 'Invalid phone number format. Use: 09XXXXXXXXX';
            header('Location: /agri_system/public/profile/buyer/addresses');
            exit;
        }

        try {
            $db = new Database();
            $conn = $db->connect();

            if ($data['is_default']) {
                $updateQuery = "UPDATE user_addresses SET is_default = 0 WHERE userID = ? AND addressID != ?";
                $stmt = $conn->prepare($updateQuery);
                $stmt->execute([$userID, $addressID]);
            }

            $query = "UPDATE user_addresses 
                     SET address_label = ?, full_name = ?, phone = ?, 
                         address_line1 = ?, address_line2 = ?, barangay = ?, 
                         municipality = ?, province = ?, postal_code = ?, is_default = ?
                     WHERE addressID = ? AND userID = ?";

            $stmt = $conn->prepare($query);
            $result = $stmt->execute([
                $data['address_label'],
                $data['full_name'],
                $data['phone'],
                $data['address_line1'],
                $data['address_line2'],
                $data['barangay'],
                $data['municipality'],
                $data['province'],
                $data['postal_code'],
                $data['is_default'],
                $addressID,
                $userID
            ]);

            if ($result) {
                CSRF::regenerateToken();
                $_SESSION['success'] = 'Address updated successfully';
            } else {
                $_SESSION['error'] = 'Failed to update address';
            }
        } catch (Exception $e) {
            error_log("Update address error: " . $e->getMessage());
            $_SESSION['error'] = 'An error occurred';
        }

        header('Location: /agri_system/public/profile/buyer/addresses');
        exit;
    }

    public function setDefaultAddress()
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

        $userID = $_SESSION['user_id'];
        $addressID = filter_var($_POST['address_id'], FILTER_VALIDATE_INT);

        if (!$addressID) {
            $_SESSION['error'] = 'Invalid address ID';
            header('Location: /agri_system/public/profile/buyer/addresses');
            exit;
        }

        try {
            $db = new Database();
            $conn = $db->connect();

            $updateQuery = "UPDATE user_addresses SET is_default = 0 WHERE userID = ?";
            $stmt = $conn->prepare($updateQuery);
            $stmt->execute([$userID]);

            $query = "UPDATE user_addresses SET is_default = 1 WHERE addressID = ? AND userID = ?";
            $stmt = $conn->prepare($query);
            $result = $stmt->execute([$addressID, $userID]);

            if ($result) {
                CSRF::regenerateToken();
                $_SESSION['success'] = 'Default address updated';
            } else {
                $_SESSION['error'] = 'Failed to set default address';
            }
        } catch (Exception $e) {
            error_log("Set default address error: " . $e->getMessage());
            $_SESSION['error'] = 'An error occurred';
        }

        header('Location: /agri_system/public/profile/buyer/addresses');
        exit;
    }

    public function getUserAddresses($userID)
    {
        try {
            $db = new Database();
            $conn = $db->connect();

            $query = "SELECT * FROM user_addresses 
                     WHERE userID = ? 
                     ORDER BY is_default DESC, created_at DESC";
            $stmt = $conn->prepare($query);
            $stmt->execute([$userID]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Get addresses error: " . $e->getMessage());
            return [];
        }
    }

    public function getAddressById($addressID, $userID)
    {
        try {
            $db = new Database();
            $conn = $db->connect();

            $query = "SELECT * FROM user_addresses WHERE addressID = ? AND userID = ? LIMIT 1";
            $stmt = $conn->prepare($query);
            $stmt->execute([$addressID, $userID]);

            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Get address error: " . $e->getMessage());
            return null;
        }
    }

    public function deleteAddress()
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

        $userID = $_SESSION['user_id'];
        $addressID = filter_var($_POST['address_id'], FILTER_VALIDATE_INT);

        if (!$addressID) {
            $_SESSION['error'] = 'Invalid address ID';
            header('Location: /agri_system/public/profile/buyer/addresses');
            exit;
        }

        try {
            $db = new Database();
            $conn = $db->connect();

            $query = "DELETE FROM user_addresses WHERE addressID = ? AND userID = ?";
            $stmt = $conn->prepare($query);
            $result = $stmt->execute([$addressID, $userID]);

            if ($result) {
                CSRF::regenerateToken();
                $_SESSION['success'] = 'Address deleted successfully';
            } else {
                $_SESSION['error'] = 'Failed to delete address';
            }
        } catch (Exception $e) {
            error_log("Delete address error: " . $e->getMessage());
            $_SESSION['error'] = 'An error occurred';
        }

        header('Location: /agri_system/public/profile/buyer/addresses');
        exit;
    }

    // account deletion

    public function requestAccountDeletion()
    {
        if (!CSRF::validateRequest()) {
            CSRF::handleFailure(false);
            return;
        }

        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = 'Invalid request method';
            header('Location: /agri_system/public/profile/security');
            exit;
        }

        $userID = $_SESSION['user_id'];
        $reason = filter_var($_POST['deletion_reason'] ?? '', FILTER_SANITIZE_STRING);

        try {
            $db = new Database();
            $conn = $db->connect();

            $checkQuery = "SELECT * FROM account_deletion_requests 
                          WHERE userID = ? AND status = 'pending' LIMIT 1";
            $stmt = $conn->prepare($checkQuery);
            $stmt->execute([$userID]);

            if ($stmt->fetch()) {
                $_SESSION['error'] = 'You already have a pending deletion request';
                header('Location: /agri_system/public/profile/security');
                exit;
            }

            // Insert deletion request
            $query = "INSERT INTO account_deletion_requests (userID, reason, status) 
                     VALUES (?, ?, 'pending')";
            $stmt = $conn->prepare($query);
            $result = $stmt->execute([$userID, $reason]);

            if ($result) {
                CSRF::regenerateToken();
                $_SESSION['success'] = 'Account deletion request submitted. Admin will review your request.';
            } else {
                $_SESSION['error'] = 'Failed to submit deletion request';
            }
        } catch (Exception $e) {
            error_log("Request deletion error: " . $e->getMessage());
            $_SESSION['error'] = 'An error occurred';
        }

        header('Location: /agri_system/public/profile/security');
        exit;
    }

    // other utility methods

    private function isLoggedIn()
    {
        return isset($_SESSION['user_id']) &&
            isset($_SESSION['logged_in']) &&
            $_SESSION['logged_in'] === true;
    }

    private function requireLogin()
    {
        if (!$this->isLoggedIn()) {
            $_SESSION['error'] = 'Please login to continue';
            header('Location: /agri_system/public/auth/login');
            exit;
        }
    }

    public function getUserRole($userID)
    {
        $user = $this->userModel->getUserById($userID);
        return $user['role'] ?? 'buyer';
    }

    public function isSeller($userID)
    {
        return $this->getUserRole($userID) === 'seller';
    }

    public function isAdmin($userID)
    {
        return $this->getUserRole($userID) === 'admin';
    }
}