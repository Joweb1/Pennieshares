<?php

require_once __DIR__ . '/../src/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
generateCsrfToken();

$message = '';
$error = '';

if (!isset($_SESSION['otp_verified']) || !$_SESSION['otp_verified'] || !isset($_SESSION['user_id_for_reset'])) {
    header("Location: forgot_password");
    exit;
}

$userId = $_SESSION['user_id_for_reset'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verifyCsrfToken($_POST['csrf_token'] ?? '');

    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    if (empty($newPassword) || empty($confirmPassword)) {
        $error = "Please fill in all fields.";
    } elseif (strlen($newPassword) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif ($newPassword !== $confirmPassword) {
        $error = "Passwords do not match.";
    } else {
        if (resetUserPassword($pdo, $userId, $newPassword)) {
            $message = "Your password has been reset successfully. You can now log in.";
            unset($_SESSION['reset_email']);
            unset($_SESSION['otp_verified']);
            unset($_SESSION['user_id_for_reset']);
            header("Refresh: 3; URL=login");
        } else {
            $error = "Failed to reset password. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
  <title>Reset Password</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <style>
    :root {
      --primary: #2563eb;
      --primary-hover: #1e40af;
      --text-dark: #1f2937;
      --text-light: #6b7280;
      --input-bg: #fff;
      --input-border: #e5e7eb;
      --card-bg: #fff;
      --page-bg: #f9fafb;
      --error-color: #ef4444;
      --success-color: #22c55e;
    }

    html[data-theme='dark'] {
        --primary: #3b82f6;
        --primary-hover: #60a5fa;
        --text-dark: #f9fafb;
        --text-light: #9ca3af;
        --input-bg: #1f2937;
        --input-border: #374151;
        --card-bg: #111827;
        --page-bg: #0d141c;
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: 'Inter', sans-serif;
      background: var(--page-bg);
      color: var(--text-dark);
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 1rem;
      transition: background-color 0.3s, color 0.3s;
    }

    .card {
      background: var(--card-bg);
      width: 100%;
      max-width: 380px;
      padding: 2rem;
      border-radius: 1.5rem;
      box-shadow: 0 20px 40px rgba(0,0,0,0.05);
      text-align: center;
      position: relative;
      transition: background-color 0.3s;
    }

    .card::before {
      content: "";
      position: absolute;
      top: -30px;
      right: -30px;
      width: 100px;
      height: 100px;
      background: rgba(37,99,235,0.08);
      border-radius: 50%;
      z-index: -1;
    }

    .card::after {
      content: "";
      position: absolute;
      bottom: -30px;
      left: -30px;
      width: 80px;
      height: 80px;
      background: rgba(59,130,246,0.05);
      border-radius: 50%;
      z-index: -1;
    }

    .illustration {
      width: 90px;
      height: auto;
      margin: 0 auto 1rem;
    }

    .title {
      font-size: 1.8rem;
      font-weight: 700;
      color: var(--text-dark);
      margin-bottom: 0.5rem;
    }

    .subtitle {
      font-size: 0.95rem;
      color: var(--text-light);
      margin-bottom: 2rem;
    }

    form {
      text-align: left;
    }

    .form-group {
      margin-bottom: 1rem;
      position: relative;
    }

    .form-input {
      width: 100%;
      padding: 0.85rem 2.5rem 0.85rem 1rem;
      background: var(--input-bg);
      border: 1px solid var(--input-border);
      border-radius: 0.75rem;
      font-size: 0.95rem;
      color: var(--text-dark);
      transition: all 0.2s ease;
    }

    .form-input:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(37,99,235,0.15);
    }
    
    .password-reveal-icon {
        position: absolute;
        top: 50%;
        right: 1rem;
        transform: translateY(-50%);
        cursor: pointer;
        color: var(--text-light);
        opacity: 0.7;
        transition: opacity 0.2s;
    }
    .password-reveal-icon:hover {
        opacity: 1;
    }

    .action-btn {
      width: 100%;
      background: var(--primary);
      color: #fff;
      padding: 0.85rem;
      border: none;
      border-radius: 0.75rem;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.3s ease;
      margin-top: 1rem;
    }

    .action-btn:hover {
      background: var(--primary-hover);
    }

    .login-link {
      text-align: center;
      margin-top: 1.5rem;
      font-size: 0.9rem;
      color: var(--text-light);
    }

    .login-link a {
      color: var(--primary);
      font-weight: 600;
      text-decoration: none;
    }
    .error-message, .success-message {
        font-size: 0.875rem;
        margin-bottom: 1rem;
        text-align: center;
    }
    .error-message { color: var(--error-color); }
    .success-message { color: var(--success-color); }
  </style>
</head>
<body>

  <div class="card">
    <img src="<?= BASE_URL ?>/assets/images/logo.png" class="illustration" alt="Reset Password Illustration" />

    <h1 class="title">Set New Password</h1>
    <p class="subtitle">Your new password must be different from previous ones.</p>

    <?php if ($message): ?>
        <p class="success-message"><?= htmlspecialchars($message) ?></p>
        <div class="login-link">
            <a href="login">Proceed to Login</a>
        </div>
    <?php else: ?>
        <form id="resetPasswordForm" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <?php if ($error): ?><p class="error-message"><?= htmlspecialchars($error) ?></p><?php endif; ?>

          <div class="form-group">
            <input type="password" class="form-input" name="new_password" id="new_password" placeholder="New Password" required />
            <i class="fas fa-eye-slash password-reveal-icon" onclick="togglePasswordVisibility('new_password', this)"></i>
          </div>
          <div class="form-group">
            <input type="password" class="form-input" name="confirm_password" id="confirm_password" placeholder="Confirm New Password" required />
            <i class="fas fa-eye-slash password-reveal-icon" onclick="togglePasswordVisibility('confirm_password', this)"></i>
          </div>

          <button class="action-btn" type="submit">Reset Password</button>
        </form>
    <?php endif; ?>
  </div>

  <script>
      (function() {
          const savedTheme = localStorage.getItem('theme');
          const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
          if (savedTheme) {
              document.documentElement.setAttribute('data-theme', savedTheme);
          } else if (prefersDark) {
              document.documentElement.setAttribute('data-theme', 'dark');
          }
      })();

      function togglePasswordVisibility(inputId, icon) {
          const input = document.getElementById(inputId);
          if (input.type === "password") {
              input.type = "text";
              icon.classList.remove("fa-eye-slash");
              icon.classList.add("fa-eye");
          } else {
              input.type = "password";
              icon.classList.remove("fa-eye");
              icon.classList.add("fa-eye-slash");
          }
      }
  </script>

</body>
</html>