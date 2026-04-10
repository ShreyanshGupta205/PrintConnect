<?php
// login.php
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
    <title>Login - PrintConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5 pt-5">
            <div class="col-md-5">
                <div class="card shadow-lg p-4">
                    <div class="text-center mb-4">
                        <h2 class="fw-bold text-primary">PrintConnect</h2>
                        <p class="text-muted">Secure login with Firebase</p>
                    </div>

                    <div id="error-alert" class="alert alert-danger d-none"></div>

                    <form id="login-form">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email address</label>
                            <input type="email" class="form-control" id="email" required autofocus>
                        </div>
                        <div class="mb-4">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" required>
                        </div>
                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary btn-lg" id="login-btn">
                                <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                                Login
                            </button>
                        </div>
                        
                        <div class="text-center mb-3">
                            <span class="text-muted small">OR</span>
                        </div>

                        <div class="d-grid mb-3">
                            <button type="button" class="btn btn-outline-dark" id="google-login-btn">
                                <i class="bi bi-google me-2"></i> Sign in with Google
                            </button>
                        </div>

                        <div class="text-center">
                            <p class="mb-0">Don't have an account? <a href="register.php" class="text-decoration-none">Register here</a></p>
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
        import { getAuth, signInWithEmailAndPassword, signInWithPopup, GoogleAuthProvider } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js";
        import { firebaseConfig } from "./js/firebase-config.js";

        // Initialize Firebase
        const app = initializeApp(firebaseConfig);
        const auth = getAuth(app);
        const googleProvider = new GoogleAuthProvider();

        const loginForm = document.getElementById('login-form');
        const loginBtn = document.getElementById('login-btn');
        const googleLoginBtn = document.getElementById('google-login-btn');
        const errorAlert = document.getElementById('error-alert');

        function showError(msg) {
            errorAlert.textContent = msg;
            errorAlert.classList.remove('d-none');
        }

        async function verifyWithBackend(user, action = 'login') {
            const idToken = await user.getIdToken();
            const response = await fetch('php/auth_verify.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    idToken, 
                    action,
                    name: user.displayName || 'User'
                })
            });
            return await response.json();
        }

        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            
            errorAlert.classList.add('d-none');
            loginBtn.disabled = true;
            loginBtn.querySelector('.spinner-border').classList.remove('d-none');

            try {
                const result = await signInWithEmailAndPassword(auth, email, password);
                const backendResult = await verifyWithBackend(result.user);
                
                if (backendResult.success) {
                    window.location.href = backendResult.redirect;
                } else {
                    showError(backendResult.message);
                }
            } catch (error) {
                showError(error.message);
            } finally {
                loginBtn.disabled = false;
                loginBtn.querySelector('.spinner-border').classList.add('d-none');
            }
        });

        googleLoginBtn.addEventListener('click', async () => {
            try {
                const result = await signInWithPopup(auth, googleProvider);
                let backendResult = await verifyWithBackend(result.user, 'login');
                
                if (!backendResult.success && backendResult.message.includes('not found')) {
                    backendResult = await verifyWithBackend(result.user, 'register');
                }

                if (backendResult.success) {
                    window.location.href = backendResult.redirect;
                } else {
                    showError(backendResult.message);
                }
            } catch (error) {
                showError(error.message);
            }
        });
    </script>
</body>
</html>
