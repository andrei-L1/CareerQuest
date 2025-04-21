<?php
include "../includes/sidebar.php";
require "../controllers/admin_dashboard.php";
require "../controllers/admin_user_management.php";
require '../auth/auth_check.php'; 
try {
    require '../config/dbcon.php';

    // Prepare queries for better security (even though no input parameters are involved)
    $queries = [
        "job_type" => "SELECT COUNT(*) FROM job_type",
        "skills" => "SELECT COUNT(*) FROM skill_masterlist WHERE deleted_at IS NULL",
        "courses" => "SELECT COUNT(*) FROM course WHERE deleted_at IS NULL",
        "roles" => "SELECT COUNT(*) FROM role WHERE deleted_at IS NULL"
    ];

    $stmt = [];
    $counts = [];

    foreach ($queries as $key => $sql) {
        $stmt[$key] = $conn->prepare($sql);
        $stmt[$key]->execute();
        $counts[$key] = $stmt[$key]->fetchColumn();
    }

    // Assigning values
    $totalJobTypes = $counts["job_type"];
    $totalSkills = $counts["skills"];
    $totalCourses = $counts["courses"];
    $totalRoles = $counts["roles"];

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $totalJobTypes = $totalSkills = $totalCourses = $totalRoles = 0; // Default to zero on failure
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Management</title>
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
    .stat-card {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 20px;
        padding: 25px;
        text-align: center;
        transition: all 0.5s ease;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        position: relative;
        overflow: hidden;
        cursor: pointer;
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.2), rgba(255, 255, 255, 0));
        clip-path: circle(10% at 90% 10%);
        transition: all 0.5s ease;
    }

    .stat-card:hover::before {
        clip-path: circle(100%);
    }

    .stat-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 12px 40px rgba(0, 0, 0, 0.3);
    }

    .stat-icon i {
        font-size: 2.5rem;
        color: #fff;
        transition: all 0.5s ease;
        position: relative;
        z-index: 2;
    }

    .stat-card:hover .stat-icon i {
        transform: scale(1.2) translateY(-5px);
    }

    .stat-title {
        font-size: 1.2rem;
        color: #fff;
        margin-top: 15px;
        transition: all 0.5s ease;
        position: relative;
        z-index: 2;
    }

    .stat-card:hover .stat-title {
        transform: translateY(-5px);
    }

    .stat-value {
        font-size: 2rem;
        color: #fff;
        font-weight: bold;
        margin-top: 10px;
        transition: all 0.5s ease;
        position: relative;
        z-index: 2;
    }

    .stat-card:hover .stat-value {
        transform: translateY(-5px);
    }

    .particles {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: radial-gradient(circle, rgba(255, 255, 255, 0.3), transparent);
        opacity: 0;
        transition: opacity 0.5s ease;
        pointer-events: none;
    }

    .stat-card:hover .particles {
        opacity: 1;
    }

    .blue {
        background: linear-gradient(135deg, rgba(25, 82, 145, 0.8), rgba(47, 108, 180, 0.8));
    }

    .green {
        background: linear-gradient(135deg, rgba(34, 139, 34, 0.8), rgba(0, 100, 0, 0.8));
    }

    .red {
        background: linear-gradient(135deg, rgba(165, 42, 42, 0.8), rgba(139, 0, 0, 0.8));
    }

    .purple {
        background: linear-gradient(135deg, rgba(75, 0, 130, 0.8), rgba(106, 90, 205, 0.8));
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .fade-in .stat-card {
        animation: fadeIn 0.5s ease forwards;
        opacity: 0;
    }

    .fade-in .stat-card:nth-child(1) { animation-delay: 0.1s; }
    .fade-in .stat-card:nth-child(2) { animation-delay: 0.2s; }
    .fade-in .stat-card:nth-child(3) { animation-delay: 0.3s; }
    .fade-in .stat-card:nth-child(4) { animation-delay: 0.4s; }
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
            <h1 class="h2">Data Management Panel</h1>
            <div class="d-flex gap-2">

                <!-- Export Users Button -->
                <a href="#" class="btn btn-outline-primary d-flex align-items-center" onclick="confirmExport(event)">
                    <i class="fas fa-file-export me-2"></i> Export Data
                </a>

            </div>
        </div>


        <div class="row mb-4 fade-in">
            <?php 
                $stats = [
                    ["Job Types", $totalJobTypes, "fas fa-briefcase", "blue"],
                    ["Skills", $totalSkills, "fas fa-tools", "green"],
                    ["Courses", $totalCourses, "fas fa-book", "red"],
                    ["Roles", $totalRoles, "fas fa-user-tag", "purple"]
                ];

                foreach ($stats as $index => $stat): 
            ?>
            <div class="col-lg-3 col-md-6 col-sm-6 mb-4">
                <div class="stat-card <?php echo $stat[3]; ?>" data-index="<?php echo $index; ?>">
                    <div class="stat-icon"><i class="<?php echo $stat[2]; ?>"></i></div>
                    <div class="stat-title"><?php echo $stat[0]; ?></div>
                    <div class="stat-value"><?php echo $stat[1]; ?></div>
                    <div class="particles"></div>
                </div>
            </div>
            <?php endforeach; ?>
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
                <button class="nav-link" id="courseTab" data-bs-toggle="tab" data-bs-target="#coursePanel" type="button" role="tab">Course Management</button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="roleTab" data-bs-toggle="tab" data-bs-target="#rolePanel" type="button" role="tab">Role Management</button>
            </li>
        </ul>

        <div class="tab-content" id="jobManagementTabsContent">
            <!-- Job Type Management -->
            <div class="tab-pane fade show active p-4 rounded shadow" id="jobTypePanel" role="tabpanel">
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
                            <tbody id="jobTypeListContainer">
                                <!-- Job Types will be loaded dynamically here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Skill Management -->
            <div class="tab-pane fade p-4 rounded shadow" id="skillPanel" role="tabpanel">
                <h3 class="mb-3 text-primary"><i class="bi bi-tools"></i> Manage Skills</h3>
                <div class="mb-3 d-flex justify-content-between align-items-center">
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
            </div>

            <!-- Course Management -->
            <div class="tab-pane fade p-4 rounded shadow" id="coursePanel" role="tabpanel">
                <h3 class="mb-3 text-primary"><i class="bi bi-book"></i> Manage Courses</h3>

                <div class="d-flex justify-content-between mb-3">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCourseModal">
                        <i class="bi bi-plus-lg"></i> Add Course
                    </button>
                </div>

                <div class="card">
                    <div class="card-body">
                        <table class="table table-hover table-striped">
                            <thead class="table-primary">
                                <tr>
                                    <th>ID</th>
                                    <th>Course Title</th>
                                    <th>Description</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="courseListContainer">
                                <!-- Courses will be loaded dynamically here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Role Management -->
            <div class="tab-pane fade p-4 rounded shadow" id="rolePanel" role="tabpanel">
                <h3 class="mb-3 text-primary"><i class="bi bi-person-badge"></i> Manage Roles</h3>
                
                <div class="d-flex justify-content-between mb-3">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRoleModal">
                        <i class="bi bi-plus-lg"></i> Add Role
                    </button>
                </div>

                <div class="card">
                    <div class="card-body">
                        <table class="table table-hover table-striped">
                            <thead class="table-primary">
                                <tr>
                                    <th>ID</th>
                                    <th>Role Title</th>
                                    <th>Description</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="roleListContainer">
                                <!-- Roles will be loaded dynamically here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

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
                                <input type="text" class="form-control" id="skillName" name="skill_name" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Category</label>
                                <input type="text" class="form-control" id="skillCategory" name="category" required>
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

        <!-- Add Course Modal -->
        <div class="modal fade" id="addCourseModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Course</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="addCourseForm">
                            <div class="mb-3">
                                <label class="form-label">Course Title</label>
                                <input type="text" class="form-control" id="courseTitle" name="course_title" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" id="courseDescription" name="course_description" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-success">Add Course</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Course Modal -->
        <div class="modal fade" id="editCourseModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Course</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="editCourseForm">
                            <input type="hidden" id="editCourseId" name="course_id">
                            <div class="mb-3">
                                <label class="form-label">Course Title</label>
                                <input type="text" class="form-control" id="editCourseTitle" name="course_title" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" id="editCourseDescription" name="course_description" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-success">Update Course</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Role Modal -->
        <div class="modal fade" id="addRoleModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Role</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="addRoleForm">
                            <div class="mb-3">
                                <label class="form-label">Role Title</label>
                                <input type="text" class="form-control" id="roleTitle" name="role_title" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" id="roleDescription" name="role_description" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-success">Add Role</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Role Modal -->
        <div class="modal fade" id="editRoleModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Role</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="editRoleForm">
                            <input type="hidden" id="editRoleId" name="id">
                            <div class="mb-3">
                                <label class="form-label">Role Title</label>
                                <input type="text" class="form-control" id="editRoleTitle" name="role_title" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" id="editRoleDescription" name="role_description" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-success">Update Role</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </main>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- SweetAlert -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom Scripts -->
    <script src="../assests/sidebar_toggle.js" defer></script>
    <script src="../assests/jobtypemanagement.js"></script>
    <script src="../assests/skillsmanagement.js"></script>
    <script src="../assests/coursemanagement.js"></script>
    <script src="../assests/rolemanagement.js"></script>
    <script src="../assests/logout.js"></script>

    <script>
    function confirmExport(event) {
        event.preventDefault(); // Prevent immediate navigation

        Swal.fire({
            title: "Are you sure?",
            text: "Do you really want to export all the Data?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes, Export!",
            cancelButtonText: "Cancel"
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = "../controllers/export_data.php"; 
            }
        });
    }
    </script>
    <script>
    // Add cursor interaction
    document.querySelectorAll('.stat-card').forEach(card => {
        card.addEventListener('mousemove', (e) => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            card.style.setProperty('--x', `${x}px`);
            card.style.setProperty('--y', `${y}px`);
        });
    });
</script>
</body>
</html>