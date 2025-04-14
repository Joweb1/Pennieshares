<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../src/functions.php';


if (!isset($_GET['token'])) {
    die("Invalid request.");
}

$token = $_GET['token'];
$user = getUserByResetToken($token);

if (!$user || time() > $user['reset_expires']) {
    die("Reset link is invalid or has expired.");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_password     = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    if ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        if (updatePassword($user['id'], $new_password)) {
            clearResetToken($user['id']);
            $message = "Password updated successfully. You can now <a href='login'>login</a>.";
        } else {
            $error = "Failed to update password.";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Reset Password</h1>
    <?php
    if (isset($error)) {
        echo "<p style='color:red;'>$error</p>";
    }
    if (isset($message)) {
        echo "<p style='color:green;'>$message</p>";
    }
    if (!isset($message)):
    ?>
    <form method="POST">
        <input type="password" name="new_password" placeholder="New Password" required><br>
        <input type="password" name="confirm_password" placeholder="Confirm New Password" required><br>
        <button type="submit">Reset Password</button>
    </form>
    <?php endif; ?>
    <p>
        <a href="login">Back to Login</a>
    </p>
</body>
</html>tml>tml>