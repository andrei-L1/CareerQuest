<?php
include "../includes/sidebar.php";
require "../controllers/chart_query.php";
require "../controllers/admin_dashboard.php";
require "../controllers/admin_user_management.php";
require '../auth/auth_check.php'; 
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- DataTables CSS -->
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
            <h1 class="h2">User Management</h1>
            <div>
                <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="fas fa-plus me-2"></i> Add User
                </button>
            </div>
        </div>

        <div class="row mb-4 fade-in">
    <div class="row g-3">
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="stat-card blue">
                <div class="stat-title">Total Users</div>
                <div class="stat-value"><?php echo $totalUsers; ?></div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="stat-card green">
                <div class="stat-title">Students</div>
                <div class="stat-value"><?php echo $totalStudents; ?></div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="stat-card yellow">
                <div class="stat-title">Moderators</div>
                <div class="stat-value"><?php echo $totalModerators; ?></div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="stat-card red">
                <div class="stat-title">Professionals</div>
                <div class="stat-value"><?php echo $totalProfessionals; ?></div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="stat-card orange">
                <div class="stat-title">Employers</div>
                <div class="stat-value"><?php echo $totalEmployers; ?></div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="stat-card purple">
                <div class="stat-title">Admins</div>
                <div class="stat-value"><?php echo $totalAdmins; ?></div>
            </div>
        </div>
    </div>
