<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require "../config/dbcon.php"; 
require "../auth/auth_check.php"; 
include "../includes/sidebar.php";

// Ensure moderator is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // Fetch moderator details including role_title
    $stmt = $conn->prepare("
        SELECT user.user_first_name, user.user_last_name, role.role_title
        FROM user
        JOIN role ON user.role_id = role.role_id
        WHERE user.user_id = :user_id
    ");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception("User not found.");
    }

    $full_name = htmlspecialchars($user['user_first_name'] . " " . $user['user_last_name']);
    $role_title = htmlspecialchars($user['role_title']);

} catch (Exception $e) {
    error_log("Dashboard Error: " . $e->getMessage());
    header("Location: ../auth/logout.php");
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moderator Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        :root {
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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
            transition: var(--sidebar-transition);
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

        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: var(--sidebar-text);
            text-decoration: none;
            transition: var(--sidebar-transition);
            white-space: nowrap;
        }

        .nav-link:hover {
            background: var(--sidebar-hover-bg);
            color: var(--sidebar-active-text);
        }

        .nav-link.active {
            background: var(--sidebar-active-bg);
            color: var(--sidebar-active-text);
            font-weight: 500;
        }

        .nav-link i {
            margin-right: 10px;
            width: 24px;
            text-align: center;
            font-size: 1.1rem;
            transition: var(--sidebar-transition);
        }

        .sidebar.collapsed .nav-link i {
            margin-right: 0;
            font-size: 1.3rem;
        }

        .nav-link span {
            transition: opacity 0.2s ease, transform 0.2s ease;
        }

        .sidebar.collapsed .nav-link span {
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

        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: var(--main-content-padding);
            transition: var(--sidebar-transition);
            min-height: 100vh;
        }

        .sidebar.collapsed + .main-content {
            margin-left: var(--sidebar-collapsed-width);
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

        body.dark-mode .nav-link.active {
            background-color: #2a2a2a;
        }

        body.dark-mode .nav-link:hover {
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
    </style>
</head>
<body>
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
                        <a class="nav-link <?= $is_active ?>" href="<?= $link ?>" <?= $logout_attr ?>>
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

    <main class="main-content">
        <div class="container-fluid">
            <div class="row mb-4">
                <div class="col-12">
                    <h1 class="display-4">Welcome, <?php echo $full_name; ?>!</h1>
                    <p class="lead">Your Role: <span class="badge bg-primary"><?php echo $role_title; ?></span></p>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <a href="../auth/logout.php" class="logout-link" onclick="confirmLogout(event)">
                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </main>

    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assests/sidebar_toggle.js" defer></script>
    <script>


        // SweetAlert for logout confirmation
        function confirmLogout(e) {
            e.preventDefault();
            const logoutUrl = e.currentTarget.getAttribute('href');
            
            Swal.fire({
                title: 'Are you sure?',
                text: "You will be logged out of the system.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, logout!',
                background: document.body.classList.contains('dark-mode') ? '#1a1a1a' : '#ffffff',
                color: document.body.classList.contains('dark-mode') ? '#ffffff' : '#333333'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = logoutUrl;
                }
            });
        }
    </script>
</body>
</html>