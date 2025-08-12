<?php
require_once 'config/dbcon.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']) || isset($_SESSION['stud_id']);

// Get search parameters
$search = $_GET['search'] ?? '';
$location = $_GET['location'] ?? '';
$category = $_GET['category'] ?? '';
$type = $_GET['type'] ?? '';

// Build the query
$query = "SELECT jp.*, 
                 e.company_name,
                 e.company_logo,
                 jt.job_type_title,
                 CONCAT(u.user_first_name, ' ', u.user_last_name) AS employer_name
          FROM job_posting jp
          JOIN employer e ON jp.employer_id = e.employer_id
          JOIN job_type jt ON jp.job_type_id = jt.job_type_id
          JOIN user u ON e.user_id = u.user_id
          WHERE jp.deleted_at IS NULL 
            AND jp.moderation_status = 'Approved'
            AND (jp.expires_at IS NULL OR jp.expires_at > NOW())";

$params = [];

if (!empty($search)) {
    $query .= " AND (jp.title LIKE ? OR jp.description LIKE ? OR e.company_name LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if (!empty($location)) {
    $query .= " AND jp.location LIKE ?";
    $params[] = "%$location%";
}

if (!empty($category)) {
    $query .= " AND jt.job_type_title = ?";
    $params[] = $category;
}

$query .= " ORDER BY jp.posted_at DESC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get job types for filter
$jobTypesQuery = "SELECT DISTINCT job_type_title FROM job_type ORDER BY job_type_title";
$jobTypesStmt = $conn->prepare($jobTypesQuery);
$jobTypesStmt->execute();
$jobTypes = $jobTypesStmt->fetchAll(PDO::FETCH_COLUMN);

