// JOB TYPE MANAGEMENT
$(document).ready(function() {
    loadJobTypeData();

    // Load job types
    function loadJobTypeData() {
        $.ajax({
            url: '../controllers/admin_jobtype_controller.php',
            type: 'POST',
            data: { action: 'fetch' },
            dataType: 'json',
            success: function(response) {
                let rowsHtml = "";

                response.jobTypes.forEach(function(jobType) {
                    rowsHtml += `
                        <tr>
                            <td>${jobType.job_type_id}</td>
                            <td>${jobType.job_type_title}</td>
                            <td>${jobType.job_type_description || 'N/A'}</td>
                            <td class="text-center">
                                <button class="btn btn-warning btn-sm editJobTypeButton" data-id="${jobType.job_type_id}" data-title="${jobType.job_type_title}" data-description="${jobType.job_type_description || ''}">
                                    <i class="bi bi-pencil"></i> Edit
                                </button>
                                <button class="btn btn-danger btn-sm deleteJobTypeButton" data-id="${jobType.job_type_id}">
                                    <i class="bi bi-trash"></i> Delete
                                </button>
                            </td>
                        </tr>
                    `;
                });

                $('#jobTypeListContainer').html(rowsHtml);
            }
        });
    }

    // Add job type
    $('#addJobTypeForm').submit(function(e) {
        e.preventDefault();
        $.ajax({
            url: '../controllers/admin_jobtype_controller.php',
            type: 'POST',
            data: $(this).serialize() + '&action=add',
            success: function(response) {
                $('#addJobTypeModal').modal('hide');
                loadJobTypeData();
                $('#addJobTypeForm')[0].reset();
                Swal.fire('Success!', 'Job type added successfully.', 'success');
            },
            error: function() {
                Swal.fire('Error!', 'Failed to add job type.', 'error');
            }
        });
    });

    // Show edit modal with data
    $(document).on('click', '.editJobTypeButton', function() {
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
                loadJobTypeData();
                Swal.fire('Success!', 'Job type updated successfully.', 'success');
            },
            error: function() {
                Swal.fire('Error!', 'Failed to update job type.', 'error');
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
                        loadJobTypeData();
                        Swal.fire(
                            'Deleted!',
                            'The job type has been deleted.',
                            'success'
                        );
                    },
                    error: function() {
                        Swal.fire(
                            'Error!',
                            'Failed to delete job type.',
                            'error'
                        );
                    }
                });
            }
        });
    }
});


