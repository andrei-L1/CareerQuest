<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>How It Works for Professionals | CareerConnect</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #36b9cc;
            --secondary-color: #f8f9fc;
            --accent-color: #2c9faf;
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
        
        .network-card {
            border-top: 4px solid var(--primary-color);
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <!-- Hero Section -->
    <section class="hero-section text-center" data-aos="fade">
        <div class="container">
            <h1 class="display-4 fw-bold mb-4">How It Works for Professionals</h1>
            <p class="lead mb-4">Advance your career through networking, mentorship, and new opportunities</p>
            <a href="#steps" class="btn btn-light btn-lg px-4 me-2">Get Started</a>
            <a href="../views/register_professional.php" class="btn btn-outline-light btn-lg px-4">Join as Professional</a>
        </div>
    </section>

    <!-- Steps Section -->
    <section id="steps" class="container py-5">
        <div class="row justify-content-center mb-5">
            <div class="col-lg-8 text-center">
                <h2 class="fw-bold mb-3">Your Professional Growth Path</h2>
                <p class="lead text-muted">Three steps to expand your network and career opportunities</p>
            </div>
        </div>
        
        <div class="row">
            <!-- Step 1 -->
            <div class="col-lg-4" data-aos="fade-up">
                <div class="step-card card h-100">
                    <img src="https://images.unsplash.com/photo-1434030216411-0b793f4b4173?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1470&q=80" class="card-img-top" alt="Build Profile">
                    <div class="step-number">1</div>
                    <div class="card-body text-center">
                        <h3 class="card-title fw-bold">Build Your Profile</h3>
                        <p class="card-text">Showcase your experience, skills, and career aspirations to stand out.</p>
                        <ul class="text-start mt-3">
                            <li>Highlight your professional journey</li>
                            <li>Showcase certifications and achievements</li>
                            <li>Set your career preferences</li>
                            <li>Upload portfolio samples</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Step 2 -->
            <div class="col-lg-4" data-aos="fade-up" data-aos-delay="100">
                <div class="step-card card h-100">
                    <img src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1471&q=80" class="card-img-top" alt="Network & Mentor">
                    <div class="step-number">2</div>
                    <div class="card-body text-center">
                        <h3 class="card-title fw-bold">Network & Mentor</h3>
                        <p class="card-text">Connect with peers, industry leaders, and emerging talent.</p>
                        <ul class="text-start mt-3">
                            <li>Join professional communities</li>
                            <li>Participate in mentorship programs</li>
                            <li>Attend virtual networking events</li>
                            <li>Share knowledge through forums</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Step 3 -->
            <div class="col-lg-4" data-aos="fade-up" data-aos-delay="200">
                <div class="step-card card h-100">
                    <img src="https://images.unsplash.com/photo-1450101499163-c8848c66ca85?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1470&q=80" class="card-img-top" alt="Access Opportunities">
                    <div class="step-number">3</div>
                    <div class="card-body text-center">
                        <h3 class="card-title fw-bold">Access Opportunities</h3>
                        <p class="card-text">Discover career advancement opportunities tailored to your profile.</p>
                        <ul class="text-start mt-3">
                            <li>Exclusive job openings</li>
                            <li>Freelance and consulting projects</li>
                            <li>Speaking and leadership opportunities</li>
                            <li>Professional development programs</li>
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
                <h2 class="fw-bold mb-3">Why Professionals Thrive With Us</h2>
                <p class="lead text-muted">Build meaningful connections that advance your career</p>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-4 text-center mb-4" data-aos="fade-up">
                <div class="benefit-icon">
                    <i class="fas fa-network-wired"></i>
                </div>
                <h4 class="fw-bold">Strategic Network</h4>
                <p>Connect with professionals across industries to open new career possibilities.</p>
            </div>
            
            <div class="col-md-4 text-center mb-4" data-aos="fade-up" data-aos-delay="100">
                <div class="benefit-icon">
                    <i class="fas fa-hands-helping"></i>
                </div>
                <h4 class="fw-bold">Mentorship</h4>
                <p>Give back by mentoring or find mentors to guide your career growth.</p>
            </div>
            
            <div class="col-md-4 text-center mb-4" data-aos="fade-up" data-aos-delay="200">
                <div class="benefit-icon">
                    <i class="fas fa-bullseye"></i>
                </div>
                <h4 class="fw-bold">Targeted Opportunities</h4>
                <p>Get matched with opportunities that align with your experience and goals.</p>
            </div>
        </div>
    </section>

    <!-- Networking Features -->
    <section class="container py-5">
        <div class="row justify-content-center mb-5">
            <div class="col-lg-8 text-center">
                <h2 class="fw-bold mb-3">Professional Networking Features</h2>
                <p class="lead text-muted">Tools designed for career growth and connection</p>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6 mb-4" data-aos="fade-up">
                <div class="card h-100 p-4 network-card">
                    <div class="card-body">
                        <div class="d-flex align-items-start">
                            <i class="fas fa-comments me-4 mt-1" style="font-size: 2rem; color: var(--primary-color);"></i>
                            <div>
                                <h4 class="fw-bold">Industry Forums</h4>
                                <p>Participate in discussions with professionals in your field. Share insights, ask questions, and establish your expertise.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-4" data-aos="fade-up" data-aos-delay="100">
                <div class="card h-100 p-4 network-card">
                    <div class="card-body">
                        <div class="d-flex align-items-start">
                            <i class="fas fa-calendar-check me-4 mt-1" style="font-size: 2rem; color: var(--primary-color);"></i>
                            <div>
                                <h4 class="fw-bold">Virtual Events</h4>
                                <p>Attend webinars, workshops, and networking mixers with professionals across various industries.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-4" data-aos="fade-up" data-aos-delay="200">
                <div class="card h-100 p-4 network-card">
                    <div class="card-body">
                        <div class="d-flex align-items-start">
                            <i class="fas fa-user-friends me-4 mt-1" style="font-size: 2rem; color: var(--primary-color);"></i>
                            <div>
                                <h4 class="fw-bold">Mentorship Program</h4>
                                <p>Join as a mentor or mentee in our structured program with matching based on goals and expertise.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-4" data-aos="fade-up" data-aos-delay="300">
                <div class="card h-100 p-4 network-card">
                    <div class="card-body">
                        <div class="d-flex align-items-start">
                            <i class="fas fa-briefcase me-4 mt-1" style="font-size: 2rem; color: var(--primary-color);"></i>
                            <div>
                                <h4 class="fw-bold">Opportunity Alerts</h4>
                                <p>Get personalized notifications about jobs, projects, and speaking opportunities matching your profile.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section text-center">
        <div class="container">
            <h2 class="display-5 fw-bold mb-4">Ready to Elevate Your Professional Journey?</h2>
            <p class="lead mb-5">Join a growing community of professionals advancing their careers together</p>
            <a href="../views/register_professional.php" class="btn btn-info btn-lg px-5 me-3">Join Now</a>
            <a href="/professional/features" class="btn btn-outline-info btn-lg px-5">Explore Features</a>
            
            <div class="back-button">
                <a href="../index.php" class="btn btn-link text-info"><i class="fas fa-arrow-left me-2"></i>Back to How It Works</a>
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