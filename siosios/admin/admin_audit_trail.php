<?php
session_start();
include("../config.php");
include("admin_session_check.php");
require_once('audit_trail_functions.php');

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

// Only super admins can view full audit trail
if ($_SESSION['admin_role'] !== 'super_admin') {
    $_SESSION['message'] = "You do not have permission to view the audit trail.";
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
if (!empty($_GET['admin_id'])) {
    $filters['admin_id'] = (int)$_GET['admin_id'];
}
if (!empty($_GET['action_type'])) {
    $filters['action_type'] = $_GET['action_type'];
}
if (!empty($_GET['date_from'])) {
    $filters['date_from'] = $_GET['date_from'];
}
if (!empty($_GET['date_to'])) {
    $filters['date_to'] = $_GET['date_to'];
}
if (!empty($_GET['search'])) {
    $filters['search'] = trim($_GET['search']);
}

// Get audit trail entries
$entries = getAuditTrail($con, $filters, $per_page, $offset);
$total_entries = getAuditTrailCount($con, $filters);
$total_pages = ceil($total_entries / $per_page);

// Get all admins for filter dropdown
$admins = $con->query("SELECT id, name FROM admins ORDER BY name");

// Get unique action types
$action_types = $con->query("SELECT DISTINCT action_type FROM audit_trail ORDER BY action_type");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Trail - SioSio Admin</title>
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
        
        .audit-entry { border-left: 4px solid #dee2e6; padding: 1rem; margin-bottom: 1rem; background: #f8f9fa; border-radius: 6px; transition: all 0.3s; }
        .audit-entry:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.1); transform: translateX(5px); }
        .audit-entry.critical { border-left-color: #dc3545; }
        .audit-entry.warning { border-left-color: #ffc107; }
        .audit-entry.info { border-left-color: #0dcaf0; }
        .audit-entry.success { border-left-color: #198754; }
        
        .audit-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.5rem; }
        .audit-action { font-weight: 600; color: #333; font-size: 1.05rem; }
        .audit-meta { font-size: 0.85rem; color: #6c757d; text-align: right; }
        .audit-description { color: #495057; margin-bottom: 0.5rem; }
        .audit-details { font-size: 0.85rem; color: #6c757d; display: flex; gap: 1.5rem; flex-wrap: wrap; }
        
        .values-toggle { cursor: pointer; color: var(--siosio-red); text-decoration: none; font-size: 0.85rem; }
        .values-toggle:hover { text-decoration: underline; }
        .values-content { display: none; margin-top: 0.75rem; padding: 0.75rem; background: white; border-radius: 4px; font-size: 0.85rem; }
        .values-content.show { display: block; }
        
        .filter-card { background: #f8f9fa; border-radius: 8px; padding: 1.5rem; margin-bottom: 1.5rem; }
        .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
        .stat-box { background: white; padding: 1.5rem; border-radius: 8px; text-align: center; border: 2px solid #e9ecef; }
        .stat-box h3 { margin: 0; color: var(--siosio-red); font-size: 2rem; }
        .stat-box p { margin: 0.5rem 0 0 0; color: #6c757d; font-size: 0.9rem; }
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
                <li><a href="admin_audit_trail.php" class="active"><i class="bi bi-clock-history"></i> Audit Trail</a></li>
                <li><a href="admin_user_audit.php"><i class="bi bi-person-lines-fill"></i> User Audit</a></li>
                <li><a href="admin_settings.php"><i class="bi bi-gear"></i> Settings</a></li>
                <li><a href="admin_logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div class="top-bar d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-0"><i class="bi bi-clock-history me-2"></i>Audit Trail</h4>
                    <small class="text-muted">System Activity Log</small>
                </div>
                <button class="btn btn-danger" onclick="window.print()">
                    <i class="bi bi-printer"></i> Print Report
                </button>
            </div>
            
            <!-- Statistics -->
            <div class="stats-row">
                <div class="stat-box">
                    <h3><?= number_format($total_entries) ?></h3>
                    <p>Total Entries</p>
                </div>
                <div class="stat-box">
                    <h3><?= $admins->num_rows ?></h3>
                    <p>Active Admins</p>
                </div>
                <div class="stat-box">
                    <h3><?= date('M d, Y') ?></h3>
                    <p>Report Date</p>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="filter-card">
                <h5 class="mb-3"><i class="bi bi-funnel me-2"></i>Filters</h5>
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Admin</label>
                        <select name="admin_id" class="form-select">
                            <option value="">All Admins</option>
                            <?php 
                            $admins->data_seek(0); // Reset pointer
                            while ($admin = $admins->fetch_assoc()): ?>
                                <option value="<?= $admin['id'] ?>" <?= (isset($filters['admin_id']) && $filters['admin_id'] == $admin['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($admin['name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Action Type</label>
                        <select name="action_type" class="form-select">
                            <option value="">All Actions</option>
                            <?php while ($type = $action_types->fetch_assoc()): ?>
                                <option value="<?= $type['action_type'] ?>" <?= (isset($filters['action_type']) && $filters['action_type'] == $type['action_type']) ? 'selected' : '' ?>>
                                    <?= getActionTypeLabel($type['action_type']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">From Date</label>
                        <input type="date" name="date_from" class="form-control" value="<?= $filters['date_from'] ?? '' ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">To Date</label>
                        <input type="date" name="date_to" class="form-control" value="<?= $filters['date_to'] ?? '' ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-danger w-100">
                            <i class="bi bi-search"></i> Filter
                        </button>
                    </div>
                    <?php if (!empty($filters)): ?>
                    <div class="col-12">
                        <a href="admin_audit_trail.php" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-x-circle"></i> Clear Filters
                        </a>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
            
            <!-- Audit Trail Entries -->
            <div class="content-card">
                <h5 class="mb-4">Activity Log (<?= number_format($total_entries) ?> entries)</h5>
                
                <?php if (empty($entries)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-inbox" style="font-size: 4rem;"></i>
                        <p class="mt-3">No audit trail entries found</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($entries as $entry): 
                        $severity_class = '';
                        if (in_array($entry['action_type'], ['user_delete', 'admin_delete', 'product_delete'])) {
                            $severity_class = 'critical';
                        } elseif (in_array($entry['action_type'], ['user_suspend', 'admin_status_change', 'settings_update'])) {
                            $severity_class = 'warning';
                        } elseif (in_array($entry['action_type'], ['login', 'return_approve', 'refund_process'])) {
                            $severity_class = 'success';
                        } else {
                            $severity_class = 'info';
                        }
                    ?>
                    <div class="audit-entry <?= $severity_class ?>">
                        <div class="audit-header">
                            <div>
                                <div class="audit-action">
                                    <i class="<?= getActionTypeIcon($entry['action_type']) ?> me-2"></i>
                                    <?= getActionTypeLabel($entry['action_type']) ?>
                                    <span class="badge bg-<?= getActionTypeBadge($entry['action_type']) ?> ms-2">
                                        <?= htmlspecialchars($entry['action_type']) ?>
                                    </span>
                                </div>
                                <div class="audit-description"><?= htmlspecialchars($entry['action_description']) ?></div>
                            </div>
                            <div class="audit-meta">
                                <div><i class="bi bi-clock me-1"></i><?= date('M d, Y h:i A', strtotime($entry['created_at'])) ?></div>
                                <div><i class="bi bi-person-badge me-1"></i><?= htmlspecialchars($entry['admin_name']) ?> (<?= ucfirst($entry['admin_role']) ?>)</div>
                            </div>
                        </div>
                        <div class="audit-details">
                            <?php if ($entry['affected_table']): ?>
                                <span><i class="bi bi-table me-1"></i>Table: <strong><?= htmlspecialchars($entry['affected_table']) ?></strong></span>
                            <?php endif; ?>
                            <?php if ($entry['affected_id']): ?>
                                <span><i class="bi bi-hash me-1"></i>Record ID: <strong><?= $entry['affected_id'] ?></strong></span>
                            <?php endif; ?>
                            <span><i class="bi bi-geo-alt me-1"></i>IP: <code><?= htmlspecialchars($entry['ip_address']) ?></code></span>
                            
                            <?php if ($entry['old_values'] || $entry['new_values']): ?>
                                <a href="#" class="values-toggle" onclick="toggleValues(event, 'values-<?= $entry['id'] ?>')">
                                    <i class="bi bi-code-square me-1"></i>View Changes
                                </a>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($entry['old_values'] || $entry['new_values']): ?>
                        <div id="values-<?= $entry['id'] ?>" class="values-content">
                            <div class="row">
                                <?php if ($entry['old_values']): ?>
                                <div class="col-md-6">
                                    <strong class="text-danger"><i class="bi bi-dash-circle me-1"></i>Old Values:</strong>
                                    <pre class="mb-0 mt-2"><?= htmlspecialchars(json_encode(json_decode($entry['old_values']), JSON_PRETTY_PRINT)) ?></pre>
                                </div>
                                <?php endif; ?>
                                <?php if ($entry['new_values']): ?>
                                <div class="col-md-6">
                                    <strong class="text-success"><i class="bi bi-plus-circle me-1"></i>New Values:</strong>
                                    <pre class="mb-0 mt-2"><?= htmlspecialchars(json_encode(json_decode($entry['new_values']), JSON_PRETTY_PRINT)) ?></pre>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                    
                    <!-- Pagination -->
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
        function toggleValues(event, id) {
            event.preventDefault();
            const element = document.getElementById(id);
            element.classList.toggle('show');
        }
        
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