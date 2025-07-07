<?php
session_start();
if (isset($_SESSION['user_id'])) { 

    if (isset($_SESSION['role_id'])) {
        switch ($_SESSION['role_id']) {
            case 1: 
                header("Location: dashboard/admin.php");
                break;
            case 2: 
                header("Location: dashboard/employer.php");
                break;
            case 3: 
                header("Location: dashboard/professional.php");
                break;
            default:
                header("Location: dashboard/moderator.php"); 
                break;
        }
    } else {
        session_destroy();
        header("Location: index.php"); 
    }
    exit();
} elseif (isset($_SESSION['stud_id'])) {
 
    header("Location: dashboard/student.php");
    exit();
}


// Database connection
require_once 'config/dbcon.php';

// Get jobs posted (job_posting table)
$jobsQuery = "SELECT COUNT(*) AS total_jobs FROM job_posting WHERE deleted_at IS NULL";
$jobsResult = $conn->query($jobsQuery);
$jobsPosted = ($jobsResult && $row = $jobsResult->fetch(PDO::FETCH_ASSOC)) ? $row['total_jobs'] : 0;

// Get professionals (user table with role_id = 3 for professionals)
$professionalsQuery = "SELECT COUNT(*) AS total_professionals FROM user WHERE role_id = 3 AND deleted_at IS NULL";
$professionalsResult = $conn->query($professionalsQuery);
$professionals = ($professionalsResult && $row = $professionalsResult->fetch(PDO::FETCH_ASSOC)) ? $row['total_professionals'] : 0;

// Get companies hiring (distinct employer_id from job_posting)
$companiesQuery = "SELECT COUNT(DISTINCT employer_id) AS total_companies FROM job_posting WHERE deleted_at IS NULL";
$companiesResult = $conn->query($companiesQuery);
$companies = ($companiesResult && $row = $companiesResult->fetch(PDO::FETCH_ASSOC)) ? $row['total_companies'] : 0;

