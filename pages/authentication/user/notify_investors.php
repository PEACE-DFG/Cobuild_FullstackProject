<?php
require_once '../../../database/db.php';
require '../../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function notifyInvestorsOfNewProject($project_id, $project_category_id) {
    global $conn;

    $stmt = $conn->prepare("
        SELECT DISTINCT inp.investor_id, inp.investor_email, u.name 
        FROM investor_notification_preferences inp
        JOIN users u ON inp.investor_id = u.id 
        WHERE inp.category_id = ? AND inp.is_active = 1 
    ");
    $stmt->bind_param("i", $project_category_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result === false) {
        return;
    }

    $subscribers = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if (count($subscribers) === 0) {
        return;
    }

    $stmt = $conn->prepare("
        SELECT p.title, p.description, pc.category_name, p.total_project_cost, p.investment_types,p.location
        FROM projects p
        JOIN project_categories pc ON p.project_category = pc.id
        WHERE p.id = ?
    ");
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $project = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$project) {
        return;
    }

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

        foreach ($subscribers as $subscriber) {
            if (empty($subscriber['investor_email']) || !filter_var($subscriber['investor_email'], FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            try {
                $mail->clearAddresses();
                $mail->addAddress($subscriber['investor_email']);

                $mail->isHTML(true);
                $mail->Subject = "New {$project['category_name']} Project Available on Cobuild";

                $body = "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                        <h2 style='color: #333;'>Hello {$subscriber['name']},</h2>
                        
                        <p style='color: #666;'>A new project matching your interests has been posted in the {$project['category_name']} category!</p>
                        
                        <div style='background: #f5f5f5; padding: 20px; margin: 20px 0; border-radius: 5px;'>
                            <h3 style='color: #007bff; margin-top: 0;'>Title:{$project['title']}</h3>
                            <p style='color: #444;'>Description: {$project['description']}</p>
                            <p style='color: #444;'>Location: {$project['location']}</p>
                            
                            <div style='margin-top: 15px;'>
                                <p><strong>Total Project Cost:</strong> {$project['total_project_cost']}</p>
                                <p><strong>Investment Types:</strong> {$project['investment_types']}</p>
                                <p><strong>Category:</strong> {$project['category_name']}</p>
                            </div>
                            
                            <div style='margin-top: 25px; text-align: center;'>
                                <a href='http://localhost/cobuild/investor/dashboard/project_details.php?id={$project_id}' 
                                   style='background: #007bff; color: white; padding: 12px 25px; text-decoration: none; 
                                          border-radius: 5px; display: inline-block;'>
                                    View Project Details
                                </a>
                            </div>
                        </div>
                        
                        <p style='color: #888; font-size: 0.9em; margin-top: 20px;'>
                            You're receiving this email because you subscribed to {$project['category_name']} project notifications on Cobuild.
                            <br><br>
                            <a href='http://localhost/cobuild/investor/dashboard/notification_preferences.php' 
                               style='color: #007bff; text-decoration: none;'>
                                Manage your notification preferences
                            </a>
                        </p>
                    </div>
                ";

                $mail->Body = $body;
                $mail->AltBody = strip_tags($body);

                $mail->send();

            } catch (Exception $e) {
                continue;
            }
        }

        return true;
    } catch (Exception $e) {
        return false;
    }
}

if (isset($_POST['project_id']) && isset($_POST['category_id'])) {
    $project_id = $_POST['project_id'];
    $category_id = $_POST['category_id'];
    notifyInvestorsOfNewProject($project_id, $category_id);
}
?>