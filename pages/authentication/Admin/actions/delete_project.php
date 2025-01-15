<?php
require_once '../../../../database/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $project_id = $_POST['project_id'];
    
    try {
        // Start transaction
        $conn->begin_transaction();

        // Delete related investment intentions first
        $delete_investments = $conn->prepare("DELETE FROM investment_intentions WHERE project_id = ?");
        $delete_investments->bind_param("i", $project_id);
        $delete_investments->execute();

        // Then delete the project
        $delete_project = $conn->prepare("DELETE FROM projects WHERE id = ?");
        $delete_project->bind_param("i", $project_id);
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