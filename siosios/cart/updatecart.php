<?php
session_start();
include("../config.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../loginreg/logreg.php");
    exit;
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

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cart_id'])) {
    $cart_id = (int)$_POST['cart_id'];
    $user_id = (int)$_SESSION['user_id'];

    // Verify cart item belongs to user
    $stmt_verify = $con->prepare("SELECT quantity FROM cart WHERE cart_id = ? AND user_id = ?");
    $stmt_verify->bind_param("ii", $cart_id, $user_id);
    $stmt_verify->execute();
    $result = $stmt_verify->get_result();
    
    if ($result->num_rows === 0) {
        $_SESSION['error'] = "Cart item not found.";
        header("Location: cart.php");
        exit;
    }
    
    $current = $result->fetch_assoc();
    $current_qty = $current['quantity'];
    $stmt_verify->close();

    // Handle increase/decrease
    if (isset($_POST['increase'])) {
        $new_qty = $current_qty + 1;
    } elseif (isset($_POST['decrease'])) {
        $new_qty = max(1, $current_qty - 1); // Minimum quantity is 1
    } else {
        $new_qty = max(1, (int)$_POST['quantity']);
    }

    // Update quantity in database
    $stmt_update = $con->prepare("UPDATE cart SET quantity = ?, updated_at = NOW() WHERE cart_id = ? AND user_id = ?");
    $stmt_update->bind_param("iii", $new_qty, $cart_id, $user_id);
    
    if ($stmt_update->execute()) {
        $_SESSION['success'] = "Cart updated successfully.";
    } else {
        $_SESSION['error'] = "Failed to update cart.";
    }
    $stmt_update->close();
}

header("Location: cart.php");
exit;
?>