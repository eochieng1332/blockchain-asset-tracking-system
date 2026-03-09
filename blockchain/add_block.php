<?php
require_once '../auth/auth_check.php';
checkRole(['Admin', 'Asset Manager']);
require_once 'blockchain.php';

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['asset_id'], $_POST['action'])) {
    $blockchain = new Blockchain($conn);
    $result = $blockchain->addBlock(
        $_POST['asset_id'],
        $_POST['action'],
        $_SESSION['username']
    );
    
    if ($result['success']) {
        require_once '../logs/log_activity.php';
        logActivity($_SESSION['user_id'], $_SESSION['username'], 'Block Added', 
                   "Added block #{$result['block_index']} for asset ID: {$_POST['asset_id']}");
        
        $response = [
            'success' => true,
            'message' => 'Block added successfully',
            'block_index' => $result['block_index'],
            'hash' => $result['hash']
        ];
    } else {
        $response['message'] = 'Failed to add block: ' . $result['error'];
    }
}

header('Content-Type: application/json');
echo json_encode($response);
?>