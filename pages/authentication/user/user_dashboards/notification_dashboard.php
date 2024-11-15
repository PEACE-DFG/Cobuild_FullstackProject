<!-- notification_dashboard.php -->
<?php
session_start();
if (!isset($_SESSION['investor_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification History</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .notification-card {
            margin-bottom: 15px;
            transition: transform 0.2s;
        }
        .notification-card:hover {
            transform: translateY(-2px);
        }
        .success {
            border-left: 4px solid #198754;
        }
        .failed {
            border-left: 4px solid #dc3545;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h2 class="mb-4">Your Notification History</h2>
        <div id="notifications-container">
            <div class="text-center">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        </div>
    </div>

    <script src="notification_scripts.js"></script>
</body>
</html>