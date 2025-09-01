<?php
require '../auth/employer_auth.php';
include '../includes/employer_navbar.php';
require '../controllers/update_due_interviews.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Applications - Employer Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        :root {
            --primary: #4361ee;
            --primary-light: #e0e7ff;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --success-light: #e0f7fe;
            --warning: #f8961e;
            --warning-light: #fff4e6;
            --danger: #f94144;
            --danger-light: #ffebec;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --light-gray: #f1f3f5;
            --dark-blue: #1A4D8F;
            --medium-blue: #4B7DA3;
            --light-blue: #5C84A1;
            --lighter-blue: #82AAC7;
            --green: #56A47B;
            --red: #7B2E2A;
        }
        
        body {
            background-color: #f8fafc;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            color: var(--dark);
        }
        
        .container-fluid {
            padding: 2rem;
            max-width: 1550px;
        }
        
        /* Header Styles */
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1.5rem;
        }
        
        .dashboard-title {
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 0.5rem;
            font-size: 1.75rem;
        }
        
        .dashboard-subtitle {
            color: var(--gray);
            font-size: 0.95rem;
        }
        
        /* Stats cards */
        .stat-card {
            padding: 1.25rem;
            border-radius: 12px;
            color: white;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            height: 100%;
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 100%);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .stat-card h5 {
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            opacity: 0.9;
            font-weight: 500;
            position: relative;
        }
        
        .stat-card h2 {
            font-size: 2rem;
            margin-bottom: 0;
            font-weight: 700;
            position: relative;
        }
        
        /* Application cards */
        .application-card {
            background: white;
            border-radius: 12px;
            border-left: 4px solid transparent;
            transition: all 0.3s ease;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
            position: relative;
            overflow: hidden;
        }
        
        .application-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            border-color: var(--primary-light);
        }
        
        .application-card.pending {
            border-left-color: var(--warning);
        }
        
        .application-card.accepted {
            border-left-color: var(--success);
        }
        
        .application-card.rejected {
            border-left-color: var(--danger);
        }
        
        .application-card.interview {
            border-left-color: var(--primary);
        }
        
        .application-card.offered {
            border-left-color: #9c36b5;
        }
        
        .application-card.under-review {
            border-left-color: #f59f00;
        }
        
        .application-card.interview-scheduled {
            border-left-color: #9775fa;
        }
        
        .applicant-avatar-wrapper {
            position: relative;
            width: 48px;
            height: 48px;
            flex-shrink: 0;
        }
        
        .applicant-avatar {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }
        
        .applicant-default-icon {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background-color: var(--light-gray);
            color: var(--gray);
            font-size: 1.25rem;
        }
        
        .applicant-name {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.15rem;
            font-size: 1rem;
        }
        
        .applicant-title {
            font-size: 0.85rem;
            color: var(--gray);
            margin-bottom: 0.25rem;
        }
        
        .applicant-email {
            font-size: 0.8rem;
            color: var(--gray);
        }
        
        .skill-badge {
            background-color: var(--light-gray);
            color: var(--dark);
            margin-right: 0.3rem;
            margin-bottom: 0.3rem;
            font-size: 0.75rem;
            font-weight: 500;
            padding: 0.35em 0.65em;
            border-radius: 50px;
            display: inline-flex;
            align-items: center;
            transition: all 0.2s;
        }
        
        .skill-badge:hover {
            background-color: #e2e8f0;
            transform: translateY(-1px);
        }
        
        .skill-badge i {
            margin-right: 0.25rem;
            font-size: 0.65rem;
            color: var(--primary);
        }
        
        .match-score {
            font-weight: 700;
            font-size: 1.1rem;
        }
        
        .high-match { color: #2a9d8f; }
        .medium-match { color: #e9c46a; }
        .low-match { color: #e76f51; }
        
        .status-badge {
            padding: 0.35em 0.85em;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: 50px;
            display: inline-flex;
            align-items: center;
            white-space: nowrap;
        }
        
        .badge-pending { background-color: var(--warning-light); color: var(--warning); }
        .badge-review { background-color: #fff3cd; color: #856404; }
        .badge-accepted { background-color: var(--success-light); color: var(--success); }
        .badge-rejected { background-color: var(--danger-light); color: var(--danger); }
        .badge-interview { background-color: var(--primary-light); color: var(--primary); }
        .badge-offered { background-color: #f3e8ff; color: #9c36b5; }
        .badge-interview-scheduled { background-color: #e5dbff; color: #7048e8; }
        
        /* Job card */
        .job-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }
        
        .job-card:hover {
            box-shadow: 0 10px 25px rgba(0,0,0,0.12);
        }
        
        .job-header {
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #f8f9fa;
        }
        
        .job-title {
            font-weight: 700;
            margin-bottom: 0.25rem;
            font-size: 1.35rem;
            color: var(--dark);
        }
        
        .job-meta {
            display: flex;
            gap: 1.25rem;
            font-size: 0.9rem;
            color: var(--gray);
        }
        
        .job-meta span {
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }
        
        /* Application pipeline */
        .pipeline-container {
            display: flex;
            overflow-x: auto;
            padding-bottom: 1rem;
            gap: 1.25rem;
            margin-bottom: 2rem;
            scrollbar-width: thin;
        }
        
        .pipeline-stage {
            min-width: 300px;
            background: white;
            border-radius: 12px;
            padding: 1.25rem;
            height: fit-content;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
        }
        
        .pipeline-stage-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.25rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .pipeline-stage-title {
            font-weight: 600;
            margin-bottom: 0;
            font-size: 0.95rem;
            color: var(--dark);
        }
        
        .pipeline-stage-count {
            background: #f1f3f5;
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--dark);
        }
        
        /* Quick actions */
        .quick-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-action {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            border: none;
            background: transparent;
            color: var(--gray);
            transition: all 0.2s;
        }
        
        .btn-action:hover {
            background: rgba(0,0,0,0.05);
            transform: scale(1.1);
        }
        
        .btn-action.accept:hover {
            color: var(--success);
            background: var(--success-light);
        }
        
        .btn-action.reject:hover {
            color: var(--danger);
            background: var(--danger-light);
        }
        
        /* Custom dropdown */
        .dropdown-actions .dropdown-menu {
            /* Your existing styles */
            border: none;
            box-shadow: 0 4px 20px rgba(0,0,0,0.12);
            border-radius: 12px;
            padding: 0.5rem;
            min-width: 200px;

            /* NEW: Keep absolute positioning but prevent clipping */
            position: absolute !important; /* Let Bootstrap handle positioning */
            z-index: 9999 !important; /* High enough to stay on top */
            inset: auto auto 0 0 !important; /* Fallback position */
        }

        /* Ensure parent containers don't clip the dropdown */
        .application-card, .kanban-column {
            overflow: visible !important;
        }
        
        .dropdown-actions .dropdown-item {
            border-radius: 8px;
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.2s;
        }
        
        .dropdown-actions .dropdown-item:hover {
            background-color: #f8f9fa;
            transform: translateX(3px);
        }
        
        .dropdown-actions .dropdown-item i {
            width: 18px;
            text-align: center;
        }
        
        /* Progress bar */
        .progress-container {
            margin-bottom: 1rem;
        }
        
        .progress-labels {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            font-size: 0.75rem;
            color: var(--gray);
        }
        
        .progress {
            height: 8px;
            border-radius: 4px;
            background: var(--light-gray);
        }
        
        .progress-bar {
            border-radius: 4px;
        }
        
        /* Responsive adjustments */
        @media (max-width: 1200px) {
            .stat-card h2 {
                font-size: 1.5rem;
            }
        }
        
        @media (max-width: 992px) {
            .pipeline-container {
                flex-direction: column;
                overflow-x: visible;
            }
            
            .pipeline-stage {
                min-width: 100%;
            }
            
            .container-fluid {
                padding: 1.5rem;
            }
        }
        
        @media (max-width: 768px) {
            .application-card {
                padding: 1.5rem;
            }
            
            .applicant-info {
                margin-bottom: 1rem;
            }
            
            .dashboard-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .job-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .job-meta {
                flex-wrap: wrap;
                gap: 0.75rem;
            }
        }
        
        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 4rem 1rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            border: 1px dashed #e2e8f0;
            margin: 2rem 0;
        }
        
        .empty-state-icon {
            font-size: 3.5rem;
            color: var(--gray);
            margin-bottom: 1.5rem;
            opacity: 0.5;
        }
        
        /* Tabs */
        .nav-tabs {
            border-bottom: 2px solid #eee;
        }
        
        .nav-tabs .nav-link {
            border: none;
            color: var(--gray);
            font-weight: 500;
            padding: 0.75rem 1.5rem;
            position: relative;
            transition: all 0.2s;
        }
        
        .nav-tabs .nav-link:hover {
            color: var(--primary);
        }
        
        .nav-tabs .nav-link.active {
            color: var(--primary);
            background: transparent;
            font-weight: 600;
        }
        
        .nav-tabs .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 100%;
            height: 3px;
            background: var(--primary);
            border-radius: 3px 3px 0 0;
        }
        
        /* Search and filter */
        .search-filter-container {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
        }
        
        .search-input {
            position: relative;
        }
        
        .search-input i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
            z-index: 10;
        }
        
        .search-input input {
            padding-left: 2.75rem;
            border-radius: 50px;
            border: 1px solid #e2e8f0;
            transition: all 0.2s;
        }
        
        .search-input input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.1);
        }
        
        /* Tags */
        .tag {
            display: inline-flex;
            align-items: center;
            background: #f1f3f5;
            padding: 0.35rem 0.85rem;
            border-radius: 50px;
            font-size: 0.85rem;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
            transition: all 0.2s;
        }
        
        .tag:hover {
            background: #e2e8f0;
        }
        
        .tag-remove {
            margin-left: 0.5rem;
            cursor: pointer;
            opacity: 0.7;
            transition: all 0.2s;
        }
        
        .tag-remove:hover {
            opacity: 1;
            transform: scale(1.1);
        }
        
        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .fade-in {
            animation: fadeIn 0.4s ease-out forwards;
        }
        
        /* Loading skeleton */
        .skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
            border-radius: 8px;
        }
        
        @keyframes shimmer {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            height: 8px;
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #ccc;
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #aaa;
        }
        
        /* Buttons */
        .btn {
            border-radius: 8px;
            padding: 0.5rem 1rem;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        .btn-primary:hover {
            background-color: #3a56d4;
            border-color: #3a56d4;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(67, 97, 238, 0.2);
        }
        
        .btn-outline-primary {
            color: var(--primary);
            border-color: var(--primary);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary);
            border-color: var(--primary);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(67, 97, 238, 0.2);
        }
        
        /* View toggle buttons */
        .btn-group .btn {
            border-radius: 8px !important;
        }
        
        .btn-group .btn.active {
            background-color: var(--primary);
            color: white;
            box-shadow: 0 4px 12px rgba(67, 97, 238, 0.2);
        }
        
        /* Modal */
        .modal-content {
            border-radius: 12px;
            border: none;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        
        .modal-header {
            border-bottom: 1px solid #e2e8f0;
            padding: 1.5rem;
        }
        
        .modal-title {
            font-weight: 600;
            color: var(--dark);
        }
        
        .modal-body {
            padding: 1.5rem;
        }
        
        .modal-footer {
            border-top: 1px solid #e2e8f0;
            padding: 1.25rem 1.5rem;
        }
        
        /* Tooltip */
        .tooltip {
            font-family: 'Inter', sans-serif;
            font-size: 0.8rem;
        }
        
        /* Badges */
        .badge {
            font-weight: 500;
            padding: 0.35em 0.65em;
            border-radius: 50px;
        }
        
        /* Card hover effect */
        .card {
            transition: all 0.3s ease;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
        }
        
        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.08);
        }
        
        /* Dropdown toggle */
        .dropdown-toggle::after {
            margin-left: 0.5em;
            vertical-align: 0.15em;
        }
        
        /* Form controls */
        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            padding: 0.5rem 1rem;
            transition: all 0.2s;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.1);
        }
        
        /* Draggable item */
        .dragging {
            opacity: 0.8;
            transform: scale(1.02);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15) !important;
            z-index: 1000;
        }
        
        /* Drop zone highlight */
        .drop-highlight {
            background-color: rgba(67, 97, 238, 0.05);
            border: 2px dashed var(--primary);
        }
        /* Toast notifications */
        .toast-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1050;
        }

        .toast {
            max-width: 350px;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- Header Section -->
        <div class="dashboard-header">
            <div>
                <h1 class="dashboard-title">Job Applications</h1>
                <p class="dashboard-subtitle">Review and manage candidate applications for your open positions</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-primary" id="exportBtn">
                    <i class="fas fa-download me-2"></i> Export
                </button>
                <div class="dropdown">
                    <button class="btn btn-primary dropdown-toggle" type="button" id="addDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-plus me-2"></i> Add New
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                       <!--  <li><a class="dropdown-item" href="#"><i class="fas fa-user-plus me-2"></i> Manual Applicant</a></li>-->
                        <li><a class="dropdown-item" href="../dashboard/employer_post_job.php"><i class="fas fa-bullhorn me-2"></i> New Job Posting</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Search and Filter Section -->
        <div class="search-filter-container mb-4" style="display: none;">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="search-input">
                        <i class="fas fa-search"></i>
                        <input type="text" class="form-control ps-4" placeholder="Search applicants by name, skills..." id="searchInput">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="statusFilter" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-filter me-2"></i> Status
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" data-status="all">All Statuses</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="#" data-status="pending"><span class="badge-pending status-badge me-2">Pending</span></a></li>
                                <li><a class="dropdown-item" href="#" data-status="under review"><span class="badge-review status-badge me-2">Under Review</span></a></li>
                                <li><a class="dropdown-item" href="#" data-status="interview scheduled"><span class="badge-interview-scheduled status-badge me-2">Interview Scheduled</span></a></li>
                                <li><a class="dropdown-item" href="#" data-status="interview"><span class="badge-interview status-badge me-2">Interview</span></a></li>
                                <li><a class="dropdown-item" href="#" data-status="offered"><span class="badge-offered status-badge me-2">Offered</span></a></li>
                                <li><a class="dropdown-item" href="#" data-status="accepted"><span class="badge-accepted status-badge me-2">Accepted</span></a></li>
                                <li><a class="dropdown-item" href="#" data-status="rejected"><span class="badge-rejected status-badge me-2">Rejected</span></a></li>
                            </ul>
                        </div>
                        
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="jobFilter" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-briefcase me-2"></i> Job
                            </button>
                            <ul class="dropdown-menu" id="jobFilterMenu">
                                <li><a class="dropdown-item" href="#" data-job="all">All Jobs</a></li>
                                <li><hr class="dropdown-divider"></li>
                             
                            </ul>
                        </div>
                        
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="dateFilter" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-calendar me-2"></i> Date
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" data-date="all">All Time</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="#" data-date="today">Today</a></li>
                                <li><a class="dropdown-item" href="#" data-date="week">This Week</a></li>
                                <li><a class="dropdown-item" href="#" data-date="month">This Month</a></li>
                                <li><a class="dropdown-item" href="#" data-date="custom">Custom Range</a></li>
                            </ul>
                        </div>
                        
                        <button class="btn btn-outline-danger" id="clearFilters">
                            <i class="fas fa-times me-2"></i> Clear
                        </button>
                    </div>
                    
                    <div class="mt-3" id="activeFilters">
                       
                    </div>
                </div>
            </div>
        </div>
     
        <!-- Stats Overview -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-4 col-lg-2">
                <div class="stat-card" style="background-color: var(--dark-blue);" onclick="filterApplications('all')">
                    <h5>Total Applications</h5>
                    <h2 id="totalApps">0</h2>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="stat-card" style="background-color: var(--medium-blue);" onclick="filterApplications('pending')">
                    <h5>Pending</h5>
                    <h2 id="pendingApps">0</h2>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="stat-card" style="background-color: var(--light-blue);" onclick="filterApplications('interview')">
                    <h5>Interview</h5>
                    <h2 id="interviewApps">0</h2>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="stat-card" style="background-color: var(--lighter-blue);" onclick="filterApplications('offered')">
                    <h5>Offered</h5>
                    <h2 id="offeredApps">0</h2>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="stat-card" style="background-color: var(--green);" onclick="filterApplications('accepted')">
                    <h5>Accepted</h5>
                    <h2 id="hiredApps">0</h2>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="stat-card" style="background-color: var(--red);" onclick="filterApplications('rejected')">
                    <h5>Rejected</h5>
                    <h2 id="rejectedApps">0</h2>
                </div>
            </div>
        </div>

        <!-- View Toggle -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="mb-0">Current Applications</h5>
            <div class="btn-group" role="group" aria-label="View toggle">
                <button type="button" class="btn btn-outline-secondary active me-2" id="listViewBtn">
                    <i class="fas fa-list me-2"></i> List View
                </button>
                <button type="button" class="btn btn-outline-secondary me-2" id="pipelineViewBtn">
                    <i class="fas fa-project-diagram me-2"></i> Pipeline
                </button>
                <button type="button" class="btn btn-outline-secondary" id="hiredViewBtn">
                    <i class="fas fa-user-check me-2"></i> Hired
                </button>
            </div>
        </div>

        <!-- Pipeline View (Hidden by default) -->
        <div id="pipelineView" style="display: none;">
            <div class="pipeline-container" id="pipelineStages">
                <!-- Dynamically populated -->
            </div>
        </div>

        <!-- List View -->
        <div id="listView">
            <div id="jobs-container" class="fade-in"></div>
        </div>

        <!-- Empty State (Hidden by default) -->
        <div id="emptyState" class="empty-state" style="display: none;">
            <div class="empty-state-icon">
                <i class="fas fa-user-tie"></i>
            </div>
            <h4 class="mb-3">No applications found</h4>
            <p class="text-muted mb-4">You currently don't have any job applications matching your criteria</p>
            <button class="btn btn-primary" onclick="loadJobApplications()">
                <i class="fas fa-sync-alt me-2"></i> Refresh
            </button>
            <button class="btn btn-outline-primary ms-2" id="clearFiltersEmpty">
                <i class="fas fa-times me-2"></i> Clear filters
            </button>
        </div>

        <!-- Hired View -->
        <div id="hiredView" style="display: none;">
            <!-- Hired Individuals Section -->
            <div class="mt-5">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="mb-1" style="color: var(--dark); font-weight: 600;">
                            <i class="fas fa-trophy me-2" style="color: #6b7280;"></i>
                            Hired Individuals
                        </h4>
                        <p class="text-muted mb-0" style="font-size: 0.9rem;">
                            Track all successful hires and their employment details
                        </p>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-secondary btn-sm" id="exportHiredBtn">
                            <i class="fas fa-download me-2"></i> Export Hired
                        </button>
                        <button class="btn btn-outline-primary btn-sm" id="refreshHiredBtn">
                            <i class="fas fa-sync-alt me-2"></i> Refresh
                        </button>
                    </div>
                </div>

                <!-- Hired Stats Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-6 col-md-3">
                        <div class="stat-card" style="background-color: #4b5563;">
                            <h5>Total Hired</h5>
                            <h2 id="totalHired">0</h2>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-card" style="background-color: #6b7280;">
                            <h5>This Month</h5>
                            <h2 id="monthlyHired">0</h2>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-card" style="background-color: #9ca3af;">
                            <h5>This Quarter</h5>
                            <h2 id="quarterlyHired">0</h2>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-card" style="background-color: #d1d5db;">
                            <h5>This Year</h5>
                            <h2 id="yearlyHired">0</h2>
                        </div>
                    </div>
                </div>

                <!-- Hired Individuals Container -->
                <div id="hiredContainer">
                    <div class="row g-3" id="hiredGrid">
                        <!-- Dynamically populated -->
                    </div>
                </div>

                <!-- Hired Empty State -->
                <div id="hiredEmptyState" class="empty-state" style="display: none;">
                    <div class="empty-state-icon">
                        <i class="fas fa-trophy" style="color: #6b7280;"></i>
                    </div>
                    <h4 class="mb-3">No Hired Individuals Yet</h4>
                    <p class="text-muted mb-4">
                        When you hire candidates, they will appear here with their employment details
                    </p>
                    <button class="btn btn-outline-primary" onclick="loadHiredIndividuals()">
                        <i class="fas fa-sync-alt me-2"></i> Refresh
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Application Details Modal -->
    <div class="modal fade" id="applicantModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Applicant Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="applicantModalContent">
                    <!-- Dynamically populated -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <!-- <button type="button" class="btn btn-primary">Save changes</button> -->
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- AOS Animation -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    
    <script>
        // Initialize AOS animation
        AOS.init({
            duration: 600,
            easing: 'ease-out-quad',
            once: true
        });

        // Current filters state
        const filters = {
            status: 'all',
            job: 'all',
            date: 'all',
            search: ''
        };

        // Load application stats
        function loadApplicationStats() {
            fetch('../controllers/employer_applications.php')
                .then(response => {
                    if (!response.ok) throw new Error("Network response was not ok");
                    return response.json();
                })
                .then(data => {
                    document.getElementById('totalApps').textContent = data.total_applications || 0;
                    document.getElementById('pendingApps').textContent = data.pending || 0;
                    document.getElementById('hiredApps').textContent = data.accepted || 0;
                    document.getElementById('interviewApps').textContent = data.interview || 0;
                    document.getElementById('offeredApps').textContent = data.offered || 0;
                    document.getElementById('rejectedApps').textContent = data.rejected || 0;
                })
                .catch(error => {
                    console.error("Failed to load application stats:", error);
                    const placeholders = ['totalApps', 'pendingApps', 'hiredApps', 'interviewApps', 'offeredApps', 'rejectedApps'];
                    placeholders.forEach(id => document.getElementById(id).textContent = '—');
                });
        }

        // Load job applications
        function loadJobApplications() {
            // Show loading state
            document.getElementById('jobs-container').innerHTML = `
                <div class="card skeleton" style="height: 150px; margin-bottom: 1rem;"></div>
                <div class="card skeleton" style="height: 150px; margin-bottom: 1rem;"></div>
                <div class="card skeleton" style="height: 150px; margin-bottom: 1rem;"></div>
            `;
            
            // Build query string from filters
            const query = new URLSearchParams();
            if (filters.status !== 'all') query.append('status', filters.status);
            if (filters.job !== 'all') query.append('job', filters.job);
            if (filters.date !== 'all') query.append('date', filters.date);
            if (filters.search) query.append('search', filters.search);

            fetch(`../controllers/employer_job_applicants.php?${query.toString()}`)
                .then(response => response.json())
                .then(data => {
                    if (Array.isArray(data) && data.length > 0) {
                        renderJobApplications(data);
                        document.getElementById('emptyState').style.display = 'none';
                        document.getElementById('listView').style.display = 'block';
                    } else {
                        showEmptyState();
                    }
                    
                    // Also populate pipeline view
                    renderPipelineView(data);
                })
                .catch(error => {
                    console.error('Error fetching applications:', error);
                    showEmptyState();
                });
        }

        // Render job applications
        function renderJobApplications(jobs) {
            const container = document.getElementById('jobs-container');
            container.innerHTML = '';

            if (jobs.length === 0) {
                showEmptyState();
                return;
            }

            jobs.forEach((job, index) => {
                const jobCard = document.createElement('div');
                jobCard.className = 'job-card fade-in';
                jobCard.style.animationDelay = `${index * 0.05}s`;
                
                jobCard.innerHTML = `
                    <div class="job-header">
                        <div>
                            <h3 class="job-title">${job.title}</h3>
                            <div class="job-meta">
                                <span><i class="fas fa-map-marker-alt"></i> ${job.location || 'Remote'}</span>
                                <span><i class="fas fa-clock"></i> ${job.type || 'Full-time'}</span>
                                <span><i class="fas fa-user-friends"></i> ${job.applicants.length} applicants</span>
                            </div>
                        </div>
                        <button class="btn btn-sm btn-outline-primary">View Job</button>
                    </div>
                    <div class="p-3">
                        ${renderApplicants(job.applicants)}
                    </div>
                `;
                
                container.appendChild(jobCard);
            });
        }

        // Render pipeline view
        function renderPipelineView(jobs) {
            const pipelineContainer = document.getElementById('pipelineStages');
            if (!pipelineContainer) return;
            
            // Group all applicants by status
            const allApplicants = jobs.flatMap(job => job.applicants.map(app => ({ ...app, jobTitle: job.title })));
            const groupedByStatus = groupByStatus(allApplicants);
            
            pipelineContainer.innerHTML = `
                <div class="pipeline-stage" data-status="Pending">
                    <div class="pipeline-stage-header">
                        <h6 class="pipeline-stage-title">New Applications</h6>
                        <span class="pipeline-stage-count">${groupedByStatus.pending.length}</span>
                    </div>
                    ${renderPipelineApplicants(groupedByStatus.pending)}
                </div>
                
                <div class="pipeline-stage" data-status="Under Review">
                    <div class="pipeline-stage-header">
                        <h6 class="pipeline-stage-title">Under Review</h6>
                        <span class="pipeline-stage-count">${groupedByStatus.review.length}</span>
                    </div>
                    ${renderPipelineApplicants(groupedByStatus.review, 'Under Review')}
                </div>


                <div class="pipeline-stage" data-status="Interview Scheduled">
                    <div class="pipeline-stage-header">
                        <h6 class="pipeline-stage-title">Interview Scheduled</h6>
                        <span class="pipeline-stage-count">${groupedByStatus.interviewscheduled.length}</span>
                    </div>
                    ${renderPipelineApplicants(groupedByStatus.interviewscheduled)}
                </div>
                
                <div class="pipeline-stage" data-status="Interview">
                    <div class="pipeline-stage-header">
                        <h6 class="pipeline-stage-title">Interview</h6>
                        <span class="pipeline-stage-count">${groupedByStatus.interview.length}</span>
                    </div>
                    ${renderPipelineApplicants(groupedByStatus.interview)}
                </div>
                
                <div class="pipeline-stage" data-status="Offered">
                    <div class="pipeline-stage-header">
                        <h6 class="pipeline-stage-title">Offer</h6>
                        <span class="pipeline-stage-count">${groupedByStatus.offered.length}</span>
                    </div>
                    ${renderPipelineApplicants(groupedByStatus.offered)}
                </div>
                
                <div class="pipeline-stage" data-status="Accepted">
                    <div class="pipeline-stage-header">
                        <h6 class="pipeline-stage-title">Hired</h6>
                        <span class="pipeline-stage-count">${groupedByStatus.accepted.length}</span>
                    </div>
                    ${renderPipelineApplicants(groupedByStatus.accepted)}
                </div>
            `;
            
            // Make pipeline cards draggable
            initDragAndDrop();
        }
        
        // Group applicants by status
        function groupByStatus(applicants) {
            return applicants.reduce((acc, applicant) => {
                const status = applicant.application_status.toLowerCase();
                if (status === 'pending') {
                    acc.pending.push(applicant);
                } else if (status === 'under review') {
                    acc.review.push(applicant);
                } else if (status === 'interview scheduled') {
                    acc.interviewscheduled.push(applicant);
                } else if (status === 'interview') {
                    acc.interview.push(applicant);
                }else if (status === 'offered') {
                    acc.offered.push(applicant);
                } else if (status === 'accepted') {
                    acc.accepted.push(applicant);
                } else if (status === 'rejected' || status === 'withdrawn') {
                    acc.rejected.push(applicant);
                }
                return acc;
            }, {
                pending: [],
                review: [],
                interviewscheduled: [],
                interview: [],
                offered: [],
                accepted: [],
                rejected: []
            });
        }

        
        // Render applicants for pipeline view
        function renderPipelineApplicants(applicants, status = '') {
            if (applicants.length === 0) {
                return `
                    <div class="text-center py-3 text-muted">
                        <i class="fas fa-user-slash mb-2"></i>
                        <div class="small">No applicants</div>
                    </div>
                `;
            }

            return applicants.map(applicant => `
                <div class="application-card p-3 mb-2 ${toClassName(applicant.application_status)}" 
                    draggable="true" 
                    data-applicant-id="${applicant.application_id}" 
                    data-email="${applicant.email}">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div class="d-flex align-items-center">
                            <div class="applicant-avatar-wrapper me-2">
                                ${applicant.profile_picture ? `
                                    <img src="../Uploads/${applicant.profile_picture}"
                                        onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                        alt="Profile"
                                        class="applicant-avatar">
                                ` : ''}
                                <div class="applicant-default-icon" style="${applicant.profile_picture ? 'display:none;' : ''}">
                                    <i class="fas fa-user"></i>
                                </div>
                            </div>
                            <div>
                                <div class="applicant-name">${applicant.name}</div>
                                <div class="applicant-title small text-muted">${applicant.jobTitle}</div>
                            </div>
                        </div>
                        <div class="dropdown dropdown-actions">
                            <button class="btn-action" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="#" onclick="viewApplicantDetails(${applicant.stud_id})"><i class="fas fa-eye me-2"></i>View Profile</a></li>
                                <li>
                                    <a class="dropdown-item" href="#" onclick="viewResume(${applicant.stud_id}, '${applicant.resume_file || ''}')">
                                        <i class="fas fa-file-pdf me-2"></i> View Resume
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-danger" href="#" 
                                    onclick="confirmRemove(${applicant.application_id}, '${applicant.email}')">
                                        <i class="fas fa-trash me-2"></i>Remove
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="match-score ${getMatchScoreClass(applicant.match_score)} small">
                            ${applicant.match_score || 'N/A'}% match
                        </span>
                        <span class="text-muted small">${formatDate(applicant.applied_at)}</span>
                    </div>
                    ${applicant.application_status.toLowerCase() === 'under review' ? `
                        <div class="text-center mt-2">
                            <button class="btn btn-sm btn-outline-primary" onclick="scheduleInterview(${applicant.application_id}, '${applicant.email}')">
                                <i class="fas fa-calendar-alt me-1"></i> Schedule Interview
                            </button>
                        </div>
                    ` : ''}
                </div>
            `).join('');
        }
        function confirmRemove(applicationId, studentEmail) {
            Swal.fire({
                title: 'Reject Application?',
                text: "This will notify the candidate and cannot be undone!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, reject it!',
                showLoaderOnConfirm: true,
                preConfirm: () => {
                    return RemoveApplication(applicationId, studentEmail)
                        .then(response => {
                            if (!response.success) {
                                throw new Error(response.message || 'Failed to reject application');
                            }
                            return response;
                        })
                        .catch(error => {
                            Swal.showValidationMessage(
                                `Request failed: ${error.message}`
                            );
                        });
                },
                allowOutsideClick: () => !Swal.isLoading()
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire(
                        'Rejected!',
                        'The application has been rejected and the candidate has been notified.',
                        'success'
                    );
                    // Remove the row from the table
                    document.querySelector(`tr[data-application-id="${applicationId}"]`)?.remove();
                }
            });
        }

        async function RemoveApplication(applicationId, studentEmail) {
            console.log(`Rejecting application ID: ${applicationId}, student email: ${studentEmail}`);

            try {
                const response = await fetch('../employer_api/reject_application.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ 
                        application_id: applicationId,
                        email: studentEmail  // Make sure your PHP expects this field
                    })
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();
                
                if (!data.success) {
                    throw new Error(data.message || 'Unknown error occurred');
                }

                return data;
            } catch (error) {
                console.error('Error:', error);
                throw error; // Re-throw to be caught by the caller
            }
        }


        function scheduleInterview(applicationId, studentEmail) {
            const modalHtml = `
                <div class="modal fade" id="scheduleInterviewModal" tabindex="-1" aria-labelledby="scheduleInterviewModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="scheduleInterviewModalLabel">Schedule Interview</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form id="interviewForm">
                                    <div class="mb-3">
                                        <label for="interviewDate" class="form-label">Date & Time</label>
                                        <input type="datetime-local" class="form-control" id="interviewDate" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="interviewMode" class="form-label">Interview Mode</label>
                                        <select class="form-select" id="interviewMode" required>
                                            <option value="">Select Mode</option>
                                            <option value="In-person">In-person</option>
                                            <option value="Virtual">Virtual</option>
                                            <option value="Phone">Phone</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="location" class="form-label">Location/Details</label>
                                        <input type="text" class="form-control" id="location" required>
                                        <small class="text-muted">For virtual interviews, provide meeting link</small>
                                    </div>
                                    <div class="mb-3">
                                        <label for="notes" class="form-label">Additional Notes</label>
                                        <textarea class="form-control" id="notes" rows="3"></textarea>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-primary" id="confirmSchedule">Schedule Interview</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            document.body.insertAdjacentHTML('beforeend', modalHtml);
            const modal = new bootstrap.Modal(document.getElementById('scheduleInterviewModal'));
            modal.show();

            document.getElementById('confirmSchedule').addEventListener('click', async function() {
                const form = document.getElementById('interviewForm');
                if (!form.checkValidity()) {
                    form.classList.add('was-validated');
                    return;
                }

                const interviewData = {
                    application_id: applicationId,
                    email: studentEmail,
                    date: document.getElementById('interviewDate').value,
                    mode: document.getElementById('interviewMode').value,
                    location: document.getElementById('location').value,
                    notes: document.getElementById('notes').value
                };

                console.log('Scheduling interview:', interviewData);

                const confirmBtn = this;
                confirmBtn.disabled = true;
                confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Scheduling...';

                try {
                    const response = await fetch('../controllers/employer_schedule_interview.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(interviewData)
                    });

                    if (!response.ok) throw new Error(`HTTP error: ${response.status}`);

                    const text = await response.text();
                    if (!text) throw new Error('Empty response');

                    const result = JSON.parse(text);
                    console.log('Response:', result);

                    if (result.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Interview Scheduled',
                            text: result.message,
                            timer: 3000,
                            showConfirmButton: false
                        });
                        modal.hide();
                        document.getElementById('scheduleInterviewModal').remove();
                        loadJobApplications();
                        loadApplicationStats(); // Added to update stat cards
                    } else {
                        throw new Error(result.message || 'Failed to schedule');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: error.message
                    });
                } finally {
                    confirmBtn.disabled = false;
                    confirmBtn.textContent = 'Schedule Interview';
                }
            });

            document.getElementById('scheduleInterviewModal').addEventListener('hidden.bs.modal', function() {
                this.remove();
            });
        }









        
        // Initialize drag and drop for pipeline
        function initDragAndDrop() {
            const cards = document.querySelectorAll('.application-card[draggable="true"]');
            const stages = document.querySelectorAll('.pipeline-stage');
            
            cards.forEach(card => {
                card.addEventListener('dragstart', () => {
                    card.classList.add('dragging');
                });
                
                card.addEventListener('dragend', () => {
                    card.classList.remove('dragging');
                });
            });
            
            stages.forEach(stage => {
                stage.addEventListener('dragover', e => {
                    e.preventDefault();
                    const draggingCard = document.querySelector('.dragging');
                    if (draggingCard) {
                        const afterElement = getDragAfterElement(stage, e.clientY);
                        if (afterElement) {
                            stage.insertBefore(draggingCard, afterElement);
                        } else {
                            stage.appendChild(draggingCard);
                        }
                    }
                });
                
                stage.addEventListener('drop', e => {
                    e.preventDefault();
                    const draggingCard = document.querySelector('.dragging');
                    if (draggingCard) {
                        const applicantId = draggingCard.getAttribute('data-applicant-id');
                        const newStatus = stage.dataset.status;

                        if (!applicantId) {
                            console.error('No applicant ID found');
                            return;
                        }

                        fetch('../controllers/employer_job_applicants.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                action: 'update_status',
                                application_id: applicantId,
                                new_status: newStatus
                            })
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                updateApplicantStatus(applicantId, newStatus);
                                showToast('Status updated successfully', 'success');
                                loadApplicationStats();
                            } else {
                                showToast(data.error || 'Failed to update status', 'error');
                            }
                        })
                        .catch(err => {
                            console.error(err);
                            showToast('Server error', 'error');
                        });
                    }
                });
            });
        }
        
        function getDragAfterElement(container, y) {
            const draggableElements = [...container.querySelectorAll('.application-card:not(.dragging)')];
            
            return draggableElements.reduce((closest, child) => {
                const box = child.getBoundingClientRect();
                const offset = y - box.top - box.height / 2;
                
                if (offset < 0 && offset > closest.offset) {
                    return { offset: offset, element: child };
                } else {
                    return closest;
                }
            }, { offset: Number.NEGATIVE_INFINITY }).element;
        }
        
        // Update applicant status
        function toClassName(status) {
            return (status || '').toLowerCase().replace(/\s+/g, '-'); // "Interview Scheduled" → "interview-scheduled"
        }

        function formatStatusLabel(status) {
            return (status || '').split(' ')
                .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
                .join(' ');
        }

        function updateApplicantStatus(applicantId, newStatus) {
            console.log(`Updating applicant ${applicantId} to ${newStatus}`);
            
            // Update pipeline view card
            const pipelineCard = document.querySelector(`.pipeline-container .application-card[data-applicant-id="${applicantId}"]`);
            if (pipelineCard) {
                // Remove all known status classes
                const allStatusClasses = [
                    'pending', 'under-review', 'interview-scheduled', 'interview',
                    'offered', 'accepted', 'rejected', 'withdrawn'
                ];
                pipelineCard.classList.remove(...allStatusClasses);

                // Add new normalized class
                pipelineCard.classList.add(toClassName(newStatus));

                // Update the status badge
                const statusBadge = pipelineCard.querySelector('.status-badge');
                if (statusBadge) {
                    statusBadge.className = `status-badge ${getStatusBadgeClass(newStatus)}`;
                    statusBadge.textContent = formatStatusLabel(newStatus);
                }

                // Update the Schedule Interview button visibility
                let scheduleButtonContainer = pipelineCard.querySelector('.text-center.mt-2');
                if (newStatus.toLowerCase() === 'under review') {
                    // Add the button if it doesn't exist
                    if (!scheduleButtonContainer) {
                        scheduleButtonContainer = document.createElement('div');
                        scheduleButtonContainer.className = 'text-center mt-2';
                        pipelineCard.appendChild(scheduleButtonContainer);
                    }
                    scheduleButtonContainer.innerHTML = `
                        <button class="btn btn-sm btn-outline-primary" onclick="scheduleInterview(${applicantId}, '${pipelineCard.dataset.email || ''}')">
                            <i class="fas fa-calendar-alt me-1"></i> Schedule Interview
                        </button>
                    `;
                } else {
                    // Remove the button if it exists
                    if (scheduleButtonContainer) {
                        scheduleButtonContainer.remove();
                    }
                }
            }

            // Update list view card
            const listCard = document.querySelector(`#listView .application-card[data-applicant-id="${applicantId}"]`);
            if (listCard) {
                // Remove all known status classes
                const allStatusClasses = [
                    'pending', 'under-review', 'interview-scheduled', 'interview',
                    'offered', 'accepted', 'rejected', 'withdrawn'
                ];
                listCard.classList.remove(...allStatusClasses);

                // Add new normalized class
                listCard.classList.add(toClassName(newStatus));

                // Update the status badge
                const statusBadge = listCard.querySelector('.status-badge');
                if (statusBadge) {
                    statusBadge.className = `status-badge ${getStatusBadgeClass(newStatus)}`;
                    statusBadge.textContent = formatStatusLabel(newStatus);
                }

                // Update the Schedule Interview button visibility
                let scheduleButtonContainer = listCard.querySelector('.text-center.mt-2');
                if (newStatus.toLowerCase() === 'under review') {
                    // Add the button if it doesn't exist
                    if (!scheduleButtonContainer) {
                        scheduleButtonContainer = document.createElement('div');
                        scheduleButtonContainer.className = 'text-center mt-2';
                        listCard.appendChild(scheduleButtonContainer);
                    }
                    scheduleButtonContainer.innerHTML = `
                        <button class="btn btn-sm btn-outline-primary" onclick="scheduleInterview(${applicantId}, '${listCard.dataset.email || ''}')">
                            <i class="fas fa-calendar-alt me-1"></i> Schedule Interview
                        </button>
                    `;
                } else {
                    // Remove the button if it exists
                    if (scheduleButtonContainer) {
                        scheduleButtonContainer.remove();
                    }
                }
            }

            showToast('Status updated successfully', 'success');
            loadApplicationStats();
        }

        
        // Render applicants list
        function renderApplicants(applicants) {
            if (applicants.length === 0) {
                return `
                    <div class="text-center py-4">
                        <i class="fas fa-user-slash fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No applicants for this position</h5>
                    </div>
                `;
            }

            return `
                <div class="row g-3">
                    ${applicants.map(applicant => `
                        <div class="col-md-6 col-lg-4 col-xl-3">
                            <div class="application-card p-3 ${toClassName(applicant.application_status)}" 
                                data-applicant-id="${applicant.application_id}" 
                                data-email="${applicant.email}">
                                <div class="d-flex justify-content-between mb-3">
                                    <div class="d-flex align-items-center">
                                        <div class="applicant-avatar-wrapper me-3">
                                            ${applicant.profile_picture ? `
                                                <img src="../Uploads/${applicant.profile_picture}"
                                                    onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                                    alt="Profile"
                                                    class="applicant-avatar">
                                            ` : ''}
                                            <div class="applicant-default-icon" style="${applicant.profile_picture ? 'display:none;' : ''}">
                                                <i class="fas fa-user"></i>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="applicant-name">${applicant.name}</div>
                                            <div class="applicant-email">${applicant.email}</div>
                                        </div>
                                    </div>
                                    <div class="dropdown dropdown-actions">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                type="button" 
                                                data-bs-toggle="dropdown"
                                                aria-expanded="false">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="#" onclick="viewApplicantDetails(${applicant.stud_id})"><i class="fas fa-eye me-2"></i> View Profile</a></li>
                                            <li>
                                                <a class="dropdown-item" href="#" onclick="viewResume(${applicant.stud_id}, '${applicant.resume_file || ''}')">
                                                    <i class="fas fa-file-pdf me-2"></i> View Resume
                                                </a>
                                            </li>   
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item text-danger" href="#" onclick="confirmRemove(${applicant.application_id}, '${applicant.email}')">
                                                    <i class="fas fa-times me-2"></i> Reject
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item" href="../dashboard/messages.php"><i class="fas fa-envelope me-2"></i> Message</a></li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-muted small">Match Score</span>
                                        <span class="match-score ${getMatchScoreClass(applicant.match_score)}">
                                            ${applicant.match_score || 'N/A'}%
                                        </span>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar ${getMatchScoreClass(applicant.match_score)}" 
                                            role="progressbar" 
                                            style="width: ${applicant.match_score || 0}%">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="text-muted small">Status</span>
                                        <span class="status-badge ${getStatusBadgeClass(applicant.application_status)}">
                                            ${applicant.application_status}
                                        </span>
                                    </div>
                                    <div class="text-muted small">Applied ${formatDate(applicant.applied_at)}</div>
                                </div>
                                
                                <div class="skills-container">
                                    <div class="text-muted small mb-1">Skills</div>
                                    <div class="d-flex flex-wrap">
                                        ${(applicant.skills || []).slice(0, 5).map(skill => `
                                            <span class="skill-badge"><i class="fas fa-check-circle"></i> ${skill}</span>
                                        `).join('')}
                                        ${applicant.skills && applicant.skills.length > 5 ? `
                                            <span class="skill-badge">+${applicant.skills.length - 5}</span>
                                        ` : ''}
                                    </div>
                                </div>
                                
                                ${applicant.application_status.toLowerCase() === 'under review' ? `
                                    <div class="text-center mt-2">
                                        <button class="btn btn-sm btn-outline-primary" onclick="scheduleInterview(${applicant.application_id}, '${applicant.email}')">
                                            <i class="fas fa-calendar-alt me-1"></i> Schedule Interview
                                        </button>
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                    `).join('')}
                </div>
            `;
        }

        function viewResume(studId, resumeFile) {
            console.log(`Viewing resume for applicant ${studId}`);
            if (!resumeFile) {
                alert("No resume uploaded.");
                return;
            }

            const resumePath = `../Uploads/${resumeFile}`;
            window.open(resumePath, '_blank');
        }

        // Show empty state
        function showEmptyState() {
            document.getElementById('jobs-container').innerHTML = '';
            document.getElementById('listView').style.display = 'none';
            document.getElementById('pipelineView').style.display = 'none';
            document.getElementById('hiredView').style.display = 'none';
            document.getElementById('emptyState').style.display = 'block';
        }

        // Filter applications
        function filterApplications(status) {
            filters.status = status;
            updateActiveFilters();
            loadJobApplications();
        }
        
        // Update status
        function updateStatus(applicantId, newStatus) {
            console.log(`Updating applicant ${applicantId} to ${newStatus}`);
            
            const card = document.querySelector(`.application-card[data-applicant-id="${applicantId}"]`);
            if (card) {
                // Remove all possible status classes
                const allStatusClasses = [
                    'pending', 'under-review', 'interview-scheduled', 'interview',
                    'offered', 'accepted', 'rejected', 'withdrawn'
                ];
                card.classList.remove(...allStatusClasses);

                // Add the new normalized status class
                card.classList.add(toClassName(newStatus));

                // Update the status badge if it exists
                const statusBadge = card.querySelector('.status-badge');
                if (statusBadge) {
                    statusBadge.className = `status-badge ${getStatusBadgeClass(newStatus)}`;
                    statusBadge.textContent = newStatus;
                }
            }

            showToast('Status updated successfully', 'success');

            loadApplicationStats();
        }

        
        function viewApplicantDetails(applicantId) {
            console.log(`Viewing details for applicant ${applicantId}`);

            fetch(`../employer_api/get_applicant_details.php?applicantId=${applicantId}`)
                .then(response => response.json())
                .then(applicant => {
                    if (applicant.error) {
                        alert(applicant.error);
                        return;
                    }

                    const modalContent = document.getElementById('applicantModalContent');
                    modalContent.innerHTML = `
                        <div class="applicant-profile">
                            <!-- Header Section - Compact -->
                            <div class="profile-header p-3" style="background-color: #f8f9fa; border-bottom: 1px solid #e0e3e7;">
                                <div class="d-flex align-items-center">
                                    <div class="profile-avatar me-3">
                                        ${applicant.profile_picture ? 
                                            `<img src="../Uploads/${applicant.profile_picture}" class="rounded-circle border" style="width: 80px; height: 80px; object-fit: cover; border-color: #d1d7dc !important;" alt="Profile">` : 
                                            `<div class="rounded-circle border d-flex align-items-center justify-content-center" style="width: 80px; height: 80px; background-color: #e9ecef; border-color: #d1d7dc !important;">
                                                <i class="fas fa-user" style="font-size: 2rem; color: #6c757d;"></i>
                                            </div>`
                                        }
                                    </div>
                                    <div class="flex-grow-1">
                                        <h3 class="mb-1" style="color: #343a40; font-size: 1.4rem;">${applicant.stud_first_name} ${applicant.stud_last_name}</h3>
                                        <p class="mb-2 text-muted" style="font-size: 0.95rem;">${applicant.edu_background}</p>
                                        <div class="d-flex flex-wrap gap-1">
                                            ${applicant.institution ? `<span class="badge bg-light text-dark border" style="font-size: 0.8rem; font-weight: 500;"><i class="fas fa-university me-1 text-muted"></i> ${applicant.institution}</span>` : ''}
                                            ${applicant.graduation_yr ? `<span class="badge bg-light text-dark border" style="font-size: 0.8rem; font-weight: 500;"><i class="fas fa-calendar-alt me-1 text-muted"></i> Grad: ${applicant.graduation_yr}</span>` : ''}
                                            ${applicant.course_title ? `<span class="badge bg-light text-dark border" style="font-size: 0.8rem; font-weight: 500;"><i class="fas fa-book me-1 text-muted"></i> ${applicant.course_title}</span>` : ''}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Body Section - More Compact -->
                            <div class="profile-body p-3">
                                <div class="row g-3">
                                    <!-- Contact Column -->
                                    <div class="col-md-6">
                                        <div class="card h-100 border">
                                            <div class="card-body p-3">
                                                <h6 class="card-title fw-bold text-uppercase" style="font-size: 0.85rem; color: #495057; letter-spacing: 0.5px;"><i class="fas fa-envelope me-2 text-muted"></i>Contact</h6>
                                                <ul class="list-unstyled mb-0" style="font-size: 0.9rem;">
                                                    <li class="mb-1"><strong class="text-dark">Email:</strong> <a href="mailto:${applicant.stud_email}" class="text-decoration-none">${applicant.stud_email}</a></li>
                                                    ${applicant.phone ? `<li class="mb-1"><strong class="text-dark">Phone:</strong> ${applicant.phone}</li>` : ''}
                                                    ${applicant.linkedin ? `<li><strong class="text-dark">LinkedIn:</strong> <a href="${applicant.linkedin}" target="_blank" class="text-decoration-none">View Profile</a></li>` : ''}
                                                </ul>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Education Column -->
                                    <div class="col-md-6">
                                        <div class="card h-100 border">
                                            <div class="card-body p-3">
                                                <h6 class="card-title fw-bold text-uppercase" style="font-size: 0.85rem; color: #495057; letter-spacing: 0.5px;"><i class="fas fa-graduation-cap me-2 text-muted"></i>Education</h6>
                                                <div class="education-item">
                                                    <h6 class="fw-bold mb-1" style="font-size: 0.9rem; color: #343a40;">${applicant.edu_background}</h6>
                                                    <p class="mb-1 text-muted" style="font-size: 0.85rem;">${applicant.institution || 'Not specified'}</p>
                                                    ${applicant.course_title ? `<p class="mb-1 text-muted" style="font-size: 0.85rem;">${applicant.course_title}</p>` : ''}
                                                    ${applicant.graduation_yr ? `<p class="small text-muted" style="font-size: 0.8rem;">Expected graduation: ${applicant.graduation_yr}</p>` : ''}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Skills Section -->
                                <div class="card border mt-3">
                                    <div class="card-body p-3">
                                        <h6 class="card-title fw-bold text-uppercase" style="font-size: 0.85rem; color: #495057; letter-spacing: 0.5px;"><i class="fas fa-tools me-2 text-muted"></i>Skills</h6>
                                        <div class="p-2 rounded" style="background-color: #f8f9fa; font-size: 0.9rem;">
                                            ${applicant.skills ? applicant.skills.split(',').map(skill => `<span class="badge bg-white text-dark border me-1 mb-1" style="font-weight: 500;">${skill.trim()}</span>`).join('') : 'No skills listed'}
                                        </div>
                                    </div>
                                </div>

                                <!-- Professional Summary -->
                                <div class="card border mt-3">
                                    <div class="card-body p-3">
                                        <h6 class="card-title fw-bold text-uppercase" style="font-size: 0.85rem; color: #495057; letter-spacing: 0.5px;"><i class="fas fa-user-tie me-2 text-muted"></i>Summary</h6>
                                        <div class="p-2 rounded" style="background-color: #f8f9fa; font-size: 0.9rem;">
                                            <p class="mb-0" style="color: #495057;">${applicant.bio ? applicant.bio.replace(/\n/g, '<br>') : 'No professional summary provided.'}</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Resume Section -->
                                <div class="card border mt-3">
                                    <div class="card-body p-3">
                                        <h6 class="card-title fw-bold text-uppercase" style="font-size: 0.85rem; color: #495057; letter-spacing: 0.5px;"><i class="fas fa-file-pdf me-2 text-muted"></i>Resume</h6>
                                        ${applicant.resume_file ? `
                                        <div class="d-flex gap-2 mt-2">
                                            <a href="#" onclick="viewResume(${applicant.stud_id}, '${applicant.resume_file}')" class="btn btn-sm btn-outline-primary">View</a>
                                            <a href="../Uploads/${applicant.resume_file}" class="btn btn-sm btn-primary" download>Download</a>
                                        </div>` : '<p class="text-muted mb-0" style="font-size: 0.85rem;">No resume uploaded.</p>'}
                                    </div>
                                </div>

                                <!-- Interview Details -->
                                ${applicant.interview_id ? `
                                <div class="card border mt-3">
                                    <div class="card-body p-3">
                                        <h6 class="card-title fw-bold text-uppercase" style="font-size: 0.85rem; color: #495057; letter-spacing: 0.5px;"><i class="fas fa-calendar-check me-2 text-muted"></i>Interview</h6>
                                        <ul class="list-unstyled mb-0" style="font-size: 0.9rem;">
                                            <li class="mb-1"><strong class="text-dark">Date:</strong> ${new Date(applicant.interview_date).toLocaleDateString('en-US', { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })}</li>
                                            <li class="mb-1"><strong class="text-dark">Mode:</strong> <span class="text-capitalize">${applicant.interview_mode}</span></li>
                                            <li class="mb-1"><strong class="text-dark">Location:</strong> ${applicant.location_details}</li>
                                            <li class="mb-1"><strong class="text-dark">Status:</strong> <span class="badge ${getInterviewStatusBadgeClass(applicant.interview_status)}">${applicant.interview_status}</span></li>
                                            ${applicant.additional_notes ? `<li class="mt-2"><strong class="text-dark">Notes:</strong><div class="p-2 mt-1 rounded" style="background-color: #f8f9fa; font-size: 0.85rem;">${applicant.additional_notes}</div></li>` : ''}
                                        </ul>
                                    </div>
                                </div>
                                ` : ''}
                            </div>
                        </div>
                    `;

                    const modal = new bootstrap.Modal(document.getElementById('applicantModal'));
                    modal.show();
                })
                .catch(error => {
                    console.error('Error fetching applicant:', error);
                    alert('Failed to load applicant details.');
                });
        }

        // Helper function for interview status badges
        function getInterviewStatusBadgeClass(status) {
            const statusClasses = {
                'scheduled': 'bg-primary',
                'completed': 'bg-success',
                'cancelled': 'bg-secondary',
                'no-show': 'bg-danger',
                'rescheduled': 'bg-warning text-dark'
            };
            return statusClasses[status.toLowerCase()] || 'bg-light text-dark';
        }

        
        // Show toast notification
        function showToast(message, type = 'success') {
            let container = document.querySelector('.toast-container');
            if (!container) {
                container = document.createElement('div');
                container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
                container.style.zIndex = '1050';
                document.body.appendChild(container);
            }

            // Check for existing toasts with the same message and type
            const existingToasts = container.querySelectorAll('.toast');
            for (const toast of existingToasts) {
                const toastBody = toast.querySelector('.toast-body');
                if (toastBody && toastBody.textContent.trim() === message && toast.classList.contains(`bg-${type}`)) {
                    return; // Exit if a toast with the same message and type exists
                }
            }

            const toastContainer = document.createElement('div');
            toastContainer.className = `toast align-items-center text-white bg-${type} border-0`;
            toastContainer.setAttribute('role', 'alert');
            toastContainer.setAttribute('aria-live', 'assertive');
            toastContainer.setAttribute('aria-atomic', 'true');
            
            toastContainer.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            `;
            
            container.appendChild(toastContainer);
            const toast = new bootstrap.Toast(toastContainer);
            toast.show();
            
            toastContainer.addEventListener('hidden.bs.toast', () => {
                toastContainer.remove();
            });
        }
        
        // Update active filters display
        function updateActiveFilters() {
            const activeFiltersContainer = document.getElementById('activeFilters');
            activeFiltersContainer.innerHTML = '';
            
            const activeFilters = [];
            
            if (filters.status !== 'all') {
                activeFilters.push({
                    key: 'status',
                    value: filters.status,
                    label: `Status: ${filters.status.charAt(0).toUpperCase() + filters.status.slice(1)}`
                });
            }
            
            if (filters.job !== 'all') {
                activeFilters.push({
                    key: 'job',
                    value: filters.job,
                    label: `Job: ${filters.job}`
                });
            }
            
            if (filters.date !== 'all') {
                activeFilters.push({
                    key: 'date',
                    value: filters.date,
                    label: `Date: ${filters.date === 'custom' ? 'Custom Range' : filters.date.charAt(0).toUpperCase() + filters.date.slice(1)}`
                });
            }
            
            if (filters.search) {
                activeFilters.push({
                    key: 'search',
                    value: filters.search,
                    label: `Search: "${filters.search}"`
                });
            }
            
            if (activeFilters.length > 0) {
                const filterTags = document.createElement('div');
                filterTags.className = 'd-flex flex-wrap align-items-center gap-2';
                
                activeFilters.forEach(filter => {
                    const tag = document.createElement('span');
                    tag.className = 'tag';
                    tag.innerHTML = `
                        ${filter.label}
                        <span class="tag-remove" onclick="removeFilter('${filter.key}')">
                            <i class="fas fa-times"></i>
                        </span>
                    `;
                    filterTags.appendChild(tag);
                });
                
                activeFiltersContainer.appendChild(filterTags);
            }
        }
        
        // Remove filter
        function removeFilter(key) {
            filters[key] = key === 'status' ? 'all' : 
                          key === 'job' ? 'all' : 
                          key === 'date' ? 'all' : '';
            
            updateActiveFilters();
            loadJobApplications();
            
            // Reset dropdowns if needed
            if (key === 'status') {
                document.querySelector('#statusFilter').textContent = 'Status';
            } else if (key === 'job') {
                document.querySelector('#jobFilter').textContent = 'Job';
            } else if (key === 'date') {
                document.querySelector('#dateFilter').textContent = 'Date';
            } else if (key === 'search') {
                document.getElementById('searchInput').value = '';
            }
        }

        // Helper functions
        function formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            const now = new Date();
            const diff = Math.floor((now - date) / (1000 * 60 * 60 * 24));
            
            if (diff === 0) return 'today';
            if (diff === 1) return 'yesterday';
            if (diff < 7) return `${diff} days ago`;
            if (diff < 30) return `${Math.floor(diff/7)} week${Math.floor(diff/7) === 1 ? '' : 's'} ago`;
            return date.toLocaleDateString();
        }

        function getMatchScoreClass(score) {
            if (!score) return '';
            if (score >= 80) return 'high-match';
            if (score >= 50) return 'medium-match';
            return 'low-match';
        }

        function getStatusBadgeClass(status) {
            switch((status || '').toLowerCase()) {
                case 'pending': return 'badge-pending';
                case 'under review': return 'badge-review';
                case 'interview scheduled': return 'badge-interview-scheduled';
                case 'interview': return 'badge-interview';
                case 'offered': return 'badge-offered';
                case 'accepted': return 'badge-accepted';
                case 'rejected':
                case 'withdrawn': return 'badge-rejected';
                default: return 'badge-secondary';
            }
        }
        
        function toClassName(status) {
            return (status || '').toLowerCase().replace(/\s+/g, '-'); // e.g., "Interview Scheduled" → "interview-scheduled"
        }


        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadApplicationStats();
            loadJobApplications();
            loadHiredIndividuals(); // Load hired individuals on page load
            
            // Initialize view toggle buttons
            document.getElementById('listViewBtn').addEventListener('click', function() {
                this.classList.add('active');
                document.getElementById('pipelineViewBtn').classList.remove('active');
                document.getElementById('hiredViewBtn').classList.remove('active');

                document.getElementById('listView').style.display = 'block';
                document.getElementById('pipelineView').style.display = 'none';
                document.getElementById('hiredView').style.display = 'none';
            });

            document.getElementById('pipelineViewBtn').addEventListener('click', function() {
                this.classList.add('active');
                document.getElementById('listViewBtn').classList.remove('active');
                document.getElementById('hiredViewBtn').classList.remove('active');

                document.getElementById('listView').style.display = 'none';
                document.getElementById('pipelineView').style.display = 'block';
                document.getElementById('hiredView').style.display = 'none';
            });

            document.getElementById('hiredViewBtn').addEventListener('click', function() {
                this.classList.add('active');
                document.getElementById('listViewBtn').classList.remove('active');
                document.getElementById('pipelineViewBtn').classList.remove('active');

                document.getElementById('listView').style.display = 'none';
                document.getElementById('pipelineView').style.display = 'none';
                document.getElementById('hiredView').style.display = 'block';
            });

            
            // Initialize status filter dropdown items
            document.querySelectorAll('#statusFilterMenu .dropdown-item').forEach(item => {
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    filters.status = this.dataset.status;
                    document.getElementById('statusFilter').textContent = this.textContent.trim();
                    updateActiveFilters();
                    loadJobApplications();
                });
            });
            
            // Initialize job filter dropdown items
            document.querySelectorAll('#jobFilterMenu .dropdown-item').forEach(item => {
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    filters.job = this.dataset.job;
                    document.getElementById('jobFilter').textContent = this.textContent.trim();
                    updateActiveFilters();
                    loadJobApplications();
                });
            });
            
            // Initialize date filter dropdown items
            document.querySelectorAll('#dateFilterMenu .dropdown-item').forEach(item => {
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    filters.date = this.dataset.date;
                    document.getElementById('dateFilter').textContent = this.textContent.trim();
                    updateActiveFilters();
                    loadJobApplications();
                });
            });
            
            // Initialize search input
            const searchInput = document.getElementById('searchInput');
            let searchTimeout;
            
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    filters.search = this.value.trim();
                    updateActiveFilters();
                    loadJobApplications();
                }, 500);
            });
            
            // Clear filters button
            document.getElementById('clearFilters').addEventListener('click', function() {
                filters.status = 'all';
                filters.job = 'all';
                filters.date = 'all';
                filters.search = '';
                searchInput.value = '';
                
                document.getElementById('statusFilter').textContent = 'Status';
                document.getElementById('jobFilter').textContent = 'Job';
                document.getElementById('dateFilter').textContent = 'Date';
                
                updateActiveFilters();
                loadJobApplications();
            });
            
            document.getElementById('clearFiltersEmpty').addEventListener('click', function() {
                document.getElementById('clearFilters').click();
            });
            
            // Export button
            document.getElementById('exportBtn').addEventListener('click', function() {
                // Build query string from filters
                const query = new URLSearchParams();
                if (filters.status !== 'all') query.append('status', filters.status);
                if (filters.job !== 'all') query.append('job', filters.job);
                if (filters.date !== 'all') query.append('date', filters.date);
                if (filters.search) query.append('search', filters.search);

                // Show loading toast
                showToast('Preparing export...', 'info');

                // Delay the download to allow toast to render
                setTimeout(() => {
                    window.location.href = `../controllers/export_applications.php?${query.toString()}`;
                }, 900); // 300ms delay to ensure toast renders
            });

            // Hired section event listeners
            document.getElementById('refreshHiredBtn').addEventListener('click', function() {
                loadHiredIndividuals();
                showToast('Refreshing hired individuals...', 'info');
            });

            document.getElementById('exportHiredBtn').addEventListener('click', function() {
                showToast('Preparing hired export...', 'info');
                setTimeout(() => {
                    window.location.href = '../controllers/export_applications.php?type=hired';
                }, 900);
            });
        });

        // Load hired individuals
        function loadHiredIndividuals() {
            // Show loading state
            document.getElementById('hiredGrid').innerHTML = `
                <div class="col-md-6 col-lg-4">
                    <div class="card skeleton" style="height: 200px;"></div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="card skeleton" style="height: 200px;"></div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="card skeleton" style="height: 200px;"></div>
                </div>
            `;
            
            fetch('../controllers/employer_applications.php?type=hired')
                .then(response => response.json())
                .then(data => {
                    if (data.hired_individuals && data.hired_individuals.length > 0) {
                        renderHiredIndividuals(data.hired_individuals);
                        updateHiredStats(data.stats);
                        document.getElementById('hiredEmptyState').style.display = 'none';
                        document.getElementById('hiredContainer').style.display = 'block';
                    } else {
                        showHiredEmptyState();
                    }
                })
                .catch(error => {
                    console.error('Error fetching hired individuals:', error);
                    showHiredEmptyState();
                });
        }

        // Render hired individuals
        function renderHiredIndividuals(hiredList) {
            const container = document.getElementById('hiredGrid');
            container.innerHTML = '';

            hiredList.forEach((hire, index) => {
                const hireCard = document.createElement('div');
                hireCard.className = 'col-md-6 col-lg-4 col-xl-3 fade-in';
                hireCard.style.animationDelay = `${index * 0.1}s`;
                
                hireCard.innerHTML = `
                    <div class="card h-100 border-0 shadow-sm" style="border-radius: 12px; transition: all 0.3s ease;">
                        <div class="card-body p-3">
                            <!-- Header with avatar and name -->
                            <div class="d-flex align-items-center mb-3">
                                <div class="applicant-avatar-wrapper me-3">
                                    ${hire.profile_picture ? `
                                        <img src="../Uploads/${hire.profile_picture}"
                                            onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                            alt="Profile"
                                            class="applicant-avatar">
                                    ` : ''}
                                    <div class="applicant-default-icon" style="${hire.profile_picture ? 'display:none;' : ''}">
                                        <i class="fas fa-user"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 fw-bold" style="color: var(--dark);">${hire.name}</h6>
                                    <p class="mb-0 small text-muted">${hire.job_title}</p>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                            type="button" 
                                            data-bs-toggle="dropdown"
                                            aria-expanded="false">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="#" onclick="viewApplicantDetails(${hire.stud_id})">
                                            <i class="fas fa-eye me-2"></i> View Profile
                                        </a></li>
                                        <li><a class="dropdown-item" href="#" onclick="viewResume(${hire.stud_id}, '${hire.resume_file || ''}')">
                                            <i class="fas fa-file-pdf me-2"></i> View Resume
                                        </a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item" href="../dashboard/messages.php">
                                            <i class="fas fa-envelope me-2"></i> Message
                                        </a></li>
                                    </ul>
                                </div>
                            </div>

                            <!-- Employment Details -->
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted small">Hire Date</span>
                                    <span class="badge bg-success text-white small">
                                        ${formatDate(hire.hire_date)}
                                    </span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted small">Application Date</span>
                                    <span class="text-muted small">${formatDate(hire.applied_at)}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted small">Days to Hire</span>
                                    <span class="fw-bold" style="color: var(--green);">
                                        ${calculateDaysToHire(hire.applied_at, hire.hire_date)}
                                    </span>
                                </div>
                            </div>

                            <!-- Skills Preview -->
                            <div class="mb-3">
                                <div class="text-muted small mb-2">Key Skills</div>
                                <div class="d-flex flex-wrap gap-1">
                                    ${(hire.skills || []).slice(0, 3).map(skill => `
                                        <span class="badge bg-light text-dark border" style="font-size: 0.7rem; font-weight: 500;">
                                            ${skill}
                                        </span>
                                    `).join('')}
                                    ${hire.skills && hire.skills.length > 3 ? `
                                        <span class="badge bg-light text-dark border" style="font-size: 0.7rem; font-weight: 500;">
                                            +${hire.skills.length - 3}
                                        </span>
                                    ` : ''}
                                </div>
                            </div>

                            <!-- Contact Info -->
                            <div class="border-top pt-2">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="small text-muted">
                                        <i class="fas fa-envelope me-1"></i>
                                        ${hire.email}
                                    </div>
                                    <button class="btn btn-sm btn-outline-success" onclick="viewApplicantDetails(${hire.stud_id})">
                                        <i class="fas fa-user me-1"></i> Profile
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                container.appendChild(hireCard);
            });
        }

        // Update hired stats
        function updateHiredStats(stats) {
            if (stats) {
                document.getElementById('totalHired').textContent = stats.total || 0;
                document.getElementById('monthlyHired').textContent = stats.monthly || 0;
                document.getElementById('quarterlyHired').textContent = stats.quarterly || 0;
                document.getElementById('yearlyHired').textContent = stats.yearly || 0;
            }
        }

        // Show hired empty state
        function showHiredEmptyState() {
            document.getElementById('hiredContainer').style.display = 'none';
            document.getElementById('hiredEmptyState').style.display = 'block';
        }

        // Calculate days to hire
        function calculateDaysToHire(appliedDate, hireDate) {
            if (!appliedDate || !hireDate) return 'N/A';
            
            const applied = new Date(appliedDate);
            const hired = new Date(hireDate);
            const diffTime = Math.abs(hired - applied);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            
            if (diffDays === 0) return 'Same day';
            if (diffDays === 1) return '1 day';
            return `${diffDays} days`;
        }
    </script>
</body>
</html>

<?php include '../includes/stud_footer.php'; ?>