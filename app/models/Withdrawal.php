<?php
// app/models/Withdrawal.php
// Handles seller withdrawal requests and balance management

require_once __DIR__ . '/../../config/database.php';

class Withdrawal 
{
    private $conn;
    private $db;

    public function __construct() 
    {
        $this->db = new Database();
        $this->conn = $this->db->connect();
    }

    // ==================== CREATE WITHDRAWAL REQUEST ====================
    
    /**
     * Create a new withdrawal request
     * @param int $shopID
     * @param float $amount
     * @param string $method - 'cash' or 'atm'
     * @param string|null $atmCard - optional ATM card number
     * @return array ['success' => bool, 'message' => string, 'withdrawal_id' => int]
     */
    public function createRequest($shopID, $amount, $method = 'cash', $atmCard = null) 
    {
        try {
            // Get shop details and balance
            $stmt = $this->conn->prepare("
                SELECT shopID, shop_name, balance, atm_card_number 
                FROM shops 
                WHERE shopID = ? AND is_active = 1
            ");
            $stmt->execute([$shopID]);
            $shop = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$shop) {
                return [
                    'success' => false,
                    'message' => 'Shop not found or inactive'
                ];
            }

            // Validate withdrawal amount
            $minWithdrawal = $this->getMinWithdrawalAmount();
            
            if ($amount < $minWithdrawal) {
                return [
                    'success' => false,
                    'message' => "Minimum withdrawal amount is ₱{$minWithdrawal}"
                ];
            }

            if ($amount > $shop['balance']) {
                return [
                    'success' => false,
                    'message' => 'Insufficient balance. Available: ₱' . number_format($shop['balance'], 2)
                ];
            }

            // Validate ATM method
            if ($method === 'atm' && empty($shop['atm_card_number'])) {
                return [
                    'success' => false,
                    'message' => 'No ATM card registered. Please register your card first or choose cash withdrawal.'
                ];
            }

            // Check for pending withdrawals
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as pending_count 
                FROM withdrawal_requests 
                WHERE shopID = ? AND status = 'pending'
            ");
            $stmt->execute([$shopID]);
            $pending = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($pending['pending_count'] > 0) {
                return [
                    'success' => false,
                    'message' => 'You have a pending withdrawal request. Please wait for admin approval.'
                ];
            }

            // Create withdrawal request
            $stmt = $this->conn->prepare("
                INSERT INTO withdrawal_requests 
                (shopID, amount, withdrawal_method, atm_card_presented, status, requested_at)
                VALUES (?, ?, ?, ?, 'pending', NOW())
            ");
            
            $stmt->execute([
                $shopID,
                $amount,
                $method,
                $atmCard ?? $shop['atm_card_number']
            ]);

            $withdrawalID = $this->conn->lastInsertId();

            return [
                'success' => true,
                'message' => 'Withdrawal request submitted successfully. Please wait for admin approval.',
                'withdrawal_id' => $withdrawalID
            ];

        } catch (Exception $e) {
            error_log("Create withdrawal error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to create withdrawal request: ' . $e->getMessage()
            ];
        }
    }

    // ==================== APPROVE WITHDRAWAL ====================
    
    /**
     * Approve withdrawal request (Admin only)
     * This triggers the DB trigger to deduct balance
     * @param int $withdrawalID
     * @param int $adminID
     * @param string|null $notes
     * @return array
     */
    public function approveWithdrawal($withdrawalID, $adminID, $notes = null) 
    {
        try {
            $this->conn->beginTransaction();

            // Get withdrawal details
            $stmt = $this->conn->prepare("
                SELECT wr.*, s.shop_name, s.balance 
                FROM withdrawal_requests wr
                JOIN shops s ON wr.shopID = s.shopID
                WHERE wr.withdrawalID = ?
            ");
            $stmt->execute([$withdrawalID]);
            $withdrawal = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$withdrawal) {
                $this->conn->rollBack();
                return [
                    'success' => false,
                    'message' => 'Withdrawal request not found'
                ];
            }

            if ($withdrawal['status'] !== 'pending') {
                $this->conn->rollBack();
                return [
                    'success' => false,
                    'message' => 'Withdrawal request is already processed'
                ];
            }

            // Check if shop still has sufficient balance
            if ($withdrawal['amount'] > $withdrawal['balance']) {
                $this->conn->rollBack();
                return [
                    'success' => false,
                    'message' => 'Insufficient shop balance'
                ];
            }

            // Update withdrawal status to 'completed' (this triggers DB trigger to deduct balance)
            $stmt = $this->conn->prepare("
                UPDATE withdrawal_requests 
                SET status = 'completed',
                    processed_by = ?,
                    processed_at = NOW(),
                    notes = ?
                WHERE withdrawalID = ?
            ");
            $stmt->execute([$adminID, $notes, $withdrawalID]);

            $this->conn->commit();

            return [
                'success' => true,
                'message' => "Withdrawal approved! ₱" . number_format($withdrawal['amount'], 2) . " will be deducted from {$withdrawal['shop_name']}'s balance."
            ];

        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Approve withdrawal error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to approve withdrawal: ' . $e->getMessage()
            ];
        }
    }

    // ==================== REJECT WITHDRAWAL ====================
    
    /**
     * Reject withdrawal request
     * @param int $withdrawalID
     * @param int $adminID
     * @param string $reason
     * @return array
     */
    public function rejectWithdrawal($withdrawalID, $adminID, $reason) 
    {
        try {
            // Get withdrawal details
            $stmt = $this->conn->prepare("
                SELECT * FROM withdrawal_requests WHERE withdrawalID = ?
            ");
            $stmt->execute([$withdrawalID]);
            $withdrawal = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$withdrawal) {
                return [
                    'success' => false,
                    'message' => 'Withdrawal request not found'
                ];
            }

            if ($withdrawal['status'] !== 'pending') {
                return [
                    'success' => false,
                    'message' => 'Withdrawal request is already processed'
                ];
            }

            // Update status to rejected
            $stmt = $this->conn->prepare("
                UPDATE withdrawal_requests 
                SET status = 'rejected',
                    processed_by = ?,
                    processed_at = NOW(),
                    rejection_reason = ?
                WHERE withdrawalID = ?
            ");
            $stmt->execute([$adminID, $reason, $withdrawalID]);

            return [
                'success' => true,
                'message' => 'Withdrawal request rejected'
            ];

        } catch (Exception $e) {
            error_log("Reject withdrawal error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to reject withdrawal: ' . $e->getMessage()
            ];
        }
    }

    // ==================== GET WITHDRAWAL HISTORY ====================
    
    /**
     * Get withdrawal history by shop
     * @param int $shopID
     * @param int $limit
     * @return array
     */
    public function getWithdrawalsByShop($shopID, $limit = 20) 
    {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    wr.*,
                    u.full_name as processed_by_name
                FROM withdrawal_requests wr
                LEFT JOIN users u ON wr.processed_by = u.userID
                WHERE wr.shopID = ?
                ORDER BY wr.requested_at DESC
                LIMIT ?
            ");
            $stmt->execute([$shopID, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("Get withdrawals error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all pending withdrawal requests (Admin)
     * @return array
     */
    public function getPendingWithdrawals() 
    {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    wr.*,
                    s.shop_name,
                    s.balance as current_balance,
                    sp.business_name,
                    u.full_name as seller_name,
                    u.phone as seller_phone
                FROM withdrawal_requests wr
                JOIN shops s ON wr.shopID = s.shopID
                JOIN seller_profiles sp ON s.sellerID = sp.sellerID
                JOIN users u ON sp.userID = u.userID
                WHERE wr.status = 'pending'
                ORDER BY wr.requested_at ASC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("Get pending withdrawals error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all withdrawal history (Admin)
     * @param int $limit
     * @return array
     */
    public function getAllWithdrawals($limit = 50) 
    {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    wr.*,
                    s.shop_name,
                    u.full_name as seller_name,
                    admin.full_name as processed_by_name
                FROM withdrawal_requests wr
                JOIN shops s ON wr.shopID = s.shopID
                JOIN seller_profiles sp ON s.sellerID = sp.sellerID
                JOIN users u ON sp.userID = u.userID
                LEFT JOIN users admin ON wr.processed_by = admin.userID
                ORDER BY wr.requested_at DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("Get all withdrawals error: " . $e->getMessage());
            return [];
        }
    }

    // ==================== WITHDRAWAL STATISTICS ====================
    
    /**
     * Get withdrawal statistics for a shop
     * @param int $shopID
     * @return array|false Returns array of stats or false on error
     */
    public function getShopWithdrawalStats($shopID) 
    {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_count,
                    COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_count,
                    COALESCE(SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END), 0) as total_withdrawn,
                    COALESCE(SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END), 0) as pending_amount
                FROM withdrawal_requests
                WHERE shopID = ?
            ");
            $stmt->execute([$shopID]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Return empty stats array if no data found
            return $result ?: [
                'pending_count' => 0,
                'completed_count' => 0,
                'rejected_count' => 0,
                'total_withdrawn' => 0,
                'pending_amount' => 0
            ];

        } catch (Exception $e) {
            error_log("Get withdrawal stats error: " . $e->getMessage());
            // Return empty stats array on error
            return [
                'pending_count' => 0,
                'completed_count' => 0,
                'rejected_count' => 0,
                'total_withdrawn' => 0,
                'pending_amount' => 0
            ];
        }
    }

    // ==================== HELPER METHODS ====================
    
    /**
     * Get minimum withdrawal amount from system settings
     * @return float
     */
    private function getMinWithdrawalAmount() 
    {
        try {
            $stmt = $this->conn->prepare("
                SELECT setting_value 
                FROM system_settings 
                WHERE setting_key = 'min_withdrawal_amount'
            ");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? floatval($result['setting_value']) : 100.00;

        } catch (Exception $e) {
            return 100.00; // Default minimum
        }
    }

    /**
     * Format withdrawal status for display
     * @param string $status
     * @return array
     */
    public function formatStatus($status) 
    {
        $statusMap = [
            'pending' => ['label' => 'Pending', 'class' => 'warning', 'icon' => 'clock'],
            'approved' => ['label' => 'Approved', 'class' => 'info', 'icon' => 'check-circle'],
            'completed' => ['label' => 'Completed', 'class' => 'success', 'icon' => 'check-double'],
            'rejected' => ['label' => 'Rejected', 'class' => 'danger', 'icon' => 'times-circle']
        ];

        return $statusMap[$status] ?? ['label' => 'Unknown', 'class' => 'secondary', 'icon' => 'question'];
    }

    /**
     * Format withdrawal method for display
     * @param string $method
     * @return string
     */
    public function formatMethod($method) 
    {
        $methods = [
            'cash' => 'Cash (At LGU Office)',
            'atm' => 'ATM Card'
        ];
        return $methods[$method] ?? 'Unknown';
    }
}
?>