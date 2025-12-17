// public/js/wishlist.js
(function() {
    'use strict';
    const API_URL = typeof WISHLIST_API_URL !== 'undefined' 
        ? WISHLIST_API_URL 
        : (typeof BASE_URL !== 'undefined' ? BASE_URL : '/agri_system/public/') + 'app/controllers/WishlistController.php';
    
    const TIMEOUT = 15000;

    console.log('🔧 Wishlist JS API URL:', API_URL);
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

    async function wishlistAction(action, payload = {}) {
        const body = { action, ...payload };
        const stateActions = ['add', 'remove', 'toggle', 'move_to_cart', 'clear_all', 'add_all_to_cart'];
        if (stateActions.includes(action)) {
            body.csrf_token = getCsrfToken();
        }

        console.log(`📤 Wishlist Action: ${action}`, { url: API_URL, payload: body });

        try {
            const response = await fetchWithTimeout(API_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                body: JSON.stringify(body)
            });

            console.log(`📥 Response status: ${response.status}`);

            if (!response.ok) {
                let errorMsg = `Server error ${response.status}`;
                try {
                    const errorData = await response.json();
                    errorMsg = errorData.message || errorMsg;
                } catch (e) {
                }
                throw new Error(errorMsg);
            }

            const data = await response.json();
            console.log(`✅ Success:`, data);
            if (data.csrf_token) {
                updateCsrfToken(data.csrf_token);
            }

            return data;
            
        } catch (error) {
            console.error('❌ Wishlist API error:', error);
            return {
                success: false,
                message: error.message || 'Network error occurred'
            };
        }
    }

    function showLoading(show = true) {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) {
            overlay.classList.toggle('active', show);
        }
    }

    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.textContent = message;
        document.body.appendChild(notification);

        setTimeout(() => notification.classList.add('show'), 100);
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 3500);
    }

    function updateWishlistCount() {
        const cards = document.querySelectorAll('.wishlist-card:not(.removing)');
        const count = cards.length;
        
        const subtitle = document.getElementById('wishlistSubtitle');
        if (subtitle) {
            subtitle.textContent = `${count} item${count !== 1 ? 's' : ''} saved`;
        }

        const badges = document.querySelectorAll('.wishlist-btn .badge, [href*="wishlist"] .badge');
        badges.forEach(badge => {
            badge.textContent = count;
            badge.style.display = count > 0 ? 'flex' : 'none';
        });

        if (count === 0) {
            showEmptyState();
        }
    }

    function showEmptyState() {
        const grid = document.getElementById('wishlistGrid');
        const header = document.querySelector('.header-actions');
        
        if (grid) {
            const baseUrl = typeof BASE_URL !== 'undefined' ? BASE_URL : '/agri_system/public/';
            grid.innerHTML = `
                <div class="empty-wishlist" id="emptyWishlist" style="grid-column: 1/-1; text-align: center; padding: 60px 20px;">
                    <div class="empty-icon" style="font-size: 80px; margin-bottom: 20px;">💔</div>
                    <h2 class="empty-title" style="font-size: 24px; color: #333; margin-bottom: 10px;">Your wishlist is empty</h2>
                    <p class="empty-message" style="font-size: 16px; color: #666; margin-bottom: 30px;">Start adding products you love to your wishlist!</p>
                    <a href="${baseUrl}marketplace" class="browse-btn" style="display: inline-block; padding: 12px 30px; background: #2d5016; color: white; text-decoration: none; border-radius: 8px; font-weight: 600; transition: all 0.3s;">
                        Browse Products
                    </a>
                </div>
            `;
        }

        if (header) {
            header.style.display = 'none';
        }
    }

    function removeCardWithAnimation(card) {
        card.classList.add('removing');
        setTimeout(() => {
            card.remove();
            updateWishlistCount();
        }, 300);
    }

    // event handlers
    async function handleRemoveItem(event) {
        const card = event.target.closest('.wishlist-card');
        if (!card) return;

        const wishlistID = card.getAttribute('data-wishlist-id');
        const productID = card.getAttribute('data-product-id');

        console.log('🗑️ Removing item:', { wishlistID, productID });

        if (!confirm('Remove this item from your wishlist?')) {
            return;
        }

        const button = event.target;
        button.disabled = true;

        const result = await wishlistAction('remove', {
            wishlistID: parseInt(wishlistID, 10),
            productID: parseInt(productID, 10)
        });

        if (result.success) {
            showNotification(result.message || 'Removed from wishlist', 'success');
            removeCardWithAnimation(card);
        } else {
            showNotification(result.message || 'Failed to remove item', 'error');
            button.disabled = false;
        }
    }

    async function handleAddToCart(event) {
        const card = event.target.closest('.wishlist-card');
        if (!card) return;

        const wishlistID = card.getAttribute('data-wishlist-id');
        const productID = card.getAttribute('data-product-id');
        const button = event.target;

        console.log('🛒 Adding to cart:', { wishlistID, productID });

        button.disabled = true;
        button.classList.add('btn-loading');

        const result = await wishlistAction('move_to_cart', {
            wishlistID: parseInt(wishlistID, 10),
            productID: parseInt(productID, 10)
        });

        button.disabled = false;
        button.classList.remove('btn-loading');

        if (result.success) {
            showNotification(result.message || 'Added to cart successfully', 'success');
            
            // Update cart badge in navigation
            if (result.cartCount !== undefined) {
                const cartBadges = document.querySelectorAll('.cart-btn .badge, [href*="cart"] .badge');
                cartBadges.forEach(badge => {
                    badge.textContent = result.cartCount;
                    badge.style.display = result.cartCount > 0 ? 'flex' : 'none';
                });
            }

            removeCardWithAnimation(card);
        } else {
            showNotification(result.message || 'Failed to add to cart', 'error');
        }
    }

    async function handleClearAll() {
        if (!confirm('Are you sure you want to clear your entire wishlist?')) {
            return;
        }

        console.log('🗑️ Clearing all wishlist items');

        const button = document.getElementById('clearAllBtn');
        if (button) {
            button.disabled = true;
            button.classList.add('btn-loading');
        }

        showLoading(true);

        const result = await wishlistAction('clear_all');

        showLoading(false);
        
        if (button) {
            button.disabled = false;
            button.classList.remove('btn-loading');
        }

        if (result.success) {
            showNotification('Wishlist cleared successfully', 'success');
            
            // Remove all cards with animation
            const cards = document.querySelectorAll('.wishlist-card');
            cards.forEach((card, index) => {
                setTimeout(() => {
                    card.classList.add('removing');
                    setTimeout(() => {
                        card.remove();
                        if (index === cards.length - 1) {
                            showEmptyState();
                        }
                    }, 300);
                }, index * 50);
            });
        } else {
            showNotification(result.message || 'Failed to clear wishlist', 'error');
        }
    }

    async function handleAddAllToCart() {
        if (!confirm('Add all available items to cart?')) {
            return;
        }

        console.log('🛒 Adding all items to cart');

        const button = document.getElementById('addAllCartBtn');
        if (button) {
            button.disabled = true;
            button.classList.add('btn-loading');
        }

        showLoading(true);

        const result = await wishlistAction('add_all_to_cart');

        showLoading(false);
        
        if (button) {
            button.disabled = false;
            button.classList.remove('btn-loading');
        }

        if (result.success) {
            const added = result.added || 0;
            const failed = result.failed || 0;
            
            let message = `Added ${added} item${added !== 1 ? 's' : ''} to cart`;
            if (failed > 0) {
                message += ` (${failed} unavailable)`;
            }
            
            showNotification(message, 'success');
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showNotification(result.message || 'Failed to add items', 'error');
        }
    }

    // event listeners
    document.addEventListener('click', function(event) {
        const target = event.target;
        if (!target) return;
        // Remove button
        if (target.classList.contains('remove-btn') || 
            target.closest('[data-action="remove"]')) {
            event.preventDefault();
            event.stopPropagation();
            handleRemoveItem(event);
            return;
        }

        // Add to cart button
        if (target.classList.contains('wishlist-add-cart-btn') || 
            target.closest('[data-action="add-to-cart"]')) {
            event.preventDefault();
            event.stopPropagation();
            handleAddToCart(event);
            return;
        }

        // Clear all button
        if (target.id === 'clearAllBtn' || target.closest('#clearAllBtn')) {
            event.preventDefault();
            event.stopPropagation();
            handleClearAll();
            return;
        }

        // Add all to cart button
        if (target.id === 'addAllCartBtn' || target.closest('#addAllCartBtn')) {
            event.preventDefault();
            event.stopPropagation();
            handleAddAllToCart();
            return;
        }
    });

    // public API
    window.WishlistPageAPI = {
        removeFromWishlist: async (wishlistID, productID) => {
            return await wishlistAction('remove', { wishlistID, productID });
        },
        moveToCart: async (wishlistID, productID) => {
            return await wishlistAction('move_to_cart', { wishlistID, productID });
        },
        clearAll: async () => {
            return await wishlistAction('clear_all');
        },
        addAllToCart: async () => {
            return await wishlistAction('add_all_to_cart');
        }
    };

    console.log('✅ Wishlist page initialized');

})();