// Get success rate (percentage of jobs filled, assuming moderation_status = 'Approved' and not deleted)
$filledJobsQuery = "SELECT COUNT(*) AS filled_jobs FROM job_posting WHERE moderation_status = 'Approved' AND deleted_at IS NULL";
$filledJobsResult = $conn->query($filledJobsQuery);
$filledJobs = ($filledJobsResult && $row = $filledJobsResult->fetch(PDO::FETCH_ASSOC)) ? $row['filled_jobs'] : 0;
$successRate = ($jobsPosted > 0) ? round(($filledJobs / $jobsPosted) * 100) : 0;


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Career Quest</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- AOS (Animate On Scroll) -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
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
            background: linear-gradient(45deg, var(--primary-color), #1C4B82);
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
            background: rgba(10, 38, 71, 0.85); 
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


            /* MODAL */
        .modal-header {
            position: relative;
            padding: 1rem;
        }
        
        .btn-close {
            position: absolute;
            top: 1rem;
            right: 1rem;
        }
        .role-card {
            border: none;
            transition: all 0.3s ease;
            background: var(--primary-color);
            border-radius: 1rem;
            overflow: hidden;
            position: relative;
        }

        .role-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.15));
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .role-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.15);
        }  

        .role-card.active {
            outline: 5px solid #4682b4; /* Blue border for the active card */
            box-shadow: 0 0 10px rgba(0, 123, 255, 0.5); /* Soft glow effect */
        }


        .role-card:hover::before {
            opacity: 1;
        }

        .bg-gradient-primary-hover {
            background: linear-gradient(45deg, var(--primary-color), #1C4B82);
        }

        .bg-gradient-success-hover {
            background: linear-gradient(45deg, var(--secondary-color), #3AA68D);
        }

        .bg-gradient-info-hover {
            background: linear-gradient(45deg, #1E3C72, #2A5298);
        }

        .icon-container {
            transition: transform 0.3s ease;
        }

        .role-card:hover .icon-container {
            transform: scale(1.1);
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
                         <a href="learnmore/student-how-it-works.php" class="btn btn-outline-primary mt-3">Learn More</a>
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
                        <a href="learnmore/employer-how-it-works.php" class="btn btn-outline-success mt-3">Learn More</a>
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
                         <a href="learnmore/applicant-how-it-works.php" class="btn btn-outline-info mt-3">Learn More</a>
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
                    <h3 class="display-4"><?php echo number_format($jobsPosted); ?></h3>
                    <p class="text-muted">Jobs Posted</p>
                </div>
                <div class="col-md-3">
                    <h3 class="display-4"><?php echo number_format($professionals); ?></h3>
                    <p class="text-muted">Professionals</p>
                </div>
                <div class="col-md-3">
                    <h3 class="display-4"><?php echo $successRate; ?>%</h3>
                    <p class="text-muted">Success Rate</p>
                </div>
                <div class="col-md-3">
                    <h3 class="display-4"><?php echo number_format($companies); ?></h3>
                    <p class="text-muted">Companies Hiring</p>
                </div>
            </div>
        </div>
    </section>
    </section>

    <!-- Testimonials -->
    <section class="bg-light py-5">
        <div class="container">
            <h2 class="text-center mb-5" data-aos="fade-up">What Our Users Say</h2>
            <div class="row">
                <!-- Testimonial 1 -->
                <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="card border-0 shadow-sm h-100 rounded-3 hover-effect">
                        <div class="card-body text-center p-4">
                            <p class="card-text fs-5 text-muted mb-3">"This platform helped me land my dream job right after graduation!"</p>
                            <p class="text-muted mb-0">— Jane Doe, Software Engineer</p>
                        </div>
                    </div>
                </div>
                <!-- Testimonial 2 -->
                <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="card border-0 shadow-sm h-100 rounded-3 hover-effect">
                        <div class="card-body text-center p-4">
                            <p class="card-text fs-5 text-muted mb-3">"We found the perfect candidate for our team in just a few days."</p>
                            <p class="text-muted mb-0">— John Smith, Hiring Manager</p>
                        </div>
                    </div>
                </div>
                <!-- Testimonial 3 -->
                <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="300">
                    <div class="card border-0 shadow-sm h-100 rounded-3 hover-effect">
                        <div class="card-body text-center p-4">
                            <p class="card-text fs-5 text-muted mb-3">"Networking with professionals here has been invaluable for my career growth."</p>
                            <p class="text-muted mb-0">— Emily Johnson, Marketing Professional</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
    </section>
    
    <section class="container py-5">
        <h2 class="text-center mb-5" data-aos="fade-up">Featured Jobs</h2>
        <div class="row">
            <!-- Job 1 -->
            <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="100">
                <div class="card shadow-sm h-100 border-0">
                    <div class="card-body">
                        <h5 class="card-title">Software Engineer</h5>
                        <p class="card-text">Join a leading tech company and work on cutting-edge projects.</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">Location: San Francisco, CA</small>
                            <a href="#" class="btn btn-primary">Apply Now</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Job 2 -->
            <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="200">
                <div class="card shadow-sm h-100 border-0">
                    <div class="card-body">
                        <h5 class="card-title">Marketing Manager</h5>
                        <p class="card-text">Lead marketing campaigns for a global brand.</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">Location: New York, NY</small>
                            <a href="#" class="btn btn-primary">Apply Now</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Job 3 -->
            <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="300">
                <div class="card shadow-sm h-100 border-0">
                    <div class="card-body">
                        <h5 class="card-title">Data Analyst</h5>
                        <p class="card-text">Work with big data and drive business decisions.</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">Location: Remote</small>
                            <a href="#" class="btn btn-primary">Apply Now</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="container py-5">
        <h2 class="text-center mb-5" data-aos="fade-up">Frequently Asked Questions</h2>
        <div class="accordion" id="faqAccordion">
            <!-- Question 1 -->
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
            
            <!-- Question 2 -->
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
            
            <!-- Question 3 -->
            <div class="accordion-item" data-aos="fade-up" data-aos-delay="300">
                <h2 class="accordion-header" id="headingThree">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                        How can I find job opportunities?
                    </button>
                </h2>
                <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Students can find jobs and internships in their field of study by browsing through our job listings. Set up alerts to never miss an opportunity!
                    </div>
                </div>
            </div>

            <!-- Question 4 -->
            <div class="accordion-item" data-aos="fade-up" data-aos-delay="400">
                <h2 class="accordion-header" id="headingFour">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                        Can I post a job as an employer?
                    </button>
                </h2>
                <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Yes, employers can post job listings, view candidate profiles, and contact potential candidates directly through the platform. Premium features may offer more visibility.
                    </div>
                </div>
            </div>

            <!-- Question 5 -->
            <div class="accordion-item" data-aos="fade-up" data-aos-delay="500">
                <h2 class="accordion-header" id="headingFive">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive">
                        Can professionals access career guidance and mentoring?
                    </button>
                </h2>
                <div id="collapseFive" class="accordion-collapse collapse" aria-labelledby="headingFive" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Yes, professionals can network with other industry experts, access mentoring sessions, and share their knowledge to help others grow in their careers.
                    </div>
                </div>
            </div>

            <!-- Question 6 -->
            <div class="accordion-item" data-aos="fade-up" data-aos-delay="600">
                <h2 class="accordion-header" id="headingSix">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSix" aria-expanded="false" aria-controls="collapseSix">
                        How do I update my profile information?
                    </button>
                </h2>
                <div id="collapseSix" class="accordion-collapse collapse" aria-labelledby="headingSix" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        To update your profile, simply log in, go to your account settings, and make changes to your information, such as job preferences, skills, and contact details.
                    </div>
                </div>
            </div>

            <!-- Question 7 -->
            <div class="accordion-item" data-aos="fade-up" data-aos-delay="700">
                <h2 class="accordion-header" id="headingSeven">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSeven" aria-expanded="false" aria-controls="collapseSeven">
                        How do I get notifications for job updates?
                    </button>
                </h2>
                <div id="collapseSeven" class="accordion-collapse collapse" aria-labelledby="headingSeven" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        You can set up job alerts based on your preferences (role, location, etc.) so you receive notifications via email when new job opportunities match your criteria.
                    </div>
                </div>
            </div>
            
            <!-- Question 8 -->
            <div class="accordion-item" data-aos="fade-up" data-aos-delay="800">
                <h2 class="accordion-header" id="headingEight">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEight" aria-expanded="false" aria-controls="collapseEight">
                        How can I contact support if I need help?
                    </button>
                </h2>
                <div id="collapseEight" class="accordion-collapse collapse" aria-labelledby="headingEight" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        If you need assistance, you can contact our support team through the help section of the platform or by sending an email to support@ourplatform.com.
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

    
    <!-- Role Selection Modal -->
    <section>
        <div class="modal fade" id="signupModal" tabindex="-1" aria-labelledby="roleSelectionModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header border-0 pb-0">
                        <div class="w-100 text-center">
                            <h3 class="modal-title fw-bold display-6" id="roleSelectionModalLabel">Get Started</h3>
                            <p class="text-muted">Create an Account to Get Started</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-4">
                            <!-- Student Role -->
                            <div class="col-12">
                                <a href="views/register_student.php" class="card role-card h-100 text-decoration-none bg-gradient-primary-hover">
                                    <div class="card-body d-flex align-items-center gap-4 py-4">
                                        <div class="icon-container bg-white bg-opacity-25 p-3 rounded-circle">
                                            <i class="fas fa-user-graduate fa-2x text-white"></i>
                                        </div>
                                        <div class="text-start">
                                            <h4 class="text-white mb-1">Applicant</h4>
                                            <p class="text-white text-opacity-75 mb-0">Find jobs, internships, and career guidance</p>
                                        </div>
                                    </div>
                                </a>
                            </div>

                            <!-- Employer Role -->
                            <div class="col-12">
                                <a href="views/register_employer.php" class="card role-card h-100 text-decoration-none bg-gradient-success-hover">
                                    <div class="card-body d-flex align-items-center gap-4 py-4">
                                        <div class="icon-container bg-white bg-opacity-25 p-3 rounded-circle">
                                            <i class="fas fa-briefcase fa-2x text-white"></i>
                                        </div>
                                        <div class="text-start">
                                            <h4 class="text-white mb-1">Employer</h4>
                                            <p class="text-white text-opacity-75 mb-0">Post jobs and find qualified candidates</p>
                                        </div>
                                    </div>
                                </a>
                            </div>

                            <!-- Professional Role 
                            <div class="col-12">
                                <a href="views/register_professional.php" class="card role-card h-100 text-decoration-none bg-gradient-info-hover">
                                    <div class="card-body d-flex align-items-center gap-4 py-4">
                                        <div class="icon-container bg-white bg-opacity-25 p-3 rounded-circle">
                                            <i class="fas fa-user-tie fa-2x text-white"></i>
                                        </div>
                                        <div class="text-start">
                                            <h4 class="text-white mb-1">Professional</h4>
                                            <p class="text-white text-opacity-75 mb-0">Network, mentor, and access opportunities</p>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            -->
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <p class="text-muted small text-center w-100 mb-0">
                            Already have an account? 
                            <a href="#" data-bs-toggle="modal" data-bs-target="#loginModal"  class="text-decoration-none">Sign In</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>


        <!-- Login Modal -->
        <div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header border-0 pb-0">
                        <div class="w-100 text-center">
                            <h3 class="modal-title fw-bold display-6" id="loginModalLabel">Welcome Aboard</h3>
                            <p class="text-muted">Log in to Your Account</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-4">
                            <!-- Student Login -->
                            <div class="col-12">
                                <a href="auth/login_student.php" class="card role-card h-100 text-decoration-none bg-gradient-primary-hover">
                                    <div class="card-body d-flex align-items-center gap-4 py-4">
                                        <div class="icon-container bg-white bg-opacity-25 p-3 rounded-circle">
                                            <i class="fas fa-user-graduate fa-2x text-white"></i>
                                        </div>
                                        <div class="text-start">
                                            <h4 class="text-white mb-1">Applicant</h4>
                                            <p class="text-white text-opacity-75 mb-0">Access jobs, internships, and career resources</p>
                                        </div>
                                    </div>
                                </a>
                            </div>

                            <!-- Employer Login -->
                            <div class="col-12">
                                <a href="auth/login_employer.php" class="card role-card h-100 text-decoration-none bg-gradient-success-hover">
                                    <div class="card-body d-flex align-items-center gap-4 py-4">
                                        <div class="icon-container bg-white bg-opacity-25 p-3 rounded-circle">
                                            <i class="fas fa-briefcase fa-2x text-white"></i>
                                        </div>
                                        <div class="text-start">
                                            <h4 class="text-white mb-1">Employer</h4>
                                            <p class="text-white text-opacity-75 mb-0">Post jobs and find qualified candidates</p>
                                        </div>
                                    </div>
                                </a>
                            </div>

                            <!-- Professional Login
                            <div class="col-12">
                                <a href="auth/login_user.php" class="card role-card h-100 text-decoration-none bg-gradient-info-hover">
                                    <div class="card-body d-flex align-items-center gap-4 py-4">
                                        <div class="icon-container bg-white bg-opacity-25 p-3 rounded-circle">
                                            <i class="fas fa-user-tie fa-2x text-white"></i>
                                        </div>
                                        <div class="text-start">
                                            <h4 class="text-white mb-1">Professional</h4>
                                            <p class="text-white text-opacity-75 mb-0">Connect, mentor, and explore opportunities</p>
                                        </div>
                                    </div>
                                </a>
                            </div>
                             -->
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <p class="text-muted small text-center w-100 mb-0">
                            Don't have an account? 
                            <a href="#" data-bs-toggle="modal" data-bs-target="#signupModal" class="text-decoration-none">Sign Up</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>


    <?php include 'includes/footer.php'; ?>

    <!-- Bootstrap JS and AOS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init();
    </script>


    <script>
        // Function to get URL parameters
        function getQueryParam(param) {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get(param);
        }

        // Check if "openSignupModal" is set in the URL
        if (getQueryParam("openloginModal") === "true") {
            document.addEventListener("DOMContentLoaded", function () {
                var signupModal = new bootstrap.Modal(document.getElementById("loginModal"));
                signupModal.show();

                // Remove the query parameter after opening the modal
                history.replaceState(null, "", window.location.pathname);
            });
        }
    </script>

    <script>
        // Function to get URL parameters
        function getQueryParam(param) {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get(param);
        }

        // Check if "openSignupModal" is set in the URL
        if (getQueryParam("opensignupModal") === "true") {
            document.addEventListener("DOMContentLoaded", function () {
                var signupModal = new bootstrap.Modal(document.getElementById("signupModal"));
                signupModal.show();

                // Remove the query parameter after opening the modal
                history.replaceState(null, "", window.location.pathname);
            });
        }
    </script>

    <script>
        // Store selected role in localStorage when a card is clicked
        document.querySelectorAll('.role-card').forEach(card => {
            card.addEventListener('click', function() {
                const role = this.querySelector('h4').textContent.toLowerCase();
                localStorage.setItem('chosenRole', role);
            });
        });

        // Highlight the previously selected role when the modal opens
        document.addEventListener('DOMContentLoaded', function () {
            const chosenRole = localStorage.getItem('chosenRole');
            if (chosenRole) {
                // Highlight the corresponding role card
                document.querySelectorAll('.role-card').forEach(card => {
                    if (card.querySelector('h4').textContent.toLowerCase() === chosenRole) {
                        card.classList.add('active');
                    } else {
                        card.classList.remove('active');
                    }
                });
            }
        });
    </script>

</body>
</html>