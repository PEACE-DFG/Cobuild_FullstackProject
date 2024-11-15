<?php
// Configuration file that should be created
// File: ../../../config/mail_config.php
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USERNAME', 'Officialcobuild@gmail.com');
define('SMTP_PASSWORD', 'udodjurhumdfrsim'); // Replace with actual password
define('SMTP_PORT', 587);
define('SMTP_FROM_EMAIL', 'Officialcobuild@gmail.com');
define('SMTP_FROM_NAME', 'Cobuild');
define('SITE_URL', 'https://your-actual-domain.com'); // Replace with actual domain

// Notification caller code
if (isset($project_id) && isset($project_category_id)) {
    $notificationResult = notifyInvestorsOfNewProject($project_id, $project_category_id);
    
    if ($notificationResult['success']) {
        // Show success message using Swal
        echo "<script>
            Swal.fire({
                title: 'Project Created Successfully!',
                html: '" . addslashes($notificationResult['message']) . "',
                icon: 'success',
                confirmButtonText: 'OK'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'dashboard.php';
                }
            });
        </script>";
    } else {
        // Show error message using Swal
        echo "<script>
            Swal.fire({
                title: 'Project Created',
                html: 'Project was created but there were some issues with notifications:<br>" . 
                      addslashes($notificationResult['message']) . "',
                icon: 'warning',
                confirmButtonText: 'OK'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'dashboard.php';
                }
            });
        </script>";
    }
} else {
    // Show error for missing parameters
    echo "<script>
        Swal.fire({
            title: 'Error',
            text: 'Required project information is missing. Please try again.',
            icon: 'error',
            confirmButtonText: 'OK'
        });
    </script>";
    error_log("Notification attempt failed: Missing project_id or project_category_id");
}