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


// Query to fetch featured jobs
try {
    $query = "
        SELECT 
            jp.job_id,
            jp.title,
            jp.description,
            jp.location,
            jp.min_salary,
            jp.max_salary,
            jp.salary_type,
            jp.salary_disclosure,
            jp.posted_at,
            jp.expires_at,
            jt.job_type_title,
            e.company_name
        FROM 
            job_posting jp
            INNER JOIN employer e ON jp.employer_id = e.employer_id
            INNER JOIN job_type jt ON jp.job_type_id = jt.job_type_id
        WHERE 
            jp.moderation_status = 'Approved'
            AND (jp.expires_at IS NULL OR jp.expires_at > NOW())
            AND jp.deleted_at IS NULL
        ORDER BY jp.posted_at DESC
        LIMIT 3
    ";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("Featured jobs retrieved: " . count($jobs));
    if (empty($jobs)) {
        error_log("No jobs found with query: " . $query);
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $jobs = [];
}
function formatSalary($min_salary, $max_salary, $salary_type, $salary_disclosure) {
    if (!$salary_disclosure) {
        return 'Not Disclosed';
    }
    if ($min_salary === null && $max_salary === null) {
        return $salary_type === 'Negotiable' ? 'Negotiable' : 'Not Disclosed';
    }

    $formatted = '';
    switch ($salary_type) {
        case 'Hourly':
            $suffix = '/hr';
            break;
        case 'Weekly':
            $suffix = '/wk';
            break;
        case 'Monthly':
            $suffix = '/mo';
            break;
        case 'Yearly':
            $suffix = '/yr';
            break;
        case 'Commission':
            $suffix = ' (Commission)';
            break;
        case 'Negotiable':
            $suffix = ' (Negotiable)';
            break;
        default:
            $suffix = '';
    }

    if ($min_salary !== null && $max_salary !== null) {
        $formatted = '$' . number_format($min_salary, 2) . ' - $' . number_format($max_salary, 2) . $suffix;
    } elseif ($min_salary !== null) {
        $formatted = '$' . number_format($min_salary, 2) . '+' . $suffix;
    } elseif ($max_salary !== null) {
        $formatted = 'Up to $' . number_format($max_salary, 2) . $suffix;
    }

    return $formatted;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Career Quest - Find Your Dream Job</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- AOS (Animate On Scroll) -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
        }

        /* Hero Section */
        .hero-section {
            background: #f2f2f1;
            position: relative;
            color: white;
            padding: 120px 0 100px;
            overflow: hidden;
            background-size: 200% 200%;
            animation: gradientBG 15s ease infinite;
        }

        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiPjxkZWZzPjxwYXR0ZXJuIGlkPSJwYXR0ZXJuIiB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHBhdHRlcm5Vbml0cz0idXNlclNwYWNlT25Vc2UiIHBhdHRlcm5UcmFuc2Zvcm09InJvdGF0ZSg0NSkiPjxyZWN0IHdpZHRoPSIyMCIgaGVpZ2h0PSIyMCIgZmlsbD0icmdiYSgyNTUsMjU1LDI1NSwwLjA1KSIvPjwvcGF0dGVybj48L2RlZnM+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0idXJsKCNwYXR0ZXJuKSIvPjwvc3ZnPg==');
            opacity: 0.5;
        }

        .hero-content {
            position: relative;
            z-index: 1;
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 800;
            line-height: 1.2;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
            color: #0A2647;
        }

        .hero-subtitle {
            font-size: 1.5rem;
            opacity: 0.9;
            margin-bottom: 2rem;
            color: #0a264773;
        }

        /* Buttons */
        .btn {
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }

        .btn:active {
            transform: translateY(0);
        }

        .btn-primary {
            background: var(--gradient-primary);
            background-size: 200% auto;
        }

        .btn-primary:hover {
            background-position: right center;
        }

        .btn-secondary {
            background: var(--gradient-secondary);
            background-size: 200% auto;
            color: white;
        }

        .btn-secondary:hover {
            background-position: right center;
            color: white;
        }

        .btn-accent {
            background: var(--gradient-accent);
            background-size: 200% auto;
            color: var(--text-dark);
        }

        .btn-accent:hover {
            background-position: right center;
            color: var(--text-dark);
        }

        .btn-outline-light {
            border: 2px solid white;
            background: transparent;
            color: white;
        }

        .btn-outline-light:hover {
            background: white;
            color: var(--primary-color);
        }
        .btn-outline-dark {
            color: #212529;        
            border-color: #212529;  
        }

        .btn-outline-dark:hover {
            background-color: #212529; 
            color: #fff;             
        }


        /* Cards */
        .card {
            border: none;
            border-radius: 12px;
            transition: all 0.3s ease;
            overflow: hidden;
            background: white;
            box-shadow: 0 6px 15px rgba(0,0,0,0.05);
            height: 100%;
        }

        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }

        .card-body {
            padding: 2rem;
        }

        .card-title {
            font-weight: 700;
            margin-bottom: 1rem;
        }

        /* Stats Section */
        .stat-item {
            text-align: center;
            padding: 1.5rem;
            position: relative;
        }

        .stat-number {
            font-size: 3.5rem;
            font-weight: 700;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
            line-height: 1;
        }

        .stat-label {
            color: var(--text-light);
            font-size: 1.1rem;
        }

        /* Testimonials */
        .testimonial-card {
            border-left: 4px solid var(--primary-color);
            position: relative;
        }

        .testimonial-card::before {
            content: '"';
            position: absolute;
            top: 0;
            left: 1rem;
            font-size: 5rem;
            color: rgba(10, 38, 71, 0.1);
            font-family: Georgia, serif;
            line-height: 1;
        }

        .testimonial-text {
            font-style: italic;
            position: relative;
            z-index: 1;
        }

        /* Job Cards */
        .job-card {
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }

        .job-card:hover {
            border-left: 4px solid var(--primary-color);
        }

        .job-type {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            background: rgba(10, 38, 71, 0.1);
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        /* FAQ */
        .accordion-button {
            font-weight: 600;
            padding: 1.25rem;
        }

        .accordion-button:not(.collapsed) {
            background-color: rgba(10, 38, 71, 0.05);
            color: var(--primary-color);
        }

        .accordion-button:focus {
            box-shadow: none;
            border-color: rgba(10, 38, 71, 0.1);
        }

        /* Section Styling */
        section {
            padding: 5rem 0;
            position: relative;
        }

        .section-title {
            font-weight: 800;
            margin-bottom: 3rem;
            position: relative;
            display: inline-block;
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

        /* Icon Wrapper */
        .icon-wrapper {
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            margin: 0 auto 1.5rem;
            background: var(--gradient-primary);
            color: white;
            font-size: 2rem;
            box-shadow: 0 4px 15px rgba(10, 38, 71, 0.2);
        }

        /* CTA Section */
        .cta-section {
            background: var(--gradient-primary);
            position: relative;
            overflow: hidden;
        }

        .cta-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        }

        /* Modal Enhancements */
        .modal-content {
            border: none;
            border-radius: 12px;
            overflow: hidden;
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

        /* Floating Action Button */
        .fab {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: var(--gradient-primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            box-shadow: 0 6px 20px rgba(10, 38, 71, 0.3);
            z-index: 1000;
            transition: all 0.3s ease;
            animation: pulse 2s infinite;
        }

        .fab:hover {
            transform: scale(1.1) translateY(-5px);
            color: white;
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(10, 38, 71, 0.4); }
            70% { box-shadow: 0 0 0 15px rgba(10, 38, 71, 0); }
            100% { box-shadow: 0 0 0 0 rgba(10, 38, 71, 0); }
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }
            
            .hero-subtitle {
                font-size: 1.2rem;
            }
            
            section {
                padding: 3rem 0;
            }
            
            .stat-number {
                font-size: 2.5rem;
            }
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary-dark);
        }

        /* Animated Underline */
        .animated-underline {
            position: relative;
            display: inline-block;
        }

        .animated-underline::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 2px;
            bottom: -2px;
            left: 0;
            background: var(--gradient-primary);
            transform: scaleX(0);
            transform-origin: bottom right;
            transition: transform 0.3s ease;
        }

        .animated-underline:hover::after {
            transform: scaleX(1);
            transform-origin: bottom left;
        }

        /* Loading Animation */
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
            margin-left: 8px;
            vertical-align: middle;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <!-- Hero Section -->
    <header class="hero-section">
        <div class="container hero-content">
            <div class="row align-items-center">
                <div class="col-lg-6" data-aos="fade-right">
                    <h1 class="hero-title animate__animated animate__fadeInDown">Launch Your <span class="animated-underline">Dream Career</span> Today</h1>
                    <p class="hero-subtitle animate__animated animate__fadeIn animate__delay-1s">Connect with top employers and find opportunities that match your skills and aspirations.</p>
                    <div class="d-flex gap-3 animate__animated animate__fadeIn animate__delay-2s">
                        <a href="#" class="btn btn-accent btn-lg" data-bs-toggle="modal" data-bs-target="#signupModal">
                            Get Started <i class="fas fa-arrow-right ms-2"></i>
                        </a>
                        <a href="#" class="btn btn-outline-dark btn-lg">
                            Explore Jobs <i class="fas fa-search ms-2"></i>
                        </a>
                    </div>
                </div>
                <div class="col-lg-6 d-none d-lg-block" data-aos="fade-left">
                    <img src="https://images.unsplash.com/photo-1521791136064-7986c2920216?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60" alt="Career Growth" class="img-fluid rounded-3 shadow-lg" style="transform: perspective(1000px) rotateY(-15deg);">
                </div>
            </div>
        </div>
    </header>

    <!-- Trust Badges -->
    <div class="bg-white py-4 shadow-sm">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-auto text-center px-4">
                    <p class="mb-0 text-muted"><i class="fas fa-check-circle text-success me-2"></i> Trusted by 10,000+ professionals</p>
                </div>
                <div class="col-auto text-center px-4">
                    <p class="mb-0 text-muted"><i class="fas fa-star text-warning me-2"></i> 4.9/5 Average Rating</p>
                </div>
                <div class="col-auto text-center px-4">
                    <p class="mb-0 text-muted"><i class="fas fa-briefcase text-primary me-2"></i> 5,000+ Job Openings</p>
                </div>
            </div>
        </div>
    </div>

    <!-- How It Works -->
    <section class="container">
        <h2 class="text-center section-title" data-aos="fade-up">How Career Quest Works</h2>
        <p class="text-center text-muted mb-5" data-aos="fade-up" data-aos-delay="100">Simple steps to achieve your career goals</p>
        
        <div class="row g-4">
            <!-- Step 1 -->
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                <div class="card h-100 border-0">
                    <div class="card-body text-center p-4">
                        <div class="position-relative mb-4">
                            <div class="icon-wrapper">
                                <i class="fas fa-user-edit"></i>
                            </div>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-accent text-dark">1</span>
                        </div>
                        <h4 class="card-title mb-3">Create Your Profile</h4>
                        <p class="card-text text-muted">Build a professional profile highlighting your skills, education, and experience.</p>
                    </div>
                </div>
            </div>
            
            <!-- Step 2 -->
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                <div class="card h-100 border-0">
                    <div class="card-body text-center p-4">
                        <div class="position-relative mb-4">
                            <div class="icon-wrapper" style="background: var(--gradient-secondary);">
                                <i class="fas fa-search"></i>
                            </div>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-accent text-dark">2</span>
                        </div>
                        <h4 class="card-title mb-3">Find Opportunities</h4>
                        <p class="card-text text-muted">Discover jobs, internships, and projects that match your profile and interests.</p>
                    </div>
                </div>
            </div>
            
            <!-- Step 3 -->
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
                <div class="card h-100 border-0">
                    <div class="card-body text-center p-4">
                        <div class="position-relative mb-4">
                            <div class="icon-wrapper" style="background: var(--gradient-accent);">
                                <i class="fas fa-bullseye"></i>
                            </div>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-accent text-dark">3</span>
                        </div>
                        <h4 class="card-title mb-3">Achieve Success</h4>
                        <p class="card-text text-muted">Apply, interview, and land your dream opportunity with our support.</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-5" data-aos="fade-up" data-aos-delay="400">
            <a href="#" class="btn btn-primary btn-lg px-5" data-bs-toggle="modal" data-bs-target="#signupModal">
                Start Your Journey <i class="fas fa-arrow-right ms-2"></i>
            </a>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="bg-light position-relative">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-3 col-6" data-aos="fade-up">
                    <div class="stat-item">
                        <div class="stat-number countup" data-count="<?php echo $jobsPosted; ?>">0</div>
                        <p class="stat-label">Jobs Posted</p>
                    </div>
                </div>
                <div class="col-md-3 col-6" data-aos="fade-up" data-aos-delay="100">
                    <div class="stat-item">
                        <div class="stat-number countup" data-count="<?php echo $professionals; ?>">0</div>
                        <p class="stat-label">Professionals</p>
                    </div>
                </div>
                <div class="col-md-3 col-6" data-aos="fade-up" data-aos-delay="200">
                    <div class="stat-item">
                        <div class="stat-number countup" data-count="<?php echo $successRate; ?>">0</div>
                        <p class="stat-label">Success Rate</p>
                    </div>
                </div>
                <div class="col-md-3 col-6" data-aos="fade-up" data-aos-delay="300">
                    <div class="stat-item">
                        <div class="stat-number countup" data-count="<?php echo $companies; ?>">0</div>
                        <p class="stat-label">Companies Hiring</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials -->
    <section class="container">
        <h2 class="text-center section-title" data-aos="fade-up">Success Stories</h2>
        <p class="text-center text-muted mb-5" data-aos="fade-up" data-aos-delay="100">Hear from people who found their dream jobs</p>
        
        <div class="row g-4">
            <!-- Testimonial 1 -->
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                <div class="card testimonial-card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <img src="https://randomuser.me/api/portraits/women/32.jpg" alt="Jane Doe" class="rounded-circle me-3" width="60">
                            <div>
                                <h5 class="mb-0">Jane Doe</h5>
                                <p class="text-muted mb-0">Software Engineer</p>
                            </div>
                        </div>
                        <p class="testimonial-text">"Career Quest helped me transition from a non-tech background to a software engineering role at a top tech company. The resources and network were invaluable!"</p>
                        <div class="mt-3">
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Testimonial 2 -->
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                <div class="card testimonial-card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <img src="https://randomuser.me/api/portraits/men/45.jpg" alt="John Smith" class="rounded-circle me-3" width="60">
                            <div>
                                <h5 class="mb-0">John Smith</h5>
                                <p class="text-muted mb-0">Marketing Director</p>
                            </div>
                        </div>
                        <p class="testimonial-text">"As an employer, I've found exceptional talent through Career Quest. The platform's matching algorithm saves us countless hours in the hiring process."</p>
                        <div class="mt-3">
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Testimonial 3 -->
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
                <div class="card testimonial-card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <img src="https://randomuser.me/api/portraits/women/65.jpg" alt="Emily Johnson" class="rounded-circle me-3" width="60">
                            <div>
                                <h5 class="mb-0">Emily Johnson</h5>
                                <p class="text-muted mb-0">Data Scientist</p>
                            </div>
                        </div>
                        <p class="testimonial-text">"The mentorship program connected me with industry leaders who provided guidance that was crucial for my career advancement. Highly recommend!"</p>
                        <div class="mt-3">
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star-half-alt text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>


    <section class="bg-light">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center mb-5">
                <div>
                    <h2 class="section-title" data-aos="fade-up">Featured Opportunities</h2>
                    <p class="text-muted" data-aos="fade-up" data-aos-delay="100">Browse our latest job openings</p>
                </div>
                <div data-aos="fade-up" data-aos-delay="200">
                    <a href="all_jobs.php" class="btn btn-outline-primary">View All Jobs <i class="fas fa-arrow-right ms-2"></i></a>
                </div>
            </div>
            
            <div class="row g-4">
                <?php if (!empty($jobs)): ?>
                    <?php foreach ($jobs as $index => $job): ?>
                        <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="<?php echo 100 * ($index + 1); ?>">
                            <div class="card job-card h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <span class="job-type"><?php echo htmlspecialchars($job['job_type_title']); ?></span>
                                        </div>
                                        <div>
                                            <i class="far fa-bookmark text-primary"></i>
                                        </div>
                                    </div>
                                    <h4 class="card-title"><?php echo htmlspecialchars($job['title']); ?></h4>
                                    <p class="text-muted mb-3"><?php echo htmlspecialchars($job['company_name']); ?></p>
                                    <div class="d-flex align-items-center mb-3">
                                        <i class="fas fa-map-marker-alt text-muted me-2"></i>
                                        <span class="text-muted"><?php echo htmlspecialchars($job['location'] ?: 'Not Specified'); ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="badge bg-primary bg-opacity-10 text-primary"><?php echo formatSalary($job['min_salary'], $job['max_salary'], $job['salary_type'], $job['salary_disclosure']); ?></span>
                                        <a href="auth/login_student.php?id=<?php echo $job['job_id']; ?>" class="btn btn-sm btn-primary">Apply Now</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center">
                        <div class="alert alert-info">
                            No featured job opportunities available at the moment.
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="container">
        <h2 class="text-center section-title" data-aos="fade-up">Frequently Asked Questions</h2>
        <p class="text-center text-muted mb-5" data-aos="fade-up" data-aos-delay="100">Find answers to common questions about Career Quest</p>
        
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="accordion" id="faqAccordion">
                    <!-- Question 1 -->
                    <div class="accordion-item border-0 mb-3 shadow-sm" data-aos="fade-up" data-aos-delay="100">
                        <h2 class="accordion-header" id="headingOne">
                            <button class="accordion-button rounded-3 collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                                <i class="fas fa-question-circle text-primary me-3"></i> How do I create a profile?
                            </button>
                        </h2>
                        <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Creating a profile is simple! Click on the "Sign Up" button, select your role (student, professional, or employer), and follow the step-by-step process to complete your profile. You'll need to provide basic information, education details, work experience (if any), and skills.
                            </div>
                        </div>
                    </div>
                    
                    <!-- Question 2 -->
                    <div class="accordion-item border-0 mb-3 shadow-sm" data-aos="fade-up" data-aos-delay="200">
                        <h2 class="accordion-header" id="headingTwo">
                            <button class="accordion-button rounded-3 collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                <i class="fas fa-question-circle text-primary me-3"></i> Is the platform free to use?
                            </button>
                        </h2>
                        <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Yes, Career Quest is completely free for students and professionals to create profiles, search for jobs, and apply for opportunities. Employers can post a limited number of jobs for free, with premium options available for additional features and visibility.
                            </div>
                        </div>
                    </div>
                    
                    <!-- Question 3 -->
                    <div class="accordion-item border-0 mb-3 shadow-sm" data-aos="fade-up" data-aos-delay="300">
                        <h2 class="accordion-header" id="headingThree">
                            <button class="accordion-button rounded-3 collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                <i class="fas fa-question-circle text-primary me-3"></i> How can I find job opportunities?
                            </button>
                        </h2>
                        <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                After completing your profile, you can browse job listings in the "Jobs" section. Our smart matching algorithm will also recommend relevant opportunities based on your skills and preferences. You can set up job alerts to receive notifications when new positions matching your criteria are posted.
                            </div>
                        </div>
                    </div>

                    <!-- Question 4 -->
                    <div class="accordion-item border-0 mb-3 shadow-sm" data-aos="fade-up" data-aos-delay="400">
                        <h2 class="accordion-header" id="headingFour">
                            <button class="accordion-button rounded-3 collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                                <i class="fas fa-question-circle text-primary me-3"></i> Can I post a job as an employer?
                            </button>
                        </h2>
                        <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Absolutely! Employers can post job listings by creating an employer account. Once your account is verified, you can post jobs, view candidate profiles, and contact potential hires directly through the platform. Premium employer accounts offer additional features like featured job listings and advanced candidate search filters.
                            </div>
                        </div>
                    </div>

                    <!-- Question 5 -->
                    <div class="accordion-item border-0 mb-3 shadow-sm" data-aos="fade-up" data-aos-delay="500">
                        <h2 class="accordion-header" id="headingFive">
                            <button class="accordion-button rounded-3 collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive">
                                <i class="fas fa-question-circle text-primary me-3"></i> How does the mentorship program work?
                            </button>
                        </h2>
                        <div id="collapseFive" class="accordion-collapse collapse" aria-labelledby="headingFive" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Our mentorship program connects students and early-career professionals with experienced industry mentors. After completing your profile, you can browse mentor profiles and request mentorship sessions. Mentors offer guidance on career development, resume reviews, interview preparation, and industry insights. Both one-time and ongoing mentorship arrangements are available.
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-5" data-aos="fade-up" data-aos-delay="600">
                    <p class="mb-3">Still have questions?</p>
                    <a href="contact.php" class="btn btn-outline-primary px-4">Contact Support</a>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section text-white">
        <div class="container text-center">
            <h2 class="display-5 fw-bold mb-4" data-aos="fade-up">Ready to Transform Your Career?</h2>
            <p class="lead mb-5 opacity-75" data-aos="fade-up" data-aos-delay="100">Join thousands of professionals and companies achieving their goals with Career Quest.</p>
            <div class="d-flex justify-content-center gap-3" data-aos="fade-up" data-aos-delay="200">
                <a href="#" class="btn btn-light btn-lg px-5" data-bs-toggle="modal" data-bs-target="#signupModal">
                    Get Started <i class="fas fa-arrow-right ms-2"></i>
                </a>
                <a href="#" class="btn btn-outline-light btn-lg px-5">
                    Learn More <i class="fas fa-info-circle ms-2"></i>
                </a>
            </div>
        </div>
    </section>

    <!-- Floating Action Button -->
    <a href="#" class="fab animate__animated animate__bounceIn" data-bs-toggle="modal" data-bs-target="#signupModal">
        <i class="fas fa-user-plus"></i>
    </a>

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
                            <a href="views/register_student.php" class="card role-card h-100 text-decoration-none text-white" style="background: var(--gradient-primary);">
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
                            <a href="views/register_employer.php" class="card role-card h-100 text-decoration-none text-white" style="background: var(--gradient-secondary);">
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
                            <a href="auth/login_student.php" class="card role-card h-100 text-decoration-none text-white" style="background: var(--gradient-primary);">
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
                            <a href="auth/login_employer.php" class="card role-card h-100 text-decoration-none text-white" style="background: var(--gradient-secondary);">
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

    <?php include 'includes/footer.php'; ?>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.11.4/gsap.min.js"></script>
    <script>
        // Initialize AOS
        AOS.init({
            duration: 800,
            easing: 'ease-in-out',
            once: true
        });

        // CountUp Animation
        document.addEventListener('DOMContentLoaded', function() {
            const countups = document.querySelectorAll('.countup');
            
            countups.forEach(countup => {
                const target = parseInt(countup.getAttribute('data-count'));
                const duration = 2000;
                const start = 0;
                const increment = target / (duration / 16);
                let current = start;
                
                const updateCount = () => {
                    current += increment;
                    if (current < target) {
                        countup.textContent = Math.floor(current);
                        requestAnimationFrame(updateCount);
                    } else {
                        countup.textContent = target;
                    }
                };
                
                const observer = new IntersectionObserver((entries) => {
                    if (entries[0].isIntersecting) {
                        updateCount();
                        observer.unobserve(countup);
                    }
                });
                
                observer.observe(countup);
            });

            // Modal transition handling
            const signupModal = document.getElementById('signupModal');
            const loginModal = document.getElementById('loginModal');
            
            if (signupModal && loginModal) {
                signupModal.addEventListener('hidden.bs.modal', function () {
                    if (document.body.classList.contains('modal-open')) {
                        document.body.classList.remove('modal-open');
                        document.body.style.paddingRight = '';
                        document.body.style.overflow = '';
                    }
                });
                
                loginModal.addEventListener('hidden.bs.modal', function () {
                    if (document.body.classList.contains('modal-open')) {
                        document.body.classList.remove('modal-open');
                        document.body.style.paddingRight = '';
                        document.body.style.overflow = '';
                    }
                });
            }

            // Check URL for modal triggers
            function getQueryParam(param) {
                const urlParams = new URLSearchParams(window.location.search);
                return urlParams.get(param);
            }

            if (getQueryParam("openloginModal") === "true") {
                const loginModal = new bootstrap.Modal(document.getElementById("loginModal"));
                loginModal.show();
                history.replaceState(null, "", window.location.pathname);
            }

            if (getQueryParam("opensignupModal") === "true") {
                const signupModal = new bootstrap.Modal(document.getElementById("signupModal"));
                signupModal.show();
                history.replaceState(null, "", window.location.pathname);
            }

            // Add loading state to buttons
            document.querySelectorAll('a[href="#"], button').forEach(button => {
                button.addEventListener('click', function(e) {
                    if (this.getAttribute('href') === '#') {
                        e.preventDefault();
                        const originalText = this.innerHTML;
                        this.innerHTML = 'Loading <span class="loading-spinner"></span>';
                        
                        // Simulate loading
                        setTimeout(() => {
                            this.innerHTML = originalText;
                        }, 1500);
                    }
                });
            });

            // Smooth scrolling for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const targetId = this.getAttribute('href');
                    if (targetId === '#') return;
                    
                    const targetElement = document.querySelector(targetId);
                    if (targetElement) {
                        window.scrollTo({
                            top: targetElement.offsetTop - 80,
                            behavior: 'smooth'
                        });
                    }
                });
            });
        });

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

        // Animate elements on scroll
        gsap.registerPlugin(ScrollTrigger);
        
        gsap.utils.toArray('.animate-on-scroll').forEach(element => {
            gsap.from(element, {
                scrollTrigger: {
                    trigger: element,
                    start: "top 80%",
                    toggleActions: "play none none none"
                },
                opacity: 0,
                y: 50,
                duration: 0.8,
                ease: "power2.out"
            });
        });
    </script>
</body>
</html>