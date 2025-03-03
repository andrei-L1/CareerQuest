<?php
  session_start(); // Start session if needed for authentication check
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Career Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    
    <?php include 'includes/navbar.php'; ?>
    
    <!-- Hero Section -->
    <header class="bg-primary text-white text-center py-5">
        <div class="container">
            <h1>Connecting Talent with Opportunity</h1>
            <p class="lead">Find jobs, post openings, and grow your professional network.</p>
            <a href="#" class="btn btn-light btn-lg">Find Jobs & Internships</a>
            <a href="#" class="btn btn-outline-light btn-lg">Hire Top Talent</a>
        </div>
    </header>
    
    <!-- How It Works -->
    <section class="container py-5">
        <h2 class="text-center">How It Works</h2>
        <div class="row text-center mt-4">
            <div class="col-md-4">
                <h4>For Students</h4>
                <p>Create Profile → Match Skills → Apply & Get Hired</p>
            </div>
            <div class="col-md-4">
                <h4>For Employers</h4>
                <p>Post Jobs → Find Top Talent → Hire Easily</p>
            </div>
            <div class="col-md-4">
                <h4>For Professionals</h4>
                <p>Build Profile → Network & Mentor → Access Opportunities</p>
            </div>
        </div>
    </section>
    
    
    <!-- Community & Networking -->
    <section class="container py-5">
        <h2 class="text-center">Join Our Professional Community</h2>
        <p class="text-center">Engage in discussions, get mentorship, and grow your network.</p>
        <div class="text-center">
            <a href="#" class="btn btn-success btn-lg">Join Now</a>
        </div>
    </section>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>


    <!-- Login Modal -->
<div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="loginModalLabel">Select Login Type</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-center gap-3">
                    <!-- User Login -->
                    <a href="auth/login_user.php" class="text-decoration-none">
                        <div class="card p-4 text-center">
                            <svg class="user-icon" xmlns="http://www.w3.org/2000/svg" width="50" height="50" fill="#007bff" viewBox="0 0 24 24">
                                <path d="M12 2a5 5 0 11-5 5 5 5 0 015-5zm-7 17a7 7 0 0114 0v1H5v-1z"/>
                            </svg>
                            <h5 class="card-title mt-2">Login</h5>
                        </div>
                    </a>

                    <!-- Student Login -->
                    <a href="auth/login_student.php" class="text-decoration-none">
                        <div class="card p-4 text-center">
                            <svg class="student-icon" xmlns="http://www.w3.org/2000/svg" width="50" height="50" fill="#28a745" viewBox="0 0 24 24">
                                <path d="M12 2L1 7l11 5 9-4.09V13h2V7L12 2zm0 9.65L3.24 7 12 3.35 20.76 7 12 11.65zm-2 3.35v1.79c-.73.21-1.41.58-2 1.08v-2.87h2zm4 0h2v2.87a4.95 4.95 0 00-2-1.08V15zm-2 1.14c1.9 0 3.45 1.55 3.45 3.45H6.55c0-1.9 1.55-3.45 3.45-3.45z"/>
                            </svg>
                            <h5 class="card-title mt-2">Student Login</h5>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
