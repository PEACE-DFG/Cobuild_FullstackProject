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
$hours = $_POST['hours'] ?? null;
$selected_skills = $_POST['selected_skills'] ?? []; // Capture selected skills
$selected_services = $_POST['selected_services'] ?? []; // Capture selected services

// Generate certificate number (format: CB-YYYY-XXXXX)
$certificate_number = 'CB-' . date('Y') . '-' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
$created_date = date('Y-m-d H:i:s');

$conn->begin_transaction();

try {
    // Get project information including skill types and total hours
    $stmt = $conn->prepare("
        SELECT 
            p.title as project_name,
            p.description as project_description,
            u.email as developer_email
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

    // Initialize skills and services details
    $skills_details = [];
    $services_details = [];

    // Get detailed skill information for selected skills
    if (!empty($selected_skills)) {
        $skills_ids = implode(',', array_map('intval', $selected_skills));
        $stmt = $conn->prepare("
            SELECT 
                ps.skill_type, 
                ps.total_hours 
            FROM project_skills ps 
            WHERE ps.project_id = ? AND ps.id IN ($skills_ids)
        ");
        $stmt->bind_param("i", $project_id);
        $stmt->execute();
        $skills_result = $stmt->get_result();

        while ($skill = $skills_result->fetch_assoc()) {
            $skills_details[] = $skill;
        }
    }

    // Get detailed service information for selected services
    if (!empty($selected_services)) {
        $services_ids = implode(',', array_map('intval', $selected_services));
        $stmt = $conn->prepare("
            SELECT 
                pserv.service_type, 
                pserv.total_hours 
            FROM project_services pserv 
            WHERE pserv.project_id = ? AND pserv.id IN ($services_ids)
        ");
        $stmt->bind_param("i", $project_id);
        $stmt->execute();
        $services_result = $stmt->get_result();

        while ($service = $services_result->fetch_assoc()) {
            $services_details[] = $service;
        }
    }

  // Insert investment intention with certificate number
$stmt = $conn->prepare("INSERT INTO investment_intentions (
    user_id, 
    project_id, 
    investment_type, 
    investment_details, 
    amount,
    hours,
    status,
    certificate_number,
    created_at
) VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, ?)");

$stmt->bind_param("iissdiss", 
    $user_id, 
    $project_id, 
    $investment_type, 
    $investment_details, 
    $amount, 
    $hours,
    $certificate_number,
    $created_date
);
$stmt->execute();

// Update the investment goal in the project table
$update_stmt = $conn->prepare("UPDATE projects SET investment_goal = investment_goal - ? WHERE id = ?");
$update_stmt->bind_param("di", $amount, $project_id);
$update_stmt->execute();

// Prepare email content for developer
$developerEmailContent = "
<div style='font-family: Arial, sans-serif; background-color: white; color: #333; padding: 20px;'>
    <h1 style='color: #007bff; text-align: center;'>Cobuild</h1>
    <h2 style='color: #007bff;'>New Investment Intention</h2>
    <p><strong>Project Name:</strong> {$project['project_name']}</p>
    <p><strong>Investor Name:</strong> {$user['name']}</p>
    <p><strong>Investment Type:</strong> {$investment_type}</p>
    <p><strong>Investment Amount:</strong> $" . number_format($amount, 2) . "</p>
    <p><strong>Certificate Number:</strong> {$certificate_number}</p>
</div>
";

    // Generate Certificate
    $certificate_path = __DIR__ . "/../../../../certificates/investment_certificate_" . time() . ".pdf";
    
    // Create PDF using DOMPDF
    $dompdf = new Dompdf();
    $options = $dompdf->getOptions();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isCssFloatEnabled', true);
    $dompdf->setOptions($options);

    // Get certificate template
    $certificate_html = file_get_contents('../../../Certificates/certificate_of_investment.php');
    
// Replace placeholders including certificate number, date, investment amount, and investment type
$certificate_html = str_replace(
    [
        '{{INVESTOR_NAME}}', 
        '{{PROJECT_NAME}}', 
        '{{INVESTMENT_TYPE}}', // New placeholder for investment type
        '{{INVESTMENT_AMOUNT}}', 
        '{{SKILLS_SERVICES}}',
        '{{INVESTMENT_DETAILS}}', 
        '{{DATE}}',
        '{{CERTIFICATE_ID}}'
    ],
    [
        $user['name'], 
        $project['project_name'], 
        $investment_type, // Add investment type here
        number_format($amount, 2), // Format the investment amount
        implode(', ', array_map(fn($s) => $s['skill_type'], $skills_details)) . 
        (count($services_details) ? ', ' . implode(', ', array_map(fn($s) => $s['service_type'], $services_details)) : ''),
        $investment_details, 
        date('F d, Y', strtotime($created_date)),
        $certificate_number
    ],
    $certificate_html
);


    $dompdf->loadHtml($certificate_html);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();
    
    file_put_contents($certificate_path, $dompdf->output());

    // Send notification to developer
    if (!empty($project['developer_email'])) {
        send_notification_email(
            $project['developer_email'], 
            'New Investment Intention', 
            $developerEmailContent
        );
    }

    // Send certificate to investor
    send_notification_email(
        $user['email'], 
        'Certificate of Investment Intention', 
        "Thank you for your investment intention in {$project['project_name']}. Your certificate number is {$certificate_number}. Please find your certificate attached.",
        $certificate_path
    );

    $conn->commit();
    echo json_encode([
        'success' => true, 
        'message' => 'Investment intention submitted successfully',
        'certificate_number' => $certificate_number
    ]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function send_notification_email($to, $subject, $message, $attachment = null) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'Officialcobuild@gmail.com';
        $mail->Password   = 'udodjurhumdfrsim'; // Consider using environment variables for sensitive data
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
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}
?>
