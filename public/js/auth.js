// public/js/auth.js
function togglePassword(fieldId) {
    const passwordInput = document.getElementById(fieldId);
    
    if (!passwordInput) {
        console.error('Password input not found:', fieldId);
        return;
    }
    
    const passwordWrapper = passwordInput.parentElement;
    if (!passwordWrapper) {
        console.error('Password wrapper not found for:', fieldId);
        return;
    }

    // toggle password visibility icon
    const toggleIcon = passwordWrapper.querySelector('.toggle-password');
    if (!toggleIcon) {
        console.error('Toggle icon not found');
        return;
    }
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.textContent = '🙈';
    } else {
        passwordInput.type = 'password';
        toggleIcon.textContent = '👁️';
    }
}

function showError(message) {
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

// helper function to hide error messages
function hideError() {
    const errorMessage = document.getElementById('errorMessage');
    if (errorMessage) {
        errorMessage.style.display = 'none';
    }
}

// email validation helper
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// login form validation
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            
            if (email.length === 0 || password.length === 0) {
                e.preventDefault();
                showError('Please fill in all fields');
                return false;
            }
            
            if (!isValidEmail(email)) {
                e.preventDefault();
                showError('Please enter a valid email address');
                return false;
            }
            
            if (password.length < 8) {
                e.preventDefault();
                showError('Password must be at least 8 characters');
                return false;
            }
            return true;
        });

        const emailInput = document.getElementById('email');
        if (emailInput) {
            emailInput.focus();
        }
    }
    
    // registration form validation
    const signupForm = document.getElementById('signupForm');
    
    if (signupForm) {
        signupForm.addEventListener('submit', function(e) {
            const fullName = document.getElementById('full_name')?.value.trim() || '';
            const email = document.getElementById('email')?.value.trim() || '';
            const password = document.getElementById('password')?.value || '';
            const confirmPassword = document.getElementById('confirm_password')?.value || '';
            
            if (fullName.length === 0 || email.length === 0 || password.length === 0 || confirmPassword.length === 0) {
                e.preventDefault();
                showError('Please fill in all fields');
                return false;
            }
            
            if (!isValidEmail(email)) {
                e.preventDefault();
                showError('Please enter a valid email address');
                return false;
            }
            
            if (password.length < 8) {
                e.preventDefault();
                showError('Password must be at least 8 characters long');
                return false;
            }
            
            if (password !== confirmPassword) {
                e.preventDefault();
                showError('Passwords do not match');
                return false;
            }
            
            return true;
        });

        // real-time password match indicator 
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

        const fullNameInput = document.getElementById('full_name');
        if (fullNameInput) {
            fullNameInput.focus();
        }
    }

    // hide error message when user starts typing in any input
    document.querySelectorAll('input').forEach(input => {
        input.addEventListener('input', function() {
            hideError();
        });
    });

    // prevent form resubmission on page refresh
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
});