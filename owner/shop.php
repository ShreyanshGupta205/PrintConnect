<?php
require_once '../config/db.php';

if (!isLoggedIn() || !hasRole('owner')) {
    redirect('../login.php');
}

$success = '';
$error = '';

// Check if owner already has a shop
$stmt = $pdo->prepare("SELECT * FROM shops WHERE owner_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$shop = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shop_name = trim($_POST['shop_name']);
    $address = trim($_POST['address']);
    $phone = trim($_POST['phone']);
    
    // Simple validation
    if (empty($shop_name) || empty($address) || empty($phone)) {
        $error = "All fields are required.";
    } else {
        if ($shop) {
            // Update existing shop
            $stmt_update = $pdo->prepare("UPDATE shops SET shop_name = ?, address = ?, phone = ? WHERE shop_id = ?");
            if ($stmt_update->execute([$shop_name, $address, $phone, $shop['shop_id']])) {
                $success = "Shop profile updated successfully.";
                // Refresh shop datasi
                $shop['shop_name'] = $shop_name;
                $shop['address'] = $address;
                $shop['phone'] = $phone;
            } else {
                $error = "Failed to update shop profile.";
            }
        } else {
            // Create new shop
            // Requires admin approval/subscription to become active, but created as inactive
            $stmt_insert = $pdo->prepare("INSERT INTO shops (owner_id, shop_name, address, phone, status) VALUES (?, ?, ?, ?, 'inactive')");
            if ($stmt_insert->execute([$_SESSION['user_id'], $shop_name, $address, $phone])) {
                $success = "Shop created successfully! An admin will review and activate it, or you may need to add a subscription.";
                
                // Refresh data
                $stmt->execute([$_SESSION['user_id']]);
                $shop = $stmt->fetch();
            } else {
                $error = "Failed to create shop.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Shop - PrintConnect Partner</title>
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
                    <?php if ($shop): ?>
                        <li class="nav-item"><a class="nav-link" href="orders.php">Incoming Orders</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link active" href="shop.php">Manage Shop</a></li>
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
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-lg border-0">
                    <div class="card-header bg-white pt-4 pb-2 border-0">
                        <h3 class="fw-bold mb-0 text-center"><?php echo $shop ? 'Shop Profile' : 'Setup Your Shop'; ?></h3>
                    </div>
                    <div class="card-body p-4 p-md-5 pt-0">
                        
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

                        <?php if ($shop && $shop['status'] == 'inactive'): ?>
                            <div class="alert alert-warning">
                                <i class="bi bi-info-circle-fill me-2"></i> 
                                <strong>Your shop is currently inactive.</strong> Customers cannot see it or place orders. Please contact an admin to activate your shop or check your subscription status.
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="shop.php">
                            <div class="mb-4">
                                <label for="shop_name" class="form-label fw-bold">Shop Name</label>
                                <input type="text" class="form-control form-control-lg" id="shop_name" name="shop_name" 
                                    value="<?php echo $shop ? escape($shop['shop_name']) : ''; ?>" required placeholder="e.g. Rapid Print Cyber Cafe">
                            </div>
                            
                            <div class="mb-4">
                                <label for="phone" class="form-label fw-bold">Contact Phone Number</label>
                                <input type="text" class="form-control" id="phone" name="phone" 
                                    value="<?php echo $shop ? escape($shop['phone']) : ''; ?>" required placeholder="For customers to contact you">
                            </div>

                            <div class="mb-4">
                                <label for="address" class="form-label fw-bold">Physical Address</label>
                                <textarea class="form-control" id="address" name="address" rows="3" required placeholder="Full address for map/location purposes"><?php echo $shop ? escape($shop['address']) : ''; ?></textarea>
                                <div class="form-text">Make it detailed so customers can find you easily for pickups.</div>
                            </div>
                            
                            <?php if ($shop): ?>
                                <div class="mb-4 border p-3 rounded bg-light">
                                    <h6 class="fw-bold mb-2">Shop Statistics</h6>
                                    <div class="d-flex justify-content-between text-muted small">
                                        <span>Status: <strong class="<?php echo $shop['status'] == 'active' ? 'text-success' : 'text-danger'; ?>"><?php echo strtoupper($shop['status']); ?></strong></span>
                                        <span>Joined: <?php echo date('M Y', strtotime($shop['created_at'])); ?></span>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="d-grid mt-5">
                                <button type="submit" class="btn btn-primary btn-lg rounded-pill shadow-sm">
                                    <?php echo $shop ? 'Save Changes' : 'Create Shop'; ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
