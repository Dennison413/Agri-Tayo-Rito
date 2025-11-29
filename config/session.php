<?php
// Secure session initialization
// Include this at the top of your index.php or bootstrap file

require_once __DIR__ . '/../app/helpers/SessionSecurity.php';
require_once __DIR__ . '/../app/helpers/csrf.php';
require_once __DIR__ . '/../app/helpers/RateLimiter.php';

// Initialize secure session
SessionSecurity::init();

// Generate CSRF token for the session
CSRF::generateToken();