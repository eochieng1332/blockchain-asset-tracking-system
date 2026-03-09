<?php
require_once '../config/db.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid asset ID");
}

$asset_id = $_GET['id'];

// Get asset details
$asset_sql = "SELECT a.*, u.username as creator_name 
              FROM assets a 
              LEFT JOIN users u ON a.created_by = u.id 
              WHERE a.id = ?";
$asset_stmt = $conn->prepare($asset_sql);
$asset_stmt->bind_param("i", $asset_id);
$asset_stmt->execute();
$asset = $asset_stmt->get_result()->fetch_assoc();

if (!$asset) {
    die("Asset not found");
}

// Get blockchain history
$blockchain_sql = "SELECT * FROM blockchain WHERE asset_id = ? ORDER BY block_index ASC";
$blockchain_stmt = $conn->prepare($blockchain_sql);
$blockchain_stmt->bind_param("i", $asset_id);
$blockchain_stmt->execute();
$blockchain = $blockchain_stmt->get_result();

// Validate blockchain for this asset
require_once '../blockchain/blockchain.php';
$bc = new Blockchain($conn);
$validation = $bc->validateChain();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asset Verification - <?php echo htmlspecialchars($asset['asset_tag']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .verification-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .verification-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .verification-header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        .verification-badge {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 50px;
            font-weight: 600;
            margin-top: 15px;
        }
        .verification-badge.valid {
            background: #10b981;
            color: white;
        }
        .verification-badge.invalid {
            background: #ef4444;
            color: white;
        }
        .asset-detail {
            padding: 15px;
            border-bottom: 1px solid #e5e7eb;
        }
        .asset-detail:last-child {
            border-bottom: none;
        }
        .detail-label {
            color: #6b7280;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .detail-value {
            font-size: 1.1rem;
            font-weight: 500;
            color: #1f2937;
        }
        .timeline {
            position: relative;
            padding: 20px 0;
        }
        .timeline::before {
            content: '';
            position: absolute;
            left: 20px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e5e7eb;
        }
        .timeline-item {
            position: relative;
            padding-left: 50px;
            margin-bottom: 25px;
        }
        .timeline-badge {
            position: absolute;
            left: 0;
            top: 0;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            z-index: 1;
        }
        .timeline-content {
            background: #f9fafb;
            padding: 15px;
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="verification-card">
                    <div class="verification-header">
                        <i class="fas fa-check-circle fa-4x mb-3"></i>
                        <h1>Asset Verification</h1>
                        <p>Blockchain Verified Asset</p>
                        <div class="verification-badge <?php echo $validation['valid'] ? 'valid' : 'invalid'; ?>">
                            <i class="fas <?php echo $validation['valid'] ? 'fa-shield-alt' : 'fa-exclamation-triangle'; ?> me-2"></i>
                            <?php echo $validation['valid'] ? 'BLOCKCHAIN VALID' : 'BLOCKCHAIN COMPROMISED'; ?>
                        </div>
                    </div>
                    
                    <div class="p-4">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="asset-detail">
                                    <div class="detail-label">Asset Tag</div>
                                    <div class="detail-value"><?php echo htmlspecialchars($asset['asset_tag']); ?></div>
                                </div>
                                <div class="asset-detail">
                                    <div class="detail-label">Asset Name</div>
                                    <div class="detail-value"><?php echo htmlspecialchars($asset['asset_name']); ?></div>
                                </div>
                                <div class="asset-detail">
                                    <div class="detail-label">Category</div>
                                    <div class="detail-value"><?php echo htmlspecialchars($asset['category']); ?></div>
                                </div>
                                <div class="asset-detail">
                                    <div class="detail-label">Serial Number</div>
                                    <div class="detail-value"><?php echo htmlspecialchars($asset['serial_number']); ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="asset-detail">
                                    <div class="detail-label">Current Location</div>
                                    <div class="detail-value"><?php echo htmlspecialchars($asset['location']); ?></div>
                                </div>
                                <div class="asset-detail">
                                    <div class="detail-label">Department</div>
                                    <div class="detail-value"><?php echo htmlspecialchars($asset['department']); ?></div>
                                </div>
                                <div class="asset-detail">
                                    <div class="detail-label">Status</div>
                                    <div class="detail-value">
                                        <span class="badge bg-<?php 
                                            echo $asset['status'] == 'Active' ? 'success' : 
                                                ($asset['status'] == 'In Maintenance' ? 'warning' : 
                                                ($asset['status'] == 'Transferred' ? 'info' : 'secondary')); 
                                        ?> fs-6">
                                            <?php echo $asset['status']; ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="asset-detail">
                                    <div class="detail-label">Created</div>
                                    <div class="detail-value"><?php echo date('F d, Y', strtotime($asset['created_at'])); ?></div>
                                </div>
                            </div>
                        </div>
                        
                        <h4 class="mb-3">Blockchain Transaction History</h4>
                        <div class="timeline">
                            <?php while($block = $blockchain->fetch_assoc()): ?>
                            <div class="timeline-item">
                                <div class="timeline-badge bg-primary">
                                    <i class="fas fa-cube"></i>
                                </div>
                                <div class="timeline-content">
                                    <div class="d-flex justify-content-between">
                                        <h6 class="mb-1">Block #<?php echo $block['block_index']; ?> - <?php echo $block['action']; ?></h6>
                                        <small class="text-muted"><?php echo date('M d, H:i', strtotime($block['timestamp'])); ?></small>
                                    </div>
                                    <p class="mb-1">User: <?php echo htmlspecialchars($block['user']); ?></p>
                                    <small class="text-muted d-block">
                                        Hash: <?php echo substr($block['hash'], 0, 30); ?>...
                                    </small>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                        
                        <div class="alert alert-info mt-4">
                            <i class="fas fa-info-circle me-2"></i>
                            This verification is powered by blockchain technology. Each transaction is cryptographically linked and immutable.
                        </div>
                        
                        <div class="text-center mt-3">
                            <a href="../assets/asset_details.php?id=<?php echo $asset_id; ?>" class="btn btn-primary">
                                <i class="fas fa-arrow-left me-2"></i>View Full Details
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>