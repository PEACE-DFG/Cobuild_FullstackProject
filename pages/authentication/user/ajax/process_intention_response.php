
<?php
// process_intention_response.php
include '../../../../database/db.php';
session_start();

if (!isset($_SESSION['user_id']) || !isset($_POST['intention_id']) || !isset($_POST['response'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

// Verify user is a developer
$stmt = $conn->prepare("SELECT user_type FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user['user_type'] !== 'developer') {
    echo json_encode(['success' => false, 'message' => 'Only developers can respond to investment intentions']);
    exit;
}

$intention_id = intval($_POST['intention_id']);
$response = $_POST['response']; // 'accepted' or 'rejected'
$developer_id = $_SESSION['user_id'];

$conn->begin_transaction();

try {
    // Verify developer owns the project
    $stmt = $conn->prepare("SELECT i.*, p.id, u.email as investor_email 
        FROM investment_intentions i
        JOIN projects p ON i.project_id = p.id
        JOIN users u ON i.user_id = u.id
        WHERE i.id = ? AND p.id = ?");
    $stmt->bind_param("ii", $intention_id, $developer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $intention = $result->fetch_assoc();
    
    if (!$intention) {
        throw new Exception('Invalid intention or unauthorized');
    }
    
    // Update intention status
    $stmt = $conn->prepare("UPDATE investment_intentions SET status = ?, responded_at = NOW() WHERE id = ?");
    $stmt->bind_param("si", $response, $intention_id);
    $stmt->execute();
    
    if ($response === 'accepted') {
        // Generate timesheet if skills/services investment
        if (in_array($intention['investment_type'], ['skill', 'service'])) {
            create_timesheet($intention);
        }
        
        // Send acceptance email with details
        send_investor_acceptance_email($intention['investor_email'], $intention);
    } else {
        // Send rejection notification
        send_investor_rejection_email($intention['investor_email'], $intention);
    }
    
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Response processed successfully']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function create_timesheet($intention) {
    global $conn;
    
    $stmt = $conn->prepare("INSERT INTO timesheets (
        intention_id,
        user_id,
        project_id,
        investment_type,
        status,
        created_at
    ) VALUES (?, ?, ?, ?, 'pending', NOW())");
    
    $stmt->bind_param("iiis", 
        $intention['id'],
        $intention['user_id'],
        $intention['project_id'],
        $intention['investment_type']
    );
    $stmt->execute();
}
?>