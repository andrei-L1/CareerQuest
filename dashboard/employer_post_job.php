<?php
require '../controllers/employer_jobs.php';
require '../auth/employer_auth.php';
include '../includes/employer_navbar.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="employer-id" content="123"> <!-- Set employer_id dynamically -->
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
        <form id="addJobForm" method="POST">
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
                    <label for="jobSalary" class="form-label">Salary *</label>
                    <div class="input-group">
                        <span class="input-group-text">₱</span>
                        <input type="number" class="form-control" id="jobSalary" name="salary" min="0" step="0.01">
                    </div>
                </div>
                <div class="col-md-6">
                    <label for="jobExpires" class="form-label">Expiration Date</label>
                    <input type="date" class="form-control" id="jobExpires" name="expires_at">
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
                <button type="submit" class="btn btn-primary">Post Job</button>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@yaireo/tagify"></script>
    <script>
        let cachedSkills = [];
        let tagify;
        let skillMap = {}; // name => id

        document.addEventListener('DOMContentLoaded', function() {
            loadJobTypes();
            loadSkills();
            document.getElementById("addJobForm").addEventListener("submit", handleJobFormSubmit);
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
            const formData = new FormData(document.getElementById('addJobForm'));

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
                    setTimeout(() => window.location.reload(), 1500);
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
