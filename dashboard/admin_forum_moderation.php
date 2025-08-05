<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require "../config/dbcon.php"; 
require "../auth/auth_check.php"; 
include "../includes/sidebar.php";


if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

try {

    $stmt = $conn->prepare("
        SELECT u.user_first_name, u.user_last_name, r.role_title
        FROM user u
        JOIN role r ON u.role_id = r.role_id
        WHERE u.user_id = :user_id
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

// Fetch reported posts
$reported_posts = [];
try {
    $stmt = $conn->prepare("
        SELECT 
            fp.post_id,
            fp.content AS post_content,
            f.forum_id,
            f.title AS forum_name,
            COUNT(r.report_id) AS report_count,
            GROUP_CONCAT(DISTINCT r.reason SEPARATOR '; ') AS report_reasons,
            fp.posted_at AS created_at,
            CASE 
                WHEN a.entity_type = 'user' THEN u.user_first_name
                WHEN a.entity_type = 'student' THEN s.stud_first_name
            END AS user_first_name,
            CASE 
                WHEN a.entity_type = 'user' THEN u.user_last_name
                WHEN a.entity_type = 'student' THEN s.stud_last_name
            END AS user_last_name,
            a.actor_id
        FROM forum_post fp
        JOIN report r ON r.content_type = 'post' AND r.content_id = fp.post_id
        JOIN forum f ON fp.forum_id = f.forum_id
        JOIN actor a ON fp.poster_id = a.actor_id
        LEFT JOIN user u ON a.entity_type = 'user' AND a.entity_id = u.user_id
        LEFT JOIN student s ON a.entity_type = 'student' AND a.entity_id = s.stud_id
        WHERE r.status = 'pending'
        GROUP BY fp.post_id, fp.content, f.forum_id, f.title, fp.posted_at, 
                 a.entity_type, u.user_first_name, u.user_last_name, 
                 s.stud_first_name, s.stud_last_name, a.actor_id
        ORDER BY report_count DESC
    ");
    $stmt->execute();
    $reported_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Error fetching reported posts: " . $e->getMessage());
}

// Fetch reported comments
$reported_comments = [];
try {
    $stmt = $conn->prepare("
        SELECT fc.comment_id, fc.content as comment_content, fc.commented_at as created_at,
               a.entity_id as user_id, u.user_first_name, u.user_last_name,
               fp.content as post_content, COUNT(r.report_id) as report_count
        FROM forum_comment fc
        JOIN actor a ON fc.commenter_id = a.actor_id
        JOIN user u ON a.entity_id = u.user_id AND a.entity_type = 'user'
        JOIN forum_post fp ON fc.post_id = fp.post_id
        JOIN report r ON fc.comment_id = r.content_id AND r.content_type = 'comment'
        WHERE r.status = 'pending'
        GROUP BY fc.comment_id
        ORDER BY report_count DESC
        LIMIT 3
    ");
    $stmt->execute();
    $reported_comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching reported comments: " . $e->getMessage());
}

// Fetch reported users
$reported_users = [];
try {
    $stmt = $conn->prepare("
        SELECT 
            u.user_id AS id,
            u.user_first_name AS first_name,
            u.user_last_name AS last_name,
            u.user_email AS email,
            r.role_title AS role,
            COUNT(re.report_id) AS report_count,
            GROUP_CONCAT(DISTINCT re.reason SEPARATOR ', ') AS reasons,
            u.created_at,
            'user' AS user_type
        FROM user u
        JOIN role r ON u.role_id = r.role_id
        JOIN actor a ON a.entity_id = u.user_id AND a.entity_type = 'user'
        JOIN report re ON a.actor_id = re.content_id AND re.content_type = 'user'
        WHERE re.status = 'pending'
        GROUP BY u.user_id

        UNION

        SELECT 
            s.stud_id AS id,
            s.stud_first_name AS first_name,
            s.stud_last_name AS last_name,
            s.stud_email AS email,
            'Student' AS role,
            COUNT(re.report_id) AS report_count,
            GROUP_CONCAT(DISTINCT re.reason SEPARATOR ', ') AS reasons,
            s.created_at,
            'student' AS user_type
        FROM student s
        JOIN actor a ON a.entity_id = s.stud_id AND a.entity_type = 'student'
        JOIN report re ON a.actor_id = re.content_id AND re.content_type = 'student'
        WHERE re.status = 'pending'
        GROUP BY s.stud_id

        ORDER BY report_count DESC
        LIMIT 2
    ");
    $stmt->execute();
    $reported_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching reported users: " . $e->getMessage());
}

// Fetch recent activity
$recent_activity = [];
try {
    $stmt = $conn->prepare("
        (
            SELECT 'post' as type,
                fp.post_id as id,
                fp.content as title,
                fp.posted_at as created_at,
                COALESCE(u.user_first_name, s.stud_first_name) as first_name,
                COALESCE(u.user_last_name, s.stud_last_name) as last_name,
                f.title as forum_name
            FROM forum_post fp
            JOIN actor a ON fp.poster_id = a.actor_id
            LEFT JOIN user u ON a.entity_type = 'user' AND a.entity_id = u.user_id
            LEFT JOIN student s ON a.entity_type = 'student' AND a.entity_id = s.stud_id
            JOIN forum f ON fp.forum_id = f.forum_id
            ORDER BY fp.posted_at DESC
            LIMIT 1
        )
        UNION
        (
            SELECT 'comment' as type,
                fc.comment_id as id,
                CONCAT('Comment on: ', SUBSTRING(fp.content, 1, 20)) as title,
                fc.commented_at as created_at,
                COALESCE(u.user_first_name, s.stud_first_name) as first_name,
                COALESCE(u.user_last_name, s.stud_last_name) as last_name,
                f.title as forum_name
            FROM forum_comment fc
            JOIN actor a ON fc.commenter_id = a.actor_id
            LEFT JOIN user u ON a.entity_type = 'user' AND a.entity_id = u.user_id
            LEFT JOIN student s ON a.entity_type = 'student' AND a.entity_id = s.stud_id
            JOIN forum_post fp ON fc.post_id = fp.post_id
            JOIN forum f ON fp.forum_id = f.forum_id
            ORDER BY fc.commented_at DESC
            LIMIT 1
        )
        UNION
        (
            SELECT 'forum' as type,
                f.forum_id as id,
                f.title as title,
                f.created_at,
                COALESCE(u.user_first_name, s.stud_first_name) as first_name,
                COALESCE(u.user_last_name, s.stud_last_name) as last_name,
                '' as forum_name
            FROM forum f
            JOIN actor a ON f.created_by = a.actor_id
            LEFT JOIN user u ON a.entity_type = 'user' AND a.entity_id = u.user_id
            LEFT JOIN student s ON a.entity_type = 'student' AND a.entity_id = s.stud_id
            ORDER BY f.created_at DESC
            LIMIT 1
        )
        ORDER BY created_at DESC;

    ");
    $stmt->execute();
    $recent_activity = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching recent activity: " . $e->getMessage());
}

// Fetch moderation stats
$moderation_stats = [];
try {
    $stmt = $conn->prepare("
        SELECT 
            (SELECT COUNT(*) FROM forum_post WHERE posted_at >= CURDATE()) as posts_today,
            (SELECT COUNT(*) FROM report WHERE status = 'pending') as pending_review,
            (SELECT COUNT(*) FROM report WHERE status = 'resolved' AND resolved_at >= CURDATE()) as resolved_today,
            (SELECT COUNT(*) FROM report WHERE resolution = 'approved') as approved_count,
            (SELECT COUNT(*) FROM report WHERE resolution = 'edited') as edited_count,
            (SELECT COUNT(*) FROM report WHERE resolution = 'deleted') as deleted_count
    ");
    $stmt->execute();
    $moderation_stats = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching moderation stats: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forum Content Moderation</title>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">

    <style>
        :root {
            --primary-color: #0A2647;
            --primary-light: #1a3a6a;
            --secondary-color: #2C7865;
            --accent-color: #FFD700;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --success-color: #28a745;
            --info-color: #17a2b8;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --gray-color: #6c757d;
            --background-light: #F5F5F5;
            --text-dark: #333333;
            --text-light: #f8f9fa;
            --shadow-color: rgba(0, 0, 0, 0.1);
            --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition-fast: all 0.15s ease;
            --transition-medium: all 0.3s ease;
            --transition-slow: all 0.5s ease;
            --border-radius-sm: 0.25rem;
            --border-radius-md: 0.5rem;
            --border-radius-lg: 1rem;
            --sidebar-width: 280px;
            --sidebar-collapsed-width: 80px;
        }

        body {
            background-color: var(--background-light);
            color: var(--text-dark);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
            transition: var(--transition-medium);
        }

        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            width: var(--sidebar-width);
            background: var(--primary-color);
            color: var(--text-light);
            transition: var(--transition-medium);
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
            color: var(--text-light);
            transition: var(--transition-medium);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-toggle:hover {
            color: var(--accent-color);
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

        .nav-item {
            position: relative;
            margin: 5px 10px;
            border-radius: var(--border-radius-sm);
            overflow: hidden;
            transition: var(--transition-medium);
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: var(--text-light);
            text-decoration: none;
            transition: var(--transition-medium);
            white-space: nowrap;
            border-radius: var(--border-radius-sm);
        }

        .nav-link:hover {
            background: var(--primary-light);
            color: var(--accent-color);
        }

        .nav-link.active {
            background: var(--primary-light);
            color: var(--accent-color);
            font-weight: 500;
        }

        .nav-link i {
            margin-right: 10px;
            width: 24px;
            text-align: center;
            font-size: 1.1rem;
            transition: var(--transition-medium);
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
            background: var(--primary-color);
            padding: 8px 15px;
            border-radius: var(--border-radius-sm);
            white-space: nowrap;
            pointer-events: none;
            box-shadow: var(--card-shadow);
            z-index: 1001;
            font-size: 0.9rem;
        }

        .sidebar.collapsed .nav-item:hover span {
            opacity: 1;
            transform: translate(10px, -50%);
        }

        /* Main Content */
        .main-content {
            margin-left: calc(var(--sidebar-width) + 20px);
            padding: 20px;
            transition: var(--transition-medium);
        }

        .sidebar.collapsed + .main-content {
            margin-left: calc(var(--sidebar-collapsed-width) + 20px);
        }

        /* Dark Mode */
        body.dark-mode {
            background-color: #121212;
            color: #e0e0e0;
            --background-light: #1e1e1e;
            --text-dark: #e0e0e0;
            --primary-color: #0d1b2a;
            --primary-light: #1b263b;
        }

        body.dark-mode .card {
            background-color: #2d2d2d;
            border-color: #444;
        }

        body.dark-mode .table {
            color: #e0e0e0;
        }

        body.dark-mode .table th {
            background-color: var(--primary-light);
        }

        body.dark-mode .table-hover tbody tr:hover {
            background-color: rgba(255, 255, 255, 0.05);
        }

        /* Cards */
        .card {
            border: none;
            border-radius: var(--border-radius-md);
            box-shadow: var(--card-shadow);
            transition: var(--transition-medium);
            margin-bottom: 20px;
            background-color: white;
        }

        .card:hover {
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .card-header {
            background-color: var(--primary-color);
            color: white;
            border-bottom: none;
            border-radius: var(--border-radius-md) var(--border-radius-md) 0 0 !important;
            padding: 15px 20px;
            font-weight: 600;
        }

        /* Tables */
        .table {
            margin-bottom: 0;
        }

        .table th {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
            padding: 12px 15px;
        }

        .table td {
            padding: 12px 15px;
            vertical-align: middle;
        }

        .table-hover tbody tr {
            transition: var(--transition-fast);
        }

        .table-hover tbody tr:hover {
            background-color: rgba(0, 0, 0, 0.02);
        }

        /* Badges */
        .badge {
            font-weight: 500;
            padding: 5px 8px;
            font-size: 0.75rem;
        }

        /* Buttons */
        .btn {
            border-radius: var(--border-radius-sm);
            font-weight: 500;
            padding: 8px 15px;
            transition: var(--transition-fast);
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 0.8rem;
        }

        .btn i {
            margin-right: 5px;
        }

        /* Action Buttons */
        .action-buttons .btn {
            margin-right: 5px;
            min-width: 30px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        /* Content Containers */
        .content-container {
            position: relative;
            padding-bottom: 25px;
            max-height: 100px;
            overflow: hidden;
        }

        .content-container.expanded {
            max-height: none;
        }

        .read-more {
            position: absolute;
            bottom: 0;
            right: 0;
            display: inline-block;
            padding: 3px 8px;
            background-color: var(--primary-color);
            color: white;
            border-radius: var(--border-radius-sm);
            font-size: 0.8rem;
            font-weight: 500;
            text-decoration: none;
            transition: var(--transition-fast);
            z-index: 1;
            cursor: pointer;
        }

        .read-more:hover {
            background-color: var(--primary-light);
            color: white;
            text-decoration: none;
        }

        /* User Avatar */
        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 10px;
            border: 2px solid var(--light-color);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        /* Stats Cards */
        .stat-card {
            border-radius: var(--border-radius-md);
            padding: 15px;
            text-align: center;
            transition: var(--transition-medium);
            height: 100%;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .stat-card .stat-value {
            font-size: 2rem;
            font-weight: 700;
            margin: 10px 0;
        }

        .stat-card .stat-label {
            font-size: 0.9rem;
            color: var(--gray-color);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Activity List */
        .activity-list {
            list-style: none;
            padding: 0;
        }

        .activity-item {
            padding: 12px 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            transition: var(--transition-fast);
        }

        .activity-item:hover {
            background-color: rgba(0, 0, 0, 0.02);
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            color: white;
            font-size: 1rem;
        }

        .activity-icon.post {
            background-color: var(--info-color);
        }

        .activity-icon.comment {
            background-color: var(--success-color);
        }

        .activity-icon.forum {
            background-color: var(--warning-color);
        }

        /* Tabs */
        .nav-tabs {
            border-bottom: 2px solid rgba(0, 0, 0, 0.05);
        }

        .nav-tabs .nav-link {
            border: none;
            color: var(--gray-color);
            font-weight: 500;
            padding: 12px 20px;
            transition: var(--transition-fast);
        }

        .nav-tabs .nav-link:hover {
            border: none;
            color: var(--primary-color);
        }

        .nav-tabs .nav-link.active {
            background-color: transparent;
            color: var(--primary-color);
            border-bottom: 2px solid var(--primary-color);
            font-weight: 600;
        }

        /* Search Box */
        .search-box {
            position: relative;
        }

        .search-box input {
            padding-left: 40px;
            border-radius: 20px;
            border: 1px solid rgba(0, 0, 0, 0.1);
            transition: var(--transition-fast);
        }

        .search-box input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(10, 38, 71, 0.1);
        }

        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-color);
        }

        /* Charts */
        .chart-container {
            position: relative;
            height: 250px;
            width: 100%;
        }

        /* Animations */
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* Responsive Adjustments */
        @media (max-width: 992px) {
            .main-content {
                margin-left: 0;
                padding-top: 70px;
            }
            
            .sidebar {
                transform: translateX(-100%);
                width: var(--sidebar-width);
                z-index: 1050;
            }
            
            .sidebar.collapsed {
                transform: translateX(0);
                width: var(--sidebar-collapsed-width);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
        }

        @media (max-width: 768px) {
            .card-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .search-box {
                width: 100%;
                margin-top: 10px;
            }
            
            .table-responsive {
                border: none;
            }
        }

        /* Loading Spinner */
        .loading-spinner {
            display: inline-block;
            width: 1.5rem;
            height: 1.5rem;
            border: 0.25em solid currentColor;
            border-right-color: transparent;
            border-radius: 50%;
            animation: spinner-border 0.75s linear infinite;
            vertical-align: -0.125em;
        }

        @keyframes spinner-border {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <nav class="sidebar" id="sidebar">
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
            <!-- Page Header -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="h3 mb-2">Forum Moderation Dashboard</h1>
                            <p class="mb-0 text-muted">Welcome back, <?php echo $full_name; ?> <span class="badge bg-primary"><?php echo $role_title; ?></span></p>
                        </div>
                        <button class="btn btn-primary d-lg-none" id="mobileMenuToggle">
                            <i class="fas fa-bars"></i>
                        </button>
                    </div>
                    <hr>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stat-card bg-light">
                        <div class="stat-icon text-primary">
                            <i class="fas fa-comment-alt fa-2x"></i>
                        </div>
                        <div class="stat-value text-primary"><?php echo htmlspecialchars($moderation_stats['posts_today'] ?? 0); ?></div>
                        <div class="stat-label">Posts Today</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card bg-light">
                        <div class="stat-icon text-warning">
                            <i class="fas fa-flag fa-2x"></i>
                        </div>
                        <div class="stat-value text-warning"><?php echo htmlspecialchars($moderation_stats['pending_review'] ?? 0); ?></div>
                        <div class="stat-label">Pending Review</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card bg-light">
                        <div class="stat-icon text-success">
                            <i class="fas fa-check-circle fa-2x"></i>
                        </div>
                        <div class="stat-value text-success"><?php echo htmlspecialchars($moderation_stats['resolved_today'] ?? 0); ?></div>
                        <div class="stat-label">Resolved Today</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card bg-light">
                        <div class="stat-icon text-info">
                            <i class="fas fa-chart-line fa-2x"></i>
                        </div>
                        <div class="stat-value text-info"><?php echo count($reported_posts) + count($reported_comments) + count($reported_users); ?></div>
                        <div class="stat-label">Total Reports</div>
                    </div>
                </div>
            </div>

            <!-- Main Content Area -->
            <div class="row">
                <div class="col-lg-8">
                    <div class="card animate__animated animate__fadeIn">
                        <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-flag me-2"></i>
                                <span>Reported Content</span>
                            </div>
                            <div class="search-box mt-2 mt-md-0">
                                <i class="fas fa-search"></i>
                                <input type="text" class="form-control" id="searchReports" placeholder="Search reports...">
                            </div>
                        </div>
                        <div class="card-body">
                            <ul class="nav nav-tabs mb-4" id="reportedTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="posts-tab" data-bs-toggle="tab" data-bs-target="#posts" type="button" role="tab">
                                        <i class="fas fa-comment-alt me-1"></i>
                                        Posts (<?php echo count($reported_posts); ?>)
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="comments-tab" data-bs-toggle="tab" data-bs-target="#comments" type="button" role="tab">
                                        <i class="fas fa-comments me-1"></i>
                                        Comments (<?php echo count($reported_comments); ?>)
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="users-tab" data-bs-toggle="tab" data-bs-target="#users" type="button" role="tab">
                                        <i class="fas fa-users me-1"></i>
                                        Users (<?php echo count($reported_users); ?>)
                                    </button>
                                </li>
                            </ul>
                            
                            <div class="tab-content" id="reportedTabsContent">
                                <!-- Posts Tab -->
                                <div class="tab-pane fade show active" id="posts" role="tabpanel">
                                    <?php if (!empty($reported_posts)): ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th width="5%">ID</th>
                                                        <th width="25%">Post Content</th>
                                                        <th width="15%">Author</th>
                                                        <th width="15%">Forum</th>
                                                        <th width="10%">Reports</th>
                                                        <th width="10%">Posted</th>
                                                        <th width="10%">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($reported_posts as $post): ?>
                                                    <tr class="animate__animated animate__fadeIn">
                                                        <td><?php echo htmlspecialchars($post['post_id']); ?></td>
                                                        <td>
                                                            <div class="content-container">
                                                                <span class="short-text"><?php echo htmlspecialchars(substr($post['post_content'], 0, 100)); ?>...</span>
                                                                <span class="full-text" style="display: none;"><?php echo nl2br(htmlspecialchars($post['post_content'])); ?></span>
                                                                <a class="read-more" onclick="toggleReadMore(this)">Read more</a>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($post['user_first_name'].'+'.$post['user_last_name']); ?>&background=random" class="user-avatar" alt="User">
                                                            <?php echo htmlspecialchars($post['user_first_name'] . ' ' . $post['user_last_name']); ?>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($post['forum_name']); ?></td>
                                                        <td>
                                                            <span class="badge bg-danger rounded-pill" data-bs-toggle="tooltip" title="<?php echo htmlspecialchars($post['report_reasons']); ?>">
                                                                <?php echo htmlspecialchars($post['report_count']); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <small class="text-muted" data-bs-toggle="tooltip" title="<?php echo htmlspecialchars($post['created_at']); ?>">
                                                                <?php echo time_elapsed_string($post['created_at']); ?>
                                                            </small>
                                                        </td>
                                                        <td class="action-buttons">
                                                            <button class="btn btn-sm btn-success" data-bs-toggle="tooltip" title="Approve" onclick="moderatePost(<?php echo $post['post_id']; ?>, 'approve')">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-warning" data-bs-toggle="tooltip" title="Edit" onclick="editPost(<?php echo $post['post_id']; ?>)">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-danger" data-bs-toggle="tooltip" title="Delete" onclick="moderatePost(<?php echo $post['post_id']; ?>, 'delete')">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-center py-4">
                                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                            <h5>No reported posts found</h5>
                                            <p class="text-muted">All clear! No posts require moderation at this time.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Comments Tab -->
                                <div class="tab-pane fade" id="comments" role="tabpanel">
                                    <?php if (!empty($reported_comments)): ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th width="5%">ID</th>
                                                        <th width="30%">Comment</th>
                                                        <th width="15%">Author</th>
                                                        <th width="15%">In Post</th>
                                                        <th width="10%">Reports</th>
                                                        <th width="15%">Posted</th>
                                                        <th width="10%">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($reported_comments as $comment): ?>
                                                    <tr class="animate__animated animate__fadeIn">
                                                        <td><?php echo htmlspecialchars($comment['comment_id']); ?></td>
                                                        <td><?php echo htmlspecialchars(substr($comment['comment_content'], 0, 50)); ?>...</td>
                                                        <td>
                                                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($comment['user_first_name'].'+'.$comment['user_last_name']); ?>&background=random" class="user-avatar" alt="User">
                                                            <?php echo htmlspecialchars($comment['user_first_name'] . ' ' . $comment['user_last_name']); ?>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($comment['post_content']); ?></td>
                                                        <td><span class="badge bg-danger rounded-pill"><?php echo htmlspecialchars($comment['report_count']); ?></span></td>
                                                        <td>
                                                            <small class="text-muted" data-bs-toggle="tooltip" title="<?php echo htmlspecialchars($comment['created_at']); ?>">
                                                                <?php echo time_elapsed_string($comment['created_at']); ?>
                                                            </small>
                                                        </td>
                                                        <td class="action-buttons">
                                                            <button class="btn btn-sm btn-success" data-bs-toggle="tooltip" title="Approve" onclick="moderateComment(<?php echo $comment['comment_id']; ?>, 'approve')">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-danger" data-bs-toggle="tooltip" title="Delete" onclick="moderateComment(<?php echo $comment['comment_id']; ?>, 'delete')">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-center py-4">
                                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                            <h5>No reported comments found</h5>
                                            <p class="text-muted">All clear! No comments require moderation at this time.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Users Tab -->
                                <div class="tab-pane fade" id="users" role="tabpanel">
                                    <?php if (!empty($reported_users)): ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th width="5%">ID</th>
                                                        <th width="20%">User</th>
                                                        <th width="15%">Email</th>
                                                        <th width="10%">Role</th>
                                                        <th width="10%">Reports</th>
                                                        <th width="20%">Reasons</th>
                                                        <th width="10%">Joined</th>
                                                        <th width="10%">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($reported_users as $user): ?>
                                                    <tr class="animate__animated animate__fadeIn">
                                                        <td><?php echo htmlspecialchars($user['id']); ?></td>
                                                        <td>
                                                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['first_name'].'+'.$user['last_name']); ?>&background=random" class="user-avatar" alt="User">
                                                            <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                        <td><?php echo htmlspecialchars($user['role']); ?></td>
                                                        <td><span class="badge bg-danger rounded-pill"><?php echo htmlspecialchars($user['report_count']); ?></span></td>
                                                        <td>
                                                            <small class="report-reasons" data-bs-toggle="tooltip" title="<?php echo htmlspecialchars($user['reasons']); ?>">
                                                                <?php echo htmlspecialchars(strlen($user['reasons']) > 30 ? substr($user['reasons'], 0, 30) . '...' : $user['reasons']); ?>
                                                            </small>
                                                        </td>
                                                        <td>
                                                            <small class="text-muted" data-bs-toggle="tooltip" title="<?php echo htmlspecialchars($user['created_at']); ?>">
                                                                <?php echo time_elapsed_string($user['created_at']); ?>
                                                            </small>
                                                        </td>
                                                        <td class="action-buttons">
                                                            <button class="btn btn-sm btn-warning" data-bs-toggle="tooltip" title="Warn User" onclick="warnUser(<?php echo $user['id']; ?>, '<?php echo $user['user_type']; ?>')">
                                                                <i class="fas fa-exclamation-triangle"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-danger" data-bs-toggle="tooltip" title="Ban User" onclick="banUser(<?php echo $user['id']; ?>, '<?php echo $user['user_type']; ?>')">
                                                                <i class="fas fa-ban"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-center py-4">
                                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                            <h5>No reported users found</h5>
                                            <p class="text-muted">All clear! No users require moderation at this time.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Sidebar -->
                <div class="col-lg-4">
                    <!-- Recent Activity -->
                    <div class="card mb-4 animate__animated animate__fadeIn">
                        <div class="card-header">
                            <i class="fas fa-history me-2"></i> Recent Activity
                        </div>
                        <div class="card-body">
                            <ul class="activity-list">
                                <?php foreach ($recent_activity as $activity): ?>
                                    <li class="activity-item d-flex">
                                        <div class="activity-icon <?php echo $activity['type']; ?>">
                                            <?php if ($activity['type'] == 'post'): ?>
                                                <i class="fas fa-comment-alt"></i>
                                            <?php elseif ($activity['type'] == 'comment'): ?>
                                                <i class="fas fa-comments"></i>
                                            <?php else: ?>
                                                <i class="fas fa-folder"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between">
                                                <h6 class="mb-1">
                                                    <?php 
                                                        if ($activity['type'] == 'post') {
                                                            echo 'New post in "' . htmlspecialchars($activity['forum_name']) . '"';
                                                        } elseif ($activity['type'] == 'comment') {
                                                            echo 'New comment on "' . htmlspecialchars($activity['title']) . '"';
                                                        } else {
                                                            echo 'New forum created: "' . htmlspecialchars($activity['title']) . '"';
                                                        }
                                                    ?>
                                                </h6>
                                                <small class="text-muted">
                                                    <?php echo time_elapsed_string($activity['created_at']); ?>
                                                </small>
                                            </div>
                                            <p class="mb-1 text-muted">
                                                <?php 
                                                    if ($activity['type'] == 'post') {
                                                        echo htmlspecialchars(substr($activity['title'], 0, 50));
                                                    } elseif ($activity['type'] == 'comment') {
                                                        echo htmlspecialchars($activity['title']);
                                                    } else {
                                                        echo 'Discussion about ' . htmlspecialchars($activity['title']);
                                                    }
                                                ?>...
                                            </p>
                                            <small class="text-muted">
                                                <?php 
                                                    if ($activity['type'] == 'forum') {
                                                        echo 'Created by: ';
                                                    } else {
                                                        echo 'Posted by: ';
                                                    }
                                                    echo htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name']);
                                                ?>
                                            </small>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                                <?php if (empty($recent_activity)): ?>
                                    <li class="activity-item text-center py-3">
                                        <i class="fas fa-info-circle text-muted mb-2"></i>
                                        <p class="mb-0 text-muted">No recent activity</p>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>

                    <!-- Moderation Stats -->
                    <div class="card animate__animated animate__fadeIn">
                        <div class="card-header">
                            <i class="fas fa-chart-pie me-2"></i> Moderation Stats
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="moderationChart"></canvas>
                            </div>
                            <div class="mt-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-success">
                                        <i class="fas fa-check-circle"></i> Approved
                                    </span>
                                    <span class="fw-bold"><?php echo htmlspecialchars($moderation_stats['approved_count'] ?? 0); ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-warning">
                                        <i class="fas fa-edit"></i> Edited
                                    </span>
                                    <span class="fw-bold"><?php echo htmlspecialchars($moderation_stats['edited_count'] ?? 0); ?></span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-danger">
                                        <i class="fas fa-trash"></i> Deleted
                                    </span>
                                    <span class="fw-bold"><?php echo htmlspecialchars($moderation_stats['deleted_count'] ?? 0); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script>
        // Initialize tooltips
        document.addEventListener('DOMContentLoaded', function() {
            // Enable tooltips everywhere
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Initialize chart
            initModerationChart();
            
            // Mobile menu toggle
            document.getElementById('mobileMenuToggle').addEventListener('click', function() {
                document.getElementById('sidebar').classList.toggle('show');
            });
            
            // Dark mode toggle
            document.getElementById('darkModeToggle').addEventListener('click', function() {
                document.body.classList.toggle('dark-mode');
                localStorage.setItem('darkMode', document.body.classList.contains('dark-mode'));
                // Re-render chart with correct colors
                initModerationChart();
            });
            
            // Check for saved dark mode preference
            if (localStorage.getItem('darkMode') === 'true') {
                document.body.classList.add('dark-mode');
            }
        });
        
        // Initialize moderation chart
        function initModerationChart() {
            const ctx = document.getElementById('moderationChart').getContext('2d');
            const isDarkMode = document.body.classList.contains('dark-mode');
            
            const approved = <?php echo $moderation_stats['approved_count'] ?? 0; ?>;
            const edited = <?php echo $moderation_stats['edited_count'] ?? 0; ?>;
            const deleted = <?php echo $moderation_stats['deleted_count'] ?? 0; ?>;
            const total = approved + edited + deleted;
            
            const chart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Approved', 'Edited', 'Deleted'],
                    datasets: [{
                        data: [approved, edited, deleted],
                        backgroundColor: [
                            '#28a745',
                            '#ffc107',
                            '#dc3545'
                        ],
                        borderColor: isDarkMode ? '#444' : '#fff',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: isDarkMode ? '#e0e0e0' : '#333',
                                font: {
                                    family: "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif"
                                }
                            }
                        },
                        tooltip: {
                            bodyFont: {
                                family: "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif"
                            },
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                    return `${label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    },
                    cutout: '70%'
                }
            });
        }
        
        // Toggle read more/less for content
        function toggleReadMore(element) {
            event.preventDefault();
            
            const container = element.parentElement;
            const shortText = container.querySelector('.short-text');
            const fullText = container.querySelector('.full-text');
            
            if (fullText.style.display === 'none') {
                shortText.style.display = 'none';
                fullText.style.display = 'inline';
                element.textContent = 'Read less';
                container.classList.add('expanded');
            } else {
                shortText.style.display = 'inline';
                fullText.style.display = 'none';
                element.textContent = 'Read more';
                container.classList.remove('expanded');
            }
        }
        
        // Search functionality for reported content
        document.getElementById('searchReports').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const activeTab = document.querySelector('.tab-pane.active');
            
            activeTab.querySelectorAll('tbody tr').forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
        
        // Post moderation actions
        function moderatePost(postId, action) {
            let title, text, confirmButtonText, icon;
            
            if (action === 'approve') {
                title = 'Approve Post';
                text = 'This will mark the post as approved and clear all reports.';
                confirmButtonText = 'Yes, approve';
                icon = 'success';
            } else {
                title = 'Delete Post';
                text = 'This will permanently delete the post and all associated content.';
                confirmButtonText = 'Yes, delete';
                icon = 'warning';
            }
            
            Swal.fire({
                title: title,
                text: text,
                icon: icon,
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: confirmButtonText,
                showLoaderOnConfirm: true,
                preConfirm: () => {
                    return fetch('moderate_post.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `post_id=${postId}&action=${action}`
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .catch(error => {
                        Swal.showValidationMessage(
                            `Request failed: ${error}`
                        );
                    });
                },
                allowOutsideClick: () => !Swal.isLoading(),
                background: document.body.classList.contains('dark-mode') ? '#2d2d2d' : '#ffffff',
                color: document.body.classList.contains('dark-mode') ? '#e0e0e0' : '#333333'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Success!',
                        text: result.value?.message || 'Action completed successfully',
                        icon: 'success',
                        confirmButtonText: 'OK',
                        background: document.body.classList.contains('dark-mode') ? '#2d2d2d' : '#ffffff',
                        color: document.body.classList.contains('dark-mode') ? '#e0e0e0' : '#333333'
                    }).then(() => {
                        location.reload();
                    });
                }
            });
        }
        
        // Comment moderation actions
        function moderateComment(commentId, action) {
            let title, text, confirmButtonText, icon;
            
            if (action === 'approve') {
                title = 'Approve Comment';
                text = 'This will mark the comment as approved and clear all reports.';
                confirmButtonText = 'Yes, approve';
                icon = 'success';
            } else {
                title = 'Delete Comment';
                text = 'This will permanently delete the comment.';
                confirmButtonText = 'Yes, delete';
                icon = 'warning';
            }
            
            Swal.fire({
                title: title,
                text: text,
                icon: icon,
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: confirmButtonText,
                showLoaderOnConfirm: true,
                preConfirm: () => {
                    return fetch('moderate_comment.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `comment_id=${commentId}&action=${action}`
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .catch(error => {
                        Swal.showValidationMessage(
                            `Request failed: ${error}`
                        );
                    });
                },
                allowOutsideClick: () => !Swal.isLoading(),
                background: document.body.classList.contains('dark-mode') ? '#2d2d2d' : '#ffffff',
                color: document.body.classList.contains('dark-mode') ? '#e0e0e0' : '#333333'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Success!',
                        text: result.value?.message || 'Action completed successfully',
                        icon: 'success',
                        confirmButtonText: 'OK',
                        background: document.body.classList.contains('dark-mode') ? '#2d2d2d' : '#ffffff',
                        color: document.body.classList.contains('dark-mode') ? '#e0e0e0' : '#333333'
                    }).then(() => {
                        location.reload();
                    });
                }
            });
        }
        
        // User moderation actions
        function warnUser(userId, userType) {
            Swal.fire({
                title: 'Send Warning to User',
                input: 'textarea',
                inputLabel: 'Warning Message',
                inputPlaceholder: 'Explain why this user is being warned...',
                inputAttributes: {
                    'aria-label': 'Enter warning message'
                },
                showCancelButton: true,
                confirmButtonText: 'Send Warning',
                showLoaderOnConfirm: true,
                preConfirm: (message) => {
                    if (!message) {
                        Swal.showValidationMessage('Please enter a warning message');
                        return false;
                    }
                    
                    return fetch('warn_user.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `user_id=${userId}&user_type=${userType}&message=${encodeURIComponent(message)}`
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .catch(error => {
                        Swal.showValidationMessage(
                            `Request failed: ${error}`
                        );
                    });
                },
                allowOutsideClick: () => !Swal.isLoading(),
                background: document.body.classList.contains('dark-mode') ? '#2d2d2d' : '#ffffff',
                color: document.body.classList.contains('dark-mode') ? '#e0e0e0' : '#333333'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Warning Sent!',
                        text: result.value?.message || 'The user has been warned.',
                        icon: 'success',
                        confirmButtonText: 'OK',
                        background: document.body.classList.contains('dark-mode') ? '#2d2d2d' : '#ffffff',
                        color: document.body.classList.contains('dark-mode') ? '#e0e0e0' : '#333333'
                    }).then(() => {
                        location.reload();
                    });
                }
            });
        }
        
        function banUser(userId, userType) {
            Swal.fire({
                title: 'Ban User',
                html: `
                    <div class="mb-3">
                        <label for="banReason" class="form-label">Reason for Ban</label>
                        <textarea id="banReason" class="form-control" placeholder="Enter the reason for banning this user..." rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="banDuration" class="form-label">Duration</label>
                        <select id="banDuration" class="form-select">
                            <option value="1">1 day</option>
                            <option value="7">1 week</option>
                            <option value="30">1 month</option>
                            <option value="365">1 year</option>
                            <option value="permanent">Permanent</option>
                        </select>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Confirm Ban',
                showLoaderOnConfirm: true,
                preConfirm: () => {
                    const reason = document.getElementById('banReason').value;
                    const duration = document.getElementById('banDuration').value;
                    
                    if (!reason) {
                        Swal.showValidationMessage('Please enter a reason for the ban');
                        return false;
                    }
                    
                    return fetch('ban_user.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `user_id=${userId}&user_type=${userType}&reason=${encodeURIComponent(reason)}&duration=${duration}`
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .catch(error => {
                        Swal.showValidationMessage(
                            `Request failed: ${error}`
                        );
                    });
                },
                allowOutsideClick: () => !Swal.isLoading(),
                background: document.body.classList.contains('dark-mode') ? '#2d2d2d' : '#ffffff',
                color: document.body.classList.contains('dark-mode') ? '#e0e0e0' : '#333333'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'User Banned!',
                        text: result.value?.message || 'The user has been banned.',
                        icon: 'success',
                        confirmButtonText: 'OK',
                        background: document.body.classList.contains('dark-mode') ? '#2d2d2d' : '#ffffff',
                        color: document.body.classList.contains('dark-mode') ? '#e0e0e0' : '#333333'
                    }).then(() => {
                        location.reload();
                    });
                }
            });
        }
        
        // Edit post functionality
        function editPost(postId) {
            // Show loading state
            Swal.fire({
                title: 'Loading post content...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                },
                background: document.body.classList.contains('dark-mode') ? '#2d2d2d' : '#ffffff',
                color: document.body.classList.contains('dark-mode') ? '#e0e0e0' : '#333333'
            });
            
            // Fetch post content
            fetch(`get_post.php?post_id=${postId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    Swal.close();
                    
                    if (data.success) {
                        Swal.fire({
                            title: 'Edit Post',
                            input: 'textarea',
                            inputValue: data.content,
                            inputAttributes: {
                                'aria-label': 'Edit the post content'
                            },
                            showCancelButton: true,
                            confirmButtonText: 'Save Changes',
                            showLoaderOnConfirm: true,
                            preConfirm: (editedContent) => {
                                return fetch('edit_post.php', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/x-www-form-urlencoded',
                                    },
                                    body: `post_id=${postId}&content=${encodeURIComponent(editedContent)}`
                                })
                                .then(response => {
                                    if (!response.ok) {
                                        throw new Error('Network response was not ok');
                                    }
                                    return response.json();
                                })
                                .catch(error => {
                                    Swal.showValidationMessage(
                                        `Request failed: ${error}`
                                    );
                                });
                            },
                            allowOutsideClick: () => !Swal.isLoading(),
                            background: document.body.classList.contains('dark-mode') ? '#2d2d2d' : '#ffffff',
                            color: document.body.classList.contains('dark-mode') ? '#e0e0e0' : '#333333'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                Swal.fire({
                                    title: 'Post Updated!',
                                    text: result.value?.message || 'The post has been successfully updated.',
                                    icon: 'success',
                                    confirmButtonText: 'OK',
                                    background: document.body.classList.contains('dark-mode') ? '#2d2d2d' : '#ffffff',
                                    color: document.body.classList.contains('dark-mode') ? '#e0e0e0' : '#333333'
                                }).then(() => {
                                    location.reload();
                                });
                            }
                        });
                    } else {
                        Swal.fire({
                            title: 'Error!',
                            text: data.message || 'Failed to fetch post content.',
                            icon: 'error',
                            confirmButtonText: 'OK',
                            background: document.body.classList.contains('dark-mode') ? '#2d2d2d' : '#ffffff',
                            color: document.body.classList.contains('dark-mode') ? '#e0e0e0' : '#333333'
                        });
                    }
                })
                .catch(error => {
                    Swal.fire({
                        title: 'Error!',
                        text: 'Failed to fetch post content.',
                        icon: 'error',
                        confirmButtonText: 'OK',
                        background: document.body.classList.contains('dark-mode') ? '#2d2d2d' : '#ffffff',
                        color: document.body.classList.contains('dark-mode') ? '#e0e0e0' : '#333333'
                    });
                });
        }
        
        // Logout confirmation
        function confirmLogout(e) {
            e.preventDefault();
            const logoutUrl = e.currentTarget.getAttribute('href');
            
            Swal.fire({
                title: 'Ready to Logout?',
                text: "You'll need to sign in again to access the dashboard.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, logout',
                background: document.body.classList.contains('dark-mode') ? '#2d2d2d' : '#ffffff',
                color: document.body.classList.contains('dark-mode') ? '#e0e0e0' : '#333333'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = logoutUrl;
                }
            });
        }
        
        // Sidebar toggle functionality
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('collapsed');
            localStorage.setItem('sidebarCollapsed', document.getElementById('sidebar').classList.contains('collapsed'));
        });
        
        // Check for saved sidebar state
        if (localStorage.getItem('sidebarCollapsed') === 'true') {
            document.getElementById('sidebar').classList.add('collapsed');
        }
    </script>
</body>
</html>

<?php
// Helper function to display time in "time ago" format
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    // Manually calculate weeks from days
    $weeks = floor($diff->d / 7);
    $remaining_days = $diff->d % 7;  // Get the remaining days

    // Prepare the time units for output
    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );

    // Adjust for the week and day outputs
    foreach ($string as $k => &$v) {
        if ($k === 'w') {
            // Use the manually calculated weeks
            if ($weeks > 0) {
                $v = $weeks . ' ' . $v . ($weeks > 1 ? 's' : '');
            } else {
                unset($string[$k]); // Remove weeks if no weeks
            }
        } elseif ($k === 'd') {
            // Use the remaining days instead of $diff->d
            $v = $remaining_days . ' ' . $v . ($remaining_days > 1 ? 's' : '');
        } elseif ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    // If full time is not required, slice to show only the first unit
    if (!$full) $string = array_slice($string, 0, 1);

    // Return the final output
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

?>