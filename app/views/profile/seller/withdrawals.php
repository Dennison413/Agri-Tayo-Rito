<?php
// views/profile/seller/withdrawals.php
// Seller Withdrawal Dashboard - Request withdrawals, view history, check balance

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'seller') {
    header('Location: /agri_system/public/auth/login');
    exit;
}

require_once __DIR__ . '/../../../app/models/Shop.php';
require_once __DIR__ . '/../../../app/models/Withdrawal.php';
require_once __DIR__ . '/../../../app/controllers/WithdrawalController.php';

$shopModel = new Shop();
$withdrawalModel = new Withdrawal();
$withdrawalController = new WithdrawalController();

// Get seller's shop
$db = new Database();
$conn = $db->connect();
$stmt = $conn->prepare("SELECT sellerID FROM seller_profiles WHERE userID = ?");
$stmt->execute([$_SESSION['user_id']]);
$seller = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$seller) {
    $_SESSION['error'] = 'Seller profile not found';
    header('Location: /agri_system/public/profile/seller/dashboard');
    exit;
}

$shop = $shopModel->getShopBySeller($seller['sellerID']);

if (!$shop) {
    $_SESSION['error'] = 'Shop not found';
    header('Location: /agri_system/public/profile/seller/dashboard');
    exit;
}

// Get withdrawal history and statistics
$withdrawals = $withdrawalController->getSellerWithdrawals($shop['shopID'], 20);
$stats = $withdrawalModel->getShopWithdrawalStats($shop['shopID']);
$balance = $shopModel->getBalance($shop['shopID']);

// Get system settings
$stmt = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'min_withdrawal_amount'");
$stmt->execute();
$minWithdrawal = $stmt->fetch(PDO::FETCH_ASSOC)['setting_value'] ?? 100;

$pageTitle = 'Withdrawals';
include __DIR__ . '/../../includes/header.php';
?>

