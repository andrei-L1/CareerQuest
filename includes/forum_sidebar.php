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
        border-right: 1px solid var(--light-gray);
        padding: 16px 8px;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .nav-btn {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: white;
        border: 1px solid var(--light-gray);
        color: var(--gray-color);
        margin-bottom: 12px;
        transition: all 0.2s;
    }

    .nav-btn:hover {
        background-color: var(--primary-light);
        color: var(--primary-color);
        border-color: var(--primary-light);
        transform: translateY(-2px);
    }

    .nav-btn.active {
        background-color: var(--primary-light);
        color: var(--primary-color);
        border-color: var(--primary-light);
    }

    .nav-btn i {
        font-size: 1.25rem;
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
    <a href="../dashboard/forums.php" class="nav-btn <?php echo ($current_page == 'forums.php') ? 'active' : ''; ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Forum">
        <i class="bi bi-people"></i>
    </a>
    <a href="../dashboard/messages.php" class="nav-btn <?php echo ($current_page == 'messages.php') ? 'active' : ''; ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Messages">
        <i class="bi bi-chat-text"></i>
    </a>
</div>
