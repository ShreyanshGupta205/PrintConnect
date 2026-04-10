<?php
// register.php
require_once 'config/db.php';

if (isLoggedIn()) {
    if ($_SESSION['role'] === 'admin') {
        redirect('admin/');
    } else {
        redirect($_SESSION['role'] . '/dashboard.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - PrintConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5 pt-3 mb-5">
            <div class="col-md-6">
                <div class="card shadow-lg p-4">
                    <div class="text-center mb-4">
                        <h2 class="fw-bold text-primary">Join PrintConnect</h2>
                        <p class="text-muted">Create a secure account via Firebase.</p>
                    </div>

                    <div id="error-alert" class="alert alert-danger d-none"></div>

                    <form id="register-form">
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email address</label>
                            <input type="email" class="form-control" id="email" required>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" required>
                            </div>
                            <div class="col-md-6">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="confirm_password" required>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">I am a...</label>
                            <div class="d-flex">
                                <div class="form-check me-4">
                                    <input class="form-check-input" type="radio" name="role" id="roleCustomer" value="customer" checked>
                                    <label class="form-check-label" for="roleCustomer">Customer</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="role" id="roleOwner" value="owner">
                                    <label class="form-check-label" for="roleOwner">Cafe Owner</label>
                                </div>
                            </div>
                        </div>
                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary btn-lg" id="reg-btn">
                                <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                                Register
                            </button>
                        </div>
                        <div class="text-center">
                            <p class="mb-0">Already have an account? <a href="login.php" class="text-decoration-none">Login here</a></p>
                            <p class="mt-2"><a href="index.php" class="text-decoration-none text-secondary">Back to Home</a></p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script type="module">
        // Firebase v10+ Modular SDK via CDN
        import { initializeApp } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-app.js";
        import { getAuth, createUserWithEmailAndPassword, updateProfile } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js";
        import { firebaseConfig } from "./js/firebase-config.js";

        // Initialize Firebase
        const app = initializeApp(firebaseConfig);
        const auth = getAuth(app);

        const regForm = document.getElementById('register-form');
        const regBtn = document.getElementById('reg-btn');
        const errorAlert = document.getElementById('error-alert');

        function showError(msg) {
            errorAlert.textContent = msg;
            errorAlert.classList.remove('d-none');
        }

        regForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const name = document.getElementById('name').value;
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const role = document.querySelector('input[name="role"]:checked').value;

            if (password !== confirmPassword) {
                return showError("Passwords do not match.");
            }

            errorAlert.classList.add('d-none');
            regBtn.disabled = true;
            regBtn.querySelector('.spinner-border').classList.remove('d-none');

            try {
                // 1. Create user in Firebase
                const result = await createUserWithEmailAndPassword(auth, email, password);
                
                // Update profile name
                await updateProfile(result.user, { displayName: name });

                // 2. Sync with backend
                const idToken = await result.user.getIdToken();
                const response = await fetch('php/auth_verify.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        idToken, 
                        action: 'register',
                        name: name,
                        role: role
                    })
                });
                
                const backendResult = await response.json();
                
                if (backendResult.success) {
                    window.location.href = backendResult.redirect;
                } else {
                    showError(backendResult.message);
                }
            } catch (error) {
                showError(error.message);
            } finally {
                regBtn.disabled = false;
                regBtn.querySelector('.spinner-border').classList.add('d-none');
            }
        });
    </script>
</body>
</html>
