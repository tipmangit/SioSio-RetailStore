<?php
session_start();
require_once('../config.php');
require_once('../notification_functions.php');

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$success = markAllAsRead($con, $_SESSION['user_id'], 'user');
echo json_encode(['success' => $success]);
?>