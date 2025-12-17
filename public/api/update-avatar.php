<?php
// public/api/update-avatar.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
define('BASE_PATH', dirname(dirname(__DIR__)));
require_once BASE_PATH . '/app/controllers/ProfileController.php';
header('Content-Type: application/json');
if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized. Please login first.'
    ]);
    exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method. Only POST is allowed.'
    ]);
    exit();
}

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid JSON data'
        ]);
        exit();
    }
    
    if (!isset($data['avatar']) || empty($data['avatar'])) {
        echo json_encode([
            'success' => false,
            'message' => 'No avatar selected'
        ]);
        exit();
    }
    
    $selectedAvatar = $data['avatar'];
    $userID = $_SESSION['user_id'];
    
    $profileController = new ProfileController();
    
    require_once BASE_PATH . '/app/models/User.php';
    $userModel = new User();
    
    if (!$userModel->isValidAvatar($selectedAvatar)) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid avatar selected. Please choose from available avatars.'
        ]);
        exit();
    }
    
    if ($userModel->updateAvatar($userID, $selectedAvatar)) {
        $_SESSION['avatar'] = $selectedAvatar;
        
        echo json_encode([
            'success' => true,
            'message' => 'Avatar updated successfully!',
            'avatar' => $selectedAvatar,
            'displayPath' => '/agri_system/public' . $selectedAvatar
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update avatar in database. Please try again.'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Avatar update error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'An unexpected error occurred. Please try again later.'
    ]);
}

exit();