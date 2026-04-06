<?php
include("../config.php");

header('Content-Type: application/json'); // Set header early

// Check if product_id is provided and is numeric
if (!isset($_GET['product_id']) || !is_numeric($_GET['product_id'])) {
    echo json_encode(['error' => 'Invalid or missing product ID.']);
    exit;
}

$product_id = intval($_GET['product_id']);
$reviews = [];

// Use prepared statement to prevent SQL injection
$sql = "SELECT user_name, rating, comment, created_at 
        FROM reviews 
        WHERE product_id = ? 
        ORDER BY created_at DESC";

$stmt = $con->prepare($sql);

if ($stmt) {
    $stmt->bind_param("i", $product_id);
    
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $reviews[] = $row;
        }
    } else {
        // Log error, but return empty array or generic error to user
        error_log("SQL Execute Error: " . $stmt->error);
        echo json_encode(['error' => 'Could not fetch reviews.']);
        $stmt->close();
        $con->close();
        exit;
    }
    $stmt->close();

} else {
    // Log error, but return empty array or generic error to user
    error_log("SQL Prepare Error: " . $con->error);
    echo json_encode(['error' => 'Could not prepare review query.']);
    $con->close();
    exit;
}

$con->close();

// Output the reviews as JSON
echo json_encode($reviews);
?>