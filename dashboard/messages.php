<?php
require_once '../config/dbcon.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if both user_id and stud_id are not set simultaneously
if (isset($_SESSION['user_id']) && isset($_SESSION['stud_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Both user and student IDs are set. Only one should be set.']);
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

    $user_id = $currentUser['entity_id'];
    $query = "
        SELECT u.*, r.role_title 
        FROM user u
        LEFT JOIN role r ON u.role_id = r.role_id
        WHERE u.user_id = ?
    ";
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
    $currentUser = [
        'entity_type' => 'student',
        'entity_id' => $_SESSION['stud_id'],
        'name' => $_SESSION['stud_first_name'] ?? 'Student',
        'role' => 'Student',
        'email' => $_SESSION['stud_email'] ?? '',
        'picture' => $_SESSION['profile_picture'] ?? ''
    ];

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messaging - <?php echo htmlspecialchars($currentUser['name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script src="https://js.pusher.com/8.4.0/pusher.min.js"></script>

    <style>
        :root {
            --primary-color: #4361ee;
            --primary-light: #e0e7ff;
            --secondary-color: #3f37c9;
            --accent-color: #4895ef;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --gray-color: #6c757d;
            --light-gray: #e9ecef;
            --card-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            --border-radius: 12px;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fb;
            color: #333;
            height: 100vh;
            margin: 0;
        }

        .container-fluid {
            padding: 0;
            width: 100%;
            height: calc(100vh - 0px);
        }

        .chat-container {
            width: calc(100% - 70px);
            margin-left: 70px;
            height: 100%;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--card-shadow);
            background-color: white;
            display: flex;
        }

        .sidebar {
            width: 300px;
            background-color: white;
            border-right: 1px solid var(--light-gray);
            display: flex;
            flex-direction: column;
            transition: all 0.3s ease;
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

        .new-message-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .new-message-btn:hover {
            background-color: var(--secondary-color);
            transform: scale(1.05);
        }

        .threads-container {
            flex: 1;
            overflow-y: auto;
        }

        .thread {
            padding: 14px 16px;
            border-bottom: 1px solid var(--light-gray);
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .thread:hover {
            background-color: #f8f9fa;
        }

        .thread.active {
            background-color: var(--primary-light);
            border-left: 3px solid var(--primary-color);
        }

        .thread-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            overflow: hidden;
            background-color: var(--light-gray);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .thread-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .thread-content {
            flex: 1;
            min-width: 0;
        }

        .thread-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 4px;
        }

        .thread-name {
            font-weight: 600;
            font-size: 0.9rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .thread-time {
            font-size: 0.7rem;
            color: var(--gray-color);
            white-space: nowrap;
            margin-left: 8px;
        }

        .thread-preview {
            display: flex;
            justify-content: space-between;
        }

        .thread-message {
            font-size: 0.85rem;
            color: var(--gray-color);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            flex: 1;
        }

        .thread-unread {
            background-color: #f0f4ff;
        }

        .unread-badge {
            background-color: var(--accent-color);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: 600;
            margin-left: 8px;
        }

        .chat-area {
            flex: 1;
            display: flex;
            flex-direction: column;
            background-color: #f8fafc;
            position: relative;
            z-index: 10;
        }

        .chat-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--light-gray);
            background-color: white;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .back-to-inbox {
            display: none;
            background: none;
            border: none;
            color: var(--gray-color);
            font-size: 1.2rem;
        }

        .chat-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            overflow: hidden;
            background-color: var(--light-gray);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .chat-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .chat-info {
            flex: 1;
        }

        .chat-name {
            font-weight: 600;
            font-size: 1rem;
            margin-bottom: 2px;
        }

        .chat-status {
            font-size: 0.75rem;
            color: var(--gray-color);
        }

        .message-list {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background-color: #f8fafc;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .message {
            max-width: 70%;
            padding: 12px 16px;
            border-radius: 18px;
            position: relative;
            word-wrap: break-word;
            line-height: 1.4;
            font-size: 0.9rem;
            animation: fadeIn 0.3s ease-out;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .message-sent {
            background-color: var(--primary-color);
            color: white;
            margin-left: auto;
            border-bottom-right-radius: 4px;
        }

        .message-received {
            background-color: white;
            margin-right: auto;
            border-bottom-left-radius: 4px;
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

        .message-input-container textarea {
            resize: none;
        }

        .message-input-container {
            padding: 16px 20px;
            border-top: 1px solid var(--light-gray);
            background-color: white;
        }

        .message-input-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .message-input {
            flex: 1;
            border-radius: 24px;
            padding: 12px 18px;
            border: 1px solid var(--light-gray);
            resize: none;
            font-family: inherit;
            transition: all 0.2s;
        }

        .message-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }

        .send-button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 50%;
            width: 42px;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .send-button:hover {
            background-color: var(--secondary-color);
            transform: scale(1.05);
        }

        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            text-align: center;
            padding: 40px;
            color: var(--gray-color);
        }

        .empty-state-icon {
            font-size: 4rem;
            color: #d1d5db;
            margin-bottom: 20px;
        }

        .empty-state-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 12px;
        }

        .empty-state-text {
            font-size: 0.95rem;
            margin-bottom: 24px;
            max-width: 400px;
            line-height: 1.5;
        }

        .empty-state-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 24px;
            padding: 10px 24px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }

        .empty-state-btn:hover {
            background-color: var(--secondary-color);
            color: white;
            transform: translateY(-2px);
        }

        .empty-state-tip {
            font-size: 0.8rem;
            color: var(--gray-color);
            margin-top: 24px;
        }

        .modal-content {
            border-radius: var(--border-radius);
            border: none;
            box-shadow: var(--card-shadow);
        }

        .modal-header {
            border-bottom: 1px solid var(--light-gray);
            padding: 16px 20px;
        }

        .modal-title {
            font-weight: 600;
        }

        .modal-body {
            padding: 20px;
        }

        .search-input {
            border-radius: 8px;
            padding: 10px 16px;
            border: 1px solid var(--light-gray);
            width: 100%;
            margin-bottom: 16px;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }

        .search-results {
            max-height: 300px;
            overflow-y: auto;
        }

        .search-result-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .search-result-item:hover {
            background-color: #f8f9fa;
        }

        .search-result-item.active {
            background-color: var(--primary-light);
        }

        .search-result-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            overflow: hidden;
            background-color: var(--light-gray);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .search-result-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .search-result-info {
            flex: 1;
        }

        .search-result-name {
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 2px;
        }

        .search-result-role {
            font-size: 0.75rem;
            color: var(--gray-color);
        }

        .modal-footer {
            border-top: 1px solid var(--light-gray);
            padding: 16px 20px;
        }

        .video-call-btn {
            background-color: var(--accent-color);
            color: white;
            border: none;
            border-radius: 8px;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .video-call-btn:hover {
            background-color: var(--secondary-color);
            transform: scale(1.05);
        }

        .video-container {
            display: none;
            position: relative;
            background-color: #000;
            flex: 1;
            border-radius: var(--border-radius);
            overflow: hidden;
        }

        .video-container.active {
            display: flex;
        }

        #local-video, #remote-video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        #local-video {
            width: 25%;
            height: 25%;
            top: 10px;
            right: 10px;
            left: auto;
            border: 2px solid white;
            border-radius: 8px;
            z-index: 10;
        }

        .video-controls {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 10px;
            background: rgba(0, 0, 0, 0.7);
            padding: 10px;
            border-radius: 24px;
        }

        .video-control-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .video-control-btn:hover {
            background-color: var(--secondary-color);
            transform: scale(1.05);
        }

        .video-control-btn.end-call {
            background-color: #dc3545;
        }

        .video-control-btn.end-call:hover {
            background-color: #c82333;
        }

        .toast-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1050;
        }

        @media (max-width: 768px) {
            .container-fluid {
                padding-bottom: 60px;
            }

            .chat-container {
                width: 100%;
                margin-left: 0;
            }

            .sidebar {
                width: 100%;
                display: block;
            }

            .chat-area {
                display: none;
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: white;
                z-index: 1000;
            }

            .chat-area.active {
                display: flex;
            }

            .back-to-inbox {
                display: block;
            }
        }

        .loading-spinner {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100%;
            padding: 40px 0;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid var(--primary-light);
            border-top-color: var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .navbar {
            background-color: var(--primary-color);
            padding: 10px 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand img {
            border-radius: 50%;
            border: 2px solid #fff;
        }

        .navbar .navbar-brand {
            font-family: 'Arial', sans-serif;
            font-weight: bold;
            color: var(--primary-color) !important;
        }

        .navbar .navbar-brand:hover {
            color: #FFD700;
        }

        .navbar-light .navbar-nav .nav-link {
            color: #ffffff !important;
        }

        .navbar-light .navbar-nav .nav-link:hover {
            color: #FFD700 !important;
        }

        .navbar a {
            padding-left: 10%;
        }

        .navbar-nav .nav-item {
            margin-right: 15px;
        }

        @media (max-width: 767px) {
            .navbar-nav {
                justify-content: center;
            }
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="chat-container">
            <!-- Sidebar Navigation -->
            <?php require '../includes/forum_sidebar.php'; ?>
            
            <!-- Sidebar -->
            <div class="sidebar">
                <div class="sidebar-header">
                    <div class="user-info">
                        <div class="user-avatar">
                            <?php if (!empty($currentUser['picture'])): ?>
                                <img src="../Uploads/<?php echo htmlspecialchars($currentUser['picture']); ?>" alt="Profile Picture">
                            <?php else: ?>
                                <i class="bi bi-person-fill text-muted"></i>
                            <?php endif; ?>
                        </div>
                        <div class="user-details">
                            <div class="user-name"><?php echo htmlspecialchars($currentUser['name']); ?></div>
                            <div class="user-role"><?php echo htmlspecialchars($currentUser['role']); ?></div>
                        </div>
                    </div>
                    <button id="new-message-btn" class="new-message-btn" title="New Message">
                        <i class="bi bi-plus-lg"></i>
                    </button>
                </div>
                
                <div class="threads-container" id="threads-list">
                    <div class="loading-spinner">
                        <div class="spinner"></div>
                    </div>
                </div>
            </div>
            
            <!-- Chat Area -->
            <div class="chat-area" id="chat-area">
                <div class="chat-header">
                    <button class="back-to-inbox" id="back-to-inbox">
                        <i class="bi bi-arrow-left"></i>
                    </button>
                    <div class="chat-avatar" id="chat-avatar">
                        <i class="bi bi-person-fill text-muted"></i>
                    </div>
                    <div class="chat-info">
                        <div class="chat-name" id="chat-with-name">Select a conversation</div>
                        <div class="chat-status" id="chat-status"></div>
                    </div>
                    <button id="video-call-btn" class="video-call-btn d-none" title="Start Video Call">
                        <i class="bi bi-camera-video"></i>
                    </button>
                </div>
                
                <!-- Video Call Container -->
                <div class="video-container" id="video-container">
                    <video id="remote-video" autoplay playsinline></video>
                    <video id="local-video" autoplay muted playsinline></video>
                    <div class="video-controls">
                        <button class="video-control-btn" id="mute-mic-btn" title="Mute/Unmute Microphone">
                            <i class="bi bi-mic-fill"></i>
                        </button>
                        <button class="video-control-btn" id="mute-video-btn" title="Turn On/Off Camera">
                            <i class="bi bi-camera-video-fill"></i>
                        </button>
                        <button class="video-control-btn end-call" id="end-call-btn" title="End Call">
                            <i class="bi bi-telephone-x-fill"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Empty State -->
                <div class="empty-state" id="empty-chat-state">
                    <div class="empty-state-icon">
                        <i class="bi bi-chat-square-text"></i>
                    </div>
                    <h4 class="empty-state-title">Your messages live here</h4>
                    <p class="empty-state-text">Select a conversation or start a new one to begin messaging</p>
                    <button class="empty-state-btn" id="empty-new-message-btn">
                        <i class="bi bi-plus-lg"></i> New Message
                    </button>
                    <div class="empty-state-tip">Tip: You can search for people by name or email</div>
                </div>
                
                <!-- Message List -->
                <div class="message-list d-none" id="message-list"></div>
                
                <!-- Message Input -->
                <div class="message-input-container d-none" id="message-input-container">
                    <div class="message-input-group">
                        <textarea class="message-input" id="message-content" rows="1" placeholder="Type a message..."></textarea>
                        <button class="send-button" id="send-button" type="button">
                            <i class="bi bi-send-fill"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- New Message Modal -->
    <div class="modal fade" id="newMessageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">New Message</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="text" class="search-input" id="recipient-search" placeholder="Search for users...">
                    <div id="search-results" class="search-results">
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-search" style="font-size: 1.5rem;"></i>
                            <p>Search for users to message</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="start-conversation" disabled>Start Conversation</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Video Call Request Modal -->
    <div class="modal fade" id="videoCallModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Incoming Video Call</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="video-call-sender">Someone is calling you...</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" id="accept-call-btn">Accept</button>
                    <button type="button" class="btn btn-danger" id="reject-call-btn">Reject</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // When receiving ICE candidates from signaling
        async function handleRemoteIce(candidate) {
            console.log('Received ICE candidate:', JSON.stringify(candidate));
            if (!peerConnection) {
                console.log('Storing pending ICE candidate:', candidate);
                pendingIceCandidates.push(candidate);
                return;
            }
            try {
                await peerConnection.addIceCandidate(new RTCIceCandidate(candidate));
                console.log('Successfully added ICE candidate:', candidate);
            } catch (e) {
                console.error('Error adding remote ICE candidate:', e, 'Candidate:', candidate);
            }
        }

        // Send signaling message (e.g., ICE candidate, answer) to backend
        async function sendSignalingMessage(type, data) {
            if (!currentCallId || !currentThreadId) {
                console.log('Queuing signaling message: type=' + type + ', data=', data);
                signalingQueue.push({type, data});
                return;
            }
            try {
                const body = `action=send_signaling_message&call_id=${currentCallId}&thread_id=${currentThreadId}&type=${encodeURIComponent(type)}&data=${encodeURIComponent(JSON.stringify(data))}`;
                const response = await fetch('../controllers/messages_controller.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: body
                });
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                const result = await response.json();
                if (result.status === 'success') {
                    console.log(`Sent signaling message: type=${type}, data=`, data);
                } else {
                    throw new Error(result.message || 'Failed to send signaling message');
                }
            } catch (error) {
                console.error('Error sending signaling message:', error);
                showToast(`Failed to send signaling message: ${error.message}`);
            }
        }

        let currentActorId = null;
        let currentThreadId = null;
        let currentParticipant = null;
        let currentUser = <?php echo json_encode($currentUser); ?>;
        let newMessageModal = new bootstrap.Modal(document.getElementById('newMessageModal'));
        let videoCallModal = new bootstrap.Modal(document.getElementById('videoCallModal'));
        let localStream = null;
        let peerConnection = null;
        let currentCallId = null;
        let pendingIceCandidates = [];
        let signalingQueue = [];
        let servers = { iceServers: [] };
        let iceLoaded = false;

        async function loadIceServers() {
            if (iceLoaded) return;
            try {
                const res = await fetch('../getIce.php', { cache: 'no-store' });
                const data = await res.json();
                console.log('Raw ICE server response:', data);
                const list = data?.v?.iceServers || [];
                servers.iceServers = list
                    .map(s => ({
                        urls: Array.isArray(s.urls) ? s.urls : [s.urls],
                        username: s.username,
                        credential: s.credential
                    }))
                    .filter(s => s.urls.some(url => url.startsWith('turns:') || url.startsWith('stun:')));
                console.log('Processed ICE servers:', servers.iceServers);
                iceLoaded = true;
            } catch (e) {
                console.error('Failed to load ICE servers:', e);
                showToast('Failed to load ICE servers');
            }
        }

        let pusher = new Pusher('d9d029433bbefa08b6a2', {
            cluster: 'ap1',
            encrypted: true
        });
        let currentChannel = null;
        let subscribedChannels = {};

        pusher.connection.bind('connected', () => {
            console.log('Pusher connected successfully');
        });
        pusher.connection.bind('error', (err) => {
            console.error('Pusher error:', err);
            showToast('Pusher connection error');
        });

        function showToast(message, type = 'danger') {
            const toastContainer = document.createElement('div');
            toastContainer.className = 'toast-container';
            toastContainer.innerHTML = `
                <div class="toast" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="toast-header bg-${type} text-white">
                        <strong class="me-auto">${type === 'danger' ? 'Error' : 'Success'}</strong>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                    <div class="toast-body">${message}</div>
                </div>
            `;
            document.body.appendChild(toastContainer);
            const toast = new bootstrap.Toast(toastContainer.querySelector('.toast'), { autohide: true, delay: 5000 });
            toast.show();
            setTimeout(() => toastContainer.remove(), 5500);
        }

        async function fetchCurrentActorId() {
            try {
                const response = await fetch('../controllers/messages_controller.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=get_actor_id&entity_type=${encodeURIComponent(currentUser.entity_type)}&entity_id=${currentUser.entity_id}`
                });
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                const data = await response.json();
                if (data.status === 'success') {
                    currentActorId = data.actor_id;
                    console.log('Fetched actor ID:', currentActorId);
                } else {
                    throw new Error(data.message || 'Failed to fetch actor ID');
                }
            } catch (error) {
                console.error('Error fetching actor ID:', error);
                showToast('Unable to initialize messaging system');
            }
        }

        function subscribeToThread(threadId) {
            if (!subscribedChannels['thread_' + threadId]) {
                currentChannel = pusher.subscribe('thread_' + threadId);
                subscribedChannels['thread_' + threadId] = true;
                currentChannel.bind('pusher:subscription_succeeded', () => {
                    console.log(`Subscribed to thread_${threadId}`);
                });
                currentChannel.bind('pusher:subscription_error', (err) => {
                    console.error(`Subscription error for thread_${threadId}:`, err);
                    showToast('Failed to subscribe to thread');
                });
                currentChannel.bind('new_message', (data) => handleNewMessage(data, threadId));
                currentChannel.bind('thread_update', handleThreadUpdate);
                currentChannel.bind('video_call', handleVideoCall);
                currentChannel.bind_global((event, data) => {
                    console.log(`Thread channel event [${event}]:`, data);
                });
            }
        }

        function unsubscribeFromCurrentChannel() {
            if (currentChannel) {
                pusher.unsubscribe(currentChannel.name);
                delete subscribedChannels[currentChannel.name];
                console.log(`Unsubscribed from ${currentChannel.name}`);
                currentChannel = null;
            }
        }

        function handleNewMessage(data, threadId) {
            if (data.thread_id === threadId && data.thread_id === currentThreadId) {
                const messageList = document.getElementById('message-list');
                const isSender = data.sender_type === currentUser.entity_type && data.sender_id == currentUser.entity_id;
                const messageEl = document.createElement('div');
                messageEl.className = `message ${isSender ? 'message-sent' : 'message-received'}`;
                messageEl.innerHTML = `
                    <div>${data.content}</div>
                    <div class="message-time">${formatTime(data.sent_at)}</div>
                `;
                messageList.appendChild(messageEl);
                messageList.scrollTop = messageList.scrollHeight;
                if (!isSender) markMessagesAsRead(threadId);
            }
            loadThreads();
        }

        function handleThreadUpdate(data) {
            if (data.thread_id === currentThreadId || data.action === 'update_all') {
                loadThreads();
            }
        }

        function handleVideoCall(data) {
            if (data.caller_id == currentActorId) return;
            console.log('Received video call:', data);
            currentThreadId = data.thread_id;
            currentCallId = Number(data.call_id);
            if (!subscribedChannels['thread_' + currentThreadId]) {
                subscribeToThread(currentThreadId);
            }
            document.getElementById('video-call-sender').textContent = `${data.caller_name} is calling you...`;
            videoCallModal.show();
        }

        async function loadThreads() {
            try {
                const response = await fetch('../controllers/messages_controller.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=get_threads`
                });
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                const data = await response.json();
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
                        document.getElementById('empty-list-new-message-btn').addEventListener('click', () => newMessageModal.show());
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
                        if (!subscribedChannels['thread_' + thread.thread_id]) {
                            subscribeToThread(thread.thread_id);
                        }
                    });
                } else {
                    throw new Error(data.message || 'Unknown error');
                }
            } catch (error) {
                console.error('Error loading threads:', error);
                document.getElementById('threads-list').innerHTML = `
                    <div class="alert alert-danger">Error loading conversations: ${error.message}</div>
                `;
                showToast(`Error loading conversations: ${error.message}`);
            }
        }

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

        async function openThread(threadId) {
            unsubscribeFromCurrentChannel();
            currentThreadId = threadId;
            subscribeToThread(threadId);
            console.log('Opened thread:', threadId);

            if (window.innerWidth <= 768) {
                document.querySelector('.sidebar-nav').style.display = 'none';
                document.getElementById('chat-area').classList.add('active');
            }

            document.getElementById('empty-chat-state').classList.add('d-none');
            document.getElementById('message-list').classList.remove('d-none');
            document.getElementById('message-input-container').classList.remove('d-none');
            document.getElementById('video-call-btn').classList.remove('d-none');
            document.getElementById('video-container').classList.remove('active');

            try {
                const response = await fetch('../controllers/messages_controller.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=get_messages&thread_id=${threadId}`
                });
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                const data = await response.json();

                if (data.status === 'success') {
                    currentParticipant = data.participant;

                    const userResponse = await fetch('../controllers/messages_controller.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `action=get_user_info&entity_type=${encodeURIComponent(currentParticipant.entity_type)}&entity_id=${currentParticipant.entity_id}`
                    });
                    if (!userResponse.ok) throw new Error(`HTTP error! status: ${userResponse.status}`);
                    const userData = await userResponse.json();

                    if (userData.status === 'success') {
                        document.getElementById('chat-with-name').textContent = userData.user.name;
                        const chatAvatar = document.getElementById('chat-avatar');
                        chatAvatar.innerHTML = '';
                        if (userData.user.picture) {
                            const img = document.createElement('img');
                            img.src = `../Uploads/${userData.user.picture}`;
                            img.alt = userData.user.name;
                            chatAvatar.appendChild(img);
                        } else {
                            const icon = document.createElement('i');
                            icon.className = 'bi bi-person-fill text-muted';
                            chatAvatar.appendChild(icon);
                        }
                    }

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
                            const isSender = message.sender_type === currentUser.entity_type && message.sender_id == currentUser.entity_id;
                            const messageEl = document.createElement('div');
                            messageEl.className = `message ${isSender ? 'message-sent' : 'message-received'}`;
                            messageEl.innerHTML = `
                                <div>${message.content}</div>
                                <div class="message-time">${formatTime(message.sent_at)}</div>
                            `;
                            messageList.appendChild(messageEl);
                        });
                    }
                    messageList.scrollTop = messageList.scrollHeight;
                    if (data.unread_count > 0) markMessagesAsRead(threadId);
                } else {
                    throw new Error(data.message || 'Unknown error');
                }
            } catch (error) {
                console.error('Error loading messages:', error);
                document.getElementById('message-list').innerHTML = `
                    <div class="alert alert-danger">Error loading messages: ${error.message}</div>
                `;
                showToast(`Error loading messages: ${error.message}`);
            }
        }

        async function markMessagesAsRead(threadId) {
            try {
                const response = await fetch('../controllers/messages_controller.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=mark_as_read&thread_id=${threadId}`
                });
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                const data = await response.json();
                if (data.status === 'success') loadThreads();
            } catch (error) {
                console.error('Error marking messages as read:', error);
                showToast(`Error marking messages as read: ${error.message}`);
            }
        }

        async function sendMessage() {
            const content = document.getElementById('message-content').value.trim();
            if (!content) return;
            if (!currentThreadId && !currentParticipant) {
                showToast('Please select a conversation first');
                return;
            }

            try {
                const body = `action=send_message&receiver_type=${encodeURIComponent(currentParticipant.entity_type)}&receiver_id=${currentParticipant.entity_id}&content=${encodeURIComponent(content)}${currentThreadId ? `&thread_id=${currentThreadId}` : ''}`;
                const response = await fetch('../controllers/messages_controller.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: body
                });
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                const data = await response.json();
                if (data.status === 'success') {
                    document.getElementById('message-content').value = '';
                    if (!currentThreadId) {
                        currentThreadId = data.thread_id;
                        subscribeToThread(currentThreadId);
                    }
                    openThread(currentThreadId);
                    loadThreads();
                } else {
                    throw new Error(data.message || 'Unknown error');
                }
            } catch (error) {
                console.error('Error sending message:', error);
                showToast(`Failed to send message: ${error.message}`);
            }
        }

        async function checkMediaPermissions() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
                stream.getTracks().forEach(track => track.stop());
                return true;
            } catch (error) {
                console.error('Media permission error:', error);
                showToast('Please grant camera and microphone permissions');
                return false;
            }
        }

        async function startVideoCall() {
            if (!currentThreadId || !currentParticipant) {
                showToast('Please select a conversation first');
                return;
            }

            if (!(await checkMediaPermissions())) return;

            try {
                localStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
                document.getElementById('local-video').srcObject = localStream;
                document.getElementById('video-container').classList.add('active');
                document.getElementById('message-list').classList.add('d-none');
                document.getElementById('message-input-container').classList.add('d-none');

                await loadIceServers();
                peerConnection = new RTCPeerConnection({ iceServers: servers.iceServers });

                peerConnection.onicecandidate = (event) => {
                    if (!event.candidate || !event.candidate.candidate) {
                        console.log('Empty or null ICE candidate ignored:', event.candidate);
                        return;
                    }
                    console.log('Sending ICE candidate:', JSON.stringify(event.candidate));
                    sendSignalingMessage('ice-candidate', event.candidate);
                };

                peerConnection.ontrack = (event) => {
                    console.log('Received remote stream:', event.streams[0]);
                    document.getElementById('remote-video').srcObject = event.streams[0];
                };

                peerConnection.oniceconnectionstatechange = () => {
                    console.log('ICE Connection State:', peerConnection.iceConnectionState);
                    if (peerConnection.iceConnectionState === 'failed') {
                        showToast('Peer connection failed (ICE). Try again or check network settings.');
                        endCall();
                    } else if (peerConnection.iceConnectionState === 'connected') {
                        console.log('WebRTC connection established successfully');
                    }
                };

                localStream.getTracks().forEach(track => peerConnection.addTrack(track, localStream));

                const offer = await peerConnection.createOffer();
                console.log('Created offer:', JSON.stringify(offer));
                await peerConnection.setLocalDescription(offer);
                console.log('Set local description (offer):', offer);

                const body = `action=initiate_video_call&thread_id=${currentThreadId}&receiver_type=${encodeURIComponent(currentParticipant.entity_type)}&receiver_id=${currentParticipant.entity_id}&offer=${encodeURIComponent(JSON.stringify(offer))}`;
                const response = await fetch('../controllers/messages_controller.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: body
                });
                const data = await response.json();
                console.log('Video call initiation response:', data);
                if (data.status === 'success') {
                    currentCallId = Number(data.call_id);
                    console.log('Set currentCallId:', currentCallId);

                    console.log('Processing signaling queue');
                    while (signalingQueue.length > 0) {
                        const {type, data} = signalingQueue.shift();
                        sendSignalingMessage(type, data);
                    }

                    setTimeout(() => {
                        if (peerConnection && peerConnection.connectionState !== 'connected') {
                            showToast('Call timed out: No response from recipient');
                            endCall();
                        }
                    }, 60000); // Extended to 60 seconds
                } else {
                    throw new Error(data.message || 'Failed to initiate video call');
                }
            } catch (error) {
                console.error('Error starting video call:', error);
                showToast(`Failed to start video call: ${error.message}`);
                endCall();
            }
        }

        async function handleVideoCallResponse(data) {
            console.log('Received video call response:', data);
            if (Number(data.call_id) !== currentCallId) {
                console.log(`Ignoring response: call_id ${data.call_id} does not match currentCallId ${currentCallId}`);
                return;
            }
            if (data.status === 'accepted') {
                try {
                    const answer = {
                        type: 'answer',
                        sdp: data.answer.sdp
                    };
                    console.log('Setting remote answer:', answer);
                    await peerConnection.setRemoteDescription(new RTCSessionDescription(answer));
                    console.log('Set remote description (answer):', answer);
                    for (const cand of pendingIceCandidates) {
                        try {
                            await peerConnection.addIceCandidate(new RTCIceCandidate(cand));
                            console.log('Applied pending ICE candidate:', cand);
                        } catch (e) {
                            console.error('Error applying pending ICE candidate:', e);
                        }
                    }
                    pendingIceCandidates = [];
                } catch (error) {
                    console.error('Error setting remote description:', error);
                    showToast('Failed to establish video call');
                    endCall();
                }
            } else if (data.status === 'rejected' || data.status === 'ended') {
                showToast(`Video call was ${data.status}`);
                endCall();
            }
        }

        async function handleSignalingMessage(data) {
            console.log('Received signaling message:', data);
            if (Number(data.call_id) !== currentCallId) {
                console.log(`Ignoring message: call_id ${data.call_id} does not match currentCallId ${currentCallId}`);
                return;
            }
            try {
                if (data.type === 'answer') {
                    console.log('Setting remote answer:', data.data);
                    await peerConnection.setRemoteDescription(new RTCSessionDescription(data.data));
                    console.log('Set remote description (answer):', data.data);
                    for (const cand of pendingIceCandidates) {
                        try {
                            await peerConnection.addIceCandidate(new RTCIceCandidate(cand));
                            console.log('Applied pending ICE candidate:', cand);
                        } catch (e) {
                            console.error('Error applying pending ICE candidate:', e);
                        }
                    }
                    pendingIceCandidates = [];
                    // Retry pending candidates after a delay if necessary
                    setTimeout(async () => {
                        if (peerConnection && peerConnection.remoteDescription) {
                            for (const cand of pendingIceCandidates) {
                                try {
                                    await peerConnection.addIceCandidate(new RTCIceCandidate(cand));
                                    console.log('Retried pending ICE candidate:', cand);
                                } catch (e) {
                                    console.error('Error retrying ICE candidate:', e);
                                }
                            }
                            pendingIceCandidates = [];
                        }
                    }, 1000);
                } else if (data.type === 'ice-candidate') {
                    await handleRemoteIce(data.data);
                }
            } catch (error) {
                console.error('Error handling signaling message:', error);
            }
        }

        async function endCall() {
            if (peerConnection) {
                peerConnection.close();
                peerConnection = null;
                console.log('Closed peer connection');
            }
            if (localStream) {
                localStream.getTracks().forEach(track => track.stop());
                localStream = null;
                console.log('Stopped local stream');
            }
            document.getElementById('local-video').srcObject = null;
            document.getElementById('remote-video').srcObject = null;
            document.getElementById('video-container').classList.remove('active');
            document.getElementById('message-list').classList.remove('d-none');
            document.getElementById('message-input-container').classList.remove('d-none');

            if (currentCallId) {
                try {
                    const response = await fetch('../controllers/messages_controller.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `action=end_video_call&call_id=${currentCallId}&thread_id=${currentThreadId}`
                    });
                    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                    const data = await response.json();
                    if (data.status !== 'success') console.error('Error ending video call:', data.message);
                } catch (error) {
                    console.error('Error ending video call:', error);
                }
                currentCallId = null;
                console.log('Cleared currentCallId');
            }
        }

        document.getElementById('video-call-btn').addEventListener('click', startVideoCall);

        document.getElementById('mute-mic-btn').addEventListener('click', () => {
            if (localStream) {
                const audioTrack = localStream.getAudioTracks()[0];
                audioTrack.enabled = !audioTrack.enabled;
                document.getElementById('mute-mic-btn').innerHTML = `<i class="bi bi-mic${audioTrack.enabled ? '-fill' : '-mute-fill'}"></i>`;
            }
        });

        document.getElementById('mute-video-btn').addEventListener('click', () => {
            if (localStream) {
                const videoTrack = localStream.getVideoTracks()[0];
                videoTrack.enabled = !videoTrack.enabled;
                document.getElementById('mute-video-btn').innerHTML = `<i class="bi bi-camera-video${videoTrack.enabled ? '-fill' : '-off-fill'}"></i>`;
            }
        });

        document.getElementById('end-call-btn').addEventListener('click', endCall);

        document.getElementById('accept-call-btn').addEventListener('click', async () => {
            videoCallModal.hide();
            if (!(await checkMediaPermissions())) return;

            try {
                localStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
                document.getElementById('local-video').srcObject = localStream;
                document.getElementById('video-container').classList.add('active');
                document.getElementById('message-list').classList.add('d-none');
                document.getElementById('message-input-container').classList.add('d-none');

                await loadIceServers();
                peerConnection = new RTCPeerConnection({ iceServers: servers.iceServers });

                peerConnection.onicecandidate = (event) => {
                    if (!event.candidate || !event.candidate.candidate) {
                        console.log('Empty or null ICE candidate ignored:', event.candidate);
                        return;
                    }
                    console.log('Sending ICE candidate:', JSON.stringify(event.candidate));
                    sendSignalingMessage('ice-candidate', event.candidate);
                };

                peerConnection.ontrack = (event) => {
                    console.log('Received remote stream:', event.streams[0]);
                    document.getElementById('remote-video').srcObject = event.streams[0];
                };

                peerConnection.oniceconnectionstatechange = () => {
                    console.log('ICE Connection State:', peerConnection.iceConnectionState);
                    if (peerConnection.iceConnectionState === 'failed') {
                        showToast('Peer connection failed (ICE). Try again or check network settings.');
                        endCall();
                    } else if (peerConnection.iceConnectionState === 'connected') {
                        console.log('WebRTC connection established successfully');
                    }
                };

                localStream.getTracks().forEach(track => peerConnection.addTrack(track, localStream));

                const body = `action=accept_video_call&call_id=${currentCallId}&thread_id=${currentThreadId}`;
                const response = await fetch('../controllers/messages_controller.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: body
                });
                const data = await response.json();
                if (data.status !== 'success') throw new Error(data.message || 'Failed to accept video call');

                const offer = { type: 'offer', sdp: data.offer.sdp };
                console.log('Received offer:', offer);
                await peerConnection.setRemoteDescription(new RTCSessionDescription(offer));
                console.log('Set remote description (offer):', offer);

                const answer = await peerConnection.createAnswer();
                console.log('Created answer:', JSON.stringify(answer));
                await peerConnection.setLocalDescription(answer);
                console.log('Set local description (answer):', answer);

                const answerBody = `action=accept_video_call&call_id=${currentCallId}&thread_id=${currentThreadId}&answer=${encodeURIComponent(JSON.stringify(answer))}`;
                const answerResponse = await fetch('../controllers/messages_controller.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: answerBody
                });
                const answerData = await answerResponse.json();
                if (answerData.status !== 'success') throw new Error(answerData.message || 'Failed to send answer');

                for (const cand of pendingIceCandidates) {
                    try {
                        await peerConnection.addIceCandidate(new RTCIceCandidate(cand));
                        console.log('Applied pending ICE candidate:', cand);
                    } catch (e) {
                        console.error('Error applying ICE candidate:', e);
                    }
                }
                pendingIceCandidates = [];

                console.log('Processing signaling queue');
                while (signalingQueue.length > 0) {
                    const {type, data} = signalingQueue.shift();
                    sendSignalingMessage(type, data);
                }
            } catch (error) {
                console.error('Error accepting video call:', error);
                showToast(`Failed to accept video call: ${error.message}`);
                endCall();
            }
        });

        document.getElementById('reject-call-btn').addEventListener('click', () => {
            videoCallModal.hide();
            fetch('../controllers/messages_controller.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=reject_video_call&call_id=${currentCallId}&thread_id=${currentThreadId}`
            })
            .then(response => {
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                return response.json();
            })
            .then(data => {
                if (data.status !== 'success') console.error('Error rejecting video call:', data.message);
            })
            .catch(error => console.error('Error rejecting video call:', error));
            currentCallId = null;
        });

        document.getElementById('send-button').addEventListener('click', sendMessage);
        document.getElementById('message-content').addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
        document.getElementById('message-content').addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });

        document.getElementById('new-message-btn').addEventListener('click', () => newMessageModal.show());
        document.getElementById('empty-new-message-btn').addEventListener('click', () => newMessageModal.show());

        let selectedRecipient = null;
        document.getElementById('recipient-search').addEventListener('input', async (e) => {
            const searchTerm = e.target.value.trim();
            const resultsContainer = document.getElementById('search-results');
            if (searchTerm.length < 2) {
                resultsContainer.innerHTML = '<div class="list-group-item text-muted text-center">Type at least 2 characters to search</div>';
                return;
            }

            try {
                const response = await fetch('../controllers/messages_controller.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=search_users&term=${encodeURIComponent(searchTerm)}&current_user_type=${encodeURIComponent(currentUser.entity_type)}&current_user_id=${currentUser.entity_id}`
                });
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                const data = await response.json();
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
                                        `<img src="../Uploads/${user.picture}" alt="${user.name}">` : 
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
                            document.querySelectorAll('#search-results button').forEach(el => el.classList.remove('active'));
                            userEl.classList.add('active');
                            selectedRecipient = {
                                entity_type: user.entity_type,
                                entity_id: user.entity_id,
                                name: user.name,
                                picture: user.picture
                            };
                            document.getElementById('start-conversation').disabled = false;
                        });
                        resultsContainer.appendChild(userEl);
                    });
                } else {
                    resultsContainer.innerHTML = '<div class="list-group-item text-muted text-center">No users found</div>';
                }
            } catch (error) {
                console.error('Error searching users:', error);
                resultsContainer.innerHTML = '<div class="list-group-item text-muted text-center">Error searching users</div>';
                showToast('Error searching users');
            }
        });

        document.getElementById('start-conversation').addEventListener('click', () => {
            if (!selectedRecipient) return;
            newMessageModal.hide();
            document.getElementById('search-results').innerHTML = '';
            document.getElementById('recipient-search').value = '';
            document.getElementById('start-conversation').disabled = true;

            currentThreadId = null;
            currentParticipant = {
                entity_type: selectedRecipient.entity_type,
                entity_id: selectedRecipient.entity_id
            };

            document.getElementById('empty-chat-state').classList.add('d-none');
            document.getElementById('message-list').classList.remove('d-none');
            document.getElementById('message-input-container').classList.remove('d-none');
            document.getElementById('video-call-btn').classList.remove('d-none');
            document.getElementById('chat-with-name').textContent = selectedRecipient.name;

            const chatAvatar = document.getElementById('chat-avatar');
            chatAvatar.innerHTML = '';
            if (selectedRecipient.picture) {
                const img = document.createElement('img');
                img.src = `../Uploads/${selectedRecipient.picture}`;
                img.alt = selectedRecipient.name;
                chatAvatar.appendChild(img);
            } else {
                const icon = document.createElement('i');
                icon.className = 'bi bi-person-fill text-muted';
                chatAvatar.appendChild(icon);
            }

            document.getElementById('message-list').innerHTML = `
                <div class="text-center py-5">
                    <p class="text-muted">Start a new conversation with ${selectedRecipient.name}</p>
                </div>
            `;
            document.getElementById('message-content').focus();

            if (window.innerWidth <= 768) {
                document.querySelector('.sidebar').style.display = 'none';
                document.getElementById('chat-area').classList.add('active');
            }

            selectedRecipient = null;
        });

        document.getElementById('back-to-inbox').addEventListener('click', () => {
            document.querySelector('.sidebar-nav').style.display = 'flex';
            document.getElementById('chat-area').classList.remove('active');
            endCall();
        });

        document.addEventListener('DOMContentLoaded', async () => {
            await fetchCurrentActorId();
            await loadIceServers();
            loadThreads();

            const userChannel = pusher.subscribe('user_' + currentActorId);
            userChannel.bind('pusher:subscription_succeeded', () => {
                console.log(`Subscribed to user_${currentActorId}`);
            });
            userChannel.bind('update', (data) => {
                console.log(`User channel event [update]:`, data);
                if (data.type === 'message') {
                    loadThreads();
                } else if (data.type === 'video_call_response') {
                    handleVideoCallResponse(data);
                } else if (data.type === 'incoming_video_call') {
                    handleVideoCall({
                        call_id: data.call_id,
                        thread_id: data.thread_id,
                        caller_id: data.caller_id,
                        caller_name: data.caller_name
                    });
                } else if (data.type === 'signaling_message') {
                    handleSignalingMessage({
                        call_id: data.call_id,
                        type: data.signal_type || data.type,
                        data: data.data
                    });
                }
            });

            const urlParams = new URLSearchParams(window.location.search);
            const threadId = urlParams.get('thread_id');
            if (threadId) openThread(threadId);

            document.getElementById('message-content').addEventListener('focus', () => {
                const messageList = document.getElementById('message-list');
                messageList.scrollTop = messageList.scrollHeight;
            });
        });

        window.addEventListener('resize', () => {
            if (window.innerWidth > 768) {
                document.querySelector('.sidebar').style.display = 'block';
                document.getElementById('chat-area').classList.remove('active');
            }
        });

        window.addEventListener('beforeunload', () => {
            Object.keys(subscribedChannels).forEach(channel => pusher.unsubscribe(channel));
            endCall();
        });
    </script>
</body>
</html>