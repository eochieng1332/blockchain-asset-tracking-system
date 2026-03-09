<?php
require_once '../auth/auth_check.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: view_assets.php");
    exit();
}

$asset_id = $_GET['id'];

// Get asset details
$asset_sql = "SELECT a.*, u.username as creator_name 
              FROM assets a 
              LEFT JOIN users u ON a.created_by = u.id 
              WHERE a.id = ? AND a.deleted_at IS NULL";
$asset_stmt = $conn->prepare($asset_sql);
$asset_stmt->bind_param("i", $asset_id);
$asset_stmt->execute();
$asset = $asset_stmt->get_result()->fetch_assoc();

if (!$asset) {
    header("Location: view_assets.php");
    exit();
}

// Get blockchain history for timeline
$blockchain_sql = "SELECT * FROM blockchain WHERE asset_id = ? ORDER BY timestamp ASC";
$blockchain_stmt = $conn->prepare($blockchain_sql);
$blockchain_stmt->bind_param("i", $asset_id);
$blockchain_stmt->execute();
$blockchain = $blockchain_stmt->get_result();

// Get transfer history
$transfer_sql = "SELECT at.*, u.username as transferrer 
                 FROM asset_transfers at 
                 LEFT JOIN users u ON at.transferred_by = u.id 
                 WHERE at.asset_id = ? 
                 ORDER BY at.transfer_timestamp ASC";
$transfer_stmt = $conn->prepare($transfer_sql);
$transfer_stmt->bind_param("i", $asset_id);
$transfer_stmt->execute();
$transfers = $transfer_stmt->get_result();

// Combine and sort all events
$events = [];

// Add creation event
$events[] = [
    'type' => 'creation',
    'date' => $asset['created_at'],
    'title' => 'Asset Created',
    'description' => 'Asset registered in the system',
    'user' => $asset['creator_name'] ?: 'System',
    'icon' => 'fa-plus-circle',
    'color' => 'success'
];

// Add blockchain events
while ($block = $blockchain->fetch_assoc()) {
    $events[] = [
        'type' => 'blockchain',
        'date' => $block['timestamp'],
        'title' => 'Blockchain Record: ' . $block['action'],
        'description' => 'Block #' . $block['block_index'] . ' recorded on blockchain',
        'user' => $block['user'],
        'icon' => 'fa-cube',
        'color' => 'primary',
        'hash' => $block['hash']
    ];
}

// Add transfer events
while ($transfer = $transfers->fetch_assoc()) {
    $events[] = [
        'type' => 'transfer',
        'date' => $transfer['transfer_timestamp'],
        'title' => 'Asset Transferred',
        'description' => 'From: ' . $transfer['from_location'] . ' → To: ' . $transfer['to_location'],
        'user' => $transfer['transferrer'] ?: 'System',
        'icon' => 'fa-exchange-alt',
        'color' => 'warning',
        'notes' => $transfer['notes']
    ];
}

// Add status changes (from blockchain)
$status_sql = "SELECT * FROM blockchain WHERE asset_id = ? AND action LIKE '%Status%' ORDER BY timestamp ASC";
$status_stmt = $conn->prepare($status_sql);
$status_stmt->bind_param("i", $asset_id);
$status_stmt->execute();
$status_changes = $status_stmt->get_result();

while ($status = $status_changes->fetch_assoc()) {
    $events[] = [
        'type' => 'status',
        'date' => $status['timestamp'],
        'title' => $status['action'],
        'description' => 'Asset status updated',
        'user' => $status['user'],
        'icon' => 'fa-flag',
        'color' => 'info'
    ];
}

