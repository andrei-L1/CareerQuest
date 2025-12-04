<?php
require_once '../config/dbcon.php';
/** @var PDO $conn */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
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
}

// Ensure actor record exists
$query = "SELECT actor_id FROM actor WHERE entity_type = ? AND entity_id = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$currentUser['entity_type'], $currentUser['entity_id']]);
$actor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$actor) {
    // Create actor record if it doesn't exist
    $query = "INSERT INTO actor (entity_type, entity_id) VALUES (?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->execute([$currentUser['entity_type'], $currentUser['entity_id']]);
    $currentUser['actor_id'] = $conn->lastInsertId();
} else {
    $currentUser['actor_id'] = $actor['actor_id'];
}

// Mark all notifications as read if requested
if (isset($_GET['mark_all_read'])) {
    $query = "UPDATE notification SET is_read = TRUE WHERE actor_id = ? AND is_read = FALSE";
    $stmt = $conn->prepare($query);
    $stmt->execute([$currentUser['actor_id']]);
}

// Fetch notifications based on entity type
if ($currentUser['entity_type'] === 'student') {
    // Students: Fetch only 'announcement' notifications
    $query = "SELECT * FROM notification 
              WHERE actor_id = ? AND deleted_at IS NULL AND notification_type = 'announcement'
              ORDER BY is_read ASC, created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute([$currentUser['actor_id']]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Count unread notifications for students
    $query = "SELECT COUNT(*) AS unread_count FROM notification 
              WHERE actor_id = ? AND is_read = FALSE AND deleted_at IS NULL AND notification_type = 'announcement'";
    $stmt = $conn->prepare($query);
    $stmt->execute([$currentUser['actor_id']]);
    $unreadCount = $stmt->fetch(PDO::FETCH_ASSOC)['unread_count'];
} else {
    // Users: Fetch 'announcement' and 'membership_request' notifications
    $query = "SELECT * FROM notification 
              WHERE actor_id = ? AND deleted_at IS NULL 
              AND notification_type IN ('announcement', 'membership_request')
              ORDER BY is_read ASC, created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute([$currentUser['actor_id']]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Count unread notifications for users
    $query = "SELECT COUNT(*) AS unread_count FROM notification 
              WHERE actor_id = ? AND is_read = FALSE AND deleted_at IS NULL 
              AND notification_type IN ('announcement', 'membership_request')";
    $stmt = $conn->prepare($query);
    $stmt->execute([$currentUser['actor_id']]);
    $unreadCount = $stmt->fetch(PDO::FETCH_ASSOC)['unread_count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Career Platform</title>
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
        }

        .notification-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
        }

        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .notification-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .notification-item {
            background: white;
            border-radius: var(--border-radius);
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            border: 1px solid var(--light-gray);
        }

        .notification-item.unread {
            background-color: #f0f7ff;
            border-left: 4px solid var(--primary-color);
        }

        .notification-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .notification-message {
            font-size: 0.95rem;
            margin-bottom: 0.5rem;
        }

        .notification-time {
            font-size: 0.8rem;
            color: var(--secondary-color);
        }

        .notification-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 0.75rem;
            padding-top: 0.75rem;
            border-top: 1px solid var(--light-gray);
        }

        .mark-read-btn {
            font-size: 0.8rem;
            color: var(--primary-color);
            cursor: pointer;
        }

        .mark-read-btn:hover {
            text-decoration: underline;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--secondary-color);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--light-gray);
        }

        @media (max-width: 767px) {
            .notification-container {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <?php require '../includes/forum_sidebar.php'; ?>
    
    <div class="forum-container">
        <div class="forum-sidebar">
            <!-- Your existing sidebar content -->
        </div>
        
        <div class="notification-container">
            <div class="notification-header">
                <h2>Notifications</h2>
                <div class="d-flex align-items-center gap-2">
                    <?php if ($unreadCount > 0): ?>
                        <a href="notifications.php?mark_all_read=1" class="btn btn-sm btn-primary">
                            <i class="bi bi-check-all"></i> Mark all as read
                        </a>
                    <?php endif; ?>
                    <span class="badge bg-<?php echo $unreadCount > 0 ? 'primary' : 'secondary'; ?>">
                        <?php echo $unreadCount; ?> unread
                    </span>
                </div>
            </div>

            <?php if (count($notifications) > 0): ?>
                <ul class="notification-list">
                    <?php foreach ($notifications as $notification): ?>
                        <li class="notification-item <?php echo !$notification['is_read'] ? 'unread' : ''; ?>" 
                            data-notification-id="<?php echo $notification['notification_id']; ?>">
                            <div class="notification-message">
                                <?php echo htmlspecialchars($notification['message']); ?>
                            </div>
                            <div class="notification-time">
                                <?php 
                                    $now = new DateTime();
                                    $createdAt = new DateTime($notification['created_at']);
                                    $interval = $now->diff($createdAt);
                                    
                                    if ($interval->y > 0) {
                                        echo $interval->y . ' year' . ($interval->y > 1 ? 's' : '') . ' ago';
                                    } elseif ($interval->m > 0) {
                                        echo $interval->m . ' month' . ($interval->m > 1 ? 's' : '') . ' ago';
                                    } elseif ($interval->d > 0) {
                                        echo $interval->d . ' day' . ($interval->d > 1 ? 's' : '') . ' ago';
                                    } elseif ($interval->h > 0) {
                                        echo $interval->h . ' hour' . ($interval->h > 1 ? 's' : '') . ' ago';
                                    } elseif ($interval->i > 0) {
                                        echo $interval->i . ' minute' . ($interval->i > 1 ? 's' : '') . ' ago';
                                    } else {
                                        echo 'Just now';
                                    }
                                ?>
                            </div>
                            <div class="notification-actions">
                                <?php if ($notification['action_url']): ?>
                                    <a href="<?php echo htmlspecialchars($notification['action_url']); ?>" class="btn btn-sm btn-outline-primary">
                                        View
                                    </a>
                                <?php else: ?>
                                    <span></span>
                                <?php endif; ?>
                                <?php if (!$notification['is_read']): ?>
                                    <span class="mark-read-btn" data-notification-id="<?php echo $notification['notification_id']; ?>">
                                        Mark as read
                                    </span>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="empty-state">
                    <i class="bi bi-bell-slash"></i>
                    <h4>No notifications yet</h4>
                    <p>When you get notifications, they'll appear here</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mark notification as read
            document.querySelectorAll('.mark-read-btn').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const notificationId = this.dataset.notificationId;
                    const notificationItem = this.closest('.notification-item');
                    
                    fetch('../forum/mark_notification_read.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: `notification_id=${notificationId}`
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            notificationItem.classList.remove('unread');
                            this.remove();
                            
                            // Update unread count
                            const badge = document.querySelector('.notification-header .badge');
                            if (badge) {
                                const currentCount = parseInt(badge.textContent);
                                if (currentCount > 1) {
                                    badge.textContent = currentCount - 1;
                                } else {
                                    badge.textContent = '0';
                                    badge.classList.remove('bg-primary');
                                    badge.classList.add('bg-secondary');
                                    
                                    // Hide "Mark all as read" button if no more unread
                                    const markAllBtn = document.querySelector('.notification-header a');
                                    if (markAllBtn) {
                                        markAllBtn.remove();
                                    }
                                }
                            }
                        } else {
                            alert('Failed to mark notification as read');
                        }
                    })
                    .catch(err => {
                        console.error('Error:', err);
                        alert('An error occurred');
                    });
                });
            });

            // Clicking on a notification marks it as read if unread
            document.querySelectorAll('.notification-item.unread').forEach(item => {
                item.addEventListener('click', function(e) {
                    // Don't mark as read if clicking on a button or link
                    if (e.target.tagName === 'A' || e.target.tagName === 'BUTTON' || e.target.closest('a') || e.target.closest('button')) {
                        return;
                    }

                    const notificationId = this.dataset.notificationId;
                    const markReadBtn = this.querySelector('.mark-read-btn');
                    
                    fetch('../forum/mark_notification_read.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: `notification_id=${notificationId}`
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            this.classList.remove('unread');
                            if (markReadBtn) {
                                markReadBtn.remove();
                            }
                            
                            // Update unread count
                            const badge = document.querySelector('.notification-header .badge');
                            if (badge) {
                                const currentCount = parseInt(badge.textContent);
                                if (currentCount > 1) {
                                    badge.textContent = currentCount - 1;
                                } else {
                                    badge.textContent = '0';
                                    badge.classList.remove('bg-primary');
                                    badge.classList.add('bg-secondary');
                                    
                                    // Hide "Mark all as read" button if no more unread
                                    const markAllBtn = document.querySelector('.notification-header a');
                                    if (markAllBtn) {
                                        markAllBtn.remove();
                                    }
                                }
                            }
                        }
                    })
                    .catch(err => {
                        console.error('Error:', err);
                    });
                });
            });
        });
    </script>
</body>
</html>