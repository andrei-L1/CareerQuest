<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>How It Works for Students | CareerConnect</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #f8f9fc;
            --accent-color: #2e59d9;
        }
        
        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f8f9fa;
        }
        
        .hero-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
            color: white;
            padding: 5rem 0;
            margin-bottom: 3rem;
        }
        
        .step-card {
            border: none;
            border-radius: 10px;
            overflow: hidden;
            transition: transform 0.3s;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .step-card:hover {
            transform: translateY(-10px);
        }
        
        .step-number {
            background-color: var(--primary-color);
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.5rem;
            margin: -25px auto 20px;
        }
        
        .benefit-icon {
            color: var(--primary-color);
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        
        .cta-section {
            background-color: var(--secondary-color);
            padding: 4rem 0;
            margin-top: 3rem;
        }
        
        .back-button {
            margin-top: 2rem;
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?> 

    <!-- Hero Section -->
    <section class="hero-section text-center" data-aos="fade">
        <div class="container">
            <h1 class="display-4 fw-bold mb-4">How It Works for Students</h1>
            <p class="lead mb-4">Your pathway to exciting career opportunities starts here</p>
            <a href="#steps" class="btn btn-light btn-lg px-4 me-2">Get Started</a>
            <a href="../views/register_student.php" class="btn btn-outline-light btn-lg px-4">Sign Up Now</a>
        </div>
    </section>

    <!-- Steps Section -->
    <section id="steps" class="container py-5">
        <div class="row justify-content-center mb-5">
            <div class="col-lg-8 text-center">
                <h2 class="fw-bold mb-3">Simple Steps to Your Dream Job</h2>
                <p class="lead text-muted">Follow these easy steps to connect with employers looking for your skills</p>
            </div>
        </div>
        
        <div class="row">
            <!-- Step 1 -->
            <div class="col-lg-4" data-aos="fade-up">
                <div class="step-card card h-100">
                    <img src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1471&q=80" class="card-img-top" alt="Create Profile">
                    <div class="step-number">1</div>
                    <div class="card-body text-center">
                        <h3 class="card-title fw-bold">Create Your Profile</h3>
                        <p class="card-text">Set up your professional profile in minutes. Highlight your skills, education, and career aspirations to stand out to employers.</p>
                        <ul class="text-start mt-3">
                            <li>Upload your resume</li>
                            <li>Add your skills and certifications</li>
                            <li>Showcase academic achievements</li>
                            <li>Set your career preferences</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Step 2 -->
            <div class="col-lg-4" data-aos="fade-up" data-aos-delay="100">
                <div class="step-card card h-100">
                    <img src="https://images.unsplash.com/photo-1551288049-bebda4e38f71?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1470&q=80" class="card-img-top" alt="Match Skills">
                    <div class="step-number">2</div>
                    <div class="card-body text-center">
                        <h3 class="card-title fw-bold">Get Matched</h3>
                        <p class="card-text">Our smart algorithm matches your profile with relevant job opportunities based on your skills and preferences.</p>
                        <ul class="text-start mt-3">
                            <li>Receive personalized job recommendations</li>
                            <li>Get matched with companies looking for your skills</li>
                            <li>See compatibility scores for each opportunity</li>
                            <li>Save your favorite matches</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Step 3 -->
            <div class="col-lg-4" data-aos="fade-up" data-aos-delay="200">
                <div class="step-card card h-100">
                    <img src="https://images.unsplash.com/photo-1521791136064-7986c2920216?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1469&q=80" class="card-img-top" alt="Apply & Get Hired">
                    <div class="step-number">3</div>
                    <div class="card-body text-center">
                        <h3 class="card-title fw-bold">Apply & Get Hired</h3>
                        <p class="card-text">Apply directly to opportunities with one click and track your applications through our dashboard.</p>
                        <ul class="text-start mt-3">
                            <li>One-click applications</li>
                            <li>Track application status</li>
                            <li>Receive interview invitations</li>
                            <li>Accept offers directly through the platform</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Benefits Section -->
    <section class="container py-5 bg-light rounded-3 my-5">
        <div class="row justify-content-center mb-5">
            <div class="col-lg-8 text-center">
                <h2 class="fw-bold mb-3">Why Students Love CareerConnect</h2>
                <p class="lead text-muted">Discover the benefits that make us different</p>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-4 text-center mb-4" data-aos="fade-up">
                <div class="benefit-icon">
                    <i class="fas fa-bolt"></i>
                </div>
                <h4 class="fw-bold">Quick Matching</h4>
                <p>Our advanced algorithm matches you with relevant jobs in seconds, saving you hours of searching.</p>
            </div>
            
            <div class="col-md-4 text-center mb-4" data-aos="fade-up" data-aos-delay="100">
                <div class="benefit-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h4 class="fw-bold">Career Growth</h4>
                <p>Access opportunities that align with your career goals and help you grow professionally.</p>
            </div>
            
            <div class="col-md-4 text-center mb-4" data-aos="fade-up" data-aos-delay="200">
                <div class="benefit-icon">
                    <i class="fas fa-user-tie"></i>
                </div>
                <h4 class="fw-bold">Direct Connections</h4>
                <p>Connect directly with hiring managers and bypass traditional application barriers.</p>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section text-center">
        <div class="container">
            <h2 class="display-5 fw-bold mb-4">Ready to Start Your Career Journey?</h2>
            <p class="lead mb-5">Join thousands of students who found their dream jobs through CareerConnect</p>
            <a href="/register" class="btn btn-primary btn-lg px-5 me-3">Get Started Now</a>
            <a href="/contact" class="btn btn-outline-primary btn-lg px-5">Contact Us</a>
            
            <div class="back-button">
                <a href="../index.php" class="btn btn-link"><i class="fas fa-arrow-left me-2"></i>Back to How It Works</a>
            </div>
        </div>
    </section>

    <!-- Footer would be included here in a real implementation -->

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 800,
            easing: 'ease-in-out',
            once: true
        });
    </script>
</body>
</html>