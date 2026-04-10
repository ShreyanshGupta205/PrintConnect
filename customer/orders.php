<?php
require_once '../config/db.php';

if (!isLoggedIn() || !hasRole('customer')) {
    redirect('../login.php');
}

// Fetch all orders for this user
$stmt = $pdo->prepare("
    SELECT o.*, s.shop_name, s.address, s.phone 
    FROM orders o 
    JOIN shops s ON o.shop_id = s.shop_id 
    WHERE o.user_id = ? 
    ORDER BY o.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - PrintConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark pb-3 pt-3 mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold" href="dashboard.php"><i class="bi bi-printer-fill me-2"></i>PrintConnect</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="upload.php">New Print Job</a></li>
                    <li class="nav-item"><a class="nav-link active" href="orders.php">My Orders</a></li>
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
        <h2 class="mb-4 fw-bold">Order History</h2>

        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <?php if (count($orders) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Order ID</th>
                                    <th>Document Details</th>
                                    <th>Shop Information</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td class="ps-4 fw-bold text-muted">#PC-<?php echo str_pad($order['order_id'], 5, '0', STR_PAD_LEFT); ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-file-earmark-text text-primary fs-3 me-3"></i>
                                                <div>
                                                    <h6 class="mb-0 text-truncate" style="max-width: 200px;" title="<?php echo escape($order['file_name']); ?>">
                                                        <?php 
                                                            // Usually, we'd want to store the original name in DB, but since we stored hashed:
                                                            // We just show a truncated name or 'Document' for now.
                                                            echo 'Document Uploaded'; 
                                                        ?>
                                                    </h6>
                                                    <small class="text-muted">
                                                        <?php echo $order['print_type'] == 'color' ? 'Color' : 'B&W'; ?> | 
                                                        <?php echo $order['pages']; ?> Pages | 
                                                        <?php echo $order['binding'] == 'yes' ? 'Bound' : 'Stapled'; ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <h6 class="mb-0"><?php echo escape($order['shop_name']); ?></h6>
                                            <small class="text-muted d-flex align-items-center"><i class="bi bi-geo-alt me-1"></i> <?php echo escape($order['address']); ?></small>
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
                                            <div class="d-inline-flex align-items-center badge <?php echo $badgeClass; ?> rounded-pill px-3 py-2 text-uppercase" style="font-size: 0.8rem; letter-spacing: 0.5px;">
                                                <?php if($order['status'] == 'ready') echo '<i class="bi bi-check-circle-fill me-1"></i>'; ?>
                                                <?php echo $order['status']; ?>
                                            </div>
                                            <?php if ($order['status'] == 'ready'): ?>
                                                <div class="mt-1 small text-success fw-bold">Ready for Pickup!</div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-muted">
                                            <?php echo date('M d, Y', strtotime($order['created_at'])); ?><br>
                                            <small><?php echo date('h:i A', strtotime($order['created_at'])); ?></small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <img src="https://cdni.iconscout.com/illustration/premium/thumb/empty-state-2130362-1800926.png" alt="No orders" style="width: 200px; opacity: 0.5;">
                        <h4 class="mt-4 text-muted">You have no order history</h4>
                        <p class="text-muted mb-4">When you place orders, they will appear here.</p>
                        <a href="upload.php" class="btn btn-primary px-4 rounded-pill">Place First Order</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
