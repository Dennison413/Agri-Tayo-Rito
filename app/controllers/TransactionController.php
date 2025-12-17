<?php
// app/controllers/TransactionController.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../models/Shop.php';
require_once __DIR__ . '/../helpers/csrf.php';
require_once __DIR__ . '/../helpers/RateLimiter.php';

class TransactionController 
{
    private $shopModel;

    public function __construct() 
    {
        $this->shopModel = new Shop();
    }

    // seller transaction history
    public function getSellerTransactions($sellerID, $limit = 50, $offset = 0) 
    {
        $shop = $this->shopModel->getShopBySeller($sellerID);

        if (!$shop) {
            return [];
        }

        return $this->shopModel->getTransactionHistory($shop['shopID'], $limit, $offset);
    }

    // get transactions by shop ID
    public function getShopTransactions($shopID, $limit = 50, $offset = 0) 
    {
        return $this->shopModel->getTransactionHistory($shopID, $limit, $offset);
    }

    // get transactions with filters
    public function getFilteredTransactions($shopID, $filters = [], $limit = 50, $offset = 0) 
    {
        try {
            $db = new Database();
            $conn = $db->connect();

            $query = "SELECT st.*, 
                            u.full_name as processed_by_name,
                            o.orderID as order_number
                     FROM seller_transactions st
                     LEFT JOIN users u ON st.processed_by = u.userID
                     LEFT JOIN orders o ON st.reference_type = 'order' AND st.reference_id = o.orderID
                     WHERE st.shopID = ?";

            $params = [$shopID];

            // Transaction type filter
            if (!empty($filters['transaction_type'])) {
                $query .= " AND st.transaction_type = ?";
                $params[] = $filters['transaction_type'];
            }

            // Date range filter
            if (!empty($filters['start_date'])) {
                $query .= " AND DATE(st.created_at) >= ?";
                $params[] = $filters['start_date'];
            }
            if (!empty($filters['end_date'])) {
                $query .= " AND DATE(st.created_at) <= ?";
                $params[] = $filters['end_date'];
            }

            // Amount range filter
            if (!empty($filters['min_amount'])) {
                $query .= " AND ABS(st.amount) >= ?";
                $params[] = $filters['min_amount'];
            }
            if (!empty($filters['max_amount'])) {
                $query .= " AND ABS(st.amount) <= ?";
                $params[] = $filters['max_amount'];
            }

            $query .= " ORDER BY st.created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;

            $stmt = $conn->prepare($query);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("Get filtered transactions error: " . $e->getMessage());
            return [];
        }
    }

