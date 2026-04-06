<?php
session_start();
include("../config.php");
include("admin_session_check.php");
require_once('../notification_functions.php');
require_once('audit_trail_functions.php');

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

$message = '';
$messageType = '';

// Check for messages from redirect pages (like add/update)
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $messageType = $_SESSION['message_type'];
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

// Determine which tab should be active
$active_tab = $_SESSION['active_tab'] ?? 'users';
unset($_SESSION['active_tab']); // Clear it after reading

// --- USER STATUS TOGGLE ---
if (isset($_POST['toggle_status'])) {
    $user_id = $_POST['user_id'];
    $current_status = $_POST['current_status'];
    $new_status = ($current_status === 'active') ? 'suspended' : 'active';
    
    // Set suspended_until if suspending
    $suspended_until_sql = "";
    if ($new_status === 'suspended') {
        $suspension_time = $_POST['suspension_time'] ?? '1h'; // Default 1 hour
        switch ($suspension_time) {
            case '1h': $duration = '1 HOUR'; break;
            case '1d': $duration = '1 DAY'; break;
            case '7d': $duration = '7 DAY'; break;
            case 'perm': $duration = '99 YEAR'; break; // ~Permanent
            default: $duration = '1 HOUR';
        }
        $suspended_until_sql = ", suspended_until = DATE_ADD(NOW(), INTERVAL $duration)";
    } else {
        $suspended_until_sql = ", suspended_until = NULL"; // Clear on unsuspend
    }
    
    $stmt = $con->prepare("UPDATE userss SET status = ? $suspended_until_sql WHERE user_id = ?");
    $stmt->bind_param("si", $new_status, $user_id);
    
    if ($stmt->execute()) {
        $message = "User status updated successfully!";
        $messageType = "success";
        
        // --- AUDIT TRAIL LOGGING ---
        $action_type = ($new_status === 'suspended') ? 'user_suspend' : 'user_unsuspend';
        logAuditTrail(
            $con,
            $_SESSION['admin_id'],
            $_SESSION['admin_name'],
            $_SESSION['admin_role'],
            $action_type,
            "User ID #{$user_id} status changed to '{$new_status}'",
            'userss',
            $user_id,
            ['status' => $current_status],
            ['status' => $new_status]
        );
        // --- END AUDIT LOG ---
        
    } else {
        $message = "Error updating status: " . $con->error;
        $messageType = "danger";
    }
    $stmt->close();
    $_SESSION['active_tab'] = 'users'; // Set active tab on reload
}

