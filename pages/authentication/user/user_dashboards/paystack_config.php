<?php
$PAYSTACK_CONFIG = [
  'public_key' => get_env('PAYSTACK_PUBLIC_KEY'),
  'secret_key' => get_env('PAYSTACK_SECRET_KEY'),
  'verification_fee' => 5000000, // Amount in kobo (₦50)
  'callback_url' => get_env('APP_URL' ??'http://localhost/Cobuild_FrontEnd-master/pages/authentication/user') . "/dashboard.php" // Get from environment variable
];

?>