<?php
require_once '../config/dbcon.php';
//include '../includes/stud_navbar.php';

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
    <title>Messaging - <?php echo htmlspecialchars($currentUser['name']); ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

    <script src="https://js.pusher.com/8.4.0/pusher.min.js"></script>
    <script>
        var pusher = new Pusher('d9d029433bbefa08b6a2', {
            cluster: 'ap1'
        });

        // Global variable to store the current thread channel
        var currentChannel = null;
    </script>


    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --success-color: #4cc9f0;
            --info-color: #4895ef;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color:rgba(245, 247, 251, 0.75);
            height: 100vh;
            margin: 0;
        }

        .container-fluid {
            display: flex;
            justify-content: center; 
            align-items: center; 
            padding: 100px;
            height: 90%;
            border-radius: 10px;
            overflow: hidden;
        }

        .chat-container {
            width: 90%;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            height: 80vh;
        }

        .sidebar {
            background-color: white;
            border-right: 1px solid #e9ecef;
            height: 100%;
            overflow-y: auto;
        }
        
        .chat-area {
            background-color: #f8fafc;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .user-info {
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
            background-color: var(--primary-color);
            color: white;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            overflow: hidden;
            margin-right: 10px;
        }
        
        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .thread {
            padding: 12px 15px;
            border-bottom: 1px solid #e9ecef;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .thread:hover {
            background-color: #f8f9fa;
        }
        
        .thread.active {
            background-color: #e6f2ff;
        }
        
        .thread-unread {
            font-weight: bold;
        }
        
        .thread-time {
            font-size: 0.75rem;
            color: #6c757d;
        }
        
        .thread-last-message {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-size: 0.85rem;
            color: #6c757d;
        }
        
        .message-list {
            flex: 1;
            padding: 1rem;
            overflow-y: auto;
            background-color: var(--bg-color);
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        
        
        /* Update your message styling */
        .message {
            max-width: 80%;
            margin-bottom: 8px;
            padding: 12px 16px;
            border-radius: 18px;
            position: relative;
            word-wrap: break-word;
            line-height: 1.4;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .message-sent {
            background-color: var(--primary-color);
            color: white;
            margin-left: auto;
            border-bottom-right-radius: 0.25rem;
        }
        
        .message-received {
            background-color: white;
            margin-right: auto;
            border-bottom-left-radius: 0.25rem;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }
        
        .message-time {
            display: none;
            position: absolute;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.7rem;
            white-space: nowrap;
            z-index: 10;
        }
        .message:hover .message-time {
            display: block;
        }
        
        .message-sent .message-time {
            left: -0.5rem;
            top: -1.5rem;
        }
        
        .message-received .message-time {
            right: -0.5rem;
            top: -1.5rem;
        }
        
        .message-input-container {
            padding: 1rem;
            border-top: 1px solid var(--border-color);
            background-color: var(--card-bg);
        }
        .message-input {
            border-radius: 1.5rem;
            padding: 0.75rem 1rem;
            resize: none;
            border: 1px solid var(--border-color);
        }

        /* Add triangle pointers */
        .message-sent::after {
            content: '';
            position: absolute;
            right: -8px;
            top: 50%;
            transform: translateY(-50%);
            width: 0;
            height: 0;
            border: 8px solid transparent;
            border-left: 8px solid var(--primary-color);
        }

        .message-received::after {
            content: '';
            position: absolute;
            left: -8px;
            top: 50%;
            transform: translateY(-50%);
            width: 0;
            height: 0;
            border: 8px solid transparent;
            border-right: 8px solid white;  
        }

        
        .message-time {
            display: none;
            position: absolute;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.7rem;
            white-space: nowrap;
            z-index: 10;
        }

        .message:hover .message-time {
            display: block;
        }

        .message-sent .message-time {
            left: -10px;
            top: -25px;
        }

        .message-received .message-time {
            right: -10px;
            top: -25px;
        }
                
        .message-input {
            padding: 15px;
            border-top: 1px solid #e9ecef;
            background-color: white;
        }
        
        .unread-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background-color: var(--info-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
        }
        
        .chat-header {
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
            background-color: white;
            display: flex;
            align-items: center;
        }
        
        .back-to-inbox {
            margin-right: 10px;
            display: none;
        }
        
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            text-align: center;
            padding: 40px; 
            color: #6c757d;
            background-color: #f8f9fa;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #adb5bd; 
        }

        .empty-state h4 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #343a40;
            margin-bottom: 10px;
        }

        .empty-state-text {
            font-size: 1rem;
            margin-bottom: 20px;
            color: #6c757d;
        }

        #empty-new-message-btn {
            font-size: 1rem;
            padding: 10px 20px;
            border-radius: 30px;
        }

        #empty-new-message-btn i {
            font-size: 1.2rem;
        }

        .mt-4 small {
            font-size: 0.875rem;
            color: #6c757d;
        }

        
        
        @media (max-width: 768px) {
            .sidebar {
                display: block;
            }
            
            .chat-area {
                display: none;
            }
            
            .chat-area.active {
                display: flex;
            }
            
            .back-to-inbox {
                display: block;
            }
        }
        /* Bigger Sidebar Navigation */
        .sidebar-nav {
            width: 80px; /* Increased from 60px */
            background-color: #f8f9fa;
            border-right: 1px solid #e9ecef;
        }

        .sidebar-nav .btn {
            width: 56px;  
            height: 56px; 
            border-radius: 12px; 
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--border-color);
            transition: all 0.2s;
            margin: 0 auto 12px auto; 
        }

        .sidebar-nav .btn i {
            font-size: 1.75rem; 
        }

        .sidebar-nav .btn:hover {
            background-color: var(--primary-light);
            border-color: var(--primary-color);
            transform: scale(1.08); 
        }

        .sidebar-nav .btn:hover {
            background-color: blue;
            border-color: var(--primary-color);
            transform: scale(1.05);
        }

  
        /* Tooltip styling */
        .tooltip-inner {
            background-color: var(--primary-color);
        }

        .bs-tooltip-end .tooltip-arrow::before {
            border-right-color: var(--primary-color);
        }
    </style>
    <style>

        /* Navbar background color */
        .navbar {
            background-color: var(--primary-color);
            padding: 10px 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        /* Navbar brand logo adjustments */
        .navbar-brand img {
            border-radius: 50%;
            border: 2px solid #fff;
        }

        /* Navbar brand text */
        .navbar .navbar-brand {
            font-family: 'Arial', sans-serif;
            font-weight: bold;
            color: var(--primary-color) !important;
        }

        /* Hover effect on brand */
        .navbar .navbar-brand:hover {
            color: #FFD700;
        }

        /* Change text and link color */
        .navbar-light .navbar-nav .nav-link {
            color: #ffffff !important;
        }

        /* Hover effect for navbar links */
        .navbar-light .navbar-nav .nav-link:hover {
            color: #FFD700 !important;
        }

        /* Adjust padding and spacing */
        .navbar a {
            padding-left: 10%; 
        }

        /* Custom spacing between navbar items */
        .navbar-nav .nav-item {
            margin-right: 15px;
        }

        /* Responsiveness for mobile */
        @media (max-width: 767px) {
            .navbar-nav {
                justify-content: center;
            }
        }

    </style>
</head>

<body>
    <nav class="navbar navbar-light bg-light">
        <a class="navbar-brand" href="#">
           <!-- <img src="/docs/4.0/assets/brand/bootstrap-solid.svg" width="30" height="30" class="d-inline-block align-top" alt=""> -->
            CareerQuest
        </a>
    </nav>
    <div class="container-fluid py-3">
        <div class="row chat-container bg-white">
            <!-- Sidebar -->
            <div class="col-md-4 col-lg-3 px-0 sidebar">
                <div class="d-flex h-100">
                    <!-- Vertical Navigation -->
                    <div class="sidebar-nav d-flex flex-column p-3 border-end" style="width: 80px; background-color: #f8f9fa;">
                        <a href="../index.php" class="btn btn-outline-primary mb-3 p-3 d-flex align-items-center justify-content-center" 
                        data-bs-toggle="tooltip" data-bs-placement="right" title="Home">
                            <i class="bi bi-house-door fs-3"></i>
                        </a>
                        <a href="../forum/index.php" class="btn btn-outline-primary p-3 d-flex align-items-center justify-content-center" 
                        data-bs-toggle="tooltip" data-bs-placement="right" title="Forum">
                            <i class="bi bi-people fs-3"></i>
                        </a>
                    </div>

                    <!-- Main Sidebar Content -->
                    <div class="flex-grow-1 d-flex flex-column" style="width: calc(100% - 60px);">
                        <div class="user-info d-flex align-items-center">
                            <div class="user-avatar">
                                <?php if (!empty($currentUser['picture'])): ?>
                                    <img src="../uploads/<?php echo htmlspecialchars($currentUser['picture']); ?>" alt="Profile Picture">
                                <?php else: ?>
                                    <div class="bg-secondary d-flex align-items-center justify-content-center h-100">
                                        <i class="bi bi-person-fill text-white"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div>
                                <div class="fw-bold"><?php echo htmlspecialchars($currentUser['name']); ?></div>
                                <small class="text-white-50"><?php echo htmlspecialchars($currentUser['role']); ?></small>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center p-3 border-bottom">
                            <h5 class="mb-0">Messages</h5>
                            <button id="new-message-btn" class="btn btn-sm btn-primary">
                                <i class="bi bi-plus-lg"></i> New
                            </button>
                        </div>
                        
                        <div id="threads-list" class="list-group list-group-flush flex-grow-1" style="overflow-y: auto;">
                            <!-- Threads will be loaded here -->
                            <div class="text-center py-5">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Chat Area -->
            <div class="col-md-8 col-lg-9 px-0 chat-area" id="chat-area">
                <div class="chat-header">
                    <button class="btn btn-sm btn-outline-secondary back-to-inbox" id="back-to-inbox">
                        <i class="bi bi-arrow-left"></i>
                    </button>
                    <div class="d-flex align-items-center">
                        <div id="chat-avatar" class="user-avatar me-2">
                            <div class="bg-secondary d-flex align-items-center justify-content-center h-100">
                                <i class="bi bi-person-fill text-white"></i>
                            </div>
                        </div>
                        <div>
                            <h5 class="mb-0" id="chat-with-name">Select a conversation</h5>
                            <small class="text-muted" id="chat-status"></small>
                        </div>
                    </div>
                </div>
                
                <!-- Update your empty-chat-state -->
                <div class="empty-state" id="empty-chat-state">
                    <div class="empty-state-icon">
                        <i class="bi bi-chat-square-text"></i>
                    </div>
                    <h4>Your messages live here</h4>
                    <p class="empty-state-text">Select a conversation or start a new one to begin messaging</p>
                    <button id="empty-new-message-btn" class="btn btn-primary mt-3">
                        <i class="bi bi-plus-lg me-2"></i> New Message
                    </button>
                    <div class="mt-4">
                        <small class="text-muted">Tip: You can search for people by name or email</small>
                    </div>
                </div>
                                
                <div class="message-list d-none" id="message-list"></div>
                
                <div class="message-input d-none" id="message-input-container">
                    <div class="input-group">
                        <textarea class="form-control" id="message-content" rows="1" placeholder="Type a message..." style="resize: none;"></textarea>
                        <button class="btn btn-primary" id="send-button" type="button">
                            <i class="bi bi-send-fill"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- New Message Modal -->
    <div class="modal fade" id="newMessageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">New Message</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <input type="text" class="form-control" id="recipient-search" placeholder="Search for users...">
                    </div>
                    <div id="search-results" class="list-group" style="max-height: 300px; overflow-y: auto;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="start-conversation" disabled>Start Conversation</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        let currentThreadId = null;
        let currentParticipant = null;
        let currentUser = <?php echo json_encode($currentUser); ?>;
        let newMessageModal = new bootstrap.Modal(document.getElementById('newMessageModal'));

        // Load threads
        function loadThreads() {
            fetch('../controllers/messages_controller.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=get_threads`
            })
            .then(response => response.json())
            .then(data => {
                const threadsList = document.getElementById('threads-list');
                
                if (data.status === 'success') {
                    threadsList.innerHTML = '';
                    
                    if (data.threads.length === 0) {
                        threadsList.innerHTML = `
                            <div class="text-center py-5">
                                <i class="bi bi-chat-square-text fs-1 text-muted mb-3"></i>
                                <p class="text-muted">No conversations yet</p>
                                <button class="btn btn-sm btn-primary" id="empty-list-new-message-btn">
                                    Start a conversation
                                </button>
                            </div>
                        `;
                        
                        document.getElementById('empty-list-new-message-btn').addEventListener('click', () => {
                            newMessageModal.show();
                        });
                        return;
                    }

                    data.threads.forEach(thread => {
                    const threadEl = document.createElement('a');
                    threadEl.className = `list-group-item list-group-item-action thread ${thread.unread_count > 0 ? 'thread-unread' : ''}`;
                    threadEl.innerHTML = `
                        <div class="d-flex align-items-center gap-3">
                         <img src="${thread.participant_picture || `https://api.dicebear.com/7.x/initials/svg?seed=${thread.participant_name || 'user'}`}" 
                            alt="${thread.participant_name}" 
                            class="rounded-circle" 
                            style="width: 40px; height: 40px; object-fit: cover;">
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start">
                                    <strong>${thread.participant_name}</strong>
                                    <small class="thread-time">${formatTime(thread.last_message_time)}</small>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <p class="thread-last-message mb-0">${thread.last_message_content}</p>
                                    ${thread.unread_count > 0 ? `<span class="badge bg-primary rounded-pill">${thread.unread_count}</span>` : ''}
                                </div>
                            </div>
                        </div>
                    `;
                    threadEl.addEventListener('click', () => openThread(thread.thread_id));
                    threadsList.appendChild(threadEl);
                });



                } else {
                    threadsList.innerHTML = `
                        <div class="alert alert-danger">Error loading conversations</div>
                    `;
                }
            });
        }

        // Format time for display
        function formatTime(timestamp) {
            const date = new Date(timestamp);
            const now = new Date();
            
            if (date.toDateString() === now.toDateString()) {
                return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            } else if (date.getFullYear() === now.getFullYear()) {
                return date.toLocaleDateString([], { month: 'short', day: 'numeric' });
            } else {
                return date.toLocaleDateString([], { year: 'numeric', month: 'short', day: 'numeric' });
            }
        }

        // Open a thread
        function openThread(threadId) {

                // Initialize Pusher only when a thread is opened
                if (currentChannel) {
                    pusher.unsubscribe(currentChannel.name);
                }

                // Subscribe to the channel for this specific thread
                currentChannel = pusher.subscribe('thread_' + threadId);
                
                currentChannel.bind('new_message', function(data) {
                    // Check if the new message belongs to the current thread
                    if (data.thread_id === threadId) {
                        // Add the new message to the UI
                        const messageList = document.getElementById('message-list');
                        const messageEl = document.createElement('div');
                        const isSender = data.sender_type === currentUser.entity_type && 
                                    data.sender_id == currentUser.entity_id;
                        
                        messageEl.className = `message ${isSender ? 'message-sent' : 'message-received'}`;
                        messageEl.innerHTML = `
                            <div>${data.content}</div>
                            <div class="message-time">${formatTime(data.sent_at)}</div>
                        `;
                        messageList.appendChild(messageEl);
                        
                        // Scroll to bottom
                        messageList.scrollTop = messageList.scrollHeight;
                        
                        // If the message is not from the current user, mark as read
                        if (!isSender) {
                            markMessagesAsRead(threadId);
                        }
                    }
                });
                
            // Mobile view - hide sidebar and show chat area
            
            if (window.innerWidth <= 768) {
                document.querySelector('.sidebar').style.display = 'none';
                document.getElementById('chat-area').classList.add('active');
            }
            
            currentThreadId = threadId;
            document.getElementById('empty-chat-state').classList.add('d-none');
            document.getElementById('message-list').classList.remove('d-none');
            document.getElementById('message-input-container').classList.remove('d-none');
            
            fetch('../controllers/messages_controller.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=get_messages&thread_id=${threadId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    currentParticipant = data.participant;
                    
                    // Get participant info
                    fetch('../controllers/messages_controller.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `action=get_user_info&entity_type=${currentParticipant.entity_type}&entity_id=${currentParticipant.entity_id}`
                    })
                    .then(response => response.json())
                    .then(userData => {
                        if (userData.status === 'success') {
                            document.getElementById('chat-with-name').textContent = userData.user.name;
                            
                            // Update avatar
                            const chatAvatar = document.getElementById('chat-avatar');
                            chatAvatar.innerHTML = '';
                            console.log(userData); 

                            if (userData.user.picture) {
                                const img = document.createElement('img');
                                img.src = `../uploads/${userData.user.picture}`;
                                img.alt = userData.user.name;
                                chatAvatar.appendChild(img);
                            } else {
                                const icon = document.createElement('i');
                                icon.className = 'bi bi-person-fill text-white';
                                chatAvatar.querySelector('div').appendChild(icon);
                            }
                        }
                    });

                    // Display messages
                    const messageList = document.getElementById('message-list');
                    messageList.innerHTML = '';
                    
                    if (data.messages.length === 0) {
                        messageList.innerHTML = `
                            <div class="text-center py-5">
                                <p class="text-muted">No messages yet in this conversation</p>
                            </div>
                        `;
                    } else {
                        data.messages.forEach(message => {
                            const messageEl = document.createElement('div');
                            const isSender = message.sender_type === currentUser.entity_type && 
                                            message.sender_id == currentUser.entity_id;
                            
                            messageEl.className = `message ${isSender ? 'message-sent' : 'message-received'}`;
                            messageEl.innerHTML = `
                                <div>${message.content}</div>
                                <div class="message-time">${formatTime(message.sent_at)}</div>
                            `;
                            messageList.appendChild(messageEl);
                        });
                    }
                    
                    // Scroll to bottom
                    messageList.scrollTop = messageList.scrollHeight;
                    
                    // Mark messages as read
                    if (data.unread_count > 0) {
                        markMessagesAsRead(threadId);
                    }
                }
            });
        }

        // Mark messages as read
        function markMessagesAsRead(threadId) {
            fetch('../controllers/messages_controller.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=mark_as_read&thread_id=${threadId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    loadThreads();
                }
            });
        }

        // Send a message
        function sendMessage() {
            const content = document.getElementById('message-content').value.trim();
            if (!content) return;
            
            if (!currentThreadId && !currentParticipant) {
                alert('Please select a conversation first');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'send_message');
            formData.append('receiver_type', currentParticipant.entity_type);
            formData.append('receiver_id', currentParticipant.entity_id);
            formData.append('content', content);
            if (currentThreadId) formData.append('thread_id', currentThreadId);

            fetch('../controllers/messages_controller.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    document.getElementById('message-content').value = '';
                    if (!currentThreadId) {
                        currentThreadId = data.thread_id;
                    }
                    openThread(currentThreadId);
                    loadThreads();
                }
            });
        }

        // Event listeners
        document.getElementById('send-button').addEventListener('click', sendMessage);
        
        document.getElementById('message-content').addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
        
        // Auto-resize textarea
        document.getElementById('message-content').addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });

        // New message functionality
        let selectedRecipient = null;
        
        document.getElementById('new-message-btn').addEventListener('click', () => {
            newMessageModal.show();
        });
        
        document.getElementById('empty-new-message-btn').addEventListener('click', () => {
            newMessageModal.show();
        });

        // Search for recipients
        document.getElementById('recipient-search').addEventListener('input', (e) => {
            const searchTerm = e.target.value.trim();
            const resultsContainer = document.getElementById('search-results');
            
            if (searchTerm.length < 2) {
                resultsContainer.innerHTML = '<div class="list-group-item text-muted text-center">Type at least 2 characters to search</div>';
                return;
            }

            fetch('../controllers/messages_controller.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=search_users&term=${encodeURIComponent(searchTerm)}&current_user_type=${currentUser.entity_type}&current_user_id=${currentUser.entity_id}`
            })
            .then(response => response.json())
            .then(data => {
                resultsContainer.innerHTML = '';
                
                if (data.status === 'success' && data.users.length > 0) {
                    data.users.forEach(user => {
                        const userEl = document.createElement('button');
                        userEl.className = 'list-group-item list-group-item-action';
                        userEl.type = 'button';
                        userEl.innerHTML = `
                            <div class="d-flex align-items-center">
                                <div class="user-avatar me-3">
                                    ${user.picture ? 
                                        `<img src="../uploads/${user.picture}" alt="${user.name}">` : 
                                        `<div class="bg-secondary d-flex align-items-center justify-content-center h-100">
                                            <i class="bi bi-person-fill text-white"></i>
                                        </div>`}
                                </div>
                                <div>
                                    <div class="fw-bold">${user.name}</div>
                                    <small class="text-muted">${user.role}</small>
                                </div>
                            </div>
                        `;
                        
                        userEl.addEventListener('click', () => {
                            // Remove active class from all
                            document.querySelectorAll('#search-results button').forEach(el => {
                                el.classList.remove('active');
                            });
                            
                            // Add active class to this one
                            userEl.classList.add('active');
                            
                            selectedRecipient = {
                                entity_type: user.entity_type,
                                entity_id: user.entity_id,
                                name: user.name,
                                picture: user.picture
                            };
                            console.log(selectedRecipient);  // Check if picture path exists

                            document.getElementById('start-conversation').disabled = false;
                        });
                        
                        resultsContainer.appendChild(userEl);
                    });
                } else {
                    resultsContainer.innerHTML = '<div class="list-group-item text-muted text-center">No users found</div>';
                }
            });
        });

        
        // Start new conversation
        document.getElementById('start-conversation').addEventListener('click', () => {
            if (!selectedRecipient) return;
            
            // Close the modal
            newMessageModal.hide();
            document.getElementById('search-results').innerHTML = '';
            document.getElementById('recipient-search').value = '';
            document.getElementById('start-conversation').disabled = true;
            
            // Set up the chat with this recipient
            currentThreadId = null;
            currentParticipant = {
                entity_type: selectedRecipient.entity_type,
                entity_id: selectedRecipient.entity_id
            };
            
            // Update chat header
            document.getElementById('empty-chat-state').classList.add('d-none');
            document.getElementById('message-list').classList.remove('d-none');
            document.getElementById('message-input-container').classList.remove('d-none');
            document.getElementById('chat-with-name').textContent = selectedRecipient.name;
            
            // Update avatar
            const chatAvatar = document.getElementById('chat-avatar');
            chatAvatar.innerHTML = '';
            if (selectedRecipient.picture) {
                const img = document.createElement('img');
                img.src = `../uploads/${selectedRecipient.picture}`;
                img.alt = selectedRecipient.name;
                chatAvatar.appendChild(img);
            } else {
                const icon = document.createElement('i');
                icon.className = 'bi bi-person-fill text-white';
                chatAvatar.querySelector('div').appendChild(icon);
            }
            
            // Clear and focus message input
            document.getElementById('message-list').innerHTML = `
                <div class="text-center py-5">
                    <p class="text-muted">Start a new conversation with ${selectedRecipient.name}</p>
                </div>
            `;
            document.getElementById('message-content').focus();
            
            // Mobile view - hide sidebar and show chat area
            if (window.innerWidth <= 768) {
                document.querySelector('.sidebar').style.display = 'none';
                document.getElementById('chat-area').classList.add('active');
            }
            
            selectedRecipient = null;
        });

        // Back to inbox button (mobile view)
        document.getElementById('back-to-inbox').addEventListener('click', () => {
            document.querySelector('.sidebar').style.display = 'block';
            document.getElementById('chat-area').classList.remove('active');
        });

        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            loadThreads();
            
            // Check if we should open a thread from URL
            const urlParams = new URLSearchParams(window.location.search);
            const threadId = urlParams.get('thread_id');
            if (threadId) {
                openThread(threadId);
            }
            
            // Auto-focus message input when chat is opened
            document.getElementById('message-content').addEventListener('focus', function() {
                // Scroll to bottom of message list
                const messageList = document.getElementById('message-list');
                messageList.scrollTop = messageList.scrollHeight;
            });
        });

        // Handle window resize
        window.addEventListener('resize', () => {
            if (window.innerWidth > 768) {
                document.querySelector('.sidebar').style.display = 'block';
                document.getElementById('chat-area').classList.remove('active');
            }
        });
    </script>

    <script>
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })
    </script>
</body>
</html>