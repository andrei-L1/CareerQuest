<?php
require_once '../config/dbcon.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if both user_id and stud_id are not set simultaneously
if (isset($_SESSION['user_id']) && isset($_SESSION['stud_id'])) {
    echo "Error: Both user and student IDs are set. Only one should be set.";
    exit;
}

// Check if neither user_id nor stud_id is set
if (!isset($_SESSION['user_id']) && !isset($_SESSION['stud_id'])) {
    header("Location: ../index.php");
    exit;
}

// Initialize current user data
if (isset($_SESSION['user_id'])) {
    $currentUser = [
        'entity_type' => 'user',
        'entity_id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_first_name'] ?? 'User',
        'role' => 'Unknown',
        'email' => $_SESSION['user_email'] ?? '',
        'picture' => $_SESSION['picture_file'] ?? ''
    ];

    // Fetch additional details from the user table
    $user_id = $currentUser['entity_id'];
    $query = "SELECT u.*, r.role_title FROM user u LEFT JOIN role r ON u.role_id = r.role_id WHERE u.user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$user_id]);
    $userDetails = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($userDetails) {
        $currentUser['full_name'] = $userDetails['user_first_name'] . ' ' . $userDetails['user_last_name'];
        $currentUser['email'] = $userDetails['user_email'];
        $currentUser['picture'] = $userDetails['picture_file'];
        $currentUser['status'] = $userDetails['status'];
        $currentUser['role'] = $userDetails['role_title'];
    }
    
    // Ensure actor record exists
    $query = "SELECT actor_id FROM actor WHERE entity_type = 'user' AND entity_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$user_id]);
    $actor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$actor) {
        // Create actor record if it doesn't exist
        $query = "INSERT INTO actor (entity_type, entity_id) VALUES ('user', ?)";
        $stmt = $conn->prepare($query);
        $stmt->execute([$user_id]);
        $currentUser['actor_id'] = $conn->lastInsertId();
    } else {
        $currentUser['actor_id'] = $actor['actor_id'];
    }
} else {
    // Student is logged in
    $currentUser = [
        'entity_type' => 'student',
        'entity_id' => $_SESSION['stud_id'],
        'name' => $_SESSION['stud_first_name'] ?? 'Student',
        'role' => 'Student',
        'email' => $_SESSION['stud_email'] ?? '',
        'picture' => $_SESSION['profile_picture'] ?? ''
    ];

    // Fetch additional details from the student table
    $stud_id = $currentUser['entity_id'];
    $query = "SELECT * FROM student WHERE stud_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$stud_id]);
    $studentDetails = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($studentDetails) {
        $currentUser['full_name'] = $studentDetails['stud_first_name'] . ' ' . $studentDetails['stud_last_name'];
        $currentUser['email'] = $studentDetails['stud_email'];
        $currentUser['picture'] = $studentDetails['profile_picture'];
        $currentUser['status'] = $studentDetails['status'];
    }
    
    // Ensure actor record exists
    $query = "SELECT actor_id FROM actor WHERE entity_type = 'student' AND entity_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$stud_id]);
    $actor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$actor) {
        // Create actor record if it doesn't exist
        $query = "INSERT INTO actor (entity_type, entity_id) VALUES ('student', ?)";
        $stmt = $conn->prepare($query);
        $stmt->execute([$stud_id]);
        $currentUser['actor_id'] = $conn->lastInsertId();
    } else {
        $currentUser['actor_id'] = $actor['actor_id'];
    }
}

// Fetch all forums from the database
$query = "SELECT f.*, 
                 CONCAT(COALESCE(u.user_first_name, s.stud_first_name), ' ', COALESCE(u.user_last_name, s.stud_last_name)) AS creator_name,
                 COUNT(fp.post_id) AS post_count,
                 (SELECT COUNT(*) FROM forum_membership fm WHERE fm.forum_id = f.forum_id AND fm.deleted_at IS NULL) AS member_count
          FROM forum f
          LEFT JOIN actor a ON f.created_by = a.actor_id
          LEFT JOIN user u ON (a.entity_type = 'user' AND a.entity_id = u.user_id)
          LEFT JOIN student s ON (a.entity_type = 'student' AND a.entity_id = s.stud_id)
          LEFT JOIN forum_post fp ON f.forum_id = fp.forum_id
          WHERE f.deleted_at IS NULL
          GROUP BY f.forum_id
          ORDER BY f.title ASC";
