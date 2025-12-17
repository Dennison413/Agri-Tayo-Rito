// public/js/pass-reset.js
let currentStep = 1;
let userEmail = '';

function showToast(message, type = 'success') {
    const toast = document.getElementById('toast-box');
    toast.className = '';
    toast.classList.add(type);
    toast.textContent = message;
    toast.style.opacity = '1';
    
    if (type !== 'loading') {
        setTimeout(() => {
            toast.style.opacity = '0';
        }, 3000);
    }
}

function togglePassword(fieldId) {
    const input = document.getElementById(fieldId);
    const icon = input.nextElementSibling;
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.textContent = '🙈';
    } else {
        input.type = 'password';
        icon.textContent = '👁️';
    }
}

function updateProgress(step) {
    document.querySelectorAll('.progress-dot').forEach((dot, index) => {
        if (index < step) {
            dot.classList.add('active');
        } else {
            dot.classList.remove('active');
        }
    });
}

function showStep(step) {
    document.querySelectorAll('.step').forEach(s => s.classList.remove('active'));
    
    switch(step) {
        case 1:
            document.getElementById('step-email').classList.add('active');
            break;
        case 2:
            document.getElementById('step-verify').classList.add('active');
            break;
        case 3:
            document.getElementById('step-reset').classList.add('active');
            break;
    }
    
    currentStep = step;
    updateProgress(step);
}

// send OTP
document.getElementById('emailForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const email = document.getElementById('email').value.trim();
    
    if (!email) {
        showToast('Enter your email', 'error');
        return;
    }
    
    const submitBtn = e.target.querySelector('.submit-btn');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Sending...';
    submitBtn.disabled = true;
    
    showToast('Sending code...', 'loading');
    
    try {
        const formData = new FormData();
        formData.append('action', 'send_otp');
        formData.append('email', email);
        
        const response = await fetch('/agri_system/public/auth/pass-reset', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            userEmail = email;
            document.getElementById('displayEmail').textContent = email;
            showToast('Code sent! Check email', 'success');
            setTimeout(() => showStep(2), 1500);
        } else {
            showToast(result.message || 'Failed to send', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Network error', 'error');
    } finally {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    }
});

// OTP input handling
const codeInputs = document.querySelectorAll('.code-input');

codeInputs.forEach((input, index) => {
    input.addEventListener('input', (e) => {
        if (e.target.value.length === 1 && index < codeInputs.length - 1) {
            codeInputs[index + 1].focus();
        }
    });
    
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Backspace' && !e.target.value && index > 0) {
            codeInputs[index - 1].focus();
        }
    });
    
    input.addEventListener('input', (e) => {
        e.target.value = e.target.value.replace(/[^0-9]/g, '');
    });

    input.addEventListener('paste', (e) => {
        e.preventDefault();
        const pastedData = e.clipboardData.getData('text').replace(/[^0-9]/g, '');
        
        if (pastedData.length === 4) {
            codeInputs.forEach((inp, idx) => {
                inp.value = pastedData[idx] || '';
            });
            codeInputs[3].focus();
        }
    });
});

