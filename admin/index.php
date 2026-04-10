<?php
require_once '../config/db.php';

if (!isLoggedIn() || !hasRole('admin')) {
    redirect('../login.php');
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die("CSRF token validation failed.");
    }
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'delete_user') {
            $id = (int)$_POST['user_id'];
            $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
            if ($stmt->execute([$id])) $success = "User successfully deleted.";
        }
        elseif ($action === 'update_shop') {
            $id = (int)$_POST['shop_id'];
            $status = $_POST['status'];
            $stmt = $pdo->prepare("UPDATE shops SET status = ? WHERE shop_id = ?");
            if ($stmt->execute([$status, $id])) $success = "Shop status updated.";
        }
        elseif ($action === 'update_order') {
            $id = (int)$_POST['order_id'];
            $status = $_POST['status'];
            $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
            if ($stmt->execute([$status, $id])) $success = "Order status updated.";
        }
        elseif ($action === 'update_sub') {
            $id = (int)$_POST['sub_id'];
            $status = $_POST['payment_status'];
            $stmt = $pdo->prepare("UPDATE subscriptions SET payment_status = ? WHERE sub_id = ?");
            if ($stmt->execute([$status, $id])) $success = "Subscription status updated.";
        }
    } catch (PDOException $e) {
        $error = "Database Error: " . $e->getMessage();
    }
}

