<?php 
include '../includes/stud_navbar.php';
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
    <style>
        :root {
            
            --accent-color: #4cc9f0;
            --success-color: #38b000;
            --warning-color: #ffaa00;
            --danger-color: #ef233c;
            --light-bg: #f8f9fa;
            --card-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --card-hover-shadow: 0 8px 16px rgba(67, 97, 238, 0.15);
        }
        
        body {
            background-color: var(--light-bg);
        }
        
        .job-container {
            font-family: 'Poppins', sans-serif;
            color: #333;
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        /* Header Styles */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .page-title {
            font-weight: 600;
            color: var(--primary-color);
            margin: 0;
        }
        
        /* Filter Section */
        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            margin-bottom: 25px;
        }
        
        .filter-row {
            align-items: flex-end;
        }
        
        .filter-label {
            font-size: 0.85rem;
            font-weight: 500;
            color: #666;
            margin-bottom: 5px;
        }
        
        /* Job Cards */
        .job-card {
            background: white;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            margin-bottom: 15px;
            padding: 20px;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
            cursor: pointer;
            position: relative;
        }
        
        .job-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--card-hover-shadow);
            border-left-color: var(--primary-color);
        }
        
        .job-card.active {
            border-left-color: var(--primary-color);
            background-color: #f5f7ff;
        }
        
        .job-title {
            font-weight: 600;
            color: #222;
            font-size: 1.1rem;
            margin-bottom: 5px;
        }
        
        .company-name {
            color: #555;
            font-weight: 500;
            font-size: 0.95rem;
            margin-bottom: 8px;
        }
        
        .job-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 12px;
        }
        
        .job-meta-item {
            display: flex;
            align-items: center;
            font-size: 0.85rem;
            color: #666;
        }
        
        .job-meta-item i {
            margin-right: 5px;
            color: var(--primary-color);
        }
        
        .job-type-badge {
            font-size: 0.75rem;
            font-weight: 500;
            padding: 4px 8px;
            border-radius: 4px;
            background-color: #e0e7ff;
            color: var(--primary-color);
        }
        
        .salary-highlight {
            font-weight: 600;
            color: var(--success-color);
            font-size: 0.95rem;
        }
        
        .expires-soon {
            color: var(--danger-color);
            font-weight: 500;
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
        
        /* Job Details */
        .job-details-container {
            background: white;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            height: 100%;
            padding: 25px;
            position: relative;
        }
        
        .detail-header {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
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
            font-weight: 600;
            color: #333;
            margin-bottom: 12px;
            font-size: 1.1rem;
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
        
        /* Skills Section */
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
        
        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        
        .btn-apply {
            background-color: var(--primary-color);
            border: none;
            padding: 10px 25px;
            font-weight: 500;
            flex-grow: 1;
        }
        
        .btn-apply:hover {
            background-color: var(--secondary-color);
        }
        
        .btn-save {
            border: 1px solid var(--primary-color);
            color: var(--primary-color);
            padding: 10px 25px;
            font-weight: 500;
            flex-grow: 1;
        }
        
        .btn-save:hover {
            background-color: #f0f4ff;
        }
        
        /* Empty States */
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
        
        /* Scrollable Containers */
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
        
        /* Responsive Adjustments */
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
        
        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .fade-in {
            animation: fadeIn 0.3s ease-out forwards;
        }
        
        /* Loading Spinner */
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
        
        /* Status Badges */
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
        
        /* Chip Filters */
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

    <!-- Filters Section -->
    <div class="filter-section">
        <div class="row filter-row g-3">
            <div class="col-md-4">
                <div class="filter-label">Search Keywords</div>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" id="search" class="form-control" placeholder="Job title, company, or keywords">
                </div>
            </div>
            <div class="col-md-2">
                <div class="filter-label">Category</div>
                <select id="category" class="form-select">
                    <option value="">All Categories</option>
                    <?php
                    $categories = ['IT', 'Marketing', 'Finance', 'Engineering', 'Design', 'Healthcare', 'Education'];
                    foreach ($categories as $category) {
                        echo "<option value='$category'>$category</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-2">
                <div class="filter-label">Job Type</div>
                <select id="jobType" class="form-select">
                    <option value="">All Types</option>
                    <?php
                    $jobTypes = ['Full-time', 'Part-time', 'Contract', 'Internship', 'Remote'];
                    foreach ($jobTypes as $type) {
                        echo "<option value='$type'>$type</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-2">
                <div class="filter-label">Location</div>
                <input type="text" id="location" class="form-control" placeholder="Any location">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-primary w-100 d-flex align-items-center justify-content-center" onclick="filterJobs()">
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
            
            ${job.salary ? `<div class="salary-highlight"><i class="bi bi-cash"></i> $${sanitize(job.salary.toLocaleString())}/year</div>` : ''}
            
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
            // Scroll into view if not fully visible
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
    
    // Basic job info
    let html = `
        <div class="detail-header">
            <h2 class="detail-title">${sanitize(job.title)}</h2>
            <h4 class="detail-company">${sanitize(job.company_name || job.company)}</h4>
            
            <div class="detail-meta">
                <span class="detail-meta-item"><i class="bi bi-geo-alt"></i> ${sanitize(job.location)}</span>
                <span class="detail-meta-item"><i class="bi bi-briefcase"></i> ${sanitize(job.job_type_title || job.job_type)}</span>
                ${job.salary ? `<span class="detail-meta-item"><i class="bi bi-cash"></i> $${sanitize(job.salary.toLocaleString())}/year</span>` : ''}
            </div>
            
            <div class="d-flex flex-wrap gap-3">
                <span class="detail-meta-item"><i class="bi bi-calendar"></i> Posted: ${formatDate(job.posted_at)}</span>
                ${isJobExpiringSoon(job.expires_at) ? 
                  `<span class="detail-meta-item expires-soon"><i class="bi bi-clock"></i> Expires soon: ${formatDate(job.expires_at)}</span>` : 
                  `<span class="detail-meta-item"><i class="bi bi-clock"></i> Expires: ${formatDate(job.expires_at)}</span>`}
            </div>
        </div>
        
        <div class="detail-section">
            <h5 class="section-title"><i class="bi bi-file-text"></i> Job Description</h5>
            <div class="job-description">
                ${sanitize(job.description || 'No description provided').replace(/\n/g, '<br>')}
            </div>
        </div>
    `;
    
    // Add skills section if we have details
    if (jobDetails && jobDetails.skills && jobDetails.skills.length > 0) {
        html += `
            <div class="detail-section">
                <h5 class="section-title"><i class="bi bi-tools"></i> Required Skills</h5>
                <div class="skills-container">
        `;
        
        jobDetails.skills.forEach(skill => {
            // Check if student has this skill
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
    
    // Add application status if already applied
    if (job.application_status) {
        html += `
            <div class="alert alert-info mt-4">
                <i class="bi bi-info-circle-fill me-2"></i> 
                You've already applied to this position. 
                Current status: <strong class="${getStatusClass(job.application_status)}">${job.application_status}</strong>
            </div>
            
            <div class="action-buttons">
                <a href="job_details.php?id=${sanitize(job.job_id)}" class="btn btn-outline-primary">
                    <i class="bi bi-eye"></i> View Application
                </a>
            </div>
        `;
    } else {
        // Add apply button
        html += `
            <div class="action-buttons">
                <a href="job_details.php?id=${sanitize(job.job_id)}" class="btn btn-apply">
                    <i class="bi bi-send-check"></i> Apply Now
                </a>
                <button class="btn btn-save" onclick="saveJob(${job.job_id})">
                    <i class="bi bi-bookmark${isJobSaved(job.job_id) ? '-fill' : ''}"></i> ${isJobSaved(job.job_id) ? 'Saved' : 'Save Job'}
                </button>
            </div>
        `;
    }
    
    container.innerHTML = html;
}

function getStatusClass(status) {
    switch(status.toLowerCase()) {
        case 'applied': return 'status-applied';
        case 'interview': return 'status-interview';
        case 'rejected': return 'status-rejected';
        default: return '';
    }
}

function isJobSaved(jobId) {
    // This would need to be implemented with actual saved jobs data
    return false;
}

function filterJobs() {
    const search = document.getElementById("search").value.toLowerCase();
    const category = document.getElementById("category").value.toLowerCase();
    const jobType = document.getElementById("jobType").value.toLowerCase();
    const location = document.getElementById("location").value.toLowerCase();
    
    // Update active filters
    activeFilters.remoteOnly = document.getElementById("remoteOnly").checked;
    activeFilters.expiringSoon = document.getElementById("expiringSoon").checked;
    activeFilters.highMatch = document.getElementById("highMatch").checked;

    const now = new Date();
    const soonDate = new Date();
    soonDate.setDate(now.getDate() + 7); // 7 days from now

    const filteredJobs = jobData.filter(job => {
        const matchesSearch = search === "" || 
             job.title.toLowerCase().includes(search) || 
             (job.company_name && job.company_name.toLowerCase().includes(search)) ||
             (job.company && job.company.toLowerCase().includes(search));
        
        const matchesCategory = category === "" || 
            (job.category && job.category.toLowerCase().includes(category));
        
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
    
    // Select the first job if available
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

    // Let the label toggle the checkbox naturally on click
    setTimeout(() => {
        if (checkbox.checked) {
            element.classList.add('active');
        } else {
            element.classList.remove('active');
        }

        filterJobs();
    }, 0); // wait for checkbox state to update
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

function isJobExpiringSoon(expiryDate) {
    if (!expiryDate) return false;
    const now = new Date();
    const soonDate = new Date();
    soonDate.setDate(now.getDate() + 7); // 7 days from now
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

function getTimeAgo(dateString) {
    if (!dateString) return "N/A";
    const date = new Date(dateString);
    const now = new Date();
    const diffInDays = Math.floor((now - date) / (1000 * 60 * 60 * 24));
    
    if (diffInDays === 0) return "Today";
    if (diffInDays === 1) return "Yesterday";
    if (diffInDays < 7) return `${diffInDays} days ago`;
    if (diffInDays < 30) return `${Math.floor(diffInDays / 7)} weeks ago`;
    return formatDate(dateString);
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
    // Implement a toast notification system
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
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>