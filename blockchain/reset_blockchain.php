<?php
require_once '../auth/auth_check.php';
checkRole(['Admin']); // Only admins can reset
require_once 'blockchain.php';

$blockchain = new Blockchain($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm']) && $_POST['confirm'] === 'yes') {
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Clear existing blockchain
        $conn->query("DELETE FROM blockchain");
        
        // Get all assets
        $assets = $conn->query("SELECT * FROM assets WHERE deleted_at IS NULL ORDER BY created_at ASC");
        
        // Rebuild blockchain
        $block_index = 0;
        $previous_hash = '0';
        
        while ($asset = $assets->fetch_assoc()) {
            // Get all actions for this asset
            $actions = [];
            
            // Add creation event
            $actions[] = [
                'action' => 'Asset Created',
                'time' => $asset['created_at'],
                'user' => 'admin' // You might want to get actual creator from asset record
            ];
            
            // Get transfers
            $transfers = $conn->query("SELECT * FROM asset_transfers WHERE asset_id = " . $asset['id'] . " ORDER BY transfer_timestamp ASC");
            while ($transfer = $transfers->fetch_assoc()) {
                // Get transferrer username
                $user_sql = "SELECT username FROM users WHERE id = " . $transfer['transferred_by'];
                $user_result = $conn->query($user_sql);
                $username = ($user_result && $user_result->num_rows > 0) ? $user_result->fetch_assoc()['username'] : 'system';
                
                $actions[] = [
                    'action' => 'Asset Transferred',
                    'time' => $transfer['transfer_timestamp'],
                    'user' => $username
                ];
            }
            
            // Sort actions by time
            usort($actions, function($a, $b) {
                return strtotime($a['time']) - strtotime($b['time']);
            });
            
            // Create blocks
            foreach ($actions as $action) {
                $timestamp = $action['time'];
                $user = $action['user'];
                
                // Calculate hash
                $hash = $blockchain->calculateHash(
                    $block_index,
                    $asset['id'],
                    $action['action'],
                    $user,
                    $timestamp,
                    $previous_hash
                );
                
                // Insert block - FIXED: Don't use bind_param with timestamp directly in the loop like that
                $insert_sql = "INSERT INTO blockchain (block_index, asset_id, action, user, timestamp, previous_hash, hash) 
                               VALUES ($block_index, {$asset['id']}, '{$action['action']}', '$user', '$timestamp', '$previous_hash', '$hash')";
                
                if (!$conn->query($insert_sql)) {
                    throw new Exception("Error inserting block: " . $conn->error);
                }
                
                $previous_hash = $hash;
                $block_index++;
            }
        }
        
        $conn->commit();
        $success = "Blockchain has been reset and is now valid!";
        
        // Log activity
        require_once '../logs/log_activity.php';
        logActivity($_SESSION['user_id'], $_SESSION['username'], 'Blockchain Reset', 'Blockchain was reset to a valid state');
        
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Error resetting blockchain: " . $e->getMessage();
    }
}

