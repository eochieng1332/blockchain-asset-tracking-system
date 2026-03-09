<?php
require_once '../auth/auth_check.php';
checkRole(['Admin', 'Asset Manager']);

require_once '../blockchain/blockchain.php';
require_once '../logs/log_activity.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: view_assets.php");
    exit();
}

$asset_id = $_GET['id'];

// Get asset details
$sql = "SELECT * FROM assets WHERE id = ? AND deleted_at IS NULL";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $asset_id);
$stmt->execute();
$asset = $stmt->get_result()->fetch_assoc();

if (!$asset) {
    header("Location: view_assets.php");
    exit();
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed!");
    }
    
    $to_location = $conn->real_escape_string($_POST['to_location']);
    $notes = $conn->real_escape_string($_POST['notes']);
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Insert transfer record
        $transfer_sql = "INSERT INTO asset_transfers (asset_id, from_location, to_location, transferred_by, notes) 
                         VALUES (?, ?, ?, ?, ?)";
        $transfer_stmt = $conn->prepare($transfer_sql);
        $transfer_stmt->bind_param("issis", $asset_id, $asset['location'], $to_location, $_SESSION['user_id'], $notes);
        $transfer_stmt->execute();
        
        // Update asset location and status
        $update_sql = "UPDATE assets SET location = ?, status = 'Transferred', updated_at = NOW() WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("si", $to_location, $asset_id);
        $update_stmt->execute();
        
        // Create blockchain block
        $blockchain = new Blockchain($conn);
        $blockchain->addBlock($asset_id, 'Asset Transferred', $_SESSION['username']);
        
        // Log activity
        logActivity($_SESSION['user_id'], $_SESSION['username'], 'Asset Transferred', 
                   "Transferred asset {$asset['asset_tag']} from {$asset['location']} to $to_location");
        
        $conn->commit();
        $success = "Asset transferred successfully!";
        
        // Refresh asset data
        $stmt->execute();
        $asset = $stmt->get_result()->fetch_assoc();
        
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Error transferring asset: " . $e->getMessage();
    }
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transfer Asset - <?php echo htmlspecialchars($asset['asset_tag']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <!-- Navigation and Sidebar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="#"><i class="fas fa-cube me-2"></i>SBATS</a>
        </div>
    </nav>

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
                </ul>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="container-fluid">
                <h1 class="h3 mb-4">Transfer Asset: <?php echo htmlspecialchars($asset['asset_tag']); ?></h1>
                
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
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Current Asset Information</h5>
                            </div>
                            <div class="card-body">
                                <table class="table">
                                    <tr>
                                        <th>Asset Name:</th>
                                        <td><?php echo htmlspecialchars($asset['asset_name']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Current Location:</th>
                                        <td>
                                            <span class="badge bg-info fs-6">
                                                <?php echo htmlspecialchars($asset['location']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Department:</th>
                                        <td><?php echo htmlspecialchars($asset['department']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Status:</th>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $asset['status'] == 'Active' ? 'success' : 'warning'; 
                                            ?>">
                                                <?php echo $asset['status']; ?>
                                            </span>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Transfer Details</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    
                                    <div class="mb-3">
                                        <label for="to_location" class="form-label">Destination Location *</label>
                                        <input type="text" class="form-control" id="to_location" name="to_location" 
                                               placeholder="e.g., Building C - Room 302" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="notes" class="form-label">Transfer Notes</label>
                                        <textarea class="form-control" id="notes" name="notes" rows="3" 
                                                  placeholder="Reason for transfer, condition notes, etc."></textarea>
                                    </div>
                                    
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        <strong>Blockchain Notice:</strong> This transfer will create a new block in the blockchain.
                                    </div>
                                    
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="view_assets.php" class="btn btn-secondary">
                                            <i class="fas fa-times me-2"></i>Cancel
                                        </a>
                                        <button type="submit" class="btn btn-warning">
                                            <i class="fas fa-exchange-alt me-2"></i>Confirm Transfer
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>