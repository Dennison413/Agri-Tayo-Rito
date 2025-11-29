// ============================================
// PROFILE EDIT FUNCTIONS
// ============================================

function editPersonalInfo() {
    document.getElementById('personalInfoView').style.display = 'none';
    document.getElementById('personalInfoEdit').style.display = 'block';
}

function cancelEdit() {
    document.getElementById('personalInfoView').style.display = 'grid';
    document.getElementById('personalInfoEdit').style.display = 'none';
}

function toggleEditMode() {
    editPersonalInfo();
}

// ============================================
// AVATAR FUNCTIONS
// ============================================

let selectedAvatarFile = null;
let selectedCustomFile = null;

/**
 * Open avatar selection modal
 */
function openAvatarModal() {
    document.getElementById('avatarModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
    
    // Reset custom file input
    const fileInput = document.getElementById('customAvatarInput');
    if (fileInput) {
        fileInput.value = '';
    }
    selectedCustomFile = null;
    
    // Hide preview
    const preview = document.getElementById('uploadPreview');
    if (preview) {
        preview.style.display = 'none';
    }
}

/**
 * Close avatar selection modal
 */
function closeAvatarModal() {
    document.getElementById('avatarModal').style.display = 'none';
    document.body.style.overflow = 'auto';
    selectedAvatarFile = null;
    selectedCustomFile = null;
    
    // Reset selection to current avatar
    const currentAvatar = document.getElementById('currentAvatar').src;
    document.querySelectorAll('.avatar-option').forEach(img => {
        if (img.src === currentAvatar) {
            img.classList.add('selected');
        } else {
            img.classList.remove('selected');
        }
    });
    
    // Hide preview
    const preview = document.getElementById('uploadPreview');
    if (preview) {
        preview.style.display = 'none';
    }
}

/**
 * Select a preset avatar
 */
function selectAvatar(avatarPath, element) {
    // Remove selected class from all avatars
    document.querySelectorAll('.avatar-option').forEach(img => {
        img.classList.remove('selected');
    });
    
    // Add selected class to clicked avatar
    element.classList.add('selected');
    selectedAvatarFile = avatarPath;
    selectedCustomFile = null; // Clear custom file selection
    
    // Hide upload preview
    const preview = document.getElementById('uploadPreview');
    if (preview) {
        preview.style.display = 'none';
    }
}

/**
 * Trigger file input click
 */
function triggerFileInput() {
    document.getElementById('customAvatarInput').click();
}

/**
 * Handle custom file selection
 */
function handleCustomFileSelect(event) {
    const file = event.target.files[0];
    
    if (!file) {
        return;
    }
    
    // Validate file type
    const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    if (!validTypes.includes(file.type)) {
        showNotification('Please select a valid image file (JPEG, PNG, or GIF)', 'error');
        event.target.value = '';
        return;
    }
    
    // Validate file size (2MB)
    const maxSize = 2 * 1024 * 1024; // 2MB in bytes
    if (file.size > maxSize) {
        showNotification('File is too large. Maximum size is 2MB', 'error');
        event.target.value = '';
        return;
    }
    
    // Store the file
    selectedCustomFile = file;
    selectedAvatarFile = null; // Clear preset selection
    
    // Deselect all preset avatars
    document.querySelectorAll('.avatar-option').forEach(img => {
        img.classList.remove('selected');
    });
    
    // Show preview
    const reader = new FileReader();
    reader.onload = function(e) {
        const preview = document.getElementById('uploadPreview');
        const previewImg = document.getElementById('uploadPreviewImg');
        
        if (preview && previewImg) {
            previewImg.src = e.target.result;
            preview.style.display = 'block';
        }
    };
    reader.readAsDataURL(file);
}

/**
 * Save selected avatar (preset or custom)
 */
function saveAvatar() {
    // Check if either preset or custom avatar is selected
    if (!selectedAvatarFile && !selectedCustomFile) {
        showNotification('Please select an avatar or upload a custom image', 'error');
        return;
    }
    
    const saveBtn = document.getElementById('saveAvatarBtn');
    const originalText = saveBtn.textContent;
    saveBtn.disabled = true;
    saveBtn.textContent = 'Saving...';
    
    if (selectedCustomFile) {
        // Upload custom avatar
        uploadCustomAvatar(selectedCustomFile, saveBtn, originalText);
    } else {
        // Save preset avatar
        savePresetAvatar(selectedAvatarFile, saveBtn, originalText);
    }
}

/**
 * Save preset avatar selection
 */
function savePresetAvatar(avatarPath, saveBtn, originalText) {
    fetch('/agri_system/public/profile/update-avatar.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ avatar: avatarPath })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update the profile avatar image
            const displayPath = data.displayPath || '/agri_system/public' + avatarPath;
            document.getElementById('currentAvatar').src = displayPath;
            
            // Close modal
            closeAvatarModal();
            
            // Show success message
            showNotification(data.message || 'Avatar updated successfully!', 'success');
        } else {
            showNotification(data.message || 'Failed to update avatar', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred while updating avatar', 'error');
    })
    .finally(() => {
        saveBtn.disabled = false;
        saveBtn.textContent = originalText;
    });
}

/**
 * Upload custom avatar
 */
function uploadCustomAvatar(file, saveBtn, originalText) {
    const formData = new FormData();
    formData.append('avatar', file);
    
    fetch('/agri_system/public/profile/upload-avatar.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update the profile avatar image
            const displayPath = data.displayPath || '/agri_system/public' + data.avatar;
            document.getElementById('currentAvatar').src = displayPath;
            
            // Close modal
            closeAvatarModal();
            
            // Show success message
            showNotification(data.message || 'Custom avatar uploaded successfully!', 'success');
        } else {
            showNotification(data.message || 'Failed to upload avatar', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred while uploading avatar', 'error');
    })
    .finally(() => {
        saveBtn.disabled = false;
        saveBtn.textContent = originalText;
    });
}

/**
 * Update editAvatar function to open modal
 */
function editAvatar() {
    openAvatarModal();
}

// ============================================
// NOTIFICATION SYSTEM
// ============================================

/**
 * Show notification message
 */
function showNotification(message, type) {
    // Remove any existing notifications first
    const existingNotifications = document.querySelectorAll('.notification-toast');
    existingNotifications.forEach(notif => notif.remove());
    
    const notification = document.createElement('div');
    notification.className = `notification-toast alert alert-${type}`;
    notification.textContent = message;
    
    // Apply styles
    Object.assign(notification.style, {
        position: 'fixed',
        top: '20px',
        right: '20px',
        zIndex: '10000',
        minWidth: '300px',
        maxWidth: '400px',
        animation: 'slideInRight 0.5s ease',
        boxShadow: '0 4px 20px rgba(0, 0, 0, 0.2)'
    });
    
    document.body.appendChild(notification);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.5s ease';
        setTimeout(() => {
            notification.remove();
        }, 500);
    }, 3000);
}

