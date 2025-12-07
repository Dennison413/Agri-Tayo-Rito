<?php
// app/controllers/ProductImageController.php
// Handles product image uploads, deletions, and management

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../models/Product.php';

class ProductImageController
{
    private $productModel;
    private $uploadDir;
    private $maxFileSize = 5242880; // 5MB
    private $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    private $maxImages = 5;

    public function __construct()
    {
        $this->productModel = new Product();
        $this->uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/agri_system/public/uploads/products/';
        
        // Create upload directory if it doesn't exist
        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0777, true);
        }
    }

    // Main handler for GET/POST requests
    public function handleRequest()
    {
        // Check authentication
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'seller') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $action = $_GET['action'] ?? $_POST['action'] ?? '';

        switch ($action) {
            case 'upload':
                $this->uploadImages();
                break;
            case 'set_main':
                $this->setMainImage();
                break;
            case 'delete':
                $this->deleteImage();
                break;
            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    }

    // Upload multiple images for a product
    private function uploadImages()
    {
        try {
            $productID = $_POST['productID'] ?? null;
            
            if (!$productID) {
                echo json_encode(['success' => false, 'message' => 'Product ID required']);
                return;
            }

            // Verify product ownership
            $db = new Database();
            $conn = $db->connect();
            $stmt = $conn->prepare("SELECT sellerID FROM products WHERE productID = ?");
            $stmt->execute([$productID]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$product || $product['sellerID'] != $this->getSellerID()) {
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                return;
            }

            // Check current image count
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM product_images WHERE productID = ?");
            $stmt->execute([$productID]);
            $currentCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

            if (!isset($_FILES['images']) || empty($_FILES['images']['name'][0])) {
                echo json_encode(['success' => false, 'message' => 'No images uploaded']);
                return;
            }

            $uploadedImages = [];
            $errors = [];
            $files = $_FILES['images'];
            $fileCount = count($files['name']);

            // Check if adding these images would exceed limit
            if (($currentCount + $fileCount) > $this->maxImages) {
                echo json_encode([
                    'success' => false, 
                    'message' => "Cannot upload {$fileCount} images. Maximum {$this->maxImages} images allowed per product (currently have {$currentCount})"
                ]);
                return;
            }

            // Process each uploaded file
            for ($i = 0; $i < $fileCount; $i++) {
                $fileName = $files['name'][$i];
                $fileTmp = $files['tmp_name'][$i];
                $fileSize = $files['size'][$i];
                $fileError = $files['error'][$i];
                $fileType = $files['type'][$i];

                // Validate file
                if ($fileError !== UPLOAD_ERR_OK) {
                    $errors[] = "{$fileName}: Upload error";
                    continue;
                }

                if ($fileSize > $this->maxFileSize) {
                    $errors[] = "{$fileName}: File too large (max 5MB)";
                    continue;
                }

                if (!in_array($fileType, $this->allowedTypes)) {
                    $errors[] = "{$fileName}: Invalid file type";
                    continue;
                }

                // Generate unique filename
                $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $newFileName = 'product_' . $productID . '_' . uniqid() . '.' . $extension;
                $targetPath = $this->uploadDir . $newFileName;

                // Move uploaded file
                if (move_uploaded_file($fileTmp, $targetPath)) {
                    $relativePath = '/uploads/products/' . $newFileName;
                    
                    // Determine if this should be main image (first image and no main exists)
                    $isMain = ($currentCount == 0 && $i == 0) ? 1 : 0;
                    
                    // Insert into database
                    $result = $this->productModel->addProductImage(
                        $productID,
                        $this->getSellerID(),
                        $relativePath,
                        $isMain,
                        null // Auto-increment order
                    );

                    if ($result['success']) {
                        $uploadedImages[] = [
                            'imageID' => $result['imageID'],
                            'path' => $relativePath,
                            'isMain' => $isMain
                        ];
                    } else {
                        unlink($targetPath); // Delete file if DB insert fails
                        $errors[] = "{$fileName}: " . $result['message'];
                    }
                } else {
                    $errors[] = "{$fileName}: Failed to move file";
                }
            }

            // Return results
            if (!empty($uploadedImages)) {
                $_SESSION['success'] = count($uploadedImages) . " image(s) uploaded successfully";
                echo json_encode([
                    'success' => true,
                    'message' => count($uploadedImages) . " image(s) uploaded successfully",
                    'images' => $uploadedImages,
                    'errors' => $errors
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'No images were uploaded',
                    'errors' => $errors
                ]);
            }

        } catch (Exception $e) {
            error_log("Image upload error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Upload failed: ' . $e->getMessage()]);
        }
    }

    // Set an image as the main product image
    private function setMainImage()
    {
        $imageID = $_GET['imageID'] ?? null;
        $productID = $_GET['productID'] ?? null;

        if (!$imageID || !$productID) {
            $_SESSION['error'] = 'Invalid parameters';
            $this->redirect($productID);
            return;
        }

        $result = $this->productModel->updateMainImage(
            $productID,
            $this->getSellerID(),
            $imageID
        );

        if ($result['success']) {
            $_SESSION['success'] = 'Main image updated successfully';
        } else {
            $_SESSION['error'] = $result['message'];
        }

        $this->redirect($productID);
    }

    // Delete a product image
    private function deleteImage()
    {
        $imageID = $_GET['imageID'] ?? null;
        $productID = $_GET['productID'] ?? null;

        if (!$imageID || !$productID) {
            $_SESSION['error'] = 'Invalid parameters';
            $this->redirect($productID);
            return;
        }

        $result = $this->productModel->deleteProductImage(
            $imageID,
            $this->getSellerID()
        );

        if ($result['success']) {
            $_SESSION['success'] = 'Image deleted successfully';
        } else {
            $_SESSION['error'] = $result['message'];
        }

        $this->redirect($productID);
    }

    // Helper: Get seller ID from session
    private function getSellerID()
    {
        $db = new Database();
        $conn = $db->connect();
        $stmt = $conn->prepare("SELECT sellerID FROM seller_profiles WHERE userID = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $seller = $stmt->fetch(PDO::FETCH_ASSOC);
        return $seller['sellerID'] ?? null;
    }

    // Helper: Redirect back to image upload page
    private function redirect($productID = null)
    {
        $url = BASE_URL . 'profile/seller/image-upload';
        if ($productID) {
            $url .= '?product=' . $productID;
        }
        header('Location: ' . $url);
        exit;
    }
}

// Handle request if called directly
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    $controller = new ProductImageController();
    $controller->handleRequest();
}