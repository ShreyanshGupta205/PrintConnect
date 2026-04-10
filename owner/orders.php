<?php
require_once '../config/db.php';

if (!isLoggedIn() || !hasRole('owner')) {
    redirect('../login.php');
}

// Check if owner has a shop
$stmt = $pdo->prepare("SELECT shop_id FROM shops WHERE owner_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$shop = $stmt->fetch();

if (!$shop) {
    redirect('shop.php'); // Force create shop first
}

$success = '';
$error = '';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['status'];
    
    // Validate that order belongs to this shop
    $stmt_check = $pdo->prepare("SELECT order_id FROM orders WHERE order_id = ? AND shop_id = ?");
    $stmt_check->execute([$order_id, $shop['shop_id']]);
    
    if ($stmt_check->fetch()) {
        $stmt_update = $pdo->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
        if ($stmt_update->execute([$new_status, $order_id])) {
            $success = "Order #$order_id status updated to " . ucfirst($new_status) . ".";
        } else {
            $error = "Failed to update order status.";
        }
    } else {
        $error = "Invalid order.";
    }
}

// Fetch all orders for this shop
$stmt_orders = $pdo->prepare("
    SELECT o.*, u.name as customer_name, u.email as customer_email 
    FROM orders o 
    JOIN users u ON o.user_id = u.user_id 
    WHERE o.shop_id = ? 
    ORDER BY 
        CASE status
            WHEN 'pending' THEN 1
            WHEN 'printing' THEN 2
            WHEN 'ready' THEN 3
            WHEN 'completed' THEN 4
        END,
        o.created_at DESC
");
$stmt_orders->execute([$shop['shop_id']]);
$orders = $stmt_orders->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - PrintConnect Partner</title>
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
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link active" href="orders.php">Incoming Orders</a></li>
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
        <h2 class="fw-bold mb-4">Manage Orders</h2>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> <?php echo escape($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo escape($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <?php if (count($orders) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Order Info</th>
                                    <th>Customer</th>
                                    <th>Specifications</th>
                                    <th>File Download</th>
                                    <th>Update Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr class="<?php echo $order['status'] == 'pending' ? 'table-warning' : ''; ?>">
                                        <td class="ps-4">
                                            <strong>#<?php echo $order['order_id']; ?></strong><br>
                                            <small class="text-muted"><?php echo date('M d, g:i A', strtotime($order['created_at'])); ?></small>
                                        </td>
                                        <td>
                                            <div class="fw-bold"><?php echo escape($order['customer_name']); ?></div>
                                            <small class="text-muted"><a href="mailto:<?php echo escape($order['customer_email']); ?>"><?php echo escape($order['customer_email']); ?></a></small>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column gap-1">
                                                <span class="badge bg-secondary text-start w-auto">Pages: <?php echo $order['pages']; ?></span>
                                                <span class="badge <?php echo $order['print_type'] == 'color' ? 'bg-primary' : 'bg-dark'; ?> text-start">Mode: <?php echo strtoupper($order['print_type']); ?></span>
                                                <span class="badge <?php echo $order['binding'] == 'yes' ? 'bg-info text-dark' : 'bg-light text-dark border'; ?> text-start">Binding: <?php echo $order['binding'] == 'yes' ? 'Yes' : 'No'; ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <a href="../uploads/<?php echo escape($order['file_name']); ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                                                <i class="bi bi-download me-1"></i> Document
                                            </a>
                                        </td>
                                        <td class="pe-4" style="min-width: 200px;">
                                            <form method="POST" action="orders.php" class="d-flex align-items-center gap-2">
                                                <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                                <select name="status" class="form-select form-select-sm" <?php echo $order['status'] == 'completed' ? 'disabled' : ''; ?>>
                                                    <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="printing" <?php echo $order['status'] == 'printing' ? 'selected' : ''; ?>>Printing</option>
                                                    <option value="ready" <?php echo $order['status'] == 'ready' ? 'selected' : ''; ?>>Ready for Pickup</option>
                                                    <option value="completed" <?php echo $order['status'] == 'completed' ? 'selected' : ''; ?>>Completed & Picked Up</option>
                                                </select>
                                                <?php if($order['status'] != 'completed'): ?>
                                                    <button type="submit" name="update_status" class="btn btn-sm btn-success px-3">Save</button>
                                                <?php endif; ?>
                                            </form>
                                            <?php if($order['status'] == 'completed'): ?>
                                                <div class="text-success mt-1 small"><i class="bi bi-check-all"></i> Finished</div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-inbox display-1 text-muted opacity-50 mb-3"></i>
                        <h4 class="text-muted">No Orders Found</h4>
                        <p class="text-muted">Orders placed by customers will appear here.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
