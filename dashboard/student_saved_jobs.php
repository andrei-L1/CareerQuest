    <?php 
    include '../includes/stud_navbar.php';
    ?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Saved Jobs</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <style>
            body {
                background-color: #f8f9fa;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            }

            h1 i {
                color: #0d6efd;
            }

            #searchInput {
                max-width: 250px;
            }

            .dropdown-menu {
                min-width: 200px;
            }

            .table-hover tbody tr:hover {
                background-color: #f1f1f1;
                cursor: pointer;
            }

            .truncate-text {
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                max-width: 300px;
                display: block;
            }

            .btn-outline-primary,
            .btn-outline-danger {
                transition: all 0.3s ease;
            }

            .btn-outline-primary:hover {
                background-color: #0d6efd;
                color: #fff;
            }

            .btn-outline-danger:hover {
                background-color: #dc3545;
                color: #fff;
            }

            .pagination .page-link {
                color: #0d6efd;
            }

            .pagination .page-item.active .page-link {
                background-color: #0d6efd;
                border-color: #0d6efd;
                color: white;
            }

            .toast {
                background-color: #28a745;
                color: white;
            }

            /* Improve modal look */
            .modal-content {
                border-radius: 0.75rem;
                box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            }

            /* Responsive adjustments */
            @media (max-width: 768px) {
                .d-flex.justify-content-between.align-items-center.mb-4 {
                    flex-direction: column;
                    align-items: flex-start !important;
                    gap: 1rem;
                }

                .d-flex > * {
                    width: 100%;
                }

                #searchInput {
                    width: 100%;
                }

                .dropdown, #orderToggle {
                    width: 100%;
                }
            }
        </style>

    </head>
    <body>
        <div class="container mt-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="fas fa-bookmark me-2"></i>Your Saved Jobs</h1>
                <div class="d-flex">
                    <input type="text" id="searchInput" class="form-control me-2" placeholder="Search saved jobs...">

                        <div class="dropdown">  <!-- Add this wrapper div -->
                            <button id="sortBtn" class="btn btn-outline-secondary dropdown-toggle" type="button" 
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-sort me-1"></i>Sort By
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item sort-option" href="#" data-sort="saved_at">Recently Saved</a></li>
                                <li><a class="dropdown-item sort-option" href="#" data-sort="posted_at">Newest Jobs</a></li>
                                <li><a class="dropdown-item sort-option" href="#" data-sort="salary">Highest Salary</a></li>
                                <li><a class="dropdown-item sort-option" href="#" data-sort="title">Job Title (A-Z)</a></li>
                                <li><a class="dropdown-item sort-option" href="#" data-sort="company_name">Company Name</a></li>
                            </ul>
                        </div>  <!-- Close the wrapper div -->

                    <button id="orderToggle" class="btn btn-outline-secondary ms-2" title="Toggle sort order">
                        <i class="fas fa-sort-amount-down"></i> 
                    </button>

                </div>
            </div>

            <div id="emptyState" style="display:none;">
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i>You haven't saved any jobs yet. Start browsing jobs to save them for later.
                    <div class="mt-3">
                        <a href="job_listings.php" class="btn btn-primary">Browse Jobs</a>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table id="savedJobsTable" class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Job Title</th>
                            <th>Company</th>
                            <th>Location</th>
                            <th>Salary</th>
                            <th>Posted</th>
                            <th>Saved</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Jobs will be loaded here via AJAX -->
                    </tbody>
                </table>
            </div>
            
            <div class="d-flex justify-content-center mt-4">
                <nav aria-label="Saved jobs pagination">
                    <ul class="pagination">
                        <!-- Pagination will be loaded here via AJAX -->
                    </ul>
                </nav>
            </div>
        </div>

        <!-- Confirmation Modal -->
        <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirm Removal</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        Are you sure you want to remove this job from your saved list?
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Remove</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Success Toast -->
        <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
            <div id="successToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header bg-success text-white">
                    <strong class="me-auto">Success</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">
                    Job removed successfully.
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            // Global variables
            let currentPage = 1;
            const perPage = 10;
            let currentSort = 'saved_at';
            let currentOrder = 'DESC';
            let currentSearch = '';
            let currentJobId, currentStudentId;

            $(document).ready(function() {
                var dropdowns = document.querySelectorAll('.dropdown-toggle');
                dropdowns.forEach(function(dropdownToggleEl) {
                    new bootstrap.Dropdown(dropdownToggleEl);
                });

                $('.dropdown-menu').on('click', '.sort-option', function(e) {
                    e.preventDefault();
                    console.log('Sort option clicked');
                    
                    currentSort = $(this).data('sort');
                    console.log('Sorting by:', currentSort);
                    
                    $('#sortBtn').html(`<i class="fas fa-sort me-1"></i>${$(this).text()}`);
                    currentPage = 1;
                    fetchSavedJobs();
                    
                    // Close the dropdown manually
                    bootstrap.Dropdown.getInstance($('#sortBtn')[0]).hide();
                });



                // Initialize tooltips
                $('[data-bs-toggle="tooltip"]').tooltip();
                
                // Load saved jobs on page load
                fetchSavedJobs();
                
                // Delete button click handler
                $(document).on('click', '.deleteBtn', function() {
                    currentJobId = $(this).data('job-id');
                    currentStudentId = $(this).data('stud-id');
                    $('#confirmDeleteModal').modal('show');
                });
                
                // Confirm delete button handler
                $('#confirmDeleteBtn').click(function() {
                    $('#confirmDeleteModal').modal('hide');
                    deleteSavedJob();
                });
                
                // Search functionality
                $('#searchInput').on('keyup', function() {
                    currentSearch = $(this).val();
                    currentPage = 1;
                    $('#savedJobsTable').addClass('loading'); // Add CSS opacity
                    fetchSavedJobs();
                });
                                
                // Sort functionality
                $('.sort-option').click(function(e) {
                    e.preventDefault();
                    console.log('Sort option clicked'); // Debug line
                    
                    // Get the selected sort parameter
                    currentSort = $(this).data('sort');
                    console.log('Sorting by:', currentSort); // Debug line
                    
                    // Update the button text to reflect the chosen sort option
                    $('#sortBtn').html(`<i class="fas fa-sort me-1"></i>${$(this).text()}`);
                    
                    // Reset to the first page when sorting
                    currentPage = 1;

                    // Fetch the jobs with the new sort order
                    fetchSavedJobs();
                });



                $('#orderToggle').click(function() {
                    currentOrder = (currentOrder === 'DESC') ? 'ASC' : 'DESC';
                    $(this).find('i')
                        .toggleClass('fa-sort-amount-down fa-sort-amount-up')
                        .attr('title', currentOrder === 'DESC' ? 'Descending' : 'Ascending');
                    fetchSavedJobs();
                });
                
            });

            // Function to fetch saved jobs with pagination and sorting
            function fetchSavedJobs() {

                console.log('Fetching with params:', {
    page: currentPage,
    per_page: perPage,
    sort: currentSort,
    order: currentOrder,
    search: currentSearch
});
                $.ajax({
                    url: '../controllers/saved_jobs.php',
                    method: 'GET',
                    data: {
                        page: currentPage,
                        per_page: perPage,
                        sort: currentSort,
                        order: currentOrder,
                        search: currentSearch
                    },
                    dataType: 'json',
                    beforeSend: function() {
                        // Show loading indicator
                        $('#savedJobsTable tbody').html('<tr><td colspan="7" class="text-center"><div class="spinner-border text-primary" role="status"></div></td></tr>');
                        $('.pagination').html('');
                    },
                    success: function(response) {
                        if (response.success) {
                            renderJobs(response.data.jobs);
                            updatePagination(response.data.pagination);
                        } else {
                            showEmptyState();
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message || 'Failed to load saved jobs'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        showEmptyState();
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'An error occurred while loading saved jobs. Please try again.'
                        });
                        console.error(error);
                    }
                });
            }

            // Function to delete a saved job
            function deleteSavedJob() {
                $.ajax({
                    url: '../controllers/saved_jobs.php',
                    method: 'POST',
                    data: { 
                        job_id: currentJobId, 
                        student_id: currentStudentId 
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Show success toast
                            const toast = new bootstrap.Toast(document.getElementById('successToast'));
                            toast.show();
                            
                            // Refresh the job list
                            fetchSavedJobs();
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'An error occurred while removing the job. Please try again.'
                        });
                        console.error(error);
                    }
                });
            }

            // Function to render jobs in the table
            function renderJobs(jobs) {
                const tbody = $('#savedJobsTable tbody');
                tbody.empty();
                
                if (jobs.length === 0) {
                    showEmptyState();
                    return;
                } else {
                    hideEmptyState();
                }
                
                jobs.forEach(job => {
                    const postedAgo = formatTimeDiff(new Date(job.posted_at));
                    const savedAgo = formatTimeDiff(new Date(job.saved_at));
                    
                    const row = `
                        <tr id="job-${job.job_id}">
                            <td>
                                <a href="student_job.php?id=${job.job_id}" class="text-decoration-none">
                                    <strong>${escapeHtml(job.title)}</strong>
                                </a>
                                <div class="text-muted small truncate-text" data-bs-toggle="tooltip" title="${escapeHtml(job.description)}">
                                    ${escapeHtml(job.description.substring(0, 50))}...
                                </div>
                            </td>
                            <td>${escapeHtml(job.company_name)}</td>
                            <td>${escapeHtml(job.location)}</td>
                            <td>
                                ${job.salary ? '$' + Number(job.salary).toLocaleString() : '<span class="text-muted">Not specified</span>'}
                            </td>
                            <td><small class="text-muted" title="${job.posted_at}">${postedAgo}</small></td>
                            <td><small class="text-muted" title="${job.saved_at}">${savedAgo}</small></td>
                            <td>
                                <div class="d-flex gap-2">
                                    <a href="student_job.php?id=${job.job_id}" class="btn btn-sm btn-outline-primary" title="View Job">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <button class="btn btn-sm btn-outline-danger deleteBtn" 
                                            data-job-id="${job.job_id}" 
                                            data-stud-id="<?php echo $_SESSION['stud_id']; ?>"
                                            title="Remove from saved">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `;
                    tbody.append(row);
                });
                
                // Reinitialize tooltips for new elements
                $('[data-bs-toggle="tooltip"]').tooltip();
            }

            // Function to update pagination controls
            function updatePagination(pagination) {
                const paginationEl = $('.pagination');
                paginationEl.empty();
                
                if (pagination.total <= 0) return;
                
                // Previous button
                const prevClass = currentPage <= 1 ? 'disabled' : '';
                paginationEl.append(`
                    <li class="page-item ${prevClass}">
                        <a class="page-link prev-page" href="#" tabindex="-1">Previous</a>
                    </li>
                `);
                
                // Page numbers
                const totalPages = pagination.total_pages;
                const maxVisiblePages = 5;
                let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
                let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
                
                if (endPage - startPage + 1 < maxVisiblePages) {
                    startPage = Math.max(1, endPage - maxVisiblePages + 1);
                }
                
                if (startPage > 1) {
                    paginationEl.append('<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>');
                    if (startPage > 2) {
                        paginationEl.append('<li class="page-item disabled"><span class="page-link">...</span></li>');
                    }
                }
                
                for (let i = startPage; i <= endPage; i++) {
                    const activeClass = i === currentPage ? 'active' : '';
                    paginationEl.append(`
                        <li class="page-item ${activeClass}">
                            <a class="page-link page-number" href="#" data-page="${i}">${i}</a>
                        </li>
                    `);
                }
                
                if (endPage < totalPages) {
                    if (endPage < totalPages - 1) {
                        paginationEl.append('<li class="page-item disabled"><span class="page-link">...</span></li>');
                    }
                    paginationEl.append(`<li class="page-item"><a class="page-link" href="#" data-page="${totalPages}">${totalPages}</a></li>`);
                }
                
                // Next button
                const nextClass = currentPage >= totalPages ? 'disabled' : '';
                paginationEl.append(`
                    <li class="page-item ${nextClass}">
                        <a class="page-link next-page" href="#">Next</a>
                    </li>
                `);
                
                // Add click handlers
                $('.page-number').click(function(e) {
                    e.preventDefault();
                    currentPage = parseInt($(this).data('page'));
                    fetchSavedJobs();
                });

                $('.prev-page').click(function(e) {
                    e.preventDefault();
                    if (currentPage > 1) {
                        currentPage--;
                        fetchSavedJobs();
                    }
                });
                
                $('.next-page').click(function(e) {
                    e.preventDefault();
                    if (currentPage < totalPages) {
                        currentPage++;
                        fetchSavedJobs();
                    }
                });
            }

            // Function to show empty state
            function showEmptyState() {
                $('#emptyState').show();
                $('#savedJobsTable').hide();
                $('.pagination').hide();
            }

            // Function to hide empty state
            function hideEmptyState() {
                $('#emptyState').hide();
                $('#savedJobsTable').show();
                $('.pagination').show();
            }

            // Helper function to escape HTML
            function escapeHtml(unsafe) {
                return unsafe
                    .toString()
                    .replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
                    .replace(/"/g, "&quot;")
                    .replace(/'/g, "&#039;");
            }

            // Helper function to format time difference
            function formatTimeDiff(date) {
                const now = new Date();
                const diff = now - date;
                
                const seconds = Math.floor(diff / 1000);
                const minutes = Math.floor(seconds / 60);
                const hours = Math.floor(minutes / 60);
                const days = Math.floor(hours / 24);
                const months = Math.floor(days / 30);
                const years = Math.floor(months / 12);
                
                if (years > 0) return years + " year" + (years > 1 ? "s" : "") + " ago";
                if (months > 0) return months + " month" + (months > 1 ? "s" : "") + " ago";
                if (days > 0) return days + " day" + (days > 1 ? "s" : "") + " ago";
                if (hours > 0) return hours + " hour" + (hours > 1 ? "s" : "") + " ago";
                if (minutes > 0) return minutes + " minute" + (minutes > 1 ? "s" : "") + " ago";
                return "Just now";
            }
        </script>
    </body>
    </html>