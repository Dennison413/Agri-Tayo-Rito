<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/jpg" href="/agri_system/public/images/agri-icon.jpg">
    <title>Agri Tayo Rito</title>
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

        .hero-section {
            background: linear-gradient(135deg, #2d5016 0%, #4a7c25 100%);
            color: white;
            padding: 80px 20px;
            text-align: center;
        }

        .hero-section h1 {
            font-size: 3rem;
            margin-bottom: 20px;
            animation: fadeInDown 0.8s ease;
        }

        .hero-section p {
            font-size: 1.3rem;
            max-width: 800px;
            margin: 0 auto;
            line-height: 1.8;
            animation: fadeInUp 0.8s ease;
        }

        .system-section {
            max-width: 1200px;
            margin: 60px auto;
            padding: 0 20px;
        }

        .system-card {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 40px;
        }

        .system-card h2 {
            color: #2d5016;
            font-size: 2rem;
            margin-bottom: 20px;
            text-align: center;
        }

        .system-card p {
            color: #333;
            font-size: 1.1rem;
            line-height: 1.8;
            margin-bottom: 15px;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .feature-box {
            background: linear-gradient(135deg, #f5f5f5 0%, #e8f5e9 100%);
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            transition: all 0.3s;
            border: 2px solid transparent;
        }

        .feature-box:hover {
            transform: translateY(-5px);
            border-color: #2d5016;
            box-shadow: 0 8px 20px rgba(45, 80, 22, 0.2);
        }

        .feature-icon {
            font-size: 3rem;
            margin-bottom: 15px;
        }

        .feature-box h3 {
            color: #2d5016;
            margin-bottom: 10px;
        }

        .feature-box p {
            color: #666;
            font-size: 0.95rem;
        }
        .team-section {
            max-width: 1400px;
            margin: 60px auto;
            padding: 0 20px;
        }

        .section-title {
            text-align: center;
            color: #2d5016;
            font-size: 2.5rem;
            margin-bottom: 50px;
            position: relative;
            padding-bottom: 15px;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 4px;
            background: linear-gradient(90deg, #2d5016, #4a7c25);
            border-radius: 2px;
        }

        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            margin-bottom: 60px;
        }

        .developer-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            transition: all 0.4s;
            position: relative;
            overflow: hidden;
        }

        .developer-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, #2d5016, #4a7c25);
        }

        .developer-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(45, 80, 22, 0.2);
        }

        .dev-photo {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            margin: 0 auto 20px;
            border: 5px solid #2d5016;
            object-fit: cover;
            transition: all 0.3s;
        }

        .developer-card:hover .dev-photo {
            transform: scale(1.05);
            border-color: #4a7c25;
        }

        .dev-name {
            color: #2d5016;
            font-size: 1.4rem;
            margin-bottom: 8px;
            font-weight: 700;
        }

        .dev-role {
            color: #666;
            font-size: 1rem;
            margin-bottom: 15px;
            font-style: italic;
        }

        .dev-description {
            color: #555;
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .contact-btn {
            background: #2d5016;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .contact-btn:hover {
            background: #4a7c25;
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(45, 80, 22, 0.3);
        }

        footer {
            background: #2d5016;
            color: white;
            padding: 40px 20px;
            text-align: center;
            margin-top: 60px;
        }

        footer p {
            margin-bottom: 10px;
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
            .hero-section h1 {
                font-size: 2rem;
            }

            .hero-section p {
                font-size: 1rem;
            }

            .nav-links {
                flex-direction: column;
                gap: 10px;
            }

            .logo-section h1 {
                font-size: 1.2rem;
            }

            .section-title {
                font-size: 2rem;
            }

            .team-grid {
                grid-template-columns: 1fr;
            }

            .system-card {
                padding: 25px;
            }
        }
    </style>
</head>
<body>
    <nav class="main-nav">
        <div class="logo-section">
            <img src="/agri_system/public/images/main-logo.jpg" alt="Logo">
            <h1>Agri Tayo Rito</h1>
        </div>
        <div class="nav-links">
            <button class="nav-btn" onclick="window.location.href='/agri_system/public/'">Home</button>
            <button class="nav-btn" onclick="window.location.href='/agri_system/public/marketplace'">Marketplace</button>
        </div>
    </nav>

    <section class="hero-section">
        <h1>About Agri Tayo Rito</h1>
        <p>Connecting local farmers with consumers for fresher produce and sustainable agriculture</p>
    </section>

    <section class="system-section">
        <div class="system-card">
            <h2>🌾 Our Mission</h2>
            <p>
                Agri Tayo Rito is a revolutionary farm-to-table marketplace that bridges the gap between local farmers and consumers. 
                We believe in supporting sustainable agriculture while providing communities with access to fresh, quality produce.
            </p>
            <p>
                Our platform empowers farmers to sell directly to consumers, eliminating unnecessary middlemen and ensuring fair prices 
                for both parties. Through innovative technology and a user-friendly interface, we're transforming the way people buy 
                fresh agricultural products.
            </p>
        </div>

        <div class="system-card">
            <h2>✨ Key Features</h2>
            <div class="features-grid">
                <div class="feature-box">
                    <div class="feature-icon">🛒</div>
                    <h3>Easy Shopping</h3>
                    <p>Browse and purchase fresh products with just a few clicks</p>
                </div>
                <div class="feature-box">
                    <div class="feature-icon">🚚</div>
                    <h3>Fast Delivery</h3>
                    <p>Get farm-fresh produce delivered right to your doorstep</p>
                </div>
                <div class="feature-box">
                    <div class="feature-icon">👨‍🌾</div>
                    <h3>Support Local</h3>
                    <p>Connect directly with farmers in your community</p>
                </div>
                <div class="feature-box">
                    <div class="feature-icon">💰</div>
                    <h3>Fair Pricing</h3>
                    <p>Transparent pricing that benefits both farmers and buyers</p>
                </div>
                <div class="feature-box">
                    <div class="feature-icon">📱</div>
                    <h3>Mobile Friendly</h3>
                    <p>Shop anywhere, anytime on any device</p>
                </div>
                <div class="feature-box">
                    <div class="feature-icon">🔒</div>
                    <h3>Secure Payments</h3>
                    <p>Safe and encrypted payment processing</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Team Section -->
    <section class="team-section">
        <h2 class="section-title">Meet Our Development Team</h2>
        <div class="team-grid">
            <!-- Developer 1 -->
            <div class="developer-card">
                <img src="/agri_system/public/images/team/dev1.jpg" alt="Developer 1" class="dev-photo" onerror="this.src='/agri_system/public/images/devs/Tanyang.jpg'">
                <h3 class="dev-name">Tanyang, Frecy May</h3>
                <p class="dev-role">Project Manager</p>
                <p class="dev-description">
                    Passionate about creating impactful agricultural solutions. Specializes in full-stack development and system architecture.
                </p>
                <a href="/agri_system/public/devs/contacts" class="contact-btn">Contact</a>
            </div>

            <!-- Developer 2 -->
            <div class="developer-card">
                <img src="/agri_system/public/images/team/dev2.jpg" alt="Developer 2" class="dev-photo" onerror="this.src='/agri_system/public/images/devs/Abescoro.jpg'">
                <h3 class="dev-name">Abescoro, Meljohn</h3>
                <p class="dev-role">System Analyst</p>
                <p class="dev-description">
                    Analyzes user requirements and translates them into technical specifications for optimal system design.
                </p>
                <a href="/agri_system/public/devs/contacts" class="contact-btn">Contact</a>
            </div>

            <!-- Developer 3 -->
            <div class="developer-card">
                <img src="/agri_system/public/images/team/dev3.jpg" alt="Developer 3" class="dev-photo" onerror="this.src='/agri_system/public/images/devs/Araguas.jpg'">
                <h3 class="dev-name">Araguas, Lizeth</h3>
                <p class="dev-role">UI/UX Designer</p>
                <p class="dev-description">
                    Creative designer focused on user experience. Ensures every interaction is intuitive and visually appealing.
                </p>
                <a href="/agri_system/public/devs/contacts" class="contact-btn">Contact</a>
            </div>

            <!-- Developer 4 -->
            <div class="developer-card">
                <img src="/agri_system/public/images/team/dev4.jpg" alt="Developer 4" class="dev-photo" onerror="this.src='/agri_system/public/images/devs/Espina.jpg'">
                <h3 class="dev-name">Espina, Rowell</h3>
                <p class="dev-role">System Tester</p>
                <p class="dev-description">
                    Manages System Testing and Error Handling
                </p>
                <a href="/agri_system/public/devs/contacts" class="contact-btn">Contact</a>
            </div>

            <!-- Developer 5 -->
            <div class="developer-card">
                <img src="/agri_system/public/images/team/dev5.jpg" alt="Developer 5" class="dev-photo" onerror="this.src='/agri_system/public/images/devs/Tenorio.jpg'">
                <h3 class="dev-name">Tenorio, Dennison Ruben</h3>
                <p class="dev-role">Full Stack Developer</p>
                <p class="dev-description">
                    Mythical Glory 63 stars.<br>San Antonio No.1 Yu Zhong
                </p>
                <a href="/agri_system/public/devs/contacts" class="contact-btn">Contact</a>
            </div>

            <!-- Developer 6 -->
            <div class="developer-card">
                <img src="/agri_system/public/images/team/dev6.jpg" alt="Developer 6" class="dev-photo" onerror="this.src='/agri_system/public/images/devs/Violeta.jpg'">
                <h3 class="dev-name">Violeta, Joemel</h3>
                <p class="dev-role">Technical Writer</p>
                <p class="dev-description">
                    Creates clear and structured documentation for both users and developers. 
                </p>
                <a href="/agri_system/public/devs/contacts" class="contact-btn">Contact</a>
            </div>

            <!-- Developer 7 -->
            <div class="developer-card">
                <img src="/agri_system/public/images/team/dev7.jpg" alt="Developer 7" class="dev-photo" onerror="this.src='/agri_system/public/images/devs/Yumol.jpg'">
                <h3 class="dev-name">Yumol, Victor</h3>
                <p class="dev-role">Technical Writer</p>
                <p class="dev-description">
                    Ensures all system processes, manuals, and reports are well-written and easy to understand.
                </p>
                <a href="/agri_system/public/devs/contacts" class="contact-btn">Contact</a>
            </div>
        </div>
    </section>

    <footer>
        <p>&copy; 2025 Agri Tayo Rito. All rights reserved.</p>
        <p>Fresh from Farm to Table</p>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -100px 0px'
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, observerOptions);

            document.querySelectorAll('.developer-card, .system-card, .feature-box').forEach(el => {
                el.style.opacity = '0';
                el.style.transform = 'translateY(20px)';
                el.style.transition = 'all 0.6s ease';
                observer.observe(el);
            });
        });
    </script>
</body>
</html>