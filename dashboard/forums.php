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
            --primary-color: #4361ee;
            --light-gray: #e9ecef;
            --gray-color: #6c757d;
            --dark-color: #212529;
            --border-radius: 12px;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fb;
            height: 100vh;
            margin: 0;
        }

        .forum-container {
            display: flex;
            height: 100vh;
        }

        /* Sidebar Styles */
        .forum-sidebar {
            width: 300px;
            background-color: white;
            border-right: 1px solid var(--light-gray);
            display: flex;
            flex-direction: column;
        }

        .sidebar-header {
            padding: 15px;
            border-bottom: 1px solid var(--light-gray);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .user-info {
            display: flex;
            align-items: center;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            overflow: hidden;
            margin-right: 10px;
            background-color: #e9ecef;
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
            font-size: 14px;
        }

        .user-role {
            font-size: 12px;
            color: var(--gray-color);
        }

        .new-forum-btn {
            background: none;
            border: none;
            color: var(--gray-color);
            font-size: 18px;
            cursor: pointer;
        }

        .new-forum-btn:hover {
            color: var(--primary-color);
        }

        .forum-navigation {
            padding: 15px;
            flex-grow: 1;
        }

        .nav-section {
            margin-bottom: 20px;
        }

        .nav-title {
            font-size: 12px;
            text-transform: uppercase;
            color: var(--gray-color);
            margin-bottom: 10px;
            font-weight: 600;
        }

        .nav-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .nav-item {
            margin-bottom: 5px;
        }

        .nav-item a {
            display: flex;
            align-items: center;
            padding: 8px 10px;
            color: #495057;
            text-decoration: none;
            border-radius: 4px;
            transition: all 0.2s;
        }

        .nav-item a i {
            margin-right: 10px;
            font-size: 16px;
        }

        .nav-item a:hover {
            background-color: var(--light-gray);
            color: var(--primary-color);
        }

        .nav-item.active a {
            background-color: #e7f1ff;
            color: var(--primary-color);
            font-weight: 500;
        }

        /* Main Content Area */
        .forum-content {
            flex: 1;
            padding: 2rem;
            overflow-y: auto;
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
        }

        .forum-header {
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .forum-title {
            font-weight: 700;
            color: #1e293b;
            font-size: 1.8rem;
            margin: 0;
        }

        .forum-header p {
            color: #64748b;
            margin: 0.5rem 0 0;
            font-size: 1rem;
        }

        .forum-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .forum-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            border: 1px solid #e2e8f0;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .forum-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
            border-color: #cbd5e1;
        }

        .forum-card h3 {
            margin: 0 0 1rem;
            color: #2563eb;
            font-size: 1.25rem;
            font-weight: 600;
            display: flex;
            align-items: center;
        }

        .forum-card h3 a {
            color: inherit;
            text-decoration: none;
            transition: color 0.2s;
        }

        .forum-card h3 a:hover {
            color: #1d4ed8;
            text-decoration: underline;
        }

        .forum-card p {
            color: #475569;
            margin-bottom: 1.5rem;
            flex-grow: 1;
            line-height: 1.5;
        }

        .forum-meta {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            color: #64748b;
            margin-top: auto;
            padding-top: 1rem;
            border-top: 1px solid #f1f5f9;
        }

        .forum-meta i {
            margin-right: 0.3rem;
        }

        .post-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .post-item {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.25rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            border: 1px solid #e2e8f0;
            position: relative;
        }

        .post-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.1);
            border-color: #cbd5e1;
        }

        .post-header {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }

        .post-author-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            overflow: hidden;
            margin-right: 1rem;
            background-color: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }


        .post-author-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .post-author-avatar i {
            font-size: 1.5rem;
            color: #94a3b8;
        }

        .post-author-info {
            flex: 1;
            min-width: 0;
        }

        .post-author-name {
            font-weight: 600;
            margin: 0;
            color: #1e293b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .post-date {
            font-size: 0.85rem;
            color: #64748b;
            margin-top: 0.25rem;
        }

        .post-title {
            font-size: 1.25rem;
            margin: 0.5rem 0 1rem;
            color: #1e293b;
            font-weight: 600;
            line-height: 1.4;
        }

        .post-content {
            margin-bottom: 1.25rem;
            color: #475569;
            line-height: 1.6;
        }

        .post-content a {
            color: #2563eb;
            text-decoration: none;
        }

        .post-content a:hover {
            text-decoration: underline;
        }

        .post-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.9rem;
            padding-top: 1rem;
            border-top: 1px solid #f1f5f9;
        }

        .post-actions {
            display: flex;
            gap: 1.25rem;
        }

        .post-actions a {
            color: #64748b;
            text-decoration: none;
            display: flex;
            align-items: center;
            transition: all 0.2s;
        }

        .post-actions a i {
            margin-right: 0.4rem;
            font-size: 1rem;
        }

        .post-actions a:hover {
            color: #2563eb;
        }

        .post-views {
            color: #64748b;
            display: flex;
            align-items: center;
        }

        .post-views i {
            margin-right: 0.4rem;
        }

        .pinned-badge {
            background-color: #2563eb;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 6px;
            font-size: 0.75rem;
            margin-left: 0.75rem;
            display: inline-flex;
            align-items: center;
        }

        .pinned-badge i {
            margin-right: 0.25rem;
        }

        /* New post button styles */
        .new-post-btn {
            background-color: #2563eb;
            color: white;
            border: none;
            padding: 0.6rem 1.25rem;
            border-radius: 8px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            transition: all 0.2s;
            box-shadow: 0 2px 4px rgba(37, 99, 235, 0.2);
        }

        .new-post-btn i {
            margin-right: 0.5rem;
        }

        .new-post-btn:hover {
            background-color: #1d4ed8;
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(37, 99, 235, 0.3);
        }

        /* Alert styles */
        .alert {
            border-radius: 10px;
            padding: 1.25rem 1.5rem;
            margin-bottom: 1.5rem;
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .alert i {
            margin-right: 0.5rem;
            font-size: 1.25rem;
        }

        .alert h4 {
            margin-top: 0;
            margin-bottom: 0.75rem;
            font-weight: 600;
        }

        /* Responsive adjustments */
        @media (max-width: 992px) {
            .forum-content {
                padding: 1.5rem;
            }
            
            .forum-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .forum-title {
                font-size: 1.5rem;
            }
        }

        @media (max-width: 768px) {
            .forum-list {
                grid-template-columns: 1fr;
            }
            
            .post-actions {
                gap: 0.75rem;
            }
        }

        /* Animation for new content */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .forum-card, .post-item {
            animation: fadeIn 0.3s ease-out forwards;
        }

        /* Hover effects for interactive elements */
        .clickable-post {
            cursor: pointer;
            transition: transform 0.2s;
        }

        .clickable-post:hover {
            transform: translateY(-3px);
        }

        /* Like button animation */
        .like-btn {
            position: relative;
            overflow: hidden;
        }

        .like-btn:active i {
            animation: likeBounce 0.4s;
        }

        @keyframes likeBounce {
            0% { transform: scale(1); }
            25% { transform: scale(1.2); }
            50% { transform: scale(0.9); }
            75% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        /* Membership badge styles */
        .forum-membership-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 6px;
            margin-left: 0.5rem;
            font-weight: 500;
        }

        .badge-member {
            background-color: #64748b;
            color: white;
        }

        .badge-moderator {
            background-color: #2563eb;
            color: white;
        }

        .badge-admin {
            background-color: #dc2626;
            color: white;
        }

        /* Private forum indicator */
        .private-forum-icon {
            color: #d946ef;
            margin-left: 0.5rem;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="forum-container">

            <!-- Sidebar Navigation -->
        <?php require '../includes/forum_sidebar.php'; ?>
        <!-- Sidebar Navigation -->
        <div class="forum-sidebar">
            <div class="sidebar-header">
                <div class="user-info">
                    <div class="user-avatar">
                        <?php if (!empty($currentUser['picture'])): ?>
                            <img src="../uploads/<?php echo htmlspecialchars($currentUser['picture']); ?>" alt="Profile Picture">
                        <?php else: ?>
                            <i class="bi bi-person-circle text-muted"></i>
                        <?php endif; ?>
                    </div>
                    <div class="user-details">
                        <div class="user-name"><?php echo htmlspecialchars($currentUser['name']); ?></div>
                        <div class="user-role"><?php echo htmlspecialchars($currentUser['role']); ?></div>
                    </div>
                </div>
                <button id="new-forum-btn" class="btn btn-sm btn-outline-primary" title="Create New Forum" data-bs-toggle="modal" data-bs-target="#newForumModal">
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
                            <a href="forums.php" class="d-flex align-items-center">
                                <i class="bi bi-house-door me-2"></i>
                                <span>Home</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="d-flex align-items-center">
                                <i class="bi bi-bell me-2"></i>
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
                                <a href="forums.php?forum_id=<?php echo $forum['forum_id']; ?>" class="d-flex align-items-center">
                                    <i class="bi bi-people-fill me-2"></i>
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
                                <a href="forums.php?forum_id=<?php echo $forum['forum_id']; ?>" class="d-flex align-items-center">
                                    <i class="bi bi-collection me-2"></i>
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
                        <h4><i class="bi bi-slash-circle"></i> Access Denied</h4>
                        <p>You have been banned from this forum and cannot view or participate in discussions.</p>
                        <p>If you believe this is an error, please contact the forum administrators.</p>
                    </div>
                <?php elseif ($isPrivateForum && !$currentUser['forum_role'] && !$currentUser['is_pending']): ?>
                    <div class="alert alert-warning">
                        <h4><i class="bi bi-lock"></i> Private Forum</h4>
                        <p>This is a private forum. You need to join to view content.</p>
                        <form action="../forum/join_forum.php" method="POST" class="mt-3">
                            <input type="hidden" name="forum_id" value="<?php echo $selectedForumId; ?>">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-door-open"></i> Request to Join
                            </button>
                        </form>
                    </div>
                <?php elseif ($isPrivateForum && $currentUser['is_pending']): ?>
                    <div class="alert alert-info">
                        <h4><i class="bi bi-hourglass"></i> Pending Approval</h4>
                        <p>Your request to join this private forum is pending approval by the forum administrators.</p>
                        <form action="../forum/cancel_join_request.php" method="POST">
                            <input type="hidden" name="forum_id" value="<?php echo $selectedForumId; ?>">
                            <button type="submit" class="btn btn-outline-danger">
                                <i class="bi bi-x-circle"></i> Cancel Request
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="forum-header d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="forum-title"><?php echo htmlspecialchars($selectedForum['title']); ?></h2>
                            <p class="text-muted mb-0"><?php echo htmlspecialchars($selectedForum['description']); ?></p>
                        </div>
                        <div class="d-flex align-items-center">
                            <?php if ($currentUser['forum_role']): ?>
                                <form action="../forum/leave_forum.php" method="POST" class="me-2">
                                    <input type="hidden" name="forum_id" value="<?php echo $selectedForumId; ?>">
                                    <button type="submit" class="btn btn-outline-danger">
                                        <i class="bi bi-door-closed"></i> Leave
                                    </button>
                                </form>
                            <?php elseif (!$isPrivateForum): ?>
                                <form action="../forum/join_forum.php" method="POST" class="me-2">
                                    <input type="hidden" name="forum_id" value="<?php echo $selectedForumId; ?>">
                                    <button type="submit" class="btn btn-outline-primary">
                                        <i class="bi bi-door-open"></i> Join
                                    </button>
                                </form>
                            <?php endif; ?>
                            
                            <?php if (in_array($currentUser['forum_role'], ['Moderator', 'Admin'])): ?>
                                <a href="../forum/manage_forum.php?forum_id=<?php echo $selectedForumId; ?>" 
                                   class="btn btn-outline-secondary me-2">
                                    <i class="bi bi-gear"></i> Manage
                                </a>
                            <?php endif; ?>
                            
                            <a href="../forum/new_post.php?forum_id=<?php echo $selectedForumId; ?>" class="btn btn-primary">
                                <i class="bi bi-plus-lg"></i> New Post
                            </a>
                        </div>
                    </div>
                    
                    <?php if (count($forumPosts) > 0): ?>
                        <ul class="post-list">
                            <?php foreach ($forumPosts as $post): ?>
                                <li class="post-item clickable-post" data-post-id="<?php echo $post['post_id']; ?>">
                                    <div class="post-header">
                                        <div class="post-author-avatar">
                                            <?php if (!empty($post['poster_picture'])): ?>
                                                <img src="../uploads/<?php echo htmlspecialchars($post['poster_picture']); ?>" alt="Poster Picture">
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
                                        <?php if (strlen($post['content']) > 200): ?>... <a href="../forum/post.php?post_id=<?php echo $post['post_id']; ?>">Read more</a><?php endif; ?>
                                    </div>
                                    <div class="post-footer">
                                        <div class="post-actions">
                                           <a href="#" class="like-btn" data-post-id="<?php echo $post['post_id']; ?>">
                                                <i class="bi bi-hand-thumbs-up"></i> Like (<span class="like-count"><?php echo $post['up_count']; ?></span>)
                                            </a>
                                            <a href="../forum/post.php?post_id=<?php echo $post['post_id']; ?>"><i class="bi bi-chat"></i> Comments (<?php echo $post['comment_count']; ?>)</a>
                                        </div>
                                        <div class="post-views">
                                            <i class="bi bi-eye"></i> <?php echo $post['view_count']; ?> views
                                        </div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div class="alert alert-info mt-4">
                            <i class="bi bi-info-circle"></i> No posts yet in this forum. Be the first to post!
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
                            // Check if user has pending request for this forum
                            $query = "SELECT status FROM forum_membership 
                                      WHERE forum_id = ? AND actor_id = ? AND deleted_at IS NULL";
                            $stmt = $conn->prepare($query);
                            $stmt->execute([$forum['forum_id'], $currentUser['actor_id']]);
                            $membership = $stmt->fetch(PDO::FETCH_ASSOC);
                            $isPending = ($membership['status'] ?? null) === 'Pending';
                        ?>
                            <div class="forum-card">
                                <h3><a href="forums.php?forum_id=<?php echo $forum['forum_id']; ?>" style="color: inherit; text-decoration: none;">
                                    <?php echo htmlspecialchars($forum['title']); ?>
                                    <?php if ($forum['is_private']): ?>
                                        <i class="bi bi-lock text-muted ms-1"></i>
                                    <?php endif; ?>
                                </a></h3>
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
                        <i class="bi bi-info-circle"></i> No forums available yet. Create the first forum!
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
                            <label for="forumTitle" class="form-label">Forum Title *</label>
                            <input type="text" class="form-control" id="forumTitle" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label for="forumDescription" class="form-label">Description *</label>
                            <textarea class="form-control" id="forumDescription" name="description" rows="3" required></textarea>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="forumPrivate" name="is_private">
                            <label class="form-check-label" for="forumPrivate">Make this a private forum (requires approval to join)</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Forum</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Navigation item click handlers
            document.querySelectorAll('.nav-item a').forEach(item => {
                item.addEventListener('click', function(e) {
                    // Remove active class from all items
                    document.querySelectorAll('.nav-item').forEach(navItem => {
                        navItem.classList.remove('active');
                    });
                    // Add active class to clicked item
                    this.parentElement.classList.add('active');
                });
            });
        });

    

        document.querySelectorAll('.like-btn').forEach(button => {
            const postId = button.dataset.postId;

            // Check if already liked (disable the button or change style)
            if (localStorage.getItem(`liked_post_${postId}`)) {
                button.classList.add('text-primary'); // Optional: style liked state
                button.style.pointerEvents = 'none'; // Prevent further clicks
            }

            button.addEventListener('click', function(e) {
                e.preventDefault();

                // Prevent if already liked
                if (localStorage.getItem(`liked_post_${postId}`)) {
                    alert('You already liked this post.');
                    return;
                }

                fetch('../forum/like_post.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `post_id=${postId}`
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const countSpan = button.querySelector('.like-count');
                        countSpan.textContent = data.like_count;

                        // Mark as liked
                        localStorage.setItem(`liked_post_${postId}`, 'true');
                        button.classList.add('text-primary');
                        button.style.pointerEvents = 'none';
                    } else {
                        alert(data.message || 'Something went wrong.');
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    alert('Something went wrong.');
                });
            });
        });



    </script>



        <script>
        document.querySelectorAll('.clickable-post').forEach(post => {
            post.addEventListener('click', function(e) {
                // Prevent click if the target is a link or inside a like/comment button
                if (e.target.closest('a')) return;

                const postId = this.dataset.postId;
                window.location.href = `../forum/post.php?post_id=${postId}`;
            });
        });

        
        </script>

</body>
</html>