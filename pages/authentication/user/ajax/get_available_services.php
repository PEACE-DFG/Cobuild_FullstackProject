<?php
include '../../../../database/db.php';
session_start();

if (!isset($_POST['project_id'])) {
    echo json_encode([]);
    exit;
}

$project_id = $_POST['project_id'];

$stmt = $conn->prepare("SELECT 
    id,
    service_type,
    total_hours
    FROM project_services 
    WHERE project_id = ?");
$stmt->bind_param("i", $project_id);
$stmt->execute();
$result = $stmt->get_result();
$services = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode($services);
?>