<?php
// Start session (for authentication handling)
session_start();

$request_uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

// If no route is provided, default to home
if ($request_uri == '' || $request_uri == 'index.php') {
    $request_uri = 'home';
}

// Define available pages
$pages = ['home', 'login', 'register', 'dashboard', 'profile', 'profile_view', 'profile_edit', 'find_broker', 'forgot_password', 'reset_password', 'logout', 'delete_account', 'payment', 'admin_verify', 'testup', 'about','stages','faqs', 'idcard', 'assets', 'admin', 'market', 'buy_shares', 'transfer', 'transactions', 'wallet', 'shares', 'loading', 'partner', 'settings', 'api/generate_transaction_history'];

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
