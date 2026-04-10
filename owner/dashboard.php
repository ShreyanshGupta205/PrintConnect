<?php
require_once '../config/db.php';

if (!isLoggedIn() || !hasRole('owner')) {
    redirect('../login.php');
}

// Check if owner has a shop
$stmt = $pdo->prepare("SELECT * FROM shops WHERE owner_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$shop = $stmt->fetch();

$recent_orders = [];
$stats = null;

if ($shop) {
    // Fetch stats
    $stmt_stats = $pdo->prepare("
        SELECT 
            COUNT(*) as total_orders,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
            SUM(CASE WHEN status = 'printing' THEN 1 ELSE 0 END) as printing_orders,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_orders
        FROM orders WHERE shop_id = ?
    ");
    $stmt_stats->execute([$shop['shop_id']]);
    $stats = $stmt_stats->fetch();

    // Fetch recent incoming orders
    $stmt_orders = $pdo->prepare("
        SELECT o.*, u.name as customer_name 
        FROM orders o 
        JOIN users u ON o.user_id = u.user_id 
        WHERE o.shop_id = ? 
        ORDER BY o.created_at DESC LIMIT 5
    ");
    $stmt_orders->execute([$shop['shop_id']]);
    $recent_orders = $stmt_orders->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Dashboard - PrintConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark pb-3 pt-3 mb-4" style="background: linear-gradient(135deg, #2b2d42, #1a1a24) !important;">
        <div class="container">
            <a class="navbar-brand fw-bold" href="dashboard.php"><i class="bi bi-printer-fill me-2"></i>PrintConnect <span class="badge bg-warning text-dark ms-2 fs-6">Partner</span></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link active" href="dashboard.php">Dashboard</a></li>
                    <?php if ($shop): ?>
                        <li class="nav-item"><a class="nav-link" href="orders.php">Incoming Orders</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link" href="shop.php">Manage Shop</a></li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-shop me-1"></i> <?php echo escape($_SESSION['name']); ?>
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
        
        <?php if (!$shop): ?>
            <!-- No shop created yet -->
            <div class="row justify-content-center">
                <div class="col-md-8 text-center mt-5">
                    <i class="bi bi-shop-window display-1 text-muted mb-4"></i>
                    <h2 class="fw-bold mb-3">Welcome to PrintConnect for Partners!</h2>
                    <p class="lead text-muted mb-4">To start receiving print orders from customers, you need to set up your shop profile first.</p>
                    <a href="shop.php" class="btn btn-primary btn-lg rounded-pill px-5 py-3 shadow">Create Shop Profile</a>
                </div>
            </div>
        <?php else: ?>
            <!-- Dashboard Content -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold mb-0"><?php echo escape($shop['shop_name']); ?></h2>
                    <p class="text-muted mb-0">Partner Dashboard</p>
                </div>
                <div>
                    <?php if ($shop['status'] == 'active'): ?>
                        <span class="badge bg-success fs-6"><i class="bi bi-broadcast me-1"></i> Live & Receiving Orders</span>
                    <?php else: ?>
                        <span class="badge bg-danger fs-6"><i class="bi bi-sign-stop-fill me-1"></i> Shop is Inactive</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Stats Row -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="card bg-white shadow-sm border-0 border-start border-primary border-5 h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">New Orders</div>
                                    <div class="h3 mb-0 fw-bold text-dark"><?php echo $stats['pending_orders'] ?? 0; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-bell fs-2 text-primary opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Printing -->
                <div class="col-md-3 mb-3">
                    <div class="card bg-white shadow-sm border-0 border-start border-info border-5 h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Printing Now</div>
                                    <div class="h3 mb-0 fw-bold text-dark"><?php echo $stats['printing_orders'] ?? 0; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-printer fs-2 text-info opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Completed -->
                <div class="col-md-3 mb-3">
                    <div class="card bg-white shadow-sm border-0 border-start border-success border-5 h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Completed</div>
                                    <div class="h3 mb-0 fw-bold text-dark"><?php echo $stats['completed_orders'] ?? 0; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-check-circle fs-2 text-success opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Total -->
                <div class="col-md-3 mb-3">
                    <div class="card bg-white shadow-sm border-0 border-start border-secondary border-5 h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">Total Orders (All time)</div>
                                    <div class="h3 mb-0 fw-bold text-dark"><?php echo $stats['total_orders'] ?? 0; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-bar-chart fs-2 text-secondary opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 mt-4">
                <div class="card-header bg-white py-3 d-flex flex-row align-items-center justify-content-between">
                    <h5 class="m-0 fw-bold text-dark">Recent Incoming Orders</h5>
                    <a href="orders.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <?php if (count($recent_orders) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">ID</th>
                                        <th>Customer</th>
                                        <th>File</th>
                                        <th>Specs</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th class="text-end pe-4">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_orders as $order): ?>
                                        <tr>
                                            <td class="ps-4 text-muted fw-bold">#<?php echo $order['order_id']; ?></td>
                                            <td><?php echo escape($order['customer_name']); ?></td>
                                            <td>
                                                <div class="text-truncate" style="max-width: 150px;" title="<?php echo escape($order['file_name']); ?>">
                                                    <a href="../uploads/<?php echo escape($order['file_name']); ?>" target="_blank" class="text-decoration-none"><i class="bi bi-download me-1"></i> Download</a>
                                                </div>
                                            </td>
                                            <td>
                                                <small class="badge bg-light text-dark border"><?php echo $order['pages']; ?> pg</small>
                                                <small class="badge bg-light text-dark border"><?php echo strtoupper($order['print_type']); ?></small>
                                                <?php if($order['binding'] == 'yes') echo '<small class="badge bg-dark">Bound</small>'; ?>
                                            </td>
                                            <td>
                                                <?php 
                                                    $badgeClass = 'bg-secondary';
                                                    switch ($order['status']) {
                                                        case 'pending': $badgeClass = 'bg-warning text-dark'; break;
                                                        case 'printing': $badgeClass = 'bg-info text-dark'; break;
                                                        case 'ready': $badgeClass = 'bg-success'; break;
                                                        case 'completed': $badgeClass = 'bg-primary'; break;
                                                    }
                                                ?>
                                                <span class="badge <?php echo $badgeClass; ?>"><?php echo ucfirst($order['status']); ?></span>
                                            </td>
                                            <td class="text-muted small"><?php echo date('M d, g:i a', strtotime($order['created_at'])); ?></td>
                                            <td class="text-end pe-4">
                                                <a href="orders.php" class="btn btn-sm btn-primary">Manage</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <h5 class="text-muted">No orders yet.</h5>
                            <p class="text-muted mb-0">When customers place orders, they will appear here.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
