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
  <title>Pennieshares FAQs</title>
  <!-- Font Awesome (CDN) -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    :root {
        --bg-primary: #f5f5f5;
        --bg-secondary: #ffffff;
        --text-primary: #000;
        --text-secondary: #333;
        --accent-color: #020066;
        --header-gradient: linear-gradient(135deg, rgba(0,0,250,1) 0%, rgba(2,0,102,1) 100%);
    }

    html[data-theme="dark"] {
        --bg-primary: #111418;
        --bg-secondary: #1b2127;
        --text-primary: #f0f4f8;
        --text-secondary: #a0b3c6;
        --accent-color: #1d90f5;
        --header-gradient: linear-gradient(135deg, #1d90f5 0%, #0d3c8a 100%);
    }

    /* Global Resets and Base */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      font-family: System, sans-serif;
      background-color: var(--bg-primary);
      color: var(--text-primary);
      line-height: 1.6;
    }
    
    /* Up-circle header background */
    .up-circle {
      position: absolute;
      top: 0;
      left: 0;
      background: var(--header-gradient);
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
      background: var(--bg-secondary);
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
      background: var(--bg-secondary);
    }
    
    .faq-header h3 {
      font-size: 1.2rem;
      color: var(--accent-color);
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
      background: var(--bg-primary);
      transition: max-height 0.4s ease, padding 0.4s ease;
    }
    
    .faq-body.open {
      padding: 20px;
    }
    
    .faq-body p {
      font-size: 0.95rem;
      color: var(--text-secondary);
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
  <div class="back" ><a href="/profile_view" ><i class="fas fa-arrow-left" ></i></a></div>
  <div class="cont">
    <div class="header-container">
      <div class="up-circle"></div>
      <div class="header-top animate-on-scroll">
        <h1 class="username">Pennieshares FAQs</h1>
        <span class="gradient-gold">Your Guide to Shares and Investments</span>
      </div>
    </div>
    
    <div class="faqs-container">
      <!-- FAQ 1 -->
      <div class="faq-section animate-on-scroll">
        <div class="faq-header">
          <h3>What is Pennieshares, and how does it work?</h3>
          <i class="fas fa-chevron-down faq-icon"></i>
        </div>
        <div class="faq-body">
          <p>Pennieshares is an open-source brokerage company that allows you to buy and sell shares in a transparent and secure way. Our platform is designed to give you control over your investments.</p>
        </div>
      </div>
      
      <!-- FAQ 2 -->
      <div class="faq-section animate-on-scroll">
        <div class="faq-header">
          <h3>How do I get started with Pennieshares?</h3>
          <i class="fas fa-chevron-down faq-icon"></i>
        </div>
        <div class="faq-body">
          <p>Getting started is easy! Simply sign up for an account, and you'll have access to our platform. You can start buying and selling shares right away.</p>
        </div>
      </div>
      
      <!-- FAQ 3 -->
      <div class="faq-section animate-on-scroll">
        <div class="faq-header">
          <h3>What does it mean that Pennieshares is open-source?</h3>
          <i class="fas fa-chevron-down faq-icon"></i>
        </div>
        <div class="faq-body">
          <p>As an open-source company, our code is transparent and available for anyone to review. This means that our operations are transparent, and you have full control over your shares and money.</p>
        </div>
      </div>
      
      <!-- FAQ 4 -->
      <div class="faq-section animate-on-scroll">
        <div class="faq-header">
          <h3>How does the open-source model benefit me?</h3>
          <i class="fas fa-chevron-down faq-icon"></i>
        </div>
        <div class="faq-body">
          <p>The open-source model ensures that our platform is transparent, secure, and community-driven. You can trust that your investments are being handled in a fair and transparent way.</p>
        </div>
      </div>
      
      <!-- FAQ 5 -->
      <div class="faq-section animate-on-scroll">
        <div class="faq-header">
          <h3>Who controls my shares and money?</h3>
          <i class="fas fa-chevron-down faq-icon"></i>
        </div>
        <div class="faq-body">
          <p>You do! As an open-source brokerage company, Pennieshares doesn't control your shares or money. You have full control over your investments.</p>
        </div>
      </div>
      
      <!-- FAQ 6 -->
      <div class="faq-section animate-on-scroll">
        <div class="faq-header">
          <h3>How do you protect my personal and financial information?</h3>
          <i class="fas fa-chevron-down faq-icon"></i>
        </div>
        <div class="faq-body">
          <p>We use advanced encryption and secure servers to protect your data. Our open-source model also ensures that our security protocols are transparent and regularly reviewed.</p>
        </div>
      </div>

      <!-- FAQ 7 -->
      <div class="faq-section animate-on-scroll">
        <div class="faq-header">
          <h3>What are your fees and commissions?</h3>
          <i class="fas fa-chevron-down faq-icon"></i>
        </div>
        <div class="faq-body">
          <p>Our fees and commissions are competitive and transparent. We offer a range of pricing options to suit different investment needs and goals.</p>
        </div>
      </div>

      <!-- FAQ 8 -->
      <div class="faq-section animate-on-scroll">
        <div class="faq-header">
          <h3>Are there any hidden fees?</h3>
          <i class="fas fa-chevron-down faq-icon"></i>
        </div>
        <div class="faq-body">
          <p>No! We are committed to transparency and will clearly disclose all fees and charges associated with our services.</p>
        </div>
      </div>

      <!-- FAQ 9 -->
      <div class="faq-section animate-on-scroll">
        <div class="faq-header">
          <h3>Do you offer investment advice?</h3>
          <i class="fas fa-chevron-down faq-icon"></i>
        </div>
        <div class="faq-body">
          <p>While we don't provide personalized investment advice, our platform offers educational resources and tools to help you make informed investment decisions.</p>
        </div>
      </div>

      <!-- FAQ 10 -->
      <div class="faq-section animate-on-scroll">
        <div class="faq-header">
          <h3>How can I get support if I have questions or concerns?</h3>
          <i class="fas fa-chevron-down faq-icon"></i>
        </div>
        <div class="faq-body">
          <p>Our community-driven support team is available to assist you via online forums, email, or chat.</p>
        </div>
      </div>

      <!-- FAQ 11 -->
      <div class="faq-section animate-on-scroll">
        <div class="faq-header">
          <h3>How can I monitor my account activity?</h3>
          <i class="fas fa-chevron-down faq-icon"></i>
        </div>
        <div class="faq-body">
          <p>You can access your account online or through our mobile app, where you can view your portfolio, transaction history, and account balances.</p>
        </div>
      </div>

      <!-- FAQ 12 -->
      <div class="faq-section animate-on-scroll">
        <div class="faq-header">
          <h3>Can I set up automatic investments or withdrawals?</h3>
          <i class="fas fa-chevron-down faq-icon"></i>
        </div>
        <div class="faq-body">
          <p>Yes! We offer flexible options for automatic investments and withdrawals to help you manage your cash flow.</p>
        </div>
      </div>
      
    </div>
    <div class="back" ><a href="/profile_view" ><i class="fas fa-arrow-left" ></i></a></div>
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
  <script>
    (function() {
        const html = document.documentElement;
        const applyTheme = (theme) => {
            html.setAttribute('data-theme', theme);
        };
        
        const savedTheme = localStorage.getItem('theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

        if (savedTheme) {
            applyTheme(savedTheme);
        } else if (prefersDark) {
            applyTheme('dark');
        } else {
            applyTheme('light');
        }
    })();
  </script>
</body>
</html>