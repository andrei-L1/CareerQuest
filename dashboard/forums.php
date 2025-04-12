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
        }

        .forum-title {
            font-weight: 600;
            color: var(--dark-color);
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
                <button id="new-forum-btn" class="new-forum-btn" title="Create New Forum">
                    <i class="bi bi-plus-lg"></i>
                </button>
            </div>
            
            <!-- Forum Navigation -->
            <div class="forum-navigation">
                <div class="nav-section">
                    <h4 class="nav-title">Main Forums</h4>
                    <ul class="nav-links">
                        <li class="nav-item active">
                            <a href="#"><i class="bi bi-house-door"></i> Home</a>
                        </li>
                        <li class="nav-item">
                            <a href="#"><i class="bi bi-people"></i> Notification</a>
                        </li>
                    </ul>
                </div>
                
                <div class="nav-section">
                    <h4 class="nav-title">Popular Forums</h4>
                    <ul class="nav-links">
                        <li class="nav-item">
                            <a href="#"><i class="bi bi-code-square"></i> Technology</a>
                        </li>
                        <li class="nav-item">
                            <a href="#"><i class="bi bi-cash-coin"></i> Finance</a>
                        </li>
                        <li class="nav-item">
                            <a href="#"><i class="bi bi-book"></i> Education</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Main Forum Content -->
        <div class="forum-content">
            <div class="forum-header">
                <h2 class="forum-title">General Discussion</h2>
            </div>
            
            <!-- Forum posts/content will go here -->
            <div class="alert alert-info">
                Select a forum category to view discussions
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Navigation item click handlers
            document.querySelectorAll('.nav-item a').forEach(item => {
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    // Remove active class from all items
                    document.querySelectorAll('.nav-item').forEach(navItem => {
                        navItem.classList.remove('active');
                    });
                    // Add active class to clicked item
                    this.parentElement.classList.add('active');
                    
                    // Update forum title
                    const forumTitle = document.querySelector('.forum-title');
                    forumTitle.textContent = this.textContent.trim();
                });
            });
            
            // New forum button functionality
            document.getElementById('new-forum-btn').addEventListener('click', function() {
                alert('New forum creation feature will be implemented here');
            });
        });
    </script>
</body>
</html>