</div>


        <!-- User Table -->
            
    <!-- Bootstrap Tabs -->
    <ul class="nav nav-tabs" id="userTabs" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" id="active-tab" data-bs-toggle="tab" href="#activeUsers" role="tab">Active Users</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="deleted-tab" data-bs-toggle="tab" href="#deletedUsers" role="tab">Deleted Users</a>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content mt-3">
        
        <!-- Active Users -->
        <div class="tab-pane fade show active" id="activeUsers" role="tabpanel">
            <div class="card">
                <div class="card-body">
                <input type="text" id="searchActiveUsers" class="form-control mb-3" placeholder="Search Active Users...">
                    <table id="activeUsersTable" class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <?php if ($user['status'] === 'active'): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($user['actor_id']) ?>
                                            <?php if ($user['entity_type'] === 'user'): ?>
                                                U-<?= htmlspecialchars($user['entity_id']) ?>
                                            <?php elseif ($user['entity_type'] === 'student'): ?>
                                                S-<?= htmlspecialchars($user['entity_id']) ?>
                                            <?php endif; ?>
                                       </td>
                                        <td><?= htmlspecialchars($user['first_name']. ' ' . $user['middle_name']. ' ' . $user['last_name']) ?></td>
                                        <td><?= htmlspecialchars($user['email']) ?></td>
                                        <td><?= htmlspecialchars($user['role_name']) ?></td>
                                        <td><span class="badge bg-success"><?= htmlspecialchars($user['status']) ?></span></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary btn-action" data-bs-toggle="modal" data-bs-target="#editUserModal">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger btn-action deleteUserBtn" data-id="<?= $user['actor_id'] ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Deleted Users -->
        <div class="tab-pane fade" id="deletedUsers" role="tabpanel">
            <div class="card">
                <div class="card-body">
                <input type="text" id="searchDeletedUsers" class="form-control mb-3" placeholder="Search Deleted Users...">
                    <table id="deletedUsersTable" class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($deletedusers as $deletedusers): ?>
                                <?php if ($deletedusers['status'] === 'Deleted'): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($deletedusers['actor_id']) ?></td>
                                        <td><?= htmlspecialchars($deletedusers['first_name'] . ' ' . $deletedusers['middle_name']. ' ' .  $deletedusers['last_name']) ?></td>
                                        <td><?= htmlspecialchars($deletedusers['email']) ?></td>
                                        <td><?= htmlspecialchars($deletedusers['role_name']) ?></td>
                                        <td><span class="badge bg-danger"><?= htmlspecialchars($deletedusers['status']) ?></span></td>
                                        <td>
                                        <button class="btn btn-sm btn-success btn-action restoreUserBtn" data-id="<?= $deletedusers['actor_id'] ?>">
                                            <i class="fas fa-undo"></i> Restore
                                        </button>

                                        </td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>



    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content bg-dark text-light border-secondary">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title" id="addUserModalLabel">Add User</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="responseMessage" class="alert d-none" role="alert"></div>
                    <form id="addUserForm">
                        <!-- Select Entity -->
                        <div class="mb-3">
                            <label for="entityType" class="form-label">Sign Up As</label>
                            <select class="form-select bg-secondary text-light border-secondary" id="entityType" name="entityType" required onchange="toggleFields()">
                                <option value="">Select</option>
                                <option value="user">User</option>
                                <option value="student">Student</option>
                            </select>
                        </div>

                        <!-- Personal Information -->
                        <div class="mb-3">
                            <label for="firstName" class="form-label">First Name</label>
                            <input type="text" class="form-control bg-secondary text-light border-secondary" id="firstName" name="first_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="lastName" class="form-label">Last Name</label>
                            <input type="text" class="form-control bg-secondary text-light border-secondary" id="lastName" name="last_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="userEmail" class="form-label">Email</label>
                            <input type="email" class="form-control bg-secondary text-light border-secondary" id="userEmail" name="email" required>
                        </div>

                        <!-- Password Fields -->
                        <div class="mb-3">
                            <label for="userPassword" class="form-label">Password</label>
                            <input type="password" class="form-control bg-secondary text-light border-secondary" id="userPassword" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirmPassword" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control bg-secondary text-light border-secondary" id="confirmPassword" name="confirm_password" required>
                            <small id="passwordHelp" class="text-danger d-none">Passwords do not match.</small>
                        </div>

                        <!-- Conditional Fields -->
                        <div id="userFields" class="mb-3 d-none">
                            <label for="userRole" class="form-label">Role</label>
                            <select class="form-select bg-secondary text-light border-secondary" id="userRole" name="role_id">
                                <option value="1">Employer</option>
                                <option value="2">Professional</option>
                                <option value="3">Moderator</option>
                                <option value="4">Admin</option>
                            </select>
                        </div>
                        <div id="studentFields" class="mb-3 d-none">
                            <label for="institution" class="form-label">Institution</label>
                            <input type="text" class="form-control bg-secondary text-light border-secondary" id="institution" name="institution">
                        </div>

                        <div id="errorMessage" class="text-danger"></div>
                    </form>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="submitUser()">Save</button>
                </div>
            </div>
        </div>
    </div>



    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content bg-dark text-light"> <!-- Dark mode applied here -->
                <div class="modal-header border-secondary">
                    <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form>

                        <div class="mb-3">
                            <label for="editUserFirstName" class="form-label">First Name</label>
                            <input type="text" class="form-control bg-secondary text-light border-0" id="editUserFirstName" required>
                        </div>
                        <div class="mb-3">
                            <label for="editUserMiddleName" class="form-label">Middle Name</label>
                            <input type="text" class="form-control bg-secondary text-light border-0" id="editUserMiddleName">
                        </div>
                        <div class="mb-3">
                            <label for="editUserLastName" class="form-label">Last Name</label>
                            <input type="text" class="form-control bg-secondary text-light border-0" id="editUserLastName" required>
                        </div>
                        <div class="mb-3">
                            <label for="editUserEmail" class="form-label">Email</label>
                            <input type="email" class="form-control bg-secondary text-light border-0" id="editUserEmail" required>
                        </div>
                        
                        <div class="mb-3">
                        <input type="hidden" id="editUserId">
                            <label for="editUserRole" class="form-label">Role</label>
                            <select class="form-select bg-secondary text-light border-0" id="editUserRole" name="role" required>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?= htmlspecialchars($role['role_id']) ?>" 
                                        <?= ($role['role_id'] == $current_role_id) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($role['role_title']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="editUserStatus" class="form-label">Status</label>
                            <select class="form-select bg-secondary text-light border-0" id="editUserStatus" required>
                                <option value="active">Active</option> 
                                <option value="Deleted">Inactive</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="saveUserChanges">Save Changes</button>
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
        <!-- DataTables JS -->
        <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
        <!-- Custom Scripts -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script src="../assests/logout.js"></script>
        <script src="../assests/sidebar_toggle.js" defer></script>

    <!-- Custom Scripts -->
    <script>
    // Enhanced JavaScript
    $(document).ready(function () {
        $('#userTable').DataTable({
            dom: 'Bfrtip',
            buttons: [
                'copy', 'csv', 'excel', 'pdf'
            ],
            responsive: true,
            language: {
                paginate: {
                    next: '<i class="fas fa-chevron-right"></i>',
                    previous: '<i class="fas fa-chevron-left"></i>'
                }
            }
        });

        // Select All Checkbox
        document.getElementById('selectAll').addEventListener('click', function () {
            const checkboxes = document.querySelectorAll('.userCheckbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });

        // Confirm Delete with SweetAlert2
        function confirmDelete() {
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
                    Swal.fire(
                        'Deleted!',
                        'The user has been deleted.',
                        'success'
                    );
                    // Perform delete action
                }
            });
        }

    });
    </script>

    <script>
    function submitUser() {
        let firstName = document.getElementById("firstName").value.trim();
        let lastName = document.getElementById("lastName").value.trim();
        let email = document.getElementById("userEmail").value.trim();
        let password = document.getElementById("userPassword").value;
        let confirmPassword = document.getElementById("confirmPassword").value;
        let entityType = document.getElementById("entityType").value;
        let institution = document.getElementById("institution").value.trim();
        let errorMessage = document.getElementById("errorMessage");
        let responseMessage = document.getElementById("responseMessage");

        errorMessage.innerHTML = ""; // Clear previous errors
        responseMessage.classList.add("d-none"); // Hide previous messages

        if (!firstName || !lastName || !email || !password || !confirmPassword) {
            errorMessage.innerHTML = "All fields are required.";
            return;
        }

        if (password !== confirmPassword) {
            errorMessage.innerHTML = "Passwords do not match.";
            return;
        }

        let formData = new FormData();
        formData.append("first_name", firstName);
        formData.append("last_name", lastName);
        formData.append("email", email);
        formData.append("password", password);
        formData.append("confirm_password", confirmPassword);
        formData.append("entity", entityType);

        if (entityType === "student") {
            formData.append("institution", institution || "Unknown");
            formData.append("status", "active");
        } else {
            let roleDropdown = document.getElementById("userRole");
            let roleTitle = roleDropdown.options[roleDropdown.selectedIndex].text;
            formData.append("role", roleTitle);
            formData.append("status", "active");
        }

        fetch("../controllers/admin_user_management.php", {
            method: "POST",
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            responseMessage.classList.remove("d-none", "alert-success", "alert-danger");
            responseMessage.classList.add(data.status === "success" ? "alert-success" : "alert-danger");
            responseMessage.innerHTML = data.message;

            if (data.status === "success") {
                setTimeout(() => {
                    location.reload();
                }, 2000);
            }
        })
        .catch(error => {
            console.error("Error:", error);
        });
    }


    </script>

    <script>
        function toggleFields() {
            let entityType = document.getElementById("entityType").value;
            document.getElementById("userFields").classList.toggle("d-none", entityType !== "user");
            document.getElementById("studentFields").classList.toggle("d-none", entityType !== "student");
        }

        document.getElementById("confirmPassword").addEventListener("input", function () {
            let password = document.getElementById("userPassword").value;
            let confirmPassword = this.value;
            let helpText = document.getElementById("passwordHelp");
            if (password !== confirmPassword) {
                helpText.classList.remove("d-none");
            } else {
                helpText.classList.add("d-none");
            }
        });
    </script>

    <script>
    document.addEventListener("DOMContentLoaded", function () {
        document.querySelectorAll(".deleteUserBtn, .restoreUserBtn").forEach(button => {
            button.addEventListener("click", function () {
                const userId = this.getAttribute("data-id");
                const isDelete = this.classList.contains("deleteUserBtn");

                if (!userId) {
                    console.error("Error: No user ID found.");
                    return;
                }

                console.log(`${isDelete ? "Deleting" : "Restoring"} User ID:`, userId);

                Swal.fire({
                    title: isDelete ? "Are you sure?" : "Restore this user?",
                    text: isDelete ? "You won't be able to revert this!" : "This will reactivate the user.",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: isDelete ? "#d33" : "#28a745",
                    cancelButtonColor: isDelete ? "#3085d6" : "#d33",
                    confirmButtonText: isDelete ? "Yes, delete it!" : "Yes, restore!",
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch("admin_user_management.php", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/x-www-form-urlencoded",
                            },
                            body: `${isDelete ? "delete_id" : "restore_id"}=${userId}`,
                        })
                        .then(response => response.text())  
                        .then(text => {
                            console.log("Raw response:", text);  
                            return JSON.parse(text);  
                        })
                        .then(data => {
                            Swal.fire({
                                icon: data.status === "success" ? "success" : "error",
                                title: data.status === "success" ? (isDelete ? "Deleted!" : "Restored!") : "Error!",
                                text: data.message,
                                timer: 2000,
                                showConfirmButton: false
                            });
                            if (data.status === "success") setTimeout(() => location.reload(), 2000);
                        })
                        .catch(error => {
                            console.error("Fetch Error:", error);
                            Swal.fire({
                                title: "Error!",
                                text: "Invalid server response. Check console for details.",
                                icon: "error",
                            });
                        });
                    }
                });
            });
        });
    });
    </script>
    
    <script>
    function filterTable(inputId, tableId) {
        document.getElementById(inputId).addEventListener("keyup", function () {
            let filter = this.value.toLowerCase();
            let rows = document.querySelectorAll(`#${tableId} tbody tr`);

                rows.forEach(row => {
                    let text = row.textContent.toLowerCase();
                    row.style.display = text.includes(filter) ? "" : "none";
                });
            });
        }

        // Apply filtering for both tables
        filterTable("searchActiveUsers", "activeUsersTable");
        filterTable("searchDeletedUsers", "deletedUsersTable");
    </script>



