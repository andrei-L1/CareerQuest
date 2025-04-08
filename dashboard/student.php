<?php
require '../controllers/student_dashboard.php';
require '../auth/auth_check_student.php';
include '../includes/stud_navbar.php';
// Only show once per session if not 100% complete and not already shown
if ($completion_percentage < 100 && !isset($_SESSION['profile_modal_shown'])) {
    $_SESSION['show_profile_modal'] = true;
    $_SESSION['profile_modal_shown'] = true; // Set a flag that it's been shown
} else {
    $_SESSION['show_profile_modal'] = false;
}

// Function to calculate time ago
function timeAgo($timestamp) {
    $time = strtotime($timestamp);
    $time_difference = time() - $time;

    if ($time_difference < 1) { return 'less than 1 second ago'; }
    $condition = [
        12 * 30 * 24 * 60 * 60 => 'year',
        30 * 24 * 60 * 60 => 'month',
        24 * 60 * 60 => 'day',
        60 * 60 => 'hour',
        60 => 'minute',
        1 => 'second'
    ];

    foreach ($condition as $secs => $str) {
        $d = $time_difference / $secs;
        if ($d >= 1) {
            $t = round($d);
            return $t . ' ' . $str . ($t > 1 ? 's' : '') . ' ago';
        }
    }
}

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
                        <span><?php echo $completion_percentage; ?>%</span>
                    </div>
                    
                    <div class="progress">
                        <div class="progress-bar <?php echo $progress_class; ?>" 
                            style="width: <?php echo $completion_percentage; ?>%"
                            role="progressbar" 
                            aria-valuenow="<?php echo $completion_percentage; ?>" 
                            aria-valuemin="0" 
                            aria-valuemax="100">
                        </div>
                    </div>
                </div>
                
                <a href="student_account_settings.php" class="btn btn-outline-primary w-100 mt-3">
                    <i class="bi bi-pencil me-2"></i> Edit Profile
                </a>

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
                        <?php foreach ($application_data as $application): ?>
                            <div class="list-group-item list-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="list-item-primary"><?php echo $application['job_title']; ?></div>
                                        <div class="list-item-secondary">Applied <?php echo timeAgo($application['applied_at']); ?></div>
                                    </div>
                                    <span class="status-badge <?php echo $application['status_class']; ?>"><?php echo $application['application_status']; ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
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
                        <?php foreach ($skills_list as $skill): ?>
                            <span class="skill-tag"><?php echo $skill; ?></span>
                        <?php endforeach; ?>
                    </div>
                    
                    <h6 class="mb-3">Current Courses</h6>
                    <div class="list-group list-group-flush">
                        <?php foreach ($courses_list as $course): ?>
                            <div class="list-group-item list-item">
                                <div>
                                    <div class="list-item-primary"><?php echo $course; ?></div>
                                    <div class="list-item-secondary">Details to be added dynamically</div>
                                </div>
                            </div>
                        <?php endforeach; ?>
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
                        <?php foreach ($recent_forum_activity as $post): ?>
                            <a href="#" class="list-group-item list-item">
                                <div class="list-item-icon bg-secondary bg-opacity-10 text-secondary">
                                    <i class="bi bi-<?php 
                                        echo $post['type'] === 'question' ? 'question-circle' : 
                                            ($post['type'] === 'idea' ? 'lightbulb' : 'chat-square-text'); 
                                    ?>"></i>
                                </div>
                                <div>
                                    <div class="list-item-primary"><?php echo htmlspecialchars($post['title']); ?></div>
                                    <div class="list-item-secondary">
                                        Posted <?php echo $post['time_ago']; ?> • 
                                        <?php echo $post['comment_count']; ?> comment<?php echo $post['comment_count'] != 1 ? 's' : ''; ?>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                        <?php if (empty($recent_forum_activity)): ?>
                            <div class="list-group-item">
                                <div class="text-muted">No recent forum activity</div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <a href="forum.php" class="btn btn-link w-100 text-center mt-2">
                        Visit forum <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>



    </div>
</div>
<!-- Profile Completion Modal -->
<div class="modal fade" id="profileCompletionModal" tabindex="-1" aria-labelledby="profileModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 rounded-4 p-4 shadow-lg bg-light">
      
      <!-- Header -->
      <div class="modal-header border-0 pb-0">
        <div class="d-flex align-items-center w-100">
          <div class="me-3">
            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
              <i class="bi bi-person-fill-check fs-4"></i>
            </div>
          </div>
          <div>
            <h5 class="modal-title fw-bold text-dark" id="profileModalLabel">Almost There!</h5>
            <p class="mb-0 text-muted small">Let’s complete your profile for a better experience.</p>
          </div>
        </div>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <!-- Body -->
      <div class="modal-body text-center pt-0">
        <p class="mt-3 mb-4 fs-6">
          You're <strong class="text-warning"><?php echo $completion_percentage; ?>%</strong> done. Unlock full benefits by completing your profile.
        </p>

        <!-- Progress -->
        <div class="progress rounded-pill mb-4" style="height: 16px;">
          <div class="progress-bar bg-primary fw-semibold" 
               role="progressbar" 
               style="width: <?php echo $completion_percentage; ?>%;" 
               aria-valuenow="<?php echo $completion_percentage; ?>" aria-valuemin="0" aria-valuemax="100">
          </div>
        </div>

        <!-- CTA -->
        <a href="student_account_settings.php" class="btn btn-outline-primary rounded-pill px-4 py-2">
          Complete Profile
        </a>
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

    <?php if (!empty($_SESSION['show_profile_modal']) && $_SESSION['show_profile_modal']): ?>
    <script>
    document.addEventListener("DOMContentLoaded", function () {
        var profileModal = new bootstrap.Modal(document.getElementById('profileCompletionModal'));
        profileModal.show();
    });
    </script>
    <?php endif; ?>

</body>
</html>