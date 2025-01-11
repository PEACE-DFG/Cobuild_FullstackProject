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

// Fetch investment intentions for the logged-in developer
$developer_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT ii.*, p.title AS project_name FROM investment_intentions ii JOIN projects p ON ii.project_id = p.id WHERE p.builder_id = ? AND ii.status = 'pending'");
$stmt->bind_param("i", $developer_id);
$stmt->execute();
$intentions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
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
    <!-- Paystack -->
    <script src="https://js.paystack.co/v1/inline.js"></script>
    <!-- CSS -->
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
                        <!-- Messages Dropdown -->
                        <div class="dropdown">
                            <button class="btn btn-primary dropdown-toggle position-relative" id="messagesDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-envelope icon"></i>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="messageCount">0</span>
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="messagesDropdown" id="messagesList">
                                <!-- Messages will be populated here dynamically -->
                            </ul>
                        </div>
                   
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
                    <form id="profile-form" method="POST" enctype="multipart/form-data" onsubmit="submitProfileForm(event)">
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
            <small class="block">&copy; 2024 Cobuild. All rights reserved. Designed by <a href="https://github.com/PEACE-DFG" target="_blank">CODEMaster</a></small>
        </p>
    </footer>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js"></script>
    <script src="dashboardlogic.js"></script>
    <script>
        // Pass the intentions to JavaScript for dynamic rendering
        const messages = <?php echo json_encode($intentions); ?>;
        const messagesList = document.getElementById('messagesList');
        const messageCount = document.getElementById('messageCount');

        function populateMessages() {
    messagesList.innerHTML = '';
    let count = 0;

    messages.forEach(message => {
        console.log(message); // Debugging line
        count++;
        const listItem = document.createElement('li');
        listItem.className = 'dropdown-item';

        // Determine what to display based on the investment_type field
        let displayText;
        if (message.investment_type === 'cash') {
            displayText = `Investment Amount: NGN ${message.amount}`;
        } else if (message.investment_type === 'service') {
            displayText = `Service: ${message.description}`; // Assuming there's a 'description' field for services
        } else if (message.investment_type === 'skill') {
            displayText = `Skills: ${message.description}`; // Assuming there's a 'description' field for skills
        } else {
            displayText = 'Unknown investment type';
        }

        listItem.innerHTML = `
            <strong>${message.project_name}</strong><br>
            ${displayText}<br>
            <button class="btn btn-success btn-sm" onclick="approveInvestment(${message.id})">Approve</button>
            <button class="btn btn-danger btn-sm" onclick="rejectInvestment(${message.id})">Reject</button>
        `;
        messagesList.appendChild(listItem);
    });

    messageCount.textContent = count; // Update message count
}



        populateMessages();

        function approveInvestment(investmentId) {
    fetch('./ajax/approval_investment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ id: investmentId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Create a success message
            const message = document.createElement('div');
            message.textContent = "Investment intention approved!";
            message.style.color = "green"; // Success color
            document.body.appendChild(message);
            setTimeout(() => {
                message.remove(); // Remove message after 3 seconds
            }, 1000);
            setTimeout(() => {
                location.reload(); // Refresh the page after approval
            }, 1000); // Wait for the message to display
        } else {
            // Create an error message
            const message = document.createElement('div');
            message.textContent = "Error approving investment intention: " + data.message;
            message.style.color = "red"; // Error color
            document.body.appendChild(message);
            setTimeout(() => {
                message.remove(); // Remove message after 3 seconds
            }, 1000);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        const message = document.createElement('div');
        message.textContent = "An error occurred. Please try again.";
        message.style.color = "red"; // Error color
        document.body.appendChild(message);
        setTimeout(() => {
            message.remove(); // Remove message after 3 seconds
        }, 1000);
    });
}


        function rejectInvestment(investmentId) {
    if (confirm("Are you sure you want to reject this investment intention?")) {
        fetch('./ajax/reject_investment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: investmentId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const message = document.createElement('div');
                message.textContent = "Investment intention rejected successfully.";
                message.style.color = "green"; // Success color
                document.body.appendChild(message);
                setTimeout(() => {
                    message.remove(); // Remove message after 3 seconds
                }, 1000);
                setTimeout(() => {
                    location.reload(); // Refresh the page after rejection
                }, 1000); // Wait for the message to display
            } else {
                const message = document.createElement('div');
                message.textContent = "Error: " + data.message;
                message.style.color = "red"; // Error color
                document.body.appendChild(message);
                setTimeout(() => {
                    message.remove(); // Remove message after 3 seconds
                }, 1000);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            const message = document.createElement('div');
            message.textContent = "An error occurred. Please try again.";
            message.style.color = "red"; // Error color
            document.body.appendChild(message);
            setTimeout(() => {
                message.remove(); // Remove message after 3 seconds
            }, 1000);
        });
    }
}

    </script>

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

