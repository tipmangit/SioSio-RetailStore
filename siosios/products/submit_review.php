<?php
include("../config.php"); // Includes session_start() and $con

// 1. Check if user is logged in (Corrected logic with &&)
if (!isset($_SESSION['valid']) || !isset($_SESSION['user_id'])) { // Changed || to && (or keep || if 'valid' alone is enough)
    $_SESSION['message'] = "You must be logged in to leave a review.";
    $redirect_url = isset($_POST['redirect']) ? $_POST['redirect'] : 'product.php'; 
    header("Location: " . $redirect_url);
    exit;
}

// 2. Check if the form was submitted via POST
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_review'])) {

    // 3. Get data from the form
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $user_id = intval($_SESSION['user_id']); 
    
    // --- THIS IS THE FIX ---
    // Use the correct session variable for the username, likely 'valid' based on your login check
    $user_name = isset($_SESSION['valid']) ? $_SESSION['valid'] : 'Anonymous'; // Changed 'username' to 'valid'
    // --- END FIX ---
    
    $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
    $comment = isset($_POST['comment']) ? htmlspecialchars(trim($_POST['comment'])) : '';
    $redirect_url = isset($_POST['redirect']) ? $_POST['redirect'] : 'product.php'; 

    // 4. Validate data
    if (empty($product_id) || empty($user_id)) { 
        $_SESSION['message'] = "An error occurred submitting review data. Please try again.";
        header("Location: " . $redirect_url);
        exit;
    }
    if ($rating < 1 || $rating > 5) {
        $_SESSION['message'] = "Invalid rating. Please select a value from 1 to 5 stars.";
        header("Location: " . $redirect_url);
        exit;
    }
    if (empty($comment)) {
        $_SESSION['message'] = "Please write a comment for your review.";
        header("Location: " . $redirect_url);
        exit;
    }

    // 5. Use a Prepared Statement
    $sql = "INSERT INTO reviews (product_id, user_id, user_name, rating, comment) VALUES (?, ?, ?, ?, ?)";
    $stmt = $con->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("iisis", $product_id, $user_id, $user_name, $rating, $comment);

        // 6. Execute and give feedback
        if ($stmt->execute()) {
            $_SESSION['message'] = "Thank you! Your review has been submitted successfully.";
        } else {
            error_log("SQL Error: " . $stmt->error); 
            $_SESSION['message'] = "Error: Could not submit your review at this time."; 
        }
        $stmt->close();
    } else {
        error_log("SQL Prepare Error: " . $con->error); 
        $_SESSION['message'] = "Error: Could not prepare the review submission.";
    }

    $con->close();

    header("Location: " . $redirect_url);
    exit;

} else {
    // If not a POST request, redirect
    $redirect_url = isset($_POST['redirect']) ? $_POST['redirect'] : 'product.php'; 
    header("Location: " . $redirect_url);
    exit;
}
?>