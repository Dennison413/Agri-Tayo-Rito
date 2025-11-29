// marketplace.js - PRODUCTION READY with CSRF & Error Handling
// ========================================
// CONFIGURATION
// ========================================
const API_BASE = '/agri_system/app/controllers/';
const ENDPOINTS = {
    cart: `${API_BASE}CartController.php`,
    wishlist: `${API_BASE}WishlistController.php`
};

// Get CSRF token
function getCSRFToken() {
    return window.marketplaceData?.csrfToken || 
           document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || 
           '';
}

// ========================================
// ADD TO CART - SECURED
// ========================================
async function addToCart(event, productId) {
    event.stopPropagation();
    
    // Check login status
    if (!window.marketplaceData?.isLoggedIn) {
        promptLogin(event);
        return;
    }
    
    const button = event.target;
    const originalText = button.textContent;
    button.disabled = true;
    button.textContent = '⏳ Adding...';
    
    try {
        const response = await fetch(ENDPOINTS.cart, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                action: 'add',
                productID: parseInt(productId),
                quantity: 1,
                csrf_token: getCSRFToken()
            })
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const result = await response.json();
        console.log('✅ Add to cart response:', result);
        
        if (result.success) {
            // Update CSRF token if provided
            if (result.csrf_token) {
                updateCSRFToken(result.csrf_token);
            }
            
            // Update cart count
            updateCartBadges(result.cartCount || 0);
            
            // Show success
            showNotification('✅ Added to cart!', 'success');
            
            // Visual feedback
            button.textContent = '✓ Added';
            button.classList.add('btn-success');
            
            setTimeout(() => {
                button.textContent = originalText;
                button.classList.remove('btn-success');
                button.disabled = false;
            }, 1500);
        } else {
            // Handle failure
            button.textContent = originalText;
            button.disabled = false;
            showNotification(result.message || 'Failed to add item', 'error');
            
            // If unauthorized, redirect to login
            if (result.redirect) {
                setTimeout(() => window.location.href = result.redirect, 1500);
            }
        }
    } catch (error) {
        console.error('❌ Cart error:', error);
        button.textContent = originalText;
        button.disabled = false;
        showNotification('Network error. Please try again.', 'error');
    }
}

// Update cart count badges
function updateCartBadges(count) {
    const badges = document.querySelectorAll('#cartBadge, .cart-btn .badge, .bottom-nav .cart-badge');
    badges.forEach(badge => {
        badge.textContent = count;
        badge.style.display = count > 0 ? 'inline-block' : 'none';
    });
    
    // Update global data
    if (window.marketplaceData) {
        window.marketplaceData.cartCount = count;
    }
}

// ========================================
// WISHLIST - SECURED
// ========================================
async function toggleWishlist(event, productId) {
    event.stopPropagation();
    
    // Check login status
    if (!window.marketplaceData?.isLoggedIn) {
        promptLogin(event);
        return;
    }
    
    // Find the button (can be called with button or productId)
    const button = event.target.closest('.wishlist-btn');
    if (!button) return;
    
    button.disabled = true;
    const originalText = button.textContent;
    button.textContent = '⏳';
    
    try {
        const response = await fetch(ENDPOINTS.wishlist, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                action: 'toggle',
                productID: parseInt(productId),
                csrf_token: getCSRFToken()
            })
        });
        
        const result = await response.json();
        console.log('✅ Wishlist response:', result);
        
        if (result.success) {
            // Update CSRF token
            if (result.csrf_token) {
                updateCSRFToken(result.csrf_token);
            }
            
            // Update button state
            button.textContent = result.inWishlist ? '❤️' : '🤍';
            button.classList.toggle('active', result.inWishlist);
            button.title = result.inWishlist ? 'Remove from Wishlist' : 'Add to Wishlist';
            
            // Update wishlist count
            updateWishlistBadges(result.wishlistCount || 0);
            
            // Show notification
            showNotification(result.message, 'success');
        } else {
            button.textContent = originalText;
            showNotification(result.message || 'Failed to update wishlist', 'error');
        }
    } catch (error) {
        console.error('❌ Wishlist error:', error);
        button.textContent = originalText;
        showNotification('Network error occurred', 'error');
    } finally {
        button.disabled = false;
    }
}

// Update wishlist count badges
function updateWishlistBadges(count) {
    const badges = document.querySelectorAll('.wishlist-badge, .nav-badge[data-type="wishlist"]');
    badges.forEach(badge => {
        badge.textContent = count;
        badge.style.display = count > 0 ? 'inline-block' : 'none';
    });
    
    // Update global data
    if (window.marketplaceData) {
        window.marketplaceData.wishlistCount = count;
    }
}

