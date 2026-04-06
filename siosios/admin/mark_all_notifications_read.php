<?php
session_start();
require_once('../config.php');
require_once('../notification_functions.php');
require_once('admin_session_check.php');
require_once('audit_trail_functions.php');

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$success = markAllAsRead($con, $_SESSION['admin_id'], 'admin');
echo json_encode(['success' => $success]);
?>