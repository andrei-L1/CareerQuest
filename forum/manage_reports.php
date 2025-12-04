<?php
require_once '../config/dbcon.php';
/** @var PDO $conn */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and has moderator privileges
$actor_id = null;
$isSystemModerator = false;
$forumModeratorForums = [];

if (isset($_SESSION['user_id'])) {
    $query = "SELECT a.actor_id, r.role_title 
              FROM actor a 
              JOIN user u ON a.entity_type = 'user' AND a.entity_id = u.user_id 
              LEFT JOIN role r ON u.role_id = r.role_id 
              WHERE u.user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        $actor_id = $result['actor_id'];
        if (in_array($result['role_title'], ['Admin', 'Moderator'])) {
            $isSystemModerator = true;
        }
    }
    // Check for forum moderator roles
    $query = "SELECT forum_id FROM forum_membership WHERE actor_id = ? AND role IN ('Moderator', 'Admin') AND status = 'Active'";
    $stmt = $conn->prepare($query);
    $stmt->execute([$actor_id]);
    $forumModeratorForums = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'forum_id');
} elseif (isset($_SESSION['stud_id'])) {
    $query = "SELECT actor_id FROM actor WHERE entity_type = 'student' AND entity_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$_SESSION['stud_id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        $actor_id = $result['actor_id'];
        // Check for forum moderator roles
        $query = "SELECT forum_id FROM forum_membership WHERE actor_id = ? AND role IN ('Moderator', 'Admin') AND status = 'Active'";
        $stmt = $conn->prepare($query);
        $stmt->execute([$actor_id]);
        $forumModeratorForums = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'forum_id');
    }
}

if (!$actor_id || (!$isSystemModerator && empty($forumModeratorForums))) {
    header("Location: ../index.php");
    exit;
}

