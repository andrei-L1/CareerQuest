<?php
require '../auth/auth_check_student.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if not logged in
if (!isset($_SESSION['stud_id'])) {
    header('Location: login.php');
    exit();
}

$stud_id = $_SESSION['stud_id'];

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require '../config/dbcon.php';
require '../controllers/student_profile_controller.php';
include '../includes/stud_navbar.php';

// Fetch all applications with extended details
$sql = "SELECT 
            a.application_id, 
            j.title as job_title, 
            j.job_id,
            j.description as job_description,
            j.location,
            j.posted_at,
            j.expires_at,
            j.moderation_status as job_status,
            jt.job_type_title,
            e.company_name,
            e.employer_id,
            a.application_status, 
            a.applied_at,
            (SELECT COUNT(*) FROM message m 
             JOIN thread th ON m.thread_id = th.thread_id
             JOIN thread_participants tp ON th.thread_id = tp.thread_id
             WHERE tp.actor_id = (SELECT actor_id FROM actor WHERE entity_type = 'student' AND entity_id = ?)
             AND m.is_read = 0) as unread_messages,
            sm.match_score
        FROM application_tracking a
        JOIN job_posting j ON a.job_id = j.job_id
        LEFT JOIN job_type jt ON j.job_type_id = jt.job_type_id
        JOIN employer e ON j.employer_id = e.employer_id
        LEFT JOIN (
            SELECT js.job_id, AVG(sm.match_score) AS match_score
            FROM skill_matching sm
            JOIN stud_skill ss ON sm.user_skills_id = ss.user_skills_id
            JOIN job_skill js ON sm.job_skills_id = js.job_skills_id
            WHERE ss.stud_id = ?
            GROUP BY js.job_id
        ) sm ON j.job_id = sm.job_id
        WHERE a.stud_id = ? AND a.deleted_at IS NULL
        ORDER BY a.applied_at DESC";

$stmt = $conn->prepare($sql);
$stmt->execute([$stud_id, $stud_id, $stud_id]);
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get application statistics
$stats = [
    'total' => count($applications),
    'pending' => 0,
    'accepted' => 0,
    'rejected' => 0,
    'match_avg' => 0
];

$total_match = 0;
foreach ($applications as $app) {
    $stats[strtolower($app['application_status'])]++;
    if ($app['match_score']) {
        $total_match += $app['match_score'];
    }
}
$stats['match_avg'] = $stats['total'] > 0 ? round($total_match / $stats['total'], 1) : 0;

// Function to format date as time ago
function getTimeAgo($timestamp, $referenceTime = null) {
    // Create a DateTime object in the Manila time zone
    $timezone = new DateTimeZone('Asia/Manila');
    $time = new DateTime($timestamp, $timezone);
    
    if ($referenceTime) {
        // Use provided reference time or default to 'now' in Manila time zone
        $now = new DateTime($referenceTime, $timezone);
    } else {
        $now = new DateTime('now', $timezone);
    }
    
    $diff = $now->diff($time);

    if ($diff->y > 0) {
        return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    }
    if ($diff->m > 0) {
        return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    }
    if ($diff->d > 0) {
        return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    }
    if ($diff->h > 0) {
        return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    }
    if ($diff->i > 0) {
        return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
    }
    return $diff->s . ' second' . ($diff->s > 1 ? 's' : '') . ' ago';
}

