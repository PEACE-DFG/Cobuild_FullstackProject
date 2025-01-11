<?php
// get_skill_names.php
require_once '../../../../database/db.php';

$skill_ids = json_decode($_POST['skill_ids']);
$skill_names = [];

$placeholders = str_repeat('?,', count($skill_ids) - 1) . '?';
$query = "SELECT id, skill_type FROM project_skills WHERE id IN ($placeholders)";

$stmt = $conn->prepare($query);
$stmt->execute($skill_ids);
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $skill_names[$row['id']] = $row['skill_type'];
}

echo json_encode($skill_names);
?>