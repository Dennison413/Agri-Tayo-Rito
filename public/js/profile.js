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
// AVATAR & COVER FUNCTIONS
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
 * Edit avatar - opens modal
 */
function editAvatar() {
    openAvatarModal();
}

/**
 * Edit cover photo
 */
function editCover() {
    document.getElementById('coverInput').click();
}

/**
 * Handle cover photo upload
 */
function handleCoverUpload(event) {
    const file = event.target.files[0];
    
    if (!file) {
        return;
    }
    
    // Validate file type
    const validTypes = ['image/jpeg', 'image/jpg', 'image/png'];
    if (!validTypes.includes(file.type)) {
        showNotification('Please select a valid image file (JPEG or PNG)', 'error');
        event.target.value = '';
        return;
    }
    
    // Validate file size (5MB)
    const maxSize = 5 * 1024 * 1024;
    if (file.size > maxSize) {
        showNotification('File is too large. Maximum size is 5MB', 'error');
        event.target.value = '';
        return;
    }
    
    // Upload the cover photo
    const formData = new FormData();
    formData.append('cover', file);
    
    showNotification('Uploading cover photo...', 'info');
    
    fetch('/agri_system/public/profile/upload-cover.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update the cover image
            document.querySelector('.cover-img').src = data.displayPath;
            showNotification('Cover photo updated successfully!', 'success');
        } else {
            showNotification(data.message || 'Failed to upload cover photo', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred while uploading', 'error');
    })
    .finally(() => {
        event.target.value = '';
    });
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
    selectedCustomFile = null;
    
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
    const maxSize = 2 * 1024 * 1024;
    if (file.size > maxSize) {
        showNotification('File is too large. Maximum size is 2MB', 'error');
        event.target.value = '';
        return;
    }
    
    // Store the file
    selectedCustomFile = file;
    selectedAvatarFile = null;
    
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
 * Save selected avatar
 */
function saveAvatar() {
    if (!selectedAvatarFile && !selectedCustomFile) {
        showNotification('Please select an avatar or upload a custom image', 'error');
        return;
    }
    
    const saveBtn = document.getElementById('saveAvatarBtn');
    const originalText = saveBtn.textContent;
    saveBtn.disabled = true;
    saveBtn.textContent = 'Saving...';
    
    if (selectedCustomFile) {
        uploadCustomAvatar(selectedCustomFile, saveBtn, originalText);
    } else {
        savePresetAvatar(selectedAvatarFile, saveBtn, originalText);
    }
}

/**
 * Save preset avatar
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
            const displayPath = data.displayPath || '/agri_system/public' + avatarPath;
            document.getElementById('currentAvatar').src = displayPath;
            closeAvatarModal();
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
            const displayPath = data.displayPath || '/agri_system/public' + data.avatar;
            document.getElementById('currentAvatar').src = displayPath;
            closeAvatarModal();
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

// ============================================
// ADDRESS MANAGEMENT FUNCTIONS
// ============================================

/**
 * Load user addresses
 */
function loadAddresses() {
    fetch('/agri_system/public/api/address.php?action=list', {
        method: 'GET',
        credentials: 'include'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayAddresses(data.addresses);
        } else {
            console.error('Failed to load addresses:', data.message);
            showNotification('Failed to load addresses', 'error');
        }
    })
    .catch(error => {
        console.error('Error loading addresses:', error);
        showNotification('Error loading addresses', 'error');
    });
}

/**
 * Display addresses in the UI
 */
