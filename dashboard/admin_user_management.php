<?php
include "../includes/sidebar.php";
require "../controllers/admin_user_management.php";
require '../auth/auth_check.php'; 
$roles = fetchRoles();
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">

    
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
    /* General Card Styling */
    .stat-card {
        padding: 20px;
        text-align: center;
        border-radius: 15px;
        color: white;
        position: relative;
        overflow: hidden;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        cursor: pointer;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .stat-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
    }

    /* Icon Styling */
    .stat-icon {
        font-size: 40px;
        margin-bottom: 15px;
        transition: transform 0.3s ease, color 0.3s ease;
    }

    .stat-card:hover .stat-icon {
        transform: scale(1.2) rotate(10deg);
    }

    /* Title and Value */
    .stat-title {
        font-size: 16px;
        font-weight: 600;
        margin-bottom: 5px;
        transition: color 0.3s ease;
    }

    .stat-value {
        font-size: 24px;
        font-weight: bold;
        transition: color 0.3s ease;
    }

    /* Particle Effects */
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

    /* Gradient Backgrounds */
    .blue {
        background: linear-gradient(135deg, rgba(0, 123, 255, 0.9), rgba(0, 86, 179, 0.9));
    }

    .green {
        background: linear-gradient(135deg, rgba(40, 167, 69, 0.9), rgba(33, 136, 56, 0.9));
    }

    .yellow {
        background: linear-gradient(135deg, rgba(255, 193, 7, 0.9), rgba(204, 154, 6, 0.9));
    }

    .red {
        background: linear-gradient(135deg, rgba(220, 53, 69, 0.9), rgba(183, 44, 57, 0.9));
    }

    .orange {
        background: linear-gradient(135deg, rgba(253, 126, 20, 0.9), rgba(211, 105, 17, 0.9));
    }

    .purple {
        background: linear-gradient(135deg, rgba(111, 66, 193, 0.9), rgba(92, 55, 160, 0.9));
    }

    /* Fade-In Animation */
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
    .fade-in .stat-card:nth-child(5) { animation-delay: 0.5s; }
    .fade-in .stat-card:nth-child(6) { animation-delay: 0.6s; }
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
   <main class="main-content">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom ">
            <h1 class="h2">User Management Panel</h1>
            <div class="d-flex gap-2">
                <!-- Add User Button -->
                <button class="btn btn-primary d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="fas fa-plus me-2"></i> Add User
                </button>

                <!-- Export Users Button -->
                <a href="#" class="btn btn-outline-primary d-flex align-items-center" onclick="confirmExport(event)">
                    <i class="fas fa-file-export me-2"></i> Export Users
                </a>
            </div>
        </div>

        <div class="row mb-4 fade-in">
            <?php 
                $stats = [
                    ["Total Users", $totalUsers, "fas fa-users", "blue"],
                    ["Students", $totalStudents, "fas fa-user-graduate", "green"],
                    ["Professionals", $totalProfessionals, "fas fa-briefcase", "red"],
                    ["Employers", $totalEmployers, "fas fa-building", "orange"],
                    ["Moderators", $totalModerators, "fas fa-user-shield", "yellow"],
                    ["Admins", $totalAdmins, "fas fa-user-cog", "purple"]
                ];
                
                foreach ($stats as $stat): 
            ?>
            <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="stat-card <?php echo $stat[3]; ?>">
                    <div class="stat-icon"><i class="<?php echo $stat[2]; ?>"></i></div>
                    <div class="stat-title"><?php echo $stat[0]; ?></div>
                    <div class="stat-value"><?php echo $stat[1]; ?></div>
                    <div class="particles"></div>
                </div>
            </div>
            <?php endforeach; ?>
         </div>




    <!-- Bootstrap Tabs -->
    <ul class="nav nav-tabs" id="userTabs" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" id="active-tab" data-bs-toggle="tab" href="#activeUsers" role="tab">Active Users</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="deleted-tab" data-bs-toggle="tab" href="#deletedUsers" role="tab">Archived Users</a>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content mt-3">
        <!-- Active Users -->
        <div class="tab-pane fade show active" id="activeUsers" role="tabpanel">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <!-- Filter Buttons -->
                        <div>
                            <div class="btn-group" role="group">
                                <button class="btn btn-outline-primary filter-btn active" data-role="">All</button>
                                <button class="btn btn-outline-primary filter-btn" data-role="Employer">Employer</button>
                                <button class="btn btn-outline-primary filter-btn" data-role="Professional">Professional</button>
                                <button class="btn btn-outline-primary filter-btn" data-role="Moderator">Moderator</button>
                                <button class="btn btn-outline-primary filter-btn" data-role="Admin">Admin</button>
                                <button class="btn btn-outline-primary filter-btn" data-role="Student">Student (No Role)</button>
                            </div>
                        </div>

                        <!-- Search Bar -->
                        <div style="max-width: 300px;">
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="text" id="searchActiveUsers" class="form-control" placeholder="Search Active Users...">
                            </div>
                        </div>

                    </div>

                    <table id="activeUsersTable" class="table table-striped">
                        <thead>
                            <tr>
                                <th style="display: none;">ID</th>
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
                                        <td style="display: none;"><?= htmlspecialchars($user['actor_id']) ?>
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
                <div class="d-flex justify-content-between align-items-center mb-3">
                        <!-- Filter Buttons -->
                        <div>
                            <div class="btn-group" role="group">
                                <button class="btn btn-outline-primary filter-btn" data-role="">All</button>
                                <button class="btn btn-outline-primary filter-btn" data-role="Employer">Employer</button>
                                <button class="btn btn-outline-primary filter-btn" data-role="Professional">Professional</button>
                                <button class="btn btn-outline-primary filter-btn" data-role="Moderator">Moderator</button>
                                <button class="btn btn-outline-primary filter-btn" data-role="Admin">Admin</button>
                                <button class="btn btn-outline-primary filter-btn" data-role="Student">Student (No Role)</button>
                            </div>
                        </div>

                        <!-- Search Bar -->
                        <div style="max-width: 300px;">
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="text" id="searchDeletedUsers" class="form-control" placeholder="Search Deleted Users...">
                            </div>
                        </div>
                    </div>
                    <table id="deletedUsersTable" class="table table-striped">
                        <thead>
                            <tr>
                                <th style="display: none;">ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                            <tbody>
                                <?php 
                                $hasDeleted = false;
                                foreach ($deletedusers as $deleteduser): 
                                    if ($deleteduser['status'] === 'Deleted'):
                                        $hasDeleted = true;
                                ?>
                                    <tr>
                                        <td style="display: none;"><?= htmlspecialchars($user['actor_id']) ?>
                                            <?php if ($user['entity_type'] === 'user'): ?>
                                                U-<?= htmlspecialchars($user['entity_id']) ?>
                                            <?php elseif ($user['entity_type'] === 'student'): ?>
                                                S-<?= htmlspecialchars($user['entity_id']) ?>
                                            <?php endif; ?>
                                    </td>
                                        <td><?= htmlspecialchars($deleteduser['first_name'] . ' ' . $deleteduser['middle_name']. ' ' .  $deleteduser['last_name']) ?></td>
                                        <td><?= htmlspecialchars($deleteduser['email']) ?></td>
                                        <td><?= htmlspecialchars($deleteduser['role_name']) ?></td>
                                        <td><span class="badge bg-danger"><?= htmlspecialchars($deleteduser['status']) ?></span></td>
                                        <td>
                                            <button class="btn btn-sm btn-success btn-action restoreUserBtn" data-id="<?= $deleteduser['actor_id'] ?>">
                                                <i class="fas fa-undo"></i> Restore
                                            </button>
                                        </td>
                                    </tr>
                                <?php 
                                    endif;
                                endforeach;

                                if (!$hasDeleted): 
                                ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            <i class="bi bi-person-x fs-4 d-block mb-2"></i>
                                            No deleted users found.
                                        </td>
                                    </tr>
                                <?php endif; ?>
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
                            <input type="text" class="form-control bg-secondary text-light border-secondary" id="firstName" name="first_name" required autocomplete="given-name">
                        </div>
                        <div class="mb-3">
                            <label for="lastName" class="form-label">Last Name</label>
                            <input type="text" class="form-control bg-secondary text-light border-secondary" id="lastName" name="last_name" required autocomplete="family-name">
                        </div>
                        <div class="mb-3">
                            <label for="userEmail" class="form-label">Email</label>
                            <input type="email" class="form-control bg-secondary text-light border-secondary" id="userEmail" name="email" required autocomplete="email">
                        </div>

                        <!-- Password Fields -->
                        <div class="mb-3">
                            <label for="userPassword" class="form-label">Password</label>
                            <input type="password" class="form-control bg-secondary text-light border-secondary" id="userPassword" name="password" required autocomplete="new-password">
                        </div>
                        <div class="mb-3">
                            <label for="confirmPassword" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control bg-secondary text-light border-secondary" id="confirmPassword" name="confirm_password" required autocomplete="new-password">
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
                            <input type="text" class="form-control bg-secondary text-light border-0" id="editUserFirstName" required autocomplete="given-name">
                        </div>
                        <div class="mb-3">
                            <label for="editUserMiddleName" class="form-label">Middle Name</label>
                            <input type="text" class="form-control bg-secondary text-light border-0" id="editUserMiddleName" autocomplete="additional-name">
                        </div>
                        <div class="mb-3">
                            <label for="editUserLastName" class="form-label">Last Name</label>
                            <input type="text" class="form-control bg-secondary text-light border-0" id="editUserLastName" required autocomplete="family-name">
                        </div>
                        <div class="mb-3">
                            <label for="editUserEmail" class="form-label">Email</label>
                            <input type="email" class="form-control bg-secondary text-light border-0" id="editUserEmail" required autocomplete="email">
                        </div>
                        
                        <div class="mb-3">
                        <input type="hidden" id="editUserId">
                            <label for="editUserRole" class="form-label">Role</label>
                            <select class="form-select bg-secondary text-light border-0" id="editUserRole" name="role" required>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?= htmlspecialchars($role['role_id']) ?>" 
                                        <?= (isset($current_role_id) && $role['role_id'] == $current_role_id) ? 'selected' : '' ?>>
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
    <!-- 
    <script>
    // Enhanced JavaScript
    $(document).ready(function () {
        $('#activeUsersTable').DataTable({
            dom: 'Bfrtip',
            buttons: ['copy', 'csv', 'excel', 'pdf'],
            responsive: true,
            language: {
                paginate: {
                    next: '<i class="fas fa-chevron-right"></i>',
                    previous: '<i class="fas fa-chevron-left"></i>'
                }
            }
        });

        $('#deletedUsersTable').DataTable({
            dom: 'Bfrtip',
            buttons: ['copy', 'csv', 'excel', 'pdf'],
            responsive: true,
            language: {
                paginate: {
                    next: '<i class="fas fa-chevron-right"></i>',
                    previous: '<i class="fas fa-chevron-left"></i>'
                }
            }
        });
    });
    </script>
    -->
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
<script>
document.querySelectorAll('.filter-btn').forEach(button => {
    button.addEventListener('click', function() {
        let selectedRole = this.getAttribute('data-role').toLowerCase();
        let rows = document.querySelectorAll("#activeUsersTable tbody tr");

        rows.forEach(row => {
            let roleCell = row.cells[3]; // Role is in the 4th column
            let role = roleCell.textContent.trim().toLowerCase();

            if (selectedRole === "" || role === selectedRole || (selectedRole === "student" && role === "")) {
                row.style.display = "";
            } else {
                row.style.display = "none";
            }
        });

        // Remove active class from all buttons and add to the clicked one
        document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
        this.classList.add('active');
    });
});

</script>

<script>
function confirmExport(event) {
    event.preventDefault(); // Prevent immediate navigation

    Swal.fire({
        title: "Are you sure?",
        text: "Do you really want to export users?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Yes, Export!",
        cancelButtonText: "Cancel"
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = "../controllers/export_user.php"; // Redirect if confirmed
        }
    });
}
</script>


</body>
</html>