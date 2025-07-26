$(document).ready(function () {
    // 🟢 Fetch Employers
    $.ajax({
        url: "../controllers/job_moderation_employer.php",
        method: "GET",
        dataType: "json",
        success: function (data) {
            console.log("Raw Response:", data); // Debugging

            if (!Array.isArray(data)) {
                console.error("Invalid JSON format:", data, typeof data);
                alert("Failed to load employers. See console for details.");
                return;
            }

            let tbody = $("#employerTableBody"); // ✅ Corrected selector
            tbody.empty();

            data.forEach(emp => {
                let suspendDisabled = emp.status === "Suspended" || emp.status === "Banned" ? "disabled" : "";
                let banDisabled = emp.status === "Banned" ? "disabled" : "";
                let reactivateDisabled = emp.status === "Active" ? "disabled" : "";
                let documentLink = emp.document_url 
                    ? `<a href="../Uploads/${emp.document_url}" target="_blank" class="btn btn-info btn-sm">View</a>` 
                    : "No document";

                tbody.append(`
                    <tr>
                        <td>${emp.full_name}</td>
                        <td>${emp.company_name}</td>
                        <td>${emp.job_title}</td>
                        <td>${emp.jobs_posted}</td>
                        <td class="status-cell">${emp.status}</td>
                        <td>${documentLink}</td>
                        <td>
                            <button class="btn btn-warning suspend-btn" data-id="${emp.employer_id}" ${suspendDisabled}>Suspend</button>
                            <button class="btn btn-danger ban-btn" data-id="${emp.employer_id}" ${banDisabled}>Ban</button>
                            <button class="btn btn-success reactivate-btn" data-id="${emp.employer_id}" ${reactivateDisabled}>
                                ${emp.status === "Verification" ? "Activate" : "Reactivate"}
                            </button>
                        </td>
                    </tr>
                `);
            });             
        },
        error: function (jqXHR, textStatus, errorThrown) {
            console.error("AJAX Error:", textStatus, errorThrown, jqXHR.responseText);
            alert("Error fetching employers. Check the console for details.");
        }
    });

    // 🟢 Handle Suspend/Ban/Reactivate Actions
    $(document).on("click", ".suspend-btn, .ban-btn, .reactivate-btn", function () {
        let employer_id = $(this).data("id");
        let action = $(this).hasClass("suspend-btn") ? "suspend" : $(this).hasClass("ban-btn") ? "ban" : "reactivate";
        let button = $(this);

        if (confirm(`Are you sure you want to ${action} this employer?`)) {
            button.prop("disabled", true); // Disable button while processing

            $.ajax({
                url: "../controllers/job_moderation_employer.php",
                method: "POST",
                data: { employer_id, action },
                dataType: "json",
                success: function (response) {
                    if (response.success) {
                        alert(response.message);
                        let newStatus = action === "suspend" ? "Suspended" : action === "ban" ? "Banned" : "Active";
                        let row = button.closest("tr");
                        row.find(".status-cell").text(newStatus);

                        // ✅ Update buttons dynamically
                        row.find(".suspend-btn").prop("disabled", newStatus !== "Active");
                        row.find(".ban-btn").prop("disabled", newStatus === "Banned");
                        row.find(".reactivate-btn").prop("disabled", newStatus === "Active");
                        // Update button text to "Reactivate" after activation
                        if (newStatus === "Active") {
                            row.find(".reactivate-btn").text("Reactivate");
                        }
                    } else {
                        alert("Error: " + response.error);
                        button.prop("disabled", false);
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    console.error("AJAX Error:", textStatus, errorThrown, jqXHR.responseText);
                    alert("Error processing request. Check the console for details.");
                    button.prop("disabled", false);
                }
            });
        }
    });
});

function filterEmployers(status, button) {
    // Remove active class from all buttons
    document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
    button.classList.add('active');

    // Get all rows
    let rows = document.querySelectorAll("#employerTableBody tr");

    rows.forEach(row => {
        let statusCell = row.querySelector("td:nth-child(5)"); // Status is 5th column (index 4)
        if (!statusCell) return; // Skip if no status cell

        let currentStatus = statusCell.textContent.trim();
        
        // Show or hide row based on status filter
        row.style.display = (status === "all" || currentStatus === status) ? "" : "none";

        // Hide buttons based on the current filter
        let suspendBtn = row.querySelector(".suspend-btn");
        let banBtn = row.querySelector(".ban-btn");
        let reactivateBtn = row.querySelector(".reactivate-btn");

        if (status === "Active") {
            suspendBtn.style.display = "inline-block";
            banBtn.style.display = "inline-block";
            reactivateBtn.style.display = "none";
        } else if (status === "Suspended") {
            suspendBtn.style.display = "none";
            banBtn.style.display = "inline-block";
            reactivateBtn.style.display = "inline-block";
        } else if (status === "Banned") {
            suspendBtn.style.display = "none";
            banBtn.style.display = "none";
            reactivateBtn.style.display = "inline-block";
        } else if (status === "Verification") {
            suspendBtn.style.display = "inline-block";
            banBtn.style.display = "inline-block";
            reactivateBtn.style.display = "inline-block";
        } else {
            // Show all buttons when "All" filter is selected
            suspendBtn.style.display = "inline-block";
            banBtn.style.display = "inline-block";
            reactivateBtn.style.display = "inline-block";
        }
    });
}