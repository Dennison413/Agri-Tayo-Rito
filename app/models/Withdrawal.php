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
        // Use exceptions for easier error handling
        $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    // ==================== CREATE WITHDRAWAL REQUEST ====================
    /**
     * Create a new withdrawal request
     * Ensures shop exists, amount valid, no pending requests, and stores atm card presented.
     *
     * @param int $shopID
     * @param float $amount
     * @param string $method - 'cash' or 'atm'
     * @param string|null $atmCard - optional ATM card number
     * @return array ['success' => bool, 'message' => string, 'withdrawal_id' => int]
     */
    public function createRequest($shopID, $amount, $method = 'cash', $atmCard = null) 
    {
        try {
            // Input sanitization & normalization
            $amount = floatval($amount);
            $method = ($method === 'atm') ? 'atm' : 'cash';

            // Start short transaction to prevent race between checks and insert
            $this->conn->beginTransaction();

            // Lock the shop row for update to get consistent balance read
            $stmt = $this->conn->prepare("
                SELECT shopID, shop_name, balance, atm_card_number, is_active
                FROM shops
                WHERE shopID = ?
                FOR UPDATE
            ");
            $stmt->execute([$shopID]);
            $shop = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$shop || intval($shop['is_active']) !== 1) {
                $this->conn->rollBack();
                return ['success' => false, 'message' => 'Shop not found or inactive'];
            }

            // Validate withdrawal amount and minimum
            $minWithdrawal = $this->getMinWithdrawalAmount();
            if ($amount < $minWithdrawal) {
                $this->conn->rollBack();
                return ['success' => false, 'message' => "Minimum withdrawal amount is ₱{$minWithdrawal}"];
            }

            if ($amount > floatval($shop['balance'])) {
                $this->conn->rollBack();
                return ['success' => false, 'message' => 'Insufficient balance. Available: ₱' . number_format($shop['balance'], 2)];
            }

            // Validate ATM method
            if ($method === 'atm' && empty($shop['atm_card_number']) && empty($atmCard)) {
                $this->conn->rollBack();
                return ['success' => false, 'message' => 'No ATM card registered. Please register your card first or choose cash withdrawal.'];
            }

            // Check for existing pending withdrawal(s)
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as pending_count
                FROM withdrawal_requests
                WHERE shopID = ? AND status = 'pending'
                FOR UPDATE
            ");
            $stmt->execute([$shopID]);
            $pending = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($pending && intval($pending['pending_count']) > 0) {
                $this->conn->rollBack();
                return ['success' => false, 'message' => 'You have a pending withdrawal request. Please wait for admin approval.'];
            }

            // Insert withdrawal request
            $stmt = $this->conn->prepare("
                INSERT INTO withdrawal_requests
                (shopID, amount, withdrawal_method, atm_card_presented, status, requested_at)
                VALUES (?, ?, ?, ?, 'pending', NOW())
            ");
            $atmValue = $atmCard ?? $shop['atm_card_number'] ?? null;
            $stmt->execute([$shopID, $amount, $method, $atmValue]);

            $withdrawalID = $this->conn->lastInsertId();

            $this->conn->commit();

            return [
                'success' => true,
                'message' => 'Withdrawal request submitted successfully. Please wait for admin approval.',
                'withdrawal_id' => $withdrawalID
            ];
        } catch (Exception $e) {
            // Rollback if in transaction
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
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
     * Performs atomic balance deduction and ledger insertion.
     *
     * @param int $withdrawalID
     * @param int $adminID
     * @param string|null $notes
     * @return array
     */
    public function approveWithdrawal($withdrawalID, $adminID, $notes = null) 
    {
        try {
            // Begin transaction for atomicity
            $this->conn->beginTransaction();

            // Lock withdrawal row for update and join the shop (shop locked too)
            $stmt = $this->conn->prepare("
                SELECT wr.*, s.shop_name, s.balance, s.shopID, s.atm_card_number, wr.withdrawalID as wID
                FROM withdrawal_requests wr
                JOIN shops s ON wr.shopID = s.shopID
                WHERE wr.withdrawalID = ?
                FOR UPDATE
            ");
            $stmt->execute([$withdrawalID]);
            $withdrawal = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$withdrawal) {
                $this->conn->rollBack();
                return ['success' => false, 'message' => 'Withdrawal request not found'];
            }

            // Ensure only pending requests are processed
            if ($withdrawal['status'] !== 'pending') {
                $this->conn->rollBack();
                return ['success' => false, 'message' => 'Withdrawal request is already processed'];
            }

            $shopID = intval($withdrawal['shopID']);
            $amount = floatval($withdrawal['amount']);
            $currentBalance = floatval($withdrawal['balance']);

            // Re-check sufficient funds
            if ($amount > $currentBalance) {
                $this->conn->rollBack();
                return ['success' => false, 'message' => 'Insufficient shop balance'];
            }

            // Compute new balance
            $newBalance = $currentBalance - $amount;

            // Update shops table (deduct balance and increment total_withdrawn)
            $updateShop = $this->conn->prepare("
                UPDATE shops
                SET balance = ?, total_withdrawn = total_withdrawn + ?
                WHERE shopID = ?
            ");
            $updateShop->execute([$newBalance, $amount, $shopID]);

            // Insert ledger entry into seller_transactions
            // transaction_type should reflect the method
            $txType = ($withdrawal['withdrawal_method'] === 'atm') ? 'withdrawal_atm' : 'withdrawal_cash';
            $insertTx = $this->conn->prepare("
                INSERT INTO seller_transactions
                (shopID, transaction_type, amount, balance_before, balance_after, reference_type, reference_id, processed_by, notes, created_at)
                VALUES (?, ?, ?, ?, ?, 'withdrawal', ?, ?, ?, NOW())
            ");
            // amount stored as negative for withdrawals (consistent with TransactionController expectation)
            $insertTx->execute([
                $shopID,
                $txType,
                -1 * $amount,
                $currentBalance,
                $newBalance,
                $withdrawalID,
                $adminID,
                $notes
            ]);
            $transactionID = $this->conn->lastInsertId();

            // Finally update withdrawal_requests to 'completed' and set processed_by / processed_at / notes
            $updateWithdrawal = $this->conn->prepare("
                UPDATE withdrawal_requests
                SET status = 'completed',
                    processed_by = ?,
                    processed_at = NOW(),
                    notes = ?
                WHERE withdrawalID = ?
            ");
            $updateWithdrawal->execute([$adminID, $notes, $withdrawalID]);

            $this->conn->commit();

            return [
                'success' => true,
                'message' => "Withdrawal approved! ₱" . number_format($amount, 2) . " has been deducted.",
                'transaction_id' => $transactionID,
                'new_balance' => $newBalance
            ];
        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
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
     *
     * @param int $withdrawalID
     * @param int $adminID
     * @param string $reason
     * @return array
     */
    public function rejectWithdrawal($withdrawalID, $adminID, $reason) 
    {
        try {
            // Simple update; no balance changes required
            $stmt = $this->conn->prepare("
                SELECT status FROM withdrawal_requests WHERE withdrawalID = ?
            ");
            $stmt->execute([$withdrawalID]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                return ['success' => false, 'message' => 'Withdrawal request not found'];
            }

            if ($row['status'] !== 'pending') {
                return ['success' => false, 'message' => 'Withdrawal request is already processed'];
            }

            $stmt = $this->conn->prepare("
                UPDATE withdrawal_requests 
                SET status = 'rejected',
                    processed_by = ?,
                    processed_at = NOW(),
                    rejection_reason = ?
                WHERE withdrawalID = ?
            ");
            $stmt->execute([$adminID, $reason, $withdrawalID]);

            return ['success' => true, 'message' => 'Withdrawal request rejected'];
        } catch (Exception $e) {
            error_log("Reject withdrawal error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to reject withdrawal: ' . $e->getMessage()];
        }
    }

    // ==================== GET WITHDRAWAL HISTORY ====================
    /**
     * Get withdrawal history by shop
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
     * NOTE: returns `balance` field (used by view) for compatibility.
     */
    public function getPendingWithdrawals() 
    {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    wr.*,
                    s.shop_name,
                    s.balance as balance,
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
            return $result ?: [
                'pending_count' => 0,
                'completed_count' => 0,
                'rejected_count' => 0,
                'total_withdrawn' => 0,
                'pending_amount' => 0
            ];
        } catch (Exception $e) {
            error_log("Get withdrawal stats error: " . $e->getMessage());
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
