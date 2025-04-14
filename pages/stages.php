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
  <title>Partnership Stages</title>
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
  
  /* HEADER (TOP SECTION) */
  .header-container {
  /* Blue gradient background 
  background: linear-gradient(135deg, #0D47A1 0%, #2962FF 100%);*/
  padding-top: 50px;
  padding-bottom:20px;
  position: relative;
  border-bottom-left-radius: 50% 10%;
  border-bottom-right-radius: 50% 10%;
  }
  
  /* Top row with username and icons */
  .header-top {
  display: flex;
  justify-content: space-around;
  align-items: center;
  margin-bottom: 0px;
  color:white;
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

    /* Entry Animation Keyframes */
    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    /* Base Styles */
    .stages-container {
      padding: 20px;
      padding-top:0;
      max-width: 1200px;
      margin: 0 auto;
    }
    
    .stage-progression {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
      margin: 30px 0;
    }
    
    .stage-card {
      background: white;
      border-radius: 15px;
      padding: 25px;
      box-shadow: 0 5px 25px rgba(0,0,0,0.1);
      position: relative;
      transition: transform 0.3s ease;
    }
    
    .stage-card:hover {
      transform: translateY(-5px);
    }
    
    .stage-header {
      display: flex;
      align-items: center;
      gap: 15px;
      margin-bottom: 20px;
    }
    
    .stage-icon {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      background: #020066;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    
    .stage-icon i {
      color: white;
      font-size: 1.5rem;
    }
    
    .stage-title {
      color: #020066;
      font-size: 1.4rem;
      margin: 0;
    }
    
    .stage-badge {
      background: linear-gradient(45deg, #FFD700, #FFC400);
      padding: 5px 15px;
      border-radius: 20px;
      font-weight: bold;
      font-size: 0.9rem;
      width: fit-content;
    }
    
    .stage-requirements {
      margin: 15px 0;
      padding: 15px;
      background: #f8f8f8;
      border-radius: 10px;
    }
    
    .requirement-item {
      display: flex;
      justify-content: space-between;
      margin: 10px 0;
    }
    
    .earning-structure {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 15px;
      margin-top: 30px;
    }
    
    .earning-tier {
      text-align: center;
      padding: 20px;
      background: white;
      border-radius: 10px;
      box-shadow: 0 3px 15px rgba(0,0,0,0.1);
    }
    
    .tier-percentage {
      font-size: 2rem;
      color: #020066;
      font-weight: bold;
      margin: 10px 0;
    }
    
    .discipline-story {
      background: #f0f4ff;
      padding: 25px;
      border-radius: 15px;
      margin: 30px 0;
      border-left: 4px solid #2962FF;
    }
    
    /* Scroll Animation Base: Hide elements before they are in view */
    .animate-on-scroll {
      opacity: 0;
      transform: translateY(20px);
      transition: opacity 0.6s ease-out, transform 0.6s ease-out;
    }
    
    /* Show class triggers animation */
    .animate-on-scroll.show {
      opacity: 1;
      transform: translateY(0);
    }
    @media (max-width: 768px) {
    .earning-structure {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
    margin-top: 30px;
    }
    
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
        <div class="user-info animate-on-scroll">
          <h1 class="username">Partnership Stages</h1>
          <span class="gradient-gold" style="font-weight:bolder;" >Unlock Your Earning Potential</span>
        </div>
      </div>
    </div>

    <div class="stages-container">
      <!-- Stage Progression -->
      <div class="stage-progression">
        <!-- Analyst Card -->
        <div class="stage-card animate-on-scroll">
          <div class="stage-header">
            <div class="stage-icon">
              <i class="fas fa-chart-line"></i>
            </div>
            <h3 class="stage-title">Analyst (VIP 1)</h3>
          </div>
          <div class="stage-badge">Your Current Stage</div>
          <div class="stage-requirements">
            <div class="requirement-item">
              <span>Earnings:</span>
              <strong>1st Gen</strong>
            </div>
            <div class="requirement-item">
              <span>Required Partners:</span>
              <strong>15</strong>
            </div>
          </div>
          <p>Start your journey by building your initial network and learning the system.</p>
        </div>

        <!-- Manager Card -->
        <div class="stage-card animate-on-scroll">
          <div class="stage-header">
            <div class="stage-icon">
              <i class="fas fa-users-cog"></i>
            </div>
            <h3 class="stage-title">Manager (VIP 2)</h3>
          </div>
          <div class="stage-requirements">
            <div class="requirement-item">
              <span>Earnings:</span>
              <strong>2nd Gen</strong>
            </div>
            <div class="requirement-item">
              <span>Required Upgrades:</span>
              <strong>10 Managers</strong>
            </div>
          </div>
          <p>Develop leadership skills by mentoring your team members.</p>
        </div>

        <!-- Executive Card -->
        <div class="stage-card animate-on-scroll">
          <div class="stage-header">
            <div class="stage-icon">
              <i class="fas fa-briefcase"></i>
            </div>
            <h3 class="stage-title">Executive (VIP 3)</h3>
          </div>
          <div class="stage-requirements">
            <div class="requirement-item">
              <span>Earnings:</span>
              <strong>3rd Gen</strong>
            </div>
            <div class="requirement-item">
              <span>Required Upgrades:</span>
              <strong>5 Executives</strong>
            </div>
          </div>
          <p>Focus on strategic growth and organizational development.</p>
        </div>

        <!-- Director Card -->
        <div class="stage-card animate-on-scroll">
          <div class="stage-header">
            <div class="stage-icon">
              <i class="fas fa-trophy"></i>
            </div>
            <h3 class="stage-title">Director (VIP 4)</h3>
          </div>
          <div class="stage-requirements">
            <div class="requirement-item">
              <span>Earnings:</span>
              <strong>4th Gen</strong>
            </div>
            <div class="requirement-item">
              <span>Required Upgrades:</span>
              <strong>3 Directors</strong>
            </div>
          </div>
          <p>Lead the organization and shape its future direction.</p>
        </div>
      </div>

      <!-- Earning Structure -->
      <div class="earning-structure animate-on-scroll">
        <div class="earning-tier">
          <i class="fas fa-user-friends fa-2x"></i>
          <div class="tier-percentage">25%</div>
          <p>1st Generation Earnings</p>
        </div>
        <div class="earning-tier">
          <i class="fas fa-network-wired fa-2x"></i>
          <div class="tier-percentage">15%</div>
          <p>2nd Generation Earnings</p>
        </div>
        <div class="earning-tier">
          <i class="fas fa-sitemap fa-2x"></i>
          <div class="tier-percentage">10%</div>
          <p>3rd Generation Earnings</p>
        </div>
        <div class="earning-tier">
          <i class="fas fa-chart-network fa-2x"></i>
          <div class="tier-percentage">5%</div>
          <p>4th Generation Earnings</p>
        </div>
      </div>

      <!-- Discipline Story -->
      <div class="discipline-story animate-on-scroll">
        <h3 class="gradient-gold">The Kolo Discipline System</h3>
        <p>Just like the childhood kolo that taught financial restraint, our withdrawal authorization system ensures you only access funds for meaningful purposes. This discipline has helped our members increase savings by an average of 300%.</p>
      </div>

    </div>
    <div class="back" ><a href="dashboard" ><i class="fas fa-arrow-left" ></i></a></div>
  </div>

  <script>
    // Scroll Animation using Intersection Observer
    document.addEventListener('DOMContentLoaded', function() {
      const scrollElements = document.querySelectorAll('.animate-on-scroll');
      
      const elementInView = (el, dividend = 1) => {
        const elementTop = el.getBoundingClientRect().top;
        return (
          elementTop <= (window.innerHeight || document.documentElement.clientHeight) / dividend
        );
      };
      
      const displayScrollElement = (element) => {
        element.classList.add('show');
      };

      const hideScrollElement = (element) => {
        element.classList.remove('show');
      };

      const handleScrollAnimation = () => {
        scrollElements.forEach((el) => {
          if (elementInView(el, 1.25)) {
            displayScrollElement(el);
          } else {
            // Optionally hide the element when it's out of view
            // hideScrollElement(el);
          }
        })
      }
      
      window.addEventListener('scroll', () => { 
        handleScrollAnimation();
      });
      
      // Trigger check on load
      handleScrollAnimation();
    });
  </script>
</body>
</html>

