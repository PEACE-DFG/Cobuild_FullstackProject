<?php
session_start();
ob_start();
require '../../../database/db.php';

// Check if user is logged in
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

// Dashboard statistics queries
$stats = [];

// Get total users count
$userQuery = "SELECT COUNT(*) as total_users FROM users";
$result = $conn->query($userQuery);
$stats['total_users'] = $result->fetch_assoc()['total_users'];

// Get active projects count
$projectQuery = "SELECT COUNT(*) as total_projects FROM projects WHERE status IN ('verified', 'pending', 'active')";
$result = $conn->query($projectQuery);
$stats['active_projects'] = $result->fetch_assoc()['total_projects'];

// Get total investments amount
$investmentQuery = "SELECT COALESCE(SUM(amount), 0) as total_investments 
                   FROM investment_intentions 
                   WHERE status = 'approved' AND investment_type = 'cash'";
$result = $conn->query($investmentQuery);
$stats['total_investments'] = $result->fetch_assoc()['total_investments'];

// Get certificates count
$certQuery = "SELECT COUNT(*) as total_certificates FROM investment_certificates";
$result = $conn->query($certQuery);
$stats['total_certificates'] = $result->fetch_assoc()['total_certificates'];

// Fetch users list
$usersQuery = "SELECT id, name, email, user_type FROM users ORDER BY id DESC";
$users = $conn->query($usersQuery)->fetch_all(MYSQLI_ASSOC);

// Fetch projects list
$projectsQuery = "SELECT p.*, u.name as builder_name 
                 FROM projects p 
                 JOIN users u ON p.builder_id = u.id 
                 ORDER BY p.created_at DESC";
$projects = $conn->query($projectsQuery)->fetch_all(MYSQLI_ASSOC);

// Fetch investments list
$investmentsQuery = "SELECT ii.*, p.title as project_name, u.name as investor_name 
                    FROM investment_intentions ii 
                    JOIN projects p ON ii.project_id = p.id 
                    JOIN users u ON ii.id = u.id 
                    ORDER BY ii.created_at DESC";
$investments = $conn->query($investmentsQuery)->fetch_all(MYSQLI_ASSOC);

// Handle AJAX requests
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'delete_user':
            if (isset($_POST['user_id'])) {
                $delete_stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                $delete_stmt->bind_param("i", $_POST['user_id']);
                $success = $delete_stmt->execute();
                echo json_encode(['success' => $success]);
            }
            exit;
            
        case 'delete_project':
            if (isset($_POST['project_id'])) {
                $delete_stmt = $conn->prepare("DELETE FROM projects WHERE id = ?");
                $delete_stmt->bind_param("i", $_POST['project_id']);
                $success = $delete_stmt->execute();
                echo json_encode(['success' => $success]);
            }
            exit;
            
        case 'get_user_details':
            if (isset($_POST['user_id'])) {
                $user_stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
                $user_stmt->bind_param("i", $_POST['user_id']);
                $user_stmt->execute();
                $user_details = $user_stmt->get_result()->fetch_assoc();
                echo json_encode($user_details);
            }
            exit;
    }
}

// Add this to your existing PHP section at the top of the file
if (isset($_POST['action']) && $_POST['action'] === 'verify_project') {
    header('Content-Type: application/json');
    if (isset($_POST['project_id'])) {
        $verify_stmt = $conn->prepare("UPDATE projects SET status = 'verified' WHERE id = ? AND status = 'pending'");
        $verify_stmt->bind_param("i", $_POST['project_id']);
        $success = $verify_stmt->execute();
        echo json_encode([
            'success' => $success,
            'message' => $success ? 'Project verified successfully' : 'Verification failed'
        ]);
    }
    exit;
}


?>

<?php
// Include the external database connection file
require_once '../../../database/db.php'; // Update the path to your actual connection file

// Function to execute a query and handle errors
function executeQuery($conn, $query) {
    $result = $conn->query($query);
    if (!$result) {
        error_log("Query failed: " . $conn->error);
        return []; // Return an empty array on failure
    }
    return $result->fetch_all(MYSQLI_ASSOC); // Fetch all results as an associative array
}

// Fetch monthly investments
$monthlyInvestments = executeQuery($conn, "
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        SUM(amount) as total_amount,
        COUNT(*) as transaction_count
    FROM investment_intentions 
    WHERE status = 'approved'
    AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month ASC
");

// Fetch project statuses
$projectStatus = executeQuery($conn, "
    SELECT 
        status, 
        COUNT(*) as count,
        ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM projects), 1) as percentage
    FROM projects
    GROUP BY status
