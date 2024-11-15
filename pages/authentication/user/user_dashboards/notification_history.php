<?php
session_start();
require_once '../../../database/db.php';
require_once '../notify_investors.php'; // The file with your existing notification function

// Check if user is logged in
if (!isset($_SESSION['investor_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$investor_id = $_SESSION['investor_id'];
$notifications = getNotificationHistory($investor_id);

header('Content-Type: application/json');
echo json_encode($notifications);