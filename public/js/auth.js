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
    // Create error message if it doesn't exist
    let errorMessage = document.getElementById('errorMessage');
    if (!errorMessage) {
        errorMessage = document.createElement('div');
        errorMessage.id = 'errorMessage';
        errorMessage.className = 'error-message';
        errorMessage.style.cssText = 'display: block; background: #f8d7da; color: #721c24; padding: 12px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #f5c6cb;';
        
        const form = document.querySelector('form');
        if (form) {
            form.parentNode.insertBefore(errorMessage, form);
        }
    }
    errorMessage.textContent = message;
    errorMessage.style.display = 'block';
}

// Helper function to hide error messages
function hideError() {
    const errorMessage = document.getElementById('errorMessage');
    if (errorMessage) {
        errorMessage.style.display = 'none';
    }
}

// Email validation helper
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// ========================================
// LOGIN FORM VALIDATION
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
                showError('Password must be at least 8 characters');
                return false;
            }
            
            // If all validations pass, allow form submission
            return true;
        });

        // Auto-focus on email field for better UX
        const emailInput = document.getElementById('email');
        if (emailInput) {
            emailInput.focus();
        }
    }
    
    // ========================================
    // REGISTRATION FORM VALIDATION
    // ========================================
    const signupForm = document.getElementById('signupForm');
    
    if (signupForm) {
        signupForm.addEventListener('submit', function(e) {
            const fullName = document.getElementById('full_name')?.value.trim() || '';
            const email = document.getElementById('email')?.value.trim() || '';
            const password = document.getElementById('password')?.value || '';
            const confirmPassword = document.getElementById('confirm_password')?.value || '';
            
            // Check for empty fields
            if (fullName.length === 0 || email.length === 0 || password.length === 0 || confirmPassword.length === 0) {
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
                showError('Password must be at least 8 characters long');
                return false;
            }
            
            // Password match validation
            if (password !== confirmPassword) {
                e.preventDefault();
                showError('Passwords do not match');
                return false;
            }
            
            // All validations passed - allow form to submit
            return true;
        });

        // Real-time password match indicator - KEEP THIS
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

        // Auto-focus on full name field for better UX
        const fullNameInput = document.getElementById('full_name');
        if (fullNameInput) {
            fullNameInput.focus();
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