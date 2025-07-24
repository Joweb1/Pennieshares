<?php

require_once __DIR__ . '/../src/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
generateCsrfToken();

$message = '';
$error = '';

if (!isset($_SESSION['reset_email'])) {
    header("Location: forgot_password");
    exit;
}

$email = $_SESSION['reset_email'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verifyCsrfToken($_POST['csrf_token'] ?? '');

    if (isset($_POST['action']) && $_POST['action'] === 'resend_otp') {
        $user = getUserByEmail($pdo, $email);
        if ($user) {
            $otp = generateAndStoreOtp($pdo, $user['id']);
            if ($otp) {
                $data = [
                    'username' => $user['username'],
                    'otp_code' => $otp
                ];
                if (sendNotificationEmail('otp_email', $data, $email, 'Password Reset OTP')) {
                    $message = "A new OTP has been sent to your email.";
                } else {
                    $error = "Failed to resend OTP email. Please try again.";
                }
            } else {
                $error = "Failed to generate new OTP. Please try again.";
            }
        } else {
            $error = "User not found.";
        }
    }
} else {
    $otp_code = trim($_POST['otp_code']);

    if (empty($otp_code)) {
        $error = "Please enter the OTP code.";
    } else {
        $user = getUserByEmail($pdo, $email);
        if ($user) {
            if (verifyOtp($pdo, $user['id'], $otp_code)) {
                $_SESSION['otp_verified'] = true;
                $_SESSION['user_id_for_reset'] = $user['id'];
                header("Location: reset_password");
                exit;
            } else {
                $error = "Invalid or expired OTP code.";
            }
        } else {
            $error = "User not found.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
  <title>Verify OTP</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
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
    }

    .form-input {
      width: 100%;
      padding: 0.85rem 1rem;
      background: var(--input-bg);
      border: 1px solid var(--input-border);
      border-radius: 0.75rem;
      font-size: 1.2rem;
      color: var(--text-dark);
      transition: all 0.2s ease;
      text-align: center;
      letter-spacing: 0.5rem;
    }

    .form-input:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(37,99,235,0.15);
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

    .resend-container {
      text-align: center;
      margin-top: 1.5rem;
      font-size: 0.9rem;
      color: var(--text-light);
    }

    .resend-btn {
      color: var(--primary);
      font-weight: 600;
      text-decoration: none;
      background: none;
      border: none;
      cursor: pointer;
    }
    .resend-btn:disabled {
        color: var(--text-light);
        cursor: not-allowed;
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
    <img src="assets/images/logo.png" class="illustration" alt="OTP Illustration" />

    <h1 class="title">Verify Code</h1>
    <p class="subtitle">Enter the 6-digit code sent to your email.</p>

    <form id="verifyOtpForm" method="POST">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <?php if ($error): ?><p class="error-message"><?= htmlspecialchars($error) ?></p><?php endif; ?>
        <?php if ($message): ?><p class="success-message"><?= htmlspecialchars($message) ?></p><?php endif; ?>

      <div class="form-group">
        <input type="text" class="form-input" name="otp_code" id="otp_code" placeholder="_ _ _ _ _ _" maxlength="6" required />
      </div>

      <button class="action-btn" type="submit">Verify</button>
    </form>

    <div class="resend-container">
      Didn't receive a code? 
      <button type="button" id="resendOtpBtn" class="resend-btn" disabled>Resend</button>
      <span id="countdown"></span>
    </div>
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

    document.addEventListener('DOMContentLoaded', function() {
        let countdownElement = document.getElementById('countdown');
        let resendBtn = document.getElementById('resendOtpBtn');
        let timeLeft = 60;

        function startCountdown() {
            resendBtn.disabled = true;
            countdownElement.textContent = `(in ${timeLeft}s)`;

            let timer = setInterval(function() {
                timeLeft--;
                countdownElement.textContent = `(in ${timeLeft}s)`;

                if (timeLeft <= 0) {
                    clearInterval(timer);
                    resendBtn.disabled = false;
                    countdownElement.textContent = '';
                    timeLeft = 60; // Reset for next time
                }
            }, 1000);
        }

        resendBtn.addEventListener('click', function() {
            let form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';

            let actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'resend_otp';
            form.appendChild(actionInput);

            let csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = 'csrf_token';
            csrfInput.value = '<?= $_SESSION["csrf_token"] ?>';
            form.appendChild(csrfInput);

            document.body.appendChild(form);
            form.submit();
        });

        startCountdown();
    });
  </script>

</body>
</html>