<?php
// verify_project.php
header('Content-Type: application/json');

// Prevent any unwanted output
ob_start();

require_once '../../../../database/db.php';
require_once 'paystack_config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    // Verify user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not authenticated');
    }

    // Get POST data
    $project_id = $_POST['project_id'] ?? null;
    if (!$project_id) {
        throw new Exception('Project ID is required');
    }

    // Verify user owns the project
    $stmt = $conn->prepare("SELECT * FROM projects WHERE id = ? AND builder_id = ?");
    $stmt->bind_param("ii", $project_id, $_SESSION['user_id']);
    $stmt->execute();
    $project = $stmt->get_result()->fetch_assoc();
    
    if (!$project) {
        throw new Exception('Project not found or unauthorized');
    }

    // Initialize Paystack payment
    $response = initializePaystackPayment($_SESSION['user_email'], $project_id);
    
    if (!$response['status']) {
        throw new Exception('Failed to initialize payment: ' . ($response['message'] ?? 'Unknown error'));
    }

    // Clear any buffered output
    ob_clean();
    
    // Return success response
    echo json_encode([
        'status' => true,
        'message' => 'Payment initialized successfully',
        'data' => [
            'authorization_url' => $response['data']['authorization_url'],
            'reference' => $response['data']['reference']
        ]
    ]);

} catch (Exception $e) {
    // Clear any buffered output
    ob_clean();
    
    // Return error response
    echo json_encode([
        'status' => false,
        'message' => $e->getMessage()
    ]);
}

// End output buffering and send response
ob_end_flush();