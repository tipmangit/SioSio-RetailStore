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

$admin_id = $_SESSION['admin_id'];

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['mark_read'])) {
        markAsRead($con, (int)$_POST['notification_id']);
    } elseif (isset($_POST['mark_all_read'])) {
        markAllAsRead($con, $admin_id, 'admin');
    } elseif (isset($_POST['delete_notification'])) {
        deleteNotification($con, (int)$_POST['notification_id']);
    }
    header("Location: admin_notifications.php");
    exit;
}

// Get filter
$filter = $_GET['filter'] ?? 'all';
$type_filter = $_GET['type'] ?? '';

// Build query
$notifications = getAdminNotifications($con, $admin_id, $filter === 'unread', 200);

// Filter by type if specified
if ($type_filter) {
    $notifications = array_filter($notifications, function($n) use ($type_filter) {
        return $n['type'] === $type_filter;
    });
}

/**
 * Get icon for notification type
 */
function getNotificationIcon($type) {
    $icons = [
        'order_placed' => 'bi-cart-check',
        'order_processing' => 'bi-hourglass-split',
        'order_shipped' => 'bi-truck',
        'order_delivered' => 'bi-check-circle',
        'order_cancelled' => 'bi-x-circle',
        'payment_success' => 'bi-credit-card',
        'payment_failed' => 'bi-exclamation-triangle',
        'return_requested' => 'bi-arrow-counterclockwise',
        'return_approved' => 'bi-check-circle',
        'return_rejected' => 'bi-x-circle',
        'refund_processed' => 'bi-cash',
        'new_product' => 'bi-box-seam',
        'low_stock_favorite' => 'bi-exclamation-triangle',
        'low_stock_alert' => 'bi-exclamation-triangle',
        'new_user_registered' => 'bi-person-plus',
        'promotion' => 'bi-tag'
    ];
    return $icons[$type] ?? 'bi-bell';
}

/**
 * Format time ago
 */
function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M d, Y', $timestamp);
    }
}

/**
 * Get action URL based on notification type
 */
function getNotificationActionUrl($notification) {
    $type = $notification['type'];
    
    if (strpos($type, 'order') !== false) {
        return 'admin_orders.php';
    } elseif (strpos($type, 'return') !== false) {
        return 'admin_returns.php';
    } elseif (strpos($type, 'low_stock') !== false) {
        return 'admin_inventory.php';
    } elseif ($type === 'new_user_registered') {
        return 'admin_users.php';
    } elseif ($type === 'new_product') {
        return 'admin_products.php';
    }
    
    return $notification['action_url'] ?? '';
}

