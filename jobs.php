<?php
require_once 'config/dbcon.php';

// Get search parameters
$search = $_GET['search'] ?? '';
$location = $_GET['location'] ?? '';
$category = $_GET['category'] ?? '';

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

// Get categories for filter
$categoriesQuery = "SELECT DISTINCT category FROM skill_masterlist";
$categoriesStmt = $conn->prepare($categoriesQuery);
$categoriesStmt->execute();
$categories = $categoriesStmt->fetchAll(PDO::FETCH_COLUMN);

function formatDate($dateString) {
    if (!$dateString) return "N/A";
    $date = new DateTime($dateString);
    return $date->format('M j, Y');
}

function getTimeAgo(string $timestamp) {
    $date = new DateTime($timestamp);
    $now = new DateTime();
    $interval = $now->diff($date);

    if ($interval->y > 0) return $interval->y . " year" . ($interval->y > 1 ? 's' : '') . " ago";
    if ($interval->m > 0) return $interval->m . " month" . ($interval->m > 1 ? 's' : '') . " ago";
    if ($interval->d > 0) return $interval->d . " day" . ($interval->d > 1 ? 's' : '') . " ago";
    if ($interval->h > 0) return $interval->h . " hour" . ($interval->h > 1 ? 's' : '') . " ago";
    if ($interval->i > 0) return $interval->i . " minute" . ($interval->i > 1 ? 's' : '') . " ago";
    return "just now";
}

function isJobExpiringSoon($expiryDate) {
    if (!$expiryDate) return false;
    $now = new DateTime();
    $soonDate = (new DateTime())->modify('+7 days');
    $expiry = new DateTime($expiryDate);
    return $expiry <= $soonDate;
}

