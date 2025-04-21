<?php
include "../includes/sidebar.php";
require "../controllers/chart_query.php";
require "../controllers/admin_dashboard.php";
require "../auth/auth_check.php"; 

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

    <!-- Custom Styles -->
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

        .main-content {
            margin-left: 350px;
            margin-right: 150px;
            padding: 20px;
            transition: margin-left 0.3s ease;
        }

        .sidebar.collapsed + .main-content {
            margin-left: 200px;
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

        .nav-links {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: var(--sidebar-text);
            text-decoration: none;
            transition: var(--sidebar-transition);
            white-space: nowrap;
        }

        .nav-links:hover {
            background: var(--sidebar-hover-bg);
            color: var(--sidebar-active-text);
        }

        .nav-links.active {
            background: var(--sidebar-active-bg);
            color: var(--sidebar-active-text);
            font-weight: 500;
        }

        .nav-links i {
            margin-right: 10px;
            width: 24px;
            text-align: center;
            font-size: 1.1rem;
            transition: var(--sidebar-transition);
        }

        .sidebar.collapsed .nav-links i {
            margin-right: 0;
            font-size: 1.3rem;
        }

        .nav-links span {
            transition: opacity 0.2s ease, transform 0.2s ease;
        }

        .sidebar.collapsed .nav-links span {
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

        body.dark-mode .nav-links.active {
            background-color: #2a2a2a;
        }

        body.dark-mode .nav-links:hover {
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

         /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--background-light);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 10px;
        }


        .main-content {
            margin-left: 350px; 
            margin-right: 150px;
            padding: 20px;
            transition: margin-left 0.3s ease;
        }

        .sidebar.collapsed + .main-content {
            margin-left: 200px; /
        }

        /* Card Styles */
        .card {
            border: none;
            border-radius: 10px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px var(--shadow-color);
        }

        .card-header {
            background-color: var(--primary-color);
            color: white;
            padding: 15px;
            border-bottom: none;
        }

        .card-title {
            margin-bottom: 0;
            font-size: 1.25rem;
        }

        .card-body {
            padding: 20px;
        }

        /* Dark Mode Styles */
        .dark-mode {
            background-color: #121212;
            color: #ffffff;
        }

        .dark-mode .sidebar {
            background-color: #1e1e1e;
        }

        .dark-mode .card {
            background-color: #2d2d2d;
            color: #ffffff;
        }

        .dark-mode .card-header {
            background-color: #1e1e1e;
        }

        .dark-mode .list-group-item {
            background-color: #2d2d2d;
            color: #ffffff;
        }

        .dark-mode .alert-warning {
            background-color: #332701;
            color: #ffc107;
        }

        .dark-mode .alert-info {
            background-color: #002b36;
            color: #17a2b8;
        }
    </style>
</head>
<body class="fade-in">

    <!-- Sidebar -->
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
                        <a class="nav-links <?= $is_active ?>" href="<?= $link ?>" <?= $logout_attr ?>>
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


    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2">Welcome Aboard <?php echo htmlspecialchars($_SESSION['user_first_name']); ?>!</h1>
             <!-- 
            <button class="btn btn-outline-secondary" id="darkModeToggle" data-bs-toggle="tooltip" data-bs-placement="top" title="Toggle Dark Mode">
                <i class="fas fa-moon"></i> Dark Mode
            </button>
            -->
        </div>

        <!-- Quick Stats -->
        <div class="row mb-4 fade-in">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total Users</h5>
                        <p class="card-text display-4"><?php echo number_format($total_users); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title">Job Postings</h5>
                        <p class="card-text display-4"><?php echo number_format($total_jobs); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <h5 class="card-title">Applications</h5>
                        <p class="card-text display-4"><?php echo number_format($total_application); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-danger text-white">
                    <div class="card-body">
                        <h5 class="card-title">Forums</h5>
                        <p class="card-text display-4"><?php echo number_format($total_forum); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="row mb-4 fade-in">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">User Growth</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="userGrowthChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Job Posting Trends</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="jobTrendsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity and Notifications -->
        <div class="row fade-in">
            <!-- Recent Activity Section -->
            <div class="col-md-8">
                <div class="card shadow-lg">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Recent Activity</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <?php if (!empty($recentActivities)): ?>
                                <?php foreach ($recentActivities as $activity): ?>
                                    <li class="list-group-item d-flex align-items-center p-2 mb-2 shadow-sm hover-shadow">
                                        <?php 
                                            // Choose icon based on activity type
                                            $icon = '';
                                            switch ($activity['activity_type']) {
                                                case 'User registered':
                                                    $icon = 'fas fa-user-plus';  // User registration icon
                                                    break;
                                                case 'Student registered':
                                                    $icon = 'fas fa-user-graduate';  // Student registration icon
                                                    break;
                                                case 'Job posted by':
                                                    $icon = 'fas fa-briefcase';  // Job posting icon
                                                    break;
                                                case 'Application submitted':
                                                    $icon = 'fas fa-paper-plane';  // Application submitted icon
                                                    break;
                                                case 'Forum post created':
                                                    $icon = 'fas fa-comments';  // Forum post icon
                                                    break;
                                                case 'Forum comment added':
                                                    $icon = 'fas fa-comment-dots';  // Forum comment icon
                                                    break;
                                                default:
                                                    $icon = 'fas fa-cogs';  // Default icon for other activities
                                                    break;
                                            }
                                        ?>
                                        <span class="icon-container me-3">
                                            <i class="<?= $icon ?> fs-3 text-primary"></i> <!-- Larger, colored icons -->
                                        </span>
                                        <div class="activity-details">
                                            <?php if ($activity['activity_type'] === 'User registered' && !empty($activity['user_type'])): ?>
                                                <span class="badge bg-success"><?= htmlspecialchars($activity['user_type']) ?></span>
                                                <strong><?= htmlspecialchars($activity['entity_name']) ?></strong>
                                            <?php elseif ($activity['activity_type'] === 'Forum post created' || $activity['activity_type'] === 'Forum comment added'): ?>
                                                <span class="badge bg-info"><?= htmlspecialchars($activity['activity_type']) ?></span>
                                                <strong><?= htmlspecialchars($activity['entity_name']) ?></strong>
                                            <?php else: ?>
                                                <span class="badge bg-secondary"><?= htmlspecialchars($activity['activity_type']) ?></span>
                                                <strong><?= htmlspecialchars($activity['entity_name']) ?></strong>
                                            <?php endif; ?>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li class="list-group-item text-center">No recent activity</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>


            <!-- Notifications Section -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Notifications</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($notifications)): ?>
                            <?php foreach ($notifications as $notification): ?>
                                <div class="alert <?= ($notification['notification_type'] == 'warning') ? 'alert-warning' : 'alert-info' ?>" role="alert">
                                    <i class="fas <?= ($notification['notification_type'] == 'warning') ? 'fa-exclamation-triangle' : 'fa-info-circle' ?> me-2"></i>
                                    <?= htmlspecialchars($notification['message']); ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="alert alert-secondary" role="alert">
                                <i class="fas fa-bell-slash me-2"></i> No new notifications.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>    
    <!-- Load jQuery first -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Then load DataTables -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

    <!-- DataTables CSS (if needed) -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <!-- Custom Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../assests/logout.js"></script>
    <script src="../assests/sidebar_toggle.js"></script>
    <!-- SweetAlert2 CSS & JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
     
    <script>
        // Initialize tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map((tooltipTriggerEl) => new bootstrap.Tooltip(tooltipTriggerEl));

        // Chart.js - User Growth
        document.addEventListener("DOMContentLoaded", function() {
            const labels = <?php echo json_encode($labels); ?>;
            const activeData = <?php echo json_encode($activeData); ?>;
            const deletedData = <?php echo json_encode($deletedData); ?>;

            const ctx = document.getElementById('userGrowthChart').getContext('2d');
            const userGrowthChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Active Users & Students',
                            data: activeData,
                            borderColor: 'rgba(0, 123, 255, 1)', // Blue for active users
                            fill: false,
                            tension: 0.3
                        },
                        {
                            label: 'Archived Users & Students',
                            data: deletedData,
                            borderColor: 'rgba(255, 0, 0, 1)', // Red for deleted users
                            fill: false,
                            tension: 0.3
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'top' }
                    },
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });



            // Chart.js - Job Posting Trends
            const jobLabels = <?php echo json_encode($jobLabels); ?>;
            const jobCounts = <?php echo json_encode($jobCounts); ?>;

            const jobTrendsChart = new Chart(document.getElementById('jobTrendsChart'), {
                type: 'bar',
                data: {
                    labels: jobLabels,
                    datasets: [{
                        label: 'Job Postings',
                        data: jobCounts,
                        backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'top' },
                    },
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        });
    </script>

</body>
</html>