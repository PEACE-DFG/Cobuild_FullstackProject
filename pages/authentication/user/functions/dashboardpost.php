<?php
require'../../../../database/db.php';
session_start();
$env = parse_ini_file( __DIR__ . '/../../../../.env');
function get_env($key){
  global $env;
  return $env[$key];
} 
require '../user_dashboards/paystack_config.php';

$user_id = $_SESSION['user_id'];

// Function to safely get POST values
function getPostValue($key, $default = '') {
  return isset($_POST[$key]) ? trim($_POST[$key]) : $default;
}

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