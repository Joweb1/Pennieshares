<?php
require_once __DIR__ . '/../src/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
verify_auth();

generateCsrfToken();

$errors = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verifyCsrfToken($_POST['csrf_token'] ?? '');
    
    $requiredFields = ['fullname', 'email', 'username', 'phone', 'password'];
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            $errors = "All fields are required";
            break;
        }
    }
    
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    if (!$email) {
        $errors = "Invalid email format";
    }
    
    if (!empty($_POST['referral'])) {
        if (!preg_match('/^[a-zA-Z]{2}\d{5}$/', $_POST['referral'])) {
            $errors = "Invalid partner code format";
        } elseif (!validatePartnerCode($pdo, $_POST['referral'])) {
            $errors = "Invalid referral partner code";
        }
    }
    
    if (empty($errors) && (strlen($_POST['username']) < 4 || strlen($_POST['username']) > 20)) {
        $errors = "Username must be between 4 and 20 characters.";
    }

    // Check for username uniqueness
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$_POST['username']]);
        if ($stmt->fetchColumn() > 0) {
            $errors = "Username is already taken.";
        }
    }
    
    if (strlen($_POST['password']) < 8) {
        $errors = "Password must be at least 8 characters";
    }
    
    if ($errors == '') {
        $existingUser = getUserByEmail($pdo, $email);
        if (!$existingUser) {
            $success = registerUser(
                $pdo,
                $_POST['fullname'],
                $email,
                $_POST['username'],
                $_POST['phone'],
                $_POST['referral'] ?? '',
                $_POST['password']
            );
            
            if ($success) {
                $_SESSION['success_message'] = "Registration successful! An OTP has been sent to your email for verification.";
                $_SESSION['reset_email'] = $email;
                $_SESSION['just_redirected'] = true;
                header("Location: verify_otp");
                exit;
            } else {
                $errors = "Registration failed. Please try again.";
            }
        } else {
            $errors = "Email already registered";
        }
    }
}
$partnercode = $_GET['partnercode'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
  <title>Sign Up</title>
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

    .signup-card {
      background: var(--card-bg);
      width: 100%;
      max-width: 400px;
      padding: 2rem;
      border-radius: 1.5rem;
      box-shadow: 0 20px 40px rgba(0,0,0,0.05);
      text-align: center;
      position: relative;
      transition: background-color 0.3s;
    }

    .signup-card::before {
      content: "";
      position: absolute;
      top: -25px;
      right: -25px;
      width: 100px;
      height: 100px;
      background: rgba(37,99,235,0.1);
      border-radius: 50%;
      z-index: -1;
    }

    .signup-card::after {
      content: "";
      position: absolute;
      bottom: -20px;
      left: -20px;
      width: 100px;
      height: 100px;
      background: rgba(59,130,246,0.05);
      border-radius: 50%;
      z-index: -1;
    }

    .illustration {
      width: 90px;
      height: auto;
      margin: 0 auto 1rem;
    }

    .signup-title {
      font-size: 1.8rem;
      font-weight: 700;
      color: var(--text-dark);
      margin-bottom: 0.5rem;
    }

    .signup-subtitle {
      font-size: 0.95rem;
      color: var(--text-light);
      margin-bottom: 1.5rem;
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

    .checkbox-container {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      margin: 1rem 0;
      font-size: 0.85rem;
      color: var(--text-light);
    }

    .checkbox-container a {
      color: var(--primary);
      text-decoration: none;
      font-weight: 500;
    }

    .signup-btn {
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
    }

    .signup-btn:hover {
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
    .error-message {
        color: var(--error-color);
        font-size: 0.875rem;
        margin-bottom: 1rem;
        text-align: center;
    }
  </style>
</head>
<body>

  <div class="signup-card">
    <img src="<?= BASE_URL ?>/assets/images/logo.png" class="illustration" alt="Signup Illustration" />

    <h1 class="signup-title">Create Account</h1>
    <p class="signup-subtitle">Fill in your details to get started</p>

    <form method="POST" onsubmit="return validateForm()">
      <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
      
      <?php if ($errors): ?>
        <p class="error-message"><?= htmlspecialchars($errors) ?></p>
      <?php endif; ?>
      <p class="error-message" id="error"></p>

      <div class="form-group">
        <input type="text" class="form-input" name="username" id="username" placeholder="Username" required />
      </div>
      <div class="form-group">
        <input type="text" class="form-input" name="fullname" id="name" placeholder="Full Name" required />
      </div>
      <div class="form-group">
        <input type="email" class="form-input" name="email" id="email" placeholder="Email Address" required />
      </div>
      <div class="form-group">
        <input type="tel" class="form-input" name="phone" id="phone" placeholder="Phone Number" required />
      </div>
      <div class="form-group">
        <input type="text" class="form-input" name="referral" id="referral" placeholder="Partner Code (Optional)" value="<?= htmlspecialchars($partnercode) ?>" pattern="[a-zA-Z]{2}\d{5}" title="Format: 2 letters followed by 5 digits (e.g. ab12345)"/>
      </div>
      <div class="form-group">
        <input type="password" class="form-input" name="password" id="password" placeholder="Password" required />
        <i class="fas fa-eye-slash password-reveal-icon" onclick="togglePasswordVisibility('password', this)"></i>
      </div>
      <div class="form-group">
        <input type="password" class="form-input" name="password_confirm" id="confirmPassword" placeholder="Confirm Password" required />
        <i class="fas fa-eye-slash password-reveal-icon" onclick="togglePasswordVisibility('confirmPassword', this)"></i>
      </div>

      <div class="checkbox-container">
        <input type="checkbox" id="terms" name="terms" required/>
        <label for="terms">I agree to the <a href="terms">Terms & Conditions</a></label>
      </div>

      <button class="signup-btn" type="submit">Register</button>
    </form>

    <div class="login-link">
      Already have an account? <a href="login">Login</a>
    </div>
  </div>
  <script type="text/javascript">
    (function() {
        const savedTheme = localStorage.getItem('theme');
        const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
        if (savedTheme) {
            document.documentElement.setAttribute('data-theme', savedTheme);
        } else if (prefersDark) {
            document.documentElement.setAttribute('data-theme', 'dark');
        }
    })();

    function validateForm() {
        const username = document.getElementById("username").value.trim();
        const name = document.getElementById("name").value.trim();
        const email = document.getElementById("email").value.trim();
        const phone = document.getElementById("phone").value.trim();
        const password = document.getElementById("password").value.trim();
        const confirmPassword = document.getElementById("confirmPassword").value.trim();
        const terms = document.getElementById("terms");
        const error = document.getElementById("error");

        error.textContent = ''; // Clear previous errors

        if (name === "" || username === "" || email === "" || phone === "" || password === "" || confirmPassword === "") {
            error.textContent = "Please fill in all required fields.";
            return false;
        }

        if (username.length < 4 || username.length > 20) {
            error.textContent = "Username must be between 4 and 20 characters.";
            return false;
        }

        const emailRegex = /^[\S@]+@[\S@]+\.[\S@]+$/;
        if (!emailRegex.test(email)) {
            error.textContent = "Please enter a valid email address.";
            return false;
        }
        
        if (password.length < 8) {
            error.textContent = "Password must be at least 8 characters long.";
            return false;
        }

        if (password !== confirmPassword) {
            error.textContent = "Passwords do not match. Please try again.";
            return false;
        }

        if (!terms.checked) {
            error.textContent = "Please agree to the terms and conditions.";
            return false;
        }

        return true;
    }

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