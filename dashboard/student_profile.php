<?php
require '../controllers/student_profile_controller.php';
require '../auth/auth_check_student.php';
include '../includes/stud_navbar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($student['stud_first_name'] ?? 'Student'); ?> | Career Profile</title>
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
            --background-color: #f8f9fa;
            --secondary-color: #3A7BD5;
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
            background-color: var(--background-color);
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
        
        .skill-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            margin: 0.25rem;
            border-radius: 20px;
            background-color: var(--primary-light);
            color: var(--primary-color);
            font-size: 0.85rem;
            font-weight: 500;
            transition: var(--transition);
        }
        
        .skill-badge:hover {
            background-color: var(--primary-color);
            color: white;
            transform: translateY(-2px);
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
    </style>
</head>
<body>
    <!-- Success Message -->
    <?php if (isset($_GET['success'])): ?>
        <div class="floating-alert alert alert-success alert-dismissible fade show" role="alert">
            <div class="d-flex align-items-center">
                <i class="fas fa-check-circle me-2"></i>
                <div>Profile updated successfully!</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="container py-4 animate__animated animate__fadeIn">
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="row align-items-center">
                <div class="col-md-auto text-center text-md-start position-relative">
                    <img src="<?php echo $profile_pic; ?>" class="profile-picture mb-3 mb-md-0" alt="Profile Picture">
                    <div class="avatar-edit">
                        <input type="file" id="profilePictureInput" name="profile_picture" accept="image/*">
                        <label for="profilePictureInput" title="Change photo">
                            <i class="fas fa-camera"></i>
                        </label>
                    </div>
                </div>
                <div class="col-md">
                    <h1 class="profile-name">
                        <?php echo htmlspecialchars($student['stud_first_name'] ?? 'No Name') . ' ' . htmlspecialchars($student['stud_last_name'] ?? 'Available'); ?>
                    </h1>
                    <div class="profile-title">
                        <?php if (!empty($student['course_title'])): ?>
                            <?php echo htmlspecialchars($student['course_title']); ?>
                            <?php if (!empty($student['graduation_yr'])): ?>
                                · Expected <?php echo htmlspecialchars($student['graduation_yr']); ?>
                            <?php endif; ?>
                        <?php else: ?>
                            Student
                        <?php endif; ?>
                    </div>
                    <div class="profile-meta">
                        <?php if (!empty($student['institution'])): ?>
                        <div class="profile-meta-item">
                            <i class="fas fa-university"></i>
                            <?php echo htmlspecialchars($student['institution']); ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($student['stud_email'])): ?>
                        <div class="profile-meta-item">
                            <i class="fas fa-envelope"></i>
                            <?php echo htmlspecialchars($student['stud_email']); ?>
                        </div>
                        <?php endif; ?>
                        
                        
                        <?php if (!empty($student['stud_no'])): ?>
                        <div class="profile-meta-item">
                            <i class="fas fa-id-card"></i>
                            <?php echo htmlspecialchars($student['stud_no']); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="action-buttons">
                        <?php if (!empty($student['resume_file'])): ?>
                        <a href="../Uploads/<?php echo htmlspecialchars($student['resume_file']); ?>" 
                           class="btn btn-light btn-sm" 
                           download>
                            <i class="fas fa-download me-1"></i> Download Resume
                        </a>
                        <?php endif; ?>
                        
                        <a href="student_account_settings.php"  class="btn btn-light btn-sm">
                            <i class="bi bi-pencil me-2"></i> Edit Profile
                        </a>
                        
                        <a href="student_job.php" class="btn btn-light btn-sm">
                            <i class="fas fa-search me-1"></i> Find Jobs
                        </a>
                    </div>
                    
                    <div class="social-links">
                        <?php if (!empty($student['linkedin'])): ?>
                        <a href="<?php echo htmlspecialchars($student['linkedin']); ?>" class="social-link" target="_blank">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                        <?php endif; ?>
                        <?php if (!empty($student['github'])): ?>
                        <a href="<?php echo htmlspecialchars($student['github']); ?>" class="social-link" target="_blank">
                            <i class="fab fa-github"></i>
                        </a>
                        <?php endif; ?>
                        <?php if (!empty($student['portfolio'])): ?>
                        <a href="<?php echo htmlspecialchars($student['portfolio']); ?>" class="social-link" target="_blank">
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
                        <?php if (!empty($student['stud_date_of_birth'])): ?>
                        <div class="info-item">
                            <div class="info-label">Date of Birth</div>
                            <div class="info-value">
                                <?php echo date('F j, Y', strtotime($student['stud_date_of_birth'])); ?>
                                <?php if (!empty($student['stud_gender'])): ?>
                                    <span class="text-muted ms-2">(<?php echo htmlspecialchars($student['stud_gender']); ?>)</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($student['phone'])): ?>
                        <div class="info-item">
                            <div class="info-label">Phone</div>
                            <div class="info-value">
                                <?php echo htmlspecialchars($student['phone']); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($student['bio'])): ?>
                        <div class="info-item">
                            <div class="info-label">Professional Summary</div>
                            <div class="info-value">
                                <?php echo htmlspecialchars($student['bio']); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Education Section -->
                <div class="card card-profile">
                    <div class="card-header-profile">
                        <i class="fas fa-graduation-cap me-2"></i> Education
                    </div>
                    <div class="card-body-profile">
                        <div class="info-item">
                            <div class="info-value fw-bold">
                                <?php echo htmlspecialchars($student['institution'] ?? 'Not specified'); ?>
                            </div>
                            <div class="info-value">
                                <?php if (!empty($student['course_title'])): ?>
                                    <?php echo htmlspecialchars($student['course_title']); ?>
                                <?php else: ?>
                                    No course specified
                                <?php endif; ?>
                            </div>
                            <div class="text-muted">
                                <?php if (!empty($student['graduation_yr'])): ?>
                                    Expected graduation: <?php echo htmlspecialchars($student['graduation_yr']); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Skills Section -->
                <div class="card card-profile shadow-sm">
                    <div class="card-header-profile bg-white border-bottom">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-code me-2 text-primary"></i>
                            <h6 class="mb-0 fw-semibold">Skills & Competencies</h6>
                        </div>
                    </div>
                    <div class="card-body-profile p-4">
                        <?php if (!empty($skills)): ?>
                            <div class="skills-container">
                                <?php foreach ($skills as $skill): ?>
                                    <div class="skill-item mb-3">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="fw-medium text-dark"><?php echo htmlspecialchars($skill['skill_name']); ?></span>
                                            <small class="text-muted">
                                                <?php 
                                                $proficiency = ucfirst($skill['proficiency']);
                                                echo "{$proficiency}"; 
                                                ?>
                                            </small>
                                        </div>
                                        
                                        <?php
                                        $progress = 0;
                                        $color_class = '';
                                        switch($skill['proficiency']) {
                                            case 'Beginner':
                                                $progress = 33;
                                                $color_class = 'bg-info';
                                                break;
                                            case 'Intermediate':
                                                $progress = 66;
                                                $color_class = 'bg-primary';
                                                break;
                                            case 'Advanced':
                                                $progress = 100;
                                                $color_class = 'bg-success';
                                                break;
                                        }
                                        ?>
                                        
                                        <div class="progress" style="height: 6px;">
                                            <div class="progress-bar <?php echo $color_class; ?> rounded" 
                                                role="progressbar" 
                                                style="width: <?php echo $progress; ?>%" 
                                                aria-valuenow="<?php echo $progress; ?>" 
                                                aria-valuemin="0" 
                                                aria-valuemax="100">
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-light border d-flex align-items-center mb-0">
                                <i class="fas fa-info-circle text-primary me-2"></i>
                                <div>
                                    <small class="text-muted">Add your skills to showcase your expertise to potential employers.</small>
                                </div>
                            </div>
                        <?php endif; ?>
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
                                <div class="info-value fw-bold text-primary" id="jobs-applied">12</div>
                                <div class="info-label">Jobs Applied</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-profile h-100">
                            <div class="card-body-profile text-center">
                                <div class="info-value fw-bold text-success" id="interviews">3</div>
                                <div class="info-label">Interviews</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-profile h-100">
                            <div class="card-body-profile text-center">
                                <div class="info-value fw-bold text-warning" id="profile-strength">85%</div>
                                <div class="info-label">Profile Strength</div>
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
                       <!--  <a href="applications.php" class="btn btn-sm btn-outline-primary">View All</a>-->
                    </div>
                    <div class="card-body-profile">
                        <?php if (!empty($applications)): ?>
                            <?php foreach($applications as $app): ?>
                            <div class="application-card">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="mb-0"><?php echo htmlspecialchars($app['job_title']); ?></h6>
                                    <span class="status-badge status-<?php echo htmlspecialchars(strtolower($app['status'])); ?>">
                                        <?php echo htmlspecialchars($app['status']); ?>
                                    </span>
                                </div>
                                <div class="text-muted small mb-2"><?php echo htmlspecialchars($app['company_name']); ?></div>
                                <div class="text-muted small">Applied on <?php echo date('M j, Y', strtotime($app['applied_date'])); ?></div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-briefcase fa-3x text-muted mb-3"></i>
                                <h5>No Applications Yet</h5>
                                <p class="text-muted">Start applying to jobs to track your progress here</p>
                                <a href="student_job.php" class="btn btn-primary-profile btn-sm">
                                    <i class="fas fa-search me-1"></i> Browse Jobs
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
            </div>
        </div>
    </div>

    <!-- EDIT PROFILE 
    <div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editProfileModalLabel">Edit Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="updateProfileForm" method="post" enctype="multipart/form-data">
                        // Bio 
                        <div class="mb-3">
                            <label for="bio" class="form-label">Bio</label>
                            <textarea class="form-control" id="bio" name="bio" rows="3"><?php echo htmlspecialchars($student['bio'] ?? ''); ?></textarea>
                        </div>

                        // Profile Picture 
                        <div class="mb-3">
                            <label for="profile_picture" class="form-label">Profile Picture</label>
                            <input type="file" class="form-control" id="profile_picture" name="profile_picture">
                        </div>

                        // Resume 
                        <div class="mb-3">
                            <label for="resume" class="form-label">Upload Resume</label>
                            <input type="file" class="form-control" id="resume" name="resume">
                        </div>

                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    -->

    <?php include '../includes/stud_footer.php'; ?>
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        /*
        $(document).ready(function() {
            setTimeout(function() {
            $('.alert-success').alert('close');

            if (window.history.replaceState) {
                const cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
                window.history.replaceState({ path: cleanUrl }, '', cleanUrl);
            }
        }, 5000);


            $("#updateProfileForm").submit(function(e) {
                e.preventDefault();
                let formData = new FormData(this);

                // Validate files before submission
                const profilePic = $('#profile_picture')[0].files[0];
                if (profilePic && profilePic.size > 5000000) { // 2MB
                    alert('Profile picture must be less than 2MB');
                    return;
                }
                
                const resume = $('#resume')[0].files[0];
                if (resume && resume.size > 5000000) { // 5MB
                    alert('Resume must be less than 5MB');
                    return;
                }

                $.ajax({
                    type: "POST",
                    url: "../controllers/student_update_profile.php",
                    data: formData,
                    contentType: false,
                    processData: false,
                    dataType: "json",
                    beforeSend: function() {
                        // Show loading state
                        $('.btn-primary').prop('disabled', true)
                            .html('<span class="spinner-border spinner-border-sm" role="status"></span> Saving...');
                    },
                    success: function(response) {
                        console.log('Server Response:', response); // For debugging
                        
                        if (response.status === "success") {
                            // Option 1: Reload the page with success message
                            window.location.href = window.location.pathname + '?success=1';
                            
                            // Option 2: Update DOM without reload 
                            /*
                            if (response.bio) {
                                $('.info-value').filter(function() {
                                    return $(this).prev('.info-label').text() === 'Professional Summary';
                                }).text(response.bio);
                            }
                            if (response.profile_picture) {
                                $('.profile-picture').attr('src', '../assets/uploads/' + response.profile_picture + '?t=' + new Date().getTime());
                            }
                            if (response.resume_file) {
                                $('a[download]').attr('href', '../assets/uploads/' + response.resume_file);
                            }
                            
                            
                            $("#editProfileModal").modal("hide");
                        } else {
                            alert('Error: ' + (response.message || 'Update failed'));
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', status, error);
                        console.error('Server Response:', xhr.responseText);
                        alert('An error occurred. Please check console for details.');
                    },
                    complete: function() {
                        // Reset button state
                        $('.btn-primary').prop('disabled', false).html('Save Changes');
                    }
                });
            });
        });
        */
    </script>
    <script>
    // Fetch the data from the backend
    fetch('../controllers/student_profile_api.php')
        .then(response => response.json())
        .then(data => {
            document.getElementById('jobs-applied').innerText = data.totalApplications;
            document.getElementById('interviews').innerText = data.totalInterviews;
            document.getElementById('profile-strength').innerText = data.profileStrength;
        })
        .catch(error => console.error('Error fetching data:', error));
</script>
</body>
</html>