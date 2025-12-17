<?php
// public/marketplace/apply-seller.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../controllers/SellerApplications.php';
require_once __DIR__ . '/../../../helpers/csrf.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'buyer') {
    header('Location: ' . BASE_URL . 'auth/login');
    exit;
}

// handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_application') {
    $controller = new SellerApplicationController();
    $controller->submitApplication();
    exit;
}

$controller = new SellerApplicationController();
$existingApp = $controller->getMyApplication();
$canApply = true;
$applicationStatus = null;

if ($existingApp) {
    $applicationStatus = $existingApp['application_status'];
    $canApply = ($applicationStatus === 'rejected');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/jpg" href="/agri_system/public/images/agri-icon.jpg">
    <title>Agri Tayo Rito</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/marketplace/marketplace.css">
    <style>
        .application-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        .application-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .application-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .application-header h1 {
            color: #2d5016;
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .application-header p {
            color: #666;
            font-size: 0.95rem;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: #2d5016;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 0.9rem;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #2d5016;
        }

        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        .file-upload-area {
            border: 2px dashed #2d5016;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            background: #f9f9f9;
            cursor: pointer;
            transition: all 0.3s;
        }

        .file-upload-area:hover {
            background: #f0f0f0;
        }

        .file-upload-area input[type="file"] {
            display: none;
        }

        .upload-icon {
            font-size: 3rem;
            color: #2d5016;
            margin-bottom: 10px;
        }

        .upload-text {
            color: #666;
            font-size: 0.9rem;
        }

        .file-name {
            margin-top: 10px;
            color: #2d5016;
            font-weight: 600;
        }

        .submit-btn {
            width: 100%;
            padding: 15px;
            background: #2d5016;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .submit-btn:hover {
            background: #1f3810;
            transform: translateY(-2px);
        }

        .submit-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }

        .status-banner {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 600;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
            border: 2px solid #ffc107;
        }

        .status-approved {
            background: #d4edda;
            color: #155724;
            border: 2px solid #28a745;
        }

        .status-rejected {
            background: #f8d7da;
            color: #721c24;
            border: 2px solid #dc3545;
        }

        .alert {
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .requirements-box {
            background: #f9f9f9;
            border-left: 4px solid #2d5016;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .requirements-box h3 {
            color: #2d5016;
            font-size: 1.1rem;
            margin-bottom: 15px;
        }

        .requirements-box ul {
            list-style: none;
            padding-left: 0;
        }

        .requirements-box li {
            padding: 8px 0;
            color: #666;
            font-size: 0.9rem;
        }

        .requirements-box li:before {
            content: "✓ ";
            color: #2d5016;
            font-weight: bold;
            margin-right: 8px;
        }

        @media (max-width: 768px) {
            .application-container {
                padding: 15px;
            }

            .application-card {
                padding: 20px;
            }

            .application-header h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/../../marketplace/marketnav.php'; ?>
    <div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="application-container">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?php 
                    echo htmlspecialchars($_SESSION['success']); 
                    unset($_SESSION['success']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error">
                    <?php 
                    echo htmlspecialchars($_SESSION['error']); 
                    unset($_SESSION['error']);
                    ?>
                </div>
            <?php endif; ?>

            <!-- Application Status Banner -->
            <?php if ($existingApp): ?>
                <?php if ($applicationStatus === 'pending'): ?>
                    <div class="status-banner status-pending">
                        ⏳ Your application is pending review by the administrator
                    </div>
                <?php elseif ($applicationStatus === 'approved'): ?>
                    <div class="status-banner status-approved">
                        ✓ Your application has been approved! You are now a seller.
                    </div>
                <?php elseif ($applicationStatus === 'rejected'): ?>
                    <div class="status-banner status-rejected">
                        ✗ Your previous application was rejected. You can reapply below.
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Application Form -->
            <?php if ($canApply): ?>
                <div class="application-card">
                    <div class="application-header">
                        <h1>📝 Apply as a Seller</h1>
                        <p>Join our marketplace and start selling your agricultural products</p>
                    </div>

                    <div class="requirements-box">
                        <h3>Requirements to Become a Seller:</h3>
                        <ul>
                            <li>Valid business name and address</li>
                            <li>Business permit or DTI registration (optional but recommended)</li>
                            <li>Commitment to provide quality agricultural products</li>
                            <li>Agree to follow marketplace policies</li>
                        </ul>
                    </div>

                    <form method="POST" 
                          enctype="multipart/form-data"
                          id="applicationForm">
                        
                        <?php echo CSRF::getTokenField(); ?>
                        <input type="hidden" name="action" value="submit_application">
                        <div class="form-group">
                            <label for="business_name">Business Name *</label>
                            <input type="text" 
                                   id="business_name" 
                                   name="business_name" 
                                   placeholder="e.g., Dela Cruz Fresh Vegetables"
                                   required>
                        </div>

                        <div class="form-group">
                            <label for="business_address">Business Address *</label>
                            <textarea id="business_address" 
                                      name="business_address" 
                                      placeholder="Enter your complete business address including barangay, municipality, and province"
                                      required></textarea>
                        </div>

                        <div class="form-group">
                            <label>Business Permit (Optional)</label>
                            <div class="file-upload-area" onclick="document.getElementById('business_permit').click()">
                                <input type="file" 
                                       id="business_permit" 
                                       name="business_permit" 
                                       accept=".jpg,.jpeg,.png,.pdf">
                                <div class="upload-icon">📄</div>
                                <div class="upload-text">
                                    Click to upload your business permit<br>
                                    <small>Supported: JPG, PNG, PDF (Max 5MB)</small>
                                </div>
                                <div class="file-name" id="fileName"></div>
                            </div>
                        </div>

                        <button type="submit" class="submit-btn">
                            Submit Application
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <div class="application-card">
                    <div class="application-header">
                        <h1>Application Details</h1>
                    </div>
                    
                    <?php if ($existingApp): ?>
                        <div class="form-group">
                            <label>Business Name</label>
                            <input type="text" value="<?php echo htmlspecialchars($existingApp['business_name']); ?>" readonly>
                        </div>
                        
                        <div class="form-group">
                            <label>Business Address</label>
                            <textarea readonly><?php echo htmlspecialchars($existingApp['business_address']); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Applied Date</label>
                            <input type="text" value="<?php echo date('F d, Y', strtotime($existingApp['applied_at'])); ?>" readonly>
                        </div>
                        
                        <?php if ($existingApp['reviewed_at']): ?>
                            <div class="form-group">
                                <label>Reviewed Date</label>
                                <input type="text" value="<?php echo date('F d, Y', strtotime($existingApp['reviewed_at'])); ?>" readonly>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.getElementById('business_permit').addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name;
            const fileNameDisplay = document.getElementById('fileName');
            
            if (fileName) {
                fileNameDisplay.textContent = '📎 ' + fileName;
            } else {
                fileNameDisplay.textContent = '';
            }
        });

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');
            
            if (sidebar) sidebar.classList.toggle('active');
            if (overlay) overlay.classList.toggle('active');
        }

        document.getElementById('applicationForm').addEventListener('submit', function(e) {
            const businessName = document.getElementById('business_name').value.trim();
            const businessAddress = document.getElementById('business_address').value.trim();

            if (!businessName || !businessAddress) {
                e.preventDefault();
                alert('Please fill in all required fields');
                return false;
            }
        });
    </script>
</body>
</html>