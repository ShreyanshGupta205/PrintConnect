<?php
require_once '../config/db.php';

if (!isLoggedIn() || !hasRole('admin')) {
    redirect('../login.php');
}

// Fetch platform statistics
$stats = [];

// Total Users by role
$stmt_users = $pdo->query("
    SELECT role, COUNT(*) as count 
    FROM users 
    GROUP BY role
");
$user_counts = $stmt_users->fetchAll(PDO::FETCH_KEY_PAIR);
$stats['customers'] = $user_counts['customer'] ?? 0;
$stats['owners'] = $user_counts['owner'] ?? 0;

// Shop stats
$stmt_shops = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive
    FROM shops
");
$stats['shops'] = $stmt_shops->fetch();

// Order stats
$stmt_orders = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
    FROM orders
");
$stats['orders'] = $stmt_orders->fetch();

// Recent Shops pending activation
$stmt_pending_shops = $pdo->query("
    SELECT s.*, u.name as owner_name 
    FROM shops s 
    JOIN users u ON s.owner_id = u.user_id 
    WHERE s.status = 'inactive' 
    ORDER BY s.created_at DESC 
    LIMIT 5
");
$pending_shops = $stmt_pending_shops->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - PrintConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-danger pb-3 pt-3 mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold" href="dashboard.php"><i class="bi bi-shield-lock-fill me-2"></i>PrintConnect <span class="badge bg-light text-danger ms-2 fs-6">Admin</span></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link active" href="dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="users.php">Manage Users</a></li>
                    <li class="nav-item"><a class="nav-link" href="shops.php">Manage Shops</a></li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle me-1"></i> <?php echo escape($_SESSION['name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                                        <li><a class="dropdown-item text-danger" href="../logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container pb-5">
        <h2 class="fw-bold mb-4">System Overview</h2>

        <!-- Stats Grid -->
        <div class="row mb-5">
            <div class="col-md-3 mb-4">
                <div class="card bg-primary text-white h-100 shadow-sm border-0 rounded-4">
                    <div class="card-body p-4 text-center">
                        <i class="bi bi-people-fill display-4 mb-3 opacity-75"></i>
                        <h5 class="card-title fw-light">Total Users</h5>
                        <h2 class="fw-bold mb-0"><?php echo current($pdo->query("SELECT COUNT(*) FROM users")->fetch()); ?></h2>
                        <div class="mt-2 small opacity-75">
                            <?php echo $stats['customers']; ?> Customers &bull; <?php echo $stats['owners']; ?> Owners
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-4">
                <div class="card bg-success text-white h-100 shadow-sm border-0 rounded-4">
                    <div class="card-body p-4 text-center">
                        <i class="bi bi-shop display-4 mb-3 opacity-75"></i>
                        <h5 class="card-title fw-light">Total Shops</h5>
                        <h2 class="fw-bold mb-0"><?php echo $stats['shops']['total'] ?? 0; ?></h2>
                        <div class="mt-2 small opacity-75">
                            <?php echo $stats['shops']['active'] ?? 0; ?> Active &bull; <?php echo $stats['shops']['inactive'] ?? 0; ?> Inactive
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-4">
                <div class="card bg-info text-white h-100 shadow-sm border-0 rounded-4">
                    <div class="card-body p-4 text-center">
                        <i class="bi bi-file-earmark-text display-4 mb-3 opacity-75"></i>
                        <h5 class="card-title fw-light">Platform Orders</h5>
                        <h2 class="fw-bold mb-0"><?php echo $stats['orders']['total'] ?? 0; ?></h2>
                        <div class="mt-2 small opacity-75">
                            <?php echo $stats['orders']['completed'] ?? 0; ?> Completed Successfully
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-4">
                <div class="card bg-dark text-white h-100 shadow-sm border-0 rounded-4">
                    <div class="card-body p-4 text-center">
                        <i class="bi bi-currency-dollar display-4 mb-3 opacity-75"></i>
                        <h5 class="card-title fw-light">Subscriptions</h5>
                        <h2 class="fw-bold mb-0"><?php echo current($pdo->query("SELECT COUNT(*) FROM subscriptions WHERE payment_status='completed' AND end_date >= CURRENT_DATE")->fetch()); ?></h2>
                        <div class="mt-2 small opacity-75">
                            Active Premium Partners
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6">
                <!-- Action Required -->
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white pb-0 border-0 pt-4 px-4 d-flex justify-content-between">
                        <h5 class="fw-bold text-danger"><i class="bi bi-exclamation-circle-fill me-2"></i>Action Required: Shop Approvals</h5>
                        <a href="shops.php" class="small">View All</a>
                    </div>
                    <div class="card-body p-4">
                        <?php if (count($pending_shops) > 0): ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($pending_shops as $shop): ?>
                                    <div class="list-group-item px-0 py-3 border-bottom">
                                        <div class="d-flex w-100 justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-1 fw-bold"><?php echo escape($shop['shop_name']); ?></h6>
                                                <p class="mb-1 small text-muted">Owner: <?php echo escape($shop['owner_name']); ?></p>
                                                <small class="text-muted"><i class="bi bi-telephone"></i> <?php echo escape($shop['phone']); ?></small>
                                            </div>
                                            <form method="POST" action="shops.php" class="d-inline">
                                                <?php echo csrf_input(); ?>
                                                <input type="hidden" name="id" value="<?php echo $shop['shop_id']; ?>">
                                                <input type="hidden" name="action" value="activate">
                                                <button type="submit" class="btn btn-sm btn-success rounded-pill px-3">Quick Activate</button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4 text-muted">
                                <i class="bi bi-check-circle display-4 mb-3 d-block opacity-25"></i>
                                All caught up! No shops awaiting approval.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6 mt-4 mt-lg-0">
                 <!-- Quick Links -->
                 <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white pb-0 border-0 pt-4 px-4">
                        <h5 class="fw-bold">Administrative Tools</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="d-grid gap-3">
                            <a href="users.php" class="btn btn-outline-primary py-3 text-start fs-5">
                                <i class="bi bi-people me-3 fs-4 w-25"></i> Manage Users Database
                            </a>
                            <a href="shops.php" class="btn btn-outline-primary py-3 text-start fs-5">
                                <i class="bi bi-shop-window me-3 fs-4 w-25"></i> Cafe Partner Management
                            </a>
                            <button class="btn btn-outline-secondary py-3 text-start fs-5" disabled>
                                <i class="bi bi-file-earmark-bar-graph me-3 fs-4 w-25"></i> Generate Reports (Coming Soon)
                            </button>
                            <button class="btn btn-outline-secondary py-3 text-start fs-5" disabled>
                                <i class="bi bi-gear me-3 fs-4 w-25"></i> Platform Settings
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
