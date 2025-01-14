<?php
require_once '../../../../database/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $project_id = $_POST['project_id'];
    $user_id = $_SESSION['user_id'];

    try {
        // Start transaction
        $conn->begin_transaction();

        // First check if project exists and belongs to the user
        $check_stmt = $conn->prepare("SELECT id FROM projects WHERE id = ? AND builder_id = ?");
        $check_stmt->bind_param("ii", $project_id, $user_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();

        if ($result->num_rows === 0) {
            throw new Exception('Project not found or unauthorized');
        }

        // Delete related investment intentions first
        $delete_investments = $conn->prepare("DELETE FROM investment_intentions WHERE project_id = ?");
        $delete_investments->bind_param("i", $project_id);
        $delete_investments->execute();

        // Then delete the project
        $delete_project = $conn->prepare("DELETE FROM projects WHERE id = ? AND builder_id = ?");
        $delete_project->bind_param("ii", $project_id, $user_id);
        $delete_project->execute();

        // Commit the transaction
        $conn->commit();
        
        echo json_encode(['success' => true]);

    } catch (Exception $e) {
        // Rollback the transaction on error
        $conn->rollback();
        
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'Deletion failed: ' . $e->getMessage()
        ]);
    }
}
?>