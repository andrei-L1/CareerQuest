document.addEventListener("DOMContentLoaded", () => fetchJobs("all"));

function filterJobs(status, clickedButton) {
    try {
        document.querySelectorAll(".btn-group .btn").forEach(btn => btn.classList.remove("active"));
        clickedButton.classList.add("active");
        fetchJobs(status);
    } catch (error) {
        console.error("Error updating filter UI:", error);
    }
}

async function fetchJobs(filterStatus = "all") {
    const jobsContainer = document.getElementById("jobsContainer");
    jobsContainer.innerHTML = `<div class="text-center"><div class="spinner-border text-primary"></div></div>`;

    try {
        const response = await fetch(`../controllers/job_moderation.php?status=${encodeURIComponent(filterStatus)}`);
        if (!response.ok) throw new Error(`Server error: ${response.status}`);

        const jobs = await response.json();
        if (jobs.error) throw new Error(jobs.error);

        const filteredJobs = filterStatus === "all" ? jobs : jobs.filter(job => job.moderation_status?.toLowerCase() === filterStatus);
        displayJobs(filteredJobs);
    } catch (error) {
        jobsContainer.innerHTML = `<p class='text-center text-danger'>Failed to load jobs. ${error.message}</p>`;
        console.error("Error fetching jobs:", error);
    }
}

function displayJobs(jobs) {
    const jobsContainer = document.getElementById("jobsContainer");
    jobsContainer.innerHTML = "";

    if (!Array.isArray(jobs) || jobs.length === 0) {
        jobsContainer.innerHTML = `<p class='text-center text-muted'>No job postings available for moderation.</p>`;
        return;
    }

    console.log("Jobs received:", jobs); // Debug: Log the jobs data

    jobs.forEach(job => {
        const jobCard = document.createElement("div");
        jobCard.classList.add("col-md-4", "mb-4");

        const title = job.title || "Untitled Job";
        const description = job.description || "No description available";
        const location = job.location || "Not provided";
        const status = job.moderation_status || "Pending";
        const companyName = job.company_name || "Unknown Employer";
        const employerJobTitle = job.employer_job_title || "N/A";
        const jobType = job.job_type_title || "Not specified";
        const salary = job.salary_disclosure && job.min_salary && job.max_salary 
            ? `${job.min_salary} - ${job.max_salary} ${job.salary_type}` 
            : job.salary_type === 'Negotiable' ? 'Negotiable' : 'Not disclosed';
        const postedAt = job.posted_at ? new Date(job.posted_at).toLocaleDateString() : "Unknown";
        const expiresAt = job.expires_at ? new Date(job.expires_at).toLocaleDateString() : "Not set";
        const skills = Array.isArray(job.skills) && job.skills.length > 0 
            ? job.skills.slice(0, 3).map(skill => skill.skill_name).join(", ") + (job.skills.length > 3 ? "..." : "")
            : "No skills listed";
        const companyLogo = job.company_logo 
            ? `../Uploads/${job.company_logo}` 
            : "https://placehold.co/50x50?text=Logo";

        console.log(`Job ID ${job.job_id}:`, { companyName, companyLogo }); // Debug: Log company details

        jobCard.innerHTML = `
            <div class="card shadow-lg animate__animated animate__fadeIn" style="min-height: 320px;">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <img src="${companyLogo}" onerror="this.src='https://placehold.co/50x50?text=Logo'" alt="Company Logo" class="me-2" style="width: 40px; height: 40px; border-radius: 50%;">
                        <div>
                            <h5 class="card-title mb-0">${title}</h5>
                            <small class="text-muted">${companyName} - ${employerJobTitle}</small>
                        </div>
                    </div>
                    <p class="card-text text-muted">${description.substring(0, 100)}...</p>
                    <p class="card-text"><i class="bi bi-geo-alt-fill"></i> <strong>Location:</strong> ${location}</p>
                    <p class="card-text"><i class="bi bi-briefcase-fill"></i> <strong>Type:</strong> ${jobType}</p>
                    <p class="card-text"><i class="bi bi-currency-dollar"></i> <strong>Salary:</strong> ${salary}</p>
                    <p class="card-text"><i class="bi bi-tags-fill"></i> <strong>Skills:</strong> ${skills}</p>
                    <p class="card-text"><i class="bi bi-calendar-date"></i> <strong>Posted:</strong> ${postedAt}</p>
                    <p class="card-text"><i class="bi bi-clock"></i> <strong>Expires:</strong> ${expiresAt}</p>
                    <p class="card-text"><span class="badge ${getStatusClass(status)}">${status}</span></p>
                    <div class="d-flex justify-content-center gap-1">
                        <button class="btn btn-sm btn-success flex-grow-1" onclick="moderateJob(${job.job_id}, 'approve')"><i class="bi bi-check-circle"></i> Approve</button>
                        <button class="btn btn-sm btn-warning text-dark flex-grow-1" onclick="moderateJob(${job.job_id}, 'flag')"><i class="bi bi-flag"></i> Flag</button>
                        <button class="btn btn-sm btn-danger flex-grow-1" onclick="moderateJob(${job.job_id}, 'reject')"><i class="bi bi-x-circle"></i> Reject</button>
                        <button class="btn btn-sm btn-dark flex-grow-1" onclick="deleteJob(${job.job_id})"><i class="bi bi-trash"></i> Delete</button>
                    </div>
                    <button class="btn btn-sm btn-info mt-2 w-100" onclick="viewJobDetails(${job.job_id})">
                        <i class="bi bi-eye"></i> View More Details
                    </button>
                </div>
            </div>
        `;

        jobsContainer.appendChild(jobCard);
    });
}

