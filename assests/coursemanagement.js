// COURSE MANAGEMENT
$(document).ready(function () {
    loadCourseData();

    // Fetch Courses
    function loadCourseData() {
        $.post("../controllers/admin_course_controller.php", { action: "fetch" }, function (response) {
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
        });
    }

    // Add Course
    $("#addCourseForm").submit(function (e) {
        e.preventDefault();
        let courseTitle = $("#courseTitle").val();
        let courseDescription = $("#courseDescription").val();

        $.post("../controllers/admin_course_controller.php", { action: "add", course_title: courseTitle, course_description: courseDescription }, function (response) {
            if (response.trim() === "success") {
                Swal.fire("Success!", "Course added successfully!", "success");
                $("#addCourseModal").modal("hide");
                loadCourseData();
                $("#addCourseForm")[0].reset();
            } else {
                Swal.fire("Error!", "Error adding course.", "error");
            }
        }).fail(function () {
            Swal.fire("Error!", "Failed to communicate with the server.", "error");
        });
    });

    // Show Edit Modal
    $(document).on("click", ".editCourseButton", function () {
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
                if (response.trim() === "success") {
                    Swal.fire("Success!", "Course updated successfully!", "success");
                    $("#editCourseModal").modal("hide");
                    loadCourseData();
                } else {
                    Swal.fire("Error!", "Failed to update course.", "error");
                }
            },
            error: function () {
                Swal.fire("Error!", "An error occurred while updating the course.", "error");
            }
        });
    });

    // Delete Course with SweetAlert confirmation
    $(document).on("click", ".deleteCourseButton", function () {
        const courseId = $(this).data("id");

        Swal.fire({
            title: 'Are you sure?',
            text: "This will permanently delete the course.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "../controllers/admin_course_controller.php",
                    type: "POST",
                    data: { action: "delete", course_id: courseId },
                    success: function (response) {
                        if (response.trim() === "success") {
                            Swal.fire("Deleted!", "Course has been deleted.", "success");
                            loadCourseData();
                        } else {
                            Swal.fire("Error!", "Failed to delete course.", "error");
                        }
                    },
                    error: function () {
                        Swal.fire("Error!", "An error occurred while deleting the course.", "error");
                    }
                });
            }
        });
    });
});
