<?php
    $currentPage = basename($_SERVER['PHP_SELF']); // Get current file name
?>

<nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top shadow-sm">
    <div class="container">
        <!-- Brand Logo with Gradient -->
        <a class="navbar-brand fw-bold" href="/index.php" style="background: linear-gradient(135deg, var(--primary-color), #2A5298); -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-size: 1.6rem; letter-spacing: -0.5px;">
            Career<span class="fw-normal">Quest</span>
        </a>

        <!-- Toggle Button for Mobile -->
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navbar Links -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-lg-center">
                <!-- Find Jobs -->
                <li class="nav-item position-relative mx-lg-1">
                    <a class="nav-link px-3 py-2 <?php echo ($currentPage == 'jobs.php') ? 'active' : ''; ?>" href="/jobs.php">
                        <i class="fas fa-briefcase me-1 d-lg-none"></i> Find Jobs
                        <span class="nav-link-underline"></span>
                    </a>
                </li>

                <!-- Networking -->
                <li class="nav-item position-relative mx-lg-1">
                    <a class="nav-link px-3 py-2 <?php echo ($currentPage == 'network.php') ? 'active' : ''; ?>" href="/network.php">
                        <i class="fas fa-users me-1 d-lg-none"></i> Networking
                        <span class="nav-link-underline"></span>
                    </a>
                </li>

                <!-- Forums -->
                <li class="nav-item position-relative mx-lg-1">
                    <a class="nav-link px-3 py-2 <?php echo ($currentPage == 'forums.php') ? 'active' : ''; ?>" href="/dashboard/forums.php">
                        <i class="fas fa-comments me-1 d-lg-none"></i> Forums
                        <span class="nav-link-underline"></span>
                    </a>
                </li>

                <!-- Resources Dropdown -->
                <li class="nav-item dropdown mx-lg-1">
                    <a class="nav-link dropdown-toggle px-3 py-2" href="#" id="resourcesDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-book-open me-1 d-lg-none"></i> Resources
                        <span class="nav-link-underline"></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg py-2" aria-labelledby="resourcesDropdown">
                        <li><h6 class="dropdown-header text-uppercase small fw-bold text-muted px-3">Career Tools</h6></li>
                        <li><a class="dropdown-item d-flex align-items-center py-2 px-3" href="/resume-builder.php"><i class="fas fa-file-alt me-2 text-primary"></i> Resume Builder</a></li>
                        <li><a class="dropdown-item d-flex align-items-center py-2 px-3" href="/interview-prep.php"><i class="fas fa-comment-dots me-2 text-primary"></i> Interview Prep</a></li>
                        <li><hr class="dropdown-divider mx-3 my-1"></li>
                        <li><h6 class="dropdown-header text-uppercase small fw-bold text-muted px-3">Learning</h6></li>
                        <li><a class="dropdown-item d-flex align-items-center py-2 px-3" href="/courses.php"><i class="fas fa-graduation-cap me-2 text-primary"></i> Courses</a></li>
                        <li><a class="dropdown-item d-flex align-items-center py-2 px-3" href="/webinars.php"><i class="fas fa-video me-2 text-primary"></i> Webinars</a></li>
                    </ul>
                </li>

                <!-- More Dropdown -->
                <li class="nav-item dropdown mx-lg-1">
                    <a class="nav-link dropdown-toggle px-3 py-2" href="#" id="moreDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-ellipsis-h me-1 d-lg-none"></i> More
                        <span class="nav-link-underline"></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg py-2" aria-labelledby="moreDropdown">
                        <li><a class="dropdown-item d-flex align-items-center py-2 px-3" href="/about.php"><i class="fas fa-info-circle me-2 text-primary"></i> About Us</a></li>
                        <li><a class="dropdown-item d-flex align-items-center py-2 px-3" href="/contact.php"><i class="fas fa-envelope me-2 text-primary"></i> Contact</a></li>
                        <li><a class="dropdown-item d-flex align-items-center py-2 px-3" href="/blog.php"><i class="fas fa-newspaper me-2 text-primary"></i> Blog</a></li>
                        <li><hr class="dropdown-divider mx-3 my-1"></li>
                        <li><a class="dropdown-item d-flex align-items-center py-2 px-3" href="/privacy.php"><i class="fas fa-shield-alt me-2 text-primary"></i> Privacy Policy</a></li>
                    </ul>
                </li>

                <!-- Auth Buttons -->
                <li class="nav-item ms-lg-3 mt-3 mt-lg-0">
                    <div class="d-flex flex-column flex-lg-row gap-2">
                        <!-- Login Button -->
                        <button class="btn btn-outline-primary px-4 py-2 rounded-pill fw-semibold" 
                            data-bs-toggle="modal" 
                            data-bs-target="#loginModal">
                            <i class="fas fa-sign-in-alt me-2"></i> Login
                        </button>
                        
                        <!-- Sign Up Button -->
                        <button class="btn btn-primary px-4 py-2 rounded-pill fw-semibold" 
                            data-bs-toggle="modal" 
                            data-bs-target="#signupModal">
                            <i class="fas fa-user-plus me-2"></i> Sign Up
                        </button>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</nav>