<div class="dashboard-container">
    <!-- Sidebar -->
    <?php include __DIR__ . '/../../includes/seller-sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="content-header">
            <div class="header-left">
                <h1><i class="bi bi-wallet2"></i> Withdrawals</h1>
                <p class="text-muted">Manage your earnings and withdrawal requests</p>
            </div>
            <div class="header-right">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#withdrawalModal">
                    <i class="bi bi-plus-lg"></i> Request Withdrawal
                </button>
            </div>
        </div>

        <!-- Alert Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill"></i>
                <?= htmlspecialchars($_SESSION['success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <?= htmlspecialchars($_SESSION['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Balance Overview Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="stat-card balance-card">
                    <div class="stat-icon bg-success">
                        <i class="bi bi-wallet2"></i>
                    </div>
                    <div class="stat-details">
                        <h6>Available Balance</h6>
                        <h3>₱<?= number_format($balance['balance'], 2) ?></h3>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-primary">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                    <div class="stat-details">
                        <h6>Total Earned</h6>
                        <h3>₱<?= number_format($balance['total_earned'], 2) ?></h3>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-info">
                        <i class="bi bi-arrow-down-circle"></i>
                    </div>
                    <div class="stat-details">
                        <h6>Total Withdrawn</h6>
                        <h3>₱<?= number_format($balance['total_withdrawn'], 2) ?></h3>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-warning">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div class="stat-details">
                        <h6>Pending Amount</h6>
                        <h3>₱<?= number_format($stats['pending_amount'], 2) ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Withdrawal Statistics -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title mb-3">Withdrawal Statistics</h5>
                <div class="row text-center">
                    <div class="col-md-3">
                        <div class="stat-item">
                            <h4 class="text-warning"><?= $stats['pending_count'] ?></h4>
                            <p class="text-muted mb-0">Pending</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-item">
                            <h4 class="text-success"><?= $stats['completed_count'] ?></h4>
                            <p class="text-muted mb-0">Completed</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-item">
                            <h4 class="text-danger"><?= $stats['rejected_count'] ?></h4>
                            <p class="text-muted mb-0">Rejected</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-item">
                            <h4 class="text-primary">₱<?= number_format($stats['total_withdrawn'], 2) ?></h4>
                            <p class="text-muted mb-0">Total Withdrawn</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ATM Card Information -->
        <?php if (!empty($shop['atm_card_number'])): ?>
            <div class="alert alert-info mb-4">
                <div class="d-flex align-items-center">
                    <i class="bi bi-credit-card fs-3 me-3"></i>
                    <div>
                        <h6 class="mb-1">Your ATM Card Number</h6>
                        <p class="mb-0"><strong><?= htmlspecialchars($shop['atm_card_number']) ?></strong></p>
                        <small class="text-muted">Present this card at LGU office for cash withdrawals</small>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-warning mb-4">
                <i class="bi bi-exclamation-triangle"></i>
                <strong>No ATM Card Registered</strong><br>
                Please contact admin to issue your exclusive ATM card for cash withdrawals.
            </div>
        <?php endif; ?>

        <!-- Withdrawal History -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-clock-history"></i> Withdrawal History</h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($withdrawals)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-inbox fs-1 text-muted"></i>
                        <p class="text-muted mt-3">No withdrawal history yet</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Status</th>
                                    <th>Processed By</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($withdrawals as $withdrawal): ?>
                                    <?php 
                                    $statusBadge = $withdrawalModel->formatStatus($withdrawal['status']);
                                    $methodLabel = $withdrawalModel->formatMethod($withdrawal['withdrawal_method']);
                                    ?>
                                    <tr>
                                        <td>
                                            <small class="text-muted">
                                                <?= date('M d, Y', strtotime($withdrawal['requested_at'])) ?><br>
                                                <?= date('h:i A', strtotime($withdrawal['requested_at'])) ?>
                                            </small>
                                        </td>
                                        <td>
                                            <strong class="text-success">₱<?= number_format($withdrawal['amount'], 2) ?></strong>
                                        </td>
                                        <td><?= htmlspecialchars($methodLabel) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $statusBadge['class'] ?>">
                                                <i class="bi bi-<?= $statusBadge['icon'] ?>"></i>
                                                <?= $statusBadge['label'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($withdrawal['processed_by_name']): ?>
                                                <small><?= htmlspecialchars($withdrawal['processed_by_name']) ?></small>
                                            <?php else: ?>
                                                <small class="text-muted">Pending</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($withdrawal['status'] === 'rejected' && $withdrawal['rejection_reason']): ?>
                                                <small class="text-danger">
                                                    <i class="bi bi-info-circle"></i>
                                                    <?= htmlspecialchars($withdrawal['rejection_reason']) ?>
                                                </small>
                                            <?php elseif ($withdrawal['notes']): ?>
                                                <small class="text-muted"><?= htmlspecialchars($withdrawal['notes']) ?></small>
                                            <?php else: ?>
                                                <small class="text-muted">-</small>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<!-- Withdrawal Request Modal -->
<div class="modal fade" id="withdrawalModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-wallet2"></i> Request Withdrawal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="withdrawalForm" method="POST" action="/agri_system/app/controllers/WithdrawalController.php">
                <input type="hidden" name="action" value="request_withdrawal">
                
                <div class="modal-body">
                    <!-- Available Balance Display -->
                    <div class="alert alert-success mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Available Balance:</span>
                            <strong class="fs-5">₱<?= number_format($balance['balance'], 2) ?></strong>
                        </div>
                    </div>

                    <!-- Amount Input -->
                    <div class="mb-3">
                        <label for="amount" class="form-label">Withdrawal Amount <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">₱</span>
                            <input type="number" 
                                   class="form-control" 
                                   id="amount" 
                                   name="amount" 
                                   min="<?= $minWithdrawal ?>" 
                                   max="<?= $balance['balance'] ?>" 
                                   step="0.01" 
                                   required>
                        </div>
                        <small class="text-muted">
                            Minimum: ₱<?= number_format($minWithdrawal, 2) ?> | 
                            Maximum: ₱<?= number_format($balance['balance'], 2) ?>
                        </small>
                    </div>

                    <!-- Withdrawal Method -->
                    <div class="mb-3">
                        <label class="form-label">Withdrawal Method <span class="text-danger">*</span></label>
                        <div class="form-check">
                            <input class="form-check-input" 
                                   type="radio" 
                                   name="withdrawal_method" 
                                   id="method_cash" 
                                   value="cash" 
                                   checked>
                            <label class="form-check-label" for="method_cash">
                                <i class="bi bi-cash"></i> Cash at LGU Office
                                <small class="d-block text-muted">Present your ATM card at LGU office to receive cash</small>
                            </label>
                        </div>
                        <div class="form-check mt-2">
                            <input class="form-check-input" 
                                   type="radio" 
                                   name="withdrawal_method" 
                                   id="method_atm" 
                                   value="atm" 
                                   <?= empty($shop['atm_card_number']) ? 'disabled' : '' ?>>
                            <label class="form-check-label" for="method_atm">
                                <i class="bi bi-credit-card"></i> ATM Card
                                <small class="d-block text-muted">
                                    <?= empty($shop['atm_card_number']) ? 'No ATM card registered' : 'Withdraw using your registered ATM card' ?>
                                </small>
                            </label>
                        </div>
                    </div>

                    <!-- Information Notice -->
                    <div class="alert alert-info mb-0">
                        <i class="bi bi-info-circle"></i>
                        <strong>Processing Time:</strong> Withdrawal requests are processed within 1 business day.
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitWithdrawal">
                        <i class="bi bi-check-lg"></i> Submit Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="/agri_system/public/js/withdrawals.js"></script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>