<?php
  session_start();
  if (isset($_SESSION['user_id']) && isset($_SESSION['stud_id'])) {
    header("Location: views/dashboard.php");
    exit();
}
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


</body>
</html>
