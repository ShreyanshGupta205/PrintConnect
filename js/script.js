// js/script.js

document.addEventListener('DOMContentLoaded', function() {
    // Basic form validation initialization or custom UI scripts can go here
    console.log("PrintConnect initialized.");

    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert-auto-dismiss');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
});
