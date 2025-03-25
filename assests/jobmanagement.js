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

    jobs.forEach(job => {
        const jobCard = document.createElement("div");
        jobCard.classList.add("col-md-4", "mb-4");

        const title = job.title || "Untitled Job";
        const description = job.description || "No description available";
        const location = job.location || "Not provided";
        const status = job.moderation_status || "Pending";

        jobCard.innerHTML = `
            <div class="card shadow-lg animate__animated animate__fadeIn" style="min-height: 220px;">
                <div class="card-body">
                    <h5 class="card-title">${title}</h5>
                    <p class="card-text text-muted">${description.substring(0, 100)}...</p>
                    <p class="card-text"><i class="bi bi-geo-alt-fill"></i> <strong>Location:</strong> ${location}</p>
                    <p class="card-text"><span class="badge ${getStatusClass(status)}">${status}</span></p>
                    <div class="d-flex justify-content-center gap-1">
                        <button class="btn btn-sm btn-success flex-grow-1" onclick="moderateJob(${job.job_id}, 'approve')"><i class="bi bi-check-circle"></i> Approve</button>
                        <button class="btn btn-sm btn-warning text-dark flex-grow-1" onclick="moderateJob(${job.job_id}, 'flag')"><i class="bi bi-flag"></i> Flag</button>
                        <button class="btn btn-sm btn-danger flex-grow-1" onclick="moderateJob(${job.job_id}, 'reject')"><i class="bi bi-x-circle"></i> Reject</button>
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

        // Make sure modal elements exist
        const modalElements = {
            title: document.getElementById("modalTitle"),
            description: document.getElementById("modalDescription"),
            location: document.getElementById("modalLocation"),
            status: document.getElementById("modalStatus"),
            date: document.getElementById("modalDate"),
            skills: document.getElementById("modalSkills") // ✅ Add this
        };

        for (let key in modalElements) {
            if (!modalElements[key]) {
                console.error(`Missing modal element: modal${key.charAt(0).toUpperCase() + key.slice(1)}`);
                return;
            }
        }

        // Populate modal with job details
        modalElements.title.innerText = job.title || "Untitled Job";
        modalElements.description.innerText = job.description || "No description available.";
        modalElements.location.innerText = job.location || "Not provided";
        modalElements.status.innerText = job.moderation_status || "Pending";
        modalElements.date.innerText = job.posted_date || "Unknown";

        // ✅ Display job skills
        if (Array.isArray(job.skills) && job.skills.length > 0) {
            modalElements.skills.innerHTML = job.skills.map(skill => `
                <li>${skill.skill_name} - <strong>${skill.importance}</strong></li>
            `).join("");
        } else {
            modalElements.skills.innerHTML = `<li class="text-muted">No skills listed.</li>`;
        }

        console.log("Showing modal...");
        new bootstrap.Modal(document.getElementById("jobDetailsModal")).show();

    } catch (error) {
        console.error("Error fetching job details:", error);
        alert(error.message || "Failed to load job details.");
    }
}
