<?php
// Move Paystack config to a separate file or environment variables
$PAYSTACK_CONFIG = [
  'public_key' => getenv('PAYSTACK_PUBLIC_KEY'),
  'secret_key' => getenv('PAYSTACK_SECRET_KEY'),
  'verification_fee' => 5000, // Amount in kobo (₦50)
  'callback_url' => getenv('APP_URL') . "/dashboard.php" // Get from environment variable
];

?>