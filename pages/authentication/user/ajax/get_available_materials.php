<?php
include '../../../../database/db.php';
session_start();

if (!isset($_POST['project_id'])) {
    echo json_encode([]);
    exit;
}

$project_id = $_POST['project_id'];

// Prepare the SQL statement to fetch materials
$stmt = $conn->prepare("SELECT 
    id,
    material_name,
    quantity,
    unit
    FROM project_materials 
    WHERE project_id = ?");
$stmt->bind_param("i", $project_id);
$stmt->execute();
$result = $stmt->get_result();
$materials = $result->fetch_all(MYSQLI_ASSOC);

// Return the materials as a JSON response
echo json_encode($materials);
?>
