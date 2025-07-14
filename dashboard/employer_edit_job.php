<?php
require '../controllers/employer_jobs.php';
require '../auth/employer_auth.php';
include '../includes/employer_navbar.php';

// Fetch job details based on job_id
$job_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$job = getJobDetails($job_id);
$job_types = getJobTypes();
$skills = getAvailableSkills();
$selected_skills = getJobSkills($job_id);

if (!$job) {
    header('Location: ../dashboard/employer_jobs.php?error=Job+not+found');
    exit;
}

// Generate CSRF token
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Prepare skills data for Tagify
$tagify_skills = array_map(function($skill) {
    return [
        'value' => (int)$skill['skill_id'], // Ensure integer skill_id
        'name' => htmlspecialchars($skill['skill_name'])
    ];
}, $skills);

$selected_tags = array_map(function($skill) {
    return [
        'value' => (int)$skill['skill_id'], // Ensure integer skill_id
        'name' => htmlspecialchars($skill['skill_name']),
        'importance' => $skill['importance'] ?? 'Medium'
    ];
}, $selected_skills);

// Create skillMap for client-side validation
$skill_map = array_column($skills, 'skill_id', 'skill_name');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Job Posting - Employer Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@yaireo/tagify@4.17.0/dist/tagify.css">
    <style>
        :root {
            --primary-color: #1A4D8F;
            --primary-light: #e8f0fe;
            --primary-lighter: #f5f8ff;
            --secondary-color: #3A7BD5;
            --accent-color: #4cc9f0;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --light-bg: #f8f9fa;
            --dark-text: #2b2d42;
            --medium-text: #495057;
            --light-text: #6c757d;
            --border-color: rgba(0, 0, 0, 0.08);
            --card-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            --card-hover-shadow: 0 6px 12px rgba(26, 77, 143, 0.1);
            --transition-fast: 0.15s ease;
            --transition-medium: 0.3s ease;
        }
        
        body {
            background-color: #f8fafc;
            color: var(--dark-text);
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
        }
        
        .dashboard-container {
            padding: 2rem;
            max-width: 1450px;
            margin: 0 auto;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .page-title {
            font-weight: 700;
            color: var(--dark-text);
            margin-bottom: 0;
            font-size: 1.75rem;
        }
        
        .form-card {
            background: white;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .form-label {
            font-weight: 500;
            color: var(--dark-text);
        }
        
        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid var(--border-color);
            transition: all var(--transition-fast);
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(26, 77, 143, 0.25);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            border-radius: 8px;
            padding: 0.5rem 1.5rem;
            font-weight: 500;
            transition: all var(--transition-fast);
        }
        
        .btn-primary:hover {
            background-color: #0d3b7a;
            border-color: #0d3b7a;
            transform: translateY(-1px);
        }
        
        .btn-outline-secondary {
            border-radius: 8px;
            padding: 0.5rem 1.5rem;
            font-weight: 500;
        }
        
        .alert {
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        
        .tagify {
            border-radius: 8px;
            border: 1px solid var(--border-color);
            min-height: 38px;
            padding: 0.2rem;
        }
        
        .tagify__tag {
            background-color: var(--primary-light);
            border-radius: 4px;
            margin: 0.2rem;
        }
        
        .tagify__tag__removeBtn {
            color: var(--danger-color);
        }
        
        .tagify__tag > div {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .tagify__tag select {
            font-size: 0.85rem;
            padding: 0.1rem;
            border-radius: 4px;
            border: 1px solid var(--border-color);
        }
        
        @media (max-width: 768px) {
            .dashboard-container {
                padding: 1rem;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }
        
        .invalid-feedback {
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <div class="container dashboard-container">
        <div class="page-header">
            <h1 class="page-title">Edit Job Posting</h1>
            <a href="../dashboard/employer_jobs.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Jobs
            </a>
        </div>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_GET['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_GET['success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="form-card">
            <form id="editJobForm" action="../controllers/employer_update_job.php" method="POST" novalidate>
                <input type="hidden" name="job_id" value="<?= htmlspecialchars($job['job_id']) ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <input type="hidden" name="skills_data" id="skillsData">

                <div class="mb-3">
                    <label for="jobTitle" class="form-label">Job Title <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="jobTitle" name="title" 
                           value="<?= htmlspecialchars($job['title']) ?>" required maxlength="255">
                    <div class="invalid-feedback">Please enter a job title (max 255 characters).</div>
                </div>

                <div class="mb-3">
                    <label for="jobType" class="form-label">Job Type <span class="text-danger">*</span></label>
                    <select class="form-select" id="jobType" name="job_type_id" required>
                        <option value="">Select job type</option>
                        <?php foreach ($job_types as $type): ?>
                            <option value="<?= $type['job_type_id'] ?>" 
                                    <?= $type['job_type_id'] == $job['job_type_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($type['job_type_title']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback">Please select a job type.</div>
                </div>

                <div class="mb-3">
                    <label for="location" class="form-label">Location <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="location" name="location" 
                           value="<?= htmlspecialchars($job['location']) ?>" required maxlength="255">
                    <div class="invalid-feedback">Please enter a location (max 255 characters).</div>
                </div>

                <div class="mb-3">
                    <label for="salary" class="form-label">Salary (Optional)</label>
                    <input type="number" class="form-control" id="salary" name="salary" 
                           value="<?= $job['salary'] ? number_format($job['salary'], 2, '.', '') : '' ?>" 
                           min="0" step="0.01" max="9999999.99">
                    <div class="invalid-feedback">Please enter a valid salary (0 to 9,999,999.99).</div>
                </div>

                <div class="mb-3">
                    <label for="expiresAt" class="form-label">Expiration Date (Optional)</label>
                    <input type="date" class="form-control" id="expiresAt" name="expires_at" 
                           value="<?= $job['expires_at'] ? date('Y-m-d', strtotime($job['expires_at'])) : '' ?>"
                           min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                    <div class="invalid-feedback">Please select a future date.</div>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Job Description <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="description" name="description" rows="6" required><?= htmlspecialchars($job['description']) ?></textarea>
                    <div class="invalid-feedback">Please enter a job description.</div>
                </div>

                <div class="mb-3">
                    <label for="skills" class="form-label">Required Skills (Optional)</label>
                    <input id="skills" name="skills" class="form-control" placeholder="Type to search skills..." />
                    <div class="invalid-feedback">Invalid skills selected. Please choose valid skills from the list.</div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Save Changes
                    </button>
                    <a href="../dashboard/employer_jobs.php" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <?php include '../includes/stud_footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@yaireo/tagify@4.17.0/dist/tagify.min.js"></script>
    <script>
        $(document).ready(function() {
            const form = document.getElementById('editJobForm');
            const expiresAtInput = document.getElementById('expiresAt');
            const skillsInput = document.getElementById('skills');
            const skillsDataInput = document.getElementById('skillsData');
            const skillMap = <?= json_encode($skill_map) ?>; // Map skill_name to skill_id

            // Initialize Tagify
            const tagify = new Tagify(skillsInput, {
                whitelist: <?= json_encode($tagify_skills) ?>,
                enforceWhitelist: true, // Only allow skills from whitelist
                maxTags: 10,
                dropdown: {
                    maxItems: 20,
                    classname: 'tagify__dropdown',
                    enabled: 0,
                    closeOnSelect: true
                },
                templates: {
                    tag: function(tagData) {
                        return `
                            <tag title="${tagData.name}" contenteditable="false" spellcheck="false" tabIndex="-1" class="tagify__tag" ${this.getAttributes(tagData)}>
                                <x title="" class="tagify__tag__removeBtn" role="button" aria-label="remove tag"></x>
                                <div>
                                    <span class="tagify__tag-text">${tagData.name}</span>
                                    <select name="skills[${tagData.value}][importance]" class="importance-select">
                                        <option value="Low" ${tagData.importance === 'Low' ? 'selected' : ''}>Low</option>
                                        <option value="Medium" ${tagData.importance === 'Medium' ? 'selected' : ''}>Medium</option>
                                        <option value="High" ${tagData.importance === 'High' ? 'selected' : ''}>High</option>
                                    </select>
                                </div>
                            </tag>
                        `;
                    },
                    dropdownItem: function(tagData) {
                        return `
                            <div ${this.getAttributes(tagData)} class="tagify__dropdown__item" tabindex="0" role="option">
                                ${tagData.name}
                            </div>
                        `;
                    }
                },
                transformTag: function(tagData) {
                    if (!tagData.value || parseInt(tagData.value) <= 0) {
                        console.warn('Invalid tag value (id):', tagData);
                        return null; // Prevent adding invalid tags
                    }
                    tagData.importance = tagData.importance || 'Medium'; // Default importance
                    console.log('Transformed tag:', tagData);
                },
                placeholder: "Type to search skills..."
            });

            // Pre-fill selected skills
            tagify.addTags(<?= json_encode($selected_tags) ?>);

            // Update skills data before submission
            function updateSkillsData() {
                const tags = tagify.value
                    .filter(tag => tag.value && parseInt(tag.value) > 0)
                    .map(tag => ({
                        id: parseInt(tag.value),
                        importance: tag.importance || 'Medium'
                    }));
                skillsDataInput.value = JSON.stringify(tags);
                console.log('Updated skills data:', skillsDataInput.value);
            }

            // Validate and set importance on tag add
            tagify.on('add', function(e) {
                const tag = e.detail.data;
                if (!tag.value || parseInt(tag.value) <= 0) {
                    console.warn('Removing invalid tag:', tag);
                    tagify.removeTags(tag);
                    return;
                }
                // Ensure importance is set
                tag.importance = tag.importance || 'Medium';
                console.log('Added tag:', tag);
                updateSkillsData();
            });

            // Update skills data on remove
            tagify.on('remove', function() {
                updateSkillsData();
                console.log('Tag removed, updated skills data:', skillsDataInput.value);
            });

            // Handle importance select changes
            $(document).on('change', '.importance-select', function(e) {
                e.preventDefault();
                const tagValue = $(this).closest('.tagify__tag').attr('value');
                const newImportance = $(this).val();
                const tag = tagify.value.find(t => t.value === tagValue);
                if (tag) {
                    tag.importance = newImportance;
                    tagify.update(); // Update Tagify to reflect changes
                    updateSkillsData();
                    console.log('Importance updated for tag:', tag);
                } else {
                    console.warn('Tag not found for value:', tagValue);
                }
            });

            // Initial update of skills data
            updateSkillsData();

            // Client-side salary validation
            $('#salary').on('input', function() {
                const value = parseFloat(this.value);
                if (isNaN(value) || value < 0 || value > 9999999.99) {
                    this.classList.add('is-invalid');
                } else {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                }
            });

            // AJAX form submission
            $('#editJobForm').on('submit', function(e) {
                e.preventDefault();

                const formElement = document.getElementById('editJobForm');
                if (!formElement) {
                    console.error('Form element not found: #editJobForm');
                    $('.form-card').prepend(
                        `<div class="alert alert-danger alert-dismissible fade show" role="alert">
                            Form not found. Please try again.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>`
                    );
                    return;
                }

                console.log('Submit handler context:', this);
                console.log('Form element:', formElement);

                // Validate form
                if (!formElement.checkValidity()) {
                    formElement.classList.add('was-validated');
                    console.log('Form validation failed');
                    return;
                }

                // Validate expiration date
                if (expiresAtInput.value) {
                    const selectedDate = new Date(expiresAtInput.value);
                    const today = new Date();
                    today.setHours(0, 0, 0, 0);
                    if (selectedDate <= today) {
                        expiresAtInput.classList.add('is-invalid');
                        return;
                    } else {
                        expiresAtInput.classList.remove('is-invalid');
                        expiresAtInput.classList.add('is-valid');
                    }
                }

                // Validate skills
                let parsedTags;
                try {
                    parsedTags = JSON.parse(skillsDataInput.value || '[]');
                } catch (e) {
                    console.error('Invalid skills JSON:', skillsDataInput.value);
                    skillsInput.classList.add('is-invalid');
                    $('.form-card').prepend(
                        `<div class="alert alert-danger alert-dismissible fade show" role="alert">
                            Invalid skills format. Please select valid skills from the list.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>`
                    );
                    return;
                }

                if (parsedTags.some(tag => !tag.id || parseInt(tag.id) <= 0)) {
                    console.warn('Invalid skills detected:', parsedTags);
                    skillsInput.classList.add('is-invalid');
                    $('.form-card').prepend(
                        `<div class="alert alert-danger alert-dismissible fade show" role="alert">
                            Invalid skills detected. Please choose valid skills from the list.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>`
                    );
                    return;
                }

                // Remove any existing alerts
                $('.alert').remove();

                $.ajax({
                    url: '../controllers/employer_update_job.php',
                    method: 'POST',
                    data: $(formElement).serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            window.location.href = '../dashboard/employer_jobs.php?success=' + encodeURIComponent(response.message);
                        } else {
                            $('.form-card').prepend(
                                `<div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    ${response.message}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>`
                            );
                        }
                    },
                    error: function(xhr) {
                        $('.form-card').prepend(
                            `<div class="alert alert-danger alert-dismissible fade show" role="alert">
                                Failed to update job: ${xhr.responseJSON?.message || 'Server error'}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>`
                        );
                    }
                });
            });

            // Clear invalid feedback on input for valid form elements
            form.querySelectorAll('input:not([id="skills"]), select, textarea').forEach(input => {
                input.addEventListener('input', function() {
                    if (this.checkValidity && this.checkValidity()) {
                        this.classList.remove('is-invalid');
                        this.classList.add('is-valid');
                    }
                });
            });
        });
    </script>
</body>
</html>