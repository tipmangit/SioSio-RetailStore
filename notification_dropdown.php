<?php
/**
 * Notification Dropdown Component (HTML ONLY)
 * Include this in user header and admin header
 */

// Ensure notification functions are loaded
if (!function_exists('getUserNotifications')) {
    // Adjust this path if notification_functions.php is in a different relative location
    require_once(__DIR__ . '/notification_functions.php'); 
}

// Determine if this is admin or user
$is_admin = isset($_SESSION['admin_id']);
$recipient_id = $is_admin ? $_SESSION['admin_id'] : ($_SESSION['user_id'] ?? 0);
$recipient_type = $is_admin ? 'admin' : 'user';

// Get notifications
if ($recipient_id > 0) {
    if ($is_admin) {
        $notifications = getAdminNotifications($con, $recipient_id, false, 10);
        $unread_count = getUnreadCount($con, $recipient_id, 'admin');
    } else {
        $notifications = getUserNotifications($con, $recipient_id, false, 10);
        $unread_count = getUnreadCount($con, $recipient_id, 'user');
    }
} else {
    $notifications = [];
    $unread_count = 0;
}

/**
 * Get icon for notification type
 */
if (!function_exists('getNotificationIcon')) {
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
}

/**
 * Get color for notification priority
 */
if (!function_exists('getPriorityColor')) {
    function getPriorityColor($priority) {
        $colors = [
            'low' => 'text-secondary',
            'normal' => 'text-primary',
            'high' => 'text-warning',
            'urgent' => 'text-danger'
        ];
        return $colors[$priority] ?? 'text-primary';
    }
}

/**
 * Format time ago
 */
if (!function_exists('timeAgo')) {
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
}
?>

<!-- Notification Bell Icon with Badge -->
<style>
.notification-dropdown {
    position: relative;
}

.notification-bell {
    position: relative;
    cursor: pointer;
    font-size: 1.5rem;
    color: #ee0f0fff;
    transition: color 0.3s;
}

.notification-bell:hover {
    color: #dc3545;
}

