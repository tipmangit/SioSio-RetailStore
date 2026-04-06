<?php
session_start();
require_once('../config.php');
require_once('../notification_functions.php');
require_once('admin_session_check.php');
require_once('audit_trail_functions.php');

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['count' => 0]);
    exit;
}

$count = getUnreadCount($con, $_SESSION['admin_id'], 'admin');
echo json_encode(['count' => $count]);
?>