document.getElementById('add-skill').addEventListener('click', function () {
    const skillsContainer = document.getElementById('skills-container');

    // Create a new skill row
    const skillRow = document.createElement('div');
    skillRow.className = 'row g-3 align-items-center mb-2';

    // Skill Select Dropdown
    const skillSelectCol = document.createElement('div');
    skillSelectCol.className = 'col-md-5';
    const skillSelect = document.createElement('select');
    skillSelect.className = 'form-select';
    skillSelect.name = 'skills[]';
    skillSelect.required = true;
    skillSelect.innerHTML = `
        <option value="">Select Skill</option>
        <!-- Dynamically populate with backend data -->
    `;
    skillSelectCol.appendChild(skillSelect);

    // Importance Input
    const importanceCol = document.createElement('div');
    importanceCol.className = 'col-md-3';
    const importanceInput = document.createElement('input');
    importanceInput.type = 'number';
    importanceInput.className = 'form-control';
    importanceInput.name = 'importance[]';
    importanceInput.placeholder = 'Importance (1-10)';
    importanceInput.min = 1;
    importanceInput.max = 10;
    importanceInput.required = true;
    importanceCol.appendChild(importanceInput);

    // Remove Button
    const removeCol = document.createElement('div');
    removeCol.className = 'col-md-2';
    const removeButton = document.createElement('button');
    removeButton.type = 'button';
    removeButton.className = 'btn btn-danger btn-sm';
    removeButton.innerHTML = '<i class="fas fa-trash"></i>';
    removeButton.addEventListener('click', function () {
        skillsContainer.removeChild(skillRow);
    });
    removeCol.appendChild(removeButton);

    // Append columns to the row
    skillRow.appendChild(skillSelectCol);
    skillRow.appendChild(importanceCol);
    skillRow.appendChild(removeCol);

    // Append the row to the container
    skillsContainer.appendChild(skillRow);
});