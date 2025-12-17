// public/js/forgot-pass.js
document.getElementById("forgotPasswordForm").addEventListener("submit", function(e) {
    e.preventDefault();

    const email = document.getElementById("email").value.trim();

    if (!validateEmail(email)) {
        showToast("Invalid email address", "error");
        return;
    }

    showToast("Sending code...", "loading");

    setTimeout(() => {
        showToast("Code sent! ✓", "success");
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

    toast.className = '';
    toast.classList.add(type);
    toast.textContent = message;
    toast.style.opacity = '1';
    
    if (type !== "loading") {
        setTimeout(() => {
            toast.style.opacity = '0';
        }, 3000);
    }
}