$stmt = $conn->prepare($query);
$stmt->execute();
$forums = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check if a specific forum is selected
$selectedForumId = $_GET['forum_id'] ?? null;
$selectedForum = null;
$forumPosts = [];
$bannedFromForum = false;
$isPrivateForum = false;

if ($selectedForumId) {
    // Get the selected forum details
    $query = "SELECT f.*, 
                     CONCAT(COALESCE(u.user_first_name, s.stud_first_name), ' ', COALESCE(u.user_last_name, s.stud_last_name)) AS creator_name,
                     f.is_private
              FROM forum f
              LEFT JOIN actor a ON f.created_by = a.actor_id
              LEFT JOIN user u ON (a.entity_type = 'user' AND a.entity_id = u.user_id)
              LEFT JOIN student s ON (a.entity_type = 'student' AND a.entity_id = s.stud_id)
              WHERE f.forum_id = ? AND f.deleted_at IS NULL";
    $stmt = $conn->prepare($query);
    $stmt->execute([$selectedForumId]);
    $selectedForum = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($selectedForum) {
        $isPrivateForum = (bool)$selectedForum['is_private'];
        
        // Check membership status
        $query = "SELECT role, status FROM forum_membership 
                  WHERE forum_id = ? AND actor_id = ? AND deleted_at IS NULL";
        $stmt = $conn->prepare($query);
        $stmt->execute([$selectedForumId, $currentUser['actor_id']]);
        $membership = $stmt->fetch(PDO::FETCH_ASSOC);

        $currentUser['forum_role'] = $membership['role'] ?? null;
        $currentUser['forum_status'] = $membership['status'] ?? null;
        $currentUser['is_pending'] = ($currentUser['forum_status'] === 'Pending');
        
        // Check if user is banned from this forum
        if ($currentUser['forum_status'] === 'Banned') {
            $bannedFromForum = true;
        }
        
        // Get posts for this forum (only if not banned and either not private or a member)
        if (!$bannedFromForum && (!$isPrivateForum || $currentUser['forum_role'] || $currentUser['forum_status'] === 'Pending')) {
            $query = "SELECT fp.*, 
                            CONCAT(COALESCE(u.user_first_name, s.stud_first_name), ' ', COALESCE(u.user_last_name, s.stud_last_name)) AS poster_name,
                            COALESCE(u.picture_file, s.profile_picture) AS poster_picture,
                            COUNT(fc.comment_id) AS comment_count
                    FROM forum_post fp
                    LEFT JOIN actor a ON fp.poster_id = a.actor_id
                    LEFT JOIN user u ON (a.entity_type = 'user' AND a.entity_id = u.user_id)
                    LEFT JOIN student s ON (a.entity_type = 'student' AND a.entity_id = s.stud_id)
                    LEFT JOIN forum_comment fc ON fp.post_id = fc.post_id AND fc.deleted_at IS NULL
                    LEFT JOIN forum_membership fm ON fm.forum_id = fp.forum_id AND fm.actor_id = fp.poster_id
                    WHERE fp.forum_id = ? 
                        AND fp.deleted_at IS NULL
                        AND fm.status = 'Active'
                    GROUP BY fp.post_id
                    ORDER BY fp.is_pinned DESC, fp.posted_at DESC";
            $stmt = $conn->prepare($query);
            $stmt->execute([$selectedForumId]);
            $forumPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
}

// Fetch forums where user is a member for the sidebar
$query = "SELECT f.forum_id, f.title, fm.role 
          FROM forum_membership fm
          JOIN forum f ON fm.forum_id = f.forum_id
          WHERE fm.actor_id = ? AND fm.deleted_at IS NULL AND f.deleted_at IS NULL
          ORDER BY f.title ASC";
$stmt = $conn->prepare($query);
$stmt->execute([$currentUser['actor_id']]);
$memberForums = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Career Platform Forum</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #3b82f6;
            --secondary-color: #64748b;
            --accent-color: #10b981;
            --danger-color: #ef4444;
            --light-gray: #f1f5f9;
            --dark-color: #1e293b;
            --border-radius: 8px;
            --card-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            --transition: all 0.2s ease-in-out;
        }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background-color: #f8fafc;
            margin: 0;
            min-height: 100vh;
            display: flex;
        }

        .forum-container {
            width: calc(100% - 70px); /* Account for sidebar-nav width */
            margin-left: 70px; /* Shift to accommodate sidebar-nav */
            display: flex;
            background-color: white;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .forum-sidebar {
            width: 280px;
            background-color: white;
            border-right: 1px solid var(--light-gray);
            transition: var(--transition);
            position: fixed;
            top: 0;
            left: 70px; /* Position to the right of sidebar-nav */
            height: 100vh;
            z-index: 1100; /* Below sidebar-nav */
        }

        .sidebar-header {
            padding: 1rem;
            border-bottom: 1px solid var(--light-gray);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            overflow: hidden;
            background-color: var(--light-gray);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .user-name {
            font-weight: 600;
            font-size: 0.95rem;
            color: var(--dark-color);
        }

        .user-role {
            font-size: 0.8rem;
            color: var(--secondary-color);
        }

        .new-forum-btn {
            background: none;
            border: none;
            color: var(--secondary-color);
            font-size: 1.25rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .new-forum-btn:hover {
            color: var(--primary-color);
            transform: scale(1.1);
        }

        .forum-navigation {
            padding: 1rem;
            flex-grow: 1;
            overflow-y: auto;
        }

        .nav-section {
            margin-bottom: 1.5rem;
        }

        .nav-title {
            font-size: 0.75rem;
            text-transform: uppercase;
            color: var(--secondary-color);
            margin-bottom: 0.75rem;
            font-weight: 600;
        }

        .nav-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .nav-item a {
            display: flex;
            align-items: center;
            padding: 0.5rem 0.75rem;
            color: var(--dark-color);
            text-decoration: none;
            border-radius: var(--border-radius);
            transition: var(--transition);
        }

        .nav-item a i {
            margin-right: 0.5rem;
            font-size: 1rem;
        }

        .nav-item a:hover {
            background-color: var(--light-gray);
            color: var(--primary-color);
        }

        .nav-item.active a {
            background-color: #eff6ff;
            color: var(--primary-color);
            font-weight: 500;
        }

        /* Main Content Area */
        .forum-content {
            flex: 1;
            padding: 1.5rem;
            overflow-y: auto;
            max-width: 1200px;
            margin: 0 auto;
        }

        .forum-header {
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .forum-title {
            font-weight: 700;
            color: var(--dark-color);
            font-size: 1.5rem;
            margin: 0;
        }

        .forum-header p {
            color: var(--secondary-color);
            margin: 0.5rem 0 0;
            font-size: 0.9rem;
        }

        .forum-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .forum-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1rem;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            border: 1px solid var(--light-gray);
            display: flex;
            flex-direction: column;
        }

        .forum-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
        }

        .forum-card h3 {
            margin: 0 0 0.75rem;
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary-color);
        }

        .forum-card h3 a {
            color: inherit;
            text-decoration: none;
        }

        .forum-card h3 a:hover {
            text-decoration: underline;
        }

        .forum-card p {
            color: var(--secondary-color);
            margin-bottom: 1rem;
            flex-grow: 1;
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .forum-meta {
            display: flex;
            justify-content: space-between;
            font-size: 0.8rem;
            color: var(--secondary-color);
            margin-top: auto;
            padding-top: 0.75rem;
            border-top: 1px solid var(--light-gray);
        }

        .post-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .post-item {
            background: white;
            border-radius: var(--border-radius);
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            border: 1px solid var(--light-gray);
        }

        .post-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .post-header {
            display: flex;
            align-items: center;
            margin-bottom: 0.75rem;
        }

        .post-author-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            overflow: hidden;
            margin-right: 0.75rem;
            background-color: var(--light-gray);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .post-author-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .post-author-info {
            flex: 1;
        }

        .post-author-name {
            font-weight: 600;
            margin: 0;
            color: var(--dark-color);
            font-size: 0.95rem;
        }

        .post-date {
            font-size: 0.8rem;
            color: var(--secondary-color);
            margin-top: 0.25rem;
        }

        .post-title {
            font-size: 1.1rem;
            margin: 0.5rem 0;
            color: var(--dark-color);
            font-weight: 600;
        }

        .post-content {
            margin-bottom: 1rem;
            color: var(--secondary-color);
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .post-content a {
            color: var(--primary-color);
            text-decoration: none;
        }

        .post-content a:hover {
            text-decoration: underline;
        }

        .post-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.85rem;
            padding-top: 0.75rem;
            border-top: 1px solid var(--light-gray);
        }

        .post-actions {
            display: flex;
            gap: 1rem;
        }

        .post-actions a {
            color: var(--secondary-color);
            text-decoration: none;
            display: flex;
            align-items: center;
            transition: var(--transition);
        }

        .post-actions a:hover {
            color: var(--primary-color);
        }

        .post-actions a i {
            margin-right: 0.4rem;
        }

        .post-views {
            color: var(--secondary-color);
            display: flex;
            align-items: center;
        }

        .post-views i {
            margin-right: 0.4rem;
        }

        .pinned-badge {
            background-color: var(--primary-color);
            color: white;
            padding: 0.2rem 0.5rem;
            border-radius: 6px;
            font-size: 0.75rem;
            margin-left: 0.5rem;
        }

        .new-post-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            transition: var(--transition);
        }

        .new-post-btn:hover {
            background-color: #2563eb;
            transform: translateY(-1px);
            box-shadow: var(--card-shadow);
        }

        .alert {
            border-radius: var(--border-radius);
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: var(--card-shadow);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .alert i {
            font-size: 1.25rem;
        }

        .alert h4 {
            margin: 0;
            font-size: 1rem;
            font-weight: 600;
        }

        /* Toast Notification */
        .toast-container {
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 1300; /* Above both sidebars */
        }

        .toast {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            padding: 1rem;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            opacity: 0;
            transition: opacity 0.3s, transform 0.3s;
            transform: translateX(100%);
        }

        .toast.show {
            opacity: 1;
            transform: translateX(0);
        }

        .toast-success {
            border-left: 4px solid var(--accent-color);
        }

        .toast-error {
            border-left: 4px solid var(--danger-color);
        }

        /* Responsive Design */
        @media (max-width: 767px) {
            .forum-container {
                width: 100%;
                margin-left: 0; /* Remove sidebar-nav offset on mobile */
                flex-direction: column;
                padding-bottom: 60px; /* Space for bottom sidebar-nav */
            }

            .forum-sidebar {
                width: 100%;
                max-height: 60px;
                overflow: hidden;
                transition: max-height 0.3s ease-in-out;
                position: relative;
                left: 0; /* Reset position for mobile */
                z-index: 1100;
            }

            .forum-sidebar.active {
                max-height: 100vh;
            }

            .forum-content {
                padding: 1rem;
            }

            .forum-list {
                grid-template-columns: 1fr;
            }

            .sidebar-toggle {
                display: block;
                position: absolute;
                top: 1rem;
                right: 1rem;
                background: none;
                border: none;
                font-size: 1.5rem;
                color: var(--primary-color);
                cursor: pointer;
                z-index: 1150; /* Above forum-sidebar but below sidebar-nav */
            }
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .forum-card, .post-item {
            animation: fadeIn 0.3s ease-out forwards;
        }

        .like-btn.active {
            color: var(--primary-color);
            font-weight: 500;
        }

        .like-btn i {
            transition: transform 0.2s;
        }

        .like-btn:active i {
            transform: scale(1.2);
        }

        /* Accessibility */
        .nav-item a:focus {
            outline: 2px solid var(--primary-color);
            outline-offset: 2px;
        }

        .new-post-btn:focus, .new-forum-btn:focus {
            outline: 2px solid var(--primary-color);
            outline-offset: 2px;
        }
    </style>
</head>
<body>
    <div class="forum-container">
        <!-- Sidebar Navigation -->
        <?php require '../includes/forum_sidebar.php'; ?>
        <div class="forum-sidebar">
            <button class="sidebar-toggle d-none" aria-label="Toggle Sidebar">
                <i class="bi bi-list"></i>
            </button>
            <div class="sidebar-header">
                <div class="user-info">
                    <div class="user-avatar" aria-label="User Profile Picture">
                        <?php if (!empty($currentUser['picture'])): ?>
                            <img src="../Uploads/<?php echo htmlspecialchars($currentUser['picture']); ?>" alt="Profile Picture of <?php echo htmlspecialchars($currentUser['name']); ?>">
                        <?php else: ?>
                            <i class="bi bi-person-circle text-muted"></i>
                        <?php endif; ?>
                    </div>
                    <div class="user-details">
                        <div class="user-name"><?php echo htmlspecialchars($currentUser['name']); ?></div>
                        <div class="user-role"><?php echo htmlspecialchars($currentUser['role']); ?></div>
                    </div>
                </div>
                <button id="new-forum-btn" class="new-forum-btn" title="Create New Forum" data-bs-toggle="modal" data-bs-target="#newForumModal" aria-label="Create New Forum">
                    <i class="bi bi-plus-lg"></i>
                </button>
            </div>
            
            <div class="forum-navigation">
                <div class="nav-section">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="nav-title mb-0">MAIN NAVIGATION</h6>
                    </div>
                    <ul class="nav-links">
                        <li class="nav-item <?php echo !$selectedForumId ? 'active' : ''; ?>">
                            <a href="forums.php" class="d-flex align-items-center" aria-current="<?php echo !$selectedForumId ? 'page' : ''; ?>">
                                <i class="bi bi-house-door"></i>
                                <span>Home</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="d-flex align-items-center" aria-label="Notifications (3 unread)">
                                <i class="bi bi-bell"></i>
                                <span>Notifications</span>
                                <span class="badge bg-danger ms-auto">3</span>
                            </a>
                        </li>
                    </ul>
                </div>
                
                <div class="nav-section">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="nav-title mb-0">MY FORUMS</h6>
                        <span class="badge bg-primary rounded-pill"><?php echo count($memberForums); ?></span>
                    </div>
                    <ul class="nav-links">
                        <?php foreach ($memberForums as $forum): ?>
                            <li class="nav-item <?php echo $selectedForumId == $forum['forum_id'] ? 'active' : ''; ?>">
                                <a href="forums.php?forum_id=<?php echo $forum['forum_id']; ?>" class="d-flex align-items-center" aria-current="<?php echo $selectedForumId == $forum['forum_id'] ? 'page' : ''; ?>">
                                    <i class="bi bi-people-fill"></i>
                                    <span class="text-truncate"><?php echo htmlspecialchars($forum['title']); ?></span>
                                    <?php if ($forum['role'] !== 'Member'): ?>
                                        <span class="badge ms-auto bg-<?php 
                                            echo $forum['role'] === 'Admin' ? 'danger' : 
                                            ($forum['role'] === 'Moderator' ? 'primary' : 'secondary'); 
                                        ?>">
                                            <?php echo $forum['role']; ?>
                                        </span>
                                    <?php endif; ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <div class="nav-section">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="nav-title mb-0">ALL FORUMS</h6>
                        <span class="badge bg-secondary rounded-pill"><?php echo count($forums); ?></span>
                    </div>
                    <ul class="nav-links">
                        <?php foreach ($forums as $forum): 
                            $isPending = ($membership['status'] ?? null) === 'Pending';
                        ?>
                            <li class="nav-item <?php echo $selectedForumId == $forum['forum_id'] ? 'active' : ''; ?>">
                                <a href="forums.php?forum_id=<?php echo $forum['forum_id']; ?>" class="d-flex align-items-center" aria-current="<?php echo $selectedForumId == $forum['forum_id'] ? 'page' : ''; ?>">
                                    <i class="bi bi-collection"></i>
                                    <span class="text-truncate"><?php echo htmlspecialchars($forum['title']); ?></span>
                                    <span class="ms-auto d-flex align-items-center">
                                        <?php if ($isPending): ?>
                                            <span class="badge bg-warning text-dark me-1">Pending</span>
                                        <?php endif; ?>
                                        <span class="badge bg-light text-dark"><?php echo $forum['post_count']; ?></span>
                                    </span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Main Forum Content -->
        <div class="forum-content">
            <?php if ($selectedForum): ?>
                <?php if ($bannedFromForum): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-slash-circle"></i>
                        <div>
                            <h4>Access Denied</h4>
                            <p>You have been banned from this forum and cannot view or participate in discussions.</p>
                            <p>If you believe this is an error, please contact the forum administrators.</p>
                        </div>
                    </div>
                <?php elseif ($isPrivateForum && !$currentUser['forum_role'] && !$currentUser['is_pending']): ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-lock"></i>
                        <div>
                            <h4>Private Forum</h4>
                            <p>This is a private forum. You need to join to view content.</p>
                            <form action="../forum/join_forum.php" method="POST" class="mt-3">
                                <input type="hidden" name="forum_id" value="<?php echo $selectedForumId; ?>">
                                <button type="submit" class="btn btn-primary new-post-btn" aria-label="Request to Join Forum">
                                    <i class="bi bi-door-open"></i> Request to Join
                                </button>
                            </form>
                        </div>
                    </div>
                <?php elseif ($isPrivateForum && $currentUser['is_pending']): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-hourglass"></i>
                        <div>
                            <h4>Pending Approval</h4>
                            <p>Your request to join this private forum is pending approval by the forum administrators.</p>
                            <form action="../forum/cancel_join_request.php" method="POST">
                                <input type="hidden" name="forum_id" value="<?php echo $selectedForumId; ?>">
                                <button type="submit" class="btn btn-outline-danger" aria-label="Cancel Join Request">
                                    <i class="bi bi-x-circle"></i> Cancel Request
                                </button>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="forum-header">
                        <div>
                            <h2 class="forum-title"><?php echo htmlspecialchars($selectedForum['title']); ?></h2>
                            <p class="text-muted mb-0"><?php echo htmlspecialchars($selectedForum['description']); ?></p>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <?php if ($currentUser['forum_role']): ?>
                                <form action="../forum/leave_forum.php" method="POST">
                                    <input type="hidden" name="forum_id" value="<?php echo $selectedForumId; ?>">
                                    <button type="submit" class="btn btn-outline-danger" aria-label="Leave Forum">
                                        <i class="bi bi-door-closed"></i> Leave
                                    </button>
                                </form>
                            <?php elseif (!$isPrivateForum): ?>
                                <form action="../forum/join_forum.php" method="POST">
                                    <input type="hidden" name="forum_id" value="<?php echo $selectedForumId; ?>">
                                    <button type="submit" class="btn btn-outline-primary" aria-label="Join Forum">
                                        <i class="bi bi-door-open"></i> Join
                                    </button>
                                </form>
                            <?php endif; ?>
                            
                            <?php if (in_array($currentUser['forum_role'], ['Moderator', 'Admin'])): ?>
                                <a href="../forum/manage_forum.php?forum_id=<?php echo $selectedForumId; ?>" 
                                   class="btn btn-outline-secondary" aria-label="Manage Forum">
                                    <i class="bi bi-gear"></i> Manage
                                </a>
                            <?php endif; ?>
                            
                            <a href="../forum/new_post.php?forum_id=<?php echo $selectedForumId; ?>" 
                               class="btn btn-primary new-post-btn" aria-label="Create New Post">
                                <i class="bi bi-plus-lg"></i> New Post
                            </a>
                        </div>
                    </div>
                    
                    <?php if (count($forumPosts) > 0): ?>
                        <ul class="post-list">
                            <?php foreach ($forumPosts as $post): ?>
                                <li class="post-item clickable-post" data-post-id="<?php echo $post['post_id']; ?>" role="article">
                                    <div class="post-header">
                                        <div class="post-author-avatar" aria-hidden="true">
                                            <?php if (!empty($post['poster_picture'])): ?>
                                                <img src="../Uploads/<?php echo htmlspecialchars($post['poster_picture']); ?>" alt="Profile Picture of <?php echo htmlspecialchars($post['poster_name']); ?>">
                                            <?php else: ?>
                                                <i class="bi bi-person-fill text-muted"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="post-author-info">
                                            <div class="post-author-name"><?php echo htmlspecialchars($post['poster_name']); ?></div>
                                            <div class="post-date"><?php echo date('M j, Y g:i a', strtotime($post['posted_at'])); ?></div>
                                        </div>
                                        <?php if ($post['is_pinned']): ?>
                                            <span class="pinned-badge"><i class="bi bi-pin-angle"></i> Pinned</span>
                                        <?php endif; ?>
                                    </div>
                                    <h3 class="post-title">
                                        <a href="../forum/post.php?post_id=<?php echo $post['post_id']; ?>" style="color: inherit; text-decoration: none;">
                                            <?php echo htmlspecialchars($post['post_title']); ?>
                                        </a>
                                    </h3>
                                    <div class="post-content">
                                        <?php echo nl2br(htmlspecialchars(substr($post['content'], 0, 200))); ?>
                                        <?php if (strlen($post['content']) > 200): ?>... 
                                            <a href="../forum/post.php?post_id=<?php echo $post['post_id']; ?>">Read more</a>
                                        <?php endif; ?>
                                    </div>
                                    <div class="post-footer">
                                        <div class="post-actions">
                                            <a href="#" class="like-btn" data-post-id="<?php echo $post['post_id']; ?>" 
                                               aria-label="Like Post (<?php echo $post['up_count']; ?> likes)">
                                                <i class="bi bi-hand-thumbs-up"></i> Like (<span class="like-count"><?php echo $post['up_count']; ?></span>)
                                            </a>
                                            <a href="../forum/post.php?post_id=<?php echo $post['post_id']; ?>" 
                                               aria-label="View Comments (<?php echo $post['comment_count']; ?> comments)">
                                                <i class="bi bi-chat"></i> Comments (<?php echo $post['comment_count']; ?>)
                                            </a>
                                        </div>
                                        <div class="post-views">
                                            <i class="bi bi-eye"></i> <?php echo $post['view_count']; ?> views
                                        </div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div class="alert alert-info mt-3">
                            <i class="bi bi-info-circle"></i>
                            <div>
                                No posts yet in this forum. Be the first to post!
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                <?php else: ?>
                    <div class="forum-header">
                        <h2 class="forum-title">All Forums</h2>
                        <p class="text-muted">Browse and join forums to participate in discussions</p>
                    </div>
                    
                    <?php if (count($forums) > 0): ?>
                        <div class="forum-list">
                            <?php foreach ($forums as $forum): 
                                $query = "SELECT status FROM forum_membership 
                                          WHERE forum_id = ? AND actor_id = ? AND deleted_at IS NULL";
                                $stmt = $conn->prepare($query);
                                $stmt->execute([$forum['forum_id'], $currentUser['actor_id']]);
                                $membership = $stmt->fetch(PDO::FETCH_ASSOC);
                                $isPending = ($membership['status'] ?? null) === 'Pending';
                            ?>
                                <div class="forum-card" role="article">
                                    <h3>
                                        <a href="forums.php?forum_id=<?php echo $forum['forum_id']; ?>" style="color: inherit; text-decoration: none;">
                                            <?php echo htmlspecialchars($forum['title']); ?>
                                            <?php if ($forum['is_private']): ?>
                                                <i class="bi bi-lock text-muted ms-1" aria-label="Private Forum"></i>
                                            <?php endif; ?>
                                        </a>
                                    </h3>
                                    <p><?php echo htmlspecialchars($forum['description']); ?></p>
                                    <div class="forum-meta">
                                        <span><i class="bi bi-person"></i> <?php echo htmlspecialchars($forum['creator_name']); ?></span>
                                        <span>
                                            <i class="bi bi-people"></i> <?php echo $forum['member_count']; ?> members • 
                                            <i class="bi bi-chat"></i> <?php echo $forum['post_count']; ?> posts
                                            <?php if ($isPending): ?>
                                                <span class="badge bg-warning text-dark ms-2">Pending Approval</span>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            <div>
                                No forums available yet. Create the first forum!
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- New Forum Modal -->
        <div class="modal fade" id="newForumModal" tabindex="-1" aria-labelledby="newForumModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="newForumModalLabel">Create New Forum</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="../forum/create_forum.php" method="POST">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="forumTitle" class="form-label">Forum Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="forumTitle" name="title" required aria-describedby="forumTitleHelp">
                                <div id="forumTitleHelp" class="form-text">Enter a concise and descriptive title for your forum.</div>
                            </div>
                            <div class="mb-3">
                                <label for="forumDescription" class="form-label">Description <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="forumDescription" name="description" rows="4" required aria-describedby="forumDescriptionHelp"></textarea>
                                <div id="forumDescriptionHelp" class="form-text">Provide a brief description of the forum's purpose.</div>
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="forumPrivate" name="is_private" aria-describedby="forumPrivateHelp">
                                <label class="form-check-label" for="forumPrivate">Make this a private forum</label>
                                <div id="forumPrivateHelp" class="form-text">Private forums require approval to join.</div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary new-post-btn">Create Forum</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Toast Container -->
        <div class="toast-container"></div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Initialize Bootstrap tooltips for sidebar-nav
                const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
                const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));

                // Sidebar toggle for mobile
                const sidebar = document.querySelector('.forum-sidebar');
                const toggleBtn = document.querySelector('.sidebar-toggle');
                if (toggleBtn) {
                    toggleBtn.addEventListener('click', () => {
                        sidebar.classList.toggle('active');
                        toggleBtn.innerHTML = sidebar.classList.contains('active') 
                            ? '<i class="bi bi-x-lg"></i>' 
                            : '<i class="bi bi-list"></i>';
                    });
                }

                // Navigation item click handlers
                document.querySelectorAll('.nav-item a').forEach(item => {
                    item.addEventListener('click', function() {
                        document.querySelectorAll('.nav-item').forEach(navItem => {
                            navItem.classList.remove('active');
                        });
                        this.parentElement.classList.add('active');
                        if (window.innerWidth <= 767) {
                            sidebar.classList.remove('active');
                            toggleBtn.innerHTML = '<i class="bi bi-list"></i>';
                        }
                    });
                });

                // Post click handler
                document.querySelectorAll('.clickable-post').forEach(post => {
                    post.addEventListener('click', function(e) {
                        if (e.target.closest('a, button')) return;
                        const postId = this.dataset.postId;
                        window.location.href = `../forum/post.php?post_id=${postId}`;
                    });
                });

                // Like button handler with toast notification
                document.querySelectorAll('.like-btn').forEach(button => {
                    const postId = button.dataset.postId;
                    if (localStorage.getItem(`liked_post_${postId}`)) {
                        button.classList.add('active');
                        button.style.pointerEvents = 'none';
                    }

                    button.addEventListener('click', function(e) {
                        e.preventDefault();
                        if (localStorage.getItem(`liked_post_${postId}`)) {
                            showToast('You already liked this post.', 'error');
                            return;
                        }

                        button.classList.add('disabled');
                        fetch('../forum/like_post.php', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                            body: `post_id=${postId}`
                        })
                        .then(res => res.json())
                        .then(data => {
                            button.classList.remove('disabled');
                            if (data.success) {
                                const countSpan = button.querySelector('.like-count');
                                countSpan.textContent = data.like_count;
                                localStorage.setItem(`liked_post_${postId}`, 'true');
                                button.classList.add('active');
                                button.style.pointerEvents = 'none';
                                showToast('Post liked successfully!', 'success');
                            } else {
                                showToast(data.message || 'Failed to like post.', 'error');
                            }
                        })
                        .catch(err => {
                            button.classList.remove('disabled');
                            showToast('An error occurred.', 'error');
                            console.error('Error:', err);
                        });
                    });
                });

                // Toast notification function
                function showToast(message, type = 'success') {
                    const toastContainer = document.querySelector('.toast-container');
                    const toast = document.createElement('div');
                    toast.className = `toast toast-${type} show`;
                    toast.innerHTML = `
                        <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                        <span>${message}</span>
                    `;
                    toastContainer.appendChild(toast);
                    setTimeout(() => {
                        toast.classList.remove('show');
                        setTimeout(() => toast.remove(), 300);
                    }, 3000);
                }
            });
        </script>
</body>
</html>