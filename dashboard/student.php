<?php
require '../controllers/student_dashboard.php';
include '../includes/stud_navbar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .dashboard-container {
            padding: 20px;
        }
        .profile-img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
        }
        .card {
            border-radius: 10px;
        }
    </style>
</head>
<body>

<div class="container dashboard-container">
    <div class="row g-4">

        <!-- Profile Overview -->
        <div class="col-md-4">
            <div class="card shadow-sm p-3">
                <div class="d-flex align-items-center">
                    <img src="../uploads/<?= $profile_picture ?>" alt="Profile Picture" class="profile-img me-3">
                    <div>
                        <h5><?= $full_name; ?></h5>
                        <p class="text-muted"><?= $institution; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Job Recommendations -->
        <div class="col-md-8">
            <div class="card shadow-sm p-3">
                <h5>Recommended Jobs</h5>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">Software Developer at XYZ Corp</li>
                    <li class="list-group-item">Marketing Intern at ABC Ltd</li>
                    <li class="list-group-item">Data Analyst at Tech Solutions</li>
                </ul>
            </div>
        </div>

        <!-- Application Status -->
        <div class="col-md-6">
            <div class="card shadow-sm p-3">
                <h5>Application Status</h5>
                <p><span class="badge bg-warning">Pending</span> - Web Developer at ABC Ltd</p>
                <p><span class="badge bg-success">Accepted</span> - Data Analyst at Tech Solutions</p>
                <p><span class="badge bg-danger">Rejected</span> - UX Designer at XYZ Corp</p>
            </div>
        </div>

        <!-- Skills & Courses -->
        <div class="col-md-6">
            <div class="card shadow-sm p-3">
                <h5>Skills & Courses</h5>
                <p>✔ Python, JavaScript, SQL</p>
                <p>📚 Enrolled in: Web Development Bootcamp</p>
            </div>
        </div>

        <!-- Recent Forum Posts -->
        <div class="col-md-6">
            <div class="card shadow-sm p-3">
                <h5>Recent Forum Posts</h5>
                <p>💬 "How to prepare for tech interviews?"</p>
                <p>💬 "Best courses for AI development?"</p>
            </div>
        </div>

        <!-- Notifications -->
        <div class="col-md-6">
            <div class="card shadow-sm p-3">
                <h5>Notifications</h5>
                <p>📢 Your job application for XYZ Corp has been viewed.</p>
                <p>📢 New forum comment on your post.</p>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>