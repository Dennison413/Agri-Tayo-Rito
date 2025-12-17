<?php
// app/views/profile/admin/applications.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../config/database.php';
require_once __DIR__ . '/../../../models/SellerApplication.php';

$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'];
$userRole = $_SESSION['user_role'] ?? null;

if (!$isLoggedIn || $userRole !== 'admin') {
    header('Location: ' . BASE_URL . 'auth/login');
    exit;
}

$applicationModel = new SellerApplication();

// Handle approve/reject actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $adminID = $_SESSION['user_id'];

    if ($_POST['action'] === 'approve') {
        $applicationID = intval($_POST['application_id']);
        $result = $applicationModel->approveApplication($applicationID, $adminID);

        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
        } else {
            $_SESSION['error'] = $result['message'];
        }
    } elseif ($_POST['action'] === 'reject') {
        $applicationID = intval($_POST['application_id']);
        $reason = $_POST['rejection_reason'] ?? 'Application does not meet requirements';
        $result = $applicationModel->rejectApplication($applicationID, $adminID, $reason);

        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
        } else {
            $_SESSION['error'] = $result['message'];
        }
    }

    header('Location: ' . BASE_URL . 'profile/admin/applications');
    exit;
}

// Get applications
$pendingApplications = $applicationModel->getPendingApplications();
$allApplications = $applicationModel->getAllApplications(['sort' => 'newest'], 50, 0);
$stats = $applicationModel->getApplicationStats();
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

    <?php
    // Include unified sidebar component
    require_once __DIR__ . '/admin-nav.php';
    ?>

    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">📋 Seller Applications</h1>
            <p class="page-subtitle">Review and approve users applying to become sellers</p>
        </div>

        <!-- Stats -->
        <section class="stats-section">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">📝</div>
                <div class="stat-info">
                    <h3 class="stat-value"><?php echo number_format($stats['total_applications']); ?></h3>
                    <p class="stat-label">Total Applications</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">⏳</div>
                <div class="stat-info">
                    <h3 class="stat-value"><?php echo number_format($stats['pending']); ?></h3>
                    <p class="stat-label">Pending Review</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #2d5016 0%, #4a7c25 100%);">✅</div>
                <div class="stat-info">
                    <h3 class="stat-value"><?php echo number_format($stats['approved']); ?></h3>
                    <p class="stat-label">Approved</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);">❌</div>
                <div class="stat-info">
                    <h3 class="stat-value"><?php echo number_format($stats['rejected']); ?></h3>
                    <p class="stat-label">Rejected</p>
                </div>
            </div>
        </section>

        <!-- Pending Applications -->
        <?php if (count($pendingApplications) > 0): ?>
            <section class="pending-section">
                <div class="section-header">
                    <h2 class="section-title">⏳ Pending Applications</h2>
                    <span class="badge"><?php echo count($pendingApplications); ?> pending</span>
                </div>

                <div class="applications-grid">
                    <?php foreach ($pendingApplications as $app): ?>
                        <div class="application-card">
                            <div class="app-header">
                                <div class="app-user">
                                    <div class="user-avatar">👤</div>
                                    <div class="user-info">
                                        <h3><?php echo htmlspecialchars($app['full_name']); ?></h3>
                                        <p class="user-email"><?php echo htmlspecialchars($app['email']); ?></p>
                                    </div>
                                </div>
                                <span class="status-badge pending">⏳ Pending</span>
                            </div>

                            <div class="app-details">
                                <div class="detail-row">
                                    <span class="label">Business Name:</span>
                                    <span class="value"><?php echo htmlspecialchars($app['business_name']); ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="label">Location:</span>
                                    <span class="value"><?php echo htmlspecialchars($app['business_address']); ?></span>
                                </div>
                                <!-- REMOVED MUNICIPALITY ROW -->
                                <div class="detail-row">
                                    <span class="label">Phone:</span>
                                    <span class="value"><?php echo htmlspecialchars($app['phone'] ?? 'Not provided'); ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="label">Applied:</span>
                                    <span class="value"><?php echo date('M d, Y h:i A', strtotime($app['applied_at'])); ?></span>
                                </div>
                                <?php if ($app['business_permit']): ?>
                                    <div class="detail-row">
                                        <span class="label">Permit:</span>
                                        <a href="<?php echo BASE_URL . $app['business_permit']; ?>" target="_blank" class="permit-link">
                                            📄 View Business Permit
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="app-actions">
                                <button onclick="showApproveModal(<?php echo $app['applicationID']; ?>, '<?php echo htmlspecialchars($app['full_name']); ?>')"
                                    class="btn-approve">✓ Approve</button>
                                <button onclick="showRejectModal(<?php echo $app['applicationID']; ?>, '<?php echo htmlspecialchars($app['full_name']); ?>')"
                                    class="btn-reject">✕ Reject</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php else: ?>
            <div class="no-data">
                <p>✅ No pending applications</p>
            </div>
        <?php endif; ?>

        <!-- All Applications History -->
        <section class="history-section">
            <div class="section-header">
                <h2 class="section-title">Application History</h2>
            </div>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Applicant</th>
                            <th>Business Name</th>
                            <th>Municipality</th>
                            <th>Applied</th>
                            <th>Status</th>
                            <th>Reviewed</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allApplications as $app): ?>
                            <tr>
                                <td>#<?php echo $app['applicationID']; ?></td>
                                <td>
                                    <div><strong><?php echo htmlspecialchars($app['full_name']); ?></strong></div>
                                    <small><?php echo htmlspecialchars($app['email']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($app['business_name']); ?></td>
                                <td><?php echo htmlspecialchars($app['business_address']); ?></td> <!-- Changed to business_address -->
                                <td><?php echo date('M d, Y', strtotime($app['applied_at'])); ?></td>
                                <td>
                                    <?php if ($app['application_status'] === 'pending'): ?>
                                        <span class="status-badge pending">⏳ Pending</span>
                                    <?php elseif ($app['application_status'] === 'approved'): ?>
                                        <span class="status-badge approved">✅ Approved</span>
                                    <?php else: ?>
                                        <span class="status-badge rejected">❌ Rejected</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($app['reviewed_at']): ?>
                                        <?php echo date('M d, Y', strtotime($app['reviewed_at'])); ?>
                                        <?php if ($app['reviewed_by_name']): ?>
                                            <br><small>by <?php echo htmlspecialchars($app['reviewed_by_name']); ?></small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <!-- Approve Modal -->
    <div id="approveModal" class="modal">
        <div class="modal-content">
            <h3>✅ Approve Application</h3>
            <p>Approve <strong id="approve_applicant_name"></strong> as a seller?</p>
            <p class="info-text">This will:</p>
            <ul class="modal-list">
                <li>Create a seller profile</li>
                <li>Create a shop for the seller</li>
                <li>Change user role to "seller"</li>
                <li>Allow them to list products</li>
            </ul>

            <form method="POST">
                <input type="hidden" name="application_id" id="approve_app_id">
                <input type="hidden" name="action" value="approve">

                <div class="modal-actions">
                    <button type="button" onclick="closeModal()" class="btn-cancel">Cancel</button>
                    <button type="submit" class="btn-confirm">Approve Application</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Reject Modal -->
    <div id="rejectModal" class="modal">
        <div class="modal-content">
            <h3>❌ Reject Application</h3>
            <p>Reject application from <strong id="reject_applicant_name"></strong>?</p>

            <form method="POST">
                <input type="hidden" name="application_id" id="reject_app_id">

                <div class="form-group">
                    <label>Reason for Rejection *</label>
                    <textarea name="rejection_reason" rows="4" required
                        placeholder="Please provide a reason for rejection..."></textarea>
                </div>

                <input type="hidden" name="action" value="reject">

                <div class="modal-actions">
                    <button type="button" onclick="closeModal()" class="btn-cancel">Cancel</button>
                    <button type="submit" class="btn-confirm-reject">Reject Application</button>
                </div>
            </form>
        </div>
    </div>

    <style>
        .applications-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .application-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
        }

        .application-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .app-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .app-user {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .user-info h3 {
            margin: 0;
            font-size: 1.1rem;
        }

        .user-email {
            margin: 2px 0 0 0;
            color: #666;
            font-size: 0.9rem;
        }

        .app-details {
            margin: 15px 0;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f5f5f5;
        }

        .detail-row .label {
            font-weight: 600;
            color: #666;
        }

        .detail-row .value {
            color: #333;
            text-align: right;
        }

        .permit-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }

        .permit-link:hover {
            text-decoration: underline;
        }

        .app-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .btn-approve {
            flex: 1;
            padding: 12px;
            background: #16a34a;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .btn-approve:hover {
            background: #15803d;
        }

        .btn-reject {
            flex: 1;
            padding: 12px;
            background: #dc2626;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .btn-reject:hover {
            background: #b91c1c;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 500;
            white-space: nowrap;
        }

        .status-badge.pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-badge.approved {
            background: #dcfce7;
            color: #166534;
        }

        .status-badge.rejected {
            background: #fee2e2;
            color: #991b1b;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
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

        .modal-content h3 {
            margin: 0 0 15px 0;
        }

        .info-text {
            margin: 10px 0 5px 0;
            font-weight: 600;
            color: #666;
        }

        .modal-list {
            margin: 10px 0 20px 20px;
            color: #666;
        }

        .modal-list li {
            margin-bottom: 5px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
            font-family: inherit;
            resize: vertical;
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
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }

        .btn-confirm {
            flex: 1;
            padding: 12px;
            background: #16a34a;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
        }

        .btn-confirm-reject {
            flex: 1;
            padding: 12px;
            background: #dc2626;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
        }

        .no-data {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 12px;
            margin: 20px 0;
        }

        .no-data p {
            font-size: 1.2rem;
            color: #16a34a;
            margin: 0;
        }

        @media (max-width: 768px) {
            .applications-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <script>
        function showApproveModal(appId, applicantName) {
            document.getElementById('approve_app_id').value = appId;
            document.getElementById('approve_applicant_name').textContent = applicantName;
            document.getElementById('approveModal').classList.add('active');
        }

        function showRejectModal(appId, applicantName) {
            document.getElementById('reject_app_id').value = appId;
            document.getElementById('reject_applicant_name').textContent = applicantName;
            document.getElementById('rejectModal').classList.add('active');
        }

        function closeModal() {
            document.querySelectorAll('.modal').forEach(m => m.classList.remove('active'));
        }

        // Close modal on background click
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeModal();
                }
            });
        });
    </script>
</body>

</html>