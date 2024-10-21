<?php
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
$dashboard_type = ($user['user_type'] == 'builder') ? 'builder' : 'investor';
// Define MAIN_DASHBOARD to include builder_dashboard.php correctly
define('MAIN_DASHBOARD', true);

// Function to handle profile image upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['profile-image'])) {
  $target_dir = "uploads/";
  $target_file = $target_dir . basename($_FILES["profile-image"]["name"]);
  $uploadOk = 1;
  $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

  // Check if image file is an actual image or fake image
  $check = getimagesize($_FILES["profile-image"]["tmp_name"]);
  if ($check !== false) {
      $uploadOk = 1;
  } else {
      echo "File is not an image.";
      $uploadOk = 0;
  }

  // Check file size
  if ($_FILES["profile-image"]["size"] > 500000) { // 500KB limit
      echo "Sorry, your file is too large.";
      $uploadOk = 0;
  }

  // Allow certain file formats
  if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
      echo "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
      $uploadOk = 0;
  }

  // Check if $uploadOk is set to 0 by an error
  if ($uploadOk == 0) {
      echo "Sorry, your file was not uploaded.";
  } else {
      // If everything is ok, try to upload file
      if (move_uploaded_file($_FILES["profile-image"]["tmp_name"], $target_file)) {
          // Update user profile with the new image path
          $stmt = $conn->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
          $stmt->bind_param("si", $target_file, $user_id);
          $stmt->execute();
      } else {
          echo "Sorry, there was an error uploading your file.";
      }
  }
}

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
    <style>
        :root {
            --primary-blue: #040b90;
            --secondary-orange: #FFA500;
            --light-ash: #F0F0F0;
            --dark-ash: #A9A9A9;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light-ash);
            color: var(--primary-blue);
        }

        .sidebar {
            width: 250px;
            height: 100%;
            background-color: var(--primary-blue);
            color: white;
            position: fixed;
            top: 0;
            left: -250px;
            z-index: 1000;
            transition: left 0.3s ease;
        }

        .sidebar.active {
            left: 0;
        }

        .sidebar-header {
            padding: 20px;
            text-align: center;
            background-color: rgba(255, 255, 255, 0.1);
        }

        .sidebar ul li a {
            color: white;
        }

        .sidebar ul li a:hover {
            background-color: var(--secondary-orange);
            color: var(--primary-blue);
        }

        .backdrop {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }

        .main-content {
            margin-left: 0;
            padding: 20px;
            transition: margin-left 0.3s ease;
        }

        @media (min-width: 769px) {
            .sidebar {
                left: 0;
            }
            .main-content {
                margin-left: 250px;
            }
        }

        .navbar {
            background-color: white !important;
        }

        .btn-primary {
            background-color: var(--secondary-orange);
            border-color: var(--secondary-orange);
            color: var(--primary-blue);
        }

        .btn-primary:hover {
            background-color: var(--primary-blue);
            border-color: var(--primary-blue);
            color: white;
        }

        .card {
            background-color: white;
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .card-body h3 {
            color: var(--primary-blue);
        }

        .progress-bar {
            background-color: var(--secondary-orange);
        }

        .chart-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }

        .charts-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-top: 20px;
        }

        footer {
            background-color: var(--primary-blue);
            color: white;
            text-align: center;
            padding: 15px;
            margin-top: 20px;
        }
    </style>
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
                <a class="nav-link" href="#"><i class="fas fa-users me-2"></i> Users</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#"><i class="fas fa-tasks me-2"></i> Tasks</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#"><i class="fas fa-chart-line me-2"></i> Analytics</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#"><i class="fas fa-cogs me-2"></i> Settings</a>
            </li>
            <li class="nav-item">
            <form action="logout.php" method="post" class="d-inline">
    <button type="submit" name="logout" class="nav-link btn btn-link text-white">
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
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#profileModal">Edit Profile</button>
                    </div>
                </div>
            </div>
        </nav>

        <?php
        if ($dashboard_type == 'builder') {
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
                <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="profile-name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="profile-name" name="profile-name" value="<?php echo htmlspecialchars($user['name']); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="profile-email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="profile-email" name="profile-email" value="<?php echo htmlspecialchars($user['email']); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="profile-password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="profile-password" name="profile-password">
                        </div>
                        <div class="mb-3">
                            <label for="profile-image" class="form-label">Profile Image</label>
                            <input type="file" class="form-control" id="profile-image" name="profile-image">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary">Update</button>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <p>&copy; 2024 Cobuild. All rights reserved.</p>
    </footer>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js"></script>

    <script>
        const menuToggle = document.getElementById('menu-toggle');
        const sidebar = document.getElementById('sidebar');
        const backdrop = document.getElementById('backdrop');

        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
            backdrop.style.display = sidebar.classList.contains('active') ? 'block' : 'none';
        });

        backdrop.addEventListener('click', () => {
            sidebar.classList.remove('active');
            backdrop.style.display = 'none';
        });
    </script>
</body>
</html>