function getStatusClass(status) {
    switch (status.toLowerCase()) {
        case "approved": return "bg-success";
        case "flagged": return "bg-warning text-dark";
        case "rejected": return "bg-danger";
        default: return "bg-secondary";
    }
}

async function moderateJob(jobId, action) {
    try {
        const response = await fetch("../controllers/job_moderation.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `job_id=${jobId}&action=${action}`
        });

        if (!response.ok) throw new Error(`Server error: ${response.status}`);

        let data;
        try {
            data = await response.json();
        } catch (parseError) {
            throw new Error("Invalid JSON response from server.");
        }

        if (data.error) throw new Error(data.error);

        alert(data.message);
        
        // Get active filter from button's data attribute instead of regex
        const activeFilter = document.querySelector(".btn-group .btn.active")?.dataset.filter || "all";
        fetchJobs(activeFilter);
    } catch (error) {
        alert("Failed to update job status. Please try again.");
        console.error("Error updating job status:", error);
    }
}

async function viewJobDetails(jobId) {
    if (!jobId || isNaN(jobId)) {
        console.error("Invalid jobId received:", jobId);
        alert("Invalid job details.");
        return;
    }

    console.log("Fetching job details for jobId:", jobId);

    try {
        const response = await fetch(`../controllers/job_moderation.php?job_id=${encodeURIComponent(jobId)}`);
        if (!response.ok) throw new Error(`Server error: ${response.status}`);

        const job = await response.json();
        console.log("Job details response:", job);

        if (!job || job.error) {
            console.error("Error: ", job.error || "Invalid job data received");
            alert(job.error || "Job not found.");
            return;
        }

        // Get or initialize modal elements
        const modal = document.getElementById("jobDetailsModal");
        if (!modal) {
            console.error("Modal element not found");
            return;
        }

        const modalElements = {
            title: document.getElementById("modalTitle"),
            description: document.getElementById("modalDescription"),
            location: document.getElementById("modalLocation"),
            status: document.getElementById("modalStatus"),
            date: document.getElementById("modalDate"),
            skills: document.getElementById("modalSkills")
        };

        for (let key in modalElements) {
            if (!modalElements[key]) {
                console.error(`Missing modal element: modal${key.charAt(0).toUpperCase() + key.slice(1)}`);
                return;
            }
        }

        // Update modal content
        modalElements.title.innerText = job.title || "Untitled Job";
        modalElements.description.innerText = job.description || "No description available.";
        modalElements.location.innerText = job.location || "Not provided";
        modalElements.status.innerText = job.moderation_status || "Pending";
        modalElements.date.innerText = job.posted_date || "Unknown";

        // Clear and update additional fields
        modalElements.description.nextElementSibling?.remove();
        modalElements.description.insertAdjacentHTML('afterend', `
            <div class="d-flex align-items-center mb-2">
                <img src="${job.company_logo ? `../Uploads/${job.company_logo}` : 'https://placehold.co/50x50?text=Logo'}" 
                     onerror="this.src='https://placehold.co/50x50?text=Logo'" 
                     alt="Company Logo" class="me-2" style="width: 40px; height: 40px; border-radius: 50%;">
                <p><strong>Company:</strong> ${job.company_name || "Unknown Employer"}</p>
            </div>
            <p><strong>Job Type:</strong> ${job.job_type_title || "Not specified"}</p>
            <p><strong>Salary:</strong> ${job.salary_disclosure && job.min_salary && job.max_salary 
                ? `${job.min_salary} - ${job.max_salary} ${job.salary_type}` 
                : job.salary_type === 'Negotiable' ? 'Negotiable' : 'Not disclosed'}</p>
            <p><strong>Expires:</strong> ${job.expires_at ? new Date(job.expires_at).toLocaleDateString() : "Not set"}</p>
        `);

        // Update job skills
        if (Array.isArray(job.skills) && job.skills.length > 0) {
            modalElements.skills.innerHTML = job.skills.map(skill => `
                <li>${skill.skill_name} - <strong>${skill.importance}</strong></li>
            `).join("");
        } else {
            modalElements.skills.innerHTML = `<li class="text-muted">No skills listed.</li>`;
        }

        // Show modal, disposing of any existing instance
        let modalInstance = bootstrap.Modal.getInstance(modal);
        if (modalInstance) {
            modalInstance.dispose();
        }
        new bootstrap.Modal(modal).show();

    } catch (error) {
        console.error("Error fetching job details:", error);
        alert(error.message || "Failed to load job details.");
    }
}

