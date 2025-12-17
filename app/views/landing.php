<?php
// Get categories from database
require_once BASE_PATH . '/app/models/Product.php';
require_once BASE_PATH . '/app/models/Category.php';
$categoryModel = new Category();
$featured_categories = $categoryModel->getAllCategories();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/jpg" href="/agri_system/public/images/agri-icon.jpg">
    <title>Agri Tayo Rito</title>
    <link rel="stylesheet" href="/agri_system/public/css/landing.css">
    <link rel="stylesheet" href="/agri_system/public/css/responsive.css">
</head>

<body>
    <header>
        <nav class="main-nav">
            <div class="left-nav-wrap">
                <div class="logo">
                    <img src="/agri_system/public/images/main-logo.jpg" alt="Logo">
                </div>
                <div class="name-title">
                    <h1>Agri Tayo Rito</h1>
                </div>
            </div>

            <div class="hamburger" onclick="toggleMenu()">
                <span></span>
                <span></span>
                <span></span>
            </div>

            <div class="navwrap" id="navlinks">
                <a href="/agri_system/public/"><button class="navbarbtn" id="home">Home</button></a>
                <a href="/agri_system/public/marketplace"><button class="navbarbtn" id="marketplace">Marketplace</button></a>
                <a href="/agri_system/public/devs/about"><button class="navbarbtn" id="about">About</button></a>
                <a href="/agri_system/public/devs/edhub"><button class="navbarbtn" id="edhub">EdHub</button></a>
                <a href="/agri_system/public/auth/login"><button class="navbarbtn" id="login" style="background-color: #ffff; color: #2d5016;">Log In</button></a>
            </div>
        </nav>
    </header>

    <main>
        <div class="main-content">
            <div class="welcomeinfo">
                <h1>Fresh from Farm to Table</h1>
                <p>Connect directly with local farmers. Get fresh produce delivered to your doorstep.</p>
                <a href="/agri_system/public/auth/register"><button class="landing-btn1">Get Started</button></a>
                <a href="/agri_system/public/marketplace"><button class="landing-btn2">View Marketplace</button></a> <!-- Users can visit the marketplace without registering, but cannot order -->    
            </div>

            <div class="subcontainer">
                <!-- Section 1: Featured Categories Slider -->
                <div class="subcontents">
                    <h1>Featured Categories</h1>
                    <div class="slider-wrap">
                        <button class="slider-btn prev-btn" onclick="moveSlide(-1)">❮</button>
                        <div class="slider" id="slider">
                            <?php foreach ($featured_categories as $cat): ?>
                                <div class="card">
                                    <img src="/agri_system/public/images/<?php echo strtolower($cat['category']); ?>.jpg" alt="<?php echo htmlspecialchars($cat['category']); ?>">
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button class="slider-btn next-btn" onclick="moveSlide(1)">❯</button>
                    </div>
                    <div class="slider-dots" id="dots"></div>
                </div>

                <!-- Section 2: Why Choose Agri Tayo Rito? -->
                <div class="subcontents">
                    <h1>Why Choose Agri Tayo Rito?</h1>
                    <div class="box">Direct Connection: Buy straight from local farmers.</div>
                    <div class="box">Freshness Guaranteed: Farm-fresh produce delivered to you.</div>
                    <div class="box">Support Local: Help sustain local agriculture and communities.</div>
                    <div class="box">Educational Resources: Access farming tips and guides.</div>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <div class="left-wrap">
            <div class="logo">
                <img src="/agri_system/public/images/main-logo.jpg" alt="Logo">
            </div>
            <div class="brand-title">
                <strong>Agri Tayo Rito</strong>
                <div class="tagline">Fresh from Farm to Table</div>
            </div>
        </div>
        <div class="right-wrap">
            <a href="/agri_system/public/"><button class="ftr-btn" id="Home">Home</button></a>
            <a href="/agri_system/public/devs/terms"><button class="ftr-btn" id="Terms">Terms of Service</button></a>
            <a href="/agri_system/public/devs/privacy"><button class="ftr-btn" id="Policy">Privacy Policy</button></a>
            <a href="/agri_system/public/devs/contacts"><button class="ftr-btn" id="Contact">Contact Us</button></a>
        </div>
        <hr>
        <div class="ftr-legal">
            <small>&copy; <?php echo date('Y'); ?> Agri Tayo Rito. All rights reserved.</small>
        </div>
    </footer>
    <script src="/agri_system/public/js/home.js"></script>
</body>
</html>