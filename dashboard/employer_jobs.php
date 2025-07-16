<?php
require '../controllers/employer_jobs.php';
require '../auth/employer_auth.php';
include '../includes/employer_navbar.php';
// Example: require database or session start here
require_once '../config/dbcon.php';

$successMessage = isset($_GET['success']) ? urldecode($_GET['success']) : null;
$errorMessage = isset($_GET['error']) ? urldecode($_GET['error']) : null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Job Postings - Employer Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1A4D8F;
            --primary-light: #e8f0fe;
            --primary-lighter: #f5f8ff;
            --secondary-color: #3A7BD5;
            --accent-color: #4cc9f0;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --light-bg: #f8f9fa;
            --dark-text: #2b2d42;
            --medium-text: #495057;
            --light-text: #6c757d;
            --border-color: rgba(0, 0, 0, 0.08);
            --card-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            --card-hover-shadow: 0 6px 12px rgba(26, 77, 143, 0.1);
            --transition-fast: 0.15s ease;
            --transition-medium: 0.3s ease;
            --dark-blue: #1A4D8F;
            --medium-blue: #4B7DA3;
            --light-blue: #5C84A1;
            --lighter-blue: #82AAC7;
        }
        
        body {
            background-color: #f8fafc;
            color: var(--dark-text);
            line-height: 1.6;
        }
        
        .dashboard-container {
            font-family: 'Poppins', sans-serif;
            padding: 2rem;
            max-width: 1450px;
            margin: 0 auto;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .page-title {
            font-weight: 700;
            color: var(--dark-text);
            margin-bottom: 0;
            font-size: 1.75rem;
        }
        
        /* Improved Card Design */
        .job-card {
            background: white;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            transition: all var(--transition-medium);
            margin-bottom: 1.5rem;
            border: none;
            overflow: hidden;
            position: relative;
        }
        
        .job-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--card-hover-shadow);
        }
        
        .job-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 1.5rem;
            background-color: white;
            border-bottom: 1px solid var(--border-color);
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .job-title {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
            font-size: 1.2rem;
            transition: color var(--transition-fast);
        }
        
        .job-card:hover .job-title {
            color: var(--secondary-color);
        }
        
        .job-meta {
            display: flex;
            gap: 1rem;
            color: var(--light-text);
            font-size: 0.9rem;
            flex-wrap: wrap;
        }
        
        .job-meta-item {
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }
        
        .job-meta-item i {
            font-size: 0.9em;
        }
        
        /* Enhanced Status Badges */
        .job-status {
            font-size: 0.75rem;
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            font-weight: 600;
            min-width: 80px;
            text-align: center;
            text-transform: capitalize;
            letter-spacing: 0.5px;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-paused {
            background-color: #ffe8a1;
            color: #6c5300;
        }
        
        .status-approved {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .status-expired {
            background-color: #e2e3e5;
            color: #383d41;
        }
        
        .job-card-body {
            padding: 1.5rem;
        }
        
        .job-description {
            color: var(--medium-text);
            margin-bottom: 1.5rem;
            line-height: 1.7;
        }
        
        .job-skills {
            margin-bottom: 1.5rem;
        }
        
        .skill-tag {
            display: inline-flex;
            align-items: center;
            background-color: var(--primary-light);
            color: var(--primary-color);
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            margin: 0 0.5rem 0.5rem 0;
            font-size: 0.8rem;
            font-weight: 500;
            transition: all var(--transition-fast);
        }
        
        .skill-tag:hover {
            background-color: var(--primary-color);
            color: white;
            transform: translateY(-1px);
        }
        
        /* Improved Stats Cards */
        .job-stats {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .job-stat {
            text-align: center;
            padding: 0.75rem;
            background-color: var(--light-bg);
            border-radius: 8px;
            flex: 1;
            transition: all var(--transition-fast);
        }
        
        .job-stat:hover {
            background-color: var(--primary-lighter);
            transform: translateY(-2px);
        }
        
        .job-stat-value {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.25rem;
        }
        
        .job-stat-label {
            font-size: 0.75rem;
            color: var(--light-text);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Enhanced Footer Actions */
        .job-card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            background-color: var(--light-bg);
            border-top: 1px solid var(--border-color);
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .action-btn {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-weight: 500;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            transition: all var(--transition-fast);
            gap: 0.4rem;
            border: 1px solid transparent;
        }
        
        .btn-edit {
            background-color: var(--primary-light);
            color: var(--primary-color);
            border-color: var(--primary-light);
        }
        
        .btn-edit:hover {
            background-color: var(--primary-color);
            color: white;
            transform: translateY(-1px);
        }
        
        .btn-delete {
            background-color: #f8d7da;
            color: var(--danger-color);
            border-color: #f8d7da;
        }
        
        .btn-delete:hover {
            background-color: var(--danger-color);
            color: white;
            transform: translateY(-1px);
        }
        
        .btn-view-applicants {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .btn-view-applicants:hover {
            background-color: #0d3b7a;
            border-color: #0d3b7a;
            transform: translateY(-1px);
        }
        
        .btn-duplicate {
            background-color: #e2e3e5;
            color: #383d41;
            border-color: #e2e3e5;
        }
        
        .btn-duplicate:hover {
            background-color: #383d41;
            color: white;
            transform: translateY(-1px);
        }
        
        /* Improved Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--light-text);
            background-color: white;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            max-width: 500px;
            margin: 2rem auto;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1.5rem;
            color: #e9ecef;
        }
        
        .empty-state h4 {
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--dark-text);
        }
        
        .empty-state p {
            margin-bottom: 1.5rem;
            font-weight: 400;
        }
        
        /* Enhanced Stats Overview Cards */
        .stats-card {
            border: none;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            transition: all var(--transition-medium);
            height: 100%;
            background-color: white;
        }
        
        .stats-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--card-hover-shadow);
        }
        
        .stats-card .card-body {
            padding: 1.5rem;
            text-align: center;
        }
        
        .stats-card h3 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--primary-color);
        }
        
        .stats-card p {
            color: var(--light-text);
            margin-bottom: 0;
            font-size: 0.9rem;
        }
        
        /* Improved Table Styling */
        .table-responsive {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
            background-color: white;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table thead {
            background-color: var(--primary-color);
            color: white;
        }
        
        .table th {
            font-weight: 500;
            padding: 1rem;
            vertical-align: middle;
        }
        
        .table td {
            vertical-align: middle;
            padding: 1rem;
            border-top: 1px solid var(--border-color);
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(26, 77, 143, 0.03);
        }
        
        /* Improved Dropdown */
        .action-dropdown .dropdown-menu {
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border: none;
            padding: 0.5rem;
            min-width: 200px;
        }
        
        .action-dropdown .dropdown-item {
            border-radius: 6px;
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all var(--transition-fast);
        }
        
        .action-dropdown .dropdown-item:hover {
            background-color: var(--primary-lighter);
            color: var(--primary-color);
        }
        
        .action-dropdown .dropdown-item i {
            width: 18px;
            text-align: center;
        }
        
        /* Improved Pagination */
        .pagination .page-item .page-link {
            border-radius: 6px;
            margin: 0 0.2rem;
            border: none;
            color: var(--primary-color);
            font-weight: 500;
            min-width: 36px;
            text-align: center;
        }
        
        .pagination .page-item.active .page-link {
            background-color: var(--primary-color);
            color: white;
        }
        
        .pagination .page-item:not(.active) .page-link:hover {
            background-color: var(--primary-light);
        }
        
        /* Improved Filter Section */
        #filterSection {
            background-color: white;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            margin-bottom: 1.5rem;
            overflow: hidden;
        }
        
        #filterToggleBtn {
            border-radius: 8px;
            padding: 0.5rem 1rem;
            font-weight: 500;
            transition: all var(--transition-fast);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border: 1px solid var(--border-color);
        }
        
        #filterToggleBtn:hover {
            background-color: var(--primary-light);
            border-color: var(--primary-color);
        }
        
        #filterToggleBtn i {
            transition: transform var(--transition-fast);
        }
        
        #filterToggleBtn[aria-expanded="true"] .bi-chevron-down {
            transform: rotate(180deg);
        }
        
        .filter-actions {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }
        
        /* Responsive Improvements */
        @media (max-width: 992px) {
            .dashboard-container {
                padding: 1.5rem;
            }
            
            .job-stats {
                flex-wrap: wrap;
            }
            
            .job-stat {
                min-width: calc(50% - 0.5rem);
            }
        }
        
        @media (max-width: 768px) {
            .dashboard-container {
                padding: 1rem;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .job-card-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .job-status {
                align-self: flex-start;
            }
            
            .job-card-footer {
                flex-direction: column;
                align-items: stretch;
                gap: 1rem;
            }
            
            .job-card-footer > div:first-child {
                order: 2;
                text-align: center;
            }
            
            .job-card-footer .d-flex {
                order: 1;
                justify-content: space-between;
                width: 100%;
            }
        }
        
        @media (max-width: 576px) {
            .job-meta {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .action-btn {
                width: 100%;
                justify-content: center;
            }
            
            .dropdown-menu {
                position: absolute !important;
            }
        }
        
        /* Animation Enhancements */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .job-card {
            animation: fadeIn 0.3s ease forwards;
        }
        
        /* Delay animations for each card */
        .job-card:nth-child(1) { animation-delay: 0.1s; }
        .job-card:nth-child(2) { animation-delay: 0.2s; }
        .job-card:nth-child(3) { animation-delay: 0.3s; }
        .job-card:nth-child(4) { animation-delay: 0.4s; }
        .job-card:nth-child(5) { animation-delay: 0.5s; }
        
        /* Loading Skeleton */
        .skeleton {
            background-color: #e9ecef;
            border-radius: 4px;
            animation: pulse 1.5s infinite ease-in-out;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        /* Tooltip Enhancements */
        .tooltip-inner {
            border-radius: 6px;
            padding: 0.5rem 0.75rem;
            font-size: 0.8rem;
        }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
        
        /* Focus States for Accessibility */
        a:focus, button:focus, input:focus, select:focus, textarea:focus {
            outline: 2px solid var(--primary-color);
            outline-offset: 2px;
        }
        
        /* Badge Improvements */
        .badge-custom {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            background-color: var(--success-color);
            color: white;
        }
        
        /* Progress Bar Styling */
        .progress {
            height: 8px;
            border-radius: 4px;
        }
        
        .progress-bar {
            background-color: var(--primary-color);
        }
        
        /* Custom Toggle Switch */
        .form-switch .form-check-input {
            width: 2.5em;
            height: 1.5em;
            cursor: pointer;
        }
        
        .form-switch .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
    </style>
    <style>
    /* Stats Cards Styling */
    .stat-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        overflow: hidden;
        position: relative;
        color: white;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 16px rgba(26, 77, 143, 0.15);
    }
    
    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 4px;
    }
    
    .stat-card-total {
        background: linear-gradient(135deg, var(--dark-blue) 0%, var(--medium-blue) 100%);
    }
    
    .stat-card-active {
        background: linear-gradient(135deg, var(--medium-blue) 0%, var(--light-blue) 100%);
    }
    
    .stat-card-pending {
        background: linear-gradient(135deg, var(--light-blue) 0%, var(--lighter-blue) 100%);
    }
    
    .stat-card-expired {
        background: linear-gradient(135deg, var(--lighter-blue) 0%, #a8c6df 100%);
    }
    
    .stat-icon {
        font-size: 2rem;
        margin-bottom: 1rem;
        opacity: 0.8;
    }
    
    .stat-value {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }
    
    .stat-label {
        font-size: 1rem;
        font-weight: 500;
        opacity: 0.9;
        margin-bottom: 0;
    }
    
    /* Responsive adjustments */
    @media (max-width: 992px) {
        .stats-row > div {
            margin-bottom: 1rem;
        }
        
        .stat-value {
            font-size: 2rem;
        }
    }
    
    @media (max-width: 768px) {
        .stat-card {
            text-align: center;
        }
        
        .stat-icon {
            font-size: 1.75rem;
        }
        
        .stat-value {
            font-size: 1.75rem;
        }
    }
</style>
    
</head>
<body>

<div class="container dashboard-container">
    <div class="page-header">
        <h1 class="page-title">Manage Job Postings</h1>
        <a href="employer_post_job.php" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Post New Job
        </a>
    </div>
    
    <div class="mb-3">
        <button id="filterToggleBtn" class="btn btn-outline-primary mb-2">
            <i class="bi bi-funnel-fill"></i> 
            <span>Show Filters</span>
            <i class="bi bi-chevron-down ms-1"></i>
        </button>
        
        <div id="filterSection" class="card collapse">
            <div class="card-body">
            <form id="jobFilterForm">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="statusFilter" class="form-label">Status</label>
                        <select class="form-select" id="statusFilter" name="status">
                            <option value="">All Statuses</option>
                            <option value="Pending">Pending Approval</option>
                            <option value="Approved">Approved</option>
                            <option value="Paused">Paused</option>
                            <option value="Rejected">Rejected</option>
                            <option value="Expired">Expired</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="dateFilter" class="form-label">Date Posted</label>
                        <select class="form-select" id="dateFilter" name="date">
                            <option value="">All Time</option>
                            <option value="today">Today</option>
                            <option value="week">This Week</option>
                            <option value="month">This Month</option>
                            <option value="year">This Year</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="searchFilter" class="form-label">Search</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="searchFilter" placeholder="Search jobs...">
                            <button class="btn btn-outline-secondary" type="button">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary me-2">Apply Filters</button>
                        <button type="reset" class="btn btn-outline-secondary">Reset</button>
                    </div>
                </div>
            </form>
            </div>
        </div>
    </div>

    
    <!-- Stats Overview -->
    <div class="row mb-4 stats-row">
        <!-- Total Jobs Card -->
        <div class="col-md-3">
            <div class="card stat-card stat-card-total h-100">
                <div class="card-body text-center">
                    <h3 class="stat-value"><?= $total_jobs ?></h3>
                    <p class="stat-label">Total Jobs</p>
                </div>
            </div>
        </div>
        
        <!-- Active Jobs Card -->
        <div class="col-md-3">
            <div class="card stat-card stat-card-active h-100">
                <div class="card-body text-center">
                    <h3 class="stat-value"><?= $active_jobs ?></h3>
                    <p class="stat-label">Active Jobs</p>
                </div>
            </div>
        </div>
        
        <!-- Pending Approval Card -->
        <div class="col-md-3">
            <div class="card stat-card stat-card-pending h-100">
                <div class="card-body text-center">
                    <h3 class="stat-value"><?= $pending_jobs ?></h3>
                    <p class="stat-label">Pending Approval</p>
                </div>
            </div>
        </div>
        
        <!-- Expired Jobs Card -->
        <div class="col-md-3">
            <div class="card stat-card stat-card-expired h-100">
                <div class="card-body text-center">
                    <h3 class="stat-value"><?= $expired_jobs ?></h3>
                    <p class="stat-label">Expired Jobs</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Jobs Table View -->
    <div class="card mb-4">
    
    
        <?php if ($successMessage): ?>
            <div class="alert alert-success" role="alert">
                <?= htmlspecialchars($successMessage) ?>
            </div>
        <?php endif; ?>

        <?php if ($errorMessage): ?>
            <div class="alert alert-danger" role="alert">
                <?= htmlspecialchars($errorMessage) ?>
            </div>
        <?php endif; ?>

        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">All Job Postings</h5>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="toggleTableView">
                <label class="form-check-label" for="toggleTableView">Table View</label>
            </div>
        </div>
        <div class="card-body p-0">
            <div id="cardView">
                <?php if (!empty($jobs)): ?>
                    <?php foreach ($jobs as $job): ?>
                        <div class="job-card">
                            <div class="job-card-header">
                                <div>
                                    <h4 class="job-title"><?= htmlspecialchars($job['title']) ?></h4>
                                    <div class="job-meta">
                                        <span class="job-meta-item">
                                            <i class="bi bi-briefcase"></i> <?= htmlspecialchars($job['job_type_title']) ?>
                                        </span>
                                        <span class="job-meta-item">
                                            <i class="bi bi-geo-alt"></i> <?= htmlspecialchars($job['location']) ?>
                                        </span>
                                        <span class="job-meta-item">
                                            <i class="bi bi-cash"></i> 
                                            <?= $job['salary'] ? '₱' . number_format($job['salary'], 2) : 'Not Specified' ?>
                                        </span>
                                        <span class="job-meta-item">
                                            <i class="bi bi-clock"></i> Posted <?= getTimeAgo($job['posted_at']) ?>
                                        </span>
                                    </div>
                                </div>
                                <span class="job-status <?= getStatusClass($job['moderation_status'], $job['expires_at']) ?>">
                                    <?= getStatusText($job['moderation_status'], $job['expires_at']) ?>
                                </span>
                            </div>
                            <div class="job-card-body">
                                <div class="job-description">
                                    <?= nl2br(htmlspecialchars(truncateDescription($job['description'], 250))) ?>
                                </div>
                                
                                <?php if (!empty($job['skills'])): ?>
                                    <div class="job-skills">
                                        <h6>Required Skills:</h6>
                                        <?php foreach ($job['skills'] as $skill): ?>
                                            <span class="skill-tag"><?= htmlspecialchars($skill['skill_name']) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="job-stats">
                                    <div class="job-stat">
                                        <div class="job-stat-value"><?= $job['applicant_count'] ?></div>
                                        <div class="job-stat-label">Applicants</div>
                                    </div>
                                    <div class="job-stat">
                                        <div class="job-stat-value"><?= $job['withdrawal_count'] ?? 0 ?></div>
                                        <div class="job-stat-label">Withdrawals</div>
                                    </div>
                                    <div class="job-stat">
                                        <div class="job-stat-value"><?= $job['saved_count'] ?></div>
                                        <div class="job-stat-label">Saved</div>
                                    </div>
                                </div>
                            </div>
                            <div class="job-card-footer">
                                <div>
                                    <?php if ($job['expires_at'] && strtotime($job['expires_at']) > time()): ?>
                                        <span class="text-muted small">
                                            Expires in <?= date_diff(new DateTime(), new DateTime($job['expires_at']))->format('%a days') ?>
                                        </span>
                                    <?php elseif ($job['expires_at']): ?>
                                        <span class="text-danger small">
                                            Expired <?= getTimeAgo($job['expires_at']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="employer_applications.php?job_id=<?= $job['job_id'] ?>" 
                                       class="action-btn btn-view-applicants">
                                        <i class="bi bi-people-fill"></i> View Applicants
                                    </a>
                                    <div class="dropdown action-dropdown">
                                        <button class="action-btn btn-edit dropdown-toggle" type="button" 
                                                id="jobActionsDropdown<?= $job['job_id'] ?>" 
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="bi bi-gear"></i> Actions
                                        </button>
                                        <ul class="dropdown-menu" aria-labelledby="jobActionsDropdown<?= $job['job_id'] ?>">
                                            <li>
                                                <a class="dropdown-item" href="employer_edit_job.php?id=<?= $job['job_id'] ?>">
                                                    <i class="bi bi-pencil"></i> Edit Job
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="#" onclick="duplicateJob(<?= $job['job_id'] ?>)">
                                                    <i class="bi bi-files"></i> Duplicate Job
                                                </a>
                                            </li>
                                            <?php if ($job['moderation_status'] == 'Approved' && (!$job['expires_at'] || strtotime($job['expires_at']) > time())): ?>
                                                <li>
                                                    <a class="dropdown-item" href="#" onclick="pauseJob(<?= $job['job_id'] ?>)">
                                                        <i class="bi bi-pause"></i> Pause Job
                                                    </a>
                                                </li>
                                            <?php elseif ($job['moderation_status'] == 'Paused'): ?>
                                                <li>
                                                    <a class="dropdown-item" href="#" onclick="activateJob(<?= $job['job_id'] ?>)">
                                                        <i class="bi bi-play"></i> Activate Job
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                            <li>
                                                <a class="dropdown-item text-danger" href="#" 
                                                   onclick="confirmDelete(<?= $job['job_id'] ?>)">
                                                    <i class="bi bi-trash"></i> Delete Job
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="bi bi-briefcase"></i>
                        <p>You haven't posted any jobs yet</p>
                        <a href="employer_post_job.php" class="btn btn-primary">
                            <i class="bi bi-plus-lg"></i> Post Your First Job
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            
            <div id="tableView" style="display: none;">
                <div class="table-responsive">
                    <table class="table table-hover" id="jobsTable">
                        <thead>
                            <tr>
                                <th>Job Title</th>
                                <th>Type</th>
                                <th>Location</th>
                                <th>Salary</th>
                                <th>Applicants</th>
                                <th>Status</th>
                                <th>Posted</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($jobs)): ?>
                                <?php foreach ($jobs as $job): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($job['title']) ?></strong>
                                            <div class="small text-muted">
                                                <?php foreach ($job['skills'] as $index => $skill): ?>
                                                    <?php if ($index < 3): ?>
                                                        <span class="badge bg-light text-dark"><?= htmlspecialchars($skill['skill_name']) ?></span>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                                <?php if (count($job['skills']) > 3): ?>
                                                    <span class="badge bg-light text-dark">+<?= count($job['skills']) - 3 ?> more</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($job['job_type_title']) ?></td>
                                        <td><?= htmlspecialchars($job['location']) ?></td>
                                        <td><?= $job['salary'] ? '₱' . number_format($job['salary'], 2) : 'N/A' ?></td>
                                        <td>
                                            <span class="fw-bold"><?= $job['applicant_count'] ?></span>
                                            <?php if ($job['new_applicants'] > 0): ?>
                                                <span class="badge-custom">+<?= $job['new_applicants'] ?> new</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge-custom <?= getStatusClass($job['moderation_status'], $job['expires_at']) ?>">
                                                <?= getStatusText($job['moderation_status'], $job['expires_at']) ?>
                                            </span>
                                        </td>
                                        <td><?= date('M d, Y', strtotime($job['posted_at'])) ?></td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" 
                                                        id="tableActionsDropdown<?= $job['job_id'] ?>" 
                                                        data-bs-toggle="dropdown" aria-expanded="false">
                                                    Actions
                                                </button>
                                                <ul class="dropdown-menu" aria-labelledby="tableActionsDropdown<?= $job['job_id'] ?>">
                                                    <!-- <li>
                                                        <a class="dropdown-item" href="employer_view_job.php?id=<?= $job['job_id'] ?>">
                                                            <i class="bi bi-eye"></i> View
                                                        </a>
                                                    </li> -->
                                                    <li>
                                                        <a class="dropdown-item" href="employer_edit_job.php?id=<?= $job['job_id'] ?>">
                                                            <i class="bi bi-pencil"></i> Edit
                                                        </a>
                                                    </li>
                                                    <!-- 
                                                    <li>
                                                        <a class="dropdown-item" href="employer_view_applicants.php?job_id=<?= $job['job_id'] ?>">
                                                            <i class="bi bi-people"></i> Applicants
                                                        </a>
                                                    </li>
                                                    -->
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item text-danger" href="#" 
                                                           onclick="confirmDelete(<?= $job['job_id'] ?>)">
                                                            <i class="bi bi-trash"></i> Delete
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <div class="empty-state">
                                            <i class="bi bi-briefcase"></i>
                                            <p>You haven't posted any jobs yet</p>
                                            <a href="employer_post_job.php" class="btn btn-primary btn-sm">
                                                <i class="bi bi-plus-lg"></i> Post Your First Job
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php if (!empty($jobs) && $total_pages > 1): ?>
            <div class="card-footer">
                <nav aria-label="Job pagination">
                    <ul class="pagination justify-content-center mb-0">
                        <li class="page-item <?= $current_page == 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $current_page - 1 ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= $i == $current_page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= $current_page == $total_pages ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $current_page + 1 ?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteConfirmationModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this job posting? This action cannot be undone.</p>
                <p class="fw-bold">All applications and data associated with this job will be permanently removed.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete Job</button>
            </div>
        </div>
    </div>
</div>

<!-- Pause Job Modal -->
<div class="modal fade" id="pauseJobModal" tabindex="-1" aria-labelledby="pauseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="pauseModalLabel">Pause Job Posting</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Pausing this job will:</p>
                <ul>
                    <li>Hide the job from search results</li>
                    <li>Prevent new applications</li>
                    <li>Keep all existing applications</li>
                </ul>
                <p>You can reactivate the job at any time.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" id="confirmPauseBtn">Pause Job</button>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/stud_footer.php'; ?>

<!-- ✅ jQuery first -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- ✅ Then DataTables core -->
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>

<!-- ✅ Then DataTables Bootstrap 5 styling -->
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

<!-- ✅ Then your custom script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const table = $('#jobsTable');
    const hasDataRows = table.find('tbody tr').length > 0 && table.find('tbody tr td:not([colspan])').length > 0;

    if (hasDataRows && !$.fn.DataTable.isDataTable('#jobsTable')) {
        table.DataTable({
            responsive: true,
            order: [[6, 'desc']],
            pageLength: 10
        });
    }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>


<script>
    // Toggle between card and table view
    document.getElementById('toggleTableView').addEventListener('change', function() {
        const cardView = document.getElementById('cardView');
        const tableView = document.getElementById('tableView');
        
        if (this.checked) {
            cardView.style.display = 'none';
            tableView.style.display = 'block';
            
            // Initialize DataTable only if not already initialized and table has data
            const table = $('#jobsTable');
            const hasDataRows = table.find('tbody tr').length > 0 && table.find('tbody tr td:not([colspan])').length > 0;

            if (hasDataRows && !$.fn.DataTable.isDataTable('#jobsTable')) {
                table.DataTable({
                    responsive: true,
                    order: [[6, 'desc']],
                    pageLength: 10
                });
            }
        } else {
            cardView.style.display = 'block';
            tableView.style.display = 'none';
            
            // Destroy DataTable if initialized to avoid memory leaks
            if ($.fn.DataTable.isDataTable('#jobsTable')) {
                table.DataTable().destroy();
            }
        }
    });
    
    // Delete job confirmation
    let jobIdToDelete = null;
    
    function confirmDelete(jobId) {
        jobIdToDelete = jobId;
        const deleteModal = new bootstrap.Modal(document.getElementById('deleteConfirmationModal'));
        deleteModal.show();
    }
    
    document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
        if (jobIdToDelete) {
            window.location.href = '../controllers/employer_delete_job.php?id=' + jobIdToDelete;
        }
    });
    
    // Pause job confirmation
    let jobIdToPause = null;
    
    function pauseJob(jobId) {
        jobIdToPause = jobId;
        const pauseModal = new bootstrap.Modal(document.getElementById('pauseJobModal'));
        pauseModal.show();
    }
    
    document.getElementById('confirmPauseBtn').addEventListener('click', function() {
        if (jobIdToPause) {
            window.location.href = '../controllers/employer_pause_job.php?id=' + jobIdToPause;
        }
    });
    
    // Activate job
    function activateJob(jobId) {
        if (confirm('Are you sure you want to activate this job posting?')) {
            window.location.href = '../controllers/employer_activate_job.php?id=' + jobId;
        }
    }
    
    // Duplicate job
    function duplicateJob(jobId) {
        if (confirm('Create a copy of this job posting?')) {
            window.location.href = '../controllers/employer_duplicate_job.php?id=' + jobId;
        }
    }
    
    // Filter form submission
    document.getElementById('jobFilterForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const status = document.getElementById('statusFilter').value;
        const date = document.getElementById('dateFilter').value;
        const search = document.getElementById('searchFilter').value;
        
        // Build query string
        let queryParams = [];
        if (status) queryParams.push(`status=${encodeURIComponent(status)}`);
        if (date) queryParams.push(`date=${encodeURIComponent(date)}`);
        if (search) queryParams.push(`search=${encodeURIComponent(search)}`);
        
        window.location.href = '../dashboard/employer_jobs.php?' + queryParams.join('&');
    });