function confirmExport(event) {
    event.preventDefault();
    const activeFilter = document.querySelector(".btn-group .btn.active")?.dataset.filter || "all";
    const filterText = activeFilter === "all" ? "all jobs" : `${activeFilter} jobs`;

    Swal.fire({
        title: 'Export Job Data',
        text: `Are you sure you want to export ${filterText} as a CSV file?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, Export',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Exporting...',
                text: 'Please wait while your file is being generated.',
                icon: 'info',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                    try {
                        window.location.href = `../controllers/export_jobs.php?status=${encodeURIComponent(activeFilter)}`;
                        setTimeout(() => {
                            Swal.fire({
                                title: 'Export Started',
                                text: 'Your job data export has started. Check your downloads.',
                                icon: 'success',
                                timer: 2000,
                                showConfirmButton: false
                            });
                        }, 1000);
                    } catch (error) {
                        console.error("Error initiating export:", error);
                        Swal.fire({
                            title: 'Error',
                            text: 'Failed to initiate export. Please try again.',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                }
            });
        }
    });
}

async function deleteJob(jobId) {
    Swal.fire({
        title: 'Delete Job',
        text: 'Are you sure you want to delete this job? This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, Delete',
        cancelButtonText: 'Cancel'
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                const response = await fetch("../controllers/job_moderation.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: `job_id=${jobId}&action=delete`
                });

                if (!response.ok) throw new Error(`Server error: ${response.status}`);

                let data;
                try {
                    data = await response.json();
                } catch (parseError) {
                    throw new Error("Invalid JSON response from server.");
                }

                if (data.error) throw new Error(data.error);

                Swal.fire({
                    title: 'Deleted',
                    text: data.message,
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                });

                // Refresh job list with current filter
                const activeFilter = document.querySelector(".btn-group .btn.active")?.dataset.filter || "all";
                fetchJobs(activeFilter);
            } catch (error) {
                Swal.fire({
                    title: 'Error',
                    text: 'Failed to delete job. Please try again.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
                console.error("Error deleting job:", error);
            }
        }
    });
}