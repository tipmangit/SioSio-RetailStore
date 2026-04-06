<?php
/**
 * Notification Functions
 * Core functions for managing notifications
 */

/**
 * Create a notification
 * @param mysqli $con Database connection
 * @param array $data Notification data
 * @return int|false Notification ID or false on failure
 */
function createNotification($con, $data) {
    $required = ['recipient_type', 'type', 'title', 'message'];
    foreach ($required as $field) {
        if (!isset($data[$field])) {
            return false;
        }
    }
    
    $stmt = $con->prepare("INSERT INTO notifications 
        (user_id, admin_id, recipient_type, order_id, product_id, type, title, message, action_url, priority, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    
    $user_id = $data['user_id'] ?? null;
    $admin_id = $data['admin_id'] ?? null;
    $order_id = $data['order_id'] ?? null;
    $product_id = $data['product_id'] ?? null;
    $action_url = $data['action_url'] ?? null;
    $priority = $data['priority'] ?? 'normal';
    
    $stmt->bind_param("iiiissssss",
        $user_id,
        $admin_id,
        $data['recipient_type'],
        $order_id,
        $product_id,
        $data['type'],
        $data['title'],
        $data['message'],
        $action_url,
        $priority
    );
    
    if ($stmt->execute()) {
        $notification_id = $stmt->insert_id;
        $stmt->close();
        
        // Send email if preferences allow
        sendNotificationEmail($con, $notification_id);
        
        return $notification_id;
    }
    
    $stmt->close();
    return false;
}

/**
 * Get notifications for a user
 * @param mysqli $con Database connection
 * @param int $user_id User ID
 * @param bool $unread_only Get only unread notifications
 * @param int $limit Limit number of results
 * @return array Notifications
 */
function getUserNotifications($con, $user_id, $unread_only = false, $limit = 50) {
    $sql = "SELECT * FROM notifications 
            WHERE user_id = ? AND recipient_type = 'user'";
    
    if ($unread_only) {
        $sql .= " AND is_read = 0";
    }
    
    $sql .= " ORDER BY created_at DESC LIMIT ?";
    
    $stmt = $con->prepare($sql);
    $stmt->bind_param("ii", $user_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $notifications = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    return $notifications;
}

/**
 * Get notifications for admin
 * @param mysqli $con Database connection
 * @param int $admin_id Admin ID
 * @param bool $unread_only Get only unread notifications
 * @param int $limit Limit number of results
 * @return array Notifications
 */
function getAdminNotifications($con, $admin_id, $unread_only = false, $limit = 50) {
    $sql = "SELECT * FROM notifications 
            WHERE admin_id = ? AND recipient_type = 'admin'";
    
    if ($unread_only) {
        $sql .= " AND is_read = 0";
    }
    
    $sql .= " ORDER BY created_at DESC LIMIT ?";
    
    $stmt = $con->prepare($sql);
    $stmt->bind_param("ii", $admin_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $notifications = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    return $notifications;
}

/**
 * Get unread notification count
 * @param mysqli $con Database connection
 * @param int $id User or Admin ID
 * @param string $type 'user' or 'admin'
 * @return int Count of unread notifications
 */
function getUnreadCount($con, $id, $type = 'user') {
    if ($type === 'user') {
        $stmt = $con->prepare("SELECT COUNT(*) as count FROM notifications 
                              WHERE user_id = ? AND recipient_type = 'user' AND is_read = 0");
    } else {
        $stmt = $con->prepare("SELECT COUNT(*) as count FROM notifications 
                              WHERE admin_id = ? AND recipient_type = 'admin' AND is_read = 0");
    }
    
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return (int)$row['count'];
}

/**
 * Mark notification as read
 * @param mysqli $con Database connection
 * @param int $notification_id Notification ID
 * @return bool Success
 */
function markAsRead($con, $notification_id) {
    $stmt = $con->prepare("UPDATE notifications SET is_read = 1, read_at = NOW() 
                          WHERE notification_id = ?");
    $stmt->bind_param("i", $notification_id);
    $success = $stmt->execute();
    $stmt->close();
    
    return $success;
}

/**
 * Mark all notifications as read
 * @param mysqli $con Database connection
 * @param int $id User or Admin ID
 * @param string $type 'user' or 'admin'
 * @return bool Success
 */
function markAllAsRead($con, $id, $type = 'user') {
    if ($type === 'user') {
        $stmt = $con->prepare("UPDATE notifications SET is_read = 1, read_at = NOW() 
                              WHERE user_id = ? AND recipient_type = 'user' AND is_read = 0");
    } else {
        $stmt = $con->prepare("UPDATE notifications SET is_read = 1, read_at = NOW() 
                              WHERE admin_id = ? AND recipient_type = 'admin' AND is_read = 0");
    }
    
    $stmt->bind_param("i", $id);
    $success = $stmt->execute();
    $stmt->close();
    
    return $success;
}

/**
 * Delete notification
 * @param mysqli $con Database connection
 * @param int $notification_id Notification ID
 * @return bool Success
 */
function deleteNotification($con, $notification_id) {
    $stmt = $con->prepare("DELETE FROM notifications WHERE notification_id = ?");
    $stmt->bind_param("i", $notification_id);
    $success = $stmt->execute();
    $stmt->close();
    
    return $success;
}

/**
 * Delete old notifications (older than 30 days and read)
 * @param mysqli $con Database connection
 * @param int $days Days to keep
 * @return int Number of deleted notifications
 */
function deleteOldNotifications($con, $days = 30) {
    $stmt = $con->prepare("DELETE FROM notifications 
                          WHERE is_read = 1 AND read_at < DATE_SUB(NOW(), INTERVAL ? DAY)");
    $stmt->bind_param("i", $days);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();
    
    return $affected;
}

/**
 * Send notification email
 * @param mysqli $con Database connection
 * @param int $notification_id Notification ID
 * @return bool Success
 */
function sendNotificationEmail($con, $notification_id) {
    // Get notification details
    $stmt = $con->prepare("SELECT n.*, u.email as user_email, u.name as user_name,
                          a.email as admin_email, a.name as admin_name
                          FROM notifications n
                          LEFT JOIN userss u ON n.user_id = u.user_id
                          LEFT JOIN admins a ON n.admin_id = a.id
                          WHERE n.notification_id = ?");
    $stmt->bind_param("i", $notification_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $notification = $result->fetch_assoc();
    $stmt->close();
    
    if (!$notification) {
        return false;
    }
    
    // Check notification preferences
    if ($notification['recipient_type'] === 'user') {
        $pref_stmt = $con->prepare("SELECT email_notifications FROM notification_preferences 
                                    WHERE user_id = ?");
        $pref_stmt->bind_param("i", $notification['user_id']);
        $pref_stmt->execute();
        $pref_result = $pref_stmt->get_result();
        $prefs = $pref_result->fetch_assoc();
        $pref_stmt->close();
        
        if (!$prefs || !$prefs['email_notifications']) {
            return false;
        }
        
        $to_email = $notification['user_email'];
        $to_name = $notification['user_name'];
    } else {
        $pref_stmt = $con->prepare("SELECT email_notifications FROM notification_preferences 
                                    WHERE admin_id = ?");
        $pref_stmt->bind_param("i", $notification['admin_id']);
        $pref_stmt->execute();
        $pref_result = $pref_stmt->get_result();
        $prefs = $pref_result->fetch_assoc();
        $pref_stmt->close();
        
        if (!$prefs || !$prefs['email_notifications']) {
            return false;
        }
        
        $to_email = $notification['admin_email'];
        $to_name = $notification['admin_name'];
    }
    
    // Send email using PHPMailer or mail()
    $subject = $notification['title'];
    $message = $notification['message'];
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: SioSio Store <noreply@siosio.com>" . "\r\n";
    
    $html_message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #dc3545; color: white; padding: 20px; text-align: center; }
            .content { background: #f8f9fa; padding: 20px; }
            .button { display: inline-block; padding: 10px 20px; background: #dc3545; color: white; text-decoration: none; border-radius: 5px; }
            .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>SioSio Store</h1>
            </div>
            <div class='content'>
                <h2>{$notification['title']}</h2>
                <p>{$notification['message']}</p>
                " . ($notification['action_url'] ? "<p><a href='https://siosio.kesug.com{$notification['action_url']}' class='button'>View Details</a></p>" : "") . "
            </div>
            <div class='footer'>
                <p>This is an automated notification from SioSio Store.</p>
                <p>If you wish to stop receiving these emails, please update your notification preferences.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return mail($to_email, $subject, $html_message, $headers);
}

/**
 * Create notification for order status change
 * @param mysqli $con Database connection
 * @param int $order_id Order ID
 * @param string $new_status New order status
 */
function notifyOrderStatusChange($con, $order_id, $new_status) {
    // Get order details
    $stmt = $con->prepare("SELECT user_id, tracking_number, total FROM orders WHERE order_id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    $stmt->close();
    
    if (!$order) {
        return false;
    }
    
    $messages = [
        'processing' => 'Your order is now being processed.',
        'shipped' => 'Your order is on its way! Track: ' . $order['tracking_number'],
        'delivered' => 'Your order has been delivered. Thank you for shopping with us!',
        'cancelled' => 'Your order has been cancelled.'
    ];
    
    $titles = [
        'processing' => 'Order is Being Processed',
        'shipped' => 'Order Shipped!',
        'delivered' => 'Order Delivered',
        'cancelled' => 'Order Cancelled'
    ];
    
    $types = [
        'processing' => 'order_processing',
        'shipped' => 'order_shipped',
        'delivered' => 'order_delivered',
        'cancelled' => 'order_cancelled'
    ];
    
    $priorities = [
        'shipped' => 'high',
        'delivered' => 'high',
        'cancelled' => 'normal',
        'processing' => 'normal'
    ];
    
    return createNotification($con, [
        'user_id' => $order['user_id'],
        'recipient_type' => 'user',
        'order_id' => $order_id,
        'type' => $types[$new_status] ?? 'order_processing',
        'title' => $titles[$new_status] ?? 'Order Update',
        'message' => $messages[$new_status] ?? 'Your order status has been updated.',
        'action_url' => '../cart/order_details.php?order_id=' . $order_id,
        'priority' => $priorities[$new_status] ?? 'normal'
    ]);
}

/**
 * Notify admin of new order
 * @param mysqli $con Database connection
 * @param int $order_id Order ID
 */
function notifyAdminNewOrder($con, $order_id) {
    // Get order details
    $stmt = $con->prepare("SELECT total FROM orders WHERE order_id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    $stmt->close();
    
    if (!$order) {
        return false;
    }
    
    // Get all active admins
    $stmt = $con->prepare("SELECT id FROM admins WHERE status = 'active'");
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($admin = $result->fetch_assoc()) {
        createNotification($con, [
            'admin_id' => $admin['id'],
            'recipient_type' => 'admin',
            'order_id' => $order_id,
            'type' => 'order_placed',
            'title' => 'New Order Received',
            'message' => 'Order #' . $order_id . ' has been placed. Total: ₱' . number_format($order['total'], 2),
            'action_url' => '../admin/admin_order_details.php?id=' . $order_id,
            'priority' => 'high'
        ]);
    }
    
    $stmt->close();
}

/**
 * Notify users about new product
* @param mysqli $con Database connection
 * @param int $product_id Product ID
 */
function notifyUsersNewProduct($con, $product_id) {
    // Get product details
    $stmt = $con->prepare("SELECT name, category, price FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    $stmt->close();
    
    if (!$product) {
        return false;
    }
    
    // FIXED: Get ALL active users who want new product notifications
    // Includes users without preferences (new users default to receiving notifications)
    $stmt = $con->prepare("
        SELECT DISTINCT u.user_id 
        FROM userss u
        LEFT JOIN notification_preferences np ON u.user_id = np.user_id
        WHERE u.status = 'active' 
        AND (np.new_products = 1 OR np.new_products IS NULL)
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $notification_count = 0;
    while ($user = $result->fetch_assoc()) {
        $created = createNotification($con, [
            'user_id' => $user['user_id'],
            'recipient_type' => 'user',
            'product_id' => $product_id,
            'type' => 'new_product',
            'title' => 'New Product Available! 🎉',
            'message' => 'Check out our new ' . $product['category'] . ': ' . $product['name'] . ' - ₱' . number_format($product['price'], 2),
            'action_url' => '../products/product.php',
            'priority' => 'low'
        ]);
        
        if ($created) {
            $notification_count++;
        }
    }
    
    $stmt->close();
    return $notification_count;
}

/**
 * Notify admin of low stock
 * @param mysqli $con Database connection
 * @param int $product_id Product ID 
 * @param int $quantity Current quantity
 */
function notifyAdminLowStock($con, $product_id, $quantity) {
    // Get product details
    $stmt = $con->prepare("SELECT name FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    $stmt->close();
    
    if (!$product) {
        return false;
    }
    
    // Get all active admins
    $stmt = $con->prepare("SELECT id FROM admins WHERE status = 'active'");
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($admin = $result->fetch_assoc()) {
        createNotification($con, [
            'admin_id' => $admin['id'],
            'recipient_type' => 'admin',
            'product_id' => $product_id,
            'type' => 'low_stock_alert',
            'title' => 'Low Stock Alert',
            'message' => 'Product "' . $product['name'] . '" is running low. Only ' . $quantity . ' units remaining.',
            'action_url' => '../admin/admin_products.php',
            'priority' => 'urgent'
        ]);
    }
    
    $stmt->close();
}

/**
 * Notify admin of return request
 * @param mysqli $con Database connection
 * @param int $return_id Return ID
 * @param int $order_id Order ID
 */
function notifyAdminReturnRequest($con, $return_id, $order_id) {
    // Get all active admins
    $stmt = $con->prepare("SELECT id FROM admins WHERE status = 'active'");
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($admin = $result->fetch_assoc()) {
        createNotification($con, [
            'admin_id' => $admin['id'],
            'recipient_type' => 'admin',
            'order_id' => $order_id,
            'type' => 'return_requested',
            'title' => 'New Return Request',
            'message' => 'Return request #' . $return_id . ' for order #' . $order_id . ' needs review.',
            'action_url' => '../admin/admin_returns.php?id=' . $return_id,
            'priority' => 'high'
        ]);
    }
    
    $stmt->close();
}

/**
 * Notify user of return status
 * @param mysqli $con Database connection
 * @param int $return_id Return ID
 * @param string $status Return status
 */
function notifyUserReturnStatus($con, $return_id, $status) {
    // Get return details
    $stmt = $con->prepare("SELECT user_id, order_id, admin_notes FROM return_requests WHERE return_id = ?");
    $stmt->bind_param("i", $return_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $return = $result->fetch_assoc();
    $stmt->close();
    
    if (!$return) {
        return false;
    }
    
    $messages = [
        'approved' => 'Your return request for order #' . $return['order_id'] . ' has been approved. Refund will be processed soon.',
        'rejected' => 'Your return request for order #' . $return['order_id'] . ' has been rejected. ' . ($return['admin_notes'] ?? '')
    ];
    
    $titles = [
        'approved' => 'Return Request Approved',
        'rejected' => 'Return Request Rejected'
    ];
    
    $types = [
        'approved' => 'return_approved',
        'rejected' => 'return_rejected'
    ];
    
    return createNotification($con, [
        'user_id' => $return['user_id'],
        'recipient_type' => 'user',
        'order_id' => $return['order_id'],
        'type' => $types[$status] ?? 'return_requested',
        'title' => $titles[$status] ?? 'Return Status Update',
        'message' => $messages[$status] ?? 'Your return request status has been updated.',
        'action_url' => '../cart/order_details.php?order_id=' . $return['order_id'],
        'priority' => 'high'
    ]);
}

/**
 * Notify user of refund processed
 * @param mysqli $con Database connection
 * @param int $order_id Order ID
 * @param float $amount Refund amount
 */
function notifyUserRefundProcessed($con, $order_id, $amount) {
    // Get order details
    $stmt = $con->prepare("SELECT user_id FROM orders WHERE order_id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    $stmt->close();
    
    if (!$order) {
        return false;
    }
    
    return createNotification($con, [
        'user_id' => $order['user_id'],
        'recipient_type' => 'user',
        'order_id' => $order_id,
        'type' => 'refund_processed',
        'title' => 'Refund Processed',
        'message' => 'Your refund of ₱' . number_format($amount, 2) . ' for order #' . $order_id . ' has been processed.',
        'action_url' => '../cart/order_details.php?order_id=' . $order_id,
        'priority' => 'high'
    ]);
}

/**
 * Notify admin of new user registration
 * @param mysqli $con Database connection
 * @param int $user_id User ID
 */
function notifyAdminNewUser($con, $user_id) {
    // Get user details
    $stmt = $con->prepare("SELECT username, email FROM userss WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    if (!$user) {
        return false;
    }
    
    // Get all active admins
    $stmt = $con->prepare("SELECT id FROM admins WHERE status = 'active'");
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($admin = $result->fetch_assoc()) {
        createNotification($con, [
            'admin_id' => $admin['id'],
            'recipient_type' => 'admin',
            'type' => 'new_user_registered',
            'title' => 'New User Registered',
            'message' => 'New user "' . $user['username'] . '" (' . $user['email'] . ') has registered.',
            'action_url' => '../admin/admin_users.php',
            'priority' => 'low'
        ]);
    }
    
    $stmt->close();
}

/**
 * Get notification preferences
 * @param mysqli $con Database connection
 * @param int $id User or Admin ID
 * @param string $type 'user' or 'admin'
 * @return array|false Preferences or false
 */
function getNotificationPreferences($con, $id, $type = 'user') {
    if ($type === 'user') {
        $stmt = $con->prepare("SELECT * FROM notification_preferences WHERE user_id = ?");
    } else {
        $stmt = $con->prepare("SELECT * FROM notification_preferences WHERE admin_id = ?");
    }
    
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $prefs = $result->fetch_assoc();
    $stmt->close();
    
    return $prefs;
}

/**
 * Update notification preferences
 * @param mysqli $con Database connection
 * @param int $id User or Admin ID
 * @param array $preferences Preferences to update
 * @param string $type 'user' or 'admin'
 * @return bool Success
 */
function updateNotificationPreferences($con, $id, $preferences, $type = 'user') {
    // Get existing preferences
    $existing = getNotificationPreferences($con, $id, $type);
    
    if (!$existing) {
        // Create new preferences
        if ($type === 'user') {
            $stmt = $con->prepare("INSERT INTO notification_preferences (user_id) VALUES (?)");
        } else {
            $stmt = $con->prepare("INSERT INTO notification_preferences (admin_id) VALUES (?)");
        }
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }
    
    // Build update query
    $updates = [];
    $params = [];
    $types_str = "";
    
    foreach ($preferences as $key => $value) {
        $updates[] = "$key = ?";
        $params[] = $value;
        $types_str .= "i";
    }
    
    if (empty($updates)) {
        return false;
    }
    
    $params[] = $id;
    $types_str .= "i";
    
    if ($type === 'user') {
        $sql = "UPDATE notification_preferences SET " . implode(", ", $updates) . " WHERE user_id = ?";
    } else {
        $sql = "UPDATE notification_preferences SET " . implode(", ", $updates) . " WHERE admin_id = ?";
    }
    
    $stmt = $con->prepare($sql);
    $stmt->bind_param($types_str, ...$params);
    $success = $stmt->execute();
    $stmt->close();
    
    return $success;
}
?>