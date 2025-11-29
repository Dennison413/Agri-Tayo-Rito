<?php
class CSRF {
    private static $tokenName = 'csrf_token';
    private static $tokenLifetime = 7200; // 2 hours

    /**
     * Generate CSRF token with timestamp
     */
    public static function generateToken() {
        if (!isset($_SESSION[self::$tokenName]) || self::isTokenExpired()) {
            $_SESSION[self::$tokenName] = bin2hex(random_bytes(32));
            $_SESSION[self::$tokenName . '_time'] = time();
        }
        return $_SESSION[self::$tokenName];
    }

    /**
     * Validate CSRF token
     */
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

    /**
     * Check if token is expired
     */
    private static function isTokenExpired() {
        if (!isset($_SESSION[self::$tokenName . '_time'])) {
            return true;
        }
        return (time() - $_SESSION[self::$tokenName . '_time']) > self::$tokenLifetime;
    }

    /**
     * Regenerate token (after expiry or failed validation)
     */
    public static function regenerateToken() {
        unset($_SESSION[self::$tokenName]);
        unset($_SESSION[self::$tokenName . '_time']);
        return self::generateToken();
    }

    /**
     * Get token field for forms (HTML)
     */
    public static function getTokenField() {
        $token = self::generateToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Get token for AJAX requests (JSON)
     */
    public static function getTokenForAjax() {
        return [
            'token' => self::generateToken(),
            'name' => self::$tokenName
        ];
    }

    /**
     * Validate token from POST/GET request
     */
    public static function validateRequest() {
        $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? null;
        return self::validateToken($token);
    }

    /**
     * Validate token from JSON request
     */
    public static function validateJsonRequest() {
        $input = json_decode(file_get_contents('php://input'), true);
        $token = $input['csrf_token'] ?? null;
        return self::validateToken($token);
    }

    /**
     * Handle CSRF validation failure
     */
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