<?php
// app/views/profile/admin/withdrawals.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../config/database.php';
require_once __DIR__ . '/../../../models/Shop.php';
require_once __DIR__ . '/../../../models/Withdrawal.php';
require_once __DIR__ . '/../../../helpers/csrf.php';

$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'];
$userRole = $_SESSION['user_role'] ?? null;

if (!$isLoggedIn || $userRole !== 'admin') {
    header('Location: ' . BASE_URL . 'auth/login');
    exit;
}

$withdrawalModel = new Withdrawal();
$shopModel = new Shop();

$pendingWithdrawals = $withdrawalModel->getPendingWithdrawals();
$allShops = $shopModel->getAllShopsWithBalance();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/jpg" href="/agri_system/public/images/agri-icon.jpg">
    <title>Agri Tayo Rito</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/responsive.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/admin/dashboard.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f3f4f6;
            color: #1f2937;
        }

        .main-content {
            margin-left: 280px;
            margin-top: 70px;
            padding: 30px;
            min-height: calc(100vh - 70px);
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
            }
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }

        .page-header {
            margin-bottom: 30px;
        }

        .page-title {
            font-size: 2rem;
            color: #1f2937;
            margin-bottom: 5px;
        }

        .page-subtitle {
            color: #6b7280;
            font-size: 1rem;
        }
        .card-lookup-section {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .section-title {
            font-size: 1.5rem;
            color: #1f2937;
            margin-bottom: 10px;
        }

        .section-subtitle {
            color: #6b7280;
            margin-bottom: 20px;
        }

        .lookup-form {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .lookup-input {
            flex: 1;
            padding: 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .lookup-input:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn-search {
            padding: 15px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            transition: transform 0.3s;
        }

        .btn-search:hover {
            transform: translateY(-2px);
        }

        .shop-result {
            margin-top: 20px;
            padding: 20px;
            background: #f9fafb;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }

        .shop-result h3 {
            margin-bottom: 15px;
            color: #1f2937;
        }

        .shop-result p {
            margin-bottom: 10px;
            color: #4b5563;
        }

        .pending-section,
        .shops-section {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }
        .table-container {
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            table-layout: fixed;
        }

        .data-table thead {
            background: #f9fafb;
        }

        .data-table th {
            padding: 15px 10px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            border-bottom: 2px solid #e5e7eb;
            font-size: 0.9rem;
            white-space: nowrap;
        }

        .data-table td {
            padding: 15px 10px;
            border-bottom: 1px solid #e5e7eb;
            color: #4b5563;
            font-size: 0.9rem;
        }
        .data-table th:nth-child(1),
        .data-table td:nth-child(1) {
            width: 25%;
            max-width: 250px;
            word-wrap: break-word;
            white-space: normal;
        }
        .data-table th:nth-child(2),
        .data-table td:nth-child(2) {
            width: 15%;
            white-space: nowrap;
        }
        .data-table th:nth-child(3),
        .data-table td:nth-child(3) {
            width: 12%;
            white-space: nowrap;
        }
        .data-table th:nth-child(4),
        .data-table td:nth-child(4) {
            width: 12%;
            white-space: nowrap;
        }
        .data-table th:nth-child(5),
        .data-table td:nth-child(5) {
            width: 12%;
            white-space: nowrap;
        }
        .data-table th:nth-child(6),
        .data-table td:nth-child(6) {
            width: 12%;
            white-space: nowrap;
        }
        .data-table th:nth-child(7),
        .data-table td:nth-child(7) {
            width: 12%;
            min-width: 140px;
            text-align: right;
            padding-right: 10px;
        }

        .data-table tbody tr:hover {
            background: #f9fafb;
        }

        .amount {
            font-weight: bold;
            color: #2d5016;
            font-size: 1rem;
        }

        .method-badge {
            padding: 5px 12px;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-block;
        }

        .method-badge.cash {
            background: #dcfce7;
            color: #166534;
        }

        .method-badge.atm {
            background: #dbeafe;
            color: #1e40af;
        }
        .action-buttons {
            display: flex;
            gap: 5px;
            flex-wrap: nowrap;
            justify-content: flex-end;
        }

        .btn-approve,
        .btn-reject,
        .btn-view-history,
        .btn-quick-cash {
            padding: 6px 10px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            font-size: 0.75rem;
            white-space: nowrap;
        }

        .btn-approve {
            background: #16a34a;
            color: white;
        }

        .btn-approve:hover {
            background: #15803d;
            transform: translateY(-1px);
        }

        .btn-reject {
            background: #dc2626;
            color: white;
        }

        .btn-reject:hover {
            background: #b91c1c;
            transform: translateY(-1px);
        }

        .btn-view-history {
            background: #3e6c1eff;
            color: white;
        }

        .btn-view-history:hover {
            background: #2d5016;
            transform: translateY(-1px);
        }

        .btn-quick-cash {
            background: #f59e0b;
            color: white;
        }

        .btn-quick-cash:hover {
            background: #d97706;
            transform: translateY(-1px);
        }

        .card-number {
            background: #dbeafe;
            color: #1e40af;
            padding: 5px 10px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.85rem;
            white-space: nowrap;
        }

        .text-muted {
            color: #9ca3af;
            font-style: italic;
        }

        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
            font-size: 1.1rem;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 10000;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            scrollbar-width: none;
            -ms-overflow-style: none;
        }

        .modal-content::-webkit-scrollbar {
            display: none;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 12px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-content.large {
            max-width: 700px;
        }

        .modal-content h3 {
            margin-bottom: 20px;
            color: #1f2937;
            font-size: 1.5rem;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #374151;
        }

        .form-group textarea,
        .form-group input[type="text"],
        .form-group input[type="number"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
            font-family: inherit;
            transition: border-color 0.3s;
        }

        .form-group textarea:focus,
        .form-group input[type="text"]:focus,
        .form-group input[type="number"]:focus {
            outline: none;
            border-color: #667eea;
        }

        .modal-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .btn-cancel {
            flex: 1;
            padding: 12px;
            background: #e5e7eb;
            color: #374151;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.3s;
        }

        .btn-cancel:hover {
            background: #d1d5db;
        }

        .btn-confirm {
            flex: 1;
            padding: 12px;
            background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: transform 0.3s;
        }

        .btn-confirm:hover {
            transform: translateY(-2px);
        }

        .btn-confirm-reject {
            flex: 1;
            padding: 12px;
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: transform 0.3s;
        }

        .btn-confirm-reject:hover {
            transform: translateY(-2px);
        }

        .history-item {
            padding: 15px;
            background: #f9fafb;
            border-radius: 8px;
            margin-bottom: 10px;
            border-left: 4px solid #2d5016;
        }

        .history-item p {
            margin: 5px 0;
            font-size: 0.9rem;
        }

        .status-badge {
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }

        .status-badge.completed {
            background: #dcfce7;
            color: #166534;
        }

        .status-badge.pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-badge.rejected {
            background: #fee2e2;
            color: #991b1b;
        }

        .info-message {
            padding: 15px;
            background: #f0f9ff;
            border-left: 4px solid #3b82f6;
            border-radius: 8px;
            margin: 15px 0;
            color: #1e40af;
        }

        .success-message {
            padding: 15px;
            background: #dcfce7;
            border-left: 4px solid #16a34a;
            border-radius: 8px;
            margin: 15px 0;
            color: #166534;
        }

        .error-message {
            padding: 15px;
            background: #fee2e2;
            border-left: 4px solid #dc2626;
            border-radius: 8px;
            margin: 15px 0;
            color: #991b1b;
        }

        .message-icon {
            font-size: 1.2rem;
            margin-right: 8px;
        }

        @media (max-width: 768px) {
            .lookup-form {
                flex-direction: column;
            }

            .action-buttons {
                flex-direction: column;
            }

            .table-container {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            .data-table {
                min-width: 800px;
                font-size: 0.8rem;
            }

            .data-table th,
            .data-table td {
                padding: 8px 6px;
                font-size: 0.75rem;
            }
            .data-table th,
            .data-table td {
                width: auto !important;
            }

            .amount {
                font-size: 0.85rem;
            }

            .btn-approve,
            .btn-reject,
            .btn-view-history,
            .btn-quick-cash {
                padding: 8px 12px;
                font-size: 0.8rem;
            }

            .card-number {
                font-size: 0.7rem;
                padding: 3px 6px;
            }

            .method-badge {
                font-size: 0.7rem;
                padding: 3px 6px;
            }

            .main-content {
                padding: 15px;
                margin-left: 0;
            }

            .page-title {
                font-size: 1.5rem;
            }

            .section-title {
                font-size: 1.2rem;
            }

            .modal-content {
                padding: 20px;
                width: 95%;
            }

            .section-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .badge {
                align-self: flex-start;
            }
        }
    </style>
</head>

<body>
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['success'];
                                            unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error"><?php echo $_SESSION['error'];
                                        unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <div class="overlay" id="overlay" onclick="toggleSidebar()"></div>
    <?php
    require_once __DIR__ . '/admin-nav.php';
    ?>

    <!-- Main Content -->
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">💰 Withdrawal Management</h1>
            <p class="page-subtitle">Process seller withdrawal requests</p>
        </div>

        <!-- Cash Withdrawal Lookup -->
        <section class="card-lookup-section">
            <h2 class="section-title">🔍 Quick Cash Withdrawal</h2>
            <p class="section-subtitle">Seller presents ATM card for instant cash withdrawal</p>

            <div class="lookup-form">
                <input type="text" id="cardNumber" placeholder="Enter ATM Card Number (e.g., 21-11827)" class="lookup-input">
                <button onclick="searchByCard()" class="btn-search">🔎 Search</button>
            </div>

            <div id="shopResult" class="shop-result" style="display: none;">
            </div>
        </section>

        <!-- Pending Withdrawal Requests -->
        <section class="pending-section">
            <div class="section-header">
                <h2 class="section-title">⏳ Pending Withdrawal Requests</h2>
                <span class="badge"><?php echo count($pendingWithdrawals); ?> requests</span>
            </div>

            <?php if (count($pendingWithdrawals) > 0): ?>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Shop Name</th>
                                <th>Seller</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Current Balance</th>
                                <th>Requested</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingWithdrawals as $withdrawal): ?>
                                <tr>
                                    <td>#<?php echo $withdrawal['withdrawalID']; ?></td>
                                    <td><?php echo htmlspecialchars($withdrawal['shop_name']); ?></td>
                                    <td><?php echo htmlspecialchars($withdrawal['seller_name']); ?></td>
                                    <td class="amount">₱<?php echo number_format($withdrawal['amount'], 2); ?></td>
                                    <td>
                                        <?php if ($withdrawal['withdrawal_method'] === 'cash'): ?>
                                            <span class="method-badge cash">💵 Cash</span>
                                        <?php else: ?>
                                            <span class="method-badge atm">🏧 ATM</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="amount">₱<?php echo number_format($withdrawal['balance'], 2); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($withdrawal['requested_at'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button onclick="approveWithdrawal(<?php echo $withdrawal['withdrawalID']; ?>)"
                                                class="btn-approve">✓ Approve</button>
                                            <button onclick="rejectWithdrawal(<?php echo $withdrawal['withdrawalID']; ?>)"
                                                class="btn-reject">✕ Reject</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-data">
                    <p>✅ No pending withdrawal requests</p>
                </div>
            <?php endif; ?>
        </section>

        <!-- All Shops Balance Overview -->
        <section class="shops-section">
            <div class="section-header">
                <h2 class="section-title">🏪 Shop Balances Overview</h2>
            </div>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Shop Name</th>
                            <th>Seller</th>
                            <th>ATM Card</th>
                            <th>Balance</th>
                            <th>Earned</th>
                            <th>Withdrawn</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allShops as $shop): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($shop['shop_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($shop['seller_name']); ?></td>
                                <td>
                                    <?php if ($shop['atm_card_number']): ?>
                                        <span class="card-number">💳 <?php echo htmlspecialchars($shop['atm_card_number']); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">Not issued</span>
                                    <?php endif; ?>
                                </td>
                                <td class="amount">₱<?php echo number_format($shop['balance'], 2); ?></td>
                                <td>₱<?php echo number_format($shop['total_earned'], 2); ?></td>
                                <td>₱<?php echo number_format($shop['total_withdrawn'], 2); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button onclick="viewHistory(<?php echo $shop['shopID']; ?>, '<?php echo htmlspecialchars($shop['shop_name']); ?>')"
                                            class="btn-view-history">👁️</button>
                                        <button onclick="quickCash(<?php echo $shop['shopID']; ?>, '<?php echo htmlspecialchars($shop['shop_name']); ?>', <?php echo $shop['balance']; ?>)"
                                            class="btn-quick-cash">💵</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <!-- Approval Modal -->
    <div id="approvalModal" class="modal">
        <div class="modal-content">
            <h3>✅ Approve Withdrawal</h3>
            <form method="POST" action="<?php echo BASE_URL; ?>withdrawal">
                <input type="hidden" name="csrf_token" value="<?php echo CSRF::generateToken(); ?>">
                <input type="hidden" name="withdrawal_id" id="approve_withdrawal_id">
                <div class="form-group">
                    <label>Notes (Optional)</label>
                    <textarea name="notes" rows="3" placeholder="Add any notes about this approval..."></textarea>
                </div>
                <input type="hidden" name="action" value="approve_withdrawal">
                <div class="modal-actions">
                    <button type="button" onclick="closeModal()" class="btn-cancel">Cancel</button>
                    <button type="submit" class="btn-confirm">Confirm Approval</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Rejection Modal -->
    <div id="rejectionModal" class="modal">
        <div class="modal-content">
            <h3>❌ Reject Withdrawal</h3>
            <form method="POST" action="<?php echo BASE_URL; ?>withdrawal">
                <input type="hidden" name="csrf_token" value="<?php echo CSRF::generateToken(); ?>">
                <input type="hidden" name="withdrawal_id" id="reject_withdrawal_id">
                <div class="form-group">
                    <label>Reason for Rejection *</label>
                    <textarea name="rejection_reason" rows="3" required placeholder="Please provide a reason for rejection..."></textarea>
                </div>
                <input type="hidden" name="action" value="reject_withdrawal">
                <div class="modal-actions">
                    <button type="button" onclick="closeModal()" class="btn-cancel">Cancel</button>
                    <button type="submit" class="btn-confirm-reject">Confirm Rejection</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Quick Cash Modal -->
    <div id="quickCashModal" class="modal">
        <div class="modal-content">
            <h3>💵 Quick Cash Withdrawal</h3>
            <form id="quickCashForm">
                <input type="hidden" id="quick_shop_id">
                <div class="form-group">
                    <label>Shop Name</label>
                    <input type="text" id="quick_shop_name" readonly>
                </div>
                <div class="form-group">
                    <label>Available Balance</label>
                    <input type="text" id="quick_balance" readonly>
                </div>
                <div class="form-group">
                    <label>Withdrawal Amount *</label>
                    <input type="number" id="quick_amount" step="100" min="100" required placeholder="Enter amount">
                </div>
                <div class="modal-actions">
                    <button type="button" onclick="closeModal()" class="btn-cancel">Cancel</button>
                    <button type="button" onclick="processQuickCash()" class="btn-confirm">Process Withdrawal</button>
                </div>
            </form>
        </div>
    </div>

    <!-- History Modal -->
    <div id="historyModal" class="modal">
        <div class="modal-content large">
            <h3>📜 Withdrawal History</h3>
            <div id="historyContent">
                <p style="text-align: center; color: #6b7280;">Loading...</p>
            </div>
            <div class="modal-actions">
                <button type="button" onclick="closeModal()" class="btn-cancel">Close</button>
            </div>
        </div>
    </div>

    <!-- Message Modal (replaces alerts) -->
    <div id="messageModal" class="modal">
        <div class="modal-content">
            <div id="messageContent"></div>
            <div class="modal-actions">
                <button type="button" onclick="closeModal()" class="btn-cancel">OK</button>
            </div>
        </div>
    </div>

    <script>
        const BASE_URL = '<?php echo BASE_URL; ?>';
        const CSRF_TOKEN = '<?php echo CSRF::generateToken(); ?>';

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');
            if (sidebar && overlay) {
                sidebar.classList.toggle('active');
                overlay.classList.toggle('active');
            }
        }

        function showMessage(message, type = 'info') {
            const modal = document.getElementById('messageModal');
            const content = document.getElementById('messageContent');

            let iconMap = {
                'success': '✅',
                'error': '❌',
                'info': 'ℹ️'
            };

            let classMap = {
                'success': 'success-message',
                'error': 'error-message',
                'info': 'info-message'
            };

            content.innerHTML = `
                <div class="${classMap[type]}">
                    <span class="message-icon">${iconMap[type]}</span>
                    <span>${message}</span>
                </div>
            `;

            modal.classList.add('active');
        }

        function searchByCard() {
            const cardNumber = document.getElementById('cardNumber').value.trim();
            if (!cardNumber) {
                showMessage('Please enter a card number', 'error');
                return;
            }

            const resultDiv = document.getElementById('shopResult');
            resultDiv.innerHTML = '<p>🔄 Searching...</p>';
            resultDiv.style.display = 'block';

            const formData = new FormData();
            formData.append('action', 'search_by_card');
            formData.append('card_number', cardNumber);
            formData.append('csrf_token', CSRF_TOKEN);

            fetch(BASE_URL + 'withdrawal', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        resultDiv.innerHTML = `
                        <h3>✅ Shop Found</h3>
                        <p><strong>Shop:</strong> ${data.shop.shop_name}</p>
                        <p><strong>Seller:</strong> ${data.shop.seller_name}</p>
                        <p><strong>Available Balance:</strong> <span class="amount">₱${parseFloat(data.shop.balance).toFixed(2)}</span></p>
                        <button onclick="processInstantWithdrawal(${data.shop.shopID}, ${data.shop.balance})" class="btn-approve" style="margin-top: 15px;">
                            💵 Process Cash Withdrawal
                        </button>
                    `;
                        resultDiv.style.display = 'block';
                    } else {
                        resultDiv.innerHTML = `<p style="color: #dc2626;">❌ ${data.message}</p>`;
                        resultDiv.style.display = 'block';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    resultDiv.innerHTML = '<p style="color: #dc2626;">❌ Network error. Please try again.</p>';
                    resultDiv.style.display = 'block';
                });
        }

        function processInstantWithdrawal(shopID, balance) {
            const modal = document.getElementById('quickCashModal');
            const shopNameInput = document.getElementById('quick_shop_name');
            const balanceInput = document.getElementById('quick_balance');
            const shopIDInput = document.getElementById('quick_shop_id');
            const amountInput = document.getElementById('quick_amount');

            const resultDiv = document.getElementById('shopResult');
            const shopNameElement = resultDiv.querySelector('p strong');
            const shopName = shopNameElement ? shopNameElement.nextSibling.textContent.trim() : 'Shop';

            shopIDInput.value = shopID;
            shopNameInput.value = shopName;
            balanceInput.value = '₱' + parseFloat(balance).toFixed(2);
            amountInput.max = balance;
            amountInput.value = '';

            modal.classList.add('active');
        }

        function quickCash(shopID, shopName, balance) {
            document.getElementById('quick_shop_id').value = shopID;
            document.getElementById('quick_shop_name').value = shopName;
            document.getElementById('quick_balance').value = '₱' + parseFloat(balance).toFixed(2);
            document.getElementById('quick_amount').max = balance;
            document.getElementById('quick_amount').value = '';
            document.getElementById('quickCashModal').classList.add('active');
        }

        function processQuickCash() {
            const shopID = document.getElementById('quick_shop_id').value;
            const amount = parseFloat(document.getElementById('quick_amount').value);
            const maxBalance = parseFloat(document.getElementById('quick_amount').max);

            if (!amount || amount <= 0) {
                showMessage('Please enter a valid amount', 'error');
                return;
            }

            if (amount > maxBalance) {
                showMessage('Amount exceeds available balance', 'error');
                return;
            }

            closeModal();

            if (!confirm(`Process cash withdrawal of ₱${amount.toFixed(2)}?`)) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'process_cash_withdrawal');
            formData.append('shop_id', shopID);
            formData.append('amount', amount);
            formData.append('csrf_token', CSRF_TOKEN);

            fetch(BASE_URL + 'withdrawal', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showMessage(data.message, 'success');
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        showMessage(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showMessage('Network error. Please try again.', 'error');
                });
        }

        function viewHistory(shopID, shopName) {
            document.getElementById('historyModal').classList.add('active');
            document.getElementById('historyContent').innerHTML = '<p style="text-align: center; color: #6b7280; padding: 20px;">🔄 Loading history...</p>';

            const formData = new FormData();
            formData.append('action', 'get_withdrawals_by_shop');
            formData.append('shop_id', shopID);
            formData.append('csrf_token', CSRF_TOKEN);

            console.log('Fetching history for shopID:', shopID);

            fetch(BASE_URL + 'withdrawal', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    console.log('History Response:', data);

                    if (data.success) {
                        if (data.withdrawals && data.withdrawals.length > 0) {
                            let html = `<h4 style="margin-bottom: 15px; color: #1f2937;">${shopName}</h4>`;
                            html += `<p style="margin-bottom: 20px; padding: 12px; background: #f9fafb; border-radius: 6px; color: #4b5563;">
                            <strong>Total Withdrawn:</strong> ₱${parseFloat(data.stats.total_withdrawn || 0).toFixed(2)} | 
                            <strong>Completed:</strong> ${data.stats.completed_count || 0} | 
                            <strong>Pending:</strong> ${data.stats.pending_count || 0} | 
                            <strong>Rejected:</strong> ${data.stats.rejected_count || 0}
                        </p>`;

                            data.withdrawals.forEach(w => {
                                let statusClass = w.status === 'completed' ? 'completed' :
                                    w.status === 'pending' ? 'pending' : 'rejected';
                                let statusIcon = w.status === 'completed' ? '✅' :
                                    w.status === 'pending' ? '⏳' : '❌';

                                html += `
                                <div class="history-item">
                                    <p style="margin-bottom: 8px;">
                                        <strong style="font-size: 1.1rem; color: #2d5016;">₱${parseFloat(w.amount).toFixed(2)}</strong> - 
                                        <span class="status-badge ${statusClass}">${statusIcon} ${w.status.toUpperCase()}</span>
                                    </p>
                                    <p style="margin: 5px 0;"><strong>Method:</strong> ${w.withdrawal_method === 'cash' ? '💵 Cash' : '🏧 ATM'}</p>
                                    <p style="margin: 5px 0;"><strong>Requested:</strong> ${formatDate(w.requested_at)}</p>
                                    ${w.processed_at ? `<p style="margin: 5px 0;"><strong>Processed:</strong> ${formatDate(w.processed_at)}</p>` : ''}
                                    ${w.processed_by_name ? `<p style="margin: 5px 0;"><strong>Processed By:</strong> ${w.processed_by_name}</p>` : ''}
                                    ${w.notes ? `<p style="margin: 5px 0; color: #6b7280;"><strong>Notes:</strong> ${w.notes}</p>` : ''}
                                    ${w.rejection_reason ? `<p style="margin: 5px 0; color: #dc2626;"><strong>Rejection Reason:</strong> ${w.rejection_reason}</p>` : ''}
                                </div>
                            `;
                            });
                            document.getElementById('historyContent').innerHTML = html;
                        } else {
                            document.getElementById('historyContent').innerHTML =
                                `<div class="info-message">
                                <span class="message-icon">ℹ️</span>
                                <span>No withdrawal history found for this shop.</span>
                            </div>`;
                        }
                    } else {
                        document.getElementById('historyContent').innerHTML =
                            `<div class="error-message">
                            <span class="message-icon">❌</span>
                            <span>${data.message || 'Failed to load withdrawal history.'}</span>
                        </div>`;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('historyContent').innerHTML =
                        `<div class="error-message">
                        <span class="message-icon">❌</span>
                        <span>Failed to load withdrawal history. Please try again.</span>
                    </div>`;
                });
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            const options = {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            };
            return date.toLocaleDateString('en-US', options);
        }

        function approveWithdrawal(id) {
            document.getElementById('approve_withdrawal_id').value = id;
            document.getElementById('approvalModal').classList.add('active');
        }

        function rejectWithdrawal(id) {
            document.getElementById('reject_withdrawal_id').value = id;
            document.getElementById('rejectionModal').classList.add('active');
        }

        function closeModal() {
            document.querySelectorAll('.modal').forEach(m => m.classList.remove('active'));
        }

        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeModal();
                }
            });
        });

        document.getElementById('cardNumber').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchByCard();
            }
        });

        document.getElementById('quick_amount').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                processQuickCash();
            }
        });
    </script>
</body>

</html>