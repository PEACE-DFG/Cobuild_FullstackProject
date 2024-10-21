<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require '../../../database';
// Fetch user information
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Check user type and load appropriate dashboard
if ($user['user_type'] == 'builder') {
    include 'builder_dashboard.php';
} else {
    include 'investor_dashboard.php';
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cobuild Dashboard</title>
    <!-- Add your CSS and JS links here -->
</head>
<body>
    <header>
        <!-- Add navigation menu -->
    </header>

    <main>
        <!-- Dashboard content will be loaded here based on user type -->
    </main>

    <footer>
        <!-- Add footer content -->
    </footer>
</body>
</html>