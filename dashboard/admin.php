<?php
require "../config/dbcon.php";
include "../includes/sidebar.php";
require "../controllers/chart_query.php";
require "../controllers/admin_dashboard.php";
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
    <link rel="stylesheet" href="../assests/admin.css">
</head>
<body class="fade-in">
    <!-- Main Content -->
    <main class="main-content">
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
                        <h5 class="card-title">Pending Applications</h5>
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
        <div class="card-header ">
            <h5 class="card-title mb-0">Recent Activity</h5>
        </div>
        <div class="card-body">
            <ul class="list-group list-group-flush">
                <?php if (!empty($recentActivities)): ?>
                    <?php foreach ($recentActivities as $activity): ?>
                        <li class="list-group-item d-flex align-items-center p-3 mb-3 border rounded shadow-sm hover-shadow">
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
    <script>
        // Dark Mode Toggle
        const darkModeToggle = document.getElementById('darkModeToggle');
        darkModeToggle.addEventListener('click', () => {
            document.body.classList.toggle('dark-mode');
            localStorage.setItem('darkMode', document.body.classList.contains('dark-mode'));
            darkModeToggle.innerHTML = document.body.classList.contains('dark-mode') ?
                '<i class="fas fa-sun"></i> Light Mode' : '<i class="fas fa-moon"></i> Dark Mode';
        });

        // Check for saved dark mode preference
        if (localStorage.getItem('darkMode') === 'true') {
            document.body.classList.add('dark-mode');
            darkModeToggle.innerHTML = '<i class="fas fa-sun"></i> Light Mode';
        }

        // Initialize tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map((tooltipTriggerEl) => new bootstrap.Tooltip(tooltipTriggerEl));

        // Chart.js - User Growth
        document.addEventListener("DOMContentLoaded", function() {
            const labels = <?php echo json_encode($labels); ?>;
            const data = <?php echo json_encode($data); ?>;

            const ctx = document.getElementById('userGrowthChart').getContext('2d');
            const userGrowthChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Users',
                        data: data,
                        borderColor: 'rgba(75, 192, 192, 1)',
                        fill: false,
                        tension: 0.3
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

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="../assests/logout.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</html>
