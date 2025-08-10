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

// Define constants
const UPLOADS_DIR = '../uploads/';
const DEFAULT_AVATAR = "https://cdn-icons-png.flaticon.com/512/847/847969.png";




// Validate file path to prevent directory traversal
function getSafeProfilePicture($filePath) {
    if (!$filePath || strpos($filePath, '..') !== false || !file_exists(UPLOADS_DIR . $filePath)) {
        return DEFAULT_AVATAR;
    }
    return UPLOADS_DIR . $filePath;
}

try {
    $stmt = $conn->prepare("
        SELECT u.user_first_name, u.user_last_name, u.picture_file, r.role_title
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
    $user_profile_picture = getSafeProfilePicture($user['picture_file']);

} catch (Exception $e) {
    error_log("Dashboard Error: " . $e->getMessage());
    header("Location: ../auth/logout.php");
    exit();
}

// Pagination settings
$posts_per_page = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $posts_per_page;

// Fetch reported posts with profile pictures
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
            CASE 
                WHEN a.entity_type = 'user' THEN u.picture_file
                WHEN a.entity_type = 'student' THEN s.profile_picture
            END AS profile_picture,
            a.actor_id,
            a.entity_type
        FROM forum_post fp
        JOIN report r ON r.content_type = 'post' AND r.content_id = fp.post_id
        JOIN forum f ON fp.forum_id = f.forum_id
        JOIN actor a ON fp.poster_id = a.actor_id
        LEFT JOIN user u ON a.entity_type = 'user' AND a.entity_id = u.user_id
        LEFT JOIN student s ON a.entity_type = 'student' AND a.entity_id = s.stud_id
        WHERE r.status = 'pending'
        GROUP BY fp.post_id, fp.content, f.forum_id, f.title, fp.posted_at, 
                 a.entity_type, u.user_first_name, u.user_last_name, 
                 s.stud_first_name, s.stud_last_name, u.picture_file, s.profile_picture, a.actor_id
        ORDER BY report_count DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindParam(':limit', $posts_per_page, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $reported_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total count for pagination
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT fp.post_id) as total FROM forum_post fp JOIN report r ON r.content_type = 'post' AND r.content_id = fp.post_id WHERE r.status = 'pending'");
    $stmt->execute();
    $total_posts = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_posts / $posts_per_page);

} catch (PDOException $e) {
    error_log("Error fetching reported posts: " . $e->getMessage());
}

