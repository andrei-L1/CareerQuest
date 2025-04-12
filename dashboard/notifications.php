<?php
require_once '../config/dbcon.php';
include '../includes/stud_navbar.php';


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
    // User is logged in (employer/professional/admin)
    $currentUser = [
        'entity_type' => 'user',
        'entity_id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_first_name'] ?? 'User',
        'role' => $_SESSION['user_type'] ?? 'Unknown',
        'email' => $_SESSION['user_email'] ?? '',
        'picture' => $_SESSION['picture_file'] ?? ''
    ];

    // Fetch additional details from the user table
    $user_id = $currentUser['entity_id'];
    $query = "SELECT * FROM user WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$user_id]);
    $userDetails = $stmt->fetch(PDO::FETCH_ASSOC);

    // Update current user data with the fetched details
    if ($userDetails) {
        $currentUser['full_name'] = $userDetails['user_first_name'] . ' ' . $userDetails['user_last_name'];
        $currentUser['email'] = $userDetails['user_email'];
        $currentUser['picture'] = $userDetails['picture_file'];
        $currentUser['status'] = $userDetails['status'];
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

    // Update current user data with the fetched details
    if ($studentDetails) {
        $currentUser['full_name'] = $studentDetails['stud_first_name'] . ' ' . $studentDetails['stud_last_name'];
        $currentUser['email'] = $studentDetails['stud_email'];
        $currentUser['picture'] = $studentDetails['profile_picture'];
        $currentUser['status'] = $studentDetails['status'];
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications</title>
    <style>
        .notification {
            padding: 10px;
            border: 1px solid #ddd;
            margin: 10px 0;
        }
        .notification.read {
            background-color: #f0f0f0;
        }
        .notification.unread {
            background-color: #e0f7fa;
        }
    </style>
</head>
<body>
    <h1>Notifications</h1>
    
    <?php if (empty($notifications)) : ?>
        <p>No new notifications.</p>
    <?php else : ?>
        <?php foreach ($notifications as $notification) : ?>
            <div class="notification <?php echo $notification['is_read'] ? 'read' : 'unread'; ?>">
                <p><strong>Notification:</strong> <?php echo htmlspecialchars($notification['message']); ?></p>
                <p><strong>Type:</strong> <?php echo htmlspecialchars($notification['notification_type']); ?></p>
                <p><strong>Created at:</strong> <?php echo date("Y-m-d H:i:s", strtotime($notification['created_at'])); ?></p>
                <?php if ($notification['action_url']) : ?>
                    <p><a href="<?php echo htmlspecialchars($notification['action_url']); ?>">Take Action</a></p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

</body>
</html>
