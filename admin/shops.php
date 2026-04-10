<?php
require_once '../config/db.php';

if (!isLoggedIn() || !hasRole('admin')) {
    redirect('../login.php');
}

$success = '';
$error = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die("CSRF token validation failed.");
    }
    $shop_id = (int)$_POST['id'];
    $action = $_POST['action'];
    
    if ($action == 'activate') {
        $stmt_update = $pdo->prepare("UPDATE shops SET status = 'active' WHERE shop_id = ?");
        if ($stmt_update->execute([$shop_id])) {
            $success = "Shop status updated to ACTIVE.";
        }
    } elseif ($action == 'deactivate') {
        $stmt_update = $pdo->prepare("UPDATE shops SET status = 'inactive' WHERE shop_id = ?");
        if ($stmt_update->execute([$shop_id])) {
            $success = "Shop status updated to INACTIVE.";
        }
    } elseif ($action == 'delete') {
         $stmt_delete = $pdo->prepare("DELETE FROM shops WHERE shop_id = ?");
         if ($stmt_delete->execute([$shop_id])) {
             $success = "Shop permanently deleted.";
         } else {
             $error = "Failed to delete shop.";
         }
    }
}

// Fetch shops
$stmt_shops = $pdo->query("
    SELECT s.*, u.name as owner_name, u.email as owner_email,
        (SELECT COUNT(*) FROM orders WHERE shop_id = s.shop_id) as total_orders
    FROM shops s
    JOIN users u ON s.owner_id = u.user_id
    ORDER BY s.created_at DESC
");
$shops = $stmt_shops->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Shops - PrintConnect Admin</title>
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
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="users.php">Manage Users</a></li>
                    <li class="nav-item"><a class="nav-link active" href="shops.php">Manage Shops</a></li>
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
        <h2 class="fw-bold mb-4">Shop Directory & Moderation</h2>

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
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Shop Details</th>
                                <th>Owner</th>
                                <th>Stats</th>
                                <th>Status</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($shops as $shop): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold fs-6"><?php echo escape($shop['shop_name']); ?></div>
                                        <div class="small text-muted text-truncate" style="max-width: 250px;"><i class="bi bi-geo-alt"></i> <?php echo escape($shop['address']); ?></div>
                                        <div class="small text-muted"><i class="bi bi-telephone"></i> <?php echo escape($shop['phone']); ?></div>
                                    </td>
                                    <td>
                                        <div><?php echo escape($shop['owner_name']); ?></div>
                                        <small class="text-muted"><?php echo escape($shop['owner_email']); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-info text-dark">Orders: <?php echo $shop['total_orders']; ?></span>
                                    </td>
                                    <td>
                                        <?php if ($shop['status'] == 'active'): ?>
                                            <span class="badge bg-success rounded-pill px-3 py-2"><i class="bi bi-check-circle me-1"></i> Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger rounded-pill px-3 py-2"><i class="bi bi-x-circle me-1"></i> Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <form method="POST" style="display:inline;">
                                            <?php echo csrf_input(); ?>
                                            <input type="hidden" name="id" value="<?php echo $shop['shop_id']; ?>">
                                            <?php if ($shop['status'] == 'active'): ?>
                                                <input type="hidden" name="action" value="deactivate">
                                                <button type="submit" class="btn btn-sm btn-outline-warning">Deactivate</button>
                                            <?php else: ?>
                                                <input type="hidden" name="action" value="activate">
                                                <button type="submit" class="btn btn-sm btn-success">Activate</button>
                                            <?php endif; ?>
                                                                                        <button type="submit" name="action" value="delete"
                                               class="btn btn-sm btn-outline-danger ms-1"
                                               onclick="return confirm('Delete this shop completely? All their orders will be deleted.');">
                                                <i class="bi bi-trash"></i>
                                            </button>
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

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
