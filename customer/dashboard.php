<?php
require_once '../config/db.php';

if (!isLoggedIn() || !hasRole('customer')) {
    redirect('../login.php');
}

// Fetch user's recent orders with shop details
$stmt = $pdo->prepare("
    SELECT o.*, s.shop_name 
    FROM orders o 
    JOIN shops s ON o.shop_id = s.shop_id 
    WHERE o.user_id = ? 
    ORDER BY o.created_at DESC 
    LIMIT 5
");
$stmt->execute([$_SESSION['user_id']]);
$recent_orders = $stmt->fetchAll();

// Get order stats
$stmt_stats = $pdo->prepare("
    SELECT 
        COUNT(*) as total_orders,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
        SUM(CASE WHEN status = 'ready' THEN 1 ELSE 0 END) as ready_orders
    FROM orders 
    WHERE user_id = ?
");
$stmt_stats->execute([$_SESSION['user_id']]);
$stats = $stmt_stats->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - PrintConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark pb-3 pt-3 mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold" href="dashboard.php"><i class="bi bi-printer-fill me-2"></i>PrintConnect <span class="badge bg-light text-primary ms-2 fs-6">Customer</span></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link active" href="dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="upload.php">New Print Job</a></li>
                    <li class="nav-item"><a class="nav-link" href="orders.php">My Orders</a></li>
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
        <h2 class="mb-4">Welcome back, <?php echo escape(explode(' ', $_SESSION['name'])[0]); ?>! 👋</h2>

        <!-- Stats Row -->
        <div class="row mb-5">
            <div class="col-md-4 mb-3">
                <div class="card h-100 p-3 bg-primary text-white text-center">
                    <div class="card-body">
                        <i class="bi bi-file-earmark-text display-4 mb-2"></i>
                        <h5 class="card-title mt-2">Total Orders</h5>
                        <h2 class="fw-bold mb-0"><?php echo $stats['total_orders'] ?? 0; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card h-100 p-3 bg-warning text-dark text-center">
                    <div class="card-body">
                        <i class="bi bi-hourglass-split display-4 mb-2"></i>
                        <h5 class="card-title mt-2">Pending Jobs</h5>
                        <h2 class="fw-bold mb-0"><?php echo $stats['pending_orders'] ?? 0; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card h-100 p-3 bg-success text-white text-center">
                    <div class="card-body">
                        <i class="bi bi-check2-circle display-4 mb-2"></i>
                        <h5 class="card-title mt-2">Ready for Pickup</h5>
                        <h2 class="fw-bold mb-0"><?php echo $stats['ready_orders'] ?? 0; ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions & Recent Orders Row -->
        <div class="row">
            <div class="col-lg-4 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white pb-0 border-0 pt-4 px-4">
                        <h5 class="fw-bold">Quick Actions</h5>
                    </div>
                    <div class="card-body p-4 text-center">
                        <a href="upload.php" class="btn btn-primary w-100 py-3 mb-3 fw-bold fs-5 shadow-sm">
                            <i class="bi bi-cloud-arrow-up-fill me-2 fs-4 align-middle"></i> Start New Print Job
                        </a>
                        <a href="orders.php" class="btn btn-outline-secondary w-100 py-2">
                            View All History
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-lg-8 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white pb-0 border-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0">Recent Orders</h5>
                        <a href="orders.php" class="text-decoration-none small">View All</a>
                    </div>
                    <div class="card-body p-4">
                        <?php if (count($recent_orders) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Document</th>
                                            <th>Cafe</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_orders as $order): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <i class="bi bi-file-earmark-pdf text-danger fs-4 me-2"></i>
                                                        <span class="text-truncate" style="max-width: 150px;" title="<?php echo escape($order['file_name']); ?>">
                                                            <?php echo escape($order['file_name']); ?>
                                                        </span>
                                                        <small class="text-muted ms-2">(<?php echo $order['pages']; ?> pg)</small>
                                                    </div>
                                                </td>
                                                <td><?php echo escape($order['shop_name']); ?></td>
                                                <td>
                                                    <?php 
                                                        $badgeClass = 'bg-secondary';
                                                        if ($order['status'] == 'pending') $badgeClass = 'bg-warning text-dark';
                                                        if ($order['status'] == 'printing') $badgeClass = 'bg-info text-dark';
                                                        if ($order['status'] == 'ready') $badgeClass = 'bg-success';
                                                        if ($order['status'] == 'completed') $badgeClass = 'bg-primary';
                                                    ?>
                                                    <span class="badge <?php echo $badgeClass; ?> rounded-pill px-3 py-2 text-uppercase" style="font-size: 0.75rem;">
                                                        <?php echo $order['status']; ?>
                                                    </span>
                                                </td>
                                                <td class="text-muted small">
                                                    <?php echo date('M d, Y', strtotime($order['created_at'])); ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="bi bi-folder2-open display-4 text-muted mb-3"></i>
                                <h5>No orders yet</h5>
                                <p class="text-muted">You haven't placed any print orders recently.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