// ========================================
// LOAD WISHLIST STATE (ON PAGE LOAD)
// ========================================
async function loadWishlistState() {
    if (!window.marketplaceData?.isLoggedIn || window.marketplaceData?.userRole !== 'buyer') {
        return;
    }
    
    const wishlistBtns = document.querySelectorAll('.wishlist-btn[data-product-id]');
    
    if (wishlistBtns.length === 0) {
        console.log('📝 No wishlist buttons found on page');
        return;
    }
    
    console.log(`🔄 Loading wishlist state for ${wishlistBtns.length} products`);
    
    // Get all product IDs from the page
    const productIds = Array.from(wishlistBtns).map(btn => parseInt(btn.getAttribute('data-product-id')));
    
    try {
        // ✅ FIX: Batch check all products in ONE request
        const response = await fetch(ENDPOINTS.wishlist, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                action: 'check_multiple', // New batch action
                productIDs: productIds,
                csrf_token: getCSRFToken()
            })
        });
        
        const result = await response.json();
        
        if (result.success && result.wishlistItems) {
            // Update buttons based on wishlist items
            wishlistBtns.forEach(btn => {
                const productId = parseInt(btn.getAttribute('data-product-id'));
                if (result.wishlistItems.includes(productId)) {
                    btn.textContent = '❤️';
                    btn.classList.add('active');
                    btn.title = 'Remove from Wishlist';
                }
            });
            
            console.log(`✅ Loaded wishlist state: ${result.wishlistItems.length} items in wishlist`);
        }
    } catch (error) {
        console.error('❌ Error loading wishlist state:', error);
        // Fail silently - wishlist icons will just show as not-wishlisted
    }
}

// ========================================
// CATEGORY FILTERING
// ========================================
function filterByCategory(categoryId) {
    const url = new URL(window.location.href);
    
    if (categoryId === null || categoryId === 'all') {
        url.searchParams.delete('category');
    } else {
        url.searchParams.set('category', categoryId);
    }
    
    // Reset to page 1 when filtering
    url.searchParams.delete('page');
    
    window.location.href = url.toString();
}

// ========================================
// SEARCH FUNCTIONALITY
// ========================================
function searchProducts() {
    const searchInput = document.getElementById('searchInput');
    if (!searchInput) return;
    
    const searchTerm = searchInput.value.trim();
    const url = new URL(window.location.href);
    
    if (searchTerm) {
        url.searchParams.set('search', searchTerm);
    } else {
        url.searchParams.delete('search');
    }
    
    // Reset to page 1 when searching
    url.searchParams.delete('page');
    
    window.location.href = url.toString();
}

// ========================================
// CATEGORY SLIDER
// ========================================
let currentSlideIndex = 0;

function slideCategories(direction) {
    const track = document.getElementById('categoryTrack');
    if (!track) return;
    
    const cards = track.querySelectorAll('.category-card');
    if (cards.length === 0) return;
    
    const cardWidth = cards[0].offsetWidth;
    const gap = 15;
    const moveDistance = cardWidth + gap;
    
    // Calculate cards per view based on screen width
    let cardsPerView = 4;
    if (window.innerWidth <= 1024) cardsPerView = 3;
    if (window.innerWidth <= 768) cardsPerView = 2;
    if (window.innerWidth <= 480) cardsPerView = 1;
    
    const maxSlides = Math.max(0, cards.length - cardsPerView);
    
    if (direction === 'next') {
        currentSlideIndex = Math.min(currentSlideIndex + 1, maxSlides);
    } else if (direction === 'prev') {
        currentSlideIndex = Math.max(currentSlideIndex - 1, 0);
    }
    
    const translateX = -(currentSlideIndex * moveDistance);
    track.style.transform = `translateX(${translateX}px)`;
}

// ========================================
// PRODUCT DETAIL VIEW
// ========================================
function viewProduct(productId) {
    window.location.href = `${window.marketplaceData.baseUrl}item-handling/product?id=${productId}`;
}

// Alias for compatibility
function viewProductDetail(productId) {
    viewProduct(productId);
}

// ========================================
// LOGIN PROMPT (GUEST USERS)
// ========================================
function promptLogin(event) {
    event.stopPropagation();
    
    const message = 'Please login to add items to your cart.\n\nWould you like to login now?';
    
    if (confirm(message)) {
        // Store intended action for after login
        sessionStorage.setItem('redirectAfterLogin', window.location.href);
        window.location.href = `${window.marketplaceData.baseUrl}auth/login`;
    }
}

