<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../src/functions.php';


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $user = getUserByEmail($email);
    if ($user) {
        // Generate reset token valid for 1 hour (3600 seconds)
        $token = generateResetToken();
        $expires = time() + 3600;
        if (setResetToken($user['id'], $token, $expires)) {
            // For demonstration, we output the reset link.
            // In production, you would email this link to the user.
            $resetLink = "http://localhost:8000/reset_password?token=" . $token;
            $message = "Password reset link: <a href='$resetLink'>$resetLink</a>";
        } else {
            $error = "Could not generate reset token.";
        }
    } else {
        $error = "Email not found.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Forgot Password</h1>
    <?php
    if (isset($error)) {
        echo "<p style='color:red;'>$error</p>";
    }
    if (isset($message)) {
        echo "<p style='color:green;'>$message</p>";
    }
    ?>
    <form method="POST">
        <input type="email" name="email" placeholder="Enter your registered email" required><br>
        <button type="submit">Send Reset Link</button>
    </form>
    <p>
        <a href="login">Back to Login</a>
    </p>
</body>
</html>tml>tml>