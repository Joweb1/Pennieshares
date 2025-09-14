<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Pennieshares — Empowering Investors with Transparency and Control</title>
  <meta name="description" content="Pennieshares is an open-source brokerage platform giving investors full transparency, security, and control." />

  <!-- Google Fonts + Material Icons -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
  <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
  <link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css" />

  <link rel="stylesheet" href="assets/css/style.css" />
</head>
<body>

  <div class="loading-overlay">
    <div class="loader"></div>
  </div>

  <!-- ───────── NAVBAR ───────── -->
  <header class="main-header">
    <div class="container">
      <div class="nav">
        <a class="brand" href="home">
          <img src="assets/images/logo.png" alt="Pennieshares Logo" class="logo" aria-hidden="true">
          <h1>Pennieshares</h1>
        </a>

        <nav class="header-nav-desktop" aria-label="Primary">
          <ul>
            <li><a href="home#stocks"><span class="material-icons-outlined">show_chart</span>Stocks & ETFs</a></li>
            <li><a href="home#api"><span class="material-icons-outlined">hub</span>Business & API</a></li>
            <li><a href="home#blog"><span class="material-icons-outlined">article</span>Blog</a></li>
            <li><a href="download"><span class="material-icons-outlined">download</span>Download App</a></li>
          </ul>
        </nav>

        <div class="header-actions">
          <a class="btn primary" href="login">
            <span class="material-icons-outlined">login</span>
            <span class="btn-text">Sign In</span>
          </a>
          <button class="menu" id="burger-menu" aria-label="Open menu">
            <span class="material-icons-outlined">menu</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Mobile Navigation -->
    <nav class="nav-mobile" id="nav-mobile">
      <div class="nav-mobile-header">
        <div class="nav-mobile-brand">
          <img src="assets/images/logo.png" alt="Pennieshares Logo" class="logo" aria-hidden="true">
          <span>Pennieshares</span>
        </div>
        <button id="close-menu-btn" aria-label="Close menu">
          <span class="material-icons-outlined">close</span>
        </button>
      </div>
      <ul class="nav-mobile-links">
        <li><a href="/home" class="active"><span class="material-icons-outlined">home</span>Home</a></li>
        <li><a href="download"><span class="material-icons-outlined">download</span>Download App</a></li>
        <li><a href="/home#what"><span class="material-icons-outlined">info</span>About</a></li>
        <li><a href="/home#features"><span class="material-icons-outlined">toggle_on</span>Features</a></li>
        <li><a href="/home#pricing"><span class="material-icons-outlined">receipt_long</span>Pricing</a></li>
        <li><a href="/home#contact"><span class="material-icons-outlined">support_agent</span>Contact</a></li>
      </ul>
      <div class="theme-toggle-mobile-wrapper">
        <button id="theme-toggle-mobile" class="theme-toggle-mobile">
            <span class="material-icons-outlined sun-icon">light_mode</span>
            <span class="material-icons-outlined moon-icon">dark_mode</span>
        </button>
      </div>
    </nav>
    <div class="nav-overlay" id="nav-overlay"></div>
  </header>

  <!-- ───────── DOWNLOAD HERO ───────── -->
  <section class="hero" id="download-hero">
    <div class="container wrap">
      <div data-aos="fade-up">
        <span class="eyebrow"><span class="material-icons-outlined" aria-hidden="true">phone_iphone</span> Mobile App</span>
        <h2 class="display">Invest on the Go</h2>
        <p class="lead">Get the full power of Pennieshares in your pocket. Our mobile app is designed for a seamless and secure investing experience.</p>
        <div class="cta">
          <a href="#" class="btn primary"><span class="material-icons-outlined">android</span> Download for Android</a>
          <a href="#" class="btn primary"><span class="material-icons-outlined">apple</span> Download for iOS</a>
        </div>
      </div>
      <div class="img-ph" data-aos="fade-left">
        <img src="assets/images/dashboardscreenlight.jpg" alt="App Screenshot Light Mode" class="light-mode-img-hero">
        <img src="assets/images/dashboardscreendark.jpg" alt="App Screenshot Dark Mode" class="dark-mode-img-hero" style="display: none;">
      </div>
    </div>
  </section>

  <!-- ───────── APP FEATURES ───────── -->
  <section class="alt" id="features">
    <div class="container">
        <div class="sec-head" data-aos="fade-up">
            <h3>Powerful Features in Your Pocket</h3>
            <p>Our mobile app brings the full power of our platform to your fingertips, with a focus on speed, security, and ease of use.</p>
        </div>
      <div class="grid-3">
        <div class="card" data-aos="fade-up" data-aos-delay="100">
          <div class="icon"><span class="material-icons-outlined">phone_iphone</span></div>
          <h4>Seamless Mobile Experience</h4>
          <p class="muted">Enjoy a fully-featured trading experience optimized for your mobile device.</p>
        </div>
        <div class="card" data-aos="fade-up" data-aos-delay="200">
          <div class="icon"><span class="material-icons-outlined">notifications</span></div>
          <h4>Real-Time Alerts</h4>
          <p class="muted">Stay on top of market movements with instant notifications and alerts.</p>
        </div>
        <div class="card" data-aos="fade-up" data-aos-delay="300">
          <div class="icon"><span class="material-icons-outlined">security</span></div>
          <h4>Secure and Encrypted</h4>
          <p class="muted">Your data and transactions are protected with the latest security standards.</p>
        </div>
      </div>
    </div>
  </section>

  <style>
    /* Phone Mockup */
    .phone-mockup {
      position: relative;
      width: 300px;
      height: 600px;
      margin: 0 auto;
      background-color: #111;
      border-radius: 40px;
      box-shadow: 0 0 0 2px #333, 0 0 0 8px #000;
      display: flex;
      justify-content: center;
      align-items: center;
    }

    .phone-mockup::before {
      content: '';
      position: absolute;
      top: 0;
      left: 50%;
      transform: translateX(-50%);
      width: 60%;
      height: 30px;
      background-color: #000;
      border-radius: 0 0 20px 20px;
    }

    .phone-screen {
      width: 280px;
      height: 580px;
      background-color: #fff;
      border-radius: 30px;
      overflow: hidden;
    }

    .phone-screen img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
  </style>

  <!-- ───────── DEVICE MOCKUP ───────── -->
  <section class="device-mockup">
    <div class="container">
        <div class="sec-head" data-aos="fade-up">
            <h3>See it in Action</h3>
            <p>A glimpse of the clean and intuitive interface of the Pennieshares mobile app.</p>
        </div>
      <div class="phone-mockup" data-aos="fade-up" data-aos-delay="200">
        <div class="phone-screen">
          <img src="assets/images/lightdashboard.png" alt="App Screenshot Light Mode" class="light-mode-img">
          <img src="assets/images/darkdashboard.png" alt="App Screenshot Dark Mode" class="dark-mode-img" style="display: none;">
        </div>
      </div>
    </div>
  </section>

  <!-- ───────── FOOTER ───────── -->
  <footer>
    <div class="container foot-grid">
      <div>
        <div class="brand" style="gap:10px">
          <img src="assets/images/logo.png" alt="Pennieshares Logo" class="logo" aria-hidden="true">
          <strong style="font-size:18px;color:var(--ink)">Pennieshares</strong>
        </div>
        <div class="sp-16"></div>
        <p class="subtle">Open‑source brokerage platform built for transparency and control.</p>
      </div>
      <div>
        <strong>Company</strong>
        <div class="sp-8"></div>
        <a class="subtle" href="#what">About</a><br/>
        <a class="subtle" href="#blog">Blog</a><br/>
        <a class="subtle" href="#news">News</a>
      </div>
      <div>
        <strong>Resources</strong>
        <div class="sp-8"></div>
        <a class="subtle" href="#resources">Library</a><br/>
        <a class="subtle" href="#tips">Investment Tips</a><br/>
        <a class="subtle" href="#api">Developers</a>
      </div>
      <div>
        <strong>Legal</strong>
        <div class="sp-8"></div>
        <a class="subtle" href="#">Privacy Policy</a><br/>
        <a class="subtle" href="#">Terms of Service</a>
      </div>
    </div>
    <div class="container" style="margin-top:18px;display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
      <span class="subtle">© 2024 Pennieshares. All rights reserved. Powered by Penniepoint.</span>
      <div style="display:flex;gap:10px">
        <a class="btn" href="https://www.youtube.com/your-channel" aria-label="YouTube" target="_blank"><span class="material-icons-outlined">play_circle</span></a>
        <a class="btn" href="https://www.instagram.com/your-profile" aria-label="Instagram" target="_blank"><span class="material-icons-outlined">camera_alt</span></a>
        <a class="btn" href="https://www.facebook.com/your-page" aria-label="Facebook" target="_blank"><span class="material-icons-outlined">facebook</span></a>
        <a class="btn" href="https://twitter.com/your-profile" aria-label="X (Twitter)" target="_blank"><span class="material-icons-outlined">share</span></a>
      </div>
    </div>
  </footer>

  
  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js" defer></script>
  <script src="script.js" defer></script>
  <script src="https://unpkg.com/swiper/swiper-bundle.min.js" defer></script>
  

</body>
</html>
