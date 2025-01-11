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
$selected_skills = isset($_POST['selected_skills']) ? $_POST['selected_skills'] : [];
$selected_services = isset($_POST['selected_services']) ? $_POST['selected_services'] : [];

// Initialize arrays to store names
$skill_names = [];
$service_names = [];

// Generate certificate number (format: CB-YYYY-XXXXX)
$certificate_number = 'CB-' . date('Y') . '-' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
$created_date = date('Y-m-d H:i:s');

$conn->begin_transaction();

try {
    // Get project information
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

  // Modify the skill names retrieval section to include hours
if (!empty($selected_skills)) {
    $skills_ids = implode(',', array_map('intval', $selected_skills));
    $stmt = $conn->prepare("
        SELECT 
            ps.id,
            ps.skill_type,
            ps.total_hours
        FROM project_skills ps 
        WHERE ps.project_id = ? AND ps.id IN ($skills_ids)
    ");
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $skills_result = $stmt->get_result();
    while ($row = $skills_result->fetch_assoc()) {
        $skill_names[$row['id']] = [
            'name' => $row['skill_type'],
            'hours' => $row['total_hours']
        ];
    }
}

// Modify the service names retrieval section to include hours
if (!empty($selected_services)) {
    $services_ids = implode(',', array_map('intval', $selected_services));
    $stmt = $conn->prepare("
        SELECT 
            ps.id,
            ps.service_type,
            ps.total_hours
        FROM project_services ps 
        WHERE ps.project_id = ? AND ps.id IN ($services_ids)
    ");
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $services_result = $stmt->get_result();
    while ($row = $services_result->fetch_assoc()) {
        $service_names[$row['id']] = [
            'name' => $row['service_type'],
            'hours' => $row['total_hours']
        ];
    }
}

// Modify investment_details to include names and hours
if ($investment_type === 'skill' && !empty($skill_names)) {
    $investment_details_array = json_decode($investment_details, true);
    $named_details = array_map(function($id) use ($skill_names) {
        return [
            'id' => $id,
            'name' => $skill_names[$id]['name'] ?? 'Unknown Skill',
            'hours' => $skill_names[$id]['hours'] ?? 0
        ];
    }, $investment_details_array);
    $investment_details = json_encode($named_details);

    // Calculate total hours for skills
    $total_skill_hours = array_sum(array_map(function($skill) {
        return $skill['hours'];
    }, $skill_names));
} elseif ($investment_type === 'service' && !empty($service_names)) {
    $investment_details_array = json_decode($investment_details, true);
    $named_details = array_map(function($id) use ($service_names) {
        return [
            'id' => $id,
            'name' => $service_names[$id]['name'] ?? 'Unknown Service',
            'hours' => $service_names[$id]['hours'] ?? 0
        ];
    }, $investment_details_array);
    $investment_details = json_encode($named_details);

    // Calculate total hours for services
    $total_service_hours = array_sum(array_map(function($service) {
        return $service['hours'];
    }, $service_names));
}
    // Insert investment intention
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

  // Modify the developer email content to include hours
$developerEmailContent = "
<div style='font-family: Arial, sans-serif; background-color: white; color: #333; padding: 20px;'>
    <h1 style='color: #007bff; text-align: center;'>Cobuild</h1>
    <h2 style='color: #007bff;'>New Investment Intention</h2>
    <p><strong>Project Name:</strong> {$project['project_name']}</p>
    <p><strong>Investor Name:</strong> {$user['name']}</p>
    <p><strong>Investment Type:</strong> {$investment_type}</p>";

if ($investment_type === 'skill' && !empty($skill_names)) {
    $developerEmailContent .= "<p><strong>Selected Skills:</strong></p><ul>";
    foreach ($skill_names as $id => $skill) {
        $developerEmailContent .= "<li>{$skill['name']} ({$skill['hours']} hours)</li>";
    }
    $developerEmailContent .= "</ul>";
    $developerEmailContent .= "<p><strong>Total Hours:</strong> {$total_skill_hours}</p>";
} elseif ($investment_type === 'service' && !empty($service_names)) {
    $developerEmailContent .= "<p><strong>Selected Services:</strong></p><ul>";
    foreach ($service_names as $id => $service) {
        $developerEmailContent .= "<li>{$service['name']} ({$service['hours']} hours)</li>";
    }
    $developerEmailContent .= "</ul>";
    $developerEmailContent .= "<p><strong>Total Hours:</strong> {$total_service_hours}</p>";
}

$developerEmailContent .= "
    <p><strong>Investment Amount:</strong> NGN" . number_format($amount, 2) . "</p>
    <p><strong>Certificate Number:</strong> {$certificate_number}</p>
</div>";

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

   // Prepare skills/services string for certificate with hours
$investment_items = [];
if (!empty($skill_names)) {
    foreach ($skill_names as $skill) {
        $investment_items[] = "{$skill['name']} ({$skill['hours']} hours)";
    }
}
if (!empty($service_names)) {
    foreach ($service_names as $service) {
        $investment_items[] = "{$service['name']} ({$service['hours']} hours)";
    }
}
$skills_services_string = implode(', ', $investment_items);

// Prepare the detailed items HTML
$detailed_items_html = '';
$total_hours = 0;

if ($investment_type === 'skill' && !empty($skill_names)) {
    $investment_type_label = 'Skills';
    foreach ($skill_names as $skill) {
        $detailed_items_html .= "
        <div class='detail-grid-item'>
            <div class='item-name'>{$skill['name']}</div>
            <div class='item-hours'>{$skill['hours']} Hours</div>
        </div>";
        $total_hours += $skill['hours'];
    }
} elseif ($investment_type === 'service' && !empty($service_names)) {
    $investment_type_label = 'Services';
    foreach ($service_names as $service) {
        $detailed_items_html .= "
        <div class='detail-grid-item'>
            <div class='item-name'>{$service['name']}</div>
            <div class='item-hours'>{$service['hours']} Hours</div>
        </div>";
        $total_hours += $service['hours'];
    }
}
// Update certificate placeholders
// Add CSS styles for the new elements
$additional_css = "
<style>
    .investment-details {
        margin: 20px 0;
        padding: 20px;
        border: 1px solid #ccc;
        border-radius: 5px;
    }
    
    .investment-details-title {
        font-size: 1.2em;
        font-weight: bold;
        color: #007bff;
        margin-bottom: 15px;
        text-align: center;
    }
    
    .investment-details-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-top: 10px;
    }
    
    .detail-grid-item {
        background: #f8f9fa;
        padding: 10px;
        border-radius: 5px;
        text-align: center;
    }
    
    .item-name {
        font-weight: bold;
        color: #333;
        margin-bottom: 5px;
    }
    
    .item-hours {
        color: #666;
    }
</style>
";

// Update certificate placeholders
$certificate_html = $additional_css . $certificate_html;
$certificate_html = str_replace(
    [
        '{{INVESTOR_NAME}}', 
        '{{PROJECT_NAME}}', 
        '{{INVESTMENT_TYPE}}',
        '{{INVESTMENT_AMOUNT}}', 
        '{{TOTAL_HOURS}}',
        '{{INVESTMENT_TYPE_LABEL}}',
        '{{DETAILED_ITEMS}}',
        '{{DATE}}',
        '{{CERTIFICATE_ID}}'
    ],
    [
        $user['name'], 
        $project['project_name'], 
        ucfirst($investment_type),
        number_format($amount, 2),
        $total_hours,
        $investment_type_label,
        $detailed_items_html,
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
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}
?>