// ============================================
// PLACEHOLDER FUNCTIONS FOR FUTURE FEATURES
// ============================================

function editCover() {
    showNotification('Cover photo upload feature coming soon!', 'error');
}

function changePassword() {
    showNotification('Password change feature coming soon!', 'error');
}

function enable2FA() {
    showNotification('Two-factor authentication feature coming soon!', 'error');
}

function viewOrderDetails(orderID) {
    window.location.href = `/agri_system/public/marketplace/orders?id=${orderID}`;
}

// ============================================
// EVENT LISTENERS
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert:not(.notification-toast)');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
    
    // Close modal when clicking outside
    document.addEventListener('click', function(event) {
        const modal = document.getElementById('avatarModal');
        if (modal && event.target === modal) {
            closeAvatarModal();
        }
    });
    
    // Close modal with Escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            const modal = document.getElementById('avatarModal');
            if (modal && modal.style.display === 'flex') {
                closeAvatarModal();
            }
        }
    });
});

// ============================================
// ADD ANIMATION STYLES DYNAMICALLY
// ============================================

// Check if styles already exist before adding
if (!document.getElementById('profile-animations')) {
    const style = document.createElement('style');
    style.id = 'profile-animations';
    style.textContent = `
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }
        
        .notification-toast {
            cursor: pointer;
            transition: transform 0.2s ease;
        }
        
        .notification-toast:hover {
            transform: translateX(-5px);
        }
    `;
    document.head.appendChild(style);
}