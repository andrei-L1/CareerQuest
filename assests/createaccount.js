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

// Event listeners
document.addEventListener("DOMContentLoaded", () => {
    const passwordField = document.getElementById("password");
    const confirmPasswordField = document.getElementById("confirm_password");

    // Validate password on input change
    passwordField.addEventListener("input", validatePassword);
    confirmPasswordField.addEventListener("input", validatePassword);
});
