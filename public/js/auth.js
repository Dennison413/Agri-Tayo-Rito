// Login and Registration Form Validation and Interactivity
function togglePassword(fieldId) {
    const passwordInput = document.getElementById(fieldId);
    
    if (!passwordInput) {
        console.error('Password input not found:', fieldId);
        return;
    }
    
    // Find the parent wrapper
    const passwordWrapper = passwordInput.parentElement;
    if (!passwordWrapper) {
        console.error('Password wrapper not found for:', fieldId);
        return;
    }
    
    // Find the toggle icon within the wrapper
    const toggleIcon = passwordWrapper.querySelector('.toggle-password');
    if (!toggleIcon) {
        console.error('Toggle icon not found');
        return;
    }
    
    // Toggle the type
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.textContent = '🙈';
    } else {
        passwordInput.type = 'password';
        toggleIcon.textContent = '👁️';
    }
}

// Helper function to show error messages
function showError(message) {
    const errorMessage = document.getElementById('errorMessage');
    if (errorMessage) {
        errorMessage.textContent = message;
        errorMessage.classList.add('show');
    }
}

// Helper function to hide error messages
function hideError() {
    const errorMessage = document.getElementById('errorMessage');
    if (errorMessage) {
        errorMessage.classList.remove('show');
    }
}

// Email validation helper
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Password strength checker
// Returns true if password contains at least one letter and one number
function isPasswordStrong(password) {
    const hasLetter = /[a-zA-Z]/.test(password);
    const hasNumber = /[0-9]/.test(password);
    return hasLetter && hasNumber;
}

// Google OAuth login
// Directs to: Google OAuth authentication flow
// TODO: Replace with actual backend OAuth endpoint
function loginWithGoogle() {
    alert('Google OAuth integration needed. This would redirect to Google login.');
    // In production, uncomment and configure:
    // window.location.href = '/auth/google-oauth.php';
}

// ========================================
// LOGIN FORM VALIDATION
// Directs to: Backend login processing (login.php POST handler)
// ========================================
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            
            // Check for empty fields
            if (email.length === 0 || password.length === 0) {
                e.preventDefault();
                showError('Please fill in all fields');
                return false;
            }
            
            // Email format validation
            if (!isValidEmail(email)) {
                e.preventDefault();
                showError('Please enter a valid email address');
                return false;
            }
            
            // Password length validation
            if (password.length < 8) {
                e.preventDefault();
                showError('Password must be at least 6 characters');
                return false;
            }
        });

        // Auto-focus on email field for better UX
        const emailInput = document.getElementById('email');
        if (emailInput) {
            emailInput.focus();
        }
    }
    // REGISTRATION FORM VALIDATION
    // Directs to: Backend registration processing (register.php POST handler)
    const signupForm = document.getElementById('signupForm');
    
    if (signupForm) {
        signupForm.addEventListener('submit', function(e) {
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            // Check for empty fields
            if (email.length === 0 || password.length === 0 || confirmPassword.length === 0) {
                e.preventDefault();
                showError('Please fill in all fields');
                return false;
            }
            
            // Email format validation
            if (!isValidEmail(email)) {
                e.preventDefault();
                showError('Please enter a valid email address');
                return false;
            }
            
            // Password length validation
            if (password.length < 6) {
                e.preventDefault();
                showError('Password must be at least 6 characters');
                return false;
            }
            
            // Password strength validation
            if (!isPasswordStrong(password)) {
                e.preventDefault();
                showError('Password must contain at least one letter and one number');
                return false;
            }
            
            // Password match validation
            if (password !== confirmPassword) {
                e.preventDefault();
                showError('Passwords do not match');
                return false;
            }
        });

        // Real-time password match indicator
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        
        if (confirmPasswordInput && passwordInput) {
            confirmPasswordInput.addEventListener('input', function() {
                if (passwordInput.value && confirmPasswordInput.value) {
                    if (passwordInput.value !== confirmPasswordInput.value) {
                        confirmPasswordInput.style.borderColor = '#ff6b6b';
                    } else {
                        confirmPasswordInput.style.borderColor = '#51cf66';
                    }
                }
            });

            // Reset border color when password field changes
            passwordInput.addEventListener('input', function() {
                if (confirmPasswordInput.value) {
                    if (passwordInput.value !== confirmPasswordInput.value) {
                        confirmPasswordInput.style.borderColor = '#ff6b6b';
                    } else {
                        confirmPasswordInput.style.borderColor = '#51cf66';
                    }
                }
            });
        }

        // Auto-focus on email field for better UX
        const emailInput = document.getElementById('email');
        if (emailInput) {
            emailInput.focus();
        }
    }

    // SHARED EVENT LISTENERS

    // Hide error message when user starts typing in any input
    document.querySelectorAll('input').forEach(input => {
        input.addEventListener('input', function() {
            hideError();
        });
    });

    // Prevent form resubmission on page refresh
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
});