");

// Fetch user types
$userTypes = executeQuery($conn, "
    SELECT 
        user_type, 
        COUNT(*) as count,
        ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM users), 1) as percentage
    FROM users
    GROUP BY user_type
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cobuild Admin Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        :root {
            --sidebar-width: 250px;
            --header-height: 60px;
            --primary-color: #4e73df;
            --secondary-color: #858796;
        }

        body {
            min-height: 100vh;
            margin: 0;
            background-color: #f8f9fc;
        }

        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: linear-gradient(180deg, var(--primary-color) 0%, #224abe 100%);
            color: white;
            transition: transform 0.3s ease-in-out;
            z-index: 1000;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }

        .sidebar-header {
            padding: 1rem;
            text-align: center;
            background: rgba(0, 0, 0, 0.1);
        }

        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 1rem;
            transition: all 0.3s;
        }

        .sidebar .nav-link:hover {
            color: white;
            background: rgba(255, 255, 255, 0.1);
        }

        .sidebar .nav-link i {
            margin-right: 0.5rem;
            width: 20px;
        }

        /* Main Content Styles */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 20px;
            transition: margin-left 0.3s ease-in-out;
        }

        /* Backdrop */
        .sidebar-backdrop {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }

        /* Top Navbar */
        .top-navbar {
            position: fixed;
            top: 0;
            right: 0;
            left: var(--sidebar-width);
            height: var(--header-height);
            background: white;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            z-index: 998;
            transition: left 0.3s ease-in-out;
            padding: 0 1rem;
        }

        /* Card Styles */
        .dashboard-card {
            background: white;
            border-radius: 0.35rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            margin-bottom: 1.5rem;
        }

        .card-icon {
            font-size: 2rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .top-navbar {
                left: 0;
            }
            
            .sidebar-backdrop.show {
                display: block;
            }
        }

        /* Content area padding for fixed navbar */
        .tab-content {
            padding-top: var(--header-height);
        }

        /* DataTables customization */
        .dataTables_wrapper .dataTables_length select,
        .dataTables_wrapper .dataTables_filter input {
            padding: 0.375rem 0.75rem;
            border: 1px solid #ddd;
            border-radius: 0.25rem;
        }

        /* Additional tab-related styles */
.tab-content > .tab-pane {
    display: none;
}

.tab-content > .active {
    display: block !important;
}

/* Ensure tables are visible in tabs */
.tab-pane.active .table {
    width: 100% !important;
}

/* Fix for DataTables responsiveness */
.dataTables_wrapper {
    width: 100%;
    overflow-x: auto;
}

