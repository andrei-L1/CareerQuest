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
    const confirmPasswordHelp = document.getElementById("confirmPasswordHelp");
    const nextButton = document.getElementById("nextStep3");

    const passwordValue = passwordField.value.trim();
    const confirmPasswordValue = confirmPasswordField.value.trim();
    const passwordRegex = /^(?=.*[A-Z])(?=.*[^a-zA-Z0-9]).{6,}$/;
    let isValid = true;

    // Validate password strength
    if (!passwordRegex.test(passwordValue)) {
        passwordHelp.textContent = "Password must be at least 6 characters, include an uppercase letter, and a special character.";
        passwordHelp.classList.remove("d-none");
        passwordField.classList.add("is-invalid");
        isValid = false;
    } else {
        passwordHelp.classList.add("d-none");
        passwordField.classList.remove("is-invalid");
    }

    // Validate password match, but only if the user has started typing in confirm field
    if (confirmPasswordValue.length > 0 && passwordValue !== confirmPasswordValue) {
        confirmPasswordHelp.textContent = "Passwords do not match.";
        confirmPasswordHelp.classList.remove("d-none");
        confirmPasswordField.classList.add("is-invalid");
        isValid = false;
    } else {
        confirmPasswordHelp.classList.add("d-none");
        confirmPasswordField.classList.remove("is-invalid");
    }

    nextButton.disabled = !isValid; // Enable/disable the Next button
    return isValid;
}

// Event listeners
document.addEventListener("DOMContentLoaded", () => {
    const passwordField = document.getElementById("password");
    const confirmPasswordField = document.getElementById("confirm_password");

    // Validate password on input change
    passwordField.addEventListener("input", validatePassword);
    confirmPasswordField.addEventListener("input", validatePassword);
});
