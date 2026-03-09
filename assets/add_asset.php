<?php
require_once '../auth/auth_check.php';
checkRole(['Admin', 'Asset Manager']);

require_once '../blockchain/blockchain.php';
require_once '../qr/generate_qr.php';
require_once '../logs/log_activity.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed!");
    }
    
    // Sanitize and validate inputs
    $asset_tag = $conn->real_escape_string($_POST['asset_tag']);
    $asset_name = $conn->real_escape_string($_POST['asset_name']);
    $category = $conn->real_escape_string($_POST['category']);
    $serial_number = $conn->real_escape_string($_POST['serial_number']);
    $location = $conn->real_escape_string($_POST['location']);
    $department = $conn->real_escape_string($_POST['department']);
    $status = $conn->real_escape_string($_POST['status']);
    
    // Check if asset tag or serial number already exists
    $checkSql = "SELECT id FROM assets WHERE asset_tag = ? OR serial_number = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("ss", $asset_tag, $serial_number);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        $error = "Asset tag or serial number already exists!";
    } else {
        // Insert asset
        $sql = "INSERT INTO assets (asset_tag, asset_name, category, serial_number, location, department, status, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssi", $asset_tag, $asset_name, $category, $serial_number, $location, $department, $status, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            $asset_id = $stmt->insert_id;
            
            // Create blockchain block
            $blockchain = new Blockchain($conn);
            $blockchain->addBlock($asset_id, 'Asset Created', $_SESSION['username']);
            
            // Generate QR code
            generateQRCode($asset_id, $asset_tag);
            
            // Log activity
            logActivity($_SESSION['user_id'], $_SESSION['username'], 'Asset Created', "Created asset: $asset_tag - $asset_name");
            
            $success = "Asset created successfully!";
        } else {
            $error = "Error creating asset: " . $conn->error;
        }
    }
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Asset - SBATS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <!-- Navigation (same as dashboard) -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="#"><i class="fas fa-cube me-2"></i>SBATS</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i><?php echo htmlspecialchars($_SESSION['full_name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="../auth/logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Sidebar (same as dashboard) -->
    <div class="d-flex">
        <div class="sidebar bg-dark text-white">
            <div class="p-3">
                <ul class="nav nav-pills flex-column">
                    <li class="nav-item mb-2">
                        <a href="../dashboard/dashboard.php" class="nav-link text-white">
                            <i class="fas fa-home me-2"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="view_assets.php" class="nav-link text-white">
                            <i class="fas fa-boxes me-2"></i>Assets
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="add_asset.php" class="nav-link active">
                            <i class="fas fa-plus-circle me-2"></i>Add Asset
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="../explorer/blockchain_explorer.php" class="nav-link text-white">
                            <i class="fas fa-link me-2"></i>Blockchain Explorer
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="container-fluid">
                <h1 class="h3 mb-4">Add New Asset</h1>
                
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-body">
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="asset_tag" class="form-label">Asset Tag *</label>
                                    <input type="text" class="form-control" id="asset_tag" name="asset_tag" 
                                           placeholder="e.g., AST-2026-001" required>
                                    <small class="text-muted">Unique identifier for the asset</small>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="asset_name" class="form-label">Asset Name *</label>
                                    <input type="text" class="form-control" id="asset_name" name="asset_name" 
                                           placeholder="e.g., Dell XPS 15 Laptop" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="category" class="form-label">Category *</label>
                                    <select class="form-select" id="category" name="category" required>
                                        <option value="">Select Category</option>
                                        <option value="Electronics">Electronics</option>
                                        <option value="Office Equipment">Office Equipment</option>
                                        <option value="Furniture">Furniture</option>
                                        <option value="Infrastructure">Infrastructure</option>
                                        <option value="Vehicles">Vehicles</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="serial_number" class="form-label">Serial Number *</label>
                                    <input type="text" class="form-control" id="serial_number" name="serial_number" 
                                           placeholder="Manufacturer serial number" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="location" class="form-label">Location *</label>
                                    <input type="text" class="form-control" id="location" name="location" 
                                           placeholder="e.g., Building A - Room 101" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="department" class="form-label">Department *</label>
                                    <select class="form-select" id="department" name="department" required>
                                        <option value="">Select Department</option>
                                        <option value="IT Department">IT Department</option>
                                        <option value="Administration">Administration</option>
                                        <option value="Finance">Finance</option>
                                        <option value="HR">Human Resources</option>
                                        <option value="Operations">Operations</option>
                                        <option value="Sales">Sales</option>
                                        <option value="Marketing">Marketing</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="status" class="form-label">Status *</label>
                                    <select class="form-select" id="status" name="status" required>
                                        <option value="Active">Active</option>
                                        <option value="In Maintenance">In Maintenance</option>
                                        <option value="Transferred">Transferred</option>
                                        <option value="Retired">Retired</option>
                                    </select>
                                </div>
                            </div>
                            
                            <hr class="my-4">
                            
                            <div class="d-flex justify-content-end gap-2">
                                <a href="view_assets.php" class="btn btn-secondary">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Create Asset
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="alert alert-info mt-4">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Blockchain Notice:</strong> Creating this asset will generate a new block in the blockchain and a QR code for verification.
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>