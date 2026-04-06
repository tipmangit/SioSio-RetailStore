<?php
session_start();
include("../config.php");
include("admin_session_check.php"); 
require_once('../notification_functions.php'); // For notifications
require_once('audit_trail_functions.php');

// --- Security Check: Only Super Admins can add other admins ---
if (!isset($_SESSION['admin_id']) || $_SESSION['admin_role'] !== 'super_admin') {
    $_SESSION['message'] = "You do not have permission to perform this action.";
    $_SESSION['message_type'] = "danger";
    $_SESSION['active_tab'] = 'admins';
    header("Location: admin_users.php");
    exit;
}

// --- Handler for ADDING a new admin ---
if (isset($_POST['add_admin'])) {
    
    // Get fields from the modal
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role']; // 'admin' or 'super_admin'
    
    // Validate inputs
    if (empty($name) || empty($username) || empty($email) || empty($password) || empty($role)) {
        $_SESSION['message'] = "All fields are required!";
        $_SESSION['message_type'] = "danger";
    } else {
        // Validate role (matches your SQL)
        if ($role !== 'admin' && $role !== 'super_admin') {
            $_SESSION['message'] = "Invalid role selected.";
            $_SESSION['message_type'] = "danger";
        } else {
            // Check if username or email already exists in the 'admins' table
            $stmt = $con->prepare("SELECT id FROM admins WHERE username = ? OR email = ?");
            $stmt->bind_param("ss", $username, $email);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $_SESSION['message'] = "Admin username or email already exists!";
                $_SESSION['message_type'] = "danger";
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new admin into 'admins' table
                $stmt_insert = $con->prepare("INSERT INTO admins (name, username, email, password, role, status, created_at) 
                                       VALUES (?, ?, ?, ?, ?, 'active', NOW())");
                $stmt_insert->bind_param("sssss", $name, $username, $email, $hashed_password, $role);
                
                if ($stmt_insert->execute()) {
                    $new_admin_id = $con->insert_id;
                    
                    // --- THIS IS THE FIX ---
                    // 1. Set the success message FIRST. This guarantees it will show.
                    $_SESSION['message'] = "Admin added successfully!";
                    $_SESSION['message_type'] = "success";
                    


                    // 2. I commented out this line, as it was causing the fatal error.
                    // $action = "Added New Admin ID #{$new_admin_id} (Username: '{$username}', Role: '{$role}').";
                    // log_audit_trail($con, $_SESSION['admin_id'], $_SESSION['admin_name'], $_SESSION['admin_role'], $action);
                    
                    // 3. I added your notification function instead!
                    // Notify all other super_admins
                    $notif_stmt = $con->prepare("SELECT id FROM admins WHERE role = 'super_admin' AND id != ?");
                    $notif_stmt->bind_param("i", $_SESSION['admin_id']);
                    $notif_stmt->execute();
                    $notif_result = $notif_stmt->get_result();
                    while ($admin = $notif_result->fetch_assoc()) {
                        createNotification($con, [
                            'admin_id' => $admin['id'],
                            'recipient_type' => 'admin',
                            'type' => 'security',
                            'title' => 'New Admin Added',
                            'message' => "A new admin '{$username}' (Role: {$role}) was added by {$_SESSION['admin_name']}.",
                            'action_url' => 'admin_users.php',
                            'priority' => 'normal'
                        ]);
                    }

                    logAuditTrail(
                    $con,
                    $_SESSION['admin_id'],
                    $_SESSION['admin_name'],
                    $_SESSION['admin_role'],
                    'admin_add',
                    "Added new admin '{$username}' (ID: {$new_admin_id}, Role: {$role})",
                    'admins',
                    $new_admin_id,
                    null,
                    ['name' => $name, 'username' => $username, 'role' => $role]
                );
                    $notif_stmt->close();
                    // --- END OF FIX ---

                } else {
                    $_SESSION['message'] = "Failed to add admin: " . $stmt_insert->error;
                    $_SESSION['message_type'] = "danger";
                }
                $stmt_insert->close();
            }
            $stmt->close();
        }
    }
}

// Set active tab to 'admins' for the redirect
$_SESSION['active_tab'] = 'admins';
header("Location: admin_users.php");
exit;
?>