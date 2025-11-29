// ==========================================
// CHECKOUT PAGE - COMPLETELY FIXED VERSION
// ==========================================

console.log('checkout.js loaded');

// Global checkout data
let checkoutData = null;
let addresses = [];
let selectedAddressId = null;

// Initialize checkout page
document.addEventListener('DOMContentLoaded', function () {
    console.log('=== Initializing Checkout Page ===');
    loadCheckoutData();
});

// ✅ FIXED: Load checkout data with better debugging
async function loadCheckoutData() {
    const loadingEl = document.getElementById('checkoutLoading');
    const containerEl = document.getElementById('checkoutContainer');
    const errorEl = document.getElementById('checkoutError');

    try {
        console.log('=== Loading Checkout Data ===');
        
        // ✅ Try multiple sources for product IDs
        let selectedProductIDs = null;
        
        // Method 1: SessionStorage
        const sessionData = sessionStorage.getItem('checkout_product_ids');
        console.log('SessionStorage raw data:', sessionData);
        
        if (sessionData) {
            try {
                selectedProductIDs = JSON.parse(sessionData);
                console.log('✅ Parsed from sessionStorage:', selectedProductIDs);
            } catch (e) {
                console.error('❌ Failed to parse sessionStorage data:', e);
            }
        }
        
        // Method 2: URL parameters (fallback)
        if (!selectedProductIDs || !Array.isArray(selectedProductIDs) || selectedProductIDs.length === 0) {
            const urlParams = new URLSearchParams(window.location.search);
            const idsParam = urlParams.get('ids');
            console.log('URL ids parameter:', idsParam);
            
            if (idsParam) {
                selectedProductIDs = idsParam.split(',').map(id => parseInt(id)).filter(id => !isNaN(id));
                console.log('✅ Parsed from URL:', selectedProductIDs);
            }
        }
        
        // Final validation
        if (!selectedProductIDs || !Array.isArray(selectedProductIDs) || selectedProductIDs.length === 0) {
            console.error('❌ No valid product IDs found');
            console.log('SessionStorage keys:', Object.keys(sessionStorage));
            console.log('URL search:', window.location.search);
            
            loadingEl.style.display = 'none';
            errorEl.style.display = 'flex';
            document.getElementById('errorMessage').textContent = 
                'No items selected for checkout. Redirecting to cart...';
            
            setTimeout(() => {
                window.location.href = BASE_URL + 'item-handling/cart';
            }, 2000);
            return;
        }

        console.log('✅ Using product IDs:', selectedProductIDs);

        // ✅ Fetch checkout data from API
        const url = new URL(CHECKOUT_API_URL, window.location.origin);
        url.searchParams.append('product_ids', selectedProductIDs.join(','));

        console.log('🌐 Fetching from:', url.toString());

        const response = await fetch(url.toString(), {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        });

        console.log('📡 Response status:', response.status);

        // Check content type
        const contentType = response.headers.get("content-type");
        if (!contentType || !contentType.includes("application/json")) {
            const text = await response.text();
            console.error('❌ Non-JSON response:', text.substring(0, 500));
            throw new Error('Server returned invalid response format');
        }

        const data = await response.json();
        console.log('📦 Checkout data received:', data);

        if (!data.success) {
            console.error('❌ API returned error:', data.message);
            if (data.redirect) {
                alert(data.message);
                window.location.href = data.redirect;
                return;
            }
            throw new Error(data.message);
        }

        // ✅ Store checkout data globally
        checkoutData = data;
        addresses = data.addresses || [];

        console.log('✅ Items to display:', data.items.length);
        console.log('✅ Addresses available:', addresses.length);

        // Display everything
        displayAddresses();
        displayOrderItems(data.items);

        // Update summary
        document.getElementById('totalItemsCount').textContent = data.itemCount;
        document.getElementById('itemsCountText').textContent = data.itemCount + ' items';
        document.getElementById('subtotalDisplay').textContent = '₱' + data.subtotal;
        document.getElementById('shippingFeeDisplay').textContent = '₱' + data.shippingFee;
        document.getElementById('totalDisplay').textContent = '₱' + data.total;

        // Show checkout UI
        loadingEl.style.display = 'none';
        containerEl.style.display = 'grid';

        console.log('✅✅✅ Checkout loaded successfully! ✅✅✅');

    } catch (error) {
        console.error('💥 Fatal error loading checkout:', error);
        loadingEl.style.display = 'none';
        errorEl.style.display = 'flex';
        document.getElementById('errorMessage').textContent = 
            error.message || 'Failed to load checkout. Please try again.';
    }
}
// Display saved addresses
function displayAddresses() {
    const container = document.getElementById('savedAddressesList');
    
    if (!addresses || addresses.length === 0) {
        container.innerHTML = `
            <div class="no-addresses">
                <p>📍 No saved addresses found</p>
                <p style="font-size: 14px; margin-top: 5px;">Please add a delivery address to continue</p>
            </div>
        `;
        return;
    }

    // Set default address as selected
    if (checkoutData.defaultAddress) {
        selectedAddressId = checkoutData.defaultAddress.addressID;
    } else {
        selectedAddressId = addresses[0].addressID;
    }

    let html = '';
    addresses.forEach((addr, index) => {
        const isSelected = addr.addressID === selectedAddressId;
        const fullName = addr.full_name || checkoutData.user.full_name;
        const phone = addr.phone || checkoutData.user.phone;
        
        html += `
            <label class="address-option ${isSelected ? 'selected' : ''}" 
                   onclick="selectAddressOption(${addr.addressID})">
                <input type="radio" name="selected_address" value="${addr.addressID}" 
                       class="address-radio" ${isSelected ? 'checked' : ''}>
                <div class="address-option-content">
                    <div class="address-option-name">${escapeHtml(fullName)}</div>
                    <div class="address-option-text">
                        ${escapeHtml(phone)}<br>
                        ${escapeHtml(addr.address)}<br>
                        ${escapeHtml(addr.municipality)}, ${escapeHtml(addr.province)} ${escapeHtml(addr.postal_code)}
                    </div>
                </div>
            </label>
        `;
    });

    container.innerHTML = html;
}

