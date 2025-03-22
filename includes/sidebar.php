<?php
$current_page = basename($_SERVER['PHP_SELF']); // Get current file name

$sidebar_menu = [
    ["Dashboard", "fas fa-tachometer-alt", "../dashboard/admin.php"],
    ["User Management", "fas fa-users", "../dashboard/admin_user_management.php"],
    ["Data Management", "fas fa-database", "../dashboard/admin_data_management.php"],
    ["Job Postings", "fas fa-briefcase", "../dashboard/admin_job_management.php"],
    ["Analytics", "fas fa-chart-line", "../dashboard/analytics.php"],
    ["Forum Moderation", "fas fa-comments", "../dashboard/forum_moderation.php"],
    ["Settings", "fas fa-cog", "../dashboard/settings.php"],
    ["Logout", "fas fa-sign-out-alt", "../auth/logout.php"] 
];
?>