// --- ADMIN STATUS TOGGLE ---
if (isset($_POST['toggle_admin_status'])) {
    if ($_SESSION['admin_role'] !== 'super_admin') {
        $message = "You do not have permission to change admin statuses.";
        $messageType = "danger";
    } else {
        $admin_id = $_POST['admin_id'];
        $current_status = $_POST['current_status'];
        
        if ($admin_id == $_SESSION['admin_id'] && $current_status === 'active') {
            $message = "Error: You cannot deactivate your own account.";
            $messageType = "danger";
        } else {
            $new_status = ($current_status === 'active') ? 'inactive' : 'active';
            $stmt = $con->prepare("UPDATE admins SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $new_status, $admin_id);
            
            if ($stmt->execute()) {
                $message = "Admin status updated successfully!";
                $messageType = "success";
                
                // --- AUDIT TRAIL LOGGING ---
                logAuditTrail(
                    $con,
                    $_SESSION['admin_id'],
                    $_SESSION['admin_name'],
                    $_SESSION['admin_role'],
                    'admin_status_change',
                    "Admin ID #{$admin_id} status changed to '{$new_status}'",
                    'admins',
                    $admin_id,
                    ['status' => $current_status],
                    ['status' => $new_status]
                );
                // --- END AUDIT LOG ---

            } else {
                $message = "Error updating admin status: " . $con->error;
                $messageType = "danger";
            }
            $stmt->close();
        }
    }
    $_SESSION['active_tab'] = 'admins'; // Set active tab on reload
}


// Fetch users
$user_search_query = $_GET['user_search'] ?? '';
$user_sql = "SELECT * FROM userss";
if (!empty($user_search_query)) {
    $user_sql .= " WHERE name LIKE ? OR username LIKE ? OR email LIKE ?";
}
$user_sql .= " ORDER BY user_id DESC";
$user_stmt = $con->prepare($user_sql);
if (!empty($user_search_query)) {
    $search_term = "%" . $user_search_query . "%";
    $user_stmt->bind_param("sss", $search_term, $search_term, $search_term);
}
$user_stmt->execute();
$users_result = $user_stmt->get_result();

// Fetch admins
$admin_search_query = $_GET['admin_search'] ?? '';
$admin_sql = "SELECT * FROM admins";
if (!empty($admin_search_query)) {
    $admin_sql .= " WHERE name LIKE ? OR username LIKE ? OR email LIKE ?";
}
$admin_sql .= " ORDER BY id DESC";
$admin_stmt = $con->prepare($admin_sql);
if (!empty($admin_search_query)) {
    $search_term = "%" . $admin_search_query . "%";
    $admin_stmt->bind_param("sss", $search_term, $search_term, $search_term);
}
$admin_stmt->execute();
$admins_result = $admin_stmt->get_result();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - User Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Joti+One&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin_styles.css">

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
        .sidebar-menu i { margin-right: 0.75rem; }
        .main-content { margin-left: var(--sidebar-width); flex: 1; padding: 2rem; width: calc(100% - var(--sidebar-width)); }
        .nav-tabs .nav-link { color: #6c757d; }
        .nav-tabs .nav-link.active { color: #dc3545; font-weight: bold; border-color: #dee2e6 #dee2e6 #fff; }
    </style>
</head>
<body>

<div class="admin-wrapper">

    <aside class="sidebar">
        <div class="sidebar-header">
            <h3><span class="sio-highlight">Sio</span><span class="sio-highlight">Sio</span> Admin</h3>
            <p class="mb-0 small text-muted">Management</p>
        </div>
        <ul class="sidebar-menu">
                <li><a href="admin_dashboard.php" ><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                <li><a href="admin_products.php"><i class="bi bi-box-seam"></i> Products</a></li>
                <li><a href="admin_orders.php"><i class="bi bi-cart-check"></i> Orders</a></li>
                <li><a href="admin_inventory.php"><i class="bi bi-clipboard-data"></i> Inventory</a></li>
                <li><a href="admin_users.php" class="active"><i class="bi bi-people"></i> Users</a></li>
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

    <main class="main-content">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="page-title">User Management</h2>
            </div>

            <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <nav>
                <div class="nav nav-tabs" id="nav-tab" role="tablist">
                    <button class="nav-link <?php echo ($active_tab == 'users') ? 'active' : ''; ?>" id="nav-users-tab" data-bs-toggle="tab" data-bs-target="#nav-users" type="button" role="tab" aria-controls="nav-users" aria-selected="true">
                        Users (<?php echo $users_result->num_rows; ?>)
                    </button>
                    <button class="nav-link <?php echo ($active_tab == 'admins') ? 'active' : ''; ?>" id="nav-admins-tab" data-bs-toggle="tab" data-bs-target="#nav-admins" type="button" role="tab" aria-controls="nav-admins" aria-selected="false">
                        Admins (<?php echo $admins_result->num_rows; ?>)
                    </button>
                </div>
            </nav>
            <div class="tab-content" id="nav-tabContent">
                
                <div class="tab-pane fade <?php echo ($active_tab == 'users') ? 'show active' : ''; ?>" id="nav-users" role="tabpanel" aria-labelledby="nav-users-tab" tabindex="0">
                    <div class="card shadow-sm mt-3">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span>Manage Users</span>
                            <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#addUserModal">
                                <i class="bi bi-person-plus-fill"></i> Add User
                            </button>
                        </div>
                        <div class="card-body">
                            <form method="GET" class="mb-3">
                                <div class="input-group">
                                    <input type="text" name="user_search" class="form-control" placeholder="Search users by name, username, or email..." value="<?php echo htmlspecialchars($user_search_query); ?>">
                                    <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
                                </div>
                            </form>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Username</th>
                                            <th>Email</th>
                                            <th>Status</th>
                                            <th>Created At</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($row = $users_result->fetch_assoc()): ?>
                                        <?php
                                            // --- NEW: Split name into parts for the edit modal ---
                                            $names = explode(' ', $row['name'], 3);
                                            $fname = $names[0] ?? '';
                                            $mname = (count($names) == 3) ? $names[1] : '';
                                            $lname = (count($names) > 1) ? end($names) : '';
                                        ?>
                                        <tr>
                                            <td><?php echo $row['user_id']; ?></td>
                                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['username']); ?></td>
                                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                                            <td>
                                                <?php if ($row['status'] === 'active'): ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php elseif ($row['status'] === 'suspended'): ?>
                                                    <span class="badge bg-danger">Suspended</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($row['status']); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                            <td>
                                                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editUserModal<?php echo $row['user_id']; ?>" title="Edit User">
                                                    <i class="bi bi-pencil-square"></i>
                                                </button>

                                                <form action="admin_users.php" method="POST" class="d-inline">
                                                    <input type="hidden" name="user_id" value="<?php echo $row['user_id']; ?>">
                                                    <input type="hidden" name="current_status" value="<?php echo $row['status']; ?>">
                                                    
                                                    <?php if ($row['status'] === 'active'): ?>
                                                        <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#suspendModal<?php echo $row['user_id']; ?>" title="Suspend User">
                                                            <i class="bi bi-person-slash"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <button type="submit" name="toggle_status" class="btn btn-success btn-sm" title="Unsuspend User">
                                                            <i class="bi bi-person-check"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </form>

                                                <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteUserModal<?php echo $row['user_id']; ?>" title="Delete User">
                                                    <i class="bi bi-trash-fill"></i>
                                                </button>
                                                
                                                <div class="modal fade" id="suspendModal<?php echo $row['user_id']; ?>" tabindex="-1">
                                                    <div class="modal-dialog">
                                                        <form action="admin_users.php" method="POST">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Suspend User: <?php echo htmlspecialchars($row['username']); ?></h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <p>Select suspension duration:</p>
                                                                    <input type="hidden" name="user_id" value="<?php echo $row['user_id']; ?>">
                                                                    <input type="hidden" name="current_status" value="active">
                                                                    <select name="suspension_time" class="form-select">
                                                                        <option value="1h">1 Hour</option>
                                                                        <option value="1d">1 Day</option>
                                                                        <option value="7d">7 Days</option>
                                                                        <option value="perm">Permanent</option>
                                                                    </select>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                    <button type="submit" name="toggle_status" class="btn btn-danger">Suspend</button>
                                                                </div>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>

                                                <div class="modal fade" id="editUserModal<?php echo $row['user_id']; ?>" tabindex="-1">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Edit User: <?php echo htmlspecialchars($row['username']); ?></h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <form action="admin_user_manage.php" method="POST">
                                                                <div class="modal-body">
                                                                    <input type="hidden" name="user_id" value="<?php echo $row['user_id']; ?>">
                                                                    <div class="mb-3">
                                                                        <label class="form-label">First Name *</label>
                                                                        <input type="text" name="fname" class="form-control" value="<?php echo htmlspecialchars($fname); ?>" required>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Middle Name</label>
                                                                        <input type="text" name="mname" class="form-control" value="<?php echo htmlspecialchars($mname); ?>">
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Last Name *</label>
                                                                        <input type="text" name="lname" class="form-control" value="<?php echo htmlspecialchars($lname); ?>" required>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Username *</label>
                                                                        <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($row['username']); ?>" required>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Email *</label>
                                                                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($row['email']); ?>" required>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Contact Number</label>
                                                                        <input type="tel" name="contact_num" class="form-control" value="<?php echo htmlspecialchars($row['contact_num']); ?>">
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Delivery Address</label>
                                                                        <textarea name="delivery_address" class="form-control" rows="2"><?php echo htmlspecialchars($row['delivery_address']); ?></textarea>
                                                                    </div>
                                                                    <hr>
                                                                    <div class="mb-3">
                                                                        <label class="form-label">New Password (Optional)</label>
                                                                        <input type="password" name="password" class="form-control" minlength="6" placeholder="Leave blank to keep current password">
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                    <button type="submit" name="update_user" class="btn btn-danger">Save Changes</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="modal fade" id="deleteUserModal<?php echo $row['user_id']; ?>" tabindex="-1">
                                                    <div class="modal-dialog">
                                                        <form action="admin_user_manage.php" method="POST">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Delete User</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <input type="hidden" name="user_id" value="<?php echo $row['user_id']; ?>">
                                                                    <p>Are you sure you want to permanently delete this user?</p>
                                                                    <p class="text-danger"><strong><?php echo htmlspecialchars($row['username']); ?> (ID: <?php echo $row['user_id']; ?>)</strong></p>
                                                                    <p class="text-danger">This action cannot be undone and may fail if the user has existing orders.</p>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                    <button type="submit" name="delete_user" class="btn btn-danger">Delete User</button>
                                                                </div>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>

                                            </td>
                                        </tr>
                                        <?php endwhile; $user_stmt->close(); ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="tab-pane fade <?php echo ($active_tab == 'admins') ? 'show active' : ''; ?>" id="nav-admins" role="tabpanel" aria-labelledby="nav-admins-tab" tabindex="0">
                    <div class="card shadow-sm mt-3">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span>Manage Admins</span>
                            <?php if ($_SESSION['admin_role'] === 'super_admin'): ?>
                                <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#addAdminModal">
                                    <i class="bi bi-person-plus-fill"></i> Add Admin
                                </button>
                            <?php else: ?>
                                <button class="btn btn-danger btn-sm" disabled title="Only Super Admins can add new admins">
                                    <i class="bi bi-person-plus-fill"></i> Add Admin
                                </button>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <form method="GET" class="mb-3">
                                <div class="input-group">
                                    <input type="text" name="admin_search" class="form-control" placeholder="Search admins by name, username, or email..." value="<?php echo htmlspecialchars($admin_search_query); ?>">
                                    <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
                                </div>
                            </form>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Username</th>
                                            <th>Email</th>
                                            <th>Role</th>
                                            <th>Status</th>
                                            <th>Created At</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($row = $admins_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $row['id']; ?></td>
                                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['username']); ?></td>
                                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                                            <td>
                                                <span class="badge <?php echo $row['role'] === 'super_admin' ? 'bg-danger' : 'bg-info'; ?>">
                                                    <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $row['role']))); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo $row['status'] === 'active' ? 'bg-success' : 'bg-secondary'; ?>">
                                                    <?php echo htmlspecialchars(ucfirst($row['status'])); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                            <td>
                                                <?php if ($_SESSION['admin_role'] === 'super_admin'): ?>
                                                    <form action="admin_users.php" method="POST" class="d-inline">
                                                        <input type="hidden" name="admin_id" value="<?php echo $row['id']; ?>">
                                                        <input type="hidden" name="current_status" value="<?php echo $row['status']; ?>">
                                                        
                                                        <?php if ($row['id'] == $_SESSION['admin_id']): // Can't deactivate self ?>
                                                            <button type="button" class="btn btn-secondary btn-sm" disabled title="You cannot change your own status.">
                                                                <i class="bi bi-toggles"></i>
                                                            </button>
                                                        <?php elseif ($row['status'] === 'active'): ?>
                                                            <button type="submit" name="toggle_admin_status" class="btn btn-warning btn-sm" title="Deactivate Admin">
                                                                <i class="bi bi-toggle-off"></i>
                                                            </button>
                                                        <?php else: ?>
                                                            <button type="submit" name="toggle_admin_status" class="btn btn-success btn-sm" title="Activate Admin">
                                                                <i class="bi bi-toggle-on"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </form>
                                                <?php else: ?>
                                                    <button class="btn btn-secondary btn-sm" disabled title="Only Super Admins can act.">
                                                        <i class="bi bi-toggles"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endwhile; $admin_stmt->close(); ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </main>

