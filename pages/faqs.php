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
  <title>Penniepoint FAQs</title>
  <!-- Font Awesome (CDN) -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    /* Global Resets and Base */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      font-family: System, sans-serif;
      background-color: #f5f5f5;
      color: #000;
      line-height: 1.6;
    }
    
    /* Up-circle header background */
    .up-circle {
      position: absolute;
      top: 0;
      left: 0;
      background: linear-gradient(135deg, rgba(0,0,250,1) 0%, rgba(2,0,102,1) 100%);
      height: 40vh;
      width: 130vw;
      transform: translateX(-30vw);
      z-index: -90;
      border-bottom-left-radius: 90% 40%;
      border-bottom-right-radius: 100% 100%;
    }
    
    /* HEADER SECTION */
    .header-container {
      padding-top: 50px;
      padding-bottom: 20px;
      position: relative;
      border-bottom-left-radius: 50% 10%;
      border-bottom-right-radius: 50% 10%;
      text-align: center;
    }
    
    .header-top {
      display: flex;
      flex-direction: column;
      align-items: center;
      color: white;
    }
    
    .username {
      font-size: 2.5rem;
      margin-bottom: 5px;
    }
    
    .gradient-gold {
      background: linear-gradient(130deg, 
        rgba(175,148,0,1),
        rgba(220,200,40,1),
        rgba(238,223,48,1),
        rgba(234,140,5,1),
        rgba(125,58,0,1),
        rgba(220,200,40,1),
        rgba(238,223,48,1),
        rgba(234,140,5,1),
        rgba(125,58,0,1)
      );
      background-size: 300%;
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      color: transparent;
      font-weight: bolder;
      animation: shine 10s linear infinite;
    }
    
    @keyframes shine {
      0% { background-position: 0%; }
      50% { background-position: 100%; }
      100% { background-position: 0%; }
    }
    
    /* Container for FAQs */
    .faqs-container {
      padding: 40px 20px;
      max-width: 900px;
      margin: 0 auto;
      padding-bottom:100px;
    }
    
    .faq-section {
      margin-bottom: 20px;
      background: #fff;
      border-radius: 10px;
      box-shadow: 0 3px 15px rgba(0,0,0,0.1);
      overflow: hidden;
      transition: transform 0.3s ease;
    }
    
    .faq-section:hover {
      transform: translateY(-3px);
    }
    
    .faq-header {
      cursor: pointer;
      padding: 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: #fff;
    }
    
    .faq-header h3 {
      font-size: 1.2rem;
      color: #020066;
    }
    
    .faq-icon {
      transition: transform 0.3s ease;
    }
    
    .faq-header.active .faq-icon {
      transform: rotate(180deg);
    }
    
    .faq-body {
      max-height: 0;
      overflow: hidden;
      padding: 0 20px;
      background: #fafafa;
      transition: max-height 0.4s ease, padding 0.4s ease;
    }
    
    .faq-body.open {
      padding: 20px;
    }
    
    .faq-body p {
      font-size: 0.95rem;
      color: #333;
    }
    
    /* Scroll Animation Base: Hide elements before in view */
    .animate-on-scroll {
      opacity: 0;
      transform: translateY(20px);
      transition: opacity 0.6s ease-out, transform 0.6s ease-out;
    }
    
    .animate-on-scroll.show {
      opacity: 1;
      transform: translateY(0);
    }
    
    @media (max-width: 768px) {
      .faq-header h3 {
        font-size: 1rem;
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
      <div class="header-top animate-on-scroll">
        <h1 class="username">Penniepoint FAQs</h1>
        <span class="gradient-gold">Your Guide to Saving and Partnering</span>
      </div>
    </div>
    
    <div class="faqs-container">
      <!-- FAQ 1 -->
      <div class="faq-section animate-on-scroll">
        <div class="faq-header">
          <h3> What is Penniepoint?</h3>
          <i class="fas fa-chevron-down faq-icon"></i>
        </div>
        <div class="faq-body">
          <p>Penniepoint is a disciplinary saving system that not only helps you reach your financial goals but also makes saving profitable by enabling you to earn extra income when you partner with others.</p>
        </div>
      </div>
      
      <!-- FAQ 2 -->
      <div class="faq-section animate-on-scroll">
        <div class="faq-header">
          <h3> How does the system work?</h3>
          <i class="fas fa-chevron-down faq-icon"></i>
        </div>
        <div class="faq-body">
          <p>Penniepoint offers various saving systems to suit your financial target. You choose the system that fits your saving style and start saving. The catch? Money cannot be withdrawn at will. To discipline your spending, you need to contact your account manager to authorize a withdrawal.</p>
        </div>
      </div>
      
      <!-- FAQ 3 -->
      <div class="faq-section animate-on-scroll">
        <div class="faq-header">
          <h3> Why can't I withdraw money freely?</h3>
          <i class="fas fa-chevron-down faq-icon"></i>
        </div>
        <div class="faq-body">
          <p>The restriction is by design – it helps discipline your saving habits. Withdrawing funds requires authorization from your account manager, ensuring that withdrawals are made only for truly important needs.</p>
        </div>
      </div>
      
      <!-- FAQ 4 -->
      <div class="faq-section animate-on-scroll">
        <div class="faq-header">
          <h3> What makes Penniepoint unique?</h3>
          <i class="fas fa-chevron-down faq-icon"></i>
        </div>
        <div class="faq-body">
          <p>Beyond a standard savings mechanism, Penniepoint instills discipline through controlled access to your funds. It’s a system that transforms saving into an opportunity for both securing your financial future and earning extra income by partnering with others.</p>
        </div>
      </div>
      
      <!-- FAQ 5 -->
      <div class="faq-section animate-on-scroll">
        <div class="faq-header">
          <h3>What are the partnership levels?</h3>
          <i class="fas fa-chevron-down faq-icon"></i>
        </div>
        <div class="faq-body">
          <p>When partnering with Penniepoint, you start as an Analyst (earning on your 1st generation). Progression requires inviting and managing partners:
            <br><br><strong>Analyst:</strong> Start your journey.
            <br><strong>Manager:</strong> Partner with 15 people and help them succeed.
            <br><strong>Executive:</strong> Assist 10 partners to become managers.
            <br><strong>Director:</strong> Lead 3 partners to become directors – earning up to your 4th generation.
          </p>
        </div>
      </div>
      
      <!-- FAQ 6 -->
      <div class="faq-section animate-on-scroll">
        <div class="faq-header">
          <h3> Why should I partner with Penniepoint?</h3>
          <i class="fas fa-chevron-down faq-icon"></i>
        </div>
        <div class="faq-body">
          <p>Partnering with Penniepoint gives you your own saving system with added benefits. Not only do you save money in a disciplined manner, but you also earn extra income as your network grows, enjoying earnings up to the 4th generation.</p>
        </div>
      </div>
      
    </div>
    <div class="back" ><a href="dashboard" ><i class="fas fa-arrow-left" ></i></a></div>
  </div>
  
  <script>
    // Accordion Toggle
    const faqHeaders = document.querySelectorAll('.faq-header');
    
    faqHeaders.forEach(header => {
      header.addEventListener('click', () => {
        // Toggle active class for the header icon rotation
        header.classList.toggle('active');
        const body = header.nextElementSibling;
        
        // Open or collapse the FAQ body
        if (body.style.maxHeight) {
          body.style.maxHeight = null;
          body.classList.remove('open');
        } else {
          body.style.maxHeight = body.scrollHeight + 40 + "px";
          body.classList.add('open');
        }
      });
    });
    
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
      
      const handleScrollAnimation = () => {
        scrollElements.forEach((el) => {
          if (elementInView(el, 1.25)) {
            displayScrollElement(el);
          }
        });
      }
      
      window.addEventListener('scroll', handleScrollAnimation);
      handleScrollAnimation();
    });
  </script>
</body>
</html>

