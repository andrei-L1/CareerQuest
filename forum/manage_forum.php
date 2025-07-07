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
<html>
<head>
    <title>Manage Forum - <?php echo htmlspecialchars($forum['title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-4">
    <h2>Manage Forum: <?php echo htmlspecialchars($forum['title']); ?></h2>
    <p><?php echo htmlspecialchars($forum['description']); ?></p>

    <h4 class="mt-4">Approved Members</h4>
    <table class="table table-bordered mt-2">
        <thead class="table-light">
            <tr>
                <th>Profile</th>
                <th>Name</th>
                <th>Role</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($members as $m): ?>
                <?php if ($m['status'] !== 'Pending'): ?>
                <tr>
                    <td>
                        <?php if ($m['picture']): ?>
                            <img src="../uploads/<?php echo htmlspecialchars($m['picture']); ?>" width="40" height="40" class="rounded-circle">
                        <?php else: ?>
                            <span class="text-muted">No Pic</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($m['first_name'] . ' ' . $m['last_name']); ?></td>
                    <td><?php echo $m['role']; ?></td>
                    <td><?php echo $m['status']; ?></td>
                    <td>
                        <?php if ($m['actor_id'] != $actorId): ?>
                            <form action="update_member.php" method="POST" class="d-flex flex-wrap gap-2">
                                <input type="hidden" name="forum_id" value="<?php echo $forumId; ?>">
                                <input type="hidden" name="actor_id" value="<?php echo $m['actor_id']; ?>">

                                <select name="new_role" class="form-select form-select-sm w-auto">
                                    <option value="Member" <?php if ($m['role'] === 'Member') echo 'selected'; ?>>Member</option>
                                    <option value="Moderator" <?php if ($m['role'] === 'Moderator') echo 'selected'; ?>>Moderator</option>
                                    <option value="Admin" <?php if ($m['role'] === 'Admin') echo 'selected'; ?>>Admin</option>
                                </select>
                                <button type="submit" name="action" value="update_role" class="btn btn-sm btn-primary">Update Role</button>

                                <?php if ($m['status'] !== 'Banned'): ?>
                                    <button type="submit" name="action" value="ban" class="btn btn-sm btn-warning">Ban</button>
                                <?php else: ?>
                                    <button type="submit" name="action" value="unban" class="btn btn-sm btn-success">Unban</button>
                                <?php endif; ?>

                                <button type="submit" name="action" value="remove" class="btn btn-sm btn-danger">Remove</button>
                            </form>
                        <?php else: ?>
                            <em>(You)</em>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endif; ?>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h4 class="mt-5">Pending Join Requests</h4>
    <table class="table table-bordered">
        <thead class="table-light">
            <tr>
                <th>Profile</th>
                <th>Name</th>
                <th>Requested On</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($members as $m): ?>
                <?php if ($m['status'] === 'Pending'): ?>
                <tr>
                    <td>
                        <?php if ($m['picture']): ?>
                            <img src="../uploads/<?php echo htmlspecialchars($m['picture']); ?>" width="40" height="40" class="rounded-circle">
                        <?php else: ?>
                            <span class="text-muted">No Pic</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($m['first_name'] . ' ' . $m['last_name']); ?></td>
                    <td><?php echo date('F j, Y', strtotime($m['joined_at'])); ?></td>
                    <td>
                        <form action="update_member.php" method="POST" class="d-flex gap-2">
                            <input type="hidden" name="forum_id" value="<?php echo $forumId; ?>">
                            <input type="hidden" name="actor_id" value="<?php echo $m['actor_id']; ?>">
                            <button type="submit" name="action" value="approve" class="btn btn-sm btn-success">Approve</button>
                            <button type="submit" name="action" value="remove" class="btn btn-sm btn-danger">Reject</button>
                        </form>
                    </td>
                </tr>
                <?php endif; ?>
            <?php endforeach; ?>
        </tbody>
    </table>

    <a href="../dashboard/forums.php?forum_id=<?php echo $forumId; ?>" class="btn btn-secondary mt-3">Back to Forum</a>
</body>
</html>
