<?php
require_once '../auth/auth_check.php';
checkRole(['Admin']);
require_once 'blockchain.php';

$blockchain = new Blockchain($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get all blocks ordered
    $blocks = $conn->query("SELECT * FROM blockchain ORDER BY block_index ASC");
    
    $conn->begin_transaction();
    
    try {
        $previous_hash = '0';
        
        while ($block = $blocks->fetch_assoc()) {
            // Recalculate with correct previous hash
            $new_hash = $blockchain->calculateHash(
                $block['block_index'],
                $block['asset_id'],
                $block['action'],
                $block['user'],
                $block['timestamp'],
                $previous_hash
            );
            
            // Update block
            $update = $conn->prepare("UPDATE blockchain SET previous_hash = ?, hash = ? WHERE id = ?");
            $update->bind_param("ssi", $previous_hash, $new_hash, $block['id']);
            $update->execute();
            
            $previous_hash = $new_hash;
        }
        
        $conn->commit();
        $success = "Blockchain has been repaired and is now valid!";
        
    } catch (Exception $e) {
        $conn->rollback();
        $error = $e->getMessage();
    }
}

$validation = $blockchain->validateChain();
?>

<!-- Simple HTML form to trigger the fix -->
<div class="card">
    <div class="card-header bg-<?php echo $validation['valid'] ? 'success' : 'danger'; ?> text-white">
        Current Status: <?php echo $validation['valid'] ? 'VALID' : 'COMPROMISED'; ?>
    </div>
    <div class="card-body">
        <p><?php echo $validation['message']; ?></p>
        
        <?php if (!$validation['valid']): ?>
        <form method="POST">
            <button type="submit" class="btn btn-warning">
                <i class="fas fa-wrench me-2"></i>Fix Blockchain Now
            </button>
        </form>
        <?php endif; ?>
    </div>
</div>