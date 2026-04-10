<?php
require_once '../config/db.php';

if (!isLoggedIn() || !hasRole('admin')) {
    redirect('../login.php');
}

$success = '';
$error = '';

// Handle actions (Delete user)
// In a real application, you'd want soft deletes or careful handling of foreign keys
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'delete' && isset($_POST['id'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die("CSRF token validation failed.");
    }
    $user_id = (int)$_POST['id'];
    
    // Prevent deleting self
    if ($user_id == $_SESSION['user_id']) {
        $error = "You cannot delete your own admin account.";
    } else {
        $stmt_delete = $pdo->prepare("DELETE FROM users WHERE user_id = ? AND role != 'admin'");
        if ($stmt_delete->execute([$user_id])) {
            $success = "User successfully deleted.";
        } else {
            $error = "Failed to delete user. They might be an admin or have associated records preventing deletion.";
        }
    }
}

// Fetch users
$stmt_users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $stmt_users->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - PrintConnect Admin</title>
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
                    <li class="nav-item"><a class="nav-link active" href="users.php">Manage Users</a></li>
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
        <h2 class="fw-bold mb-4">User Management</h2>

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
                                <th class="ps-4">ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Joined Date</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td class="ps-4 text-muted">#<?php echo $user['user_id']; ?></td>
                                    <td class="fw-bold"><?php echo escape($user['name']); ?>
                                        <?php if($user['user_id'] == $_SESSION['user_id']) echo '<span class="badge bg-primary ms-2">You</span>'; ?>
                                    </td>
                                    <td><a href="mailto:<?php echo escape($user['email']); ?>"><?php echo escape($user['email']); ?></a></td>
                                    <td>
                                        <?php 
                                            $roleClass = 'bg-secondary';
                                            if ($user['role'] == 'admin') $roleClass = 'bg-danger';
                                            if ($user['role'] == 'owner') $roleClass = 'bg-warning text-dark';
                                            if ($user['role'] == 'customer') $roleClass = 'bg-success';
                                        ?>
                                        <span class="badge <?php echo $roleClass; ?> px-2 py-1 text-uppercase" style="font-size: 0.75rem;"><?php echo $user['role']; ?></span>
                                    </td>
                                    <td class="text-muted small"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                    <td class="text-end pe-4">
                                        <?php if ($user['role'] != 'admin'): ?>
                                            <form method="POST" style="display:inline;">
                                                <?php echo csrf_input(); ?>
                                                <input type="hidden" name="id" value="<?php echo $user['user_id']; ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <button type="submit" 
                                                   class="btn btn-sm btn-outline-danger"
                                                   onclick="return confirm('Are you sure you want to delete this user? This will also delete their orders/shops.');">
                                                    <i class="bi bi-trash3"></i> Delete
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-outline-secondary" disabled>Protected</button>
                                        <?php endif; ?>
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
