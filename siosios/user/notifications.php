<?php
session_start();
include("../config.php");
require_once('../notification_functions.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../loginreg/logreg.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['mark_read'])) {
        markAsRead($con, (int)$_POST['notification_id']);
    } elseif (isset($_POST['mark_all_read'])) {
        markAllAsRead($con, $user_id, 'user');
    } elseif (isset($_POST['delete_notification'])) {
        deleteNotification($con, (int)$_POST['notification_id']);
    }
    header("Location: notifications.php");
    exit;
}

// Get filter
$filter = $_GET['filter'] ?? 'all';

// Get notifications
if ($filter === 'unread') {
    $notifications = getUserNotifications($con, $user_id, true, 100);
} else {
    $notifications = getUserNotifications($con, $user_id, false, 100);
}

$unread_count = getUnreadCount($con, $user_id, 'user');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Notifications - SioSio Store</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
    body { background: #f8f9fa; }
    
    .notification-card {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 1rem;
        border: 1px solid #e9ecef;
        transition: all 0.3s;
        position: relative;
    }
    
    .notification-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        transform: translateY(-2px);
    }
    
    .notification-card.unread {
        background: #fff5f5;
        border-left: 4px solid #dc3545;
    }
    
    .notification-header {
        display: flex;
        justify-content: space-between;
        align-items: start;
        margin-bottom: 0.5rem;
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
    .icon-payment { background: #d1e7dd; color: #198754; }
    .icon-return { background: #fff3cd; color: #ffc107; }
    .icon-product { background: #f8d7da; color: #dc3545; }
    .icon-refund { background: #d1ecf1; color: #0c5460; }
    
    .notification-content {
        flex: 1;
    }
    
    .notification-title {
        font-weight: 600;
        font-size: 1.1rem;
        margin-bottom: 0.5rem;
        color: #333;
    }
    
    .notification-message {
        color: #666;
        line-height: 1.6;
        margin-bottom: 0.5rem;
    }
    
    .notification-time {
        font-size: 0.875rem;
        color: #999;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .notification-actions {
        display: flex;
        gap: 0.5rem;
        margin-top: 1rem;
    }
    
    .priority-badge {
        position: absolute;
        top: 1rem;
        right: 1rem;
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
    }
    
    .filter-tabs {
        background: white;
        border-radius: 12px;
        padding: 1rem;
        margin-bottom: 2rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    
    .filter-tabs .nav-link {
        color: #f80808ff;
        border: none;
        padding: 0.5rem 1.5rem;
        border-radius: 8px;
        transition: all 0.3s;
    }

    .filter-tabs .nav-link.active {
        background: #dc3545;
        color: white;
    }
    
    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        background: white;
        border-radius: 12px;
    }
    
    .empty-state i {
        font-size: 4rem;
        color: #ddd;
        margin-bottom: 1rem;
    }
</style>
</head>
<body>
<?php include("../headfoot/header.php"); ?>

<div class="container py-5" style="margin-top: 100px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-danger mb-0">
            <i class="bi bi-bell"></i> Notifications
            <?php if ($unread_count > 0): ?>
                <span class="badge bg-danger"><?= $unread_count ?></span>
            <?php endif; ?>
        </h2>
        
        <?php if ($unread_count > 0): ?>
        <form method="POST" style="display: inline;">
            <button type="submit" name="mark_all_read" class="btn btn-outline-danger">
                <i class="bi bi-check-all"></i> Mark All as Read
            </button>
        </form>
        <?php endif; ?>
    </div>
    
    <!-- Filter Tabs -->
    <div class="filter-tabs">
        <ul class="nav nav-pills">
            <li class="nav-item">
                <a class="nav-link <?= $filter === 'all' ? 'active' : '' ?>" href="?filter=all">
                    All Notifications
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $filter === 'unread' ? 'active' : '' ?>" href="?filter=unread">
                    Unread (<?= $unread_count ?>)
                </a>
            </li>
        </ul>
    </div>
    
    <!-- Notifications List -->
    <?php if (empty($notifications)): ?>
        <div class="empty-state">
            <i class="bi bi-bell-slash"></i>
            <h4>No notifications yet</h4>
            <p class="text-muted">When you receive notifications, they'll appear here.</p>
            <a href="../products/product.php" class="btn btn-danger mt-3">
                Start Shopping
            </a>
        </div>
    <?php else: ?>
        <?php foreach ($notifications as $notification): ?>
            <div class="notification-card <?= $notification['is_read'] ? '' : 'unread' ?>">
                <?php if ($notification['priority'] === 'high' || $notification['priority'] === 'urgent'): ?>
                    <span class="priority-badge badge bg-<?= $notification['priority'] === 'urgent' ? 'danger' : 'warning' ?>">
                        <?= ucfirst($notification['priority']) ?>
                    </span>
                <?php endif; ?>
                
                <div class="d-flex">
                    <div class="notification-icon <?php
                        if (strpos($notification['type'], 'order') !== false) echo 'icon-order';
                        elseif (strpos($notification['type'], 'payment') !== false) echo 'icon-payment';
                        elseif (strpos($notification['type'], 'return') !== false) echo 'icon-return';
                        elseif (strpos($notification['type'], 'refund') !== false) echo 'icon-refund';
                        else echo 'icon-product';
                    ?>">
                        <i class="bi <?= getNotificationIcon($notification['type']) ?>"></i>
                    </div>
                    
                    <div class="notification-content">
                        <div class="notification-header">
                            <div>
                                <div class="notification-title"><?= htmlspecialchars($notification['title']) ?></div>
                                <div class="notification-message"><?= htmlspecialchars($notification['message']) ?></div>
                                <div class="notification-time">
                                    <i class="bi bi-clock"></i>
                                    <?= timeAgo($notification['created_at']) ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="notification-actions">
                            <?php if ($notification['action_url']): ?>
                                <a href="<?= htmlspecialchars($notification['action_url']) ?>" 
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

<?php include("../headfoot/footer.php"); ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>