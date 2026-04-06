<?php
session_start();
include("../config.php");

// ✅ Require login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../loginreg/logreg.php");
    exit;
}

$user_id    = (int)$_SESSION['user_id'];
$session_id = session_id();

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

// ✅ Initialize cart session if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if (isset($_POST['add_to_cart'])) {
    $product_id   = (int)$_POST['id'];
    $product_name = $_POST['name'];
    $price        = (float)$_POST['price'];
    $image_url    = $_POST['image_url'];
    $quantity     = max(1, (int)$_POST['quantity']);

    // ✅ FIXED: Check if product exists with 'active' status only
    $stmt_check = $con->prepare("SELECT cart_id, quantity FROM cart 
                                  WHERE user_id = ? AND product_id = ? AND status = 'active'");
    $stmt_check->bind_param("ii", $user_id, $product_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        // ✅ Product exists in active cart → update quantity
        $row = $result_check->fetch_assoc();
        $new_quantity = $row['quantity'] + $quantity;

        $stmt_update = $con->prepare("UPDATE cart 
                                       SET quantity = ?, updated_at = NOW() 
                                       WHERE cart_id = ?");
        $stmt_update->bind_param("ii", $new_quantity, $row['cart_id']);
        $stmt_update->execute();
        $db_id = $row['cart_id'];
        $stmt_update->close();
    } else {
        // ✅ Product does not exist in active cart → insert new
        $stmt_insert = $con->prepare("INSERT INTO cart 
            (user_id, product_id, quantity, price_at_time, status, created_at, updated_at) 
            VALUES (?, ?, ?, ?, 'active', NOW(), NOW())");
        $stmt_insert->bind_param(
            "iiid",
            $user_id,      // i
            $product_id,   // i
            $quantity,     // i
            $price         // d
        );
        $stmt_insert->execute();
        $db_id = $stmt_insert->insert_id;
        $stmt_insert->close();
    }
    $stmt_check->close();

    // ✅ Update PHP session cart
    $found = false;
    foreach ($_SESSION['cart'] as &$cart_item) {
        if ($cart_item['id'] == $product_id) {
            $cart_item['quantity'] += $quantity;
            $found = true;
            break;
        }
    }
    if (!$found) {
        $_SESSION['cart'][] = [
            'id'        => $product_id,
            'db_id'     => $db_id,
            'name'      => $product_name,
            'price'     => $price,
            'image_url' => $image_url,
            'quantity'  => $quantity
        ];
    }
    
    // ⭐ --- START OF CHANGE --- ⭐
    // Set a success message in the session.
    $_SESSION['message'] = "<strong>" . htmlspecialchars($product_name) . "</strong> has been added to your cart!";
    // ⭐ --- END OF CHANGE --- ⭐
}

// ✅ Redirect back
$redirect = $_POST['redirect'] ?? '../cart/cart.php';
header("Location: $redirect");
exit;
?>