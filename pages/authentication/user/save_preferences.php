<?php
session_start();
require_once '../../../database/db.php';
require '../../../vendor/autoload.php'; // For PHPMailer

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit(json_encode(['success' => false, 'message' => 'Not authenticated']));
}

$user_id = $_SESSION['user_id'];
$selected_categories = $_POST['project_categories'] ?? [];

try {
    // Start transaction
    $conn->begin_transaction();

    // Get user's email
    $stmt = $conn->prepare("
        SELECT email 
        FROM users 
        WHERE id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $user_email = $user['email'];

    // First, deactivate all preferences for this user
    $stmt = $conn->prepare("
        UPDATE investor_notification_preferences 
        SET is_active = 0 
        WHERE investor_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    // Then activate selected preferences with email
    if (!empty($selected_categories)) {
        $stmt = $conn->prepare("
            INSERT INTO investor_notification_preferences 
                (investor_id, investor_email, category_id, is_active) 
            VALUES (?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE 
                is_active = 1,
                investor_email = VALUES(investor_email)
        ");

        foreach ($selected_categories as $category_id) {
            $stmt->bind_param("isi", $user_id, $user_email, $category_id);
            $stmt->execute();
        }
    }

    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}