.status-badge {
    padding: 0.5em 0.8em;
    font-size: 0.8em;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.verify-project {
    margin-right: 4px;
}

.verify-project:hover {
    background-color: #218838;
    border-color: #1e7e34;
}

/* Animation for status change */
.status-badge {
    transition: all 0.3s ease;
}

/* Tooltip styling */
.tooltip {
    font-size: 0.8rem;
}
.dashboard-card {
    background: #fff;
    border-radius: 0.35rem;
    border: 1px solid #e3e6f0;
    transition: box-shadow 0.3s ease-in-out;
}

.dashboard-card:hover {
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
}

.dashboard-card canvas {
    min-height: 300px;
}

.chart-legend {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    font-size: 0.875rem;
}

.chart-legend-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

@media (max-width: 768px) {
    .dashboard-card canvas {
        min-height: 250px;
    }
    
    .chart-legend {
        font-size: 0.75rem;
    }
}

.form-select-sm {
    font-size: 0.875rem;
    padding: 0.25rem 2rem 0.25rem 0.5rem;
}
    </style>
</head>
<body>

<!-- Sidebar -->
<nav class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h3>Cobuild Admin</h3>
    </div>
  <!-- Modify your tab navigation links to include data-bs-toggle -->
<ul class="nav flex-column">
    <li class="nav-item">
        <a class="nav-link active" data-bs-toggle="tab" href="#dashboard">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#users">
            <i class="fas fa-users"></i> Users
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#projects">
            <i class="fas fa-project-diagram"></i> Projects
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#investments">
            <i class="fas fa-chart-line"></i> Investments
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#certificates">
            <i class="fas fa-certificate"></i> Certificates
        </a>
    </li>
</ul>
</nav>

<!-- Backdrop -->
<div class="sidebar-backdrop"></div>

<!-- Top Navbar -->
<nav class="top-navbar">
    <div class="d-flex justify-content-between align-items-center h-100">
        <button class="btn btn-link" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>
        <!-- <div class="d-flex align-items-center">
            <div class="dropdown me-3">
                <button class="btn btn-link position-relative" id="notificationsDropdown" data-bs-toggle="dropdown">
                    <i class="fas fa-bell"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        3
                    </span>
                </button>
                <div class="dropdown-menu dropdown-menu-end">
                    <a class="dropdown-item" href="#">New user registration</a>
                    <a class="dropdown-item" href="#">New investment</a>
                    <a class="dropdown-item" href="#">New project created</a>
                </div>
            </div>
            <div class="dropdown">
                <button class="btn btn-link d-flex align-items-center" id="userDropdown" data-bs-toggle="dropdown">
                    <img src="<?php echo htmlspecialchars($user['profile_image'] ?? 'passport.jpg'); ?>" 
                         class="rounded-circle me-2" 
                         width="32" 
                         height="32" 
                         alt="User">
                    <span><?php echo htmlspecialchars($user['name'] ?? 'Admin'); ?></span>
                </button>
                <div class="dropdown-menu dropdown-menu-end">
                    <a class="dropdown-item" href="#profile">Profile</a>
                    <a class="dropdown-item" href="#settings">Settings</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="logout.php">Logout</a>
                </div>
            </div>
        </div> -->
    </div>
</nav>

<!-- Main Content -->
<div class="main-content">
    <div class="tab-content">
        <!-- Dashboard Tab -->
        <div class="tab-pane fade show active" id="dashboard">
            <div class="container-fluid">
                <!-- Statistics Cards -->
                <div class="row">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="dashboard-card p-4">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="text-primary fw-bold">TOTAL USERS</h6>
                                    <h4 class="mb-0"><?php echo number_format($stats['total_users']); ?></h4>
                                </div>
                                <i class="fas fa-users card-icon text-primary"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="dashboard-card p-4">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="text-success fw-bold">ACTIVE PROJECTS</h6>
                                    <h4 class="mb-0"><?php echo number_format($stats['active_projects']); ?></h4>
                                </div>
                                <i class="fas fa-project-diagram card-icon text-success"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="dashboard-card p-4">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="text-info fw-bold">TOTAL INVESTMENTS</h6>
                                    <h4 class="mb-0">₦<?php echo number_format($stats['total_investments']); ?></h4>
                                </div>
                                <i class="fas fa-chart-line card-icon text-info"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="dashboard-card p-4">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="text-warning fw-bold">CERTIFICATES</h6>
                                    <h4 class="mb-0"><?php echo number_format($stats['total_certificates']); ?></h4>
                                </div>
                                <i class="fas fa-certificate card-icon text-warning"></i>
                            </div>
                        </div>
                    </div>
                </div>


                <!-- Improved Dashboard Layout -->
<div class="tab-pane fade show active" id="dashboard">
    <div class="container-fluid">
        <!-- Loading State -->
        <div id="dashboardLoading" class="text-center py-5 d-none">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
        
        <div id="dashboardContent">
    <!-- User Type Chart -->
     <div style="text-align:center">
        <h2>Users Categories</h2>
     </div>
    <div class="chart-container" style="position: relative; height: 400px;">
        <canvas id="userTypeChart"></canvas>
    </div>

    <div style="text-align:center">
        <h2>Projects Categories</h2>
     </div>
    <!-- Project Status Chart -->
    <div class="chart-container" style="position: relative; height: 400px;">
        <canvas id="projectStatusChart"></canvas>
    </div>

    <!-- Monthly Investments Chart -->
    <!-- <div class="chart-container" style="position: relative; height: 400px;">
        <canvas id="monthlyInvestmentsChart"></canvas>
    </div> -->
</div>

    </div>
</div>

           

                
            </div>
        </div>

        <!-- Users Tab -->
        <div class="tab-pane fade" id="users">
            <div class="container-fluid">
                <div class="dashboard-card p-4">
                    <h5 class="mb-4">Users Management</h5>
                    <div class="table-responsive">
                        <table class="table table-striped" id="usersTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Type</th>
                                    <!-- <th>Actions</th> -->
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['id']); ?></td>
                                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo htmlspecialchars($user['user_type']); ?></td>
                                        <!-- <td>
                                            <button class="btn btn-sm btn-primary edit-user" data-id="<?php echo $user['id']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger delete-user" data-id="<?php echo $user['id']; ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td> -->
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    <!-- Update the Projects Tab table structure -->
<div class="tab-pane fade" id="projects">
    <div class="container-fluid">
        <div class="dashboard-card p-4">
            <h5 class="mb-4">Projects Management</h5>
            <div class="table-responsive">
                <table class="table table-striped" id="projectsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Builder</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Verify Now</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($projects as $project): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($project['id']); ?></td>
                                <td><?php echo htmlspecialchars($project['title']); ?></td>
                                <td><?php echo htmlspecialchars($project['builder_name']); ?></td>
                                <td>
                                    <span class="status-badge badge <?php 
                                        echo $project['status'] === 'verified' ? 'bg-success' : 
                                            ($project['status'] === 'pending' ? 'bg-warning' : 'bg-secondary');
                                    ?>">
                                        <?php echo htmlspecialchars($project['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($project['created_at'])); ?></td>
                                <td>
                                    <?php if ($project['status'] === 'pending'): ?>
                                        <button class="btn btn-sm btn-success verify-project" 
                                                data-id="<?php echo $project['id']; ?>"
                                                title="Verify Project">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    <?php endif; ?>
                                    <!-- <button class="btn btn-sm btn-primary edit-project" 
                                            data-id="<?php echo $project['id']; ?>"
                                            title="Edit Project">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger delete-project" 
                                            data-id="<?php echo $project['id']; ?>"
                                            title="Delete Project">
                                        <i class="fas fa-trash"></i>
                                    </button> -->
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

        <!-- Investments Tab -->
        <div class="tab-pane fade" id="investments">
            <div class="container-fluid">
                <div class="dashboard-card p-4">
                    <h5 class="mb-4">Investments Management</h5>
                    <div class="table-responsive">
                        <table class="table table-striped" id="investmentsTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Project</th>
                                    <th>Investor</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($investments as $investment): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($investment['id']); ?></td>
                                        <td><?php echo htmlspecialchars($investment['project_name']); ?></td>
                                        <td><?php echo htmlspecialchars($investment['investor_name']); ?></td>
                                        <td>₦<?php echo number_format($investment['amount']); ?></td>
                                        <td><?php echo htmlspecialchars($investment['status']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($investment['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Certificates Tab -->
        <div class="tab-pane fade" id="certificates">
            <div class="container-fluid">
                <div class="dashboard-card p-4">
                    <h5 class="mb-4">Investment Certificates</h5>
                    <div class="table-responsive">
                        <table class="table table-striped" id="certificatesTable">
                            <thead>
                                <tr>
                                    <th>Certificate ID</th>
                                    <th>Project</th>
                                    <th>Investor</th>
                                    <th>Issue Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Certificate data will be loaded dynamically -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<!-- Improved JavaScript Implementation -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>

<script>
$(document).ready(function() {
    // Initialize DataTables
    $('#usersTable, #projectsTable, #investmentsTable, #certificatesTable').DataTable();

    // Sidebar Toggle
    $('#sidebarToggle').click(function() {
        $('.sidebar').toggleClass('active');
        $('.sidebar-backdrop').toggleClass('show');
        $('.main-content, .top-navbar').toggleClass('sidebar-open');
    });

    // Close sidebar when clicking backdrop
    $('.sidebar-backdrop').click(function() {
        $('.sidebar').removeClass('active');
        $('.sidebar-backdrop').removeClass('show');
    });

    // Delete User Handler
    $('.delete-user').click(function() {
        if (confirm('Are you sure you want to delete this user?')) {
            const userId = $(this).data('id');
            $.post('', {
                action: 'delete_user',
                user_id: userId
            }, function(response) {
                if (response.success) {
                    location.reload();
                }
            });
        }
    });

    // Delete Project Handler
    $('.delete-project').click(function() {
        if (confirm('Are you sure you want to delete this project?')) {
            const projectId = $(this).data('id');
            $.post('', {
                action: 'delete_project',
                project_id: projectId
            }, function(response) {
                if (response.success) {
                    location.reload();
                }
            });
        }
    });

    // Edit User Handler
    $('.edit-user').click(function() {
        const userId = $(this).data('id');
        $.post('', {
            action: 'get_user_details',
            user_id: userId
        }, function(response) {
            // Handle user edit form population
            // You can implement a modal or redirect to edit page
        });
    });
});
document.addEventListener('DOMContentLoaded', function() {
    // Sidebar Toggle
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.querySelector('.main-content');
    const topNavbar = document.querySelector('.top-navbar');

    sidebarToggle.addEventListener('click', function() {
        sidebar.classList.toggle('collapsed');
        mainContent.classList.toggle('expanded');
        topNavbar.classList.toggle('expanded');
    });

    // Handle responsive behavior
    function handleResize() {
        if (window.innerWidth <= 768) {
            sidebar.classList.add('collapsed');
            mainContent.classList.add('expanded');
            topNavbar.classList.add('expanded');
        } else {
            sidebar.classList.remove('collapsed');
            mainContent.classList.remove('expanded');
            topNavbar.classList.remove('expanded');
        }
    }

    window.addEventListener('resize', handleResize);
    handleResize(); // Initial check

    // Initialize Bootstrap tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

// Tab functionality
$(document).ready(function() {
    // Ensure Bootstrap tabs are properly initialized
    const triggerTabList = [].slice.call(document.querySelectorAll('.nav-link'));
    triggerTabList.forEach(function(triggerEl) {
        const tabTrigger = new bootstrap.Tab(triggerEl);
        
        triggerEl.addEventListener('click', function(event) {
            event.preventDefault();
            tabTrigger.show();
        });
    });

    // Store the last active tab
    let lastTab = localStorage.getItem('lastActiveTab');
    if (lastTab) {
        // Show the last active tab
        $('.nav-link[href="' + lastTab + '"]').tab('show');
    }

    // Save the last active tab
    $('.nav-link').on('shown.bs.tab', function (e) {
        localStorage.setItem('lastActiveTab', $(e.target).attr('href'));
    });

    // Initialize DataTables with specific configurations
    $('#usersTable').DataTable({
        "pageLength": 10,
        "order": [[0, "desc"]],
        "responsive": true
    });

    $('#projectsTable').DataTable({
        "pageLength": 10,
        "order": [[4, "desc"]], // Sort by created date
        "responsive": true
    });

    $('#investmentsTable').DataTable({
        "pageLength": 10,
        "order": [[5, "desc"]], // Sort by date
        "responsive": true
    });

    $('#certificatesTable').DataTable({
        "pageLength": 10,
        "order": [[3, "desc"]], // Sort by issue date
        "responsive": true
    });

    // Add active class to the current tab's nav link
    $('.nav-link').on('click', function() {
        $('.nav-link').removeClass('active');
        $(this).addClass('active');
    });

    // Refresh tables when tab is shown
    $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        // Adjust DataTables column widths
        $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
    });
});

$(document).ready(function() {
    // Add this to your existing jQuery initialization
    
    // Verify Project Handler
    $('.verify-project').click(function() {
        const projectId = $(this).data('id');
        const button = $(this);
        const statusCell = button.closest('tr').find('td:nth-child(4)');
        
        if (confirm('Are you sure you want to verify this project?')) {
            $.post('', {
                action: 'verify_project',
                project_id: projectId
            }, function(response) {
                if (response.success) {
                    // Update the status badge
                    statusCell.html('<span class="status-badge badge bg-success">verified</span>');
                    // Remove the verify button
                    button.remove();
                    // Show success message
                    alert('Project verified successfully');
                } else {
                    alert('Verification failed. Please try again.');
                }
            }).fail(function() {
                alert('An error occurred. Please try again.');
            });
        }
    });

    // Initialize tooltips for the new verify button
    $('[data-bs-toggle="tooltip"]').tooltip();
});
   // Pass PHP data to JavaScript
   const monthlyInvestments = <?php echo json_encode($monthlyInvestments); ?>;
        const projectStatus = <?php echo json_encode($projectStatus); ?>;
        const userTypes = <?php echo json_encode($userTypes); ?>;

        // Chart configuration
        const chartColors = {
            primary: '#4e73df',
            success: '#1cc88a',
            info: '#36b9cc',
            warning: '#f6c23e',
            danger: '#e74a3b'
        };

        // Monthly Investments Chart
        new Chart(document.getElementById('monthlyInvestmentsChart'), {
            type: 'line',
            data: {
                labels: monthlyInvestments.map(item => item.month),
                datasets: [{
                    label: 'Total Investments',
                    data: monthlyInvestments.map(item => item.total_amount),
                    borderColor: chartColors.primary,
                    fill: false,
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: true }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });

        // Project Status Chart
        new Chart(document.getElementById('projectStatusChart'), {
            type: 'pie',
            data: {
                labels: projectStatus.map(item => item.status),
                datasets: [{
                    label: 'Project Status',
                    data: projectStatus.map(item => item.count),
                    backgroundColor: [chartColors.primary, chartColors.success, chartColors.warning, chartColors.danger]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: true }
                }
            }
        });

        // User Type Chart
        new Chart(document.getElementById('userTypeChart'), {
            type: 'bar',
            data: {
                labels: userTypes.map(item => item.user_type),
                datasets: [{
                    label: 'User Types',
                    data: userTypes.map(item => item.count),
                    backgroundColor: chartColors.info
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: true }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
</script>

</body>
</html>