.notification-badge {
    position: absolute;
    top: -5px;
    right: -10px;
    background: #dc3545;
    color: white;
    border-radius: 50%;
    padding: 2px 6px;
    font-size: 0.7rem;
    font-weight: bold;
    min-width: 18px;
    text-align: center;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

.notification-dropdown-menu {
    position: absolute;
    top: 100%;
    right: 0;
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    width: 380px;
    max-height: 500px;
    overflow: hidden;
    display: none;
    z-index: 1000;
    margin-top: 10px;
}

.notification-dropdown-menu.show {
    display: block;
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.notification-header {
    padding: 15px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f8f9fa;
}

.notification-header h6 {
    margin: 0;
    font-weight: 600;
}

.notification-list {
    max-height: 400px;
    overflow-y: auto;
}

.notification-item {
    padding: 12px 15px;
    border-bottom: 1px solid #f0f0f0;
    cursor: pointer;
    transition: background 0.2s;
    display: flex;
    gap: 12px;
}

.notification-item:hover {
    background: #f8f9fa;
}

.notification-item.unread {
    background: #fff5f5;
}

.notification-item.unread::before {
    content: '';
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
    width: 4px;
    height: 70%;
    background: #dc3545;
    border-radius: 0 4px 4px 0;
}

.notification-icon {
    font-size: 1.5rem;
    flex-shrink: 0;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f0f0f0;
    border-radius: 50%;
}

.notification-content {
    flex: 1;
    min-width: 0;
}

.notification-title {
    font-weight: 600;
    font-size: 0.9rem;
    margin-bottom: 4px;
    color: #333;
}

.notification-message {
    font-size: 0.85rem;
    color: #666;
    line-height: 1.4;
    margin-bottom: 4px;
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

.notification-time {
    font-size: 0.75rem;
    color: #999;
}

.notification-footer {
    padding: 10px 15px;
    border-top: 1px solid #eee;
    text-align: center;
    background: #f8f9fa;
}

.notification-footer a {
    color: #dc3545;
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 500;
}

.notification-footer a:hover {
    text-decoration: underline;
}

.empty-notifications {
    padding: 40px 20px;
    text-align: center;
    color: #999;
}

.empty-notifications i {
    font-size: 3rem;
    margin-bottom: 10px;
    opacity: 0.3;
}

.mark-all-read {
    color: #dc3545;
    font-size: 0.85rem;
    cursor: pointer;
    border: none;
    background: none;
    padding: 0;
}

.mark-all-read:hover {
    text-decoration: underline;
}
</style>

<div class="notification-dropdown">
    <div class="notification-bell" id="notificationBell">
        <i class="bi bi-bell"></i>
        <?php if ($unread_count > 0): ?>
            <span class="notification-badge" id="notificationBadge"><?= $unread_count > 99 ? '99+' : $unread_count ?></span>
        <?php endif; ?>
    </div>
    
    <div class="notification-dropdown-menu" id="notificationDropdown">
        <div class="notification-header">
            <h6>Notifications</h6>
            <?php if ($unread_count > 0): ?>
                <button class="mark-all-read" onclick="markAllNotificationsAsRead()">
                    Mark all as read
                </button>
            <?php endif; ?>
        </div>
        
        <div class="notification-list" id="notificationList">
            <?php if (empty($notifications)): ?>
                <div class="empty-notifications">
                    <i class="bi bi-bell-slash"></i>
                    <p>No notifications yet</p>
                </div>
            <?php else: ?>
                <?php foreach ($notifications as $notification): ?>
                    <div class="notification-item <?= $notification['is_read'] ? '' : 'unread' ?>" 
                         onclick="handleNotificationClick(<?= $notification['notification_id'] ?>, '<?= htmlspecialchars($notification['action_url'] ?? '') ?>')">
                        <div class="notification-icon <?= getPriorityColor($notification['priority']) ?>">
                            <i class="bi <?= getNotificationIcon($notification['type']) ?>"></i>
                        </div>
                        <div class="notification-content">
                            <div class="notification-title"><?= htmlspecialchars($notification['title']) ?></div>
                            <div class="notification-message"><?= htmlspecialchars($notification['message']) ?></div>
                            <div class="notification-time"><?= timeAgo($notification['created_at']) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($notifications)): ?>
            <div class="notification-footer">
                <a href="<?= $is_admin ? '../admin/admin_notifications.php' : '../user/notifications.php' ?>">
                    View All Notifications
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Toggle notification dropdown
document.getElementById('notificationBell').addEventListener('click', function(e) {
    e.stopPropagation();
    const dropdown = document.getElementById('notificationDropdown');
    dropdown.classList.toggle('show');
});

// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
    const dropdown = document.getElementById('notificationDropdown');
    const bell = document.getElementById('notificationBell');
    
    if (!dropdown.contains(e.target) && !bell.contains(e.target)) {
        dropdown.classList.remove('show');
    }
});

// Handle notification click
function handleNotificationClick(notificationId, actionUrl) {
    // Mark as read
    fetch('<?= $is_admin ? "../admin" : "../user" ?>/mark_notification_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ notification_id: notificationId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update badge count
            updateBadgeCount();
        }
    });
    
    // Redirect if action URL exists
    if (actionUrl) {
        // Check if it's a new product notification with product_id parameter
        if (actionUrl.includes('products.php?product_id=')) {
            // Extract product_id from URL
            const urlParams = new URLSearchParams(actionUrl.split('?')[1]);
            const productId = urlParams.get('product_id');
            
            if (productId) {
                // Store the product ID in sessionStorage to open modal after page load
                sessionStorage.setItem('openProductModal', productId);
            }
        }
        
        window.location.href = actionUrl;
    }
}

// Mark all notifications as read
function markAllNotificationsAsRead() {
    fetch('<?= $is_admin ? "../admin" : "../user" ?>/mark_all_notifications_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reload to update UI
            location.reload();
        }
    });
}

// Update badge count
function updateBadgeCount() {
    fetch('<?= $is_admin ? "../admin" : "../user" ?>/get_unread_count.php')
        .then(response => response.json())
        .then(data => {
            const badge = document.getElementById('notificationBadge');
            if (data.count > 0) {
                if (badge) {
                    badge.textContent = data.count > 99 ? '99+' : data.count;
                } else {
                    // Create badge if it doesn't exist
                    const newBadge = document.createElement('span');
                    newBadge.id = 'notificationBadge';
                    newBadge.className = 'notification-badge';
                    newBadge.textContent = data.count > 99 ? '99+' : data.count;
                    document.getElementById('notificationBell').appendChild(newBadge);
                }
            } else if (badge) {
                badge.remove();
            }
        });
}

// Poll for new notifications every 30 seconds
setInterval(function() {
    updateBadgeCount();
}, 30000);
</script>