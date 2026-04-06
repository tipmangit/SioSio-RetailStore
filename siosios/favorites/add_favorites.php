<?php
session_start();
include("../config.php");

// Check if user is logged in
if (!isset($_SESSION['valid']) || !isset($_SESSION['user_id'])) {
    // Redirect guests to login
    $_SESSION['message'] = "Please log in to add items to favorites."; // Add message
    header("Location: ../loginreg/logreg.php"); // Adjust path if needed
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// Get data from POST, use null coalescing operator for safety
$product_name  = $_POST['product_name'] ?? null;
$product_price = $_POST['product_price'] ?? null; // Keep as string or float depending on DB
$product_image = $_POST['product_image'] ?? null;
$redirect_url  = $_POST['redirect'] ?? 'product.php'; // Get redirect URL or fallback

// Validate required data
if ($product_name && $product_price !== null && $product_image) {

    // Check if already favorited using prepared statement
    $stmt_check = $con->prepare("SELECT id FROM favorites WHERE user_id = ? AND product_name = ?");
    if (!$stmt_check) {
        // Log error for admin, show generic message to user
        error_log("SQL Prepare Error (Check Fav): " . $con->error);
        $_SESSION['message'] = "Error: Could not check favorites.";
        header("Location: " . $redirect_url);
        exit;
    }

    $stmt_check->bind_param("is", $user_id, $product_name);

    if (!$stmt_check->execute()) {
        error_log("SQL Execute Error (Check Fav): " . $stmt_check->error);
        $_SESSION['message'] = "Error: Could not check favorites.";
        $stmt_check->close();
        header("Location: " . $redirect_url);
        exit;
    }

    $result = $stmt_check->get_result();

    if ($result->num_rows == 0) {
        // Not favorited, insert using prepared statement
        $stmt_insert = $con->prepare("INSERT INTO favorites (user_id, product_name, product_price, product_image, created_at) VALUES (?, ?, ?, ?, NOW())");
        if (!$stmt_insert) {
            error_log("SQL Prepare Error (Insert Fav): " . $con->error);
            $_SESSION['message'] = "Error: Could not prepare to add favorite.";
            $stmt_check->close();
            header("Location: " . $redirect_url);
            exit;
        }

        // Use 'd' for decimal/double if price column is DECIMAL, 's' if VARCHAR
        $stmt_insert->bind_param("isds", $user_id, $product_name, $product_price, $product_image); // Adjust 'd' if needed

        if ($stmt_insert->execute()) {
             $_SESSION['message'] = htmlspecialchars($product_name) . " added to favorites!";
        } else {
             error_log("SQL Execute Error (Insert Fav): " . $stmt_insert->error);
             // Check for duplicate entry error specifically (error code 1062)
             if ($stmt_insert->errno == 1062) {
                 $_SESSION['message'] = htmlspecialchars($product_name) . " is already in your favorites.";
             } else {
                 $_SESSION['message'] = "Error adding favorite.";
             }
        }
        $stmt_insert->close();

    } else {
        // Already favorited
        $_SESSION['message'] = htmlspecialchars($product_name) . " is already in your favorites.";
    }

    $stmt_check->close();

} else {
    // Missing product data
    $_SESSION['message'] = "Error: Missing product information for favorites.";
}

$con->close(); // Close connection

// Redirect back to the previous page
header("Location: " . $redirect_url);
exit;
?>