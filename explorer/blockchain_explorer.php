<?php
require_once '../auth/auth_check.php';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Get total blocks count
$total_result = $conn->query("SELECT COUNT(*) as count FROM blockchain");
$total_blocks = $total_result->fetch_assoc()['count'];
$total_pages = ceil($total_blocks / $per_page);

// Get blocks with asset details
$sql = "SELECT b.*, a.asset_tag, a.asset_name 
        FROM blockchain b 
        LEFT JOIN assets a ON b.asset_id = a.id 
        ORDER BY b.block_index DESC 
        LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $per_page, $offset);
$stmt->execute();
$blocks = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blockchain Explorer - SBATS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .blockchain-visual {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
            padding: 20px 0;
        }
        
        .block-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 20px;
            width: 300px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            transition: transform 0.3s ease;
            position: relative;
        }
        
        .block-card:hover {
            transform: translateY(-5px);
        }
        
        .block-card::after {
            content: '→';
            position: absolute;
            right: -20px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 24px;
            color: #666;
        }
        
        .block-card:last-child::after {
            display: none;
        }
        
        .block-header {
            border-bottom: 1px solid rgba(255,255,255,0.2);
            padding-bottom: 10px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .block-hash {
            font-family: monospace;
            font-size: 12px;
            background: rgba(0,0,0,0.3);
            padding: 5px;
            border-radius: 5px;
            word-break: break-all;
        }
        
        .block-details {
            font-size: 14px;
        }
        
        .block-details p {
            margin-bottom: 5px;
        }
        
        .chain-link {
            display: inline-block;
            margin: 0 10px;
            font-size: 20px;
            color: #0d6efd;
        }
    </style>
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
                        <a href="blockchain_explorer.php" class="nav-link active">
                            <i class="fas fa-link me-2"></i>Blockchain Explorer
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="../blockchain/validate_chain.php" class="nav-link text-white">
                            <i class="fas fa-shield-alt me-2"></i>Validate Chain
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3">Blockchain Explorer</h1>
                    <span class="badge bg-primary fs-6">
                        <i class="fas fa-cube me-2"></i>Total Blocks: <?php echo $total_blocks; ?>
                    </span>
                </div>
                
                <!-- Blockchain Visualization -->
                <div class="blockchain-visual mb-4">
                    <?php 
                    $blocks->data_seek(0);
                    $display_blocks = 5; // Show only 5 blocks in visual
                    $count = 0;
                    while($block = $blocks->fetch_assoc()): 
                        if ($count++ >= $display_blocks) break;
                    ?>
                    <div class="block-card">
                        <div class="block-header">
                            <span class="badge bg-light text-dark">Block #<?php echo $block['block_index']; ?></span>
                            <i class="fas fa-cube"></i>
                        </div>
                        <div class="block-details">
                            <p><strong>Asset:</strong> <?php echo htmlspecialchars($block['asset_tag'] ?: 'Unknown'); ?></p>
                            <p><strong>Action:</strong> <?php echo htmlspecialchars($block['action']); ?></p>
                            <p><strong>User:</strong> <?php echo htmlspecialchars($block['user']); ?></p>
                            <p><strong>Time:</strong> <?php echo date('H:i:s', strtotime($block['timestamp'])); ?></p>
                            <div class="block-hash mt-2">
                                <small>Hash: <?php echo substr($block['hash'], 0, 20); ?>...</small>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                
                <!-- Blocks Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">All Blocks</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Block #</th>
                                        <th>Asset</th>
                                        <th>Action</th>
                                        <th>User</th>
                                        <th>Timestamp</th>
                                        <th>Previous Hash</th>
                                        <th>Hash</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $blocks->data_seek(0);
                                    while($block = $blocks->fetch_assoc()): 
                                    ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-primary">#<?php echo $block['block_index']; ?></span>
                                        </td>
                                        <td>
                                            <?php if ($block['asset_tag']): ?>
                                                <a href="../assets/asset_details.php?id=<?php echo $block['asset_id']; ?>">
                                                    <?php echo htmlspecialchars($block['asset_tag']); ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">Unknown</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $block['action'] == 'Asset Created' ? 'success' : 
                                                    ($block['action'] == 'Asset Transferred' ? 'info' : 
                                                    ($block['action'] == 'Asset Updated' ? 'warning' : 'secondary')); 
                                            ?>">
                                                <?php echo $block['action']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($block['user']); ?></td>
                                        <td>
                                            <small><?php echo date('Y-m-d H:i:s', strtotime($block['timestamp'])); ?></small>
                                        </td>
                                        <td>
                                            <small class="font-monospace text-muted">
                                                <?php echo substr($block['previous_hash'], 0, 15); ?>...
                                            </small>
                                        </td>
                                        <td>
                                            <small class="font-monospace text-muted">
                                                <?php echo substr($block['hash'], 0, 15); ?>...
                                            </small>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                        <nav aria-label="Blockchain pagination">
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page-1; ?>">Previous</a>
                                </li>
                                
                                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                                <?php endfor; ?>
                                
                                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page+1; ?>">Next</a>
                                </li>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>