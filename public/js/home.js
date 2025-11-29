// Hamburger Menu
function toggleMenu() {
    const navwrap = document.getElementById('navlinks');
    const hamburger = document.querySelector('.hamburger');
    navwrap.classList.toggle('active');
    hamburger.classList.toggle('active');
}

// Close menu when clicking outside
document.addEventListener('click', (e) => {
    const navwrap = document.getElementById('navlinks');
    const hamburger = document.querySelector('.hamburger');
    const nav = document.querySelector('.main-nav');

    if (!nav.contains(e.target) && navwrap.classList.contains('active')) {
        navwrap.classList.remove('active');
        hamburger.classList.remove('active');
    }
});

// Close menu when window is resized to desktop view
window.addEventListener('resize', () => {
    if (window.innerWidth > 768) {
        const navwrap = document.getElementById('navlinks');
        const hamburger = document.querySelector('.hamburger');
        navwrap.classList.remove('active');
        hamburger.classList.remove('active');
    }
});

// Image slider
let currentSlide = 0;
const slider = document.getElementById('slider');
const cards = document.querySelectorAll('.card');
const totalSlides = cards.length;
const dotsContainer = document.getElementById('dots');

// Dots indicator ng slider
for (let i = 0; i < totalSlides; i++) {
    const dot = document.createElement('span');
    dot.classList.add('dot');
    dot.onclick = () => goToSlide(i);
    dotsContainer.appendChild(dot);
}

const dots = document.querySelectorAll('.dot');
function updateSlider() {
    slider.style.transform = `translateX(-${currentSlide * 100}%)`;
    dots.forEach((dot, index) => {
        dot.classList.toggle('active', index === currentSlide);
    });
}

function moveSlide(direction) {
    currentSlide += direction;
    if (currentSlide < 0) {
        currentSlide = totalSlides - 1;
    } else if (currentSlide >= totalSlides) {
        currentSlide = 0;
    }
    updateSlider();
}

function goToSlide(index) {
    currentSlide = index;
    updateSlider();
}

// Auto-play slider (optional)
let autoplayInterval;

function startAutoplay() {
    autoplayInterval = setInterval(() => {
        moveSlide(1);
    }, 5000);
}

function stopAutoplay() {
    clearInterval(autoplayInterval);
}

startAutoplay();
slider.addEventListener('mouseenter', stopAutoplay);
slider.addEventListener('mouseleave', startAutoplay);

document.addEventListener('keydown', (e) => {
    if (e.key === 'ArrowLeft') moveSlide(-1);
    if (e.key === 'ArrowRight') moveSlide(1);
});
let touchStartX = 0;
let touchEndX = 0;

slider.addEventListener('touchstart', (e) => {
    touchStartX = e.changedTouches[0].screenX;
});

slider.addEventListener('touchend', (e) => {
    touchEndX = e.changedTouches[0].screenX;
    handleSwipe();
});

function handleSwipe() {
    if (touchStartX - touchEndX > 50) {
        moveSlide(1);
    }
    if (touchEndX - touchStartX > 50) {
        moveSlide(-1);
    }
}
updateSlider();

// Navbar buttons
document.getElementById('home')?.addEventListener('click', () => {
    window.location.href = 'landing.php';
});

document.getElementById('marketplace')?.addEventListener('click', () => {
    window.location.href = 'marketplace/marketplace.php';
});

document.getElementById('about')?.addEventListener('click', () => {
    window.location.href = 'devs/about.php';
});

document.getElementById('edhub')?.addEventListener('click', () => {
    window.location.href = 'devs/edhub.php';
});

document.getElementById('login')?.addEventListener('click', () => {
    window.location.href = 'auth/login.php';
});

// Landing page buttons
document.querySelector('.landing-btn1')?.addEventListener('click', () => {
    window.location.href = 'auth/register.php';
});

document.querySelector('.landing-btn2')?.addEventListener('click', () => {
    window.location.href = 'marketplace/marketplace.php';
});

// Footer buttons
document.querySelectorAll('.ftr-btn').forEach(btn => {
    btn.addEventListener('click', (e) => {
        const btnId = e.target.id;
        
        switch(btnId) {
            case 'Home':
                window.location.href = 'landing.php';
                break;
            case 'Terms':
                window.location.href = 'devs/terms.php';
                break;
            case 'Policy':
                window.location.href = 'devs/privacy.php';
                break;
            case 'Contact':
                window.location.href = 'devs/contacts.php';
                break;
        }
    });
});

// smooth scrolling
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Add scroll to top functionality
window.addEventListener('scroll', () => {
    const scrollTop = document.documentElement.scrollTop;
    
    // Add shadow to navbar on scroll
    const navbar = document.querySelector('.main-nav');
    if (scrollTop > 50) {
        navbar.style.boxShadow = '0 4px 8px rgba(0, 0, 0, 0.3)';
    } else {
        navbar.style.boxShadow = '0 2px 4px rgba(0, 0, 0, 0.5)';
    }
});

// BOX ANIMATION ON SCROLL (Optional Enhancement)
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
        }
    });
}, observerOptions);

// Observe all boxes
document.querySelectorAll('.box').forEach(box => {
    box.style.opacity = '0';
    box.style.transform = 'translateY(20px)';
    box.style.transition = 'all 0.6s ease';
    observer.observe(box);
});