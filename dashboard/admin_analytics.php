<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require "../config/dbcon.php"; 
require "../auth/auth_check.php"; 
include "../includes/sidebar.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // Get user info
    $stmt = $conn->prepare("
        SELECT user.user_first_name, user.user_last_name, role.role_title
        FROM user
        JOIN role ON user.role_id = role.role_id
        WHERE user.user_id = :user_id
    ");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception("User not found.");
    }

    $full_name = htmlspecialchars($user['user_first_name'] . " " . $user['user_last_name']);
    $role_title = htmlspecialchars($user['role_title']);

    // Get analytics data
    $analytics = [];
    
    // Total Users Count
    $stmt = $conn->query("SELECT COUNT(*) as total_users FROM user WHERE deleted_at IS NULL");
    $analytics['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_users'];
    
    // Total Students Count
    $stmt = $conn->query("SELECT COUNT(*) as total_students FROM student WHERE deleted_at IS NULL");
    $analytics['total_students'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_students'];
    
    // Total Employers Count
    $stmt = $conn->query("SELECT COUNT(*) as total_employers FROM employer WHERE deleted_at IS NULL");
    $analytics['total_employers'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_employers'];
    
    // Total Professionals Count
    $stmt = $conn->query("SELECT COUNT(*) as total_professionals FROM professional WHERE deleted_at IS NULL");
    $analytics['total_professionals'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_professionals'];
    
    // Total Job Postings Count
    $stmt = $conn->query("SELECT COUNT(*) as total_jobs FROM job_posting WHERE deleted_at IS NULL");
    $analytics['total_jobs'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_jobs'];
    
    // Active Job Postings Count
    $stmt = $conn->query("SELECT COUNT(*) as active_jobs FROM job_posting WHERE deleted_at IS NULL AND (expires_at IS NULL OR expires_at > NOW())");
    $analytics['active_jobs'] = $stmt->fetch(PDO::FETCH_ASSOC)['active_jobs'];
    
    // Total Applications Count
    $stmt = $conn->query("SELECT COUNT(*) as total_applications FROM application_tracking WHERE deleted_at IS NULL");
    $analytics['total_applications'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_applications'];
    
    // Recent Job Postings (last 30 days)
    $stmt = $conn->query("SELECT COUNT(*) as recent_jobs FROM job_posting WHERE posted_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND deleted_at IS NULL");
    $analytics['recent_jobs'] = $stmt->fetch(PDO::FETCH_ASSOC)['recent_jobs'];
    
    // Job Postings by Status
    $stmt = $conn->query("SELECT moderation_status, COUNT(*) as count FROM job_posting WHERE deleted_at IS NULL GROUP BY moderation_status");
    $analytics['job_statuses'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Applications by Status
    $stmt = $conn->query("SELECT application_status, COUNT(*) as count FROM application_tracking WHERE deleted_at IS NULL GROUP BY application_status");
    $analytics['application_statuses'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Popular Skills
    $stmt = $conn->query("
        SELECT s.skill_name, COUNT(js.skill_id) as demand 
        FROM job_skill js
        JOIN skill_masterlist s ON js.skill_id = s.skill_id
        WHERE js.deleted_at IS NULL
        GROUP BY js.skill_id
        ORDER BY demand DESC
        LIMIT 5
    ");
    $analytics['popular_skills'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Student Skills
    $stmt = $conn->query("
        SELECT s.skill_name, COUNT(ss.skill_id) as student_count 
        FROM stud_skill ss
        JOIN skill_masterlist s ON ss.skill_id = s.skill_id
        WHERE ss.deleted_at IS NULL
        GROUP BY ss.skill_id
        ORDER BY student_count DESC
        LIMIT 5
    ");
    $analytics['student_skills'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Forum Activity
    $stmt = $conn->query("SELECT COUNT(*) as total_posts FROM forum_post WHERE deleted_at IS NULL");
    $analytics['total_posts'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_posts'];
    
    $stmt = $conn->query("SELECT COUNT(*) as total_comments FROM forum_comment WHERE deleted_at IS NULL");
    $analytics['total_comments'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_comments'];

} catch (Exception $e) {
    error_log("Dashboard Error: " . $e->getMessage());
    header("Location: ../auth/logout.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
         :root {
            --primary-color: #0A2647; /* Navy Blue */
            --secondary-color: #2C7865; /* Teal */
            --accent-color: #FFD700; /* Gold */
            --background-light: #F5F5F5; /* Light Gray */
            --text-dark: #333333; /* Dark Gray */
            --shadow-color: rgba(0, 0, 0, 0.1);
            --font-family: 'Poppins', sans-serif;


            --sidebar-width: 280px;
            --sidebar-collapsed-width: 80px;
            --sidebar-bg: #2c3e50;
            --sidebar-active-bg: #34495e;
            --sidebar-text: #ecf0f1;
            --sidebar-active-text: #3498db;
            --sidebar-hover-bg: #34495e;
            --sidebar-transition: all 0.3s ease;
            --main-content-padding: 20px;
        }

        body {
            background-color: var(--background-light);
            color: var(--text-dark);
            /* font-family: var(--font-family);*/
            transition: background-color 0.3s ease, color 0.3s ease;

            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            overflow-x: hidden;
            transition: var(--sidebar-transition);
        }

        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            width: var(--sidebar-width);
            background: var(--sidebar-bg);
            color: var(--sidebar-text);
            transition: var(--sidebar-transition);
            z-index: 1000;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
        }

        .sidebar.collapsed {
            width: var(--sidebar-collapsed-width);
        }

        .sidebar-toggle {
            padding: 15px;
            text-align: right;
            cursor: pointer;
            color: var(--sidebar-text);
            transition: var(--sidebar-transition);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-toggle:hover {
            color: var(--sidebar-active-text);
        }

        .sidebar.collapsed .sidebar-toggle {
            text-align: center;
            padding: 15px 0;
        }

        .sidebar-nav {
            flex: 1;
            overflow-y: auto;
            padding: 10px 0;
        }

        .sidebar-nav::-webkit-scrollbar {
            width: 5px;
        }

        .sidebar-nav::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 5px;
        }

        .nav-item {
            position: relative;
            margin: 5px 10px;
            border-radius: 5px;
            overflow: hidden;
            transition: var(--sidebar-transition);
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: var(--sidebar-text);
            text-decoration: none;
            transition: var(--sidebar-transition);
            white-space: nowrap;
        }

        .nav-link:hover {
            background: var(--sidebar-hover-bg);
            color: var(--sidebar-active-text);
        }

        .nav-link.active {
            background: var(--sidebar-active-bg);
            color: var(--sidebar-active-text);
            font-weight: 500;
        }

        .nav-link i {
            margin-right: 10px;
            width: 24px;
            text-align: center;
            font-size: 1.1rem;
            transition: var(--sidebar-transition);
        }

        .sidebar.collapsed .nav-link i {
            margin-right: 0;
            font-size: 1.3rem;
        }

        .nav-link span {
            transition: opacity 0.2s ease, transform 0.2s ease;
        }

        .sidebar.collapsed .nav-link span {
            opacity: 0;
            position: absolute;
            left: 100%;
            top: 50%;
            transform: translateY(-50%);
            background: var(--sidebar-bg);
            padding: 5px 15px;
            border-radius: 4px;
            white-space: nowrap;
            pointer-events: none;
            box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.2);
            z-index: 1001;
        }

        .sidebar.collapsed .nav-item:hover span {
            opacity: 1;
            transform: translate(10px, -50%);
        }

        /* Main Content */
        .main-content {
            margin-left: 350px;
            margin-right: 150px;
            padding: 20px;
            transition: margin-left 0.3s ease;
        }

        .sidebar.collapsed + .main-content {
            margin-left: 200px;
        }

        /* Dark Mode Toggle */
        .dark-mode-toggle {
            padding: 15px;
            display: flex;
            justify-content: center;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .toggle-btn {
            background: none;
            border: none;
            color: var(--sidebar-text);
            cursor: pointer;
            font-size: 1.2rem;
            transition: var(--sidebar-transition);
        }

        .toggle-btn:hover {
            color: var(--sidebar-active-text);
        }

        /* Dark Mode Styles */
        body.dark-mode {
            background-color: #121212;
            color: #ffffff;
        }

        body.dark-mode .sidebar {
            background-color: #1a1a1a;
        }

        body.dark-mode .nav-link.active {
            background-color: #2a2a2a;
        }

        body.dark-mode .nav-link:hover {
            background-color: #2a2a2a;
        }

        /* Logout button styling */
        .logout-link {
            color: #e74c3c;
            transition: color 0.2s;
        }

        .logout-link:hover {
            color: #c0392b;
            text-decoration: none;
        }

        body.dark-mode .logout-link {
            color: #ff6b6b;
        }

        body.dark-mode .logout-link:hover {
            color: #ff5252;
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                width: var(--sidebar-width);
            }

            .sidebar.collapsed {
                transform: translateX(0);
                width: var(--sidebar-collapsed-width);
            }

            .main-content {
                margin-left: 0;
            }
        }

        .sidebar.collapsed + .main-content {
            margin-left: 200px;
        }

        /* Dashboard specific styles */
        .stat-card {
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            height: 100%;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card .icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }

        .stat-card .count {
            font-size: 2rem;
            font-weight: bold;
        }

        .stat-card .title {
            font-size: 1rem;
            color: #6c757d;
        }

        .chart-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .chart-title {
            font-size: 1.2rem;
            margin-bottom: 15px;
            color: var(--primary-color);
            font-weight: 600;
        }

        .skill-badge {
            margin-right: 5px;
            margin-bottom: 5px;
            font-weight: normal;
        }

        .badge-count {
            background-color: #f8f9fa;
            color: #495057;
            margin-left: 5px;
        }

        /* Dark mode styles */
        body.dark-mode {
            background-color: #121212;
            color: #ffffff;
        }

        body.dark-mode .stat-card {
            background-color: #1e1e1e;
            color: #ffffff;
        }

        body.dark-mode .chart-container {
            background-color: #1e1e1e;
            color: #ffffff;
        }

        body.dark-mode .card {
            background-color: #1e1e1e;
            color: #ffffff;
        }

        body.dark-mode .table {
            color: #ffffff;
        }

        body.dark-mode .skill-badge {
            background-color: #2c3e50;
            color: #ffffff;
        }

        body.dark-mode .badge-count {
            background-color: #495057;
            color: #ffffff;
        }
    </style>
</head>
<body>
    <nav class="sidebar collapsed" id="sidebar">
        <div class="sidebar-toggle" id="sidebarToggle">
            <i class="fas fa-chevron-right"></i>
        </div>
        <div class="sidebar-nav">
            <ul class="nav flex-column">
                <?php foreach ($sidebar_menu as $item): ?>
                    <?php 
                        $is_active = ($current_page == basename($item[2])) ? 'active' : '';
                        $icon = $item[1];
                        $title = $item[0];
                        $link = $item[2];
                        $logout_attr = ($title === "Logout") ? 'onclick="confirmLogout(event)"' : '';
                    ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $is_active ?>" href="<?= $link ?>" <?= $logout_attr ?>>
                            <i class="<?= $icon ?>"></i>
                            <span><?= $title ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="dark-mode-toggle">
            <button class="toggle-btn" id="darkModeToggle">
                <i class="fas fa-moon"></i>
            </button>
        </div>
    </nav>

    <main class="main-content">
        <div class="container-fluid">
            <div class="row mb-4">
                <div class="col-12">
                    <h1 class="display-4">Welcome, <?php echo $full_name; ?>!</h1>
                    <p class="lead">Your Role: <span class="badge bg-primary"><?php echo $role_title; ?></span></p>
                </div>
            </div>

            <!-- Summary Stats Row -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stat-card bg-white">
                        <div class="icon text-primary">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="count"><?php echo number_format($analytics['total_users']); ?></div>
                        <div class="title">Total Users</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card bg-white">
                        <div class="icon text-success">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <div class="count"><?php echo number_format($analytics['total_students']); ?></div>
                        <div class="title">Students</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card bg-white">
                        <div class="icon text-info">
                            <i class="fas fa-building"></i>
                        </div>
                        <div class="count"><?php echo number_format($analytics['total_employers']); ?></div>
                        <div class="title">Employers</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card bg-white">
                        <div class="icon text-warning">
                            <i class="fas fa-briefcase"></i>
                        </div>
                        <div class="count"><?php echo number_format($analytics['total_professionals']); ?></div>
                        <div class="title">Professionals</div>
                    </div>
                </div>
            </div>

            <!-- Job Stats Row -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="stat-card bg-white">
                        <div class="icon text-danger">
                            <i class="fas fa-bullhorn"></i>
                        </div>
                        <div class="count"><?php echo number_format($analytics['total_jobs']); ?></div>
                        <div class="title">Total Job Postings</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card bg-white">
                        <div class="icon text-primary">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="count"><?php echo number_format($analytics['active_jobs']); ?></div>
                        <div class="title">Active Jobs</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card bg-white">
                        <div class="icon text-success">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div class="count"><?php echo number_format($analytics['total_applications']); ?></div>
                        <div class="title">Job Applications</div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="chart-container">
                        <div class="chart-title">Job Postings by Status</div>
                        <canvas id="jobStatusChart"></canvas>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="chart-container">
                        <div class="chart-title">Applications by Status</div>
                        <canvas id="applicationStatusChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Skills and Forum Activity Row -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Top In-Demand Skills</h5>
                        </div>
                        <div class="card-body">
                            <?php foreach ($analytics['popular_skills'] as $skill): ?>
                                <span class="badge bg-secondary skill-badge">
                                    <?php echo htmlspecialchars($skill['skill_name']); ?>
                                    <span class="badge bg-light text-dark badge-count"><?php echo $skill['demand']; ?></span>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">Top Student Skills</h5>
                        </div>
                        <div class="card-body">
                            <?php foreach ($analytics['student_skills'] as $skill): ?>
                                <span class="badge bg-secondary skill-badge">
                                    <?php echo htmlspecialchars($skill['skill_name']); ?>
                                    <span class="badge bg-light text-dark badge-count"><?php echo $skill['student_count']; ?></span>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Forum Activity Row -->
            <div class="row">
                <div class="col-md-6">
                    <div class="stat-card bg-white">
                        <div class="icon text-info">
                            <i class="fas fa-comments"></i>
                        </div>
                        <div class="count"><?php echo number_format($analytics['total_posts']); ?></div>
                        <div class="title">Forum Posts</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="stat-card bg-white">
                        <div class="icon text-warning">
                            <i class="fas fa-reply"></i>
                        </div>
                        <div class="count"><?php echo number_format($analytics['total_comments']); ?></div>
                        <div class="title">Forum Comments</div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assests/sidebar_toggle.js" defer></script>
    <script>
        // Initialize charts when the page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Job Status Chart
            const jobStatusCtx = document.getElementById('jobStatusChart').getContext('2d');
            const jobStatusChart = new Chart(jobStatusCtx, {
                type: 'doughnut',
                data: {
                    labels: <?php echo json_encode(array_column($analytics['job_statuses'], 'moderation_status')); ?>,
                    datasets: [{
                        data: <?php echo json_encode(array_column($analytics['job_statuses'], 'count')); ?>,
                        backgroundColor: [
                            '#FF6384', // Pending
                            '#36A2EB', // Approved
                            '#FFCE56', // Rejected
                            '#4BC0C0'  // Paused
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        }
                    }
                }
            });

            // Application Status Chart
            const appStatusCtx = document.getElementById('applicationStatusChart').getContext('2d');
            const appStatusChart = new Chart(appStatusCtx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode(array_column($analytics['application_statuses'], 'application_status')); ?>,
                    datasets: [{
                        label: 'Applications',
                        data: <?php echo json_encode(array_column($analytics['application_statuses'], 'count')); ?>,
                        backgroundColor: '#4BC0C0',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });

            // Dark mode toggle
            const darkModeToggle = document.getElementById('darkModeToggle');
            darkModeToggle.addEventListener('click', function() {
                document.body.classList.toggle('dark-mode');
                // Save preference to localStorage
                const isDarkMode = document.body.classList.contains('dark-mode');
                localStorage.setItem('darkMode', isDarkMode);
            });

            // Check for saved dark mode preference
            if (localStorage.getItem('darkMode') === 'true') {
                document.body.classList.add('dark-mode');
            }

            // SweetAlert for logout confirmation
            function confirmLogout(e) {
                e.preventDefault();
                const logoutUrl = e.currentTarget.getAttribute('href');
                
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You will be logged out of the system.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, logout!',
                    background: document.body.classList.contains('dark-mode') ? '#1a1a1a' : '#ffffff',
                    color: document.body.classList.contains('dark-mode') ? '#ffffff' : '#333333'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = logoutUrl;
                    }
                });
            }
        });
    </script>
</body>
</html>