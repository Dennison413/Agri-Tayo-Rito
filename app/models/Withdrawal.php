<?php
// app/models/Withdrawal.php
// FIXED: Added processed_by_name to withdrawal history query
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

class Withdrawal
{
    private $conn;
    private $db;

    public function __construct()
    {
        $this->db = new Database();
        $this->conn = $this->db->connect();
        $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    // ==================== CREATE WITHDRAWAL REQUEST ====================
    public function createRequest($shopID, $amount, $method = 'cash', $atmCard = null)
    {
        try {
            $amount = floatval($amount);
            $method = ($method === 'atm') ? 'atm' : 'cash';

            $this->conn->beginTransaction();

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

            $minWithdrawal = $this->getMinWithdrawalAmount();
            if ($amount < $minWithdrawal) {
                $this->conn->rollBack();
                return ['success' => false, 'message' => "Minimum withdrawal amount is ₱{$minWithdrawal}"];
            }

            if ($amount > floatval($shop['balance'])) {
                $this->conn->rollBack();
                return ['success' => false, 'message' => 'Insufficient balance. Available: ₱' . number_format($shop['balance'], 2)];
            }

            if ($method === 'atm' && empty($shop['atm_card_number']) && empty($atmCard)) {
                $this->conn->rollBack();
                return ['success' => false, 'message' => 'No ATM card registered. Please register your card first or choose cash withdrawal.'];
            }

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
    public function approveWithdrawal($withdrawalID, $adminID, $notes = null)
    {
        try {
            $this->conn->beginTransaction();

            $stmt = $this->conn->prepare("
                SELECT wr.*, s.shop_name, s.balance, s.shopID
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

            if ($withdrawal['status'] !== 'pending') {
                $this->conn->rollBack();
                return ['success' => false, 'message' => 'Withdrawal request already processed'];
            }

            $shopID = intval($withdrawal['shopID']);
            $amount = floatval($withdrawal['amount']);
            $currentBalance = floatval($withdrawal['balance']);

            if ($amount > $currentBalance) {
                $this->conn->rollBack();
                return ['success' => false, 'message' => 'Insufficient shop balance'];
            }

            $newBalance = $currentBalance - $amount;

            // Update shop balance MANUALLY
            $updateShop = $this->conn->prepare("
                UPDATE shops
                SET balance = ?, total_withdrawn = total_withdrawn + ?
                WHERE shopID = ?
            ");
            $updateShop->execute([$newBalance, $amount, $shopID]);

            // Insert transaction ledger
            $txType = ($withdrawal['withdrawal_method'] === 'atm') ? 'withdrawal_atm' : 'withdrawal_cash';
            $insertTx = $this->conn->prepare("
                INSERT INTO seller_transactions
                (shopID, transaction_type, amount, balance_before, balance_after, reference_type, reference_id, processed_by, notes, created_at)
                VALUES (?, ?, ?, ?, ?, 'withdrawal', ?, ?, ?, NOW())
            ");
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

            // Update withdrawal status to 'completed'
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
    public function rejectWithdrawal($withdrawalID, $adminID, $reason)
    {
        try {
            $stmt = $this->conn->prepare("
                SELECT status FROM withdrawal_requests WHERE withdrawalID = ?
            ");
            $stmt->execute([$withdrawalID]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                return ['success' => false, 'message' => 'Withdrawal request not found'];
            }

            if ($row['status'] !== 'pending') {
                return ['success' => false, 'message' => 'Withdrawal request already processed'];
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
    public function getWithdrawalsByShop($shopID, $limit = 20)
    {
        try {
            // Convert to integers to prevent SQL injection
            $shopID = intval($shopID);
            $limit = intval($limit);

            error_log("DEBUG: Fetching withdrawals for shopID: " . $shopID);

            // ✅ Use direct integer in LIMIT clause (safe because we used intval)
            $stmt = $this->conn->prepare("
            SELECT 
                wr.withdrawalID,
                wr.shopID,
                wr.amount,
                wr.withdrawal_method,
                wr.atm_card_presented,
                wr.status,
                wr.requested_at,
                wr.processed_by,
                wr.processed_at,
                wr.rejection_reason,
                wr.notes,
                u.full_name as processed_by_name
            FROM withdrawal_requests wr
            LEFT JOIN users u ON wr.processed_by = u.userID
            WHERE wr.shopID = ?
            ORDER BY wr.requested_at DESC
            LIMIT {$limit}
        ");

            $stmt->execute([$shopID]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            error_log("DEBUG: Found " . count($results) . " withdrawal records");

            return $results;
        } catch (Exception $e) {
            error_log("Get withdrawals error: " . $e->getMessage());
            return [];
        }
    }

    public function getPendingWithdrawals()
    {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    wr.*,
                    s.shop_name,
                    s.balance,
                    s.atm_card_number,
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

    // ==================== HELPER METHODS ====================
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
            return 100.00;
        }
    }

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
}
