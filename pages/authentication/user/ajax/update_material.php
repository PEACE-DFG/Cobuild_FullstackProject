<?php
// Include database connection
include '../../../../database/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $materialId = $_POST['id'];
    $materialName = $_POST['material_name'];
    $materialCategory = $_POST['material_category'];
    $quantity = $_POST['quantity'];
    $unit = $_POST['unit'];

    if (empty($materialId) || empty($materialName) || empty($materialCategory) || empty($quantity) || empty($unit)) {
        echo json_encode(['success' => false, 'error' => 'All fields are required']);
        exit;
    }

    // Prepare the SQL statement to update the material
    $stmt = $conn->prepare("UPDATE project_materials SET material_name = ?, material_category = ?, quantity = ?, unit = ? WHERE id = ?");
    $stmt->bind_param("ssisi", $materialName, $materialCategory, $quantity, $unit, $materialId);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'material' => [
            'id' => $materialId,
            'material_name' => $materialName,
            'material_category' => $materialCategory,
            'quantity' => $quantity,
            'unit' => $unit
        ]]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update material']);
    }

    $stmt->close();
}
$conn->close();
?>