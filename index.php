<?php
require_once 'db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PayClone - Secure Digital Payments Made Simple</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            line-height: 1.6;
            color: #164e63;
            background: linear-gradient(135deg, #ecfeff 0%, #ffffff 100%);
            overflow-x: hidden;
        }

        /* Navigation Styles */
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid #d1d5db;
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 700;
            color: #164e63;
            text-decoration: none;
            background: linear-gradient(135deg, #164e63, #0ea5e9);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 2rem;
        }

        .nav-links a {
            text-decoration: none;
            color: #475569;
            font-weight: 500;
            transition: all 0.3s ease;
            position: relative;
        }

        .nav-links a:hover {
            color: #0ea5e9;
            transform: translateY(-2px);
        }

        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background: linear-gradient(90deg, #164e63, #0ea5e9);
            transition: width 0.3s ease;
        }

        .nav-links a:hover::after {
            width: 100%;
        }

        .nav-buttons {
            display: flex;
            gap: 1rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-primary {
            background: linear-gradient(135deg, #164e63, #0ea5e9);
            color: white;
            box-shadow: 0 4px 15px rgba(6, 182, 212, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(6, 182, 212, 0.4);
        }

        .btn-secondary {
            background: transparent;
            color: #164e63;
            border: 2px solid #164e63;
        }

        .btn-secondary:hover {
            background: #164e63;
            color: white;
            transform: translateY(-2px);
        }

        /* Hero Section */
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 8rem 2rem 4rem;
            background: linear-gradient(135deg, #ecfeff 0%, #ffffff 50%, #f0f9ff 100%);
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 200%;
            background: radial-gradient(circle, rgba(14, 165, 233, 0.1) 0%, transparent 70%);
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(5deg); }
        }

        .hero-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
            position: relative;
            z-index: 2;
        }

        .hero-content h1 {
            font-size: 3.5rem;
            font-weight: 700;
            line-height: 1.2;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, #164e63, #0ea5e9);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: slideInLeft 1s ease-out;
        }

        @keyframes slideInLeft {
            from { opacity: 0; transform: translateX(-50px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .hero-content p {
            font-size: 1.25rem;
            color: #475569;
            margin-bottom: 2rem;
            animation: slideInLeft 1s ease-out 0.2s both;
        }

        .hero-buttons {
            display: flex;
            gap: 1rem;
            animation: slideInLeft 1s ease-out 0.4s both;
        }

        .hero-image {
            display: flex;
            justify-content: center;
            align-items: center;
            animation: slideInRight 1s ease-out 0.6s both;
        }

        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(50px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .hero-graphic {
            width: 100%;
            max-width: 500px;
            height: 400px;
            background: linear-gradient(135deg, #164e63, #0ea5e9);
            border-radius: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            font-weight: 600;
            box-shadow: 0 20px 40px rgba(14, 165, 233, 0.3);
            position: relative;
            overflow: hidden;
        }

        .hero-graphic::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            animation: shine 3s infinite;
        }

        @keyframes shine {
            0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
            100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
        }

        /* Features Section */
        .features {
            padding: 6rem 2rem;
            background: white;
        }

        .features-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .section-title {
            text-align: center;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #164e63, #0ea5e9);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .section-subtitle {
            text-align: center;
            font-size: 1.25rem;
            color: #475569;
            margin-bottom: 4rem;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .feature-card {
            background: linear-gradient(135deg, #ecfeff, #ffffff);
            padding: 2rem;
            border-radius: 1rem;
            border: 1px solid #d1d5db;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(14, 165, 233, 0.2);
        }

        .feature-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #164e63, #0ea5e9);
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .feature-card h3 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #164e63;
        }

        .feature-card p {
            color: #475569;
            line-height: 1.6;
        }

        /* Trust Section */
        .trust {
            padding: 6rem 2rem;
            background: linear-gradient(135deg, #f9fafb, #ecfeff);
        }

        .trust-container {
            max-width: 1200px;
            margin: 0 auto;
            text-align: center;
        }

        .trust-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .stat-card {
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 10px 30px rgba(14, 165, 233, 0.1);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(14, 165, 233, 0.2);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #164e63, #0ea5e9);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stat-label {
            color: #475569;
            font-weight: 500;
            margin-top: 0.5rem;
        }

        /* CTA Section */
        .cta {
            padding: 6rem 2rem;
            background: linear-gradient(135deg, #164e63, #0ea5e9);
            color: white;
            text-align: center;
        }

        .cta-container {
            max-width: 800px;
            margin: 0 auto;
        }

        .cta h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .cta p {
            font-size: 1.25rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }

        .btn-white {
            background: white;
            color: #164e63;
            padding: 1rem 2rem;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .btn-white:hover {
            background: #f9fafb;
            transform: translateY(-2px);
        }

        /* Footer */
        .footer {
            background: #0f172a;
            color: #ecfeff;
            padding: 3rem 2rem 1rem;
        }

        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }

        .footer-section h4 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #0ea5e9;
        }

        .footer-section ul {
            list-style: none;
        }

        .footer-section ul li {
            margin-bottom: 0.5rem;
        }

        .footer-section ul li a {
            color: #94a3b8;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-section ul li a:hover {
            color: #0ea5e9;
        }

        .footer-bottom {
            border-top: 1px solid #334155;
            margin-top: 2rem;
            padding-top: 1rem;
            text-align: center;
            color: #94a3b8;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }

            .hero-container {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .hero-content h1 {
                font-size: 2.5rem;
            }

            .hero-buttons {
                justify-content: center;
            }

            .features-grid {
                grid-template-columns: 1fr;
            }

            .trust-stats {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        /* Loading Animation */
        .loading {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #164e63, #0ea5e9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            transition: opacity 0.5s ease;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top: 3px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Loading Screen -->
    <div class="loading" id="loading">
        <div class="spinner"></div>
    </div>

    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="#" class="logo">PayClone</a>
            <ul class="nav-links">
                <li><a href="#home">Home</a></li>
                <li><a href="#features">Features</a></li>
                <li><a href="#security">Security</a></li>
                <li><a href="#contact">Contact</a></li>
            </ul>
            <div class="nav-buttons">
                <a href="#" class="btn btn-secondary" onclick="redirectTo('login.php')">Login</a>
                <a href="#" class="btn btn-primary" onclick="redirectTo('signup.php')">Sign Up</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="hero-container">
            <div class="hero-content">
                <h1>Secure Digital Payments Made Simple</h1>
                <p>Send, receive, and manage your money with confidence. Join millions who trust PayClone for fast, secure, and reliable digital transactions worldwide.</p>
                <div class="hero-buttons">
                    <a href="#" class="btn btn-primary" onclick="redirectTo('signup.php')">Get Started Free</a>
                    <a href="#features" class="btn btn-secondary">Learn More</a>
                </div>
            </div>
            <div class="hero-image">
                <div class="hero-graphic">
                    <div>
                        <div style="font-size: 3rem; margin-bottom: 1rem;">💳</div>
                        <div>Secure Payments</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
        <div class="features-container">
            <h2 class="section-title">Why Choose PayClone?</h2>
            <p class="section-subtitle">Experience the future of digital payments with our cutting-edge features</p>
            
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">🔒</div>
                    <h3>Bank-Level Security</h3>
                    <p>Your money and personal information are protected with advanced encryption and fraud monitoring systems.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">⚡</div>
                    <h3>Instant Transfers</h3>
                    <p>Send money to friends, family, or businesses instantly with just an email address or phone number.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">🌍</div>
                    <h3>Global Reach</h3>
                    <p>Send and receive money across borders with competitive exchange rates and low fees.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">📱</div>
                    <h3>Mobile First</h3>
                    <p>Manage your finances on the go with our responsive design that works perfectly on any device.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">💰</div>
                    <h3>Digital Wallet</h3>
                    <p>Store multiple currencies, track spending, and manage your balance with our intelligent wallet system.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">🎯</div>
                    <h3>Smart Analytics</h3>
                    <p>Get insights into your spending patterns and financial habits with detailed transaction analytics.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Trust Section -->
    <section class="trust" id="security">
        <div class="trust-container">
            <h2 class="section-title">Trusted by Millions Worldwide</h2>
            <p class="section-subtitle">Join the growing community of users who rely on PayClone for their financial needs</p>
            
            <div class="trust-stats">
                <div class="stat-card">
                    <div class="stat-number">50M+</div>
                    <div class="stat-label">Active Users</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-number">$2.5B+</div>
                    <div class="stat-label">Transactions Processed</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-number">200+</div>
                    <div class="stat-label">Countries Supported</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-number">99.9%</div>
                    <div class="stat-label">Uptime Guarantee</div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta">
        <div class="cta-container">
            <h2>Ready to Get Started?</h2>
            <p>Join PayClone today and experience the future of digital payments. It's free to sign up and takes less than 2 minutes.</p>
            <a href="#" class="btn btn-white" onclick="redirectTo('signup.php')">Create Your Account</a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer" id="contact">
        <div class="footer-container">
            <div class="footer-section">
                <h4>PayClone</h4>
                <p>Making digital payments simple, secure, and accessible for everyone around the world.</p>
            </div>
            
            <div class="footer-section">
                <h4>Product</h4>
                <ul>
                    <li><a href="#">Personal</a></li>
                    <li><a href="#">Business</a></li>
                    <li><a href="#">Developer API</a></li>
                    <li><a href="#">Mobile App</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h4>Support</h4>
                <ul>
                    <li><a href="#">Help Center</a></li>
                    <li><a href="#">Contact Us</a></li>
                    <li><a href="#">Security</a></li>
                    <li><a href="#">Status</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h4>Company</h4>
                <ul>
                    <li><a href="#">About</a></li>
                    <li><a href="#">Careers</a></li>
                    <li><a href="#">Press</a></li>
                    <li><a href="#">Legal</a></li>
                </ul>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; 2024 PayClone. All rights reserved. | Privacy Policy | Terms of Service</p>
        </div>
    </footer>

    <script>
        // Loading screen
        window.addEventListener('load', function() {
            setTimeout(function() {
                document.getElementById('loading').style.opacity = '0';
                setTimeout(function() {
                    document.getElementById('loading').style.display = 'none';
                }, 500);
            }, 1000);
        });

        // Smooth scrolling for navigation links
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

        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 100) {
                navbar.style.background = 'rgba(255, 255, 255, 0.98)';
                navbar.style.boxShadow = '0 2px 20px rgba(0, 0, 0, 0.1)';
            } else {
                navbar.style.background = 'rgba(255, 255, 255, 0.95)';
                navbar.style.boxShadow = 'none';
            }
        });

        // Redirect function using JavaScript
        function redirectTo(page) {
            // Add loading animation
            document.body.style.opacity = '0.8';
            
            setTimeout(function() {
                window.location.href = page;
            }, 300);
        }

        // Animation on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Observe elements for animation
        document.querySelectorAll('.feature-card, .stat-card').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(30px)';
            el.style.transition = 'all 0.6s ease';
            observer.observe(el);
        });

        // Counter animation for stats
        function animateCounter(element, target) {
            let current = 0;
            const increment = target / 100;
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    current = target;
                    clearInterval(timer);
                }
                
                if (target >= 1000000) {
                    element.textContent = (current / 1000000).toFixed(1) + 'M+';
                } else if (target >= 1000) {
                    element.textContent = (current / 1000).toFixed(1) + 'K+';
                } else {
                    element.textContent = current.toFixed(1) + '%';
                }
            }, 20);
        }

        // Trigger counter animation when stats section is visible
        const statsObserver = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const numbers = entry.target.querySelectorAll('.stat-number');
                    numbers.forEach(num => {
                        const text = num.textContent;
                        if (text.includes('50M+')) animateCounter(num, 50000000);
                        else if (text.includes('$2.5B+')) animateCounter(num, 2500000000);
                        else if (text.includes('200+')) animateCounter(num, 200);
                        else if (text.includes('99.9%')) animateCounter(num, 99.9);
                    });
                    statsObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });

        const trustSection = document.querySelector('.trust');
        if (trustSection) {
            statsObserver.observe(trustSection);
        }
    </script>
</body>
</html>