</script>



<script>
document.addEventListener('DOMContentLoaded', function() {
    const filterToggleBtn = document.getElementById('filterToggleBtn');
    const filterSection = document.getElementById('filterSection');
    const toggleIcon = filterToggleBtn.querySelector('.bi-chevron-down');
    const toggleText = filterToggleBtn.querySelector('span');
    
    // Initialize collapse plugin
    const filterCollapse = new bootstrap.Collapse(filterSection, {
        toggle: false
    });
    
    // Toggle visibility
    filterToggleBtn.addEventListener('click', function() {
        filterCollapse.toggle();
        
        // Update button text and icon
        if (filterSection.classList.contains('show')) {
            toggleText.textContent = 'Hide Filters';
            toggleIcon.classList.replace('bi-chevron-down', 'bi-chevron-up');
        } else {
            toggleText.textContent = 'Show Filters';
            toggleIcon.classList.replace('bi-chevron-up', 'bi-chevron-down');
        }
    });
    
    // Optional: Remember state
    if (localStorage.getItem('filtersHidden') === 'true') {
        filterCollapse.hide();
        toggleText.textContent = 'Show Filters';
        toggleIcon.classList.replace('bi-chevron-down', 'bi-chevron-up');
    }
    
    filterSection.addEventListener('hidden.bs.collapse', function() {
        localStorage.setItem('filtersHidden', 'true');
    });
    
    filterSection.addEventListener('shown.bs.collapse', function() {
        localStorage.setItem('filtersHidden', 'false');
    });
});
</script>
<script>
    setTimeout(() => {
        const alert = document.querySelector('.alert');
        if (alert) {
            alert.style.display = 'none';
        }
    }, 3000);
</script>

</body>
</html>