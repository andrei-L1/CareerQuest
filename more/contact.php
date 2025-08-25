<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us | Career Quest</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- AOS (Animate On Scroll) -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #0A2647;
            --primary-dark: #07203a;
            --secondary-color: #2C7865;
            --secondary-dark: #1f5a4d;
            --accent-color: #FFD700;
            --accent-dark: #e6c200;
            --background-light: #F8F9FA;
            --text-dark: #212529;
            --text-light: #6C757D;
            --gradient-primary: linear-gradient(135deg, var(--primary-color), #1C4B82);
            --gradient-secondary: linear-gradient(135deg, var(--secondary-color), #3AA68D);
            --gradient-accent: linear-gradient(135deg, var(--accent-color), #FFC107);
        }

        body {
            background-color: var(--background-light);
            color: var(--text-dark);
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
        }

        .contact-hero {
            background: #f2f2f1;
            padding: 100px 0 60px;
            position: relative;
        }

        .contact-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiPjxkZWZzPjxwYXR0ZXJuIGlkPSJwYXR0ZXJuIiB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHBhdHRlcm5Vbml0cz0idXNlclNwYWNlT25Vc2UiIHBhdHRlcm5UcmFuc2Zvcm09InJvdGF0ZSg0NSkiPjxyZWN0IHdpZHRoPSIyMCIgaGVpZ2h0PSIyMCIgZmlsbD0icmdiYSgyNTUsMjU1LDI1NSwwLjA1KSIvPjwvcGF0dGVybj48L2RlZnM+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0idXJsKCNwYXR0ZXJuKSIvPjwvc3ZnPg==');
            opacity: 0.5;
        }

        .contact-hero-content {
            position: relative;
            z-index: 1;
        }

        .section-title {
            font-weight: 800;
            margin-bottom: 1rem;
            position: relative;
            display: inline-block;
            color: var(--primary-color);
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 60px;
            height: 4px;
            background: var(--gradient-primary);
            border-radius: 2px;
        }

        .contact-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            overflow: hidden;
            margin: 40px 0;
        }

        .contact-info {
            background: var(--gradient-primary);
            color: white;
            padding: 48px 32px;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .contact-info h2 {
            font-weight: 700;
            margin-bottom: 24px;
        }

        .contact-info p {
            opacity: 0.9;
            margin-bottom: 32px;
        }

        .info-item {
            display: flex;
            align-items: center;
            margin-bottom: 24px;
        }

        .info-item .icon {
            width: 50px;
            height: 50px;
            background: rgba(255,255,255,0.15);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 16px;
            flex-shrink: 0;
        }

        .info-item .content {
            flex: 1;
        }

        .info-item h5 {
            margin-bottom: 4px;
            font-weight: 600;
        }

        .info-item p {
            margin-bottom: 0;
            opacity: 0.9;
        }

        .social-links {
            margin-top: 32px;
        }

        .social-links a {
            display: inline-block;
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.15);
            border-radius: 50%;
            text-align: center;
            line-height: 40px;
            margin-right: 12px;
            transition: all 0.3s ease;
        }

        .social-links a:hover {
            background: rgba(255,255,255,0.25);
            transform: translateY(-3px);
        }

        .contact-form {
            padding: 48px 32px;
        }

        .contact-form h3 {
            color: var(--primary-color);
            font-weight: 700;
            margin-bottom: 32px;
        }

        .form-group {
            position: relative;
            margin-bottom: 28px;
        }

        .form-control {
            padding: 16px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s;
            height: auto;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(10, 38, 71, 0.15);
        }

        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }

        .form-label {
            position: absolute;
            left: 16px;
            top: 16px;
            background: white;
            padding: 0 8px;
            color: var(--text-light);
            transition: all 0.3s;
            pointer-events: none;
        }

        .form-control:focus + .form-label,
        .form-control:not(:placeholder-shown) + .form-label {
            top: -12px;
            left: 12px;
            font-size: 14px;
            color: var(--primary-color);
            font-weight: 500;
        }

        .btn-primary {
            background: var(--gradient-primary);
            border: none;
            padding: 12px 30px;
            font-weight: 600;
            border-radius: 50px;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(10, 38, 71, 0.2);
        }

        .btn-primary:hover {
            background: var(--gradient-primary);
            background-position: right center;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(10, 38, 71, 0.3);
        }

        .map-container {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-top: 60px;
        }

        .map-container iframe {
            width: 100%;
            height: 350px;
            border: none;
        }

        @media (max-width: 992px) {
            .contact-info, .contact-form {
                padding: 32px 24px;
            }
        }

        @media (max-width: 768px) {
            .contact-hero {
                padding: 80px 0 40px;
            }
            
            .contact-info, .contact-form {
                padding: 24px 16px;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php' ?>
    
    <!-- Hero Section -->
    <section class="contact-hero">
        <div class="container contact-hero-content">
            <div class="row justify-content-center text-center">
                <div class="col-lg-8">
                    <h1 class="section-title" data-aos="fade-up">Get in Touch</h1>
                    <p class="lead mb-0" data-aos="fade-up" data-aos-delay="100">We'd love to hear from you! Reach out with any questions, feedback, or partnership opportunities.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="container">
        <div class="contact-container" data-aos="fade-up" data-aos-delay="200">
            <div class="row g-0">
                <div class="col-lg-5">
                    <div class="contact-info">
                        <h2>Contact Information</h2>
                        <p>Fill out the form or contact us directly using the information below.</p>
                        
                        <div class="info-item">
                            <div class="icon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div class="content">
                                <h5>Address</h5>
                                <p>400 Diaz St</p>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <div class="icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="content">
                                <h5>Email</h5>
                                <p>careerquest93@gmail.com</p>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <div class="icon">
                                <i class="fas fa-phone-alt"></i>
                            </div>
                            <div class="content">
                                <h5>Phone</h5>
                                <p>+63 0900 232 7483</p>
                            </div>
                        </div>
                        
                        <div class="social-links">
                            <a href="#"><i class="fab fa-facebook-f"></i></a>
                            <a href="#"><i class="fab fa-twitter"></i></a>
                            <a href="#"><i class="fab fa-linkedin-in"></i></a>
                            <a href="#"><i class="fab fa-instagram"></i></a>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-7">
                    <form class="contact-form" autocomplete="off">
                        <h3>Send us a message</h3>
                        
                        <div class="form-group">
                            <input type="text" class="form-control" id="name" name="name" required placeholder=" " />
                            <label for="name" class="form-label">Your Name</label>
                        </div>
                        
                        <div class="form-group">
                            <input type="email" class="form-control" id="email" name="email" required placeholder=" " />
                            <label for="email" class="form-label">Your Email</label>
                        </div>
                        
                        <div class="form-group">
                            <input type="text" class="form-control" id="subject" name="subject" required placeholder=" " />
                            <label for="subject" class="form-label">Subject</label>
                        </div>
                        
                        <div class="form-group">
                            <textarea class="form-control" id="message" name="message" required placeholder=" "></textarea>
                            <label for="message" class="form-label">Your Message</label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Send Message</button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="map-container" data-aos="fade-up" data-aos-delay="300">
            <iframe src="https://www.google.com/maps/embed?pb=!1m14!1m12!1m3!1d1358.7478969296922!2d120.91672304231082!3d15.585267827435088!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!5e0!3m2!1sen!2sph!4v1756098811763!5m2!1sen!2sph" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
        </div>
    </section>

    <!-- Bootstrap JS (for navbar functionality) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- AOS (Animate On Scroll) -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        // Initialize AOS
        AOS.init({
            duration: 800,
            easing: 'ease-in-out',
            once: true
        });

        // Form label animation
        document.addEventListener('DOMContentLoaded', function() {
            const formInputs = document.querySelectorAll('.form-control');
            
            formInputs.forEach(input => {
                // Check if input has value on page load
                if (input.value) {
                    input.nextElementSibling.classList.add('active');
                }
                
                input.addEventListener('focus', function() {
                    this.nextElementSibling.classList.add('active');
                });
                
                input.addEventListener('blur', function() {
                    if (!this.value) {
                        this.nextElementSibling.classList.remove('active');
                    }
                });
            });
        });
    </script>
    <?php include '../includes/footer.php' ?>
</body>
</html>