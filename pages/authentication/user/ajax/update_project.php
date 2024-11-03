<?php
require_once '../../../../database/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $project_id = $_POST['project_id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $location = $_POST['location'];
    $investment_goal = $_POST['investment_goal'];
    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("
        UPDATE projects 
        SET title = ?, description = ?, location = ?, investment_goal = ? 
        WHERE id = ? AND builder_id = ?
    ");
    
    $stmt->bind_param("sssiii", $title, $description, $location, $investment_goal, $project_id, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Update failed']);
    }
}
?>