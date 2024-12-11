<?php
ob_start();
require '../../../database/db.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
// Fetch user information
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
// Check if the user exists
if (!$user) {
    header("Location: login.php");
    exit();
}
// Check user type and load appropriate dashboard
$dashboard_type = ($user['user_type'] == 'developer') ? 'developer' : 'investor';
// Define MAIN_DASHBOARD to include builder_dashboard.php correctly
define('MAIN_DASHBOARD', true);
// Initialize an array to store error messages
$errors = [];
ob_end_flush();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cobuild Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome for icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <!-- Chart.js for charts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom Styles -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- paystack -->
    <script src="https://js.paystack.co/v1/inline.js"></script>
    <!-- css -->
     <link rel="stylesheet" href="dashboard.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

</head>
<body>

    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2>Cobuild</h2>
        </div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="#"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a>
            </li>
       
            <li class="nav-item">
                <a class="nav-link" href="../../../index.php"><i class="fas fa-home"></i> Go to Home</a>
            </li>
            <li class="nav-item">
            <form action="logout.php" method="post" class="d-inline">
            <button type="submit" name="logout" id="logoutButton" class="nav-link btn btn-link text-white">
                <i class="fas fa-sign-out-alt me-2"></i> Logout
            </button>
            </form>

            </li>
        </ul>
    </nav>

    <!-- Backdrop -->
    <div class="backdrop" id="backdrop"></div>

    <!-- Main Content -->
    <div class="main-content" id="main-content">
        <nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm">
            <div class="container-fluid">
                <button class="navbar-toggler" type="button" id="menu-toggle" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="navbar-collapse" id="navbarNav">
                    <div class="ms-auto d-flex align-items-center">
                        <button type="button" class="btn btn-primary position-relative">
                            <i class="fas fa-envelope icon"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                99+
                                <span class="visually-hidden">unread messages</span>
                            </span>
                        </button>
                        <button type="button" class="btn btn-primary position-relative mx-3">
                            <i class="fas fa-bell icon"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                99+
                                <span class="visually-hidden">unread messages</span>
                            </span>
                        </button>
                        <img src="<?php echo isset($user['profile_image']) ? htmlspecialchars($user['profile_image']) : 'https://static.vecteezy.com/system/resources/previews/019/879/186/non_2x/user-icon-on-transparent-background-free-png.png'; ?>" alt="User" class="rounded-circle" width="40">
                        <span class="me-3"><?php echo htmlspecialchars($user['name'] ?? 'Guest'); ?></span>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#profileModal"><small>Edit Profile</small></button>
                    </div>
                </div>
            </div>
        </nav>
        <?php
        if ($dashboard_type == 'developer') {
            include 'user_dashboards/builder_dashboard.php';
        } else {
            include 'user_dashboards/investor_dashboard.php';
        }
        ?>
    </div>
<!-- Profile Modal -->
<div class="modal fade" id="profileModal" tabindex="-1" aria-labelledby="profileModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="profileModalLabel">Edit Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="profile-form" method="POST" enctype="multipart/form-data"  onsubmit="submitProfileForm(event)">
                <div class="mb-3">
    <label for="profile-name" class="form-label">Name</label>
    <input type="text" class="form-control" id="profile-name" name="profile-name" value="<?php echo htmlspecialchars($user['name']); ?>" readonly>
</div>
<div class="mb-3">
    <label for="profile-email" class="form-label">Email</label>
    <input type="email" class="form-control" id="profile-email" name="profile-email" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
</div>
<div class="mb-3">
    <label for="profile-password" class="form-label">New Password</label>
    <input type="password" class="form-control" id="profile-password" name="profile-password" readonly>
</div>
<div class="mb-3">
    <label for="profile-image" class="form-label">Profile Image</label>
    <input type="file" class="form-control" id="profile-image" name="profile-image" readonly>
</div>
                    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary" disabled>Update</button>
    </div>
                </form>
            </div>
       
        </div>
    </div>
</div>

    <footer>
        <p>
            <small class="block">&copy; 2024 Cobuild. All rights reserved.Designed by <a href="https://github.com/PEACE-DFG" target="_blank">CODEMaster</a></small>

        </p>
    </footer>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js"></script>
    <script src="dashboardlogic.js"></script>

<?php
if (!empty($errors)) {
    // Convert the $errors array into a single string
    $error_message = implode("\n", $errors);
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: '" . addslashes($error_message) . "'
            });
        });
    </script>";
}
?>

<div id="notificationToast" class="toast" style="display:none; position: fixed; bottom: 20px; right: 20px; padding: 10px; background-color: #333; color: #fff; border-radius: 5px; z-index: 1000;"></div>

</body>
</html>