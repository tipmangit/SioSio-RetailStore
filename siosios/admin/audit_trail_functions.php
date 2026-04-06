<?php
/**
 * Audit Trail Functions
 * Save this file as: /admin/audit_trail_functions.php
 */

/**
 * Log an audit trail entry
 */
function logAuditTrail($con, $admin_id, $admin_name, $admin_role, $action_type, $action_description, $affected_table = null, $affected_id = null, $old_values = null, $new_values = null) {
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    $old_values_json = $old_values ? json_encode($old_values) : null;
    $new_values_json = $new_values ? json_encode($new_values) : null;
    
    $stmt = $con->prepare("INSERT INTO audit_trail 
        (admin_id, admin_name, admin_role, action_type, action_description, affected_table, affected_id, old_values, new_values, ip_address, user_agent) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    if ($stmt === false) {
        error_log("Audit trail prepare failed: " . $con->error);
        return false;
    }
    
    $stmt->bind_param("isssssissss", 
        $admin_id, 
        $admin_name, 
        $admin_role, 
        $action_type, 
        $action_description, 
        $affected_table, 
        $affected_id, 
        $old_values_json, 
        $new_values_json, 
        $ip_address, 
        $user_agent
    );
    
    $result = $stmt->execute();
    
    if (!$result) {
        error_log("Audit trail insert failed: " . $stmt->error);
    }
    
    $stmt->close();
    return $result;
}

/**
 * Get audit trail entries with filters
 */
function getAuditTrail($con, $filters = [], $limit = 50, $offset = 0) {
    $sql = "SELECT * FROM audit_trail WHERE 1=1";
    $params = [];
    $types = "";
    
    if (!empty($filters['admin_id'])) {
        $sql .= " AND admin_id = ?";
        $params[] = $filters['admin_id'];
        $types .= "i";
    }
    
    if (!empty($filters['action_type'])) {
        $sql .= " AND action_type = ?";
        $params[] = $filters['action_type'];
        $types .= "s";
    }
    
    if (!empty($filters['date_from'])) {
        $sql .= " AND created_at >= ?";
        $params[] = $filters['date_from'];
        $types .= "s";
    }
    
    if (!empty($filters['date_to'])) {
        $sql .= " AND created_at <= ?";
        $params[] = $filters['date_to'] . ' 23:59:59';
        $types .= "s";
    }
    
    if (!empty($filters['affected_table'])) {
        $sql .= " AND affected_table = ?";
        $params[] = $filters['affected_table'];
        $types .= "s";
    }
    
    if (!empty($filters['search'])) {
        $sql .= " AND (admin_name LIKE ? OR action_description LIKE ?)";
        $search = "%" . $filters['search'] . "%";
        $params[] = $search;
        $params[] = $search;
        $types .= "ss";
    }
    
    $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";
    
    $stmt = $con->prepare($sql);
    
    if ($stmt === false) {
        error_log("Get audit trail prepare failed: " . $con->error);
        return [];
    }
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $entries = [];
    while ($row = $result->fetch_assoc()) {
        $entries[] = $row;
    }
    
    $stmt->close();
    return $entries;
}

/**
 * Get total count of audit trail entries
 */
function getAuditTrailCount($con, $filters = []) {
    $sql = "SELECT COUNT(*) as total FROM audit_trail WHERE 1=1";
    $params = [];
    $types = "";
    
    if (!empty($filters['admin_id'])) {
        $sql .= " AND admin_id = ?";
        $params[] = $filters['admin_id'];
        $types .= "i";
    }
    
    if (!empty($filters['action_type'])) {
        $sql .= " AND action_type = ?";
        $params[] = $filters['action_type'];
        $types .= "s";
    }
    
    if (!empty($filters['date_from'])) {
        $sql .= " AND created_at >= ?";
        $params[] = $filters['date_from'];
        $types .= "s";
    }
    
    if (!empty($filters['date_to'])) {
        $sql .= " AND created_at <= ?";
        $params[] = $filters['date_to'] . ' 23:59:59';
        $types .= "s";
    }
    
    if (!empty($filters['search'])) {
        $sql .= " AND (admin_name LIKE ? OR action_description LIKE ?)";
        $search = "%" . $filters['search'] . "%";
        $params[] = $search;
        $params[] = $search;
        $types .= "ss";
    }
    
    $stmt = $con->prepare($sql);
    
    if ($stmt === false) {
        return 0;
    }
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return (int)$row['total'];
}

/**
 * Get action type label for display
 */
function getActionTypeLabel($action_type) {
    $labels = [
        'login' => 'Login',
        'logout' => 'Logout',
        'user_add' => 'User Added',
        'user_update' => 'User Updated',
        'user_delete' => 'User Deleted',
        'user_suspend' => 'User Suspended',
        'user_unsuspend' => 'User Unsuspended',
        'admin_add' => 'Admin Added',
        'admin_update' => 'Admin Updated',
        'admin_status_change' => 'Admin Status Changed',
        'product_add' => 'Product Added',
        'product_update' => 'Product Updated',
        'product_delete' => 'Product Deleted',
        'product_status_change' => 'Product Status Changed',
        'order_status_update' => 'Order Status Updated',
        'order_view' => 'Order Viewed',
        'return_approve' => 'Return Approved',
        'return_reject' => 'Return Rejected',
        'refund_process' => 'Refund Processed',
        'inventory_update' => 'Inventory Updated',
        'settings_update' => 'Settings Updated',
        'notification_send' => 'Notification Sent',
        'cms_update' => 'CMS Updated'
    ];
    
    return $labels[$action_type] ?? ucfirst(str_replace('_', ' ', $action_type));
}

/**
 * Get icon for action type
 */
function getActionTypeIcon($action_type) {
    $icons = [
        'login' => 'bi-box-arrow-in-right',
        'logout' => 'bi-box-arrow-right',
        'user_add' => 'bi-person-plus',
        'user_update' => 'bi-person-check',
        'user_delete' => 'bi-person-x',
        'user_suspend' => 'bi-person-slash',
        'user_unsuspend' => 'bi-person-check',
        'admin_add' => 'bi-shield-plus',
        'admin_update' => 'bi-shield-check',
        'admin_status_change' => 'bi-shield',
        'product_add' => 'bi-box-seam',
        'product_update' => 'bi-pencil-square',
        'product_delete' => 'bi-trash',
        'product_status_change' => 'bi-toggle-on',
        'order_status_update' => 'bi-cart-check',
        'order_view' => 'bi-eye',
        'return_approve' => 'bi-check-circle',
        'return_reject' => 'bi-x-circle',
        'refund_process' => 'bi-cash',
        'inventory_update' => 'bi-clipboard-data',
        'settings_update' => 'bi-gear',
        'notification_send' => 'bi-bell',
        'cms_update' => 'bi-file-text'
    ];
    
    return $icons[$action_type] ?? 'bi-activity';
}

/**
 * Get badge color for action type
 */
function getActionTypeBadge($action_type) {
    $badges = [
        'login' => 'success',
        'logout' => 'secondary',
        'user_add' => 'primary',
        'user_update' => 'info',
        'user_delete' => 'danger',
        'user_suspend' => 'warning',
        'user_unsuspend' => 'success',
        'admin_add' => 'primary',
        'admin_update' => 'info',
        'admin_status_change' => 'warning',
        'product_add' => 'primary',
        'product_update' => 'info',
        'product_delete' => 'danger',
        'product_status_change' => 'warning',
        'order_status_update' => 'info',
        'order_view' => 'secondary',
        'return_approve' => 'success',
        'return_reject' => 'danger',
        'refund_process' => 'success',
        'inventory_update' => 'info',
        'settings_update' => 'warning',
        'notification_send' => 'info',
        'cms_update' => 'info'
    ];
    
    return $badges[$action_type] ?? 'secondary';
}