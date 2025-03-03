// Toggle fields based on entity selection
function toggleFields() {
    const entity = document.getElementById("entity").value;
    document.getElementById("user-fields").classList.toggle("d-none", entity !== "user");
    document.getElementById("student-fields").classList.toggle("d-none", entity !== "student");
}

// Update progress bar
function updateProgressBar(step) {
    document.getElementById("progressBar").style.width = `${step * 25}%`;
}

// Step navigation
function nextStep(step) {
    const currentStep = document.querySelector("div:not(.d-none)[id^='step-']");
    const inputs = currentStep.querySelectorAll("input[required], select[required]");
    let isValid = true;

    inputs.forEach((input) => {
        if (!input.checkValidity()) {
            input.classList.add("is-invalid");
            isValid = false;
        } else {
            input.classList.remove("is-invalid");
        }
    });

    if (!isValid) return;

    document.querySelectorAll("div[id^='step-']").forEach((el) => el.classList.add("d-none"));
    document.getElementById(`step-${step}`).classList.remove("d-none");
    updateProgressBar(step);
    document.querySelector(`#step-${step} input`)?.focus();
}

function prevStep(step) {
    document.querySelectorAll("div[id^='step-']").forEach((el) => el.classList.add("d-none"));
    document.getElementById(`step-${step}`).classList.remove("d-none");
    updateProgressBar(step);
}

// Password validation function
function validatePassword() {
    const passwordField = document.getElementById("password");
    const confirmPasswordField = document.getElementById("confirm_password");
    const passwordHelp = document.getElementById("passwordHelp");
    const nextButton = document.querySelector("#step-3 .btn-primary"); // Next button

    const passwordValue = passwordField.value.trim();
    const confirmPasswordValue = confirmPasswordField.value.trim();
    const passwordRegex = /^(?=.*[A-Z])(?=.*[^a-zA-Z0-9]).{6,}$/;
    let isValid = true;
    let message = "";

    if (!passwordValue) {
        message = "Password is required.";
        isValid = false;
    } else if (!passwordRegex.test(passwordValue)) {
        message = "Password must be at least 6 characters, include an uppercase letter, and a special character.";
        isValid = false;
    } else if (passwordValue !== confirmPasswordValue) {
        message = "Passwords do not match.";
        isValid = false;
    }

    if (!isValid) {
        passwordHelp.textContent = message;
        passwordHelp.classList.remove("d-none");
        passwordField.classList.add("is-invalid");
        confirmPasswordField.classList.add("is-invalid");
        nextButton.disabled = true; // Disable next button
    } else {
        passwordHelp.classList.add("d-none");
        passwordField.classList.remove("is-invalid");
        confirmPasswordField.classList.remove("is-invalid");
        nextButton.disabled = false; // Enable next button
    }

    return isValid;
}

// Event listeners
document.addEventListener("DOMContentLoaded", () => {
    toggleFields();
    document.getElementById("entity").addEventListener("change", toggleFields);

    const passwordField = document.getElementById("password");
    const confirmPasswordField = document.getElementById("confirm_password");

    // Validate password on input change
    passwordField.addEventListener("input", validatePassword);
    confirmPasswordField.addEventListener("input", validatePassword);
});
