<?php
require '../controllers/employer_jobs.php';
require '../auth/employer_auth.php';
include '../includes/employer_navbar.php';
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
            --secondary-color: #3A7BD5;
            --primary-light: #e0e7ff;
            --accent-color: #4cc9f0;
            --success-color: #38b000;
            --warning-color: #ffaa00;
            --danger-color: #ef233c;
            --light-bg: #f8f9fa;
            --card-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --card-hover-shadow: 0 8px 16px rgba(67, 97, 238, 0.15);
            --border-radius: 12px;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }
        
        .dashboard-container {
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .page-title {
            font-weight: 600;
            color: #2b2d42;
            margin-bottom: 0;
        }
        
        .job-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
            border: none;
            overflow: hidden;
        }
        
        .job-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--card-hover-shadow);
        }
        
        .job-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.25rem 1.5rem;
            background-color: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .job-title {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.25rem;
            font-size: 1.1rem;
        }
        
        .job-meta {
            display: flex;
            gap: 15px;
            color: #6c757d;
            font-size: 0.85rem;
        }
        
        .job-meta-item {
            display: flex;
            align-items: center;
        }
        
        .job-meta-item i {
            margin-right: 5px;
        }
        
        .job-status {
            font-size: 0.75rem;
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: 500;
            min-width: 80px;
            text-align: center;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-paused {
            background-color: #f0ad4e; 
            color: #3e2f00;           
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
            color: #495057;
            margin-bottom: 1.5rem;
        }
        
        .job-skills {
            margin-bottom: 1.5rem;
        }
        
        .skill-tag {
            display: inline-block;
            background-color: var(--primary-light);
            color: var(--primary-color);
            padding: 6px 12px;
            border-radius: 20px;
            margin: 0 8px 8px 0;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .job-stats {
            display: flex;
            gap: 15px;
            margin-top: 1rem;
        }
        
        .job-stat {
            text-align: center;
            padding: 0.5rem 1rem;
            background-color: #f8f9fa;
            border-radius: 8px;
            flex: 1;
        }
        
        .job-stat-value {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .job-stat-label {
            font-size: 0.75rem;
            color: #6c757d;
        }
        
        .job-card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            background-color: #f8f9fa;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .action-btn {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-weight: 500;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            transition: all 0.2s ease;
        }
        
        .action-btn i {
            margin-right: 5px;
        }
        
        .btn-edit {
            background-color: var(--primary-light);
            color: var(--primary-color);
            border: 1px solid var(--primary-light);
        }
        
        .btn-edit:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-delete {
            background-color: #f8d7da;
            color: var(--danger-color);
            border: 1px solid #f8d7da;
        }
        
        .btn-delete:hover {
            background-color: var(--danger-color);
            color: white;
        }
        
        .btn-view-applicants {
            background-color: var(--primary-color);
            color: white;
            border: 1px solid var(--primary-color);
        }
        
        .btn-view-applicants:hover {
            background-color: #0d3b7a;
            border-color: #0d3b7a;
        }
        
        .btn-duplicate {
            background-color: #e2e3e5;
            color: #383d41;
            border: 1px solid #e2e3e5;
        }
        
        .btn-duplicate:hover {
            background-color: #383d41;
            color: white;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #adb5bd;
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
        }
        
        .empty-state i {
            font-size: 1.5rem;
            margin-bottom: 15px;
            color: #e9ecef;
        }
        
        .empty-state p {
            margin-bottom: 1.5rem;
            font-weight: 500;
        }
        
        .badge-custom {
            font-size: 0.75rem;
            font-weight: 500;
            padding: 5px 10px;
            border-radius: 20px;
        }
        
        .table-responsive {
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--card-shadow);
        }
        
        .table thead {
            background-color: var(--primary-color);
            color: white;
        }
        
        .table th {
            font-weight: 500;
            padding: 1rem;
        }
        
        .table td {
            vertical-align: middle;
            padding: 1rem;
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(26, 77, 143, 0.05);
        }
        
        .action-dropdown .dropdown-menu {
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            border: none;
            padding: 0.5rem;
        }
        
        .action-dropdown .dropdown-item {
            border-radius: 6px;
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
        }
        
        .action-dropdown .dropdown-item i {
            margin-right: 8px;
            width: 18px;
            text-align: center;
        }
        
        @media (max-width: 768px) {
            .dashboard-container {
                padding: 1rem;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .job-meta {
                flex-wrap: wrap;
                gap: 8px;
            }
            
            .job-card-footer {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
        }

    </style>

<style>
    #filterToggleBtn {
        border-radius: 8px;
        padding: 8px 16px;
        font-weight: 500;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    #filterToggleBtn:hover {
        background-color: var(--primary-light);
        border-color: var(--primary-color);
    }

    #filterToggleBtn i {
        transition: transform 0.2s ease;
    }

    #filterToggleBtn[aria-expanded="true"] .bi-chevron-down {
        transform: rotate(180deg);
    }

    #filterSection {
        transition: all 0.3s ease;
    }

    .filter-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    @media (max-width: 576px) {
        .filter-actions {
            flex-direction: column;
        }
        
        .filter-actions .btn {
            width: 100%;
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
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <h3 class="text-primary"><?= $total_jobs ?></h3>
                    <p class="text-muted mb-0">Total Jobs</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <h3 class="text-success"><?= $active_jobs ?></h3>
                    <p class="text-muted mb-0">Active Jobs</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <h3 class="text-warning"><?= $pending_jobs ?></h3>
                    <p class="text-muted mb-0">Pending Approval</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <h3 class="text-danger"><?= $expired_jobs ?></h3>
                    <p class="text-muted mb-0">Expired Jobs</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Jobs Table View -->
    <div class="card mb-4">
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
                                    <a href="employer_view_applicants.php?job_id=<?= $job['job_id'] ?>" 
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
                                                <span class="badge-custom bg-success">+<?= $job['new_applicants'] ?> new</span>
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
                                                    <li>
                                                        <a class="dropdown-item" href="employer_view_job.php?id=<?= $job['job_id'] ?>">
                                                            <i class="bi bi-eye"></i> View
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="employer_edit_job.php?id=<?= $job['job_id'] ?>">
                                                            <i class="bi bi-pencil"></i> Edit
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="employer_view_applicants.php?job_id=<?= $job['job_id'] ?>">
                                                            <i class="bi bi-people"></i> Applicants
                                                        </a>
                                                    </li>
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
$(document).ready(function () {
    $('#yourTableID').DataTable(); // Example
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
            // Initialize DataTable if not already initialized
            if (!$.fn.DataTable.isDataTable('#jobsTable')) {
                $('#jobsTable').DataTable({
                    responsive: true,
                    order: [[6, 'desc']] // Sort by posted date by default
                });
            }
        } else {
            cardView.style.display = 'block';
            tableView.style.display = 'none';
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
</body>
</html>