<?php
require '../controllers/employer_dashboard.php';
require '../auth/employer_auth.php';
include '../includes/employer_navbar.php';
// Only show once per session if not 100% complete and not already shown
if ($completion_percentage < 100 && !isset($_SESSION['profile_modal_shown'])) {
    $_SESSION['show_profile_modal'] = true;
    $_SESSION['profile_modal_shown'] = true; // Set a flag that it's been shown
} else {
    $_SESSION['show_profile_modal'] = false;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employer Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">

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
            background-color: #f8f9fa;
        }
        
        .dashboard-container {
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        /* Profile Card */
        .profile-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
            height: 100%;
            border: none;
            overflow: hidden;
            position: relative;
        }
        
        .profile-card:hover {
            box-shadow: var(--card-hover-shadow);
            transform: translateY(-3px);
        }
        
        .profile-img-container {
            position: relative;
            width: 90px;
            height: 90px;
        }
        
        .profile-img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid white;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .profile-status {
            position: absolute;
            bottom: 0;
            right: 0;
            width: 18px;
            height: 18px;
            background-color: var(--success-color);
            border-radius: 50%;
            border: 2px solid white;
        }
        
        .profile-name {
            font-weight: 600;
            color: #2b2d42;
            margin-bottom: 0.25rem;
            font-size: 1.1rem;
        }
        
        .profile-institution {
            color: #6c757d;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .profile-meta {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-top: 15px;
        }
        
        .profile-meta-item {
            display: flex;
            align-items: center;
            font-size: 0.85rem;
            color: #6c757d;
        }
        
        .profile-meta-item i {
            margin-right: 8px;
            color: var(--primary-color);
            width: 20px;
            text-align: center;
        }
        
        /* Dashboard Cards */
        .dashboard-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
            height: 100%;
            border: none;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            height: 100%; 
        }
        
        .dashboard-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--card-hover-shadow);
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            font-weight: 600;
            color: var(--primary-color);
            padding: 1.25rem 1.5rem;
            border-radius: var(--border-radius) var(--border-radius) 0 0 !important;
            display: flex;
            align-items: center;
            font-size: 1rem;
        }
        
        .card-header i {
            margin-right: 10px;
            font-size: 1.2rem;
        }
        
        .card-body {
            padding: 1.5rem;
            flex: 1;
            overflow: auto;
        }
        
        .card-footer {
            flex-shrink: 0;
            background-color: white;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            padding: .25rem 1.5rem;
        }
            
        /* List Items */
        .list-item {
            padding: .8rem;
            border-left: none;
            border-right: none;
            display: flex;
            align-items: center;
            transition: all 0.2s ease;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .list-item:last-child {
            border-bottom: none;
        }
        
        .list-item:hover {
            background-color: #f8f9ff;
        }
        
        .list-item-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            flex-shrink: 0;
            background-color: var(--primary-light);
            color: var(--primary-color);
        }
        
        .list-item-content {
            flex: 1;
            min-width: 0;
        }
        
        .list-item-primary {
            font-weight: 500;
            color: #2b2d42;
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .list-item-secondary {
            font-size: 0.8rem;
            color: #6c757d;
            display: flex;
            align-items: center;
        }
        
        /* Badges */
        .status-badge {
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
        
        .status-accepted {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        /* Skills Tags */
        .skill-tag {
            display: inline-block;
            background-color: var(--primary-light);
            color: var(--primary-color);
            padding: 6px 12px;
            border-radius: 20px;
            margin: 0 8px 8px 0;
            font-size: 0.8rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .skill-tag:hover {
            background-color: var(--primary-color);
            color: white;
            transform: translateY(-1px);
        }
        
        /* Progress Bar */
        .progress-container {
            margin: 20px 0;
        }
        
        .progress-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .progress {
            height: 10px;
            border-radius: 5px;
            background-color: #e9ecef;
        }
        
        .progress-bar {
            background-color: var(--primary-color);
            border-radius: 5px;
        }
        
        /* Buttons */
        .btn-outline-primary {
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        /* View All Links */
        .view-all-link {
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s ease;
            padding: 8px;
            border-radius: 6px;
        }
        
        .view-all-link:hover {
            background-color: var(--primary-light);
        }
        
        .view-all-link i {
            margin-left: 5px;
            transition: transform 0.2s ease;
        }
        
        .view-all-link:hover i {
            transform: translateX(3px);
        }
        
        /* Empty States */
        .empty-state {
            text-align: center;
            padding: 2rem 1rem;
            color: #adb5bd;
        }
        
        .empty-state i {
            font-size: 2.5rem;
            margin-bottom: 15px;
            color: #e9ecef;
        }
        
        .empty-state p {
            margin-bottom: 0;
            font-weight: 500;
        }
        
        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .dashboard-container {
                padding: 1rem;
            }
            
            .profile-img-container {
                width: 70px;
                height: 70px;
            }
            
            .card-header {
                padding: 1rem;
            }
            
            .card-body {
                padding: 1rem;
            }
        }
        
        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .fade-in {
            animation: fadeIn 0.4s ease-out forwards;
        }
        
        /* Custom Scrollbar for cards with overflow */
        .scrollable-card {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .scrollable-card::-webkit-scrollbar {
            width: 6px;
        }
        
        .scrollable-card::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        .scrollable-card::-webkit-scrollbar-thumb {
            background: var(--primary-light);
            border-radius: 10px;
        }
        
        .scrollable-card::-webkit-scrollbar-thumb:hover {
            background: var(--primary-color);
        }
        
        /* Employer-specific styles */
        .applicant-count {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .applicant-label {
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .job-stats {
            display: flex;
            justify-content: space-between;
            margin-top: 1rem;
        }
        
        .job-stat {
            text-align: center;
            padding: 0.5rem;
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
    </style>
</head>
<body>

<div class="container dashboard-container">
    <div class="row g-4">

        <!-- Profile Overview -->
        <div class="col-lg-4">
            <div class="profile-card p-4 fade-in">
                <div class="d-flex align-items-center">
                    <div class="profile-img-container me-3">
                        <img src="../uploads/<?= $profile_picture ?>" alt="Profile Picture" class="profile-img">
                        <div class="profile-status"></div>
                    </div>
                    <div>
                        <h5 class="profile-name"><?= $full_name; ?></h5>
                        <p class="profile-institution"><?= $company_name; ?></p>
                        <?php if (!empty($job_title)): ?>
                            <span class="badge bg-primary mt-1"><?= $job_title ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="profile-meta mt-4">
                    <div class="profile-meta-item mb-2">
                        <i class="bi bi-envelope-fill"></i> <?= $email; ?>
                    </div>
                    <?php if (!empty($contact_number)): ?>
                        <div class="profile-meta-item mb-2">
                            <i class="bi bi-telephone-fill"></i> <?= $contact_number; ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($company_website)): ?>
                        <div class="profile-meta-item mb-2">
                            <i class="bi bi-globe"></i> 
                            <a href="<?= (strpos($company_website, 'http') === 0 ? $company_website : 'https://' . $company_website) ?>" 
                            target="_blank" class="text-decoration-none">
                                <?= parse_url($company_website, PHP_URL_HOST) ?? $company_website ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($company_description)): ?>
                    <div class="company-description mt-3">
                        <h6 class="fw-bold">About Company</h6>
                        <p class="text-muted small"><?= nl2br($company_description) ?></p>
                    </div>
                <?php endif; ?>
                
                <div class="progress-container mt-4">
                    <div class="progress-label d-flex justify-content-between">
                        <span>Profile Completion</span>
                        <span><?= $completion_percentage ?>%</span>
                    </div>
                    
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar <?= $progress_class ?>" 
                            style="width: <?= $completion_percentage ?>%"
                            role="progressbar" 
                            aria-valuenow="<?= $completion_percentage ?>" 
                            aria-valuemin="0" 
                            aria-valuemax="100">
                        </div>
                    </div>
                    
                    <?php if ($completion_percentage < 100): ?>
                        <div class="mt-2">
                            <small class="text-muted">Missing: <?= implode(', ', $missing_fields) ?></small>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="d-grid gap-2 mt-3">
                    <a href="employer_account_settings.php" class="btn btn-outline-primary py-2">
                        <i class="bi bi-pencil-fill me-2"></i> Edit Profile
                    </a>
                    <a href="employer_company_profile.php" class="btn btn-outline-secondary py-2">
                        <i class="bi bi-building me-2"></i> Company Profile
                    </a>
                </div>
            </div>
        </div>

        <!-- Job Postings Overview -->
        <div class="col-md-8">
            <div class="dashboard-card fade-in">
                <div class="card-header">
                    <i class="bi bi-clipboard-check-fill"></i> Job Postings Overview
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 text-center">
                            <div class="applicant-count"><?= $total_jobs ?></div>
                            <div class="applicant-label">Total Jobs Posted</div>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="applicant-count"><?= $active_jobs ?></div>
                            <div class="applicant-label">Active Jobs</div>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="applicant-count"><?= $total_applicants ?></div>
                            <div class="applicant-label">Total Applicants</div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <h6 class="mb-3 fw-semibold text-dark">Recent Job Postings</h6>
                        <div class="list-group list-group-flush scrollable-card" style="max-height: 200px;">
                            <?php if (!empty($recent_jobs)): ?>
                                <?php foreach ($recent_jobs as $job): ?>
                                    <div class="list-group-item list-item">
                                        <div class="d-flex justify-content-between align-items-center w-100">
                                            <div class="d-flex align-items-center">
                                                <div class="list-item-icon">
                                                    <i class="bi bi-briefcase-fill"></i>
                                                </div>
                                                <div>
                                                    <div class="list-item-primary"><?= $job['title'] ?></div>
                                                    <div class="list-item-secondary">
                                                        <span class="me-2"><i class="bi bi-people-fill me-1"></i> <?= $job['applicant_count'] ?> applicants</span>
                                                        <span><i class="bi bi-calendar me-1"></i> Posted <?= getTimeAgo($job['posted_at']) ?></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <span class="status-badge <?= $job['status_class'] ?>">
                                                <?= $job['status'] ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="bi bi-inbox"></i>
                                    <p>No jobs posted yet</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
                <div class="card-footer bg-transparent border-top">
                    <a href="employer_jobs.php" class="view-all-link">
                        View all jobs <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Recent Applicants -->
        <div class="col-md-6">
            <div class="dashboard-card fade-in">
                <div class="card-header">
                    <i class="bi bi-people-fill"></i> Recent Applicants
                </div>
                <div class="card-body p-0 scrollable-card">
                    <div class="list-group list-group-flush">
                        <?php if (!empty($recent_applicants)): ?>
                            <?php foreach ($recent_applicants as $applicant): ?>
                                <a href="employer_applicant_details.php?id=<?= $applicant['application_id'] ?>" class="list-group-item list-item">
                                    <div class="d-flex align-items-center" style="width: 100%;"> 
                                        <div class="profile-img-container me-3" style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; background-color: #e0e0e0; border-radius: 50%;">
                                            <?php if (!empty($applicant['profile_picture'])): ?>
                                                <img src="../uploads/<?= $applicant['profile_picture'] ?>" alt="" class="profile-img" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                                            <?php else: ?>
                                                <i class="fas fa-user" style="font-size: 24px; color: #777;"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex-grow-1"> 
                                            <div class="list-item-primary"><?= $applicant['full_name'] ?></div>
                                            <div class="list-item-secondary">
                                                <span class="me-2"><?= $applicant['job_title'] ?></span>
                                                <span><i class="bi bi-clock me-1"></i> Applied <?= getTimeAgo($applicant['applied_at']) ?></span>
                                            </div>
                                        </div>
                                        <div class="ms-auto"> 
                                            <span class="status-badge <?= $applicant['status_class'] ?>">
                                                <?= $applicant['application_status'] ?>
                                            </span>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="bi bi-person-x"></i>
                                <p>No recent applicants</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-top">
                    <a href="employer_applications.php" class="view-all-link">
                        View all applicants <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Recent Forum Activity -->
        <div class="col-md-6">
            <div class="dashboard-card fade-in">
                <div class="card-header">
                    <i class="bi bi-chat-square-text-fill"></i> Recent Forum Activity
                </div>
                <div class="card-body p-0 scrollable-card">
                    <div class="list-group list-group-flush">
                        <?php if (!empty($recent_forum_activity)): ?>
                            <?php foreach ($recent_forum_activity as $post): ?>
                                <a href="#" class="list-group-item list-item">
                                    <div class="d-flex align-items-center">
                                        <div class="list-item-icon">
                                            <i class="bi bi-<?php 
                                                echo $post['type'] === 'question' ? 'question-circle-fill' : 
                                                    ($post['type'] === 'idea' ? 'lightbulb-fill' : 'chat-square-text-fill'); 
                                            ?>"></i>
                                        </div>
                                        <div class="list-item-content">
                                            <div class="list-item-primary"><?php echo htmlspecialchars($post['title']); ?></div>
                                            <div class="list-item-secondary">
                                                <span class="me-2"><i class="bi bi-clock me-1"></i> <?php echo $post['time_ago']; ?></span>
                                                <span><i class="bi bi-chat-left-text me-1"></i> <?php echo $post['comment_count']; ?> comments</span>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="bi bi-chat-square"></i>
                                <p>No recent activity</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-top">
                    <a href="forums.php" class="view-all-link">
                        Visit forum <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Profile Completion Modal -->
<div class="modal fade" id="profileCompletionModal" tabindex="-1" aria-labelledby="profileModalLabel" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 rounded-4 p-4 shadow-lg" style="background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);">
      
      <!-- Header -->
      <div class="modal-header border-0 pb-0">
        <div class="d-flex align-items-center w-100">
          <div class="me-3">
            <div class="bg-primary bg-opacity-10 text-primary rounded-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
              <i class="bi bi-person-fill-check fs-4"></i>
            </div>
          </div>
          <div>
            <h5 class="modal-title fw-bold text-dark mb-1" id="profileModalLabel">Complete Your Profile</h5>
            <p class="mb-0 text-muted small">Unlock all features by completing your profile</p>
          </div>
        </div>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal" aria-label="Close" tabindex="0"></button>
      </div>
      
      <!-- Body -->
      <div class="modal-body text-center pt-0">
        <div class="position-relative my-4 mx-auto" style="width: 150px; height: 150px;">
          <svg class="circular-progress" viewBox="0 0 36 36" style="width: 100%; height: 100%;">
            <path class="circle-bg"
              d="M18 2.0845
                a 15.9155 15.9155 0 0 1 0 31.831
                a 15.9155 15.9155 0 0 1 0 -31.831"
              fill="none"
              stroke="#e9ecef"
              stroke-width="3"
            />
            <path class="circle-fill"
              d="M18 2.0845
                a 15.9155 15.9155 0 0 1 0 31.831
                a 15.9155 15.9155 0 0 1 0 -31.831"
              fill="none"
              stroke="var(--primary-color)"
              stroke-width="3"
              stroke-dasharray="<?php echo $completion_percentage; ?>, 100"
              stroke-linecap="round"
            />
            <text x="18" y="20.5" class="percentage" fill="var(--primary-color)" font-size="10" text-anchor="middle" dy=".3em" font-weight="bold"><?php echo $completion_percentage; ?>%</text>
          </svg>
        </div>

        <?php if (!empty($missing_fields)): ?>
            <div class="text-start mb-4 bg-white p-3 rounded-3">
                <p class="mb-2 fw-semibold text-dark">Missing information:</p>
                <ul class="list-unstyled mb-0">
                    <?php foreach ($missing_fields as $field): ?>
                        <li class="d-flex align-items-center mb-2">
                            <i class="bi bi-x-circle-fill text-danger me-2"></i>
                            <span><?php echo $field; ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- CTA -->
        <div class="d-grid gap-2">
          <a href="employer_account_settings.php" class="btn btn-primary rounded-pill py-2 fw-semibold">
            <i class="bi bi-pencil-fill me-2"></i> Complete Profile Now
          </a>
          <button type="button" class="btn btn-link text-muted" data-bs-dismiss="modal">Maybe Later</button>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include '../includes/stud_footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Simple animation trigger
    document.addEventListener('DOMContentLoaded', function() {
        const cards = document.querySelectorAll('.fade-in');
        cards.forEach((card, index) => {
            card.style.animationDelay = `${index * 0.1}s`;
        });
        
        // Add hover effects dynamically
        document.querySelectorAll('.dashboard-card').forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.style.transform = 'translateY(-5px)';
            });
            card.addEventListener('mouseleave', () => {
                card.style.transform = 'translateY(0)';
            });
        });
    });
</script>

<?php if (!empty($_SESSION['show_profile_modal']) && $_SESSION['show_profile_modal']): ?>
<script>
document.addEventListener("DOMContentLoaded", function () {
    var profileModal = new bootstrap.Modal(document.getElementById('profileCompletionModal'), {
        keyboard: false,
        focus: true
    });
    
    // Store the last focused element before opening modal
    let lastFocusedElement;
    
    // Show modal
    profileModal.show();
    
    // When modal is shown, store the last focused element
    document.getElementById('profileCompletionModal').addEventListener('shown.bs.modal', function () {
        lastFocusedElement = document.activeElement;
    });
    
    // When modal is hidden, restore focus and update session
    document.getElementById('profileCompletionModal').addEventListener('hidden.bs.modal', function () {
        if (lastFocusedElement) {
            lastFocusedElement.focus();
        }
        
        // Update session flag
        fetch('../controllers/update_profile_modal_flag.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                console.error('Failed to update modal flag');
            }
        })
        .catch(error => console.error('Error:', error));
    });
});
</script>
<?php endif; ?>

</body>
</html>