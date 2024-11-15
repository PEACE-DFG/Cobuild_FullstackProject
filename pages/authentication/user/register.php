<?php
// Include database connection and PHPMailer
include('../../../database/db.php');
require '../../../vendor/autoload.php';  // Include Composer autoload for PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Initialize message variables
$message = '';
$message_type = '';

// Function to sanitize inputs
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Registration form data
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $phone_number = sanitize($_POST['phone_number']);
    $user_type = sanitize($_POST['user_type']);

    // Check if passwords match
    if ($password !== $confirm_password) {
        $message = "Passwords do not match!";
        $message_type = 'error';  // Error type for SweetAlert
    } else {
        // Secure password hashing
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        // Generate unique 4-digit code
        $unique_code = rand(1000, 9999);  // Generates a random 4-digit number

        // Check if the email already exists in the database
        $check_email = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $check_email->bind_param("s", $email);
        $check_email->execute();
        $result = $check_email->get_result();

        if ($result->num_rows > 0) {
            $message = "Email already exists!";
            $message_type = 'error';
        } else {
            // Prepare statement for inserting a new user
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, phone_number, user_type, unique_code) VALUES (?, ?, ?, ?, ?, ?)");

            // Check if the statement was successfully prepared
            if ($stmt) {
                $stmt->bind_param("ssssss", $name, $email, $hashed_password, $phone_number, $user_type, $unique_code);

                // Execute the statement
                if ($stmt->execute()) {
                    // Send the unique code via email using PHPMailer
                    if (sendEmail($email, $unique_code)) {
                        $message = "Registration successful! Check your email for the unique code.";
                        $message_type = 'success';
                    }  else {
                        // If email fails, delete user entry
                        $delete_user = $conn->prepare("DELETE FROM users WHERE email = ?");
                        $delete_user->bind_param("s", $email);
                        $delete_user->execute();

                        $message = "Registration failed because the email could not be sent Due to Network Issue. Please try again.";
                        $message_type = 'error';
                    }
                } else {
                    $message = "There was an error during registration. Please try again later.";
                    $message_type = 'error';
                }

                // Close the statement
                $stmt->close();
            } else {
                // Statement preparation failed
                $message = "There was an error preparing the statement. Please contact support.";
                $message_type = 'error';
            }
        }

        // Close the email check statement
        $check_email->close();
    }

    // Close the database connection
    $conn->close();
}
// Function to send email using PHPMailer
function sendEmail($to, $unique_code) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';  // Use your SMTP server
        $mail->SMTPAuth   = true;
        $mail->Username   = 'Officialcobuild@gmail.com';  // Your email
        $mail->Password   = 'udodjurhumdfrsim';     // Your App Password or SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('Officialcobuild@gmail.com', 'Cobuild');
        $mail->addAddress($to);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Your Cobuild Unique Code';
        // Create HTML email body
        $mail->addEmbeddedImage('../../../images/Cobuild_logo.png', 'logo_img'); // Local path and CID

        $mail->Body = "
            <div style='max-width: 600px; margin: 0 auto; font-family: Arial, sans-serif;'border:1px solid transparent;box-shadow:5px 5px 3px grey;>
                <div style='background: blue; padding: 20px; text-align: center;'>
                    <img src='cid:logo_img' alt='Cobuild' style='max-width: 200px;'>
                </div>
                
                <div style='background-color: #ffffff; padding: 30px; border-radius: 5px; margin-top: 20px;'>
                    <h2 style='color: #4b0397; text-align: center;'>Welcome to Cobuild!</h2>
                    
                    <p style='color: #666; font-size: 16px; line-height: 1.6; text-align: center;'>
                        Thank you for registering with Cobuild. Here is your unique verification code:
                    </p>
                    
                    <div style='background: blue; color: white; border:2px solid transparent;
                              padding: 15px; margin: 20px 0; text-align: center; font-size: 24px; 
                              border-radius: 5px;'>
                        $unique_code
                    </div>
                    
                    <p style='color: #666; font-size: 14px; text-align: center;'>
                        Please use this code to login and complete any payments.<br>
                        <strong>Important:</strong> Do not share this code with anyone.
                    </p>
                </div>
                
                <div style='text-align: center; margin-top: 20px; color: #666; font-size: 12px;'>
                    <p>&copy; " . date('Y') . " Cobuild. All rights reserved.</p>
                </div>
            </div>
        ";
        $mail->AltBody = 'Thank you for registering with Cobuild. Your unique code: ' . $unique_code;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email error: " . $mail->ErrorInfo);
        return false;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Page</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">

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

        .register-container {
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

        /* Hide loader when register form is visible */
        body.loaded .loader {
            display: none;
        }

        body.loaded .register-container {
            display: block;
        } 

        /* Add some responsive behavior */
        @media (max-width: 768px) {
            .register-container {
                padding: 30px;
            }
        }

        @media (max-width: 576px) {
            .register-container {
                padding: 20px;
            }

            .register-container h2 {
                font-size: 1.5rem;
            }

            .register-container button {
                padding: 10px;
                font-size: 0.9rem;
            }
        }
         
    </style>
</head>
<body>

 <!-- Loader -->
 <div class="loader">
        <span>Cobuild</span>
    </div>

    <div class="register-container text-center">
    <div class="logo-container">
            <img src="../../../images/Cobuild_logo.png" alt="Cobuild Logo">
        </div>
        <h2>User Registration</h2>
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
           <div class="row">
           <div class="col mb-2">
                <input type="text" name="name" class="form-control" placeholder="Full Name" required>
            </div>
            <div class="col mb-2">
                <input type="email" name="email" class="form-control" placeholder="Email Address" required>
            </div>
           </div>
          <div class="row">
          <div class="col mb-2">
                <input type="password" name="password" class="form-control" placeholder="Password" required>
            </div>
            <div class=" col mb-2">
                <input type="password" name="confirm_password" class="form-control" placeholder="Repeat Password" required>
            </div>
          </div>
        <div class="row">
        <div class="mb-2">
                <input type="tel" name="phone_number" class="form-control" placeholder="Phone Number" required>
            </div>
            <div class="mb-2">
                <select name="user_type" class="form-control" required>
                    <option value="" disabled selected>Select Type</option>
                    <option value="developer">Developer</option>
                    <option value="investor">Investor</option>
                </select>
            </div>
        </div>
            <button type="submit" class="btn btn-primary">Register</button>
        </form>

        <div class="footer-text">
            <p>Already have an account? <a href="login.php">Login here</a></p>
        </div>
    </div>

    <!-- SweetAlert2 Logic -->
    <script>
            // Simulate a loading time
    window.addEventListener('load', function() {
        setTimeout(() => {
            document.body.classList.add('loaded');
        }, 3000); // Delay for 3 seconds to display the loader animation
    });

        var message = "<?php echo isset($message) ? $message : ''; ?>";
        var messageType = "<?php echo isset($message_type) ? $message_type : ''; ?>";

        if (messageType === 'success') {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: message,
                showConfirmButton: false,
                timer: 2000  // 2 seconds delay for the alert
            }).then(() => {
                window.location.href = "login.php";  // Redirect to login page after success
            });
        } else if (message) {
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: message
            });
        }
        
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
