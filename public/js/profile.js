// public/js/profile.js
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

// avatar management variables

let selectedAvatarFile = null;
let selectedCustomFile = null;

// edit avatar
function editAvatar() {
    console.log('🎨 Opening avatar modal');
    openAvatarModal();
}

// open avatar selection modal
function openAvatarModal() {
    const modal = document.getElementById('avatarModal');
    if (!modal) {
        console.error('❌ Avatar modal not found');
        return;
    }
    
    console.log('✅ Avatar modal found, opening...');
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    const fileInput = document.getElementById('customAvatarInput');
    if (fileInput) {
        fileInput.value = '';
    }
    selectedCustomFile = null;
    const preview = document.getElementById('uploadPreview');
    if (preview) {
        preview.style.display = 'none';
    }
    
    const saveBtn = document.getElementById('saveAvatarBtn');
    console.log('💾 Save button found:', !!saveBtn);
}
function closeAvatarModal() {
    const modal = document.getElementById('avatarModal');
    if (!modal) return;
    
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
    selectedAvatarFile = null;
    selectedCustomFile = null;
    
    const currentAvatar = document.getElementById('currentAvatar');
    if (currentAvatar) {
        const currentSrc = currentAvatar.src;
        document.querySelectorAll('.avatar-option').forEach(img => {
            if (img.src === currentSrc) {
                img.classList.add('selected');
            } else {
                img.classList.remove('selected');
            }
        });
    }
    
    const preview = document.getElementById('uploadPreview');
    if (preview) {
        preview.style.display = 'none';
    }
}

// edit cover photo
function editCover() {
    const coverInput = document.getElementById('coverInput');
    if (coverInput) {
        coverInput.click();
    }
}

// handle cover photo upload
function handleCoverUpload(event) {
    const file = event.target.files[0];
    
    if (!file) {
        return;
    }
    
    console.log('📸 Cover upload started:', file.name);
    const validTypes = ['image/jpeg', 'image/jpg', 'image/png'];
    if (!validTypes.includes(file.type)) {
        showNotification('Please select a valid image file (JPEG or PNG)', 'error');
        event.target.value = '';
        return;
    }
    
    const maxSize = 5 * 1024 * 1024; // 5MB
    if (file.size > maxSize) {
        showNotification('File is too large. Maximum size is 5MB', 'error');
        event.target.value = '';
        return;
    }
    const formData = new FormData();
    formData.append('cover', file);
    
    showNotification('Uploading cover photo...', 'info');
    const uploadUrl = window.API_BASE_PATH + 'profile/upload-cover.php';
    console.log('🔗 Upload URL:', uploadUrl);
    
    fetch(uploadUrl, {
        method: 'POST',
        body: formData,
        credentials: 'include'
    })
    .then(response => {
        console.log('📡 Response status:', response.status);
        return response.text().then(text => {
            console.log('📄 Raw response:', text);
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('❌ JSON parse error:', e);
                throw new Error('Invalid server response: ' + text.substring(0, 100));
            }
        });
    })
    .then(data => {
        console.log('✅ Parsed data:', data);
        if (data.success) {
            const coverImg = document.querySelector('.cover-img');
            if (coverImg) {
                coverImg.src = data.displayPath;
            }
            showNotification('Cover photo updated successfully!', 'success');
        } else {
            showNotification(data.message || 'Failed to upload cover photo', 'error');
        }
    })
    .catch(error => {
        console.error('❌ Upload error:', error);
        showNotification('An error occurred while uploading: ' + error.message, 'error');
    })
    .finally(() => {
        event.target.value = '';
    });
}

// select preset avatar
function selectAvatar(avatarPath, element) {
    console.log('✅ Avatar selected:', avatarPath);
    document.querySelectorAll('.avatar-option').forEach(img => {
        img.classList.remove('selected');
    });
    element.classList.add('selected');
    selectedAvatarFile = avatarPath;
    selectedCustomFile = null;
    
    const preview = document.getElementById('uploadPreview');
    if (preview) {
        preview.style.display = 'none';
    }
}

