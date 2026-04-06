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

$order_id = $_GET['order_id'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$order_id) {
    $_SESSION['error'] = "Invalid order ID.";
    header("Location: my_orders.php");
    exit;
}

// Verify order belongs to user and is cancellable
$stmt = $con->prepare("SELECT * FROM orders WHERE order_id = ? AND user_id = ?");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    $_SESSION['error'] = "Order not found.";
    header("Location: my_orders.php");
    exit;
}

// Check if order can be cancelled (only processing orders)
if ($order['order_status'] !== 'processing') {
    $_SESSION['error'] = "This order cannot be cancelled. Current status: " . ucfirst($order['order_status']);
    header("Location: order_details.php?order_id=" . $order_id);
    exit;
}

// Begin transaction
$con->begin_transaction();

try {
    // Update order status to cancelled
    $stmt = $con->prepare("UPDATE orders SET order_status = 'cancelled' WHERE order_id = ?");
    $stmt->bind_param("i", $order_id);
    if (!$stmt->execute()) {
        throw new Exception("Failed to cancel order");
    }
    $stmt->close();

    // If payment was made via Stripe, process refund
    if ($order['pay_method'] === 'stripe' && $order['pay_status'] === 'paid' && !empty($order['stripe_payment_id'])) {
        require_once('../vendor/autoload.php');
        \Stripe\Stripe::setApiKey('sk_test_51SDmtZ8CuwBmuHazluCT9JY8B8dJqHYDx7nM3dvnHAErAHhy58AUfW0nzomE9jFYJVMkMd7uuswFiGEztVERM80I00DRWjv9EO');
        
        try {
            // Create refund
            $refund = \Stripe\Refund::create([
                'charge' => $order['stripe_payment_id'],
                'reason' => 'requested_by_customer'
            ]);
            
            // Update payment status
            $stmt = $con->prepare("UPDATE orders SET pay_status = 'refunded' WHERE order_id = ?");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            $stmt->close();
        } catch (Exception $e) {
            // Log refund error but don't fail the cancellation
            error_log("Refund failed for order " . $order_id . ": " . $e->getMessage());
        }
    }

    // Return items to cart (optional - uncomment if you want this feature)
    /*
    $stmt_items = $con->prepare("SELECT product_id, quantity, price_at_time 
                                 FROM order_items WHERE order_id = ?");
    $stmt_items->bind_param("i", $order_id);
    $stmt_items->execute();
    $items = $stmt_items->get_result();
    
    $stmt_cart = $con->prepare("INSERT INTO cart (user_id, product_id, quantity, price_at_time, status, created_at, updated_at)
                                VALUES (?, ?, ?, ?, 'active', NOW(), NOW())");
    
    while ($item = $items->fetch_assoc()) {
        $stmt_cart->bind_param("iiid", 
            $user_id, 
            $item['product_id'], 
            $item['quantity'], 
            $item['price_at_time']
        );
        $stmt_cart->execute();
    }
    $stmt_items->close();
    $stmt_cart->close();
    */

    // Create notification
    $stmt_notif = $con->prepare("INSERT INTO notifications 
        (user_id, order_id, type, title, message, created_at)
        VALUES (?, ?, 'order_cancelled', 'Order Cancelled', ?, NOW())");
    
    $message = "Your order #" . $order_id . " has been cancelled successfully.";
    $stmt_notif->bind_param("iis", $user_id, $order_id, $message);
    $stmt_notif->execute();
    $stmt_notif->close();

    // Commit transaction
    $con->commit();

    $_SESSION['success'] = "Order #" . $order_id . " has been cancelled successfully.";
    header("Location: my_orders.php");
    exit;

} catch (Exception $e) {
    // Rollback on error
    $con->rollback();
    $_SESSION['error'] = "Failed to cancel order: " . $e->getMessage();
    header("Location: order_details.php?order_id=" . $order_id);
    exit;
}
?>