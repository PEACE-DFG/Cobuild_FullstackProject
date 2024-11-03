<?php
session_start();

require_once '../../../../database/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

if (isset($_GET['id'])) {
    $project_id = $_GET['id'];
    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("SELECT * FROM projects WHERE id = ? AND builder_id = ?");
    $stmt->bind_param("ii", $project_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($project = $result->fetch_assoc()) {
        echo json_encode($project);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Project not found']);
    }
}
?>