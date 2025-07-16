<?php
require_once '../auth/auth_check_employer.php';
include '../includes/employer_navbar.php';

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
        SELECT jp.job_id, jp.title, jp.location, jp.salary, jp.posted_at, 
               jp.moderation_status, jp.flagged, jp.expires_at
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
$profile_picture = !empty($employer['picture_file']) ? '../uploads/' . $employer['picture_file'] : 
                  (!empty($employer['company_logo']) ? '../uploads/' . $employer['company_logo'] : '/skillmatch-main/skillmatch-main/uploads/default.png');
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
    <!-- Animate.css for animations -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #4361ee;
            --primary-light: #e0e7ff;
            --secondary-color: #f8f9fa;
            --dark-color: #1e293b;
            --text-color: #334155;
            --light-text: #64748b;
            --accent-color: #3a0ca3;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --border-radius: 12px;
            --box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        body {
            background-color: var(--secondary-color);
            color: var(--text-color);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
          
        }
        
        .profile-header {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: white;
            border-radius: var(--border-radius);
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
        
        .card-profile {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 1.5rem;
            transition: var(--transition);
            background-color: white;
            overflow: hidden;
        }
        
        .card-profile:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        
        .card-header-profile {
            background-color: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            font-size: 1.1rem;
            font-weight: 600;
            padding: 1rem 1.5rem;
            color: var(--dark-color);
        }
        
        .card-body-profile {
            padding: 1.5rem;
        }
        
        .info-item {
            margin-bottom: 1.25rem;
        }
        
        .info-label {
            color: var(--light-text);
            font-size: 0.85rem;
            margin-bottom: 0.25rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .info-value {
            font-size: 1rem;
            color: var(--text-color);
        }
        
        .btn-primary-profile {
            background-color: var(--primary-color);
            border: none;
            border-radius: 8px;
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            transition: var(--transition);
            box-shadow: 0 4px 6px -1px rgba(67, 97, 238, 0.3);
        }
        
        .btn-primary-profile:hover {
            background-color: var(--accent-color);
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(67, 97, 238, 0.3);
        }
        
        .btn-outline-primary {
            border-radius: 8px;
            font-weight: 500;
            padding: 0.75rem 1.5rem;
        }
        
        .progress {
            height: 8px;
            border-radius: 4px;
            background-color: #e2e8f0;
        }
        
        .progress-bar {
            background-color: var(--primary-color);
            border-radius: 4px;
        }
        
        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1.25rem;
            color: var(--dark-color);
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
            background-color: var(--primary-color);
            border-radius: 3px;
        }
        
        .application-card {
            border-left: 4px solid var(--primary-color);
            transition: var(--transition);
            margin-bottom: 1rem;
            border-radius: 4px;
            background-color: white;
            padding: 1rem;
        }
        
        .application-card:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        .status-badge {
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-pending {
            background-color: var(--warning-color);
            color: white;
        }
        
        .status-accepted {
            background-color: var(--success-color);
            color: white;
        }
        
        .status-rejected {
            background-color: var(--danger-color);
            color: white;
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
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
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
        
        .modal-content {
            border-radius: var(--border-radius);
            border: none;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }
        
        .modal-header {
            border-bottom: none;
            padding: 1.5rem;
        }
        
        .modal-body {
            padding: 1.5rem;
        }
        
        .modal-footer {
            border-top: none;
            padding: 1rem 1.5rem;
        }
        
        .form-control, .form-select {
            border-radius: 8px;
            padding: 0.75rem 1rem;
            border: 1px solid #e2e8f0;
            transition: var(--transition);
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
        }
        
        .file-upload {
            position: relative;
            margin-bottom: 1rem;
        }
        
        .file-upload-label {
            display: block;
            padding: 1.5rem;
            border: 2px dashed #e2e8f0;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
            background-color: #f8fafc;
        }
        
        .file-upload-label:hover {
            border-color: var(--primary-color);
            background-color: rgba(67, 97, 238, 0.05);
        }
        
        .file-upload-label i {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .file-upload-input {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
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
            background: white;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            text-align: center;
            line-height: 40px;
            color: var(--primary-color);
            transition: var(--transition);
        }
        
        .avatar-edit label:hover {
            background-color: var(--primary-color);
            color: white;
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
            background-color: #f1f5f9;
            color: var(--light-text);
            transition: var(--transition);
        }
        
        .social-link:hover {
            background-color: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }
        
        .job-card {
            border-radius: var(--border-radius);
            overflow: hidden;
            transition: var(--transition);
            margin-bottom: 1.5rem;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .job-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
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
        
        .job-status-pending {
            background-color: var(--warning-color);
            color: white;
        }
        
        .job-status-approved {
            background-color: var(--success-color);
            color: white;
        }
        
        .job-status-rejected {
            background-color: var(--danger-color);
            color: white;
        }
        
        .job-status-paused {
            background-color: var(--light-text);
            color: white;
        }
        
        .job-flagged {
            position: absolute;
            top: 15px;
            left: 15px;
            color: var(--danger-color);
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
            
            .btn-primary-profile, .btn-outline-primary {
                width: 100%;
                text-align: center;
            }
        }
        .application-card {
            border-left: 4px solid var(--primary-color);
            transition: var(--transition);
            margin-bottom: 1rem;
            border-radius: 4px;
            background-color: white;
            padding: 1rem;
        }
        
        .application-card:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        .status-badge {
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-pending {
            background-color: var(--warning-color);
            color: white;
        }
        
        .status-accepted {
            background-color: var(--success-color);
            color: white;
        }
        
        .status-rejected {
            background-color: var(--danger-color);
            color: white;
        }
        
        .status-under-review {
            background-color: var(--primary-color);
            color: white;
        }
        
        .status-interview-scheduled {
            background-color: var(--primary-color);
            color: white;
        }
        
        .status-interview {
            background-color: var(--primary-color);
            color: white;
        }
        
        .status-offered {
            background-color: var(--success-color);
            color: white;
        }
        
        .status-accepted {
            background-color: var(--success-color);
            color: white;
        }
        
        .status-rejected {
            background-color: var(--danger-color);
            color: white;
        }
        
        .status-withdrawn {
            background-color: var(--light-text);
            color: white;
        }
        
        .job-status-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .job-status-approved {
            background-color: var(--success-color);
            color: white;
        }
        
        .job-status-pending {
            background-color: var(--warning-color);
            color: white;
        }
        
        .job-status-rejected {
            background-color: var(--danger-color);
            color: white;
        }
        
        .job-flagged {
            position: absolute;
            top: 1rem;
            left: 1rem;
            color: var(--danger-color);
            font-size: 1.2rem;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            margin-top: 1rem;
        }
        
        .social-links {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .social-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
            text-decoration: none;
            transition: var(--transition);
        }
        
        .social-link:hover {
            background-color: rgba(255, 255, 255, 0.3);
            color: white;
            transform: translateY(-2px);
        }
    </style>
</head>
<body >
    
    <div class="container py-4 animate__animated animate__fadeIn">
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
                        <?php if (!empty($employer['user_first_name'])): ?>
                        <div class="profile-meta-item">
                            <i class="fas fa-user"></i>
                            <?php echo htmlspecialchars($employer['user_first_name'] . ' ' . $employer['user_last_name']); ?>
                        </div>
                        <?php endif; ?>
                        
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
                        
                        <?php if (!empty($employer['status'])): ?>
                        <div class="profile-meta-item">
                            <i class="fas fa-badge-check"></i>
                            <?php echo htmlspecialchars($employer['status']); ?>
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
                    
                    <div class="social-links">
                        <?php if (!empty($employer['company_website'])): ?>
                        <a href="<?php echo htmlspecialchars($employer['company_website']); ?>" class="social-link" target="_blank">
                            <i class="fas fa-globe"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Left Column - Profile Details -->
            <div class="col-lg-5">
                <!-- About Section -->
                <div class="card card-profile">
                    <div class="card-header-profile">
                        <i class="fas fa-user-circle me-2"></i> About
                    </div>
                    <div class="card-body-profile">
                        <?php if (!empty($employer['user_first_name'])): ?>
                        <div class="info-item">
                            <div class="info-label">Full Name</div>
                            <div class="info-value">
                                <?php echo htmlspecialchars($employer['user_first_name'] . ' ' . $employer['user_last_name']); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($employer['job_title'])): ?>
                        <div class="info-item">
                            <div class="info-label">Job Title</div>
                            <div class="info-value">
                                <?php echo htmlspecialchars($employer['job_title']); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($employer['contact_number'])): ?>
                        <div class="info-item">
                            <div class="info-label">Phone</div>
                            <div class="info-value">
                                <?php echo htmlspecialchars($employer['contact_number']); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($employer['user_email'])): ?>
                        <div class="info-item">
                            <div class="info-label">Email</div>
                            <div class="info-value">
                                <?php echo htmlspecialchars($employer['user_email']); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($employer['status'])): ?>
                        <div class="info-item">
                            <div class="info-label">Account Status</div>
                            <div class="info-value">
                                <span class="badge bg-<?php echo $employer['status'] === 'Active' ? 'success' : 'warning'; ?>">
                                    <?php echo htmlspecialchars($employer['status']); ?>
                                </span>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Company Stats Section -->
                <div class="card card-profile">
                    <div class="card-header-profile">
                        <i class="fas fa-chart-bar me-2"></i> Company Statistics
                    </div>
                    <div class="card-body-profile">
                        <div class="info-item">
                            <div class="info-value fw-bold text-primary" id="jobs-posted"><?php echo $job_stats['total_jobs'] ?? 0; ?></div>
                            <div class="info-label">Total Jobs Posted</div>
                        </div>
                        <div class="info-item">
                            <div class="info-value fw-bold text-success" id="applications-received"><?php echo $app_stats['total_applications'] ?? 0; ?></div>
                            <div class="info-label">Applications Received</div>
                        </div>
                        <div class="info-item">
                            <div class="info-value fw-bold text-warning" id="interviews-scheduled"><?php echo $interview_stats['total_interviews'] ?? 0; ?></div>
                            <div class="info-label">Interviews Scheduled</div>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="card card-profile">
                    <div class="card-header-profile">
                        <i class="fas fa-bolt me-2"></i> Quick Actions
                    </div>
                    <div class="card-body-profile">
                        <div class="d-grid gap-2">
                            <a href="../dashboard/employer_post_job.php" class="btn btn-primary-profile btn-sm">
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
                    <div class="col-md-4">
                        <div class="card card-profile h-100">
                            <div class="card-body-profile text-center">
                                <div class="info-value fw-bold text-primary" id="active-jobs"><?php echo $job_stats['active_jobs'] ?? 0; ?></div>
                                <div class="info-label">Active Jobs</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-profile h-100">
                            <div class="card-body-profile text-center">
                                <div class="info-value fw-bold text-success" id="pending-applications"><?php echo $app_stats['pending_applications'] ?? 0; ?></div>
                                <div class="info-label">Pending Applications</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-profile h-100">
                            <div class="card-body-profile text-center">
                                <div class="info-value fw-bold text-warning" id="upcoming-interviews"><?php echo $interview_stats['upcoming_interviews'] ?? 0; ?></div>
                                <div class="info-label">Upcoming Interviews</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Applications -->
                <div class="card card-profile">
                    <div class="card-header-profile d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-briefcase me-2"></i> Recent Applications
                        </div>
                        <a href="manage_applications.php" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                    <div class="card-body-profile">
                        <?php if (!empty($applications)): ?>
                            <?php foreach($applications as $app): ?>
                            <div class="application-card">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="mb-0"><?php echo htmlspecialchars($app['job_title']); ?></h6>
                                    <span class="status-badge status-<?php echo htmlspecialchars(strtolower(str_replace(' ', '-', $app['status']))); ?>">
                                        <?php echo htmlspecialchars($app['status']); ?>
                                    </span>
                                </div>
                                <div class="text-muted small mb-2"><?php echo htmlspecialchars($app['stud_first_name'] . ' ' . $app['stud_last_name']); ?></div>
                                <div class="text-muted small">Applied on <?php echo date('M j, Y', strtotime($app['applied_date'])); ?></div>
                                
                                <div class="action-buttons mt-2">
                                    <a href="employer_applications.php?application_id=<?php echo $app['application_id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye me-1"></i> View
                                    </a>
                                    <?php if ($app['status'] === 'Pending' || $app['status'] === 'Under Review'): ?>
                                    <a href="employer_applications.php?application_id=<?php echo $app['application_id']; ?>" class="btn btn-sm btn-outline-success">
                                        <i class="fas fa-calendar-alt me-1"></i> Schedule Interview
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-briefcase fa-3x text-muted mb-3"></i>
                                <h5>No Applications Yet</h5>
                                <p class="text-muted">Applications to your job postings will appear here</p>
                                <a href="../dashboard/employer_post_job.php" class="btn btn-primary-profile btn-sm">
                                    <i class="fas fa-plus me-1"></i> Post a Job
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Active Job Postings -->
                <div class="card card-profile">
                    <div class="card-header-profile d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-list-alt me-2"></i> Active Job Postings
                        </div>
                        <a href="manage_jobs.php" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                    <div class="card-body-profile">
                        <?php if (!empty($jobs)): ?>
                            <?php foreach($jobs as $job): ?>
                            <div class="job-card card mb-3 position-relative">
                                <?php if ($job['flagged']): ?>
                                    <span class="job-flagged">
                                        <i class="fas fa-flag" title="This job has been flagged"></i>
                                    </span>
                                <?php endif; ?>
                                
                                <span class="job-status-badge job-status-<?php echo strtolower($job['moderation_status']); ?>">
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
                                    
                                    <?php if (!empty($job['salary'])): ?>
                                    <p class="card-text mb-1">
                                        <i class="fas fa-money-bill-wave text-muted me-1"></i>
                                        $<?php echo number_format($job['salary'], 2); ?>
                                    </p>
                                    <?php endif; ?>
                                    
                                    <p class="card-text text-muted small mt-2">
                                        Posted <?php echo date('M j, Y', strtotime($job['posted_at'])); ?>
                                        <?php if (!empty($job['expires_at'])): ?>
                                            · Expires <?php echo date('M j, Y', strtotime($job['expires_at'])); ?>
                                        <?php endif; ?>
                                    </p>
                                    
                                    <div class="action-buttons mt-3">
                                        <a href="employer_jobs.php?job_id=<?php echo $job['job_id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye me-1"></i> View
                                        </a>
                                        <a href="employer_post_job.php?edit=<?php echo $job['job_id']; ?>" class="btn btn-sm btn-outline-secondary">
                                            <i class="fas fa-edit me-1"></i> Edit
                                        </a>
                                        <?php if ($job['moderation_status'] === 'Approved'): ?>
                                        <a href="employer_applications.php?job_id=<?php echo $job['job_id']; ?>" class="btn btn-sm btn-outline-success">
                                            <i class="fas fa-users me-1"></i> View Applicants
                                        </a>
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
                                <a href="../dashboard/employer_post_job.php" class="btn btn-primary-profile btn-sm">
                                    <i class="fas fa-plus me-1"></i> Post a Job
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
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
                        if (file.size > 2 * 1024 * 1024) {
                            alert('File size must be less than 2MB');
                            return;
                        }
                        
                        // Create FormData and upload
                        const formData = new FormData();
                        formData.append('profile_picture', file);
                        formData.append('action', 'update_employer_profile');
                        
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
                                const newSrc = '/skillmatch-main/skillmatch-main/uploads/' + data.profile_picture;
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

        function renderJobs(jobs) {
            let jobsHtml = '';
            if (jobs && jobs.length > 0) {
                jobs.forEach(job => {
                    jobsHtml += `
                        <div class="job-card card mb-3 position-relative">
                            ${job.flagged ? `<span class="job-flagged"><i class="fas fa-flag" title="This job has been flagged"></i></span>` : ''}
                            <span class="job-status-badge job-status-${job.moderation_status.toLowerCase()}">${job.moderation_status}</span>
                            <div class="card-body">
                                <h5 class="card-title">${job.title}</h5>
                                ${job.location ? `<p class="card-text mb-1"><i class="fas fa-map-marker-alt text-muted me-1"></i>${job.location}</p>` : ''}
                                ${job.salary ? `<p class="card-text mb-1"><i class="fas fa-money-bill-wave text-muted me-1"></i>$${parseFloat(job.salary).toFixed(2)}</p>` : ''}
                                <p class="card-text text-muted small mt-2">Posted ${new Date(job.posted_at).toLocaleDateString()}</p>
                                <div class="action-buttons mt-3">
                                    <a href="view_job.php?id=${job.job_id}" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye me-1"></i> View</a>
                                    <a href="edit_job.php?id=${job.job_id}" class="btn btn-sm btn-outline-secondary"><i class="fas fa-edit me-1"></i> Edit</a>
                                    ${job.moderation_status === 'Approved' ? `<a href="manage_applications.php?job_id=${job.job_id}" class="btn btn-sm btn-outline-success"><i class="fas fa-users me-1"></i> View Applicants</a>` : ''}
                                </div>
                            </div>
                        </div>
                    `;
                });
            } else {
                jobsHtml = `
                    <div class="text-center py-4">
                        <i class="fas fa-list-alt fa-3x text-muted mb-3"></i>
                        <h5>No Active Job Postings</h5>
                        <p class="text-muted">You haven't posted any jobs yet</p>
                        <a href="post_job.php" class="btn btn-primary-profile btn-sm">
                            <i class="fas fa-plus me-1"></i> Post a Job
                        </a>
                    </div>
                `;
            }
            document.querySelector('.jobs-section').innerHTML = jobsHtml;
        }

        function renderStats(stats) {
            document.getElementById('jobs-posted').innerText = stats.totalJobPostings;
            document.getElementById('applications-received').innerText = stats.totalApplicationsReceived;
            document.getElementById('interviews-scheduled').innerText = stats.totalInterviewsScheduled;
        }

        function renderApplications(applications) {
            let appsHtml = '';
            if (applications && applications.length > 0) {
                applications.forEach(app => {
                    appsHtml += `
                        <div class="application-card">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0">${app.job_title}</h6>
                                <span class="status-badge status-${app.status.toLowerCase()}">${app.status}</span>
                            </div>
                            <div class="text-muted small mb-2">${app.stud_first_name} ${app.stud_last_name}</div>
                            <div class="text-muted small">Applied on ${new Date(app.applied_date).toLocaleDateString()}</div>
                            <div class="action-buttons mt-2">
                                <a href="view_application.php?id=${app.application_id}" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye me-1"></i> View
                                </a>
                                ${(app.status === 'Pending' || app.status === 'Under Review') ? `<a href="schedule_interview.php?application_id=${app.application_id}" class="btn btn-sm btn-outline-success"><i class="fas fa-calendar-alt me-1"></i> Schedule Interview</a>` : ''}
                            </div>
                        </div>
                    `;
                });
            } else {
                appsHtml = `
                    <div class="text-center py-4">
                        <i class="fas fa-briefcase fa-3x text-muted mb-3"></i>
                        <h5>No Applications Yet</h5>
                        <p class="text-muted">Applications to your job postings will appear here</p>
                    </div>
                `;
            }
            document.querySelector('.applications-section').innerHTML = appsHtml;
        }

        // Profile data is now loaded directly from PHP
        document.addEventListener('DOMContentLoaded', function() {
            // Any additional client-side functionality can be added here
            console.log('Employer profile loaded successfully');
        });
    </script>
</body>
</html>