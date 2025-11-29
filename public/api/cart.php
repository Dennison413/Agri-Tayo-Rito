<?php
/**
 * API Router for Cart Operations
 * File: /agri_system/public/api/cart.php
 */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load the CartController
require_once __DIR__ . '/../../app/controllers/CartController.php';

// Create controller instance and handle request
$controller = new CartController();
$controller->handleAjaxRequest();