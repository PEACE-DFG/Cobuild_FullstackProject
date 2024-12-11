<?php
include '../../../../database/db.php'; // Your database connection file

header('Content-Type: application/json');

if (isset($_POST['project_id'])) {
    $project_id = intval($_POST['project_id']);
    
    $stmt = $conn->prepare("SELECT 
        id,
        project_id,
        material_name,
        material_category,
        quantity,
        unit
        FROM project_materials
        WHERE project_id = ?");
    
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $materials = $result->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode($materials);
    exit;
} else {
    echo json_encode(['error' => 'No project ID provided']);
    exit;
}