// trigger custom file input
function triggerFileInput() {
    const fileInput = document.getElementById('customAvatarInput');
    if (fileInput) {
        fileInput.click();
    }
}

// handle custom file selection
function handleCustomFileSelect(event) {
    const file = event.target.files[0];
    
    if (!file) {
        return;
    }
    
    console.log('🎨 Custom avatar selected:', file.name);
    const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    if (!validTypes.includes(file.type)) {
        showNotification('Please select a valid image file (JPEG, PNG, or GIF)', 'error');
        event.target.value = '';
        return;
    }
    const maxSize = 2 * 1024 * 1024; // 2MB
    if (file.size > maxSize) {
        showNotification('File is too large. Maximum size is 2MB', 'error');
        event.target.value = '';
        return;
    }
    
    selectedCustomFile = file;
    selectedAvatarFile = null;
    
    document.querySelectorAll('.avatar-option').forEach(img => {
        img.classList.remove('selected');
    });
    
    const reader = new FileReader();
    reader.onload = function(e) {
        const preview = document.getElementById('uploadPreview');
        const previewImg = document.getElementById('uploadPreviewImg');
        
        if (preview && previewImg) {
            previewImg.src = e.target.result;
            preview.style.display = 'flex';
            console.log('✅ Preview displayed');
        }
    };
    reader.readAsDataURL(file);
}

// save avatar
function saveAvatar() {
    console.log('💾 Save avatar clicked');
    console.log('📁 Selected file:', selectedAvatarFile);
    console.log('📁 Custom file:', selectedCustomFile);
    
    if (!selectedAvatarFile && !selectedCustomFile) {
        showNotification('Please select an avatar or upload a custom image', 'error');
        return;
    }
    
    const saveBtn = document.getElementById('saveAvatarBtn');
    if (!saveBtn) {
        console.error('❌ Save button not found!');
        return;
    }
    
    const originalText = saveBtn.textContent;
    saveBtn.disabled = true;
    saveBtn.textContent = 'Saving...';
    
    if (selectedCustomFile) {
        uploadCustomAvatar(selectedCustomFile, saveBtn, originalText);
    } else {
        savePresetAvatar(selectedAvatarFile, saveBtn, originalText);
    }
}

// save preset avatar
function savePresetAvatar(avatarPath, saveBtn, originalText) {
    console.log('💾 Saving preset avatar:', avatarPath);
    
    const url = window.API_BASE_PATH + 'profile/update-avatar.php';
    console.log('🔗 Update URL:', url);
    
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        credentials: 'include',
        body: JSON.stringify({ avatar: avatarPath })
    })
    .then(response => response.json())
    .then(data => {
        console.log('✅ Update response:', data);
        if (data.success) {
            const displayPath = data.displayPath || window.API_BASE_PATH + avatarPath;
            const currentAvatar = document.getElementById('currentAvatar');
            if (currentAvatar) {
                currentAvatar.src = displayPath;
            }
            closeAvatarModal();
            showNotification(data.message || 'Avatar updated successfully!', 'success');
        } else {
            showNotification(data.message || 'Failed to update avatar', 'error');
        }
    })
    .catch(error => {
        console.error('❌ Error:', error);
        showNotification('An error occurred while updating avatar', 'error');
    })
    .finally(() => {
        saveBtn.disabled = false;
        saveBtn.textContent = originalText;
    });
}

// upload custom avatar
function uploadCustomAvatar(file, saveBtn, originalText) {
    console.log('📤 Uploading custom avatar:', file.name);
    
    const formData = new FormData();
    formData.append('avatar', file);
    
    const url = window.API_BASE_PATH + 'profile/upload-avatar.php';
    console.log('🔗 Upload URL:', url);
    
    fetch(url, {
        method: 'POST',
        body: formData,
        credentials: 'include'
    })
    .then(response => response.json())
    .then(data => {
        console.log('✅ Upload response:', data);
        if (data.success) {
            const displayPath = data.displayPath || window.API_BASE_PATH + data.avatar;
            const currentAvatar = document.getElementById('currentAvatar');
            if (currentAvatar) {
                currentAvatar.src = displayPath;
            }
            closeAvatarModal();
            showNotification(data.message || 'Custom avatar uploaded successfully!', 'success');
        } else {
            showNotification(data.message || 'Failed to upload avatar', 'error');
        }
    })
    .catch(error => {
        console.error('❌ Error:', error);
        showNotification('An error occurred while uploading avatar', 'error');
    })
    .finally(() => {
        saveBtn.disabled = false;
        saveBtn.textContent = originalText;
    });
}

