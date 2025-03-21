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
    

    <!-- Job Management Panel -->
    <main class="main-content">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2">Job Management Panel</h1>
        </div>

        <!-- Navigation Tabs -->
        <ul class="nav nav-tabs" id="jobManagementTabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" id="jobTypeTab" data-bs-toggle="tab" data-bs-target="#jobTypePanel" type="button" role="tab">Job Type Management</button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="skillTab" data-bs-toggle="tab" data-bs-target="#skillPanel" type="button" role="tab">Skill Management</button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="jobPostingTab" data-bs-toggle="tab" data-bs-target="#jobPostingPanel" type="button" role="tab">Job Posting Management</button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="jobSkillTab" data-bs-toggle="tab" data-bs-target="#jobSkillPanel" type="button" role="tab">Job Skill Mapping</button>
            </li>
        </ul>

        <div class="tab-content" id="jobManagementTabsContent">
            <!-- Job Type Management -->
            <div class="tab-pane fade show active p-4 bg-light rounded shadow" id="jobTypePanel" role="tabpanel">
                <h3 class="mb-3 text-primary"><i class="bi bi-briefcase"></i> Manage Job Types</h3>

                <div class="d-flex justify-content-between mb-3">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addJobTypeModal">
                        <i class="bi bi-plus-lg"></i> Add Job Type
                    </button>
                </div>

                <div class="card">
                    <div class="card-body">
                        <table class="table table-hover table-striped">
                            <thead class="table-primary">
                                <tr>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Description</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="jobTypeList">
                                <!-- Job Types will be loaded dynamically here -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination Controls -->
                <div id="paginationControls" class="mt-3 d-flex justify-content-center"></div>
            </div>


            <!-- Skill Management -->
            <div class="tab-pane fade p-4 bg-light rounded shadow" id="skillPanel" role="tabpanel">
                <h3 class="mb-3 text-primary"><i class="bi bi-tools"></i> Manage Skills</h3>

                <div class="d-flex justify-content-between mb-3">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSkillModal">
                        <i class="bi bi-plus-lg"></i> Add Skill
                    </button>
                </div>

                <div class="card">
                    <div class="card-body">
                        <table class="table table-hover table-striped">
                            <thead class="table-primary">
                                <tr>
                                    <th>ID</th>
                                    <th>Skill Name</th>
                                    <th>Category</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="skillList">
                                <!-- Skills will be loaded dynamically here -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination Controls -->
                <div id="skillPaginationControls" class="mt-3 d-flex justify-content-center"></div>
            </div>



            <!-- Job Posting Management -->
            <div class="tab-pane fade" id="jobPostingPanel" role="tabpanel">
                <h3 class="mt-3">Course Management</h3>
                <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addJobModal">Add Job</button>
                <div id="jobPostingList"></div>
            </div>

            <!-- Job Skill Mapping -->
            <div class="tab-pane fade" id="jobSkillPanel" role="tabpanel">
                <h3 class="mt-3">Role Management</h3>
                <div id="jobSkillMapping"></div>
            </div>
        </div>
    </main>

    <!-- Add Job Type Modal -->
<div class="modal fade" id="addJobTypeModal" tabindex="-1" aria-labelledby="addJobTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Job Type</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addJobTypeForm">
                    <div class="mb-3">
                        <label for="jobTypeTitle" class="form-label">Job Type Title</label>
                        <input type="text" class="form-control" id="jobTypeTitle" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="jobTypeDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="jobTypeDescription" name="description"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Save</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Job Type Modal -->
<div class="modal fade" id="editJobTypeModal" tabindex="-1" aria-labelledby="editJobTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Job Type</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editJobTypeForm">
                    <input type="hidden" id="editJobTypeId" name="id">
                    <div class="mb-3">
                        <label for="editJobTypeTitle" class="form-label">Job Type Title</label>
                        <input type="text" class="form-control" id="editJobTypeTitle" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="editJobTypeDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="editJobTypeDescription" name="description"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Update</button>
                </form>
            </div>
        </div>
    </div>
</div>





<!-- Add Skill Modal -->
<div class="modal fade" id="addSkillModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Skill</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addSkillForm">
                    <div class="mb-3">
                        <label class="form-label">Skill Name</label>
                        <input type="text" class="form-control" id="skillName" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <input type="text" class="form-control" id="skillCategory" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Save Skill</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Skill Modal -->
<div class="modal fade" id="editSkillModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Skill</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
            <form id="editSkillForm">
                <input type="hidden" id="editSkillId" name="id"> 
                <div class="mb-3">
                    <label class="form-label">Skill Name</label>
                    <input type="text" class="form-control" id="editSkillName" name="skill_name" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Category</label>
                    <input type="text" class="form-control" id="editSkillCategory" name="category" required> 
                </div>
                <button type="submit" class="btn btn-success">Update Skill</button>
            </form>

            </div>
        </div>
    </div>
</div>




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
    <script src="../assests/datamanagement.js"></script>
</body>
</html>