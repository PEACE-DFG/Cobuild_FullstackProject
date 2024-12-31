<?php
include '../../../../database/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$investment_id = intval($data['id']);

$conn->begin_transaction();

try {
    // Update investment intention status to approved
    $stmt = $conn->prepare("UPDATE investment_intentions SET status = 'approved' WHERE id = ?");
    $stmt->bind_param("i", $investment_id);
    $stmt->execute();

    // Optionally, update the investment goal in the project table here
    // ...

    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
