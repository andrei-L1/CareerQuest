<?php
require '../controllers/employer_jobs.php';
require '../auth/employer_auth.php';
include '../includes/employer_navbar.php';

// Fetch employer status and document_url status
global $conn;
$user_id = $_SESSION['user_id'];
try {
    $stmt = $conn->prepare("SELECT employer_id, status, document_url FROM employer WHERE user_id = :user_id AND deleted_at IS NULL");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $employer = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$employer) {
        $_SESSION['error'] = "Employer account not found.";
        header("Location: ../unauthorized.php");
        exit();
    }
    $employer_id = $employer['employer_id'];
    $employer_status = $employer['status'];
    $has_document = !empty($employer['document_url']) ? '1' : '0'; // Flag for document presence
} catch (PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
    header("Location: ../error.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="employer-id" content="<?php echo htmlspecialchars($employer_id); ?>">
    <meta name="employer-status" content="<?php echo htmlspecialchars($employer_status); ?>">
    <meta name="has-document" content="<?php echo htmlspecialchars($has_document); ?>">
    <title>Post New Job</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@yaireo/tagify/dist/tagify.css">
    <style>
        .form-label { font-weight: 500; margin-bottom: 0.5rem; }
        .alert {
            position: fixed; top: 20px; right: 20px; z-index: 1100;
            min-width: 300px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        .form-disabled { opacity: 0.6; pointer-events: none; }
        .alert-verification {
            background-color: #fff3cd; border-color: #ffec99; color: #856404;
        }
    </style>
</head>
<body>
    <div class="container py-5 dashboard-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Post New Job</h2>
            <a href="employer_jobs.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Jobs
            </a>
        </div>
        <?php if ($employer_status === 'Verification' || $has_document === '0'): ?>
            <div class="alert alert-verification alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo $employer_status === 'Verification' ? 
                    'Your account is under verification. Please wait for approval before posting jobs.' : 
                    'You must upload a verification document before posting jobs. <a href="employer_settings.php">Upload now</a>.'; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <form id="addJobForm" method="POST" class="<?php echo $employer_status === 'Verification' || $has_document === '0' ? 'form-disabled' : ''; ?>">
            <div class="row g-3">
                <div class="col-md-12">
                    <label for="jobTitle" class="form-label">Job Title *</label>
                    <input type="text" class="form-control" id="jobTitle" name="title" required>
                </div>
                <div class="col-md-6">
                    <label for="jobType" class="form-label">Job Type *</label>
                    <select class="form-select" id="jobType" name="job_type_id" required>
                        <option value="">Select Job Type</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="jobLocation" class="form-label">Location *</label>
                    <input type="text" class="form-control" id="jobLocation" name="location" required>
                </div>
                <div class="col-md-6">
                    <label for="jobSalaryType" class="form-label">Salary Type *</label>
                    <select class="form-select" id="jobSalaryType" name="salary_type" required>
                        <option value="Yearly">Yearly</option>
                        <option value="Monthly">Monthly</option>
                        <option value="Weekly">Weekly</option>
                        <option value="Hourly">Hourly</option>
                        <option value="Commission">Commission</option>
                        <option value="Negotiable">Negotiable</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="jobExpires" class="form-label">Expiration Date</label>
                    <input type="date" class="form-control" id="jobExpires" name="expires_at">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Salary Range</label>
                    <div class="input-group">
                        <span class="input-group-text">₱</span>
                        <input type="number" class="form-control" id="jobMinSalary" name="min_salary" min="0" step="0.01" placeholder="Minimum">
                        <input type="number" class="form-control" id="jobMaxSalary" name="max_salary" min="0" step="0.01" placeholder="Maximum">
                    </div>
                </div>
                <div class="col-md-6">
                    <label for="jobSalaryDisclosure" class="form-label">Disclose Salary</label>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="jobSalaryDisclosure" name="salary_disclosure" checked>
                        <label class="form-check-label" for="jobSalaryDisclosure">Show salary on job posting</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <label for="jobVisibility" class="form-label">Visible To *</label>
                    <select class="form-select" id="jobVisibility" name="visible_to" required>
                        <option value="both" selected>Students and Applicants</option>
                        <option value="students">Students Only</option>
                        <option value="applicants">Applicants Only</option>
                    </select>
                </div>
                <div class="col-12">
                    <label for="jobDescription" class="form-label">Job Description *</label>
                    <textarea class="form-control" id="jobDescription" name="description" rows="5" required></textarea>
                </div>
                <div class="col-12">
                    <label for="skillsInput" class="form-label">Required Skills *</label>
                    <input id="skillsInput" name="skillsInput" placeholder="Type a skill and press Enter" class="form-control" required>
                    <div id="importanceContainer" class="mt-3"></div>
                </div>
            </div>
            <div class="mt-4 d-flex justify-content-end">
                <button type="submit" class="btn btn-primary" <?php echo $employer_status === 'Verification' || $has_document === '0' ? 'disabled' : ''; ?>>Post Job</button>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@yaireo/tagify"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let cachedSkills = [];
        let tagify;
        let skillMap = {}; // name => id

        document.addEventListener('DOMContentLoaded', function() {
            // Check employer status and document presence
            const employerStatus = document.querySelector('meta[name="employer-status"]').getAttribute('content');
            const hasDocument = document.querySelector('meta[name="has-document"]').getAttribute('content');
            if (employerStatus === 'Verification' || hasDocument === '0') {
                document.querySelector('#addJobForm').classList.add('form-disabled');
                document.querySelector('#addJobForm button[type="submit"]').disabled = true;
            }

            loadJobTypes();
            loadSkills();
            document.getElementById("addJobForm").addEventListener("submit", handleJobFormSubmit);
            // Disable salary inputs when disclosure is unchecked
            document.getElementById("jobSalaryDisclosure").addEventListener("change", function() {
                const minSalaryInput = document.getElementById("jobMinSalary");
                const maxSalaryInput = document.getElementById("jobMaxSalary");
                minSalaryInput.disabled = !this.checked;
                maxSalaryInput.disabled = !this.checked;
                if (!this.checked) {
                    minSalaryInput.value = '';
                    maxSalaryInput.value = '';
                }
            });
        });

        function loadJobTypes() {
            fetch("../controllers/job_moderation.php?type=job_types")
                .then(response => response.json())
                .then(data => {
                    let jobTypeSelect = document.getElementById("jobType");
                    jobTypeSelect.innerHTML = '<option value="">Select Job Type</option>';
                    data.forEach(jobType => {
                        jobTypeSelect.innerHTML += `<option value="${jobType.job_type_id}">${jobType.job_type_title}</option>`;
                    });
                })
                .catch(error => console.error("Error loading job types:", error));
        }

        function loadSkills() {
            fetch("../controllers/job_moderation.php?type=skills")
                .then(response => response.json())
                .then(data => {
                    cachedSkills = data;
                    skillMap = {};
                    data.forEach(skill => skillMap[skill.skill_name] = skill.skill_id);
                    initializeTagify();
                })
                .catch(error => console.error("Error loading skills:", error));
        }

        function initializeTagify() {
            const input = document.querySelector('#skillsInput');
            tagify = new Tagify(input, {
                whitelist: cachedSkills.map(skill => skill.skill_name),
                enforceWhitelist: true,
                dropdown: {
                    enabled: 1,
                    closeOnSelect: false
                }
            });

            tagify.on('add', updateImportanceFields);
            tagify.on('remove', updateImportanceFields);
        }

        function updateImportanceFields() {
            const container = document.getElementById('importanceContainer');
            container.innerHTML = '';

            tagify.value.forEach(tag => {
                const skillName = tag.value;
                const importanceRow = document.createElement('div');
                importanceRow.className = "mb-2";
                importanceRow.innerHTML = `
                    <label class="form-label">${skillName} Importance</label>
                    <select class="form-select skill-importance" data-skill="${skillName}" required>
                        <option value="">Select Importance</option>
                        <option value="Low">Low</option>
                        <option value="Medium">Medium</option>
                        <option value="High">High</option>
                    </select>
                `;
                container.appendChild(importanceRow);
            });
        }

        function getCurrentEmployerId() {
            return document.querySelector('meta[name="employer-id"]').getAttribute('content');
        }

        function handleJobFormSubmit(event) {
            event.preventDefault();
            const employerStatus = document.querySelector('meta[name="employer-status"]').getAttribute('content');
            const hasDocument = document.querySelector('meta[name="has-document"]').getAttribute('content');
            if (employerStatus === 'Verification' || hasDocument === '0') {
                const message = employerStatus === 'Verification' 
                    ? 'Your account is under verification. Please wait for approval before posting jobs.'
                    : 'You must upload a verification document before posting jobs. <a href="employer_settings.php">Upload now</a>.';
                showAlert('danger', message);
                return;
            }

            const formData = new FormData(document.getElementById('addJobForm'));

            // Validate visible_to
            const visibleTo = formData.get('visible_to');
            if (!['students', 'applicants', 'both'].includes(visibleTo)) {
                showAlert('danger', 'Invalid visibility selection.');
                return;
            }

            const selectedSkills = tagify.value;
            const importanceFields = document.querySelectorAll('.skill-importance');
            selectedSkills.forEach((tag, index) => {
                const skillName = tag.value;
                const skillId = skillMap[skillName];
                const importance = [...importanceFields].find(sel => sel.dataset.skill === skillName)?.value || '';
                if (skillId && importance) {
                    formData.append(`skills[${index}][skill_id]`, skillId);
                    formData.append(`skills[${index}][importance]`, importance);
                }
            });

            formData.append('employer_id', getCurrentEmployerId());

            fetch("../controllers/employer_post_job.php", {
                method: "POST",
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', 'Job posted successfully!');
                    setTimeout(() => window.location.href = 'employer_jobs.php', 1500);
                } else {
                    showAlert('danger', 'Error: ' + data.error);
                }
            })
            .catch(error => {
                console.error("Error:", error);
                showAlert('danger', 'An error occurred while posting the job');
            });
        }

        function showAlert(type, message) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
            alertDiv.role = 'alert';
            alertDiv.innerHTML = `${message}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>`;
            const container = document.querySelector('.dashboard-container');
            container.insertBefore(alertDiv, container.firstChild);
            setTimeout(() => {
                alertDiv.classList.remove('show');
                setTimeout(() => alertDiv.remove(), 150);
            }, 5000);
        }
    </script>
</body>
</html>
<?php include '../includes/stud_footer.php'; ?>