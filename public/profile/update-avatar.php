<?php
/**
 * Avatar Update Endpoint
 * Handles AJAX requests to update user avatar
 * 
 * File location: /agri_system/public/profile/update-avatar.php
 */

// Start session
session_start();

// Define base path
define('BASE_PATH', dirname(dirname(__DIR__)));

// Include the ProfileController
require_once BASE_PATH . '/app/controllers/ProfileController.php';

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
    // Get JSON data from request body
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // Check if JSON is valid
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid JSON data'
        ]);
        exit();
    }
    
    // Validate avatar field exists
    if (!isset($data['avatar']) || empty($data['avatar'])) {
        echo json_encode([
            'success' => false,
            'message' => 'No avatar selected'
        ]);
        exit();
    }
    
    $selectedAvatar = $data['avatar'];
    $userID = $_SESSION['user_id'];
    
    // Initialize controller and model
    $profileController = new ProfileController();
    
    // Get user model to validate and update
    require_once BASE_PATH . '/app/models/User.php';
    $userModel = new User();
    
    // Validate avatar path
    if (!$userModel->isValidAvatar($selectedAvatar)) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid avatar selected. Please choose from available avatars.'
        ]);
        exit();
    }
    
    // Update avatar in database
    if ($userModel->updateAvatar($userID, $selectedAvatar)) {
        // Update session avatar
        $_SESSION['avatar'] = $selectedAvatar;
        
        // Return success with full path for display
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
    // Log the error (in production, log to file instead of exposing)
    error_log("Avatar update error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'An unexpected error occurred. Please try again later.'
    ]);
}

exit();