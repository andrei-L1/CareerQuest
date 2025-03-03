// Toggle fields based on entity selection
function toggleFields() {
    const entity = document.getElementById('entity').value;
    document.getElementById('user-fields').classList.toggle('d-none', entity !== 'user');
    document.getElementById('student-fields').classList.toggle('d-none', entity !== 'student');
}

// Update progress bar
function updateProgressBar(step) {
    const progressBar = document.getElementById('progressBar');
    progressBar.style.width = `${step * 25}%`;
}



// Step navigation
function nextStep(step) {
    const currentStep = document.querySelector('div:not(.d-none)[id^="step-"]');
    const inputs = currentStep.querySelectorAll("input[required], select[required]");
    let isValid = true;

    inputs.forEach(input => {
        if (!input.checkValidity()) {
            input.classList.add("is-invalid");
            isValid = false;
        } else {
            input.classList.remove("is-invalid");
        }
    });

    if (!isValid) return;

    document.querySelectorAll('div[id^="step-"]').forEach(el => el.classList.add('d-none'));
    document.getElementById(`step-${step}`).classList.remove('d-none');
    updateProgressBar(step);
    document.querySelector(`#step-${step} input`)?.focus();
}

function prevStep(step) {
    document.querySelectorAll('div[id^="step-"]').forEach(el => el.classList.add('d-none'));
    document.getElementById(`step-${step}`).classList.remove('d-none');
    updateProgressBar(step);
}

// Event listeners
document.addEventListener('DOMContentLoaded', () => {
    toggleFields();
    document.getElementById('entity').addEventListener('change', toggleFields);

    const passwordField = document.getElementById("password");
    const confirmPasswordField = document.getElementById("confirm_password");
    const nextButton = document.querySelector("#step-3 .btn-primary");

    // Validate password when typing
    passwordField.addEventListener("input", validatePassword);
    confirmPasswordField.addEventListener("input", validatePassword);

    // Prevent moving forward if validation fails
    nextButton.addEventListener('click', (event) => {
        console.log("Next button clicked"); // Debugging log
        if (!validatePassword()) {
            console.log("Validation failed, preventing next step"); // Debugging log
            event.preventDefault(); // 🚫 Prevents navigation
            return;
        }
        console.log("Validation passed, moving to next step"); // Debugging log
        nextStep(4);
    });
});

// Function to validate password
function validatePassword() {
    const passwordField = document.getElementById("password");
    const confirmPasswordField = document.getElementById("confirm_password");
    const passwordHelp = document.getElementById("passwordHelp");
    const passwordRegex = /^(?=.*[A-Z])(?=.*[^a-zA-Z0-9]).{6,}$/;
    let passwordValue = passwordField.value.trim();
    let confirmPasswordValue = confirmPasswordField.value.trim();
    let isValid = true;

    if (passwordValue === "") {
        passwordHelp.textContent = "Password is required.";
        isValid = false;
    } else if (!passwordRegex.test(passwordValue)) {
        passwordHelp.textContent = "Password must be at least 6 characters, include an uppercase letter and a special character.";
        isValid = false;
    } else if (passwordValue !== confirmPasswordValue) {
        passwordHelp.textContent = "Passwords do not match.";
        isValid = false;
    }

    if (!isValid) {
        passwordField.classList.add("is-invalid");
        confirmPasswordField.classList.add("is-invalid");
        passwordHelp.classList.remove("d-none");
    } else {
        passwordField.classList.remove("is-invalid");
        confirmPasswordField.classList.remove("is-invalid");
        passwordHelp.classList.add("d-none");
    }

    return isValid;
}
