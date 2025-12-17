<?php
// app/models/User.php
require_once __DIR__ . '/../../config/database.php';

class User
{
    private $conn;
    private $table = 'users';

    // upload configuration
    private $maxFileSize = 2097152; // 2MB in bytes
    private $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    private $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    private $uploadPath = '/uploads/profiles/';

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->connect();
    }

    public function getConnection()
    {
        return $this->conn;
    }

    public function getUserByEmail($email)
    {
        $query = "SELECT * FROM " . $this->table . " WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getUserById($userID)
    {
        try {
            $query = "SELECT * FROM " . $this->table . " WHERE userID = :userID LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':userID', $userID);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$result) {
                error_log("User not found with ID: " . $userID);
                return null;
            }

            return $result;
        } catch (PDOException $e) {
            error_log("Error in getUserById: " . $e->getMessage());
            return null;
        }
    }

    // get user with their addresses
    public function getUserWithAddresses($userID)
    {
        try {
            $query = "SELECT u.*, 
                      (SELECT COUNT(*) FROM user_addresses WHERE userID = u.userID) as address_count
                      FROM " . $this->table . " u 
                      WHERE u.userID = :userID LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':userID', $userID);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getUserWithAddresses: " . $e->getMessage());
            return null;
        }
    }

    // check if email already exists
    public function emailExists($email)
    {
        $query = "SELECT userID FROM {$this->table} WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }

    public function createUser($data)
    {
        if ($this->emailExists($data['email'])) {
            return [
                'success' => false,
                'message' => 'Email already exists'
            ];
        }

        $hasPhone = !empty($data['phone']);

        if ($hasPhone) {
            $query = "INSERT INTO " . $this->table . " 
                  (email, password_hash, full_name, phone, role) 
                  VALUES (:email, :password_hash, :full_name, :phone, :role)";
        } else {
            $query = "INSERT INTO " . $this->table . " 
                  (email, password_hash, full_name, role) 
                  VALUES (:email, :password_hash, :full_name, :role)";
        }

        try {
            $stmt = $this->conn->prepare($query);

            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':password_hash', $data['password_hash']);
            $stmt->bindParam(':full_name', $data['full_name']);
            $stmt->bindParam(':role', $data['role']);

            if ($hasPhone) {
                $stmt->bindParam(':phone', $data['phone']);
            }

            if ($stmt->execute()) {
                $userID = $this->conn->lastInsertId();
                return [
                    'success' => true,
                    'message' => 'User created successfully',
                    'userID' => $userID
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to create user'
            ];
        } catch (PDOException $e) {
            error_log("Create user error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Database error occurred'
            ];
        }
    }

    // update user details
    public function updateUser($userID, $data)
    {
        $updates = [];
        $params = [':userID' => $userID];

        if (isset($data['full_name'])) {
            $updates[] = "full_name = :full_name";
            $params[':full_name'] = $data['full_name'];
        }

        if (isset($data['phone'])) {
            $updates[] = "phone = :phone";
            $params[':phone'] = $data['phone'];
        }

        if (empty($updates)) {
            return true; 
        }

        $query = "UPDATE " . $this->table . " 
                  SET " . implode(', ', $updates) . ", 
                      updated_at = CURRENT_TIMESTAMP
                  WHERE userID = :userID";

        try {
            $stmt = $this->conn->prepare($query);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Update user error: " . $e->getMessage());
            return false;
        }
    }

    // update user password
    public function updatePassword($userID, $newPasswordHash)
    {
        $query = "UPDATE {$this->table} 
                  SET password_hash = :password_hash, 
                      updated_at = CURRENT_TIMESTAMP 
                  WHERE userID = :userID";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':password_hash', $newPasswordHash);
            $stmt->bindParam(':userID', $userID);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Update password error: " . $e->getMessage());
            return false;
        }
    }

    // update user role (for seller approval or admin changes)
    public function updateUserRole($userID, $newRole)
    {
        $validRoles = ['buyer', 'seller', 'admin'];

        if (!in_array($newRole, $validRoles)) {
            return [
                'success' => false,
                'message' => 'Invalid role'
            ];
        }

        $query = "UPDATE {$this->table} 
                  SET role = :role, 
                      updated_at = CURRENT_TIMESTAMP 
                  WHERE userID = :userID";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':role', $newRole);
            $stmt->bindParam(':userID', $userID);

            if ($stmt->execute()) {
                return [
                    'success' => true,
                    'message' => 'User role updated successfully'
                ];
            }
        } catch (PDOException $e) {
            error_log("Update role error: " . $e->getMessage());
        }

        return [
            'success' => false,
            'message' => 'Failed to update user role'
        ];
    }

    // deactivate user account
    public function deactivateUser($userID)
    {
        $query = "UPDATE {$this->table} 
                  SET is_active = 0, 
                      updated_at = CURRENT_TIMESTAMP 
                  WHERE userID = :userID";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':userID', $userID);

        return $stmt->execute();
    }

    // reactivate user account
    public function activateUser($userID)
    {
        $query = "UPDATE {$this->table} 
                  SET is_active = 1, 
                      updated_at = CURRENT_TIMESTAMP 
                  WHERE userID = :userID";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':userID', $userID);

        return $stmt->execute();
    }

    // permanently delete user
    public function deleteUser($userID)
    {
        try {
            $user = $this->getUserById($userID);

            $query = "DELETE FROM {$this->table} WHERE userID = :userID";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':userID', $userID);

            if ($stmt->execute()) {
                // Delete custom avatar if exists
                if ($user && $this->isCustomAvatar($user['avatar'])) {
                    $this->deleteOldCustomAvatar($user['avatar']);
                }
                return true;
            }

            return false;
        } catch (PDOException $e) {
            error_log("Delete user error: " . $e->getMessage());
            return false;
        }
    }

    // get all users by role
    public function getUsersByRole($role, $limit = 50, $offset = 0)
    {
        $query = "SELECT userID, email, full_name, phone, 
                         is_active, created_at, updated_at
                  FROM {$this->table} 
                  WHERE role = :role 
                  ORDER BY created_at DESC 
                  LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':role', $role, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // get user statistics
    public function getUserStats()
    {
        $query = "SELECT 
                    COUNT(*) as total_users,
                    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_users,
                    SUM(CASE WHEN role = 'buyer' THEN 1 ELSE 0 END) as total_buyers,
                    SUM(CASE WHEN role = 'seller' THEN 1 ELSE 0 END) as total_sellers,
                    SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as total_admins,
                    SUM(CASE WHEN is_active = 1 AND role = 'buyer' THEN 1 ELSE 0 END) as active_buyers,
                    SUM(CASE WHEN is_active = 1 AND role = 'seller' THEN 1 ELSE 0 END) as active_sellers
                  FROM {$this->table}";

        $stmt = $this->conn->query($query);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateAvatar($userID, $avatar)
    {
        try {
            $query = "UPDATE " . $this->table . " 
                      SET avatar = :avatar, 
                          updated_at = CURRENT_TIMESTAMP
                      WHERE userID = :userID";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':userID', $userID);
            $stmt->bindParam(':avatar', $avatar);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error updating avatar: " . $e->getMessage());
            return false;
        }
    }

    public function getAvailableAvatars()
    {
        $avatars = [];
        for ($i = 1; $i <= 13; $i++) {
            $avatars[] = "/images/avatars/avt{$i}.jpg";
        }
        return $avatars;
    }

    public function isValidAvatar($avatar)
    {
        $validAvatars = $this->getAvailableAvatars();
        return in_array($avatar, $validAvatars);
    }

    // custom avatar upload validation
    public function validateAvatarUpload($file)
    {
        // Check file size
        if ($file['size'] > $this->maxFileSize) {
            return [
                'valid' => false,
                'error' => 'File is too large. Maximum size is 2MB.'
            ];
        }

        // check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $this->allowedTypes)) {
            return [
                'valid' => false,
                'error' => 'Invalid file type. Only JPEG, PNG, and GIF are allowed.'
            ];
        }

        // check file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowedExtensions)) {
            return [
                'valid' => false,
                'error' => 'Invalid file extension.'
            ];
        }

        // verify it's actually an image
        $imageInfo = getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            return [
                'valid' => false,
                'error' => 'File is not a valid image.'
            ];
        }

        return ['valid' => true];
    }

    public function uploadCustomAvatar($userID, $file, $oldAvatar = null)
    {
        try {
            // create upload directory if it doesn't exist
            $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/agri_system/public' . $this->uploadPath;
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // generate unique filename
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $filename = 'user_' . $userID . '_' . uniqid() . '.' . $extension;
            $fullPath = $uploadDir . $filename;
            $dbPath = $this->uploadPath . $filename;

            // process and resize image
            $processResult = $this->processImage($file['tmp_name'], $fullPath, $extension);

            if (!$processResult) {
                return [
                    'success' => false,
                    'error' => 'Failed to process image'
                ];
            }

            // update database
            if ($this->updateAvatar($userID, $dbPath)) {
                $this->deleteOldCustomAvatar($oldAvatar);

                return [
                    'success' => true,
                    'avatarPath' => $dbPath
                ];
            } else {
                if (file_exists($fullPath)) {
                    unlink($fullPath);
                }

                return [
                    'success' => false,
                    'error' => 'Failed to update database'
                ];
            }
        } catch (Exception $e) {
            error_log("Error in uploadCustomAvatar: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'An error occurred during upload'
            ];
        }
    }

    private function processImage($sourcePath, $destinationPath, $extension)
    {
        try {
            list($width, $height) = getimagesize($sourcePath);  // get original image dimensions

            $maxSize = 500;  // calculate new dimensions (max 500x500, maintain aspect ratio)
            $ratio = $width / $height;

            if ($width > $height) {
                $newWidth = $maxSize;
                $newHeight = $maxSize / $ratio;
            } else {
                $newHeight = $maxSize;
                $newWidth = $maxSize * $ratio;
            }

            // Create image resource based on file type
            switch ($extension) {
                case 'jpg':
                case 'jpeg':
                    $sourceImage = imagecreatefromjpeg($sourcePath);
                    break;
                case 'png':
                    $sourceImage = imagecreatefrompng($sourcePath);
                    break;
                case 'gif':
                    $sourceImage = imagecreatefromgif($sourcePath);
                    break;
                default:
                    return false;
            }

            if (!$sourceImage) {
                return false;
            }

            // create new image with new dimensions
            $newImage = imagecreatetruecolor($newWidth, $newHeight);

            // preserve transparency for PNG and GIF
            if ($extension === 'png' || $extension === 'gif') {
                imagealphablending($newImage, false);
                imagesavealpha($newImage, true);
                $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
                imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
            }

            // Resize image
            imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

            // Save image based on file type
            $result = false;
            switch ($extension) {
                case 'jpg':
                case 'jpeg':
                    $result = imagejpeg($newImage, $destinationPath, 90);
                    break;
                case 'png':
                    $result = imagepng($newImage, $destinationPath, 8);
                    break;
                case 'gif':
                    $result = imagegif($newImage, $destinationPath);
                    break;
            }

            // Free memory
            imagedestroy($sourceImage);
            imagedestroy($newImage);

            return $result;
        } catch (Exception $e) {
            error_log("Error processing image: " . $e->getMessage());
            return false;
        }
    }

    private function deleteOldCustomAvatar($oldAvatar)
    {
        if (empty($oldAvatar)) {
            return;
        }

        if (strpos($oldAvatar, '/images/avatars/') !== false) { // don't delete preset avatars
            return;
        }

        // Only delete custom avatars in /uploads/profiles/
        if (strpos($oldAvatar, $this->uploadPath) === 0) {
            $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/agri_system/public' . $oldAvatar;
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        }
    }

    public function isCustomAvatar($avatar)
    {
        return !empty($avatar) && strpos($avatar, $this->uploadPath) === 0;
    }
}
