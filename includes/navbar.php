<?php
    $currentPage = basename($_SERVER['PHP_SELF']); // Get current file name
?>

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
    <div class="container">
        <!-- Brand Logo -->
        <a class="navbar-brand fw-bold" href="index.php" style="color: var(--primary-color);">Career Quest</a>

        <!-- Toggle Button for Mobile -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navbar Links -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <!-- Find Jobs -->
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage == 'jobs.php') ? 'active fw-bold' : ''; ?>" href="jobs.php" style="color: var(--primary-color);">Find Jobs</a>
                </li>

                <!-- Networking -->
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage == 'network.php') ? 'active fw-bold' : ''; ?>" href="network.php" style="color: var(--primary-color);">Networking</a>
                </li>

                <!-- Forums -->
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage == 'forums.php') ? 'active fw-bold' : ''; ?>" href="forums.php" style="color: var(--primary-color);">Forums</a>
                </li>

                <!-- Dropdown for More -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="moreDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false" style="color: var(--primary-color);">
                        More
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="moreDropdown">
                        <li><a class="dropdown-item" href="about.php">About Us</a></li>
                        <li><a class="dropdown-item" href="contact.php">Contact</a></li>
                        <li><a class="dropdown-item" href="privacy.php">Privacy Policy</a></li>
                    </ul>
                </li>


                <!-- Login Button (Triggers Login Modal) -->
                <li class="nav-item">
                    <button class="btn btn-primary text-white px-3" 
                        style="background-color: var(--primary-color); border-color: var(--primary-color);" 
                        data-bs-toggle="modal" 
                        data-bs-target="#loginModal">
                        Login
                    </button>
                </li>

                <!-- Sign Up Button (Triggers Sign Up Modal) -->
                <li class="nav-item ms-2">
                    <button class="btn btn-outline-primary px-3" 
                        style="color: var(--primary-color); border-color: var(--primary-color);" 
                        data-bs-toggle="modal" 
                        data-bs-target="#signupModal">
                        Sign Up
                    </button>
                </li>

            </ul>
        </div>
    </div>
</nav>