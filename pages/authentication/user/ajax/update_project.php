<?php
require_once '../../../../database/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $project_id = $_POST['project_id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $location = $_POST['location'];
    $investment_goal = $_POST['investment_goal'];
    $project_category = $_POST['project_category'];
    $total_project_cost = $_POST['total_project_cost'];
    $projected_revenue = $_POST['projected_revenue'];
    $projected_profit = $_POST['projected_profit'];
    $developer_info = $_POST['developer_info'];
    $user_id = $_SESSION['user_id'];

    // Convert investment types to a JSON format if stored as JSON
    $investment_types = isset($_POST['investment_types']) ? json_encode($_POST['investment_types']) : json_encode([]);

    // Update the project details
    $stmt = $conn->prepare("
        UPDATE projects 
        SET title = ?, description = ?, location = ?, investment_goal = ?, project_category = ?, 
            total_project_cost = ?, projected_revenue = ?, projected_profit = ?, developer_info = ?, 
            investment_types = ? 
        WHERE id = ? AND builder_id = ?
    ");
    
    $stmt->bind_param(
        "sssissssssi", 
        $title, 
        $description, 
        $location, 
        $investment_goal, 
        $project_category, 
        $total_project_cost, 
        $projected_revenue, 
        $projected_profit, 
        $developer_info, 
        $investment_types,
        $project_id, 
        $user_id
    );

    if ($stmt->execute()) {
        // Clear existing materials for the project
        $delete_stmt = $conn->prepare("DELETE FROM project_materials WHERE project_id = ?");
        $delete_stmt->bind_param("i", $project_id);
        $delete_stmt->execute();
        $delete_stmt->close();

        // Insert new materials
        if (isset($_POST['materials']) && is_array($_POST['materials'])) {
            $insert_stmt = $conn->prepare("
                INSERT INTO project_materials (project_id, material_name, material_category, quantity, unit) 
                VALUES (?, ?, ?, ?, ?)
            ");

            foreach ($_POST['materials'] as $material) {
                // Decode the JSON string back to an associative array
                $materialData = json_decode($material, true);

                // Ensure all required fields are present
                if (isset($materialData['material_name'], $materialData['material_category'], $materialData['quantity'], $materialData['unit'])) {
                    $name = $materialData['material_name'];
                    $category = $materialData['material_category'];
                    $quantity = (int)$materialData['quantity']; // Ensure quantity is an integer
                    $unit = $materialData['unit'];

                    // Bind parameters and execute
                    $insert_stmt->bind_param("issis", $project_id, $name, $category, $quantity, $unit);
                    if (!$insert_stmt->execute()) {
                        http_response_code(500);
                        echo json_encode(['success' => false, 'message' => 'Failed to insert material']);
                        exit;
                    }
                }
            }
            $insert_stmt->close();
        }

        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Update failed']);
    }
}
?>