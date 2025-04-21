// ROLE MANAGEMENT
$(document).ready(function () {
    loadRoleData();

    function loadRoleData() {
        $.post("../controllers/admin_role_controller.php", { action: "fetch" }, function (response) {
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
        });
    }

    // Add Role
    $("#addRoleForm").submit(function (e) {
        e.preventDefault();
        let roleTitle = $("#roleTitle").val();
        let roleDescription = $("#roleDescription").val();

        $.post("../controllers/admin_role_controller.php", {
            action: "add",
            role_title: roleTitle,
            role_description: roleDescription
        }, function (response) {
            if (response.trim() === "success") {
                Swal.fire("Success!", "Role added successfully!", "success");
                $("#addRoleModal").modal("hide");
                loadRoleData();
                $("#addRoleForm")[0].reset();
            } else {
                Swal.fire("Error!", "Error adding role.", "error");
            }
        }).fail(function () {
            Swal.fire("Error!", "Failed to communicate with the server.", "error");
        });
    });

    // Show Edit Modal
    $(document).on("click", ".editRoleButton", function () {
        $("#editRoleId").val($(this).data("id"));
        $("#editRoleTitle").val($(this).data("title"));
        $("#editRoleDescription").val($(this).data("description"));
        $("#editRoleModal").modal("show");
    });

    // Edit Role
    $("#editRoleForm").submit(function (e) {
        e.preventDefault();
        $.ajax({
            url: "../controllers/admin_role_controller.php",
            type: "POST",
            data: $(this).serialize() + "&action=edit",
            success: function (response) {
                if (response.trim() === "success") {
                    Swal.fire("Success!", "Role updated successfully!", "success");
                    $("#editRoleModal").modal("hide");
                    loadRoleData();
                } else {
                    Swal.fire("Error!", "Failed to update role.", "error");
                }
            },
            error: function () {
                Swal.fire("Error!", "An error occurred while updating the role.", "error");
            }
        });
    });

    // Delete Role with SweetAlert confirmation
    $(document).on("click", ".deleteRoleButton", function () {
        const roleId = $(this).data("id");

        Swal.fire({
            title: 'Are you sure?',
            text: "This will permanently delete the role.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "../controllers/admin_role_controller.php",
                    type: "POST",
                    data: { action: "delete", id: roleId },
                    success: function (response) {
                        if (response.trim() === "success") {
                            Swal.fire("Deleted!", "Role has been deleted.", "success");
                            loadRoleData();
                        } else {
                            Swal.fire("Error!", "Failed to delete role.", "error");
                        }
                    },
                    error: function () {
                        Swal.fire("Error!", "An error occurred while deleting the role.", "error");
                    }
                });
            }
        });
    });
});