$unread_count = getUnreadCount($con, $admin_id, 'admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - SioSio Admin</title>
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
            margin-bottom: 1.5rem;
        }
        
        .notification-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border: 1px solid #e9ecef;
            transition: all 0.3s;
            position: relative;
        }
        
        .notification-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .notification-card.unread {
            background: #fff5f5;
            border-left: 4px solid #dc3545;
        }
        
        .notification-card.urgent {
            border-left: 4px solid #dc3545;
            background: #fff0f0;
        }
        
        .notification-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-right: 1rem;
            flex-shrink: 0;
        }
        
        .icon-order { background: #e7f5ff; color: #0d6efd; }
        .icon-return { background: #fff3cd; color: #ffc107; }
        .icon-stock { background: #f8d7da; color: #dc3545; }
        .icon-user { background: #d1e7dd; color: #198754; }
        
        .priority-urgent {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        .filter-pills {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-bottom: 1.5rem;
        }
        
        .filter-pill {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            border: 1px solid #ddd;
            background: white;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            color: #666;
            font-size: 0.9rem;
        }
        
        .filter-pill:hover {
            background: #f8f9fa;
            color: #333;
        }
        
        .filter-pill.active {
            background: #dc3545;
            color: white;
            border-color: #dc3545;
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
                <li><a href="admin_dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                <li><a href="admin_products.php"><i class="bi bi-box-seam"></i> Products</a></li>
                <li><a href="admin_orders.php"><i class="bi bi-cart-check"></i> Orders</a></li>
                <li><a href="admin_inventory.php"><i class="bi bi-clipboard-data"></i> Inventory</a></li>
                <li><a href="admin_users.php"><i class="bi bi-people"></i> Users</a></li>
                <li><a href="admin_chat_support.php"><i class="bi bi-chat-dots"></i> Chat Support</a></li>
                <li><a href="admin_reports.php"><i class="bi bi-graph-up"></i> Reports</a></li>
                <li><a href="admin_returns.php"><i class="bi bi-box-arrow-in-left"></i> Returns</a></li>
                <li><a href="admin_cms.php"><i class="bi bi-file-text"></i> Content Management</a></li>
                <li><a href="admin_notifications.php" class="active"><i class="bi bi-bell"></i> Notifications</a></li>
                <li><a href="admin_audit_trail.php"><i class="bi bi-clock-history"></i> Audit Trail</a></li>
                <li><a href="admin_user_audit.php"><i class="bi bi-person-lines-fill"></i> User Audit</a></li>
                <li><a href="admin_settings.php"><i class="bi bi-gear"></i> Settings</a></li>
                <li><a href="admin_logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
            </ul>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <div class="top-bar d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-0">
                        Notifications 
                        <?php if ($unread_count > 0): ?>
                            <span class="badge bg-danger"><?= $unread_count ?></span>
                        <?php endif; ?>
                    </h4>
                </div>
                <?php if ($unread_count > 0): ?>
                    <form method="POST" style="display: inline;">
                        <button type="submit" name="mark_all_read" class="btn btn-outline-danger">
                            <i class="bi bi-check-all"></i> Mark All as Read
                        </button>
                    </form>
                <?php endif; ?>
            </div>
            
            <div class="content-card">
                <!-- Filter Pills -->
                <div class="filter-pills">
                    <a href="?filter=all" class="filter-pill <?= $filter === 'all' && empty($type_filter) ? 'active' : '' ?>">
                        All
                    </a>
                    <a href="?filter=unread" class="filter-pill <?= $filter === 'unread' ? 'active' : '' ?>">
                        Unread (<?= $unread_count ?>)
                    </a>
                    <a href="?type=order_placed" class="filter-pill <?= $type_filter === 'order_placed' ? 'active' : '' ?>">
                        <i class="bi bi-cart-check"></i> New Orders
                    </a>
                    <a href="?type=return_requested" class="filter-pill <?= $type_filter === 'return_requested' ? 'active' : '' ?>">
                        <i class="bi bi-arrow-counterclockwise"></i> Returns
                    </a>
                    <a href="?type=low_stock_alert" class="filter-pill <?= $type_filter === 'low_stock_alert' ? 'active' : '' ?>">
                        <i class="bi bi-exclamation-triangle"></i> Low Stock
                    </a>
                    <a href="?type=new_user_registered" class="filter-pill <?= $type_filter === 'new_user_registered' ? 'active' : '' ?>">
                        <i class="bi bi-person-plus"></i> New Users
                    </a>
                </div>
                
                <!-- Notifications List -->
                <?php if (empty($notifications)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-bell-slash" style="font-size: 4rem; color: #ddd;"></i>
                        <h5 class="mt-3 text-muted">No notifications found</h5>
                        <p class="text-muted">All caught up!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($notifications as $notification): ?>
                        <div class="notification-card <?= $notification['is_read'] ? '' : 'unread' ?> <?= $notification['priority'] === 'urgent' ? 'urgent priority-urgent' : '' ?>">
                            <div class="d-flex">
                                <div class="notification-icon <?php
                                    if (strpos($notification['type'], 'order') !== false) echo 'icon-order';
                                    elseif (strpos($notification['type'], 'return') !== false) echo 'icon-return';
                                    elseif (strpos($notification['type'], 'stock') !== false) echo 'icon-stock';
                                    elseif (strpos($notification['type'], 'user') !== false) echo 'icon-user';
                                    else echo 'icon-order';
                                ?>">
                                    <i class="bi <?= getNotificationIcon($notification['type']) ?>"></i>
                                </div>
                                
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <h6 class="mb-1"><?= htmlspecialchars($notification['title']) ?></h6>
                                            <p class="mb-2 text-muted"><?= htmlspecialchars($notification['message']) ?></p>
                                            <small class="text-muted">
                                                <i class="bi bi-clock"></i> <?= timeAgo($notification['created_at']) ?>
                                                <?php if ($notification['priority'] !== 'normal'): ?>
                                                    <span class="badge bg-<?= $notification['priority'] === 'urgent' ? 'danger' : ($notification['priority'] === 'high' ? 'warning' : 'secondary') ?> ms-2">
                                                        <?= ucfirst($notification['priority']) ?>
                                                    </span>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex gap-2">
                                        <?php 
                                            $action_url = getNotificationActionUrl($notification);
                                            if ($action_url):
                                        ?>
                                            <a href="<?= htmlspecialchars($action_url) ?>" 
                                               class="btn btn-sm btn-danger">
                                                <i class="bi bi-eye"></i> View Details
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if (!$notification['is_read']): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="notification_id" value="<?= $notification['notification_id'] ?>">
                                                <button type="submit" name="mark_read" class="btn btn-sm btn-outline-secondary">
                                                    <i class="bi bi-check"></i> Mark as Read
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <form method="POST" style="display: inline;" 
                                              onsubmit="return confirm('Delete this notification?')">
                                            <input type="hidden" name="notification_id" value="<?= $notification['notification_id'] ?>">
                                            <button type="submit" name="delete_notification" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>