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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
         
            --accent-color: #4cc9f0;
            --success-color: #38b000;
            --warning-color: #ffaa00;
            --danger-color: #ef233c;
            --light-bg: #f8f9fa;
            --card-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --card-hover-shadow: 0 8px 16px rgba(67, 97, 238, 0.15);
        }
        
        body {
            background-color: var(--light-bg);
          
        }
        
        .dashboard-container {
            font-family: 'Poppins', sans-serif;
            color: #333;
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        /* Profile Card */
        .profile-card {
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .profile-card:hover {
            box-shadow: var(--card-hover-shadow);
        }
        
        .profile-img {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid white;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }
        
        .profile-name {
            font-weight: 600;
            color: #222;
            margin-bottom: 0.25rem;
        }
        
        .profile-institution {
            color: #666;
            font-size: 0.9rem;
        }
        
        .profile-meta {
            display: flex;
            gap: 15px;
            margin-top: 15px;
            flex-wrap: wrap;
        }
        
        .profile-meta-item {
            display: flex;
            align-items: center;
            font-size: 0.85rem;
            color: #666;
        }
        
        .profile-meta-item i {
            margin-right: 5px;
            color: var(--primary-color);
        }
        
        /* Dashboard Cards */
        .dashboard-card {
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
            height: 100%;
            border: none;
        }
        
        .dashboard-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--card-hover-shadow);
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            font-weight: 600;
            color: var(--primary-color);
            padding: 1rem 1.25rem;
            border-radius: 12px 12px 0 0 !important;
            display: flex;
            align-items: center;
        }
        
        .card-header i {
            margin-right: 8px;
            font-size: 1.1rem;
        }
        
        .card-body {
            padding: 1.25rem;
        }
        
        /* List Items */
        .list-item {
            padding: 0.75rem 1rem;
            border-left: none;
            border-right: none;
            display: flex;
            align-items: center;
            transition: all 0.2s ease;
        }
        
        .list-item:hover {
            background-color: #f8f9ff;
        }
        
        .list-item-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            flex-shrink: 0;
        }
        
        .list-item-primary {
            font-weight: 500;
            color: #333;
            margin-bottom: 2px;
        }
        
        .list-item-secondary {
            font-size: 0.8rem;
            color: #666;
        }
        
        /* Badges */
        .status-badge {
            font-size: 0.75rem;
            padding: 4px 8px;
            border-radius: 20px;
            font-weight: 500;
        }
        
        .status-pending {
            background-color: #fef9c3;
            color: #ca8a04;
        }
        
        .status-accepted {
            background-color: #dcfce7;
            color: var(--success-color);
        }
        
        .status-rejected {
            background-color: #fee2e2;
            color: var(--danger-color);
        }
        
        /* Skills Tags */
        .skill-tag {
            display: inline-block;
            background-color: #e0e7ff;
            color: var(--primary-color);
            padding: 4px 10px;
            border-radius: 20px;
            margin: 0 5px 5px 0;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        /* Progress Bar */
        .progress-container {
            margin: 15px 0;
        }
        
        .progress-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 0.85rem;
        }
        
        .progress {
            height: 8px;
            border-radius: 4px;
        }
        
        .progress-bar {
            background-color: var(--primary-color);
        }
        
        /* Empty States */
        .empty-state {
            text-align: center;
            padding: 30px 15px;
            color: #999;
        }
        
        .empty-state i {
            font-size: 2rem;
            margin-bottom: 10px;
            color: #ddd;
        }
        
        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .profile-img {
                width: 70px;
                height: 70px;
            }
            
            .dashboard-container {
                padding: 15px;
            }
        }
        
        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .fade-in {
            animation: fadeIn 0.4s ease-out forwards;
        }
    </style>
</head>
<body>

