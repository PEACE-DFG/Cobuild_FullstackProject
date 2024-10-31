<?php
ob_start();
// Ensure this file is not accessed directly
if (!defined('MAIN_DASHBOARD')) {
    die('Direct access not permitted');
}

// Add error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Initialize database connection if not already set
if (!isset($conn)) {
    require_once 'config/database.php';
}

// Verify database connection
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verify user session and user type
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    $_SESSION['error_message'] = "Please log in to access the dashboard";
    echo "<script>window.location.href = 'login.php';</script>";
    exit();
}

// Verify user is a builder
$stmt = $conn->prepare("SELECT user_type FROM users WHERE id = ? AND user_type = 'builder'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    $_SESSION['error_message'] = "Unauthorized access";
    echo "<script>window.location.href = 'login.php';</script>";
    exit();
}

// Function to safely get POST values
function getPostValue($key, $default = '') {
    return isset($_POST[$key]) ? trim($_POST[$key]) : $default;
}

// Function to handle file upload
function handleFileUpload($file) {
    if (!isset($file) || $file['error'] !== 0) {
        return '';
    }

    $upload_dir = 'uploads/land_titles/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_extensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
    
    if (!in_array($file_extension, $allowed_extensions)) {
        throw new Exception("Invalid file type");
    }

    $new_filename = uniqid() . '_' . basename($file['name']);
    $target_path = $upload_dir . $new_filename;

    if (!move_uploaded_file($file['tmp_name'], $target_path)) {
        throw new Exception("Failed to upload file");
    }

    return $target_path;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = getPostValue('action');
        
        if ($action === 'create_project') {
            // Begin transaction
            $conn->begin_transaction();

            try {
                // Prepare project data
                $title = getPostValue('title');
                $description = getPostValue('description');
                $location = getPostValue('location');
                
                // Validate required fields
                if (empty($title) || empty($description) || empty($location)) {
                    throw new Exception("All fields are required");
                }

                // Handle land title document upload
                $land_title_document = isset($_FILES['land_title']) ? handleFileUpload($_FILES['land_title']) : '';

                // Insert project with correct enum values
                $stmt = $conn->prepare("
                    INSERT INTO projects (
                        builder_id, 
                        title, 
                        description, 
                        location, 
                        status,
                        land_title_document,
                        verification_status,
                        verification_fee_paid
                    ) VALUES (?, ?, ?, ?, 'pending', ?, 'unverified', FALSE)
                ");

                if (!$stmt) {
                    throw new Exception("Failed to prepare statement: " . $conn->error);
                }

                $stmt->bind_param(
                    "issss",
                    $user_id,
                    $title,
                    $description,
                    $location,
                    $land_title_document
                );

                if (!$stmt->execute()) {
                    throw new Exception("Failed to create project: " . $stmt->error);
                }

                // Commit transaction
                $conn->commit();
                $_SESSION['success_message'] = "Project created successfully";
                echo "<script>window.location.href = 'dashboard.php';</script>";
                exit();
                
            } catch (Exception $e) {
                // Rollback transaction on error
                $conn->rollback();
                throw $e;
            }
        }
        
        elseif ($action === 'verify_project') {
            $project_id = getPostValue('project_id');
            
            if (!$project_id) {
                throw new Exception("Project ID is required");
            }

            // Update project verification status
            $stmt = $conn->prepare("
                UPDATE projects 
                SET verification_status = 'unverified' 
                WHERE id = ? AND builder_id = ?
            ");

            if (!$stmt) {
                throw new Exception("Failed to prepare statement: " . $conn->error);
            }

            $stmt->bind_param("ii", $project_id, $user_id);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to update project: " . $stmt->error);
            }

            $_SESSION['success_message'] = "Project verification requested";
            echo "<script>window.location.href = 'dashboard.php';</script>";
            exit();
        }

    } catch (Exception $e) {
        error_log("Error in builder dashboard: " . $e->getMessage());
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
        echo "<script>window.location.href = 'dashboard.php';</script>";
        exit();
    }
}

