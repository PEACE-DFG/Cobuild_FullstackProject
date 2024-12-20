<?php
// process_cash_investment.php
include '../../../../database/db.php';
session_start();

// Ensure user is logged in and has required permissions
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Validate input
if (!isset($_POST['project_id']) || !isset($_POST['investment_amount'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$user_id = $_SESSION['user_id'];
$project_id = intval($_POST['project_id']);
$investment_amount = floatval($_POST['investment_amount']);

// Start a database transaction
$conn->begin_transaction();

try {
    // Check current project investment goal
    $stmt = $conn->prepare("SELECT investment_goal FROM projects WHERE id = ?");
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $project = $result->fetch_assoc();

    if (!$project) {
        throw new Exception('Project not found');
    }

    // Validate investment amount
    if ($investment_amount > $project['investment_goal']) {
        throw new Exception('Investment exceeds project needs');
    }

    // Record cash investment
    $stmt = $conn->prepare("INSERT INTO cash_investments (user_id, project_id, investment_amount, investment_date) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iid", $user_id, $project_id, $investment_amount);
    $stmt->execute();

    // Update project investment goal
    $stmt = $conn->prepare("UPDATE projects SET investment_goal = investment_goal - ? WHERE id = ?");
    $stmt->bind_param("di", $investment_amount, $project_id);
    $stmt->execute();

    // Commit transaction
    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'Investment successful']);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

<?php
// process_skill_investment.php
include '../config.php';
session_start();

// Ensure user is logged in and has required permissions
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Validate input
if (!isset($_POST['project_id']) || !isset($_POST['skill_ids'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$user_id = $_SESSION['user_id'];
$project_id = intval($_POST['project_id']);
$skill_ids = array_map('intval', $_POST['skill_ids']);

// Start a database transaction
$conn->begin_transaction();

try {
    // Insert skill investments
    $stmt = $conn->prepare("INSERT INTO project_skills (project_id, skill_id, user_id, added_date) VALUES (?, ?, ?, NOW())");
    
    foreach ($skill_ids as $skill_id) {
        $stmt->bind_param("iii", $project_id, $skill_id, $user_id);
        $stmt->execute();
    }

    // Commit transaction
    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'Skill investment successful']);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

<?php
// process_service_investment.php
include '../config.php';
session_start();

// Ensure user is logged in and has required permissions
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Validate input
if (!isset($_POST['project_id']) || !isset($_POST['service_ids'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$user_id = $_SESSION['user_id'];
$project_id = intval($_POST['project_id']);
$service_ids = array_map('intval', $_POST['service_ids']);

// Start a database transaction
$conn->begin_transaction();

try {
    // Insert service investments
    $stmt = $conn->prepare("INSERT INTO project_services (project_id, service_id, user_id, added_date) VALUES (?, ?, ?, NOW())");
    
    foreach ($service_ids as $service_id) {
        $stmt->bind_param("iii", $project_id, $service_id, $user_id);
        $stmt->execute();
    }

    // Commit transaction
    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'Service investment successful']);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>