// Fetch reported comments
$reported_comments = [];
try {
    $stmt = $conn->prepare("
        SELECT 
            fc.comment_id,
            fc.content AS comment_content,
            fc.commented_at AS created_at,
            COALESCE(u.user_first_name, s.stud_first_name) AS user_first_name,
            COALESCE(u.user_last_name, s.stud_last_name) AS user_last_name,
            COALESCE(u.picture_file, s.profile_picture) AS profile_picture,
            fp.content AS post_content,
            COUNT(r.report_id) AS report_count
        FROM forum_comment fc
        JOIN actor a ON fc.commenter_id = a.actor_id
        LEFT JOIN user u ON a.entity_type = 'user' AND a.entity_id = u.user_id
        LEFT JOIN student s ON a.entity_type = 'student' AND a.entity_id = s.stud_id
        JOIN forum_post fp ON fc.post_id = fp.post_id
        JOIN report r ON fc.comment_id = r.content_id AND r.content_type = 'comment'
        WHERE r.status = 'pending'
        GROUP BY fc.comment_id
        ORDER BY report_count DESC
        LIMIT 3
    ");
    $stmt->execute();
    $reported_comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Debugging: Log the number of rows fetched
    error_log("Fetched " . count($reported_comments) . " reported comments.");
} catch (PDOException $e) {
    error_log("Error fetching reported comments: " . $e->getMessage());
}
$reported_users = [];
try {
    $stmt = $conn->prepare("
        SELECT 
            CASE 
                WHEN a.entity_type = 'user' THEN u.user_id
                WHEN a.entity_type = 'student' THEN s.stud_id
            END AS id,
            CASE 
                WHEN a.entity_type = 'user' THEN u.user_first_name
                WHEN a.entity_type = 'student' THEN s.stud_first_name
            END AS first_name,
            CASE 
                WHEN a.entity_type = 'user' THEN u.user_last_name
                WHEN a.entity_type = 'student' THEN s.stud_last_name
            END AS last_name,
            CASE 
                WHEN a.entity_type = 'user' THEN u.user_email
                WHEN a.entity_type = 'student' THEN s.stud_email
            END AS email,
            CASE 
                WHEN a.entity_type = 'user' THEN u.picture_file
                WHEN a.entity_type = 'student' THEN s.profile_picture
            END AS profile_picture,
            CASE 
                WHEN a.entity_type = 'user' THEN r.role_title
                WHEN a.entity_type = 'student' THEN 'Student'
            END AS role,
            COUNT(re.report_id) AS report_count,
            GROUP_CONCAT(DISTINCT re.reason SEPARATOR ', ') AS reasons,
            CASE 
                WHEN a.entity_type = 'user' THEN u.created_at
                WHEN a.entity_type = 'student' THEN s.created_at
            END AS created_at,
            a.entity_type AS user_type
        FROM report re
        LEFT JOIN actor a 
            ON re.content_id = a.actor_id
        LEFT JOIN user u 
            ON a.entity_type = 'user' AND a.entity_id = u.user_id
        LEFT JOIN role r 
            ON u.role_id = r.role_id
        LEFT JOIN student s 
            ON a.entity_type = 'student' AND a.entity_id = s.stud_id
        WHERE re.status = 'pending'
          AND (a.entity_type = 'user' OR a.entity_type = 'student')
        GROUP BY id, first_name, last_name, email, profile_picture, role, created_at, user_type
        ORDER BY report_count DESC
        LIMIT 10
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
            SELECT 'post' AS type,
                fp.post_id AS id,
                fp.content AS title,
                fp.posted_at AS created_at,
                COALESCE(u.user_first_name, s.stud_first_name) AS first_name,
                COALESCE(u.user_last_name, s.stud_last_name) AS last_name,
                COALESCE(u.picture_file, s.profile_picture) AS profile_picture,
                f.title AS forum_name
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
            SELECT 'comment' AS type,
                fc.comment_id AS id,
                CONCAT('Comment on: ', SUBSTRING(fp.content, 1, 20)) AS title,
                fc.commented_at AS created_at,
                COALESCE(u.user_first_name, s.stud_first_name) AS first_name,
                COALESCE(u.user_last_name, s.stud_last_name) AS last_name,
                COALESCE(u.picture_file, s.profile_picture) AS profile_picture,
                f.title AS forum_name
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
            SELECT 'forum' AS type,
                f.forum_id AS id,
                f.title AS title,
                f.created_at,
                COALESCE(u.user_first_name, s.stud_first_name) AS first_name,
                COALESCE(u.user_last_name, s.stud_last_name) AS last_name,
                COALESCE(u.picture_file, s.profile_picture) AS profile_picture,
                '' AS forum_name
            FROM forum f
            JOIN actor a ON f.created_by = a.actor_id
            LEFT JOIN user u ON a.entity_type = 'user' AND a.entity_id = u.user_id
            LEFT JOIN student s ON a.entity_type = 'student' AND a.entity_id = s.stud_id
            ORDER BY f.created_at DESC
            LIMIT 1
        )
        ORDER BY created_at DESC
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
            (SELECT COUNT(*) FROM forum_post WHERE posted_at >= CURDATE()) AS posts_today,
            (SELECT COUNT(*) FROM report WHERE status = 'pending') AS pending_review,
            (SELECT COUNT(*) FROM report WHERE status = 'resolved' AND resolved_at >= CURDATE()) AS resolved_today,
            (SELECT COUNT(*) FROM report WHERE resolution = 'approved') AS approved_count,
            (SELECT COUNT(*) FROM report WHERE resolution = 'edited') AS edited_count,
            (SELECT COUNT(*) FROM report WHERE resolution = 'deleted') AS deleted_count
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #0A2647;
            --secondary-color: #2C7865;
            --accent-color: #FFD700;
            --background-light: #F5F5F5;
            --text-dark: #333333;
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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
            transition: var(--sidebar-transition);
        }

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

        .main-content {
            margin-left: 350px;
            margin-right: 150px;
            padding: 20px;
            transition: margin-left 0.3s ease;
        }

        .sidebar.collapsed + .main-content {
            margin-left: 200px;
        }

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

        .badge-moderation {
            background-color: #dc3545;
        }

        .badge-normal {
            background-color: #28a745;
        }

        .post-content {
            max-height: 100px;
            overflow: hidden;
            position: relative;
        }

        .post-content.expanded {
            max-height: none;
        }

        .content-container {
            position: relative;
            padding-bottom: 40px;
        }

        .read-more {
            position: absolute;
            bottom: 10px;
            right: 10px;
            display: inline-block;
            padding: 3px 7px;
            background-color: var(--primary-color);
            color: #fff;
            border-radius: 3px;
            font-size: 0.9rem;
            font-weight: 400;
            text-decoration: none;
            transition: background-color 0.3s ease, transform 0.2s ease;
            z-index: 1;
        }

        .read-more:hover {
            background-color: blue;
            transform: translateY(-1px);
        }

        .read-more:active {
            transform: translateY(1px);
        }

        .moderation-action {
            cursor: pointer;
            transition: all 0.2s;
        }

        .moderation-action:hover {
            transform: scale(1.1);
        }

        .nav-tabs .nav-link.active {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .nav-tabs .nav-link {
            color: var(--primary-color);
        }

        .table th {
            background-color: var(--primary-color);
            color: white;
        }

        .action-buttons .btn {
            margin-right: 5px;
        }

        .search-box {
            position: relative;
            margin-bottom: 20px;
        }

        .search-box input {
            padding-left: 40px;
            border-radius: 20px;
        }

        .search-box i {
            position: absolute;
            left: 15px;
            top: 10px;
            color: #6c757d;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 10px;
        }

        .post-meta {
            font-size: 0.85rem;
            color: #6c757d;
        }

        .post-card {
            transition: transform 0.2s;
        }

        .post-card:hover {
            transform: translateY(-5px);
        }

        .status-badge {
            font-size: 0.75rem;
            padding: 3px 8px;
            border-radius: 10px;
        }

        .pagination .page-item.active .page-link {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .pagination .page-link {
            color: var(--primary-color);
        }

        .time-ago {
            font-size: 0.8rem;
            color: #6c757d;
        }

        .action-buttons .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }

        .loading-spinner {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 9999;
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
            <div class="row mb-4 pt-3 pb-2 mb-3 border-bottom">
                <div class="col-12">
                    <h1 class="h2">Forum Moderation</h1>
                    <p class="lead">
                        Welcome, <?php echo $full_name; ?> <span class="badge bg-primary"><?php echo $role_title; ?></span>
                    </p>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-flag me-2"></i> Reported Content</span>
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" class="form-control" id="searchReports" placeholder="Search reported content...">
                            </div>
                        </div>
                        <div class="card-body">
                            <ul class="nav nav-tabs mb-4" id="reportedTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="posts-tab" data-bs-toggle="tab" data-bs-target="#posts" type="button" role="tab">
                                        Posts (<?php echo count($reported_posts); ?>)
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="comments-tab" data-bs-toggle="tab" data-bs-target="#comments" type="button" role="tab">
                                        Comments (<?php echo count($reported_comments); ?>)
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="users-tab" data-bs-toggle="tab" data-bs-target="#users" type="button" role="tab">
                                        Users (<?php echo count($reported_users); ?>)
                                    </button>
                                </li>
                            </ul>

                            <div class="tab-content" id="reportedTabsContent">
                                <div class="tab-pane fade show active" id="posts" role="tabpanel">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th width="5%">ID</th>
                                                    <th width="20%">Post</th>
                                                    <th width="15%">Author</th>
                                                    <th width="15%">Forum</th>
                                                    <th width="15%">Report Reasons</th>
                                                    <th width="10%">Reports</th>
                                                    <th width="10%">Posted</th>
                                                    <th width="10%">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($reported_posts as $post): ?>
                                                    <?php
                                                        $profile_picture = getSafeProfilePicture($post['profile_picture']);
                                                        $content = htmlspecialchars($post['post_content']);
                                                        $short_content = strlen($content) > 100 ? substr($content, 0, 100) . '...' : $content;
                                                        $show_read_more = strlen($content) > 100;
                                                    ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($post['post_id']); ?></td>
                                                        <td>
                                                            <div class="content-container">
                                                                <span class="short-text"><?php echo $short_content; ?></span>
                                                                <?php if ($show_read_more): ?>
                                                                    <span class="full-text" style="display: none;"><?php echo nl2br($content); ?></span>
                                                                    <a href="#" class="read-more" onclick="toggleReadMore(this, 'post')">Read more</a>
                                                                <?php endif; ?>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <img src="<?php echo $profile_picture; ?>" class="user-avatar" alt="User">
                                                            <?php echo htmlspecialchars($post['user_first_name'] . ' ' . $post['user_last_name']); ?>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($post['forum_name']); ?></td>
                                                        <td>
                                                            <div class="content-container">
                                                                <?php
                                                                    $reasons = htmlspecialchars($post['report_reasons']);
                                                                    $short_reasons = strlen($reasons) > 50 ? substr($reasons, 0, 50) . '...' : $reasons;
                                                                    $show_reasons_read_more = strlen($reasons) > 50;
                                                                ?>
                                                                <span class="short-text"><?php echo $short_reasons; ?></span>
                                                                <?php if ($show_reasons_read_more): ?>
                                                                    <span class="full-text" style="display: none;"><?php echo nl2br($reasons); ?></span>
                                                                    <a href="#" class="read-more" onclick="toggleReadMore(this, 'reason')">Read more</a>
                                                                <?php endif; ?>
                                                            </div>
                                                        </td>
                                                        <td><span class="badge bg-danger"><?php echo htmlspecialchars($post['report_count']); ?></span></td>
                                                        <td>
                                                            <span class="time-ago" title="<?php echo htmlspecialchars($post['created_at']); ?>">
                                                                <?php echo time_elapsed_string($post['created_at']); ?>
                                                            </span>
                                                        </td>
                                                        <td class="action-buttons">
                                                            <button class="btn btn-sm btn-success moderation-action" title="Approve" onclick="moderatePost(<?php echo $post['post_id']; ?>, 'approve')">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-warning moderation-action" title="Edit" onclick="editPost(<?php echo $post['post_id']; ?>)">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-danger moderation-action" title="Delete" onclick="moderatePost(<?php echo $post['post_id']; ?>, 'delete')">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                                <?php if (empty($reported_posts)): ?>
                                                    <tr>
                                                        <td colspan="8" class="text-center">No reported posts found</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                        <!-- Pagination -->
                                        <?php if ($total_pages > 1): ?>
                                            <nav aria-label="Page navigation">
                                                <ul class="pagination justify-content-center">
                                                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                                        <a class="page-link" href="?page=<?php echo $page - 1; ?>" aria-label="Previous">
                                                            <span aria-hidden="true">&laquo;</span>
                                                        </a>
                                                    </li>
                                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                                        <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                                        </li>
                                                    <?php endfor; ?>
                                                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                                        <a class="page-link" href="?page=<?php echo $page + 1; ?>" aria-label="Next">
                                                            <span aria-hidden="true">&raquo;</span>
                                                        </a>
                                                    </li>
                                                </ul>
                                            </nav>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="tab-pane fade" id="comments" role="tabpanel">
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
                                                    <?php
                                                        $profile_picture = getSafeProfilePicture($comment['profile_picture']);
                                                        $comment_content = htmlspecialchars($comment['comment_content']);
                                                        $short_comment = strlen($comment_content) > 50 ? substr($comment_content, 0, 50) . '...' : $comment_content;
                                                    ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($comment['comment_id']); ?></td>
                                                        <td>
                                                            <div class="content-container">
                                                                <span class="short-text"><?php echo $short_comment; ?></span>
                                                                <?php if (strlen($comment_content) > 50): ?>
                                                                    <span class="full-text" style="display: none;"><?php echo nl2br($comment_content); ?></span>
                                                                    <a href="#" class="read-more" onclick="toggleReadMore(this, 'comment')">Read more</a>
                                                                <?php endif; ?>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <img src="<?php echo $profile_picture; ?>" class="user-avatar" alt="User">
                                                            <?php echo htmlspecialchars($comment['user_first_name'] . ' ' . $comment['user_last_name']); ?>
                                                        </td>
                                                        <td><?php echo htmlspecialchars(substr($comment['post_content'], 0, 50)); ?>...</td>
                                                        <td><span class="badge bg-danger"><?php echo htmlspecialchars($comment['report_count']); ?></span></td>
                                                        <td>
                                                            <span class="time-ago" title="<?php echo htmlspecialchars($comment['created_at']); ?>">
                                                                <?php echo time_elapsed_string($comment['created_at']); ?>
                                                            </span>
                                                        </td>
                                                        <td class="action-buttons">
                                                            <button class="btn btn-sm btn-success moderation-action" title="Approve" onclick="moderateComment(<?php echo $comment['comment_id']; ?>, 'approve')">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-danger moderation-action" title="Delete" onclick="moderateComment(<?php echo $comment['comment_id']; ?>, 'delete')">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                                <?php if (empty($reported_comments)): ?>
                                                    <tr>
                                                        <td colspan="7" class="text-center">No reported comments found</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <div class="tab-pane fade" id="users" role="tabpanel">
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
                                                    <?php
                                                        $profile_picture = getSafeProfilePicture($user['profile_picture']);
                                                        $reasons = htmlspecialchars($user['reasons']);
                                                        $short_reasons = strlen($reasons) > 30 ? substr($reasons, 0, 30) . '...' : $reasons;
                                                    ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($user['id']); ?></td>
                                                        <td>
                                                            <img src="<?php echo $profile_picture; ?>" class="user-avatar" alt="User">
                                                            <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                        <td><?php echo htmlspecialchars($user['role']); ?></td>
                                                        <td><span class="badge bg-danger"><?php echo htmlspecialchars($user['report_count']); ?></span></td>
                                                        <td>
                                                            <span class="report-reasons" title="<?php echo $reasons; ?>">
                                                                <?php echo $short_reasons; ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <span class="time-ago" title="<?php echo htmlspecialchars($user['created_at']); ?>">
                                                                <?php echo time_elapsed_string($user['created_at']); ?>
                                                            </span>
                                                        </td>
                                                        <td class="action-buttons">
                                                            <button class="btn btn-sm btn-warning moderation-action" title="Warn" onclick="warnUser(<?php echo $user['id']; ?>, '<?php echo $user['user_type']; ?>')">
                                                                <i class="fas fa-exclamation-triangle"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-danger moderation-action" title="Ban" onclick="banUser(<?php echo $user['id']; ?>, '<?php echo $user['user_type']; ?>')">
                                                                    <i class="fas fa-ban"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                                <?php if (empty($reported_users)): ?>
                                                    <tr>
                                                        <td colspan="8" class="text-center">No reported users found</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-comments me-2"></i> Recent Forum Activity
                        </div>
                        <div class="card-body">
                            <div class="list-group">
                                <?php foreach ($recent_activity as $activity): ?>
                                    <?php
                                        $profile_picture = getSafeProfilePicture($activity['profile_picture']);
                                    ?>
                                    <div class="list-group-item">
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
                                        <p class="mb-1">
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
                                            <img src="<?php echo $profile_picture; ?>" class="user-avatar" alt="User">
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
                                <?php endforeach; ?>
                                <?php if (empty($recent_activity)): ?>
                                    <div class="list-group-item">
                                        <p class="mb-1 text-center">No recent activity found</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-chart-bar me-2"></i> Moderation Statistics
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-md-4">
                                    <div class="card bg-light mb-3">
                                        <div class="card-body">
                                            <h3 class="text-primary"><?php echo htmlspecialchars($moderation_stats['posts_today'] ?? 0); ?></h3>
                                            <p class="mb-0">Posts Today</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg-light mb-3">
                                        <div class="card-body">
                                            <h3 class="text-warning"><?php echo htmlspecialchars($moderation_stats['pending_review'] ?? 0); ?></h3>
                                            <p class="mb-0">Pending Review</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg-light mb-3">
                                        <div class="card-body">
                                            <h3 class="text-success"><?php echo htmlspecialchars($moderation_stats['resolved_today'] ?? 0); ?></h3>
                                            <p class="mb-0">Resolved Today</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3">
                                <h6>Moderation Actions</h6>
                                <?php
                                    $total = ($moderation_stats['approved_count'] ?? 0) + ($moderation_stats['edited_count'] ?? 0) + ($moderation_stats['deleted_count'] ?? 0);
                                    $approved_percent = $total > 0 ? round(($moderation_stats['approved_count'] / $total) * 100) : 0;
                                    $edited_percent = $total > 0 ? round(($moderation_stats['edited_count'] / $total) * 100) : 0;
                                    $deleted_percent = $total > 0 ? round(($moderation_stats['deleted_count'] / $total) * 100) : 0;
                                ?>
                                <div class="progress mb-2">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $approved_percent; ?>%;"
                                         aria-valuenow="<?php echo $approved_percent; ?>" aria-valuemin="0" aria-valuemax="100">
                                        Approved (<?php echo $approved_percent; ?>%)
                                    </div>
                                </div>
                                <div class="progress mb-2">
                                    <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo $edited_percent; ?>%;"
                                         aria-valuenow="<?php echo $edited_percent; ?>" aria-valuemin="0" aria-valuemax="100">
                                        Edited (<?php echo $edited_percent; ?>%)
                                    </div>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar bg-danger" role="progressbar" style="width: <?php echo $deleted_percent; ?>%;"
                                         aria-valuenow="<?php echo $deleted_percent; ?>" aria-valuemin="0" aria-valuemax="100">
                                        Deleted (<?php echo $deleted_percent; ?>%)
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="loading-spinner">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assests/sidebar_toggle.js" defer></script>
    <script>
        // Modularized AJAX handler
        const ajaxHandler = {
            async sendRequest(url, data) {
                const spinner = document.querySelector('.loading-spinner');
                spinner.style.display = 'block';
                try {
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: new URLSearchParams(data).toString()
                    });
                    const result = await response.json();
                    spinner.style.display = 'none';
                    return result;
                } catch (error) {
                    spinner.style.display = 'none';
                    throw error;
                }
            },

            showError(message) {
                Swal.fire({
                    title: 'Error!',
                    text: message,
                    icon: 'error',
                    confirmButtonText: 'OK',
                    background: document.body.classList.contains('dark-mode') ? '#1a1a1a' : '#ffffff',
                    color: document.body.classList.contains('dark-mode') ? '#ffffff' : '#333333'
                });
            },

            showSuccess(message) {
                Swal.fire({
                    title: 'Success!',
                    text: message,
                    icon: 'success',
                    confirmButtonText: 'OK',
                    background: document.body.classList.contains('dark-mode') ? '#1a1a1a' : '#ffffff',
                    color: document.body.classList.contains('dark-mode') ? '#ffffff' : '#333333'
                }).then(() => {
                    location.reload();
                });
            }
        };

        // Toggle read more/less
        function toggleReadMore(element, type) {
            event.preventDefault();
            const container = element.parentElement;
            const shortText = container.querySelector('.short-text');
            const fullText = container.querySelector('.full-text');
            
            if (!fullText) return; // Skip if no full text

            if (fullText.style.display === 'none') {
                shortText.style.display = 'none';
                fullText.style.display = 'inline';
                element.textContent = 'Read less';
            } else {
                shortText.style.display = 'inline';
                fullText.style.display = 'none';
                element.textContent = 'Read more';
            }
        }

        // Debounced search
        let searchTimeout;
        document.getElementById('searchReports').addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const searchTerm = e.target.value.toLowerCase();
                const activeTab = document.querySelector('.tab-pane.active');
                
                activeTab.querySelectorAll('tbody tr').forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(searchTerm) ? '' : 'none';
                });
            }, 300);
        });

        // Post moderation actions
        async function moderatePost(postId, action) {
            const config = {
                approve: {
                    title: 'Approve Post',
                    text: 'Are you sure you want to approve this post?',
                    confirmButtonText: 'Yes, approve',
                    icon: 'success'
                },
                delete: {
                    title: 'Delete Post',
                    text: 'Are you sure you want to delete this post? This action cannot be undone.',
                    confirmButtonText: 'Yes, delete',
                    icon: 'warning'
                }
            }[action];

            const result = await Swal.fire({
                title: config.title,
                text: config.text,
                icon: config.icon,
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: config.confirmButtonText,
                background: document.body.classList.contains('dark-mode') ? '#1a1a1a' : '#ffffff',
                color: document.body.classList.contains('dark-mode') ? '#ffffff' : '#333333'
            });

            if (result.isConfirmed) {
                try {
                    const data = await ajaxHandler.sendRequest('../controllers/moderate_post.php', { post_id: postId, action });
                    if (data.success) {
                        ajaxHandler.showSuccess(data.message);
                    } else {
                        ajaxHandler.showError(data.message);
                    }
                } catch (error) {
                    ajaxHandler.showError('An error occurred while processing your request.');
                }
            }
        }

        // Comment moderation actions
        async function moderateComment(commentId, action) {
            const config = {
                approve: {
                    title: 'Approve Comment',
                    text: 'Are you sure you want to approve this comment?',
                    confirmButtonText: 'Yes, approve',
                    icon: 'success'
                },
                delete: {
                    title: 'Delete Comment',
                    text: 'Are you sure you want to delete this comment? This action cannot be undone.',
                    confirmButtonText: 'Yes, delete',
                    icon: 'warning'
                }
            }[action];

            const result = await Swal.fire({
                title: config.title,
                text: config.text,
                icon: config.icon,
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: config.confirmButtonText,
                background: document.body.classList.contains('dark-mode') ? '#1a1a1a' : '#ffffff',
                color: document.body.classList.contains('dark-mode') ? '#ffffff' : '#333333'
            });

            if (result.isConfirmed) {
                try {
                    const data = await ajaxHandler.sendRequest('../controllers/moderate_comment.php', { comment_id: commentId, action });
                    if (data.success) {
                        ajaxHandler.showSuccess(data.message);
                    } else {
                        ajaxHandler.showError(data.message);
                    }
                } catch (error) {
                    ajaxHandler.showError('An error occurred while processing your request.');
                }
            }
        }

        // User moderation actions
        async function warnUser(userId, userType) {
            console.log('warnUser params:', { userId, userType });
            const result = await Swal.fire({
                title: 'Send Warning to User',
                input: 'textarea',
                inputLabel: 'Warning Message',
                inputPlaceholder: 'Enter the warning message to send to this user...',
                showCancelButton: true,
                confirmButtonText: 'Send Warning',
                showLoaderOnConfirm: true,
                preConfirm: async (message) => {
                    try {
                        return await ajaxHandler.sendRequest(
                            '../controllers/warn_user.php', 
                            { user_id: userId, user_type: userType, message }
                        );
                    } catch (error) {
                        Swal.showValidationMessage(`Request failed: ${error}`);
                    }
                },
                allowOutsideClick: () => !Swal.isLoading(),
                background: document.body.classList.contains('dark-mode') ? '#1a1a1a' : '#ffffff',
                color: document.body.classList.contains('dark-mode') ? '#ffffff' : '#333333'
            });

            if (result.isConfirmed) {
                ajaxHandler.showSuccess(result.value.message);
            }
        }


        async function banUser(userId, userType) {
            console.log('banUser params:', { userId, userType }); // Debug
            const result = await Swal.fire({
                title: 'Ban User',
                html: `
                    <div class="form-group">
                        <label for="banReason">Reason for Ban</label>
                        <textarea id="banReason" class="form-control" placeholder="Enter the reason for banning this user..."></textarea>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Ban User',
                showLoaderOnConfirm: true,
                preConfirm: async () => {
                    const reason = document.getElementById('banReason').value;
                    console.log('Sending to ban_user.php:', { user_id: userId, user_type: userType, reason }); // Debug
                    if (!reason) {
                        Swal.showValidationMessage('Please enter a reason for the ban');
                        return false;
                    }
                    try {
                        return await ajaxHandler.sendRequest('../controllers/ban_user.php', {
                            user_id: userId,
                            user_type: userType,
                            reason
                        });
                    } catch (error) {
                        Swal.showValidationMessage(`Request failed: ${error}`);
                    }
                },
                allowOutsideClick: () => !Swal.isLoading(),
                background: document.body.classList.contains('dark-mode') ? '#1a1a1a' : '#ffffff',
                color: document.body.classList.contains('dark-mode') ? '#ffffff' : '#333333'
            });

            if (result.isConfirmed) {
                ajaxHandler.showSuccess(result.value.message);
            }
        }

        async function editPost(postId) {
            try {
                const data = await ajaxHandler.sendRequest(`../controllers/get_post.php?post_id=${postId}`);
                if (!data.success) {
                    ajaxHandler.showError(data.message);
                    return;
                }

                const post = data.post;
                
                const result = await Swal.fire({
                    title: 'Edit Post',
                    html: `
                        <div class="mb-3">
                            <label class="form-label">Post Title</label>
                            <input type="text" id="postTitle" class="form-control" value="${post.post_title || ''}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Content</label>
                            <textarea id="postContent" class="form-control" rows="5">${post.content || ''}</textarea>
                        </div>
                        <div class="form-check mb-3">
                            <input type="checkbox" class="form-check-input" id="isPinned" ${post.is_pinned ? 'checked' : ''}>
                            <label class="form-check-label" for="isPinned">Pin this post</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="isAnnouncement" ${post.is_announcement ? 'checked' : ''}>
                            <label class="form-check-label" for="isAnnouncement">Mark as announcement</label>
                        </div>
                    `,
                    showCancelButton: true,
                    confirmButtonText: 'Save Changes',
                    showLoaderOnConfirm: true,
                    preConfirm: async () => {
                        try {
                            const postTitle = document.getElementById('postTitle').value;
                            const postContent = document.getElementById('postContent').value;
                            const isPinned = document.getElementById('isPinned').checked;
                            const isAnnouncement = document.getElementById('isAnnouncement').checked;
                            
                            if (!postTitle || !postContent) {
                                Swal.showValidationMessage('Title and content are required');
                                return false;
                            }
                            
                            return await ajaxHandler.sendRequest('../controllers/edit_post.php', {
                                post_id: postId,
                                content: postContent,
                                post_title: postTitle,
                                is_pinned: isPinned,
                                is_announcement: isAnnouncement
                            });
                        } catch (error) {
                            Swal.showValidationMessage(`Request failed: ${error}`);
                        }
                    },
                    allowOutsideClick: () => !Swal.isLoading(),
                    background: document.body.classList.contains('dark-mode') ? '#1a1a1a' : '#ffffff',
                    color: document.body.classList.contains('dark-mode') ? '#ffffff' : '#333333'
                });

                if (result.isConfirmed) {
                    ajaxHandler.showSuccess(result.value.message);
                }
            } catch (error) {
                ajaxHandler.showError('Failed to fetch post details.');
            }
        }

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

        // Initialize tooltips
        document.addEventListener('DOMContentLoaded', function() {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.forEach(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));

            document.querySelectorAll('.time-ago').forEach(el => new bootstrap.Tooltip(el));
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $weeks = floor($diff->d / 7);
    $remaining_days = $diff->d % 7;

    $string = [
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    ];

    $output = [];
    foreach ($string as $k => $v) {
        if ($k === 'w' && $weeks > 0) {
            $output[] = $weeks . ' ' . $v . ($weeks > 1 ? 's' : '');
        } elseif ($k === 'd' && $remaining_days > 0) {
            $output[] = $remaining_days . ' ' . $v . ($remaining_days > 1 ? 's' : '');
        } elseif ($k !== 'w' && $diff->$k) {
            $output[] = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        }
    }

    if (!$full) {
        $output = array_slice($output, 0, 1);
    }

    return $output ? implode(', ', $output) . ' ago' : 'just now';
}
?>