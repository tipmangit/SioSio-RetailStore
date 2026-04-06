<?php
/**
 * Mark Notification as Read - User Version
 * Place this file in: mainfolder/user/mark_notification_read.php
 */
session_start();
header('Content-Type: application/json');

require_once(__DIR__ . '/../config.php');
require_once(__DIR__ . '/../notification_functions.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$notification_id = (int)($input['notification_id'] ?? 0);

if ($notification_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid notification ID']);
    exit;
}

// Verify notification belongs to user
$stmt = $con->prepare("SELECT notification_id FROM notifications WHERE notification_id = ? AND user_id = ? AND recipient_type = 'user'");
$stmt->bind_param("ii", $notification_id, $user_id);
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