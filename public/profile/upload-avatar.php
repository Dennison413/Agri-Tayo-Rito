<?php
/**
 * Custom Avatar Upload Endpoint
 * Handles file uploads for custom user avatars
 * 
 * File location: /agri_system/public/profile/upload-avatar.php
 */

// Start session
session_start();

// Define base path
define('BASE_PATH', dirname(dirname(__DIR__)));

// Include required files
require_once BASE_PATH . '/app/controllers/ProfileController.php';
require_once BASE_PATH . '/app/models/User.php';

// Set JSON response header
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized. Please login first.'
    ]);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method. Only POST is allowed.'
    ]);
    exit();
}

try {
    $userID = $_SESSION['user_id'];
    
    // Check if file was uploaded
    if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] === UPLOAD_ERR_NO_FILE) {
        echo json_encode([
            'success' => false,
            'message' => 'No file uploaded'
        ]);
        exit();
    }
    
    $file = $_FILES['avatar'];
    
    // Check for upload errors
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
    
    // Initialize User model
    $userModel = new User();
    
    // Validate file
    $validation = $userModel->validateAvatarUpload($file);
    
    if (!$validation['valid']) {
        echo json_encode([
            'success' => false,
            'message' => $validation['error']
        ]);
        exit();
    }
    
    // Get current user data to check for old custom avatar
    $currentUser = $userModel->getUserById($userID);
    $oldAvatar = $currentUser['avatar'] ?? null;
    
    // Process and save the uploaded file
    $result = $userModel->uploadCustomAvatar($userID, $file, $oldAvatar);
    
    if ($result['success']) {
        // Update session
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
    // Log the error
    error_log("Avatar upload error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'An unexpected error occurred. Please try again later.'
    ]);
}

exit();