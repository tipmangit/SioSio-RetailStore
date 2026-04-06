<?php
// user_audit_functions.php

/**
 * Gets the user's real IP address, checking for proxies.
 * @return string The IP address.
 */
function get_real_ip_address() {
    // Check for Cloudflare header
    if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
        return $_SERVER["HTTP_CF_CONNECTING_IP"];
    }
    
    // Check for X-Forwarded-For header (can be a list)
    if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
        $ip_list = explode(',', $_SERVER["HTTP_X_FORWARDED_FOR"]);
        return trim($ip_list[0]); // Take the first IP in the list
    }
    
    // Check for Client-IP header
    if (isset($_SERVER["HTTP_CLIENT_IP"])) {
        return $_SERVER["HTTP_CLIENT_IP"];
    }
    
    // Fallback to the standard remote address
    return $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
}


/**
 * Logs a user action to the user_audit_trail table.
 *
 * @param mysqli $con The database connection.
 * @param int|null $user_id The user's ID (if known).
 * @param string|null $username The username or email used (if known).
 * @param string $action_type A short code for the action (e.g., 'login_success').
 * @param string $description A human-readable description of the action.
 * @return bool True on success.
 */
function logUserAuditTrail($con, $user_id, $username, $action_type, $description) {
    
    // Use the new, accurate function
    $ip_address = get_real_ip_address();
    
    $stmt = $con->prepare(
        "INSERT INTO user_audit_trail (user_id, username, action_type, action_description, ip_address, created_at) 
         VALUES (?, ?, ?, ?, ?, NOW())"
    );
    
    if ($stmt === false) {
        // --- THIS IS THE FIX ---
        error_log("logUserAuditTrail: Failed to prepare statement. Error: " . $con->error);
        return false;
    }
    
    $stmt->bind_param("issss", $user_id, $username, $action_type, $description, $ip_address);
    
    if (!$stmt->execute()) {
        // --- THIS IS THE SECOND FIX ---
        error_log("logUserAuditTrail: Failed to execute. Error: " . $stmt->error);
    }
    
    $stmt->close();
    return true;
}

?>