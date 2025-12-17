<?php
class SessionSecurity {
    public static function init() {
        if (session_status() === PHP_SESSION_NONE) {
            // secure session configuration
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
            ini_set('session.cookie_samesite', 'Strict');
            
            session_start();
            
            // regenerate session ID on first access
            if (!isset($_SESSION['initiated'])) {
                session_regenerate_id(true);
                $_SESSION['initiated'] = true;
                $_SESSION['created_at'] = time();
            }
            
            // validate session
            self::validateSession();
        }
    }

    // regenerate session ID
    public static function regenerate() {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
            $_SESSION['regenerated_at'] = time();
        }
    }

    // validate session integrity
    private static function validateSession() {
        if (isset($_SESSION['created_at'])) {
            $age = time() - $_SESSION['created_at'];
            if ($age > 86400) { 
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

        return true;
    }

    // destroy session securely
    public static function destroy() {
        session_unset();
        session_destroy();
        
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
    }
}