<?php
// public/api/cart.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../app/controllers/CartController.php';
// Create controller instance and handle request
$controller = new CartController();
$controller->handleAjaxRequest();