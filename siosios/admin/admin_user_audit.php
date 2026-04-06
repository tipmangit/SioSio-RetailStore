<?php
session_start();
include("../config.php");
include("admin_session_check.php");
require_once('../user_audit_functions.php'); // Use the new user functions

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

// Only super admins can view
if ($_SESSION['admin_role'] !== 'super_admin') {
    $_SESSION['message'] = "You do not have permission to view this log.";
    $_SESSION['message_type'] = "danger";
    header("Location: admin_dashboard.php");
    exit;
}

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 50;
$offset = ($page - 1) * $per_page;

// Filters
$filters = [];
$where_clauses = [];
$params = [];
$types = "";

if (!empty($_GET['user_id'])) {
    $where_clauses[] = "user_id = ?";
    $params[] = (int)$_GET['user_id'];
    $types .= "i";
    $filters['user_id'] = (int)$_GET['user_id'];
}
if (!empty($_GET['username'])) {
    $where_clauses[] = "username LIKE ?";
    $params[] = "%" . $_GET['username'] . "%";
    $types .= "s";
    $filters['username'] = $_GET['username'];
}
if (!empty($_GET['action_type'])) {
    $where_clauses[] = "action_type = ?";
    $params[] = $_GET['action_type'];
    $types .= "s";
    $filters['action_type'] = $_GET['action_type'];
}
if (!empty($_GET['ip_address'])) {
    $where_clauses[] = "ip_address = ?";
    $params[] = $_GET['ip_address'];
    $types .= "s";
    $filters['ip_address'] = $_GET['ip_address'];
}

$sql_where = "";
if (!empty($where_clauses)) {
    $sql_where = " WHERE " . implode(" AND ", $where_clauses);
}

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM user_audit_trail" . $sql_where;
$count_stmt = $con->prepare($count_sql);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_entries = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_entries / $per_page);
$count_stmt->close();

// Get entries
$sql = "SELECT * FROM user_audit_trail" . $sql_where . " ORDER BY created_at DESC LIMIT ? OFFSET ?";
$types .= "ii";
$params[] = $per_page;
$params[] = $offset;

