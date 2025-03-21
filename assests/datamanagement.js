
function confirmDeleteJob(jobId) {
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            // Perform delete action here (e.g., AJAX call)
            Swal.fire(
                'Deleted!',
                'The job has been deleted.',
                'success'
            );
        }
    });
}




$(document).ready(function() {
let currentPage = 1;
let totalPages = 1;
const limit = 5; // Number of job types per page

loadJobTypes(currentPage);

// Load job types with pagination
function loadJobTypes(page) {
$.ajax({
    url: '../controllers/admin_job_controller.php',
    type: 'POST',
    data: { action: 'fetch', page: page, limit: limit },
    dataType: 'json',
    success: function(response) {
        let rows = ""; // Store generated table rows

        response.jobTypes.forEach(function(jobType) {
            rows += `
                <tr>
                    <td>${jobType.job_type_id}</td>
                    <td>${jobType.job_type_title}</td>
                    <td>${jobType.job_type_description || 'N/A'}</td>
                    <td class="text-center">
                        <button class="btn btn-warning btn-sm edit-job-type" data-id="${jobType.job_type_id}" data-title="${jobType.job_type_title}" data-description="${jobType.job_type_description}">
                            <i class="bi bi-pencil"></i> Edit
                        </button>
                        <button class="btn btn-danger btn-sm delete-job-type" data-id="${jobType.job_type_id}">
                            <i class="bi bi-trash"></i> Delete
                        </button>
                    </td>
                </tr>
            `;
        });

        $('#jobTypeList').html(rows); // Insert rows into tbody

        // Update pagination
        totalPages = response.totalPages;
        updatePagination();
    }
});
}

// Update pagination UI
function updatePagination() {
let paginationHtml = `<nav><ul class="pagination justify-content-center">`;

if (currentPage > 1) {
    paginationHtml += `<li class="page-item"><a class="page-link" href="#" data-page="${currentPage - 1}">&laquo; Previous</a></li>`;
}

for (let i = 1; i <= totalPages; i++) {
    paginationHtml += `<li class="page-item ${i === currentPage ? 'active' : ''}">
        <a class="page-link" href="#" data-page="${i}">${i}</a>
    </li>`;
}

if (currentPage < totalPages) {
    paginationHtml += `<li class="page-item"><a class="page-link" href="#" data-page="${currentPage + 1}">Next &raquo;</a></li>`;
}

paginationHtml += `</ul></nav>`;

$('#paginationControls').html(paginationHtml);
}

// Handle pagination clicks
$(document).on('click', '.page-link', function(e) {
e.preventDefault();
let page = $(this).data('page');
if (page !== currentPage) {
    currentPage = page;
    loadJobTypes(currentPage);
}
});

// Add job type
$('#addJobTypeForm').submit(function(e) {
e.preventDefault();
$.ajax({
    url: '../controllers/admin_job_controller.php',
    type: 'POST',
    data: $(this).serialize() + '&action=add',
    success: function(response) {
        $('#addJobTypeModal').modal('hide');
        loadJobTypes(currentPage);
        $('#addJobTypeForm')[0].reset();
    }
});
});

// Show edit modal with data
$(document).on('click', '.edit-job-type', function() {
$('#editJobTypeId').val($(this).data('id'));
$('#editJobTypeTitle').val($(this).data('title'));
$('#editJobTypeDescription').val($(this).data('description'));
$('#editJobTypeModal').modal('show');
});

// Edit job type
$('#editJobTypeForm').submit(function(e) {
e.preventDefault();
$.ajax({
    url: '../controllers/admin_job_controller.php',
    type: 'POST',
    data: $(this).serialize() + '&action=edit',
    success: function(response) {
        $('#editJobTypeModal').modal('hide');
        loadJobTypes(currentPage);
    }
});
});

// Delete job type
$(document).on('click', '.delete-job-type', function() {
if (!confirm("Are you sure you want to delete this job type?")) return;
$.ajax({
    url: '../controllers/admin_job_controller.php',
    type: 'POST',
    data: { action: 'delete', id: $(this).data('id') },
    success: function(response) {
        loadJobTypes(currentPage);
    }
});
});
});





