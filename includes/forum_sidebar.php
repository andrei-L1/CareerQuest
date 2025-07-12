<?php
// Get the current page name
$current_page = basename($_SERVER['PHP_SELF']);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$dashboardLink = '../index.php'; 

if (isset($_SESSION['stud_id'])) {
    $dashboardLink = '../dashboard/student.php';
} elseif (isset($_SESSION['user_id'])) {
    $dashboardLink = '../dashboard/employer.php';
}
?>

<style>
    /* Sidebar Navigation */
    .sidebar-nav {
        width: 70px;
        background-color: white;
        border-right: 1px solid var(--light-gray, #e0e0e0);
        padding: 16px 8px;
        display: flex;
        flex-direction: column;
        align-items: center;
        position: fixed;
        top: 0;
        left: 0;
        height: 100vh;
        z-index: 1200;
        box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
    }

    .nav-btn {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: white;
        border: 1px solid var(--light-gray, #e0e0e0);
        color: var(--gray-color, #6c757d);
        margin-bottom: 12px;
        transition: all 0.2s;
        text-decoration: none; /* Ensure no underline */
        cursor: pointer; /* Explicit cursor for clickability */
    }

    .nav-btn:hover {
        background-color: var(--primary-light, #e6f0fa);
        color: var(--primary-color, #007bff);
        border-color: var(--primary-light, #e6f0fa);
        transform: translateY(-2px);
    }

    .nav-btn.active {
        background-color: var(--primary-light, #e6f0fa);
        color: var(--primary-color, #007bff);
        border-color: var(--primary-light, #e6f0fa);
    }

    .nav-btn i {
        font-size: 1.25rem;
    }

    /* Responsive Design for Mobile */
    @media (max-width: 767px) {
        .sidebar-nav {
            width: 100%;
            height: auto;
            flex-direction: row;
            justify-content: space-around;
            position: fixed;
            bottom: 0;
            top: auto;
            left: 0;
            border-right: none;
            border-top: 1px solid var(--light-gray, #e0e0e0);
            padding: 8px;
            background-color: white;
            z-index: 1200;
            box-shadow: 0 -2px 5px rgba(0, 0, 0, 0.1);
        }

        .nav-btn {
            width: 40px;
            height: 40px;
            margin-bottom: 0;
            margin-right: 8px;
        }

        .nav-btn:last-child {
            margin-right: 0;
        }

        .nav-btn:hover {
            transform: none;
        }

        .nav-btn i {
            font-size: 1.1rem;
        }

        /* Hide tooltips on mobile without disabling pointer events */
        .nav-btn[data-bs-toggle="tooltip"]::after,
        .nav-btn[data-bs-toggle="tooltip"] + .tooltip {
            display: none;
        }
    }
</style>

<div class="sidebar-nav">
    <a href="<?= $dashboardLink ?>" 
       class="nav-btn <?= ($current_page == basename($dashboardLink)) ? 'active' : ''; ?>" 
       data-bs-toggle="tooltip" 
       data-bs-placement="right" 
       title="Home">
        <i class="bi bi-house-door"></i>
    </a>
    <a href="../dashboard/forums.php" 
       class="nav-btn <?php echo ($current_page == 'forums.php') ? 'active' : ''; ?>" 
       data-bs-toggle="tooltip" 
       data-bs-placement="right" 
       title="Forum">
        <i class="bi bi-people"></i>
    </a>
    <a href="../dashboard/messages.php" 
       class="nav-btn <?php echo ($current_page == 'messages.php') ? 'active' : ''; ?>" 
       data-bs-toggle="tooltip" 
       data-bs-placement="right" 
       title="Messages">
        <i class="bi bi-chat-text"></i>
    </a>
</div>