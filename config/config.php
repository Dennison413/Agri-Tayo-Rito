<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Constants
define('APP_NAME', 'AgriApp');
define('BASE_URL', '/agri_system/public/');  // Changed to relative path

// Load database
require_once __DIR__ . '/database.php';

// Connect
$db = new Database();
$conn = $db->connect();

// Make global
$GLOBALS['db'] = $db;
$GLOBALS['conn'] = $conn;

