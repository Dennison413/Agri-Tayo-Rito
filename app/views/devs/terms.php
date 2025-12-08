<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms of Service - Agri Tayo Rito</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
        }

        .main-nav {
            background-color: #2d5016;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
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
            padding: 60px 20px;
            text-align: center;
        }

        .hero h1 {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }

        .hero p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .container {
            max-width: 900px;
            margin: -40px auto 60px;
            padding: 0 20px;
        }

        .terms-card {
            background: white;
            border-radius: 20px;
            padding: 50px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        }

        .last-updated {
            background: #e8f5e9;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: center;
            color: #2d5016;
            font-weight: 600;
        }

        .section {
            margin-bottom: 35px;
        }

        .section-title {
            color: #2d5016;
            font-size: 1.5rem;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 3px solid #e8f5e9;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-icon {
            font-size: 1.8rem;
        }

        .section p {
            color: #555;
            line-height: 1.8;
            margin-bottom: 15px;
            font-size: 1rem;
        }

        .section ul {
            margin-left: 30px;
            color: #555;
            line-height: 2;
        }

        .section ul li {
            margin-bottom: 10px;
        }

        .highlight-box {
            background: #fff3cd;
            border-left: 5px solid #ffc107;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
        }

        .highlight-box strong {
            color: #856404;
        }

        .accordion {
            margin-top: 40px;
        }

        .accordion-item {
            background: #f8f9fa;
            margin-bottom: 15px;
            border-radius: 10px;
            overflow: hidden;
            border: 2px solid #e0e0e0;
            transition: all 0.3s;
        }

        .accordion-item:hover {
            border-color: #2d5016;
        }

        .accordion-header {
            background: #2d5016;
            color: white;
            padding: 20px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 600;
            transition: all 0.3s;
        }

        .accordion-header:hover {
            background: #4a7c25;
        }

        .accordion-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
            padding: 0 20px;
        }

        .accordion-content.active {
            max-height: 1000px;
            padding: 20px;
        }

        .arrow {
            transition: transform 0.3s;
        }

        .arrow.rotate {
            transform: rotate(180deg);
        }

        footer {
            background: #2d5016;
            color: white;
            padding: 30px 20px;
            text-align: center;
        }

        @media (max-width: 768px) {
            .hero h1 {
                font-size: 1.8rem;
            }

            .terms-card {
                padding: 30px 20px;
            }

            .section-title {
                font-size: 1.2rem;
            }

            .nav-links {
                flex-direction: column;
                gap: 10px;
            }

            .logo-section h1 {
                font-size: 1.2rem;
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
        <h1>Terms of Service</h1>
        <p>Please read these terms carefully before using our platform</p>
    </section>

    <div class="container">
        <div class="terms-card">
            <div class="last-updated">
                📅 Last Updated: December 8, 2024
            </div>

            <div class="section">
                <h2 class="section-title">
                    <span class="section-icon">📋</span>
                    1. Acceptance of Terms
                </h2>
                <p>
                    By accessing and using Agri Tayo Rito ("the Platform"), you accept and agree to be bound by these Terms of Service. 
                    If you do not agree to these terms, please do not use our services.
                </p>
                <p>
                    These terms apply to all users of the Platform, including buyers, sellers, and visitors. We reserve the right to 
                    modify these terms at any time, and continued use of the Platform constitutes acceptance of any changes.
                </p>
            </div>

            <div class="section">
                <h2 class="section-title">
                    <span class="section-icon">👥</span>
                    2. User Accounts
                </h2>
                <p>To access certain features, you must create an account. You agree to:</p>
                <ul>
                    <li>Provide accurate, current, and complete information during registration</li>
                    <li>Maintain the security of your password and account</li>
                    <li>Notify us immediately of any unauthorized access or security breaches</li>
                    <li>Accept responsibility for all activities under your account</li>
                    <li>Not share your account credentials with others</li>
                </ul>
                <div class="highlight-box">
                    <strong>Important:</strong> You must be at least 18 years old to create an account and use our services.
                </div>
            </div>

            <div class="section">
                <h2 class="section-title">
                    <span class="section-icon">🛒</span>
                    3. Buyer Responsibilities
                </h2>
                <p>As a buyer on our platform, you agree to:</p>
                <ul>
                    <li>Make purchases in good faith and honor all confirmed orders</li>
                    <li>Provide accurate delivery information</li>
                    <li>Pay for all orders placed through your account</li>
                    <li>Communicate respectfully with sellers</li>
                    <li>Report any issues or disputes promptly through proper channels</li>
                    <li>Not misuse the review or rating system</li>
                </ul>
            </div>

            <div class="section">
                <h2 class="section-title">
                    <span class="section-icon">👨‍🌾</span>
                    4. Seller Responsibilities
                </h2>
                <p>Sellers on Agri Tayo Rito must:</p>
                <ul>
                    <li>Complete the seller application and verification process</li>
                    <li>Provide accurate product descriptions, images, and pricing</li>
                    <li>Maintain adequate stock levels or update product availability</li>
                    <li>Process orders promptly and ship within stated timeframes</li>
                    <li>Ensure products meet quality and safety standards</li>
                    <li>Respond to buyer inquiries in a timely manner</li>
                    <li>Comply with all applicable laws and regulations</li>
                </ul>
                <div class="highlight-box">
                    <strong>Note:</strong> Sellers found violating quality standards may have their accounts suspended or terminated.
                </div>
            </div>

            <div class="section">
                <h2 class="section-title">
                    <span class="section-icon">💳</span>
                    5. Payments and Fees
                </h2>
                <p>
                    All transactions are processed securely through our payment gateway. Prices are displayed in Philippine Peso (₱) 
                    and include applicable taxes unless stated otherwise.
                </p>
                <ul>
                    <li><strong>Buyers:</strong> Payment is required at checkout. We accept various payment methods including credit/debit cards and e-wallets</li>
                    <li><strong>Sellers:</strong> Platform commission fees apply to each successful transaction</li>
                    <li><strong>Refunds:</strong> Refund policies vary by seller; please review individual seller policies before purchasing</li>
                </ul>
            </div>

            <div class="section">
                <h2 class="section-title">
                    <span class="section-icon">🚚</span>
                    6. Delivery and Shipping
                </h2>
                <p>
                    Delivery times and shipping costs vary depending on the seller and your location. While we partner with reliable 
                    delivery services, Agri Tayo Rito is not responsible for delays caused by circumstances beyond our control.
                </p>
                <p>Buyers should:</p>
                <ul>
                    <li>Inspect products upon delivery</li>
                    <li>Report damaged or incorrect items within 24 hours</li>
                    <li>Provide accessible delivery addresses</li>
                </ul>
            </div>

            <div class="section">
                <h2 class="section-title">
                    <span class="section-icon">🚫</span>
                    7. Prohibited Activities
                </h2>
                <p>Users must not:</p>
                <ul>
                    <li>Sell or attempt to purchase illegal or prohibited products</li>
                    <li>Engage in fraudulent activities or transactions</li>
                    <li>Harass, abuse, or harm other users</li>
                    <li>Attempt to manipulate ratings, reviews, or search rankings</li>
                    <li>Use the Platform for any unlawful purpose</li>
                    <li>Scrape, data mine, or use automated tools to access the Platform</li>
                    <li>Impersonate other users or entities</li>
                </ul>
            </div>

            <div class="section">
                <h2 class="section-title">
                    <span class="section-icon">⚖️</span>
                    8. Intellectual Property
                </h2>
                <p>
                    All content on Agri Tayo Rito, including logos, designs, text, graphics, and software, is the property of 
                    Agri Tayo Rito or its licensors and is protected by copyright and intellectual property laws.
                </p>
                <p>
                    Users retain ownership of content they upload but grant us a license to use, display, and distribute such 
                    content for Platform operations.
                </p>
            </div>

            <div class="section">
                <h2 class="section-title">
                    <span class="section-icon">🛡️</span>
                    9. Limitation of Liability
                </h2>
                <p>
                    Agri Tayo Rito acts as a marketplace platform connecting buyers and sellers. We are not responsible for:
                </p>
                <ul>
                    <li>The quality, safety, or legality of products listed</li>
                    <li>The accuracy of product listings or seller information</li>
                    <li>Disputes between buyers and sellers</li>
                    <li>Delivery delays or product damages during shipping</li>
                    <li>Loss of data or service interruptions</li>
                </ul>
                <div class="highlight-box">
                    <strong>Disclaimer:</strong> Our Platform is provided "as is" without warranties of any kind, either express or implied.
                </div>
            </div>

            <div class="section">
                <h2 class="section-title">
                    <span class="section-icon">📞</span>
                    10. Dispute Resolution
                </h2>
                <p>
                    If disputes arise between users, we encourage resolution through direct communication. For unresolved issues, 
                    users may contact our support team at <strong>agritayorito@gmail.com</strong>.
                </p>
                <p>
                    By using our Platform, you agree that any legal disputes will be resolved through arbitration in accordance 
                    with Philippine law.
                </p>
            </div>

            <div class="section">
                <h2 class="section-title">
                    <span class="section-icon">🔄</span>
                    11. Termination
                </h2>
                <p>
                    We reserve the right to suspend or terminate accounts that violate these Terms of Service. Users may also 
                    close their accounts at any time by contacting support.
                </p>
                <p>
                    Upon termination, you lose access to the Platform and any associated benefits. Outstanding obligations 
                    (payments, orders) remain valid.
                </p>
            </div>

            <div class="accordion">
                <div class="accordion-item">
                    <div class="accordion-header" onclick="toggleAccordion(this)">
                        <span>📌 Frequently Asked Questions</span>
                        <span class="arrow">▼</span>
                    </div>
                    <div class="accordion-content">
                        <p><strong>Q: How do I become a seller?</strong></p>
                        <p>A: Register as a buyer first, then apply through the "Apply as Seller" option in your profile. Complete the verification process to start selling.</p>
                        
                        <p><strong>Q: What payment methods are accepted?</strong></p>
                        <p>A: We accept major credit/debit cards, GCash, PayMaya, and bank transfers.</p>
                        
                        <p><strong>Q: How long does delivery take?</strong></p>
                        <p>A: Delivery times vary by location and seller. Most orders arrive within 2-5 business days.</p>
                    </div>
                </div>

                <div class="accordion-item">
                    <div class="accordion-header" onclick="toggleAccordion(this)">
                        <span>📧 Contact Support</span>
                        <span class="arrow">▼</span>
                    </div>
                    <div class="accordion-content">
                        <p>For questions or concerns about these Terms of Service, please contact us:</p>
                        <p><strong>Email:</strong> agritayorito@gmail.com</p>
                        <p><strong>Business Hours:</strong> Monday - Friday, 8:00 AM - 5:00 PM</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <p>&copy; 2025 Agri Tayo Rito. All rights reserved.</p>
        <p>Fresh from Farm to Table</p>
    </footer>

    <script>
        function toggleAccordion(header) {
            const content = header.nextElementSibling;
            const arrow = header.querySelector('.arrow');
            const isActive = content.classList.contains('active');

            // Close all accordions
            document.querySelectorAll('.accordion-content').forEach(item => {
                item.classList.remove('active');
            });
            document.querySelectorAll('.arrow').forEach(item => {
                item.classList.remove('rotate');
            });

            // Open clicked accordion if it wasn't active
            if (!isActive) {
                content.classList.add('active');
                arrow.classList.add('rotate');
            }
        }

        // Smooth scroll to sections
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth' });
                }
            });
        });
    </script>
</body>
</html>