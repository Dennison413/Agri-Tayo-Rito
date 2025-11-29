<?php

require_once BASE_PATH . '/app/controllers/AuthController.php';

$authController = new AuthController();
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $fullName = trim($_POST['full_name'] ?? '');
    
    // Validate passwords match
    if ($password !== $confirmPassword) {
        $error = 'Passwords do not match';
    } else {
        $data = [
            'email' => $email,
            'password' => $password,
            'confirm_password' => $confirmPassword,
            'full_name' => $fullName
        ];
        
        $result = $authController->register($data);
        
        if ($result['success']) {
            $success = $result['message'];
            // Redirect to login after 2 seconds
            header("refresh:2;url=/agri_system/public/auth/login");
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Agri Tayo Rito</title>
    <link rel="stylesheet" href="/agri_system/public/css/auth/auth.css">
</head>
<body>
    <div class="form-container">
        <div class="logo-section">
            <h1>🌾 Agri Tayo Rito</h1>
            <p>Fresh from Farm to Table</p>
        </div>

        <h2>Sign Up</h2>

        <?php if ($error): ?>
        <div class="error-message" style="display: block;">
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="success-message" style="display: block; background: #d4edda; color: #155724; padding: 12px; border-radius: 8px; margin-bottom: 20px;">
            <?php echo htmlspecialchars($success); ?>
            <br><small>Redirecting to login...</small>
        </div>
        <?php endif; ?>

        <form action="/agri_system/public/auth/register" method="POST" id="signupForm">
            <div class="input-group">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" placeholder="Juan Dela Cruz" required 
                       value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
            </div>

            <div class="input-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="Example@gmail.com" required
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>

            <div class="input-group">
                <label for="password">Password</label>
                <div class="password-wrapper">
                    <input type="password" id="password" name="password" placeholder="••••••••••" required minlength="8">
                    <span class="toggle-password" onclick="togglePassword('password')">👁️</span>
                </div>
                <small style="color: #666; font-size: 12px;">Minimum 8 characters</small>
            </div>

            <div class="input-group">
                <label for="confirm_password">Confirm Password</label>
                <div class="password-wrapper">
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="••••••••••" required>
                    <span class="toggle-password" onclick="togglePassword('confirm_password')">👁️</span>
                </div>
            </div>

            <button type="submit" class="btn-primary">SIGN UP</button>
        </form>

        <button class="btn-home" onclick="window.location.href='/agri_system/public/'">HOME</button>

        <div class="signup-link">
            Already have an account? <a href="/agri_system/public/auth/login">Log In</a>
        </div>
    </div>

    <script src="/agri_system/public/js/auth.js"></script>
</body>
</html>