//SKILL MANAGEMENT
$(document).ready(function () {
let currentPage = 1;
let totalPages = 1;
const limit = 5; // Number of skills per page

fetchSkills(currentPage);

// Fetch skills with pagination
function fetchSkills(page = 1) {
$.post("../controllers/admin_skill_controller.php", { action: "fetch", page: page, limit: limit }, function (response) {
    let data = JSON.parse(response);
    let skills = data.skills;
    let skillListHtml = "";

    skills.forEach((skill, index) => {
        skillListHtml += `
            <tr>
                <td>${skill.skill_id}</td>
                <td>${skill.skill_name}</td>
                <td>${skill.category || "Uncategorized"}</td>
                <td class="text-center">
                    <button class="btn btn-warning btn-sm editSkillBtn" data-id="${skill.skill_id}" data-name="${skill.skill_name}" data-category="${skill.category}">
                        <i class="bi bi-pencil"></i> Edit
                    </button>
                    <button class="btn btn-danger btn-sm deleteSkillBtn" data-id="${skill.skill_id}">
                        <i class="bi bi-trash"></i> Delete
                    </button>
                </td>
            </tr>`;
    });

    $("#skillList").html(skillListHtml);
    totalPages = data.totalPages;
    updateSkillPagination();
});
}

// Update pagination UI
function updateSkillPagination() {
let paginationHtml = `<nav><ul class="pagination justify-content-center">`;

if (currentPage > 1) {
    paginationHtml += `<li class="page-item"><a class="page-link" href="#" data-page="${currentPage - 1}">&laquo; Previous</a></li>`;
}

for (let i = 1; i <= totalPages; i++) {
    paginationHtml += `<li class="page-item ${i === currentPage ? 'active' : ''}">
        <a class="page-link" href="#" data-page="${i}">${i}</a>
    </li>`;
}

if (currentPage < totalPages) {
    paginationHtml += `<li class="page-item"><a class="page-link" href="#" data-page="${currentPage + 1}">Next &raquo;</a></li>`;
}

paginationHtml += `</ul></nav>`;
$('#skillPaginationControls').html(paginationHtml);
}

// Handle pagination clicks
$(document).on("click", ".page-link", function (e) {
e.preventDefault();
let page = $(this).data("page");
if (page !== currentPage) {
    currentPage = page;
    fetchSkills(currentPage);
}
});

// Add skill
$("#addSkillForm").submit(function (e) {
e.preventDefault();
let skillName = $("#skillName").val();
let category = $("#skillCategory").val();

$.post("../controllers/admin_skill_controller.php", { action: "add", skill_name: skillName, category: category }, function (response) {
    if (response.trim() === "success") {
        alert("Skill added successfully!");
        $("#addSkillModal").modal("hide");
        fetchSkills(currentPage);
        $("#addSkillForm")[0].reset();
    } else {
        alert("Error adding skill.");
    }
});
});

// Show edit modal with data
$(document).on("click", ".editSkillBtn", function () {
$("#editSkillId").val($(this).data("id"));
$("#editSkillName").val($(this).data("name"));
$("#editSkillCategory").val($(this).data("category"));
$("#editSkillModal").modal("show");
});

// Edit skill
$("#editSkillForm").submit(function (e) {
e.preventDefault();

let skillData = {
    action: "edit",
    id: $("#editSkillId").val(), // Make sure ID is included
    skill_name: $("#editSkillName").val(),
    category: $("#editSkillCategory").val(),
};

$.post("../controllers/admin_skill_controller.php", skillData, function (response) {
    if (response.trim() === "success") {
        alert("Skill updated successfully!");
        $("#editSkillModal").modal("hide");
        fetchSkills(currentPage);
    } else {
        alert("Error updating skill.");
    }
});
});


// Delete skill
$(document).on("click", ".deleteSkillBtn", function () {
if (!confirm("Are you sure you want to delete this skill?")) return;
$.ajax({
    url: "../controllers/admin_skill_controller.php",
    type: "POST",
    data: { action: "delete", id: $(this).data("id") },
    success: function (response) {
        fetchSkills(currentPage);
    },
});
});
});


