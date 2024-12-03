<?php
// Include database connection
include '../../../../database/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $materialId = $_POST['id'];

    if (empty($materialId)) {
        echo json_encode(['success' => false, 'error' => 'Material ID is required']);
        exit;
    }

    // Prepare the SQL statement to delete the material
    $stmt = $conn->prepare("DELETE FROM project_materials WHERE id = ?");
    $stmt->bind_param("i", $materialId);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to delete material']);
    }

    $stmt->close();
}
$conn->close();
?>