<script>
 
document.addEventListener("DOMContentLoaded", function () {
    // Edit User Modal - Pre-fill data from backend
    document.querySelectorAll(".btn-action").forEach(button => {
        button.addEventListener("click", function () {
            let row = this.closest("tr"); 
            let actorId = row.querySelector("td:first-child").textContent.trim();
            
            fetch(`../controllers/get_user.php?user_id=${actorId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        console.error(data.error);
                        Swal.fire("Error!", data.error, "error");
                    } else {
                        document.getElementById("editUserFirstName").value = data.first_name || '';
                        document.getElementById("editUserMiddleName").value = data.middle_name || '';
                        document.getElementById("editUserLastName").value = data.last_name || '';
                        document.getElementById("editUserEmail").value = data.email || '';

                        let roleDropdown = document.getElementById("editUserRole");
                        roleDropdown.value = data.role_id || '';
                        roleDropdown.disabled = !data.role_id;

                        if (!data.role_id) {
                            roleDropdown.innerHTML = '<option selected>Cannot be edited</option>';
                        }

                        document.getElementById("editUserStatus").value = data.status || '';
                        document.getElementById("editUserId").value = actorId;
                    }
                })
                .catch(error => {
                    console.error("Error fetching user data:", error);
                    Swal.fire("Error!", "Failed to load user data.", "error");
                });
        });
    });

    // Save Changes - Edit User AJAX
    document.getElementById("saveUserChanges").addEventListener("click", function () {
        let actorId = document.getElementById("editUserId").value;
        let firstName = document.getElementById("editUserFirstName").value.trim();
        let middleName = document.getElementById("editUserMiddleName").value.trim();
        let lastName = document.getElementById("editUserLastName").value.trim();
        let email = document.getElementById("editUserEmail").value.trim();
        let roleId = document.getElementById("editUserRole").value.trim();
        let status = document.getElementById("editUserStatus").value.trim();

        let saveButton = document.getElementById("saveUserChanges");
        saveButton.disabled = true; 

        fetch("../controllers/admin_user_management.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({
                edit_id: actorId,
                first_name: firstName,
                middle_name: middleName,
                last_name: lastName,
                email: email,
                role: roleId,
                status: status
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === "success") {
                Swal.fire({
                    icon: "success",
                    title: "Updated!",
                    text: "User updated successfully!",
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => location.reload());
            } else {
                Swal.fire("Error!", data.message, "error");
            }
        })
        .catch(error => {
            console.error("Error:", error);
            Swal.fire("Error!", "Something went wrong. Please try again.", "error");
        })
        .finally(() => {
            saveButton.disabled = false;
        });
    });
});



</script>

</body>
</html>