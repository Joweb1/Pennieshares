<?php
require_once __DIR__ . '/../src/init.php';

// Admin Access Check
if (!isset($_SESSION['user']) || empty($_SESSION['user']['is_admin'])) {
    header("HTTP/1.1 403 Forbidden");
    exit("Access Denied: You do not have administrative privileges.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    try {
        if ($action === 'pause') {
            $pdo->exec("UPDATE users SET earnings_paused = 1");
            $_SESSION['action_message'] = 'All users\' earnings have been paused.';
        } elseif ($action === 'resume') {
            $pdo->exec("UPDATE users SET earnings_paused = 0");
            $_SESSION['action_message'] = 'All users\' earnings have been resumed.';
        }
    } catch (PDOException $e) {
        error_log("Error updating all users earnings: " . $e->getMessage());
        $_SESSION['action_message'] = 'Error: Could not update all users\' earnings.';
    }
}

header('Location: /admin');
exit();
?>
