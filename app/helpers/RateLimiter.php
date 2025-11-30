<?php
class RateLimiter {
    // 🔧 DEVELOPMENT MODE: Set to false in production
    private static $devMode = false;
    
    /**
     * Check login attempts (5 attempts per 15 minutes)
     */
    public static function checkLoginAttempts($email) {
        if (self::$devMode) {
            return ['allowed' => true, 'remaining' => 999];
        }
        
        $key = 'login_attempts_' . md5($email);
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 0, 'time' => time()];
        }
        
        $attempts = $_SESSION[$key];
        
        // Reset after 15 minutes (900 seconds)
        if (time() - $attempts['time'] > 900) {
            $_SESSION[$key] = ['count' => 0, 'time' => time()];
            return ['allowed' => true, 'remaining' => 5];
        }
        
        // Max 5 attempts in 15 minutes
        if ($attempts['count'] >= 10) {
            $timeRemaining = 900 - (time() - $attempts['time']);
            return [
                'allowed' => false, 
                'remaining' => 0,
                'retry_after' => ceil($timeRemaining / 60) // minutes
            ];
        }
        
        return [
            'allowed' => true, 
            'remaining' => 5 - $attempts['count']
        ];
    }
    
    /**
     * Record failed login attempt
     */
    public static function recordFailedLogin($email) {
        if (self::$devMode) return;
        
        $key = 'login_attempts_' . md5($email);
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 0, 'time' => time()];
        }
        
        $_SESSION[$key]['count']++;
        $_SESSION[$key]['last_attempt'] = time();
    }
    
    /**
     * Reset login attempts (after successful login)
     */
    public static function resetLoginAttempts($email) {
        $key = 'login_attempts_' . md5($email);
        unset($_SESSION[$key]);
    }

    /**
     * Check registration attempts (50 per hour per IP in dev mode, 3 in production)
     */
    public static function checkRegistrationAttempts() {
        $key = 'registration_attempts_' . md5($_SERVER['REMOTE_ADDR']);
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 0, 'time' => time()];
        }
        
        $attempts = $_SESSION[$key];
        
        // Reset after 1 hour (3600 seconds)
        if (time() - $attempts['time'] > 3600) {
            $_SESSION[$key] = ['count' => 0, 'time' => time()];
            return ['allowed' => true, 'remaining' => self::$devMode ? 50 : 3];
        }
        
        // Max registrations per hour per IP
        $maxAttempts = self::$devMode ? 50 : 3; // 50 in dev, 3 in production
        
        if ($attempts['count'] >= $maxAttempts) {
            $timeRemaining = 3600 - (time() - $attempts['time']);
            return [
                'allowed' => false,
                'remaining' => 0,
                'retry_after' => ceil($timeRemaining / 60)
            ];
        }
        
        return ['allowed' => true, 'remaining' => $maxAttempts - $attempts['count']];
    }

    /**
     * Record registration attempt
     */
    public static function recordRegistrationAttempt() {
        $key = 'registration_attempts_' . md5($_SERVER['REMOTE_ADDR']);
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 0, 'time' => time()];
        }
        
        $_SESSION[$key]['count']++;
    }

    /**
     * Check password reset attempts (3 per hour per email)
     */
    public static function checkPasswordResetAttempts($email) {
        if (self::$devMode) {
            return ['allowed' => true, 'remaining' => 999];
        }
        
        $key = 'password_reset_' . md5($email);
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 0, 'time' => time()];
        }
        
        $attempts = $_SESSION[$key];
        
        if (time() - $attempts['time'] > 3600) {
            $_SESSION[$key] = ['count' => 0, 'time' => time()];
            return ['allowed' => true, 'remaining' => 3];
        }
        
        if ($attempts['count'] >= 3) {
            $timeRemaining = 3600 - (time() - $attempts['time']);
            return [
                'allowed' => false,
                'remaining' => 0,
                'retry_after' => ceil($timeRemaining / 60)
            ];
        }
        
        return ['allowed' => true, 'remaining' => 3 - $attempts['count']];
    }

    /**
     * Record password reset attempt
     */
    public static function recordPasswordResetAttempt($email) {
        if (self::$devMode) return;
        
        $key = 'password_reset_' . md5($email);
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 0, 'time' => time()];
        }
        
        $_SESSION[$key]['count']++;
    }

    /**
     * Check API/AJAX request rate (60 requests per minute per session)
     */
    public static function checkApiRate($identifier = null) {
        if (self::$devMode) {
            return ['allowed' => true, 'remaining' => 999];
        }
        
        $key = 'api_rate_' . ($identifier ?? session_id());
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 0, 'time' => time()];
        }
        
        $rate = $_SESSION[$key];
        
        // Reset after 1 minute
        if (time() - $rate['time'] > 60) {
            $_SESSION[$key] = ['count' => 0, 'time' => time()];
            return ['allowed' => true, 'remaining' => 60];
        }
        
        if ($rate['count'] >= 60) {
            return ['allowed' => false, 'remaining' => 0, 'retry_after' => 1];
        }
        
        $_SESSION[$key]['count']++;
        return ['allowed' => true, 'remaining' => 60 - $_SESSION[$key]['count']];
    }
    
    /**
     * Clear all rate limit data (for testing)
     */
    public static function clearAll() {
        foreach ($_SESSION as $key => $value) {
            if (strpos($key, 'login_attempts_') === 0 || 
                strpos($key, 'registration_attempts_') === 0 || 
                strpos($key, 'password_reset_') === 0 || 
                strpos($key, 'api_rate_') === 0) {
                unset($_SESSION[$key]);
            }
        }
    }
}