// Password Reset Multi-Step Form Handling
const appState = {
    currentStep: 1,
    email: '',
    verificationCode: '',
    resetToken: ''
};

// ==========================================
// NAVIGATION FUNCTIONS
// ==========================================

/**
 * Navigate to a specific step
 * @param {number} stepNumber - Step number (1, 2, or 3)
 */
function goToStep(stepNumber) {
    // Hide all steps
    document.querySelectorAll('.step').forEach(step => {
        step.classList.remove('active');
    });
    
    // Show target step
    const targetStep = document.getElementById(`step-${getStepName(stepNumber)}`);
    if (targetStep) {
        targetStep.classList.add('active');
        appState.currentStep = stepNumber;
        
        // Update progress dots
        updateProgressDots(stepNumber);
        
        // Focus first input
        const firstInput = targetStep.querySelector('input');
        if (firstInput) {
            setTimeout(() => firstInput.focus(), 300);
        }
    }
}

/**
 * Get step name from number
 * @param {number} stepNumber - Step number
 * @returns {string} - Step name
 */
function getStepName(stepNumber) {
    const steps = {
        1: 'email',
        2: 'verify',
        3: 'reset'
    };
    return steps[stepNumber] || 'email';
}

/**
 * Update progress indicator dots
 * @param {number} activeStep - Current active step
 */
function updateProgressDots(activeStep) {
    document.querySelectorAll('.progress-dot').forEach((dot, index) => {
        if (index + 1 === activeStep) {
            dot.classList.add('active');
        } else {
            dot.classList.remove('active');
        }
    });
}

// ==========================================
// UTILITY FUNCTIONS
// ==========================================

/**
 * Show toast notification
 * @param {string} message - Message to display
 * @param {string} type - Type: 'success', 'error', 'loading'
 */
function showToast(message, type = 'success') {
    const toast = document.getElementById('toast-box');
    toast.textContent = message;
    toast.className = type;
    
    // Auto-hide after 3 seconds
    setTimeout(() => {
        toast.classList.remove(type);
    }, 3000);
}

/**
 * Validate email format
 * @param {string} email - Email to validate
 * @returns {boolean} - True if valid
 */
function validateEmail(email) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
}

/**
 * Toggle password visibility
 * @param {string} inputId - Password input ID
 */
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const icon = input.parentElement.querySelector('.toggle-password');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.textContent = '👁️‍🗨️';
    } else {
        input.type = 'password';
        icon.textContent = '👁️';
    }
}

/**
 * Validate password strength
 * @param {string} password - Password to validate
 * @returns {object} - Validation result
 */
function validatePassword(password) {
    if (password.length < 8) {
        return {
            isValid: false,
            message: 'Password must be at least 8 characters long'
        };
    }
    if (!/[A-Z]/.test(password)) {
        return {
            isValid: false,
            message: 'Password must contain at least one uppercase letter'
        };
    }
    if (!/[a-z]/.test(password)) {
        return {
            isValid: false,
            message: 'Password must contain at least one lowercase letter'
        };
    }
    if (!/[0-9]/.test(password)) {
        return {
            isValid: false,
            message: 'Password must contain at least one number'
        };
    }
    return { isValid: true, message: 'Strong password' };
}

// ==========================================
// STEP 1: EMAIL SUBMISSION
// ==========================================

document.getElementById('emailForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const emailInput = document.getElementById('email');
    const email = emailInput.value.trim();
    
    // Validate email
    if (!validateEmail(email)) {
        showToast('Please enter a valid email address', 'error');
        return;
    }
    
    const submitBtn = e.target.querySelector('.submit-btn');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'SENDING...';
    submitBtn.disabled = true;
    
    try {
        // Send request to backend
        const response = await fetch('../backend/forgot-password.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email: email })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Save email to state
            appState.email = email;
            
            // Update display email in step 2
            document.getElementById('displayEmail').textContent = email;
            
            showToast('Verification code sent to your email!', 'success');
            
            // Move to verification step
            setTimeout(() => {
                goToStep(2);
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            }, 1500);
        } else {
            showToast(data.message || 'Email not found', 'error');
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Something went wrong. Please try again.', 'error');
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    }
});

// ==========================================
// STEP 2: CODE VERIFICATION
// ==========================================

const codeInputs = document.querySelectorAll('.code-input');