// address management functions
function getCSRFToken() {
    const tokenInput = document.querySelector('input[name="csrf_token"]');
    const tokenMeta = document.querySelector('meta[name="csrf-token"]');
    
    if (tokenInput) {
        return tokenInput.value;
    } else if (tokenMeta) {
        return tokenMeta.getAttribute('content');
    }
    
    console.warn('⚠️ CSRF token not found');
    return null;
}

// load addresses
function loadAddresses() {
    console.log('📍 Loading addresses...');
    
    fetch(window.API_BASE_PATH + 'api/address.php?action=list', {
        method: 'GET',
        credentials: 'include'
    })
    .then(response => {
        console.log('📡 Address response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('✅ Addresses loaded:', data);
        if (data.success) {
            displayAddresses(data.addresses);
        } else {
            console.error('❌ Failed to load addresses:', data.message);
            const container = document.getElementById('addressesContainer');
            if (container) {
                container.innerHTML = `
                    <div class="empty-state">
                        <p>Failed to load addresses: ${escapeHtml(data.message)}</p>
                    </div>
                `;
            }
        }
    })
    .catch(error => {
        console.error('❌ Error loading addresses:', error);
        const container = document.getElementById('addressesContainer');
        if (container) {
            container.innerHTML = `
                <div class="empty-state">
                    <p>Error loading addresses. Please refresh the page.</p>
                </div>
            `;
        }
    });
}

// display addresses
function displayAddresses(addresses) {
    const container = document.getElementById('addressesContainer');
    
    if (!container) {
        console.error('❌ Address container not found');
        return;
    }
    
    if (!addresses || addresses.length === 0) {
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

// open add address modal
function openAddAddressModal() {
    const modal = document.getElementById('addressModal');
    if (!modal) {
        console.error('❌ Address modal not found');
        return;
    }
    
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    const form = document.getElementById('addressForm');
    if (form) {
        form.reset();
    }
    
    const title = document.getElementById('addressModalTitle');
    if (title) {
        title.textContent = 'Add New Address';
    }
    
    const idField = document.getElementById('addressID');
    if (idField) {
        idField.value = '';
    }
}

// close address modal
function closeAddressModal() {
    const modal = document.getElementById('addressModal');
    if (!modal) return;
    
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
}

// edit address
function editAddress(addressID) {
    console.log('✏️ Editing address:', addressID);
    
    fetch(window.API_BASE_PATH + `api/address.php?action=get&id=${addressID}`, {
        method: 'GET',
        credentials: 'include'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.address) {
            const addr = data.address;
            
            const fields = {
                'addressID': addr.addressID,
                'addressField': addr.address,
                'municipalityField': addr.municipality,
                'provinceField': addr.province,
                'postalCodeField': addr.postal_code
            };
            
            for (const [id, value] of Object.entries(fields)) {
                const field = document.getElementById(id);
                if (field) {
                    field.value = value;
                }
            }
            const title = document.getElementById('addressModalTitle');
            if (title) {
                title.textContent = 'Edit Address';
            }
            const modal = document.getElementById('addressModal');
            if (modal) {
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            }
        } else {
            showNotification('Failed to load address details', 'error');
        }
    })
    .catch(error => {
        console.error('❌ Error:', error);
        showNotification('Error loading address', 'error');
    });
}

// save address
function saveAddress(event) {
    event.preventDefault();
    
    console.log('💾 Saving address...');
    
    const addressID = document.getElementById('addressID')?.value;
    const action = addressID ? 'update' : 'add';
    
    const data = {
        action: action,
        address: document.getElementById('addressField')?.value.trim() || '',
        municipality: document.getElementById('municipalityField')?.value.trim() || '',
        province: document.getElementById('provinceField')?.value.trim() || '',
        postal_code: document.getElementById('postalCodeField')?.value.trim() || ''
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
    if (!saveBtn) return;
    
    const originalText = saveBtn.textContent;
    saveBtn.disabled = true;
    saveBtn.textContent = 'Saving...';
    
    const csrfToken = getCSRFToken();
    if (csrfToken) {
        data.csrf_token = csrfToken;
    }
    
    console.log('📤 Sending data:', data);
    
    fetch(window.API_BASE_PATH + 'api/address.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        credentials: 'include',
        body: JSON.stringify(data)
    })
    .then(response => {
        console.log('📡 Response status:', response.status);
        return response.json();
    })
    .then(result => {
        console.log('✅ Save result:', result);
        if (result.success) {
            showNotification(result.message || 'Address saved successfully', 'success');
            closeAddressModal();
            loadAddresses();
            
            if (result.csrf_token) {
                updateCSRFToken(result.csrf_token);
            }
        } else {
            showNotification(result.message || 'Failed to save address', 'error');
        }
    })
    .catch(error => {
        console.error('❌ Error:', error);
        showNotification('An error occurred', 'error');
    })
    .finally(() => {
        saveBtn.disabled = false;
        saveBtn.textContent = originalText;
    });
}

// delete address
function deleteAddress(addressID) {
    if (!confirm('Are you sure you want to delete this address?')) {
        return;
    }
    
    console.log('🗑️ Deleting address:', addressID);
    
    const data = {
        action: 'delete',
        addressID: addressID
    };
    
    const csrfToken = getCSRFToken();
    if (csrfToken) {
        data.csrf_token = csrfToken;
    }
    
    fetch(window.API_BASE_PATH + 'api/address.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        credentials: 'include',
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        console.log('✅ Delete result:', data);
        if (data.success) {
            showNotification('Address deleted successfully', 'success');
            loadAddresses();
            
            if (data.csrf_token) {
                updateCSRFToken(data.csrf_token);
            }
        } else {
            showNotification(data.message || 'Failed to delete address', 'error');
        }
    })
    .catch(error => {
        console.error('❌ Error:', error);
        showNotification('An error occurred', 'error');
    });
}

// update CSRF token in DOM
function updateCSRFToken(newToken) {
    const tokenInput = document.querySelector('input[name="csrf_token"]');
    const tokenMeta = document.querySelector('meta[name="csrf-token"]');
    
    if (tokenInput) {
        tokenInput.value = newToken;
    }
    if (tokenMeta) {
        tokenMeta.setAttribute('content', newToken);
    }
    
    console.log('🔒 CSRF token updated');
}

// security functions
function changePassword() {
    window.location.href = window.API_BASE_PATH + 'auth/pass-reset';
}

function enable2FA() {
    showNotification('Two-factor authentication feature coming soon!', 'info');
}

// utility functions
function escapeHtml(text) {
    if (!text) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return String(text).replace(/[&<>"']/g, m => map[m]);
}


function showNotification(message, type) {
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

// view order details
function viewOrderDetails(orderID) {
    window.location.href = window.API_BASE_PATH + 'marketplace/myorders';
}

// initialize
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Profile.js loaded');
    if (!window.API_BASE_PATH) {
        window.API_BASE_PATH = '/agri_system/public/';
        console.log('📁 API_BASE_PATH set to:', window.API_BASE_PATH);
    }
    
    const avatarModal = document.getElementById('avatarModal');
    const saveBtn = document.getElementById('saveAvatarBtn');
    console.log('🔍 Avatar modal found:', !!avatarModal);
    console.log('🔍 Save button found:', !!saveBtn);
    
    const addressContainer = document.getElementById('addressesContainer');
    if (addressContainer) {
        console.log('📍 Address container found, loading addresses...');
        loadAddresses();
    } else {
        console.warn('⚠️ Address container not found');
    }
    
    const alerts = document.querySelectorAll('.alert:not(.notification-toast)');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
    
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
// Inject CSS for animations if not already present
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