$stmt = $con->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$entries = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get unique action types
$action_types = $con->query("SELECT DISTINCT action_type FROM user_audit_trail ORDER BY action_type");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Audit Trail - SioSio Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Joti+One&display=swap" rel="stylesheet">
    <style>
        :root { --siosio-red: #dc3545; --sidebar-width: 260px; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8f9fa; }
        .admin-wrapper { display: flex; min-height: 100vh; }
        .sidebar { width: var(--sidebar-width); background: linear-gradient(180deg, #1a1a1a 0%, #2d2d2d 100%); color: white; position: fixed; height: 100vh; overflow-y: auto; z-index: 1000; }
        .sidebar-header { padding: 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.1); text-align: center; }
        .sidebar-header h3 { font-family: 'Joti One', cursive; margin: 0; font-size: 1.5rem; }
        .sio-highlight { color: var(--siosio-red); }
        .sidebar-menu { list-style: none; padding: 1rem 0; margin: 0; }
        .sidebar-menu a { display: flex; align-items: center; padding: 0.875rem 1.5rem; color: rgba(255,255,255,0.8); text-decoration: none; transition: all 0.3s; }
        .sidebar-menu a:hover, .sidebar-menu a.active { background: rgba(220, 53, 69, 0.1); color: white; border-left: 3px solid var(--siosio-red); }
        .sidebar-menu i { margin-right: 0.75rem; font-size: 1.2rem; }
        .main-content { margin-left: var(--sidebar-width); flex: 1; padding: 2rem; width: calc(100% - var(--sidebar-width)); }
        .top-bar { background: white; padding: 1rem 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.08); margin-bottom: 2rem; }
        .content-card { background: white; border-radius: 8px; padding: 1.5rem; box-shadow: 0 2px 4px rgba(0,0,0,0.08); margin-bottom: 1.5rem; }
        .filter-card { background: #f8f9fa; border-radius: 8px; padding: 1.5rem; margin-bottom: 1.5rem; }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h3><span class="sio-highlight">Sio</span><span class="sio-highlight">Sio</span> Admin</h3>
                <p class="mb-0 small text-muted">Management Panel</p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="admin_dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                <li><a href="admin_products.php"><i class="bi bi-box-seam"></i> Products</a></li>
                <li><a href="admin_orders.php"><i class="bi bi-cart-check"></i> Orders</a></li>
                <li><a href="admin_inventory.php"><i class="bi bi-clipboard-data"></i> Inventory</a></li>
                <li><a href="admin_users.php"><i class="bi bi-people"></i> Users</a></li>
                <li><a href="admin_chat_support.php"><i class="bi bi-chat-dots"></i> Chat Support</a></li>
                <li><a href="admin_reports.php"><i class="bi bi-graph-up"></i> Reports</a></li>
                <li><a href="admin_returns.php"><i class="bi bi-box-arrow-in-left"></i> Returns</a></li>
                <li><a href="admin_cms.php"><i class="bi bi-file-text"></i> Content Management</a></li>
                <li><a href="admin_notifications.php"><i class="bi bi-bell"></i> Notifications</a></li>
                <li><a href="admin_audit_trail.php"><i class="bi bi-clock-history"></i> Admin Audit</a></li>
                <li><a href="admin_user_audit.php" class="active"><i class="bi bi-person-lines-fill"></i> User Audit</a></li>
                <li><a href="admin_settings.php"><i class="bi bi-gear"></i> Settings</a></li>
                <li><a href="admin_logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div class="top-bar">
                <h4 class="mb-0"><i class="bi bi-person-lines-fill me-2"></i>User Audit Trail</h4>
                <small class="text-muted">User Activity Log (Logins, etc.)</small>
            </div>
            
            <div class="filter-card">
                <h5 class="mb-3"><i class="bi bi-funnel me-2"></i>Filters</h5>
                <form method="GET" class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label">User ID</label>
                        <input type="number" name="user_id" class="form-control" value="<?= htmlspecialchars($filters['user_id'] ?? '') ?>">
                    </div>
                     <div class="col-md-3">
                        <label class="form-label">Username/Email</label>
                        <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($filters['username'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Action Type</label>
                        <select name="action_type" class="form-select">
                            <option value="">All Actions</option>
                            <?php while ($type = $action_types->fetch_assoc()): ?>
                                <option value="<?= $type['action_type'] ?>" <?= (isset($filters['action_type']) && $filters['action_type'] == $type['action_type']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($type['action_type']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">IP Address</label>
                        <input type="text" name="ip_address" class="form-control" value="<?= htmlspecialchars($filters['ip_address'] ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-danger w-100">
                            <i class="bi bi-search"></i> Filter
                        </button>
                    </div>
                    <?php if (!empty($filters)): ?>
                    <div class="col-12">
                        <a href="admin_user_audit.php" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-x-circle"></i> Clear Filters
                        </a>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
            
            <div class="content-card">
                <h5 class="mb-4">User Activity Log (<?= number_format($total_entries) ?> entries)</h5>
                
                <?php if (empty($entries)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-inbox" style="font-size: 4rem;"></i>
                        <p class="mt-3">No user audit trail entries found</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Timestamp</th>
                                    <th>User ID</th>
                                    <th>Username</th>
                                    <th>Action</th>
                                    <th>Description</th>
                                    <th>IP Address</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($entries as $entry): ?>
                                <tr>
                                    <td class="text-nowrap"><?= date('M d, Y h:i A', strtotime($entry['created_at'])) ?></td>
                                    <td><?= htmlspecialchars($entry['user_id'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($entry['username'] ?? 'N/A') ?></td>
                                    <td>
                                        <?php 
                                            $color = 'secondary';
                                            if(str_contains($entry['action_type'], 'success')) $color = 'success';
                                            if(str_contains($entry['action_type'], 'fail')) $color = 'danger';
                                            if(str_contains($entry['action_type'], 'lock')) $color = 'warning';
                                        ?>
                                        <span class="badge bg-<?= $color ?>"><?= htmlspecialchars($entry['action_type']) ?></span>
                                    </td>
                                    <td><?= htmlspecialchars($entry['action_description']) ?></td>
                                    <td><code><?= htmlspecialchars($entry['ip_address']) ?></code></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if ($total_pages > 1): ?>
                    <nav class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page - 1 ?><?= !empty($filters) ? '&' . http_build_query($filters) : '' ?>">Previous</a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?><?= !empty($filters) ? '&' . http_build_query($filters) : '' ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page + 1 ?><?= !empty($filters) ? '&' . http_build_query($filters) : '' ?>">Next</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function() {
            const timeoutInMilliseconds = 1800000;
            let inactivityTimer;

            function logout() {
                window.location.href = 'admin_logout.php?reason=idle';
            }

            function resetTimer() {
                clearTimeout(inactivityTimer);
                inactivityTimer = setTimeout(logout, timeoutInMilliseconds);
            }

            window.addEventListener('load', resetTimer);
            document.addEventListener('mousemove', resetTimer);
            document.addEventListener('mousedown', resetTimer);
            document.addEventListener('keypress', resetTimer);
            document.addEventListener('touchmove', resetTimer);
            document.addEventListener('scroll', resetTimer);
        })();
    </script>
</body>
</html>