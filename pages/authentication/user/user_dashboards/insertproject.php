<?php
function insertProjectMaterials($conn, $project_id, $materials) {
  // Validate project_id
  $checkProjectQuery = "SELECT id FROM projects WHERE id = ?";
  $checkStmt = $conn->prepare($checkProjectQuery);
  $checkStmt->bind_param("i", $project_id);
  $checkStmt->execute();
  $checkResult = $checkStmt->get_result();

  if ($checkResult->num_rows === 0) {
      error_log("Project ID $project_id does not exist in projects table");
      return false;
  }

  // Prepare materials insertion
  $stmt = $conn->prepare("
      INSERT INTO project_materials 
      (project_id, material_name, material_category, quantity, unit) 
      VALUES (?, ?, ?, ?, ?)
  ");

  if (!$stmt) {
      error_log("Prepare statement failed: " . $conn->error);
      return false;
  }

  $successCount = 0;
  foreach ($materials as $material) {
      // Sanitize and validate material data
      $materialName = trim($material['material_name'] ?? '');
      $materialCategory = trim($material['material_category'] ?? '');
      $quantity = floatval($material['quantity'] ?? 0);
      $unit = trim($material['unit'] ?? '');

      // Skip empty materials
      if (empty($materialName)) continue;

      $stmt->bind_param(
          "issds", 
          $project_id, 
          $materialName, 
          $materialCategory, 
          $quantity, 
          $unit
      );

      if (!$stmt->execute()) {
          error_log("Material insertion failed: " . $stmt->error);
      } else {
          $successCount++;
      }
  }

  $stmt->close();

  return $successCount > 0;
}