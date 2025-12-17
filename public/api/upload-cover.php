<?php
// public/api/upload-cover.php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('BASE_PATH', dirname(dirname(__DIR__)));
require_once BASE_PATH . '/app/models/User.php';
require_once BASE_PATH . '/config/database.php';

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
    if (!isset($_FILES['cover']) || $_FILES['cover']['error'] === UPLOAD_ERR_NO_FILE) {
        echo json_encode([
            'success' => false,
            'message' => 'No file uploaded'
        ]);
        exit();
    }
    
    $file = $_FILES['cover'];
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
    
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid file type. Only JPEG and PNG are allowed.'
        ]);
        exit();
    }
    
    $maxFileSize = 5 * 1024 * 1024;
    if ($file['size'] > $maxFileSize) {
        echo json_encode([
            'success' => false,
            'message' => 'File is too large. Maximum size is 5MB.'
        ]);
        exit();
    }

    $imageInfo = getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        echo json_encode([
            'success' => false,
            'message' => 'File is not a valid image.'
        ]);
        exit();
    }
    
    $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/agri_system/public/uploads/profiles/';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to create upload directory'
            ]);
            exit();
        }
    }
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = 'cover_' . $userID . '_' . time() . '.' . $extension;
    $fullPath = $uploadDir . $filename;
    $dbPath = '/uploads/profiles/' . $filename;
    
    if (!processAndSaveCoverImage($file['tmp_name'], $fullPath, $extension)) {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to process image'
        ]);
        exit();
    }
    
    try {
        $db = new Database();
        $conn = $db->connect();
        
        $stmt = $conn->prepare("SELECT profile_image FROM users WHERE userID = ?");
        $stmt->execute([$userID]);
        $oldCover = $stmt->fetchColumn();
        
        $stmt = $conn->prepare("UPDATE users SET profile_image = ?, updated_at = CURRENT_TIMESTAMP WHERE userID = ?");
        
        if ($stmt->execute([$dbPath, $userID])) {
            if ($oldCover && strpos($oldCover, '/uploads/profiles/') === 0) {
                $oldPath = $_SERVER['DOCUMENT_ROOT'] . '/agri_system/public' . $oldCover;
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }
            
            $_SESSION['profile_image'] = $dbPath;
            
            echo json_encode([
                'success' => true,
                'message' => 'Cover photo updated successfully!',
                'coverPath' => $dbPath,
                'displayPath' => '/agri_system/public' . $dbPath
            ]);
        } else {
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
            
            echo json_encode([
                'success' => false,
                'message' => 'Failed to update database'
            ]);
        }
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
        
        echo json_encode([
            'success' => false,
            'message' => 'Database error occurred'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Cover upload error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'An unexpected error occurred. Please try again later.'
    ]);
}

// Function to process and save the cover image
function processAndSaveCoverImage($sourcePath, $destinationPath, $extension) {
    try {
        list($width, $height) = getimagesize($sourcePath);
        
        $targetWidth = 1200;
        $targetHeight = 400;
        
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                $sourceImage = imagecreatefromjpeg($sourcePath);
                break;
            case 'png':
                $sourceImage = imagecreatefrompng($sourcePath);
                break;
            default:
                return false;
        }
        
        if (!$sourceImage) {
            return false;
        }
        
        $sourceAspect = $width / $height;
        $targetAspect = $targetWidth / $targetHeight;
        
        if ($sourceAspect > $targetAspect) {
            $newHeight = $height;
            $newWidth = $height * $targetAspect;
            $cropX = ($width - $newWidth) / 2;
            $cropY = 0;
        } else {
            $newWidth = $width;
            $newHeight = $width / $targetAspect;
            $cropX = 0;
            $cropY = ($height - $newHeight) / 2;
        }
        
        $newImage = imagecreatetruecolor($targetWidth, $targetHeight);
        
        if ($extension === 'png') {
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
            imagefilledrectangle($newImage, 0, 0, $targetWidth, $targetHeight, $transparent);
        }
        
        imagecopyresampled(
            $newImage, $sourceImage,
            0, 0,
            $cropX, $cropY,
            $targetWidth, $targetHeight,
            $newWidth, $newHeight
        );
        
        $result = false;
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                $result = imagejpeg($newImage, $destinationPath, 90);
                break;
            case 'png':
                $result = imagepng($newImage, $destinationPath, 8);
                break;
        }
        
        imagedestroy($sourceImage);
        imagedestroy($newImage);
        
        return $result;
        
    } catch (Exception $e) {
        error_log("Error processing cover image: " . $e->getMessage());
        return false;
    }
}

exit();