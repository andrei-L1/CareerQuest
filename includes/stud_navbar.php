<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// Check if both user_id and stud_id are not set simultaneously
if (isset($_SESSION['user_id']) && isset($_SESSION['stud_id'])) {
    echo "Error: Both user and student IDs are set. Only one should be set.";
    exit;
}

// Check if neither user_id nor stud_id is set
if (!isset($_SESSION['user_id']) && !isset($_SESSION['stud_id'])) {
    header("Location: ../index.php");
    exit;
}
// Include your external PDO database connection
require '../config/dbcon.php';

// Function to fetch student details from the database
function getStudentDetails($conn, $studentId) {
    $stmt = $conn->prepare("SELECT * FROM student WHERE stud_id = :student_id AND deleted_at IS NULL");
    $stmt->bindParam(':student_id', $studentId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Function to get unread notification count using PDO
function getUnreadNotificationCount($conn, $studentId) {
    $stmt = $conn->prepare("SELECT actor_id FROM actor WHERE entity_type = 'student' AND entity_id = :student_id");
    $stmt->bindParam(':student_id', $studentId, PDO::PARAM_INT);
    $stmt->execute();
    
    if ($stmt->rowCount() === 1) {
        $actor = $stmt->fetch(PDO::FETCH_ASSOC);
        $actorId = $actor['actor_id'];
        
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notification 
                              WHERE actor_id = :actor_id AND is_read = 0 AND deleted_at IS NULL");
        $stmt->bindParam(':actor_id', $actorId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchColumn();
    }
    return 0;
}

function getUnreadMessageCount($conn, $studentId) {
    // Get the actor_id of the student
    $stmt = $conn->prepare("SELECT actor_id FROM actor WHERE entity_type = 'student' AND entity_id = :studentId");
    $stmt->bindParam(':studentId', $studentId, PDO::PARAM_INT);
    $stmt->execute();
    
    if ($stmt->rowCount() === 1) {
        $actor = $stmt->fetch(PDO::FETCH_ASSOC);
        $actorId = $actor['actor_id'];
        
        // Count unread messages for this student (as receiver)
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM message 
                                WHERE receiver_id = :actor_id AND is_read = 0 AND deleted_at IS NULL");
        $stmt->bindParam(':actor_id', $actorId, PDO::PARAM_INT);
        $stmt->execute();
        
        // Return the unread message count
        return (int) $stmt->fetchColumn(); // Casting to int for consistency
    }
    return 0; // No unread messages if no actor_id found
}


// Get student ID from session
$studentId = $_SESSION['stud_id'];

// Fetch student data
$studentData = getStudentDetails($conn, $studentId);

// Set session variables if data is found
if ($studentData) {
    $_SESSION['stud_first_name'] = $studentData['stud_first_name'];
    $_SESSION['stud_last_name'] = $studentData['stud_last_name'];
    $_SESSION['profile_picture'] = $studentData['profile_picture'];
}

// Profile picture handling
$profilePicture = $studentData['profile_picture'] ?? '';
if (!empty($profilePicture) && file_exists('../Uploads/' . $profilePicture)) {
    $profile_pic = '../Uploads/' . $profilePicture;
}
 else {
    $name = trim(($studentData['stud_first_name'] ?? '') . ' ' . ($studentData['stud_last_name'] ?? ''));
    $profile_pic = 'https://ui-avatars.com/api/?name=' . urlencode($name ?: 'Student') . '&background=457B9D&color=fff&rounded=true&size=128';
}

// Get unread notification count
$notification_count = getUnreadNotificationCount($conn, $studentId);
$message_count = getUnreadMessageCount($conn, $studentId);

// Get current page
$currentPage = basename($_SERVER['PHP_SELF']);
ob_start(); 
// Navigation links
$nav_links = [
    "Dashboard" => "../dashboard/student.php",
    "Jobs" => "../dashboard/student_job.php",
    "Applications" => "student_applications.php",
    "Notifications" => "../dashboard/notifications.php",
    "Forum" => "forums.php",
    "Messages" => "messages.php"
];

// Get student name safely
$firstName = htmlspecialchars($studentData['stud_first_name'] ?? 'Student');
$lastName = htmlspecialchars($studentData['stud_last_name'] ?? '');


$resumeFile = $studentData['resume_file'] ?? '';

// Define the base directory for uploads
$uploadsDir = '../Uploads/';

// Check if the resume file exists
if (!empty($resumeFile) && file_exists($uploadsDir . $resumeFile)) {
    $resumeLink = $uploadsDir . $resumeFile; // Direct link to the file for viewing
} else {
    $resumeLink = ''; // No resume available
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1A4D8F;
            --secondary-color: #3A7BD5;
            --accent-color: #4ECDC4;
            --text-dark: #2D3748;
            --text-light: #F8F9FA;
            --light-bg: #F7FAFC;
            --danger-color: #E53E3E;
            --success-color: #38A169;
            --warning-color: #DD6B20;
            --hover-color: rgba(58, 123, 213, 0.1);
            --transition-speed: 0.3s;
            --nav-height: 70px;
            --border-radius: 8px;
            --box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }
        
        body {
            font-family: 'Poppins', sans-serif !important;
            background-color: var(--light-bg);
        }
        
        .navbar {
            background-color: white !important;
            box-shadow: var(--box-shadow);
            padding: 0.5rem 2rem;
            min-height: var(--nav-height);
            transition: all var(--transition-speed) ease;
            z-index: 1030;
        }

        .navbar.scrolled {
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.12);
        }

        .navbar-brand {
            font-weight: 700;
            color: var(--primary-color) !important;
            font-size: 1.6rem;
            display: flex;
            align-items: center;
            transition: all var(--transition-speed) ease;
            letter-spacing: -0.5px;
        }

        .navbar-brand i {
            margin-right: 10px;
            color: var(--accent-color);
            font-size: 1.5em;
        }

        .navbar-brand:hover {
            color: var(--secondary-color) !important;
        }

        .nav-link {
            color: var(--text-dark) !important;
            font-weight: 500;
            margin: 0 0.5rem;
            padding: 0.5rem 1rem !important;
            border-radius: var(--border-radius);
            transition: all var(--transition-speed) ease;
            position: relative;
            display: flex;
            align-items: center;
            font-size: 0.95rem;
        }

        .nav-link i {
            margin-right: 8px;
            font-size: 1.1em;
        }

        .nav-link:not(.active):hover {
            background-color: var(--hover-color);
            color: var(--secondary-color) !important;
        }

        .nav-link.active {
            font-weight: 600;
            color: white !important;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            box-shadow: 0 4px 12px rgba(26, 77, 143, 0.25);
        }

        .nav-link.active i {
            color: white !important;
        }

        .nav-link.active:after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 0;
            border-left: 8px solid transparent;
            border-right: 8px solid transparent;
            border-top: 8px solid var(--primary-color);
        }

        .dropdown-menu {
            border: none;
            box-shadow: var(--box-shadow);
            border-radius: var(--border-radius);
            padding: 0.5rem;
            margin-top: 0.8rem;
            border: 1px solid rgba(0, 0, 0, 0.05);
            animation: fadeIn 0.2s ease-in-out;
            min-width: 220px;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .dropdown-item {
            border-radius: 6px;
            padding: 0.65rem 1rem;
            transition: all var(--transition-speed) ease;
            display: flex;
            align-items: center;
            font-size: 0.9rem;
        }

        .dropdown-item i {
            width: 20px;
            text-align: center;
            margin-right: 12px;
            color: var(--secondary-color);
        }

        .dropdown-item:hover {
            background-color: var(--hover-color);
            color: var(--secondary-color);
            transform: translateX(3px);
        }

        .dropdown-header {
            font-weight: 600;
            color: var(--primary-color);
            font-size: 0.85rem;
            padding: 0.5rem 1rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .logout-btn {
            background: linear-gradient(135deg, var(--danger-color), #C53030);
            color: white !important;
            border-radius: var(--border-radius);
            padding: 0.6rem 1.5rem !important;
            font-weight: 500;
            transition: all var(--transition-speed) ease;
            display: flex;
            align-items: center;
            box-shadow: 0 4px 10px rgba(229, 62, 62, 0.25);
            margin-left: 1rem;
            border: none;
        }

        .logout-btn i {
            margin-right: 8px;
            color: white !important;
        }

        .logout-btn:hover {
            background: linear-gradient(135deg, #C53030, var(--danger-color));
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(229, 62, 62, 0.35);
            color: white !important;
        }

        .navbar-toggler {
            border: none;
            padding: 0.5rem;
        }

        .navbar-toggler:focus {
            box-shadow: none;
        }

        .navbar-toggler-icon {
            background-image: none;
            height: 2px;
            width: 24px;
            position: relative;
            transition: all var(--transition-speed) ease;
            background-color: var(--primary-color);
        }

        .navbar-toggler-icon:before,
        .navbar-toggler-icon:after {
            content: '';
            position: absolute;
            height: 2px;
            width: 24px;
            background-color: var(--primary-color);
            left: 0;
            transition: all var(--transition-speed) ease;
        }

        .navbar-toggler-icon:before {
            top: -8px;
        }

        .navbar-toggler-icon:after {
            top: 8px;
        }

        .navbar-toggler[aria-expanded="true"] .navbar-toggler-icon {
            background-color: transparent;
        }

        .navbar-toggler[aria-expanded="true"] .navbar-toggler-icon:before {
            transform: rotate(45deg);
            top: 0;
        }

        .navbar-toggler[aria-expanded="true"] .navbar-toggler-icon:after {
            transform: rotate(-45deg);
            top: 0;
        }

        /* Notification badge */
        .notification-badge {
            font-size: 0.7rem;
            padding: 0.25em 0.6em;
            margin-left: 6px;
            vertical-align: middle;
            font-weight: 600;
        }

        /* Profile picture */
        .profile-container {
            position: relative;
            margin-right: 10px;
        }

        .profile-pic {
            width: 38px;
            height: 38px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .profile-pic:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .online-status {
            position: absolute;
            bottom: 2px;
            right: 2px;
            width: 10px;
            height: 10px;
            background-color: var(--success-color);
            border-radius: 50%;
            border: 2px solid white;
        }

        .user-name {
            font-weight: 500;
            color: var(--text-dark);
            margin-left: 8px;
        }

        /* New badge for new features */
        .new-badge {
            background-color: var(--accent-color);
            color: white;
            font-size: 0.6rem;
            padding: 0.2em 0.5em;
            border-radius: 10px;
            margin-left: 8px;
            vertical-align: middle;
            font-weight: 600;
        }

        /* Responsive adjustments */
        @media (max-width: 991.98px) {
            .navbar {
                padding: 0.5rem 1rem;
            }
            
            .navbar-collapse {
                padding: 1rem 0;
                background-color: white;
                border-radius: var(--border-radius);
                box-shadow: var(--box-shadow);
                margin-top: 1rem;
                z-index: 1040;
            }
            
            .nav-link {
                margin: 0.3rem 0;
                padding: 0.8rem 1.5rem !important;
            }
            
            .nav-link.active:after {
                display: none;
            }
            
            .logout-btn {
                margin-top: 0.5rem;
                width: calc(100% - 3rem);
                justify-content: center;
                margin-left: 1.5rem;
                margin-bottom: 0.5rem;
            }

            .dropdown-menu {
                margin-top: 0;
                box-shadow: none;
                border: none;
                animation: none;
                position: static !important;
                transform: none !important;
                background-color: var(--light-bg);
            }
        }

        /* Animation for notification bell */
        @keyframes ring {
            0% { transform: rotate(0deg); }
            25% { transform: rotate(15deg); }
            50% { transform: rotate(-15deg); }
            75% { transform: rotate(10deg); }
            100% { transform: rotate(0deg); }
        }

        .has-notifications {
            position: relative;
        }

        .has-notifications i {
            animation: ring 0.5s ease-in-out;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="../dashboard/student.php">
            CareerQuest
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav mx-auto mb-2 mb-lg-0"> <!-- Centered navigation -->
                <?php foreach ($nav_links as $name => $url): ?>
                    <li class="nav-item mx-1"> <!-- Added horizontal spacing -->
                        <a class="nav-link position-relative px-3 py-2 <?= ($currentPage == basename($url)) ? 'active' : '' ?> <?= ($name === 'Notifications' && $notification_count > 0) ? 'has-notifications' : '' ?>" href="<?= $url; ?>">
                            <span><?= $name; ?></span>
                            <?php if ($name === "Notifications" && $notification_count > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    <?= $notification_count ?>
                                </span>
                            <?php endif; ?>

                            <?php if ($name === "Messages" && $message_count > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    <?= $message_count ?>
                                </span>
                            <?php endif; ?>

                            <?php if ($name === "Forum"): ?>
                              <!--  <span class="badge bg-primary ms-1">New</span> -->
                            <?php endif; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <!-- User dropdown moved to the right -->
            <div class="d-flex align-items-center">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="position-relative me-2">
                            <img src="<?= $profile_pic ?>" class="rounded-circle" width="36" height="36" alt="Profile">
                            <span class="position-absolute bottom-0 end-0 p-1 bg-success border border-light rounded-circle"></span>
                        </div>
                        <span class="d-none d-lg-inline"><?= $_SESSION['stud_first_name'] ?? 'Student' ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><h6 class="dropdown-header"><?= htmlspecialchars($firstName . ' ' . $lastName) ?></h6></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../dashboard/student_profile.php">My Profile</a></li>
                        <?php if ($resumeLink): ?>
                            <li><a class="dropdown-item" href="<?= $resumeLink ?>" target="_blank">My Resume</a></li>
                        <?php else: ?>
                            <li><a class="dropdown-item" href="javascript:void(0);">No Resume Uploaded</a></li>
                        <?php endif; ?>
                        <li><a class="dropdown-item" href="../dashboard/student_account_settings.php">Account Settings</a></li>
                        <li><a  class="dropdown-item" href="student_saved_jobs.php">Saved Jobs</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="../auth/logout.php">Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</nav>

<!-- Keep this ONLY -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Initialize all dropdown functionality
    document.addEventListener('DOMContentLoaded', function() {
        // Handle dropdown toggle
        const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
        
        dropdownToggles.forEach(toggle => {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                const dropdownMenu = this.nextElementSibling;
                
                // Close all other open dropdowns first
                document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                    if (menu !== dropdownMenu) {
                        menu.classList.remove('show');
                    }
                });
                
                // Toggle current dropdown
                dropdownMenu.classList.toggle('show');
                
                // Update aria-expanded attribute
                const isExpanded = this.getAttribute('aria-expanded') === 'true';
                this.setAttribute('aria-expanded', !isExpanded);
            });
        });

        // Close dropdowns when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.dropdown')) {
                document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                    menu.classList.remove('show');
                });
                document.querySelectorAll('.dropdown-toggle').forEach(toggle => {
                    toggle.setAttribute('aria-expanded', 'false');
                });
            }
        });

        // Scroll effect for navbar
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            navbar.classList.toggle('scrolled', window.scrollY > 10);
        });

        // Animation for notification bell if there are unread notifications
        <?php if ($notification_count > 0): ?>
        const notificationBell = document.querySelector('.has-notifications i');
        setInterval(() => {
            notificationBell.style.animation = 'none';
            setTimeout(() => {
                notificationBell.style.animation = 'ring 0.5s ease-in-out';
            }, 50);
        }, 8000);
        <?php endif; ?>
    });
</script>


<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Custom logout confirmation
document.addEventListener('DOMContentLoaded', function() {
    // Find all logout links/buttons
    const logoutLinks = document.querySelectorAll('[href="../auth/logout.php"], .logout-btn');
    
    logoutLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            Swal.fire({
                title: 'Logout Confirmation',
                text: 'Are you sure you want to log out?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#1A4D8F;', 
                cancelButtonColor: '#6c757d', 
                confirmButtonText: 'Yes, Logout',
                cancelButtonText: 'Cancel',
                background: 'white',
                customClass: {
                    title: 'text-dark',
                    confirmButton: 'btn btn-primary px-4 py-2 me-3', 
                    cancelButton: 'btn btn-outline-secondary px-4 py-2', 
                    actions: 'gap-3'
                },
                buttonsStyling: false,
                reverseButtons: true,
                showClass: {
                    popup: 'animate__animated animate__fadeInDown animate__faster'
                },
                hideClass: {
                    popup: 'animate__animated animate__fadeOutUp animate__faster'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = "../auth/logout.php";
                }
            });
        });
    });
});
</script>

</body>
</html>