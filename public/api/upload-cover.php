<?php
// public/api/upload-cover.php

declare(strict_types=1);
ini_set('display_errors', 0);
error_reporting(0);

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('BASE_PATH', dirname(dirname(__DIR__)));

require_once BASE_PATH . '/config/database.php';

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

    if (!isset($_FILES['cover']) || $_FILES['cover']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Invalid upload']);
        exit;
    }

    $userID = (int) $_SESSION['user_id'];
    $file = $_FILES['cover'];

    $allowed = ['image/jpeg', 'image/png'];
    $mime = mime_content_type($file['tmp_name']);

    if (!in_array($mime, $allowed, true)) {
        echo json_encode(['success' => false, 'message' => 'Invalid image type']);
        exit;
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'File too large']);
        exit;
    }

    $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/agri_system/public/uploads/profiles/';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
        echo json_encode(['success' => false, 'message' => 'Upload directory error']);
        exit;
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = "cover_{$userID}_" . time() . '.' . $ext;
    $fullPath = $uploadDir . $filename;
    $dbPath = '/uploads/profiles/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
        echo json_encode(['success' => false, 'message' => 'Failed to save image']);
        exit;
    }

    $db = new Database();
    $conn = $db->connect();

    $old = $conn->prepare("SELECT profile_image FROM users WHERE userID = ?");
    $old->execute([$userID]);
    $oldCover = $old->fetchColumn();

    $stmt = $conn->prepare("UPDATE users SET profile_image = ?, updated_at = NOW() WHERE userID = ?");
    $stmt->execute([$dbPath, $userID]);

    if ($oldCover && str_starts_with($oldCover, '/uploads/')) {
        $oldFile = $_SERVER['DOCUMENT_ROOT'] . '/agri_system/public' . $oldCover;
        if (is_file($oldFile)) unlink($oldFile);
    }

    $_SESSION['profile_image'] = $dbPath;

    echo json_encode([
        'success' => true,
        'message' => 'Cover updated successfully',
        'coverPath' => $dbPath,
        'displayPath' => '/agri_system/public' . $dbPath
    ]);
} catch (Throwable $e) {
    error_log('[upload-cover] ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error']);
}

exit;