</div> <div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="admin_user_manage.php" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">First Name *</label>
                        <input type="text" name="fname" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Middle Name</label>
                        <input type="text" name="mname" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Last Name *</label>
                        <input type="text" name="lname" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Username *</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password *</label>
                        <input type="password" name="password" class="form-control" minlength="6" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contact Number</label>
                        <input type="tel" name="contact_num" class="form-control" placeholder="09XXXXXXXXX">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Delivery Address</label>
                        <textarea name="delivery_address" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_user" class="btn btn-danger">Add User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="addAdminModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Admin</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="admin_admin_add.php" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Full Name *</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Username *</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password *</label>
                        <input type="password" name="password" class="form-control" minlength="6" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role *</label>
                        <select name="role" class="form-select" required>
                            <option value="admin">Admin</option> 
                            <option value="super_admin">Super Admin</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_admin" class="btn btn-danger">Add Admin</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Optional: If you want URL hashes to control tabs
    document.addEventListener('DOMContentLoaded', function() {
        var hash = window.location.hash;
        if (hash) {
            var triggerEl = document.querySelector('.nav-tabs button[data-bs-target="' + hash + '"]');
            if (triggerEl) {
                var tab = new bootstrap.Tab(triggerEl);
                tab.show();
            }
        }
    });

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