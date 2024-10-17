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
                    } else {
                        $message = "Registration successful, but email could not be sent.";
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
        $mail->Body    = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; }
                .container { background-color: #f4f4f4; padding: 20px; border-radius: 8px; }
                h1 { color: #0044cc; }
                p { color: #333; }
                .code { background-color: #0044cc; color: white; padding: 10px; border-radius: 4px; display: inline-block; }
            </style>
        </head>
        <body>
            <div class='container'>
               <img src='../../../images/Cobuild_logo.png' alt='' class='img-fluid w-100 h-25 d-flex justify-cente'>
                <h1>Welcome to Cobuild!</h1>
                <p>Thank you for registering with Cobuild. Here is your unique code:</p>
                <p class='code'>$unique_code</p>
                <p>Use this code to log in and complete any payments.Please do not share this Unique Code.</p>
            </div>
        </body>
        </html>
        ";
        $mail->AltBody = 'Thank you for registering with Cobuild. Your unique code: ' . $unique_code;

        $mail->send();
        return true;
    } catch (Exception $e) {
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
            border-radius: 10px;
            max-width: 450px;
            width: 100%;
            box-shadow: 0px 8px 16px rgba(0, 0, 0, 0.3);
            position: relative;
        }

        .register-container h2 {
            text-align: center;
            color: #333;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .register-container input, .register-container select {
            border-radius: 50px;
            padding: 12px 20px;
            border: 1px solid #ddd;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        .register-container input:focus, .register-container select:focus {
            border-color: #6a11cb;
            box-shadow: 0 0 10px rgba(106, 17, 203, 0.3);
        }

        .register-container button {
            background: linear-gradient(135deg, #6a11cb, #021f50);
            color: white;
            border: none;
            padding: 12px;
            font-size: 1rem;
            border-radius: 50px;
            width: 100%;
            transition: background-color 0.3s;
        }

        .register-container button:hover {
            background-color: #5a0fc7;
        }

        .footer-text {
            text-align: center;
            color: #555;
        }

        .footer-text a {
            color: #6a11cb;
            text-decoration: none;
        }

        .footer-text a:hover {
            text-decoration: underline;
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
    <div class="register-container text-center">
    <img src="../../../images/Cobuild_logo.png" alt="" class="img-fluid w-100 h-25 d-flex justify-center">

        <h2>User Registration</h2>
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
            <div class="mb-2">
                <input type="text" name="name" class="form-control" placeholder="Full Name" required>
            </div>
            <div class="mb-2">
                <input type="email" name="email" class="form-control" placeholder="Email Address" required>
            </div>
            <div class="mb-2">
                <input type="password" name="password" class="form-control" placeholder="Password" required>
            </div>
            <div class="mb-2">
                <input type="password" name="confirm_password" class="form-control" placeholder="Repeat Password" required>
            </div>
            <div class="mb-2">
                <input type="tel" name="phone_number" class="form-control" placeholder="Phone Number" required>
            </div>
            <div class="mb-2">
                <select name="user_type" class="form-control" required>
                    <option value="" disabled selected>Select Type</option>
                    <option value="builder">Builder</option>
                    <option value="investor">Investor</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Register</button>
        </form>

        <div class="footer-text">
            <p>Already have an account? <a href="login.php">Login here</a></p>
        </div>
    </div>

    <!-- SweetAlert2 Logic -->
    <script>
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
