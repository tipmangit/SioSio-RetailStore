<?php
/**
 * Mark Notification as Read - Admin Version
 * Place this file in: mainfolder/admin/mark_notification_read.php
 */
session_start();
header('Content-Type: application/json');

require_once(__DIR__ . '/../config.php');
require_once(__DIR__ . '/../notification_functions.php');
require_once('audit_trail_functions.php');

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$admin_id = $_SESSION['admin_id'];

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$notification_id = (int)($input['notification_id'] ?? 0);

if ($notification_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid notification ID']);
    exit;
}

// Verify notification belongs to admin
$stmt = $con->prepare("SELECT notification_id FROM notifications WHERE notification_id = ? AND admin_id = ? AND recipient_type = 'admin'");
$stmt->bind_param("ii", $notification_id, $admin_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    echo json_encode(['success' => false, 'message' => 'Notification not found']);
    exit;
}
$stmt->close();

// Mark as read
if (markAsRead($con, $notification_id)) {
    echo json_encode(['success' => true, 'message' => 'Notification marked as read']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to mark notification as read']);
}
?>