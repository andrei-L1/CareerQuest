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
    <style>
        .form-label { font-weight: 500; margin-bottom: 0.5rem; }
        #skills-table-body tr td { vertical-align: middle; }
        #skills-table-body .form-select { width: 100%; }
        #add-skill { padding: 0.25rem 0.5rem; font-size: 0.875rem; }
        .alert {
            position: fixed; top: 20px; right: 20px; z-index: 1100;
            min-width: 300px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
    </style>
</head>
<body>
    <div class="container py-5 dashboard-container">
        <h2 class="mb-4">Post New Job</h2>
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
                    <label for="jobSalary" class="form-label">Salary (per annum)</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
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
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">Required Skills</h6>
                            <button type="button" id="add-skill" class="btn btn-sm btn-primary">
                                <i class="fas fa-plus"></i> Add Skill
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th width="60%">Skill</th>
                                        <th width="30%">Importance</th>
                                        <th width="10%">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="skills-table-body">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-4 d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">Post Job</button>
            </div>
        </form>
    </div>

    <script>
        let cachedSkills = [];

        document.addEventListener('DOMContentLoaded', function() {
            loadJobTypes();
            loadSkills();
            document.getElementById('add-skill').addEventListener('click', addSkillRow);
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
            if (cachedSkills.length === 0) {
                fetch("../controllers/job_moderation.php?type=skills")
                    .then(response => response.json())
                    .then(data => {
                        cachedSkills = data;
                    })
                    .catch(error => console.error("Error loading skills:", error));
            }
        }

        function addSkillRow() {
            const skillsContainer = document.getElementById('skills-table-body');
            const skillRow = document.createElement('tr');

            const skillSelectTd = document.createElement('td');
            const skillSelect = document.createElement('select');
            skillSelect.className = 'form-select skill-select';
            skillSelect.name = 'skills[]';
            skillSelect.required = true;

            skillSelect.innerHTML = '<option value="">Select Skill</option>';
            cachedSkills.forEach(skill => {
                skillSelect.innerHTML += `<option value="${skill.skill_id}">${skill.skill_name}</option>`;
            });

            skillSelectTd.appendChild(skillSelect);

            const importanceTd = document.createElement('td');
            const importanceSelect = document.createElement('select');
            importanceSelect.className = 'form-select importance-select';
            importanceSelect.name = 'importance[]';
            ['Low', 'Medium', 'High'].forEach(level => {
                importanceSelect.innerHTML += `<option value="${level}">${level}</option>`;
            });
            importanceTd.appendChild(importanceSelect);

            const removeTd = document.createElement('td');
            const removeButton = document.createElement('button');
            removeButton.type = 'button';
            removeButton.className = 'btn btn-danger btn-sm';
            removeButton.innerHTML = '<i class="fas fa-trash"></i>';
            removeButton.addEventListener('click', function() {
                skillsContainer.removeChild(skillRow);
            });
            removeTd.appendChild(removeButton);

            skillRow.appendChild(skillSelectTd);
            skillRow.appendChild(importanceTd);
            skillRow.appendChild(removeTd);
            skillsContainer.appendChild(skillRow);
        }

        function handleJobFormSubmit(event) {
            event.preventDefault();
            let formData = new FormData(this);

            document.querySelectorAll("#skills-table-body tr").forEach((row, index) => {
                let skill = row.querySelector(".skill-select").value;
                let importance = row.querySelector(".importance-select").value;
                if (skill) {
                    formData.append(`skills[${index}][skill_id]`, skill);
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

        function getCurrentEmployerId() {
            return document.querySelector('meta[name="employer-id"]').getAttribute('content');
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