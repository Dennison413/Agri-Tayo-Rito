<?php
// public/api/upload-avatar.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
define('BASE_PATH', dirname(dirname(__DIR__)));
require_once BASE_PATH . '/app/controllers/ProfileController.php';
require_once BASE_PATH . '/app/models/User.php';

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
    $userID = $_SESSION['user_id'];
    
    if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] === UPLOAD_ERR_NO_FILE) {
        echo json_encode([
            'success' => false,
            'message' => 'No file uploaded'
        ]);
        exit();
    }
    
    $file = $_FILES['avatar'];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => 'File is too large (server limit)',
            UPLOAD_ERR_FORM_SIZE => 'File is too large',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
        ];
        
        $message = $errorMessages[$file['error']] ?? 'Unknown upload error';
        
        echo json_encode([
            'success' => false,
            'message' => $message
        ]);
        exit();
    }
    
    $userModel = new User();
    
    $validation = $userModel->validateAvatarUpload($file);
    
    if (!$validation['valid']) {
        echo json_encode([
            'success' => false,
            'message' => $validation['error']
        ]);
        exit();
    }
    
    $currentUser = $userModel->getUserById($userID);
    $oldAvatar = $currentUser['avatar'] ?? null;
    
    $result = $userModel->uploadCustomAvatar($userID, $file, $oldAvatar);
    
    if ($result['success']) {
        $_SESSION['avatar'] = $result['avatarPath'];
        
        echo json_encode([
            'success' => true,
            'message' => 'Custom avatar uploaded successfully!',
            'avatar' => $result['avatarPath'],
            'displayPath' => '/agri_system/public' . $result['avatarPath']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => $result['error'] ?? 'Failed to upload avatar'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Avatar upload error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'An unexpected error occurred. Please try again later.'
    ]);
}

exit();