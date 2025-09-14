<?php
// Start session (for authentication handling)
session_start();

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/src/functions.php';

// Process any pending profits
processPendingProfits($pdo);

$request_uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

// Get page from query parameter if available
if (isset($_GET['page'])) {
    $request_uri = $_GET['page'];
} else {
    // If no route is provided, default to home
    if ($request_uri == '' || $request_uri == 'index.php') {
        $request_uri = 'home';
    }
}

// Define available pages
    $pages = ['home', 'login', 'register', 'profile', 'profile_view', 'profile_edit', 'find_broker', 'forgot_password', 'verify_otp', 'reset_password', 'logout', 'delete_account', 'payment', 'admin_verify', 'testup', 'about','faqs', 'idcard', 'assets', 'admin', 'market', 'buy_shares', 'transfer', 'transactions', 'wallet', 'shares', 'loading', 'partner', 'settings', 'api/generate_transaction_history', 'kyc', 'admin_kyc', 'broker_verify', 'verify_registration_otp', 'save-subscription', 'terms', 'download', 'broker_application', 'paystack_payment', 'payment_success', 'payment_failure'];

// Retrieve query parameters safely
$partnercode = $_GET['partnercode'] ?? NULL; // Example: ?token=abc123

// Debugging: Print token (Remove in production
// Check if the requested page exists
if (in_array($request_uri, $pages)) {
    if ($request_uri === 'api/generate_transaction_history') {
        require "pages/api/generate_transaction_history.php";
    } else {
        // Include the page while keeping query parameters available
        require "pages/$request_uri.php";
    }
} else {
    // Send a 404 header and display an error page
    http_response_code(404);
    require 'pages/404.php'; // Custom error page
}

?>