function sanitize($text) {
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find Jobs | CareerQuest</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
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
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            --shadow-sm: 0 2px 8px rgba(0,0,0,0.08);
            --shadow-md: 0 4px 20px rgba(0,0,0,0.12);
            --card-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --card-hover-shadow: 0 8px 16px rgba(67, 97, 238, 0.15);
        }

        body {
            background-color: var(--background-light);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
        }

        /* Navbar Styling */
        .navbar {
            padding: 0.75rem 0;
            background-color: white !important;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
        }

        .navbar.scrolled {
            box-shadow: var(--shadow-md);
            padding: 0.5rem 0;
        }

        .navbar-brand {
            font-weight: 800;
            background-clip: text;
            -webkit-background-clip: text;
            background-size: 200% auto;
            transition: var(--transition);
        }

        .navbar-brand:hover {
            background-position: right center;
        }

        .nav-link {
            position: relative;
            font-weight: 500;
            color: var(--text-dark) !important;
            transition: var(--transition);
            margin: 0 0.25rem;
        }

        .nav-link-underline {
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 0;
            height: 2px;
            background: var(--primary-color);
            transform: translateX(-50%);
            transition: var(--transition);
        }

        .nav-link:hover .nav-link-underline,
        .nav-link.active .nav-link-underline {
            width: calc(100% - 2rem);
        }

        .nav-link:hover,
        .nav-link.active {
            color: var(--primary-color) !important;
        }

        .dropdown-menu {
            border-radius: 12px !important;
            border: none;
            box-shadow: var(--shadow-md);
            margin-top: 0.5rem !important;
        }

        .dropdown-item {
            border-radius: 8px !important;
            margin: 0.15rem 0.5rem;
            width: auto;
            transition: var(--transition);
        }

        .dropdown-item:hover {
            background-color: var(--primary-light);
            color: var(--primary-color) !important;
        }

        .dropdown-divider {
            opacity: 0.2;
        }

        .btn {
            transition: var(--transition);
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .btn-primary {
            background: var(--gradient-primary);
            background-size: 200% auto;
            border: none;
            box-shadow: 0 4px 15px rgba(10, 38, 71, 0.2);
        }

        .btn-primary:hover {
            background-position: right center;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(10, 38, 71, 0.3);
        }

        .btn-outline-primary {
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            background: transparent;
        }

        .btn-outline-primary:hover {
            background: var(--primary-color);
            color: white !important;
            border-color: var(--primary-color);
        }

        @media (max-width: 991.98px) {
            .navbar-collapse {
                padding: 1rem 0;
                background: white;
                border-radius: 0 0 12px 12px;
                box-shadow: var(--shadow-md);
            }

            .nav-item {
                margin: 0.25rem 0;
            }

            .nav-link {
                padding: 0.75rem 1.25rem !important;
                margin: 0;
            }

            .nav-link-underline {
                display: none;
            }

            .dropdown-menu {
                box-shadow: none;
                border: none;
                margin: 0 !important;
                padding: 0 0 0 1.5rem;
                background: rgba(10, 38, 71, 0.03);
            }

            .dropdown-item {
                padding: 0.75rem 1.25rem;
            }

            .btn {
                width: 100%;
                margin: 0.25rem 0;
            }
        }

        .navbar-toggler {
            padding: 0.5rem;
            border: none;
            box-shadow: none !important;
        }

        .navbar-toggler:focus {
            box-shadow: none !important;
        }

        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%2810, 38, 71, 0.8%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }

        /* Job Page Specific Styles */
        .job-container {
            color: #333;
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
            height: 100vh;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e0e0e0;
        }

        .page-title {
            font-weight: 300;
            color: var(--primary-color);
            margin: 0;
            font-size: 35px;
        }

        .job-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 16px;
            transition: all 0.2s ease;
            background: white;
            box-shadow: var(--card-shadow);
            display: flex;
            flex-direction: column;
            height: 100%;
            position: relative;
            cursor: pointer;
        }

        .job-card:hover {
            box-shadow: var(--card-hover-shadow);
            transform: translateY(-1px);
        }

        .job-card.active {
            border: 1px solid var(--primary-color);
            background-color: #f8f9ff;
        }

        .job-title {
            font-size: 1.2rem;
            font-weight: 500;
            color: #2d3748;
        }

        .company-name {
            color: #4a5568;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }

        .job-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin: 10px 0;
            align-items: center;
        }

        .job-meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #4a5568;
            font-size: 0.9rem;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 400;
            background: #e2e8f0;
            color: #2d3748;
        }

        .job-meta-item i {
            margin-right: 5px;
            color: var(--primary-color);
        }

        .job-type-badge {
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 400;
            background: #e2e8f0;
            color: #2d3748;
        }

        .expires-soon {
            color: #ef233c;
            font-weight: 400;
        }

        .job-details-container {
            background: white;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            height: 90%;
            padding: 25px;
            position: relative;
        }

        .detail-header {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e0e0e0;
        }

        .detail-title {
            font-weight: 600;
            color: #222;
            font-size: 1.5rem;
            margin-bottom: 5px;
        }

        .detail-company {
            font-size: 1.1rem;
            color: var(--primary-color);
            font-weight: 500;
            margin-bottom: 15px;
        }

        .detail-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
        }

        .detail-meta-item {
            display: flex;
            align-items: center;
            font-size: 0.9rem;
        }

        .detail-meta-item i {
            margin-right: 7px;
            color: var(--primary-color);
        }

        .detail-section {
            margin-bottom: 25px;
        }

        .section-title {
            font-weight: 500;
            color: #333;
            margin-bottom: 12px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
        }

        .section-title i {
            margin-right: 8px;
            color: var(--primary-color);
        }

        .job-description {
            line-height: 1.7;
            color: #444;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .btn-apply {
            background: var(--gradient-primary);
            background-size: 200% auto;
            color: white;
            border: none;
            padding: 10px 25px;
            font-weight: 400;
            flex-grow: 1;
            box-shadow: 0 4px 15px rgba(10, 38, 71, 0.2);
        }

        .btn-apply:hover {
            background-position: right center;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(10, 38, 71, 0.3);
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .empty-state i {
            font-size: 3rem;
            color: #ccc;
            margin-bottom: 15px;
        }

        .scrollable-container {
            max-height: calc(100vh - 250px);
            overflow-y: auto;
            padding-right: 10px;
        }

        .scrollable-container::-webkit-scrollbar {
            width: 8px;
        }

        .scrollable-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .scrollable-container::-webkit-scrollbar-thumb {
            background: #ccc;
            border-radius: 10px;
        }

        .scrollable-container::-webkit-scrollbar-thumb:hover {
            background: #aaa;
        }

        .filter-container {
            overflow: hidden;
            transition: height 0.3s ease-out;
            height: 0;
        }

        .filter-section {
            padding: 1.5rem;
            background: white;
            border-radius: 0.375rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
            margin-bottom: 1rem;
        }

        .filter-toggle-btn .bi {
            transition: transform 0.3s ease;
        }

        .filter-toggle-btn.collapsed .bi {
            transform: rotate(-90deg);
        }

        .filter-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 15px;
        }

        .filter-chip {
            display: inline-flex;
            align-items: center;
            background-color: #e2e8f0;
            color: var(--primary-color);
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .filter-chip:hover {
            background-color: #c7d2fe;
        }

        .filter-chip.active {
            background-color: var(--primary-color);
            color: white;
        }

        .filter-label {
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: #495057;
        }

        .jobsearch-container {
            max-width: 75%;
            margin: 0 auto;
            padding: 16px;
            margin-bottom: 5px;
        }

        .jobsearch-form {
            display: flex;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            height: 56px;
            align-items: center;
            padding: 4px;
        }

        .jobsearch-field {
            flex: 1;
            position: relative;
            padding: 0 8px;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .jobsearch-what-field::after {
            content: "";
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
            height: 60%;
            width: 1px;
            background-color: #e0e0e0;
        }

        .jobsearch-input-wrapper {
            display: flex;
            align-items: center;
            height: 100%;
        }

        .jobsearch-input {
            flex: 1;
            border: none;
            outline: none;
            padding: 0 8px;
            font-size: 14px;
            height: 100%;
            background: transparent;
        }

        .jobsearch-input::placeholder {
            font-size: 16px;
            color: #999;
        }

        .jobsearch-location-btn {
            background: none;
            border: none;
            padding: 0 8px;
            cursor: pointer;
            color: #666;
            display: flex;
            align-items: center;
        }

        .jobsearch-location-btn:hover {
            color: #333;
        }

        .jobsearch-submit-container {
            padding-left: 8px;
            padding-right: 10px;
        }

        .jobsearch-submit-btn {
            background: var(--gradient-primary);
            background-size: 200% auto;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 0 24px;
            height: 48px;
            font-size: 16px;
            cursor: pointer;
            white-space: nowrap;
            font-weight: 400;
            border-radius: 6px;
            box-shadow: 0 4px 15px rgba(10, 38, 71, 0.2);
        }

        .jobsearch-submit-btn:hover {
            background-position: right center;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(10, 38, 71, 0.3);
        }

        .text-truncate {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        @media (max-width: 992px) {
            .job-container {
                padding: 15px;
            }

            .job-card {
                padding: 15px;
            }

            .job-details-container {
                padding: 20px;
            }
        }

        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .filter-row > div {
                margin-bottom: 15px;
            }

            .scrollable-container {
                max-height: none;
                overflow-y: visible;
            }

            .action-buttons {
                flex-direction: column;
                gap: 10px;
            }
            .btn-apply {
                background: var(--gradient-primary);
                background-size: 200% auto;
                color: white;
                border: none;
                padding: 5px 8px;
                font-weight: 100;
                flex-grow: 1;
                box-shadow: 0 4px 15px rgba(10, 38, 71, 0.2);
            }

            .jobsearch-container {
                max-width: 100%;
                padding: 10px;
            }

            .jobsearch-form {
                flex-wrap: wrap;
                height: auto;
                padding: 8px;
            }

            .jobsearch-what-field {
                flex: 1;
                min-width: 0;
            }

            .jobsearch-what-field::after {
                display: none; /* Remove divider since location field is hidden */
            }

            .jobsearch-where-field {
                display: none; /* Hide location field on mobile */
            }

            .jobsearch-submit-container {
                flex: 0 0 auto;
                padding: 8px;
            }

            .jobsearch-submit-btn {
                width: 100%;
                padding: 0 16px;
                font-size: 14px;
                height: 44px;
            }

            .jobsearch-input {
                font-size: 12px;
            }

            .jobsearch-input::placeholder {
                font-size: 14px;
            }

            #jobDetails {
                display: none;
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .fade-in {
            animation: fadeIn 0.3s ease-out forwards;
        }

        .spinner-container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 200px;
        }

        .spinner {
            width: 3rem;
            height: 3rem;
            color: var(--primary-color);
        }

        /* Modal-specific styles */
        #jobDetailsModal .modal-content {
            background: white;
            border-radius: 0;
            box-shadow: var(--card-shadow);
        }

        #jobDetailsModal .modal-body {
            padding: 25px;
            max-height: 100vh;
            overflow-y: auto;
        }

        #jobDetailsModal .modal-body::-webkit-scrollbar {
            width: 8px;
        }

        #jobDetailsModal .modal-body::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        #jobDetailsModal .modal-body::-webkit-scrollbar-thumb {
            background: #ccc;
            border-radius: 10px;
        }

        #jobDetailsModal .modal-body::-webkit-scrollbar-thumb:hover {
            background: #aaa;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container job-container">
        <!-- Header Section -->
        <div class="page-header">
            <h1 class="page-title">Job Opportunities</h1>
        </div>

        <!-- Search Bar -->
        <div class="jobsearch-container">
            <form method="GET" action="jobs.php" class="jobsearch-form">
                <!-- What Field -->
                <div class="jobsearch-field jobsearch-what-field">
                    <div class="jobsearch-input-wrapper">
                        <input 
                            type="text" 
                            id="search" 
                            class="jobsearch-input" 
                            name="search" 
                            placeholder="Job title, keywords, or company" 
                            value="<?php echo htmlspecialchars($search); ?>"
                            autocomplete="off"
                        />
                    </div>
                </div>
                
                <!-- Where Field -->
                <div class="jobsearch-field jobsearch-where-field">
                    <div class="jobsearch-input-wrapper">
                        <input 
                            type="text" 
                            id="location" 
                            class="jobsearch-input" 
                            name="location" 
                            placeholder="Any location" 
                            value="<?php echo htmlspecialchars($location); ?>"
                            autocomplete="off"
                        />
                        <button type="button" class="jobsearch-location-btn">
                            <span class="jobsearch-location-icon">
                                <svg viewBox="0 0 16 16" width="16" height="16">
                                    <path d="M8 0C4.3 0 1.3 3 1.3 6.7c0 5.3 6.3 9.2 6.5 9.3.1.1.3.1.4 0 .2-.1 6.5-4 6.5-9.3C14.7 3 11.7 0 8 0zm0 9.5c-1.6 0-2.8-1.3-2.8-2.8S6.4 4 8 4s2.8 1.3 2.8 2.8-1.3 2.7-2.8 2.7z" fill="currentColor"></path>
                                </svg>
                            </span>
                        </button>
                    </div>
                </div>
                
                <!-- Submit Button -->
                <div class="jobsearch-submit-container">
                    <button type="submit" class="jobsearch-submit-btn">
                        Find Jobs
                    </button>
                </div>
            </form>
        </div>

        <!-- Filter Toggle Button -->
        <div class="mb-3">
            <button class="btn btn-outline-primary filter-toggle-btn collapsed" type="button" id="filterToggleBtn">
                <i class="fas fa-funnel-dollar me-2"></i> <span class="btn-text">Show Filters</span>
            </button>
        </div>

        <!-- Filters Section -->
        <div class="filter-container collapsed" id="filterContainer">
            <div class="filter-section">
                <form method="GET" action="jobs.php">
                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                    <input type="hidden" name="location" value="<?php echo htmlspecialchars($location); ?>">
                    <div class="row filter-row g-3">
                        <div class="col-md-5">
                            <div class="filter-label">Category</div>
                            <select id="category" name="category" class="form-select">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat); ?>" 
                                            <?php echo ($category === $cat) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <div class="filter-label">Job Type</div>
                            <select id="jobType" name="job_type" class="form-select">
                                <option value="">All Types</option>
                                <?php foreach ($jobTypes as $type): ?>
                                    <option value="<?php echo htmlspecialchars($type); ?>" 
                                            <?php echo ($category === $type) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($type); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100 d-flex align-items-center justify-content-center">
                                <i class="fas fa-funnel-dollar me-2"></i> Filter
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Main Content -->
        <div class="row">
            <!-- Job Listings Column -->
            <div class="col-lg-5">
                <div id="jobListings" class="scrollable-container pe-3">
                    <?php if (empty($jobs)): ?>
                        <div class="empty-state">
                            <i class="fas fa-briefcase"></i>
                            <h5 class="mt-3">No jobs found</h5>
                            <p class="text-muted">Try adjusting your search criteria or check back later for new opportunities.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($jobs as $index => $job): ?>
                            <div class="job-card fade-in <?php echo $index === 0 ? 'active' : ''; ?>" 
                                 onclick="selectJob('<?php echo sanitize($job['job_id']); ?>')">
                                <div class="job-title"><?php echo sanitize($job['title']); ?></div>
                                <div class="company-name"><?php echo sanitize($job['company_name']); ?></div>
                                <div class="job-meta">
                                    <span class="job-meta-item"><i class="fas fa-map-marker-alt"></i> <?php echo sanitize($job['location']); ?></span>
                                    <span class="job-meta-item"><i class="fas fa-clock"></i> <?php echo getTimeAgo($job['posted_at']); ?></span>
                                    <span class="job-type-badge"><?php echo sanitize($job['job_type_title']); ?></span>
                                </div>
                                <div class="d-flex justify-content-between mt-2">
                                    <?php if (isJobExpiringSoon($job['expires_at'])): ?>
                                        <div class="expires-soon"><i class="fas fa-clock"></i> Expires soon: <?php echo formatDate($job['expires_at']); ?></div>
                                    <?php else: ?>
                                        <div class="text-muted"><i class="fas fa-clock"></i> Expires: <?php echo formatDate($job['expires_at']); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Job Details Column (Desktop) -->
            <div class="col-lg-7">
                <div id="jobDetails" class="job-details-container scrollable-container fade-in">
                    <?php if (empty($jobs)): ?>
                        <div class="empty-state">
                            <i class="fas fa-briefcase"></i>
                            <h5 class="mt-3">No jobs available</h5>
                            <p class="text-muted">Check back later for new opportunities</p>
                        </div>
                    <?php else: ?>
                        <div class="detail-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="job-info flex-grow-1 pe-3" style="max-width: 70%;">
                                    <h2 class="detail-title text-truncate"><?php echo sanitize($jobs[0]['title']); ?></h2>
                                    <h4 class="detail-company text-truncate"><?php echo sanitize($jobs[0]['company_name']); ?></h4>
                                    <div class="detail-meta">
                                        <span class="detail-meta-item"><i class="fas fa-map-marker-alt"></i> <?php echo sanitize($jobs[0]['location']); ?></span>
                                        <span class="detail-meta-item" data-bs-toggle="tooltip" title="Job Type"><i class="fas fa-briefcase"></i> <?php echo sanitize($jobs[0]['job_type_title']); ?></span>
                                    </div>
                                    <div class="d-flex flex-wrap gap-3">
                                        <span class="detail-meta-item"><i class="fas fa-calendar"></i> Posted: <?php echo formatDate($jobs[0]['posted_at']); ?></span>
                                        <?php if (isJobExpiringSoon($jobs[0]['expires_at'])): ?>
                                            <span class="detail-meta-item expires-soon"><i class="fas fa-clock"></i> Expires soon: <?php echo formatDate($jobs[0]['expires_at']); ?></span>
                                        <?php else: ?>
                                            <span class="detail-meta-item"><i class="fas fa-clock"></i> Expires: <?php echo formatDate($jobs[0]['expires_at']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="action-buttons flex-shrink-0">
                                    <button class="btn btn-apply" data-bs-toggle="modal" data-bs-target="#loginModal">
                                        <i class="fas fa-send-check"></i> Login to Apply
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="detail-section">
                            <h5 class="section-title"><i class="fas fa-file-text"></i> Job Description</h5>
                            <div class="job-description">
                                <?php echo nl2br(sanitize($jobs[0]['description'])); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Job Details Modal (Mobile) -->
        <div class="modal fade" id="jobDetailsModal" tabindex="-1" aria-labelledby="jobDetailsModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-fullscreen">
                <div class="modal-content">
                    <div class="modal-header border-0">
                        <h5 class="modal-title" id="jobDetailsModalLabel">Job Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="jobDetailsModalContent">
                        <!-- Content will be populated by JavaScript -->
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
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.11.4/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.11.4/ScrollTrigger.min.js"></script>
    <script>
        // Initialize AOS
        AOS.init({
            duration: 800,
            easing: 'ease-in-out',
            once: true
        });

        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Initialize tooltips
        document.addEventListener('DOMContentLoaded', function() {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Highlight active dropdown parent
            const currentPage = '<?php echo basename($_SERVER['PHP_SELF']); ?>';
            const dropdownItems = document.querySelectorAll('.dropdown-item');
            
            dropdownItems.forEach(item => {
                if (item.getAttribute('href').includes(currentPage)) {
                    item.classList.add('active');
                    const dropdown = item.closest('.dropdown');
                    if (dropdown) {
                        const toggle = dropdown.querySelector('.dropdown-toggle');
                        toggle.classList.add('active');
                    }
                }
            });

            // Toggle Filters Functionality
            const filterToggle = document.getElementById('filterToggleBtn');
            const filterContainer = document.getElementById('filterContainer');
            
            filterToggle.addEventListener('click', function() {
                const content = filterContainer.querySelector('.filter-section');
                
                if (filterContainer.classList.contains('collapsed')) {
                    const contentHeight = content.scrollHeight;
                    filterContainer.style.height = contentHeight + 'px';
                    filterContainer.classList.remove('collapsed');
                    this.querySelector('.btn-text').textContent = 'Hide Filters';
                    this.classList.remove('collapsed');
                    
                    setTimeout(() => {
                        filterContainer.style.height = 'auto';
                    }, 300);
                } else {
                    filterContainer.style.height = filterContainer.scrollHeight + 'px';
                    setTimeout(() => {
                        filterContainer.style.height = '0';
                    }, 10);
                    
                    filterContainer.classList.add('collapsed');
                    this.querySelector('.btn-text').textContent = 'Show Filters';
                    this.classList.add('collapsed');
                }
            });

            // Handle window resize when filters are open
            window.addEventListener('resize', function() {
                if (!filterContainer.classList.contains('collapsed')) {
                    const content = filterContainer.querySelector('.filter-section');
                    filterContainer.style.height = content.scrollHeight + 'px';
                }
            });
        });

        function selectJob(jobId) {
            const jobs = <?php echo json_encode($jobs); ?>;
            const job = jobs.find(j => j.job_id == jobId);
            
            if (job) {
                const jobDetailsHTML = `
                    <div class="detail-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="job-info flex-grow-1 pe-3" style="max-width: 70%;">
                                <h2 class="detail-title text-truncate">${sanitize(job.title)}</h2>
                                <h4 class="detail-company text-truncate">${sanitize(job.company_name)}</h4>
                                <div class="detail-meta">
                                    <span class="detail-meta-item"><i class="fas fa-map-marker-alt"></i> ${sanitize(job.location)}</span>
                                    <span class="detail-meta-item" data-bs-toggle="tooltip" title="Job Type"><i class="fas fa-briefcase"></i> ${sanitize(job.job_type_title)}</span>
                                </div>
                                <div class="d-flex flex-wrap gap-3">
                                    <span class="detail-meta-item"><i class="fas fa-calendar"></i> Posted: ${formatDate(job.posted_at)}</span>
                                    ${isJobExpiringSoon(job.expires_at) ? 
                                        `<span class="detail-meta-item expires-soon"><i class="fas fa-clock"></i> Expires soon: ${formatDate(job.expires_at)}</span>` : 
                                        `<span class="detail-meta-item"><i class="fas fa-clock"></i> Expires: ${formatDate(job.expires_at)}</span>`}
                                </div>
                            </div>
                            <div class="action-buttons flex-shrink-0">
                                <button class="btn btn-apply" data-bs-toggle="modal" data-bs-target="#loginModal">
                                    <i class="fas fa-send-check"></i> Login to Apply
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="detail-section">
                        <h5 class="section-title"><i class="fas fa-file-text"></i> Job Description</h5>
                        <div class="job-description">
                            ${sanitize(job.description).replace(/\n/g, '<br>')}
                        </div>
                    </div>
                `;

                // Update job details based on screen size
                if (window.innerWidth <= 768) {
                    // Mobile: Populate and show full-screen modal
                    const modalContent = document.getElementById('jobDetailsModalContent');
                    modalContent.innerHTML = jobDetailsHTML;
                    const modal = new bootstrap.Modal(document.getElementById('jobDetailsModal'));
                    modal.show();
                } else {
                    // Desktop: Update inline job details
                    const detailsContainer = document.getElementById('jobDetails');
                    detailsContainer.innerHTML = jobDetailsHTML;
                }

                // Update active state in job list
                document.querySelectorAll('.job-card').forEach(card => {
                    card.classList.remove('active');
                    if (card.getAttribute('onclick').includes(jobId)) {
                        card.classList.add('active');
                        if (!isElementInViewport(card)) {
                            card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                        }
                    }
                });

                // Re-initialize tooltips
                const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
                tooltipTriggerList.forEach(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
            }
        }

        function isElementInViewport(el) {
            const rect = el.getBoundingClientRect();
            return (
                rect.top >= 0 &&
                rect.left >= 0 &&
                rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
                rect.right <= (window.innerWidth || document.documentElement.clientWidth)
            );
        }

        function formatDate(dateString) {
            if (!dateString) return "N/A";
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric' 
            });
        }

        function isJobExpiringSoon(expiryDate) {
            if (!expiryDate) return false;
            const now = new Date();
            const soonDate = new Date();
            soonDate.setDate(now.getDate() + 7);
            return new Date(expiryDate) <= soonDate;
        }

        function sanitize(text) {
            if (!text) return '';
            const temp = document.createElement("div");
            temp.textContent = text;
            return temp.innerHTML;
        }
    </script>
</body>
</html>