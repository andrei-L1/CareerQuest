<?php
require_once 'config/dbcon.php';

// Fetch all public forums from the database
$query = "SELECT f.*, 
                 CONCAT(COALESCE(u.user_first_name, s.stud_first_name), ' ', COALESCE(u.user_last_name, s.stud_last_name)) AS creator_name,
                 COUNT(DISTINCT fp.post_id) AS post_count,
                 (SELECT COUNT(*) FROM forum_membership fm WHERE fm.forum_id = f.forum_id AND fm.deleted_at IS NULL AND fm.status = 'Active') AS member_count
          FROM forum f
          LEFT JOIN actor a ON f.created_by = a.actor_id
          LEFT JOIN user u ON (a.entity_type = 'user' AND a.entity_id = u.user_id)
          LEFT JOIN student s ON (a.entity_type = 'student' AND a.entity_id = s.stud_id)
          LEFT JOIN forum_post fp ON f.forum_id = fp.forum_id AND fp.deleted_at IS NULL
          WHERE f.deleted_at IS NULL AND f.is_private = 0
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
    // Get the selected forum details (only public forums)
    $query = "SELECT f.*, 
                     CONCAT(COALESCE(u.user_first_name, s.stud_first_name), ' ', COALESCE(u.user_last_name, s.stud_last_name)) AS creator_name,
                     f.is_private,
                     COUNT(DISTINCT fp.post_id) AS post_count,
                     (SELECT COUNT(*) FROM forum_membership fm WHERE fm.forum_id = f.forum_id AND fm.deleted_at IS NULL AND fm.status = 'Active') AS member_count
              FROM forum f
              LEFT JOIN actor a ON f.created_by = a.actor_id
              LEFT JOIN user u ON (a.entity_type = 'user' AND a.entity_id = u.user_id)
              LEFT JOIN student s ON (a.entity_type = 'student' AND a.entity_id = s.stud_id)
              LEFT JOIN forum_post fp ON f.forum_id = fp.forum_id AND fp.deleted_at IS NULL
              WHERE f.forum_id = ? AND f.deleted_at IS NULL AND f.is_private = 0
              GROUP BY f.forum_id";
    $stmt = $conn->prepare($query);
    $stmt->execute([$selectedForumId]);
    $selectedForum = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($selectedForum) {
        // Get posts for this forum (only from active members)
        $query = "SELECT fp.post_id, fp.forum_id, fp.post_title, fp.content, fp.posted_at, fp.is_pinned, fp.view_count, 
                         CONCAT(COALESCE(u.user_first_name, s.stud_first_name), ' ', COALESCE(u.user_last_name, s.stud_last_name)) AS poster_name,
                         COALESCE(u.picture_file, s.profile_picture) AS poster_picture,
                         COUNT(fc.comment_id) AS comment_count
                  FROM forum_post fp
                  LEFT JOIN actor a ON fp.poster_id = a.actor_id
                  LEFT JOIN user u ON (a.entity_type = 'user' AND a.entity_id = u.user_id)
                  LEFT JOIN student s ON (a.entity_type = 'student' AND a.entity_id = s.stud_id)
                  LEFT JOIN forum_comment fc ON fp.post_id = fc.post_id AND fc.deleted_at IS NULL
                  LEFT JOIN forum_membership fm ON fm.forum_id = fp.forum_id AND fm.actor_id = fp.poster_id AND fm.deleted_at IS NULL
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

// Fetch recent posts from all public forums
$query = "SELECT fp.post_id, fp.forum_id, fp.post_title, fp.content, fp.posted_at, fp.is_pinned, fp.view_count,
                 f.title AS forum_title,
                 CONCAT(COALESCE(u.user_first_name, s.stud_first_name), ' ', COALESCE(u.user_last_name, s.stud_last_name)) AS poster_name,
                 COALESCE(u.picture_file, s.profile_picture) AS poster_picture,
                 COUNT(fc.comment_id) AS comment_count
          FROM forum_post fp
          JOIN forum f ON fp.forum_id = f.forum_id
          LEFT JOIN actor a ON fp.poster_id = a.actor_id
          LEFT JOIN user u ON (a.entity_type = 'user' AND a.entity_id = u.user_id)
          LEFT JOIN student s ON (a.entity_type = 'student' AND a.entity_id = s.stud_id)
          LEFT JOIN forum_comment fc ON fp.post_id = fc.post_id AND fc.deleted_at IS NULL
          LEFT JOIN forum_membership fm ON fm.forum_id = fp.forum_id AND fm.actor_id = fp.poster_id AND fm.deleted_at IS NULL
          WHERE f.deleted_at IS NULL 
                AND f.is_private = 0
                AND fp.deleted_at IS NULL
                AND fm.status = 'Active'
          GROUP BY fp.post_id
          ORDER BY fp.posted_at DESC
          LIMIT 10";
$stmt = $conn->prepare($query);
$stmt->execute();
$recentPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forums - CareerQuest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #0A2647;
            --primary-light: rgba(10, 38, 71, 0.1);
            --secondary-color: #2C7865;
            --accent-color: #FFD700;
            --text-dark: #212529;
            --text-light: #6C757D;
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            --shadow-sm: 0 2px 8px rgba(0,0,0,0.08);
            --shadow-md: 0 4px 20px rgba(0,0,0,0.12);
        }

        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .forum-card {
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            border: none;
            overflow: hidden;
        }

        .forum-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .post-card {
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            border: none;
            margin-bottom: 1rem;
        }

        .post-card:hover {
            box-shadow: var(--shadow-md);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), #1C4B82);
            border: none;
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #1C4B82, var(--primary-color));
            transform: translateY(-1px);
        }

        .forum-header {
            background: linear-gradient(135deg, var(--primary-color), #1C4B82);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }

        .stats-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: var(--shadow-sm);
        }

        .stats-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-color);
        }

        .stats-label {
            color: var(--text-light);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .login-prompt {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            border: 2px dashed #dee2e6;
        }

        .role-card {
            transition: var(--transition);
            border: none;
            border-radius: 12px;
        }

        .role-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .icon-container {
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <!-- Forum Header -->
    <div class="forum-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="display-5 fw-bold mb-3">
                        <i class="fas fa-comments me-3"></i>
                        CareerQuest Forums
                    </h1>
                    <p class="lead mb-0">Connect, share, and learn from the community</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <button class="btn btn-light btn-lg" data-bs-toggle="modal" data-bs-target="#loginModal">
                        <i class="fas fa-sign-in-alt me-2"></i>Login to Participate
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Statistics Row -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo count($forums); ?></div>
                    <div class="stats-label">Public Forums</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo array_sum(array_column($forums, 'post_count') ?: [0]); ?></div>
                    <div class="stats-label">Total Posts</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo array_sum(array_column($forums, 'member_count') ?: [0]); ?></div>
                    <div class="stats-label">Active Members</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo count($recentPosts); ?></div>
                    <div class="stats-label">Recent Posts</div>
                </div>
            </div>
        </div>

        <?php if ($selectedForum): ?>
            <!-- Selected Forum View -->
            <div class="row">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2 class="mb-1"><?php echo htmlspecialchars($selectedForum['title']); ?></h2>
                            <p class="text-muted mb-0">
                                Created by <?php echo htmlspecialchars($selectedForum['creator_name'] ?? 'Unknown'); ?> • 
                                <?php echo $selectedForum['post_count'] ?? 0; ?> posts • 
                                <?php echo $selectedForum['member_count'] ?? 0; ?> members
                            </p>
                        </div>
                        <a href="forums.php" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Forums
                        </a>
                    </div>

                    <?php if (empty($forumPosts)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">No posts yet</h4>
                            <p class="text-muted">Login to start a discussion in this forum!</p>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#loginModal">
                                <i class="fas fa-sign-in-alt me-2"></i>Login to Post
                            </button>
                        </div>
                    <?php else: ?>
                        <!-- Forum Posts -->
                        <?php foreach ($forumPosts as $post): ?>
                            <div class="post-card p-4">
                                <div class="d-flex align-items-start">
                                    <img src="<?php echo $post['poster_picture'] ? 'Uploads/' . htmlspecialchars($post['poster_picture']) : 'Uploads/default.png'; ?>" 
                                         class="rounded-circle me-3" width="50" height="50" alt="Profile">
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div>
                                                <h5 class="mb-1"><?php echo htmlspecialchars($post['post_title']); ?></h5>
                                                <p class="text-muted mb-0">
                                                    By <?php echo htmlspecialchars($post['poster_name']); ?> • 
                                                    <?php echo date('M j, Y', strtotime($post['posted_at'])); ?>
                                                </p>
                                            </div>
                                            <?php if ($post['is_pinned']): ?>
                                                <span class="badge bg-warning text-dark">
                                                    <i class="fas fa-thumbtack me-1"></i>Pinned
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="mb-3"><?php echo htmlspecialchars(substr($post['content'], 0, 200)) . (strlen($post['content']) > 200 ? '...' : ''); ?></p>
                                        <div class="d-flex align-items-center">
                                            <span class="text-muted me-3">
                                                <i class="fas fa-comment me-1"></i><?php echo $post['comment_count']; ?> comments
                                            </span>
                                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#loginModal">
                                                Login to View
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <!-- Main Forums View -->
            <div class="row">
                <div class="col-lg-8">
                    <!-- Recent Posts Section -->
                    <div class="card forum-card mb-4">
                        <div class="card-header bg-white border-0 py-3">
                            <h4 class="mb-0">
                                <i class="fas fa-clock me-2 text-primary"></i>
                                Recent Discussions
                            </h4>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($recentPosts)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">No recent posts</h5>
                                    <p class="text-muted">Join the community to start discussions!</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($recentPosts as $post): ?>
                                    <div class="border-bottom p-4">
                                        <div class="d-flex align-items-start">
                                            <img src="<?php echo $post['poster_picture'] ? 'Uploads/' . htmlspecialchars($post['poster_picture']) : 'Uploads/default.png'; ?>" 
                                                 class="rounded-circle me-3" width="40" height="40" alt="Profile">
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1">
                                                    <a href="forums.php?forum_id=<?php echo $post['forum_id']; ?>" class="text-decoration-none">
                                                        <?php echo htmlspecialchars($post['post_title']); ?>
                                                    </a>
                                                </h6>
                                                <p class="text-muted small mb-1">
                                                    By <?php echo htmlspecialchars($post['poster_name']); ?> in 
                                                    <strong><?php echo htmlspecialchars($post['forum_title']); ?></strong> • 
                                                    <?php echo date('M j, Y', strtotime($post['posted_at'])); ?>
                                                </p>
                                                <p class="text-muted small mb-0">
                                                    <i class="fas fa-comment me-1"></i><?php echo $post['comment_count']; ?> comments
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <!-- Forums List -->
                    <div class="card forum-card">
                        <div class="card-header bg-white border-0 py-3">
                            <h5 class="mb-0">
                                <i class="fas fa-list me-2 text-primary"></i>
                                Public Forums
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($forums)): ?>
                                <div class="text-center py-4">
                                    <p class="text-muted mb-0">No public forums available</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($forums as $forum): ?>
                                    <div class="border-bottom p-3">
                                        <h6 class="mb-1">
                                            <a href="forums.php?forum_id=<?php echo $forum['forum_id']; ?>" class="text-decoration-none">
                                                <?php echo htmlspecialchars($forum['title']); ?>
                                            </a>
                                        </h6>
                                        <p class="text-muted small mb-1"><?php echo htmlspecialchars($forum['description']); ?></p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="text-muted small">
                                                <?php echo $forum['post_count'] ?? 0; ?> posts • <?php echo $forum['member_count'] ?? 0; ?> members
                                            </span>
                                            <a href="forums.php?forum_id=<?php echo $forum['forum_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                View
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Login Prompt -->
                    <div class="login-prompt mt-4">
                        <i class="fas fa-users fa-2x text-muted mb-3"></i>
                        <h5>Join the Discussion</h5>
                        <p class="text-muted">Login to participate in forums, create posts, and connect with the community.</p>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#loginModal">
                            <i class="fas fa-sign-in-alt me-2"></i>Login Now
                        </button>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Login Modal -->
    <div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-0 pb-0">
                    <div class="w-100 text-center">
                        <h3 class="modal-title fw-bold display-6" id="loginModalLabel">Welcome Back</h3>
                        <p class="text-muted">Sign in to your account</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <!-- Student Login -->
                        <div class="col-12">
                            <a href="auth/login_student.php" class="card role-card h-100 text-decoration-none text-white" style="background: linear-gradient(135deg, #0A2647, #1C4B82);">
                                <div class="card-body d-flex align-items-center gap-4 py-4">
                                    <div class="icon-container bg-white bg-opacity-25 p-3 rounded-circle">
                                        <i class="fas fa-user-graduate fa-2x"></i>
                                    </div>
                                    <div class="text-start">
                                        <h4 class="mb-1">Applicant</h4>
                                        <p class="opacity-75 mb-0">Access jobs and career resources</p>
                                    </div>
                                    <i class="fas fa-arrow-right ms-auto opacity-50"></i>
                                </div>
                            </a>
                        </div>

                        <!-- Employer Login -->
                        <div class="col-12">
                            <a href="auth/login_employer.php" class="card role-card h-100 text-decoration-none text-white" style="background: linear-gradient(135deg, #2C7865, #1A5F4A);">
                                <div class="card-body d-flex align-items-center gap-4 py-4">
                                    <div class="icon-container bg-white bg-opacity-25 p-3 rounded-circle">
                                        <i class="fas fa-briefcase fa-2x"></i>
                                    </div>
                                    <div class="text-start">
                                        <h4 class="mb-1">Employer</h4>
                                        <p class="opacity-75 mb-0">Manage your job postings</p>
                                    </div>
                                    <i class="fas fa-arrow-right ms-auto opacity-50"></i>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <p class="text-muted small text-center w-100 mb-0">
                        Don't have an account? 
                        <a href="views/signup.php" class="text-decoration-none fw-bold">Sign Up</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>