// Get current validation status
$validation = $blockchain->validateChain();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Blockchain - SBATS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="../dashboard/dashboard.php">
                <i class="fas fa-cube me-2"></i>SBATS
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i><?php echo htmlspecialchars($_SESSION['full_name']); ?>
                            <span class="badge bg-light text-dark ms-2"><?php echo $_SESSION['role']; ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="../auth/logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
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
                        <a href="../assets/view_assets.php" class="nav-link text-white">
                            <i class="fas fa-boxes me-2"></i>Assets
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="../assets/add_asset.php" class="nav-link text-white">
                            <i class="fas fa-plus-circle me-2"></i>Add Asset
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="../explorer/blockchain_explorer.php" class="nav-link text-white">
                            <i class="fas fa-link me-2"></i>Blockchain Explorer
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="validate_chain.php" class="nav-link text-white">
                            <i class="fas fa-shield-alt me-2"></i>Validate Chain
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="reset_blockchain.php" class="nav-link active">
                            <i class="fas fa-sync-alt me-2"></i>Reset Blockchain
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="container-fluid">
                <h1 class="h3 mb-4">Blockchain Reset Tool</h1>
                
                <?php if (isset($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    
                    <div class="alert alert-info mt-3">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>New Validation Result:</strong>
                        <?php 
                        $new_validation = $blockchain->validateChain();
                        echo $new_validation['message']; 
                        ?>
                    </div>
                    
                    <div class="text-center mt-4">
                        <a href="validate_chain.php" class="btn btn-primary">
                            <i class="fas fa-shield-alt me-2"></i>View Validated Chain
                        </a>
                        <a href="../explorer/blockchain_explorer.php" class="btn btn-info">
                            <i class="fas fa-link me-2"></i>Explore Blockchain
                        </a>
                    </div>
                    
                <?php elseif (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php else: ?>
                
                <div class="row">
                    <div class="col-md-8 mx-auto">
                        <!-- Current Status Card -->
                        <div class="card mb-4">
                            <div class="card-header bg-<?php echo $validation['valid'] ? 'success' : 'danger'; ?> text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-<?php echo $validation['valid'] ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                                    Current Blockchain Status: 
                                    <span class="badge bg-light text-dark">
                                        <?php echo $validation['valid'] ? 'VALID' : 'COMPROMISED'; ?>
                                    </span>
                                </h5>
                            </div>
                            <div class="card-body">
                                <p class="lead"><?php echo $validation['message']; ?></p>
                                
                                <?php if (!$validation['valid'] && isset($validation['block'])): ?>
                                    <div class="alert alert-danger mt-3">
                                        <strong>Tampered Block Detected:</strong><br>
                                        Block #<?php echo $validation['block']['block_index']; ?><br>
                                        Asset ID: <?php echo $validation['block']['asset_id']; ?><br>
                                        Action: <?php echo $validation['block']['action']; ?><br>
                                        Timestamp: <?php echo $validation['block']['timestamp']; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Statistics -->
                                <?php
                                $stats = $conn->query("
                                    SELECT 
                                        COUNT(*) as total_blocks,
                                        COUNT(DISTINCT asset_id) as unique_assets,
                                        MIN(timestamp) as first_block,
                                        MAX(timestamp) as last_block
                                    FROM blockchain
                                ")->fetch_assoc();
                                ?>
                                
                                <div class="row mt-4">
                                    <div class="col-md-3">
                                        <div class="text-center">
                                            <h3><?php echo $stats['total_blocks']; ?></h3>
                                            <small class="text-muted">Total Blocks</small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="text-center">
                                            <h3><?php echo $stats['unique_assets']; ?></h3>
                                            <small class="text-muted">Assets on Chain</small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="text-center">
                                            <h3><?php echo date('M d', strtotime($stats['first_block'])); ?></h3>
                                            <small class="text-muted">First Block</small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="text-center">
                                            <h3><?php echo date('M d', strtotime($stats['last_block'])); ?></h3>
                                            <small class="text-muted">Last Block</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Reset Confirmation Card -->
                        <div class="card border-warning">
                            <div class="card-header bg-warning">
                                <h5 class="mb-0 text-dark">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    Reset Blockchain
                                </h5>
                            </div>
                            <div class="card-body">
                                <p>This will rebuild the entire blockchain from asset and transfer records, creating a valid chain.</p>
                                
                                <div class="alert alert-danger">
                                    <i class="fas fa-skull-crosswalk me-2"></i>
                                    <strong>Warning:</strong> This action will delete all existing blockchain records and recreate them. 
                                    This is irreversible and should only be used when the blockchain is compromised!
                                </div>
                                
                                <form method="POST" onsubmit="return confirm('⚠️ ARE YOU ABSOLUTELY SURE?\n\nThis will delete all blockchain records and rebuild from scratch. This action cannot be undone!');">
                                    <input type="hidden" name="confirm" value="yes">
                                    
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="confirmCheck" required>
                                        <label class="form-check-label" for="confirmCheck">
                                            I understand that this will permanently delete all existing blockchain data
                                        </label>
                                    </div>
                                    
                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-danger btn-lg" id="resetBtn" disabled>
                                            <i class="fas fa-sync-alt me-2"></i>Reset Blockchain Now
                                        </button>
                                        <a href="validate_chain.php" class="btn btn-secondary">
                                            <i class="fas fa-times me-2"></i>Cancel
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Information Card -->
                        <div class="card mt-4">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Why Reset?
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Common reasons for compromise:</h6>
                                        <ul class="small">
                                            <li>Manual editing of database records</li>
                                            <li>Inserting blocks without proper hash calculation</li>
                                            <li>Timestamp mismatches</li>
                                            <li>Sample data with incorrect hash chains</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>After reset, the blockchain will:</h6>
                                        <ul class="small">
                                            <li>✓ Have properly linked blocks</li>
                                            <li>✓ Pass all validation checks</li>
                                            <li>✓ Maintain all asset history</li>
                                            <li>✓ Be ready for new transactions</li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <p class="text-muted mt-3 mb-0">
                                    <i class="fas fa-lightbulb me-2"></i>
                                    resetting creates a clean, valid blockchain.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Enable reset button only when checkbox is checked
        document.getElementById('confirmCheck').addEventListener('change', function() {
            document.getElementById('resetBtn').disabled = !this.checked;
        });
    </script>
</body>
</html>