// public/js/marketplace-wishlist.js
// Handles wishlist toggle functionality in marketplace

(function() {
    'use strict';

    // ==================== CONFIGURATION ====================
    const API_URL = (typeof BASE_URL !== 'undefined' ? BASE_URL : '') + '/app/controllers/WishlistController.php';
    const TIMEOUT = 15000;

    // ==================== CSRF TOKEN ====================
    function getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) return meta.getAttribute('content') || '';
        
        const cookie = document.cookie.split(';')
            .map(c => c.trim())
            .find(c => c.startsWith('csrf_token='));
        return cookie ? decodeURIComponent(cookie.split('=')[1]) : '';
    }

    function updateCsrfToken(token) {
        if (!token) return;
        
        let meta = document.querySelector('meta[name="csrf-token"]');
        if (!meta) {
            meta = document.createElement('meta');
            meta.setAttribute('name', 'csrf-token');
            document.head.appendChild(meta);
        }
        meta.setAttribute('content', token);
        
        document.cookie = `csrf_token=${encodeURIComponent(token)}; path=/; samesite=lax`;
    }

    // ==================== API CALLS ====================
    async function fetchWithTimeout(url, options = {}, timeout = TIMEOUT) {
        const controller = new AbortController();
        const id = setTimeout(() => controller.abort(), timeout);
        
        options.signal = controller.signal;
        
        try {
            const response = await fetch(url, options);
            clearTimeout(id);
            return response;
        } catch (error) {
            clearTimeout(id);
            throw error;
        }
    }

    async function toggleWishlistAPI(productID) {
        try {
            const response = await fetchWithTimeout(API_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    action: 'toggle',
                    productID: productID,
                    csrf_token: getCsrfToken()
                })
            });

            if (!response.ok) {
                throw new Error(`Server error ${response.status}`);
            }

            const data = await response.json();
            
            if (data.csrf_token) {
                updateCsrfToken(data.csrf_token);
            }

            return data;
            
        } catch (error) {
            console.error('Wishlist toggle error:', error);
            return {
                success: false,
                message: error.message || 'Network error occurred'
            };
        }
    }

    // ==================== UI FEEDBACK ====================
    function showNotification(message, type = 'info') {
        // Remove existing notifications
        const existing = document.querySelectorAll('.wishlist-notification');
        existing.forEach(n => n.remove());

        const notification = document.createElement('div');
        notification.className = `wishlist-notification wishlist-notification-${type}`;
        notification.textContent = message;
        
        notification.style.cssText = `
            position: fixed;
            top: 80px;
            right: 20px;
            padding: 12px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 9999;
            font-size: 14px;
            font-weight: 500;
            transform: translateX(400px);
            opacity: 0;
            transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        `;

        if (type === 'success') {
            notification.style.background = '#d4edda';
            notification.style.color = '#155724';
            notification.style.border = '1px solid #c3e6cb';
        } else if (type === 'error') {
            notification.style.background = '#f8d7da';
            notification.style.color = '#721c24';
            notification.style.border = '1px solid #f5c6cb';
        } else {
            notification.style.background = '#d1ecf1';
            notification.style.color = '#0c5460';
            notification.style.border = '1px solid #bee5eb';
        }

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
            notification.style.opacity = '1';
        }, 100);

        setTimeout(() => {
            notification.style.transform = 'translateX(400px)';
            notification.style.opacity = '0';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    function updateWishlistButton(button, inWishlist) {
        if (!button) return;

        if (inWishlist) {
            button.textContent = '❤️';
            button.classList.add('active');
            button.title = 'Remove from wishlist';
            button.setAttribute('aria-label', 'Remove from wishlist');
        } else {
            button.textContent = '🤍';
            button.classList.remove('active');
            button.title = 'Add to wishlist';
            button.setAttribute('aria-label', 'Add to wishlist');
        }
    }

    function updateWishlistCount(count) {
        if (typeof count !== 'number') return;

        const badges = document.querySelectorAll('.wishlist-btn .badge, .nav-link[href*="wishlist"] .badge');
        badges.forEach(badge => {
            badge.textContent = count;
            badge.style.display = count > 0 ? 'flex' : 'none';
        });
    }

    // ==================== MAIN TOGGLE FUNCTION ====================
    window.toggleWishlist = async function(event, productID) {
        event.preventDefault();
        event.stopPropagation();

        // Check if user is logged in
        if (!window.marketplaceData || !window.marketplaceData.isLoggedIn) {
            showNotification('Please login to add items to wishlist', 'info');
            setTimeout(() => {
                window.location.href = BASE_URL + '/auth/login';
            }, 1500);
            return;
        }

        // Check if user is buyer
        if (window.marketplaceData.userRole !== 'buyer') {
            showNotification('Only buyers can add items to wishlist', 'error');
            return;
        }

        const button = event.target.closest('.wishlist-btn');
        if (!button) return;

        // Prevent double-clicks
        if (button.disabled) return;
        button.disabled = true;

        // Add loading animation
        const originalContent = button.textContent;
        button.style.transform = 'scale(0.9)';

        const result = await toggleWishlistAPI(productID);

        // Restore button state
        button.disabled = false;
        button.style.transform = 'scale(1)';

        if (result.success) {
            updateWishlistButton(button, result.inWishlist);
            
            if (result.wishlistCount !== undefined) {
                updateWishlistCount(result.wishlistCount);
            }

            showNotification(
                result.message || (result.inWishlist ? 'Added to wishlist' : 'Removed from wishlist'),
                'success'
            );
        } else {
            button.textContent = originalContent;
            showNotification(result.message || 'Failed to update wishlist', 'error');
        }
    };

    // ==================== INITIALIZE WISHLIST STATES ====================
    async function initializeWishlistStates() {
        if (!window.marketplaceData || 
            !window.marketplaceData.isLoggedIn || 
            window.marketplaceData.userRole !== 'buyer') {
            return;
        }

        try {
            const response = await fetchWithTimeout(API_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    action: 'get_items'
                })
            });

            if (!response.ok) return;

            const data = await response.json();
            
            if (data.success && data.items) {
                const wishlistProductIDs = data.items.map(item => parseInt(item.productID, 10));
                
                // Update all wishlist buttons
                document.querySelectorAll('.wishlist-btn[data-product-id]').forEach(button => {
                    const productID = parseInt(button.getAttribute('data-product-id'), 10);
                    const inWishlist = wishlistProductIDs.includes(productID);
                    updateWishlistButton(button, inWishlist);
                });

                // Update count
                if (data.count !== undefined) {
                    updateWishlistCount(data.count);
                }
            }
        } catch (error) {
            console.error('Failed to initialize wishlist states:', error);
        }
    }

    // ==================== INITIALIZE ON PAGE LOAD ====================
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeWishlistStates);
    } else {
        initializeWishlistStates();
    }

    console.log('✅ Marketplace wishlist initialized');

})();