<?php
// public/api/checkout.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../app/controllers/OrderController.php';
$controller = new OrderController();
$controller->handleCheckoutRequest();