// public/js/cart.js
function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    if (!meta) {
        console.error('CSRF token meta tag not found!');
        return '';
    }
    return meta.getAttribute('content');
}

// Update CSRF token in meta tag
function updateCsrfToken(token) {
    let meta = document.querySelector('meta[name="csrf-token"]');
    if (!meta) {
        meta = document.createElement('meta');
        meta.setAttribute('name', 'csrf-token');
        document.head.appendChild(meta);
    }
    meta.setAttribute('content', token);
}

// Check if sessionStorage is available
function isSessionStorageAvailable() {
    try {
        const test = '__storage_test__';
        sessionStorage.setItem(test, test);
        sessionStorage.removeItem(test);
        return true;
    } catch (e) {
        return false;
    }
}

// Select/Deselect all items
function selectAllItems() {
    const checkAll = document.getElementById("selectAllCheckbox");
    const itemCheckboxes = document.querySelectorAll(".item-checkbox");
    
    itemCheckboxes.forEach(cb => {
        cb.checked = checkAll.checked;
    });
    
    calculateCartTotal();
}

// Calculate cart totals based on selected items
function calculateCartTotal() {
    const selected = document.querySelectorAll(".item-checkbox:checked");
    let subtotal = 0;
    let count = 0;
    const shippingFee = 0;

    selected.forEach(cb => {
        const card = cb.closest(".cart-item-card");
        const price = parseFloat(card.dataset.price);
        const qtyElement = card.querySelector(".cart-qty-number");
        const qty = parseInt(qtyElement.textContent);

        if (!isNaN(price) && !isNaN(qty)) {
            subtotal += price * qty;
            count++;
        }
    });

    document.getElementById("selectedItemsCount").textContent = count;
    document.getElementById("subtotalAmount").textContent = "₱" + subtotal.toFixed(2);
    
    const shippingFeeText = count > 0 ? "₱" + shippingFee.toFixed(2) : "₱0.00";
    document.getElementById("shippingFee").textContent = shippingFeeText;
    
    const grandTotal = subtotal + (count > 0 ? shippingFee : 0);
    document.getElementById("cartGrandTotal").textContent = "₱" + grandTotal.toFixed(2);

    const checkoutBtn = document.getElementById("checkoutBtn");
    if (checkoutBtn) {
        checkoutBtn.disabled = count === 0;
    }
}

// Show notification toast
function showNotification(message, type = 'success') {
    const existing = document.querySelector('.toast-notification');
    if (existing) {
        existing.remove();
    }

    const toast = document.createElement('div');
    toast.className = `toast-notification ${type}`;
    toast.textContent = message;
    document.body.appendChild(toast);

    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Update item quantity
async function updateQty(productID, change) {
    const qtyEl = document.getElementById(`qty-${productID}`);
    if (!qtyEl) {
        console.error('Quantity element not found for product:', productID);
        showNotification('Error: Element not found', 'error');
        return;
    }

    const currentQty = parseInt(qtyEl.textContent);
    const newQty = currentQty + change;

    if (newQty < 1) {
        if (confirm("Remove this item from your cart?")) {
            await removeItem(productID);
        }
        return;
    }

    if (newQty > 20) {
        showNotification('Maximum 20 items per product', 'error');
        return;
    }

    const card = qtyEl.closest('.cart-item-card');
    card.classList.add('loading');

    const csrfToken = getCsrfToken();
    if (!csrfToken) {
        showNotification('Security token missing. Please refresh the page.', 'error');
        card.classList.remove('loading');
        return;
    }

    try {
        const response = await fetch(CART_CONTROLLER_URL, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-Requested-With": "XMLHttpRequest"
            },
            credentials: "same-origin",
            body: JSON.stringify({
                action: "update",
                productID: productID,
                quantity: newQty,
                csrf_token: csrfToken
            })
        });

        const contentType = response.headers.get("content-type");
        if (!contentType || !contentType.includes("application/json")) {
            const text = await response.text();
            console.error('Non-JSON response:', text);
            showNotification('Server error: Invalid response format', 'error');
            card.classList.remove('loading');
            return;
        }

        const data = await response.json();

        if (!data.success) {
            showNotification(data.message || "Failed to update quantity", 'error');
            card.classList.remove('loading');
            return;
        }

        qtyEl.textContent = newQty;

        const price = parseFloat(card.dataset.price);
        const totalElement = card.querySelector(".cart-item-total");
        if (totalElement) {
            totalElement.textContent = "₱" + (price * newQty).toFixed(2);
        }

        if (data.csrf_token) {
            updateCsrfToken(data.csrf_token);
        }

        calculateCartTotal();
        showNotification("Quantity updated", 'success');

    } catch (error) {
        console.error("Error updating quantity:", error);
        showNotification("Connection error. Please try again.", 'error');
    } finally {
        card.classList.remove('loading');
    }
}

