// public/js/shop.js
function switchTab(tabName) {
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
    
    event.target.closest('.tab-btn').classList.add('active');
    document.getElementById(tabName + 'Tab').classList.add('active');
}

// category Filter
function filterShopCategory(category) {
    document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');

    const products = document.querySelectorAll('.product-card');
    products.forEach(product => {
        if (category === 'all' || product.dataset.category === category) {
            product.style.display = 'block';
        } else {
            product.style.display = 'none';
        }
    });
}

// toggle Follow
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

// message Shop
function messageShop() {
    alert('Message feature - Coming soon! You will be able to chat directly with the seller.');
}

// view Product
function viewProduct(productId) {
    window.location.href = '../marketplace/product.php?id=' + productId;
}

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