// Select address option
function selectAddressOption(addressID) {
    selectedAddressId = addressID;
    
    document.querySelectorAll('.address-option').forEach(option => {
        option.classList.remove('selected');
    });
    event.currentTarget.classList.add('selected');
    event.currentTarget.querySelector('input[type="radio"]').checked = true;
}

// Show add address form
function showAddAddressForm() {
    const form = document.getElementById('addAddressForm');
    form.classList.add('active');
    form.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

// Cancel add address
function cancelAddAddress() {
    const form = document.getElementById('addAddressForm');
    form.classList.remove('active');
    
    document.getElementById('new_address').value = '';
    document.getElementById('new_municipality').value = '';
    document.getElementById('new_province').value = '';
    document.getElementById('new_postal_code').value = '';
}

// ✅ FIXED: Save new address to DATABASE via API
async function saveNewAddress() {
    const address = document.getElementById('new_address').value.trim();
    const municipality = document.getElementById('new_municipality').value.trim();
    const province = document.getElementById('new_province').value.trim();
    const postalCode = document.getElementById('new_postal_code').value.trim();

    // Validate inputs
    if (!address || !municipality || !province || !postalCode) {
        showNotification('Please fill in all required fields', 'warning');
        return;
    }

    // Validate postal code (4 digits)
    if (!/^\d{4}$/.test(postalCode)) {
        showNotification('Postal code must be 4 digits', 'warning');
        return;
    }

    // Show loading state
    const saveBtn = event.target;
    const originalText = saveBtn.innerHTML;
    saveBtn.disabled = true;
    saveBtn.innerHTML = 'Saving...';

    try {
        console.log('Saving address...', { address, municipality, province, postalCode });

        const response = await fetch(BASE_URL + 'api/address.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                action: 'add',
                address: address,
                municipality: municipality,
                province: province,
                postal_code: postalCode,
                csrf_token: getCsrfToken()
            })
        });

        // Check response type
        const contentType = response.headers.get("content-type");
        if (!contentType || !contentType.includes("application/json")) {
            const text = await response.text();
            console.error('Non-JSON response:', text);
            throw new Error('Server returned invalid response');
        }

        const data = await response.json();
        console.log('Add address response:', data);

        if (!data.success) {
            showNotification(data.message || 'Failed to save address', 'error');
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalText;
            return;
        }

        // ✅ Add the new address to the list
        const newAddress = data.address;
        newAddress.full_name = checkoutData.user.full_name;
        newAddress.phone = checkoutData.user.phone;
        
        addresses.push(newAddress);
        selectedAddressId = newAddress.addressID;

        // Update CSRF token
        if (data.csrf_token) {
            updateCsrfToken(data.csrf_token);
        }

        // Redisplay addresses
        displayAddresses();

        // Hide form and reset
        cancelAddAddress();

        showNotification('Address saved successfully!', 'success');
        
        // Reset button
        saveBtn.disabled = false;
        saveBtn.innerHTML = originalText;

    } catch (error) {
        console.error('Error saving address:', error);
        showNotification('Failed to save address. Please try again.', 'error');
        
        // Reset button
        saveBtn.disabled = false;
        saveBtn.innerHTML = originalText;
    }
}
// Display order items grouped by shop
function displayOrderItems(items) {
    const container = document.getElementById('orderItemsList');

    if (!items || items.length === 0) {
        container.innerHTML = '<p class="empty-message">No items found</p>';
        return;
    }

    // Group items by shop
    const itemsByShop = {};
    items.forEach(item => {
        if (!itemsByShop[item.shop_name]) {
            itemsByShop[item.shop_name] = [];
        }
        itemsByShop[item.shop_name].push(item);
    });

    let html = '';
    for (const [shopName, shopItems] of Object.entries(itemsByShop)) {
        html += `
            <div class="shop-order-section">
                <div class="shop-order-header">
                    <span class="shop-icon">🌾</span>
                    <span class="shop-order-name">${escapeHtml(shopName)}</span>
                </div>
                ${shopItems.map(item => `
                    <div class="checkout-item">
                        <img src="${BASE_URL}${item.primary_image}" 
                             alt="${escapeHtml(item.product_name)}"
                             class="checkout-item-image"
                             onerror="this.src='${BASE_URL}images/placeholder.jpg'">
                        <div class="checkout-item-info">
                            <div class="checkout-item-name">${escapeHtml(item.product_name)}</div>
                            <div class="checkout-item-details">₱${item.price} per ${item.unit}</div>
                            <div class="checkout-item-pricing">
                                <span class="checkout-item-qty">Qty: ${item.quantity} ${item.unit}</span>
                                <span class="checkout-item-total">₱${item.item_total}</span>
                            </div>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
    }

    container.innerHTML = html;
}

