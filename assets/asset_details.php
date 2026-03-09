<?php
require_once '../auth/auth_check.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: view_assets.php");
    exit();
}

$asset_id = $_GET['id'];

// Get asset details
$sql = "SELECT a.*, u.username as creator_name 
        FROM assets a 
        LEFT JOIN users u ON a.created_by = u.id 
        WHERE a.id = ? AND a.deleted_at IS NULL";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $asset_id);
$stmt->execute();
$asset = $stmt->get_result()->fetch_assoc();

if (!$asset) {
    header("Location: view_assets.php");
    exit();
}

// Get blockchain history
$blockchain_sql = "SELECT * FROM blockchain WHERE asset_id = ? ORDER BY block_index ASC";
$blockchain_stmt = $conn->prepare($blockchain_sql);
$blockchain_stmt->bind_param("i", $asset_id);
$blockchain_stmt->execute();
$blockchain_history = $blockchain_stmt->get_result();

// Get transfer history
$transfer_sql = "SELECT at.*, u.username as transferrer 
                 FROM asset_transfers at 
                 LEFT JOIN users u ON at.transferred_by = u.id 
                 WHERE at.asset_id = ? 
                 ORDER BY at.transfer_timestamp DESC";
$transfer_stmt = $conn->prepare($transfer_sql);
$transfer_stmt->bind_param("i", $asset_id);
$transfer_stmt->execute();
$transfers = $transfer_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asset Details - <?php echo htmlspecialchars($asset['asset_tag']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <!-- Navigation and Sidebar (same as before) -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="#"><i class="fas fa-cube me-2"></i>SBATS</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
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
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3">Asset Details: <?php echo htmlspecialchars($asset['asset_tag']); ?></h1>
                    <div>
                        <a href="edit_asset.php?id=<?php echo $asset_id; ?>" class="btn btn-primary">
                            <i class="fas fa-edit me-2"></i>Edit Asset
                        </a>
                        <a href="transfer_asset.php?id=<?php echo $asset_id; ?>" class="btn btn-warning">
                            <i class="fas fa-exchange-alt me-2"></i>Transfer
                        </a>
                    </div>
                </div>

                <!-- Asset Information Card -->
                <div class="row">
                    <div class="col-md-8">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Asset Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="text-muted">Asset Tag</label>
                                        <p class="h5"><?php echo htmlspecialchars($asset['asset_tag']); ?></p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="text-muted">Asset Name</label>
                                        <p class="h5"><?php echo htmlspecialchars($asset['asset_name']); ?></p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="text-muted">Category</label>
                                        <p><?php echo htmlspecialchars($asset['category']); ?></p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="text-muted">Serial Number</label>
                                        <p><?php echo htmlspecialchars($asset['serial_number']); ?></p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="text-muted">Location</label>
                                        <p><?php echo htmlspecialchars($asset['location']); ?></p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="text-muted">Department</label>
                                        <p><?php echo htmlspecialchars($asset['department']); ?></p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="text-muted">Status</label>
                                        <p>
                                            <span class="badge bg-<?php 
                                                echo $asset['status'] == 'Active' ? 'success' : 
                                                    ($asset['status'] == 'In Maintenance' ? 'warning' : 
                                                    ($asset['status'] == 'Transferred' ? 'info' : 'secondary')); 
                                            ?> fs-6">
                                                <?php echo $asset['status']; ?>
                                            </span>
                                        </p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="text-muted">Created By</label>
                                        <p><?php echo htmlspecialchars($asset['creator_name'] ?: 'System'); ?></p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="text-muted">Created Date</label>
                                        <p><?php echo date('F d, Y H:i', strtotime($asset['created_at'])); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Blockchain History -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Blockchain Transaction History</h5>
                            </div>
                            <div class="card-body">
                                <div class="timeline">
                                    <?php while($block = $blockchain_history->fetch_assoc()): ?>
                                    <div class="timeline-item">
                                        <div class="timeline-badge bg-primary">
                                            <i class="fas fa-cube"></i>
                                        </div>
                                        <div class="timeline-content">
                                            <h6>Block #<?php echo $block['block_index']; ?> - <?php echo $block['action']; ?></h6>
                                            <p class="text-muted mb-1">
                                                <small>
                                                    <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($block['user']); ?>
                                                    <i class="fas fa-clock ms-3 me-1"></i><?php echo date('M d, Y H:i:s', strtotime($block['timestamp'])); ?>
                                                </small>
                                            </p>
                                            <div class="mt-2 p-2 bg-light rounded">
                                                <small class="text-muted d-block">
                                                    <strong>Previous Hash:</strong> 
                                                    <span class="font-monospace"><?php echo substr($block['previous_hash'], 0, 20); ?>...</span>
                                                </small>
                                                <small class="text-muted d-block">
                                                    <strong>Current Hash:</strong> 
                                                    <span class="font-monospace"><?php echo substr($block['hash'], 0, 20); ?>...</span>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endwhile; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Transfer History -->
                        <?php if ($transfers->num_rows > 0): ?>
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Transfer History</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>From</th>
                                                <th>To</th>
                                                <th>Transferred By</th>
                                                <th>Notes</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while($transfer = $transfers->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo date('M d, Y H:i', strtotime($transfer['transfer_timestamp'])); ?></td>
                                                <td><?php echo htmlspecialchars($transfer['from_location']); ?></td>
                                                <td><?php echo htmlspecialchars($transfer['to_location']); ?></td>
                                                <td><?php echo htmlspecialchars($transfer['transferrer']); ?></td>
                                                <td><?php echo htmlspecialchars($transfer['notes']); ?></td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Sidebar Cards -->
                    <div class="col-md-4">
                        <!-- QR Code -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">QR Code</h5>
                            </div>
                            <div class="card-body text-center">
                                <?php
                                $qr_path = "../qr/qrcodes/asset_{$asset_id}.png";
                                if (file_exists($qr_path)):
                                ?>
                                <img src="<?php echo $qr_path; ?>" alt="QR Code" class="img-fluid mb-3" style="max-width: 200px;">
                                <p class="text-muted small">Scan to verify asset</p>
                                <a href="<?php echo $qr_path; ?>" download class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-download me-2"></i>Download QR
                                </a>
                                <?php else: ?>
                                <p class="text-muted">QR code not generated</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Barcode -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Barcode</h5>
                            </div>
                            <div class="card-body text-center">
                                <svg id="barcode"></svg>
                                <p class="text-muted small mt-2"><?php echo htmlspecialchars($asset['asset_tag']); ?></p>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Quick Actions</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <a href="transfer_asset.php?id=<?php echo $asset_id; ?>" class="btn btn-warning">
                                        <i class="fas fa-exchange-alt me-2"></i>Transfer Asset
                                    </a>
                                    <a href="asset_timeline.php?id=<?php echo $asset_id; ?>" class="btn btn-info">
                                        <i class="fas fa-history me-2"></i>View Timeline
                                    </a>
                                    <a href="../verify/verify_asset.php?id=<?php echo $asset_id; ?>" class="btn btn-success">
                                        <i class="fas fa-check-circle me-2"></i>Verify on Blockchain
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Generate barcode
        JsBarcode("#barcode", "<?php echo $asset['asset_tag']; ?>", {
            format: "CODE128",
            lineColor: "#000",
            width: 2,
            height: 60,
            displayValue: true
        });
    </script>
</body>
</html>