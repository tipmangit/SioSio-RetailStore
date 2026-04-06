<?php
session_start();
include("../config.php");
include("admin_session_check.php");
require_once('audit_trail_functions.php');

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

$message = '';
$messageType = '';

// Handle order status update
if (isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $new_status = $_POST['order_status'];
    
    $stmt = $con->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?");
    $stmt->bind_param("si", $new_status, $order_id);
    
    if ($stmt->execute()) {
        // ✅ Notify user of status change
        require_once(__DIR__ . '/../notification_functions.php');
        notifyOrderStatusChange($con, $order_id, $new_status);
        
        $message = "Order #$order_id status updated to " . ucfirst($new_status) . "!";
        $messageType = "success";
        logAuditTrail(
            $con,
            $_SESSION['admin_id'],
            $_SESSION['admin_name'],
            $_SESSION['admin_role'],
            'order_status_update',
            "Updated order #{$order_id} status to '{$new_status}'",
            'orders',
            $order_id,
            null,
            ['order_status' => $new_status]
        );
    } else {
        $message = "Error updating order status.";
        $messageType = "danger";
    }
    $stmt->close();
}

// Handle bulk status update
if (isset($_POST['bulk_update'])) {
    if (!empty($_POST['selected_orders']) && !empty($_POST['bulk_status'])) {
        $selected_orders = $_POST['selected_orders'];
        $bulk_status = $_POST['bulk_status'];
        $updated_count = 0;
        
        foreach ($selected_orders as $order_id) {
            $stmt = $con->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?");
            $stmt->bind_param("si", $bulk_status, $order_id);
            if ($stmt->execute()) {
                $updated_count++;
            }
            $stmt->close();
        }
        
        $message = "$updated_count order(s) updated to " . ucfirst($bulk_status) . "!";
        $messageType = "success";
    } else {
        $message = "Please select orders and a status.";
        $messageType = "warning";
    }
}

// Filters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Fetch orders
$sql = "SELECT o.*, u.name as customer_name, u.email as customer_email 
        FROM orders o 
        JOIN userss u ON o.user_id = u.user_id 
        WHERE 1=1";

if ($status_filter) {
    $sql .= " AND o.order_status = '" . $con->real_escape_string($status_filter) . "'";
}
if ($search) {
    $sql .= " AND (o.tracking_number LIKE '%" . $con->real_escape_string($search) . "%' 
              OR u.name LIKE '%" . $con->real_escape_string($search) . "%')";
}
$sql .= " ORDER BY o.created_at DESC";