// Fetch pending reports with content for editing
if ($isSystemModerator) {
    $query = "SELECT r.*, 
                     CASE 
                         WHEN r.content_type = 'post' THEN fp.post_title
                         WHEN r.content_type = 'comment' THEN LEFT(fc.content, 50)
                         WHEN r.content_type = 'user' THEN CONCAT(COALESCE(u.user_first_name, s.stud_first_name), ' ', COALESCE(u.user_last_name, s.stud_last_name))
                     END AS content_title,
                     CASE 
                         WHEN r.content_type = 'post' THEN fp.content
                         WHEN r.content_type = 'comment' THEN fc.content
                         WHEN r.content_type = 'user' THEN NULL
                     END AS content_body,
                     CONCAT(COALESCE(rb_user.user_first_name, rb_student.stud_first_name), ' ', COALESCE(rb_user.user_last_name, rb_student.stud_last_name)) AS reporter_name,
                     f.title AS forum_title
              FROM report r
              LEFT JOIN forum_post fp ON r.content_type = 'post' AND r.content_id = fp.post_id
              LEFT JOIN forum_comment fc ON r.content_type = 'comment' AND r.content_id = fc.comment_id
              LEFT JOIN forum_post fp2 ON fc.post_id = fp2.post_id
              LEFT JOIN forum f ON fp.forum_id = f.forum_id OR fp2.forum_id = f.forum_id
              LEFT JOIN actor a ON r.reported_by = a.actor_id
              LEFT JOIN user rb_user ON a.entity_type = 'user' AND a.entity_id = rb_user.user_id
              LEFT JOIN student rb_student ON a.entity_type = 'student' AND a.entity_id = rb_student.stud_id
              LEFT JOIN actor ca ON r.content_type = 'user' AND r.content_id = ca.actor_id
              LEFT JOIN user u ON ca.entity_type = 'user' AND ca.entity_id = u.user_id
              LEFT JOIN student s ON ca.entity_type = 'student' AND ca.entity_id = s.stud_id
              WHERE r.status = 'pending'
              ORDER BY r.reported_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
} else {
    $query = "SELECT r.*, 
                     CASE 
                         WHEN r.content_type = 'post' THEN fp.post_title
                         WHEN r.content_type = 'comment' THEN LEFT(fc.content, 50)
                         WHEN r.content_type = 'user' THEN CONCAT(COALESCE(u.user_first_name, s.stud_first_name), ' ', COALESCE(u.user_last_name, s.stud_last_name))
                     END AS content_title,
                     CASE 
                         WHEN r.content_type = 'post' THEN fp.content
                         WHEN r.content_type = 'comment' THEN fc.content
                         WHEN r.content_type = 'user' THEN NULL
                     END AS content_body,
                     CONCAT(COALESCE(rb_user.user_first_name, rb_student.stud_first_name), ' ', COALESCE(rb_user.user_last_name, rb_student.stud_last_name)) AS reporter_name,
                     f.title AS forum_title
              FROM report r
              LEFT JOIN forum_post fp ON r.content_type = 'post' AND r.content_id = fp.post_id
              LEFT JOIN forum_comment fc ON r.content_type = 'comment' AND r.content_id = fc.comment_id
              LEFT JOIN forum_post fp2 ON fc.post_id = fp2.post_id
              LEFT JOIN forum f ON fp.forum_id = f.forum_id OR fp2.forum_id = f.forum_id
              LEFT JOIN actor a ON r.reported_by = a.actor_id
              LEFT JOIN user rb_user ON a.entity_type = 'user' AND a.entity_id = rb_user.user_id
              LEFT JOIN student rb_student ON a.entity_type = 'student' AND a.entity_id = rb_student.stud_id
              LEFT JOIN actor ca ON r.content_type = 'user' AND r.content_id = ca.actor_id
              LEFT JOIN user u ON ca.entity_type = 'user' AND ca.entity_id = u.user_id
              LEFT JOIN student s ON ca.entity_type = 'student' AND ca.entity_id = s.stud_id
              WHERE r.status = 'pending' AND (r.content_type = 'user' OR f.forum_id IN (" . implode(',', array_fill(0, count($forumModeratorForums), '?')) . "))
              ORDER BY r.reported_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute($forumModeratorForums);
}
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Reports - Career Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #3b82f6;
            --secondary-color: #64748b;
            --danger-color: #ef4444;
            --success-color: #10b981;
            --light-gray: #f1f5f9;
            --dark-color: #1e293b;
            --card-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            --border-radius: 8px;
            --transition: all 0.2s ease-in-out;
        }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background-color: #f8fafc;
            color: var(--dark-color);
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }

        .section-title {
            font-weight: 700;
            font-size: 1.75rem;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--primary-color);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .section-title i {
            color: var(--primary-color);
        }

        .report-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--light-gray);
            transition: var(--transition);
        }

        .report-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
        }

        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .report-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--primary-color);
            margin: 0;
        }

        .report-meta {
            font-size: 0.9rem;
            color: var(--secondary-color);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .report-content {
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
            line-height: 1.6;
        }

        .report-content p {
            margin-bottom: 0.75rem;
        }

        .report-content strong {
            color: var(--dark-color);
        }

        .report-actions {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .btn {
            border-radius: var(--border-radius);
            padding: 0.5rem 1rem;
            font-weight: 500;
            transition: var(--transition);
            position: relative;
        }

        .btn:hover {
            transform: translateY(-1px);
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: #2563eb;
            border-color: #2563eb;
        }

        .btn-warning {
            background-color: #f59e0b;
            border-color: #f59e0b;
        }

        .btn-warning:hover {
            background-color: #d97706;
            border-color: #d97706;
        }

        .btn-danger {
            background-color: var(--danger-color);
            border-color: var(--danger-color);
        }

        .btn-danger:hover {
            background-color: #dc2626;
            border-color: #dc2626;
        }

        .btn:disabled {
            opacity: 0.6;
            pointer-events: none;
        }

        .btn-loading::after {
            content: '';
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #fff;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 0.8s linear infinite;
            margin-left: 8px;
            vertical-align: middle;
        }

        .edit-form {
            display: none;
            margin-top: 1.5rem;
            padding: 1rem;
            background: var(--light-gray);
            border-radius: var(--border-radius);
        }

        .edit-form.show {
            display: block;
            animation: fadeIn 0.3s ease-out;
        }

        .form-control {
            border-radius: var(--border-radius);
            padding: 0.75rem;
            border: 1px solid var(--light-gray);
            transition: var(--transition);
            font-size: 0.95rem;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .alert {
            border-radius: var(--border-radius);
            padding: 1rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--card-shadow);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .toast-container {
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 1300;
        }

        .toast {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            padding: 1rem;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            opacity: 0;
            transition: opacity 0.3s, transform 0.3s;
            transform: translateX(100%);
        }

        .toast.show {
            opacity: 1;
            transform: translateX(0);
        }

        .toast-success {
            border-left: 4px solid var(--success-color);
        }

        .toast-error {
            border-left: 4px solid var(--danger-color);
        }

        /* Modal Styles */
        .modal-content {
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
        }

        .modal-header {
            border-bottom: 1px solid var(--light-gray);
        }

        .modal-footer {
            border-top: 1px solid var(--light-gray);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 0 1rem;
            }

            .report-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .report-actions {
                flex-direction: column;
                gap: 0.5rem;
            }

            .btn {
                width: 100%;
                text-align: center;
            }
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Accessibility */
        .btn:focus, .form-control:focus {
            outline: 2px solid var(--primary-color);
            outline-offset: 2px;
        }

        .report-card[aria-hidden="true"] {
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="section-title">
            <i class="bi bi-shield-exclamation"></i> Manage Reports
        </h2>
        <?php if (count($reports) > 0): ?>
            <?php foreach ($reports as $report): ?>
                <div class="report-card" data-report-id="<?php echo $report['report_id']; ?>" role="region" aria-labelledby="report-title-<?php echo $report['report_id']; ?>">
                    <div class="report-header">
                        <h3 class="report-title" id="report-title-<?php echo $report['report_id']; ?>">
                            <?php 
                            if ($report['content_type'] === 'user') {
                                echo 'User Report: ' . htmlspecialchars($report['content_title']);
                            } else {
                                echo ucfirst($report['content_type']) . ': ' . htmlspecialchars($report['content_title']);
                            }
                            ?>
                            <?php if ($report['forum_title'] && $report['content_type'] !== 'user'): ?>
                                <span class="badge bg-secondary ms-2"><?php echo htmlspecialchars($report['forum_title']); ?></span>
                            <?php endif; ?>
                        </h3>
                        <div class="report-meta">
                            <i class="bi bi-person-circle"></i>
                            Reported by: <?php echo htmlspecialchars($report['reporter_name']); ?> on 
                            <?php echo date('M j, Y g:i a', strtotime($report['reported_at'])); ?>
                        </div>
                    </div>
                    <div class="report-content">
                        <p><strong>Reason:</strong> <?php echo nl2br(htmlspecialchars($report['reason'])); ?></p>
                        <?php if ($report['content_type'] !== 'user' && !empty($report['content_body'])): ?>
                            <p><strong>Content:</strong> <?php echo nl2br(htmlspecialchars($report['content_body'])); ?></p>
                        <?php endif; ?>
                    </div>
                    <?php if ($report['content_type'] !== 'user'): ?>
                        <form class="edit-form" data-report-id="<?php echo $report['report_id']; ?>" aria-hidden="true">
                            <div class="mb-3">
                                <label for="editContent_<?php echo $report['report_id']; ?>" class="form-label">Edit Content</label>
                                <textarea class="form-control" id="editContent_<?php echo $report['report_id']; ?>" name="edited_content" rows="4" required aria-describedby="editHelp_<?php echo $report['report_id']; ?>"><?php echo htmlspecialchars($report['content_body']); ?></textarea>
                                <div id="editHelp_<?php echo $report['report_id']; ?>" class="form-text">Modify the content to address the reported issue.</div>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-warning save-edit-btn" data-report-id="<?php echo $report['report_id']; ?>" aria-label="Save Edited Content">Save Changes</button>
                                <button type="button" class="btn btn-outline-secondary cancel-edit-btn" aria-label="Cancel Edit">Cancel</button>
                            </div>
                        </form>
                    <?php endif; ?>
                    <div class="report-actions">
                        <button class="btn btn-primary resolve-btn" 
                                data-report-id="<?php echo $report['report_id']; ?>" 
                                data-action="approved" 
                                data-bs-toggle="tooltip" 
                                title="Dismiss the report, no action taken"
                                aria-label="Approve <?php echo $report['content_type'] === 'user' ? 'User' : 'Content'; ?>">
                            <i class="bi bi-check-circle"></i> Approve
                        </button>
                        <button class="btn btn-warning <?php echo $report['content_type'] === 'user' ? 'send-warning-btn' : 'edit-btn'; ?>" 
                                data-report-id="<?php echo $report['report_id']; ?>" 
                                data-action="edited" 
                                data-content-type="<?php echo $report['content_type']; ?>"
                                data-bs-toggle="tooltip" 
                                title="<?php echo $report['content_type'] === 'user' ? 'Send a warning notification to the user' : 'Edit the reported content'; ?>"
                                aria-label="<?php echo $report['content_type'] === 'user' ? 'Send Warning to User' : 'Edit Content'; ?>">
                            <i class="bi bi-<?php echo $report['content_type'] === 'user' ? 'exclamation-triangle' : 'pencil-square'; ?>"></i> 
                            <?php echo $report['content_type'] === 'user' ? 'Send Warning' : 'Edit'; ?>
                        </button>
                        <button class="btn btn-danger resolve-btn" 
                                data-report-id="<?php echo $report['report_id']; ?>" 
                                data-action="deleted" 
                                data-bs-toggle="tooltip" 
                                title="<?php echo $report['content_type'] === 'user' ? 'Suspend the user\'s account' : 'Delete the reported content'; ?>"
                                aria-label="<?php echo $report['content_type'] === 'user' ? 'Suspend User' : 'Delete Content'; ?>">
                            <i class="bi bi-<?php echo $report['content_type'] === 'user' ? 'person-x' : 'trash'; ?>"></i> 
                            <?php echo $report['content_type'] === 'user' ? 'Suspend' : 'Delete'; ?>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="alert alert-info" role="alert">
                <i class="bi bi-info-circle me-2"></i> No pending reports.
            </div>
        <?php endif; ?>
    </div>

    <!-- Warning Confirmation Modal -->
    <div class="modal fade" id="warningModal" tabindex="-1" aria-labelledby="warningModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="warningModalLabel">Send Warning to User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="warningForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="warningMessage" class="form-label">Warning Message <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="warningMessage" name="warning_message" rows="4" required aria-describedby="warningMessageHelp"></textarea>
                            <div id="warningMessageHelp" class="form-text">Specify the reason for the warning (this will be sent to the user).</div>
                        </div>
                        <input type="hidden" name="report_id" id="warningReportId">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" aria-label="Cancel">Cancel</button>
                        <button type="submit" class="btn btn-warning" id="sendWarningSubmit" aria-label="Send Warning">Send Warning</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="toast-container"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize Bootstrap tooltips
        document.addEventListener('DOMContentLoaded', function() {
            const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            tooltipTriggerList.forEach(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
        });

        // Toast notification function
        function showToast(message, type = 'success') {
            const toastContainer = document.querySelector('.toast-container');
            const toast = document.createElement('div');
            toast.className = `toast toast-${type} show`;
            toast.setAttribute('role', 'alert');
            toast.setAttribute('aria-live', 'assertive');
            toast.setAttribute('aria-atomic', 'true');
            toast.innerHTML = `
                <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                <span>${message}</span>
            `;
            toastContainer.appendChild(toast);
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        // Resolve report handler
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.resolve-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const reportId = this.dataset.reportId;
                    const action = this.dataset.action;
                    this.classList.add('btn-loading', 'disabled');

                    fetch('../forum/resolve_report.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: `report_id=${reportId}&resolution=${action}`
                    })
                    .then(res => res.json())
                    .then(data => {
                        this.classList.remove('btn-loading', 'disabled');
                        if (data.success) {
                            const reportCard = this.closest('.report-card');
                            reportCard.style.opacity = '0';
                            setTimeout(() => reportCard.setAttribute('aria-hidden', 'true'), 300);
                            showToast('Report resolved successfully!', 'success');
                        } else {
                            showToast(data.message || 'Failed to resolve report.', 'error');
                        }
                    })
                    .catch(err => {
                        this.classList.remove('btn-loading', 'disabled');
                        showToast('An error occurred.', 'error');
                        console.error('Error:', err);
                    });
                });
            });

            // Edit button handler
            document.querySelectorAll('.edit-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const reportId = this.dataset.reportId;
                    const editForm = document.querySelector(`.edit-form[data-report-id="${reportId}"]`);
                    editForm.classList.toggle('show');
                    editForm.setAttribute('aria-hidden', !editForm.classList.contains('show'));
                    this.classList.toggle('disabled');
                });
            });

            // Cancel edit button handler
            document.querySelectorAll('.cancel-edit-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const reportCard = this.closest('.report-card');
                    const reportId = reportCard.dataset.reportId;
                    const editForm = document.querySelector(`.edit-form[data-report-id="${reportId}"]`);
                    editForm.classList.remove('show');
                    editForm.setAttribute('aria-hidden', 'true');
                    document.querySelector(`.edit-btn[data-report-id="${reportId}"]`).classList.remove('disabled');
                });
            });

            // Save edit button handler
            document.querySelectorAll('.save-edit-btn').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const reportId = this.dataset.reportId;
                    const editForm = document.querySelector(`.edit-form[data-report-id="${reportId}"]`);
                    const editedContent = document.querySelector(`#editContent_${reportId}`).value;
                    this.classList.add('btn-loading', 'disabled');

                    fetch('../forum/resolve_report.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: `report_id=${reportId}&resolution=edited&edited_content=${encodeURIComponent(editedContent)}`
                    })
                    .then(res => res.json())
                    .then(data => {
                        this.classList.remove('btn-loading', 'disabled');
                        if (data.success) {
                            const reportCard = this.closest('.report-card');
                            reportCard.style.opacity = '0';
                            setTimeout(() => reportCard.setAttribute('aria-hidden', 'true'), 300);
                            showToast('Content edited and report resolved successfully!', 'success');
                        } else {
                            showToast(data.message || 'Failed to save changes.', 'error');
                        }
                    })
                    .catch(err => {
                        this.classList.remove('btn-loading', 'disabled');
                        showToast('An error occurred.', 'error');
                        console.error('Error:', err);
                    });
                });
            });

            // Send warning button handler (for user reports)
            document.querySelectorAll('.send-warning-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const reportId = this.dataset.reportId;
                    document.getElementById('warningReportId').value = reportId;
                    const warningModal = new bootstrap.Modal(document.getElementById('warningModal'));
                    warningModal.show();
                });
            });

            // Warning form submission
            document.getElementById('warningForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const reportId = document.getElementById('warningReportId').value;
                const warningMessage = document.getElementById('warningMessage').value;
                const submitButton = document.getElementById('sendWarningSubmit');
                submitButton.classList.add('btn-loading', 'disabled');

                fetch('../forum/resolve_report.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `report_id=${reportId}&resolution=edited&warning_message=${encodeURIComponent(warningMessage)}`
                })
                .then(res => res.json())
                .then(data => {
                    submitButton.classList.remove('btn-loading', 'disabled');
                    if (data.success) {
                        const reportCard = document.querySelector(`.report-card[data-report-id="${reportId}"]`);
                        reportCard.style.opacity = '0';
                        setTimeout(() => reportCard.setAttribute('aria-hidden', 'true'), 300);
                        bootstrap.Modal.getInstance(document.getElementById('warningModal')).hide();
                        showToast('Warning sent and report resolved successfully!', 'success');
                    } else {
                        showToast(data.message || 'Failed to send warning.', 'error');
                    }
                })
                .catch(err => {
                    submitButton.classList.remove('btn-loading', 'disabled');
                    showToast('An error occurred.', 'error');
                    console.error('Error:', err);
                });
            });
        });
    </script>
</body>
</html>