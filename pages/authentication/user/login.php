<?php
// Include your database connection and PHPMailer
include('../../../database/db.php');
require '../../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Function to generate random reset code
function generateResetCode($length = 8) {
    return bin2hex(random_bytes($length));
}

// Function to send email using PHPMailer
function sendResetEmail($to, $resetCode) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';  // Use your SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = 'Officialcobuild@gmail.com';  // Your email
        $mail->Password = 'udodjurhumdfrsim'; // Your App Password or SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        // Recipients
        $mail->setFrom('support@cobuild.com', 'Cobuild Support');
        $mail->addAddress($to);
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset - Cobuild';
        $mail->addEmbeddedImage('../../../images/Cobuild_logo.png', 'logo_img'); // Local path and CID

        $mail->Body = "
        <html>
        <head>
            <style>
                body {
                    font-family: Arial, sans-serif; 
                    line-height: 1.6; 
                    color: #333; 
                    max-width: 600px; 
                    margin: 0 auto; 
                    padding: 20px;
                }
                h2 {
                    color: #4b0397;
                }
                .reset-code {
                    background-color: #f4f4f4; 
                    padding: 15px; 
                    margin: 20px 0; 
                    text-align: center;
                }
                .code {
                    color: #031c46; 
                    margin: 0;
                }
                hr {
                    border: 1px solid #eee; 
                    margin: 20px 0;
                }
                p.footer {
                    font-size: 12px; 
                    color: #666;
                }
            </style>
        </head>
        <body>
           <div style='background: blue; padding: 20px; text-align: center;'>
                    <img src='cid:logo_img' alt='Cobuild' style='max-width: 200px;'>
                </div>
            <h2>Password Reset Request</h2>
            <p>You have requested to reset your password. Please use the following code to complete your password reset:</p>
            <div class='reset-code'>
                <h3 class='code'>{$resetCode}</h3>
            </div>
            <p>This code will expire in 1 hour for security purposes. Also use the same generated unique code, which was sent to your mail upon registeration to login to login.    </p>
            <p>If you didn't request this reset, please ignore this email or contact support if you have concerns.</p>
            <hr>
            <p class='footer'>This is an automated email, please do not reply.</p>
        </body>
        </html>";

        return $mail->send();
    } catch (Exception $e) {
        error_log("Mail Error: {$mail->ErrorInfo}");
        return false;
    }
}

// Handle password reset request
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action'])) {
    if ($_POST['action'] === 'request_reset') {
        try {
            $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid email address');
            }

            // Check if email exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                throw new Exception('Email not found in our records');
            }

            // Generate and store reset code
            $resetCode = generateResetCode();
            $expiryTime = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            $updateStmt = $conn->prepare("UPDATE users SET reset_code = ?, reset_code_expiry = ? WHERE email = ?");
            $updateStmt->bind_param("sss", $resetCode, $expiryTime, $email);
            
            if (!$updateStmt->execute()) {
                throw new Exception('Failed to process reset request');
            }

            // Send reset email
            if (!sendResetEmail($email, $resetCode)) {
                throw new Exception('Failed to send reset email');
            }

            // Successful response
            echo json_encode(['status' => 'success', 'message' => 'Reset instructions sent to your email']);
            exit;

        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            exit;
        }
    }

    // Password update
    if ($_POST['action'] === 'update_password') {
        try {
            $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
            $resetCode = trim($_POST['code']);
            $password = $_POST['password'];

            if (strlen($password) < 8) {
                throw new Exception('Password must be at least 8 characters long');
            }

            // Verify reset code
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND reset_code = ? AND reset_code_expiry > NOW()");
            $stmt->bind_param("ss", $email, $resetCode);
            $stmt->execute();
            
            if ($stmt->get_result()->num_rows === 0) {
                throw new Exception('Invalid or expired reset code');
            }

            // Update password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $updateStmt = $conn->prepare("UPDATE users SET password = ?, reset_code = NULL, reset_code_expiry = NULL WHERE email = ?");
            $updateStmt->bind_param("ss", $hashedPassword, $email);
            
            if (!$updateStmt->execute()) {
                throw new Exception('Failed to update password');
            }

            echo json_encode(['status' => 'success', 'message' => 'Password updated successfully']);
            exit;

        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            exit;
        }
    }
}