// Select payment method
function selectPayment(radio) {
    document.querySelectorAll('.payment-option').forEach(option => {
        option.classList.remove('selected');
    });
    radio.closest('.payment-option').classList.add('selected');
}

// Apply promo code (placeholder)
function applyPromo() {
    const promoCode = document.getElementById('promoCode').value.trim();
    
    if (!promoCode) {
        showNotification('Please enter a promo code', 'warning');
        return;
    }
    
    showNotification('Promo code feature coming soon!', 'info');
}

// ✅ FIXED: Place order function
async function placeOrder() {
    const btn = document.getElementById('placeOrderBtn');
    
    // Check if address is selected
    if (!selectedAddressId) {
        showNotification('Please select a delivery address', 'warning');
        return;
    }

    // Get selected payment method
    const paymentMethod = document.querySelector('input[name="payment_method"]:checked')?.value;
    if (!paymentMethod) {
        showNotification('Please select a payment method', 'warning');
        return;
    }

    // Get notes if any
    const notes = document.getElementById('notes')?.value || '';

    const orderData = {
        action: 'place_order',
        address_id: selectedAddressId,
        payment_method: paymentMethod,
        notes: notes,
        csrf_token: getCsrfToken()
    };

    // Show confirmation popup
    const confirmed = await showConfirmationPopup(orderData);
    if (!confirmed) return;

    // Disable button
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-small"></span> Processing...';

    try {
        const response = await fetch(CHECKOUT_API_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: JSON.stringify(orderData)
        });

        const data = await response.json();
        console.log('Place order response:', data);

        if (data.success) {
            if (data.csrf_token) {
                updateCsrfToken(data.csrf_token);
            }

            // ✅ Clear sessionStorage
            sessionStorage.removeItem('checkout_product_ids');

            showOrderSuccess(data.orderID);

            setTimeout(() => {
                window.location.href = data.redirect || BASE_URL + 'profile/buyer/orders';
            }, 2000);

        } else {
            showNotification('Error: ' + data.message, 'error');
            btn.disabled = false;
            btn.innerHTML = '<span>Place Order</span><span>→</span>';
        }

    } catch (error) {
        console.error('Place order error:', error);
        showNotification('Connection error. Please try again.', 'error');
        btn.disabled = false;
        btn.innerHTML = '<span>Place Order</span><span>→</span>';
    }
}

