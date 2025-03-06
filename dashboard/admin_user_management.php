<?php
include "../includes/sidebar.php";
require "../controllers/chart_query.php";
require "../controllers/admin_dashboard.php";
require "../controllers/admin_user_management.php";
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
            margin-left: 250px;
            padding: 20px;
            transition: margin-left 0.3s ease;
        }

        .sidebar.collapsed + .main-content {
            margin-left: 60px;
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

        <!-- Bulk Actions -->
        <div class="bulk-actions mb-3">
            <button class="btn btn-danger me-2" id="deleteSelected">
                <i class="fas fa-trash me-2"></i> Delete Selected
            </button>
            <button class="btn btn-warning me-2" id="changeStatusSelected">
                <i class="fas fa-sync me-2"></i> Change Status
            </button>
            <button class="btn btn-success" id="exportUsers">
                <i class="fas fa-file-export me-2"></i> Export
            </button>
        </div>

        <!-- User Table -->
        <div class="card">
            <div class="card-body">
                <table id="userTable" class="table table-striped" style="width:100%">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAll"></th>
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
                            <tr>
                                <td><input type="checkbox" class="userCheckbox"></td>
                                <td><?= htmlspecialchars($user['actor_id']) ?></td>
                                <td><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td><?= htmlspecialchars($user['role_name']) ?></td>
                                <td>
                                    <span class="badge 
                                        <?php 
                                            if ($user['status'] === 'Active') {
                                                echo 'bg-success'; 
                                            } elseif ($user['status'] === 'Pending') {
                                                echo 'bg-warning'; 
                                            } elseif ($user['status'] === 'Inactive') {
                                                echo 'bg-danger'; 
                                            } else {
                                                echo 'bg-success';
                                            }
                                        ?>">
                                        <?= htmlspecialchars($user['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-primary btn-action" data-bs-toggle="modal" data-bs-target="#editUserModal">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger btn-action" onclick="confirmDelete()">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
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
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form>
                            <div class="mb-3">
                                <label for="editUserName" class="form-label">Name</label>
                                <input type="text" class="form-control" id="editUserName" required>
                            </div>
                            <div class="mb-3">
                                <label for="editUserEmail" class="form-label">Email</label>
                                <input type="email" class="form-control" id="editUserEmail" required>
                            </div>
                            <div class="mb-3">
                                <label for="editUserRole" class="form-label">Role</label>
                                <select class="form-select" id="editUserRole" required>
                                    <option value="Student">Student</option>
                                    <option value="Employer">Employer</option>
                                    <option value="Professional">Professional</option>
                                    <option value="Moderator">Moderator</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="editUserStatus" class="form-label">Status</label>
                                <select class="form-select" id="editUserStatus" required>
                                    <option value="Active">Active</option>
                                    <option value="Pending">Pending</option>
                                    <option value="Inactive">Inactive</option>
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
    <!-- SweetAlert2 CSS & JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        
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

        // Bulk Delete with SweetAlert2
        document.getElementById('deleteSelected').addEventListener('click', function () {
            const selectedUsers = document.querySelectorAll('.userCheckbox:checked');
            if (selectedUsers.length > 0) {
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You won't be able to revert this!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete them!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire(
                            'Deleted!',
                            'The selected users have been deleted.',
                            'success'
                        );
                        // Perform bulk delete action
                    }
                });
            } else {
                Swal.fire(
                    'No Selection',
                    'Please select at least one user.',
                    'warning'
                );
            }
        });

        // Bulk Change Status with SweetAlert2
        document.getElementById('changeStatusSelected').addEventListener('click', function () {
            const selectedUsers = document.querySelectorAll('.userCheckbox:checked');
            if (selectedUsers.length > 0) {
                Swal.fire({
                    title: 'Change Status',
                    input: 'select',
                    inputOptions: {
                        'Active': 'Active',
                        'Pending': 'Pending',
                        'Inactive': 'Inactive'
                    },
                    inputPlaceholder: 'Select a status',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Change'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire(
                            'Changed!',
                            'The status has been updated.',
                            'success'
                        );
                        // Perform bulk status change action
                    }
                });
            } else {
                Swal.fire(
                    'No Selection',
                    'Please select at least one user.',
                    'warning'
                );
            }
        });

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
        } else {
            let roleDropdown = document.getElementById("userRole");
            let roleTitle = roleDropdown.options[roleDropdown.selectedIndex].text;
            formData.append("role", roleTitle);
            formData.append("status", "Pending");
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

</body>
</html>