// ========================================
// SIDEBAR TOGGLE
// ========================================
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar') || document.querySelector('.sidebar');
    const overlay = document.getElementById('overlay');
    
    if (sidebar) {
        sidebar.classList.toggle('active');
    }
    
    if (overlay) {
        overlay.classList.toggle('active');
    }
}

// ========================================
// NOTIFICATION SYSTEM
// ========================================
function showNotification(message, type = 'success') {
    // Remove existing notifications
    const existingNotif = document.querySelector('.notification-toast');
    if (existingNotif) {
        existingNotif.remove();
    }
    
    // Create notification
    const notification = document.createElement('div');
    notification.className = `notification-toast notification-${type}`;
    notification.textContent = message;
    document.body.appendChild(notification);
    
    // Show notification
    setTimeout(() => notification.classList.add('show'), 10);
    
    // Auto-hide after 3 seconds
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// ========================================
// CSRF TOKEN MANAGEMENT
// ========================================
function updateCSRFToken(newToken) {
    // Update meta tag
    const metaTag = document.querySelector('meta[name="csrf-token"]');
    if (metaTag) {
        metaTag.setAttribute('content', newToken);
    }
    
    // Update global data
    if (window.marketplaceData) {
        window.marketplaceData.csrfToken = newToken;
    }
    
    console.log('🔒 CSRF token updated');
}

// ========================================
// INITIALIZE ON PAGE LOAD
// ========================================
document.addEventListener('DOMContentLoaded', function() {
    console.log('🌾 Agri Tayo Rito Marketplace Initialized');
    console.log('📊 Data:', window.marketplaceData);
    
    // Load wishlist states for logged-in buyers
    if (window.marketplaceData?.isLoggedIn && window.marketplaceData?.userRole === 'buyer') {
        loadWishlistState();
    }
    
    // Search on Enter key
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                searchProducts();
            }
        });
    }
    
    // Slider resize handler
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
            currentSlideIndex = 0;
            const track = document.getElementById('categoryTrack');
            if (track) {
                track.style.transform = 'translateX(0)';
            }
        }, 250);
    });
    
    // Keyboard navigation for slider
    document.addEventListener('keydown', function(e) {
        // Only if no input is focused
        if (document.activeElement.tagName === 'INPUT') return;
        
        if (e.key === 'ArrowLeft') {
            slideCategories('prev');
        } else if (e.key === 'ArrowRight') {
            slideCategories('next');
        }
    });
    
    // Handle redirect after login
    const redirectUrl = sessionStorage.getItem('redirectAfterLogin');
    if (redirectUrl && window.marketplaceData?.isLoggedIn) {
        sessionStorage.removeItem('redirectAfterLogin');
        // User just logged in, already on the page they wanted
    }
    
    console.log('✅ Marketplace ready');
});

// ========================================
// INJECT NOTIFICATION STYLES
// ========================================
if (!document.getElementById('marketplace-notification-styles')) {
    const style = document.createElement('style');
    style.id = 'marketplace-notification-styles';
    style.textContent = `
        .notification-toast {
            position: fixed;
            bottom: 100px;
            left: 50%;
            transform: translateX(-50%) translateY(100px);
            background: #2d5016;
            color: white;
            padding: 12px 24px;
            border-radius: 25px;
            font-size: 0.9rem;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            z-index: 10000;
            opacity: 0;
            transition: all 0.3s ease;
            pointer-events: none;
        }
        
        .notification-toast.show {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }
        
        .notification-toast.notification-error {
            background: #dc3545;
        }
        
        .notification-toast.notification-warning {
            background: #ffc107;
            color: #333;
        }
        
        .notification-toast.notification-info {
            background: #17a2b8;
        }
        
        .wishlist-btn.active {
            animation: heartBeat 0.3s ease;
        }
        
        @keyframes heartBeat {
            0%, 100% { transform: scale(1); }
            25% { transform: scale(1.3); }
            50% { transform: scale(1.1); }
            75% { transform: scale(1.2); }
        }
        
        .btn-success {
            background: #28a745 !important;
        }
        
        .add-to-cart-btn:disabled,
        .wishlist-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        @media (max-width: 768px) {
            .notification-toast {
                bottom: 80px;
                font-size: 0.85rem;
                padding: 10px 20px;
                max-width: 90%;
            }
        }
    `;
    document.head.appendChild(style);
}