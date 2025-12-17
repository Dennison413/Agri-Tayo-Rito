<?php
// app/views/auth/login.php
require_once BASE_PATH . '/app/controllers/AuthController.php';

$authController = new AuthController();

// handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $result = $authController->login($email, $password);
    
    if ($result['success']) {
        // role-based redirect
        $role = $result['role'];
        
        switch ($role) {
            case 'admin':
                header('Location: /agri_system/public/profile/admin/dashboard');
                break;
            case 'seller':
                header('Location: /agri_system/public/profile/seller/dashboard');
                break;
            case 'buyer':
            default:
                header('Location: /agri_system/public/marketplace');
                break;
        }
        exit;
    } else {
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/jpg" href="/agri_system/public/images/agri-icon.jpg">
    <title>Log In - Agri Tayo Rito</title>
    <link rel="stylesheet" href="/agri_system/public/css/auth/auth.css">
</head>
<body>
    <div class="form-container">
        <div class="logo-section">
            <h1>🌾 Agri Tayo Rito</h1>
            <p>Fresh from Farm to Table</p>
        </div>

        <h2>Welcome Back</h2>

        <?php if (isset($error)): ?>
        <div class="error-message" id="errorMessage" style="display: block;">
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <form action="/agri_system/public/auth/login" method="POST" id="loginForm">
            <div class="input-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="Example@gmail.com" required>
            </div>

            <div class="input-group">
                <label for="password">Password</label>
                <div class="password-wrapper">
                    <input type="password" id="password" name="password" placeholder="••••••••••" required>
                    <span class="toggle-password" onclick="togglePassword('password')">👁️</span>
                </div>
            </div>

            <div class="forgot-password">
                <a href="/agri_system/public/auth/pass-reset">Forgot Password?</a>
            </div>

            <button type="submit" class="btn-primary">LOG IN</button>
        </form>

        <button class="btn-home" onclick="window.location.href='/agri_system/public/'">HOME</button>

        <div class="signup-link">
            Don't have an account? <a href="/agri_system/public/auth/register">Sign Up</a>
        </div>
    </div>
    <script src="/agri_system/public/js/auth.js"></script>
</body>
</html>