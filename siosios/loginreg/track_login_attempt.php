<?php
/**
 * File: track_login_attempt.php
 * Tracks failed login attempts and logs all login activity.
 * UPDATED: Now handles both User and Admin logins.
 */

// Set the content type to JSON. This is crucial!
header('Content-Type: application/json');

// This will help catch any stray warnings or notices
// and prevent them from breaking the JSON output.
ob_start();

session_start();
include("../config.php");
require_once('../notification_functions.php');
require_once('../user_audit_functions.php'); // We'll use this for admin logins too

// Helper function to send a JSON error and stop the script
function send_json_error($message, $extra_data = []) {
    // Clear any pending output (like PHP warnings)
    ob_end_clean();
    echo json_encode(array_merge([
        'success' => false,
        'message' => $message
    ], $extra_data));
    exit;
}

// Gracefully handle database connection errors
if (!$con || $con->connect_error) {
    // Log the error for your records
    error_log('Database connection error: ' . ($con ? $con->connect_error : 'Unknown error'));
    send_json_error('An internal server error occurred. Please try again later. (Code: DB_CONN)');
}

$identifier = trim($_POST['login_identifier'] ?? '');
$password = trim($_POST['password'] ?? '');
$ip_address = get_real_ip_address(); // Use the new, accurate function

// --- Create/Alter tables (errors here are less critical but good to check) ---
if (!$con->query("CREATE TABLE IF NOT EXISTS login_attempts (
    attempt_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    email VARCHAR(255),
    attempt_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    success TINYINT DEFAULT 0,
    ip_address VARCHAR(45),
    FOREIGN KEY (user_id) REFERENCES userss(user_id) ON DELETE CASCADE,
    INDEX (user_id),
    INDEX (email),
    INDEX (attempt_time)
)")) {
    error_log('Failed to create login_attempts: ' . $con->error);
}

if (!$con->query("ALTER TABLE userss ADD COLUMN IF NOT EXISTS suspended_until DATETIME")) {
    error_log('Failed to alter userss: ' . $con->error);
}

