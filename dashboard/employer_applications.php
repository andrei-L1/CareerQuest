<?php
require '../auth/employer_auth.php';
include '../includes/employer_navbar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Applications - Employer Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --warning: #f8961e;
            --danger: #f94144;
            --light: #f8f9fa;
            --dark: #212529;
        }
        
        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }
        
        .container-fluid {
            padding: 1.5rem;
            max-width: 1450px;
        }
        
        /* Compact header */
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        /* Stats cards */
        .stat-card {
            padding: 0.75rem;
            border-radius: 0.5rem;
            color: white;
            text-align: center;
            transition: transform 0.2s;
            cursor: pointer;
            height: 100%;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .stat-card h5 {
            font-size: 0.85rem;
            margin-bottom: 0.25rem;
            opacity: 0.9;
        }
        
        .stat-card h2 {
            font-size: 1.5rem;
            margin-bottom: 0;
            font-weight: 600;
        }
        
        /* Application cards */
        .application-card {
            background: white;
            border-radius: 0.5rem;
            border-left: 4px solid transparent;
            transition: all 0.2s;
            margin-bottom: 1rem;
        }
        
        .application-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
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
        
        .applicant-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .applicant-name {
            font-weight: 600;
            color: var(--dark);
        }
        
        .applicant-email {
            font-size: 0.8rem;
            color: #6c757d;
        }
        
        .skill-badge {
            background-color: #e9ecef;
            color: #495057;
            margin-right: 0.3rem;
            margin-bottom: 0.3rem;
            font-size: 0.75rem;
            font-weight: 500;
            padding: 0.35em 0.65em;
        }
        
        .match-score {
            font-weight: 700;
        }
        
        .high-match { color: #2a9d8f; }
        .medium-match { color: #e9c46a; }
        .low-match { color: #e76f51; }
        
        .status-badge {
            padding: 0.35em 0.65em;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-pending { background-color: rgba(248, 150, 30, 0.1); color: var(--warning); }
        .badge-accepted { background-color: rgba(76, 201, 240, 0.1); color: var(--success); }
        .badge-rejected { background-color: rgba(249, 65, 68, 0.1); color: var(--danger); }
        
        /* Job card */
        .job-card {
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
            overflow: hidden;
        }
        
        .job-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #f8f9fa;
        }
        
        .job-title {
            font-weight: 600;
            margin-bottom: 0;
        }
        
        /* Custom dropdown */
        .dropdown-actions .dropdown-menu {
            border: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border-radius: 0.5rem;
            padding: 0.5rem;
        }
        
        .dropdown-actions .dropdown-item {
            border-radius: 0.25rem;
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
        }
        
        .dropdown-actions .dropdown-item i {
            width: 20px;
            text-align: center;
            margin-right: 0.5rem;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .application-card {
                padding: 1rem;
            }
            
            .applicant-info {
                margin-bottom: 1rem;
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
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- Header Section -->
        <div class="dashboard-header">
            <div>
                <h1 class="h3 mb-0">Job Applications</h1>
                <p class="text-muted mb-0">Review and manage candidate applications</p>
            </div>
            <div>
                <button class="btn btn-sm btn-outline-primary me-2" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                    <i class="fas fa-filter me-1"></i> Filters
                </button>
                <div class="btn-group">
                    <button class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-download me-1"></i> Export
                    </button>
                    <button class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-print me-1"></i> Print
                    </button>
                </div>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="collapse mb-4" id="filterCollapse">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form class="row g-3">
                        <div class="col-md-4">
                            <label for="jobSelect" class="form-label">Job Position</label>
                            <select class="form-select" id="jobSelect">
                                <option selected>All Positions</option>
                                <option>Frontend Developer</option>
                                <option>Backend Engineer</option>
                                <option>UX Designer</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="statusSelect" class="form-label">Status</label>
                            <select class="form-select" id="statusSelect">
                                <option selected>All Statuses</option>
                                <option>Pending</option>
                                <option>Accepted</option>
                                <option>Rejected</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="dateSelect" class="form-label">Date Range</label>
                            <select class="form-select" id="dateSelect">
                                <option selected>Any Time</option>
                                <option>Last 7 Days</option>
                                <option>Last 30 Days</option>
                                <option>Last 90 Days</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="button" class="btn btn-primary w-100">
                                <i class="fas fa-search me-1"></i> Search
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Stats Overview -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-4 col-lg-2">
                <div class="stat-card bg-primary" onclick="filterApplications('all')">
                    <h5>Total Applications</h5>
                    <h2 id="totalApps">0</h2>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="stat-card bg-warning" onclick="filterApplications('pending')">
                    <h5>Pending</h5>
                    <h2 id="pendingApps">0</h2>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="stat-card bg-success" onclick="filterApplications('accepted')">
                    <h5>Accepted</h5>
                    <h2 id="hiredApps">0</h2>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="stat-card bg-info" onclick="filterApplications('interviewed')">
                    <h5>Interviewed</h5>
                    <h2 id="interviewedApps">0</h2>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="stat-card bg-secondary" onclick="filterApplications('offered')">
                    <h5>Offered</h5>
                    <h2 id="offeredApps">0</h2>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="stat-card bg-danger" onclick="filterApplications('rejected')">
                    <h5>Rejected</h5>
                    <h2 id="rejectedApps">0</h2>
                </div>
            </div>
        </div>

        <!-- Applications Container -->
        <div id="jobs-container" class="fade-in"></div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
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
                    document.getElementById('interviewedApps').textContent = data.interviewed || 0;
                    document.getElementById('offeredApps').textContent = data.offered || 0;
                    document.getElementById('rejectedApps').textContent = data.rejected || 0;
                })
                .catch(error => {
                    console.error("Failed to load application stats:", error);
                    const placeholders = ['totalApps', 'pendingApps', 'hiredApps', 'interviewedApps', 'offeredApps', 'rejectedApps'];
                    placeholders.forEach(id => document.getElementById(id).textContent = '—');
                });
        }

        // Load job applications
        function loadJobApplications() {
            fetch('../controllers/employer_job_applicants.php')
                .then(response => response.json())
                .then(data => {
                    if (Array.isArray(data)) {
                        renderJobApplications(data);
                    } else {
                        console.error("Unexpected response:", data);
                        showEmptyState();
                    }
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
                        <h3 class="job-title">${job.title}</h3>
                        <span class="badge bg-light text-dark">${job.applicants.length} applicants</span>
                    </div>
                    <div class="p-3">
                        ${renderApplicants(job.applicants)}
                    </div>
                `;
                
                container.appendChild(jobCard);
            });
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
                            <div class="application-card p-3 ${applicant.application_status.toLowerCase()}">
                                <div class="d-flex justify-content-between mb-3">
                                    <div class="d-flex align-items-center">
                                        <img src="../uploads/${applicant.profile_picture || 'default.jpg'}"
                                            onerror="this.onerror=null;this.src='../assets/default-profile.png'"
                                            alt="Profile" class="applicant-avatar me-3">
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
                                            <li><a class="dropdown-item" href="#"><i class="fas fa-eye"></i> View Profile</a></li>
                                            <li><a class="dropdown-item" href="#"><i class="fas fa-file-pdf"></i> View Resume</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item text-success" href="#"><i class="fas fa-check"></i> Accept</a></li>
                                            <li><a class="dropdown-item text-danger" href="#"><i class="fas fa-times"></i> Reject</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item" href="#"><i class="fas fa-envelope"></i> Message</a></li>
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
                                    <div class="progress" style="height: 6px;">
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
                                            <span class="skill-badge">${skill}</span>
                                        `).join('')}
                                        ${applicant.skills && applicant.skills.length > 5 ? `
                                            <span class="skill-badge">+${applicant.skills.length - 5}</span>
                                        ` : ''}
                                    </div>
                                </div>
                            </div>
                        </div>
                    `).join('')}
                </div>
            `;
        }

        // Show empty state
        function showEmptyState() {
            const container = document.getElementById('jobs-container');
            container.innerHTML = `
                <div class="text-center py-5">
                    <i class="fas fa-file-alt fa-4x text-muted mb-4"></i>
                    <h3 class="text-muted mb-3">No applications found</h3>
                    <p class="text-muted mb-4">You currently don't have any job applications matching your criteria</p>
                    <button class="btn btn-primary" onclick="loadJobApplications()">
                        <i class="fas fa-sync-alt me-2"></i> Refresh
                    </button>
                </div>
            `;
        }

        // Filter applications
        function filterApplications(status) {
            // Implement your filtering logic here
            console.log(`Filtering by: ${status}`);
            // You would typically make an API call with the filter parameter
            // For now, we'll just reload all applications
            loadJobApplications();
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
            if (diff < 30) return `${Math.floor(diff/7)} weeks ago`;
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
                case 'accepted': return 'badge-accepted';
                case 'rejected': return 'badge-rejected';
                default: return 'badge-secondary';
            }
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadApplicationStats();
            loadJobApplications();
            
            // Initialize tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>
</html>

<?php include '../includes/stud_footer.php'; ?>