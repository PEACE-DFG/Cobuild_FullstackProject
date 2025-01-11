<?php
// get_service_names.php
require_once '../../../../database/db.php';

$service_ids = json_decode($_POST['service_ids']);
$service_names = [];

$placeholders = str_repeat('?,', count($service_ids) - 1) . '?';
$query = "SELECT id, service_type FROM project_services WHERE id IN ($placeholders)";

$stmt = $conn->prepare($query);
$stmt->execute($service_ids);
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $service_names[$row['id']] = $row['service_type'];
}

echo json_encode($service_names);
?>