// --- Check for recent failed attempts ---
$stmt = $con->prepare("SELECT COUNT(*) as fail_count FROM login_attempts
                            WHERE email = ? AND success = 0
                            AND attempt_time > DATE_SUB(NOW(), INTERVAL 1 MINUTE)");

if ($stmt === false) {
    error_log('Prepare failed (count): ' . $con->error);
    send_json_error('An internal database error occurred. (Code: L1)');
}

$stmt->bind_param("s", $identifier);
$stmt->execute();
$result = $stmt->get_result();
$fail_count = $result->fetch_assoc()['fail_count'];
$stmt->close();

// --- Lock account if 5+ failed attempts ---
if ($fail_count >= 5) {
    $stmt = $con->prepare("INSERT INTO login_attempts (email, success, ip_address) VALUES (?, 0, ?)");
    
    if ($stmt === false) {
        error_log('Prepare failed (lock insert): ' . $con->error);
        send_json_error('An internal database error occurred. (Code: L2)');
    }
    
    $stmt->bind_param("ss", $identifier, $ip_address);
    $stmt->execute();
    $stmt->close();
    
    // Notify admin
    notifyAdminFailedLoginAttempts($con, $identifier, $fail_count + 1);

    // --- USER AUDIT ---
    $desc = "Account locked (5+ attempts) for identifier '{$identifier}'.";
    logUserAuditTrail($con, null, $identifier, 'login_fail_locked', $desc);
    // ------------------

    send_json_error('Too many failed login attempts. Please try again in 1 minute.', ['locked' => true]);
}

// --- Try to authenticate user ---
$stmt = $con->prepare("SELECT user_id, username, password, status, suspended_until FROM userss
                            WHERE email = ? OR username = ?");

if ($stmt === false) {
    error_log('Prepare failed (select user): ' . $con->error);
    send_json_error('An internal database error occurred. (Code: L3)');
}

$stmt->bind_param("ss", $identifier, $identifier);
$stmt->execute();
$res = $stmt->get_result();
$row = null; // Initialize $row

if ($res->num_rows === 1) {
    // ---
    // --- FOUND A USER ---
    // ---
    $row = $res->fetch_assoc();
    $user_id = $row['user_id'];
    
    // Check if account is suspended
    if ($row['status'] === 'suspended') {
        if ($row['suspended_until'] && strtotime($row['suspended_until']) <= time()) {
            // Suspension time has expired, auto-unlock the account
            $unlock_stmt = $con->prepare("UPDATE userss SET status = 'active', suspended_until = NULL, login_attempts = 0 WHERE user_id = ?");
            if ($unlock_stmt) {
                $unlock_stmt->bind_param("i", $user_id);
                $unlock_stmt->execute();
                $unlock_stmt->close();
                
                // --- USER AUDIT ---
                $desc = "Account auto-unlocked for user '{$row['username']}' (ID: $user_id) upon login attempt.";
                logUserAuditTrail($con, $user_id, $row['username'], 'user_autounlock', $desc);
                // ------------------
            }
            // Let the login proceed normally now
        } else {
            // Account is still suspended. Calculate and format the remaining time.
            $remaining_time = strtotime($row['suspended_until']) - time();
            $stmt->close(); // Close the database connection first

            $permanent_threshold = 60 * 60 * 24 * 365 * 50;
            $error_message = "";

            if ($remaining_time > $permanent_threshold) {
                $error_message = "Account permanently suspended.";
            } elseif ($remaining_time > (60 * 60 * 48)) {
                $days = ceil($remaining_time / (60 * 60 * 24));
                $error_message = "Account suspended. Try again in {$days} days.";
            } elseif ($remaining_time > 3600) {
                $hours = floor($remaining_time / 3600);
                $minutes = floor(($remaining_time % 3600) / 60);
                $error_message = "Account suspended. Try again in {$hours}h {$minutes}m.";
            } elseif ($remaining_time > 0) {
                $minutes = floor($remaining_time / 60);
                $seconds = $remaining_time % 60;
                $error_message = "Account suspended. Try again in {$minutes}m {$seconds}s.";
            } else {
                $error_message = "Account suspended.";
            }
            
            // --- USER AUDIT ---
            $desc = "Login attempt on suspended account for user '{$row['username']}' (ID: $user_id). Message: $error_message";
            logUserAuditTrail($con, $user_id, $row['username'], 'login_fail_suspended', $desc);
            // ------------------
            
            send_json_error($error_message, ['suspended' => true]);
        }
    }
    
    // Verify password
    if (password_verify($password, $row['password'])) {
        // Login successful
        $stmt_success = $con->prepare("INSERT INTO login_attempts (user_id, email, success, ip_address)
                                        VALUES (?, ?, 1, ?)");
        if ($stmt_success === false) {
            error_log('Prepare failed (success insert): ' . $con->error);
        } else {
            $stmt_success->bind_param("iss", $user_id, $identifier, $ip_address);
            $stmt_success->execute();
            $stmt_success->close();
        }
        
        $_SESSION['user_id'] = $user_id;
        $_SESSION['valid'] = $row['username'] ?? '';
        $stmt->close();
        
        // --- USER AUDIT ---
        logUserAuditTrail($con, $user_id, $row['username'], 'login_success', 'User successfully logged in.');
        // ------------------
        
        // --- Success ---
        ob_end_clean(); // Clear buffer
        echo json_encode(['success' => true, 'redirect' => '../homepage/index.php']);
        exit;
    }
    
} else {
    // ---
    // --- NO USER FOUND, CHECK ADMINS ---
    // ---
    $stmt->close(); // Close the user statement
    $admin_stmt = $con->prepare("SELECT id, name, username, password, role, status FROM admins
                                  WHERE email = ? OR username = ?");
    
    if ($admin_stmt === false) {
        error_log('Prepare failed (select admin): ' . $con->error);
        send_json_error('An internal database error occurred. (Code: L4)');
    }
    
    $admin_stmt->bind_param("ss", $identifier, $identifier);
    $admin_stmt->execute();
    $admin_res = $admin_stmt->get_result();
    
    if ($admin_res->num_rows === 1) {
        // --- FOUND AN ADMIN ---
        $row = $admin_res->fetch_assoc(); // Use $row to store admin data for auditing
        
        // Verify password
        if (password_verify($password, $row['password'])) {
            
            // Check if admin account is active
            if ($row['status'] !== 'active') {
                // --- USER AUDIT (for admin) ---
                $desc = "Login attempt on inactive admin account for '{$row['username']}' (ID: {$row['id']}).";
                logUserAuditTrail($con, $row['id'], $row['username'], 'admin_login_fail_inactive', $desc);
                // ------------------
                send_json_error('Your admin account is inactive. Please contact the super admin.');
            }
            
            // --- ADMIN LOGIN SUCCESSFUL ---
            $_SESSION['admin_id'] = $row['id'];
            $_SESSION['admin_name'] = $row['name'];
            $_SESSION['admin_role'] = $row['role'];
            $_SESSION['admin_valid'] = $row['username'];
            
            $admin_stmt->close();
            
            // --- USER AUDIT (for admin) ---
            $desc = "Admin '{$row['username']}' (ID: {$row['id']}) logged in successfully.";
            logUserAuditTrail($con, $row['id'], $row['username'], 'admin_login_success', $desc);
            // ------------------
            
            // --- Success ---
            ob_end_clean(); // Clear buffer
            echo json_encode(['success' => true, 'redirect' => '../admin/admin_dashboard.php']); // Redirect to admin
            exit;
        }
    }
    $admin_stmt->close();
}


// --- Login failed (User not found OR Admin not found OR password incorrect) ---
$stmt_fail = $con->prepare("INSERT INTO login_attempts (email, success, ip_address) VALUES (?, 0, ?)");

if ($stmt_fail === false) {
    error_log('Prepare failed (fail insert): ' . $con->error);
} else {
    $stmt_fail->bind_param("ss", $identifier, $ip_address);
    $stmt_fail->execute();
    $stmt_fail->close();
}

if (isset($stmt) && $stmt && $stmt->num_rows > 0) {
    $stmt->close();
}

// --- USER AUDIT ---
if ($row) {
    // User or Admin was found, but password_verify failed
    $is_admin = isset($row['role']);
    $user_id = $is_admin ? $row['id'] : $row['user_id'];
    $username = $row['username'];
    $action_type = $is_admin ? 'admin_login_fail_pass' : 'login_fail_password';
    $desc = "Failed login attempt (wrong password) for ".($is_admin ? 'admin' : 'user')." '{$username}' (ID: {$user_id}).";
    
    logUserAuditTrail($con, $user_id, $username, $action_type, $desc);
} else {
    // User was not found in either table
    $desc = "Failed login attempt (user not found) for identifier '{$identifier}'.";
    logUserAuditTrail($con, null, $identifier, 'login_fail_user', $desc);
}
// ------------------

send_json_error('Invalid email/username or password.');


/**
 * Notify admin of failed login attempts leading to a lock
 */
function notifyAdminFailedLoginAttempts($con, $email, $attempts) {
    $stmt = $con->prepare("SELECT id FROM admins WHERE status = 'active'");
    if (!$stmt) {
        error_log('Notify admin prepare failed: ' . $con->error);
        return; // Fail silently
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($admin = $result->fetch_assoc()) {
        // Assuming createNotification() is a robust function
        createNotification($con, [
            'admin_id' => $admin['id'],
            'recipient_type' => 'admin',
            'type' => 'login_security',
            'title' => 'Multiple Failed Login Attempts',
            'message' => 'Email "' . htmlspecialchars($email) . '" has ' . $attempts . ' failed login attempts and is temporarily locked for 1 minute.',
            'action_url' => '../admin/admin_users.php',
            'priority' => 'urgent'
        ]);
    }
    $stmt->close();
}
?>