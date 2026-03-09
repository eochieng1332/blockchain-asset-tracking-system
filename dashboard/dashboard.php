<?php
require_once '../auth/auth_check.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SBATS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body style = "background-image: url(../img/bg.jpg);">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
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
                            <li><a class="dropdown-item" href="#"><i class="fas fa-user-cog me-2"></i>Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="../auth/logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a></li>
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
                        <a href="dashboard.php" class="nav-link active">
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
                        <a href="../blockchain/validate_chain.php" class="nav-link text-white">
                            <i class="fas fa-shield-alt me-2"></i>Validate Chain
                        </a>
                    </li>
                    <li class="nav-item mt-4 pt-4 border-top">
                        <span class="text-muted small">System Info</span>
                    </li>
                    <li class="nav-item mt-2">
                        <small class="text-muted">
                            <i class="fas fa-circle text-success me-2"></i>Blockchain Active
                        </small>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="container-fluid">
                <h1 class="h3 mb-4">Dashboard Overview</h1>
                
                <?php
                // Fetch statistics
                $stats = [];
                
                // Total Assets
                $result = $conn->query("SELECT COUNT(*) as count FROM assets WHERE deleted_at IS NULL");
                $stats['total_assets'] = $result->fetch_assoc()['count'];
                
                // Active Assets
                $result = $conn->query("SELECT COUNT(*) as count FROM assets WHERE status = 'Active' AND deleted_at IS NULL");
                $stats['active_assets'] = $result->fetch_assoc()['count'];
                
                // Maintenance Assets
                $result = $conn->query("SELECT COUNT(*) as count FROM assets WHERE status = 'In Maintenance' AND deleted_at IS NULL");
                $stats['maintenance_assets'] = $result->fetch_assoc()['count'];
                
                // Total Blockchain Blocks
                $result = $conn->query("SELECT COUNT(*) as count FROM blockchain");
                $stats['total_blocks'] = $result->fetch_assoc()['count'];
                
                // Recent Activity
                $recentActivity = $conn->query("
                    SELECT al.*, u.full_name 
                    FROM activity_logs al 
                    LEFT JOIN users u ON al.user_id = u.id 
                    ORDER BY al.timestamp DESC 
                    LIMIT 10
                ");
                ?>

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card stat-card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-white-50">Total Assets</h6>
                                        <h2 class="mb-0"><?php echo $stats['total_assets']; ?></h2>
                                    </div>
                                    <i class="fas fa-boxes fa-3x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card stat-card bg-success text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-white-50">Active Assets</h6>
                                        <h2 class="mb-0"><?php echo $stats['active_assets']; ?></h2>
                                    </div>
                                    <i class="fas fa-check-circle fa-3x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card stat-card bg-warning text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-white-50">In Maintenance</h6>
                                        <h2 class="mb-0"><?php echo $stats['maintenance_assets']; ?></h2>
                                    </div>
                                    <i class="fas fa-tools fa-3x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card stat-card bg-info text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-white-50">Blockchain Blocks</h6>
                                        <h2 class="mb-0"><?php echo $stats['total_blocks']; ?></h2>
                                    </div>
                                    <i class="fas fa-link fa-3x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Asset Distribution by Department</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="assetChart" style="height: 300px;"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Asset Status</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="statusChart" style="height: 300px;"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Recent Activity Log</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Action</th>
                                        <th>Details</th>
                                        <th>Time</th>
                                        <th>IP Address</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($activity = $recentActivity->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($activity['full_name'] ?: $activity['username']); ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $activity['action'] == 'Login' ? 'success' : 
                                                    ($activity['action'] == 'Asset Created' ? 'primary' : 
                                                    ($activity['action'] == 'Asset Transferred' ? 'info' : 'secondary')); 
                                            ?>">
                                                <?php echo htmlspecialchars($activity['action']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($activity['details']); ?></td>
                                        <td>
                                            <small class="text-muted">
                                                <?php echo date('M d, H:i', strtotime($activity['timestamp'])); ?>
                                            </small>
                                        </td>
                                        <td><small><?php echo htmlspecialchars($activity['ip_address']); ?></small></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Asset Distribution Chart
        const ctx1 = document.getElementById('assetChart').getContext('2d');
        new Chart(ctx1, {
            type: 'bar',
            data: {
                labels: <?php 
                    $depts = $conn->query("SELECT department, COUNT(*) as count FROM assets WHERE deleted_at IS NULL GROUP BY department");
                    $labels = [];
                    $counts = [];
                    while($row = $depts->fetch_assoc()) {
                        $labels[] = $row['department'];
                        $counts[] = $row['count'];
                    }
                    echo json_encode($labels);
                ?>,
                datasets: [{
                    label: 'Number of Assets',
                    data: <?php echo json_encode($counts); ?>,
                    backgroundColor: '#0d6efd',
                    borderRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        // Status Chart
        const ctx2 = document.getElementById('statusChart').getContext('2d');
        new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: ['Active', 'In Maintenance', 'Transferred', 'Retired'],
                datasets: [{
                    data: [
                        <?php echo $stats['active_assets']; ?>,
                        <?php echo $stats['maintenance_assets']; ?>,
                        <?php 
                            $result = $conn->query("SELECT COUNT(*) as count FROM assets WHERE status = 'Transferred' AND deleted_at IS NULL");
                            echo $result->fetch_assoc()['count'];
                        ?>,
                        <?php 
                            $result = $conn->query("SELECT COUNT(*) as count FROM assets WHERE status = 'Retired' AND deleted_at IS NULL");
                            echo $result->fetch_assoc()['count'];
                        ?>
                    ],
                    backgroundColor: ['#198754', '#ffc107', '#0dcaf0', '#6c757d']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    </script>
</body>
</html>