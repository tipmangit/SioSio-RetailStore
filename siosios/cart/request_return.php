<?php
session_start();
include("../config.php");

// 1. Authenticate User
if (!isset($_SESSION['user_id'])) {
    header("HTTP/1.1 403 Forbidden");
    exit('User not authenticated.');
}

if (!isset($_SESSION['user_id'])) {
    // Not logged in, redirect to login page
    header("Location: ../loginreg/logreg.php?error=notloggedin");
    exit;
}

// --- NEW SUSPENSION CHECK ---
$user_id_from_session = $_SESSION['user_id']; // Store user ID

// 2. Fetch the current user's status from the database
// Make sure $con (database connection) is available here
if (isset($con)) {
    $stmt_check_status = $con->prepare("SELECT status FROM userss WHERE user_id = ?");

    // Check if prepare() succeeded
    if ($stmt_check_status) {
        $stmt_check_status->bind_param("i", $user_id_from_session);
        $stmt_check_status->execute();
        $result_status = $stmt_check_status->get_result();

        if ($result_status->num_rows === 1) {
            $user_data = $result_status->fetch_assoc();
            
            // 3. Check if the user is suspended
            if ($user_data['status'] === 'suspended') {
                // User is suspended, log them out 
                session_unset();     // Unset $_SESSION variable
                session_destroy();   // Destroy session data
                
                // Redirect to login page with a specific error message
                header("Location: ../loginreg/logreg.php?error=suspended"); 
                exit; // Stop script execution
            }
            // If status is 'active' or something else, execution continues normally below...
        } else {
            // User ID from session not found in database (maybe deleted?) - Log them out.
            session_unset();
            session_destroy();
            header("Location: ../loginreg/logreg.php?error=notfound");
            exit;
        }
        $stmt_check_status->close();
    } else {
        // Database query failed - Log out as a safety measure
        error_log("Failed to prepare statement to check user status: " . $con->error); // Log error for admin
        session_unset();
        session_destroy();
        header("Location: ../loginreg/logreg.php?error=dberror");
        exit;
    }
} else {
    // Database connection ($con) not available - Log out as a safety measure
     error_log("Database connection variable not available for user status check."); 
     session_unset();
     session_destroy();
     header("Location: ../loginreg/logreg.php?error=dberror");
     exit;
}
// --- END OF NEW SUSPENSION CHECK ---

$user_id = (int)$_SESSION['user_id'];

// 2. Verify Request Method
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 3. Validate Input
    if (empty($_POST['order_id']) || empty($_POST['reason']) || empty($_FILES['video_proof']['name'])) {
        $_SESSION['message'] = "Please fill all required fields and upload a proof video.";
        $_SESSION['message_type'] = "danger";
        header("Location: my_orders.php");
        exit;
    }

    $order_id = (int)$_POST['order_id'];
    $reason = trim($_POST['reason']);
    $comments = isset($_POST['comments']) ? trim($_POST['comments']) : '';

    // 4. Authorize and Check Eligibility (Verify the order belongs to the user and is 'delivered')
    $stmt_verify = $con->prepare("SELECT order_id FROM orders WHERE order_id = ? AND user_id = ? AND order_status = 'delivered'");
    $stmt_verify->bind_param("ii", $order_id, $user_id);
    $stmt_verify->execute();
    $result = $stmt_verify->get_result();
    if ($result->num_rows === 0) {
        $_SESSION['message'] = "This order is not eligible for a return request.";
        $_SESSION['message_type'] = "warning";
        header("Location: my_orders.php");
        exit;
    }
    $stmt_verify->close();

    // 5. Handle Video File Upload
    $video_file = $_FILES['video_proof'];
    $target_dir = "../uploads/return_videos/"; // Ensure this directory exists and is writable
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0755, true);
    }
    
    $file_extension = strtolower(pathinfo($video_file['name'], PATHINFO_EXTENSION));
    $allowed_extensions = ['mp4', 'mov', 'avi', 'mkv', 'webm'];
    $max_file_size = 50 * 1024 * 1024; // 50MB

    if (!in_array($file_extension, $allowed_extensions)) {
        $_SESSION['message'] = "Invalid file type. Only video files are allowed.";
        $_SESSION['message_type'] = "danger";
        header("Location: my_orders.php");
        exit;
    }

    if ($video_file['size'] > $max_file_size) {
        $_SESSION['message'] = "File is too large. Maximum size is 50MB.";
        $_SESSION['message_type'] = "danger";
        header("Location: my_orders.php");
        exit;
    }

    $new_filename = "return_" . $order_id . "_" . uniqid() . "." . $file_extension;
    $target_file_path = $target_dir . $new_filename;

    if (move_uploaded_file($video_file['tmp_name'], $target_file_path)) {
        // 6. Update Database using a Transaction
        $con->begin_transaction();
        try {
            // **MODIFIED SQL QUERY** to match your new table structure
            $stmt_insert = $con->prepare(
                "INSERT INTO return_requests (order_id, user_id, reason, comments, video_proof_path) VALUES (?, ?, ?, ?, ?)"
            );
            $stmt_insert->bind_param("iisss", $order_id, $user_id, $reason, $comments, $target_file_path);
            $stmt_insert->execute();
            $stmt_insert->close();

            // This part remains the same: update the main orders table status for UI consistency
            $stmt_update = $con->prepare("UPDATE orders SET order_status = 'return_requested' WHERE order_id = ?");
            $stmt_update->bind_param("i", $order_id);
            $stmt_update->execute();
            $stmt_update->close();

            $con->commit();
            $_SESSION['message'] = "Return request submitted successfully.";
            $_SESSION['message_type'] = "success";

        } catch (mysqli_sql_exception $exception) {
            $con->rollback();
            unlink($target_file_path); // Delete the uploaded file if DB update fails
            error_log("Return request failed: " . $exception->getMessage()); // Log error for debugging
            $_SESSION['message'] = "A database error occurred. Please try again.";
            $_SESSION['message_type'] = "danger";
        }
    } else {
        $_SESSION['message'] = "There was an error uploading your video proof.";
        $_SESSION['message_type'] = "danger";
    }

    // 7. Redirect back to the orders page
    header("Location: my_orders.php");
    exit;

} else {
    // Redirect if not a POST request
    header("Location: my_orders.php");
    exit;
}
?>