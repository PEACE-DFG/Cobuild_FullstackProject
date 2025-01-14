
<?php
session_start();

// Destroy the session if user confirmed logout
if (isset($_POST['confirm_logout']) && $_POST['confirm_logout'] == "yes") {
    // Unset all session variables and destroy the session
    session_unset();
    session_destroy();

    // Prevent browser from loading cached pages after logout
    header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1
    header("Pragma: no-cache"); // HTTP 1.0
    header("Expires: 0"); // Proxies

    // Redirect to the login page
    header("Location: login.php");
    exit();
}
?>
