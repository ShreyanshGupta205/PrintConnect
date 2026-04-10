<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
// Index page - Landing Page
require_once 'config/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PrintConnect - Online Cyber Cafe Service</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark pb-3 pt-3">
        <div class="container">
            <a class="navbar-brand fw-bold fs-4" href="index.php"><i class="bi bi-printer-fill me-2"></i>PrintConnect</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item d-flex align-items-center">
                            <?php if ($_SESSION['role'] === 'admin'): ?>
                                <a class="nav-link btn btn-outline-light px-3 me-2" href="admin/">Admin Panel</a>
                            <?php else: ?>
                                <a class="nav-link btn btn-outline-light px-3 me-2" href="<?php echo $_SESSION['role']; ?>/dashboard.php">Dashboard</a>
                            <?php endif; ?>
                            <a class="nav-link" href="logout.php">Logout</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link btn btn-light text-primary px-3 fw-bold ms-lg-3" href="register.php">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="row align-items-center mb-5">
            <div class="col-lg-6">
                <h1 class="display-4 fw-bold mb-4 text-dark">Print your documents online, hassle-free.</h1>
                <p class="lead mb-4 text-secondary">Connect with local cyber cafes. Upload, pay, and pick up your prints without waiting in long lines.</p>
                <?php if (!isLoggedIn()): ?>
                    <a href="register.php" class="btn btn-primary btn-lg shadow">Get Started Now</a>
                    <a href="login.php" class="btn btn-outline-secondary btn-lg ms-2">Login</a>
                <?php else: ?>
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <a href="admin/" class="btn btn-primary btn-lg shadow">Go to Admin Panel</a>
                    <?php else: ?>
                        <a href="<?php echo $_SESSION['role']; ?>/dashboard.php" class="btn btn-primary btn-lg shadow">Go to Dashboard</a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <div class="col-lg-6 text-center mt-5 mt-lg-0">
                <img src="https://raw.githubusercontent.com/king500205king/PrintConnect/main/images/hero.png" class="img-fluid rounded-4 shadow-lg" alt="Printing Service" style="max-height: 400px; object-fit: cover;">
            </div>
        </div>

        <div class="row text-center mt-5 pt-5 border-top">
            <h2 class="mb-5 fw-bold">How PrintConnect Works</h2>
            <div class="col-md-4 mb-4">
                <div class="card h-100 p-4">
                    <i class="bi bi-cloud-arrow-up display-1 text-primary mb-3"></i>
                    <h3 class="h4">1. Upload Document</h3>
                    <p class="text-muted">Upload your PDF or Word document securely to our platform from any device.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100 p-4">
                    <i class="bi bi-shop display-1 text-primary mb-3"></i>
                    <h3 class="h4">2. Choose a Cafe</h3>
                    <p class="text-muted">Select a local cyber cafe, specify your print settings (color, binding), and place the order.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100 p-4">
                    <i class="bi bi-check-circle display-1 text-primary mb-3"></i>
                    <h3 class="h4">3. Pick Up</h3>
                    <p class="text-muted">Track your order status and pick up your documents when they are marked as ready.</p>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-dark text-white pt-5 pb-3 mt-5">
        <div class="container text-center">
            <p>&copy; <?php echo date('Y'); ?> PrintConnect. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/script.js"></script>
</body>
</html>
