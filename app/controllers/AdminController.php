<?php
// app/controllers/AdminController.php

require_once __DIR__ . '/../models/Admin.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Orders.php';

class AdminController
{
    private $admin;

    public function __construct()
    {
        $this->admin = new Admin();
    }

    // ===========================
    // DASHBOARD
    // ===========================
    public function dashboard()
    {
        $stats = $this->admin->getDashboardStats();

        include __DIR__ . '/../../views/admin/dashboard.php';
    }

    public function revenueAnalytics()
    {
        $start = $_GET['start'] ?? null;
        $end   = $_GET['end'] ?? null;

        $data = $this->admin->getRevenueAnalytics($start, $end);

        echo json_encode($data);
    }

    public function topSellingProducts()
    {
        $limit = $_GET['limit'] ?? 10;

        $data = $this->admin->getTopSellingProducts($limit);

        echo json_encode($data);
    }

    public function topShops()
    {
        $limit = $_GET['limit'] ?? 10;

        $data = $this->admin->getTopShops($limit);

        echo json_encode($data);
    }

    // ===========================
    // USER MANAGEMENT
    // ===========================
    public function users()
    {
        $filters = [
            'role'      => $_GET['role']      ?? null,
            'is_active' => $_GET['is_active'] ?? null,
            'search'    => $_GET['search']    ?? null,
        ];

        $limit  = $_GET['limit']  ?? 50;
        $offset = $_GET['offset'] ?? 0;

        $data = $this->admin->getAllUsers($filters, $limit, $offset);

        include __DIR__ . '/../../views/admin/users.php';
    }

    public function toggleUserStatus()
    {
        $userID  = $_POST['userID'];
        $adminID = $_SESSION['adminID'];  
        $reason  = $_POST['reason'] ?? null;

        $result = $this->admin->toggleUserStatus($userID, $adminID, $reason);

        echo json_encode($result);
    }

    public function userActivity()
    {
        $userID = $_GET['userID'];
        $limit  = $_GET['limit'] ?? 20;

        $data = $this->admin->getUserActivity($userID, $limit);

        echo json_encode($data);
    }

    // ===========================
    // ACCOUNT DELETION REQUESTS
    // ===========================
    public function deletionRequests()
    {
        $data = $this->admin->getPendingDeletionRequests();

        include __DIR__ . '/../../views/admin/deletion_requests.php';
    }

    public function approveDeletion()
    {
        $requestID = $_POST['requestID'];
        $adminID   = $_SESSION['adminID'];

        $result = $this->admin->approveDeletionRequest($requestID, $adminID);

        echo json_encode($result);
    }

    public function rejectDeletion()
    {
        $requestID = $_POST['requestID'];
        $adminID   = $_SESSION['adminID'];
        $reason    = $_POST['reason'] ?? null;

        $result = $this->admin->rejectDeletionRequest($requestID, $adminID, $reason);

        echo json_encode($result);
    }

    // ===========================
    // CATEGORY MANAGEMENT
    // ===========================
    public function addCategory()
    {
        $name = $_POST['category_name'];
        $image = $_POST['image'] ?? null;

        $result = $this->admin->addCategory($name, $image);

        echo json_encode($result);
    }

    public function updateCategory()
    {
        $id = $_POST['categoryID'];
        $name = $_POST['category_name'];

        $result = $this->admin->updateCategory($id, $name);

        echo json_encode($result);
    }

    public function deleteCategory()
    {
        $id = $_POST['categoryID'];

        $result = $this->admin->deleteCategory($id);

        echo json_encode($result);
    }

    // ===========================
    // DELIVERY / ORDER MANAGEMENT
    // ===========================
    public function pendingPickups()
    {
        $data = $this->admin->getOrdersPendingPickup();

        include __DIR__ . '/../../views/admin/pending_pickups.php';
    }

    public function assignRider()
    {
        $orderID = $_POST['orderID'];
        $riderID = $_POST['riderID'];
        $adminID = $_SESSION['adminID'];

        $result = $this->admin->assignRiderToOrder($orderID, $riderID, $adminID);

        echo json_encode($result);
    }

    public function receivePayment()
    {
        $orderID = $_POST['orderID'];
        $adminID = $_SESSION['adminID'];

        $result = $this->admin->receivePaymentFromBuyer($orderID, $adminID);

        echo json_encode($result);
    }

    // ===========================
    // SYSTEM SETTINGS
    // ===========================
    public function settings()
    {
        $settings = $this->admin->getAllSystemSettings();

        include __DIR__ . '/../../views/admin/settings.php';
    }

    public function updateSetting()
    {
        $key   = $_POST['key'];
        $value = $_POST['value'];

        $result = $this->admin->updateSystemSetting($key, $value);

        echo json_encode($result);
    }

    // ===========================
    // REPORTS
    // ===========================
    public function salesReport()
    {
        $start = $_GET['start'];
        $end   = $_GET['end'];

        $data = $this->admin->generateSalesReport($start, $end);

        echo json_encode($data);
    }

    public function sellerEarningsReport()
    {
        $start = $_GET['start'];
        $end   = $_GET['end'];

        $data = $this->admin->generateSellerEarningsReport($start, $end);

        echo json_encode($data);
    }

    public function deliveryPerformance()
    {
        $start = $_GET['start'] ?? null;
        $end   = $_GET['end']   ?? null;

        $data = $this->admin->getDeliveryPerformance($start, $end);

        echo json_encode($data);
    }
}
