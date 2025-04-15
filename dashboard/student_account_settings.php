<?php
// Start session at the very top
require '../auth/auth_check_student.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if not logged in
if (!isset($_SESSION['stud_id'])) {
    header('Location: login.php');
    exit();
}

$stud_id = $_SESSION['stud_id'];

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require '../config/dbcon.php';
require '../controllers/student_profile_controller.php';
include '../includes/stud_navbar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings | CareerConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
      <style>
        :root {
            --primary-color: #4361ee;
            --primary-light: #eef2ff;
            --secondary-color: #3f37c9;
            --accent-color: #f8f9fa;
            --text-color: #2b2d42;
            --light-text: #8d99ae;
            --light-bg: #f8f9fa;
            --card-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            --border-radius: 12px;
            --section-gap: 2rem;
        }
        
        body {
            background-color: var(--light-bg);
            color: var(--text-color);
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
        }
        
        .settings-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }
        
        .settings-card {
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            margin-bottom: 2rem;
            border: none;
            overflow: hidden;
            transition: var(--transition);
            background: white;
        }
        
        .settings-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12);
        }
        
        .settings-header {
            padding: 1.5rem;
            font-weight: 600;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            font-size: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .settings-header i {
            font-size: 1.1em;
        }
        
        .profile-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 2rem 1.5rem;
            text-align: center;
        }
        
        .profile-picture-preview {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            border: 5px solid white;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: var(--transition);
            cursor: pointer;
            margin-bottom: 1.5rem;
        }
        
        .profile-picture-preview:hover {
            transform: scale(1.03);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }
        
        .profile-name {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
            color: var(--text-color);
        }
        
        .profile-email {
            color: var(--light-text);
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        
        .profile-institution {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--light-text);
            font-size: 0.85rem;
        }
        
        .form-section {
            margin-bottom: var(--section-gap);
            padding: 1.5rem;
            background-color: white;
            border-radius: var(--border-radius);
        }
        
        .form-section h5 {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.1rem;
        }
        
        .form-control, .form-select {
            border-radius: 8px;
            padding: 0.75rem 1rem;
            border: 1px solid #e0e0e0;
            transition: var(--transition);
            font-size: 0.95rem;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(67, 97, 238, 0.25);
        }
        
        .form-label {
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--text-color);
            font-size: 0.95rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            padding: 0.75rem 1.75rem;
            border-radius: 8px;
            font-weight: 500;
            transition: var(--transition);
            letter-spacing: 0.5px;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(67, 97, 238, 0.3);
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
        }
        
        .btn-outline-secondary {
            border-radius: 8px;
            padding: 0.75rem 1.75rem;
            transition: var(--transition);
        }
        
        .btn-outline-secondary:hover {
            background-color: #f1f3f9;
        }
        
        .file-upload-wrapper {
            position: relative;
            margin-bottom: 1rem;
        }
        
        .file-upload-label {
            display: block;
            padding: 2rem;
            border: 2px dashed #e0e0e0;
            border-radius: var(--border-radius);
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
            background-color: #fafbff;
        }
        
        .file-upload-label:hover {
            border-color: var(--primary-color);
            background-color: rgba(67, 97, 238, 0.05);
        }
        
        .file-upload-icon {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 0.75rem;
        }
        
        .file-upload-text {
            margin-bottom: 0.25rem;
            font-weight: 500;
        }
        
        .file-upload-subtext {
            font-size: 0.8rem;
            color: var(--light-text);
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
        
        .avatar-upload {
            position: relative;
            display: inline-block;
        }
        
        .avatar-edit {
            position: absolute;
            right: 10px;
            bottom: 10px;
            z-index: 1;
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
            border: 1px solid #e0e0e0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            text-align: center;
            line-height: 40px;
            color: var(--primary-color);
            transition: var(--transition);
        }
        
        .avatar-edit label:hover {
            background-color: var(--primary-light);
            transform: scale(1.1);
        }
        
        .progress {
            height: 8px;
            margin-top: 1rem;
            border-radius: 4px;
            background-color: #f0f0f0;
        }
        
        .progress-bar {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 4px;
        }
        
        .section-divider {
            border-top: 1px solid rgba(0, 0, 0, 0.08);
            margin: 2rem 0;
        }
        
        .quick-links .list-group-item {
            border: none;
            padding: 0.75rem 1rem;
            background: transparent;
            transition: var(--transition);
        }
        
        .quick-links .list-group-item:hover {
            background-color: var(--primary-light);
            padding-left: 1.25rem;
        }
        
        .quick-links .list-group-item i {
            color: var(--primary-color);
            width: 24px;
            text-align: center;
        }
        
        .quick-links .list-group-item a {
            text-decoration: none;
            color: var(--text-color);
            font-weight: 500;
            font-size: 0.95rem;
        }
        
        .skill-row {
            background-color: #f9fafd;
            border: 1px solid #e0e0e0 !important;
            transition: var(--transition);
        }
        
        .skill-row:hover {
            background-color: #f1f5ff;
            border-color: var(--primary-light) !important;
        }
        
        .remove-skill {
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .bio-counter {
            font-size: 0.85rem;
            color: var(--light-text);
        }
        
        .bio-counter.warning {
            color: #ff6b6b;
        }
        
        .alert {
            border-radius: 8px;
            padding: 1rem 1.25rem;
        }
        
        .alert i {
            margin-right: 0.5rem;
        }
        
        @media (max-width: 992px) {
            .settings-container {
                padding: 1rem;
            }
            
            .profile-picture-preview {
                width: 120px;
                height: 120px;
            }
        }
        
        @media (max-width: 768px) {
            .form-section {
                padding: 1.25rem;
            }
            
            .settings-header {
                padding: 1rem;
                font-size: 1.1rem;
            }
            
            .profile-card {
                padding: 1.5rem 1rem;
            }
        }
        
        /* Animation classes */
        .animate-fade-in {
            animation: fadeIn 0.5s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="container py-3 animate__animated animate__fadeIn">
        <div class="row">
            <div class="col-lg-3">
                <div class="card settings-card">
                    <div class="card-body text-center">
                        <div class="avatar-upload">
                            <img src="<?= $profile_pic ?>" class="profile-picture-preview mb-3" id="profilePicturePreview">
                            <div class="avatar-edit">
                                <input type="file" id="profilePictureInput" name="profile_picture" accept="image/*">
                                <label for="profilePictureInput" title="Change photo">
                                    <i class="fas fa-camera"></i>
                                </label>
                            </div>
                        </div>
                        <h5 class="mb-1"><?= htmlspecialchars($student['stud_first_name'] . ' ' . $student['stud_last_name']) ?></h5>
                        <p class="text-muted mb-2"><?= htmlspecialchars($student['stud_email']) ?></p>
                        <?php if (!empty($student['institution'])): ?>
                            <p class="text-muted small">
                                <i class="fas fa-university me-1"></i>
                                <?= htmlspecialchars($student['institution']) ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Quick Links Card -->
                <div class="card settings-card mt-4">
                    <div class="settings-header">
                        <i class="fas fa-link me-2"></i> Quick Links
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex align-items-center">
                                <i class="fas fa-user-circle me-2 text-primary"></i>
                                <a href="student_profile.php" class="text-decoration-none">View Public Profile</a>
                            </li>
                            <li class="list-group-item d-flex align-items-center">
                                <i class="fas fa-lock me-2 text-primary"></i>
                                <a href="change_password.php" class="text-decoration-none">Change Password</a>
                            </li>
                            <li class="list-group-item d-flex align-items-center">
                                <i class="fas fa-bell me-2 text-primary"></i>
                                <a href="notification_settings.php" class="text-decoration-none">Notification Settings</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-9">
                <div class="card settings-card">
                    <div class="settings-header">
                        <i class="fas fa-user-cog me-2"></i> Account Settings
                    </div>
                    
                    <div class="card-body">
                        <!-- Success/Error Messages -->
                        <?php if (isset($_GET['success'])): ?>
                            <div class="alert alert-success alert-dismissible fade show mb-4 animate__animated animate__fadeIn">
                                <i class="fas fa-check-circle me-2"></i>
                                Profile updated successfully!
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($_GET['error'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show mb-4 animate__animated animate__shakeX">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <?= htmlspecialchars(urldecode($_GET['error'])) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form id="accountSettingsForm" enctype="multipart/form-data" action="../controllers/student_update_profile.php" method="POST">
                            <!-- Personal Information Section -->
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <div class="form-section animate__animated animate__fadeIn">
                                <h5><i class="fas fa-user me-2"></i> Personal Information</h5>
                                <div class="row g-3 mt-3">
                                    <div class="col-md-4">
                                        <label class="form-label">First Name*</label>
                                        <input type="text" class="form-control" name="first_name" 
                                               value="<?= htmlspecialchars($student['stud_first_name'] ?? '') ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Middle Name</label>
                                        <input type="text" class="form-control" name="middle_name" 
                                               value="<?= htmlspecialchars($student['stud_middle_name'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Last Name*</label>
                                        <input type="text" class="form-control" name="last_name" 
                                               value="<?= htmlspecialchars($student['stud_last_name'] ?? '') ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Gender</label>
                                        <select class="form-select" name="gender">
                                            <option value="">Select Gender</option>
                                            <option value="Male" <?= ($student['stud_gender'] ?? '') == 'Male' ? 'selected' : '' ?>>Male</option>
                                            <option value="Female" <?= ($student['stud_gender'] ?? '') == 'Female' ? 'selected' : '' ?>>Female</option>
                                            <option value="Other" <?= ($student['stud_gender'] ?? '') == 'Other' ? 'selected' : '' ?>>Other</option>
                                            <option value="Prefer not to say" <?= ($student['stud_gender'] ?? '') == 'Prefer not to say' ? 'selected' : '' ?>>Prefer not to say</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Date of Birth</label>
                                        <input type="date" class="form-control" name="date_of_birth" 
                                               value="<?= !empty($student['stud_date_of_birth']) ? htmlspecialchars($student['stud_date_of_birth']) : '' ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="section-divider"></div>
                            <div class="form-section animate__animated animate__fadeIn">
                                <h5><i class="fas fa-tools me-2"></i> Skills</h5>
                                <div class="row g-3 mt-3">
                                    <div class="col-12">
                                        <div id="skillsContainer">
                                            <!-- Skills will be added here dynamically -->
                                        </div>
                                        <button type="button" class="btn btn-outline-primary mt-2" id="addSkillBtn">
                                            <i class="fas fa-plus me-1"></i> Add Skill
                                        </button>
                                    </div>
                                </div>
                            </div>
                                                                                    
                            <!-- Contact Information Section -->
                            <div class="form-section animate__animated animate__fadeIn">
                                <h5><i class="fas fa-envelope me-2"></i> Contact Information</h5>
                                <div class="row g-3 mt-3">
                                    <div class="col-md-12">
                                        <label class="form-label">Email*</label>
                                        <input type="email" class="form-control" name="email" 
                                               value="<?= htmlspecialchars($student['stud_email'] ?? '') ?>" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="section-divider"></div>
                            
                            <!-- Academic Information Section -->
                            <div class="form-section animate__animated animate__fadeIn">
                                <h5><i class="fas fa-graduation-cap me-2"></i> Academic Information</h5>
                                <div class="row g-3 mt-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Institution</label>
                                        <input type="text" class="form-control" name="institution" 
                                               value="<?= htmlspecialchars($student['institution'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Expected Graduation Year</label>
                                        <input type="number" class="form-control" name="graduation_yr" 
                                               min="<?= date('Y') ?>" max="<?= date('Y') + 10 ?>"
                                               value="<?= htmlspecialchars($student['graduation_yr'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Course</label>
                                        <select class="form-select" name="course_id">
                                            <?php foreach ($courses as $course): ?>
                                                <option value="<?= $course['course_id'] ?>" 
                                                    <?= (!empty($student['course_id']) && $student['course_id'] == $course['course_id']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($course['course_title']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>


                                </div>
                            </div>
                            
                            <div class="section-divider"></div>
                            
                            <!-- Profile Media Section -->
                            <div class="form-section animate__animated animate__fadeIn">
                                <h5><i class="fas fa-images me-2"></i> Profile Media</h5>
                                <div class="row g-3 mt-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Profile Picture</label>
                                        <div class="file-upload-wrapper">
                                            <label for="profilePictureInput" class="file-upload-label">
                                                <i class="fas fa-cloud-upload-alt fa-2x mb-2"></i>
                                                <p class="mb-1">Click to upload or drag and drop</p>
                                                <p class="small text-muted">JPG, PNG or GIF (Max. 2MB)</p>
                                            </label>
                                            <input type="file" class="file-upload-input" id="profilePictureInput" name="profile_picture" accept="image/*">
                                        </div>
                                        <div class="progress d-none" id="profilePictureProgress">
                                            <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Resume/CV</label>
                                        <div class="file-upload-wrapper">
                                            <label for="resumeInput" class="file-upload-label">
                                                <i class="fas fa-file-alt fa-2x mb-2"></i>
                                                <p class="mb-1">Click to upload or drag and drop</p>
                                                <p class="small text-muted">PDF, DOC or DOCX (Max. 5MB)</p>
                                            </label>
                                            <input type="file" class="file-upload-input" id="resumeInput" name="resume" accept=".pdf,.doc,.docx">
                                        </div>
                                        <?php if (!empty($student['resume_file'])): ?>
                                            <div class="mt-3">
                                                <a href="../uploads/<?= htmlspecialchars($student['resume_file']) ?>" 
                                                   class="btn btn-sm btn-outline-primary" download>
                                                    <i class="fas fa-download me-1"></i> Download Current Resume
                                                </a>
                                                <button type="button" class="btn btn-sm btn-outline-danger ms-2" id="deleteResumeBtn">
                                                    <i class="fas fa-trash me-1"></i> Delete
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                        <div class="progress d-none" id="resumeProgress">
                                            <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="section-divider"></div>
                            
                            <!-- Professional Summary -->
                            <div class="form-section animate__animated animate__fadeIn">
                                <h5><i class="fas fa-file-alt me-2"></i> Professional Summary</h5>
                                <div class="mt-3">
                                    <textarea class="form-control" name="bio" rows="5" placeholder="Tell employers about yourself, your skills, and your career goals..."><?= htmlspecialchars($student['bio'] ?? '') ?></textarea>
                                    <small class="text-muted">Maximum 500 characters</small>
                                    <div class="text-end">
                                        <span id="bioCounter"><?= 500 - strlen($student['bio'] ?? '') ?></span>/500
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between mt-4">
                                <button type="button" class="btn btn-outline-secondary px-4" id="discardChangesBtn">
                                    <i class="fas fa-times me-2"></i> Discard Changes
                                </button>
                                <button type="submit" class="btn btn-primary px-4">
                                    <i class="fas fa-save me-2"></i> Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include '../includes/stud_footer.php'; ?>
    <!-- JavaScript Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
    $(document).ready(function() {
        // Profile picture preview
        $('#profilePictureInput').change(function(e) {
            const file = e.target.files[0];
            if (file) {
                // Show loading state
                $('#profilePictureProgress').removeClass('d-none');
                
                // Simulate upload progress (in a real app, you'd use actual upload progress)
                let progress = 0;
                const progressInterval = setInterval(() => {
                    progress += 5;
                    $('#profilePictureProgress .progress-bar').css('width', progress + '%');
                    
                    if (progress >= 100) {
                        clearInterval(progressInterval);
                        $('#profilePictureProgress').addClass('d-none');
                        
                        // Preview the image
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            $('#profilePicturePreview').attr('src', e.target.result);
                            
                            // Show success message
                            Swal.fire({
                                icon: 'success',
                                title: 'Profile picture updated!',
                                showConfirmButton: false,
                                timer: 1500
                            });
                        }
                        reader.readAsDataURL(file);
                    }
                }, 100);
            }
        });
        
        // Resume upload progress
        $('#resumeInput').change(function(e) {
            const file = e.target.files[0];
            if (file) {
                // Show loading state
                $('#resumeProgress').removeClass('d-none');
                
                // Simulate upload progress
                let progress = 0;
                const progressInterval = setInterval(() => {
                    progress += 5;
                    $('#resumeProgress .progress-bar').css('width', progress + '%');
                    
                    if (progress >= 100) {
                        clearInterval(progressInterval);
                        $('#resumeProgress').addClass('d-none');
                        
                        // Show success message
                        Swal.fire({
                            icon: 'success',
                            title: 'Resume uploaded!',
                            showConfirmButton: false,
                            timer: 1500
                        });
                    }
                }, 100);
            }
        });
        
        // Delete resume button
        $('#deleteResumeBtn').click(function() {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // In a real app, you would make an AJAX call to delete the file
                    // For now, we'll just show a success message
                    Swal.fire(
                        'Deleted!',
                        'Your resume has been deleted.',
                        'success'
                    );
                    // Hide the download and delete buttons
                    $(this).parent().fadeOut();
                }
            });
        });
        
        // Bio character counter
        $('textarea[name="bio"]').on('input', function() {
            const maxLength = 500;
            const currentLength = $(this).val().length;
            const remaining = maxLength - currentLength;
            
            $('#bioCounter').text(remaining);
            
            if (remaining < 0) {
                $('#bioCounter').css('color', 'red');
                $(this).val($(this).val().substring(0, maxLength));
            } else {
                $('#bioCounter').css('color', 'inherit');
            }
        });
        
        // Discard changes button
        $('#discardChangesBtn').click(function() {
            Swal.fire({
                title: 'Discard changes?',
                text: "You have unsaved changes that will be lost.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, discard them!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Reload the page to discard changes
                    window.location.reload();
                }
            });
        });
        
        // Form submission with AJAX
        $('#accountSettingsForm').submit(function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            // Show loading state
            $('button[type="submit"]').prop('disabled', true)
                .html('<span class="spinner-border spinner-border-sm" role="status"></span> Saving...');
            
            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if(response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Profile Updated!',
                            text: 'Your changes have been saved successfully.',
                            showConfirmButton: false,
                            timer: 1500
                        }).then(() => {
                            window.location.href = 'student_account_settings.php?success=1';
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message || 'An error occurred while saving your changes.',
                        });
                    }
                },
                error: function(xhr) {
                    let errorMsg = 'An error occurred while saving your changes.';
                    try {
                        const response = JSON.parse(xhr.responseText);
                        errorMsg = response.message || errorMsg;
                    } catch(e) {
                        errorMsg = xhr.statusText || errorMsg;
                    }
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: errorMsg,
                    });
                },
                complete: function() {
                    $('button[type="submit"]').prop('disabled', false)
                        .html('<i class="fas fa-save me-2"></i> Save Changes');
                }
            });
        });
        
        // Drag and drop functionality
        $('.file-upload-label').on('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).css('border-color', '#3A7BD5');
            $(this).css('background-color', 'rgba(58, 123, 213, 0.1)');
        });
        
        $('.file-upload-label').on('dragleave', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).css('border-color', '#ced4da');
            $(this).css('background-color', 'transparent');
        });
        
        $('.file-upload-label').on('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).css('border-color', '#ced4da');
            $(this).css('background-color', 'transparent');
            
            const files = e.originalEvent.dataTransfer.files;
            if (files.length > 0) {
                $(this).siblings('.file-upload-input')[0].files = files;
                $(this).siblings('.file-upload-input').trigger('change');
            }
        });
    });




    

