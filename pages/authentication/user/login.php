<?php
// Include your database connection
include('../../../database/db.php');

// Initialize message variables for SweetAlert
$message = '';
$message_type = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and retrieve form inputs
    $email = htmlspecialchars(trim($_POST['email']));
    $password = $_POST['password'];
    $unique_code = htmlspecialchars(trim($_POST['id_code']));

    // Prepare a SQL query to get user details based on the entered email
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Fetch the user data
        $user = $result->fetch_assoc();
        $db_password = $user['password'];  // Hashed password stored in the DB
        $db_unique_code = $user['unique_code'];  // Unique code stored in the DB

        // Verify password and unique code
        if (password_verify($password, $db_password) && $unique_code == $db_unique_code) {
            // Login successful
            $message = "Login successful! Redirecting to your dashboard...";
            $message_type = 'success';

            // Start session and store the user ID
            session_start();
            $_SESSION['user_id'] = $user['id'];  // Store the user ID in session

            // Don't redirect immediately, use JavaScript later to redirect after SweetAlert
        } else {
            // Invalid password or unique code
            $message = "Invalid password or unique code!";
            $message_type = 'error';
        }
    } else {
        // Email not found in the database
        $message = "No account found with this email!";
        $message_type = 'error';
    }

    // Close the statement and connection
    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Page</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
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
            border-radius: 10px;
            max-width: 400px;
            width: 100%;
            box-shadow: 0px 8px 16px rgba(0, 0, 0, 0.3);
            position: relative;
            display: none; /* Initially hide the login container */
        }

        .login-container h2 {
            text-align: center;
            color: #333;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .login-container input {
            border-radius: 50px;
            padding: 12px 20px;
            border: 1px solid #ddd;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        .login-container input:focus {
            border-color: #6a11cb;
            box-shadow: 0 0 10px rgba(106, 17, 203, 0.3);
        }

        .login-container button {
            background: linear-gradient(135deg, #6a11cb, #021f50);
            color: white;
            border: none;
            padding: 12px;
            font-size: 1rem;
            border-radius: 50px;
            width: 100%;
            transition: background-color 0.3s;
        }

        .login-container button:hover {
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

        /* Hide loader when login form is visible */
        body.loaded .loader {
            display: none;
        }

        body.loaded .login-container {
            display: block;
        }
    </style>
</head>
<body>

    <!-- Loader -->
    <div class="loader">
        <span>Cobuild</span>
    </div>

    <!-- Login Form -->
    <div class="login-container">
        <img src="../../../images/Cobuild_logo.png" alt="">

        <h2>User Login</h2>
        <form action="" method="POST">
            <div class="mb-3">
                <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
            </div>
            <div class="mb-3">
                <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
            </div>
            <div class="mb-3">
                <input type="text" name="id_code" class="form-control" placeholder="Enter your ID code" required>
            </div>
            <button type="submit" class="btn btn-primary">Login</button>
        </form>
        <div class="footer-text">
            <p>Don't have an account? <a href="register.php">Register here</a></p>
        </div>
    </div>

    <script>
        // Simulate a loading time
        window.addEventListener('load', function() {
            setTimeout(() => {
                document.body.classList.add('loaded');
            }, 3000); // Delay for 3 seconds to display the loader animation
        });

        // Show SweetAlert for any login messages
        var message = "<?php echo isset($message) ? $message : ''; ?>";
        var messageType = "<?php echo isset($message_type) ? $message_type : ''; ?>";

        if (message && messageType === 'success') {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: message,
                showConfirmButton: false,
                timer: 2000  // Display the alert for 2 seconds
            }).then(() => {
                // Show loader and navigate to the dashboard after a short delay
                document.body.classList.remove('loaded'); // Show the loader again
                setTimeout(() => {
                    window.location.href = "dashboardtest.php";  // Navigate to the dashboard after the loader
                }, 2000);  // 2-second delay before navigating to the dashboard
            });
        } else if (message) {
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: message
            });
        }
    </script>

</body>
</html>