// Handle regular login
$message = '';
$message_type = '';

if ($_SERVER["REQUEST_METHOD"] === "POST" && !isset($_POST['action'])) {
    try {
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'];
        $uniqueCode = trim($_POST['id_code']);

        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            throw new Exception("No account found with this email!");
        }

        $user = $result->fetch_assoc();

        if (!password_verify($password, $user['password']) || $uniqueCode !== $user['unique_code']) {
            throw new Exception("Invalid credentials!");
        }

        // Login successful
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        
        $message = "Login successful! Redirecting to your dashboard...";
        $message_type = 'success';

    } catch (Exception $e) {
        $message = $e->getMessage();
        $message_type = 'error';
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Cobuild</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body, html {
            height: 100%;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, #4b0397, #031c46);
            font-family: 'Poppins', sans-serif;
            overflow: hidden;
        }

        .login-container {
            background-color: white;
            padding: 40px;
            border-radius: 15px;
            max-width: 400px;
            width: 100%;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            position: relative;
            display: none;
            animation: fadeIn 0.5s ease-out forwards;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .logo-container {
            text-align: center;
            margin-bottom: 25px;
        }

        .logo-container img {
            max-width: 180px;
            height: auto;
        }

        h2 {
            color: #333;
            font-weight: 600;
            text-align: center;
            margin-bottom: 30px;
        }

        .form-control {
            border-radius: 50px;
            padding: 12px 20px;
            border: 1px solid #ddd;
            transition: all 0.3s ease;
            margin-bottom: 15px;
        }

        .form-control:focus {
            border-color: #4b0397;
            box-shadow: 0 0 0 0.2rem rgba(75, 3, 151, 0.25);
        }

        .btn-primary {
            background: linear-gradient(135deg, #4b0397, #031c46);
            border: none;
            border-radius: 50px;
            padding: 12px;
            font-weight: 500;
            width: 100%;
            margin-top: 10px;
            transition: transform 0.2s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(75, 3, 151, 0.3);
        }

        .footer-text {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }

        .footer-text a {
            color: #4b0397;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .footer-text a:hover {
            color: #031c46;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background: white;
            margin: 50px auto;
            padding: 30px;
            border-radius: 15px;
            max-width: 400px;
            width: 90%;
            position: relative;
            animation: modalSlide 0.3s ease-out;
        }

        @keyframes modalSlide {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .close {
            position: absolute;
            right: 20px;
            top: 15px;
            font-size: 24px;
            cursor: pointer;
            color: #666;
            transition: color 0.3s ease;
        }

        .close:hover {
            color: #4b0397;
        }

        .modal-step {
            display: none;
        }

        .modal-step.active {
            display: block;
        }

         /* Loader Styles */
         .loader {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #fff;
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 100;
        }

        .loader span {
            font-size: 2.5rem;
            font-weight: bold;
            color: #6a11cb;
            border-right: 2px solid rgba(106, 17, 203, 0.8);
            white-space: nowrap;
            overflow: hidden;
            width: 0;
            animation: typing 3s steps(10) forwards, blink 0.75s step-end infinite;
        }

        @keyframes typing {
            from { width: 0; }
            to { width: 8ch; } /* Adjust according to the length of the text "Cobuild" */
        }

        @keyframes blink {
            from, to { border-color: transparent; }
             50% { border-color: rgba(106, 17, 203, 0.8); }
        }

        /* Password strength indicator */
        .password-strength {
            height: 5px;
            margin-top: -10px;
            margin-bottom: 15px;
            border-radius: 2px;
            transition: all 0.3s ease;
        }

        .strength-weak { background: #ff4444; width: 33.33%; }
        .strength-medium { background: #ffbb33; width: 66.66%; }
        .strength-strong { background: #00C851; width: 100%; }
    </style>
</head>
<body>
    <!-- Loader -->
    <div class="loader">
        <span>Cobuild</span>
    </div>

    <!-- Login Container -->
    <div class="login-container">
        <div class="logo-container">
            <img src="../../../images/Cobuild_logo.png" alt="Cobuild Logo">
        </div>

        <h2>Welcome Back</h2>
        
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
            <div class="mb-3">
                <input type="email" name="email" class="form-control" placeholder="Email address" required>
            </div>
            <div class="mb-3">
                <input type="password" name="password" class="form-control" placeholder="Password" required>
            </div>
            <div class="mb-3">
                <input type="text" name="id_code" class="form-control" placeholder="ID Code" required>
            </div>
            <button type="submit" class="btn btn-primary">Sign In</button>
        </form>

        <div class="footer-text">
            <p>Don't have an account? <a href="register.php">Register here</a></p>
            <p>Forgot password? <a href="#" id="forgotPasswordLink" style="color:orange">Reset here</a></p>
        </div>
    </div>

    <!-- Password Reset Modal -->
    <div id="resetModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            
            <!-- Step 1: Email Entry -->
            <div class="modal-step active" id="step1">
                <h3>Reset Your Password</h3>
                <p>Enter your email address to receive a reset code.</p>
                <div class="mb-3">
                    <input type="email" id="resetEmail" class="form-control" placeholder="Email address" required>
                </div>
                <button onclick="requestReset()" class="btn btn-primary">Send Reset Code</button>
            </div>
            
            <!-- Step 2: Code & New Password -->
            <div class="modal-step" id="step2">
                <h3>Create New Password</h3>
                <p>Enter the code sent to your email and choose a new password.</p>
                <div class="mb-3">
                <input type="text" id="resetCode" class="form-control" placeholder="Reset Code" required>
                </div>
                <div class="mb-3">
                    <input type="password" id="newPassword" class="form-control" placeholder="New Password" required>
                    <div class="password-strength"></div>
                </div>
                <div class="mb-3">
                    <input type="password" id="confirmPassword" class="form-control" placeholder="Confirm Password" required>
                </div>
                <button onclick="updatePassword()" class="btn btn-primary">Update Password</button>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="position-fixed top-0 end-0 p-3" style="z-index: 1500">
        <div id="liveToast" class="toast hide" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <strong class="me-auto">Notification</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body"></div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener("DOMContentLoaded", function () {
        // Get the modal and trigger elements
        const resetModal = document.getElementById("resetModal");
        const forgotPasswordLink = document.getElementById("forgotPasswordLink");
        const closeBtn = resetModal.querySelector(".close");

        // Show the modal when clicking "Forgot password"
        forgotPasswordLink.addEventListener("click", function (event) {
            event.preventDefault(); // Prevent default link behavior
            resetModal.style.display = "block"; // Show the modal
        });

        // Hide the modal when clicking the close button
        closeBtn.addEventListener("click", function () {
            resetModal.style.display = "none"; // Hide the modal
        });

        // Hide modal when clicking outside of the modal content
        window.addEventListener("click", function (event) {
            if (event.target === resetModal) {
                resetModal.style.display = "none";
            }
        });
    });

    // Show loader for 1.5 seconds then fade out
    window.addEventListener('load', () => {
        setTimeout(() => {
            document.querySelector('.loader').style.display = 'none';
            document.querySelector('.login-container').style.display = 'block';
        }, 1500);
    });

    // Toast notification function with Bootstrap 5
    function showToast(message, type = 'info') {
        const toastLiveExample = document.getElementById('liveToast');
        const toastBootstrap = bootstrap.Toast.getOrCreateInstance(toastLiveExample);
        const toastBody = toastLiveExample.querySelector('.toast-body');

        // Set toast color based on type
        toastLiveExample.className = `toast align-items-center text-white bg-${type} border-0`;
        toastBody.textContent = message;

        toastBootstrap.show();
    }

    // Handle PHP messages with SweetAlert2
    <?php if ($message): ?>
        document.addEventListener('DOMContentLoaded', () => {
            <?php if ($message_type === 'success'): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: '<?php echo $message; ?>',
                    timer: 2000,
                    showConfirmButton: false,
                    timerProgressBar: true
                }).then(() => {
                    window.location.href = 'dashboard.php';
                });
            <?php else: ?>
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: '<?php echo $message; ?>',
                    confirmButtonColor: '#4b0397'
                });
            <?php endif; ?>
        });
    <?php endif; ?>

    // Request password reset
    function requestReset() {
        const email = document.getElementById('resetEmail').value;

        if (!email) {
            showToast('Please enter your email address', 'danger');
            return;
        }

        // Show loading state
        Swal.fire({
            title: 'Sending Reset Code',
            text: 'Please wait...',
            allowOutsideClick: false,
            showConfirmButton: false,
            willOpen: () => {
                Swal.showLoading();
            }
        });

        // Create a FormData object to send the email
        const formData = new FormData();
        formData.append('action', 'request_reset');
        formData.append('email', email);

        // Use fetch to send the form data
        fetch(window.location.href, {
            method: 'POST',
            body: formData // No need to set Content-Type; the browser will do it automatically
        })
        .then(response => {
            if (response.ok) {
                return response.text(); // Get the raw response text
            } else {
                throw new Error('Network response was not ok: ' + response.statusText);
            }
        })
        .then(data => {
            // Check if the response indicates success
            if (data.includes('Reset instructions sent to your email')) {
                Swal.fire({
                    icon: 'success',
                    title: 'Reset Code Sent!',
                    text: 'Please check your email for the reset code.',
                    confirmButtonColor: '#4b0397'
                });
                document.getElementById('step1').classList.remove('active');
                document.getElementById('step2').classList.add('active');
            } else {
                throw new Error(data); // If not success, throw the response as error
            }
        })
        .catch(error => {
            showToast(error.message || 'Failed to send reset code', 'danger');
            Swal.close();
        });
    }

    // Update password
    function updatePassword() {
        const email = document.getElementById('resetEmail').value;
        const code = document.getElementById('resetCode').value;
        const password = document.getElementById('newPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;

        // Validation
        if (!email || !code || !password || !confirmPassword) {
            showToast('Please fill in all fields', 'danger');
            return;
        }

        if (password !== confirmPassword) {
            showToast('Passwords do not match', 'danger');
            return;
        }

        if (password.length < 8) {
            showToast('Password must be at least 8 characters long', 'danger');
            return;
        }

        // Show loading state
        Swal.fire({
            title: 'Updating Password',
            text: 'Please wait...',
            allowOutsideClick: false,
            showConfirmButton: false,
            willOpen: () => {
                Swal.showLoading();
            }
        });

        // Create FormData for updating password
        const formData = new FormData();
        formData.append('action', 'update_password');
        formData.append('email', email);
        formData.append('code', code);
        formData.append('password', password);

        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (response.ok) {
                return response.text(); // Get the raw response text
            } else {
                throw new Error('Network response was not ok: ' + response.statusText);
            }
        })
        .then(data => {
            if (data.includes('Password updated successfully')) {
                Swal.fire({
                    icon: 'success',
                    title: 'Password Updated!',
                    text: 'You can now log in with your new password.',
                    confirmButtonColor: '#4b0397',
                    timer: 2000,
                    timerProgressBar: true,
                    showConfirmButton: false
                }).then(() => {
                    resetModal.style.display = 'none'; // Close the modal
                    resetModalToStep1(); // Reset the modal to step 1
                });
            } else {
                throw new Error(data); // If not success, throw the response as error
            }
        })
        .catch(error => {
            showToast(error.message || 'Failed to update password', 'danger');
            Swal.close();
        });
    }
</script>

</body>
</html>