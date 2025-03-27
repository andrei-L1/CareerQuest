<?php
require '../controllers/student_profile_controller.php';
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
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #f8f9fa;

            --dark-color: #2c3e50;
            --text-color: #333;
            --light-text: #7f8c8d;
        }
        
        body {
            background-color: var(--secondary-color);
            color: var(--text-color);
            font-family: 'Segoe UI', 'Roboto', sans-serif;
        }
        
        .profile-header {
            background: linear-gradient(135deg, var(--primary-color), var(--dark-color));
            color: white;
            border-radius: 8px;
            padding: 30px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }
        
        .profile-header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transform: translate(50px, -50px);
        }
        
        .profile-picture {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            border: 5px solid white;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            z-index: 1;
            position: relative;
        }
        
        .profile-name {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .profile-title {
            font-size: 18px;
            margin-bottom: 15px;
            opacity: 0.9;
        }
        
        .profile-meta {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
        }
        
        .profile-meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 14px;
        }
        
        .card-profile {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 25px;
            transition: transform 0.3s ease;
        }
        
        .card-profile:hover {
            transform: translateY(-5px);
        }
        
        .card-header-profile {
            background-color: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            font-size: 18px;
            font-weight: 600;
            padding: 15px 20px;
            border-radius: 10px 10px 0 0 !important;
        }
        
        .card-body-profile {
            padding: 20px;
        }
        
        .info-item {
            margin-bottom: 15px;
        }
        
        .info-label {
            color: var(--light-text);
            font-size: 14px;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .info-value {
            font-size: 16px;
        }
        
        .btn-primary-profile {
            background-color: var(--primary-color);
            border: none;
            border-radius: 6px;
            font-weight: 600;
            padding: 8px 20px;
            transition: all 0.3s ease;
        }
        
        .btn-primary-profile:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .skill-badge {
            display: inline-block;
            padding: 6px 12px;
            margin: 5px;
            border-radius: 20px;
            background-color: #e8f4fc;
            color: var(--primary-color);
            font-size: 14px;
            font-weight: 500;
        }
        
        .progress {
            height: 8px;
            border-radius: 4px;
            background-color: #ecf0f1;
        }
        
        .progress-bar {
            background-color: var(--accent-color);
        }
        
        .section-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--dark-color);
            position: relative;
            padding-bottom: 10px;
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
            transition: all 0.3s ease;
        }
        
        .application-card:hover {
            transform: translateX(5px);
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-pending {
            background-color: #f39c12;
            color: white;
        }
        
        .status-accepted {
            background-color: var(--accent-color);
            color: white;
        }
        
        .status-rejected {
            background-color: #e74c3c;
            color: white;
        }
    </style>
</head>
<body>
    <!-- Success Message -->
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert" style="position: fixed; top: 80px; right: 20px; z-index: 1000;">
            Profile updated successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="container py-4">
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="row align-items-center">
                <div class="col-md-auto text-center text-md-start">
                    <img src="<?php echo $profile_pic; ?>" class="profile-picture mb-3 mb-md-0" alt="Profile Picture">
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
                    <?php if (!empty($student['resume_file'])): ?>
                    <a href="../assests/uploads/<?php echo htmlspecialchars($student['resume_file']); ?>" 
                       class="btn btn-light btn-sm mt-2" 
                       download>
                        <i class="fas fa-download me-1"></i> Download Resume
                    </a>
                    <?php endif; ?>
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
                
                <!-- Skills Section (Placeholder) -->
                <div class="card card-profile">
                    <div class="card-header-profile">
                        <i class="fas fa-code me-2"></i> Skills
                    </div>
                    <div class="card-body-profile">
                        <div class="alert alert-info">
                            Skills functionality will be implemented in a future update.
                        </div>
                        <!-- Placeholder skills - to be replaced with database integration -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span>Web Development</span>
                                <span class="text-muted">Intermediate</span>
                            </div>
                            <div class="progress mt-2">
                                <div class="progress-bar" style="width: 66%"></div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span>Database Management</span>
                                <span class="text-muted">Beginner</span>
                            </div>
                            <div class="progress mt-2">
                                <div class="progress-bar" style="width: 33%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Right Column - Professional Content -->
            <div class="col-lg-7">
                <!-- Quick Actions -->
                <div class="card card-profile mb-4">
                    <div class="card-body-profile">
                        <div class="d-flex justify-content-between">
                            <a href="edit_profile.php" class="btn btn-outline-primary">
                                <i class="fas fa-edit me-1"></i> Edit Profile
                            </a>
                            <a href="job_search.php" class="btn btn-primary-profile">
                                <i class="fas fa-search me-1"></i> Find Jobs
                            </a>
                            <a href="resume_builder.php" class="btn btn-outline-secondary">
                                <i class="fas fa-file-alt me-1"></i> Build Resume
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Applications (Placeholder) -->
                <div class="card card-profile">
                    <div class="card-header-profile">
                        <i class="fas fa-briefcase me-2"></i> Recent Applications
                    </div>
                    <div class="card-body-profile">
                        <div class="alert alert-info mb-4">
                            Application tracking will be implemented in a future update.
                        </div>
                        
                        <!-- Placeholder applications - to be replaced with database integration -->
                        <div class="application-card p-3 mb-3 bg-white rounded">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0">Web Developer Intern</h6>
                                <span class="status-badge status-pending">Pending</span>
                            </div>
                            <div class="text-muted small mb-2">Tech Solutions Inc.</div>
                            <div class="text-muted small">Applied on May 15, 2023</div>
                        </div>
                        
                        <div class="application-card p-3 mb-3 bg-white rounded">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0">Data Analyst</h6>
                                <span class="status-badge status-accepted">Accepted</span>
                            </div>
                            <div class="text-muted small mb-2">Analytics Corp</div>
                            <div class="text-muted small">Applied on April 28, 2023</div>
                        </div>
                        
                        <a href="#" class="btn btn-outline-primary btn-sm">View All Applications</a>
                    </div>
                </div>
                
                <!-- Recommended Jobs (Placeholder) -->
                <div class="card card-profile mt-4">
                    <div class="card-header-profile">
                        <i class="fas fa-lightbulb me-2"></i> Recommended For You
                    </div>
                    <div class="card-body-profile">
                        <div class="alert alert-info">
                            Job recommendations will be implemented in a future update.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>