// verify OTP
document.getElementById('verifyForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const otp = Array.from(codeInputs).map(input => input.value).join('');
    
    console.log('🔍 OTP Debug:');
    console.log('- OTP entered:', otp);
    console.log('- OTP length:', otp.length);
    console.log('- Individual values:', Array.from(codeInputs).map(input => input.value));
    
    if (otp.length !== 4) {
        showToast('Enter 4-digit code', 'error');
        return;
    }
    
    // validate that all characters are digits
    if (!/^\d{4}$/.test(otp)) {
        showToast('Numbers only', 'error');
        return;
    }
    
    const submitBtn = e.target.querySelector('.submit-btn');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Verifying...';
    submitBtn.disabled = true;
    
    showToast('Verifying...', 'loading');
    
    try {
        const formData = new FormData();
        formData.append('action', 'verify_otp');
        formData.append('otp', otp);
        
        console.log('📤 Sending FormData:');
        for (let pair of formData.entries()) {
            console.log('- ' + pair[0] + ': ' + pair[1]);
        }
        
        const response = await fetch('/agri_system/public/auth/pass-reset', {
            method: 'POST',
            body: formData
        });
        
        console.log('📥 Response status:', response.status);
        
        const responseText = await response.text();
        console.log('📥 Raw response:', responseText);
        
        let result;
        try {
            result = JSON.parse(responseText);
            console.log('📥 Parsed result:', result);
        } catch (parseError) {
            console.error('❌ JSON parse error:', parseError);
            console.error('Response was:', responseText);
            showToast('Server error', 'error');
            return;
        }
        
        if (result.success) {
            showToast('Verified! ✓', 'success');
            setTimeout(() => showStep(3), 1000);
        } else {
            showToast(result.message || 'Invalid code', 'error');
            codeInputs.forEach(input => input.value = '');
            codeInputs[0].focus();
        }
    } catch (error) {
        console.error('❌ Verification error:', error);
        showToast('Network error', 'error');
    } finally {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    }
});

// resend OTP
document.getElementById('resendCode').addEventListener('click', async (e) => {
    e.preventDefault();
    
    const link = e.target;
    const originalText = link.textContent;
    link.textContent = 'Sending...';
    link.style.pointerEvents = 'none';
    
    showToast('Resending...', 'loading');
    
    try {
        const formData = new FormData();
        formData.append('action', 'resend_otp');
        
        const response = await fetch('/agri_system/public/auth/pass-reset', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast('New code sent!', 'success');
            codeInputs.forEach(input => input.value = '');
            codeInputs[0].focus();
        } else {
            showToast(result.message || 'Resend failed', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Network error', 'error');
    } finally {
        link.textContent = originalText;
        link.style.pointerEvents = 'auto';
    }
});

// start over - go back to Step 1
document.getElementById('startOver').addEventListener('click', (e) => {
    e.preventDefault();
    document.getElementById('email').value = '';
    codeInputs.forEach(input => input.value = '');
    document.getElementById('new_password').value = '';
    document.getElementById('confirm_password').value = '';
    showStep(1);
    showToast('Starting over...', 'loading');
});

// reset password
document.getElementById('resetForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    if (newPassword.length < 8) {
        showToast('Password needs 8+ chars', 'error');
        return;
    }
    
    if (newPassword !== confirmPassword) {
        showToast('Passwords don\'t match', 'error');
        return;
    }
    
    const submitBtn = e.target.querySelector('.submit-btn');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Resetting...';
    submitBtn.disabled = true;
    
    showToast('Resetting password...', 'loading');
    
    try {
        const formData = new FormData();
        formData.append('action', 'reset_password');
        formData.append('new_password', newPassword);
        formData.append('confirm_password', confirmPassword);
        
        const response = await fetch('/agri_system/public/auth/pass-reset', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast('Success! Redirecting...', 'success');
            setTimeout(() => {
                window.location.href = '/agri_system/public/auth/login';
            }, 2000);
        } else {
            showToast(result.message || 'Reset failed', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Network error', 'error');
    } finally {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    }
});

// password strength indicator
document.getElementById('new_password').addEventListener('input', function() {
    const password = this.value;
    
    if (password.length >= 8) {
        this.style.borderColor = '#51cf66';
    } else if (password.length > 0) {
        this.style.borderColor = '#ffa94d';
    } else {
        this.style.borderColor = '';
    }
});

// password match indicator
document.getElementById('confirm_password').addEventListener('input', function() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = this.value;
    
    if (confirmPassword && newPassword) {
        if (newPassword === confirmPassword) {
            this.style.borderColor = '#51cf66';
        } else {
            this.style.borderColor = '#ff6b6b';
        }
    } else {
        this.style.borderColor = '';
    }
});

document.addEventListener('DOMContentLoaded', () => {
    showStep(1);
    document.getElementById('email').focus();
});