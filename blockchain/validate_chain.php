<?php
require_once '../auth/auth_check.php';
require_once 'blockchain.php';

$blockchain = new Blockchain($conn);
$validation_result = $blockchain->validateChain();

// Log validation attempt
require_once '../logs/log_activity.php';
logActivity($_SESSION['user_id'], $_SESSION['username'], 'Chain Validation', 
           "Blockchain validation performed. Result: " . ($validation_result['valid'] ? 'VALID' : 'INVALID'));

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blockchain Validation - SBATS</title>
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
                        <a href="../assets/view_assets.php" class="nav-link text-white">
                            <i class="fas fa-boxes me-2"></i>Assets
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="../explorer/blockchain_explorer.php" class="nav-link text-white">
                            <i class="fas fa-link me-2"></i>Blockchain Explorer
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="validate_chain.php" class="nav-link active">
                            <i class="fas fa-shield-alt me-2"></i>Validate Chain
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="container-fluid">
                <h1 class="h3 mb-4">Blockchain Integrity Validation</h1>
                
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-body">
                                <div class="text-center mb-4">
                                    <?php if ($validation_result['valid']): ?>
                                        <div class="display-1 text-success mb-3">
                                            <i class="fas fa-shield-alt"></i>
                                        </div>
                                        <p class="lead"><?php echo $validation_result['message']; ?></p>
                                        <p class="text-muted">Total Blocks: <?php echo $validation_result['blocks_count']; ?></p>
                                    <?php else: ?>
                                        <div class="display-1 text-danger mb-3">
                                            <i class="fas fa-exclamation-circle"></i>
                                        </div>
                                        <p class="lead text-danger"><?php echo $validation_result['message']; ?></p>
                                        <?php if (isset($validation_result['block'])): ?>
                                            <div class="alert alert-warning mt-3">
                                                <strong>Tampered Block Details:</strong><br>
                                                Block #<?php echo $validation_result['block']['block_index']; ?><br>
                                                Asset ID: <?php echo $validation_result['block']['asset_id']; ?><br>
                                                Action: <?php echo $validation_result['block']['action']; ?><br>
                                                Timestamp: <?php echo $validation_result['block']['timestamp']; ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                                
                                <hr>
                                
                                <div class="row mt-4">
                                    <div class="col-md-6">
                                        <h5>Blockchain Statistics</h5>
                                        <?php
                                        $stats = $conn->query("
                                            SELECT 
                                                COUNT(*) as total_blocks,
                                                MIN(timestamp) as first_block,
                                                MAX(timestamp) as last_block
                                            FROM blockchain
                                        ")->fetch_assoc();
                                        ?>
                                        <ul class="list-unstyled">
                                            <li><strong>Total Blocks:</strong> <?php echo $stats['total_blocks']; ?></li>
                                            <li><strong>First Block:</strong> <?php echo $stats['first_block']; ?></li>
                                            <li><strong>Last Block:</strong> <?php echo $stats['last_block']; ?></li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <h5>Security Features</h5>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-check-circle text-success me-2"></i>SHA-256 Hashing</li>
                                            <li><i class="fas fa-check-circle text-success me-2"></i>Linked Blocks</li>
                                            <li><i class="fas fa-check-circle text-success me-2"></i>Immutable Records</li>
                                            <li><i class="fas fa-check-circle text-success me-2"></i>Tamper Detection</li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <div class="text-center mt-4">
                                    <a href="validate_chain.php" class="btn btn-primary">
                                        <i class="fas fa-sync-alt me-2"></i>Re-validate Chain
                                    </a>
                                    <a href="../explorer/blockchain_explorer.php" class="btn btn-secondary">
                                        <i class="fas fa-link me-2"></i>View Blockchain
                                    </a>
                                </div>
                                <?php
// Add this at the bottom of validate_chain.php, inside the main content area
if (!$validation_result['valid'] && $_SESSION['role'] === 'Admin'): 
?>
<div class="row mt-4">
    <div class="col-12">
        <div class="card border-warning">
            <div class="card-header bg-warning text-dark">
                <i class="fas fa-tools me-2"></i>Blockchain Repair Options
            </div>
            <div class="card-body">
                <p>The blockchain is currently compromised. As an administrator, you can:</p>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="d-grid">
                            <a href="reset_blockchain.php" class="btn btn-danger btn-lg">
                                <i class="fas fa-sync-alt me-2"></i>Reset Entire Blockchain
                            </a>
                            <small class="text-muted mt-2">This will rebuild the chain from asset records</small>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="d-grid">
                            <button type="button" class="btn btn-warning btn-lg" onclick="healBlockchain()">
                                <i class="fas fa-medkit me-2"></i>Auto-Heal Blockchain
                            </button>
                            <small class="text-muted mt-2">Attempt to fix hash mismatches</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function healBlockchain() {
    if (confirm('This will attempt to recalculate hashes for all blocks. Continue?')) {
        fetch('heal_blockchain.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Blockchain healed successfully! ' + data.message);
                    location.reload();
                } else {
                    alert('Failed to heal blockchain: ' + data.message);
                }
            });
    }
}
</script>
<?php endif; ?>
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