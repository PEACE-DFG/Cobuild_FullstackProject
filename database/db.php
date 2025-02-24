<?php
// Database connection settings
$host = 'localhost';        // Server host (could be localhost or server IP)
$db_name = 'ogadxeqy_cobuild';       // Database name
$username = 'ogadxeqy_usercobuild';         // MySQL username (use a secure one in production)
$password = 'usercobuild2025';        // MySQL password (set accordingly)
$charset = 'utf8mb4';       // Character set for compatibility with different languages

// Enable error reporting for debugging in development
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // Create a new MySQLi connection
    $conn = new mysqli($host, $username, $password, $db_name);

    // Set the character set to utf8mb4
    if (!$conn->set_charset($charset)) {
        throw new Exception("Error loading character set utf8mb4: " . $conn->error);
    }

    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // If no exceptions, the connection is successful
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
