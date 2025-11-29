// Tab Switching
function switchTab(tabName) {
    // Remove active class from all tabs and content
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
    
    // Add active class to selected tab and content
    event.target.closest('.tab-btn').classList.add('active');
    document.getElementById(tabName + 'Tab').classList.add('active');
}

// Category Filter
function filterShopCategory(category) {
    // Update active filter button
    document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    
    // Filter products
    const products = document.querySelectorAll('.product-card');
    products.forEach(product => {
        if (category === 'all' || product.dataset.category === category) {
            product.style.display = 'block';
        } else {
            product.style.display = 'none';
        }
    });
}

// Toggle Follow
function toggleFollow(btn) {
    btn.classList.toggle('following');
    if (btn.classList.contains('following')) {
        btn.innerHTML = '<span>✓ Following</span>';
        showNotification('You are now following Mang Juan\'s Farm!');
    } else {
        btn.innerHTML = '<span>+ Follow</span>';
        showNotification('Unfollowed Mang Juan\'s Farm');
    }
}

// Message Shop
function messageShop() {
    alert('Message feature - Coming soon! You will be able to chat directly with the seller.');
}

// View Product
function viewProduct(productId) {
    // Navigate to product detail page
    window.location.href = '../marketplace/product.php?id=' + productId;
}

// Toggle Wishlist
function toggleWishlist(event, btn) {
    event.stopPropagation(); // Prevent card click
    btn.classList.toggle('active');
    
    if (btn.classList.contains('active')) {
        btn.textContent = '❤️';
        showNotification('Added to wishlist!');
    } else {
        btn.textContent = '🤍';
        showNotification('Removed from wishlist');
    }
}

// Show Notification
function showNotification(message) {
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 80px;
        right: 20px;
        background: #2d5016;
        color: white;
        padding: 15px 25px;
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        z-index: 10000;
        font-weight: 600;
        animation: slideIn 0.3s ease;
    `;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Initialize page
document.addEventListener('DOMContentLoaded', () => {
    console.log('Shop page loaded successfully! 🛍️');
});
