<?php
session_start();
include("../config.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../loginreg/logreg.php");
    exit;
}

if (isset($_GET['cart_id'])) {
    $cart_id = intval($_GET['cart_id']);

    $stmt = $con->prepare("DELETE FROM cart WHERE cart_id = ?");
    $stmt->bind_param("i", $cart_id);
    $stmt->execute();
    $stmt->close();
}

header("Location: cart.php");
exit;
?>
