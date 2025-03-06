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
        }

        body {
            background-color: var(--background-light);
            color: var(--text-dark);
            font-family: 'Poppins', sans-serif;
        }

        .main-content {
            margin-left: 250px; /* Adjust this when sidebar is open */
            padding: 20px;
            transition: margin-left 0.3s ease;
        }

        .sidebar.collapsed + .main-content {
            margin-left: 60px; /* Adjust when sidebar is collapsed */
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
    <main class="main-content">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2">User Management</h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="fas fa-plus me-2"></i> Add User
            </button>
        </div>

        <!-- Bulk Actions -->
        <div class="bulk-actions">
            <button class="btn btn-danger" id="deleteSelected">
                <i class="fas fa-trash me-2"></i> Delete Selected
            </button>
            <button class="btn btn-warning" id="changeStatusSelected">
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
                        <tr>
                            <td><input type="checkbox" class="userCheckbox"></td>
                            <td>1</td>
                            <td>John Doe</td>
                            <td>john.doe@example.com</td>
                            <td>Student</td>
                            <td><span class="badge bg-success">Active</span></td>
                            <td>
                                <button class="btn btn-sm btn-primary btn-action" data-bs-toggle="modal" data-bs-target="#editUserModal">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger btn-action" onclick="confirmDelete()">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td><input type="checkbox" class="userCheckbox"></td>
                            <td>2</td>
                            <td>Jane Smith</td>
                            <td>jane.smith@example.com</td>
                            <td>Employer</td>
                            <td><span class="badge bg-warning">Pending</span></td>
                            <td>
                                <button class="btn btn-sm btn-primary btn-action" data-bs-toggle="modal" data-bs-target="#editUserModal">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger btn-action" onclick="confirmDelete()">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <!-- Add more rows as needed -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Add User Modal -->
        <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addUserModalLabel">Add User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form>
                            <div class="mb-3">
                                <label for="userName" class="form-label">Name</label>
                                <input type="text" class="form-control" id="userName" required>
                            </div>
                            <div class="mb-3">
                                <label for="userEmail" class="form-label">Email</label>
                                <input type="email" class="form-control" id="userEmail" required>
                            </div>
                            <div class="mb-3">
                                <label for="userRole" class="form-label">Role</label>
                                <select class="form-select" id="userRole" required>
                                    <option value="Student">Student</option>
                                    <option value="Employer">Employer</option>
                                    <option value="Professional">Professional</option>
                                    <option value="Moderator">Moderator</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="userStatus" class="form-label">Status</label>
                                <select class="form-select" id="userStatus" required>
                                    <option value="Active">Active</option>
                                    <option value="Pending">Pending</option>
                                    <option value="Inactive">Inactive</option>
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
    <script>
        // Initialize DataTable with Export Buttons
        $(document).ready(function () {
            $('#userTable').DataTable({
                dom: 'Bfrtip',
                buttons: [
                    'copy', 'csv', 'excel', 'pdf'
                ]
            });
        });

        // Select All Checkbox
        document.getElementById('selectAll').addEventListener('click', function () {
            const checkboxes = document.querySelectorAll('.userCheckbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });

        // Confirm Delete
        function confirmDelete() {
            if (confirm('Are you sure you want to delete this user?')) {
                // Perform delete action
            }
        }

        // Bulk Delete
        document.getElementById('deleteSelected').addEventListener('click', function () {
            const selectedUsers = document.querySelectorAll('.userCheckbox:checked');
            if (selectedUsers.length > 0) {
                if (confirm('Are you sure you want to delete the selected users?')) {
                    // Perform bulk delete action
                }
            } else {
                alert('Please select at least one user.');
            }
        });

        // Bulk Change Status
        document.getElementById('changeStatusSelected').addEventListener('click', function () {
            const selectedUsers = document.querySelectorAll('.userCheckbox:checked');
            if (selectedUsers.length > 0) {
                const newStatus = prompt('Enter new status (Active, Pending, Inactive):');
                if (newStatus) {
                    // Perform bulk status change action
                }
            } else {
                alert('Please select at least one user.');
            }
        });


        // Dark Mode Toggle
        const darkModeToggle = document.getElementById('darkModeToggle');
        darkModeToggle.addEventListener('click', () => {
            document.body.classList.toggle('dark-mode');
            localStorage.setItem('darkMode', document.body.classList.contains('dark-mode'));
            darkModeToggle.innerHTML = document.body.classList.contains('dark-mode') ?
                '<i class="fas fa-sun"></i> Light Mode' : '<i class="fas fa-moon"></i> Dark Mode';
        });
             
         if (localStorage.getItem('darkMode') === 'true') {
            document.body.classList.add('dark-mode');
            darkModeToggle.innerHTML = '<i class="fas fa-sun"></i> Light Mode';
        }

    </script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <!-- Custom Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../assests/logout.js"></script>
    <script src="../assests/sidebar_toggle.js" defer></script>
    <!-- SweetAlert2 CSS & JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        
</body>
</html>