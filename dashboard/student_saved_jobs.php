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

        /* Card styles */
        .job-card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            margin-bottom: 20px;
            border: none;
            overflow: hidden;
        }

        .job-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1.25rem;
        }

        .card-title {
            font-weight: 600;
            color: #212529;
            margin-bottom: 0.5rem;
        }

        .card-subtitle {
            color: #6c757d;
            font-weight: 500;
        }

        .card-body {
            padding: 1.25rem;
        }

        .job-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 1rem;
        }

        .job-meta-item {
            display: flex;
            align-items: center;
            color: #495057;
        }

        .job-meta-item i {
            margin-right: 8px;
            color: #6c757d;
            width: 18px;
            text-align: center;
        }

        .job-description {
            color: #495057;
            line-height: 1.5;
            display: -webkit-box;
            --webkit-line-clamp: 3;
            --webkit-box-orient: vertical;
            overflow: hidden;
            margin-bottom: 1.25rem;
        }

        .card-footer {
            background-color: #f8f9fa;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1rem 1.25rem;
        }

        .time-badge {
            font-size: 0.8rem;
            background-color: #e9ecef;
            color: #495057;
            padding: 4px 8px;
            border-radius: 4px;
            display: inline-flex;
            align-items: center;
        }

        .time-badge i {
            margin-right: 4px;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
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
            
            .job-meta {
                flex-direction: column;
                gap: 8px;
            }
        }

        /* Loading state */
        .loading-placeholder {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .loading-card {
            height: 180px;
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
            border-radius: 10px;
        }

        @keyframes loading {
            0% {
                background-position: 200% 0;
            }
            100% {
                background-position: -200% 0;
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

                <div class="dropdown">
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
                </div>

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

        <div id="jobsContainer" class="row">
            <!-- Jobs will be loaded here via AJAX -->
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
                $('#jobsContainer').addClass('loading');
                fetchSavedJobs();
            });
                            
            // Sort functionality
            $('.sort-option').click(function(e) {
                e.preventDefault();
                console.log('Sort option clicked');
                
                currentSort = $(this).data('sort');
                console.log('Sorting by:', currentSort);
                
                $('#sortBtn').html(`<i class="fas fa-sort me-1"></i>${$(this).text()}`);
                currentPage = 1;
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
                    $('#jobsContainer').html(`
                        <div class="loading-placeholder">
                            <div class="loading-card"></div>
                            <div class="loading-card"></div>
                            <div class="loading-card"></div>
                        </div>
                    `);
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

        // Function to render jobs as cards
        function renderJobs(jobs) {
            const container = $('#jobsContainer');
            container.empty();
            
            if (jobs.length === 0) {
                showEmptyState();
                return;
            } else {
                hideEmptyState();
            }
            
            jobs.forEach(job => {
                const postedAgo = formatTimeDiff(new Date(job.posted_at));
                const savedAgo = formatTimeDiff(new Date(job.saved_at));
                const salaryDisplay = job.salary ? '$' + Number(job.salary).toLocaleString() : 'Not specified';
                
                const card = `
                    <div class="col-md-6 col-lg-4 mb-4" id="job-${job.job_id}">
                        <div class="card job-card h-100">
                            <div class="card-header">
                                <h5 class="card-title mb-1">
                                    <a href="student_job.php?id=${job.job_id}" class="text-decoration-none text-dark">
                                        ${escapeHtml(job.title)}
                                    </a>
                                </h5>
                                <h6 class="card-subtitle text-muted">
                                    <i class="fas fa-building me-1"></i>${escapeHtml(job.company_name)}
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="job-meta">
                                    <div class="job-meta-item">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span>${escapeHtml(job.location)}</span>
                                    </div>
                                    <div class="job-meta-item">
                                        <i class="fas fa-money-bill-wave"></i>
                                        <span>${salaryDisplay}</span>
                                    </div>
                                </div>
                                <p class="job-description">${escapeHtml(job.description.substring(0, 200))}...</p>
                            </div>
                            <div class="card-footer bg-transparent">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="time-badge me-2" title="Posted ${job.posted_at}">
                                            <i class="far fa-clock"></i> ${postedAgo}
                                        </span>
                                        <span class="time-badge" title="Saved ${job.saved_at}">
                                            <i class="fas fa-bookmark"></i> ${savedAgo}
                                        </span>
                                    </div>
                                    <div class="action-buttons">
                                        <a href="student_job.php?id=${job.job_id}#select" class="btn btn-sm btn-outline-primary" title="View Job">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <button class="btn btn-sm btn-outline-danger deleteBtn" 
                                                data-job-id="${job.job_id}" 
                                                data-stud-id="<?php echo $_SESSION['stud_id']; ?>"
                                                title="Remove from saved">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                container.append(card);
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
            $('#jobsContainer').hide();
            $('.pagination').hide();
        }

        // Function to hide empty state
        function hideEmptyState() {
            $('#emptyState').hide();
            $('#jobsContainer').show();
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
            
            if (years > 0) return years + "y";
            if (months > 0) return months + "mo";
            if (days > 0) return days + "d";
            if (hours > 0) return hours + "h";
            if (minutes > 0) return minutes + "m";
            return "Just now";
        }
    </script>
</body>
</html>