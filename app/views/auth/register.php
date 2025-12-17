<?php
// app/views/auth/register.php
require_once BASE_PATH . '/app/controllers/AuthController.php';
require_once BASE_PATH . '/app/helpers/csrf.php';
require_once BASE_PATH . '/app/helpers/RateLimiter.php';

$authController = new AuthController();
$error = '';
$success = '';

// handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rateLimitCheck = RateLimiter::checkRegistrationAttempts();
    if (!$rateLimitCheck['allowed']) {
        $error = "Too many registration attempts. Please try again in {$rateLimitCheck['retry_after']} minutes.";
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $fullName = trim($_POST['full_name'] ?? '');

        if (empty($fullName) || empty($email) || empty($password) || empty($confirmPassword)) {
            $error = 'All fields are required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address';
        } elseif ($password !== $confirmPassword) {
            $error = 'Passwords do not match';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters long';
        } else {
            // prepare data for registration
            $data = [
                'email' => $email,
                'password' => $password,
                'confirm_password' => $confirmPassword,
                'full_name' => $fullName
            ];

            // attempt registration
            $result = $authController->register($data);

            if ($result['success']) {
                // registration successful - now auto-login
                $loginResult = $authController->login($email, $password);

                if ($loginResult['success']) {
                    header('Location: /agri_system/public/marketplace');
                    exit;
                } else {
                    $_SESSION['registration_success'] = true;
                    $_SESSION['success_message'] = 'Registration successful! Please log in.';
                    header('Location: /agri_system/public/auth/login');
                    exit;
                }
            } else {
                $error = $result['message'];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/jpg" href="/agri_system/public/images/agri-icon.jpg">
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
            <div class="error-message" style="display: block; background: #f8d7da; color: #721c24; padding: 12px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #f5c6cb;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success-message" style="display: block; background: #d4edda; color: #155724; padding: 12px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form action="/agri_system/public/auth/register" method="POST" id="signupForm">
            <?php echo CSRF::getTokenField(); ?>

            <div class="input-group">
                <label for="full_name">Full Name *</label>
                <input type="text"
                    id="full_name"
                    name="full_name"
                    placeholder="Juan Dela Cruz"
                    required
                    value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
            </div>

            <div class="input-group">
                <label for="email">Email *</label>
                <input type="email"
                    id="email"
                    name="email"
                    placeholder="example@gmail.com"
                    required
                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>

            <div class="input-group">
                <label for="password">Password *</label>
                <div class="password-wrapper">
                    <input type="password"
                        id="password"
                        name="password"
                        placeholder="••••••••••"
                        required
                        minlength="8"
                        autocomplete="new-password"
                        data-form-type="password">
                    <span class="toggle-password" onclick="togglePassword('password')">👁️</span>
                </div>
                <small style="color: #666; font-size: 12px;">Minimum 8 characters</small>
            </div>

            <div class="input-group">
                <label for="confirm_password">Confirm Password *</label>
                <div class="password-wrapper">
                    <input type="password"
                        id="confirm_password"
                        name="confirm_password"
                        placeholder="••••••••••"
                        required
                        autocomplete="new-password"
                        data-form-type="password">
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