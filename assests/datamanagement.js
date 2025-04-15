// JOB TYPE MANAGEMENT

$(document).ready(function() {
    let jobTypePageIndex = 1;
    let jobTypeTotalPages = 1;
    const jobTypesPerPageLimit = 5;

    loadJobTypeData(jobTypePageIndex);

    // Load job types with pagination
    function loadJobTypeData(pageIndex) {
        $.ajax({
            url: '../controllers/admin_jobtype_controller.php',
            type: 'POST',
            data: { action: 'fetch', page: pageIndex, limit: jobTypesPerPageLimit },
            dataType: 'json',
            success: function(response) {
                let rowsHtml = ""; // Store generated table rows

                response.jobTypes.forEach(function(jobType) {
                    rowsHtml += `
                        <tr>
                            <td>${jobType.job_type_id}</td>
                            <td>${jobType.job_type_title}</td>
                            <td>${jobType.job_type_description || 'N/A'}</td>
                            <td class="text-center">
                                <button class="btn btn-warning btn-sm editJobTypeButton" data-id="${jobType.job_type_id}" data-title="${jobType.job_type_title}" data-description="${jobType.job_type_description}">
                                    <i class="bi bi-pencil"></i> Edit
                                </button>
                                <button class="btn btn-danger btn-sm deleteJobTypeButton" data-id="${jobType.job_type_id}">
                                    <i class="bi bi-trash"></i> Delete
                                </button>
                            </td>
                        </tr>
                    `;
                });

                $('#jobTypeListContainer').html(rowsHtml); // Insert rows into tbody

                // Update pagination
                jobTypeTotalPages = response.totalPages;
                updateJobTypePagination();
            }
        });
    }

    // Update pagination UI
    function updateJobTypePagination() {
        let paginationHtml = `<nav><ul class="pagination justify-content-center">`;

        if (jobTypePageIndex > 1) {
            paginationHtml += `<li class="page-item"><a class="page-link" href="#" data-page="${jobTypePageIndex - 1}">&laquo; Previous</a></li>`;
        }

        for (let i = 1; i <= jobTypeTotalPages; i++) {
            paginationHtml += `<li class="page-item ${i === jobTypePageIndex ? 'active' : ''}">
                <a class="page-link" href="#" data-page="${i}">${i}</a>
            </li>`;
        }

        if (jobTypePageIndex < jobTypeTotalPages) {
            paginationHtml += `<li class="page-item"><a class="page-link" href="#" data-page="${jobTypePageIndex + 1}">Next &raquo;</a></li>`;
        }

        paginationHtml += `</ul></nav>`;

        $('#jobTypePaginationControls').html(paginationHtml);
    }

    // Handle pagination clicks
    $(document).on('click', '.page-link', function(e) {
        e.preventDefault();
        let pageIndex = $(this).data('page');
        if (pageIndex !== jobTypePageIndex) {
            jobTypePageIndex = pageIndex;
            loadJobTypeData(jobTypePageIndex);
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
                loadJobTypeData(jobTypePageIndex);
                $('#addJobTypeForm')[0].reset();
            }
        });
    });

    // Show edit modal with data
    $(document).on('click', '.editJobTypeButton', function() {
        $('#editJobTypeIdInput').val($(this).data('id'));
        $('#editJobTypeTitleInput').val($(this).data('title'));
        $('#editJobTypeDescriptionInput').val($(this).data('description'));
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
                loadJobTypeData(jobTypePageIndex);
            }
        });
    });

    // Delete job type
    $(document).on('click', '.deleteJobTypeButton', function() {
        const jobTypeId = $(this).data('id');
        confirmDeleteJobType(jobTypeId);
    });

    // Confirm job type deletion
    function confirmDeleteJobType(jobTypeId) {
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
                $.ajax({
                    url: '../controllers/admin_jobtype_controller.php',
                    type: 'POST',
                    data: { action: 'delete', id: jobTypeId },
                    success: function(response) {
                        loadJobTypeData(jobTypePageIndex);
                        Swal.fire(
                            'Deleted!',
                            'The job type has been deleted.',
                            'success'
                        );
                    }
                });
            }
        });
    }
});



