<?php 
include '../includes/stud_navbar.php';
require '../auth/auth_check_student.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Listings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.6.8/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.6.8/dist/sweetalert2.all.min.js"></script>
    <style>
        :root {
            --primary-color: #1A4D8F;
            --accent-color: #4cc9f0;
            --success-color: #38b000;
            --warning-color: #ffaa00;
            --danger-color: #ef233c;
            --light-bg: #f8f9fa;
            --card-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --card-hover-shadow: 0 8px 16px rgba(67, 97, 238, 0.15);
        }
        
        body {
            background-color: #f8f9fa;
        }
        
        .job-container {
            color: #333;
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
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
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            display: flex;
            flex-direction: column;
            height: 100%;
            position: relative;
        }
        
        .job-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
            transform: translateY(-1px);
        }

        .job-card.active {
            border: 1px solid #4a6bff;
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
        .expires-soon{
            margin-left: 5px;
        }
        .text-muted{
            margin-left: 5px;
        }
        .salary{
            margin-left: 5px;
        }
        .salary-highlight {
            font-weight: 400;
            color: var(--success-color);
            font-size: 0.85rem;
        }
        
        .expires-soon {
            color: var(--danger-color);
            font-weight: 400;
        }
        
        .match-badge {
            font-size: 0.75rem;
            padding: 4px 8px;
            border-radius: 4px;
            position: absolute;
            top: 15px;
            right: 15px;
        }
        
        .high-match {
            background-color: #dcfce7;
            color: var(--success-color);
        }
        
        .medium-match {
            background-color: #fef9c3;
            color: #ca8a04;
        }
        
        .low-match {
            background-color: #fee2e2;
            color: var(--danger-color);
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
            padding-bottom: 16px;
            margin-bottom: 16px;
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
        
        .skills-container {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }
        
        .skill-tag {
            display: inline-flex;
            align-items: center;
            background-color: #f3f4f6;
            padding: 5px 12px;
            border-radius: 20px;
            margin: 2px;
            font-size: 0.85rem;
            transition: all 0.2s ease;
        }
        
        .high-skill {
            background-color: #dcfce7;
            color: var(--success-color);
            border-left: 3px solid var(--success-color);
        }
        
        .medium-skill {
            background-color: #fef9c3;
            color: #ca8a04;
            border-left: 3px solid #f59e0b;
        }
        
        .low-skill {
            background-color: #fee2e2;
            color: var(--danger-color);
            border-left: 3px solid var(--danger-color);
        }
        
        .skill-tag i {
            margin-left: 5px;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        
        .btn-apply {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 25px;
            font-weight: 400;
            flex-grow: 1;
        }
        
        .btn-apply:hover {
            background-color: var(--secondary-color);
        }
        
        .btn-save {
            border: 1px solid var(--primary-color);
            color: var(--primary-color);
            padding: 10px 25px;
            font-weight: 400;
            flex-grow: 1;
        }
        
        .btn-save:hover {
            background-color: #f0f4ff;
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
        
        .status-badge {
            font-size: 0.8rem;
            padding: 4px 10px;
            border-radius: 20px;
            font-weight: 500;
        }
        
        .status-applied {
            background-color: #e0f2fe;
            color: #0369a1;
        }
        
        .status-interview {
            background-color: #f0f9ff;
            color: #0c4a6e;
        }
        
        .status-rejected {
            background-color: #fee2e2;
            color: #b91c1c;
        }
    </style>
    <style>
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
            background-color: #e0e7ff;
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
        
        .filter-chip i {
            margin-left: 5px;
            font-size: 0.9rem;
        }
        
        .filter-chip.active {
            background-color: var(--primary-color);
            color: white;
        }

        .filter-chip:hover {
            background: #e9ecef;
            transform: translateY(-1px);
        }

        .filter-chip.active {
            background: #1A4D8F;
            color: white;
            border-color: #1A4D8F;
        }

        .filter-label {
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: #495057;
        }
    </style>
    <style>
        .jobsearch-input::placeholder {
            font-size: 16px; 
            color: #999;      
        }

        .jobsearch-container {
            max-width: 75%;
            margin: 0 auto;
            padding: 16px;
            font-family: Arial, sans-serif;
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
            background-color: #2557a7;
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
        }

        .jobsearch-submit-btn:hover {
            background-color: #1a4b8e;
        }
    </style>

    <style>
        .text-truncate {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    </style>

</head>
<body>

<div class="container job-container">
    <!-- Header Section -->
    <div class="page-header">
        <h1 class="page-title">Job Opportunities</h1>
        <div class="d-flex flex-wrap gap-2">
            <button class="btn btn-outline-primary d-flex align-items-center" onclick="showRecommendedJobs()">
                <i class="bi bi-stars me-2"></i> Recommended
            </button>
            <button class="btn btn-outline-secondary d-flex align-items-center" onclick="showAllJobs()">
                <i class="bi bi-list-ul me-2"></i> All Jobs
            </button>
        </div>
    </div>

    <?php
        require '../config/dbcon.php';
        /** @var PDO $conn */
        $jobTypes = [];
        $categories = [];

        try {
            $stmt = $conn->prepare("SELECT job_type_title FROM job_type");
            $stmt->execute();
            $jobTypes = $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            echo "Error fetching job types: " . $e->getMessage();
        }
        try{
            $stmt = $conn->prepare("SELECT DISTINCT category FROM skill_masterlist");
            $stmt->execute();
            $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }catch (PDOException $e){
            echo "Error fetching categories: " .$e->getMessage();
        }
    ?>

    <div class="jobsearch-container">
        <div class="jobsearch-form" >
            <!-- What Field -->
            <div class="jobsearch-field jobsearch-what-field">
                <div class="jobsearch-input-wrapper">
                    <input 
                    type="text" 
                    id="search" 
                    class="jobsearch-input" 
                    name="q" 
                    placeholder="Job title, keywords, or company" 
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
                    name="l" 
                    placeholder="Any location" 
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
                <button class="btn btn-primary w-100 d-flex align-items-center justify-content-center" onclick="filterJobs()" style="background-color: #1A4D8F; border-color: #1A4D8F;">
                    Find Jobs
                </button>
            </div>
        </div>
    </div>

    <!-- Filter Toggle Button -->
    <div class="mb-3">
        <button class="btn btn-outline-primary filter-toggle-btn" type="button" id="filterToggleBtn">
            <i class="bi bi-funnel-fill me-2"></i> <span class="btn-text">Show Filters</span>
        </button>
    </div>

    <!-- Filters Section -->
    <div class="filter-container" id="filterContainer">
        <div class="filter-section">
            <div class="row filter-row g-3">
                <div class="col-md-5">
                    <div class="filter-label">Category</div>
                    <select id="category" class="form-select">
                        <option value="">All Categories</option>
                        <?php
                            foreach($categories as $category){
                                echo "<option value='$category'>$category</option>";
                            }
                        ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <div class="filter-label">Job Type</div>
                    <select id="jobType" class="form-select">
                        <option value="">All Types</option>
                        <?php
                        foreach ($jobTypes as $type) {
                            echo "<option value='$type'>$type</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-primary w-100 d-flex align-items-center justify-content-center" onclick="filterJobs()" style="background-color: #1A4D8F; border-color: #1A4D8F;">
                        <i class="bi bi-funnel-fill me-2"></i> Filter
                    </button>
                </div>
            </div>
            
            <!-- Quick Filters -->
            <div class="filter-chips mt-3">
                <div class="form-check form-check-inline m-0">
                    <input class="form-check-input d-none" type="checkbox" id="remoteOnly" value="1">
                    <label class="filter-chip" for="remoteOnly" onclick="toggleFilterChip(this)">
                        <i class="bi bi-laptop"></i> Remote Only
                    </label>
                </div>
                <div class="form-check form-check-inline m-0">
                    <input class="form-check-input d-none" type="checkbox" id="expiringSoon" value="1">
                    <label class="filter-chip" for="expiringSoon" onclick="toggleFilterChip(this)">
                        <i class="bi bi-clock"></i> Expiring Soon
                    </label>
                </div>
                <div class="form-check form-check-inline m-0">
                    <input class="form-check-input d-none" type="checkbox" id="highMatch" value="1">
                    <label class="filter-chip" for="highMatch" onclick="toggleFilterChip(this)">
                        <i class="bi bi-lightning-charge"></i> High Match
                    </label>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="row">
        <!-- Job Listings Column -->
        <div class="col-lg-5">
            <div id="jobListings" class="scrollable-container pe-3">
                <div class="spinner-container">
                    <div class="spinner-border spinner" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Job Details Column -->
        <div class="col-lg-7">
            <div id="jobDetails" class="job-details-container scrollable-container fade-in">
                <div class="empty-state">
                    <i class="bi bi-briefcase" style="font-size: 3rem;"></i>
                    <h5 class="mt-3">Select a job to view details</h5>
                    <p class="text-muted">Browse through the job listings on the left to see more information</p>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/stud_footer.php'; ?>

<script>
    // Toggle Filters Functionality
    document.addEventListener('DOMContentLoaded', function() {
        const filterToggle = document.getElementById('filterToggleBtn');
        const filterContainer = document.getElementById('filterContainer');
        
        // Initialize as collapsed
        filterContainer.classList.add('collapsed');
        
        filterToggle.addEventListener('click', function() {
            const content = filterContainer.querySelector('.filter-section');
            
            if (filterContainer.classList.contains('collapsed')) {
                // Calculate full height
                const contentHeight = content.scrollHeight;
                filterContainer.style.height = contentHeight + 'px';
                filterContainer.classList.remove('collapsed');
                this.querySelector('.btn-text').textContent = 'Hide Filters';
                this.classList.remove('collapsed');
                
                // Remove fixed height after transition to allow responsive behavior
                setTimeout(() => {
                    filterContainer.style.height = 'auto';
                }, 300);
            } else {
                // Set fixed height before collapsing for smooth animation
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

    // Toggle Filter Chips
    function toggleFilterChip(element) {
        const checkbox = document.getElementById(element.htmlFor);
        checkbox.checked = !checkbox.checked;
        element.classList.toggle('active', checkbox.checked);
        filterJobs(); // Optional: trigger filtering immediately
    }

    // Filter Jobs Function (placeholder)
    function filterJobs() {
        // Your existing filter logic here
        console.log('Filtering jobs...');
    }
</script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    fetchJobs();
    fetchStudentSkills();
});

let jobData = [];
let selectedJobId = null;
let studentSkills = [];
let isShowingRecommended = false;
let activeFilters = {
    remoteOnly: false,
    expiringSoon: false,
    highMatch: false
};

// Fetch student skills on page load
function fetchStudentSkills() {
    fetch("../controllers/student_skills.php?action=get_student_skills")
        .then(response => response.json())
        .then(data => {
            studentSkills = data;
        })
        .catch(error => {
            console.error("Error fetching student skills:", error);
        });
}

function fetchJobs() {
    document.getElementById("jobListings").innerHTML = `
        <div class="spinner-container">
            <div class="spinner-border spinner" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>`;
    
    fetch("../controllers/student_job.php?action=get_all_jobs") 
        .then(response => response.json())
        .then(data => {
            jobData = data;
            if (jobData.length > 0) {
                renderJobListings(jobData);
                selectJob(jobData[0].job_id); // Select first job by default
            } else {
                showEmptyState();
            }
        })
        .catch(error => {
            console.error("Error fetching jobs:", error);
            showErrorState();
        });
}

function fetchRecommendedJobs() {
    document.getElementById("jobListings").innerHTML = `
        <div class="spinner-container">
            <div class="spinner-border spinner" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>`;
    
    fetch("../controllers/student_job.php?action=get_recommended_jobs")
        .then(response => response.json())
        .then(data => {
            jobData = data;
            if (jobData.length > 0) {
                renderJobListings(jobData);
                selectJob(jobData[0].job_id); // Select first job by default
            } else {
                showEmptyState("No recommended jobs found based on your skills");
            }
        })
        .catch(error => {
            console.error("Error fetching recommended jobs:", error);
            showErrorState("Error loading recommended jobs");
        });
}

function showRecommendedJobs() {
    isShowingRecommended = true;
    document.querySelector('.page-title').textContent = "Recommended Jobs";
    fetchRecommendedJobs();
}

function showAllJobs() {
    isShowingRecommended = false;
    document.querySelector('.page-title').textContent = "Job Opportunities";
    fetchJobs();
}

function formatSalary(job) {
    if (job.salary_disclosure && job.min_salary && job.max_salary) {
        return `₱${Number(job.min_salary).toLocaleString()} - ₱${Number(job.max_salary).toLocaleString()} per ${job.salary_type.toLowerCase()}`;
    } else if (job.salary_disclosure && job.min_salary) {
        return `₱${Number(job.min_salary).toLocaleString()} per ${job.salary_type.toLowerCase()}`;
    } else {
        return job.salary_type === 'Negotiable' ? 'Negotiable' : `Salary ${job.salary_type.toLowerCase()}`;
    }
}

function renderJobListings(jobs) {
    const container = document.getElementById("jobListings");
    
    if (jobs.length === 0) {
        showEmptyState();
        document.getElementById("jobDetails").innerHTML = `
            <div class="empty-state">
                <i class="bi bi-exclamation-circle"></i>
                <h5 class="mt-3">No jobs found</h5>
                <p class="text-muted">Try adjusting your filters or check back later</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = jobs.map(job => `
        <div class="job-card fade-in ${selectedJobId === job.job_id ? 'active' : ''}" 
             onclick="selectJob('${sanitize(job.job_id)}')">
            ${job.match_score ? getMatchBadge(job.match_score) : ''}
            <div class="job-title">${sanitize(job.title)}</div>
            <div class="company-name">${sanitize(job.company_name || job.company)}</div>
            
            <div class="job-meta">
                <span class="job-meta-item"><i class="bi bi-geo-alt"></i> ${sanitize(job.location)}</span>
                <span class="job-meta-item"><i class="bi bi-clock"></i> ${getTimeAgo(job.posted_at)}</span>
                <span class="job-type-badge">${sanitize(job.job_type_title || job.job_type)}</span>
            </div>
            
            <div class="salary"><i class="bi bi-cash"></i> ${formatSalary(job)}</div>
            
            <div class="d-flex justify-content-between mt-2">
                ${isJobExpiringSoon(job.expires_at) ? 
                  `<div class="expires-soon"><i class="bi bi-clock"></i> Expires soon: ${formatDate(job.expires_at)}</div>` : 
                  `<div class="text-muted"><i class="bi bi-clock"></i> Expires: ${formatDate(job.expires_at)}</div>`}
            </div>
        </div>
    `).join('');
}

function getMatchBadge(score) {
    if (score >= 70) {
        return `<span class="match-badge high-match"><i class="bi bi-check-circle-fill me-1"></i> ${score}% Match</span>`;
    } else if (score >= 40) {
        return `<span class="match-badge medium-match"><i class="bi bi-dash-circle-fill me-1"></i> ${score}% Match</span>`;
    } else {
        return `<span class="match-badge low-match"><i class="bi bi-exclamation-circle-fill me-1"></i> ${score}% Match</span>`;
    }
}

function selectJob(jobId) {
    selectedJobId = jobId;
    const job = jobData.find(j => j.job_id == jobId);
    
    if (job) {
        // Show loading state in details panel
        document.getElementById("jobDetails").innerHTML = `
            <div class="spinner-container">
                <div class="spinner-border spinner" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>`;
        
        // Fetch job skills and match details
        fetch(`../controllers/student_job.php?action=get_job_details&job_id=${jobId}`)
            .then(response => response.json())
            .then(jobDetails => {
                renderJobDetails(job, jobDetails);
            })
            .catch(error => {
                console.error("Error fetching job details:", error);
                renderJobDetails(job, null);
            });
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

function renderJobDetails(job, jobDetails) {
    const container = document.getElementById("jobDetails");
    
    let html = `
       <div class="detail-header">
        <div class="d-flex justify-content-between align-items-center">
            <div class="job-info flex-grow-1 pe-3" style="max-width: 70%;">
                <h2 class="detail-title text-truncate">${sanitize(job.title)}</h2>
                <h4 class="detail-company text-truncate">${sanitize(job.company_name || job.company)}</h4>
                <div class="detail-meta">
                    <span class="detail-meta-item"><i class="bi bi-geo-alt"></i> ${sanitize(job.location)}</span>
                    <span class="detail-meta-item" data-bs-toggle="tooltip" title="Job Type"><i class="bi bi-briefcase"></i> ${sanitize(job.job_type_title || job.job_type)}</span>
                    <span class="detail-meta-item" data-bs-toggle="tooltip" title="Salary"><i class="bi bi-cash"></i> ${formatSalary(job)}</span>
                </div>
                <div class="d-flex flex-wrap gap-3">
                    <span class="detail-meta-item"><i class="bi bi-calendar"></i> Posted: ${formatDate(job.posted_at)}</span>
                    ${isJobExpiringSoon(job.expires_at) ? 
                    `<span class="detail-meta-item expires-soon"><i class="bi bi-clock"></i> Expires soon: ${formatDate(job.expires_at)}</span>` : 
                    `<span class="detail-meta-item"><i class="bi bi-clock"></i> Expires: ${formatDate(job.expires_at)}</span>`}
                </div>
            </div>
            
            <div class="action-buttons flex-shrink-0">
                <div class="d-flex flex-column gap-2">
                    ${job.application_status ? 
                    `<div class="alert alert-info mt-2">
                        <i class="bi bi-info-circle-fill me-2"></i> 
                        You've already applied to this position. 
                        Current status: <strong class="${getStatusClass(job.application_status)}">${job.application_status}</strong>
                    </div>
                    <a href="job_details.php?id=${sanitize(job.job_id)}" class="btn btn-outline-primary mt-2">
                        <i class="bi bi-eye"></i> View Application
                    </a>` : 
                    `<button class="btn btn-apply" data-job-id="${job.job_id}" onclick="confirmApply(${job.job_id})">
                            <i class="bi bi-send-check"></i> Apply Now
                        </button>
                        <button class="btn btn-save" onclick="${job.is_saved ? 'unsaveJob' : 'saveJob'}(${job.job_id})">
                            <i class="bi bi-bookmark${job.is_saved ? '-fill' : ''}"></i> ${job.is_saved ? 'Saved' : 'Save Job'}
                        </button>`
                    }
                </div>
            </div>
        </div>
    </div>
    `;

    if (jobDetails && jobDetails.skills && jobDetails.skills.length > 0) {
        html += `
            <div class="detail-section">
                <h5 class="section-title"><i class="bi bi-tools"></i> Required Skills</h5>
                <div class="skills-container">
        `;

        jobDetails.skills.forEach(skill => {
            const studentSkill = studentSkills.find(s => s.skill_id == skill.skill_id);
            const skillClass = studentSkill ? 
                (studentSkill.proficiency === 'Advanced' ? 'high-skill' : 
                 studentSkill.proficiency === 'Intermediate' ? 'medium-skill' : 'low-skill') : '';
            
            const skillLevel = studentSkill ? 
                `<span class="text-muted" style="font-size:0.7rem;">(${studentSkill.proficiency})</span>` : '';
            
            html += `
                <span class="skill-tag ${skillClass}" title="${skill.importance} importance">
                    ${sanitize(skill.skill_name)} ${skillLevel}
                    ${studentSkill ? `<i class="bi bi-check-circle-fill"></i>` : ''}
                </span>
            `;
        });

        html += `</div></div>`;
    }

    // Add job description with cleaner layout
    html += `
        <div class="detail-section">
            <h5 class="section-title"><i class="bi bi-file-text"></i> Job Description</h5>
            <div class="job-description">
                ${sanitize(job.description || 'No description provided').replace(/\n/g, '<br>')}
            </div>
        </div>
    `;

    // Add responsibilities if available
    if (jobDetails && jobDetails.responsibilities) {
        html += `
            <div class="detail-section">
                <h5 class="section-title"><i class="bi bi-list-check"></i> Key Responsibilities</h5>
                <ul class="job-description">
                    ${jobDetails.responsibilities.split('\n').map(item => item.trim() ? `<li>${sanitize(item)}</li>` : '').join('')}
                </ul>
            </div>
        `;
    }

    container.innerHTML = html;

    // Enable tooltips
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltipTriggerList.forEach(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
}

function getStatusClass(status) {
    switch(status.toLowerCase()) {
        case 'pending': return 'status-pending';
        case 'accepted': return 'status-accepted';
        case 'rejected': return 'status-rejected';
        default: return '';
    }
}

function filterJobs() {
    const search = document.getElementById("search").value.toLowerCase();
    const category = document.getElementById("category").value.toLowerCase();
    const jobType = document.getElementById("jobType").value.toLowerCase();
    const location = document.getElementById("location").value.toLowerCase();
    
    activeFilters.remoteOnly = document.getElementById("remoteOnly").checked;
    activeFilters.expiringSoon = document.getElementById("expiringSoon").checked;
    activeFilters.highMatch = document.getElementById("highMatch").checked;

    const now = new Date();
    const soonDate = new Date();
    soonDate.setDate(now.getDate() + 7);

    const filteredJobs = jobData.filter(job => {
        const matchesSearch = search === "" || 
             job.title.toLowerCase().includes(search) || 
             (job.company_name && job.company_name.toLowerCase().includes(search)) ||
             (job.company && job.company.toLowerCase().includes(search));
        
        const matchesCategory = category === "" || 
             (job.categories && job.categories.toLowerCase().split(', ').some(cat => cat.toLowerCase() === category));

        const matchesJobType = jobType === "" || 
            (job.job_type_title && job.job_type_title.toLowerCase().includes(jobType)) ||
            (job.job_type && job.job_type.toLowerCase().includes(jobType));
        
        const matchesLocation = location === "" || 
            (job.location && job.location.toLowerCase().includes(location));
        
        const matchesRemote = !activeFilters.remoteOnly || 
            (job.location && job.location.toLowerCase().includes('remote'));
        
        const matchesExpiringSoon = !activeFilters.expiringSoon || 
            (job.expires_at && new Date(job.expires_at) <= soonDate);
        
        const matchesHighMatch = !activeFilters.highMatch || 
            (job.match_score && job.match_score >= 70);
        
        return matchesSearch && matchesCategory && matchesJobType && 
               matchesLocation && matchesRemote && matchesExpiringSoon && matchesHighMatch;
    });

    renderJobListings(filteredJobs);
    
    if (filteredJobs.length > 0) {
        selectJob(filteredJobs[0].job_id);
    } else {
        document.getElementById("jobDetails").innerHTML = `
            <div class="empty-state">
                <i class="bi bi-exclamation-circle"></i>
                <h5 class="mt-3">No jobs match your filters</h5>
                <p class="text-muted">Try adjusting your search criteria</p>
            </div>
        `;
    }
}

function toggleFilterChip(element) {
    const checkbox = document.getElementById(element.htmlFor);

    setTimeout(() => {
        if (checkbox.checked) {
            element.classList.add('active');
        } else {
            element.classList.remove('active');
        }

        filterJobs();
    }, 0);
}

function saveJob(jobId) {
    const btn = document.querySelector(`button[onclick="saveJob(${jobId})"]`);
    btn.innerHTML = `<i class="bi bi-bookmark-fill"></i> Saving...`;
    btn.disabled = true;
    
    fetch("../controllers/student_job.php?action=save_job", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify({ job_id: jobId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            btn.innerHTML = `<i class="bi bi-bookmark-fill"></i> Saved`;
            btn.setAttribute('onclick', `unsaveJob(${jobId})`);
            showToast('Job saved successfully!', 'success');
        } else {
            btn.innerHTML = `<i class="bi bi-bookmark"></i> Save Job`;
            btn.disabled = false;
            showToast('Error saving job: ' + (data.message || "Unknown error"), 'danger');
        }
    })
    .catch(error => {
        console.error("Error saving job:", error);
        btn.innerHTML = `<i class="bi bi-bookmark"></i> Save Job`;
        btn.disabled = false;
        showToast('Error saving job. Please try again.', 'danger');
    });
}

function unsaveJob(jobId) {
    const btn = document.querySelector(`button[onclick="unsaveJob(${jobId})"]`);
    btn.innerHTML = `<i class="bi bi-bookmark"></i> Unsaving...`;
    btn.disabled = true;
    
    fetch("../controllers/student_job.php?action=unsave_job", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify({ job_id: jobId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            btn.innerHTML = `<i class="bi bi-bookmark"></i> Save Job`;
            btn.setAttribute('onclick', `saveJob(${jobId})`);
            showToast('Job unsaved successfully!', 'success');
        } else {
            btn.innerHTML = `<i class="bi bi-bookmark-fill"></i> Saved`;
            btn.disabled = false;
            showToast('Error unsaving job: ' + (data.message || "Unknown error"), 'danger');
        }
    })
    .catch(error => {
        console.error("Error unsaving job:", error);
        btn.innerHTML = `<i class="bi bi-bookmark-fill"></i> Saved`;
        btn.disabled = false;
        showToast('Error unsaving job. Please try again.', 'danger');
    });
}

function isJobExpiringSoon(expiryDate) {
    if (!expiryDate) return false;
    const now = new Date();
    const soonDate = new Date();
    soonDate.setDate(now.getDate() + 7);
    return new Date(expiryDate) <= soonDate;
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

function getTimeAgo(timestamp, referenceTime = null) {
    const manilaTimeZone = 'Asia/Manila';
    
    const date = new Date(timestamp);
    const now = referenceTime ? new Date(referenceTime) : new Date();

    const options = { timeZone: manilaTimeZone, year: 'numeric', month: '2-digit', day: '2-digit', 
                      hour: '2-digit', minute: '2-digit', second: '2-digit' };
    
    const manilaDate = new Date(new Intl.DateTimeFormat('en-US', options).format(date));
    const manilaNow = new Date(new Intl.DateTimeFormat('en-US', options).format(now));
    
    let diffInSeconds = Math.floor((manilaNow - manilaDate) / 1000);

    const units = [
        { label: 'year', seconds: 365 * 24 * 60 * 60 },
        { label: 'month', seconds: 30 * 24 * 60 * 60 },
        { label: 'day', seconds: 24 * 60 * 60 },
        { label: 'hour', seconds: 60 * 60 },
        { label: 'minute', seconds: 60 },
        { label: 'second', seconds: 1 },
    ];

    for (const unit of units) {
        const value = Math.floor(diffInSeconds / unit.seconds);
        if (value > 0) {
            return `${value} ${unit.label}${value > 1 ? 's' : ''} ago`;
        }
    }

    return 'just now';
}

function sanitize(text) {
    if (!text) return '';
    const temp = document.createElement("div");
    temp.textContent = text;
    return temp.innerHTML;
}

function showEmptyState(message = "No jobs available at the moment") {
    document.getElementById("jobListings").innerHTML = `
        <div class="empty-state">
            <i class="bi bi-briefcase"></i>
            <h5 class="mt-3">${message}</h5>
            <p class="text-muted">Check back later for new opportunities</p>
        </div>
    `;
}

function showErrorState(message = "Error loading jobs") {
    document.getElementById("jobListings").innerHTML = `
        <div class="empty-state">
            <i class="bi bi-exclamation-triangle text-danger"></i>
            <h5 class="mt-3">${message}</h5>
            <p class="text-muted">Please try again later</p>
            <button class="btn btn-outline-primary mt-3" onclick="fetchJobs()">
                <i class="bi bi-arrow-repeat"></i> Retry
            </button>
        </div>
    `;
}

function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast show position-fixed bottom-0 end-0 mb-4 me-4 bg-${type} text-white`;
    toast.style.zIndex = '1100';
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <i class="bi ${type === 'success' ? 'bi-check-circle' : 'bi-exclamation-triangle'} me-2"></i>
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

function applyForJob(jobId) {
    console.log("Applying for job ID:", jobId);
    const btn = document.querySelector(`.btn-apply[data-job-id="${jobId}"]`);
    if (!btn) return;
    
    btn.innerHTML = `<i class="bi bi-hourglass"></i> Applying...`;
    btn.disabled = true;
    
    fetch("../controllers/student_apply_job.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify({ job_id: jobId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            btn.innerHTML = `<i class="bi bi-check-circle"></i> Applied`;
            btn.classList.remove('btn-primary');
            btn.classList.add('btn-success');
            showToast('Application submitted successfully!', 'success');
            
            // Update the UI to show application status
            const jobCard = document.querySelector(`.job-card[onclick*="${jobId}"]`);
            if (jobCard) {
                const statusBadge = document.createElement('div');
                statusBadge.className = 'status-badge status-applied mt-2';
                statusBadge.innerHTML = '<i class="bi bi-send-check"></i> Applied - Pending';
                jobCard.querySelector('.job-meta').appendChild(statusBadge);
            }
            
            // Refresh the details view
            if (selectedJobId == jobId) {
                selectJob(jobId);
            }
        } else {
            btn.innerHTML = `<i class="bi bi-send-check"></i> Apply Now`;
            btn.disabled = false;
            showToast(data.message || 'Error submitting application', 'danger');
        }
    })
    .catch(error => {
        console.error("Error applying for job:", error);
        btn.innerHTML = `<i class="bi bi-send-check"></i> Apply Now`;
        btn.disabled = false;
        showToast('Error submitting application. Please try again.', 'danger');
    });
}

function confirmApply(jobId) {
    Swal.fire({
        title: 'Are you sure?',
        text: "Do you really want to apply for this job?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, apply!',
        cancelButtonText: 'No, cancel',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            // Proceed with the job application
            applyForJob(jobId);
        }
    });
}

document.addEventListener("DOMContentLoaded", function() {
    // Function to handle job selection
    function handleJobSelection() {
        const urlParams = new URLSearchParams(window.location.search);
        const jobId = urlParams.get('id');
        
        if (jobId && /^\d+$/.test(jobId)) {
            // Show loading state
            const detailsSection = document.getElementById("jobDetails");
            if (detailsSection) {
                detailsSection.innerHTML = `
                    <div class="text-center py-4">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                        <p>Loading job details...</p>
                    </div>`;
            }
            
            // Clean URL but preserve parameters
            history.replaceState(null, null, window.location.pathname + '?' + urlParams.toString());
            
            // Try to select job
            const attemptSelection = () => {
                if (typeof selectJob === 'function') {
                    selectJob(jobId);
                    if (detailsSection) {
                        detailsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                } else {
                    // If selectJob isn't available yet, try again
                    setTimeout(attemptSelection, 100);
                }
            };
            
            // Start with a small delay to let other scripts load
            setTimeout(attemptSelection, 300);
        }
    }
    
    // Check URL for selection trigger
    if (window.location.hash === '#select') {
        handleJobSelection();
    }
    
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('select_job')) {
        handleJobSelection();
    }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>