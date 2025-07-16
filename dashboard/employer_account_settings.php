<?php
// Start session at the very top
require '../auth/auth_check_employer.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require '../config/dbcon.php';
include '../includes/employer_navbar.php';

// Fetch employer data
try {
    $stmt = $conn->prepare("
        SELECT 
            u.user_id, u.user_first_name, u.user_middle_name, u.user_last_name, 
            u.user_email, u.picture_file, u.status as user_status,
            e.employer_id, e.company_name, e.job_title, e.company_logo,
            e.company_website, e.contact_number, e.company_description
        FROM user u
        JOIN employer e ON u.user_id = e.user_id
        WHERE u.user_id = :user_id AND u.deleted_at IS NULL
    ");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $employer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$employer) {
        throw new Exception("Employer not found");
    }
} catch (Exception $e) {
    error_log("Error fetching employer data: " . $e->getMessage());
    header('Location: ../auth/logout.php');
    exit();
}

// Set profile picture and company logo
$profile_picture = !empty($employer['picture_file']) ? '../Uploads/' . $employer['picture_file'] : '../Uploads/default.png';
$company_logo = !empty($employer['company_logo']) ? '../Uploads/' . $employer['company_logo'] : '../Uploads/default.png';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings | <?php echo htmlspecialchars($employer['company_name'] ?? 'Employer'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #1A4D8F;
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
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            color: var(--primary-color);        
            font-size: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .settings-header i {
            font-size: 1.1em;
        }
        .profile-card, .logo-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 2rem 1.5rem;
            text-align: center;
        }
        .profile-picture-preview, .company-logo-preview {
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
        .profile-picture-preview:hover, .company-logo-preview:hover {
            transform: scale(1.03);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
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
        .profile-name {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
            color: var(--text-color);
        }
        .profile-email {
            color: var(--light-text);
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        .profile-company {
            color: var(--light-text);
            font-size: 0.9rem;
            margin-bottom: 0.2rem;
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
        .form-section {
            margin-bottom: var(--section-gap);
            padding-left: 10px;
            padding-right: 10px;
            background-color: white;
            border-radius: var(--border-radius);
            border-bottom: 2px solid rgba(0, 0, 0, 0.05);
        }
        .form-section h5 {
            --adjust: 0.75rem;
        }
        .profile-name {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
            color: var(--text-color);
        }
        .profile-email {
            color: var(--light-text);
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        .profile-company {
            color: var(--light-text);
            font-size: 0.9rem;
            margin-bottom: 0.2rem;
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
        .form-section {
            margin-bottom: var(--section-gap);
            padding-left: 10px;
            padding-right: 10px;
            background-color: white;
            border-radius: var(--border-radius);
            border-bottom: 2px solid rgba(0, 0, 0, 0.05);
        }
        .form-section h5 {
            color: var(--primary-color);
            font-weight: 400;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.1rem;
            padding: 0;
            margin: 0;
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
        @media (max-width: 992px) {
            .settings-container {
                padding: 1rem;
            }
            .profile-picture-preview, .company-logo-preview {
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
            .profile-card, .logo-card {
                padding: 1.5rem 1rem;
            }
        }
    </style>
</head>
<body>
<div class="settings-main">
    <!-- Success/Error Messages -->
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?php echo htmlspecialchars($_GET['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?php echo htmlspecialchars($_GET['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <div class="container py-3 animate__animated animate__fadeIn">
        <div class="row">
            <div class="col-lg-3">
                <!-- Profile Picture Card -->
                <div class="card settings-card">
                    <div class="card-body text-center position-relative profile-card">
                        <div class="avatar-upload">
                            <img src="<?php echo htmlspecialchars($profile_picture); ?>" class="profile-picture-preview mb-3" id="profilePicturePreview">
                            <div class="avatar-edit">
                                <input type="file" id="profilePictureInput" name="profile_picture" accept="image/*">
                                <label for="profilePictureInput" title="Change photo">
                                    <i class="fas fa-camera"></i>
                                </label>
                            </div>
                        </div>
                        <div class="profile-name"><?php echo htmlspecialchars($employer['user_first_name'] . ' ' . $employer['user_last_name']); ?></div>
                        <div class="profile-email"><?php echo htmlspecialchars($employer['user_email']); ?></div>
                        <?php if (!empty($employer['company_name'])): ?>
                            <div class="profile-company"><?php echo htmlspecialchars($employer['company_name']); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($employer['job_title'])): ?>
                            <div class="profile-company"><?php echo htmlspecialchars($employer['job_title']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- Company Logo Card -->
                <div class="card settings-card mt-4">
                    <div class="card-body text-center position-relative logo-card">
                        <div class="avatar-upload">
                            <img src="<?php echo htmlspecialchars($company_logo); ?>" class="company-logo-preview mb-3" id="companyLogoPreview">
                            <div class="avatar-edit">
                                <input type="file" id="companyLogoInput" name="company_logo" accept="image/*">
                                <label for="companyLogoInput" title="Change company logo">
                                    <i class="fas fa-camera"></i>
                                </label>
                            </div>
                        </div>
                        <div class="profile-name">Company Logo</div>
                        <div class="form-text">Recommended: Square image, max 10MB.</div>
                    </div>
                </div>
                <!-- Quick Links Card -->
                <div class="card settings-card mt-4 quick-links">
                    <div class="settings-header">
                        <i class="fas fa-link me-2"></i> Quick Links
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex align-items-center">
                                <i class="fas fa-user-circle me-2 text-primary"></i>
                                <a href="employer_profile.php" class="text-decoration-none">View Profile</a>
                            </li>
                            <li class="list-group-item d-flex align-items-center">
                                <i class="fas fa-lock me-2 text-primary"></i>
                                <a href="#security" class="text-decoration-none">Change Password</a>
                            </li>
                            <li class="list-group-item d-flex align-items-center">
                                <i class="fas fa-bell me-2 text-primary"></i>
                                <a href="#notifications" class="text-decoration-none">Notification Settings</a>
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
                        <div id="profileAlertArea"></div>
                        <?php if (isset($_GET['success'])): ?>
                            <div class="alert alert-success alert-dismissible fade show mb-4 animate__animated animate__fadeIn">
                                <i class="fas fa-check-circle me-2"></i>
                                <?php echo htmlspecialchars($_GET['success']); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        <?php if (isset($_GET['error'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show mb-4 animate__animated animate__shakeX">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <?php echo htmlspecialchars($_GET['error']); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        <form id="employerProfileForm" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <!-- Profile Information Section -->
                            <div class="form-section animate__animated animate__fadeIn">
                                <h5><i class="fas fa-user me-2"></i> Profile Information</h5>
                                <div class="row g-3 mt-3">
                                    <div class="col-md-6">
                                        <label for="first_name" class="form-label">First Name</label>
                                        <input type="text" id="first_name" class="form-control" name="first_name" autocomplete="given-name" value="<?php echo htmlspecialchars($employer['user_first_name']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="last_name" class="form-label">Last Name</label>
                                        <input type="text" id="last_name" class="form-control" name="last_name" autocomplete="family-name" value="<?php echo htmlspecialchars($employer['user_last_name']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">Email Address</label>
                                        <input type="email" id="email" class="form-control" name="email" autocomplete="email" value="<?php echo htmlspecialchars($employer['user_email']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="contact_number" class="form-label">Contact Number</label>
                                        <input type="tel" id="contact_number" class="form-control auto-save" name="contact_number" autocomplete="tel" value="<?php echo htmlspecialchars($employer['contact_number'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="d-flex justify-content-end mt-4">
                                    <button type="submit" class="btn btn-primary px-4">
                                        <i class="fas fa-save me-2"></i> Save Changes
                                    </button>
                                </div>
                            </div>
                            <!-- Company Details Section -->
                            <div class="form-section animate__animated animate__fadeIn">
                                <h5><i class="fas fa-building me-2"></i> Company Details</h5>
                                <div class="row g-3 mt-3">
                                    <div class="col-md-6">
                                        <label for="company_name" class="form-label">Company Name</label>
                                        <input type="text" id="company_name" class="form-control auto-save" name="company_name" value="<?php echo htmlspecialchars($employer['company_name'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="job_title" class="form-label">Job Title</label>
                                        <input type="text" id="job_title" class="form-control auto-save" name="job_title" value="<?php echo htmlspecialchars($employer['job_title'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="company_website" class="form-label">Company Website</label>
                                        <input type="url" id="company_website" class="form-control auto-save" name="company_website" value="<?php echo htmlspecialchars($employer['company_website'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-12">
                                        <label for="company_description" class="form-label">Company Description</label>
                                        <textarea id="company_description" class="form-control auto-save" name="company_description" rows="3"><?php echo htmlspecialchars($employer['company_description'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                            </div>
                            <!-- Security Section -->
                            <div class="form-section animate__animated animate__fadeIn" id="security">
                                <h5><i class="fas fa-shield-alt me-2"></i> Security</h5>
                                <div class="row g-3 mt-3">
                                    <div class="col-md-12">
                                        <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                                            <i class="fas fa-key me-2"></i> Change Password
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <!-- Notification Preferences Section -->
                            <div class="form-section animate__animated animate__fadeIn" id="notifications">
                                <h5><i class="fas fa-bell me-2"></i> Notification Preferences</h5>
                                <div class="row g-3 mt-3">
                                    <div class="col-md-12">
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" id="emailNotifications" checked>
                                            <label class="form-check-label" for="emailNotifications">Email Notifications</label>
                                        </div>
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" id="applicationAlerts" checked>
                                            <label class="form-check-label" for="applicationAlerts">Job Application Alerts</label>
                                        </div>
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" id="marketingEmails">
                                            <label class="form-check-label" for="marketingEmails">Marketing Communications</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Danger Zone Section -->
                            <div class="form-section animate__animated animate__fadeIn danger-zone" id="danger">
                                <h5><i class="fas fa-exclamation-triangle me-2"></i> Danger Zone</h5>
                                <div class="row g-3 mt-3">
                                    <div class="col-md-12 mb-3">
                                        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                                            <i class="fas fa-trash me-2"></i> Delete Account
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="changePasswordModalLabel">
                        <i class="fas fa-key me-2"></i> Change Password
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="changePasswordForm">
                        <div class="mb-3">
                            <label for="currentPassword" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="currentPassword" required>
                        </div>
                        <div class="mb-3">
                            <label for="newPassword" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="newPassword" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirmPassword" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirmPassword" required>
                        </div>
                        <div class="alert alert-info small">
                            <i class="fas fa-info-circle me-2"></i> Password must be at least 8 characters long and contain a mix of letters, numbers, and symbols.
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="savePasswordBtn">
                        <i class="fas fa-save me-1"></i> Save Password
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Account Modal -->
    <div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-labelledby="deleteAccountModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger" id="deleteAccountModalLabel">
                        <i class="fas fa-exclamation-triangle me-2"></i> Delete Account
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete your account? This action cannot be undone.</p>
                    <p class="text-muted small">
                        This will permanently delete all your data including:
                    </p>
                    <ul class="text-muted small">
                        <li>Your profile information</li>
                        <li>All job postings</li>
                        <li>Application data</li>
                        <li>Company information</li>
                    </ul>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="confirmDelete">
                        <label class="form-check-label" for="confirmDelete">
                            I understand that this action is irreversible
                        </label>
                    </div>
                    <div class="mb-2">
                        <label for="deleteConfirmText" class="form-label">Type <b>DELETE</b> to confirm:</label>
                        <input type="text" class="form-control" id="deleteConfirmText" aria-label="Type DELETE to confirm">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn" disabled>
                        <i class="fas fa-trash me-2"></i> Delete Account
                    </button>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/employer_footer.php'; ?>
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        $(document).ready(function() {
            // Profile picture preview and upload
            $('#profilePictureInput').change(function() {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        $('#profilePicturePreview').attr('src', e.target.result);
                    };
                    reader.readAsDataURL(this.files[0]);

                    const formData = new FormData();
                    formData.append('csrf_token', $('input[name="csrf_token"]').val());
                    formData.append('profile_picture', this.files[0]);

                    $.ajax({
                        url: '../controllers/employer_update_profile.php',
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        dataType: 'json',
                        success: function(response) {
                            if (response.status === 'success' && response.profile_picture) {
                                $('#profilePicturePreview').attr('src', '../Uploads/' + response.profile_picture);
                                showAlert('Profile picture updated!', 'success');
                            } else {
                                showAlert('Error: ' + response.message, 'danger');
                            }
                        },
                        error: function(xhr) {
                            let message = 'An error occurred.';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                message = xhr.responseJSON.message;
                            }
                            showAlert(message, 'danger');
                        }
                    });
                }
            });

            // Company logo preview and upload
            $('#companyLogoInput').change(function() {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        $('#companyLogoPreview').attr('src', e.target.result);
                    };
                    reader.readAsDataURL(this.files[0]);

                    const formData = new FormData();
                    formData.append('csrf_token', $('input[name="csrf_token"]').val());
                    formData.append('company_logo', this.files[0]);

                    $.ajax({
                        url: '../controllers/employer_update_profile.php',
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        dataType: 'json',
                        success: function(response) {
                            if (response.status === 'success' && response.company_logo) {
                                $('#companyLogoPreview').attr('src', '../Uploads/' + response.company_logo);
                                showAlert('Company logo updated!', 'success');
                            } else {
                                showAlert('Error: ' + response.message, 'danger');
                            }
                        },
                        error: function(xhr) {
                            let message = 'An error occurred.';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                message = xhr.responseJSON.message;
                            }
                            showAlert(message, 'danger');
                        }
                    });
                }
            });

            // Auto-save for company/contact info fields on blur
            $('.auto-save').on('blur', function() {
                const fieldName = $(this).attr('name');
                const value = $(this).val();
                const formData = new FormData();
                formData.append('csrf_token', $('input[name="csrf_token"]').val());
                formData.append(fieldName, value);

                $.ajax({
                    url: '../controllers/employer_update_profile.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            showAlert('Saved!', 'success');
                        } else {
                            showAlert('Error: ' + response.message, 'danger');
                        }
                    },
                    error: function(xhr) {
                        let message = 'An error occurred.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }
                        showAlert(message, 'danger');
                    }
                });
            });

            // Employer profile form submission
            $('#employerProfileForm').submit(function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                formData.append('action', 'update_employer_profile');
                const submitBtn = $(this).find('button[type="submit"]');
                
                $.ajax({
                    url: '../controllers/employer_update_profile.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    beforeSend: function() {
                        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status"></span> Saving...');
                    },
                    success: function(response) {
                        if (response.status === 'success') {
                            showAlert('Profile updated successfully!', 'success');
                            updateProfileDisplay(response);
                        } else {
                            showAlert('Error: ' + response.message, 'danger');
                        }
                    },
                    error: function(xhr) {
                        let message = 'An error occurred.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }
                        showAlert(message, 'danger');
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false).html('<i class="fas fa-save me-2"></i>Save Changes');
                    }
                });
            });

            // Function to update profile display after successful updates
            function updateProfileDisplay(response) {
                const firstName = $('input[name="first_name"]').val();
                const lastName = $('input[name="last_name"]').val();
                const email = $('input[name="email"]').val();
                const companyName = $('input[name="company_name"]').val();
                const jobTitle = $('input[name="job_title"]').val();
                
                if (firstName && lastName) {
                    $('.profile-name').first().text(firstName + ' ' + lastName);
                }
                if (email) {
                    $('.profile-email').text(email);
                }
                if (companyName) {
                    $('.profile-company').first().html(companyName);
                }
                if (jobTitle) {
                    $('.profile-company').last().html(jobTitle);
                }
            }

            // Show alert function
            function showAlert(message, type) {
                const alertHtml = `
                    <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `;
                $('#profileAlertArea').html(alertHtml);
                setTimeout(function() {
                    $('#profileAlertArea .alert').alert('close');
                }, 5000);
            }

            // Auto-dismiss existing alerts
            setTimeout(function() {
                $('.alert').alert('close');
            }, 5000);

            // Notification toggles summary
            function updateNotifSummary() {
                let summary = [];
                if ($('#emailNotifications').is(':checked')) summary.push('Email');
                if ($('#applicationAlerts').is(':checked')) summary.push('Job Alerts');
                if ($('#marketingEmails').is(':checked')) summary.push('Marketing');
                $('#notifSummary').text(summary.length === 0 ? 'All notifications disabled' : summary.join(', ') + ' enabled');
            }
            $('#emailNotifications, #applicationAlerts, #marketingEmails').change(updateNotifSummary);
            updateNotifSummary();

            // Danger zone delete confirm
            function checkDeleteEnable() {
                const checked = $('#confirmDelete').is(':checked');
                const text = $('#deleteConfirmText').val();
                $('#confirmDeleteBtn').prop('disabled', !(checked && text === 'DELETE'));
            }
            $('#confirmDelete, #deleteConfirmText').on('input change', checkDeleteEnable);
        });
    </script>
</body>
</html>