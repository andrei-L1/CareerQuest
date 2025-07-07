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
                 COUNT(fp.post_id) AS post_count
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

if ($selectedForumId) {
    // Get the selected forum details
    $query = "SELECT f.*, 
                     CONCAT(COALESCE(u.user_first_name, s.stud_first_name), ' ', COALESCE(u.user_last_name, s.stud_last_name)) AS creator_name
              FROM forum f
              LEFT JOIN actor a ON f.created_by = a.actor_id
              LEFT JOIN user u ON (a.entity_type = 'user' AND a.entity_id = u.user_id)
              LEFT JOIN student s ON (a.entity_type = 'student' AND a.entity_id = s.stud_id)
              WHERE f.forum_id = ? AND f.deleted_at IS NULL";
    $stmt = $conn->prepare($query);
    $stmt->execute([$selectedForumId]);
    $selectedForum = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($selectedForum) {
        // Get posts for this forum
        $query = "SELECT fp.*, 
                         CONCAT(COALESCE(u.user_first_name, s.stud_first_name), ' ', COALESCE(u.user_last_name, s.stud_last_name)) AS poster_name,
                         COALESCE(u.picture_file, s.profile_picture) AS poster_picture,
                         COUNT(fc.comment_id) AS comment_count
                  FROM forum_post fp
                  LEFT JOIN actor a ON fp.poster_id = a.actor_id
                  LEFT JOIN user u ON (a.entity_type = 'user' AND a.entity_id = u.user_id)
                  LEFT JOIN student s ON (a.entity_type = 'student' AND a.entity_id = s.stud_id)
                  LEFT JOIN forum_comment fc ON fp.post_id = fc.post_id AND fc.deleted_at IS NULL
                  WHERE fp.forum_id = ? AND fp.deleted_at IS NULL
                  GROUP BY fp.post_id
                  ORDER BY fp.is_pinned DESC, fp.posted_at DESC";
        $stmt = $conn->prepare($query);
        $stmt->execute([$selectedForumId]);
        $forumPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
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
            padding: 20px;
            overflow-y: auto;
        }

        .forum-header {
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .forum-title {
            font-weight: 600;
            color: var(--dark-color);
        }

        .forum-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .forum-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .forum-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .forum-card h3 {
            margin-top: 0;
            color: var(--primary-color);
        }

        .forum-card p {
            color: var(--gray-color);
            margin-bottom: 10px;
        }

        .forum-meta {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: var(--gray-color);
        }

        .post-list {
            list-style: none;
            padding: 0;
        }

        .post-item {
            background: white;
            border-radius: var(--border-radius);
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .post-header {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }

        .post-author-avatar {
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
        }

        .post-date {
            font-size: 12px;
            color: var(--gray-color);
        }

        .post-title {
            font-size: 18px;
            margin: 10px 0;
            color: var(--dark-color);
        }

        .post-content {
            margin-bottom: 10px;
            color: var(--dark-color);
        }

        .post-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 14px;
        }

        .post-actions a {
            color: var(--gray-color);
            margin-right: 10px;
            text-decoration: none;
        }

        .post-actions a:hover {
            color: var(--primary-color);
        }

        .pinned-badge {
            background-color: var(--primary-color);
            color: white;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 12px;
            margin-left: 10px;
        }

        .new-post-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            font-weight: 500;
        }

        .new-post-btn:hover {
            background-color: #3a56d4;
            color: white;
        }

        @media (max-width: 768px) {
            .forum-sidebar {
                width: 100%;
                height: auto;
            }
            
            .forum-container {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="forum-container">
        <!-- Sidebar Navigation -->
        <?php require '../includes/forum_sidebar.php'; ?>
        
        <!-- Forum Sidebar Navigation -->
        <div class="forum-sidebar">
            <div class="sidebar-header">
                <div class="user-info">
                    <div class="user-avatar">
                        <?php if (!empty($currentUser['picture'])): ?>
                            <img src="../uploads/<?php echo htmlspecialchars($currentUser['picture']); ?>" alt="Profile Picture">
                        <?php else: ?>
                            <i class="bi bi-person-fill text-muted"></i>
                        <?php endif; ?>
                    </div>
                    <div class="user-details">
                        <div class="user-name"><?php echo htmlspecialchars($currentUser['name']); ?></div>
                        <div class="user-role"><?php echo htmlspecialchars($currentUser['role']); ?></div>
                    </div>
                </div>
                <button id="new-forum-btn" class="new-forum-btn" title="Create New Forum" data-bs-toggle="modal" data-bs-target="#newForumModal">
                    <i class="bi bi-plus-lg"></i>
                </button>
            </div>
            
            <!-- Forum Navigation -->
            <div class="forum-navigation">
                <div class="nav-section">
                    <h4 class="nav-title">Main Forums</h4>
                    <ul class="nav-links">
                        <li class="nav-item <?php echo !$selectedForumId ? 'active' : ''; ?>">
                            <a href="forums.php"><i class="bi bi-house-door"></i> Home</a>
                        </li>
                        <li class="nav-item">
                            <a href="#"><i class="bi bi-people"></i> Notification</a>
                        </li>
                    </ul>
                </div>
                
                <div class="nav-section">
                    <h4 class="nav-title">Forum Categories</h4>
                    <ul class="nav-links">
                        <?php foreach ($forums as $forum): ?>
                            <li class="nav-item <?php echo $selectedForumId == $forum['forum_id'] ? 'active' : ''; ?>">
                                <a href="forums.php?forum_id=<?php echo $forum['forum_id']; ?>">
                                    <i class="bi bi-collection"></i> <?php echo htmlspecialchars($forum['title']); ?>
                                    <span class="ms-auto badge bg-secondary"><?php echo $forum['post_count']; ?></span>
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
                <div class="forum-header">
                    <h2 class="forum-title"><?php echo htmlspecialchars($selectedForum['title']); ?></h2>
                    <a href="../forum/new_post.php?forum_id=<?php echo $selectedForumId; ?>" class="new-post-btn">
                        <i class="bi bi-plus-lg"></i> New Post
                    </a>
                </div>
                
                <p class="text-muted"><?php echo htmlspecialchars($selectedForum['description']); ?></p>
                
                <?php if (count($forumPosts) > 0): ?>
                    <ul class="post-list">
                        <?php foreach ($forumPosts as $post): ?>
                            <li class="post-item">
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
                                        <span class="pinned-badge">Pinned</span>
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
                                        <a href="#"><i class="bi bi-hand-thumbs-up"></i> Like (<?php echo $post['up_count']; ?>)</a>
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
                    <div class="alert alert-info">
                        No posts yet in this forum. Be the first to post!
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="forum-header">
                    <h2 class="forum-title">All Forums</h2>
                </div>
                
                <?php if (count($forums) > 0): ?>
                    <div class="forum-list">
                        <?php foreach ($forums as $forum): ?>
                            <div class="forum-card">
                                <h3><a href="forums.php?forum_id=<?php echo $forum['forum_id']; ?>" style="color: inherit; text-decoration: none;"><?php echo htmlspecialchars($forum['title']); ?></a></h3>
                                <p><?php echo htmlspecialchars($forum['description']); ?></p>
                                <div class="forum-meta">
                                    <span>Created by <?php echo htmlspecialchars($forum['creator_name']); ?></span>
                                    <span><?php echo $forum['post_count']; ?> posts</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        No forums available yet. Create the first forum!
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
                            <label for="forumTitle" class="form-label">Forum Title</label>
                            <input type="text" class="form-control" id="forumTitle" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label for="forumDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="forumDescription" name="description" rows="3" required></textarea>
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
    </script>
</body>
</html>