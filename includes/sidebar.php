<?php
$sidebar_menu = [
    ["Dashboard", "fas fa-tachometer-alt", "#"],
    ["User Management", "fas fa-users", "user-management.html"],
    ["Job Postings", "fas fa-briefcase", "#"],
    ["Analytics", "fas fa-chart-line", "#"],
    ["Forum Moderation", "fas fa-comments", "#"],
    ["Settings", "fas fa-cog", "#"],
    ["Logout", "fas fa-sign-out-alt", "../auth/logout.php"] 
];
?>

    <nav class="sidebar">
        <div class="sidebar-sticky pt-3">
            <ul class="nav flex-column">
                <?php foreach ($sidebar_menu as $item): ?>
                    <li class="nav-item">
                        <?php if ($item[0] === "Logout"): ?>
                            <a href="#" class="nav-link text-danger" onclick="confirmLogout(event)">
                                <i class="<?= $item[1] ?> me-2"></i> <?= $item[0] ?>
                            </a>
                        <?php else: ?>
                            <a class="nav-link" href="<?= $item[2] ?>">
                                <i class="<?= $item[1] ?> me-2"></i> <?= $item[0] ?>
                            </a>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </nav>


    