// Skills Management
$(document).ready(function() {
    // Fetch available skills from the database
    function fetchAvailableSkills() {
        return $.ajax({
            url: '../controllers/fetch_skills.php',
            method: 'GET',
            dataType: 'json'
        });
    }

    // Track existing skills for deletion
    const existingSkills = new Set();

    // Add skill row
    async function addSkillRow(skill = null) {
        const skills = await fetchAvailableSkills();
        const skillId = skill ? skill.skill_id : '';
        const proficiency = skill ? skill.proficiency : 'Beginner';
        
        if (skillId) {
            existingSkills.add(skillId);
        }
        
        const rowId = 'skill_' + (skill ? skill.skill_id : Date.now());
        const skillOptions = skills.map(s => 
            `<option value="${s.skill_id}" ${skillId == s.skill_id ? 'selected' : ''}>${s.skill_name}</option>`
        ).join('');
        
        const row = `
            <div class="skill-row mb-3 p-3 border rounded" id="${rowId}" data-skill-id="${skillId || ''}">
                <div class="row g-2">
                    <div class="col-md-6">
                        <select class="form-select skill-select" name="skills[${rowId}][skill_id]" required
                            ${skillId ? 'disabled' : ''}>
                            <option value="">Select a skill</option>
                            ${skillOptions}
                        </select>
                    </div>
                    <div class="col-md-5">
                        <select class="form-select" name="skills[${rowId}][proficiency]" required>
                            <option value="Beginner" ${proficiency == 'Beginner' ? 'selected' : ''}>Beginner</option>
                            <option value="Intermediate" ${proficiency == 'Intermediate' ? 'selected' : ''}>Intermediate</option>
                            <option value="Advanced" ${proficiency == 'Advanced' ? 'selected' : ''}>Advanced</option>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-outline-danger w-100 remove-skill">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <input type="hidden" name="skills[${rowId}][group_no]" value="<?= $stud_id ?>">
                    ${skillId ? `<input type="hidden" name="skills[${rowId}][skill_id]" value="${skillId}">` : ''}
                </div>
            </div>
        `;
        
        $('#skillsContainer').append(row);
    }

    // Add skill button click handler
    $('#addSkillBtn').click(function() {
        addSkillRow();
    });

    // Remove skill button click handler
    $(document).on('click', '.remove-skill', function() {
        const row = $(this).closest('.skill-row');
        const skillId = row.data('skill-id');
        
        if (skillId) {
            Swal.fire({
                title: 'Remove Skill?',
                text: "This will permanently remove this skill from your profile.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, remove it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    existingSkills.delete(skillId);
                    row.remove();
                }
            });
        } else {
            row.remove();
        }
    });

    // Load existing skills when page loads
    function loadExistingSkills() {
        $.ajax({
            url: '../controllers/fetch_student_skills.php?stud_id=<?= $stud_id ?>',
            method: 'GET',
            dataType: 'json',
            success: function(skills) {
                if (skills.length > 0) {
                    skills.forEach(skill => {
                        addSkillRow(skill);
                    });
                }
            },
            error: function() {
                // No empty row is added if there's an error
            }
        });
    }


    // Initialize skills on page load
    loadExistingSkills();
});
    </script>
</body>
</html>