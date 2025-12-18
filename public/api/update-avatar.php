<?php
// public/api/update-avatar.php

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

    $data = json_decode(file_get_contents('php://input'), true);

    if (!is_array($data) || empty($data['avatar'])) {
        echo json_encode(['success' => false, 'message' => 'No avatar selected']);
        exit;
    }

    $avatarPath = trim($data['avatar']);
    $userID = (int) $_SESSION['user_id'];

    $userModel = new User();

    if (!$userModel->isValidAvatar($avatarPath)) {
        echo json_encode(['success' => false, 'message' => 'Invalid avatar']);
        exit;
    }

    if (!$userModel->updateAvatar($userID, $avatarPath)) {
        echo json_encode(['success' => false, 'message' => 'Failed to update avatar']);
        exit;
    }

    $_SESSION['avatar'] = $avatarPath;

    echo json_encode([
        'success' => true,
        'message' => 'Avatar updated successfully',
        'avatar' => $avatarPath,
        'displayPath' => '/agri_system/public' . $avatarPath
    ]);
} catch (Throwable $e) {
    error_log('[update-avatar] ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error']);
}

exit;
