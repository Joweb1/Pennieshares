<?php

require_once __DIR__ . '/../src/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
    generateCsrfToken(); // Generate new CSRF token for each form
}

verify_auth();
generateCsrfToken();

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    verifyCsrfToken($_POST['csrf_token'] ?? '');

    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields";
    } else {
        $user = loginUser($email, $password);
        if ($user) {
            session_regenerate_id(true); // Prevent session fixation
            $_SESSION['user'] = $user;
            header("Location: dashboard");
            exit;
        } else {
            $error = "Invalid email or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login Page</title>
  <!-- Font Awesome (CDN) -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
  <!-- Link to your CSS -->
  <link rel="stylesheet" href="style.css" />
  <style type="text/css">
  /* Basic reset and font styling */
  @import url('https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&display=swap');

  * {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
  font-family:"Roboto", sans-serif;
  }
  
  body {
  font-family: "Roboto", sans-serif;
  background-color: #ffffff;
  color: #000000;
  }
  
  /* Container for the login form */
  .login-container {
  width: 350px;
  max-width: 90%;
  margin: 60px auto;
  text-align: center;
  border: 1px solid rgba(215,215,255,0.2);
  padding: 30px 20px;
  border-radius: 50px;
  box-shadow: 0 0 10px rgba(0,0,0,0.1);
  }
  
  /* Logo circle (the "P" in a circle) */
  .logo-circle {
  width: 60px;
  height: 60px;
  margin: 0 auto 20px;
  border-radius: 50%;
  background-color: #001970; /* Dark Blue or your preferred color */
  display: flex;
  align-items: center;
  justify-content: center;
  }
  
  .logo-img {
  width: 80px;
  height:auto;
  }
  /* Headings */
  .login-container h1 {
  font-size: 28px;
  font-style:normal;
  color: #001970; /* Dark Blue color */
  padding: 5px 0;
  }
  
  .login-container h2 {
  font-size: 25px;
  letter-spacing:.1px;
  color: #001970;
  font-weight:600;
  margin-bottom: 50px;
  }
  
  .login-container h3 {
  font-size: 18px;
  color: #001970;
  margin-bottom: 20px;
  text-transform:none;
  }
  
  /* Form styling */
  form {
  width: 100%;
  text-align: left;
  }
  
  /* Input groups */
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
  border-radius: 50px;
  font-size: 14px;
  }
  
  /* Forgot password link */
  .forgot-password-container {
  text-align: right;
  margin-bottom: 20px;
  }
  
  .forgot-password {
  color: red;
  text-decoration: none;
  font-size: 14px;
  }
  
  .forgot-password:hover {
  text-decoration: underline;
  }
  
  /* Login button */
  .login-btn {
  width: 100%;
  background-color: #001970; /* Dark Blue */
  color: #ffffff;
  padding: 12px;
  border: none;
  border-radius: 50px;
  font-size: 16px;
  cursor: pointer;
  text-transform: uppercase;
  }
  
  .login-btn:hover {
  background-color: #0c2da1; /* Slightly lighter/darker shade */
  }
  .error {
  	color:red;
  	font-size:14px;
  }
  .login {
  margin:20px 5px;
  font-size:14px;
  }
  </style>
</head>
<body>

  <div class="login-container">
    <!-- Logo or "P" icon -->
    <div class="logo-circle">
      <img class="logo-img" src="assets/images/logo.png" >
    </div>

    <!-- Headings -->
    <h1>Welcome</h1>
    <h2>Sign in</h2>

    <!-- Login Form -->
    <form id="loginForm" method="POST" onsubmit="return validateForm()">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>" />
    <p class="error" >
    <?php if ($error): 
    echo $error;
    endif; ?>
    </p>
    <p class="error" id="error" ></p>
      <div class="input-group">
        <div class="input-wrapper">
          <i class="fas fa-envelope"></i>
          <input type="email" id="email" name="email" placeholder="e.g example@gmail.com" />
        </div>
      </div>

      <div class="input-group">
        <div class="input-wrapper">
          <i class="fas fa-lock"></i>
          <input
            type="password"
            id="password"
            name="password"
            placeholder="••••••••"
          />
        </div>
      </div>

      <div class="forgot-password-container">
        <a href="forgot_password" class="forgot-password">Forgot password?</a>
      </div>
      <p class="login" > Don't have an account? 
      	<a href="register" >Register</a>
      </p>
      <button type="submit" class="login-btn">Login</button>
    </form>
  </div>

  <!-- JavaScript file -->
  <script type="text/javascript">
  	function validateForm() {
  	const email = document.getElementById("email").value.trim();
  	const password = document.getElementById("password").value.trim();
  	const error = document.getElementById("error");
  	
  	// Simple validation checks
  	if (email === "" && password === "") {
  	//alert("Please enter your credentials.");
  	error.innerHTML = "Please enter your credentials"
  	return false; // Prevent form submission
  	}
  	
  	if (email === "") {
  	//alert("Please enter your email.");
  	error.innerHTML = "Please enter your email"
  	return false; // Prevent form submission
  	}
  	
  	if (password === "") {
  	//alert("Please enter your password.");
  	error.innerHTML = "Please enter your password"
  	return false; // Prevent form submission
  	}
 	
  	// If everything is filled in, allow form submission
  	return true;
  	}
  
  </script>
</body>
</html>