<style>
    :root {
        --primary-color: #0A2647;
        --primary-light: rgba(10, 38, 71, 0.1);
        --secondary-color: #2C7865;
        --accent-color: #FFD700;
        --text-dark: #212529;
        --text-light: #6C757D;
        --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        --shadow-sm: 0 2px 8px rgba(0,0,0,0.08);
        --shadow-md: 0 4px 20px rgba(0,0,0,0.12);
    }

    /* Navbar Styling */
    .navbar {
        padding: 0.75rem 0;
        background-color: white !important;
        box-shadow: var(--shadow-sm);
        transition: var(--transition);
    }

    .navbar.scrolled {
        box-shadow: var(--shadow-md);
        padding: 0.5rem 0;
    }

    .navbar-brand {
        font-weight: 800;
        background-clip: text;
        -webkit-background-clip: text;
        background-size: 200% auto;
        transition: var(--transition);
    }

    .navbar-brand:hover {
        background-position: right center;
    }

    /* Nav Links */
    .nav-link {
        position: relative;
        font-weight: 500;
        color: var(--text-dark) !important;
        transition: var(--transition);
        margin: 0 0.25rem;
    }

    .nav-link-underline {
        position: absolute;
        bottom: 0;
        left: 50%;
        width: 0;
        height: 2px;
        background: var(--primary-color);
        transform: translateX(-50%);
        transition: var(--transition);
    }

    .nav-link:hover .nav-link-underline,
    .nav-link.active .nav-link-underline {
        width: calc(100% - 2rem);
    }

    .nav-link:hover,
    .nav-link.active {
        color: var(--primary-color) !important;
    }

    /* Dropdown Menu */
    .dropdown-menu {
        border-radius: 12px !important;
        border: none;
        box-shadow: var(--shadow-md);
        margin-top: 0.5rem !important;
    }

    .dropdown-item {
        border-radius: 8px !important;
        margin: 0.15rem 0.5rem;
        width: auto;
        transition: var(--transition);
    }

    .dropdown-item:hover {
        background-color: var(--primary-light);
        color: var(--primary-color) !important;
    }

    .dropdown-divider {
        opacity: 0.2;
    }

    /* Buttons */
    .btn {
        transition: var(--transition);
        font-weight: 600;
        letter-spacing: 0.5px;
    }

    .btn-primary {
        background: linear-gradient(135deg, var(--primary-color), #1C4B82);
        background-size: 200% auto;
        border: none;
        box-shadow: 0 4px 15px rgba(10, 38, 71, 0.2);
    }

    .btn-primary:hover {
        background-position: right center;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(10, 38, 71, 0.3);
    }

    .btn-outline-primary {
        border: 2px solid var(--primary-color);
        color: var(--primary-color);
        background: transparent;
    }

    .btn-outline-primary:hover {
        background: var(--primary-color);
        color: white !important;
        border-color: var(--primary-color);
    }

    /* Mobile Menu Adjustments */
    @media (max-width: 991.98px) {
        .navbar-collapse {
            padding: 1rem 0;
            background: white;
            border-radius: 0 0 12px 12px;
            box-shadow: var(--shadow-md);
        }

        .nav-item {
            margin: 0.25rem 0;
        }

        .nav-link {
            padding: 0.75rem 1.25rem !important;
            margin: 0;
        }

        .nav-link-underline {
            display: none;
        }

        .dropdown-menu {
            box-shadow: none;
            border: none;
            margin: 0 !important;
            padding: 0 0 0 1.5rem;
            background: rgba(10, 38, 71, 0.03);
        }

        .dropdown-item {
            padding: 0.75rem 1.25rem;
        }

        .btn {
            width: 100%;
            margin: 0.25rem 0;
        }
    }

    /* Navbar Toggler */
    .navbar-toggler {
        padding: 0.5rem;
        border: none;
        box-shadow: none !important;
    }

    .navbar-toggler:focus {
        box-shadow: none !important;
    }

    .navbar-toggler-icon {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%2810, 38, 71, 0.8%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
    }
</style>

<script>
    // Add scroll effect to navbar
    window.addEventListener('scroll', function() {
        const navbar = document.querySelector('.navbar');
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });

    // Initialize tooltips
    document.addEventListener('DOMContentLoaded', function() {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Highlight active dropdown parent
        const currentPage = '<?php echo $currentPage; ?>';
        const dropdownItems = document.querySelectorAll('.dropdown-item');
        
        dropdownItems.forEach(item => {
            if (item.getAttribute('href').includes(currentPage)) {
                item.classList.add('active');
                const dropdown = item.closest('.dropdown');
                if (dropdown) {
                    const toggle = dropdown.querySelector('.dropdown-toggle');
                    toggle.classList.add('active');
                }
            }
        });
    });
</script>