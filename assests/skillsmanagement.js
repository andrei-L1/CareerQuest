// SKILL MANAGEMENT
$(document).ready(function () {
    loadSkillData();

    // Load Skills
    function loadSkillData() {
        $.ajax({
            url: "../controllers/admin_skill_controller.php",
            type: "POST",
            data: { action: "fetch" },
            success: function (response) {
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
                                <button class="btn btn-warning btn-sm editSkillBtn" data-id="${skill.skill_id}" data-name="${skill.skill_name}" data-category="${skill.category || ''}">
                                    <i class="bi bi-pencil"></i> Edit
                                </button>
                                <button class="btn btn-danger btn-sm deleteSkillBtn" data-id="${skill.skill_id}">
                                    <i class="bi bi-trash"></i> Delete
                                </button>
                            </td>
                        </tr>`;
                });

                $("#skillList").html(skillListHtml);
            },
            error: function () {
                Swal.fire("Error!", "Failed to load skills.", "error");
            }
        });
    }

    // Add Skill
    $("#addSkillForm").submit(function (e) {
        e.preventDefault();
        $.ajax({
            url: "../controllers/admin_skill_controller.php",
            type: "POST",
            data: $(this).serialize() + "&action=add",
            success: function (response) {
                if (response.trim() === "success") {
                    $("#addSkillModal").modal("hide");
                    loadSkillData();
                    $("#addSkillForm")[0].reset();
                    Swal.fire("Success!", "Skill added successfully.", "success");
                } else {
                    Swal.fire("Error!", "Failed to add skill.", "error");
                }
            },
            error: function () {
                Swal.fire("Error!", "An error occurred while adding the skill.", "error");
            }
        });
    });

    // Show Edit Modal
    $(document).on("click", ".editSkillBtn", function () {
        $("#editSkillId").val($(this).data("id"));
        $("#editSkillName").val($(this).data("name"));
        $("#editSkillCategory").val($(this).data("category"));
        $("#editSkillModal").modal("show");
    });

    // Edit Skill
    $("#editSkillForm").submit(function (e) {
        e.preventDefault();
        $.ajax({
            url: "../controllers/admin_skill_controller.php",
            type: "POST",
            data: $(this).serialize() + "&action=edit",
            success: function (response) {
                if (response.trim() === "success") {
                    $("#editSkillModal").modal("hide");
                    loadSkillData();
                    Swal.fire("Success!", "Skill updated successfully.", "success");
                } else {
                    Swal.fire("Error!", "Failed to update skill.", "error");
                }
            },
            error: function () {
                Swal.fire("Error!", "An error occurred while updating the skill.", "error");
            }
        });
    });

    // Delete Skill
    $(document).on("click", ".deleteSkillBtn", function () {
        const skillId = $(this).data("id");
        confirmDeleteSkill(skillId);
    });

    // Confirm Delete Skill
    function confirmDeleteSkill(skillId) {
        Swal.fire({
            title: 'Are you sure?',
            text: "This action cannot be undone!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "../controllers/admin_skill_controller.php",
                    type: "POST",
                    data: { action: "delete", id: skillId },
                    success: function (response) {
                        loadSkillData();
                        Swal.fire("Deleted!", "Skill has been deleted.", "success");
                    },
                    error: function () {
                        Swal.fire("Error!", "Failed to delete skill.", "error");
                    }
                });
            }
        });
    }
});