// Fetch Data
$users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
$shops = $pdo->query("SELECT s.*, u.name as owner_name FROM shops s JOIN users u ON s.owner_id = u.user_id ORDER BY s.created_at DESC")->fetchAll();
$orders = $pdo->query("SELECT o.*, u.name as customer_name, s.shop_name FROM orders o JOIN users u ON o.user_id = u.user_id JOIN shops s ON o.shop_id = s.shop_id ORDER BY o.created_at DESC")->fetchAll();
try {
    $subscriptions = $pdo->query("SELECT sub.*, s.shop_name FROM subscriptions sub JOIN shops s ON sub.shop_id = s.shop_id ORDER BY sub.created_at DESC")->fetchAll();
} catch (PDOException $e) {
    $subscriptions = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Admin Interactive - PrintConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        .table-wrapper {
            max-height: 400px;
            overflow-y: auto;
        }
        thead th {
            position: sticky;
            top: 0;
            background-color: #f8f9fa !important;
            z-index: 1;
        }
    </style>
</head>
<body class="bg-light align-items-start">
    <!-- Navbar similar to main site but distinct for master -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark pb-3 pt-3 mb-4 shadow">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-bold" href="index.php"><i class="bi bi-hdd-network-fill me-2"></i>PrintConnect <span class="badge bg-danger ms-2 fs-6">Master Control Room</span></a>
            <div class="ms-auto d-flex align-items-center">
                <a href="../index.php" class="btn btn-outline-light btn-sm">Exit</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4 pb-5">
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> <?php echo escape($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo escape($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Users -->
            <div class="col-xl-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white py-3 border-bottom border-light">
                        <h5 class="mb-0 fw-bold"><i class="bi bi-people text-primary me-2"></i>Users Directory</h5>
                    </div>
                    <div class="card-body p-0 table-wrapper">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-secondary">
                                <tr>
                                    <th class="ps-4">User Details</th>
                                    <th>Role</th>
                                    <th class="text-end pe-4">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $u): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold"><?php echo escape($u['name']); ?> <span class="text-muted small fw-normal">#<?php echo $u['user_id']; ?></span></div>
                                        <div class="small text-muted"><i class="bi bi-envelope"></i> <?php echo escape($u['email']); ?></div>
                                    </td>
                                    <td>
                                        <?php 
                                            $rc = 'bg-secondary';
                                            if($u['role']=='admin') $rc = 'bg-danger';
                                            if($u['role']=='owner') $rc = 'bg-warning text-dark';
                                            if($u['role']=='customer') $rc = 'bg-success';
                                        ?>
                                        <span class="badge <?php echo $rc; ?> text-uppercase"><?php echo $u['role']; ?></span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete this user completely? (Causes cascade deletion of shops/orders) \n\nAre you sure?');">
                                            <?php echo csrf_input(); ?>
                                            <input type="hidden" name="action" value="delete_user">
                                            <input type="hidden" name="user_id" value="<?php echo $u['user_id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Delete</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Shops -->
            <div class="col-xl-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white py-3 border-bottom border-light">
                        <h5 class="mb-0 fw-bold"><i class="bi bi-shop text-success me-2"></i>Shop Management</h5>
                    </div>
                    <div class="card-body p-0 table-wrapper">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-secondary">
                                <tr>
                                    <th class="ps-4">Shop Details</th>
                                    <th>Status View</th>
                                    <th class="text-end pe-4">Update Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($shops as $s): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold"><?php echo escape($s['shop_name']); ?> <span class="text-muted small fw-normal">#<?php echo $s['shop_id']; ?></span></div>
                                        <div class="small text-muted"><i class="bi bi-person"></i> <?php echo escape($s['owner_name']); ?></div>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $s['status'] == 'active' ? 'bg-success' : 'bg-secondary'; ?>">
                                            <?php echo strtoupper($s['status']); ?>
                                        </span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <form method="POST" class="d-flex align-items-center justify-content-end gap-2">
                                            <?php echo csrf_input(); ?>
                                            <input type="hidden" name="action" value="update_shop">
                                            <input type="hidden" name="shop_id" value="<?php echo $s['shop_id']; ?>">
                                            <select name="status" class="form-select form-select-sm" style="width: auto;">
                                                <option value="active" <?php echo $s['status']=='active'?'selected':''; ?>>Active</option>
                                                <option value="inactive" <?php echo $s['status']=='inactive'?'selected':''; ?>>Inactive</option>
                                            </select>
                                            <button type="submit" class="btn btn-sm btn-primary">Save</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Orders -->
            <div class="col-xl-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white py-3 border-bottom border-light">
                        <h5 class="mb-0 fw-bold"><i class="bi bi-box-seam text-info me-2"></i>Order Control Center</h5>
                    </div>
                    <div class="card-body p-0 table-wrapper">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-secondary">
                                <tr>
                                    <th class="ps-4">Order Record</th>
                                    <th>Current State</th>
                                    <th class="text-end pe-4">Force Override Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $o): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold">#<?php echo $o['order_id']; ?> - <?php echo escape($o['customer_name']); ?></div>
                                        <div class="small text-muted">Shop: <?php echo escape($o['shop_name']); ?> | <i class="bi bi-file-earmark"></i> <?php echo $o['pages']; ?>pg</div>
                                    </td>
                                    <td>
                                        <?php 
                                            $bClass = 'bg-secondary';
                                            if($o['status'] == 'pending') $bClass = 'bg-warning text-dark';
                                            if($o['status'] == 'printing') $bClass = 'bg-info text-dark';
                                            if($o['status'] == 'ready') $bClass = 'bg-success';
                                            if($o['status'] == 'completed') $bClass = 'bg-primary';
                                        ?>
                                        <span class="badge <?php echo $bClass; ?>"><?php echo strtoupper($o['status']); ?></span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <form method="POST" class="d-flex align-items-center justify-content-end gap-2">
                                            <?php echo csrf_input(); ?>
                                            <input type="hidden" name="action" value="update_order">
                                            <input type="hidden" name="order_id" value="<?php echo $o['order_id']; ?>">
                                            <select name="status" class="form-select form-select-sm" style="width: auto;">
                                                <option value="pending" <?php echo $o['status']=='pending'?'selected':''; ?>>Pending</option>
                                                <option value="printing" <?php echo $o['status']=='printing'?'selected':''; ?>>Printing</option>
                                                <option value="ready" <?php echo $o['status']=='ready'?'selected':''; ?>>Ready</option>
                                                <option value="completed" <?php echo $o['status']=='completed'?'selected':''; ?>>Completed</option>
                                            </select>
                                            <button type="submit" class="btn btn-sm btn-primary">Save</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Subscriptions -->
            <div class="col-xl-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white py-3 border-bottom border-light">
                        <h5 class="mb-0 fw-bold"><i class="bi bi-wallet2 text-warning me-2"></i>Subscriptions Engine</h5>
                    </div>
                    <div class="card-body p-0 table-wrapper">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-secondary">
                                <tr>
                                    <th class="ps-4">Sub Instance</th>
                                    <th>State</th>
                                    <th class="text-end pe-4">Update State</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($subscriptions as $sub): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold"><?php echo escape($sub['shop_name']); ?> <span class="text-muted small fw-normal">#<?php echo $sub['sub_id']; ?></span></div>
                                        <div class="small fw-semibold text-success">$<?php echo number_format($sub['amount'], 2); ?></div>
                                        <div class="small text-muted"><i class="bi bi-calendar"></i> <?php echo date('M d', strtotime($sub['end_date'])); ?></div>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $sub['payment_status'] == 'completed' ? 'bg-success' : ($sub['payment_status'] == 'failed' ? 'bg-danger' : 'bg-warning text-dark'); ?>">
                                            <?php echo strtoupper($sub['payment_status']); ?>
                                        </span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <form method="POST" class="d-flex align-items-center justify-content-end gap-2">
                                            <?php echo csrf_input(); ?>
                                            <input type="hidden" name="action" value="update_sub">
                                            <input type="hidden" name="sub_id" value="<?php echo $sub['sub_id']; ?>">
                                            <select name="payment_status" class="form-select form-select-sm" style="width: auto;">
                                                <option value="pending" <?php echo $sub['payment_status']=='pending'?'selected':''; ?>>Pending</option>
                                                <option value="completed" <?php echo $sub['payment_status']=='completed'?'selected':''; ?>>Completed</option>
                                                <option value="failed" <?php echo $sub['payment_status']=='failed'?'selected':''; ?>>Failed</option>
                                            </select>
                                            <button type="submit" class="btn btn-sm btn-primary">Save</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
