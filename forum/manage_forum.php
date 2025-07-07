<?php
require_once '../config/dbcon.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// Check login
if (!isset($_SESSION['user_id']) && !isset($_SESSION['stud_id'])) {
    header("Location: ../index.php");
    exit;
}

$entityType = isset($_SESSION['user_id']) ? 'user' : 'student';
$entityId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : $_SESSION['stud_id'];

// Get actor_id
$stmt = $conn->prepare("SELECT actor_id FROM actor WHERE entity_type = ? AND entity_id = ?");
$stmt->execute([$entityType, $entityId]);
$actor = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$actor) {
    echo "Actor not found.";
    exit;
}
$actorId = $actor['actor_id'];

// Get forum ID
$forumId = $_GET['forum_id'] ?? null;
if (!$forumId) {
    echo "Forum ID is required.";
    exit;
}

// Verify current user has moderator or admin role in the forum
$stmt = $conn->prepare("SELECT role FROM forum_membership WHERE forum_id = ? AND actor_id = ? AND status = 'Active'");
$stmt->execute([$forumId, $actorId]);
$membership = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$membership || !in_array($membership['role'], ['Moderator', 'Admin'])) {
    echo "Access denied. You must be a Moderator or Admin to manage this forum.";
    exit;
}

// Fetch forum details
$stmt = $conn->prepare("SELECT * FROM forum WHERE forum_id = ?");
$stmt->execute([$forumId]);
$forum = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch forum members
$query = "
    SELECT fm.*, a.entity_type, 
           COALESCE(u.user_first_name, s.stud_first_name) AS first_name,
           COALESCE(u.user_last_name, s.stud_last_name) AS last_name,
           COALESCE(u.picture_file, s.profile_picture) AS picture
    FROM forum_membership fm
    JOIN actor a ON fm.actor_id = a.actor_id
    LEFT JOIN user u ON (a.entity_type = 'user' AND a.entity_id = u.user_id)
    LEFT JOIN student s ON (a.entity_type = 'student' AND a.entity_id = s.stud_id)
    WHERE fm.forum_id = ?
    ORDER BY fm.role DESC, first_name ASC
