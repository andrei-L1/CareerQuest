<?php
$current_page = basename($_SERVER['PHP_SELF']); // Get current file name

$sidebar_menu = [
    ["Dashboard", "fas fa-tachometer-alt", "admin.php"],
    ["User Management", "fas fa-users", "admin_user_management.php"],
    ["Job Postings", "fas fa-briefcase", "admin_job_management.php"],
    ["Analytics", "fas fa-chart-line", "analytics.php"],
    ["Forum Moderation", "fas fa-comments", "forum_moderation.php"],
    ["Settings", "fas fa-cog", "settings.php"],
    ["Logout", "fas fa-sign-out-alt", "../auth/logout.php"] 
];
?>


