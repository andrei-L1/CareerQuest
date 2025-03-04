<?php
session_start();
if (isset($_SESSION['user_id']) && isset($_SESSION['stud_id'])) {
    header("Location: views/dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Career Platform</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- AOS (Animate On Scroll) -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        /* Custom Colors */
        :root {
            --primary-color: #0A2647; /* Navy Blue */
            --secondary-color: #2C7865; /* Teal */
            --accent-color: #FFD700; /* Gold */
            --background-light: #F5F5F5; /* Light Gray */
            --text-dark: #333333; /* Dark Gray */
        }

        body {
            background-color: var(--background-light);
            color: var(--text-dark);
        }

        .hero-section {
            background: url('https://via.placeholder.com/1500x800') no-repeat center center/cover;
            position: relative;
            color: white;
            padding: 100px 0;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(10, 38, 71, 0.7); /* Navy Blue with opacity */
        }

        .hero-content {
            position: relative;
            z-index: 1;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: #1C4B82; /* Darker Navy Blue */
            border-color: #1C4B82;
        }

        .btn-outline-primary {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            color: white;
        }

        .bg-gradient-primary {
            background: linear-gradient(45deg, var(--primary-color), #1C4B82);
        }

        .bg-gradient-success {
            background: linear-gradient(45deg, var(--secondary-color), #3AA68D);
        }

        .bg-gradient-info {
            background: linear-gradient(45deg, #1E3C72, #2A5298);
        }

        .card {
            border: none;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
        }

        .icon-wrapper {
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .bg-light {
            background-color: var(--background-light) !important;
        }

        .text-dark {
            color: var(--text-dark) !important;
        }

        .bg-dark {
            background-color: var(--primary-color) !important;
        }

        .btn-light {
            background-color: white;
            color: var(--primary-color);
        }

        .btn-light:hover {
            background-color: var(--accent-color);
            color: var(--primary-color);
        }

        .btn-outline-light {
            border-color: white;
            color: white;
        }

        .btn-outline-light:hover {
            background-color: white;
            color: var(--primary-color);
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <!-- Hero Section -->
    <header class="hero-section text-center">
        <div class="container hero-content">
            <h1 data-aos="fade-down">Connecting Talent with Opportunity</h1>
            <p class="lead" data-aos="fade-up" data-aos-delay="100">Find jobs, post openings, and grow your professional network.</p>
            <a href="#" class="btn btn-light btn-lg me-2" data-aos="fade-up" data-aos-delay="200">Find Jobs & Internships</a>
            <a href="#" class="btn btn-outline-light btn-lg" data-aos="fade-up" data-aos-delay="300">Hire Top Talent</a>
        </div>
    </header>

    <!-- How It Works -->
    <section class="container py-5">
        <h2 class="text-center mb-5 display-4 font-weight-bold" data-aos="fade-up">How It Works</h2>
        <div class="row text-center">
            <!-- For Students -->
            <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="100">
                <div class="card shadow-lg h-100">
                    <div class="card-body p-4">
                        <div class="icon-wrapper bg-gradient-primary mx-auto mb-4">
                            <i class="fas fa-user-graduate fa-3x text-white"></i>
                        </div>
                        <h4 class="card-title font-weight-bold mb-3">For Students</h4>
                        <p class="card-text text-muted">Create Profile → Match Skills → Apply & Get Hired</p>
                        <a href="#" class="btn btn-outline-primary mt-3">Learn More</a>
                    </div>
                </div>
            </div>

            <!-- For Employers -->
            <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="200">
                <div class="card shadow-lg h-100">
                    <div class="card-body p-4">
                        <div class="icon-wrapper bg-gradient-success mx-auto mb-4">
                            <i class="fas fa-briefcase fa-3x text-white"></i>
                        </div>
                        <h4 class="card-title font-weight-bold mb-3">For Employers</h4>
                        <p class="card-text text-muted">Post Jobs → Find Top Talent → Hire Easily</p>
                        <a href="#" class="btn btn-outline-success mt-3">Learn More</a>
                    </div>
                </div>
            </div>

            <!-- For Professionals -->
            <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="300">
                <div class="card shadow-lg h-100">
                    <div class="card-body p-4">
                        <div class="icon-wrapper bg-gradient-info mx-auto mb-4">
                            <i class="fas fa-chalkboard-teacher fa-3x text-white"></i>
                        </div>
                        <h4 class="card-title font-weight-bold mb-3">For Professionals</h4>
                        <p class="card-text text-muted">Build Profile → Network & Mentor → Access Opportunities</p>
                        <a href="#" class="btn btn-outline-info mt-3">Learn More</a>
                    </div>
                </div>
            </div>
        </div>
    </section>


    <section class="bg-light py-5">
    <div class="container text-center">
        <h2 class="mb-5" data-aos="fade-up">By the Numbers</h2>
        <div class="row" data-aos="fade-up">
            <div class="col-md-3">
                <h3 class="display-4">10,000+</h3>
                <p class="text-muted">Jobs Posted</p>
            </div>
            <div class="col-md-3">
                <h3 class="display-4">50,000+</h3>
                <p class="text-muted">Professionals</p>
            </div>
            <div class="col-md-3">
                <h3 class="display-4">95%</h3>
                <p class="text-muted">Success Rate</p>
            </div>
            <div class="col-md-3">
                <h3 class="display-4">100+</h3>
                <p class="text-muted">Companies Hiring</p>
            </div>
            </div>
        </div>
    </section>

    <!-- Testimonials -->
    <section class="bg-light py-5">
        <div class="container">
            <h2 class="text-center mb-5" data-aos="fade-up">What Our Users Say</h2>
            <div class="row">
                <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <p class="card-text">"This platform helped me land my dream job right after graduation!"</p>
                            <p class="text-muted">— Jane Doe, Software Engineer</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <p class="card-text">"We found the perfect candidate for our team in just a few days."</p>
                            <p class="text-muted">— John Smith, Hiring Manager</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="300">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <p class="card-text">"Networking with professionals here has been invaluable for my career growth."</p>
                            <p class="text-muted">— Emily Johnson, Marketing Professional</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Jobs -->
    <section class="container py-5">
        <h2 class="text-center mb-5" data-aos="fade-up">Featured Jobs</h2>
        <div class="row">
            <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="100">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title">Software Engineer</h5>
                        <p class="card-text">Join a leading tech company and work on cutting-edge projects.</p>
                        <a href="#" class="btn btn-primary">Apply Now</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="200">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title">Marketing Manager</h5>
                        <p class="card-text">Lead marketing campaigns for a global brand.</p>
                        <a href="#" class="btn btn-primary">Apply Now</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="300">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title">Data Analyst</h5>
                        <p class="card-text">Work with big data and drive business decisions.</p>
                        <a href="#" class="btn btn-primary">Apply Now</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="container py-5">
    <h2 class="text-center mb-5" data-aos="fade-up">Frequently Asked Questions</h2>
    <div class="accordion" id="faqAccordion">
        <div class="accordion-item" data-aos="fade-up" data-aos-delay="100">
            <h2 class="accordion-header" id="headingOne">
                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                    How do I create a profile?
                </button>
            </h2>
            <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#faqAccordion">
                <div class="accordion-body">
                    Creating a profile is easy! Just sign up, fill in your details, and start exploring opportunities.
                </div>
            </div>
        </div>
        <div class="accordion-item" data-aos="fade-up" data-aos-delay="200">
            <h2 class="accordion-header" id="headingTwo">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                    Is the platform free to use?
                </button>
            </h2>
            <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#faqAccordion">
                <div class="accordion-body">
                    Yes, our platform is completely free for students and professionals. Employers may have premium options.
                </div>
            </div>
         </div>
        </div>
    </section>

    <!-- Call-to-Action Banner -->
    <section class="bg-dark text-white text-center py-5">
        <div class="container">
            <h2 data-aos="fade-up">Ready to Take the Next Step?</h2>
            <p class="lead" data-aos="fade-up" data-aos-delay="100">Join thousands of professionals and companies achieving their goals.</p>
            <a href="#" class="btn btn-light btn-lg" data-aos="fade-up" data-aos-delay="200">Sign Up Now</a>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <!-- Bootstrap JS and AOS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init();
    </script>
</body>
</html>