<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Career Quest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
            background: #f8f9fa;
        }
        .about-hero {
            background: linear-gradient(135deg, #0A2647 60%, #2C7865 100%);
            color: white;
            padding: 100px 0 60px;
            position: relative;
            overflow: hidden;
        }
        .about-hero h1 {
            font-weight: 800;
            font-size: 3.2rem;
        }
        .about-hero p {
            font-size: 1.3rem;
            opacity: 0.95;
        }
        .about-section-title {
            font-weight: 800;
            margin-bottom: 2rem;
            position: relative;
            display: inline-block;
        }
        .about-section-title::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 0;
            width: 50px;
            height: 3px;
            background: linear-gradient(135deg, #0A2647, #2C7865);
            border-radius: 2px;
        }
        .feature-icon {
            font-size: 2.5rem;
            color: #0A2647;
            margin-bottom: 1rem;
        }
        .impact-card {
            border: none;
            border-radius: 16px;
            background: #fff;
            box-shadow: 0 4px 24px rgba(10,38,71,0.09);
            transition: transform 0.2s;
        }
        .impact-card:hover {
            transform: translateY(-8px) scale(1.03);
        }
        .team-img {
            width: 110px;
            height: 110px;
            object-fit: cover;
            border-radius: 50%;
            margin-bottom: 1rem;
            border: 4px solid #0A2647;
            box-shadow: 0 2px 12px rgba(10,38,71,0.13);
        }
        .card.feature-card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(10,38,71,0.07);
            transition: transform 0.2s;
        }
        .card.feature-card:hover {
            transform: translateY(-8px) scale(1.03);
        }
        .gradient-bg {
            background: linear-gradient(135deg, #0A2647 60%, #2C7865 100%);
            color: white;
        }
        .about-hero-img {
            max-width: 420px;
            border-radius: 18px;
            box-shadow: 0 8px 32px rgba(10,38,71,0.18);
        }
        .role-card {
            transition: all 0.3s ease;
            cursor: pointer;
            border: 2px solid transparent;
        }

        .role-card:hover {
            transform: translateY(-5px);
            border-color: rgba(255,255,255,0.3);
        }

        .role-card.active {
            border-color: white;
            box-shadow: 0 0 0 3px rgba(255,255,255,0.5);
        }
        @media (max-width: 768px) {
            .about-hero h1 { font-size: 2.1rem; }
            .about-hero-img { max-width: 100%; }
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <!-- Hero Section -->
    <section class="about-hero text-center">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-7 text-lg-start text-center" data-aos="fade-right">
                    <h1 class="mb-3">About <span style="color:#FFD700;">Career Quest</span></h1>
                    <p class="mb-4">Empowering students, professionals, and employers to connect, collaborate, and grow through tailored opportunities and vibrant communities.</p>
                </div>
                <div class="col-lg-5 d-none d-lg-block" data-aos="fade-left">
                    <img src="https://images.unsplash.com/photo-1461749280684-dccba630e2f6?auto=format&fit=crop&w=800&q=80" alt="Teamwork" class="about-hero-img">
                </div>
            </div>
        </div>
    </section>

    <!-- Vision Section -->
    <section class="container py-5">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-4 mb-lg-0" data-aos="fade-up">
                <img src="https://images.unsplash.com/photo-1503676382389-4809596d5290?auto=format&fit=crop&w=600&q=80" alt="Vision" class="img-fluid rounded shadow">
            </div>
            <div class="col-lg-6" data-aos="fade-up" data-aos-delay="100">
                <h2 class="about-section-title">Our Vision</h2>
                <p class="lead text-muted">
                    We envision a world where talent meets opportunity seamlessly. Career Quest leverages advanced skill-matching to connect students and professionals with employers, fostering career growth through job postings, forums, and personalized tools.
                </p>
            </div>
        </div>
    </section>

    <!-- Impact/Stats Section -->
    <section class="gradient-bg py-5">
        <div class="container">
            <h2 class="about-section-title text-center">Our Impact</h2>
            <div class="row g-4 justify-content-center">
                <div class="col-md-4" data-aos="zoom-in">
                    <div class="card impact-card text-center p-4">
                        <h3 class="display-5 fw-bold text-primary">10K+</h3>
                        <p class="text-muted mb-0">Active Job Postings</p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="zoom-in" data-aos-delay="100">
                    <div class="card impact-card text-center p-4">
                        <h3 class="display-5 fw-bold text-success">50K+</h3>
                        <p class="text-muted mb-0">Registered Users</p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="zoom-in" data-aos-delay="200">
                    <div class="card impact-card text-center p-4">
                        <h3 class="display-5 fw-bold text-warning">1M+</h3>
                        <p class="text-muted mb-0">Forum Interactions</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="container py-5">
        <h2 class="about-section-title text-center">What We Offer</h2>
        <div class="row g-4">
            <div class="col-md-4" data-aos="fade-up">
                <div class="card feature-card text-center p-4 h-100">
                    <div class="feature-icon mb-2"><i class="fas fa-search"></i></div>
                    <h4 class="fw-bold mb-2">Job Matching</h4>
                    <p class="text-muted">Find jobs tailored to your skills, powered by our advanced matching algorithms for students and professionals.</p>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                <div class="card feature-card text-center p-4 h-100">
                    <div class="feature-icon mb-2"><i class="fas fa-users"></i></div>
                    <h4 class="fw-bold mb-2">Community Forums</h4>
                    <p class="text-muted">Engage in discussions, connect with peers and industry experts, and grow your network in our vibrant forums.</p>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                <div class="card feature-card text-center p-4 h-100">
                    <div class="feature-icon mb-2"><i class="fas fa-briefcase"></i></div>
                    <h4 class="fw-bold mb-2">Career Tools</h4>
                    <p class="text-muted">Build your profile, track applications, and save jobs to streamline your career journey with our easy-to-use tools.</p>
                </div>
            </div>
        </div>
    </section>

       <!-- Role Selection Modal -->
       <div class="modal fade" id="signupModal" tabindex="-1" aria-labelledby="roleSelectionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-0 pb-0">
                    <div class="w-100 text-center">
                        <h3 class="modal-title fw-bold display-6" id="roleSelectionModalLabel">Join Career Quest</h3>
                        <p class="text-muted">Select your role to get started</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <!-- Student Role -->
                        <div class="col-12">
                            <a href="../views/register_student.php" class="card role-card h-100 text-decoration-none text-white" style="background: var(--gradient-primary);">
                                <div class="card-body d-flex align-items-center gap-4 py-4">
                                    <div class="icon-container bg-white bg-opacity-25 p-3 rounded-circle">
                                        <i class="fas fa-user-graduate fa-2x"></i>
                                    </div>
                                    <div class="text-start">
                                        <h4 class="mb-1">Applicant</h4>
                                        <p class="opacity-75 mb-0">Find jobs, internships, and career guidance</p>
                                    </div>
                                    <i class="fas fa-arrow-right ms-auto opacity-50"></i>
                                </div>
                            </a>
                        </div>

                        <!-- Employer Role -->
                        <div class="col-12">
                            <a href="../views/register_employer.php" class="card role-card h-100 text-decoration-none text-white" style="background: var(--gradient-secondary);">
                                <div class="card-body d-flex align-items-center gap-4 py-4">
                                    <div class="icon-container bg-white bg-opacity-25 p-3 rounded-circle">
                                        <i class="fas fa-briefcase fa-2x"></i>
                                    </div>
                                    <div class="text-start">
                                        <h4 class="mb-1">Employer</h4>
                                        <p class="opacity-75 mb-0">Post jobs and find qualified candidates</p>
                                    </div>
                                    <i class="fas fa-arrow-right ms-auto opacity-50"></i>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <p class="text-muted small text-center w-100 mb-0">
                        Already have an account? 
                        <a href="#" data-bs-toggle="modal" data-bs-target="#loginModal" class="text-decoration-none fw-bold">Sign In</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Login Modal -->
    <div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-0 pb-0">
                    <div class="w-100 text-center">
                        <h3 class="modal-title fw-bold display-6" id="loginModalLabel">Welcome Back</h3>
                        <p class="text-muted">Sign in to your account</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <!-- Student Login -->
                        <div class="col-12">
                            <a href="../auth/login_student.php" class="card role-card h-100 text-decoration-none text-white" style="background: var(--gradient-primary);">
                                <div class="card-body d-flex align-items-center gap-4 py-4">
                                    <div class="icon-container bg-white bg-opacity-25 p-3 rounded-circle">
                                        <i class="fas fa-user-graduate fa-2x"></i>
                                    </div>
                                    <div class="text-start">
                                        <h4 class="mb-1">Applicant</h4>
                                        <p class="opacity-75 mb-0">Access jobs and career resources</p>
                                    </div>
                                    <i class="fas fa-arrow-right ms-auto opacity-50"></i>
                                </div>
                            </a>
                        </div>

                        <!-- Employer Login -->
                        <div class="col-12">
                            <a href="../auth/login_employer.php" class="card role-card h-100 text-decoration-none text-white" style="background: var(--gradient-secondary);">
                                <div class="card-body d-flex align-items-center gap-4 py-4">
                                    <div class="icon-container bg-white bg-opacity-25 p-3 rounded-circle">
                                        <i class="fas fa-briefcase fa-2x"></i>
                                    </div>
                                    <div class="text-start">
                                        <h4 class="mb-1">Employer</h4>
                                        <p class="opacity-75 mb-0">Manage your job postings</p>
                                    </div>
                                    <i class="fas fa-arrow-right ms-auto opacity-50"></i>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <p class="text-muted small text-center w-100 mb-0">
                        Don't have an account? 
                        <a href="#" data-bs-toggle="modal" data-bs-target="#signupModal" class="text-decoration-none fw-bold">Sign Up</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        
        AOS.init({
            duration: 900,
            once: true
        });
    </script>
    <script>
        // Store selected role in localStorage
        document.querySelectorAll('.role-card').forEach(card => {
            card.addEventListener('click', function() {
                const role = this.querySelector('h4').textContent.toLowerCase();
                localStorage.setItem('chosenRole', role);
            });
        });

        // Highlight selected role when modal opens
        document.addEventListener('DOMContentLoaded', function() {
            const chosenRole = localStorage.getItem('chosenRole');
            if (chosenRole) {
                document.querySelectorAll('.role-card').forEach(card => {
                    if (card.querySelector('h4').textContent.toLowerCase() === chosenRole) {
                        card.classList.add('active');
                    }
                });
            }
        });
    </script>
</body>
</html>