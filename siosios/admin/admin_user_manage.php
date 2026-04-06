<?php
// admin_user_manage.php - Handles adding, updating, and deleting users
session_start();
include("../config.php");
include("admin_session_check.php"); 
require_once('../notification_functions.php');
require_once('audit_trail_functions.php');

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

// --- Handler for ADDING a new user ---
if (isset($_POST['add_user'])) {
    
    $fname = trim($_POST['fname']);
    $mname = trim($_POST['mname'] ?? '');
    $lname = trim($_POST['lname']);
    $name = preg_replace('/\s+/', ' ', trim("$fname $mname $lname"));
    
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $contact_num = trim($_POST['contact_num'] ?? '');
    $delivery_address = trim($_POST['delivery_address'] ?? '');
    
    if (empty($fname) || empty($lname) || empty($username) || empty($email) || empty($password)) {
        $_SESSION['message'] = "All required fields (* First Name, Last Name, Username, Email, Password) must be filled!";
        $_SESSION['message_type'] = "danger";
    } else {
        $stmt_check = $con->prepare("SELECT user_id FROM userss WHERE username = ? OR email = ?");
        $stmt_check->bind_param("ss", $username, $email);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows > 0) {
            $_SESSION['message'] = "Username or email already exists!";
            $_SESSION['message_type'] = "danger";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt_insert = $con->prepare("INSERT INTO userss (name, username, email, password, contact_num, delivery_address, created_at) 
                                   VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt_insert->bind_param("ssssss", $name, $username, $email, $hashed_password, $contact_num, $delivery_address);
            
            
            if ($stmt_insert->execute()) {
                $new_user_id = $con->insert_id;
                
                $_SESSION['message'] = "User added successfully!";
                $_SESSION['message_type'] = "success";

                logAuditTrail(
                $con,
                $_SESSION['admin_id'],
                $_SESSION['admin_name'],
                $_SESSION['admin_role'],
                'user_add',
                "Added new user '{$username}' (ID: {$new_user_id})",
                'userss',
                $new_user_id,
                null,
                ['name' => $name, 'username' => $username, 'email' => $email]
            );
                            
                // Notify super admins
                $notif_stmt = $con->prepare("SELECT id FROM admins WHERE role = 'super_admin'");
                $notif_stmt->execute();
                $notif_result = $notif_stmt->get_result();
                while ($admin = $notif_result->fetch_assoc()) {
                    createNotification($con, [
                        'admin_id' => $admin['id'], 'recipient_type' => 'admin', 'type' => 'new_user',
                        'title' => 'New User Registered', 'message' => "A new user '{$username}' was added by {$_SESSION['admin_name']}.",
                        'action_url' => 'admin_users.php'
                    ]);
                }
                $notif_stmt->close();
            } else {
                $_SESSION['message'] = "Failed to add user: " . $stmt_insert->error;
                $_SESSION['message_type'] = "danger";
            }
            $stmt_insert->close();
        }
        $stmt_check->close();
    }
}

// --- Handler for UPDATING a user ---
if (isset($_POST['update_user'])) {
    $user_id = (int)$_POST['user_id'];
    
    $fname = trim($_POST['fname']);
    $mname = trim($_POST['mname'] ?? '');
    $lname = trim($_POST['lname']);
    $name = preg_replace('/\s+/', ' ', trim("$fname $mname $lname"));

    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $contact_num = trim($_POST['contact_num'] ?? '');
    $delivery_address = trim($_POST['delivery_address'] ?? '');
    $password = $_POST['password']; // Optional new password

    if (empty($fname) || empty($lname) || empty($username) || empty($email) || empty($user_id)) {
        $_SESSION['message'] = "All required fields (* First Name, Last Name, Username, Email) must be filled!";
        $_SESSION['message_type'] = "danger";
    } else {
        // Check if username or email is taken by *another* user
        $stmt_check = $con->prepare("SELECT user_id FROM userss WHERE (username = ? OR email = ?) AND user_id != ?");
        $stmt_check->bind_param("ssi", $username, $email, $user_id);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows > 0) {
            $_SESSION['message'] = "Username or email is already taken by another user!";
            $_SESSION['message_type'] = "danger";
        } else {
            // Check if password needs to be updated
            if (!empty($password)) {
                // Update with new password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt_update = $con->prepare("UPDATE userss SET name = ?, username = ?, email = ?, password = ?, contact_num = ?, delivery_address = ? WHERE user_id = ?");
                $stmt_update->bind_param("ssssssi", $name, $username, $email, $hashed_password, $contact_num, $delivery_address, $user_id);
            } else {
                // Update without changing password
                $stmt_update = $con->prepare("UPDATE userss SET name = ?, username = ?, email = ?, contact_num = ?, delivery_address = ? WHERE user_id = ?");
                $stmt_update->bind_param("sssssi", $name, $username, $email, $contact_num, $delivery_address, $user_id);
            }

            if ($stmt_update->execute()) {
                $_SESSION['message'] = "User updated successfully!";
                $_SESSION['message_type'] = "success";
                logAuditTrail(
                $con,
                $_SESSION['admin_id'],
                $_SESSION['admin_name'],
                $_SESSION['admin_role'],
                'user_update',
                "Updated user '{$username}' (ID: {$user_id})",
                'userss',
                $user_id
            );

            } else {
                $_SESSION['message'] = "Failed to update user: " . $stmt_update->error;
                $_SESSION['message_type'] = "danger";
            }
            $stmt_update->close();
        }
        $stmt_check->close();
    }
}

//  Handler for DELETING a user ---
if (isset($_POST['delete_user'])) {
    $user_id = (int)$_POST['user_id'];

    if (empty($user_id)) {
        $_SESSION['message'] = "Invalid user ID.";
        $_SESSION['message_type'] = "danger";
    } else {
        // Soft delete: anonymize user data instead of deleting
        $anonymized_name = "Deleted User #" . $user_id;
        $anonymized_email = "deleted" . $user_id . "@deleted.com";
        $anonymized_username = "deleted_" . $user_id;
        
        $stmt = $con->prepare("UPDATE userss SET 
            name = ?, 
            email = ?, 
            username = ?, 
            password = '', 
            status = '', 
            contact_num = '', 
            delivery_address = '' 
            WHERE user_id = ?");
        $stmt->bind_param("sssi", $anonymized_name, $anonymized_email, $anonymized_username, $user_id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "User anonymized successfully!";
            $_SESSION['message_type'] = "success";
            
            logAuditTrail(
                $con,
                $_SESSION['admin_id'],
                $_SESSION['admin_name'],
                $_SESSION['admin_role'],
                'user_soft_delete',
                "Soft-deleted and anonymized user ID #{$user_id}",
                'userss',
                $user_id
            );
        } else {
            $_SESSION['message'] = "Failed to delete user.";
            $_SESSION['message_type'] = "danger";
        }
        $stmt->close();
    }
}

// Redirect back to the users tab
$_SESSION['active_tab'] = 'users';
header("Location: admin_users.php");
exit;
?>