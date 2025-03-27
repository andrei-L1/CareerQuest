<?php include '../includes/stud_navbar.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Listings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .job-container {
            padding: 20px;
        }
        .table thead {
            background-color: #007bff;
            color: white;
        }
    </style>
</head>
<body>

<div class="container job-container">
    <h2 class="mb-4">Available Jobs</h2>

    <!-- Filters Section -->
    <div class="row mb-3">
        <div class="col-md-4">
            <input type="text" id="search" class="form-control" placeholder="Search for jobs...">
        </div>
        <div class="col-md-3">
            <select id="category" class="form-control">
                <option value="">All Categories</option>
                <option value="IT">IT</option>
                <option value="Marketing">Marketing</option>
                <option value="Finance">Finance</option>
            </select>
        </div>
        <div class="col-md-3">
            <input type="text" id="location" class="form-control" placeholder="Filter by location">
        </div>
        <div class="col-md-2">
            <button class="btn btn-primary w-100" onclick="filterJobs()">Filter</button>
        </div>
    </div>

    <!-- Job Listings Table -->
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Company</th>
                    <th>Location</th>
                    <th>Category</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="jobTable"></tbody>
        </table>
    </div>

</div>

<script>
document.addEventListener("DOMContentLoaded", fetchJobs);

let jobData = [];

function fetchJobs() {
    fetch("../controllers/student_job.php")
        .then(response => response.json())
        .then(data => {
            jobData = data;
            renderJobs(jobData);
        })
        .catch(error => console.error("Error fetching jobs:", error));
}

function renderJobs(jobs) {
    const tableBody = document.getElementById("jobTable");
    tableBody.innerHTML = "";

    jobs.forEach(job => {
        const row = document.createElement("tr");
        row.innerHTML = `
            <td>${sanitize(job.title)}</td>
            <td>${sanitize(job.company)}</td>
            <td>${sanitize(job.location)}</td>
            <td>${sanitize(job.category)}</td>
            <td><a href="job_details.php?id=${sanitize(job.job_id)}" class="btn btn-sm btn-success">Apply</a></td>
        `;
        tableBody.appendChild(row);
    });
}

function filterJobs() {
    const search = document.getElementById("search").value.toLowerCase();
    const category = document.getElementById("category").value.toLowerCase();
    const location = document.getElementById("location").value.toLowerCase();

    const filteredJobs = jobData.filter(job =>
        (search === "" || job.title.toLowerCase().includes(search)) &&
        (category === "" || job.category.toLowerCase().includes(category)) &&
        (location === "" || job.location.toLowerCase().includes(location))
    );

    renderJobs(filteredJobs);
}

function sanitize(text) {
    const temp = document.createElement("div");
    temp.textContent = text;
    return temp.innerHTML;
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
