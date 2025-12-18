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

        .privacy-card {
            background: white;
            border-radius: 20px;
            padding: 50px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        }

        .last-updated {
            background: #e3f2fd;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: center;
            color: #1565c0;
            font-weight: 600;
        }

        .intro {
            background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            border-left: 5px solid #2d5016;
        }

        .intro p {
            color: #2d5016;
            line-height: 1.8;
            font-size: 1rem;
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

        .info-box {
            background: #d1ecf1;
            border-left: 5px solid #17a2b8;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
        }

        .info-box strong {
            color: #0c5460;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border-radius: 10px;
            overflow: hidden;
        }

        .data-table th,
        .data-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        .data-table th {
            background: #2d5016;
            color: white;
            font-weight: 600;
        }

        .data-table tr:last-child td {
            border-bottom: none;
        }

        .data-table tr:hover {
            background: #f8f9fa;
        }

        .rights-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }

        .right-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 20px;
            border-radius: 12px;
            border: 2px solid #dee2e6;
            transition: all 0.3s;
        }

        .right-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            border-color: #2d5016;
        }

        .right-card h4 {
            color: #2d5016;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .right-card p {
            color: #666;
            font-size: 0.95rem;
            line-height: 1.6;
        }

        .contact-section {
            background: linear-gradient(135deg, #2d5016 0%, #4a7c25 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            margin-top: 40px;
        }

        .contact-section h3 {
            margin-bottom: 15px;
        }

        .contact-section p {
            margin-bottom: 20px;
            opacity: 0.9;
        }

        .contact-btn {
            background: white;
            color: #2d5016;
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .contact-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
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

            .privacy-card {
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

            .data-table {
                font-size: 0.9rem;
            }

            .data-table th,
            .data-table td {
                padding: 10px;
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

    <section class="hero">
        <h1>Privacy Policy</h1>
        <p>Your privacy is important to us</p>
    </section>

    <div class="container">
        <div class="privacy-card">
            <div class="last-updated">
                🔒 Last Updated: December 8, 2025
            </div>

            <div class="intro">
                <p>
                    <strong>Welcome to Agri Tayo Rito.</strong> We are committed to protecting your personal information 
                    and your right to privacy. This Privacy Policy explains how we collect, use, disclose, and safeguard 
                    your information when you use our platform. Please read this policy carefully.
                </p>
            </div>

            <div class="section">
                <h2 class="section-title">
                    <span class="section-icon">📊</span>
                    1. Information We Collect
                </h2>
                <p>
                    We collect information that you provide directly to us, information we obtain automatically when you 
                    use our services, and information from third-party sources.
                </p>

                <h3 style="color: #2d5016; margin: 20px 0 10px;">Personal Information You Provide:</h3>
                <ul>
                    <li><strong>Account Information:</strong> Name, email address, phone number, password</li>
                    <li><strong>Profile Information:</strong> Profile picture, bio, location, preferences</li>
                    <li><strong>Payment Information:</strong> Billing address, payment card details (processed securely)</li>
                    <li><strong>Seller Information:</strong> Business details, tax information, bank account details</li>
                    <li><strong>Communications:</strong> Messages, reviews, feedback you provide</li>
                    <li><strong>Transaction Data:</strong> Purchase history, order details, delivery information</li>
                </ul>

                <h3 style="color: #2d5016; margin: 20px 0 10px;">Information Collected Automatically:</h3>
                <ul>
                    <li><strong>Device Information:</strong> IP address, browser type, operating system</li>
                    <li><strong>Usage Data:</strong> Pages visited, time spent, clicks, search queries</li>
                    <li><strong>Location Data:</strong> Approximate location based on IP address</li>
                    <li><strong>Cookies:</strong> See our Cookie Policy for details</li>
                </ul>
            </div>

            <div class="section">
                <h2 class="section-title">
                    <span class="section-icon">🎯</span>
                    2. How We Use Your Information
                </h2>
                <p>We use your personal information for the following purposes:</p>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Purpose</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>🛒 Service Delivery</td>
                            <td>Process transactions, fulfill orders, manage your account</td>
                        </tr>
                        <tr>
                            <td>📧 Communication</td>
                            <td>Send order updates, newsletters, promotional materials</td>
                        </tr>
                        <tr>
                            <td>🔧 Improvement</td>
                            <td>Analyze usage patterns, improve platform functionality</td>
                        </tr>
                        <tr>
                            <td>🛡️ Security</td>
                            <td>Detect and prevent fraud, maintain platform security</td>
                        </tr>
                        <tr>
                            <td>⚖️ Legal Compliance</td>
                            <td>Comply with legal obligations, resolve disputes</td>
                        </tr>
                        <tr>
                            <td>🎯 Personalization</td>
                            <td>Customize your experience, recommend relevant products</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="section">
                <h2 class="section-title">
                    <span class="section-icon">🔐</span>
                    3. How We Protect Your Information
                </h2>
                <p>
                    We implement appropriate technical and organizational measures to protect your personal information:
                </p>
                <ul>
                    <li><strong>Encryption:</strong> All sensitive data is encrypted in transit using SSL/TLS</li>
                    <li><strong>Secure Storage:</strong> Personal information is stored on secure servers with restricted access</li>
                    <li><strong>Access Controls:</strong> Only authorized personnel have access to personal data</li>
                    <li><strong>Regular Audits:</strong> We conduct security assessments and vulnerability testing</li>
                    <li><strong>Payment Security:</strong> Payment processing follows PCI-DSS standards</li>
                </ul>

                <div class="highlight-box">
                    <strong>Important:</strong> While we strive to protect your information, no method of transmission 
                    over the internet is 100% secure. You are responsible for maintaining the confidentiality of your 
                    account credentials.
                </div>
            </div>

            <div class="section">
                <h2 class="section-title">
                    <span class="section-icon">🤝</span>
                    4. Information Sharing and Disclosure
                </h2>
                <p>We may share your information in the following circumstances:</p>

                <ul>
                    <li><strong>With Sellers:</strong> Buyers' shipping and contact information is shared with sellers to fulfill orders</li>
                    <li><strong>Service Providers:</strong> Third-party vendors who help us operate (payment processors, delivery services)</li>
                    <li><strong>Business Transfers:</strong> In the event of a merger, acquisition, or sale of assets</li>
                    <li><strong>Legal Requirements:</strong> When required by law or to protect our rights</li>
                    <li><strong>With Your Consent:</strong> Any other sharing will be done with your explicit permission</li>
                </ul>

                <div class="info-box">
                    <strong>We Never Sell Your Data:</strong> Agri Tayo Rito does not sell, rent, or trade your 
                    personal information to third parties for marketing purposes.
                </div>
            </div>

            <div class="section">
                <h2 class="section-title">
                    <span class="section-icon">⚡</span>
                    5. Your Privacy Rights
                </h2>
                <p>You have the following rights regarding your personal information:</p>

                <div class="rights-grid">
                    <div class="right-card">
                        <h4>👁️ Access</h4>
                        <p>Request a copy of the personal information we hold about you</p>
                    </div>
                    <div class="right-card">
                        <h4>✏️ Correction</h4>
                        <p>Request correction of inaccurate or incomplete information</p>
                    </div>
                    <div class="right-card">
                        <h4>🗑️ Deletion</h4>
                        <p>Request deletion of your personal information</p>
                    </div>
                    <div class="right-card">
                        <h4>🚫 Opt-Out</h4>
                        <p>Unsubscribe from marketing communications at any time</p>
                    </div>
                    <div class="right-card">
                        <h4>📦 Portability</h4>
                        <p>Request transfer of your data in a portable format</p>
                    </div>
                    <div class="right-card">
                        <h4>⛔ Object</h4>
                        <p>Object to certain types of data processing</p>
                    </div>
                </div>

                <p style="margin-top: 20px;">
                    To exercise these rights, please contact us at <strong>agritayorito@gmail.com</strong>. 
                    We will respond to your request within 30 days.
                </p>
            </div>

            <div class="section">
                <h2 class="section-title">
                    <span class="section-icon">🍪</span>
                    6. Cookies and Tracking Technologies
                </h2>
                <p>
                    We use cookies and similar technologies to enhance your experience on our platform. Cookies are 
                    small text files stored on your device that help us:
                </p>
                <ul>
                    <li>Remember your login status and preferences</li>
                    <li>Analyze platform usage and performance</li>
                    <li>Personalize content and advertisements</li>
                    <li>Provide social media features</li>
                </ul>
                <p>
                    You can control cookie settings through your browser preferences. Note that disabling cookies 
                    may affect platform functionality.
                </p>
            </div>

            <div class="section">
                <h2 class="section-title">
                    <span class="section-icon">👶</span>
                    7. Children's Privacy
                </h2>
                <p>
                    Agri Tayo Rito is not intended for users under the age of 18. We do not knowingly collect 
                    personal information from children. If you believe we have inadvertently collected information 
                    from a minor, please contact us immediately.
                </p>
            </div>

            <div class="section">
                <h2 class="section-title">
                    <span class="section-icon">🌍</span>
                    8. International Data Transfers
                </h2>
                <p>
                    Your information may be transferred to and processed in countries other than the Philippines. 
                    We ensure appropriate safeguards are in place to protect your data in accordance with this 
                    Privacy Policy.
                </p>
            </div>

            <div class="section">
                <h2 class="section-title">
                    <span class="section-icon">📅</span>
                    9. Data Retention
                </h2>
                <p>
                    We retain your personal information for as long as necessary to:
                </p>
                <ul>
                    <li>Provide our services to you</li>
                    <li>Comply with legal obligations</li>
                    <li>Resolve disputes and enforce agreements</li>
                    <li>Maintain business records</li>
                </ul>
                <p>
                    When your data is no longer needed, we will securely delete or anonymize it.
                </p>
            </div>

            <div class="section">
                <h2 class="section-title">
                    <span class="section-icon">🔄</span>
                    10. Changes to This Privacy Policy
                </h2>
                <p>
                    We may update this Privacy Policy from time to time to reflect changes in our practices or legal 
                    requirements. We will notify you of significant changes by:
                </p>
                <ul>
                    <li>Posting the new policy on this page</li>
                    <li>Updating the "Last Updated" date</li>
                    <li>Sending an email notification (for material changes)</li>
                </ul>
                <p>
                    Your continued use of the Platform after changes constitutes acceptance of the updated policy.
                </p>
            </div>

            <div class="contact-section">
                <h3>Questions About Your Privacy?</h3>
                <p>
                    If you have questions or concerns about this Privacy Policy or our data practices, 
                    please don't hesitate to reach out.
                </p>
                <a href="/agri_system/public/devs/contacts" class="contact-btn">Contact Us</a>
            </div>
        </div>
    </div>

    <footer>
        <p>&copy; 2025 Agri Tayo Rito. All rights reserved.</p>
        <p>Fresh from Farm to Table</p>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sections = document.querySelectorAll('.section');
            
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

            sections.forEach(section => {
                section.style.opacity = '0';
                section.style.transform = 'translateY(20px)';
                section.style.transition = 'all 0.6s ease';
                observer.observe(section);
            });
        });

        const sections = document.querySelectorAll('.section');
        let currentSection = '';

        window.addEventListener('scroll', () => {
            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                const sectionHeight = section.clientHeight;
                
                if (window.pageYOffset >= (sectionTop - 200)) {
                    currentSection = section.getAttribute('id');
                    section.style.borderLeft = '5px solid #2d5016';
                    section.style.paddingLeft = '20px';
                } else {
                    section.style.borderLeft = 'none';
                    section.style.paddingLeft = '0';
                }
            });
        });
    </script>
</body>
</html>