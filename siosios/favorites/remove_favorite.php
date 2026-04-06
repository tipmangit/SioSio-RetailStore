<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("../config.php");

// ✅ Use actual user_id
if (!isset($_SESSION['user_id'])) {
    header("Location: ../loginreg/logreg.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $favorite_id = (int)$_POST['favorite_id'];

    $stmt = $con->prepare("DELETE FROM favorites WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $favorite_id, $user_id);
    $stmt->execute();
    $stmt->close();
}

header("Location: favorites.php");
exit;
?>
