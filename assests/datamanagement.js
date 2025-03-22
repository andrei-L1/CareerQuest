
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
    url: '../controllers/admin_jobtype_controller.php',
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
    url: '../controllers/admin_jobtype_controller.php',
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
    url: '../controllers/admin_jobtype_controller.php',
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
    url: '../controllers/admin_jobtype_controller.php',
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





















//COURSE MANAGEMENT

$(document).ready(function () {
    let currentPage = 1;
    let totalPages = 1;
    const limit = 5;

    fetchCourses(currentPage);

    // Fetch Courses with Pagination
    function fetchCourses(page = 1) {
        $.post("../controllers/admin_course_controller.php", { action: "fetch", page: page, limit: limit }, function (response) {
            let data = JSON.parse(response);
            let courses = data.courses;
            let courseListHtml = "";

            courses.forEach((course) => {
                courseListHtml += `
                    <tr>
                        <td>${course.course_id}</td>
                        <td>${course.course_title}</td>
                        <td>${course.course_description}</td>
                        <td class="text-center">
                            <button class="btn btn-warning btn-sm editCourseBtn" data-id="${course.course_id}" data-title="${course.course_title}" data-description="${course.course_description}">
                                <i class="bi bi-pencil"></i> Edit
                            </button>
                            <button class="btn btn-danger btn-sm deleteCourseBtn" data-id="${course.course_id}">
                                <i class="bi bi-trash"></i> Delete
                            </button>
                        </td>
                    </tr>`;
            });

            $("#courseList").html(courseListHtml);
            totalPages = data.totalPages;
            updateCoursePagination();
        });
    }

    // Update Pagination UI
    function updateCoursePagination() {
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
        $('#coursePaginationControls').html(paginationHtml);
    }

    // Handle Pagination Clicks
    $(document).on("click", ".page-link", function (e) {
        e.preventDefault();
        let page = $(this).data("page");
        if (page !== currentPage) {
            currentPage = page;
            fetchCourses(currentPage);
        }
    });

    // Add Course
    $("#addCourseForm").submit(function (e) {
        e.preventDefault();
        let courseTitle = $("#courseTitle").val();
        let courseDescription = $("#courseDescription").val();

        $.post("../controllers/admin_course_controller.php", { action: "add", course_title: courseTitle, course_description: courseDescription }, function (response) {
            if (response.trim() === "success") {
                alert("Course added successfully!");
                $("#addCourseModal").modal("hide");
                fetchCourses(currentPage);
                $("#addCourseForm")[0].reset();
            } else {
                alert("Error adding course.");
            }
        });
    });

    // Show Edit Modal
    $(document).on("click", ".editCourseBtn", function () {
        $("#editCourseId").val($(this).data("id"));
        $("#editCourseTitle").val($(this).data("title"));
        $("#editCourseDescription").val($(this).data("description"));
        $("#editCourseModal").modal("show");
    });

    // Edit Course
    $("#editCourseForm").submit(function (e) {
        e.preventDefault();
        $.ajax({
            url: "../controllers/admin_course_controller.php",
            type: "POST",
            data: {
                action: "edit",
                course_id: $("#editCourseId").val(),
                course_title: $("#editCourseTitle").val(),
                course_description: $("#editCourseDescription").val()
            },
            success: function (response) {
                $("#editCourseModal").modal("hide");
                fetchCourses(currentPage);
            },
        });
    });

    // Delete Course
    $(document).on("click", ".deleteCourseBtn", function () {
        if (!confirm("Are you sure you want to delete this course?")) return;
        $.ajax({
            url: "../controllers/admin_course_controller.php",
            type: "POST",
            data: { action: "delete", course_id: $(this).data("id") },
            success: function (response) {
                fetchCourses(currentPage);
            },
        });
    });
});



$(document).ready(function () {
    let currentPage = 1;
    let totalPages = 1;
    const limit = 5;

    fetchRoles(currentPage);

    function fetchRoles(page = 1) {
        $.post("../controllers/admin_role_controller.php", { action: "fetch", page: page, limit: limit }, function (response) {
            let data = JSON.parse(response);
            let roles = data.roles;
            let roleListHtml = "";

            roles.forEach(role => {
                roleListHtml += `
                    <tr>
                        <td>${role.role_id}</td>
                        <td>${role.role_title}</td>
                        <td>${role.role_description}</td>
                        <td class="text-center">
                            <button class="btn btn-warning btn-sm editRoleBtn" data-id="${role.role_id}" data-title="${role.role_title}" data-description="${role.role_description}">
                                <i class="bi bi-pencil"></i> Edit
                            </button>
                            <button class="btn btn-danger btn-sm deleteRoleBtn" data-id="${role.role_id}">
                                <i class="bi bi-trash"></i> Delete
                            </button>
                        </td>
                    </tr>`;
            });

            $("#roleList").html(roleListHtml);
            totalPages = data.totalPages;
            updateRolePagination();
        });
    }

    function updateRolePagination() {
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
        $('#rolePaginationControls').html(paginationHtml);
    }

    $(document).on("click", ".page-link", function (e) {
        e.preventDefault();
        let page = $(this).data("page");
        if (page !== currentPage) {
            currentPage = page;
            fetchRoles(currentPage);
        }
    });

    $("#addRoleForm").submit(function (e) {
        e.preventDefault();
        let roleTitle = $("#roleTitle").val();
        let roleDescription = $("#roleDescription").val();

        $.post("../controllers/admin_role_controller.php", { action: "add", role_title: roleTitle, role_description: roleDescription }, function (response) {
            if (response.trim() === "success") {
                alert("Role added successfully!");
                $("#addRoleModal").modal("hide");
                fetchRoles(currentPage);
                $("#addRoleForm")[0].reset();
            } else {
                alert("Error adding role.");
            }
        });
    });

    $(document).on("click", ".editRoleBtn", function () {
        $("#editRoleId").val($(this).data("id"));
        $("#editRoleTitle").val($(this).data("title"));
        $("#editRoleDescription").val($(this).data("description"));
        $("#editRoleModal").modal("show");
    });

    $("#editRoleForm").submit(function (e) {
        e.preventDefault();
        $.ajax({
            url: "../controllers/admin_role_controller.php",
            type: "POST",
            data: $(this).serialize() + "&action=edit",
            success: function (response) {
                $("#editRoleModal").modal("hide");
                fetchRoles(currentPage);
            },
        });
    });

    $(document).on("click", ".deleteRoleBtn", function () {
        if (!confirm("Are you sure you want to delete this role?")) return;
        $.ajax({
            url: "../controllers/admin_role_controller.php",
            type: "POST",
            data: { action: "delete", id: $(this).data("id") },
            success: function (response) {
                fetchRoles(currentPage);
            },
        });
    });
});