// Sort events by date (oldest first for timeline)
usort($events, function($a, $b) {
    return strtotime($a['date']) - strtotime($b['date']);
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asset Timeline - <?php echo htmlspecialchars($asset['asset_tag']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .timeline-container {
            position: relative;
            padding: 20px 0;
        }
        
        .timeline-container::before {
            content: '';
            position: absolute;
            left: 50%;
            top: 0;
            bottom: 0;
            width: 4px;
            background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
            transform: translateX(-50%);
            border-radius: 2px;
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 50px;
            width: 100%;
        }
        
        .timeline-item:nth-child(odd) .timeline-content {
            margin-left: auto;
            transform: translateX(calc(50% + 30px));
        }
        
        .timeline-item:nth-child(even) .timeline-content {
            margin-right: auto;
            transform: translateX(calc(-50% - 30px));
        }
        
        .timeline-content {
            width: calc(50% - 50px);
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            position: relative;
            transition: transform 0.3s;
        }
        
        .timeline-content:hover {
            transform: scale(1.02) translateX(calc(50% + 30px)) !important;
            z-index: 10;
        }
        
        .timeline-badge {
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            z-index: 2;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .timeline-date {
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 5px;
        }
        
        .timeline-title {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .timeline-user {
            font-size: 0.9rem;
            color: #495057;
            margin-bottom: 10px;
        }
        
        .timeline-user i {
            margin-right: 5px;
            color: #6c757d;
        }
        
        .timeline-description {
            color: #6c757d;
            margin-bottom: 10px;
        }
        
        .timeline-hash {
            font-family: monospace;
            font-size: 0.8rem;
            background: #f8f9fa;
            padding: 8px;
            border-radius: 5px;
            word-break: break-all;
        }
        
        .asset-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }
        
        @media (max-width: 768px) {
            .timeline-container::before {
                left: 30px;
            }
            
            .timeline-item:nth-child(odd) .timeline-content,
            .timeline-item:nth-child(even) .timeline-content {
                width: calc(100% - 80px);
                margin-left: 80px !important;
                transform: none !important;
            }
            
            .timeline-badge {
                left: 30px;
            }
        }
        
        .timeline-filters {
            margin-bottom: 30px;
        }
        
        .filter-btn {
            margin: 0 5px;
            border-radius: 20px;
            padding: 5px 15px;
        }
        
        .filter-btn.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
    </style>
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
                        <a href="view_assets.php" class="nav-link text-white">
                            <i class="fas fa-boxes me-2"></i>Assets
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="add_asset.php" class="nav-link text-white">
                            <i class="fas fa-plus-circle me-2"></i>Add Asset
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="../explorer/blockchain_explorer.php" class="nav-link text-white">
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
                <!-- Asset Header -->
                <div class="asset-header">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h1 class="display-6 mb-2"><?php echo htmlspecialchars($asset['asset_name']); ?></h1>
                            <p class="mb-0">
                                <i class="fas fa-tag me-2"></i><?php echo htmlspecialchars($asset['asset_tag']); ?>
                                <i class="fas fa-map-marker-alt ms-4 me-2"></i><?php echo htmlspecialchars($asset['location']); ?>
                                <span class="badge bg-light text-dark ms-4">
                                    <i class="fas fa-<?php 
                                        echo $asset['status'] == 'Active' ? 'check-circle text-success' : 
                                            ($asset['status'] == 'In Maintenance' ? 'tools text-warning' : 
                                            ($asset['status'] == 'Transferred' ? 'exchange-alt text-info' : 'ban text-secondary')); 
                                    ?> me-1"></i>
                                    <?php echo $asset['status']; ?>
                                </span>
                            </p>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <a href="asset_details.php?id=<?php echo $asset_id; ?>" class="btn btn-light me-2">
                                <i class="fas fa-arrow-left me-2"></i>Back to Details
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Timeline Filters -->
                <div class="timeline-filters text-center">
                    <button class="btn btn-outline-primary filter-btn active" data-filter="all">All Events</button>
                    <button class="btn btn-outline-success filter-btn" data-filter="creation">Creation</button>
                    <button class="btn btn-outline-primary filter-btn" data-filter="blockchain">Blockchain</button>
                    <button class="btn btn-outline-warning filter-btn" data-filter="transfer">Transfers</button>
                    <button class="btn btn-outline-info filter-btn" data-filter="status">Status Changes</button>
                </div>

                <!-- Timeline -->
                <div class="timeline-container">
                    <?php foreach ($events as $index => $event): ?>
                    <div class="timeline-item" data-type="<?php echo $event['type']; ?>">
                        <div class="timeline-badge bg-<?php echo $event['color']; ?>">
                            <i class="fas <?php echo $event['icon']; ?>"></i>
                        </div>
                        
                        <div class="timeline-content">
                            <div class="timeline-date">
                                <i class="far fa-calendar-alt me-1"></i>
                                <?php echo date('F d, Y', strtotime($event['date'])); ?>
                                <span class="ms-2">
                                    <i class="far fa-clock me-1"></i>
                                    <?php echo date('h:i A', strtotime($event['date'])); ?>
                                </span>
                            </div>
                            
                            <h5 class="timeline-title">
                                <?php echo htmlspecialchars($event['title']); ?>
                                <?php if ($index === 0): ?>
                                    <span class="badge bg-success ms-2">First Event</span>
                                <?php endif; ?>
                                <?php if ($index === count($events) - 1): ?>
                                    <span class="badge bg-info ms-2">Latest Event</span>
                                <?php endif; ?>
                            </h5>
                            
                            <div class="timeline-user">
                                <i class="fas fa-user"></i> <?php echo htmlspecialchars($event['user']); ?>
                            </div>
                            
                            <div class="timeline-description">
                                <?php echo htmlspecialchars($event['description']); ?>
                            </div>
                            
                            <?php if (isset($event['notes']) && $event['notes']): ?>
                                <div class="alert alert-light mt-2 mb-0 py-2">
                                    <i class="fas fa-sticky-note me-2 text-muted"></i>
                                    <?php echo htmlspecialchars($event['notes']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (isset($event['hash'])): ?>
                                <div class="timeline-hash mt-2">
                                    <i class="fas fa-link me-2 text-muted"></i>
                                    <?php echo htmlspecialchars($event['hash']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Timeline Summary -->
                <div class="row mt-5">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Timeline Summary</h5>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-md-3">
                                        <div class="p-3">
                                            <h3 class="text-primary"><?php echo count($events); ?></h3>
                                            <small class="text-muted">Total Events</small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="p-3">
                                            <h3 class="text-success">
                                                <?php 
                                                echo $events[0]['date'] ? date('M d, Y', strtotime($events[0]['date'])) : 'N/A';
                                                ?>
                                            </h3>
                                            <small class="text-muted">First Event</small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="p-3">
                                            <h3 class="text-info">
                                                <?php 
                                                echo end($events)['date'] ? date('M d, Y', strtotime(end($events)['date'])) : 'N/A';
                                                ?>
                                            </h3>
                                            <small class="text-muted">Latest Event</small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="p-3">
                                            <h3 class="text-warning">
                                                <?php 
                                                $first = strtotime($events[0]['date']);
                                                $last = strtotime(end($events)['date']);
                                                $diff = $last - $first;
                                                echo floor($diff / (60 * 60 * 24)) . ' days';
                                                ?>
                                            </h3>
                                            <small class="text-muted">Total Timeline</small>
                                        </div>
                                    </div>
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
        // Timeline filtering
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                // Update active button
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                const filter = this.dataset.filter;
                const items = document.querySelectorAll('.timeline-item');
                
                items.forEach(item => {
                    if (filter === 'all' || item.dataset.type === filter) {
                        item.style.display = '';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
        });

        // Animate timeline items on scroll
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = 1;
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        });

        document.querySelectorAll('.timeline-item').forEach(item => {
            item.style.opacity = 0;
            item.style.transform = 'translateY(20px)';
            item.style.transition = 'opacity 0.5s, transform 0.5s';
            observer.observe(item);
        });

        // Print timeline
        function printTimeline() {
            window.print();
        }

        // Export timeline as PDF (simulated)
        function exportTimeline() {
            alert('Timeline export feature would generate a PDF report here.');
        }
    </script>
</body>
</html>