let cachedSkills = [];

document.getElementById('add-skill').addEventListener('click', function () {
    const skillsContainer = document.getElementById('skills-table-body');
    const skillRow = document.createElement('tr');

    // Skill Dropdown
    const skillSelectTd = document.createElement('td');
    const skillSelect = document.createElement('select');
    skillSelect.className = 'form-select skill-select';
    skillSelect.name = 'skills[]';
    skillSelect.required = true;

    if (cachedSkills.length === 0) {
        fetch("../controllers/job_moderation.php?type=skills")
            .then(response => response.json())
            .then(data => {
                console.log(data);
                cachedSkills = data;
                populateSkills(skillSelect, cachedSkills);
            })
            .catch(error => console.error("Error loading skills:", error));
    } else {
        populateSkills(skillSelect, cachedSkills);
    }

    skillSelectTd.appendChild(skillSelect);

    // Importance Dropdown
    const importanceTd = document.createElement('td');
    const importanceSelect = document.createElement('select');
    importanceSelect.className = 'form-select importance-select';
    importanceSelect.name = 'importance[]';
    ['Low', 'Medium', 'High'].forEach(level => {
        importanceSelect.innerHTML += `<option value="${level}">${level}</option>`;
    });
    importanceTd.appendChild(importanceSelect);

    // Remove Button
    const removeTd = document.createElement('td');
    const removeButton = document.createElement('button');
    removeButton.type = 'button';
    removeButton.className = 'btn btn-danger btn-sm';
    removeButton.innerHTML = '<i class="fas fa-trash"></i>';
    removeButton.addEventListener('click', function () {
        skillsContainer.removeChild(skillRow);
    });
    removeTd.appendChild(removeButton);

    skillRow.appendChild(skillSelectTd);
    skillRow.appendChild(importanceTd);
    skillRow.appendChild(removeTd);
    skillsContainer.appendChild(skillRow);
});

function populateSkills(skillSelect, skills) {
    skillSelect.innerHTML = '<option value="">Select Skill</option>';
    skills.forEach(skill => {
        skillSelect.innerHTML += `<option value="${skill.skill_id}">${skill.skill_name}</option>`;
    });
}

document.getElementById("addJobForm").addEventListener("submit", function (event) {
    event.preventDefault();

    let formData = new FormData(this);

    // Collect skills dynamically
    document.querySelectorAll("#skills-table-body tr").forEach((row, index) => {
        let skill = row.querySelector(".skill-select").value;
        let importance = row.querySelector(".importance-select").value;
        if (skill) {
            formData.append(`skills[${index}][skill]`, skill);
            formData.append(`skills[${index}][importance]`, importance);
        }
    });

    fetch("../controllers/job_moderation.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert("Job added successfully!");
            location.reload();
        } else {
            alert("Error: " + data.error);
        }
    })
    .catch(error => console.error("Error:", error));
});

// Load Employers & Job Types Dynamically
document.addEventListener("DOMContentLoaded", function () {
    fetch("../controllers/job_moderation.php?type=employers")
        .then(response => response.json())
        .then(data => {
            let employerSelect = document.getElementById("employer_id");
            data.forEach(employer => {
                employerSelect.innerHTML += `<option value="${employer.employer_id}">${employer.company_name}</option>`;
            });
        });

    fetch("../controllers/job_moderation.php?type=job_types")
        .then(response => response.json())
        .then(data => {
            let jobTypeSelect = document.getElementById("job_type");
            data.forEach(jobType => {
                jobTypeSelect.innerHTML += `<option value="${jobType.job_type_id}">${jobType.job_type_title}</option>`;
            });
        });
});