// Fetch dashboard statistics with proper error handling
try {
    // Initialize variables
    $total_projects = $live_projects = $verified_projects = $featured_projects = 0;
    $recent_projects = $project_statuses = $investment_types = [];

    // Fetch total projects
    $stmt = $conn->prepare("
        SELECT COUNT(*) FROM projects WHERE builder_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $total_projects = $stmt->get_result()->fetch_row()[0];
    $stmt->close();

    // Fetch live projects
    $stmt = $conn->prepare("
        SELECT COUNT(*) FROM projects 
        WHERE builder_id = ? AND status = 'live'
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $live_projects = $stmt->get_result()->fetch_row()[0];
    $stmt->close();

    // Fetch verified projects
    $stmt = $conn->prepare("
        SELECT COUNT(*) FROM projects 
        WHERE builder_id = ? AND verification_status = 'verified'
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $verified_projects = $stmt->get_result()->fetch_row()[0];
    $stmt->close();

    // Fetch featured projects
    $stmt = $conn->prepare("
        SELECT COUNT(*) FROM featured_projects fp 
        JOIN projects p ON fp.project_id = p.id 
        WHERE p.builder_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $featured_projects = $stmt->get_result()->fetch_row()[0];
    $stmt->close();

    // Fetch recent projects with investment counts
    $stmt = $conn->prepare("
        SELECT 
            p.*, 
            COALESCE(COUNT(DISTINCT i.id), 0) as investment_count 
        FROM projects p 
        LEFT JOIN investments i ON p.id = i.project_id 
        WHERE p.builder_id = ? 
        GROUP BY p.id 
        ORDER BY p.id DESC 
        LIMIT 5
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $recent_projects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Fetch project statuses
    $stmt = $conn->prepare("
        SELECT 
            status, 
            COUNT(*) as count 
        FROM projects 
        WHERE builder_id = ? 
        GROUP BY status
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $project_statuses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Fetch investment types
    $stmt = $conn->prepare("
        SELECT 
            i.investment_type, 
            COUNT(*) as count 
        FROM investments i 
        JOIN projects p ON i.project_id = p.id 
        WHERE p.builder_id = ? 
        GROUP BY i.investment_type
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $investment_types = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

} catch (Exception $e) {
    error_log("Error fetching dashboard data: " . $e->getMessage());
    $_SESSION['error_message'] = "Error loading dashboard data";
}

ob_end_flush();
?>

<!-- Rest of your HTML code remains the same -->
<!-- Dashboard Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Developer Dashboard</h2>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newProjectModal">
        Create New Project
    </button>
</div>

<!-- Widgets Section -->
<div class="row mt-4">
    <div class="col-md-3 mb-4">
        <div class="card">
            <div class="card-body text-center">
                <h4>Total Projects</h4>
                <p class="display-4"><?php echo $total_projects; ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card">
            <div class="card-body text-center">
                <h4>Live Projects</h4>
                <p class="display-4"><?php echo $live_projects; ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card">
            <div class="card-body text-center">
                <h4>Verified Projects</h4>
                <p class="display-4"><?php echo $verified_projects; ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card">
            <div class="card-body text-center">
                <h4>Featured Projects</h4>
                <p class="display-4"><?php echo $featured_projects; ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Charts Section -->
<div class="row mt-4">
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Project Status Overview</h5>
                <?php if (empty($project_statuses)): ?>
                    <div class="alert alert-info">No project status data available</div>
                <?php else: ?>
                    <canvas id="projectStatusChart"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Investment Types</h5>
                <?php if (empty($investment_types)): ?>
                    <div class="alert alert-info">No investment data available</div>
                <?php else: ?>
                    <canvas id="investmentTypesChart"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Recent Projects Table -->
<div class="card mt-4">
    <div class="card-body">
        <h5 class="card-title">Recent Projects</h5>
        <?php if (empty($recent_projects)): ?>
            <div class="alert alert-info">
                No projects found. Click on "Create New Project" to get started!
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Project Title</th>
                            <th>Status</th>
                            <th>Verification Status</th>
                            <th>Location</th>
                            <th>Investments</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_projects as $project): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($project['title']); ?></td>
                            <td><?php echo ucfirst($project['status']); ?></td>
                            <td><?php echo ucfirst($project['verification_status']); ?></td>
                            <td><?php echo htmlspecialchars($project['location']); ?></td>
                            <td><?php echo $project['investment_count']; ?></td>
                            <td>
                                <button class="btn btn-primary btn-sm" onclick="editProject(<?php echo $project['id']; ?>)">Edit</button>
                                <button class="btn btn-success btn-sm" onclick="verifyProject(<?php echo $project['id']; ?>)">Verify</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modals -->
<div class="modal fade" id="newProjectModal" tabindex="-1" aria-labelledby="newProjectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="dashboard.php" method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="newProjectModalLabel">Create New Project</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_project">
                    <div class="mb-3">
                        <label for="title" class="form-label">Project Title</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="location" class="form-label">Location</label>
                        <input type="text" class="form-control" id="location" name="location" required>
                    </div>
                    <div class="mb-3">
                        <label for="investment_goal" class="form-label">Investment Goal</label>
                        <input type="number" class="form-control" id="investment_goal" name="investment_goal" required>
                    </div>
                    <div class="mb-3">
                        <label for="land_title" class="form-label">Land Title Document (optional)</label>
                        <input type="file" class="form-control" id="land_title" name="land_title">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Create Project</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success">
        <?php 
        echo $_SESSION['success_message']; 
        unset($_SESSION['success_message']);
        ?>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger">
        <?php 
        echo $_SESSION['error_message']; 
        unset($_SESSION['error_message']);
        ?>
    </div>
<?php endif; ?>

<!-- Chart.js Scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    <?php if (!empty($project_statuses)): ?>
    var ctx1 = document.getElementById('projectStatusChart').getContext('2d');
    var projectStatusChart = new Chart(ctx1, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($project_statuses, 'status')); ?>,
            datasets: [{
                label: 'Projects',
                data: <?php echo json_encode(array_column($project_statuses, 'count')); ?>,
                backgroundColor: '#FF6384',
                borderColor: '#FF6384',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            legend: {
                display: false
            },
            title: {
                display: true,
                text: 'Project Status Overview'
            },
            scales: {
                x: {
                    beginAtZero: true
                },
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    <?php endif; ?>

    <?php if (!empty($investment_types)): ?>
    var ctx2 = document.getElementById('investmentTypesChart').getContext('2d');
    var investmentTypesChart = new Chart(ctx2, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($investment_types, 'investment_type')); ?>,
            datasets: [{
                label: 'Investments',
                data: <?php echo json_encode(array_column($investment_types, 'count')); ?>,
                backgroundColor: '#36A2EB',
                borderColor: '#36A2EB',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            legend: {
                display: false
            },
            title: {
                display: true,
                text: 'Investment Types Distribution'
            },
            scales: {
                x: {
                    beginAtZero: true
                },
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    <?php endif; ?>

    // Functions for project actions
    function editProject(projectId) {
        // Redirect to the edit project page or show modal for editing
        window.location.href = 'edit_project.php?id=' + projectId;
    }

    function verifyProject(projectId) {
        if (confirm('Are you sure you want to verify this project?')) {
            // Send an AJAX request to verify the project
            fetch('dashboard.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'action=verify_project&project_id=' + projectId
            }).then(response => {
                if (response.ok) {
                    location.reload();
                } else {
                    alert('Failed to verify project');
                }
            });
        }
    }
</script>
