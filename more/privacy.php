<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy | SkillMatch</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        :root {
            --primary-color: #4f46e5;
            --primary-light: #6366f1;
            --primary-dark: #3730a3;
            --secondary-color: #10b981;
            --dark-color: #1e293b;
            --light-color: #f8fafc;
            --gray-color: #64748b;
            --border-radius: 12px;
            --box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: #f9fafb;
            color: var(--dark-color);
            line-height: 1.6;
        }
        
        .privacy-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
            color: #fff;
            padding: 5rem 0 3.5rem 0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .privacy-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 20% 50%, rgba(255,255,255,0.1) 0%, transparent 70%);
        }
        
        .privacy-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            position: relative;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .privacy-header p {
            font-size: 1.15rem;
            opacity: 0.9;
            max-width: 700px;
            margin: 0 auto;
            position: relative;
            font-weight: 300;
        }
        
        .privacy-card {
            background: #fff;
            box-shadow: var(--box-shadow);
            border-radius: var(--border-radius);
            max-width: 900px;
            margin: -3rem auto 4rem auto;
            padding: 3.5rem;
            position: relative;
            z-index: 10;
            border: 1px solid rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
            transform: translateY(0);
            transition: var(--transition);
            margin-top:50px;
        }
        
        .privacy-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
        }
        
        .privacy-card h2 {
            color: var(--primary-dark);
            font-size: 1.5rem;
            font-weight: 600;
            margin-top: 2.5rem;
            margin-bottom: 1.25rem;
            position: relative;
            padding-left: 1.5rem;
        }
        
        .privacy-card h2::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0.35em;
            height: 1em;
            width: 5px;
            background: var(--secondary-color);
            border-radius: 3px;
        }
        
        .privacy-card p, .privacy-card ul {
            color: var(--gray-color);
            font-size: 1.05rem;
            margin-bottom: 1.5rem;
            font-weight: 400;
        }
        
        .privacy-card ul {
            padding-left: 1.5rem;
        }
        
        .privacy-card li {
            margin-bottom: 0.75rem;
            position: relative;
            padding-left: 1.5rem;
        }
        
        .privacy-card li::before {
            content: '•';
            position: absolute;
            left: 0;
            color: var(--secondary-color);
            font-weight: bold;
        }
        
        .privacy-card a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            border-bottom: 1px dotted currentColor;
        }
        
        .privacy-card a:hover {
            color: var(--primary-dark);
            border-bottom-style: solid;
        }
        
        .section-divider {
            height: 1px;
            background: linear-gradient(90deg, transparent 0%, rgba(99, 102, 241, 0.2) 50%, transparent 100%);
            margin: 2.5rem 0;
        }
        
        @media (max-width: 992px) {
            .privacy-card {
                margin: -2rem auto 3rem auto;
                padding: 2.5rem;
            }
            
            .privacy-header {
                padding: 4rem 0 2.5rem 0;
            }
        }
        
        @media (max-width: 768px) {
            .privacy-header h1 {
                font-size: 2rem;
            }
            
            .privacy-header p {
                font-size: 1rem;
                padding: 0 1rem;
            }
            
            .privacy-card {
                padding: 2rem 1.5rem;
                margin: -1.5rem auto 2.5rem auto;
                border-radius: 10px;
            }
            
            .privacy-card h2 {
                font-size: 1.3rem;
            }
        }
        
        @media (max-width: 576px) {
            .privacy-header h1 {
                font-size: 1.75rem;
            }
            
            .privacy-card {
                padding: 1.75rem 1.25rem;
                margin: -1rem auto 2rem auto;
                
            }
            
            .privacy-card h2 {
                font-size: 1.2rem;
                margin-top: 2rem;
            }
        }
        
        /* Floating animation for decorative elements */
        .floating {
            animation: floating 6s ease-in-out infinite;
        }
        
        @keyframes floating {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-15px); }
            100% { transform: translateY(0px); }
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php' ?>
    
    <div class="privacy-header animate__animated animate__fadeIn">
        <div class="container">
            <h1 class="animate__animated animate__fadeInDown">Privacy Policy</h1>
            <p class="animate__animated animate__fadeIn animate__delay-1s">Your privacy is our priority. This policy outlines how we collect, use, and protect your personal information.</p>
        </div>
        
        <!-- Decorative elements -->
        <div class="position-absolute top-0 end-0 mt-5 me-5 floating" style="opacity: 0.2; z-index: 1;">
            <svg width="120" height="120" viewBox="0 0 120 120" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="60" cy="60" r="50" stroke="white" stroke-width="2" stroke-dasharray="5 5"/>
            </svg>
        </div>
        <div class="position-absolute bottom-0 start-0 mb-5 ms-5 floating" style="opacity: 0.2; z-index: 1; animation-delay: 2s;">
            <svg width="80" height="80" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M40 10C40 10 70 20 70 40C70 60 40 70 40 70C40 70 10 60 10 40C10 20 40 10 40 10Z" stroke="white" stroke-width="2"/>
            </svg>
        </div>
    </div>
    
    <div class="container">
        <div class="privacy-card animate__animated animate__fadeInUp animate__delay-1s">
            <h2>1. Information We Collect</h2>
            <p>We collect information you provide directly to us, such as when you create an account, update your profile, or communicate with us. This may include:</p>
            <ul>
                <li>Personal identification details (name, email, phone number)</li>
                <li>Professional information (skills, work experience, education)</li>
                <li>Usage data and analytics</li>
                <li>Any other information you voluntarily provide</li>
            </ul>
            
            <div class="section-divider"></div>
            
            <h2>2. How We Use Your Information</h2>
            <p>Your data helps us deliver and improve our services while ensuring a secure experience:</p>
            <ul>
                <li>To provide, maintain, and improve our platform's functionality</li>
                <li>To personalize your experience and match you with relevant opportunities</li>
                <li>To communicate important updates, offers, and support messages</li>
                <li>To develop new features and services based on user needs</li>
                <li>To ensure platform security and prevent fraudulent activities</li>
            </ul>
            
            <div class="section-divider"></div>
            
            <h2>3. Data Sharing and Disclosure</h2>
            <p>We respect your privacy and handle your information with care:</p>
            <ul>
                <li>We do not sell your personal information to third parties</li>
                <li>Data may be shared with trusted service providers under strict confidentiality agreements</li>
                <li>We may disclose information when required by law or to protect our rights</li>
                <li>Aggregated, anonymized data may be used for research and analytics</li>
            </ul>
            
            <div class="section-divider"></div>
            
            <h2>4. Cookies and Tracking Technologies</h2>
            <p>Our platform uses modern technologies to enhance your experience:</p>
            <ul>
                <li>Essential cookies for core functionality and security</li>
                <li>Analytics cookies to understand usage patterns and improve services</li>
                <li>Preference cookies to remember your settings and choices</li>
                <li>You can manage cookie preferences through your browser settings</li>
            </ul>
            
            <div class="section-divider"></div>
            
            <h2>5. Your Rights and Choices</h2>
            <p>You have control over your personal information:</p>
            <ul>
                <li>Access and review the data we hold about you</li>
                <li>Request corrections or updates to inaccurate information</li>
                <li>Delete your account and associated data</li>
                <li>Opt-out of marketing communications</li>
                <li>Export your data for transfer to other services</li>
            </ul>
            <p>For any privacy-related requests, please contact us at <a href="mailto:careerquest93@gmail.com">privacy@skillmatch.com</a>.</p>
            
            <div class="section-divider"></div>
            
            <h2>6. Changes to This Policy</h2>
            <p>We may update this Privacy Policy periodically to reflect changes in our practices or legal requirements. Significant changes will be communicated through our platform or via email.</p>
            
            <div class="section-divider"></div>
            
            <h2>7. Contact Us</h2>
            <p>If you have any questions about our privacy practices or this policy, our dedicated privacy team is here to help:</p>
            <p>
                <strong>Email:</strong> <a href="mailto:careerquest93@gmail.com">privacy@skillmatch.com</a><br>
                <strong>Mail:</strong> Privacy Office, SkillMatch Inc., 123 Tech Plaza, San Francisco, CA 94107
            </p>
            <p class="text-muted mt-4"><small>Last updated: June 15, 2023</small></p>
        </div>
    </div>
    
    <?php include '../includes/footer.php' ?>
    
    <!-- Bootstrap JS (for navbar functionality) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add smooth scrolling to all links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
        
        // Add intersection observer for scroll animations
        const observerOptions = {
            threshold: 0.1
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate__animated', 'animate__fadeInUp');
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);
        
        document.querySelectorAll('.privacy-card h2, .privacy-card p, .privacy-card ul').forEach(el => {
            observer.observe(el);
        });
    </script>
</body>
</html>