// ✅ FIXED: Show confirmation popup with FULL order details including items
function showConfirmationPopup(orderData) {
    return new Promise((resolve) => {
        console.log('=== SHOWING CONFIRMATION POPUP ===');
        console.log('Selected Address ID:', selectedAddressId);
        console.log('Checkout Data:', checkoutData);
        
        const paymentMethods = {
            'cod': 'Cash on Delivery',
            'gcash': 'GCash',
            'paymaya': 'PayMaya'
        };

        const selectedAddr = addresses.find(addr => addr.addressID === selectedAddressId);
        if (!selectedAddr) {
            console.error('❌ Selected address not found!');
            showNotification('Address not found', 'error');
            resolve(false);
            return;
        }

        console.log('Selected address:', selectedAddr);

        const fullName = selectedAddr.full_name || checkoutData.user.full_name;
        const phone = selectedAddr.phone || checkoutData.user.phone;
        const addressText = `${selectedAddr.address}, ${selectedAddr.municipality}, ${selectedAddr.province} ${selectedAddr.postal_code}`;

        // ✅ Build items list HTML
        let itemsListHtml = '';
        if (checkoutData.items && checkoutData.items.length > 0) {
            itemsListHtml = checkoutData.items.map(item => `
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #eee;">
                    <div style="flex: 1;">
                        <div style="font-weight: 600; color: #333; margin-bottom: 4px;">
                            ${escapeHtml(item.product_name)}
                        </div>
                        <div style="font-size: 13px; color: #666;">
                            ${item.quantity} ${item.unit} × ₱${item.price}
                        </div>
                    </div>
                    <div style="font-weight: 700; color: #2d5016; font-size: 16px;">
                        ₱${item.item_total}
                    </div>
                </div>
            `).join('');
        } else {
            itemsListHtml = '<p style="color: #999; text-align: center; padding: 20px;">No items</p>';
        }

        const overlay = document.createElement('div');
        overlay.className = 'confirmation-overlay';
        overlay.innerHTML = `
            <div class="confirmation-popup">
                <div class="popup-header">
                    <h2>📋 Confirm Your Order</h2>
                    <p>Please review your order details before proceeding</p>
                </div>
                
                <div class="popup-body">
                    <div class="confirm-section">
                        <h3>📍 Delivery Address</h3>
                        <div class="confirm-detail">
                            <strong>${escapeHtml(fullName)}</strong><br>
                            ${escapeHtml(phone)}<br>
                            ${escapeHtml(addressText)}
                        </div>
                    </div>
                    
                    <div class="confirm-section">
                        <h3>💳 Payment Method</h3>
                        <div class="confirm-detail">
                            <strong>${paymentMethods[orderData.payment_method]}</strong>
                        </div>
                    </div>
                    
                    <div class="confirm-section">
                        <h3>🛒 Order Items</h3>
                        <div class="confirm-detail" style="padding: 0 15px;">
                            ${itemsListHtml}
                        </div>
                    </div>
                    
                    <div class="confirm-section" style="background: #f0f8e8; padding: 20px; border-radius: 8px; margin-top: 15px; border: 2px solid #2d5016;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <span style="font-size: 18px; font-weight: 600; color: #333;">Total Amount:</span>
                            <span style="font-size: 24px; font-weight: 700; color: #2d5016;">₱${checkoutData.total}</span>
                        </div>
                        <div style="text-align: center; font-size: 13px; color: #666; padding-top: 8px; border-top: 1px dashed #2d5016;">
                            ${checkoutData.itemCount} items • Free LGU Delivery 🚚
                        </div>
                    </div>
                </div>
                
                <div class="popup-actions">
                    <button class="btn-cancel" onclick="closeConfirmationPopup(false)">
                        ❌ Cancel
                    </button>
                    <button class="btn-confirm" onclick="closeConfirmationPopup(true)">
                        ✅ Confirm Order
                    </button>
                </div>
            </div>
        `;

        document.body.appendChild(overlay);
        window._confirmResolve = resolve;
        
        console.log('✅ Confirmation popup displayed with', checkoutData.items.length, 'items');
    });
}

// Close confirmation popup
function closeConfirmationPopup(confirmed) {
    const overlay = document.querySelector('.confirmation-overlay');
    if (overlay) {
        overlay.remove();
    }

    if (window._confirmResolve) {
        window._confirmResolve(confirmed);
        delete window._confirmResolve;
    }
}

// Show order success message
function showOrderSuccess(orderID) {
    const overlay = document.createElement('div');
    overlay.className = 'success-overlay';
    overlay.innerHTML = `
        <div class="success-popup">
            <div class="success-icon">✅</div>
            <h2>Order Placed Successfully!</h2>
            <p>Thank you for your purchase!</p>
            <div class="order-details">
                <p><strong>Order ID:</strong> #${orderID}</p>
                <p>You will be redirected to your orders page...</p>
            </div>
        </div>
    `;

    document.body.appendChild(overlay);
}

// Show notification
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    setTimeout(() => notification.classList.add('show'), 10);
    
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Helper functions
function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
}

function updateCsrfToken(token) {
    let meta = document.querySelector('meta[name="csrf-token"]');
    if (!meta) {
        meta = document.createElement('meta');
        meta.setAttribute('name', 'csrf-token');
        document.head.appendChild(meta);
    }
    meta.setAttribute('content', token);
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}