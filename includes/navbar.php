<?php
    $currentPage = basename($_SERVER['PHP_SELF']); // Get current file name
?>

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold text-primary" href="index.php">Career Platform</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage == 'jobs.php') ? 'active text-primary fw-bold' : ''; ?>" href="jobs.php">Find Jobs</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage == 'network.php') ? 'active text-primary fw-bold' : ''; ?>" href="network.php">Networking</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage == 'forums.php') ? 'active text-primary fw-bold' : ''; ?>" href="forums.php">Forums</a>
                </li>
                
                <!-- Dropdown for More -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="moreDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        More
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="moreDropdown">
                        <li><a class="dropdown-item" href="about.php">About Us</a></li>
                        <li><a class="dropdown-item" href="contact.php">Contact</a></li>
                        <li><a class="dropdown-item" href="privacy.php">Privacy Policy</a></li>
                    </ul>
                </li>

                <!-- Login & Signup Buttons -->
                <li class="nav-item">
                    <a class="btn btn-primary text-white px-3" href="#" data-bs-toggle="modal" data-bs-target="#loginModal">Login</a>
                </li>

                <li class="nav-item ms-2">
                    <a class="btn btn-primary text-white px-3" href="./views/signup.php">Sign Up</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