// Remove item from cart
async function removeItem(productID) {
    const itemCard = document.querySelector(`[data-product-id="${productID}"]`);
    if (!itemCard) {
        console.error('Item card not found for product:', productID);
        return;
    }

    itemCard.style.opacity = '0.5';
    itemCard.style.pointerEvents = 'none';

    const csrfToken = getCsrfToken();
    if (!csrfToken) {
        showNotification('Security token missing. Please refresh the page.', 'error');
        itemCard.style.opacity = '1';
        itemCard.style.pointerEvents = 'auto';
        return;
    }

    try {
        const response = await fetch(CART_CONTROLLER_URL, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-Requested-With": "XMLHttpRequest"
            },
            credentials: "same-origin",
            body: JSON.stringify({
                action: "remove",
                productID: productID,
                csrf_token: csrfToken
            })
        });

        const contentType = response.headers.get("content-type");
        if (!contentType || !contentType.includes("application/json")) {
            const text = await response.text();
            console.error('Non-JSON response:', text);
            showNotification('Server error: Invalid response format', 'error');
            itemCard.style.opacity = '1';
            itemCard.style.pointerEvents = 'auto';
            return;
        }

        const data = await response.json();

        if (!data.success) {
            showNotification(data.message || "Failed to remove item", 'error');
            itemCard.style.opacity = '1';
            itemCard.style.pointerEvents = 'auto';
            return;
        }

        itemCard.style.transition = "all 0.3s ease";
        itemCard.style.transform = "translateX(-20px)";
        itemCard.style.opacity = "0";

        setTimeout(() => {
            itemCard.remove();

            const shopSection = document.querySelector('.shop-section');
            if (shopSection && shopSection.querySelectorAll('.cart-item-card').length === 0) {
                shopSection.remove();
            }

            const remainingItems = document.querySelectorAll('.cart-item-card');
            if (remainingItems.length === 0) {
                location.reload();
            }

            calculateCartTotal();
        }, 300);

        if (data.csrf_token) {
            updateCsrfToken(data.csrf_token);
        }

        showNotification("Item removed from cart", 'success');

    } catch (error) {
        console.error("Error removing item:", error);
        showNotification("Connection error. Please try again.", 'error');
        itemCard.style.opacity = '1';
        itemCard.style.pointerEvents = 'auto';
    }
}

