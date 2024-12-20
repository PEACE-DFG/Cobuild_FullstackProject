<?php
// process_investment_intention.php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dompdf\Dompdf;

include '../../../../database/db.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/SMTP.php';
require '../../../../vendor/autoload.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Verify user is an investor
$stmt = $conn->prepare("SELECT user_type, name, email FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user['user_type'] !== 'investor') {
    echo json_encode(['success' => false, 'message' => 'Only investors can make investment intentions']);
    exit;
}

// Validate input
if (!isset($_POST['project_id']) || !isset($_POST['investment_type'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$user_id = $_SESSION['user_id'];
$project_id = intval($_POST['project_id']);
$investment_type = $_POST['investment_type'];
$investment_details = $_POST['investment_details'] ?? null;
$amount = $_POST['amount'] ?? null;

$conn->begin_transaction();

try {
    // Get project and developer information
    $stmt = $conn->prepare("
        SELECT p.title as project_name, u.email as developer_email 
        FROM projects p 
        JOIN users u ON p.builder_id = u.id 
        WHERE p.id = ? AND u.user_type = 'developer'
    ");
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $project = $result->fetch_assoc();

    if (!$project) {
        throw new Exception('Project not found or the associated user is not a developer');
    }

    // Insert investment intention
    $stmt = $conn->prepare("INSERT INTO investment_intentions (
        user_id, 
        project_id, 
        investment_type, 
        investment_details, 
        amount, 
        status, 
        created_at
    ) VALUES (?, ?, ?, ?, ?, 'pending', NOW())");

    $stmt->bind_param("iissd", $user_id, $project_id, $investment_type, $investment_details, $amount);
    $stmt->execute();

    // Generate Certificate
    $certificate = generate_certificate($user['name'], $project['project_name'], $investment_details, $amount);

    // Send notification to developer and investor
    if (!empty($project['developer_email'])) {
        send_notification_email(
            $project['developer_email'], 
            'New Investment Intention', 
            "A new investment intention has been submitted for your project."
        );
    }

    send_notification_email(
        $user['email'], 
        'Certificate of Investment Intention', 
        "Thank you for your investment intention. Attached is your certificate.",
        $certificate
    );

    $conn->commit();
    echo json_encode([
        'success' => true, 
        'message' => 'Investment intention submitted successfully'
    ]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

/**
 * Generate a PDF certificate using DOMPDF
 *
 * @param string $investor_name Investor's full name
 * @param string $project_name Project name
 * @param string $details Investment details
 * @param float $amount Investment amount
 * @return string Path to the generated PDF certificate
 */
function generate_certificate($investor_name, $project_name, $details, $amount) {
    $dompdf = new Dompdf();
     // Configure DOMPDF options (ensure the image paths are handled correctly)
     $options = $dompdf->getOptions();
     $options->set('isHtml5ParserEnabled', true);  // Enable HTML5 parsing
     $options->set('isCssFloatEnabled', true);    // Enable floating styles in CSS
     $dompdf->setOptions($options);

    ob_start();
    include '../../../Certificates/certificate_of_investment.php'; // Adjust path as needed
    $certificate_html = ob_get_clean();

    $certificate_html = str_replace(
        ['{{INVESTOR_NAME}}', '{{PROJECT_NAME}}', '{{INVESTMENT_DETAILS}}', '{{AMOUNT}}'],
        [$investor_name, $project_name, $details, number_format($amount, 2)],
        $certificate_html
    );

    $dompdf->loadHtml($certificate_html);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();

    $output_path = "../../../../certificates/investment_certificate_" . time() . ".pdf";
    file_put_contents($output_path, $dompdf->output());

    return $output_path;
}

/**
 * Send an email using PHPMailer
 *
 * @param string $to Recipient's email address
 * @param string $subject Email subject
 * @param string $message Email body content
 * @param string|null $attachment Path to attachment file
 * @throws Exception If email sending fails
 */
function send_notification_email($to, $subject, $message, $attachment = null) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'Officialcobuild@gmail.com';
        $mail->Password   = 'udodjurhumdfrsim';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('Officialcobuild@gmail.com', 'Cobuild');
        $mail->addAddress($to);

        // Attachments
        if ($attachment) {
            $mail->addAttachment($attachment);
        }

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;

        $mail->send();
    } catch (Exception $e) {
        throw new Exception("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
    }
}
?>
