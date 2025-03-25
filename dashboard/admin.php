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
    <link rel="stylesheet" href="../assests/sidebar.css">
    <!-- Custom Styles -->
    <style>

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
        :root {
            --primary-color: #0A2647; /* Navy Blue */
            --secondary-color: #2C7865; /* Teal */
            --accent-color: #FFD700; /* Gold */
            --background-light: #F5F5F5; /* Light Gray */
            --text-dark: #333333; /* Dark Gray */
            --shadow-color: rgba(0, 0, 0, 0.1);
        }

        body {
            background-color: var(--background-light);
            color: var(--text-dark);
            font-family: 'Poppins', sans-serif;
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
        <div class="sidebar-sticky pt-3">
            <ul class="nav flex-column">
                <?php foreach ($sidebar_menu as $item): ?>
                    <?php 
                        // Check if the menu item matches the current page
                        $is_active = ($current_page == basename($item[2])) ? 'active' : ''; 
                    ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $is_active ?>" href="<?= $item[2] ?>" <?= $item[0] === "Logout" ? 'onclick="confirmLogout(event)"' : '' ?>>
                            <i class="<?= $item[1] ?> me-2"></i>
                            <span><?= $item[0] ?></span> 
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </nav>


    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2">Welcome Aboard <?php echo htmlspecialchars($_SESSION['user_first_name']); ?>!</h1>
            <button class="btn btn-outline-secondary" id="darkModeToggle" data-bs-toggle="tooltip" data-bs-placement="top" title="Toggle Dark Mode">
                <i class="fas fa-moon"></i> Dark Mode
            </button>
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
                                    <li class="list-group-item d-flex align-items-center p-2 mb-2  shadow-sm hover-shadow">
                                        <?php 
                                            // Choose icon based on activity type
                                            $icon = '';
                                            switch ($activity['activity_type']) {
                                                case 'User registered':
                                                    $icon = 'fas fa-user-plus';  // User registration icon
                                                    break;
                                                case 'Login':
                                                    $icon = 'fas fa-sign-in-alt';  // Login icon
                                                    break;
                                                case 'Logout':
                                                    $icon = 'fas fa-sign-out-alt'; // Logout icon
                                                    break;
                                                case 'Password changed':
                                                    $icon = 'fas fa-key';  // Password change icon
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