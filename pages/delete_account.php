<?php
if(session_status() === PHP_SESSION_NONE) {
   session_start();
}
require_once __DIR__ . '/../src/functions.php';
check_auth(); // Ensure user is logged in

// Validate CSRF Token
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF Token Mismatch. Request denied.");
    }

    // Get Logged-in User's ID
    $user_id = $_SESSION['user']['id'];

    // Delete User from Database
    deleteUser($user_id);
} else {
    header("HTTP/1.1 403 Forbidden");
}
?>
