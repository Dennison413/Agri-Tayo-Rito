<?php
// Initialize controller and get user data
require_once BASE_PATH . '/app/controllers/ProfileController.php';
require_once BASE_PATH . '/config/database.php';

$profileController = new ProfileController();
$user = $profileController->getProfile();
$userStats = $profileController->getUserStats($user['userID']);

// Get pending orders (not completed/cancelled)
try {
    $db = new Database();
    $conn = $db->connect();
    
    $query = "SELECT 
                o.orderID,
                o.order_date as created_at,
                o.total_amount,
                o.order_status as status,
                GROUP_CONCAT(p.product_name SEPARATOR ', ') as product_name,
                MIN(pi.image_path) as image
              FROM orders o
              JOIN order_items oi ON o.orderID = oi.orderID
              JOIN products p ON oi.productID = p.productID
              LEFT JOIN product_images pi ON p.productID = pi.productID AND pi.is_main = 1
              WHERE o.buyerID = ? AND o.order_status = 'pending'
              GROUP BY o.orderID
              ORDER BY o.order_date DESC
              LIMIT 5";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$user['userID']]);
    $recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format image paths
    foreach ($recentOrders as &$order) {
        if (empty($order['image'])) {
            $order['image'] = '/agri_system/public/images/placeholder-product.jpg';
        } else {
            $order['image'] = '/agri_system/public' . $order['image'];
        }
    }
} catch (Exception $e) {
    error_log("Error fetching orders: " . $e->getMessage());
    $recentOrders = [];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $profileController->updateProfile();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Agri Tayo Rito</title>
    <style>
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5ff;
        }

        .main-content {
            margin-top: 20px;
            margin-bottom: 70px;
            padding: 20px;
            max-width: 1400px;
            margin-left: auto;
            margin-right: auto;
        }

        .alert {
            padding: 12px 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: opacity 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        /* Profile Container */
        .profile-container {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        /* Profile Header */
        .profile-header {
            position: relative;
        }

        .profile-cover {
            position: relative;
            height: 250px;
            overflow: hidden;
            background: linear-gradient(135deg, #2d5016, #4a7c25);
        }

        .cover-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .edit-cover-btn {
            position: absolute;
            bottom: 15px;
            right: 15px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .edit-cover-btn:hover {
            background: rgba(0, 0, 0, 0.9);
            transform: translateY(-2px);
        }

        /* Profile Info Section */
        .profile-info-section {
            display: flex;
            align-items: flex-end;
            padding: 0 30px 20px;
            gap: 20px;
            position: relative;
            margin-top: 10px;
        }

        .profile-avatar-container {
            position: relative;
        }

        .profile-avatar {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    border: 5px solid white;
    object-fit: cover;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}
        .edit-avatar-btn {
            position: absolute;
            bottom: 5px;
            right: 5px;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: #2d5016;
            color: white;
            border: 2px solid white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            transition: all 0.3s;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        .edit-avatar-btn:hover {
            background: #4a7c25;
            transform: scale(1.1);
        }

        .profile-details {
            flex: 1;
            padding-top: 40px;
        }

        .profile-name {
            font-size: 1.8rem;
            font-weight: 700;
            color: #2d5016;
            margin-bottom: 5px;
        }

        .profile-email {
            color: #666;
            font-size: 0.95rem;
            margin-bottom: 3px;
        }

        .profile-joined {
            color: #999;
            font-size: 0.85rem;
        }

        .edit-profile-btn {
            padding: 10px 24px;
            background: #2d5016;
            color: white;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.3s;
            margin-top: 40px;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 8px rgba(45, 80, 22, 0.3);
        }

        .edit-profile-btn:hover {
            background: #1f3810;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(45, 80, 22, 0.4);
        }

        /* Profile Content */
        .profile-content {
            padding: 30px;
        }

        /* Profile Cards */
        .profile-card {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
        }

        .profile-card:hover {
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .card-header h2 {
            font-size: 1.3rem;
            font-weight: 700;
            color: #2d5016;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .card-edit-btn,
        .card-add-btn {
            padding: 8px 16px;
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 20px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 600;
            color: #666;
            transition: all 0.3s;
        }

        .card-edit-btn:hover {
            background: #f5f5f5;
            border-color: #2d5016;
            color: #2d5016;
        }

        .card-add-btn {
            background: #2d5016;
            color: white;
            border-color: #2d5016;
        }

        .card-add-btn:hover {
            background: #1f3810;
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(45, 80, 22, 0.3);
        }

        /* Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-item label {
            font-size: 0.75rem;
            color: #999;
            margin-bottom: 5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-value {
            font-size: 0.95rem;
            color: #333;
            font-weight: 600;
        }

        .info-item input {
            padding: 10px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 0.9rem;
            transition: all 0.3s;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .info-item input:focus {
            outline: none;
            border-color: #2d5016;
            box-shadow: 0 0 0 3px rgba(45, 80, 22, 0.1);
        }

        .info-item input:disabled {
            background-color: #f5f5f5;
            cursor: not-allowed;
            color: #999;
        }

        /* Form Actions */
        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #f0f0f0;
        }

        .btn-save,
        .btn-cancel {
            padding: 10px 24px;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-save {
            background: #2d5016;
            color: white;
            box-shadow: 0 2px 8px rgba(45, 80, 22, 0.3);
        }

        .btn-save:hover {
            background: #1f3810;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(45, 80, 22, 0.4);
        }

        .btn-cancel {
            background: white;
            color: #666;
            border: 2px solid #e0e0e0;
        }

        .btn-cancel:hover {
            background: #f5f5f5;
            border-color: #999;
        }

        /* Address Cards */
        .addresses-list {
            display: grid;
            gap: 15px;
        }

        .address-card {
            position: relative;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 20px;
            transition: all 0.3s;
        }

        .address-card:hover {
            border-color: #2d5016;
            box-shadow: 0 4px 15px rgba(45, 80, 22, 0.1);
        }

        .address-card.default {
            border-color: #2d5016;
            background: linear-gradient(135deg, rgba(45, 80, 22, 0.05), rgba(74, 124, 37, 0.05));
        }

        .address-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: #2d5016;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .address-content h3 {
            font-size: 1.1rem;
            margin-bottom: 10px;
            color: #2d5016;
            font-weight: 700;
        }

        .address-recipient {
            font-weight: 600;
            color: #333;
            margin-bottom: 3px;
            font-size: 0.95rem;
        }

        .address-phone {
            color: #666;
            font-size: 0.85rem;
            margin-bottom: 8px;
        }

        .address-text {
            color: #555;
            line-height: 1.6;
            font-size: 0.9rem;
        }

        .address-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid #f0f0f0;
        }

        .address-actions button {
            padding: 6px 14px;
            border: 2px solid #e0e0e0;
            background: white;
            border-radius: 20px;
            cursor: pointer;
            font-size: 0.8rem;
            font-weight: 600;
            transition: all 0.3s;
        }

        .address-actions button:hover {
            background: #f5f5f5;
            border-color: #2d5016;
            color: #2d5016;
            transform: translateY(-2px);
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .stat-card {
            text-align: center;
            padding: 25px;
            background: white;
            border-radius: 12px;
            border: 2px solid #e0e0e0;
            transition: all 0.3s;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(45, 80, 22, 0.15);
            border-color: #2d5016;
        }

        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #2d5016;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 0.85rem;
            color: #666;
            font-weight: 600;
        }

        /* Orders List */
        .orders-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .order-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
            background: white;
        }

        .order-item:hover {
            border-color: #2d5016;
            box-shadow: 0 4px 15px rgba(45, 80, 22, 0.1);
            transform: translateY(-2px);
        }

        .order-image {
            width: 80px;
            height: 80px;
            border-radius: 8px;
            overflow: hidden;
            flex-shrink: 0;
        }

        .order-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .order-info {
            flex: 1;
        }

        .order-info h3 {
            font-size: 0.95rem;
            font-weight: 700;
            margin-bottom: 5px;
            color: #2d5016;
        }

        .order-id {
            font-size: 0.8rem;
            color: #666;
            margin-bottom: 2px;
        }

        .order-date {
            font-size: 0.8rem;
            color: #999;
        }

        .order-status {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .order-status.delivered {
            background: #d4edda;
            color: #155724;
        }

        .order-status.shipping {
            background: #fff3cd;
            color: #856404;
        }

        .order-status.pending {
            background: #cce5ff;
            color: #004085;
        }

        .order-price {
            font-size: 1.1rem;
            font-weight: 700;
            color: #2d5016;
            margin-left: 15px;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
        }

        .empty-state p {
            color: #666;
            margin-bottom: 20px;
            font-size: 0.95rem;
        }

        .btn-primary {
            padding: 10px 24px;
            background: #2d5016;
            color: white;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
            box-shadow: 0 2px 8px rgba(45, 80, 22, 0.3);
        }

        .btn-primary:hover {
            background: #1f3810;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(45, 80, 22, 0.4);
        }

        /* Security List */
        .security-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .security-btn {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 18px;
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
            text-align: left;
            width: 100%;
        }

        .security-btn:hover {
            border-color: #2d5016;
            box-shadow: 0 4px 15px rgba(45, 80, 22, 0.1);
            transform: translateY(-2px);
        }

        .security-icon {
            font-size: 2rem;
        }

        .security-info {
            flex: 1;
        }

        .security-info h3 {
            font-size: 0.95rem;
            font-weight: 700;
            margin-bottom: 3px;
            color: #2d5016;
        }

        .security-info p {
            font-size: 0.8rem;
            color: #666;
        }

        .security-btn .arrow {
            font-size: 1.5rem;
            color: #999;
        }

        .view-all-link {
            color: #2d5016;
            font-weight: 600;
            text-decoration: none;
            font-size: 0.85rem;
            transition: all 0.3s;
        }

        .view-all-link:hover {
            color: #1f3810;
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 15px;
                margin-top: 60px;
            }

            .profile-info-section {
                flex-direction: column;
                align-items: flex-start;
                padding: 0 20px 20px;
            }

            .edit-profile-btn {
                margin-top: 15px;
                width: 100%;
                justify-content: center;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .order-item {
                flex-wrap: wrap;
            }

            .order-price {
                margin-left: 0;
                width: 100%;
                text-align: right;
            }

            .profile-content {
                padding: 20px;
            }

            .profile-name {
                font-size: 1.5rem;
            }

            .profile-cover {
                height: 200px;
            }

            .profile-avatar {
                width: 100px;
                height: 100px;
                font-size: 2.5rem;
            }
        }

        @media (max-width: 480px) {
            .main-content {
                padding: 10px;
            }

            .profile-content {
                padding: 15px;
            }

            .profile-card {
                padding: 15px;
            }

            .card-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .card-edit-btn,
            .card-add-btn {
                width: 100%;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .form-actions {
                flex-direction: column;
            }

            .btn-save,
            .btn-cancel {
                width: 100%;
            }

            .address-actions {
                flex-direction: column;
            }

            .address-actions button {
                width: 100%;
            }

            .profile-cover {
                height: 150px;
            }

            .profile-avatar {
                width: 80px;
                height: 80px;
                font-size: 2rem;
            }

            .profile-info-section {
                margin-top: -40px;
                padding: 0 15px 15px;
            }

            .profile-name {
                font-size: 1.2rem;
            }

            .profile-email,
            .profile-joined {
                font-size: 0.8rem;
            }
        }

        /* ============================================
   AVATAR MODAL STYLES
   ============================================ */

        /* Modal Overlay */
        .modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

/* Modal Content Container - IMPROVED SIZE */
.modal-content {
    background: white;
    border-radius: 16px;
    padding: 0;
    width: 90%;
    max-width: 800px;  /* Increased from 650px */
    max-height: 90vh;
    overflow: hidden;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
    animation: slideUp 0.3s ease;
}

@keyframes slideUp {
    from {
        transform: translateY(50px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

/* Modal Header */
.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 30px;
    border-bottom: 2px solid #f0f0f0;
    background: linear-gradient(135deg, #f8f9fa, #ffffff);
}

.modal-header h2 {
    font-size: 1.4rem;
    font-weight: 700;
    color: #2d5016;
    margin: 0;
}

/* Modal Close Button */
.modal-close {
    background: none;
    border: none;
    font-size: 2rem;
    color: #999;
    cursor: pointer;
    padding: 0;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.3s;
    line-height: 1;
}

.modal-close:hover {
    background: #f5f5f5;
    color: #333;
    transform: rotate(90deg);
}
        .upload-section {
    padding: 15px 30px;
    background: #f8f9fa;
    border-bottom: 1px solid #e0e0e0;
}

.upload-btn {
    width: 100%;
    padding: 15px;  /* Reduced from 20px */
    background: white;
    border: 2px dashed #2d5016;
    border-radius: 10px;
    cursor: pointer;
    display: flex;
    flex-direction: row;  /* Changed from column to row */
    align-items: center;
    justify-content: center;
    gap: 12px;
    transition: all 0.3s;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.upload-btn:hover {
    background: #f0f8f0;
    border-color: #4a7c25;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(45, 80, 22, 0.2);
}

.upload-icon {
    font-size: 1.8rem;  /* Reduced from 2.5rem */
}

.upload-btn span:nth-child(2) {
    font-size: 0.9rem;  /* Reduced from 1rem */
    font-weight: 700;
    color: #2d5016;
}

.upload-hint {
    font-size: 0.7rem;
    color: #666;
}

.upload-preview {
    margin-top: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
}

.upload-preview img {
    width: 80px;  /* Reduced from 120px */
    height: 80px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #2d5016;
    box-shadow: 0 4px 12px rgba(45, 80, 22, 0.3);
}

.preview-label {
    font-size: 0.8rem;
    font-weight: 600;
    color: #2d5016;
}

/* Divider - MADE COMPACT */
.divider {
    padding: 12px 30px;  /* Reduced from 15px */
    text-align: center;
    position: relative;
    background: white;
}

.divider span {
    background: white;
    padding: 0 15px;
    color: #999;
    font-size: 0.8rem;
    font-weight: 600;
    position: relative;
    z-index: 1;
}

.divider::before {
    content: '';
    position: absolute;
    left: 30px;
    right: 30px;
    top: 50%;
    height: 1px;
    background: #e0e0e0;
}

.avatar-grid {
    display: grid;
    grid-template-columns: repeat(6, 1fr);  /* Show 6 per row on desktop */
    gap: 20px;
    padding: 25px 30px;
    max-height: 50vh;  /* Reduced from 55vh */
    overflow-y: auto;
}

/* Custom Scrollbar */
.avatar-grid::-webkit-scrollbar {
    width: 8px;
}

.avatar-grid::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.avatar-grid::-webkit-scrollbar-thumb {
    background: #2d5016;
    border-radius: 10px;
}

.avatar-grid::-webkit-scrollbar-thumb:hover {
    background: #4a7c25;
}

/* Avatar Options */
.avatar-option {
    width: 100%;
    aspect-ratio: 1;  /* Maintains square shape */
    border-radius: 50%;
    cursor: pointer;
    border: 3px solid transparent;
    transition: all 0.3s ease;
    object-fit: cover;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.avatar-option:hover {
    border-color: #4a7c25;
    transform: scale(1.1);
    box-shadow: 0 6px 20px rgba(74, 124, 37, 0.4);
}

.avatar-option.selected {
    border-color: #2d5016;
    box-shadow: 0 0 0 4px rgba(45, 80, 22, 0.2);
    transform: scale(1.05);
}

.modal-footer {
    display: flex;
    gap: 12px;
    padding: 20px 30px;
    border-top: 2px solid #f0f0f0;
    justify-content: flex-end;
    background: #f8f9fa;
    position: sticky;
    bottom: 0;
}

.modal-footer button {
    min-width: 120px;
}
/* Responsive adjustments */
@media (max-width: 768px) {
    .upload-section {
        padding: 15px 20px;
    }
    
    .upload-btn {
        padding: 15px;
    }
    
    .upload-icon {
        font-size: 2rem;
    }
    
    .upload-preview img {
        width: 100px;
        height: 100px;
    }
}

@media (max-width: 480px) {
    .upload-section {
        padding: 15px;
    }
    
    .upload-btn {
        padding: 15px 10px;
    }
    
    .upload-btn span:nth-child(2) {
        font-size: 0.9rem;
    }
    
    .upload-hint {
        font-size: 0.7rem;
    }
    
    .divider {
        padding: 12px 15px;
    }
}
    </style>
</head>

<body>
    <div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

    <main class="main-content">
        <div class="profile-container">

            <!-- Success/Error Messages -->
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

            <!-- Profile Header -->
            <div class="profile-header">
                <div class="profile-cover">
                    <img src="<?php 
                        if (!empty($user['profile_image']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/agri_system/public' . $user['profile_image'])) {
                            echo '/agri_system/public' . htmlspecialchars($user['profile_image']);
                        } else {
                            echo 'https://images.unsplash.com/photo-1464226184884-fa280b87c399?w=1200';
                        }
                    ?>" alt="Cover" class="cover-img">
                    <input type="file" id="coverInput" accept="image/jpeg,image/png" style="display: none;" onchange="handleCoverUpload(event)">
                    <button class="edit-cover-btn" onclick="editCover()">📷 Change Cover</button>
                </div>

                <div class="profile-info-section">
                    <div class="profile-avatar-container">
                        <img src="<?php echo $profileController->getAvatarPath($user['avatar']); ?>"
                            alt="Profile Avatar"
                            class="profile-avatar"
                            id="currentAvatar">
                        <button class="edit-avatar-btn" onclick="editAvatar()">📷</button>
                    </div>

                    <div class="profile-details">
                        <h1 class="profile-name"><?php echo htmlspecialchars($user['full_name']); ?></h1>
                        <p class="profile-email"><?php echo htmlspecialchars($user['email']); ?></p>
                        <p class="profile-joined">Member since <?php echo $profileController->formatDate($user['created_at']); ?></p>
                    </div>

                    <button class="edit-profile-btn" onclick="toggleEditMode()">
                        ✏️ Edit Profile
                    </button>
                </div>
            </div>

            <!-- Profile Content -->
            <div class="profile-content">

                <!-- Personal Information (Removed address fields) -->
                <div class="profile-card">
                    <div class="card-header">
                        <h2>👤 Personal Information</h2>
                        <button class="card-edit-btn" onclick="editPersonalInfo()">Edit</button>
                    </div>

                    <!-- View Mode -->
                    <div class="info-grid" id="personalInfoView">
                        <div class="info-item">
                            <label>Full Name</label>
                            <div class="info-value"><?php echo htmlspecialchars($user['full_name']); ?></div>
                        </div>
                        <div class="info-item">
                            <label>Email</label>
                            <div class="info-value"><?php echo htmlspecialchars($user['email']); ?></div>
                        </div>
                        <div class="info-item">
                            <label>Phone Number</label>
                            <div class="info-value"><?php echo $profileController->formatPhone($user['phone']); ?></div>
                        </div>
                    </div>

                    <!-- Edit Mode -->
                    <form method="POST" action="/agri_system/public/profile/user" id="personalInfoEdit" style="display: none;">
                        <div class="info-grid">
                            <div class="info-item">
                                <label>Full Name *</label>
                                <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                            </div>
                            <div class="info-item">
                                <label>Email (Cannot be changed)</label>
                                <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                            </div>
                            <div class="info-item">
                                <label>Phone Number *</label>
                                <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="submit" name="update_profile" class="btn-save">Save Changes</button>
                            <button type="button" class="btn-cancel" onclick="cancelEdit()">Cancel</button>
                        </div>
                    </form>
                </div>

                <!-- Address Management (NEW - Replaces Primary Address) -->
                <div class="profile-card">
                    <div class="card-header">
                        <h2>📍 My Addresses</h2>
                        <button class="card-add-btn" onclick="openAddAddressModal()">+ Add Address</button>
                    </div>
                    <div class="addresses-list" id="addressesContainer">
                        <!-- Addresses will be loaded here via JavaScript -->
                        <div class="empty-state">
                            <p>Loading addresses...</p>
                        </div>
                    </div>
                </div>

                <!-- Account Statistics -->
                <div class="profile-card">
                    <div class="card-header">
                        <h2>📊 Account Statistics</h2>
                    </div>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon">📦</div>
                            <div class="stat-value"><?php echo $userStats['total_orders']; ?></div>
                            <div class="stat-label">Total Orders</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">✅</div>
                            <div class="stat-value"><?php echo $userStats['completed_orders']; ?></div>
                            <div class="stat-label">Completed</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">❤️</div>
                            <div class="stat-value"><?php echo $userStats['wishlist_count']; ?></div>
                            <div class="stat-label">Wishlist Items</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">⭐</div>
                            <div class="stat-value"><?php echo $userStats['reviews_count']; ?></div>
                            <div class="stat-label">Reviews Given</div>
                        </div>
                    </div>
                </div>

                <!-- Recent Orders (Now shows PENDING orders only) -->
                <div class="profile-card">
                    <div class="card-header">
                        <h2>📦 Pending Orders</h2>
                        <a href="/agri_system/public/marketplace/myorders" class="view-all-link">View All →</a>
                    </div>
                    <div class="orders-list">
                        <?php if (empty($recentOrders)): ?>
                            <div class="empty-state">
                                <p>No pending orders. Start shopping in our marketplace!</p>
                                <a href="/agri_system/public/marketplace" class="btn-primary">Browse Products</a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recentOrders as $order): ?>
                                <div class="order-item" onclick="viewOrderDetails(<?php echo $order['orderID']; ?>)">
                                    <div class="order-image">
                                        <img src="<?php echo htmlspecialchars($order['image']); ?>" alt="Order">
                                    </div>
                                    <div class="order-info">
                                        <h3><?php echo htmlspecialchars($order['product_name']); ?></h3>
                                        <p class="order-id">Order #<?php echo htmlspecialchars($order['orderID']); ?></p>
                                        <p class="order-date"><?php echo $profileController->formatDate($order['created_at']); ?></p>
                                    </div>
                                    <div class="order-status <?php echo strtolower($order['status']); ?>">
                                        <?php echo htmlspecialchars($order['status']); ?>
                                    </div>
                                    <div class="order-price">₱<?php echo number_format($order['total_amount'], 2); ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Security Settings -->
                <div class="profile-card">
                    <div class="card-header">
                        <h2>🔒 Security</h2>
                    </div>
                    <div class="security-list">
                        <button class="security-btn" onclick="changePassword()">
                            <div class="security-icon">🔑</div>
                            <div class="security-info">
                                <h3>Change Password</h3>
                                <p>Update your password regularly</p>
                            </div>
                            <span class="arrow">›</span>
                        </button>
                        <button class="security-btn" onclick="enable2FA()">
                            <div class="security-icon">🔐</div>
                            <div class="security-info">
                                <h3>Two-Factor Authentication</h3>
                                <p>Add an extra layer of security (Coming soon)</p>
                            </div>
                            <span class="arrow">›</span>
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <!-- Avatar Selection Modal -->
    <div id="avatarModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Choose Your Avatar</h2>
                <button class="modal-close" onclick="closeAvatarModal()">&times;</button>
            </div>
            
            <!-- Upload Custom Avatar Section -->
            <div class="upload-section">
                <input type="file" 
                       id="customAvatarInput" 
                       accept="image/jpeg,image/jpg,image/png,image/gif" 
                       onchange="handleCustomFileSelect(event)" 
                       style="display: none;">
                
                <button class="upload-btn" onclick="triggerFileInput()">
                    <span class="upload-icon">📤</span>
                    <span>Upload Custom Avatar</span>
                    <span class="upload-hint">JPEG, PNG, GIF (Max 2MB)</span>
                </button>
                
                <!-- Upload Preview -->
                <div id="uploadPreview" class="upload-preview" style="display: none;">
                    <img id="uploadPreviewImg" src="" alt="Preview">
                </div>
            </div>
            
            <div class="divider">
                <span>Or choose from preset avatars</span>
            </div>
            
            <!-- Preset Avatars Grid -->
            <div class="avatar-grid">
                <?php
                $availableAvatars = $profileController->getAvailableAvatars();
                foreach ($availableAvatars as $avatarPath):
                    $isSelected = ($user['avatar'] === $avatarPath) ? 'selected' : '';
                ?>
                    <img src="/agri_system/public<?php echo $avatarPath; ?>"
                        alt="Avatar"
                        class="avatar-option <?php echo $isSelected; ?>"
                        data-avatar="<?php echo htmlspecialchars($avatarPath); ?>"
                        onclick="selectAvatar('<?php echo htmlspecialchars($avatarPath); ?>', this)">
                <?php endforeach; ?>
            </div>
            
            <div class="modal-footer">
                <button class="btn-cancel" onclick="closeAvatarModal()">Cancel</button>
                <button class="btn-save" id="saveAvatarBtn" onclick="saveAvatar()">Save Avatar</button>
            </div>
        </div>
    </div>

    <!-- Address Modal -->
    <div id="addressModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="addressModalTitle">Add New Address</h2>
                <button class="modal-close" onclick="closeAddressModal()">&times;</button>
            </div>
            
            <form id="addressForm" onsubmit="saveAddress(event)">
                <input type="hidden" id="addressID" value="">
                
                <div class="form-body" style="padding: 30px;">
                    <div class="form-group">
                        <label>Complete Address *</label>
                        <textarea id="addressField" 
                                  required 
                                  placeholder="House/Unit No., Street, Barangay"
                                  style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-family: inherit; min-height: 80px;"></textarea>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label>Municipality *</label>
                            <input type="text" 
                                   id="municipalityField" 
                                   required 
                                   placeholder="e.g., San Pablo City"
                                   style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-family: inherit;">
                        </div>
                        
                        <div class="form-group">
                            <label>Province *</label>
                            <input type="text" 
                                   id="provinceField" 
                                   required 
                                   placeholder="e.g., Laguna"
                                   style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-family: inherit;">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Postal Code *</label>
                        <input type="text" 
                               id="postalCodeField" 
                               required 
                               placeholder="e.g., 4000"
                               maxlength="10"
                               style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-family: inherit;">
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeAddressModal()">Cancel</button>
                    <button type="submit" class="btn-save">Save Address</button>
                </div>
            </form>
        </div>
    </div>

    <?php include BASE_PATH . '/app/views/marketplace/marketnav.php'; ?>

    <script src="/agri_system/public/js/marketplace.js"></script>
    <script src="/agri_system/public/js/profile.js"></script>
</body>
</html>