<?php
// Logout functionality
if (isset($_POST['logout'])) {
  session_destroy();
  header("Location: login.php");
  exit();
}

?>