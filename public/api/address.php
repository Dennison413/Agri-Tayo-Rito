<?php
/**
 * Address API Handler
 * File: /agri_system/public/api/address.php
 */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_log("Address API called - Method: {$_SERVER['REQUEST_METHOD']}, User: " . ($_SESSION['user_id'] ?? 'none'));

// Set JSON response headers
header('Content-Type: application/json');
header('Access-Control-Allow-Credentials: true');

// Load dependencies
require_once __DIR__ . '/../../app/models/UserAddress.php';
require_once __DIR__ . '/../../app/helpers/csrf.php';

// Check authentication
if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode([
        'success' => false,
        'message' => 'Please login first',
        'redirect' => '/agri_system/public/auth/login'
    ]);
    exit;
}

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

// Handle GET requests (fetch addresses)
if ($method === 'GET') {
    $addressModel = new UserAddress();
    $userID = $_SESSION['user_id'];
    
    $action = $_GET['action'] ?? 'list';
    
    if ($action === 'list') {
        $addresses = $addressModel->getUserAddresses($userID);
        
        echo json_encode([
            'success' => true,
            'addresses' => $addresses,
            'count' => count($addresses)
        ]);
        exit;
    }
    
    if ($action === 'get' && isset($_GET['id'])) {
        $addressID = filter_var($_GET['id'], FILTER_VALIDATE_INT);
        $address = $addressModel->getAddress($addressID, $userID);
        
        if ($address) {
            echo json_encode([
                'success' => true,
                'address' => $address
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Address not found'
            ]);
        }
        exit;
    }
}

// Handle POST requests (add/update/delete)
if ($method === 'POST') {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid request data'
        ]);
        exit;
    }
    
    // CSRF validation
    if (!CSRF::validateJsonRequest()) {
        CSRF::handleFailure(true);
        exit;
    }
    
    $action = $input['action'] ?? null;
    $addressModel = new UserAddress();
    $userID = $_SESSION['user_id'];
    
    // ADD ADDRESS
    if ($action === 'add') {
        // Validate required fields
        $required = ['address', 'municipality', 'province', 'postal_code'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                echo json_encode([
                    'success' => false,
                    'message' => ucfirst(str_replace('_', ' ', $field)) . ' is required'
                ]);
                exit;
            }
        }
        
        // Sanitize inputs
        $address = filter_var($input['address'], FILTER_SANITIZE_STRING);
        $municipality = filter_var($input['municipality'], FILTER_SANITIZE_STRING);
        $province = filter_var($input['province'], FILTER_SANITIZE_STRING);
        $postal_code = filter_var($input['postal_code'], FILTER_SANITIZE_STRING);
        
        $result = $addressModel->addAddress($userID, $address, $municipality, $province, $postal_code);
        
        if ($result['success']) {
            // Get the newly created address
            $newAddress = $addressModel->getAddress($result['addressID'], $userID);
            
            echo json_encode([
                'success' => true,
                'message' => 'Address added successfully',
                'address' => $newAddress,
                'csrf_token' => CSRF::generateToken()
            ]);
        } else {
            echo json_encode($result);
        }
        exit;
    }
    
    // UPDATE ADDRESS
    if ($action === 'update') {
        $addressID = filter_var($input['addressID'] ?? null, FILTER_VALIDATE_INT);
        
        if (!$addressID) {
            echo json_encode([
                'success' => false,
                'message' => 'Address ID is required'
            ]);
            exit;
        }
        
        // Validate required fields
        $required = ['address', 'municipality', 'province', 'postal_code'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                echo json_encode([
                    'success' => false,
                    'message' => ucfirst(str_replace('_', ' ', $field)) . ' is required'
                ]);
                exit;
            }
        }
        
        // Sanitize inputs
        $address = filter_var($input['address'], FILTER_SANITIZE_STRING);
        $municipality = filter_var($input['municipality'], FILTER_SANITIZE_STRING);
        $province = filter_var($input['province'], FILTER_SANITIZE_STRING);
        $postal_code = filter_var($input['postal_code'], FILTER_SANITIZE_STRING);
        
        $result = $addressModel->updateAddress($addressID, $userID, $address, $municipality, $province, $postal_code);
        
        echo json_encode(array_merge($result, [
            'csrf_token' => CSRF::generateToken()
        ]));
        exit;
    }
    
    // DELETE ADDRESS
    if ($action === 'delete') {
        $addressID = filter_var($input['addressID'] ?? null, FILTER_VALIDATE_INT);
        
        if (!$addressID) {
            echo json_encode([
                'success' => false,
                'message' => 'Address ID is required'
            ]);
            exit;
        }
        
        $result = $addressModel->deleteAddress($addressID, $userID);
        
        echo json_encode(array_merge($result, [
            'csrf_token' => CSRF::generateToken()
        ]));
        exit;
    }
    
    // Invalid action
    echo json_encode([
        'success' => false,
        'message' => 'Invalid action'
    ]);
    exit;
}

// Method not allowed
echo json_encode([
    'success' => false,
    'message' => 'Method not allowed'
]);
exit;