function displayAddresses(addresses) {
    const container = document.getElementById('addressesContainer');
    
    if (!container) {
        console.error('Address container not found');
        return;
    }
    
    if (addresses.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <p>No addresses saved yet.</p>
                <button class="btn-primary" onclick="openAddAddressModal()">Add Your First Address</button>
            </div>
        `;
        return;
    }
    
    container.innerHTML = addresses.map(address => `
        <div class="address-card">
            <div class="address-content">
                <h3>${escapeHtml(address.address)}</h3>
                <p class="address-text">
                    ${escapeHtml(address.municipality)}, ${escapeHtml(address.province)} ${escapeHtml(address.postal_code)}
                </p>
            </div>
            <div class="address-actions">
                <button onclick="editAddress(${address.addressID})">
                    ✏️ Edit
                </button>
                <button onclick="deleteAddress(${address.addressID})">
                    🗑️ Delete
                </button>
            </div>
        </div>
    `).join('');
}

/**
 * Open add address modal
 */
function openAddAddressModal() {
    document.getElementById('addressModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
    
    // Reset form
    document.getElementById('addressForm').reset();
    document.getElementById('addressModalTitle').textContent = 'Add New Address';
    document.getElementById('addressID').value = '';
}

/**
 * Close address modal
 */
function closeAddressModal() {
    document.getElementById('addressModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

/**
 * Edit address
 */
function editAddress(addressID) {
    fetch(`/agri_system/public/api/address.php?action=get&id=${addressID}`, {
        method: 'GET',
        credentials: 'include'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.address) {
            const addr = data.address;
            
            // Fill form
            document.getElementById('addressID').value = addr.addressID;
            document.getElementById('addressField').value = addr.address;
            document.getElementById('municipalityField').value = addr.municipality;
            document.getElementById('provinceField').value = addr.province;
            document.getElementById('postalCodeField').value = addr.postal_code;
            
            // Change modal title
            document.getElementById('addressModalTitle').textContent = 'Edit Address';
            
            // Open modal
            document.getElementById('addressModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        } else {
            showNotification('Failed to load address details', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error loading address', 'error');
    });
}

/**
 * Save address (add or update)
 */
function saveAddress(event) {
    event.preventDefault();
    
    const addressID = document.getElementById('addressID').value;
    const action = addressID ? 'update' : 'add';
    
    const data = {
        action: action,
        address: document.getElementById('addressField').value.trim(),
        municipality: document.getElementById('municipalityField').value.trim(),
        province: document.getElementById('provinceField').value.trim(),
        postal_code: document.getElementById('postalCodeField').value.trim()
    };
    
    if (addressID) {
        data.addressID = parseInt(addressID);
    }
    
    // Validate
    if (!data.address || !data.municipality || !data.province || !data.postal_code) {
        showNotification('All fields are required', 'error');
        return;
    }
    
    const saveBtn = event.target.querySelector('button[type="submit"]');
    const originalText = saveBtn.textContent;
    saveBtn.disabled = true;
    saveBtn.textContent = 'Saving...';
    
    fetch('/agri_system/public/api/address.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        credentials: 'include',
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showNotification(result.message || 'Address saved successfully', 'success');
            closeAddressModal();
            loadAddresses();
        } else {
            showNotification(result.message || 'Failed to save address', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');
    })
    .finally(() => {
        saveBtn.disabled = false;
        saveBtn.textContent = originalText;
    });
}

/**
 * Delete address
 */
function deleteAddress(addressID) {
    if (!confirm('Are you sure you want to delete this address?')) {
        return;
    }
    
    fetch('/agri_system/public/api/address.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        credentials: 'include',
        body: JSON.stringify({
            action: 'delete',
            addressID: addressID
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Address deleted successfully', 'success');
            loadAddresses();
        } else {
            showNotification(data.message || 'Failed to delete address', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');
    });
}

// ============================================
// SECURITY FUNCTIONS
// ============================================

function changePassword() {
    window.location.href = '/agri_system/public/profile/security';
}

function enable2FA() {
    showNotification('Two-factor authentication feature coming soon!', 'info');
}

// ============================================
// UTILITY FUNCTIONS
// ============================================

/**
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
    if (!text) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

/**
 * Format phone number
 */
function formatPhone(phone) {
    if (!phone) return 'Not provided';
    
    if (phone.length === 11 && phone.startsWith('09')) {
        return phone.substring(0, 4) + '-' + phone.substring(4, 7) + '-' + phone.substring(7);
    }
    
    return phone;
}

/**
 * Show notification
 */
function showNotification(message, type) {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.notification-toast');
    existingNotifications.forEach(notif => notif.remove());
    
    const notification = document.createElement('div');
    notification.className = `notification-toast alert alert-${type}`;
    notification.textContent = message;
    
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
    
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.5s ease';
        setTimeout(() => {
            notification.remove();
        }, 500);
    }, 3000);
}

/**
 * View order details
 */
function viewOrderDetails(orderID) {
    window.location.href = `/agri_system/public/marketplace/myorders`;
}

// ============================================
// EVENT LISTENERS
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    
    // Load addresses on page load
    const addressContainer = document.getElementById('addressesContainer');
    if (addressContainer) {
        loadAddresses();
    }
    
    // Auto-hide alerts
    const alerts = document.querySelectorAll('.alert:not(.notification-toast)');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
    
    // Close modals when clicking outside
    document.addEventListener('click', function(event) {
        const avatarModal = document.getElementById('avatarModal');
        if (avatarModal && event.target === avatarModal) {
            closeAvatarModal();
        }
        
        const addressModal = document.getElementById('addressModal');
        if (addressModal && event.target === addressModal) {
            closeAddressModal();
        }
    });
    
    // Close modals with Escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            const avatarModal = document.getElementById('avatarModal');
            if (avatarModal && avatarModal.style.display === 'flex') {
                closeAvatarModal();
            }
            
            const addressModal = document.getElementById('addressModal');
            if (addressModal && addressModal.style.display === 'flex') {
                closeAddressModal();
            }
        }
    });
});

// Add animation styles
if (!document.getElementById('profile-animations')) {
    const style = document.createElement('style');
    style.id = 'profile-animations';
    style.textContent = `
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @keyframes slideOutRight {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
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