    // transaction summary and statistics
    public function getTransactionSummary($shopID, $period = 'all') 
    {
        try {
            $db = new Database();
            $conn = $db->connect();

            $query = "SELECT 
                        COUNT(*) as total_transactions,
                        SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END) as total_income,
                        SUM(CASE WHEN amount < 0 THEN ABS(amount) ELSE 0 END) as total_withdrawals,
                        SUM(CASE WHEN transaction_type = 'order_payment' THEN 1 ELSE 0 END) as order_count,
                        SUM(CASE WHEN transaction_type LIKE 'withdrawal_%' THEN 1 ELSE 0 END) as withdrawal_count
                     FROM seller_transactions
                     WHERE shopID = ?";

            $params = [$shopID];

            // Add period filter
            if ($period !== 'all') {
                switch ($period) {
                    case 'today':
                        $query .= " AND DATE(created_at) = CURDATE()";
                        break;
                    case 'week':
                        $query .= " AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                        break;
                    case 'month':
                        $query .= " AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                        break;
                    case 'year':
                        $query .= " AND created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
                        break;
                }
            }

            $stmt = $conn->prepare($query);
            $stmt->execute($params);

            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("Get transaction summary error: " . $e->getMessage());
            return [];
        }
    }

    // monthly earnings report
    public function getMonthlyEarnings($shopID, $year = null) 
    {
        $year = $year ?? date('Y');

        try {
            $db = new Database();
            $conn = $db->connect();

            $query = "SELECT 
                        MONTH(created_at) as month,
                        MONTHNAME(created_at) as month_name,
                        SUM(CASE WHEN transaction_type = 'order_payment' THEN amount ELSE 0 END) as earnings,
                        SUM(CASE WHEN transaction_type LIKE 'withdrawal_%' THEN ABS(amount) ELSE 0 END) as withdrawals,
                        COUNT(CASE WHEN transaction_type = 'order_payment' THEN 1 END) as order_count
                     FROM seller_transactions
                     WHERE shopID = ? AND YEAR(created_at) = ?
                     GROUP BY MONTH(created_at), MONTHNAME(created_at)
                     ORDER BY MONTH(created_at) ASC";

            $stmt = $conn->prepare($query);
            $stmt->execute([$shopID, $year]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("Get monthly earnings error: " . $e->getMessage());
            return [];
        }
    }

    // recent transactions
    public function getRecentTransactions($shopID, $limit = 5) 
    {
        return $this->shopModel->getTransactionHistory($shopID, $limit, 0);
    }

    // export transactions to CSV
    public function exportToCSV($shopID, $filters = []) 
    {
        if (!CSRF::validateRequest()) {
            $_SESSION['error'] = 'Security validation failed';
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
        }

        $rateCheck = RateLimiter::checkApiRate('csv_export_' . $shopID);
        if (!$rateCheck['allowed']) {
            $_SESSION['error'] = 'Too many export requests. Please wait a moment.';
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
        }

        $transactions = $this->getFilteredTransactions($shopID, $filters, 10000, 0);

        if (empty($transactions)) {
            $_SESSION['error'] = 'No transactions to export';
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
        }
        CSRF::regenerateToken();

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="transactions_' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');

        fputcsv($output, [
            'Transaction ID',
            'Date',
            'Type',
            'Amount',
            'Balance Before',
            'Balance After',
            'Reference Type',
            'Reference ID',
            'Processed By',
            'Notes'
        ]);

        foreach ($transactions as $transaction) {
            fputcsv($output, [
                $transaction['transactionID'],
                $transaction['created_at'],
                $this->formatTransactionType($transaction['transaction_type'])['label'],
                number_format($transaction['amount'], 2),
                number_format($transaction['balance_before'], 2),
                number_format($transaction['balance_after'], 2),
                ucfirst($transaction['reference_type']),
                $transaction['reference_id'] ?? 'N/A',
                $transaction['processed_by_name'] ?? 'System',
                $transaction['notes'] ?? ''
            ]);
        }

        fclose($output);
        exit;
    }

    // format transaction type for display
    public function formatTransactionType($type) 
    {
        $types = [
            'order_payment' => ['label' => 'Order Payment', 'class' => 'success', 'icon' => 'arrow-up'],
            'withdrawal_cash' => ['label' => 'Cash Withdrawal', 'class' => 'danger', 'icon' => 'arrow-down'],
            'withdrawal_atm' => ['label' => 'ATM Withdrawal', 'class' => 'danger', 'icon' => 'arrow-down'],
            'adjustment' => ['label' => 'Balance Adjustment', 'class' => 'warning', 'icon' => 'edit'],
            'refund' => ['label' => 'Refund', 'class' => 'info', 'icon' => 'refresh']
        ];

        return $types[$type] ?? ['label' => ucfirst(str_replace('_', ' ', $type)), 'class' => 'secondary', 'icon' => 'circle'];
    }

    public function formatAmount($amount) 
    {
        $formatted = number_format(abs($amount), 2);
        
        if ($amount > 0) {
            return [
                'amount' => '+₱' . $formatted,
                'class' => 'text-success'
            ];
        } elseif ($amount < 0) {
            return [
                'amount' => '-₱' . $formatted,
                'class' => 'text-danger'
            ];
        } else {
            return [
                'amount' => '₱' . $formatted,
                'class' => 'text-muted'
            ];
        }
    }

    public function getTransactionIcon($type) 
    {
        $icons = [
            'order_payment' => 'bi-arrow-up-circle-fill text-success',
            'withdrawal_cash' => 'bi-arrow-down-circle-fill text-danger',
            'withdrawal_atm' => 'bi-arrow-down-circle-fill text-danger',
            'adjustment' => 'bi-pencil-square text-warning',
            'refund' => 'bi-arrow-counterclockwise text-info'
        ];

        return $icons[$type] ?? 'bi-circle text-secondary';
    }

    private function isLoggedIn() 
    {
        return isset($_SESSION['user_id']) && 
               isset($_SESSION['logged_in']) && 
               $_SESSION['logged_in'] === true;
    }

    private function requireSeller() 
    {
        if (!$this->isLoggedIn() || $_SESSION['user_role'] !== 'seller') {
            $_SESSION['error'] = 'Unauthorized access';
            header('Location: /agri_system/public/auth/login');
            exit;
        }
    }
}

// handle requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'GET') {
    $controller = new TransactionController();
    $action = $_GET['action'] ?? $_POST['action'] ?? '';

    switch ($action) {
        case 'export_csv':
            if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'seller') {
                $_SESSION['error'] = 'Unauthorized';
                header('Location: /agri_system/public/auth/login');
                exit;
            }

            // Get seller's shop
            $db = new Database();
            $conn = $db->connect();
            $stmt = $conn->prepare("SELECT shopID FROM shops WHERE sellerID IN 
                                   (SELECT sellerID FROM seller_profiles WHERE userID = ?)");
            $stmt->execute([$_SESSION['user_id']]);
            $shop = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($shop) {
                $filters = [
                    'transaction_type' => $_GET['type'] ?? null,
                    'start_date' => $_GET['start_date'] ?? null,
                    'end_date' => $_GET['end_date'] ?? null
                ];
                $controller->exportToCSV($shop['shopID'], $filters);
            }
            break;
    }
}