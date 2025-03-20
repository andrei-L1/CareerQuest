<?php
include "../includes/sidebar.php";
require "../controllers/admin_dashboard.php";
require "../controllers/admin_user_management.php";
require "../controllers/admin_job_controller.php";
require '../auth/auth_check.php'; 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Posting Management</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- DataTables CSS (Optional, if you still need it for other tables) -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
    <link rel="stylesheet" href="../assests/sidebar.css">
    
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
        }

        body {
            background-color: var(--background-light);
            color: var(--text-dark);
            font-family: var(--font-family);
            transition: background-color 0.3s ease, color 0.3s ease;
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

        .card {
            border: none;
            border-radius: 10px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            overflow: hidden;
            background: linear-gradient(145deg, var(--background-light), #ffffff);
            box-shadow: 0 4px 6px var(--shadow-color);
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
        .dark-mode .card-body{
            background-color: #1e1e1e;
        }

        .dark-mode td{
            background-color:rgba(56, 56, 56, 0.91);
            color: white;
        }
        .dark-mode th{
            background-color:rgb(30, 30, 30);
            color: white;
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

        .dark-mode ::-webkit-scrollbar-track {
            background: #2d2d2d;
        }

        .dark-mode ::-webkit-scrollbar-thumb {
            background: var(--secondary-color);
        }
          /* Loading Spinner */
          .loading-spinner {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1000;
        }

        .loading-spinner.active {
            display: block;
        }

        /* SweetAlert2 Customization */
        .swal2-popup {
            font-family: var(--font-family);
            border-radius: 10px;
        }

        .swal2-confirm {
            background-color: var(--primary-color) !important;
        }

        .swal2-cancel {
            background-color: var(--secondary-color) !important;
        }
    </style>
    <style>
        .stats-container {
            width: 100%;
            padding: 20px;
        }
        .stat-card {
            padding: 20px;
            color: white;
            text-align: left;
            border: none;
            border-radius: 10px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            overflow: hidden;
        }
        .blue { background-color: #007bff; }
        .green { background-color: #28a745; }
        .yellow { background-color: #ffc107; color: black; }
        .red { background-color: #dc3545; }
        .purple { background-color: #6f42c1; }
        .stat-card.orange {
            background-color: #ff9800; 
            color: white;
        }

        .stat-title {
            font-size: 18px;
            font-weight: bold;
        }
        .stat-value {
            font-size: 40px;
            font-weight: bold;
            margin-top: 10px;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px var(--shadow-color);
        }

        /* Job Card Styles */
        .job-card {
            margin-bottom: 20px;
            border: 1px solid rgba(0, 0, 0, 0.125);
            border-radius: 10px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .job-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px var(--shadow-color);
        }

        .job-card .card-body {
            padding: 20px;
        }

        .job-card .card-title {
            font-size: 1.25rem;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .job-card .card-text {
            font-size: 0.9rem;
            color: #666;
        }

        .job-card .badge {
            font-size: 0.9rem;
            padding: 0.5em 0.75em;
        }

        .job-card .actions {
            margin-top: 15px;
        }

        .job-card .actions .btn {
            margin-right: 5px;
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
   <main class="main-content">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2">Job Posting Management</h1>
            <div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addJobModal">
                    <i class="fas fa-plus me-2"></i> Add Job
                </button>
            </div>
        </div>

<!-- Search Bar -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="input-group">
            <input type="text" class="form-control" id="searchJobs" placeholder="Search jobs by title, company, or location">
            <button class="btn btn-outline-secondary" type="button" id="searchButton">
                <i class="fas fa-search"></i>
            </button>
        </div>
    </div>
</div>


        <!-- Job Cards Grid -->
        <div class="row">
            <?php
            // Example job data (replace with your actual data from the database)
            $jobs = [
                [
                    'id' => 1,
                    'title' => 'Software Engineer',
                    'company' => 'Tech Corp',
                    'location' => 'New York, USA',
                    'status' => 'Active',
                ],
                [
                    'id' => 2,
                    'title' => 'Product Manager',
                    'company' => 'Innovate Inc',
                    'location' => 'San Francisco, USA',
                    'status' => 'Pending',
                ],
                // Add more jobs as needed
            ];

            foreach ($jobs as $job): ?>
                <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
                    <div class="card job-card">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($job['title']) ?></h5>
                            <p class="card-text"><strong>Company:</strong> <?= htmlspecialchars($job['company']) ?></p>
                            <p class="card-text"><strong>Location:</strong> <?= htmlspecialchars($job['location']) ?></p>
                            <p class="card-text">
                                <strong>Status:</strong> 
                                <span class="badge <?= $job['status'] === 'Active' ? 'bg-success' : 'bg-warning' ?>">
                                    <?= htmlspecialchars($job['status']) ?>
                                </span>
                            </p>
                            <div class="actions">

                                <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#viewJobModal">
                                    <i class="fas fa-eye"></i> View Details
                                </button>
                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editJobModal">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="confirmDeleteJob(<?= $job['id'] ?>)">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Add Job Modal -->
        <div class="modal fade" id="addJobModal" tabindex="-1" aria-labelledby="addJobModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addJobModalLabel">Add Job</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form>
                            <div class="mb-3">
                                <label for="jobTitle" class="form-label">Title</label>
                                <input type="text" class="form-control" id="jobTitle" required>
                            </div>
                            <div class="mb-3">
                                <label for="jobCompany" class="form-label">Company</label>
                                <input type="text" class="form-control" id="jobCompany" required>
                            </div>
                            <div class="mb-3">
                                <label for="jobLocation" class="form-label">Location</label>
                                <input type="text" class="form-control" id="jobLocation" required>
                            </div>
                            <div class="mb-3">
                                <label for="jobStatus" class="form-label">Status</label>
                                <select class="form-select" id="jobStatus" required>
                                    <option value="Active">Active</option>
                                    <option value="Pending">Pending</option>
                                    <option value="Closed">Closed</option>
                                </select>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary">Save</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Job Modal -->
        <div class="modal fade" id="editJobModal" tabindex="-1" aria-labelledby="editJobModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editJobModalLabel">Edit Job</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form>
                            <div class="mb-3">
                                <label for="editJobTitle" class="form-label">Title</label>
                                <input type="text" class="form-control" id="editJobTitle" required>
                            </div>
                            <div class="mb-3">
                                <label for="editJobCompany" class="form-label">Company</label>
                                <input type="text" class="form-control" id="editJobCompany" required>
                            </div>
                            <div class="mb-3">
                                <label for="editJobLocation" class="form-label">Location</label>
                                <input type="text" class="form-control" id="editJobLocation" required>
                            </div>
                            <div class="mb-3">
                                <label for="editJobStatus" class="form-label">Status</label>
                                <select class="form-select" id="editJobStatus" required>
                                    <option value="Active">Active</option>
                                    <option value="Pending">Pending</option>
                                    <option value="Closed">Closed</option>
                                </select>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary">Save Changes</button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Loading Spinner -->
    <div class="loading-spinner">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>  

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- SweetAlert -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom Scripts -->
    <script src="../assests/sidebar_toggle.js" defer></script>
    <script>
        function confirmDeleteJob(jobId) {
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
                    // Perform delete action here (e.g., AJAX call)
                    Swal.fire(
                        'Deleted!',
                        'The job has been deleted.',
                        'success'
                    );
                }
            });
        }
    </script>

<script>
    const trendsCtx = document.getElementById('jobTrendsChart').getContext('2d');
    const jobTrendsChart = new Chart(trendsCtx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            datasets: [{
                label: 'Job Postings',
                data: [12, 19, 15, 20, 18, 25],
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 2,
                fill: false
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
</script>
</body>
</html>