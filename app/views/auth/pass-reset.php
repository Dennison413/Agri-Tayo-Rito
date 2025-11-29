<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Agri Tayo Rito</title>
    <link rel="stylesheet" href="/agri_system/public/css/auth/forgot-pass.css">
    <style>
        /* Step visibility control */
        .step {
            display: none;
            animation: fadeIn 0.4s ease;
        }
        
        .step.active {
            display: block;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Progress indicator (optional) */
        .progress-dots {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-bottom: 20px;
        }
        
        .progress-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #e0e0e0;
            transition: all 0.3s;
        }
        
        .progress-dot.active {
            background: #2d5016;
            width: 24px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Home Button (visible on all steps) -->
        <button class="home-btn" onclick="window.location.href='/agri_system/public/'">🏠</button>

        <!-- Progress Indicator -->
        <div class="progress-dots">
            <div class="progress-dot active" data-step="1"></div>
            <div class="progress-dot" data-step="2"></div>
            <div class="progress-dot" data-step="3"></div>
        </div>

        <!-- STEP 1: Enter Email -->
        <div class="step active" id="step-email">
            <h1 class="page-title">Forget Password</h1>
            <div class="icon-circle">
                <div class="lock-icon">
                    <div class="lock-body">
                        <span class="lock-dots">••••••••</span>
                    </div>
                    <div class="lock-shackle"></div>
                    <div class="question-badge">?</div>
                </div>
            </div>
            <p class="instructions">
                Please enter your <strong>Email Address</strong> to Receive a Verification Code
            </p>
            <form id="emailForm">
                <div class="input-group">
                    <label for="email">Email Address</label>
                    <div class="input-wrapper">
                        <span class="email-icon">✉</span>
                        <input type="email" id="email" name="email" placeholder="Example@gmail.com" required>
                    </div>
                </div>
                <a href="/agri_system/public/auth/login" class="try-another-link">Try another way</a>
                <button type="submit" class="submit-btn">SEND</button>
            </form>
        </div>

        <!-- STEP 2: Verify Code -->
        <div class="step" id="step-verify">
            <h1 class="page-title">Forget Password</h1>
            <div class="icon-circle">
                <div class="email-icon">
                    <div class="envelope">
                        <div class="envelope-flap"></div>
                        <div class="at-symbol">@</div>
                    </div>
                </div>
            </div>
            <p class="instructions">
                Please Enter The <strong>4 Digit Code</strong> sent to<br>
                <span id="displayEmail">Example@gmail.com</span>
            </p>
            <form id="verifyForm">
                <div class="code-input-group">
                    <input type="text" class="code-input" maxlength="1" pattern="[0-9]" required>
                    <input type="text" class="code-input" maxlength="1" pattern="[0-9]" required>
                    <input type="text" class="code-input" maxlength="1" pattern="[0-9]" required>
                    <input type="text" class="code-input" maxlength="1" pattern="[0-9]" required>
                </div>
                <a href="#" class="resend-link" id="resendCode">Resend Code</a>
                <button type="submit" class="submit-btn">VERIFY</button>
            </form>
        </div>

        <!-- STEP 3: Reset Password -->
        <div class="step" id="step-reset">
            <h1 class="page-title">Forget Password</h1>
            <div class="icon-circle">
                <div class="lock-icon">
                    <div class="lock-body">
                        <span class="lock-dots">••••••••</span>
                    </div>
                    <div class="lock-shackle"></div>
                    <div class="check-badge">✓</div>
                </div>
            </div>
            <p class="instructions">
                Your New Password Must be Different from Previous Used Password
            </p>
            <form id="resetForm">
                <div class="input-group">
                    <label for="new_password">New Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="new_password" name="new_password" placeholder="••••••••••••••••" required>
                        <span class="toggle-password" onclick="togglePassword('new_password')">👁️</span>
                    </div>
                </div>
                <div class="input-group">
                    <label for="confirm_password">Confirm Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="••••••••••••••••" required>
                        <span class="toggle-password" onclick="togglePassword('confirm_password')">👁️</span>
                    </div>
                </div>
                <a href="login.php" class="change-password-link">Change Password</a>
                <button type="submit" class="submit-btn">SEND</button>
            </form>
        </div>
    </div>
    <div id="toast-box"></div>

    <script src="/public/js/pass-reset.js"></script>
</body>
</html>