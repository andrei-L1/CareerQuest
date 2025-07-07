<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>How It Works for Employers | CareerConnect</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1cc88a;
            --secondary-color: #f8f9fc;
            --accent-color: #17a673;
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
        
        .testimonial-card {
            border-left: 4px solid var(--primary-color);
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <!-- Hero Section -->
    <section class="hero-section text-center" data-aos="fade">
        <div class="container">
            <h1 class="display-4 fw-bold mb-4">How It Works for Employers</h1>
            <p class="lead mb-4">Find the perfect candidates for your open positions faster than ever</p>
            <a href="#steps" class="btn btn-light btn-lg px-4 me-2">Get Started</a>
            <a href="../views/register_employer.php" class="btn btn-outline-light btn-lg px-4">Post a Job</a>
        </div>
    </section>

    <!-- Steps Section -->
    <section id="steps" class="container py-5">
        <div class="row justify-content-center mb-5">
            <div class="col-lg-8 text-center">
                <h2 class="fw-bold mb-3">Simple Hiring Process</h2>
                <p class="lead text-muted">Streamline your recruitment with our efficient three-step process</p>
            </div>
        </div>
        
        <div class="row">
            <!-- Step 1 -->
            <div class="col-lg-4" data-aos="fade-up">
                <div class="step-card card h-100">
                    <img src="https://images.unsplash.com/photo-1579621970563-ebec7560ff3e?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1471&q=80" class="card-img-top" alt="Post Jobs">
                    <div class="step-number">1</div>
                    <div class="card-body text-center">
                        <h3 class="card-title fw-bold">Post Your Jobs</h3>
                        <p class="card-text">Create detailed job listings in minutes and reach thousands of qualified candidates.</p>
                        <ul class="text-start mt-3">
                            <li>Easy job posting wizard</li>
                            <li>Customizable application forms</li>
                            <li>Set specific requirements</li>
                            <li>Add screening questions</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Step 2 -->
            <div class="col-lg-4" data-aos="fade-up" data-aos-delay="100">
                <div class="step-card card h-100">
                    <img src="https://images.unsplash.com/photo-1521791055366-0d553872125f?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1469&q=80" class="card-img-top" alt="Find Talent">
                    <div class="step-number">2</div>
                    <div class="card-body text-center">
                        <h3 class="card-title fw-bold">Find Top Talent</h3>
                        <p class="card-text">Our AI-powered matching system connects you with the most qualified candidates.</p>
                        <ul class="text-start mt-3">
                            <li>Smart candidate matching</li>
                            <li>Filter by skills, education, and experience</li>
                            <li>View candidate compatibility scores</li>
                            <li>Save and compare top applicants</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Step 3 -->
            <div class="col-lg-4" data-aos="fade-up" data-aos-delay="200">
                <div class="step-card card h-100">
                    <img src="https://images.unsplash.com/photo-1554224155-6726b3ff858f?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1511&q=80" class="card-img-top" alt="Hire Easily">
                    <div class="step-number">3</div>
                    <div class="card-body text-center">
                        <h3 class="card-title fw-bold">Hire Easily</h3>
                        <p class="card-text">Manage the entire hiring process through our platform from interview to offer.</p>
                        <ul class="text-start mt-3">
                            <li>Schedule interviews directly</li>
                            <li>Collaborate with your team</li>
                            <li>Send offers with one click</li>
                            <li>Track onboarding progress</li>
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
                <h2 class="fw-bold mb-3">Why Employers Choose CareerConnect</h2>
                <p class="lead text-muted">The smarter way to build your team</p>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-4 text-center mb-4" data-aos="fade-up">
                <div class="benefit-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <h4 class="fw-bold">Save Time</h4>
                <p>Reduce time-to-hire by up to 60% with our automated matching and screening tools.</p>
            </div>
            
            <div class="col-md-4 text-center mb-4" data-aos="fade-up" data-aos-delay="100">
                <div class="benefit-icon">
                    <i class="fas fa-search-dollar"></i>
                </div>
                <h4 class="fw-bold">Better Candidates</h4>
                <p>Access pre-vetted candidates with verified skills and academic credentials.</p>
            </div>
            
            <div class="col-md-4 text-center mb-4" data-aos="fade-up" data-aos-delay="200">
                <div class="benefit-icon">
                    <i class="fas fa-chart-pie"></i>
                </div>
                <h4 class="fw-bold">Data-Driven</h4>
                <p>Make informed decisions with candidate analytics and performance predictions.</p>
            </div>
        </div>
    </section>

    <!-- Testimonials -->
    <section class="container py-5">
        <div class="row justify-content-center mb-5">
            <div class="col-lg-8 text-center">
                <h2 class="fw-bold mb-3">What Employers Say</h2>
                <p class="lead text-muted">Trusted by companies of all sizes</p>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-4 mb-4" data-aos="fade-up">
                <div class="card h-100 p-4 testimonial-card">
                    <div class="card-body">
                        <p class="card-text mb-4">"We cut our hiring time in half and found candidates with exactly the skills we needed. The matching system is incredibly accurate."</p>
                        <div class="d-flex align-items-center">
                            <img src="https://randomuser.me/api/portraits/women/45.jpg" class="rounded-circle me-3" width="50" alt="Sarah Johnson">
                            <div>
                                <h6 class="mb-0 fw-bold">Sarah Johnson</h6>
                                <small class="text-muted">HR Director, TechSolutions Inc.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="100">
                <div class="card h-100 p-4 testimonial-card">
                    <div class="card-body">
                        <p class="card-text mb-4">"The quality of candidates from CareerConnect is unmatched. We've hired 12 graduates this year who are all performing exceptionally well."</p>
                        <div class="d-flex align-items-center">
                            <img src="https://randomuser.me/api/portraits/men/32.jpg" class="rounded-circle me-3" width="50" alt="Michael Chen">
                            <div>
                                <h6 class="mb-0 fw-bold">Michael Chen</h6>
                                <small class="text-muted">Talent Acquisition, Global Finance</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="200">
                <div class="card h-100 p-4 testimonial-card">
                    <div class="card-body">
                        <p class="card-text mb-4">"As a small business, we don't have a big HR team. CareerConnect makes it easy to find great talent without the overhead."</p>
                        <div class="d-flex align-items-center">
                            <img src="https://randomuser.me/api/portraits/women/68.jpg" class="rounded-circle me-3" width="50" alt="Lisa Rodriguez">
                            <div>
                                <h6 class="mb-0 fw-bold">Lisa Rodriguez</h6>
                                <small class="text-muted">CEO, BrightStart Marketing</small>
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
            <h2 class="display-5 fw-bold mb-4">Ready to Find Your Next Star Employee?</h2>
            <p class="lead mb-5">Join thousands of companies who found their perfect hires through CareerConnect</p>
            <a href="../index.php" class="btn btn-link text-success"><i class="fas fa-arrow-left me-2"></i>Back to How It Works</a>
            <a href="../views/register_employer.php"  class="btn btn-success btn-lg px-5 me-3">Post Your First Job</a>
            
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