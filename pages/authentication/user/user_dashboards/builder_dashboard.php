<?php
ob_start();
if (!defined('MAIN_DASHBOARD')) {
    die('Direct access not permitted');
}
require_once __DIR__ . '/../../../../vendor/autoload.php';


$dotenv = Dotenv\Dotenv::createImmutable('../../../.');

$dotenv->load();
$env = parse_ini_file( __DIR__ . '/../../../../.env');
function get_env($key){
  global $env;
  return $env[$key];
} 
require 'paystack_config.php';

function initializePaystackPayment($email, $project_id, $amount = null) {
    global $PAYSTACK_CONFIG;
    
    $amount = $amount ?? $PAYSTACK_CONFIG['verification_fee'];
    
    $fields = [
        'email' => $email,
        'amount' => $amount,
        'callback_url' => $PAYSTACK_CONFIG['callback_url'],
        'metadata' => [
            'project_id' => $project_id,
            'payment_type' => 'verification_fee'
        ]
    ];

    $ch = curl_init("https://api.paystack.co/transaction/initialize");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($fields),
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer " . $PAYSTACK_CONFIG['secret_key'],
            "Content-Type: application/json"
        ]
    ]);
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['status' => false, 'message' => $error];
    }
    
    return json_decode($response, true);
}

function verifyPaystackPayment($reference) {
    global $PAYSTACK_CONFIG;
    
    $ch = curl_init("https://api.paystack.co/transaction/verify/" . rawurlencode($reference));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer " . $PAYSTACK_CONFIG['secret_key'],
            "Content-Type: application/json"
        ]
    ]);
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['status' => false, 'message' => $error];
    }
    
    return json_decode($response, true);
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
$stmt = $conn->prepare("SELECT user_type FROM users WHERE id = ? AND user_type = 'Developer'");
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
                $investment_goal = getPostValue('investment_goal');  // Make sure investment goal is captured

                
                // Validate required fields
                if (empty($title) || empty($description) || empty($location)|| empty($investment_goal)) {
                    throw new Exception("All fields are required, including investment goal");
                }

                   // Ensure land title document is provided
        if (!isset($_FILES['land_title']) || $_FILES['land_title']['error'] !== 0) {
            throw new Exception("Land title document is required");
        }
                // Handle land title document upload
                $land_title_document = isset($_FILES['land_title']) ? handleFileUpload($_FILES['land_title']) : '';

                $stmt = $conn->prepare("
                INSERT INTO projects (
                    builder_id, 
                    title, 
                    description, 
                    location,
                    investment_goal,
                    status,
                    land_title_document,
                    verification_status,
                    verification_fee_paid,
                    current_stage
                ) VALUES (?, ?, ?, ?, ?, 'pending', ?, 'unverified', FALSE, 'planning')
            ");
                if (!$stmt) {
                    throw new Exception("Failed to prepare statement: " . $conn->error);
                }

                $stmt->bind_param(
                    "isssss",
                    $user_id,
                    $title,
                    $description,
                    $location,
                    $investment_goal,
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
// Modify the verify_project action handler to ensure proper JSON response
if ($action === 'verify_project') {
    // Ensure we're sending JSON response
    header('Content-Type: application/json');
    
    // Clear any existing output
    ob_clean();
    
    $project_id = getPostValue('project_id');
    
    if (!$project_id) {
        throw new Exception('Project ID is required');
    }

    // Verify project ownership
    $stmt = $conn->prepare("SELECT * FROM projects WHERE id = ? AND builder_id = ?");
    $stmt->bind_param("ii", $project_id, $user_id);
    $stmt->execute();
    $project = $stmt->get_result()->fetch_assoc();
    
    if (!$project) {
        throw new Exception('Project not found or unauthorized');
    }

    // Get user email for payment
    $stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    // Initialize Paystack payment
    $payment = initializePaystackPayment($user['email'], $project_id);
    
    if (!$payment['status']) {
        throw new Exception('Failed to initialize payment: ' . ($payment['message'] ?? 'Unknown error'));
    }

    echo json_encode([
        'status' => true,
        'message' => 'Payment initialized successfully',
        'authorization_url' => $payment['data']['authorization_url']
    ]);
    exit;
}

// ... rest of your existing action handlers ...

} catch (Exception $e) {
if ($action === 'verify_project') {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => false,
        'message' => $e->getMessage()
    ]);
    exit;
} else {
    $_SESSION['error_message'] = "Error: " . $e->getMessage();
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
$paystackPublicKey = $PAYSTACK_CONFIG['public_key'];
ob_end_flush();
?>
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
<!-- information -->
 <marquee behavior="" direction="">
    <h5>Please Note that verification fee is Fifty Thousand Naira Only(50,000)</h5>
 </marquee>

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
                                <button class="btn btn-danger btn-sm" onclick="deleteProject(<?php echo $project['id']; ?>)">Delete</button>
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
 <!-- Edit Project Modal -->
<div class="modal fade" id="editProjectModal" tabindex="-1" aria-labelledby="editProjectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editProjectModalLabel">Edit Project</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editProjectForm">
                    <input type="hidden" id="edit_project_id" name="project_id">
                    <input type="hidden" name="action" value="edit_project">
                    <div class="mb-3">
                        <label for="edit_title" class="form-label">Project Title</label>
                        <input type="text" class="form-control" id="edit_title" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="edit_location" class="form-label">Location</label>
                        <input type="text" class="form-control" id="edit_location" name="location" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_investment_goal" class="form-label">Investment Goal</label>
                        <input type="number" class="form-control" id="edit_investment_goal" name="investment_goal" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="saveProjectChanges()">Save Changes</button>
            </div>
        </div>
    </div>
</div>
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
                        <label for="land_title" class="form-label">Land Title Document (Required)</label>
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
<?php
require 'builder_logic.php';