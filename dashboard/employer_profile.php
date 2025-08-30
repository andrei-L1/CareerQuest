<?php
require_once '../auth/auth_check_employer.php';
require_once '../config/dbcon.php';
include '../includes/employer_navbar.php';

$csrf_token = bin2hex(random_bytes(32)); // Placeholder; implement your actual CSRF token logic

function formatSalary($min_salary, $max_salary, $salary_type, $salary_disclosure) {
    if ($salary_disclosure && $min_salary && $max_salary) {
        return '₱' . number_format($min_salary) . ' - ₱' . number_format($max_salary) . ' per ' . strtolower($salary_type);
    } elseif ($salary_disclosure && $min_salary) {
        return '₱' . number_format($min_salary) . ' per ' . strtolower($salary_type);
    } else {
        return $salary_type === 'Negotiable' ? 'Negotiable' : 'Salary ' . strtolower($salary_type ?: 'Not Specified');
    }
}

// Fetch additional employer data for the profile
try {
    $user_id = $_SESSION['user_id'];
    $employer_id = $_SESSION['employer_id'];
    
    // Fetch comprehensive employer data
    $stmt = $conn->prepare("
        SELECT 
            u.user_first_name, u.user_middle_name, u.user_last_name, 
            u.user_email, u.picture_file, u.status as user_status,
            e.employer_id, e.company_name, e.job_title, e.company_logo,
            e.company_website, e.contact_number, e.company_description, e.status as employer_status
        FROM user u
        JOIN employer e ON u.user_id = e.user_id
        WHERE u.user_id = :user_id AND u.deleted_at IS NULL
    ");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $employer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$employer) {
        throw new Exception("Employer not found.");
    }
    
    // Fetch statistics
    // Total jobs posted
    $jobs_stmt = $conn->prepare("
        SELECT COUNT(*) AS total_jobs,
               SUM(CASE WHEN moderation_status = 'Approved' AND (expires_at IS NULL OR expires_at > NOW()) THEN 1 ELSE 0 END) AS active_jobs
        FROM job_posting 
        WHERE employer_id = :employer_id AND deleted_at IS NULL
    ");
    $jobs_stmt->bindParam(':employer_id', $employer_id, PDO::PARAM_INT);
    $jobs_stmt->execute();
    $job_stats = $jobs_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Total applications received
    $apps_stmt = $conn->prepare("
        SELECT COUNT(*) AS total_applications,
               SUM(CASE WHEN application_status = 'Pending' OR application_status = 'Under Review' THEN 1 ELSE 0 END) AS pending_applications
        FROM application_tracking at
        JOIN job_posting jp ON at.job_id = jp.job_id
        WHERE jp.employer_id = :employer_id AND at.deleted_at IS NULL AND jp.deleted_at IS NULL
    ");
    $apps_stmt->bindParam(':employer_id', $employer_id, PDO::PARAM_INT);
    $apps_stmt->execute();
    $app_stats = $apps_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Total interviews scheduled
    $interviews_stmt = $conn->prepare("
        SELECT COUNT(*) AS total_interviews,
               SUM(CASE WHEN status = 'Scheduled' AND interview_date > NOW() THEN 1 ELSE 0 END) AS upcoming_interviews
        FROM interviews i
        JOIN application_tracking at ON i.application_id = at.application_id
        JOIN job_posting jp ON at.job_id = jp.job_id
        WHERE jp.employer_id = :employer_id AND jp.deleted_at IS NULL
    ");
    $interviews_stmt->bindParam(':employer_id', $employer_id, PDO::PARAM_INT);
    $interviews_stmt->execute();
    $interview_stats = $interviews_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Fetch recent applications
    $recent_apps_stmt = $conn->prepare("
        SELECT at.application_id, at.application_status as status, at.applied_at as applied_date, 
               jp.title as job_title, s.stud_first_name, s.stud_last_name
        FROM application_tracking at
        JOIN job_posting jp ON at.job_id = jp.job_id
        JOIN student s ON at.stud_id = s.stud_id
        WHERE jp.employer_id = :employer_id AND at.deleted_at IS NULL AND jp.deleted_at IS NULL
        ORDER BY at.applied_at DESC
        LIMIT 5
    ");
    $recent_apps_stmt->bindParam(':employer_id', $employer_id, PDO::PARAM_INT);
    $recent_apps_stmt->execute();
    $applications = $recent_apps_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch active job postings
    $jobs_stmt = $conn->prepare("
        SELECT jp.job_id, jp.title, jp.location, jp.min_salary, jp.max_salary, jp.salary_type, jp.salary_disclosure, 
               jp.posted_at, jp.moderation_status, jp.flagged, jp.expires_at
        FROM job_posting jp
        WHERE jp.employer_id = :employer_id AND jp.deleted_at IS NULL
        ORDER BY jp.posted_at DESC
        LIMIT 5
    ");
    $jobs_stmt->bindParam(':employer_id', $employer_id, PDO::PARAM_INT);
    $jobs_stmt->execute();
    $jobs = $jobs_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Employer Profile Error: " . $e->getMessage());
    // Set default values if there's an error
    $job_stats = ['total_jobs' => 0, 'active_jobs' => 0];
    $app_stats = ['total_applications' => 0, 'pending_applications' => 0];
    $interview_stats = ['total_interviews' => 0, 'upcoming_interviews' => 0];
    $applications = [];
    $jobs = [];
}

// Set profile picture - prioritize user's profile picture over company logo
$profile_picture = !empty($employer['picture_file']) ? '../Uploads/' . $employer['picture_file'] : 
                  (!empty($employer['company_logo']) ? '../Uploads/' . $employer['company_logo'] : '../Uploads/default.png');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($employer['company_name'] ?? 'Employer'); ?> | Employer Profile</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        :root {
            --primary: #4361ee;
            --primary-light: #e0e7ff;
            --primary-dark: #3a0ca3;
            --secondary: #6c757d;
            --success: #28a745;
            --info: #17a2b8;
            --warning: #ffc107;
            --danger: #dc3545;
            --light: #f8f9fa;
            --dark: #343a40;
            --white: #ffffff;
            --gray-100: #f8f9fa;
            --gray-200: #e9ecef;
            --gray-300: #dee2e6;
            --gray-400: #ced4da;
            --gray-500: #adb5bd;
            --gray-600: #6c757d;
            --gray-700: #495057;
            --gray-800: #343a40;
            --gray-900: #212529;
            --border-radius: 0.375rem;
            --border-radius-lg: 0.5rem;
            --box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            --box-shadow-sm: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            --transition: all 0.3s ease;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f5f7fb;
            color: var(--gray-800);
            line-height: 1.6;
        }

        .profile-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: var(--white);
            border-radius: var(--border-radius-lg);
            padding: 2.5rem;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
            box-shadow: var(--box-shadow);
        }

        .profile-header::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .profile-header::after {
            content: '';
            position: absolute;
            bottom: -80px;
            left: -80px;
            width: 250px;
            height: 250px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
        }

        .profile-picture {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            z-index: 1;
            position: relative;
            transition: var(--transition);
        }

        .profile-picture:hover {
            transform: scale(1.05);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }

        .profile-name {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            line-height: 1.2;
        }

        .profile-title {
            font-size: 1.1rem;
            margin-bottom: 1.25rem;
            opacity: 0.9;
            font-weight: 500;
        }

        .profile-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .profile-meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
        }

        .profile-meta-item i {
            font-size: 1rem;
            opacity: 0.8;
        }

        .card {
            border: none;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--box-shadow-sm);
            margin-bottom: 1.5rem;
            transition: var(--transition);
            background-color: var(--white);
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: var(--box-shadow);
        }

        .card-header {
            background-color: var(--white);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            font-size: 1.1rem;
            font-weight: 600;
            padding: 1rem 1.5rem;
            color: var(--dark);
        }

        .card-body {
            padding: 1.5rem;
        }

        .info-item {
            margin-bottom: 1.25rem;
        }

        .info-label {
            color: var(--gray-600);
            font-size: 0.85rem;
            margin-bottom: 0.25rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-value {
            font-size: 1rem;
            color: var(--gray-800);
        }

        .btn-primary {
            background-color: var(--primary);
            border: none;
            border-radius: var(--border-radius);
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            transition: var(--transition);
            box-shadow: 0 4px 6px -1px rgba(67, 97, 238, 0.3);
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(67, 97, 238, 0.3);
        }

        .btn-outline-primary {
            border-radius: var(--border-radius);
            font-weight: 500;
            padding: 0.75rem 1.5rem;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1.25rem;
            color: var(--dark);
            position: relative;
            padding-bottom: 0.75rem;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background-color: var(--primary);
            border-radius: 3px;
        }

        .application-card {
            border-left: 4px solid var(--primary);
            transition: var(--transition);
            margin-bottom: 1rem;
            border-radius: var(--border-radius);
            background-color: var(--white);
            padding: 1rem;
        }

        .application-card:hover {
            transform: translateX(5px);
            box-shadow: var(--box-shadow-sm);
        }

        .badge {
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .bg-pending {
            background-color: var(--warning);
            color: var(--dark);
        }

        .bg-approved {
            background-color: var(--success);
            color: var(--white);
        }

        .bg-rejected {
            background-color: var(--danger);
            color: var(--white);
        }

        .bg-under-review {
            background-color: var(--info);
            color: var(--white);
        }

        .bg-interview-scheduled {
            background-color: var(--primary);
            color: var(--white);
        }

        .bg-offered {
            background-color: var(--success);
            color: var(--white);
        }

        .bg-withdrawn {
            background-color: var(--secondary);
            color: var(--white);
        }

        .action-buttons {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            margin-top: 1rem;
        }

        .floating-alert {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            box-shadow: var(--box-shadow);
            border-radius: var(--border-radius);
            border-left: 4px solid;
            animation: slideInRight 0.3s forwards, fadeOut 0.5s 4.5s forwards;
        }

        @keyframes slideInRight {
            from { transform: translateX(100%); }
            to { transform: translateX(0); }
        }

        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }

        .avatar-edit {
            position: absolute;
            right: 10px;
            bottom: 10px;
            z-index: 2;
        }

        .avatar-edit input {
            display: none;
        }

        .avatar-edit label {
            display: inline-block;
            width: 40px;
            height: 40px;
            margin-bottom: 0;
            border-radius: 50%;
            background: var(--white);
            border: 1px solid var(--gray-300);
            box-shadow: var(--box-shadow-sm);
            cursor: pointer;
            text-align: center;
            line-height: 40px;
            color: var(--primary);
            transition: var(--transition);
        }

        .avatar-edit label:hover {
            background-color: var(--primary);
            color: var(--white);
        }

        .social-links {
            display: flex;
            gap: 0.75rem;
            margin-top: 1rem;
        }

        .social-link {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.2);
            color: var(--white);
            transition: var(--transition);
        }

        .social-link:hover {
            background-color: rgba(255, 255, 255, 0.3);
            color: var(--white);
            transform: translateY(-2px);
        }

        .job-card {
            border-radius: var(--border-radius-lg);
            overflow: hidden;
            transition: var(--transition);
            margin-bottom: 1.5rem;
            border: 1px solid var(--gray-200);
        }

        .job-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--box-shadow);
        }

        .job-status-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .job-flagged {
            position: absolute;
            top: 15px;
            left: 15px;
            color: var(--danger);
        }

        .stat-card {
            text-align: center;
            padding: 1.5rem;
            border-radius: var(--border-radius-lg);
            background-color: var(--white);
            box-shadow: var(--box-shadow-sm);
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--box-shadow);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 0.9rem;
            color: var(--gray-600);
        }

        .text-primary {
            color: var(--primary) !important;
        }

        .text-success {
            color: var(--success) !important;
        }

        .text-warning {
            color: var(--warning) !important;
        }

        .text-danger {
            color: var(--danger) !important;
        }

        .bg-primary-light {
            background-color: var(--primary-light);
        }

        @media (max-width: 768px) {
            .profile-header {
                padding: 1.5rem;
            }
            
            .profile-name {
                font-size: 1.5rem;
            }
            
            .profile-title {
                font-size: 1rem;
            }
            
            .profile-meta {
                gap: 1rem;
                flex-direction: column;
            }
            
            .profile-picture {
                width: 120px;
                height: 120px;
                margin-bottom: 1rem;
            }
            
            .action-buttons {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .btn {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="row align-items-center">
                <div class="col-md-auto text-center text-md-start position-relative">
                    <img src="<?php echo $profile_picture; ?>" class="profile-picture mb-3 mb-md-0" alt="Profile Picture">
                    <div class="avatar-edit">
                        <input type="file" id="profilePictureInput" name="profile_picture" accept="image/*">
                        <label for="profilePictureInput" title="Change photo">
                            <i class="fas fa-camera"></i>
                        </label>
                    </div>
                </div>
                <div class="col-md">
                    <h1 class="profile-name">
                        <?php echo htmlspecialchars($employer['user_first_name'] ?? 'Employer') . ' ' . htmlspecialchars($employer['user_last_name'] ?? ''); ?>
                    </h1>
                    <div class="profile-title">
                        <?php if (!empty($employer['job_title'])): ?>
                            <?php echo htmlspecialchars($employer['job_title']); ?>
                            <?php if (!empty($employer['company_name'])): ?>
                                · <?php echo htmlspecialchars($employer['company_name']); ?>
                            <?php endif; ?>
                        <?php else: ?>
                            Employer
                            <?php if (!empty($employer['company_name'])): ?>
                                · <?php echo htmlspecialchars($employer['company_name']); ?>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <div class="profile-meta">
                        <?php if (!empty($employer['user_email'])): ?>
                        <div class="profile-meta-item">
                            <i class="fas fa-envelope"></i>
                            <?php echo htmlspecialchars($employer['user_email']); ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($employer['contact_number'])): ?>
                        <div class="profile-meta-item">
                            <i class="fas fa-phone"></i>
                            <?php echo htmlspecialchars($employer['contact_number']); ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($employer['company_website'])): ?>
                        <div class="profile-meta-item">
                            <i class="fas fa-globe"></i>
                            <?php echo htmlspecialchars($employer['company_website']); ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($employer['user_status'])): ?>
                        <div class="profile-meta-item">
                            <i class="fas fa-badge-check"></i>
                            <?php echo htmlspecialchars($employer['user_status']); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="action-buttons">
                        <a href="../dashboard/employer_post_job.php" class="btn btn-light btn-sm">
                            <i class="fas fa-plus me-1"></i> Post New Job
                        </a>
                        
                        <a href="employer_account_settings.php" class="btn btn-light btn-sm">
                            <i class="fas fa-cog me-1"></i> Account Settings
                        </a>
                        
                        <a href="../dashboard/employer_jobs.php" class="btn btn-light btn-sm">
                            <i class="fas fa-briefcase me-1"></i> Manage Jobs
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Left Column - Profile Details -->
            <div class="col-lg-5">
                <!-- About Section -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-user-circle me-2"></i> About
                    </div>
                    <div class="card-body">
                        <?php if (!empty($employer['company_name'])): ?>
                        <div class="info-item">
                            <div class="info-label">Company Name</div>
                            <div class="info-value">
                                <?php echo htmlspecialchars($employer['company_name']); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($employer['job_title'])): ?>
                        <div class="info-item">
                            <div class="info-label">Your Job Title</div>
                            <div class="info-value">
                                <?php echo htmlspecialchars($employer['job_title']); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($employer['company_description'])): ?>
                        <div class="info-item">
                            <div class="info-label">Company Description</div>
                            <div class="info-value">
                                <?php echo htmlspecialchars($employer['company_description']); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Company Stats Section -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-chart-bar me-2"></i> Company Statistics
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="stat-card">
                                    <div class="stat-value text-primary"><?php echo $job_stats['total_jobs'] ?? 0; ?></div>
                                    <div class="stat-label">Total Jobs Posted</div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="stat-card">
                                    <div class="stat-value text-success"><?php echo $app_stats['total_applications'] ?? 0; ?></div>
                                    <div class="stat-label">Applications Received</div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="stat-card">
                                    <div class="stat-value text-warning"><?php echo $interview_stats['total_interviews'] ?? 0; ?></div>
                                    <div class="stat-label">Interviews Scheduled</div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="stat-card">
                                    <div class="stat-value text-info"><?php echo $job_stats['active_jobs'] ?? 0; ?></div>
                                    <div class="stat-label">Active Jobs</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-bolt me-2"></i> Quick Actions
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="../dashboard/employer_post_job.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus me-1"></i> Post New Job
                            </a>
                            <a href="employer_account_settings.php" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-cog me-1"></i> Account Settings
                            </a>
                            <a href="../dashboard/employer_jobs.php" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-briefcase me-1"></i> Manage Jobs
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Right Column - Professional Content -->
            <div class="col-lg-7">
                <!-- Quick Stats -->
                <div class="row mb-4">
                    <div class="col-md-4 mb-3">
                        <div class="stat-card bg-primary-light">
                            <div class="stat-value text-primary"><?php echo $job_stats['active_jobs'] ?? 0; ?></div>
                            <div class="stat-label">Active Jobs</div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="stat-card bg-primary-light">
                            <div class="stat-value text-success"><?php echo $app_stats['pending_applications'] ?? 0; ?></div>
                            <div class="stat-label">Pending Applications</div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="stat-card bg-primary-light">
                            <div class="stat-value text-warning"><?php echo $interview_stats['upcoming_interviews'] ?? 0; ?></div>
                            <div class="stat-label">Upcoming Interviews</div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Applications -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-briefcase me-2"></i> Recent Applications
                        </div>
                        <a href="../dashboard/employer_applications.php" class="btn btn-sm btn-primary">View All</a>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($applications)): ?>
                            <?php foreach($applications as $app): ?>
                            <div class="application-card">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="mb-0"><?php echo htmlspecialchars($app['job_title']); ?></h6>
                                    <span class="badge bg-<?php echo strtolower(str_replace(' ', '-', $app['status'])); ?>">
                                        <?php echo htmlspecialchars($app['status']); ?>
                                    </span>
                                </div>
                                <div class="text-muted small mb-2"><?php echo htmlspecialchars($app['stud_first_name'] . ' ' . $app['stud_last_name']); ?></div>
                                <div class="text-muted small">Applied on <?php echo date('M j, Y', strtotime($app['applied_date'])); ?></div>
                                
                                <div class="action-buttons mt-2">
                                    <!-- 
                                    <a href="employer_applications.php?application_id=<?= htmlspecialchars($app['application_id']) ?>&csrf_token=<?= htmlspecialchars($csrf_token) ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye me-1"></i> View
                                    </a>
                                     -->
                                    <?php if ($app['status'] === 'Pending' || $app['status'] === 'Under Review'): ?>
                                        <!-- 
                                        <a href="employer_schedule_interview.php?application_id=<?= htmlspecialchars($app['application_id']) ?>&csrf_token=<?= htmlspecialchars($csrf_token) ?>" class="btn btn-sm btn-outline-success">
                                            <i class="fas fa-calendar-alt me-1"></i> Schedule Interview
                                        </a>
                                        -->
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-briefcase fa-3x text-muted mb-3"></i>
                                <h5>No Applications Yet</h5>
                                <p class="text-muted">Applications to your job postings will appear here</p>
                                <a href="../dashboard/employer_post_job.php" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus me-1"></i> Post a Job
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Active Job Postings -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-list-alt me-2"></i> Active Job Postings
                        </div>
                        <a href="../dashboard/employer_jobs.php" class="btn btn-sm btn-primary">View All</a>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($jobs)): ?>
                            <?php foreach($jobs as $job): ?>
                            <div class="job-card card mb-3 position-relative">
                                <?php if ($job['flagged']): ?>
                                    <span class="job-flagged">
                                        <i class="fas fa-flag" title="This job has been flagged"></i>
                                    </span>
                                <?php endif; ?>
                                
                                <span class="badge job-status-badge bg-<?php echo strtolower($job['moderation_status']); ?>">
                                    <?php echo htmlspecialchars($job['moderation_status']); ?>
                                </span>
                                
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($job['title']); ?></h5>
                                    <?php if (!empty($job['location'])): ?>
                                    <p class="card-text mb-1">
                                        <i class="fas fa-map-marker-alt text-muted me-1"></i>
                                        <?php echo htmlspecialchars($job['location']); ?>
                                    </p>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($job['min_salary']) || !empty($job['max_salary']) || !empty($job['salary_type'])): ?>
                                    <p class="card-text mb-1">
                                        <i class="fas fa-money-bill-wave text-muted me-1"></i>
                                        <?= htmlspecialchars(formatSalary(
                                            $job['min_salary'] ?? null,
                                            $job['max_salary'] ?? null,
                                            $job['salary_type'] ?? null,
                                            $job['salary_disclosure'] ?? false
                                        )) ?>
                                    </p>
                                    <?php endif; ?>
                                    
                                    <p class="card-text text-muted small mt-2">
                                        Posted <?php echo date('M j, Y', strtotime($job['posted_at'])); ?>
                                        <?php if (!empty($job['expires_at'])): ?>
                                            · Expires <?php echo date('M j, Y', strtotime($job['expires_at'])); ?>
                                        <?php endif; ?>
                                    </p>
                                    
                                    <div class="action-buttons mt-3">
                                        <!--
                                        <a href="employer_jobs.php?job_id=<?= htmlspecialchars($job['job_id']) ?>&csrf_token=<?= htmlspecialchars($csrf_token) ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye me-1"></i> View
                                        </a>
                                        <a href="employer_post_job.php?edit=<?= htmlspecialchars($job['job_id']) ?>&csrf_token=<?= htmlspecialchars($csrf_token) ?>" class="btn btn-sm btn-outline-secondary">
                                            <i class="fas fa-edit me-1"></i> Edit
                                        </a>
                                        -->
                                        <?php if ($job['moderation_status'] === 'Approved'): ?>
                                            <!--
                                            <a href="employer_applications.php?job_id=<?= htmlspecialchars($job['job_id']) ?>&csrf_token=<?= htmlspecialchars($csrf_token) ?>" class="btn btn-sm btn-outline-success">
                                                <i class="fas fa-users me-1"></i> View Applicants
                                            </a>
                                            -->
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-list-alt fa-3x text-muted mb-3"></i>
                                <h5>No Active Job Postings</h5>
                                <p class="text-muted">You haven't posted any jobs yet</p>
                                <a href="../dashboard/employer_post_job.php" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus me-1"></i> Post a Job
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/stud_footer.php'; ?>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        // Profile picture upload functionality
        document.addEventListener('DOMContentLoaded', function() {
            const profilePictureInput = document.getElementById('profilePictureInput');
            const profilePicture = document.querySelector('.profile-picture');
            const csrfToken = '<?= htmlspecialchars($csrf_token) ?>';
            
            if (profilePictureInput) {
                profilePictureInput.addEventListener('change', function(e) {
                    if (this.files && this.files[0]) {
                        const file = this.files[0];
                        
                        // Validate file type
                        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                        if (!allowedTypes.includes(file.type)) {
                            alert('Please select a valid image file (JPG, PNG, or GIF)');
                            return;
                        }
                        
                        // Validate file size (2MB limit)
                        if (file.size > 10 * 1024 * 1024) {
                            alert('File size must be less than 10MB');
                            return;
                        }
                        
                        // Create FormData and upload
                        const formData = new FormData();
                        formData.append('profile_picture', file);
                        formData.append('action', 'update_employer_profile');
                        formData.append('csrf_token', csrfToken);
                        
                        // Show loading state
                        profilePicture.style.opacity = '0.7';
                        
                        fetch('../controllers/employer_update_profile.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                // Update the profile picture with the new one
                                const newSrc = 'Uploads/' + data.profile_picture;
                                profilePicture.src = newSrc;
                                
                                // Show success message
                                showAlert('Profile picture updated successfully!', 'success');
                            } else {
                                showAlert('Error: ' + data.message, 'danger');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            showAlert('An error occurred while uploading the image.', 'danger');
                        })
                        .finally(() => {
                            // Reset loading state
                            profilePicture.style.opacity = '1';
                            // Clear the input
                            profilePictureInput.value = '';
                        });
                    }
                });
            }
            
            // Function to show alerts
            function showAlert(message, type) {
                const alertHtml = `
                    <div class="alert alert-${type} alert-dismissible fade show floating-alert" role="alert">
                        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `;
                
                // Remove any existing alerts
                const existingAlert = document.querySelector('.floating-alert');
                if (existingAlert) {
                    existingAlert.remove();
                }
                
                // Add new alert
                document.body.insertAdjacentHTML('afterbegin', alertHtml);
                
                // Auto-dismiss after 5 seconds
                setTimeout(() => {
                    const alert = document.querySelector('.floating-alert');
                    if (alert) {
                        alert.remove();
                    }
                }, 5000);
            }
        });
    </script>
</body>
</html>