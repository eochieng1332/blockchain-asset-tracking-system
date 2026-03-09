<?php
require_once '../auth/auth_check.php';

// Handle soft delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    checkRole(['Admin', 'Asset Manager']);
    $id = $_GET['delete'];
    $sql = "UPDATE assets SET deleted_at = NOW() WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        // Get asset details for logging
        $assetSql = "SELECT asset_tag, asset_name FROM assets WHERE id = ?";
        $assetStmt = $conn->prepare($assetSql);
        $assetStmt->bind_param("i", $id);
        $assetStmt->execute();
        $asset = $assetStmt->get_result()->fetch_assoc();
        
        require_once '../logs/log_activity.php';
        logActivity($_SESSION['user_id'], $_SESSION['username'], 'Asset Deleted', 
                   "Deleted asset: {$asset['asset_tag']} - {$asset['asset_name']}");
        
        header("Location: view_assets.php?msg=deleted");
        exit();
    }
}

// Search and filter
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : '';
$department_filter = isset($_GET['department']) ? $conn->real_escape_string($_GET['department']) : '';

$where_clauses = ["deleted_at IS NULL"];
if ($search) {
    $where_clauses[] = "(asset_tag LIKE '%$search%' OR asset_name LIKE '%$search%' OR serial_number LIKE '%$search%')";
}
if ($status_filter) {
    $where_clauses[] = "status = '$status_filter'";
}
if ($department_filter) {
    $where_clauses[] = "department = '$department_filter'";
}

$where_sql = implode(' AND ', $where_clauses);
$sql = "SELECT a.*, u.username as creator_name 
        FROM assets a 
        LEFT JOIN users u ON a.created_by = u.id 
        WHERE $where_sql 
        ORDER BY a.created_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Assets - SBATS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <!-- Navigation and Sidebar (same as add_asset.php) -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="#"><i class="fas fa-cube me-2"></i>SBATS</a>
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
                        <a href="view_assets.php" class="nav-link active">
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
                </ul>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3">Asset Management</h1>
                    <a href="add_asset.php" class="btn btn-primary">
                        <i class="fas fa-plus-circle me-2"></i>Add New Asset
                    </a>
                </div>
                
                <?php if (isset($_GET['msg'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php 
                        if ($_GET['msg'] == 'deleted') echo "Asset deleted successfully!";
                        if ($_GET['msg'] == 'updated') echo "Asset updated successfully!";
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Search and Filter -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" action="" class="row g-3">
                            <div class="col-md-4">
                                <input type="text" class="form-control" name="search" 
                                       placeholder="Search by tag, name, or serial..." 
                                       value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" name="status">
                                    <option value="">All Status</option>
                                    <option value="Active" <?php echo $status_filter == 'Active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="In Maintenance" <?php echo $status_filter == 'In Maintenance' ? 'selected' : ''; ?>>In Maintenance</option>
                                    <option value="Transferred" <?php echo $status_filter == 'Transferred' ? 'selected' : ''; ?>>Transferred</option>
                                    <option value="Retired" <?php echo $status_filter == 'Retired' ? 'selected' : ''; ?>>Retired</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" name="department">
                                    <option value="">All Departments</option>
                                    <option value="IT Department" <?php echo $department_filter == 'IT Department' ? 'selected' : ''; ?>>IT</option>
                                    <option value="Administration" <?php echo $department_filter == 'Administration' ? 'selected' : ''; ?>>Administration</option>
                                    <option value="Finance" <?php echo $department_filter == 'Finance' ? 'selected' : ''; ?>>Finance</option>
                                    <option value="HR" <?php echo $department_filter == 'HR' ? 'selected' : ''; ?>>HR</option>
                                    <option value="Operations" <?php echo $department_filter == 'Operations' ? 'selected' : ''; ?>>Operations</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search me-2"></i>Filter
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Assets Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Asset Tag</th>
                                        <th>Name</th>
                                        <th>Category</th>
                                        <th>Location</th>
                                        <th>Department</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result->num_rows > 0): ?>
                                        <?php while($asset = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($asset['asset_tag']); ?></strong>
                                            </td>
                                            <td><?php echo htmlspecialchars($asset['asset_name']); ?></td>
                                            <td><?php echo htmlspecialchars($asset['category']); ?></td>
                                            <td><?php echo htmlspecialchars($asset['location']); ?></td>
                                            <td><?php echo htmlspecialchars($asset['department']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $asset['status'] == 'Active' ? 'success' : 
                                                        ($asset['status'] == 'In Maintenance' ? 'warning' : 
                                                        ($asset['status'] == 'Transferred' ? 'info' : 'secondary')); 
                                                ?>">
                                                    <?php echo $asset['status']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php echo date('M d, Y', strtotime($asset['created_at'])); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="asset_details.php?id=<?php echo $asset['id']; ?>" 
                                                       class="btn btn-sm btn-info" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="edit_asset.php?id=<?php echo $asset['id']; ?>" 
                                                       class="btn btn-sm btn-primary" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="transfer_asset.php?id=<?php echo $asset['id']; ?>" 
                                                       class="btn btn-sm btn-warning" title="Transfer">
                                                        <i class="fas fa-exchange-alt"></i>
                                                    </a>
                                                    <?php if ($_SESSION['role'] === 'Admin'): ?>
                                                    <a href="?delete=<?php echo $asset['id']; ?>" 
                                                       class="btn btn-sm btn-danger" 
                                                       onclick="return confirm('Are you sure you want to delete this asset?')"
                                                       title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-4">
                                                <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                                                <p class="text-muted">No assets found</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>