include 'includes/navbar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find Jobs | CareerQuest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        :root {
            --primary-color: #0A2647;
            --secondary-color: #2C7865;
            --accent-color: #FFD700;
            --text-dark: #212529;
            --text-light: #6C757D;
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            --shadow-sm: 0 2px 8px rgba(0,0,0,0.08);
            --shadow-md: 0 4px 20px rgba(0,0,0,0.12);
        }

        .hero-section {
            background: linear-gradient(135deg, var(--primary-color), #1C4B82);
            color: white;
            padding: 3rem 0;
        }

        .job-card {
            border: none;
            border-radius: 20px;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            overflow: hidden;
            background: white;
            border-left: 5px solid var(--primary-color);
            margin-bottom: 1.5rem;
        }

        .job-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
            border-left-color: var(--secondary-color);
        }

        .job-badge {
            background: linear-gradient(135deg, var(--accent-color), #FFA500);
            color: var(--text-dark);
            font-weight: 600;
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-apply {
            background: linear-gradient(135deg, var(--secondary-color), #1E5A4A);
            border: none;
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 25px;
            font-weight: 600;
            transition: var(--transition);
            font-size: 0.9rem;
        }

        .btn-apply:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(44, 120, 101, 0.4);
            color: white;
        }

        .search-box {
            background: transparent;
            border-radius: 50px;
            padding: 0.5rem 1rem;
            border: 1px solid rgba(10, 38, 71, 0.2);
            transition: var(--transition);
            font-size: 0.95rem;
        }

        .search-box:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(10, 38, 71, 0.1);
            outline: none;
            background: white;
        }

        .input-group-text {
            background: transparent;
            border: 1px solid rgba(10, 38, 71, 0.2);
            border-right: none;
            border-radius: 50px 0 0 50px;
        }

        .input-group .search-box {
            border-left: none;
            border-radius: 0 50px 50px 0;
        }

        .btn-search {
            background: var(--primary-color);
            border: none;
            color: white;
            border-radius: 50px;
            padding: 0.5rem 1.5rem;
            font-weight: 600;
            transition: var(--transition);
        }

        .btn-search:hover {
            background: var(--secondary-color);
        }

        .filter-toggle {
            background: var(--primary-color);
            color: white;
            border-radius: 25px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
        }

        .filter-toggle:hover {
            background: var(--secondary-color);
            color: white;
        }

        .search-section {
            background: transparent;
            padding: 1rem 0;
        }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold mb-4 animate__animated animate__fadeInUp">
                        Find Your Dream Job
                    </h1>
                    <p class="lead mb-4 animate__animated animate__fadeInUp animate__delay-1s">
                        Discover thousands of opportunities from top companies and start your career journey today.
                    </p>
                </div>
                <div class="col-lg-6 text-center">
                    <div class="animate__animated animate__fadeInRight animate__delay-1s">
                        <i class="fas fa-briefcase" style="font-size: 8rem; opacity: 0.2;"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Search Bar -->
    <section class="search-section py-4">
        <div class="container">
            <form method="GET" action="jobs.php">
                <div class="row g-3 align-items-center">
                    <div class="col-md-10">
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="text" class="form-control search-box" 
                                   name="search" placeholder="Job title, keywords, or company" 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-search w-100">
                            <i class="fas fa-search me-2"></i>Search
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </section>

    <!-- Filter Section -->
    <section class="py-2">
        <div class="container">
            <div class="mb-3">
                <button class="btn filter-toggle" type="button" data-bs-toggle="collapse" 
                        data-bs-target="#filterCollapse" aria-expanded="false" aria-controls="filterCollapse">
                    <i class="fas fa-filter me-2"></i>Filter Jobs
                </button>
            </div>
            <div class="collapse" id="filterCollapse">
                <div class="search-card">
                    <form method="GET" action="jobs.php">
                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0">
                                        <i class="fas fa-map-marker-alt text-muted"></i>
                                    </span>
                                    <input type="text" class="form-control search-box border-start-0" 
                                           name="location" placeholder="Location" 
                                           value="<?php echo htmlspecialchars($location); ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <select class="form-select search-box" name="category">
                                    <option value="">All Categories</option>
                                    <?php foreach ($jobTypes as $jobType): ?>
                                        <option value="<?php echo htmlspecialchars($jobType); ?>" 
                                                <?php echo ($category === $jobType) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($jobType); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100 rounded-pill">
                                    <i class="fas fa-filter me-2"></i>Apply Filters
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Job Listings -->
    <section class="py-4">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2 class="fw-bold text-dark mb-1">Job Opportunities</h2>
                            <p class="text-muted mb-0">Find your next career move</p>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-primary fs-6 px-3 py-2"><?php echo count($jobs); ?> jobs found</span>
                        </div>
                    </div>

                    <?php if (empty($jobs)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-search fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">No jobs found</h4>
                            <p class="text-muted">Try adjusting your search criteria or check back later for new opportunities.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($jobs as $job): ?>
                            <div class="job-card">
                                <div class="card-body p-4">
                                    <div class="row align-items-center">
                                        <div class="col-md-9">
                                            <h5 class="fw-bold mb-2"><?php echo htmlspecialchars($job['title']); ?></h5>
                                            <p class="text-muted mb-2"><?php echo htmlspecialchars($job['company_name']); ?></p>
                                            <div class="d-flex align-items-center mb-3 flex-wrap">
                                                <span class="badge bg-light text-dark me-2 mb-1">
                                                    <i class="fas fa-map-marker-alt me-1"></i>
                                                    <?php echo htmlspecialchars($job['location']); ?>
                                                </span>
                                                <span class="badge bg-light text-dark me-2 mb-1">
                                                    <i class="fas fa-clock me-1"></i>
                                                    Full-time
                                                </span>
                                                <span class="badge bg-light text-dark me-2 mb-1">
                                                    <i class="fas fa-calendar me-1"></i>
                                                    <?php echo date('M j', strtotime($job['posted_at'])); ?>
                                                </span>
                                            </div>
                                            <p class="text-muted mb-0" style="line-height: 1.6;">
                                                <?php echo htmlspecialchars(substr($job['description'], 0, 200)) . (strlen($job['description']) > 200 ? '...' : ''); ?>
                                            </p>
                                        </div>
                                        <div class="col-md-3 text-end d-flex flex-column align-items-end">
                                            <div class="mb-3">
                                                <span class="job-badge"><?php echo htmlspecialchars($job['job_type_title']); ?></span>
                                            </div>
                                            <?php if ($isLoggedIn): ?>
                                                <a href="dashboard/student_job.php?action=get_job_details&job_id=<?php echo $job['job_id']; ?>" 
                                                   class="btn btn-apply">
                                                    <i class="fas fa-paper-plane me-1"></i>Apply Now
                                                </a>
                                            <?php else: ?>
                                                <button class="btn btn-apply" data-bs-toggle="modal" data-bs-target="#loginModal">
                                                    <i class="fas fa-sign-in-alt me-1"></i>Login to view details
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Animate job cards on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate__animated', 'animate__fadeInUp');
                }
            });
        }, observerOptions);

        document.querySelectorAll('.job-card').forEach(el => {
            observer.observe(el);
        });
    </script>
</body>
</html>