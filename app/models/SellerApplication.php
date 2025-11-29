<?php
// app/models/SellerApplication.php
// Seller Application Management - Application Submission, Review, Approval/Rejection
require_once __DIR__ . '/../../config/database.php';

class SellerApplication
{
    private $conn;
    private $table = 'seller_applications';
    private $sellerProfilesTable = 'seller_profiles';

    // File upload configuration
    private $uploadPath = '/uploads/business_permits/';
    private $maxFileSize = 5242880; // 5MB
    private $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->connect();
    }

    // ==================== APPLICATION SUBMISSION ====================

    /**
     * Submit seller application
     * Used by: Buyers who want to become sellers
     */
    public function submitApplication($userID, $data, $permitFile = null)
    {
        try {
            // Check if user already has a pending or approved application
            $checkQuery = "SELECT applicationID, application_status 
                          FROM {$this->table} 
                          WHERE userID = ? 
                          ORDER BY applied_at DESC 
                          LIMIT 1";
            $stmt = $this->conn->prepare($checkQuery);
            $stmt->execute([$userID]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                if ($existing['application_status'] === 'pending') {
                    return [
                        'success' => false,
                        'message' => 'You already have a pending application'
                    ];
                } elseif ($existing['application_status'] === 'approved') {
                    return [
                        'success' => false,
                        'message' => 'You are already a seller'
                    ];
                }
            }

            // Handle business permit upload
            $permitPath = null;
            if ($permitFile && $permitFile['error'] === UPLOAD_ERR_OK) {
                $uploadResult = $this->uploadBusinessPermit($userID, $permitFile);
                if (!$uploadResult['success']) {
                    return $uploadResult;
                }
                $permitPath = $uploadResult['file_path'];
            }

            // Insert application
            $query = "INSERT INTO {$this->table} 
                     (userID, business_name, business_address, business_permit, application_status) 
                     VALUES (?, ?, ?, ?, 'pending')";

            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([
                $userID,
                $data['business_name'],
                $data['business_address'],
                $permitPath
            ]);

            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Application submitted successfully. Please wait for admin approval.',
                    'applicationID' => $this->conn->lastInsertId()
                ];
            }
        } catch (PDOException $e) {
            error_log("Submit application error: " . $e->getMessage());
        }

        return ['success' => false, 'message' => 'Failed to submit application'];
    }

    /**
     * Upload business permit document
     */
    private function uploadBusinessPermit($userID, $file)
    {
        try {
            // Validate file
            $validation = $this->validatePermitFile($file);
            if (!$validation['valid']) {
                return ['success' => false, 'message' => $validation['error']];
            }

            // Create upload directory if it doesn't exist
            $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/agri_system/public' . $this->uploadPath;
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Generate unique filename
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $filename = 'permit_user_' . $userID . '_' . time() . '.' . $extension;
            $fullPath = $uploadDir . $filename;
            $dbPath = $this->uploadPath . $filename;

            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $fullPath)) {
                return [
                    'success' => true,
                    'file_path' => $dbPath
                ];
            }

            return ['success' => false, 'message' => 'Failed to upload file'];
        } catch (Exception $e) {
            error_log("Upload permit error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Upload error occurred'];
        }
    }

    /**
     * Validate business permit file
     */
    private function validatePermitFile($file)
    {
        // Check file size
        if ($file['size'] > $this->maxFileSize) {
            return [
                'valid' => false,
                'error' => 'File is too large. Maximum size is 5MB.'
            ];
        }

        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $this->allowedTypes)) {
            return [
                'valid' => false,
                'error' => 'Invalid file type. Only JPG, PNG, and PDF are allowed.'
            ];
        }

        return ['valid' => true];
    }

    // ==================== APPLICATION RETRIEVAL ====================

    /**
     * Get application by ID
     */
    public function getApplicationById($applicationID)
{
    $query = "SELECT sa.*, 
                    u.full_name, u.email, u.phone,
                    reviewer.full_name as reviewed_by_name
             FROM {$this->table} sa
             JOIN users u ON sa.userID = u.userID
             LEFT JOIN users reviewer ON sa.reviewed_by = reviewer.userID
             WHERE sa.applicationID = ?";

    $stmt = $this->conn->prepare($query);
    $stmt->execute([$applicationID]);

    return $stmt->fetch(PDO::FETCH_ASSOC);
}

    /**
     * Get application by user ID
     */
   public function getApplicationByUser($userID)
{
    $query = "SELECT sa.*,
                    reviewer.full_name as reviewed_by_name
             FROM {$this->table} sa
             LEFT JOIN users reviewer ON sa.reviewed_by = reviewer.userID
             WHERE sa.userID = ?
             ORDER BY sa.applied_at DESC
             LIMIT 1";

    $stmt = $this->conn->prepare($query);
    $stmt->execute([$userID]);

    return $stmt->fetch(PDO::FETCH_ASSOC);
}

    /**
     * Get all applications with filters
     * Used by: Admin to view and manage applications
     */
    public function getAllApplications($filters = [], $limit = 50, $offset = 0)
{
    $query = "SELECT sa.*, 
                u.full_name, u.email, u.phone,
                reviewer.full_name as reviewed_by_name
         FROM {$this->table} sa
         JOIN users u ON sa.userID = u.userID
         LEFT JOIN users reviewer ON sa.reviewed_by = reviewer.userID
         WHERE 1=1";

    $params = [];

    // Status filter
    if (!empty($filters['status'])) {
        $query .= " AND sa.application_status = ?";
        $params[] = $filters['status'];
    }

    // Search filter
    if (!empty($filters['search'])) {
        $query .= " AND (u.full_name LIKE ? OR u.email LIKE ? OR sa.business_name LIKE ?)";
        $searchTerm = '%' . $filters['search'] . '%';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    // Date range filter
    if (!empty($filters['start_date'])) {
        $query .= " AND DATE(sa.applied_at) >= ?";
        $params[] = $filters['start_date'];
    }
    if (!empty($filters['end_date'])) {
        $query .= " AND DATE(sa.applied_at) <= ?";
        $params[] = $filters['end_date'];
    }

    // Sorting
    $sortOptions = [
        'newest' => 'sa.applied_at DESC',
        'oldest' => 'sa.applied_at ASC',
        'name' => 'u.full_name ASC',
        'business' => 'sa.business_name ASC'
    ];

    $sortBy = $filters['sort'] ?? 'newest';
    $query .= " ORDER BY " . ($sortOptions[$sortBy] ?? $sortOptions['newest']);

    // LIMIT and OFFSET - bind as integers
    $query .= " LIMIT ? OFFSET ?";

    $stmt = $this->conn->prepare($query);

    // Bind all string/text parameters first
    foreach ($params as $index => $param) {
        $stmt->bindValue($index + 1, $param);
    }

    // Bind LIMIT and OFFSET as integers
    $stmt->bindValue(count($params) + 1, (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(count($params) + 2, (int)$offset, PDO::PARAM_INT);

    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function getRecentApplications($limit = 10)
{
    $query = "SELECT sa.*, 
                    u.full_name, u.email
             FROM {$this->table} sa
             JOIN users u ON sa.userID = u.userID
             ORDER BY sa.applied_at DESC
             LIMIT ?";

    $stmt = $this->conn->prepare($query);
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

    /**
     * Get pending applications count
     */
    public function getPendingCount()
    {
        $query = "SELECT COUNT(*) as count FROM {$this->table} 
                 WHERE application_status = 'pending'";
        $stmt = $this->conn->query($query);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result['count'] ?? 0;
    }

    /**
     * Get pending applications
     */
    public function getPendingApplications($limit = 50)
    {
        return $this->getAllApplications(['status' => 'pending'], $limit, 0);
    }

    // ==================== APPLICATION APPROVAL/REJECTION ====================

    /**
     * Approve seller application
     * Creates seller profile and shop, updates user role
     */
    public function approveApplication($applicationID, $adminID)
    {
        try {
            $this->conn->beginTransaction();

            // Get application details
            $application = $this->getApplicationById($applicationID);

            if (!$application) {
                return ['success' => false, 'message' => 'Application not found'];
            }

            if ($application['application_status'] !== 'pending') {
                return ['success' => false, 'message' => 'Application already processed'];
            }

            // Update application status
            $updateQuery = "UPDATE {$this->table} 
                           SET application_status = 'approved',
                               reviewed_by = ?,
                               reviewed_at = NOW()
                           WHERE applicationID = ?";
            $stmt = $this->conn->prepare($updateQuery);
            $stmt->execute([$adminID, $applicationID]);

            // Update user role to seller
            $userModel = new User();
            $roleResult = $userModel->updateUserRole($application['userID'], 'seller');

            if (!$roleResult['success']) {
                throw new Exception('Failed to update user role');
            }

            // Create seller profile
            $profileQuery = "INSERT INTO {$this->sellerProfilesTable} 
                            (userID, business_name, business_description, farm_location, is_verified) 
                            VALUES (?, ?, NULL, ?, 1)";
            $profileStmt = $this->conn->prepare($profileQuery);
            $profileStmt->execute([
                $application['userID'],
                $application['business_name'],
                $application['business_address']
            ]);
            $sellerID = $this->conn->lastInsertId();

            // Create shop
            $shopModel = new Shop();
            $shopResult = $shopModel->createShop($sellerID, [
                'shop_name' => $application['business_name'],
                'shop_description' => "Welcome to {$application['business_name']}!",
                'farm_location' => $application['business_address'],
                'contact_number' => $application['phone'] ?? null
            ]);

            if (!$shopResult['success']) {
                throw new Exception('Failed to create shop');
            }

            $this->conn->commit();

            return [
                'success' => true,
                'message' => 'Application approved successfully. Seller account created.',
                'sellerID' => $sellerID,
                'shopID' => $shopResult['shopID']
            ];
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Approve application error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to approve application: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Reject seller application
     */
    public function rejectApplication($applicationID, $adminID, $reason = null)
    {
        try {
            // Get application details
            $application = $this->getApplicationById($applicationID);

            if (!$application) {
                return ['success' => false, 'message' => 'Application not found'];
            }

            if ($application['application_status'] !== 'pending') {
                return ['success' => false, 'message' => 'Application already processed'];
            }

            // Update application status
            $query = "UPDATE {$this->table} 
                     SET application_status = 'rejected',
                         reviewed_by = ?,
                         reviewed_at = NOW()
                     WHERE applicationID = ?";

            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([$adminID, $applicationID]);

            if ($result) {
                // Optional: Send rejection notification to user
                // TODO: Implement notification system

                return [
                    'success' => true,
                    'message' => 'Application rejected successfully'
                ];
            }
        } catch (PDOException $e) {
            error_log("Reject application error: " . $e->getMessage());
        }

        return ['success' => false, 'message' => 'Failed to reject application'];
    }

    // ==================== APPLICATION STATISTICS ====================

    /**
     * Get application statistics for admin dashboard
     */
    public function getApplicationStats()
    {
        $query = "SELECT 
                    COUNT(*) as total_applications,
                    SUM(CASE WHEN application_status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN application_status = 'approved' THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN application_status = 'rejected' THEN 1 ELSE 0 END) as rejected,
                    SUM(CASE WHEN DATE(applied_at) = CURDATE() THEN 1 ELSE 0 END) as today,
                    SUM(CASE WHEN DATE(applied_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as this_week,
                    SUM(CASE WHEN DATE(applied_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as this_month
                 FROM {$this->table}";

        $stmt = $this->conn->query($query);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get approval rate
     */
    public function getApprovalRate()
    {
        $query = "SELECT 
                    COUNT(*) as total_processed,
                    SUM(CASE WHEN application_status = 'approved' THEN 1 ELSE 0 END) as approved
                 FROM {$this->table}
                 WHERE application_status IN ('approved', 'rejected')";

        $stmt = $this->conn->query($query);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result['total_processed'] > 0) {
            $approvalRate = ($result['approved'] / $result['total_processed']) * 100;
            return round($approvalRate, 1);
        }

        return 0;
    }

    /**
     * Get applications by date range
     */
    public function getApplicationsByDateRange($startDate, $endDate)
    {
        $query = "SELECT 
                    DATE(applied_at) as date,
                    COUNT(*) as total,
                    SUM(CASE WHEN application_status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN application_status = 'approved' THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN application_status = 'rejected' THEN 1 ELSE 0 END) as rejected
                 FROM {$this->table}
                 WHERE DATE(applied_at) BETWEEN ? AND ?
                 GROUP BY DATE(applied_at)
                 ORDER BY date ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$startDate, $endDate]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ==================== UTILITY METHODS ====================

    /**
     * Check if user can apply (no pending or approved applications)
     */
    public function canUserApply($userID)
    {
        $query = "SELECT application_status 
                 FROM {$this->table} 
                 WHERE userID = ? 
                 ORDER BY applied_at DESC 
                 LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$userID]);
        $latest = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$latest) {
            return ['can_apply' => true];
        }

        if ($latest['application_status'] === 'pending') {
            return [
                'can_apply' => false,
                'message' => 'You have a pending application'
            ];
        }

        if ($latest['application_status'] === 'approved') {
            return [
                'can_apply' => false,
                'message' => 'You are already a seller'
            ];
        }

        // If rejected, user can reapply
        return [
            'can_apply' => true,
            'message' => 'You can reapply'
        ];
    }

    /**
     * Check application status for user
     */
    public function getApplicationStatus($userID)
    {
        $query = "SELECT application_status, applied_at, reviewed_at 
                 FROM {$this->table} 
                 WHERE userID = ? 
                 ORDER BY applied_at DESC 
                 LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([$userID]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Delete application (if rejected and user wants to clear it)
     */
    public function deleteApplication($applicationID, $userID)
    {
        // Verify ownership
        $query = "SELECT application_status FROM {$this->table} 
                 WHERE applicationID = ? AND userID = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$applicationID, $userID]);
        $application = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$application) {
            return ['success' => false, 'message' => 'Application not found'];
        }

        // Only allow deletion of rejected applications
        if ($application['application_status'] !== 'rejected') {
            return ['success' => false, 'message' => 'Can only delete rejected applications'];
        }

        $deleteQuery = "DELETE FROM {$this->table} WHERE applicationID = ?";

        try {
            $deleteStmt = $this->conn->prepare($deleteQuery);
            $result = $deleteStmt->execute([$applicationID]);

            if ($result) {
                return ['success' => true, 'message' => 'Application deleted successfully'];
            }
        } catch (PDOException $e) {
            error_log("Delete application error: " . $e->getMessage());
        }

        return ['success' => false, 'message' => 'Failed to delete application'];
    }
}
