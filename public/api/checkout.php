<?php
/**
 * API Router for Checkout Operations
 * File: /agri_system/public/api/checkout.php
 */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load the OrderController
require_once __DIR__ . '/../../app/controllers/OrderController.php';

// Create controller instance and handle request
$controller = new OrderController();
$controller->handleCheckoutRequest();