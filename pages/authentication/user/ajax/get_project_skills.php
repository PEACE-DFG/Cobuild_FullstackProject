<?php
include '../../../../database/db.php';  // Adjust path as needed
session_start();

if (!isset($_POST['project_id'])) {
    echo json_encode([]);
    exit;
}

$project_id = $_POST['project_id'];

$stmt = $conn->prepare("SELECT 
    id,
    project_id,
    skill_type,
    total_hours
    FROM project_skills
    WHERE project_id = ?");
$stmt->bind_param("i", $project_id);
$stmt->execute();
$result = $stmt->get_result();
$skills = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode($skills);
?>