// Auto-focus and navigation between code inputs
codeInputs.forEach((input, index) => {
    // Move to next on input
    input.addEventListener('input', (e) => {
        const value = e.target.value;
        
        // Only allow digits
        if (!/^\d$/.test(value)) {
            e.target.value = '';
            return;
        }
        
        // Move to next input
        if (value && index < codeInputs.length - 1) {
            codeInputs[index + 1].focus();
        }
    });
    
    // Move to previous on backspace
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Backspace' && !e.target.value && index > 0) {
            codeInputs[index - 1].focus();
        }
    });
    
    // Handle paste
    input.addEventListener('paste', (e) => {
        e.preventDefault();
        const pastedData = e.clipboardData.getData('text').trim();
        
        if (/^\d{4}$/.test(pastedData)) {
            pastedData.split('').forEach((digit, i) => {
                if (codeInputs[i]) {
                    codeInputs[i].value = digit;
                }
            });
            codeInputs[3].focus();
        }
    });
});

// Verify code form submission
document.getElementById('verifyForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const code = Array.from(codeInputs).map(input => input.value).join('');
    
    if (code.length !== 4) {
        showToast('Please enter the complete 4-digit code', 'error');
        return;
    }
    
    const submitBtn = e.target.querySelector('.submit-btn');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'VERIFYING...';
    submitBtn.disabled = true;
    
    try {
        const response = await fetch('../backend/verify-code.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                email: appState.email,
                code: code
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Save token
            appState.resetToken = data.token;
            appState.verificationCode = code;
            
            showToast('Code verified successfully!', 'success');
            
            // Move to reset password step
            setTimeout(() => {
                goToStep(3);
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            }, 1500);
        } else {
            showToast(data.message || 'Invalid or expired code', 'error');
            
            // Clear inputs
            codeInputs.forEach(input => input.value = '');
            codeInputs[0].focus();
            
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Something went wrong. Please try again.', 'error');
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    }
});

// Resend code functionality
document.getElementById('resendCode').addEventListener('click', async (e) => {
    e.preventDefault();
    
    const resendBtn = e.target;
    let countdown = 60;
    
    // Check if already in countdown
    if (resendBtn.dataset.countdown === 'active') {
        return;
    }
    
    try {
        showToast('Resending code...', 'loading');
        
        const response = await fetch('../backend/forgot-password.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email: appState.email })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('New code sent to your email!', 'success');
            
            // Start countdown
            resendBtn.dataset.countdown = 'active';
            resendBtn.style.pointerEvents = 'none';
            resendBtn.style.opacity = '0.5';
            const originalText = resendBtn.textContent;
            
            const interval = setInterval(() => {
                countdown--;
                resendBtn.textContent = `Resend Code (${countdown}s)`;
                
                if (countdown <= 0) {
                    clearInterval(interval);
                    resendBtn.textContent = originalText;
                    resendBtn.style.pointerEvents = 'auto';
                    resendBtn.style.opacity = '1';
                    delete resendBtn.dataset.countdown;
                }
            }, 1000);
        } else {
            showToast(data.message || 'Failed to resend code', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Something went wrong. Please try again.', 'error');
    }
});

// ==========================================
// STEP 3: RESET PASSWORD
// ==========================================

const confirmPasswordInput = document.getElementById('confirm_password');

// Real-time password match indicator
confirmPasswordInput.addEventListener('input', () => {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = confirmPasswordInput.value;
    
    if (confirmPassword.length > 0) {
        if (newPassword === confirmPassword) {
            confirmPasswordInput.style.borderColor = '#4caf50';
        } else {
            confirmPasswordInput.style.borderColor = '#c62828';
        }
    } else {
        confirmPasswordInput.style.borderColor = '#e0e0e0';
    }
});

// Reset password form submission
document.getElementById('resetForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    // Validate password strength
    const validation = validatePassword(newPassword);
    if (!validation.isValid) {
        showToast(validation.message, 'error');
        return;
    }
    
    // Check password match
    if (newPassword !== confirmPassword) {
        showToast('Passwords do not match', 'error');
        return;
    }
    
    const submitBtn = e.target.querySelector('.submit-btn');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'UPDATING...';
    submitBtn.disabled = true;
    
    try {
        const response = await fetch('../backend/reset-password.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                email: appState.email,
                token: appState.resetToken,
                new_password: newPassword
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('Password reset successfully!', 'success');
            
            // Clear state
            appState.email = '';
            appState.verificationCode = '';
            appState.resetToken = '';
            
            // Redirect to login
            setTimeout(() => {
                window.location.href = 'login.php';
            }, 2000);
        } else {
            showToast(data.message || 'Failed to reset password', 'error');
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Something went wrong. Please try again.', 'error');
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    }
});

// ==========================================
// INITIALIZE
// ==========================================
document.addEventListener('DOMContentLoaded', () => {
    // Start at step 1
    goToStep(1);
    
    // Prevent going back to reload the page
    window.history.pushState(null, null, window.location.href);
    window.onpopstate = () => {
        window.history.pushState(null, null, window.location.href);
    };
});