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

                tbody.append(`
                    <tr>
                        <td>${emp.full_name}</td>
                        <td>${emp.company_name}</td>
                        <td>${emp.job_title}</td>
                        <td>${emp.jobs_posted}</td>
                        <td>${emp.status}</td>
                        <td>
                            <button class="btn btn-warning suspend-btn" data-id="${emp.employer_id}" ${suspendDisabled}>Suspend</button>
                            <button class="btn btn-danger ban-btn" data-id="${emp.employer_id}" ${banDisabled}>Ban</button>
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

    // 🟢 Handle Suspend/Ban Actions
    $(document).on("click", ".suspend-btn, .ban-btn", function () {
        let employer_id = $(this).data("id");
        let action = $(this).hasClass("suspend-btn") ? "suspend" : "ban";
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
                        // ✅ Update status dynamically instead of reloading
                        button.closest("tr").find("td:nth-child(5)").text(action === "suspend" ? "Suspended" : "Banned");
                        button.prop("disabled", true);
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