// Function to get application timeline
function getApplicationTimeline($conn, $application_id) {
    $sql = "SELECT 
                n.message AS event,
                n.created_at AS event_date,
                n.notification_type AS type
            FROM notification n
            WHERE n.reference_type = 'application' 
            AND n.reference_id = ?
            ORDER BY n.created_at DESC
            LIMIT 3";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$application_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Applications</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS (for toasts and other components) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --primary-color: #1A4D8F;
            --secondary-color: #3A7BD5;
            --primary-light: #e0e7ff;
            --accent-color: #4cc9f0;
            --success-color: #38b000;
            --warning-color: #ffaa00;
            --danger-color: #ef233c;
            --light-bg: #f8f9fa;
            --card-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --card-hover-shadow: 0 8px 16px rgba(67, 97, 238, 0.15);
            --border-radius: 12px;
        }
        
        body {
            background-color: #f8f9fa;
            
        /* font-family: 'Poppins', sans-serif; */
        }
        
        .dashboard-container {
            padding: 2rem;
            max-width: 1300px;
            margin: 0 auto;
        }
        
        /* Stats Cards */
        .stat-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--card-hover-shadow);
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        /* Application Card */
        .application-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
            border: none;
            overflow: hidden;
        }
        
        .application-card:hover {
            box-shadow: var(--card-hover-shadow);
            transform: translateY(-3px);
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            font-weight: 600;
            color: var(--primary-color);
            padding: 1.25rem 1.5rem;
            border-radius: var(--border-radius) var(--border-radius) 0 0 !important;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 1rem;
        }
        
        .card-header i {
            margin-right: 10px;
            font-size: 1.2rem;
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        /* Status Badges */
        .status-badge {
            font-size: 0.75rem;
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: 500;
            min-width: 80px;
            text-align: center;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-accepted {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .status-closed {
            background-color: #e2e3e5;
            color: #383d41;
        }
        
        /* Skill Match Meter */
        .match-meter {
            height: 8px;
            border-radius: 4px;
            background-color: #e9ecef;
            overflow: hidden;
            margin-top: 5px;
        }
        
        .match-progress {
            height: 100%;
            background: linear-gradient(90deg, var(--danger-color), var(--warning-color), var(--success-color));
        }
        
        /* Timeline */
        .timeline {
            position: relative;
            padding-left: 1.5rem;
            margin-top: 1rem;
        }
        
        .timeline:before {
            content: '';
            position: absolute;
            left: 7px;
            top: 0;
            bottom: 0;
            width: 2px;
            background-color: #e9ecef;
        }
        
        .timeline-item {
            position: relative;
            padding-bottom: 1rem;
        }
        
        .timeline-item:last-child {
            padding-bottom: 0;
        }
        
        .timeline-dot {
            position: absolute;
            left: -1.5rem;
            top: 0;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background-color: var(--primary-color);
        }
        
        .timeline-content {
            font-size: 0.85rem;
            color: #495057;
        }
        
        .timeline-date {
            font-size: 0.75rem;
            color: #6c757d;
        }
        
        /* Application Details */
        .application-detail {
            display: flex;
            margin-bottom: 1rem;
        }
        
        .detail-label {
            font-weight: 500;
            color: #495057;
            min-width: 120px;
        }
        
        .detail-value {
            color: #212529;
            flex: 1;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            max-width: 400px;
            margin: 0 auto;
        }
        
        .empty-state i {
            font-size: 3.0rem;
            margin-bottom: 1rem;
            color: #dee2e6;
        }

        #search-icon{
            font-size: 1.0rem;
            margin-bottom: 1rem;
            color: #dee2e6;
        }
        
        .empty-state-title {
            color: #343a40;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .empty-state-description {
            color: #6c757d;
            margin-bottom: 1.5rem;
        }
        
        .btn-primary {
            background-color: #1A4D8F;
            border-color: #1A4D8F;
            padding: 0.5rem 1.5rem;
        }
        
        .btn-primary:hover {
            background-color: #153d70;
            border-color: #153d70;
        }
        
        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .dashboard-container {
                padding: 1rem;
            }
            
            .card-header {
                padding: 1rem;
                flex-direction: column;
                align-items: flex-start;
            }
            
            .card-body {
                padding: 1rem;
            }
            
            .application-detail {
                flex-direction: column;
            }
            
            .detail-label {
                margin-bottom: 0.25rem;
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
        
        /* Filter Controls */
        .filter-controls {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            padding: 1.25rem 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .filter-controls .d-flex {
            gap: 0.5rem;
            align-items: center;
        }

        .filter-controls .fw-semibold {
            font-size: 0.95rem;
            color: #555;
            margin-right: 0.5rem;
        }

        .filter-btn {
            padding: 0.35rem 0.9rem;
            border-radius: 20px;
            font-size: 0.85rem;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-block;
        }

        /* Override Bootstrap btn-sm styles */
        .btn-sm.filter-btn {
            padding: 0.35rem 0.9rem;
            line-height: 1.3;
        }

        /* Color overrides for the status buttons */
        .filter-btn.btn-outline-primary {
            color: #4285f4;
            border-color: #4285f4;
        }
        .filter-btn.btn-outline-warning {
            color: #f4b400;
            border-color: #f4b400;
        }
        .filter-btn.btn-outline-success {
            color: #0f9d58;
            border-color: #0f9d58;
        }
        .filter-btn.btn-outline-danger {
            color: #db4437;
            border-color: #db4437;
        }

        /* Active state for all buttons */
        .filter-btn.active {
            color: white !important;
        }
        .filter-btn.btn-outline-primary.active {
            background: #4285f4;
        }
        .filter-btn.btn-outline-warning.active {
            background: #f4b400;
        }
        .filter-btn.btn-outline-success.active {
            background: #0f9d58;
        }
        .filter-btn.btn-outline-danger.active {
            background: #db4437;
        }

        /* Hover states */
        .filter-btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        
        /* Job Type Badge */
        .job-type-badge {
            background-color: var(--primary-light);
            color: var(--primary-color);
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        /* Modal fixes */
        .modal {
            z-index: 1060;
        }
        
        .modal-backdrop {
            z-index: 1050;
        }
        
        body.modal-open {
            overflow: hidden;
            padding-right: 0 !important;
        }
        
        .modal.show {
            display: block;
        }
        
        .modal-backdrop.show {
            opacity: 0.5;
        }
        .list-item{
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            flex-shrink: 0;
            background-color: var(--primary-light);
            color: var(--primary-color);
        }
        .list-item-primary {
            font-weight: 500;
            color: #2b2d42;
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .list-item i {
            margin: 0;
            padding: 0;
        }
    </style>
</head>
<body>

<div class="container dashboard-container">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-normal text-dark mb-0">My Applications</h2>
            <p class="text-muted">Track the status of your job applications</p>
        </div>
    </div>
    
    <!-- Application Statistics -->
    <div class="row mb-4 fade-in">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-value text-primary"><?= $stats['total'] ?></div>
                <div class="stat-label">Total Applications</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-value text-warning"><?= $stats['pending'] ?></div>
                <div class="stat-label">Pending</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-value text-success"><?= $stats['accepted'] ?></div>
                <div class="stat-label">Accepted</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-value" style="color: <?= $stats['match_avg'] > 70 ? 'var(--success-color)' : ($stats['match_avg'] > 40 ? 'var(--warning-color)' : 'var(--danger-color)') ?>">
                    <?= $stats['match_avg'] ?>%
                </div>
                <div class="stat-label">Avg. Skill Match</div>
            </div>
        </div>
    </div>
    
    <!-- Filter Controls -->
    <div class="filter-controls fade-in">
        <div class="d-flex flex-wrap align-items-center">
            <span class="fw-semibold">Filter by:</span>
            <a href="?status=all" class="btn btn-sm btn-outline-primary filter-btn <?= (!isset($_GET['status']) || $_GET['status'] == 'all') ? 'active' : '' ?>">All</a>
            <a href="?status=pending" class="btn btn-sm btn-outline-warning filter-btn <?= (isset($_GET['status']) && $_GET['status'] == 'pending') ? 'active' : '' ?>">Pending</a>
            <a href="?status=accepted" class="btn btn-sm btn-outline-success filter-btn <?= (isset($_GET['status']) && $_GET['status'] == 'accepted') ? 'active' : '' ?>">Accepted</a>
            <a href="?status=rejected" class="btn btn-sm btn-outline-danger filter-btn <?= (isset($_GET['status']) && $_GET['status'] == 'rejected') ? 'active' : '' ?>">Rejected</a>
        </div>
    </div>

    <!-- Applications List -->
    <?php if (!empty($applications)): ?>
        <?php foreach ($applications as $application): 
            // Skip if filtered and doesn't match
            if (isset($_GET['status']) && $_GET['status'] != 'all' && strtolower($application['application_status']) != $_GET['status']) {
                continue;
            }
            
            // Determine status class
            $statusClass = 'status-' . strtolower($application['application_status']);
            if ($application['job_status'] == 'Rejected') {
                $statusClass = 'status-closed';
                $application['application_status'] = 'Job Closed';
            }
            
            // Get timeline events
            $timeline = getApplicationTimeline($conn, $application['application_id']);
        ?>
            <div class="application-card fade-in">
                <div class="card-header">
                    <div class="d-flex align-items-center">
                        <div class="d-flex align-items-center">
                            <div class="list-item">
                                <i class="bi bi-briefcase-fill"></i>
                            </div>
                            <div class="list-item-primary">
                                <?= htmlspecialchars($application['job_title']) ?>
                                <?php if ($application['job_type_title']): ?>
                                    <span class="job-type-badge ms-2"><?= htmlspecialchars($application['job_type_title']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="status-badge <?= $statusClass ?> me-2">
                            <?= $application['application_status'] ?>
                        </span>
                        <!--
                        <?php if ($application['unread_messages'] > 0): ?>
                            <span class="badge bg-danger rounded-pill">
                                <i class="bi bi-envelope-fill"></i> <?= $application['unread_messages'] ?>
                            </span>
                        <?php endif; ?>
                        -->
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="application-detail">
                                <span class="detail-label">Company:</span>
                                <span class="detail-value"><?= htmlspecialchars($application['company_name']) ?></span>
                            </div>
                            <div class="application-detail">
                                <span class="detail-label">Location:</span>
                                <span class="detail-value"><?= htmlspecialchars($application['location']) ?></span>
                            </div>
                            <div class="application-detail">
                                <span class="detail-label">Applied:</span>
                                <span class="detail-value"><?= getTimeAgo($application['applied_at']) ?></span>
                            </div>
                            <div class="application-detail">
                                <span class="detail-label">Job Posted:</span>
                                <span class="detail-value"><?= date('M j, Y', strtotime($application['posted_at'])) ?></span>
                            </div>
                            <?php if ($application['match_score']): ?>
                                <div class="application-detail">
                                    <span class="detail-label">Skill Match:</span>
                                    <span class="detail-value">
                                        <?= $application['match_score'] ?>%
                                        <div class="match-meter">
                                            <div class="match-progress" style="width: <?= $application['match_score'] ?>%"></div>
                                        </div>
                                    </span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($timeline)): ?>
                                <div class="application-detail">
                                    <span class="detail-label">Recent Activity:</span>
                                    <span class="detail-value">
                                        <div class="timeline">
                                            <?php foreach ($timeline as $event): ?>
                                                <div class="timeline-item">
                                                    <div class="timeline-dot"></div>
                                                    <div class="timeline-content"><?= htmlspecialchars($event['event']) ?></div>
                                                    <div class="timeline-date"><?= getTimeAgo($event['event_date']) ?></div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex flex-column h-100 justify-content-between">
                                <div>
                                    <h6 class="fw-semibold mb-3">Job Summary</h6>
                                    <p class="text-muted small"><?= htmlspecialchars(substr($application['job_description'], 0, 200)) ?><?= strlen($application['job_description']) > 200 ? '...' : '' ?></p>
                                </div>
                                
                                <div class="d-flex justify-content-end mt-3 gap-2 flex-wrap">
                                    <a href="student_job.php?id=<?= $application['job_id'] ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye-fill"></i> View Job
                                    </a>
                                    <?php if ($application['unread_messages'] > 0): ?>
                                        <a href="messages.php?thread=employer_<?= $application['employer_id'] ?>" class="btn btn-sm btn-primary">
                                            <i class="bi bi-envelope-fill"></i> Messages
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($application['application_status'] == 'Pending' && $application['job_status'] != 'Rejected'): ?>
                                        <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#withdrawModal<?= $application['application_id'] ?>">
                                            <i class="bi bi-x-circle-fill"></i> Withdraw
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Withdraw Confirmation Modal - Placed at bottom of page -->
            <?php if ($application['application_status'] == 'Pending' && $application['job_status'] != 'Rejected'): ?>
                <div class="modal fade" id="withdrawModal<?= $application['application_id'] ?>" tabindex="-1" aria-labelledby="withdrawModalLabel<?= $application['application_id'] ?>" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="withdrawModalLabel<?= $application['application_id'] ?>">Confirm Withdrawal</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p>Are you sure you want to withdraw your application for <strong><?= htmlspecialchars($application['job_title']) ?></strong> at <?= htmlspecialchars($application['company_name']) ?>?</p>
                                <p class="text-muted">This action cannot be undone.</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <form action="../controllers/withdraw_application.php" method="post">
                                    <input type="hidden" name="application_id" value="<?= $application['application_id'] ?>">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <button type="submit" class="btn btn-danger">Withdraw Application</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="application-card fade-in">
            <div class="empty-state">
                <i class="bi bi-inbox"></i>
                <h4 class="empty-state-title">No applications found</h4>
                <p class="empty-state-description">You haven't applied to any jobs yet</p>
                <a href="student_job.php" class="btn btn-primary mt-3">
                    <i class="bi bi-search" id="search-icon"></i> Browse Jobs
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php include '../includes/stud_footer.php'; ?>

<!-- Bootstrap Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Enhanced modal handling
    document.addEventListener('DOMContentLoaded', function() {
        // Animation trigger
        const cards = document.querySelectorAll('.fade-in');
        cards.forEach((card, index) => {
            card.style.animationDelay = `${index * 0.1}s`;
        });
        
        // Initialize tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Modal event handlers
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('show.bs.modal', function() {
                document.body.style.overflow = 'hidden';
                document.body.style.paddingRight = '0';
            });
            
            modal.addEventListener('hidden.bs.modal', function() {
                document.body.style.overflow = 'auto';
                document.body.style.paddingRight = '0';
            });
        });
        
        // Fix for multiple backdrops
        document.addEventListener('hidden.bs.modal', function() {
            if (document.querySelectorAll('.modal.show').length === 0) {
                const backdrops = document.querySelectorAll('.modal-backdrop');
                backdrops.forEach(backdrop => backdrop.remove());
                document.body.classList.remove('modal-open');
                document.body.style.paddingRight = '';
            }
        });
    });
</script>
<script>
    window.onload = function() {
        const toastElement = document.querySelector('.toast');
        if (toastElement) {
            // Initialize the toast
            const toast = new bootstrap.Toast(toastElement, {
                delay: 5000 // 5 seconds
            });
            toast.show(); // Show the toast

            // Clean the URL after the toast disappears
            setTimeout(function() {
                history.replaceState(null, null, window.location.pathname); // Removes the query parameters
            }, 5000); // 5 seconds after the toast shows
        }
    };
</script>

</body>
</html>

<?php
if (isset($_SESSION['message'])) {
    echo '
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div class="toast align-items-center text-bg-success" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    ' . $_SESSION['message'] . '
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>
    ';
    unset($_SESSION['message']);  
}
?>
