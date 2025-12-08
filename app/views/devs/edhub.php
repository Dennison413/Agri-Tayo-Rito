<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Educational Hub - Agri Tayo Rito</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f5f5 0%, #e8f5e9 100%);
            min-height: 100vh;
        }

        .main-nav {
            background-color: #2d5016;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo-section img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            border: 3px solid white;
        }

        .logo-section h1 {
            color: white;
            font-size: 1.5rem;
        }

        .nav-links {
            display: flex;
            gap: 15px;
        }

        .nav-btn {
            background: white;
            color: #2d5016;
            border: none;
            padding: 10px 20px;
            border-radius: 20px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .nav-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        .hero {
            background: linear-gradient(135deg, #2d5016 0%, #4a7c25 100%);
            color: white;
            padding: 80px 20px 60px;
            text-align: center;
        }

        .hero h1 {
            font-size: 3rem;
            margin-bottom: 15px;
            animation: fadeInDown 0.8s ease;
        }

        .hero p {
            font-size: 1.2rem;
            opacity: 0.9;
            max-width: 700px;
            margin: 0 auto;
            animation: fadeInUp 0.8s ease;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 60px 20px;
        }

        .section-header {
            text-align: center;
            margin-bottom: 50px;
        }

        .section-title {
            color: #2d5016;
            font-size: 2.5rem;
            margin-bottom: 15px;
            position: relative;
            display: inline-block;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: linear-gradient(90deg, #2d5016, #4a7c25);
            border-radius: 2px;
        }

        .section-description {
            color: #666;
            font-size: 1.1rem;
            max-width: 700px;
            margin: 20px auto 0;
            line-height: 1.8;
        }

        .tutorial-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
            margin-bottom: 60px;
        }

        .tutorial-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.4s;
            position: relative;
        }

        .tutorial-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, #2d5016, #4a7c25);
        }

        .tutorial-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(45, 80, 22, 0.2);
        }

        .video-container {
            position: relative;
            padding-bottom: 56.25%; /* 16:9 aspect ratio */
            height: 0;
            overflow: hidden;
            background: #000;
        }

        .video-container video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .video-placeholder {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #2d5016 0%, #4a7c25 100%);
            color: white;
            font-size: 3rem;
        }

        .tutorial-content {
            padding: 25px;
        }

        .tutorial-category {
            display: inline-block;
            background: #e8f5e9;
            color: #2d5016;
            padding: 5px 15px;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .tutorial-title {
            color: #2d5016;
            font-size: 1.4rem;
            margin-bottom: 12px;
            font-weight: 700;
        }

        .tutorial-description {
            color: #666;
            line-height: 1.7;
            margin-bottom: 15px;
            font-size: 0.95rem;
        }

        .tutorial-meta {
            display: flex;
            gap: 20px;
            padding-top: 15px;
            border-top: 2px solid #f0f0f0;
            color: #888;
            font-size: 0.9rem;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .quick-tips {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 60px;
        }

        .tips-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }

        .tip-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 25px;
            border-radius: 15px;
            border-left: 5px solid #2d5016;
            transition: all 0.3s;
        }

        .tip-card:hover {
            transform: translateX(10px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }

        .tip-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }

        .tip-title {
            color: #2d5016;
            font-size: 1.2rem;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .tip-text {
            color: #666;
            line-height: 1.7;
            font-size: 0.95rem;
        }

        .resources-section {
            background: linear-gradient(135deg, #2d5016 0%, #4a7c25 100%);
            color: white;
            padding: 60px 40px;
            border-radius: 20px;
            margin-bottom: 60px;
            text-align: center;
        }

        .resources-section h2 {
            font-size: 2.2rem;
            margin-bottom: 20px;
        }

        .resources-section p {
            font-size: 1.1rem;
            margin-bottom: 30px;
            opacity: 0.9;
        }

        .resource-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .resource-btn {
            background: white;
            color: #2d5016;
            border: none;
            padding: 15px 30px;
            border-radius: 25px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .resource-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        }

        .faq-section {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .faq-item {
            border-bottom: 2px solid #f0f0f0;
            padding: 20px 0;
        }

        .faq-item:last-child {
            border-bottom: none;
        }

        .faq-question {
            color: #2d5016;
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .faq-question:hover {
            color: #4a7c25;
        }

        .faq-answer {
            color: #666;
            line-height: 1.8;
            padding-left: 30px;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }

        .faq-answer.active {
            max-height: 500px;
            margin-top: 10px;
        }

        .faq-icon {
            transition: transform 0.3s;
        }

        .faq-icon.rotate {
            transform: rotate(180deg);
        }

        footer {
            background: #2d5016;
            color: white;
            padding: 30px 20px;
            text-align: center;
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2rem;
            }

            .hero p {
                font-size: 1rem;
            }

            .section-title {
                font-size: 2rem;
            }

            .tutorial-grid {
                grid-template-columns: 1fr;
            }

            .nav-links {
                flex-direction: column;
                gap: 10px;
            }

            .logo-section h1 {
                font-size: 1.2rem;
            }

            .quick-tips,
            .faq-section {
                padding: 25px;
            }

            .resources-section {
                padding: 40px 25px;
            }

            .resource-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <nav class="main-nav">
        <div class="logo-section">
            <img src="/agri_system/public/images/logo.jpg" alt="Logo">
            <h1>Agri Tayo Rito</h1>
        </div>
        <div class="nav-links">
            <button class="nav-btn" onclick="window.location.href='/agri_system/public/'">Home</button>
            <button class="nav-btn" onclick="window.location.href='/agri_system/public/marketplace'">Marketplace</button>
        </div>
    </nav>

    <section class="hero">
        <h1>📚 Educational Hub</h1>
        <p>Learn how to buy, sell, and thrive on Agri Tayo Rito with our comprehensive tutorials and guides</p>
    </section>

    <div class="container">
        <!-- Video Tutorials Section -->
        <div class="section-header">
            <h2 class="section-title">🎥 Video Tutorials</h2>
            <p class="section-description">
                Watch step-by-step guides to help you get started and make the most of our platform
            </p>
        </div>

        <div class="tutorial-grid">
            <!-- Tutorial 1: Getting Started -->
            <div class="tutorial-card">
                <div class="video-container">
                    <!-- Replace src with your actual video URL -->
                    <video controls poster="/agri_system/public/images/tutorial-thumbnails/getting-started.jpg">
                        <source src="/agri_system/public/videos/getting-started.mp4" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                    <!-- Placeholder if no video -->
                    <div class="video-placeholder">🎬</div>
                </div>
                <div class="tutorial-content">
                    <span class="tutorial-category">Getting Started</span>
                    <h3 class="tutorial-title">How to Create Your Account</h3>
                    <p class="tutorial-description">
                        Learn how to register, set up your profile, and start exploring fresh produce from local farmers.
                    </p>
                    <div class="tutorial-meta">
                        <div class="meta-item">⏱️ 5 min</div>
                        <div class="meta-item">👁️ 1.2K views</div>
                    </div>
                </div>
            </div>

            <!-- Tutorial 2: Buying Guide -->
            <div class="tutorial-card">
                <div class="video-container">
                    <video controls poster="/agri_system/public/images/tutorial-thumbnails/buying-guide.jpg">
                        <source src="/agri_system/public/videos/buying-guide.mp4" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                    <div class="video-placeholder">🛒</div>
                </div>
                <div class="tutorial-content">
                    <span class="tutorial-category">For Buyers</span>
                    <h3 class="tutorial-title">How to Purchase Products</h3>
                    <p class="tutorial-description">
                        A complete guide to browsing products, adding to cart, and completing your first order.
                    </p>
                    <div class="tutorial-meta">
                        <div class="meta-item">⏱️ 8 min</div>
                        <div class="meta-item">👁️ 2.5K views</div>
                    </div>
                </div>
            </div>

            <!-- Tutorial 3: Seller Setup -->
            <div class="tutorial-card">
                <div class="video-container">
                    <video controls poster="/agri_system/public/images/tutorial-thumbnails/seller-setup.jpg">
                        <source src="/agri_system/public/videos/seller-setup.mp4" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                    <div class="video-placeholder">🏪</div>
                </div>
                <div class="tutorial-content">
                    <span class="tutorial-category">For Sellers</span>
                    <h3 class="tutorial-title">Setting Up Your Seller Account</h3>
                    <p class="tutorial-description">
                        Step-by-step instructions on applying as a seller and creating your first shop.
                    </p>
                    <div class="tutorial-meta">
                        <div class="meta-item">⏱️ 12 min</div>
                        <div class="meta-item">👁️ 890 views</div>
                    </div>
                </div>
            </div>

            <!-- Tutorial 4: Product Listing -->
            <div class="tutorial-card">
                <div class="video-container">
                    <video controls poster="/agri_system/public/images/tutorial-thumbnails/product-listing.jpg">
                        <source src="/agri_system/public/videos/product-listing.mp4" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                    <div class="video-placeholder">📦</div>
                </div>
                <div class="tutorial-content">
                    <span class="tutorial-category">For Sellers</span>
                    <h3 class="tutorial-title">How to List Your Products</h3>
                    <p class="tutorial-description">
                        Learn how to add products, upload photos, set prices, and manage your inventory.
                    </p>
                    <div class="tutorial-meta">
                        <div class="meta-item">⏱️ 10 min</div>
                        <div class="meta-item">👁️ 1.8K views</div>
                    </div>
                </div>
            </div>

            <!-- Tutorial 5: Order Management -->
            <div class="tutorial-card">
                <div class="video-container">
                    <video controls poster="/agri_system/public/images/tutorial-thumbnails/order-management.jpg">
                        <source src="/agri_system/public/videos/order-management.mp4" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                    <div class="video-placeholder">📋</div>
                </div>
                <div class="tutorial-content">
                    <span class="tutorial-category">For Sellers</span>
                    <h3 class="tutorial-title">Managing Orders & Deliveries</h3>
                    <p class="tutorial-description">
                        Learn how to process orders, coordinate with delivery riders, and handle customer requests.
                    </p>
                    <div class="tutorial-meta">
                        <div class="meta-item">⏱️ 15 min</div>
                        <div class="meta-item">👁️ 650 views</div>
                    </div>
                </div>
            </div>

            <!-- Tutorial 6: Payment & Withdrawals -->
            <div class="tutorial-card">
                <div class="video-container">
                    <video controls poster="/agri_system/public/images/tutorial-thumbnails/payments.jpg">
                        <source src="/agri_system/public/videos/payments.mp4" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                    <div class="video-placeholder">💰</div>
                </div>
                <div class="tutorial-content">
                    <span class="tutorial-category">For Sellers</span>
                    <h3 class="tutorial-title">Payment Processing & Withdrawals</h3>
                    <p class="tutorial-description">
                        Understand how payments work and how to withdraw your earnings safely and efficiently.
                    </p>
                    <div class="tutorial-meta">
                        <div class="meta-item">⏱️ 7 min</div>
                        <div class="meta-item">👁️ 980 views</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Tips Section -->
        <div class="quick-tips">
            <div class="section-header">
                <h2 class="section-title">💡 Quick Tips</h2>
                <p class="section-description">
                    Essential tips and best practices to enhance your experience
                </p>
            </div>

            <div class="tips-grid">
                <div class="tip-card">
                    <div class="tip-icon">🌱</div>
                    <h4 class="tip-title">Quality Over Quantity</h4>
                    <p class="tip-text">
                        Focus on high-quality products with clear descriptions and good photos to attract more buyers.
                    </p>
                </div>

                <div class="tip-card">
                    <div class="tip-icon">📸</div>
                    <h4 class="tip-title">Great Product Photos</h4>
                    <p class="tip-text">
                        Use natural lighting and multiple angles to showcase your products professionally.
                    </p>
                </div>

                <div class="tip-card">
                    <div class="tip-icon">💬</div>
                    <h4 class="tip-title">Communicate Promptly</h4>
                    <p class="tip-text">
                        Respond to buyer inquiries quickly to build trust and increase sales.
                    </p>
                </div>

                <div class="tip-card">
                    <div class="tip-icon">📦</div>
                    <h4 class="tip-title">Package with Care</h4>
                    <p class="tip-text">
                        Ensure products are properly packaged to maintain freshness during delivery.
                    </p>
                </div>

                <div class="tip-card">
                    <div class="tip-icon">⭐</div>
                    <h4 class="tip-title">Build Your Reputation</h4>
                    <p class="tip-text">
                        Encourage satisfied customers to leave reviews to boost your credibility.
                    </p>
                </div>

                <div class="tip-card">
                    <div class="tip-icon">🔄</div>
                    <h4 class="tip-title">Keep Stock Updated</h4>
                    <p class="tip-text">
                        Regularly update your inventory to avoid disappointing customers with out-of-stock items.
                    </p>
                </div>
            </div>
        </div>

        <!-- Additional Resources -->
        <div class="resources-section">
            <h2>📖 Additional Resources</h2>
            <p>Download guides, join our community, and access more helpful materials</p>
            <div class="resource-buttons">
                <a href="#" class="resource-btn">📄 Download Seller Guide PDF</a>
                <a href="#" class="resource-btn">📄 Download Buyer Guide PDF</a>
                <a href="#" class="resource-btn">💬 Join Community Forum</a>
                <a href="/agri_system/public/devs/contacts" class="resource-btn">📧 Contact Support</a>
            </div>
        </div>

        <!-- FAQ Section -->
        <div class="faq-section">
            <div class="section-header">
                <h2 class="section-title">❓ Frequently Asked Questions</h2>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <span class="faq-icon">▼</span>
                    How do I become a seller on Agri Tayo Rito?
                </div>
                <div class="faq-answer">
                    First, register as a buyer. Then, navigate to your profile and click "Apply as Seller". 
                    Complete the application form with your business details and wait for admin approval. 
                    Once approved, you can start listing products.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <span class="faq-icon">▼</span>
                    What payment methods are accepted?
                </div>
                <div class="faq-answer">
                    We accept major credit/debit cards, GCash, PayMaya, and bank transfers. All payments 
                    are processed securely through our payment gateway.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <span class="faq-icon">▼</span>
                    How long does delivery take?
                </div>
                <div class="faq-answer">
                    Delivery times vary depending on your location and the seller. Most orders arrive 
                    within 2-5 business days. You can track your order status in real-time through your account.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <span class="faq-icon">▼</span>
                    Can I cancel or modify my order?
                </div>
                <div class="faq-answer">
                    Orders can be cancelled before the seller confirms them. Once confirmed, modifications 
                    may require contacting the seller directly. Check your order status in "My Orders".
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <span class="faq-icon">▼</span>
                    How do I withdraw my earnings as a seller?
                </div>
                <div class="faq-answer">
                    Navigate to your seller dashboard and click "Withdrawals". Enter your bank account 
                    details and the amount you wish to withdraw. Funds typically arrive within 3-5 business days.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <span class="faq-icon">▼</span>
                    What should I do if I receive a damaged product?
                </div>
                <div class="faq-answer">
                    Contact the seller immediately through the messaging system and provide photos of 
                    the damaged product. If the issue is not resolved, contact our support team at 
                    agritayorito@gmail.com within 24 hours of delivery.
                </div>
            </div>
        </div>
    </div>

    <footer>
        <p>&copy; 2025 Agri Tayo Rito. All rights reserved.</p>
        <p>Fresh from Farm to Table</p>
    </footer>

    <script>
        // FAQ Toggle Function
        function toggleFaq(element) {
            const answer = element.nextElementSibling;
            const icon = element.querySelector('.faq-icon');
            const isActive = answer.classList.contains('active');

            // Close all other FAQs
            document.querySelectorAll('.faq-answer').forEach(item => {
                item.classList.remove('active');
            });
            document.querySelectorAll('.faq-icon').forEach(item => {
                item.classList.remove('rotate');
            });

            // Toggle current FAQ
            if (!isActive) {
                answer.classList.add('active');
                icon.classList.add('rotate');
            }
        }

        // Scroll Animation for Cards
        document.addEventListener('DOMContentLoaded', () => {
            const cards = document.querySelectorAll('.tutorial-card, .tip-card');
            
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

            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                card.style.transition = `all 0.6s ease ${index * 0.1}s`;
                observer.observe(card);
            });
        });

        // Video play tracking (optional)
        document.querySelectorAll('video').forEach(video => {
            video.addEventListener('play', function() {
                console.log('Video started:', this.closest('.tutorial-card').querySelector('.tutorial-title').textContent);
            });
        });
    </script>
</body>
</html>