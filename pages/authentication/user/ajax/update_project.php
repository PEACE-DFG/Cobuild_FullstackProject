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
    $total_project_cost = $_POST['total_project_cost'];
    $projected_revenue = $_POST['projected_revenue'];
    $projected_profit = $_POST['projected_profit'];
    $developer_info = $_POST['developer_info'];
    $user_id = $_SESSION['user_id'];

    // Convert investment types to a JSON format if stored as JSON
    $investment_types = isset($_POST['investment_types']) ? json_encode($_POST['investment_types']) : json_encode([]);

    // Update the project details
    $stmt = $conn->prepare("
        UPDATE projects 
        SET title = ?, description = ?, location = ?, investment_goal = ?, 
            total_project_cost = ?, projected_revenue = ?, projected_profit = ?, developer_info = ?, 
            investment_types = ? 
        WHERE id = ? AND builder_id = ?
    ");
    
    $stmt->bind_param(
        "sssisssssis", 
        $title, 
        $description, 
        $location, 
        $investment_goal, 
        $total_project_cost, 
        $projected_revenue, 
        $projected_profit, 
        $developer_info, 
        $investment_types,
        $project_id, 
        $user_id
    );

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Update failed']);
    }
}
?>