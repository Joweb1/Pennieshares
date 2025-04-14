<?php
require_once __DIR__ . '/../src/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
    generateCsrfToken();
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
    // Add these validations
    if (!empty($_POST['referral'])) {
    if (!preg_match('/^[a-zA-Z]{2}\d{5}$/', $_POST['referral'])) {
    $errors = "Invalid partner code format";
    } elseif (!validatePartnerCode($_POST['referral'])) {
    $errors = "Invalid referral partner code";
    }
    }
    
    // Validate username format
    if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $_POST['username'])) {
    $errors = "Username must be 3-20 characters (letters, numbers, underscores)";
    }
    
    if (strlen($_POST['password']) < 8) {
        $errors = "Password must be at least 8 characters";
    }
    
    if ($errors == '') {
        $existingUser = getUserByEmail($email);
        if (!$existingUser) {
            $success = registerUser(
                $_POST['fullname'],
                $email,
                $_POST['username'],
                $_POST['phone'],
                $_POST['referral'] ?? '',
                $_POST['password']
            );
            
            if ($success) {
                header("Location: login");
                exit;
            } else {
                $errors = "Registration failed. Please try again.";
            }
        } else {
            $errors = "Email already registered";
        }
    }
}
?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Register Page</title>
    
    <!-- Font Awesome (CDN) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Link to CSS file -->
    <style type="text/css">
    /* Basic reset */
    * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    }
    
    /* Body styling */
    body {
    font-family: Arial, sans-serif;
    background-color: #ffffff;
    color: #000000;
    }
    
    /* Container for the registration form */
    .container {
    width: 400px;
    max-width: 90%;
    margin: 40px auto;
    border: 1px solid #ddd;
    padding: 30px 20px;
    border-radius: 8px;
    position: relative; /* allows us to position the logo circle in the corner */
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    
    /* Logo circle (the "P" in a circle), positioned top-right */
    .logo-circle {
    position: absolute;
    top: 30px;
    right: 30px;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background-color: #001970; /* Dark Blue */
    display: flex;
    align-items: center;
    justify-content: center;
    }
    
    .logo-img {
    width: 58px;
    height:auto;
    }
    
    /* Header text */
    .container h1 {
    font-size: 25px;
    text-align: left;
    color: #001970; /* Dark Blue */
    line-height: 1.4;
    margin-bottom: 10px;
    font-weight:400;
    }
    
    .container h1 strong {
    font-weight: 800;
    font-size:25px;
    }
    
    .container h2 {
    text-align: center;
    color: red;
    font-size: 24px;
    margin: 20px 0;
    background: linear-gradient(180deg, 
    rgba(175,14,0,1),  /* Darkish gold */
    rgba(220,20,40,1), /* Classic gold */
    rgba(125,58,0,1) 60% /* Darkish gold */
    );
    background-size: 100%;
    
    /* Use background clip to show gradient through the text */
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    color: transparent;
    }
    
    /* Form styling */
    form {
    margin-top: 20px;
    }
    
    .input-group {
    margin-bottom: 20px;
    }
    
    .input-group label {
    display: block;
    font-weight: bold;
    margin-bottom: 5px;
    color: #000000;
    }
    
    /* Wrapping icon + input together */
    .input-wrapper {
    position: relative;
    }
    
    .input-wrapper i {
    position: absolute;
    top: 50%;
    left: 10px;
    transform: translateY(-50%);
    color: #001970;
    }
    
    .input-wrapper input {
    width: 100%;
    padding: 10px 10px 10px 35px; 
    border: 1px solid #001970;
    border-radius: 4px;
    font-size: 14px;
    }
    
    /* Terms & Conditions */
    .terms-container {
    display: flex;
    align-items: center;
    margin-bottom: 20px;
    }
    
    .terms-label {
    margin-left: 5px;
    font-size: 12px;
    font-weight:600;
    }
    
    /* Register button */
    .register-btn {
    width: 100%;
    background-color: #001970; /* Dark Blue */
    color: #ffffff;
    padding: 12px;
    border: none;
    border-radius: 4px;
    font-size: 16px;
    font-weight:600;
    cursor: pointer;
    text-transform: uppercase;
    }
    
    .register-btn:hover {
    background-color: #0c2da1; /* Slightly lighter/darker shade */
    }
    .login {
    	margin:20px 5px;
    	font-size:14px;
    }
    </style>
    </head>
    <body>
    <div class="container">
    <!-- Logo circle in the top-right corner -->
    <div class="logo-circle">
    <img class="logo-img" src="assets/images/logo.png" >
    </div>
    
    <!-- Main header text -->
    <h1><strong>Partner</strong> <br /> with <strong>Penniepoint</strong> <br /> as an <strong>Analyst</strong></h1>
    
    <!-- Register heading -->
    <h2>REGISTER</h2>
    
    <!-- Registration form -->
    <form id="registerForm" onsubmit="return validateForm()" method="POST" >
    <!-- Name -->
    <p id="error" style="color:red;"  ></p><?php if (isset($errors)) echo "<p style='color:red;'>$errors</p>"; ?>
    <div class="input-group">
    <label for="name">Username</label>
    <div class="input-wrapper">
    <i class="fas fa-user"></i>
    <input 
    type="text" 
    id="username" 
    name="username" 
    placeholder="Enter username" 
    />
    </div>
    </div>
    <div class="input-group">
    <label for="name">Full Name</label>
    <div class="input-wrapper">
    <i class="fas fa-user-plus"></i>
    <input 
    type="text" 
    id="name" 
    name="fullname" 
    placeholder="Enter your full name" 
    />
    </div>
    </div>
    
    <!-- Email -->
    <div class="input-group">
    <label for="email">Email</label>
    <div class="input-wrapper">
    <i class="fas fa-envelope"></i>
    <input 
    type="email" 
    id="email" 
    name="email" 
    placeholder="Enter your email" 
    />
    </div>
    </div>
    
    <div class="input-group">
    <label for="phone">Phone</label>
    <div class="input-wrapper">
    <i class="fas fa-phone"></i>
    <input 
    type="tel" 
    id="phone" 
    name="phone" 
    placeholder="Enter phone number" 
    />
    </div>
    </div>
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
    <div class="input-group">
    <label for="referral">Referral Partner Code</label>
    <div class="input-wrapper">
    <i class="fas fa-users"></i>
    <input 
    type="text" 
    id="referral" 
    name="referral" 
    value="<?= $referral ?>" 
    placeholder="Enter referral code" 
    required="required" 
    pattern="[a-zA-Z]{2}\d{5}" 
    title="Format: 2 letters followed by 5 digits (e.g. ab12345)"
    />
    </div>
    </div>
    
    <!-- Password -->
    <div class="input-group">
    <label for="password">Password</label>
    <div class="input-wrapper">
    <i class="fas fa-lock"></i>
    <input 
    type="password" 
    id="password" 
    name="password" 
    placeholder="Enter your password" 
    />
    </div>
    </div>
    
    <!-- Confirm Password -->
    <div class="input-group">
    <label for="confirmPassword">Confirm Password</label>
    <div class="input-wrapper">
    <i class="fas fa-lock"></i>
    <input 
    type="password" 
    id="confirmPassword" 
    name="password_confirm" 
    placeholder="Confirm your password" 
    />
    </div>
    </div>
    
    <!-- Terms & Conditions -->
    <div class="terms-container">
    <input 
    type="checkbox" 
    id="terms" 
    name="terms" 
    />
    <label for="terms" class="terms-label">
    Read and click to agree to <a href="#" >terms and conditions</a>
    </label>
    </div>
    
    <!-- Register button -->
    <button type="submit" class="register-btn">Register</button>
    </form>
    <p class="login" > Already have an account? 
    	<a href="login" >Login</a>
    </p>
    </div>
    
    <!-- Link to JavaScript file -->
    <script type="text/javascript">
    function validateForm() {
    const username = document.getElementById("username").value.trim();
    const name = document.getElementById("name").value.trim();
    const email = document.getElementById("email").value.trim();
    const phone = document.getElementById("phone").value.trim();
    const referral = document.getElementById("referral").value.trim();
    const password = document.getElementById("password").value.trim();
    const confirmPassword = document.getElementById("confirmPassword").value.trim();
    const terms = document.getElementById("terms");
    const errors = document.getElementById("error");
    
    // Check if name is empty
    if (name === "") {
    errors.innerHTML = "Please enter your name.";
    return false;
    }
    
    if (username === "") {
    errors.innerHTML = "Please enter your username.";
    return false;
    }
    
    // Check if email is empty
    if (email === "") {
    errors.innerHTML = "Please enter your email.";
    return false;
    }
    
    if (referral === "") {
    errors.innerHTML = "Please enter your referral partner code.";
    return false;
    }
    
    if (phone === "") {
    errors.innerHTML = "Please enter your phone no.";
    return false;
    }
    
    // Simple email format check (not fully RFC compliant, but enough for demonstration)
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
    errors.innerHTML = "Please enter a valid email address.";
    return false;
    }
    
    // Check if password is empty
    if (password === "") {
    errors.innerHTML = "Please enter your password.";
    return false;
    }
    
    // Check if confirm password is empty
    if (confirmPassword === "") {
    errors.innerHTML = "Please confirm your password.";
    return false;
    }
    
    // Check if passwords match
    if (password !== confirmPassword) {
    errors.innerHTML = "Passwords do not match. Please try again.";
    return false;
    }
    
    // Check if terms & conditions are agreed to
    if (!terms.checked) {
    errors.innerHTML = "Please agree to the terms and conditions.";
    return false;
    }
    
    // If everything is valid, allow form submission
    return true;
    }
    
    </script>
    </body>
    </html>
