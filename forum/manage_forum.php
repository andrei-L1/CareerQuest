<?php
require_once '../config/dbcon.php';
/** @var PDO $conn */
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
    SELECT fm.*, a.entity_type, a.entity_id, 
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

$userId = $_GET['user_id'] ?? null;
$userType = $_GET['user_type'] ?? null;

// Then when displaying the page, you can highlight the specific user if these parameters exist
if ($userId && $userType) {
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            // Scroll to and highlight the user's row
            const userRow = document.querySelector('tr[data-user-id=\"$userId\"][data-user-type=\"$userType\"]');
            if (userRow) {
                userRow.scrollIntoView({behavior: 'smooth', block: 'center'});
                userRow.style.backgroundColor = '#fffde7';
                setTimeout(() => {
                    userRow.style.backgroundColor = '';
                }, 3000);
            }
        });
    </script>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Forum - <?php echo htmlspecialchars($forum['title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        :root {
            --primary-color: #4a6fdc;
            --secondary-color: #6c757d;
            --success-color: #28a745;
            --warning-color: #fd7e14;
            --danger-color: #dc3545;
            --dark-color: #343a40;
            --light-color: #f8f9fa;
        }
        
        body {
            background-color: #f5f7fb;
            color: #333;
        }
        
        .container {
            max-width: 1200px;
        }
        
        .forum-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            border-left: 5px solid var(--primary-color);
            transition: all 0.3s ease;
        }
        
        .forum-header:hover {
            box-shadow: 0 6px 16px rgba(0,0,0,0.12);
        }
        
        .member-avatar {
            width: 42px;
            height: 42px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .table-responsive {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            background-color: white;
        }
        
        .table thead {
            background: linear-gradient(135deg, var(--primary-color) 0%, #3a5bd9 100%);
            color: white;
        }
        
        .table th {
            font-weight: 500;
            padding: 12px 15px;
        }
        
        .table td {
            padding: 12px 15px;
            vertical-align: middle;
        }
        
        .table-hover tbody tr {
            transition: all 0.2s ease;
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(74, 111, 220, 0.05);
            transform: translateY(-1px);
        }
        
        .badge-role {
            font-size: 0.75rem;
            padding: 4px 8px;
            font-weight: 500;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        
        .badge-admin {
            background-color: var(--danger-color);
        }
        
        .badge-moderator {
            background-color: var(--warning-color);
        }
        
        .badge-member {
            background-color: var(--secondary-color);
        }
        
        .badge-pending {
            background-color: var(--secondary-color);
        }
        
        .badge-banned {
            background-color: var(--dark-color);
        }
        
        .action-btn {
            width: 34px;
            height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            border-radius: 8px !important;
            transition: all 0.2s ease;
        }
        
        .action-btn:hover {
            transform: scale(1.1);
        }
        
        .section-title {
            border-bottom: 2px solid rgba(0,0,0,0.05);
            padding-bottom: 10px;
            margin-bottom: 25px;
            color: var(--primary-color);
            font-weight: 600;
            display: flex;
            align-items: center;
        }
        
        .section-title i {
            margin-right: 10px;
            font-size: 1.2em;
        }
        
        .no-members {
            background-color: rgba(248, 249, 250, 0.7);
            padding: 30px;
            text-align: center;
            border-radius: 12px;
            color: var(--secondary-color);
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .no-members i {
            font-size: 2.5rem;
            margin-bottom: 15px;
            opacity: 0.7;
        }
        
        .highlighted-row {
            animation: highlightFade 3s ease-out;
        }
        
        @keyframes highlightFade {
            0% { background-color: rgba(255, 253, 231, 1); }
            100% { background-color: rgba(255, 253, 231, 0); }
        }
        
        .btn-outline-primary {
            border-radius: 8px;
            padding: 8px 16px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            transform: translateY(-1px);
        }
        
        .form-select {
            border-radius: 8px;
            padding: 6px 12px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(74, 111, 220, 0.25);
        }
        
        .member-name {
            font-weight: 500;
            color: #333;
        }
        
        .member-type {
            font-size: 0.8rem;
            color: var(--secondary-color);
        }
        
        .status-badge {
            min-width: 80px;
            display: inline-block;
            text-align: center;
            border-radius: 50px;
            padding: 4px 10px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .search-box {
            position: relative;
            margin-bottom: 20px;
        }
        
        .search-box i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--secondary-color);
        }
        
        .search-input {
            padding-left: 40px;
            border-radius: 50px;
            border: 1px solid #dee2e6;
            transition: all 0.3s ease;
        }
        
        .search-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(74, 111, 220, 0.25);
        }
        
        .role-select {
            border-radius: 8px;
            padding: 4px 8px;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .role-select:hover {
            border-color: var(--primary-color);
        }
        
        .tab-content {
            padding: 20px 0;
        }
        
        .nav-tabs .nav-link {
            border: none;
            color: var(--secondary-color);
            font-weight: 500;
            padding: 10px 20px;
            border-radius: 8px 8px 0 0;
            transition: all 0.3s ease;
        }
        
        .nav-tabs .nav-link.active {
            color: var(--primary-color);
            background-color: transparent;
            border-bottom: 3px solid var(--primary-color);
        }
        
        .nav-tabs .nav-link:hover:not(.active) {
            color: var(--primary-color);
            background-color: rgba(74, 111, 220, 0.05);
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
        }
        
        .empty-state i {
            font-size: 3rem;
            color: var(--secondary-color);
            opacity: 0.6;
            margin-bottom: 15px;
        }
        
        .empty-state h5 {
            color: var(--secondary-color);
            font-weight: 500;
        }
        
        .tooltip-inner {
            border-radius: 6px;
            padding: 6px 12px;
            font-size: 0.8rem;
        }
        
        .back-btn {
            border-radius: 8px;
            padding: 8px 16px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            transition: all 0.3s ease;
        }
        
        .back-btn i {
            margin-right: 6px;
            transition: transform 0.3s ease;
        }
        
        .back-btn:hover i {
            transform: translateX(-3px);
        }
        
        @media (max-width: 768px) {
            .table-responsive {
                border-radius: 0;
                box-shadow: none;
            }
            
            .forum-header {
                padding: 15px;
            }
            
            .action-btn {
                width: 30px;
                height: 30px;
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <?php if (isset($_GET['message'])): ?>
            <div class="alert alert-success alert-dismissible fade show animate__animated animate__fadeIn" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <?php echo htmlspecialchars($_GET['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="forum-header animate__animated animate__fadeIn">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
                <div class="mb-3 mb-md-0">
                    <h2 class="mb-2"><i class="bi bi-people-fill me-2" style="color: var(--primary-color);"></i>Manage Forum: <?php echo htmlspecialchars($forum['title']); ?></h2>
                    <p class="lead mb-0 text-muted"><?php echo htmlspecialchars($forum['description']); ?></p>
                </div>
                <a href="../dashboard/forums.php?forum_id=<?php echo $forumId; ?>" class="btn btn-outline-primary back-btn">
                    <i class="bi bi-arrow-left"></i> Back to Forum
                </a>
            </div>
        </div>

        <!-- Search Box -->
        <div class="search-box animate__animated animate__fadeIn">
            <i class="bi bi-search"></i>
            <input type="text" id="memberSearch" class="form-control search-input" placeholder="Search members by name...">
        </div>

        <!-- Tab Navigation -->
        <ul class="nav nav-tabs animate__animated animate__fadeIn" id="memberTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="approved-tab" data-bs-toggle="tab" data-bs-target="#approved" type="button" role="tab">
                    <i class="bi bi-check-circle-fill me-1"></i> Approved Members
                    <span class="badge bg-primary ms-2"><?php echo count(array_filter($members, fn($m) => $m['status'] !== 'Pending')); ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" type="button" role="tab">
                    <i class="bi bi-hourglass-split me-1"></i> Pending Requests
                    <span class="badge bg-warning text-dark ms-2"><?php echo count(array_filter($members, fn($m) => $m['status'] === 'Pending')); ?></span>
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="memberTabsContent">
            <!-- Approved Members Tab -->
            <div class="tab-pane fade show active" id="approved" role="tabpanel">
                <?php if (count(array_filter($members, fn($m) => $m['status'] !== 'Pending')) > 0): ?>
                    <div class="table-responsive animate__animated animate__fadeIn">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th style="width: 50px;"></th>
                                    <th>Member</th>
                                    <th style="width: 150px;">Role</th>
                                    <th style="width: 120px;">Status</th>
                                    <th style="width: 220px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($members as $m): ?>
                                    <?php if ($m['status'] !== 'Pending'): ?>
                                    <tr class="member-row" data-user-id="<?php echo $m['entity_id']; ?>" data-user-type="<?php echo $m['entity_type']; ?>" data-search="<?php echo strtolower(htmlspecialchars($m['first_name'] . ' ' . $m['last_name'])); ?>">
                                        <td>
                                            <?php if ($m['picture']): ?>
                                                <img src="../Uploads/<?php echo htmlspecialchars($m['picture']); ?>" class="member-avatar" alt="<?php echo htmlspecialchars($m['first_name']); ?>">
                                            <?php else: ?>
                                                <div class="member-avatar bg-secondary d-flex align-items-center justify-content-center">
                                                    <i class="bi bi-person-fill text-white"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="member-name"><?php echo htmlspecialchars($m['first_name'] . ' ' . $m['last_name']); ?></div>
                                            <div class="member-type"><?php echo ucfirst($m['entity_type']); ?></div>
                                        </td>
                                        <td>
                                            <span class="badge badge-role 
                                                <?php echo $m['role'] === 'Admin' ? 'badge-admin' : 
                                                      ($m['role'] === 'Moderator' ? 'badge-moderator' : 'badge-member'); ?>">
                                                <?php echo $m['role']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-badge 
                                                <?php echo $m['status'] === 'Banned' ? 'bg-dark text-white' : 'bg-success text-white'; ?>">
                                                <?php echo $m['status']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($m['actor_id'] != $actorId): ?>
                                                <form action="update_member.php" method="POST" class="d-inline">
                                                    <input type="hidden" name="forum_id" value="<?php echo $forumId; ?>">
                                                    <input type="hidden" name="actor_id" value="<?php echo $m['actor_id']; ?>">
                                                    <input type="hidden" name="action" value="update_role">
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? bin2hex(random_bytes(32))); ?>">
                                                    
                                                    <div class="d-flex align-items-center">
                                                        <select name="new_role" class="form-select form-select-sm role-select me-2" onchange="this.form.submit()" data-bs-toggle="tooltip" data-bs-placement="top" title="Change role">
                                                            <option value="Member" <?php if ($m['role'] === 'Member') echo 'selected'; ?>>Member</option>
                                                            <option value="Moderator" <?php if ($m['role'] === 'Moderator') echo 'selected'; ?>>Moderator</option>
                                                            <option value="Admin" <?php if ($m['role'] === 'Admin') echo 'selected'; ?>>Admin</option>
                                                        </select>

                                                        <div class="btn-group btn-group-sm" role="group">
                                                            <?php if ($m['status'] !== 'Banned'): ?>
                                                                <button type="submit" name="action" value="ban" class="btn btn-outline-warning action-btn" data-bs-toggle="tooltip" data-bs-placement="top" title="Ban member">
                                                                    <i class="bi bi-slash-circle"></i>
                                                                </button>
                                                            <?php else: ?>
                                                                <button type="submit" name="action" value="unban" class="btn btn-outline-success action-btn" data-bs-toggle="tooltip" data-bs-placement="top" title="Unban member">
                                                                    <i class="bi bi-arrow-counterclockwise"></i>
                                                                </button>
                                                            <?php endif; ?>

                                                            <button type="submit" name="action" value="remove" class="btn btn-outline-danger action-btn" data-bs-toggle="tooltip" data-bs-placement="top" title="Remove member">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </div>
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
                    <div class="empty-state animate__animated animate__fadeIn">
                        <i class="bi bi-people"></i>
                        <h5>No approved members yet</h5>
                        <p class="text-muted">When members join and get approved, they'll appear here</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Pending Requests Tab -->
            <div class="tab-pane fade" id="pending" role="tabpanel">
                <?php if (count(array_filter($members, fn($m) => $m['status'] === 'Pending')) > 0): ?>
                    <div class="table-responsive animate__animated animate__fadeIn">
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
                                   <tr class="member-row" data-user-id="<?php echo $m['entity_id']; ?>" data-user-type="<?php echo $m['entity_type']; ?>" data-search="<?php echo strtolower(htmlspecialchars($m['first_name'] . ' ' . $m['last_name'])); ?>">
                                        <td>
                                            <?php if ($m['picture']): ?>
                                                <img src="../Uploads/<?php echo htmlspecialchars($m['picture']); ?>" class="member-avatar" alt="<?php echo htmlspecialchars($m['first_name']); ?>">
                                            <?php else: ?>
                                                <div class="member-avatar bg-secondary d-flex align-items-center justify-content-center">
                                                    <i class="bi bi-person-fill text-white"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="member-name"><?php echo htmlspecialchars($m['first_name'] . ' ' . $m['last_name']); ?></div>
                                            <div class="member-type"><?php echo ucfirst($m['entity_type']); ?></div>
                                        </td>
                                        <td>
                                            <?php echo date('M j, Y', strtotime($m['joined_at'])); ?>
                                        </td>
                                        <td>
                                            <form action="update_member.php" method="POST" class="d-inline">
                                                <input type="hidden" name="forum_id" value="<?php echo $forumId; ?>">
                                                <input type="hidden" name="actor_id" value="<?php echo $m['actor_id']; ?>">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? bin2hex(random_bytes(32))); ?>">
                                                
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <button type="submit" name="action" value="approve" class="btn btn-success action-btn" data-bs-toggle="tooltip" data-bs-placement="top" title="Approve request">
                                                        <i class="bi bi-check-lg"></i>
                                                    </button>
                                                    <button type="submit" name="action" value="remove" class="btn btn-danger action-btn" data-bs-toggle="tooltip" data-bs-placement="top" title="Reject request">
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
                    <div class="empty-state animate__animated animate__fadeIn">
                        <i class="bi bi-check2-all"></i>
                        <h5>No pending join requests</h5>
                        <p class="text-muted">When users request to join, they'll appear here for approval</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Highlight specific user if parameters exist
            const userId = '<?php echo $userId ?? ''; ?>';
            const userType = '<?php echo $userType ?? ''; ?>';
            
            if (userId && userType) {
                const userRow = document.querySelector(`tr[data-user-id="${userId}"][data-user-type="${userType}"]`);
                if (userRow) {
                    // Switch to the appropriate tab if needed
                    if (userRow.closest('#pending')) {
                        const pendingTab = new bootstrap.Tab(document.querySelector('#pending-tab'));
                        pendingTab.show();
                    }
                    
                    userRow.classList.add('highlighted-row');
                    setTimeout(() => {
                        userRow.scrollIntoView({behavior: 'smooth', block: 'center'});
                    }, 300);
                }
            }
            
            // Member search functionality
            const searchInput = document.getElementById('memberSearch');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    const memberRows = document.querySelectorAll('.member-row');
                    
                    memberRows.forEach(row => {
                        const searchText = row.getAttribute('data-search');
                        if (searchText.includes(searchTerm)) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                });
            }
            
            // Add animation to table rows
            const tableRows = document.querySelectorAll('.table tbody tr');
            tableRows.forEach((row, index) => {
                row.style.opacity = '0';
                row.style.transform = 'translateY(10px)';
                row.style.transition = 'all 0.3s ease';
                
                setTimeout(() => {
                    row.style.opacity = '1';
                    row.style.transform = 'translateY(0)';
                }, 100 + (index * 50));
            });
        });
    </script>
</body>
</html>