";
$stmt = $conn->prepare($query);
$stmt->execute([$forumId]);
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Forum - <?php echo htmlspecialchars($forum['title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .forum-header {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .member-avatar {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 50%;
        }
        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .table thead {
            background-color: #4a6fdc;
            color: white;
        }
        .badge-role {
            font-size: 0.8rem;
            padding: 4px 8px;
        }
        .badge-admin {
            background-color: #dc3545;
        }
        .badge-moderator {
            background-color: #fd7e14;
        }
        .badge-member {
            background-color: #6c757d;
        }
        .badge-pending {
            background-color: #6c757d;
        }
        .badge-banned {
            background-color: #343a40;
        }
        .action-btn {
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0;
        }
        .section-title {
            border-bottom: 2px solid #dee2e6;
            padding-bottom: 8px;
            margin-bottom: 20px;
        }
        .no-members {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-radius: 8px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="forum-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="bi bi-people-fill me-2"></i>Manage Forum: <?php echo htmlspecialchars($forum['title']); ?></h2>
                    <p class="lead mb-0"><?php echo htmlspecialchars($forum['description']); ?></p>
                </div>
                <a href="../dashboard/forums.php?forum_id=<?php echo $forumId; ?>" class="btn btn-outline-primary">
                    <i class="bi bi-arrow-left me-1"></i> Back to Forum
                </a>
            </div>
        </div>

        <!-- Approved Members Section -->
        <div class="mb-5">
            <h4 class="section-title">
                <i class="bi bi-check-circle-fill text-success me-2"></i>
                Approved Members
                <span class="badge bg-primary ms-2"><?php echo count(array_filter($members, fn($m) => $m['status'] !== 'Pending')); ?></span>
            </h4>
            
            <?php if (count(array_filter($members, fn($m) => $m['status'] !== 'Pending')) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th style="width: 50px;"></th>
                                <th>Member</th>
                                <th style="width: 120px;">Role</th>
                                <th style="width: 120px;">Status</th>
                                <th style="width: 250px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($members as $m): ?>
                                <?php if ($m['status'] !== 'Pending'): ?>
                                <tr>
                                    <td>
                                        <?php if ($m['picture']): ?>
                                            <img src="../uploads/<?php echo htmlspecialchars($m['picture']); ?>" class="member-avatar">
                                        <?php else: ?>
                                            <div class="member-avatar bg-secondary d-flex align-items-center justify-content-center">
                                                <i class="bi bi-person-fill text-white"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($m['first_name'] . ' ' . $m['last_name']); ?></strong>
                                        <div class="text-muted small"><?php echo ucfirst($m['entity_type']); ?></div>
                                    </td>
                                    <td>
                                        <span class="badge badge-role 
                                            <?php echo $m['role'] === 'Admin' ? 'badge-admin' : 
                                                  ($m['role'] === 'Moderator' ? 'badge-moderator' : 'badge-member'); ?>">
                                            <?php echo $m['role']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge 
                                            <?php echo $m['status'] === 'Banned' ? 'bg-dark' : 'bg-success'; ?>">
                                            <?php echo $m['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($m['actor_id'] != $actorId): ?>
                                            <form action="update_member.php" method="POST" class="d-inline">
                                                <input type="hidden" name="forum_id" value="<?php echo $forumId; ?>">
                                                <input type="hidden" name="actor_id" value="<?php echo $m['actor_id']; ?>">
                                                
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                                                        Change Role
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li>
                                                            <button type="submit" name="action" value="update_role" class="dropdown-item" <?php if ($m['role'] === 'Member') echo 'active'; ?>>
                                                                <input type="hidden" name="new_role" value="Member">
                                                                <i class="bi bi-person me-2"></i>Member
                                                            </button>
                                                        </li>
                                                        <li>
                                                            <button type="submit" name="action" value="update_role" class="dropdown-item" <?php if ($m['role'] === 'Moderator') echo 'active'; ?>>
                                                                <input type="hidden" name="new_role" value="Moderator">
                                                                <i class="bi bi-shield me-2"></i>Moderator
                                                            </button>
                                                        </li>
                                                        <li>
                                                            <button type="submit" name="action" value="update_role" class="dropdown-item" <?php if ($m['role'] === 'Admin') echo 'active'; ?>>
                                                                <input type="hidden" name="new_role" value="Admin">
                                                                <i class="bi bi-star me-2"></i>Admin
                                                            </button>
                                                        </li>
                                                    </ul>
                                                    
                                                    <?php if ($m['status'] !== 'Banned'): ?>
                                                        <button type="submit" name="action" value="ban" class="btn btn-outline-warning" title="Ban">
                                                            <i class="bi bi-slash-circle"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <button type="submit" name="action" value="unban" class="btn btn-outline-success" title="Unban">
                                                            <i class="bi bi-arrow-counterclockwise"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    
                                                    <button type="submit" name="action" value="remove" class="btn btn-outline-danger" title="Remove">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            </form>
                                        <?php else: ?>
                                            <span class="badge bg-light text-dark">
                                                <i class="bi bi-person-check me-1"></i>You
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-members">
                    <i class="bi bi-people display-6 text-muted mb-3"></i>
                    <p class="mb-0">No approved members yet.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Pending Requests Section -->
        <div class="mb-4">
            <h4 class="section-title">
                <i class="bi bi-hourglass-split text-warning me-2"></i>
                Pending Join Requests
                <span class="badge bg-warning text-dark ms-2"><?php echo count(array_filter($members, fn($m) => $m['status'] === 'Pending')); ?></span>
            </h4>
            
            <?php if (count(array_filter($members, fn($m) => $m['status'] === 'Pending')) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th style="width: 50px;"></th>
                                <th>User</th>
                                <th>Requested On</th>
                                <th style="width: 150px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($members as $m): ?>
                                <?php if ($m['status'] === 'Pending'): ?>
                                <tr>
                                    <td>
                                        <?php if ($m['picture']): ?>
                                            <img src="../uploads/<?php echo htmlspecialchars($m['picture']); ?>" class="member-avatar">
                                        <?php else: ?>
                                            <div class="member-avatar bg-secondary d-flex align-items-center justify-content-center">
                                                <i class="bi bi-person-fill text-white"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($m['first_name'] . ' ' . $m['last_name']); ?></strong>
                                        <div class="text-muted small"><?php echo ucfirst($m['entity_type']); ?></div>
                                    </td>
                                    <td>
                                        <?php echo date('M j, Y', strtotime($m['joined_at'])); ?>
                                    </td>
                                    <td>
                                        <form action="update_member.php" method="POST" class="d-inline">
                                            <input type="hidden" name="forum_id" value="<?php echo $forumId; ?>">
                                            <input type="hidden" name="actor_id" value="<?php echo $m['actor_id']; ?>">
                                            
                                            <div class="btn-group btn-group-sm" role="group">
                                                <button type="submit" name="action" value="approve" class="btn btn-success" title="Approve">
                                                    <i class="bi bi-check-lg"></i>
                                                </button>
                                                <button type="submit" name="action" value="remove" class="btn btn-danger" title="Reject">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-members">
                    <i class="bi bi-check2-all display-6 text-muted mb-3"></i>
                    <p class="mb-0">No pending join requests.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>