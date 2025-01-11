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
    $stmt = $conn->prepare("SELECT user_id FROM investment_intentions WHERE id = ?");
    $stmt->bind_param("i", $investment_id);
    $stmt->execute();
    $stmt->bind_result($user_id);
    $stmt->fetch();
    $stmt->close();

    // Update investment intention status to rejected
    $stmt = $conn->prepare("UPDATE investment_intentions SET status = 'rejected' WHERE id = ?");
    $stmt->bind_param("i", $investment_id);
    $stmt->execute();

    // Get investor email
    $stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($investor_email);
    $stmt->fetch();
    $stmt->close();

    // Send rejection notification email
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
    $mail->Subject = 'Investment Intention Rejected';
    $mail->Body    = "Your investment intention has been rejected. Please contact support for more details.";

    $mail->send();

    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
