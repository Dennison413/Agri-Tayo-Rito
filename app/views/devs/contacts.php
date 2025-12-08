<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Agri Tayo Rito</title>
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
            max-width: 1200px;
            margin: -40px auto 60px;
            padding: 0 20px;
        }

        .contact-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 40px;
        }

        .contact-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .card-icon {
            font-size: 3rem;
            margin-bottom: 20px;
        }

        .card-title {
            color: #2d5016;
            font-size: 1.5rem;
            margin-bottom: 15px;
            font-weight: 700;
        }

        .card-description {
            color: #666;
            line-height: 1.8;
            margin-bottom: 20px;
        }

        .contact-info {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
            transition: all 0.3s;
        }

        .info-item:hover {
            background: #e8f5e9;
            transform: translateX(5px);
        }

        .info-icon {
            font-size: 1.5rem;
            color: #2d5016;
        }

        .info-content h3 {
            color: #2d5016;
            font-size: 1rem;
            margin-bottom: 5px;
        }

        .info-content p {
            color: #666;
            font-size: 0.95rem;
        }

        .info-content a {
            color: #2d5016;
            text-decoration: none;
            font-weight: 600;
        }

        .info-content a:hover {
            text-decoration: underline;
        }

        .form-card {
            grid-column: 1 / -1;
        }

        .contact-form {
            display: grid;
            gap: 20px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            color: #2d5016;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s;
            font-family: inherit;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #2d5016;
            box-shadow: 0 0 0 3px rgba(45, 80, 22, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 150px;
        }

        .submit-btn {
            background: #2d5016;
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 25px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            justify-self: start;
        }

        .submit-btn:hover {
            background: #4a7c25;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(45, 80, 22, 0.3);
        }

        .social-media {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .social-btn {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            text-decoration: none;
        }

        .social-btn.facebook {
            background: #1877f2;
            color: white;
        }

        .social-btn.twitter {
            background: #1da1f2;
            color: white;
        }

        .social-btn.instagram {
            background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%);
            color: white;
        }

        .social-btn.email {
            background: #ea4335;
            color: white;
        }

        .social-btn:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }

        .map-container {
            grid-column: 1 / -1;
            height: 400px;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .map-placeholder {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #2d5016;
            font-size: 1.2rem;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px 20px;
            border-radius: 10px;
            border: 2px solid #c3e6cb;
            display: none;
            margin-bottom: 20px;
        }

        footer {
            background: #2d5016;
            color: white;
            padding: 30px 20px;
            text-align: center;
            margin-top: 60px;
        }

        @media (max-width: 768px) {
            .hero h1 {
                font-size: 1.8rem;
            }

            .contact-grid {
                grid-template-columns: 1fr;
            }

            .contact-card {
                padding: 25px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .nav-links {
                flex-direction: column;
                gap: 10px;
            }

            .logo-section h1 {
                font-size: 1.2rem;
            }

            .map-container {
                height: 300px;
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
        <h1>Get in Touch</h1>
        <p>We'd love to hear from you! Reach out to us for any questions or support.</p>
    </section>

    <div class="container">
        <div class="contact-grid">
            <!-- Contact Information -->
            <div class="contact-card">
                <div class="card-icon">📧</div>
                <h2 class="card-title">Contact Information</h2>
                <p class="card-description">
                    Have questions? Our team is here to help. Contact us through any of the following channels.
                </p>

                <div class="contact-info">
                    <div class="info-item">
                        <div class="info-icon">📬</div>
                        <div class="info-content">
                            <h3>Email</h3>
                            <p><a href="mailto:agritayorito@gmail.com">agritayorito@gmail.com</a></p>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-icon">📱</div>
                        <div class="info-content">
                            <h3>Phone</h3>
                            <p>+63 912 345 6789</p>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-icon">🕐</div>
                        <div class="info-content">
                            <h3>Business Hours</h3>
                            <p>Monday - Friday: 8:00 AM - 5:00 PM</p>
                            <p>Saturday: 9:00 AM - 3:00 PM</p>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-icon">📍</div>
                        <div class="info-content">
                            <h3>Location</h3>
                            <p>San Pablo, Calabarzon, Philippines</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Social Media & Quick Links -->
            <div class="contact-card">
                <div class="card-icon">🌐</div>
                <h2 class="card-title">Connect With Us</h2>
                <p class="card-description">
                    Follow us on social media for updates, promotions, and agricultural tips!
                </p>

                <div class="social-media">
                    <a href="https://facebook.com/agritayorito" class="social-btn facebook" title="Facebook" target="_blank">
                        📘
                    </a>
                    <a href="https://twitter.com/agritayorito" class="social-btn twitter" title="Twitter" target="_blank">
                        🐦
                    </a>
                    <a href="https://instagram.com/agritayorito" class="social-btn instagram" title="Instagram" target="_blank">
                        📷
                    </a>
                    <a href="mailto:agritayorito@gmail.com" class="social-btn email" title="Email">
                        ✉️
                    </a>
                </div>

                <div style="margin-top: 40px;">
                    <h3 style="color: #2d5016; margin-bottom: 15px;">Quick Links</h3>
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <a href="/agri_system/public/devs/about" style="color: #2d5016; text-decoration: none; padding: 10px; background: #f8f9fa; border-radius: 8px; transition: all 0.3s;">
                            👥 About Us
                        </a>
                        <a href="/agri_system/public/devs/terms" style="color: #2d5016; text-decoration: none; padding: 10px; background: #f8f9fa; border-radius: 8px; transition: all 0.3s;">
                            📋 Terms of Service
                        </a>
                        <a href="/agri_system/public/devs/privacy" style="color: #2d5016; text-decoration: none; padding: 10px; background: #f8f9fa; border-radius: 8px; transition: all 0.3s;">
                            🔒 Privacy Policy
                        </a>
                        <a href="/agri_system/public/devs/edhub" style="color: #2d5016; text-decoration: none; padding: 10px; background: #f8f9fa; border-radius: 8px; transition: all 0.3s;">
                            📚 Educational Hub
                        </a>
                    </div>
                </div>
            </div>

            <!-- Contact Form -->
            <div class="contact-card form-card">
                <div class="card-icon">✍️</div>
                <h2 class="card-title">Send Us a Message</h2>
                <p class="card-description">
                    Fill out the form below and we'll get back to you as soon as possible.
                </p>

                <div class="success-message" id="successMessage">
                    ✅ Thank you for your message! We'll get back to you soon.
                </div>

                <form class="contact-form" id="contactForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Full Name *</label>
                            <input type="text" id="name" name="name" required placeholder="Juan Dela Cruz">
                        </div>

                        <div class="form-group">
                            <label for="email">Email Address *</label>
                            <input type="email" id="email" name="email" required placeholder="juan@example.com">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" placeholder="+63 912 345 6789">
                        </div>

                        <div class="form-group">
                            <label for="subject">Subject *</label>
                            <select id="subject" name="subject" required>
                                <option value="">Select a subject</option>
                                <option value="general">General Inquiry</option>
                                <option value="support">Technical Support</option>
                                <option value="seller">Seller Application</option>
                                <option value="feedback">Feedback</option>
                                <option value="complaint">Complaint</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="message">Message *</label>
                        <textarea id="message" name="message" required placeholder="Tell us how we can help you..."></textarea>
                    </div>

                    <button type="submit" class="submit-btn">Send Message</button>
                </form>
            </div>

            <!-- Map Placeholder -->
            <div class="map-container">
                <div class="map-placeholder">
                    📍 San Pablo, Calabarzon, Philippines
                    <br><small style="margin-top: 10px; display: block; opacity: 0.7;">
                        Map integration available upon request
                    </small>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <p>&copy; 2025 Agri Tayo Rito. All rights reserved.</p>
        <p>Fresh from Farm to Table</p>
    </footer>

    <script>
        // Form submission handler
        document.getElementById('contactForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const successMessage = document.getElementById('successMessage');
            successMessage.style.display = 'block';
            
            // Scroll to success message
            successMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
            
            // Reset form after 2 seconds
            setTimeout(() => {
                this.reset();
                setTimeout(() => {
                    successMessage.style.display = 'none';
                }, 3000);
            }, 2000);
        });

        // Smooth scroll for quick links
        document.querySelectorAll('a[href^="/"]').forEach(link => {
            link.addEventListener('mouseenter', function() {
                this.style.background = '#e8f5e9';
                this.style.transform = 'translateX(5px)';
            });
            link.addEventListener('mouseleave', function() {
                this.style.background = '#f8f9fa';
                this.style.transform = 'translateX(0)';
            });
        });

        // Info items hover effect
        document.querySelectorAll('.info-item').forEach(item => {
            item.addEventListener('mouseenter', function() {
                this.style.boxShadow = '0 4px 15px rgba(45, 80, 22, 0.1)';
            });
            item.addEventListener('mouseleave', function() {
                this.style.boxShadow = 'none';
            });
        });

        // Form validation
        const inputs = document.querySelectorAll('.form-group input, .form-group textarea, .form-group select');
        inputs.forEach(input => {
            input.addEventListener('invalid', function(e) {
                e.preventDefault();
                this.style.borderColor = '#dc3545';
            });
            
            input.addEventListener('input', function() {
                if (this.validity.valid) {
                    this.style.borderColor = '#28a745';
                } else {
                    this.style.borderColor = '#e0e0e0';
                }
            });
        });
    </script>
</body>
</html>