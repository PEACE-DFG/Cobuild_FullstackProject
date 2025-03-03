<?php
include '../../../../database/db.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/SMTP.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$investment_id = intval($data['id']);

$conn->begin_transaction();

try {
    // Get investment details
    $stmt = $conn->prepare("SELECT user_id, amount, project_id FROM investment_intentions WHERE id = ?");
    $stmt->bind_param("i", $investment_id);
    $stmt->execute();
    $stmt->bind_result($user_id, $amount, $project_id);
    $stmt->fetch();
    $stmt->close();

    // Update investment intention status to approved
    $stmt = $conn->prepare("UPDATE investment_intentions SET status = 'accepted' WHERE id = ?");
    $stmt->bind_param("i", $investment_id);
    $stmt->execute();

    // Deduct the investment amount from the project's investment goal
    $update_stmt = $conn->prepare("UPDATE projects SET investment_goal = investment_goal - ? WHERE id = ?");
    $update_stmt->bind_param("di", $amount, $project_id);
    $update_stmt->execute();

    // Get investor email
    $stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($investor_email);
    $stmt->fetch();
    $stmt->close();

    // Send approval notification email
    $mail = new PHPMailer(true);
    
    // Email settings
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'Officialcobuild@gmail.com';
    $mail->Password   = 'udodjurhumdfrsim'; // Use environment variables for sensitive data
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    // Recipients
    $mail->setFrom('Officialcobuild@gmail.com', 'Cobuild');
    $mail->addAddress($investor_email);

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Investment Intention Approved';
    $mail->Body    = "Your investment intention of NGN " . number_format($amount, 2) . " has been approved.";

    $mail->send();

    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