$(document).ready(function () {
    let currentSkillPage = 1; // Renamed counter
    let totalSkillPages = 1;  // Renamed counter
    const skillsPerPage = 5;  // Renamed limit

    // Fetch skills on tab activation
    $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        // Check if the activated tab is the skill management tab
        if ($(e.target).attr('href') === '#skillPanel') {
            // Fetch skills and update pagination when tab is shown
            fetchSkills(currentSkillPage);
            updateSkillPagination();
        }
    });

    // Fetch skills with pagination
    function fetchSkills(page = 1) {
        $.post("../controllers/admin_skill_controller.php", { action: "fetch", page: page, limit: skillsPerPage }, function (response) {
            let data = JSON.parse(response);
            let skills = data.skills;
            let skillListHtml = "";

            skills.forEach((skill) => {
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
            totalSkillPages = data.totalPages;
            updateSkillPagination();
        });
    }

    // Update pagination UI with limited page numbers
// Update pagination UI with limited page numbers for skill management
function updateSkillPagination() {
    const maxPagesToShow = 5; // Limit number of page numbers shown
    let skillStartPage = Math.max(1, currentSkillPage - Math.floor(maxPagesToShow / 2));
    let skillEndPage = Math.min(totalSkillPages, skillStartPage + maxPagesToShow - 1);

    let paginationHtml = `<nav><ul class="pagination justify-content-center">`;

    // Previous page link
    if (currentSkillPage > 1) {
        paginationHtml += `<li class="page-item"><a class="page-link" href="#" data-page="${currentSkillPage - 1}">&laquo; Previous</a></li>`;
    }

    // Page numbers (limit the range displayed)
    for (let i = skillStartPage; i <= skillEndPage; i++) {
        paginationHtml += `<li class="page-item ${i === currentSkillPage ? 'active' : ''}">
            <a class="page-link" href="#" data-page="${i}">${i}</a>
        </li>`;
    }

    // Next page link
    if (currentSkillPage < totalSkillPages) {
        paginationHtml += `<li class="page-item"><a class="page-link" href="#" data-page="${currentSkillPage + 1}">Next &raquo;</a></li>`;
    }

    paginationHtml += `</ul></nav>`;

    // Update pagination controls in the DOM
    $('#skillPaginationControls').html(paginationHtml);
}


    // Handle pagination clicks
    $(document).on("click", ".page-link", function (e) {
        e.preventDefault();
        let page = $(this).data("page");
        if (page !== currentSkillPage) {
            currentSkillPage = page;
            fetchSkills(currentSkillPage);
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
                fetchSkills(currentSkillPage);
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
            id: $("#editSkillId").val(),
            skill_name: $("#editSkillName").val(),
            category: $("#editSkillCategory").val(),
        };

        $.post("../controllers/admin_skill_controller.php", skillData, function (response) {
            if (response.trim() === "success") {
                alert("Skill updated successfully!");
                $("#editSkillModal").modal("hide");
                fetchSkills(currentSkillPage);
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
                fetchSkills(currentSkillPage);
            },
        });
    });
});




















// COURSE MANAGEMENT

$(document).ready(function () {
    let coursePageIndex = 1;
    let coursePageCount = 1;
    const coursesPerPageLimit = 5;

    loadCourseData(coursePageIndex);

    // Fetch Courses with Pagination
    function loadCourseData(pageIndex = 1) {
        $.post("../controllers/admin_course_controller.php", { action: "fetch", page: pageIndex, limit: coursesPerPageLimit }, function (response) {
            let responseData = JSON.parse(response);
            let courseEntries = responseData.courses;
            let courseTableContent = "";

            courseEntries.forEach((course) => {
                courseTableContent += `
                    <tr>
                        <td>${course.course_id}</td>
                        <td>${course.course_title}</td>
                        <td>${course.course_description}</td>
                        <td class="text-center">
                            <button class="btn btn-warning btn-sm editCourseButton" data-id="${course.course_id}" data-title="${course.course_title}" data-description="${course.course_description}">
                                <i class="bi bi-pencil"></i> Edit
                            </button>
                            <button class="btn btn-danger btn-sm deleteCourseButton" data-id="${course.course_id}">
                                <i class="bi bi-trash"></i> Delete
                            </button>
                        </td>
                    </tr>`;
            });

            $("#courseListContainer").html(courseTableContent);
            coursePageCount = responseData.totalPages;
            updateCoursePaginationControls();
        });
    }

    // Update Pagination UI
    function updateCoursePaginationControls() {
        let paginationHtml = `<nav><ul class="pagination justify-content-center">`;

        if (coursePageIndex > 1) {
            paginationHtml += `<li class="page-item"><a class="page-link" href="#" data-page="${coursePageIndex - 1}">&laquo; Previous</a></li>`;
        }

        for (let i = 1; i <= coursePageCount; i++) {
            paginationHtml += `<li class="page-item ${i === coursePageIndex ? 'active' : ''}">
                <a class="page-link" href="#" data-page="${i}">${i}</a>
            </li>`;
        }

        if (coursePageIndex < coursePageCount) {
            paginationHtml += `<li class="page-item"><a class="page-link" href="#" data-page="${coursePageIndex + 1}">Next &raquo;</a></li>`;
        }

        paginationHtml += `</ul></nav>`;
        $('#coursePaginationControlsContainer').html(paginationHtml);
    }

    // Handle Pagination Clicks
    $(document).on("click", ".page-link", function (e) {
        e.preventDefault();
        let pageIndex = $(this).data("page");
        if (pageIndex !== coursePageIndex) {
            coursePageIndex = pageIndex;
            loadCourseData(coursePageIndex);
        }
    });

    // Add Course
    $("#addCourseForm").submit(function (e) {
        e.preventDefault();
        let courseTitle = $("#courseTitleInput").val();
        let courseDescription = $("#courseDescriptionInput").val();

        $.post("../controllers/admin_course_controller.php", { action: "add", course_title: courseTitle, course_description: courseDescription }, function (response) {
            if (response.trim() === "success") {
                alert("Course added successfully!");
                $("#addCourseModal").modal("hide");
                loadCourseData(coursePageIndex);
                $("#addCourseForm")[0].reset();
            } else {
                alert("Error adding course.");
            }
        });
    });

    // Show Edit Modal
    $(document).on("click", ".editCourseButton", function () {
        $("#editCourseIdInput").val($(this).data("id"));
        $("#editCourseTitleInput").val($(this).data("title"));
        $("#editCourseDescriptionInput").val($(this).data("description"));
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
                course_id: $("#editCourseIdInput").val(),
                course_title: $("#editCourseTitleInput").val(),
                course_description: $("#editCourseDescriptionInput").val()
            },
            success: function (response) {
                $("#editCourseModal").modal("hide");
                loadCourseData(coursePageIndex);
            },
        });
    });

    // Delete Course
    $(document).on("click", ".deleteCourseButton", function () {
        if (!confirm("Are you sure you want to delete this course?")) return;
        $.ajax({
            url: "../controllers/admin_course_controller.php",
            type: "POST",
            data: { action: "delete", course_id: $(this).data("id") },
            success: function (response) {
                loadCourseData(coursePageIndex);
            },
        });
    });
});





