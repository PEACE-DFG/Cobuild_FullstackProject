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

// Get the user ID from the session
$user_id = $_SESSION['user_id'];

// Verify user is an investor
$stmt = $conn->prepare("SELECT user_type, name, email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
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

$project_id = intval($_POST['project_id']);
$investment_type = $_POST['investment_type'];
$amount = $_POST['amount'] ?? 0;
$hours = $_POST['hours'] ?? 0;
$investment_details = [];

// Initialize variables
$quantity = 0;
$unit = '';
$selected_items = [];

// Process different investment types
switch ($investment_type) {
    case 'cash':
        if (!isset($_POST['amount']) || floatval($_POST['amount']) <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid cash amount']);
            exit;
        }
        $quantity = 1;
        $unit = 'NGN';
        $investment_details = [
            'type' => 'cash',
            'amount' => floatval($_POST['amount'])
        ];
        break;

    case 'skill':
        if (!isset($_POST['selected_skills']) || empty($_POST['selected_skills'])) {
            echo json_encode(['success' => false, 'message' => 'No skills selected']);
            exit;
        }
        $selected_items = $_POST['selected_skills'];
        $unit = 'skills';
        break;

    case 'service':
        if (!isset($_POST['selected_services']) || empty($_POST['selected_services'])) {
            echo json_encode(['success' => false, 'message' => 'No services selected']);
            exit;
        }
        $selected_items = $_POST['selected_services'];
        $unit = 'services';
        break;

    case 'materials':
        if (!isset($_POST['selected_materials']) || empty($_POST['selected_materials'])) {
            echo json_encode(['success' => false, 'message' => 'No materials selected']);
            exit;
        }
        $selected_items = $_POST['selected_materials'];
        $unit = 'materials';
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid investment type']);
        exit;
}

// Generate certificate number
$certificate_number = 'CB-' . date('Y') . '-' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
$created_date = date('Y-m-d H:i:s');

$conn->begin_transaction();

try {
    // Get project information
    $stmt = $conn->prepare("
        SELECT 
            p.title as project_name,
            p.description as project_description,
            u.email as developer_email,
            u.name as developer_name
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

    // Process investment details based on type
    if ($investment_type !== 'cash') {
        $table_name = "project_" . ($investment_type === 'skill' ? 'skills' : ($investment_type === 'service' ? 'services' : 'materials'));
        $name_field = ($investment_type === 'materials' ? 'material_name' : ($investment_type === 'skill' ? 'skill_type' : 'service_type'));
        
        $items_ids = implode(',', array_map('intval', $selected_items));
        $stmt = $conn->prepare("
            SELECT 
                id,
                {$name_field} as name,
                " . ($investment_type === 'materials' ? 'quantity, unit' : 'total_hours') . "
            FROM {$table_name}
            WHERE project_id = ? AND id IN ($items_ids)
        ");
        $stmt->bind_param("i", $project_id);
        $stmt->execute();
        $items_result = $stmt->get_result();

        $selected_items_details = [];
        while ($row = $items_result->fetch_assoc()) {
            $selected_items_details[$row['id']] = $row;
            if ($investment_type === 'materials') {
                $quantity += $row['quantity'];
                $unit = $row['unit'];
            }
        }

        if ($investment_type !== 'materials') {
            $quantity = count($selected_items_details);
        }

        $investment_details = [
            'type' => $investment_type,
            'items' => $selected_items_details
        ];
    }

    // Insert investment intention
    $investment_details_json = json_encode($investment_details);
    
    $stmt = $conn->prepare("
        INSERT INTO investment_intentions (
            investor_id, project_id, investment_type, quantity, unit,
            investment_details, amount, hours, status, certificate_number,
            user_id, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?)
    ");

    $stmt->bind_param(
        "iisdssdisss",
        $user_id, $project_id, $investment_type, $quantity, $unit,
        $investment_details_json, $amount, $hours, $certificate_number,
        $user_id, $created_date
    );
    $stmt->execute();

    // Prepare email content
    $email_content = generate_email_content(
        $project, $user, $investment_type, $investment_details,
        $amount, $certificate_number, $selected_items_details ?? []
    );

    // Generate Certificate
    $certificate_path = generate_certificate(
        $user, $project, $investment_type, $amount,
        $hours, $investment_details, $created_date,
        $certificate_number
    );

    // Send notifications
    send_notification_email(
        $project['developer_email'],
        'New Investment Intention',
        $email_content
    );

    send_notification_email(
        $user['email'],
        'Certificate of Investment Intention',
        "Thank you for your investment intention in {$project['project_name']}. 
         Your certificate number is {$certificate_number}. 
         Please find your certificate attached.",
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
    error_log("Investment Intention Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function generate_email_content($project, $user, $investment_type, $investment_details, $amount, $certificate_number, $items = []) {
    $content = "
    <div style='font-family: Arial, sans-serif; background-color: white; color: #333; padding: 20px;'>
        <h1 style='color: #007bff; text-align: center;'>Cobuild</h1>
        <h2 style='color: #007bff;'>New Investment Intention</h2>
        <p><strong>Project Name:</strong> {$project['project_name']}</p>
        <p><strong>Investor Name:</strong> {$user['name']}</p>
        <p><strong>Investment Type:</strong> " . ucfirst($investment_type) . "</p>";

    if ($investment_type === 'cash') {
        $content .= "<p><strong>Investment Amount:</strong> NGN" . number_format($amount, 2) . "</p>";
    } else {
        $content .= "<p><strong>Selected " . ucfirst($investment_type) . "s:</strong></p><ul>";
        foreach ($items as $item) {
            $content .= "<li>{$item['name']} " . 
                       (isset($item['total_hours']) ? "({$item['total_hours']} hours)" : 
                       "({$item['quantity']} {$item['unit']})") . "</li>";
        }
        $content .= "</ul>";
    }

    $content .= "
        <p><strong>Certificate Number:</strong> {$certificate_number}</p>
    </div>";

    return $content;
}

function generate_certificate($user, $project, $investment_type, $amount, $hours, $investment_details, $created_date, $certificate_number) {
    $certificate_path = __DIR__ . "/../../../../certificates/investment_certificate_" . time() . ".pdf";
    
    $dompdf = new Dompdf();
    $options = $dompdf->getOptions();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isCssFloatEnabled', true);
    $dompdf->setOptions($options);

    $certificate_html = file_get_contents('../../../Certificates/certificate_of_investment.php');

    // Format detailed items for certificate
    $detailed_items = '';
    if ($investment_type === 'cash') {
        $detailed_items = "Cash Investment of NGN " . number_format($amount, 2);
    } else {
        $items = $investment_details['items'];
        foreach ($items as $item) {
            $detailed_items .= $item['name'] . "\n";
        }
    }

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
            $hours,
            ucfirst($investment_type),
            $detailed_items,
            date('F d, Y', strtotime($created_date)),
            $certificate_number
        ],
        $certificate_html
    );

    $dompdf->loadHtml($certificate_html);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();
    
    file_put_contents($certificate_path, $dompdf->output());
    return $certificate_path;
}

function send_notification_email($to, $subject, $message, $attachment = null) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'Officialcobuild@gmail.com';
        $mail->Password   = 'udodjurhumdfrsim';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('Officialcobuild@gmail.com', 'Cobuild');
        $mail->addAddress($to);

        if ($attachment) {
            $mail->addAttachment($attachment);
        }

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email Error: {$mail->ErrorInfo}");
        return false;
    }
}
?>