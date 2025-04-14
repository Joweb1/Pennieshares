<?php
require_once __DIR__ . '/../src/functions.php';
check_auth();

$user = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>About Penniepoint</title>
  <!-- Font Awesome (CDN) -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
  * {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
  }
  
  body {
  font-family: System, sans-serif;
  background-color: #f5f5f5;
  color: #000;
  }
  .up-circle {
  	top:0;
  	left:0;
  	position:absolute;
  	background: linear-gradient(135deg, rgba(0,0,250,1) 0%, rgba(2,0,102,1) 100%);
  	height:40vh;
  	width:130vw;
  	transform:translateX(-30vw);
  	z-index:-90;
  	border-bottom-left-radius: 90% 40%;
  	border-bottom-right-radius: 100% 100%;
  }
  .cont {
  overflow-x:hidden;
  width:100vw;
  height:100vh;
  }
  
  /* HEADER (TOP SECTION) */
  .header-container {
  /* Blue gradient background 
  background: linear-gradient(135deg, #0D47A1 0%, #2962FF 100%);*/
  padding-top: 50px;
  position: relative;
  border-bottom-left-radius: 50% 10%;
  border-bottom-right-radius: 50% 10%;
  }
  
  /* Top row with username and icons */
  .header-top {
  display: flex;
  justify-content: space-around;
  align-items: center;
  color:white;
  }
  .dashboard {
  	display:flex;
  	color:white;
  	background: linear-gradient(135deg, rgba(87,71,255,0.61), rgba(190,28,99,0.61) , rgba(2,0,102,0.61) );
  	margin:30px 0px;
  	padding:0;
  	margin-bottom:5px;
  	justify-content:space-around;
  	align-items:center;
  	border-radius:3px;
  	box-shadow: 0 0px 20px rgba(0,0,0,0.21);
  }
  
  /* Apply this class to the element containing your text */
  .gradient-gold {
  /* Create a linear gradient with multiple gold tones */
  background: linear-gradient(130deg, 
  rgba(175,148,0,1),  /* Darkish gold */
  rgba(220,200,40,1), /* Classic gold */
  rgba(238,223,48,1), /* Bright, shining gold */
  rgba(234,140,5,1),  /* Warm, reflective gold */
  rgba(125,58,0,1),  /* Darkish gold */
  rgba(220,200,40,1), /* Classic gold */
  rgba(238,223,48,1), /* Bright, shining gold */
  rgba(234,140,5,1),  /* Warm, reflective gold */
  rgba(125,58,0,1),  /* Darkish gold */
  rgba(220,200,40,1), /* Classic gold */
  rgba(238,223,48,1), /* Bright, shining gold */
  rgba(234,140,5,1),  /* Warm, reflective gold */
  rgba(125,58,0,1),  /* Darkish gold */
  rgba(220,200,40,1), /* Classic gold */
  rgba(238,223,48,1), /* Bright, shining gold */
  rgba(234,140,5,1),  /* Warm, reflective gold */
  rgba(125,58,0,1),  /* Darkish gold */
  rgba(220,200,40,1), /* Classic gold */
  rgba(238,223,48,1), /* Bright, shining gold */
  rgba(234,140,5,1)  /* Warm, reflective gold */
  );
  background-size: 300%;
  
  /* Use background clip to show gradient through the text */
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  color: transparent;
  
  /* Animate the background for a shimmering effect */
  animation: shine 12s linear infinite;
  }
  
  /* Define the animation */
  @keyframes shine {
  0% {
  background-position: 0%;
  }
  50% {
  background-position: 100%;
  }
  100% {
  background-position: 0%;
  }
  }
  /* Partnering Code & Total Partner */
  .code-value {
  font-size: 18px;
  color: #ff5722 !important; /* Orange for code */
  background:white;
  width:auto;
  margin-top: 5px;
  font-weight:bold;
  }
  
  .partner-number {
  font-size: 30px; /* Blue for total partner number */
  margin-top: 5px;
  font-weight:bold;
  border:1px solid black;
  }
  /* RESPONSIVE DESIGN */
  @media (max-width: 768px) {
  .partner-stats {
  flex-direction: column;
  }
  
  }
    /* Add new styles */
    .about-container {
      padding: 0px 20px;
      max-width: 1200px;
      margin: 0 auto;
    }

    .about-section {
      background: white;
      border-radius: 15px;
      padding: 25px;
      margin: 20px 0;
      box-shadow: 0 5px 25px rgba(0,0,0,0.1);
    }

    .section-title {
      color: #020066;
      font-size: 2rem;
      margin-bottom: 20px;
      position: relative;
    }

    .section-title::after {
      content: '';
      position: absolute;
      bottom: -10px;
      left: 0;
      width: 50px;
      height: 3px;
      background: linear-gradient(90deg, #020066, #2962FF);
    }

    .partnership-levels {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
      margin-top: 30px;
    }

    .level-card {
      background: #f8f8f8;
      padding: 20px;
      border-radius: 10px;
      border-left: 4px solid #020066;
    }

    .level-card h3 {
      color: #020066;
      margin-bottom: 10px;
    }

    .contact-form {
      display: grid;
      gap: 15px;
      max-width: 600px;
      margin: 0 auto;
    }

    .form-input {
      padding: 12px;
      border: 2px solid #eee;
      border-radius: 8px;
      font-size: 16px;
    }

    .form-input:focus {
      border-color: #020066;
      outline: none;
    }

    .submit-btn {
      background: linear-gradient(135deg, #020066, #2962FF);
      color: white;
      padding: 15px 30px;
      border: none;
      border-radius: 25px;
      font-weight: bold;
      cursor: pointer;
      transition: transform 0.3s ease;
    }

    .contact-info {
      display: flex;
      flex-direction:column;
      justify-content: center;
      gap: 30px;
      margin-top: 40px;
    }

    .info-item {
      display: flex;
      align-items: center;
      gap: 10px;
      color: #020066;
    }

    .highlight {
      color: #020066;
      font-weight: bold;
    }

    .discipline-story {
      background: #f0f4ff;
      padding: 20px;
      border-radius: 10px;
      margin: 20px 0;
      border-left: 4px solid #2962FF;
    }
    .back{
    background:transparent;
    color:white;
    border-radius:10px;
    font-size:20px;
    position:absolute;
    z-index:2;
    top:5px;
    font-weight:600;
    left:10px;
    padding:10px 10px;
    text-align:center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.03);
    }
    a{
    color:inherit;
    text-decoration:none;
    }
  </style>
</head>
<body>
  <div class="cont">
    <div class="header-container">
      <div class="up-circle"></div>
      <div class="header-top">
        <div class="user-info">
          <h1 class="username">About Us</h1>
          <span class="gradient-gold" style="font-weight:800" >Financial Discipline Through Innovation</span>
        </div>
      </div>
    </div>

    <div class="about-container">
      <!-- Our Mission Section -->
      <div class="about-section">
        <h2 class="section-title">Our Mission</h2>
        <p class="highlight">Penniepoint is a disciplinary saving system that helps you make extra profit while saving...</p>
        <p>Operating since 2015, we've helped thousands achieve financial goals through our unique saving discipline system.</p>
        
        <div class="discipline-story">
          <h3>The Discipline Story</h3>
          <p>"Just like childhood kolo savings that taught financial restraint, Penniepoint helps you save meaningfully through intentional withdrawal processes."</p>
        </div>
      </div>

      <!-- How It Works Section -->
      <div class="about-section">
        <h2 class="section-title">How It Works</h2>
        <div class="partnership-levels">
          <div class="level-card">
            <h3>📈 Analyst</h3>
            <p>Entry-level partnership with 1st generation earnings</p>
            <p class="highlight">Requirement: 15 partners to upgrade</p>
          </div>
          <div class="level-card">
            <h3>👔 Manager</h3>
            <p>2nd generation earnings with leadership responsibilities</p>
            <p class="highlight">Assist 10 partners to upgrade</p>
          </div>
          <div class="level-card">
            <h3>🎯 Executive</h3>
            <p>3rd generation earnings with strategic oversight</p>
            <p class="highlight">Mentor 5 executives</p>
          </div>
          <div class="level-card">
            <h3>🏆 Director</h3>
            <p>4th generation earnings with organizational leadership</p>
            <p class="highlight">Develop 3 directors</p>
          </div>
        </div>
      </div>

      <!-- Contact Section -->
      <div class="about-section">
        <h2 class="section-title">Get in Touch</h2>
        <form class="contact-form">
          <input type="text" class="form-input" placeholder="Your Name">
          <input type="email" class="form-input" placeholder="Email Address">
          <textarea class="form-input" rows="5" placeholder="Your Message"></textarea>
          <button type="submit" class="submit-btn">
            <i class="fas fa-paper-plane"></i> Send Message
          </button>
        </form>

        <div class="contact-info">
          <div class="info-item">
            <i class="fas fa-phone-alt"></i>
            <span>+234 908 5178 305</span>
          </div>
          <div class="info-item">
            <i class="fas fa-envelope"></i>
            <span>support@penniepoint.com</span>
          </div>
          <div class="info-item">
            <i class="fas fa-map-marker-alt"></i>
            <span>Global Headquarters</span>
          </div>
        </div>
      </div>
    </div>
    <div class="back" ><a href="dashboard" ><i class="fas fa-arrow-left" ></i></a></div>
  </div>
</body>
</html>