$(document).ready(function () {
    let rolePageIndex = 1;
    let rolePageCount = 1;
    const rolesPerPageLimit = 5;

    loadRolePage(rolePageIndex);

    function loadRolePage(pageIndex = 1) {
        $.post("../controllers/admin_role_controller.php", { action: "fetch", page: pageIndex, limit: rolesPerPageLimit }, function (response) {
            let responseData = JSON.parse(response);
            let roleEntries = responseData.roles;
            let roleTableContent = "";

            roleEntries.forEach(role => {
                roleTableContent += `
                    <tr>
                        <td>${role.role_id}</td>
                        <td>${role.role_title}</td>
                        <td>${role.role_description}</td>
                        <td class="text-center">
                            <button class="btn btn-warning btn-sm editRoleButton" data-id="${role.role_id}" data-title="${role.role_title}" data-description="${role.role_description}">
                                <i class="bi bi-pencil"></i> Edit
                            </button>
                            <button class="btn btn-danger btn-sm deleteRoleButton" data-id="${role.role_id}">
                                <i class="bi bi-trash"></i> Delete
                            </button>
                        </td>
                    </tr>`;
            });

            $("#roleListContainer").html(roleTableContent);
            rolePageCount = responseData.totalPages;
            updateRolePaginationControls();
        });
    }

    function updateRolePaginationControls() {
        let paginationControls = `<nav><ul class="pagination justify-content-center">`;

        if (rolePageIndex > 1) {
            paginationControls += `<li class="page-item"><a class="page-link" href="#" data-page="${rolePageIndex - 1}">&laquo; Previous</a></li>`;
        }

        for (let i = 1; i <= rolePageCount; i++) {
            paginationControls += `<li class="page-item ${i === rolePageIndex ? 'active' : ''}">
                <a class="page-link" href="#" data-page="${i}">${i}</a>
            </li>`;
        }

        if (rolePageIndex < rolePageCount) {
            paginationControls += `<li class="page-item"><a class="page-link" href="#" data-page="${rolePageIndex + 1}">Next &raquo;</a></li>`;
        }

        paginationControls += `</ul></nav>`;
        $('#rolePaginationControlsContainer').html(paginationControls);
    }

    $(document).on("click", ".page-link", function (e) {
        e.preventDefault();
        let pageIndex = $(this).data("page");
        if (pageIndex !== rolePageIndex) {
            rolePageIndex = pageIndex;
            loadRolePage(rolePageIndex);
        }
    });

    $("#addRoleForm").submit(function (e) {
        e.preventDefault();
        let roleTitle = $("#roleTitleInput").val();
        let roleDescription = $("#roleDescriptionInput").val();

        $.post("../controllers/admin_role_controller.php", { action: "add", role_title: roleTitle, role_description: roleDescription }, function (response) {
            if (response.trim() === "success") {
                alert("Role added successfully!");
                $("#addRoleModal").modal("hide");
                loadRolePage(rolePageIndex);
                $("#addRoleForm")[0].reset();
            } else {
                alert("Error adding role.");
            }
        });
    });

    $(document).on("click", ".editRoleButton", function () {
        $("#editRoleIdInput").val($(this).data("id"));
        $("#editRoleTitleInput").val($(this).data("title"));
        $("#editRoleDescriptionInput").val($(this).data("description"));
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
                loadRolePage(rolePageIndex);
            },
        });
    });

    $(document).on("click", ".deleteRoleButton", function () {
        if (!confirm("Are you sure you want to delete this role?")) return;
        $.ajax({
            url: "../controllers/admin_role_controller.php",
            type: "POST",
            data: { action: "delete", id: $(this).data("id") },
            success: function (response) {
                loadRolePage(rolePageIndex);
            },
        });
    });
});