// process checkout
async function goToCheckout() {
    console.log('=== CHECKOUT PROCESS STARTED ===');
    
    const selectedCheckboxes = document.querySelectorAll(".item-checkbox:checked");
    console.log('Selected checkboxes count:', selectedCheckboxes.length);

    if (selectedCheckboxes.length === 0) {
        showNotification("Please select at least one item to checkout", 'error');
        return;
    }

    const selectedProductIDs = [];
    
    selectedCheckboxes.forEach((cb, index) => {
        const card = cb.closest(".cart-item-card");
        
        if (!card) {
            console.error(`Checkbox ${index + 1}: No parent card found`);
            return;
        }
        
        // Get productID from data attribute
        const productID = parseInt(card.getAttribute('data-product-id'));
        
        if (productID && !isNaN(productID)) {
            selectedProductIDs.push(productID);
            console.log(`✅ Item ${index + 1}: productID = ${productID}`);
        } else {
            console.error(`❌ Could not extract productID from card ${index + 1}`);
        }
    });

    console.log('Final selected product IDs:', selectedProductIDs);

    if (selectedProductIDs.length === 0) {
        console.error('❌ No valid product IDs found!');
        alert('Error: Could not identify selected products. Please refresh the page and try again.');
        return;
    }

    if (!isSessionStorageAvailable()) {
        console.error('❌ SessionStorage is not available!');
        alert('Your browser settings are blocking storage. Please enable cookies/storage for this site.');
        return;
    }

    const checkoutBtn = document.getElementById('checkoutBtn');
    if (!checkoutBtn) {
        console.error('❌ Checkout button not found');
        return;
    }
    
    const originalText = checkoutBtn.innerHTML;
    checkoutBtn.disabled = true;
    checkoutBtn.innerHTML = 'Processing...';

    try {
        const dataToSave = JSON.stringify(selectedProductIDs);
        sessionStorage.setItem('checkout_product_ids', dataToSave);
        
        const verification = sessionStorage.getItem('checkout_product_ids');
        console.log('✅ Saved to sessionStorage:', verification);
        
        if (verification !== dataToSave) {
            throw new Error('SessionStorage verification failed');
        }
    } catch (e) {
        console.error('❌ Failed to save to sessionStorage:', e);
        alert('Failed to save checkout data. Please try again.');
        checkoutBtn.disabled = false;
        checkoutBtn.innerHTML = originalText;
        return;
    }

    const checkoutURL = BASE_URL + "item-handling/checkout?ids=" + selectedProductIDs.join(',');
    console.log('✅ Redirect URL:', checkoutURL);

    console.log('✅ Redirecting to checkout...');
    window.location.href = checkoutURL;
}

document.addEventListener("DOMContentLoaded", function() {
    console.log('=== Cart Page Initialization ===');
    console.log('BASE_URL:', BASE_URL);
    console.log('CART_CONTROLLER_URL:', CART_CONTROLLER_URL);
    console.log('CSRF Token:', getCsrfToken());

    if (!isSessionStorageAvailable()) {
        console.error('❌ SessionStorage is not available!');
        alert('Warning: Your browser settings may prevent checkout. Please enable cookies.');
    } else {
        console.log('✅ SessionStorage is working');
    }

    calculateCartTotal();

    document.querySelectorAll(".item-checkbox").forEach(cb => {
        cb.addEventListener("change", calculateCartTotal);
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'a' && e.ctrlKey) {
            e.preventDefault();
            const selectAllCheckbox = document.getElementById('selectAllCheckbox');
            if (selectAllCheckbox) {
                selectAllCheckbox.checked = !selectAllCheckbox.checked;
                selectAllItems();
            }
        }
    });

    console.log('=== Cart page initialized successfully ===');
});

// CSS for toast notifications
if (!document.querySelector('style[data-cart-toast]')) {
    const style = document.createElement('style');
    style.setAttribute('data-cart-toast', 'true');
    style.textContent = `
        .toast-notification {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #323232;
            color: white;
            padding: 16px 24px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            z-index: 10000;
            animation: slideIn 0.3s ease;
            font-size: 14px;
            max-width: 300px;
        }
        .toast-notification.success {
            background: #4CAF50;
        }
        .toast-notification.error {
            background: #f44336;
        }
        .toast-notification.warning {
            background: #ff9800;
        }
        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(400px);
                opacity: 0;
            }
        }
        .cart-item-card.loading {
            opacity: 0.6;
            pointer-events: none;
        }
    `;
    document.head.appendChild(style);
}