<div class="container dashboard-container">
    <div class="row g-4">

        <!-- Profile Overview -->
        <div class="col-lg-4">
            <div class="profile-card p-3 fade-in">
                <div class="d-flex align-items-center">
                    <img src="../uploads/<?= $profile_picture ?>" alt="Profile Picture" class="profile-img me-3">
                    <div>
                        <h5 class="profile-name"><?= $full_name; ?></h5>
                        <p class="profile-institution"><?= $institution; ?></p>
                    </div>
                </div>
                
                <div class="profile-meta mt-3">
                    <span class="profile-meta-item">
                        <i class="bi bi-envelope"></i> <?= $email; ?>
                    </span>
                    <!--
                    <span class="profile-meta-item">
                        <i class="bi bi-geo-alt"></i> <?= $location ?? 'Not specified'; ?>
                    </span>
                     -->
                    <span class="profile-meta-item">
                        <i class="bi bi-mortarboard"></i> <?= $degree_program ?? 'Not specified'; ?>
                    </span>
                </div>
                
                <div class="progress-container mt-3">
                    <div class="progress-label">
                        <span>Profile Completion</span>
                        <span>75%</span>
                    </div>
                    
                    <div class="progress">
                        <div class="progress-bar" style="width: 75%"></div>
                    </div>
                    
                </div>
                
                <a href="student_account_settings.php" class="btn btn-outline-primary w-100 mt-3">
                    <i class="bi bi-pencil me-2"></i> Edit Profile
                </a>

            </div>
        </div>
        <!-- Job Recommendations -->
        <div class="col-lg-8">
            <div class="dashboard-card fade-in">
                <div class="card-header">
                    <i class="bi bi-stars"></i> Recommended Jobs
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <a href="#" class="list-group-item list-item">
                            <div class="list-item-icon bg-primary bg-opacity-10 text-primary">
                                <i class="bi bi-code-slash"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="list-item-primary">Software Developer at XYZ Corp</div>
                                <div class="list-item-secondary">Matches 85% of your skills • $65,000/yr • San Francisco, CA</div>
                            </div>
                            <i class="bi bi-chevron-right text-muted"></i>
                        </a>
                        <a href="#" class="list-group-item list-item">
                            <div class="list-item-icon bg-success bg-opacity-10 text-success">
                                <i class="bi bi-megaphone"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="list-item-primary">Marketing Intern at ABC Ltd</div>
                                <div class="list-item-secondary">Matches 72% of your skills • $20/hr • Remote</div>
                            </div>
                            <i class="bi bi-chevron-right text-muted"></i>
                        </a>
                        <a href="#" class="list-group-item list-item">
                            <div class="list-item-icon bg-info bg-opacity-10 text-info">
                                <i class="bi bi-bar-chart"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="list-item-primary">Data Analyst at Tech Solutions</div>
                                <div class="list-item-secondary">Matches 91% of your skills • $58,000/yr • New York, NY</div>
                            </div>
                            <i class="bi bi-chevron-right text-muted"></i>
                        </a>
                    </div>
                    <a href="job_listings.php" class="btn btn-link w-100 text-center py-2">
                        View all recommendations <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Application Status -->
        <div class="col-md-6">
            <div class="dashboard-card fade-in">
                <div class="card-header">
                    <i class="bi bi-clipboard-check"></i> Application Status
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <div class="list-group-item list-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="list-item-primary">Web Developer at ABC Ltd</div>
                                    <div class="list-item-secondary">Applied 3 days ago</div>
                                </div>
                                <span class="status-badge status-pending">Pending Review</span>
                            </div>
                        </div>
                        <div class="list-group-item list-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="list-item-primary">Data Analyst at Tech Solutions</div>
                                    <div class="list-item-secondary">Applied 1 week ago</div>
                                </div>
                                <span class="status-badge status-accepted">Interview Scheduled</span>
                            </div>
                        </div>
                        <div class="list-group-item list-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="list-item-primary">UX Designer at XYZ Corp</div>
                                    <div class="list-item-secondary">Applied 2 weeks ago</div>
                                </div>
                                <span class="status-badge status-rejected">Not Selected</span>
                            </div>
                        </div>
                    </div>
                    <a href="applications.php" class="btn btn-link w-100 text-center mt-2">
                        View all applications <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Skills & Courses -->
        <div class="col-md-6">
            <div class="dashboard-card fade-in">
                <div class="card-header">
                    <i class="bi bi-tools"></i> Skills & Courses
                </div>
                <div class="card-body">
                    <h6 class="mb-3">Your Skills</h6>
                    <div class="mb-4">
                        <span class="skill-tag">Python</span>
                        <span class="skill-tag">JavaScript</span>
                        <span class="skill-tag">SQL</span>
                        <span class="skill-tag">Data Analysis</span>
                        <span class="skill-tag">UI/UX Design</span>
                        <span class="skill-tag">Project Management</span>
                    </div>
                    
                    <h6 class="mb-3">Current Courses</h6>
                    <div class="list-group list-group-flush">
                        <div class="list-group-item list-item">
                            <div class="list-item-icon bg-warning bg-opacity-10 text-warning">
                                <i class="bi bi-laptop"></i>
                            </div>
                            <div>
                                <div class="list-item-primary">Web Development Bootcamp</div>
                                <div class="list-item-secondary">65% completed • Ends in 3 weeks</div>
                            </div>
                        </div>
                        <div class="list-group-item list-item">
                            <div class="list-item-icon bg-danger bg-opacity-10 text-danger">
                                <i class="bi bi-robot"></i>
                            </div>
                            <div>
                                <div class="list-item-primary">AI Fundamentals</div>
                                <div class="list-item-secondary">Enrolled • Starts next week</div>
                            </div>
                        </div>
                    </div>
                    <a href="skills.php" class="btn btn-link w-100 text-center mt-2">
                        Manage skills <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Recent Forum Posts -->
        <div class="col-md-6">
            <div class="dashboard-card fade-in">
                <div class="card-header">
                    <i class="bi bi-chat-square-text"></i> Recent Forum Activity
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <a href="#" class="list-group-item list-item">
                            <div class="list-item-icon bg-secondary bg-opacity-10 text-secondary">
                                <i class="bi bi-question-circle"></i>
                            </div>
                            <div>
                                <div class="list-item-primary">How to prepare for tech interviews?</div>
                                <div class="list-item-secondary">Posted 2 days ago • 15 comments</div>
                            </div>
                        </a>
                        <a href="#" class="list-group-item list-item">
                            <div class="list-item-icon bg-info bg-opacity-10 text-info">
                                <i class="bi bi-lightbulb"></i>
                            </div>
                            <div>
                                <div class="list-item-primary">Best courses for AI development?</div>
                                <div class="list-item-secondary">Posted 1 week ago • 8 comments</div>
                            </div>
                        </a>
                    </div>
                    <a href="forum.php" class="btn btn-link w-100 text-center mt-2">
                        Visit forum <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Notifications -->
        <div class="col-md-6">
            <div class="dashboard-card fade-in">
                <div class="card-header">
                    <i class="bi bi-bell"></i> Notifications
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <a href="#" class="list-group-item list-item">
                            <div class="list-item-icon bg-success bg-opacity-10 text-success">
                                <i class="bi bi-eye"></i>
                            </div>
                            <div>
                                <div class="list-item-primary">Your application for XYZ Corp has been viewed</div>
                                <div class="list-item-secondary">Today at 10:42 AM</div>
                            </div>
                        </a>
                        <a href="#" class="list-group-item list-item">
                            <div class="list-item-icon bg-primary bg-opacity-10 text-primary">
                                <i class="bi bi-chat-left"></i>
                            </div>
                            <div>
                                <div class="list-item-primary">New comment on your forum post</div>
                                <div class="list-item-secondary">Yesterday at 3:15 PM</div>
                            </div>
                        </a>
                        <a href="#" class="list-group-item list-item">
                            <div class="list-item-icon bg-warning bg-opacity-10 text-warning">
                                <i class="bi bi-calendar-event"></i>
                            </div>
                            <div>
                                <div class="list-item-primary">Upcoming interview reminder</div>
                                <div class="list-item-secondary">Tomorrow at 2:00 PM</div>
                            </div>
                        </a>
                    </div>
                    <a href="notifications.php" class="btn btn-link w-100 text-center mt-2">
                        View all notifications <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Simple animation trigger
    document.addEventListener('DOMContentLoaded', function() {
        const cards = document.querySelectorAll('.fade-in');
        cards.forEach((card, index) => {
            card.style.animationDelay = `${index * 0.1}s`;
        });
    });
</script>

</body>
</html>