$orders = $con->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders Management - SioSio Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Joti+One&display=swap" rel="stylesheet">
    <style>
        :root {
            --siosio-red: #dc3545;
            --sidebar-width: 260px;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        
        .admin-wrapper {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, #1a1a1a 0%, #2d2d2d 100%);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        
        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }
        
        .sidebar-header h3 {
            font-family: 'Joti One', cursive;
            margin: 0;
            font-size: 1.5rem;
        }
        
        .sio-highlight {
            color: var(--siosio-red);
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 1rem 0;
            margin: 0;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 0.875rem 1.5rem;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: rgba(220, 53, 69, 0.1);
            color: white;
            border-left: 3px solid var(--siosio-red);
        }
        
        .sidebar-menu i {
            margin-right: 0.75rem;
            font-size: 1.2rem;
        }
        
        .main-content {
            margin-left: var(--sidebar-width);
            flex: 1;
            padding: 2rem;
            width: calc(100% - var(--sidebar-width));
        }
        
        .top-bar {
            background: white;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }
        
        .content-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .status-processing { background: #fff3cd; color: #856404; }
        .status-shipped { background: #cfe2ff; color: #084298; }
        .status-delivered { background: #d1e7dd; color: #0f5132; }
        .status-cancelled { background: #f8d7da; color: #842029; }
        
        .bulk-actions-bar {
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            border-radius: 6px;
            padding: 0.75rem 1rem;
            margin-bottom: 1rem;
            display: none;
        }
        
        .bulk-actions-bar.active {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .status-form {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .status-select {
            min-width: 140px;
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h3><span class="sio-highlight">Sio</span><span class="sio-highlight">Sio</span> Admin</h3>
                <p class="mb-0 small text-muted">Management Panel</p>
            </div>
            
            <ul class="sidebar-menu">
                <li><a href="admin_dashboard.php" ><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                <li><a href="admin_products.php"><i class="bi bi-box-seam"></i> Products</a></li>
                <li><a href="admin_orders.php" class="active"><i class="bi bi-cart-check"></i> Orders</a></li>
                <li><a href="admin_inventory.php"><i class="bi bi-clipboard-data"></i> Inventory</a></li>
                <li><a href="admin_users.php"><i class="bi bi-people"></i> Users</a></li>
                <li><a href="admin_chat_support.php"><i class="bi bi-chat-dots"></i> Chat Support</a></li>
                <li><a href="admin_reports.php"><i class="bi bi-graph-up"></i> Reports</a></li>
                <li><a href="admin_returns.php"><i class="bi bi-box-arrow-in-left"></i> Returns</a></li>
                <li><a href="admin_cms.php"><i class="bi bi-file-text"></i> Content Management</a></li>
                <li><a href="admin_notifications.php"><i class="bi bi-bell"></i> Notifications</a></li>
                <li><a href="admin_audit_trail.php"><i class="bi bi-clock-history"></i> Audit Trail</a></li>
                <li><a href="admin_user_audit.php"><i class="bi bi-person-lines-fill"></i> User Audit</a></li>
                <li><a href="admin_settings.php"><i class="bi bi-gear"></i> Settings</a></li>
                <li><a href="admin_logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
            </ul>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <div class="top-bar">
                <h4 class="mb-0">Orders Management</h4>
            </div>
            
            <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                <i class="bi bi-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-triangle' ?>-fill me-2"></i>
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <div class="content-card">
                <!-- Filters -->
                <div class="row mb-4">
                    <div class="col-md-10">
                        <form method="GET" class="row g-2">
                            <div class="col-md-5">
                                <input type="text" name="search" class="form-control" 
                                       placeholder="Search by tracking or customer name..." value="<?= htmlspecialchars($search) ?>">
                            </div>
                            <div class="col-md-4">
                                <select name="status" class="form-select">
                                    <option value="">All Statuses</option>
                                    <option value="processing" <?= $status_filter === 'processing' ? 'selected' : '' ?>>Processing</option>
                                    <option value="shipped" <?= $status_filter === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                                    <option value="delivered" <?= $status_filter === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                    <option value="cancelled" <?= $status_filter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-outline-primary w-100">
                                    <i class="bi bi-search"></i> Search
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Bulk Actions Bar -->
                <div class="bulk-actions-bar" id="bulkActionsBar">
                    <div>
                        <strong><span id="selectedCount">0</span> order(s) selected</strong>
                    </div>
                    <form method="POST" class="d-flex gap-2" id="bulkForm">
                        <select name="bulk_status" class="form-select form-select-sm status-select" required>
                            <option value="">Select Status</option>
                            <option value="processing">Processing</option>
                            <option value="shipped">Shipped</option>
                            <option value="delivered">Delivered</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                        <button type="submit" name="bulk_update" class="btn btn-sm btn-primary">
                            <i class="bi bi-arrow-repeat"></i> Update Selected
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearSelection()">
                            Clear
                        </button>
                    </form>
                </div>
                
                <!-- Orders Table -->
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th width="30">
                                    <input type="checkbox" class="form-check-input" id="selectAll" onchange="toggleSelectAll(this)">
                                </th>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Tracking</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Payment</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($orders->num_rows === 0): ?>
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                                        <p class="mt-2">No orders found</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php while ($order = $orders->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" class="form-check-input order-checkbox" 
                                               value="<?= $order['order_id'] ?>" 
                                               onchange="updateBulkActions()">
                                    </td>
                                    <td><strong>#<?= $order['order_id'] ?></strong></td>
                                    <td>
                                        <?= htmlspecialchars($order['customer_name']) ?><br>
                                        <small class="text-muted"><?= htmlspecialchars($order['customer_email']) ?></small>
                                    </td>
                                    <td><code><?= htmlspecialchars($order['tracking_number']) ?></code></td>
                                    <td><strong>₱<?= number_format($order['total'], 2) ?></strong></td>
                                    <td>
                                        <form method="POST" class="status-form" onsubmit="return confirmStatusChange(event, '<?= $order['order_id'] ?>')">
                                            <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                            <select name="order_status" class="form-select form-select-sm status-select" required>
                                                <option value="processing" <?= $order['order_status'] === 'processing' ? 'selected' : '' ?>>Processing</option>
                                                <option value="shipped" <?= $order['order_status'] === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                                                <option value="delivered" <?= $order['order_status'] === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                                <option value="cancelled" <?= $order['order_status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                            </select>
                                            <button type="submit" name="update_status" class="btn btn-sm btn-success" title="Update Status">
                                                <i class="bi bi-check-lg"></i>
                                            </button>
                                        </form>
                                        <div class="mt-1">
                                            <span class="status-badge status-<?= $order['order_status'] ?>">
                                                <?= ucfirst($order['order_status']) ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $order['pay_status'] === 'paid' ? 'success' : 'warning' ?>">
                                            <?= ucfirst($order['pay_status']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('M d, Y', strtotime($order['created_at'])) ?></td>
                                    <td>
                                        <a href="admin_order_details.php?id=<?= $order['order_id'] ?>" 
                                           class="btn btn-sm btn-outline-primary" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmStatusChange(event, orderId) {
            const select = event.target.querySelector('select[name="order_status"]');
            const newStatus = select.value;
            return confirm(`Are you sure you want to change Order #${orderId} status to "${newStatus}"?`);
        }
        
        function toggleSelectAll(checkbox) {
            const checkboxes = document.querySelectorAll('.order-checkbox');
            checkboxes.forEach(cb => cb.checked = checkbox.checked);
            updateBulkActions();
        }
        
        function updateBulkActions() {
            const checkboxes = document.querySelectorAll('.order-checkbox:checked');
            const count = checkboxes.length;
            const bulkBar = document.getElementById('bulkActionsBar');
            const countSpan = document.getElementById('selectedCount');
            const bulkForm = document.getElementById('bulkForm');
            const selectAllCheckbox = document.getElementById('selectAll');
            
            countSpan.textContent = count;
            
            if (count > 0) {
                bulkBar.classList.add('active');
                
                // Clear previous hidden inputs
                const existingInputs = bulkForm.querySelectorAll('input[name="selected_orders[]"]');
                existingInputs.forEach(input => input.remove());
                
                // Add new hidden inputs for selected orders
                checkboxes.forEach(cb => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'selected_orders[]';
                    input.value = cb.value;
                    bulkForm.appendChild(input);
                });
            } else {
                bulkBar.classList.remove('active');
                selectAllCheckbox.checked = false;
            }
        }
        
        function clearSelection() {
            document.querySelectorAll('.order-checkbox').forEach(cb => cb.checked = false);
            document.getElementById('selectAll').checked = false;
            updateBulkActions();
        }

        (function() {
            const timeoutInMilliseconds = 1800000; // 30 minutes (30 * 60 * 1000)
            let inactivityTimer;

            // Function to redirect to the logout page
            function logout() {
                // You can add a reason for a custom message on the login page
                window.location.href = 'admin_logout.php?reason=idle';
            }

            // Function to reset the timer
            function resetTimer() {
                clearTimeout(inactivityTimer);
                inactivityTimer = setTimeout(logout, timeoutInMilliseconds);
            }

            // Reset the timer whenever the user interacts with the page
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