// Forgot Password Form Submission Handling
document.getElementById("forgotPasswordForm").addEventListener("submit", function(e) {
    e.preventDefault();

    const email = document.getElementById("email").value.trim();

    if (!validateEmail(email)) {
        showToast("Please enter a valid email address.");
        return;
    }

    showToast("Sending verification code...", "loading");

    setTimeout(() => {
        showToast("Verification code sent successfully ✅", "success");
        // Here you will redirect to Verify Code Page later
        // window.location.href = "verify-code.php";
    }, 1500);
});

function validateEmail(email) {
    const pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return pattern.test(email);
}

function showToast(message, type = "error") {
    let toast = document.getElementById("toast-box");

    if (!toast) {
        toast = document.createElement("div");
        toast.id = "toast-box";
        document.body.appendChild(toast);
    }

    toast.innerText = message;
    toast.className = type;
    
    setTimeout(() => {
        toast.className = "";
    }, 3000);
}
