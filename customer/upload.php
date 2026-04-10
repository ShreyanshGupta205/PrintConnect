<?php
require_once '../config/db.php';

if (!isLoggedIn() || !hasRole('customer')) {
    redirect('../login.php');
}

// Fetch active shops for selection
$stmt_shops = $pdo->query("SELECT shop_id, shop_name, address FROM shops WHERE status = 'active' ORDER BY shop_name ASC");
$shops = $stmt_shops->fetchAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shop_id = $_POST['shop_id'];
    $pages = $_POST['pages'];
    $print_type = $_POST['print_type'];
    $binding = $_POST['binding'];
    
    // File upload logic
    if (isset($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['document']['tmp_name'];
        $fileName = $_FILES['document']['name'];
        $fileSize = $_FILES['document']['size'];
        $fileType = $_FILES['document']['type'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));
        
        $allowedfileExtensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
        
        if (in_array($fileExtension, $allowedfileExtensions)) {
            // Check size (10MB limit)
            if ($fileSize < 10485760) {
                // Sanitize file name
                $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
                $uploadFileDir = '../uploads/';
                $dest_path = $uploadFileDir . $newFileName;
                
                if (move_uploaded_file($fileTmpPath, $dest_path)) {
                    // Create Order in DB
                    $stmt = $pdo->prepare("INSERT INTO orders (user_id, shop_id, file_name, pages, print_type, binding, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
                    if ($stmt->execute([$_SESSION['user_id'], $shop_id, $newFileName, $pages, $print_type, $binding])) {
                        $success = "Order placed successfully! The shop has been notified.";
                    } else {
                        $error = "Database error. Failed to save order.";
                    }
                } else {
                    $error = "There was an error moving the uploaded file. Ensure upload directory has write permissions.";
                }
            } else {
                $error = "File is too large. Maximum size is 10MB.";
            }
        } else {
             $error = "Upload failed. Allowed file types: " . implode(',', $allowedfileExtensions);
        }
    } else {
        $error = "There was an error with the file upload.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Print Job - PrintConnect</title>
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
                    <li class="nav-item"><a class="nav-link active" href="upload.php">New Print Job</a></li>
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
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="d-flex align-items-center mb-4">
                    <a href="dashboard.php" class="btn btn-outline-secondary rounded-circle me-3" style="width: 40px; height: 40px; padding: 0.375rem;"><i class="bi bi-arrow-left"></i></a>
                    <h2 class="mb-0 fw-bold">Create New Print Job</h2>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo escape($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i> <?php echo escape($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="card shadow-sm border-0">
                    <div class="card-body p-4 p-md-5">
                        <form action="upload.php" method="POST" enctype="multipart/form-data">
                            
                            <h5 class="fw-bold mb-3 text-primary"><i class="bi bi-1-circle-fill me-2"></i>Select Cyber Cafe</h5>
                            <div class="mb-4">
                                <select class="form-select form-select-lg bg-light" name="shop_id" required>
                                    <option value="" disabled selected>Choose a nearby cafe...</option>
                                    <?php foreach ($shops as $shop): ?>
                                        <option value="<?php echo $shop['shop_id']; ?>">
                                            <?php echo escape($shop['shop_name']) . ' - ' . escape($shop['address']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (empty($shops)): ?>
                                    <div class="form-text text-danger">No active shops available right now.</div>
                                <?php endif; ?>
                            </div>

                            <hr class="my-4 text-muted">

                            <h5 class="fw-bold mb-3 text-primary"><i class="bi bi-2-circle-fill me-2"></i>Upload File</h5>
                            <div class="mb-4">
                                <!-- Standard File Input (can use JS later to make it a drag & drop zone) -->
                                <div class="drop-zone bg-light">
                                    <span class="drop-zone__prompt">Drag & Drop file or click to browse</span>
                                    <input type="file" name="document" class="drop-zone__input" required accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                                </div>
                                <div class="form-text mt-2"><i class="bi bi-info-circle me-1"></i> Max size: 10MB. Supported formats: PDF, DOCX, JPG.</div>
                            </div>

                            <hr class="my-4 text-muted">

                            <h5 class="fw-bold mb-3 text-primary"><i class="bi bi-3-circle-fill me-2"></i>Print Settings</h5>
                            
                            <div class="row mb-4">
                                <div class="col-md-6 mb-3 mb-md-0">
                                    <label class="form-label fw-semibold">Number of Pages expected (Total output)</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-file-earmark"></i></span>
                                        <input type="number" class="form-control" name="pages" min="1" value="1" required>
                                    </div>
                                    <div class="form-text">Estimate the number of pages to be printed.</div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Color Mode</label>
                                    <div class="d-flex">
                                        <div class="form-check me-4">
                                            <input class="form-check-input" type="radio" name="print_type" id="bw" value="bw" checked>
                                            <label class="form-check-label" for="bw">Black & White</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="print_type" id="color" value="color">
                                            <label class="form-check-label" for="color">Color</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-5">
                                <label class="form-label fw-semibold">Binding Required?</label>
                                <div class="d-flex">
                                    <div class="form-check me-4">
                                        <input class="form-check-input" type="radio" name="binding" id="bindNo" value="no" checked>
                                        <label class="form-check-label" for="bindNo">No, just staple</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="binding" id="bindYes" value="yes">
                                        <label class="form-check-label" for="bindYes">Yes, spiral binding</label>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg w-100 py-3 shadow-sm rounded-pill fw-bold" <?php echo empty($shops) ? 'disabled' : ''; ?>>
                                <i class="bi bi-send-fill me-2"></i> Submit Print Order
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Simple script to handle custom dropzone UI
        document.querySelectorAll(".drop-zone__input").forEach((inputElement) => {
            const dropZoneElement = inputElement.closest(".drop-zone");

            dropZoneElement.addEventListener("click", (e) => {
                inputElement.click();
            });

            inputElement.addEventListener("change", (e) => {
                if (inputElement.files.length) {
                    updateThumbnail(dropZoneElement, inputElement.files[0]);
                }
            });

            dropZoneElement.addEventListener("dragover", (e) => {
                e.preventDefault();
                dropZoneElement.classList.add("drop-zone--over");
            });

            ["dragleave", "dragend"].forEach((type) => {
                dropZoneElement.addEventListener(type, (e) => {
                    dropZoneElement.classList.remove("drop-zone--over");
                });
            });

            dropZoneElement.addEventListener("drop", (e) => {
                e.preventDefault();

                if (e.dataTransfer.files.length) {
                    inputElement.files = e.dataTransfer.files;
                    updateThumbnail(dropZoneElement, e.dataTransfer.files[0]);
                }

                dropZoneElement.classList.remove("drop-zone--over");
            });
        });

        function updateThumbnail(dropZoneElement, file) {
            let thumbnailElement = dropZoneElement.querySelector(".drop-zone__thumb");

            // First time - remove the prompt
            if (dropZoneElement.querySelector(".drop-zone__prompt")) {
                dropZoneElement.querySelector(".drop-zone__prompt").remove();
            }

            // First time - there is no thumbnail element, so lets create it
            if (!thumbnailElement) {
                thumbnailElement = document.createElement("div");
                thumbnailElement.classList.add("drop-zone__thumb");
                dropZoneElement.appendChild(thumbnailElement);
                // Basic styling for the thumb
                thumbnailElement.style.padding = "20px";
                thumbnailElement.style.fontWeight = "bold";
                thumbnailElement.style.color = "#4361ee";
            }

            thumbnailElement.dataset.label = file.name;
            thumbnailElement.innerHTML = `<i class="bi bi-file-earmark-check-fill d-block fs-1"></i> \${file.name}`;
        }
    </script>
</body>
</html>
