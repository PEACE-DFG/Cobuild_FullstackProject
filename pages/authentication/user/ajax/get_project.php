<?php
session_start();

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../../../database/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

if (isset($_GET['id'])) {
    $project_id = $_GET['id'];
    error_log("Project ID received: " . $project_id); // Log the project ID
    $user_id = $_SESSION['user_id'];

    // Fetch project details
    $stmt = $conn->prepare("SELECT * FROM projects WHERE id = ? AND builder_id = ?");
    $stmt->bind_param("ii", $project_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($project = $result->fetch_assoc()) {
        // Fetch building materials associated with the project
        $materials_stmt = $conn->prepare("SELECT * FROM project_materials WHERE project_id = ?");
        $materials_stmt->bind_param("i", $project_id);
        $materials_stmt->execute();
        $materials_result = $materials_stmt->get_result();

        $building_materials = [];
        while ($material = $materials_result->fetch_assoc()) {
            $building_materials[] = $material;
        }

        // Fetch services for the project
        $service_query = $conn->prepare("
            SELECT service_type, total_hours 
            FROM project_services 
            WHERE project_id = ?
        ");
        $service_query->bind_param("i", $project_id);
        $service_query->execute();
        $services_result = $service_query->get_result();
        $project_services = [];
        while ($service = $services_result->fetch_assoc()) {
            $project_services[] = $service;
        }

        // Fetch skills for the project
        $skill_query = $conn->prepare("
            SELECT skill_type, total_hours 
            FROM project_skills 
            WHERE project_id = ?
        ");
        $skill_query->bind_param("i", $project_id);
        $skill_query->execute();
        $skills_result = $skill_query->get_result();
        $project_skills = [];
        while ($skill = $skills_result->fetch_assoc()) {
            $project_skills[] = $skill;
        }

        // Add building materials, services, and skills to the project data
        $project['building_materials'] = $building_materials;
        $project['services'] = $project_services;
        $project['skills'] = $project_skills;

        // Return the project data as JSON
        echo json_encode($project);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Project not found']);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>