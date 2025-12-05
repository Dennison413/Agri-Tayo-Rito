<?php
// app/views/profile/seller/withdrawals.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../config/database.php';
require_once __DIR__ . '/../../../models/Shop.php';
require_once __DIR__ . '/../../../models/Withdrawal.php';
require_once __DIR__ . '/../../../helpers/csrf.php';

// Check if user is logged in and is seller
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'];
$userRole = $_SESSION['user_role'] ?? null;
$userID = $_SESSION['user_id'] ?? null;

if (!$isLoggedIn || $userRole !== 'seller') {
    header('Location: ' . BASE_URL . 'auth/login');
    exit;
}

$db = new Database();
$conn = $db->connect();
$shopModel = new Shop();
$withdrawalModel = new Withdrawal();

// Get seller's shop
$stmt = $conn->prepare("SELECT sellerID FROM seller_profiles WHERE userID = ?");
$stmt->execute([$userID]);
$sellerProfile = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sellerProfile) {
    die("Seller profile not found");
}

$shop = $shopModel->getShopBySeller($sellerProfile['sellerID']);
$balance = $shopModel->getBalance($shop['shopID']);
$withdrawalHistory = $withdrawalModel->getWithdrawalsByShop($shop['shopID'], 20);

// Get minimum withdrawal amount
$stmt = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'min_withdrawal_amount'");
$minWithdrawal = $stmt->fetch(PDO::FETCH_ASSOC)['setting_value'] ?? 100;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Withdrawals - Agri Tayo Rito</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/responsive.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/seller/dashboard.css">
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
    // Include reusable seller navigation
    include __DIR__ . '/seller-nav.php'; 
    ?>

    <!-- Main Content -->
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">💰 Withdrawals</h1>
            <p class="page-subtitle">Manage your earnings and withdrawal requests</p>
        </div>

        <!-- Balance Cards -->
        <section class="balance-section">
            <div class="balance-card current">
                <div class="balance-icon">💵</div>
                <div class="balance-info">
                    <p class="balance-label">Available Balance</p>
                    <h2 class="balance-amount">₱<?php echo number_format($balance['balance'], 2); ?></h2>
                </div>
            </div>

            <div class="balance-card earned">
                <div class="balance-icon">📈</div>
                <div class="balance-info">
                    <p class="balance-label">Total Earned</p>
                    <h2 class="balance-amount">₱<?php echo number_format($balance['total_earned'], 2); ?></h2>
                </div>
            </div>

            <div class="balance-card withdrawn">
                <div class="balance-icon">💸</div>
                <div class="balance-info">
                    <p class="balance-label">Total Withdrawn</p>
                    <h2 class="balance-amount">₱<?php echo number_format($balance['total_withdrawn'], 2); ?></h2>
                </div>
            </div>

            <?php if (!empty($shop['atm_card_number'])): ?>
            <div class="balance-card card">
                <div class="balance-icon">💳</div>
                <div class="balance-info">
                    <p class="balance-label">ATM Card Number</p>
                    <h2 class="balance-amount"><?php echo htmlspecialchars($shop['atm_card_number']); ?></h2>
                </div>
            </div>
            <?php endif; ?>
        </section>

        <!-- Withdrawal Request Form -->
        <section class="withdrawal-form-section">
            <div class="form-card">
                <h2 class="section-title">Request Withdrawal</h2>
                
                <form id="withdrawalForm" onsubmit="submitWithdrawal(event)">
                    <input type="hidden" name="csrf_token" value="<?php echo CSRF::generateToken(); ?>">
                    <input type="hidden" name="shop_id" value="<?php echo $shop['shopID']; ?>">
                    
                    <div class="form-group">
                        <label for="amount">Amount to Withdraw *</label>
                        <div class="input-group">
                            <span class="input-prefix">₱</span>
                            <input type="number" id="amount" name="amount" step="100" 
                                   min="<?php echo $minWithdrawal; ?>" 
                                   max="<?php echo $balance['balance']; ?>" 
                                   required>
                        </div>
                        <small>Minimum: ₱<?php echo number_format($minWithdrawal, 2); ?> | Available: ₱<?php echo number_format($balance['balance'], 2); ?></small>
                    </div>

                    <div class="form-group">
                        <label>Withdrawal Method *</label>
                        <div class="radio-group">
                            <label class="radio-option">
                                <input type="radio" name="withdrawal_method" value="cash" checked>
                                <span>💵 Cash (Present ATM card to LGU)</span>
                            </label>
                            <?php if (!empty($shop['atm_card_number'])): ?>
                            <label class="radio-option">
                                <input type="radio" name="withdrawal_method" value="atm">
                                <span>🏧 ATM Transfer (Use your card)</span>
                            </label>
                            <?php endif; ?>
                        </div>
                    </div>

                    <input type="hidden" name="atm_card_number" value="<?php echo htmlspecialchars($shop['atm_card_number'] ?? ''); ?>">
                    <input type="hidden" name="action" value="request_withdrawal">

                    <button type="submit" class="btn-submit" id="submitBtn" <?php echo ($balance['balance'] < $minWithdrawal) ? 'disabled' : ''; ?>>
                        Submit Withdrawal Request
                    </button>
                </form>
            </div>
        </section>

        <!-- Withdrawal History -->
        <section class="history-section">
            <div class="section-header">
                <h2 class="section-title">Withdrawal History</h2>
            </div>

            <div class="table-container">
                <?php if (count($withdrawalHistory) > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th>Processed</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($withdrawalHistory as $withdrawal): ?>
                        <tr>
                            <td><?php echo date('M d, Y', strtotime($withdrawal['requested_at'])); ?></td>
                            <td>₱<?php echo number_format($withdrawal['amount'], 2); ?></td>
                            <td>
                                <?php if ($withdrawal['withdrawal_method'] === 'cash'): ?>
                                    💵 Cash
                                <?php else: ?>
                                    🏧 ATM
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo $withdrawal['status']; ?>">
                                    <?php echo ucfirst($withdrawal['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($withdrawal['processed_at']): ?>
                                    <?php echo date('M d, Y', strtotime($withdrawal['processed_at'])); ?>
                                <?php else: ?>
                                    <span class="text-muted">Pending</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p class="no-data">No withdrawal history yet.</p>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <!-- Success Modal -->
    <div id="successModal" class="modal">
        <div class="modal-content success">
            <div class="modal-icon">✅</div>
            <h3>Withdrawal Request Submitted!</h3>
            <p id="successMessage">Your withdrawal request has been submitted successfully.</p>
            <button onclick="closeSuccessModal()" class="btn-ok">OK</button>
        </div>
    </div>

    <!-- Error Modal -->
    <div id="errorModal" class="modal">
        <div class="modal-content error">
            <div class="modal-icon">❌</div>
            <h3>Request Failed</h3>
            <p id="errorMessage">Something went wrong. Please try again.</p>
            <button onclick="closeErrorModal()" class="btn-ok">OK</button>
        </div>
    </div>

    <style>
        .balance-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .balance-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .balance-card.current {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .balance-card.earned {
            background: linear-gradient(135deg, #2d5016 0%, #4a7c25 100%);
            color: white;
        }

        .balance-card.withdrawn {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            color: white;
        }

        .balance-icon {
            font-size: 2.5rem;
        }

        .balance-label {
            font-size: 0.9rem;
            opacity: 0.9;
            margin: 0;
        }

        .balance-amount {
            font-size: 1.8rem;
            font-weight: bold;
            margin: 5px 0 0 0;
        }

        .withdrawal-form-section {
            margin-bottom: 30px;
        }

        .form-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }

        .input-group {
            position: relative;
        }

        .input-group input {
            width: 100%;
            padding: 12px 12px 12px 35px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
        }

        .input-prefix {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            font-weight: bold;
            color: #666;
        }

        .form-group small {
            display: block;
            margin-top: 5px;
            color: #666;
            font-size: 0.85rem;
        }

        .radio-group {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .radio-option {
            padding: 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .radio-option:hover {
            border-color: #667eea;
            background: #f9fafb;
        }

        .radio-option input:checked + span {
            font-weight: bold;
            color: #667eea;
        }

        .btn-submit {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
            margin-top: 20px;
        }

        .btn-submit:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .history-section {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .table-container {
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th,
        .data-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        .data-table th {
            background: #f9fafb;
            font-weight: 600;
            color: #374151;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-approved {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-completed {
            background: #dcfce7;
            color: #166534;
        }

        .status-rejected {
            background: #fee2e2;
            color: #991b1b;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .text-muted {
            color: #999;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 10000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            padding: 40px;
            border-radius: 12px;
            max-width: 400px;
            width: 90%;
            text-align: center;
        }

        .modal-icon {
            font-size: 4rem;
            margin-bottom: 20px;
        }

        .modal-content h3 {
            margin: 0 0 10px 0;
            color: #1f2937;
        }

        .modal-content p {
            margin: 0 0 20px 0;
            color: #6b7280;
        }

        .btn-ok {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
        }

        .modal-content.error .btn-ok {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }

        @media (max-width: 768px) {
            .balance-section {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sellerSidebar');
            const overlay = document.getElementById('sidebarOverlay');
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        }

        async function submitWithdrawal(event) {
            event.preventDefault();
            
            const form = document.getElementById('withdrawalForm');
            const submitBtn = document.getElementById('submitBtn');
            const formData = new FormData(form);
            
            // Disable submit button
            submitBtn.disabled = true;
            submitBtn.textContent = 'Processing...';
            
            try {
                const response = await fetch('<?php echo BASE_URL; ?>withdrawal', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Show success modal
                    document.getElementById('successMessage').textContent = result.message;
                    document.getElementById('successModal').classList.add('active');
                    
                    // Reset form
                    form.reset();
                    
                    // Reload page after 2 seconds
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    // Show error modal
                    document.getElementById('errorMessage').textContent = result.message;
                    document.getElementById('errorModal').classList.add('active');
                }
            } catch (error) {
                console.error('Error:', error);
                document.getElementById('errorMessage').textContent = 'Network error. Please try again.';
                document.getElementById('errorModal').classList.add('active');
            } finally {
                // Re-enable submit button
                submitBtn.disabled = false;
                submitBtn.textContent = 'Submit Withdrawal Request';
            }
        }

        function closeSuccessModal() {
            document.getElementById('successModal').classList.remove('active');
        }

        function closeErrorModal() {
            document.getElementById('errorModal').classList.remove('active');
        }
    </script>
</body>
</html>