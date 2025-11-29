<?php
// app/views/profile/admin/withdrawals.php
// Admin page to manage seller withdrawal requests
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../config/database.php';
require_once __DIR__ . '/../../../models/Shop.php';
require_once __DIR__ . '/../../../models/Withdrawal.php';

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
    <title>Manage Withdrawals - Admin</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/responsive.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/admin/dashboard.css">
</head>
<body>
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <div class="overlay" id="overlay" onclick="toggleSidebar()"></div>
    <?php 
    // Include unified sidebar component
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
            <div class="lookup-card">
                <h2 class="section-title">🔍 Quick Cash Withdrawal</h2>
                <p class="section-subtitle">Seller presents ATM card for instant cash withdrawal</p>
                
                <div class="lookup-form">
                    <input type="text" id="cardNumber" placeholder="Enter ATM Card Number" class="lookup-input">
                    <button onclick="searchByCard()" class="btn-search">Search</button>
                </div>

                <div id="shopResult" class="shop-result" style="display: none;">
                    <!-- Results will be shown here -->
                </div>
            </div>
        </section>

        <!-- Pending Withdrawal Requests -->
        <section class="pending-section">
            <div class="section-header">
                <h2 class="section-title">Pending Withdrawal Requests</h2>
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
                            <td>₱<?php echo number_format($withdrawal['balance'], 2); ?></td>
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
                <h2 class="section-title">Shop Balances Overview</h2>
            </div>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Shop</th>
                            <th>Seller</th>
                            <th>ATM Card</th>
                            <th>Balance</th>
                            <th>Total Earned</th>
                            <th>Total Withdrawn</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allShops as $shop): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($shop['shop_name']); ?></td>
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
            <h3>Approve Withdrawal</h3>
            <form method="POST" action="<?php echo BASE_URL; ?>app/controllers/WithdrawalController.php">
                <input type="hidden" name="withdrawal_id" id="approve_withdrawal_id">
                <div class="form-group">
                    <label>Notes (Optional)</label>
                    <textarea name="notes" rows="3"></textarea>
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
            <h3>Reject Withdrawal</h3>
            <form method="POST" action="<?php echo BASE_URL; ?>app/controllers/WithdrawalController.php">
                <input type="hidden" name="withdrawal_id" id="reject_withdrawal_id">
                <div class="form-group">
                    <label>Reason for Rejection *</label>
                    <textarea name="rejection_reason" rows="3" required></textarea>
                </div>
                <input type="hidden" name="action" value="reject_withdrawal">
                <div class="modal-actions">
                    <button type="button" onclick="closeModal()" class="btn-cancel">Cancel</button>
                    <button type="submit" class="btn-confirm-reject">Confirm Rejection</button>
                </div>
            </form>
        </div>
    </div>

    <style>
        .card-lookup-section {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
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
        }

        .btn-search {
            padding: 15px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
        }

        .shop-result {
            margin-top: 20px;
            padding: 20px;
            background: #f9fafb;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }

        .amount {
            font-weight: bold;
            color: #2d5016;
        }

        .method-badge {
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 0.9rem;
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
        }

        .btn-approve {
            padding: 8px 15px;
            background: #16a34a;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        .btn-reject {
            padding: 8px 15px;
            background: #dc2626;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
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
        }

        .modal-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #666;
        }
    </style>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        }

        function searchByCard() {
            const cardNumber = document.getElementById('cardNumber').value;
            if (!cardNumber) {
                alert('Please enter a card number');
                return;
            }

            fetch('<?php echo BASE_URL; ?>app/controllers/WithdrawalController.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=search_by_card&card_number=${encodeURIComponent(cardNumber)}`
            })
            .then(response => response.json())
            .then(data => {
                const resultDiv = document.getElementById('shopResult');
                if (data.success) {
                    resultDiv.innerHTML = `
                        <h3>Shop Found</h3>
                        <p><strong>Shop:</strong> ${data.shop.shop_name}</p>
                        <p><strong>Seller:</strong> ${data.shop.seller_name}</p>
                        <p><strong>Available Balance:</strong> ₱${data.shop.balance}</p>
                        <button onclick="processInstantWithdrawal(${data.shop.shopID}, ${data.shop.balance})" class="btn-approve">
                            Process Cash Withdrawal
                        </button>
                    `;
                    resultDiv.style.display = 'block';
                } else {
                    resultDiv.innerHTML = `<p style="color: red;">❌ ${data.message}</p>`;
                    resultDiv.style.display = 'block';
                }
            });
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
    </script>
</body>
</html>