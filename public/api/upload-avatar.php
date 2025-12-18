<?php
// public/api/upload-avatar.php

declare(strict_types=1);
ini_set('display_errors', 0);
error_reporting(0);

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('BASE_PATH', dirname(dirname(__DIR__)));

require_once BASE_PATH . '/app/models/User.php';

try {
    if (
        empty($_SESSION['user_id']) ||
        empty($_SESSION['logged_in']) ||
        $_SESSION['logged_in'] !== true
    ) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        exit;
    }

    if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Invalid upload']);
        exit;
    }

    $userID = (int) $_SESSION['user_id'];
    $file = $_FILES['avatar'];

    $userModel = new User();

    $validation = $userModel->validateAvatarUpload($file);
    if (!$validation['valid']) {
        echo json_encode(['success' => false, 'message' => $validation['error']]);
        exit;
    }

    $currentUser = $userModel->getUserById($userID);
    $oldAvatar = $currentUser['avatar'] ?? null;

    $result = $userModel->uploadCustomAvatar($userID, $file, $oldAvatar);

    if (!$result['success']) {
        echo json_encode([
            'success' => false,
            'message' => $result['error'] ?? 'Upload failed'
        ]);
        exit;
    }

    $_SESSION['avatar'] = $result['avatarPath'];

    echo json_encode([
        'success' => true,
        'message' => 'Custom avatar uploaded successfully',
        'avatar' => $result['avatarPath'],
        'displayPath' => '/agri_system/public' . $result['avatarPath']
    ]);
} catch (Throwable $e) {
    error_log('[upload-avatar] ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error']);
}

exit;
