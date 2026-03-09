<?php
class Blockchain {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Calculate SHA256 hash for a block
     */
    public function calculateHash($block_index, $asset_id, $action, $user, $timestamp, $previous_hash) {
        $data = $block_index . $asset_id . $action . $user . $timestamp . $previous_hash;
        return hash('sha256', $data);
    }
    
    /**
     * Get the latest block index
     */
    public function getLatestBlockIndex() {
        $sql = "SELECT MAX(block_index) as max_index FROM blockchain";
        $result = $this->conn->query($sql);
        $row = $result->fetch_assoc();
        return $row['max_index'] !== null ? $row['max_index'] + 1 : 0;
    }
    
    /**
     * Get the latest block's hash
     */
    public function getLatestBlockHash() {
        $sql = "SELECT hash FROM blockchain ORDER BY block_index DESC LIMIT 1";
        $result = $this->conn->query($sql);
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['hash'];
        }
        return '0'; // Genesis block previous hash
    }
    
    /**
     * Add a new block to the blockchain
     */
    public function addBlock($asset_id, $action, $user) {
        $block_index = $this->getLatestBlockIndex();
        $previous_hash = $this->getLatestBlockHash();
        $timestamp = date('Y-m-d H:i:s');
        
        // Calculate hash
        $hash = $this->calculateHash($block_index, $asset_id, $action, $user, $timestamp, $previous_hash);
        
        // Insert block
        $sql = "INSERT INTO blockchain (block_index, asset_id, action, user, timestamp, previous_hash, hash) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iisssss", $block_index, $asset_id, $action, $user, $timestamp, $previous_hash, $hash);
        
        if ($stmt->execute()) {
            return [
                'success' => true,
                'block_index' => $block_index,
                'hash' => $hash
            ];
        } else {
            return [
                'success' => false,
                'error' => $this->conn->error
            ];
        }
    }
    
    /**
     * Validate the entire blockchain
     */
    public function validateChain() {
        $sql = "SELECT * FROM blockchain ORDER BY block_index ASC";
        $result = $this->conn->query($sql);
        
        $blocks = [];
        while ($row = $result->fetch_assoc()) {
            $blocks[] = $row;
        }
        
        if (empty($blocks)) {
            return ['valid' => true, 'message' => 'Blockchain is empty'];
        }
        
        // Check genesis block
        if ($blocks[0]['previous_hash'] !== '0') {
            return ['valid' => false, 'message' => 'Genesis block has invalid previous hash'];
        }
        
        // Validate each block
        for ($i = 1; $i < count($blocks); $i++) {
            $current = $blocks[$i];
            $previous = $blocks[$i - 1];
            
            // Check if previous hash matches
            if ($current['previous_hash'] !== $previous['hash']) {
                return [
                    'valid' => false,
                    'message' => "Block #{$current['block_index']} has invalid previous hash reference",
                    'block' => $current
                ];
            }
            
            // Recalculate hash to verify integrity
            $calculated_hash = $this->calculateHash(
                $current['block_index'],
                $current['asset_id'],
                $current['action'],
                $current['user'],
                $current['timestamp'],
                $current['previous_hash']
            );
            
            if ($calculated_hash !== $current['hash']) {
                return [
                    'valid' => false,
                    'message' => "Block #{$current['block_index']} has been tampered with! Hash mismatch",
                    'block' => $current
                ];
            }
        }
        
        return [
            'valid' => true,
            'message' => 'Blockchain is valid and secure',
            'blocks_count' => count($blocks)
        ];
    }
}
?>