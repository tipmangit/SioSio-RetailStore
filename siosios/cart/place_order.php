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


$user_id = (int)$_SESSION['user_id'];

if (!isset($_POST['selected']) || empty($_POST['selected'])) {
    header("Location: cart.php");
    exit;
}

// Generate tracking number
$tracking_number = "TRK" . strtoupper(uniqid());

// Insert order
$stmt = $con->prepare("INSERT INTO orders (user_id, tracking_number, order_status, created_at) 
                       VALUES (?, ?, 'Pending', NOW())");
$stmt->bind_param("is", $user_id, $tracking_number);
$stmt->execute();
$order_id = $stmt->insert_id;
$stmt->close();

// Insert order items
$cart_ids = $_POST['selected'];
$placeholders = implode(',', array_fill(0, count($cart_ids), '?'));
$types = str_repeat('i', count($cart_ids));

$query = "SELECT c.cart_id, c.product_id, c.quantity, c.price_at_time, p.product_name
          FROM cart c
          JOIN products p ON c.product_id = p.product_id
          WHERE c.cart_id IN ($placeholders) AND c.user_id = ?";
$types .= 'i';
$params = array_merge($cart_ids, [$user_id]);

$stmt = $con->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $stmt_item = $con->prepare("INSERT INTO order_items 
        (order_id, product_id, product_name, quantity, price_at_time) 
        VALUES (?, ?, ?, ?, ?)");
    $stmt_item->bind_param(
        "iisid",
        $order_id,
        $row['product_id'],
        $row['product_name'],
        $row['quantity'],
        $row['price_at_time']
    );
    $stmt_item->execute();
    $stmt_item->close();
}
$stmt->close();

// Clear those cart items
$del_stmt = $con->prepare("DELETE FROM cart WHERE cart_id IN ($placeholders) AND user_id = ?");
$del_stmt->bind_param($types, ...$params);
$del_stmt->execute();
$del_stmt->close();

// Redirect to success page
header("Location: success.php?tracking=" . urlencode($tracking_number));
exit;
?>
