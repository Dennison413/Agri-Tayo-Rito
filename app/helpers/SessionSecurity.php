<?php
class SessionSecurity {
    /**
     * Initialize secure session
     * Call this at the start of your application
     */
    public static function init() {
        if (session_status() === PHP_SESSION_NONE) {
            // Secure session configuration
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
            ini_set('session.cookie_samesite', 'Strict');
            
            session_start();
            
            // Regenerate session ID on first access
            if (!isset($_SESSION['initiated'])) {
                session_regenerate_id(true);
                $_SESSION['initiated'] = true;
                $_SESSION['created_at'] = time();
            }
            
            // Validate session
            self::validateSession();
        }
    }

    /**
     * Regenerate session ID (after login, role change)
     */
    public static function regenerate() {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
            $_SESSION['regenerated_at'] = time();
        }
    }

    /**
     * Validate session integrity
     */
    private static function validateSession() {
        // Check session age (max 24 hours)
        if (isset($_SESSION['created_at'])) {
            $age = time() - $_SESSION['created_at'];
            if ($age > 86400) { // 24 hours
                self::destroy();
                return false;
            }
        }

        // Check user agent
        if (!isset($_SESSION['user_agent'])) {
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        } else {
            $currentAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            if ($_SESSION['user_agent'] !== $currentAgent) {
                error_log("Session hijacking attempt detected");
                self::destroy();
                return false;
            }
        }

        // Check IP address (optional, can cause issues with mobile users)
        // Uncomment if needed
        /*
        if (!isset($_SESSION['ip_address'])) {
            $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
        } else {
            if ($_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR']) {
                error_log("Session IP mismatch detected");
                self::destroy();
                return false;
            }
        }
        */

        return true;
    }

    /**
     * Destroy session completely
     */
    public static function destroy() {
        session_unset();
        session_destroy();
        
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
    }
}