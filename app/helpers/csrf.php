<?php
class CSRF {
    private static $tokenName = 'csrf_token';
    private static $tokenLifetime = 7200; // 2 hours

    // generate a new CSRF token
    public static function generateToken() {
        if (!isset($_SESSION[self::$tokenName]) || self::isTokenExpired()) {
            $_SESSION[self::$tokenName] = bin2hex(random_bytes(32));
            $_SESSION[self::$tokenName . '_time'] = time();
        }
        return $_SESSION[self::$tokenName];
    }

    // validate a given CSRF token
    public static function validateToken($token) {
        if (!isset($_SESSION[self::$tokenName]) || !isset($token)) {
            error_log("CSRF validation failed: Token missing");
            return false;
        }

        if (self::isTokenExpired()) {
            error_log("CSRF validation failed: Token expired");
            self::regenerateToken();
            return false;
        }

        $valid = hash_equals($_SESSION[self::$tokenName], $token);
        
        if (!$valid) {
            error_log("CSRF validation failed: Token mismatch");
        }

        return $valid;
    }

    // check if the token is expired
    private static function isTokenExpired() {
        if (!isset($_SESSION[self::$tokenName . '_time'])) {
            return true;
        }
        return (time() - $_SESSION[self::$tokenName . '_time']) > self::$tokenLifetime;
    }

    // regenerate the CSRF token (invalidate the old one)
    public static function regenerateToken() {
        unset($_SESSION[self::$tokenName]);
        unset($_SESSION[self::$tokenName . '_time']);
        return self::generateToken();
    }

    // get hidden input field for forms
    public static function getTokenField() {
        $token = self::generateToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    // get token for AJAX requests
    public static function getTokenForAjax() {
        return [
            'token' => self::generateToken(),
            'name' => self::$tokenName
        ];
    }

    // Validate token from standard request (POST/GET)
    public static function validateRequest() {
        $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? null;
        return self::validateToken($token);
    }

    // Validate token from JSON request body
    public static function validateJsonRequest() {
        $input = json_decode(file_get_contents('php://input'), true);
        $token = $input['csrf_token'] ?? null;
        return self::validateToken($token);
    }

    // Handle CSRF validation failure
    public static function handleFailure($isAjax = false) {
        if ($isAjax) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Security token validation failed. Please refresh the page.',
                'error_code' => 'CSRF_VALIDATION_FAILED'
            ]);
            exit;
        } else {
            $_SESSION['error'] = 'Security validation failed. Please try again.';
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/agri_system/public/marketplace'));
            exit;
        }
    }
}