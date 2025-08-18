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

  <style>
    :root {
      --blue: #0A4DFF;
      --blue-600: #0739bd;
      --red: #FF3B30;
      --ink: #0A2540;
      --ink-2: #243b53;
      --bg: #ffffff;
      --bg-alt: #f6f8fb;
      --card: #ffffff;
      --muted: #5b7186;
      --ring: rgba(10, 77, 255, .25);
      --radius: 14px;
      --shadow: 0 10px 30px rgba(16, 24, 40, .08), 0 2px 6px rgba(16, 24, 40, .06);
      --shadow-sm: 0 4px 16px rgba(16, 24, 40, .08);
      --shadow-bm: inset 0 30px 26px rgba(16, 24, 250, .2);
      --max: 1200px;

      /* Light Theme Variables */
      --bg-primary-light: #f4f7fa;
      --bg-secondary-light: #ffffff;
      --bg-tertiary-light: #e9eef2;
      --text-primary-light: #111418;
      --text-secondary-light: #5a6470;
      --border-color-light: #dde3e9;
      --accent-color-light: #0c7ff2;
      --accent-text-light: #ffffff;

      /* Dark Theme Variables */
      --bg-primary-dark: #111418;
      --bg-secondary-dark: #1b2127;
      --bg-tertiary-dark: #283039;
      --text-primary-dark: #ffffff;
      --text-secondary-dark: #9cabba;
      --border-color-dark: #3b4754;
      --accent-color-dark: #0c7ff2;
      --accent-text-dark: #ffffff;

      /* Default to Light Theme */
      --bg-primary: var(--bg-primary-light);
      --bg-secondary: var(--bg-secondary-light);
      --bg-tertiary: var(--bg-tertiary-light);
      --text-primary: var(--text-primary-light);
      --text-secondary: var(--text-secondary-light);
      --border-color: var(--border-color-light);
      --accent-color: var(--accent-color-light);
      --accent-text: var(--accent-text-light);

      --select-arrow: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' fill='%239cabba' viewBox='0 0 256 256'%3e%3cpath d='M215.39,92.61,132.61,175.39a8,8,0,0,1-11.22,0L41.61,92.61A8,8,0,0,1,52.83,81.39H203.17a8,8,0,0,1,11.22,11.22Z'%3e%3c/path%3e%3c/svg%3e");
    }

    html[data-theme="dark"] {
      --bg-primary: var(--bg-primary-dark);
      --bg-secondary: var(--bg-secondary-dark);
      --bg-tertiary: var(--bg-tertiary-dark);
      --text-primary: var(--text-primary-dark);
      --text-secondary: var(--text-secondary-dark);
      --border-color: var(--border-color-dark);
      --accent-color: var(--accent-color-dark);
      --accent-text: var(--accent-text-dark);
    }

    * {
      box-sizing: border-box;
    }

    html,
    body {
      margin: 0;
      background: var(--bg-primary);
      color: var(--text-primary);
      font-family: Inter, system-ui, Segoe UI, Roboto, Helvetica, Arial, sans-serif;
      line-height: 1.55;
      overflow-x: hidden; /* Prevent horizontal scrolling */
    }
    
  /*  .body{
        height:100vh;
        width:100%;
        overflow-x:hidden;
    } */

    img {
      max-width: 100%;
      height: auto;
      display: block;
    }

    a {
      text-decoration: none;
      color: inherit;
      transition: color 0.2s ease;
    }
    a:hover {
      color: var(--accent-color);
    }

    .container {
      max-width: var(--max);
      margin-inline: auto;
      padding: 0 20px;
    }

    /* --- Header --- */
    .main-header {
      position: fixed;
      top: 0;
      width:100vw;
      z-index: 80;
      background: rgba(244, 247, 250, 0.85); /* Light theme background with opacity */
      -webkit-backdrop-filter: blur(10px);
      backdrop-filter: blur(10px);
      border-bottom: 1px solid var(--border-color);
      transition: background-color 0.3s ease, border-color 0.3s ease;
    }

    html[data-theme="dark"] .main-header {
        background: rgba(17, 20, 24, 0.85); /* Dark theme background with opacity */
    }

    .body-no-scroll {
        overflow: hidden;
    }

    .nav {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      height: 72px;
    }

    .brand {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .logo {
      width: 36px;
      height: 36px;
      border-radius: 9px;
    }

    .brand h1 {
      font-size: 18px;
      margin: 0;
      color: var(--text-primary);
      font-weight: 800;
      letter-spacing: .2px;
    }

    .header-actions {
      display: flex;
      align-items: center;
      gap: 1rem;
    }

    #burger-menu {
      display: flex;
      padding: 0.5rem;
      background: transparent;
      border: none;
      color: var(--text-primary);
    }

    /* --- Mobile Navigation Menu --- */
    .nav-mobile {
      position: fixed;
      top: 0;
      left: -100%;
      /* Start off-screen */
      width: 80vw;
      max-width: 320px;
      height: 100vh;
      background-color: var(--bg-secondary);
      z-index: 100;
      transition: left 0.3s ease-in-out;
      display: flex;
      flex-direction: column;
      justify-content: space-between; /* Push content apart */
      padding: 2rem 1.5rem;
      border-right: 1px solid var(--border-color);
      box-shadow: var(--shadow);
    }

    .nav-mobile.is-open {
      left: 0;
    }

    .nav-mobile-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 2rem;
    }

    .nav-mobile-brand {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      font-size: 1.2rem;
      font-weight: 700;
    }

    #close-menu-btn {
      padding: 0.5rem;
      background: transparent;
      border: none;
      color: var(--text-primary);
    }

    .nav-mobile-links {
      list-style: none;
      padding: 0;
      margin: 0;
      display: flex;
      flex-direction: column;
      gap: 1rem;
    }

    .nav-mobile-links a {
      display: flex;
      align-items: center;
      gap: 1rem;
      padding: 0.75rem 1rem;
      border-radius: 8px;
      font-weight: 500;
      color: var(--text-secondary);
      transition: background-color 0.2s ease, color 0.2s ease;
    }

    .nav-mobile-links a:hover,
    .nav-mobile-links a.active {
      background-color: var(--bg-tertiary);
      color: var(--accent-color);
    }

    .nav-mobile-links .material-icons-outlined {
      color: var(--text-secondary);
      transition: color 0.2s ease;
    }

    .nav-mobile-links a:hover .material-icons-outlined,
    .nav-mobile-links a.active .material-icons-outlined {
      color: var(--accent-color);
    }

    .nav-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100vw;
      height: 100vh;
      background-color: rgba(0, 0, 0, 0.5);
      z-index: 99;
      opacity: 0;
      visibility: hidden;
      transition: opacity 0.3s ease-in-out, visibility 0.3s ease-in-out;
    }

    .nav-overlay.is-open {
      opacity: 1;
      visibility: visible;
    }

    /* Mobile Theme Toggle */
    .theme-toggle-mobile-wrapper {
      margin-top: auto; /* Pushes the toggle to the bottom */
      padding-top: 1.5rem; /* Space from links above */
      border-top: 1px solid var(--border-color);
      text-align: center;
    }

    .theme-toggle-mobile {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 0.75rem;
      padding: 0.75rem;
      border-radius: 999px;
      background-color: var(--bg-tertiary);
      color: var(--text-primary);
      font-weight: 500;
      cursor: pointer;
      transition: background-color 0.2s ease, color 0.2s ease;
      border: none;
      width: 50px;
      height: 50px;
    }

    .theme-toggle-mobile:hover {
      background-color: var(--border-color);
    }

    .theme-toggle-mobile .material-icons-outlined {
      font-size: 1.5rem;
    }

    .theme-toggle-mobile .moon-icon {
      display: none;
    }

    html[data-theme='dark'] .theme-toggle-mobile .moon-icon {
      display: block;
    }

    html[data-theme='dark'] .theme-toggle-mobile .sun-icon {
      display: none;
    }

    /* Mobile Theme Toggle */
    .theme-toggle-mobile-wrapper {
      margin-top: auto; /* Pushes the toggle to the bottom */
      padding-top: 1.5rem; /* Space from links above */
      border-top: 1px solid var(--border-color);
      text-align: center;
    }

    .theme-toggle-mobile {
      display: inline-flex;
      align-items: center;
      gap: 0.75rem;
      padding: 0.75rem 1.25rem;
      border-radius: 999px;
      background-color: var(--bg-tertiary);
      color: var(--text-primary);
      font-weight: 500;
      cursor: pointer;
      transition: background-color 0.2s ease, color 0.2s ease;
    }

    .theme-toggle-mobile:hover {
      background-color: var(--border-color);
    }

    .theme-toggle-mobile .material-icons-outlined {
      font-size: 1.5rem;
    }

    .theme-toggle-mobile .moon-icon,
    html[data-theme="dark"] .theme-toggle-mobile .sun-icon {
      display: none;
    }

    html[data-theme="dark"] .theme-toggle-mobile .moon-icon {
      display: inline-block;
    }


    /* Dropdown */
    .dropdown {
      position: relative
    }

    .dropdown-toggle {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 10px 12px;
      border-radius: 12px
    }

    .dropdown-toggle:hover {
      background: #f3f6ff
    }

    .dropdown-panel {
      position: absolute;
      top: calc(100% + 10px);
      left: 0;
      min-width: 280px;
      background: var(--bg-secondary);
      border: 1px solid var(--border-color);
      border-radius: 14px;
      box-shadow: var(--shadow);
      padding: 8px;
      display: none;
    }

    .dropdown.open .dropdown-panel {
      display: block
    }

    .dropdown a.item {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 12px;
      border-radius: 10px
    }

    .dropdown a.item:hover {
      background: var(--bg-tertiary);
    }

    .dropdown .material-icons-outlined {
      font-size: 20px;
      color: var(--accent-color)
    }

    /* Hero */
    .hero {
      position: relative;
      overflow: hidden;
      background: var(--bg-primary);
      border-bottom: 1px solid var(--border-color);
      box-shadow:var(--shadow-bm);
      margin-top:70px;
    }

    .hero .wrap {
      display: grid;
      grid-template-columns: 1.15fr .85fr;
      gap: 40px;
      align-items: center;
      padding: 90px 20px
    }

    .eyebrow {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 6px 10px;
      border-radius: 999px;
      background: var(--bg-tertiary);
      color: var(--accent-color);
      font-weight: 600;
      font-size: 12px
    }

    h2.display {
      font-size: 44px;
      line-height: 1.1;
      margin: 16px 0 10px;
      color: var(--text-primary);
      letter-spacing: -.02em
    }

    p.lead {
      font-size: 18px;
      color: var(--text-secondary);
      max-width: 60ch
    }

    .cta {
      display: flex;
      gap: 12px;
      margin-top: 18px;
      flex-wrap: wrap
    }

    .cta .btn {
      padding: 12px 16px;
      border-radius: 12px
    }

    .btn.ghost {
      border: 1px solid #d7def2
    }

    .trust-badges {
      display: flex;
      gap: 16px;
      margin-top: 22px;
      flex-wrap: wrap
    }

    .badge {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 8px 12px;
      border-radius: 999px;
      background: var(--bg-secondary);
      border: 1px solid var(--border-color);
      box-shadow: var(--shadow-sm);
      font-size: 13px;
      color: var(--text-secondary);
    }

    /* Image placeholders */
    .img-ph {
      background: linear-gradient(135deg, #eaf0ff, #f7f9ff);
      border: 1px dashed #cdd8ff;
      border-radius: 16px;
      height: 360px;
      display: grid;
      place-items: center;
      color: #7e8ca6;
      font-weight: 600;
    }

    /* Video placeholders */
    .video-placeholder {
      background: linear-gradient(135deg, #eaf0ff, #f7f9ff);
      border: 1px dashed #cdd8ff;
      border-radius: 16px;
      height: 360px;
      display: grid;
      place-items: center;
      color: #7e8ca6;
      font-weight: 600;
      width: 100%; /* Ensure video fills its container */
      object-fit: cover; /* Ensure video covers the area without distortion */
    }

    /* Sections */
    section {
      padding: 72px 0
    }

    section.alt {
      background: var(--bg-tertiary);
    }

    .sec-head {
      display: flex;
      align-items: flex-end;
      justify-content: space-between;
      gap: 20px;
      margin-bottom: 28px
    }

    .sec-head h3 {
      margin: 0;
      color: var(--text-primary);
      font-size: 28px;
      letter-spacing: -.01em
    }

    .sec-head p {
      color: var(--text-secondary);
      max-width: 70ch;
      margin: 6px 0 0
    }

    /* Feature cards */
    .grid-3 {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 18px
    }

    .card {
      background: var(--bg-secondary);
      border: 1px solid var(--border-color);
      border-radius: 16px;
      padding: 18px;
      box-shadow: var(--shadow-sm);
      transition: transform .2s ease;
    }

    .card:hover {
      transform: translateY(-2px)
    }

    .icon {
      width: 40px;
      height: 40px;
      border-radius: 10px;
      display: grid;
      place-items: center;
      background: var(--bg-tertiary);
      color: var(--accent-color);
      margin-bottom: 10px;
      box-shadow: inset 0 0 0 1px var(--border-color);
    }

    .card h4 {
      margin: 6px 0 6px;
      color: var(--text-primary)
    }

    .muted {
      color: var(--text-secondary)
    }

    /* Split layout */
    .split {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 30px;
      align-items: center
    }

    /* Pricing table */
    .pricing {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 18px
    }

    .price-tile {
      border: 1px solid var(--border-color);
      border-radius: 16px;
      background: var(--bg-secondary);
      padding: 22px;
      box-shadow: var(--shadow-sm)
    }

    .tag {
      display: inline-flex;
      gap: 6px;
      align-items: center;
      padding: 6px 10px;
      border-radius: 999px;
      background: var(--bg-tertiary);
      border: 1px solid var(--border-color);
      color: var(--text-secondary);
      font-size: 12px;
      font-weight: 700
    }

    /* Resources list */
    .res-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 18px
    }

    .res {
      border: 1px solid var(--border-color);
      border-radius: 14px;
      padding: 18px;
      background: var(--bg-secondary);
    }

    /* Contact */
    form {
      display: grid;
      gap: 14px
    }

    .field {
      display: grid;
      gap: 8px
    }

    input,
    textarea {
      border: 1px solid var(--border-color);
      border-radius: 12px;
      padding: 12px 14px;
      font: inherit;
      outline: none;
      background: var(--bg-secondary);
      color: var(--text-primary);
    }

    textarea {
      min-height: 120px;
      resize: vertical
    }

    input:focus,
    textarea:focus {
      border-color: var(--blue);
      box-shadow: 0 0 0 4px var(--ring)
    }

    .contact-grid {
      display: grid;
      grid-template-columns: 1.2fr .8fr;
      gap: 24px
    }

    /* Footer */
    footer {
      padding: 40px 0;
      border-top: 1px solid var(--border-color);
      background: var(--bg-primary);
    }

    .foot-grid {
      display: grid;
      grid-template-columns: 2fr 1fr 1fr 1fr;
      gap: 16px
    }

    .subtle {
      color: var(--text-secondary);
      font-size: 14px;
    }

    /* Utilities */
    .pill {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 6px 10px;
      border-radius: 999px;
      border: 1px solid var(--border-color);
      background: var(--bg-secondary);
      color: var(--text-secondary);
    }

    .sp-8 {
      height: 8px
    }

    .sp-16 {
      height: 16px
    }

    .sp-24 {
      height: 24px
    }

    /* Buttons */
    .btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 10px 14px;
      border-radius: 12px;
      border: 1px solid transparent;
      font-weight: 600;
      transition: all 0.2s ease;
    }

    .btn.primary {
      background: var(--blue);
      border-color: var(--blue);
      color: #fff;
    }

    .btn.primary:hover {
      background: var(--blue-600);
      border-color: var(--blue-600);
    }

    .btn.secondary {
      background: transparent;
      border-color: var(--red);
      color: var(--red);
    }

    .btn.secondary:hover {
      background: var(--red);
      color: #fff;
    }

    /* Responsive */
    @media (max-width: 1024px) {
      .header-nav-desktop {
        display: none;
      }

      .grid-3,
      .pricing,
      .res-grid,
      .foot-grid {
        grid-template-columns: 1fr 1fr
      }

      .hero .wrap {
        grid-template-columns: 1fr;
        gap: 28px
      }

      .split {
        grid-template-columns: 1fr
      }

      .contact-grid {
        grid-template-columns: 1fr
      }
    }

    @media (min-width: 1024px) {
      #burger-menu {
        display: none;
      }

      .header-nav-desktop {
        display: flex;
      }
    }

    @media (max-width: 760px) {

      .grid-3,
      .pricing,
      .res-grid {
        grid-template-columns: 1fr
      }

      .foot-grid {
        grid-template-columns: 1fr 1fr
      }

      h2.display {
        font-size: 34px
      }
    }

    /* Swiper Carousel Styles */
    .blog-swiper {
      width: 100%;
      padding-bottom: 50px; /* Space for pagination */
      overflow: hidden; /* Prevent horizontal overflow */
    }

    .blog-card {
      background: var(--card);
      border: 1px solid #eef2f7;
      border-radius: 16px;
      overflow: hidden;
      box-shadow: var(--shadow-sm);
      transition: transform .2s ease;
      height: 100%; /* Ensure cards take full height of slide */
      display: flex;
      flex-direction: column;
    }

    .blog-card:hover {
      transform: translateY(-2px);
    }

    .blog-card-video {
      width: 100%;
      height: 200px; /* Fixed height for videos */
      object-fit: cover;
      border-bottom: 1px solid #eef2f7;
    }

    .blog-card h4 {
      margin: 15px 18px 5px;
      color: var(--ink);
      font-size: 1.2em;
    }

    .blog-card p.muted {
      margin: 0 18px 15px;
      flex-grow: 1; /* Allow description to take available space */
    }

    .blog-card .read-more {
      display: block;
      padding: 10px 18px 15px;
      color: var(--blue);
      font-weight: 600;
      text-align: right;
    }

    /* Swiper Navigation (Arrows) */
    .swiper-button-next,
    .swiper-button-prev {
      color: var(--blue);
      width: 40px;
      height: 40px;
      background-color: rgba(255, 255, 255, 0.8);
      border-radius: 50%;
      box-shadow: var(--shadow-sm);
      transition: background-color 0.3s ease;
      /* Adjust positioning to prevent overflow */
      top: 50%;
      transform: translateY(-50%);
      margin-top: 0; /* Override default Swiper margin */
      z-index: 10; /* Ensure they are above slides */
    }

    .swiper-button-prev {
      left: 10px; /* Adjust as needed */
    }

    .swiper-button-next {
      right: 10px; /* Adjust as needed */
    }

    .swiper-button-next:hover,
    .swiper-button-prev:hover {
      background-color: var(--blue);
      color: #fff;
    }

    .swiper-button-next::after,
    .swiper-button-prev::after {
      font-size: 1.2em;
    }

    /* Swiper Pagination (Dots) */
    .swiper-pagination-bullet {
      background: var(--muted);
      opacity: 0.7;
      transition: background 0.3s ease;
    }

    .swiper-pagination-bullet-active {
      background: var(--blue);
      opacity: 1;
    }

    /* Responsive adjustments for Swiper */
    @media (max-width: 768px) {
      .swiper-button-next,
      .swiper-button-prev {
        display: none; /* Hide arrows on smaller screens */
      }
    }
  </style>
</head>
<body>

  <!-- ───────── NAVBAR ───────── -->
  <header class="main-header">
    <div class="container">
      <div class="nav">
        <a class="brand" href="#">
          <img src="assets/images/logo.png" alt="Pennieshares Logo" class="logo" aria-hidden="true">
          <h1>Pennieshares</h1>
        </a>

        <nav class="header-nav-desktop" aria-label="Primary">
          <ul>
            <li><a href="#stocks"><span class="material-icons-outlined">show_chart</span>Stocks & ETFs</a></li>
            <li><a href="#api"><span class="material-icons-outlined">hub</span>Business & API</a></li>
            <li><a href="#blog"><span class="material-icons-outlined">article</span>Blog</a></li>
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
        <li><a href="/" class="active"><span class="material-icons-outlined">home</span>Home</a></li>
        <li><a href="/download"><span class="material-icons-outlined">download</span>Download App</a></li>
        <li><a href="#what"><span class="material-icons-outlined">info</span>About</a></li>
        <li><a href="#features"><span class="material-icons-outlined">toggle_on</span>Features</a></li>
        <li><a href="#pricing"><span class="material-icons-outlined">receipt_long</span>Pricing</a></li>
        <li><a href="#contact"><span class="material-icons-outlined">support_agent</span>Contact</a></li>
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
  <!-- ───────── HERO ──────── -->
  <section class="hero" id="home">
    <div class="container wrap">
      <div data-aos="fade-up">
        <span class="eyebrow"><span class="material-icons-outlined" aria-hidden="true">verified_user</span> Open‑Source. Transparent. Secure.</span>
        <h2 class="display">Empowering Investors with <span style="color:var(--blue)">Transparency</span> and <span style="color:var(--red)">Control</span></h2>
        <p class="lead">Pennieshares is an innovative, open‑source brokerage platform designed to give you full control over your investments. Experience a new level of transparency and security in the financial market.</p>
        <div class="cta">
          <a class="btn primary" href="register"><span class="material-icons-outlined">rocket_launch</span> Get Started</a>
          <a class="btn ghost" href="#what"><span class="material-icons-outlined">play_circle</span> Learn More</a>
        </div>
        <div class="trust-badges" data-aos="fade-up" data-aos-delay="200">
          <div class="badge"><span class="material-icons-outlined">code</span> Open‑Source</div>
          <div class="badge"><span class="material-icons-outlined">visibility</span> Full Transparency</div>
          <div class="badge"><span class="material-icons-outlined">shield_lock</span> Advanced Encryption</div>
        </div>
      </div>
      <video src="assets/videos/hero.mp4" muted playsinline class="video-placeholder boomerang-video" data-aos="fade-left"></video>
    </div>
  </section>

  <!-- ───────── WHAT IS ───────── -->
  <section id="what">
    <div class="container split">
      <div data-aos="fade-up">
        <div class="sec-head">
          <h3>What is Pennieshares?</h3>
        </div>
        <p>Pennieshares is an innovative, open‑source brokerage platform that provides a secure and transparent environment for buying and selling shares. Our platform is designed to be user‑friendly, making it easy for both new and experienced investors to get started. <strong>Sign up today</strong> and take control of your financial future.</p>
        <div class="sp-16"></div>
        <div style="display:flex;gap:10px;flex-wrap:wrap">
          <span class="pill"><span class="material-icons-outlined">lock_open_right</span> Open‑Source</span>
          <span class="pill"><span class="material-icons-outlined">visibility</span> Transparent</span>
          <span class="pill"><span class="material-icons-outlined">bolt</span> Fast</span>
          <span class="pill"><span class="material-icons-outlined">devices</span> Cross‑Platform</span>
        </div>
      </div>
      <video src="assets/videos/checkportfolio.mp4" muted playsinline class="video-placeholder boomerang-video" data-aos="fade-right"></video>
    </div>
  </section>

  <!-- ───────── OPEN-SOURCE & TRANSPARENCY ───────── -->
  <section id="features" class="alt">
    <div class="container">
      <div class="sec-head" data-aos="fade-up">
        <h3>Open‑Source &amp; Transparency</h3>
        <p>Our open‑source approach ensures full transparency, fosters trust, and invites community‑driven improvements.</p>
      </div>

      <div class="grid-3">
        <div class="card" data-aos="fade-up" data-aos-delay="100">
          <div class="icon"><span class="material-icons-outlined">visibility</span></div>
          <h4>Full Transparency</h4>
          <p class="muted">Every aspect of our platform is open for review, ensuring complete transparency.</p>
        </div>
        <div class="card" data-aos="fade-up" data-aos-delay="200">
          <div class="icon"><span class="material-icons-outlined">groups</span></div>
          <h4>Community‑Driven Security</h4>
          <p class="muted">Our community actively contributes to the security and improvement of the platform.</p>
        </div>
        <div class="card" data-aos="fade-up" data-aos-delay="300">
          <div class="icon"><span class="material-icons-outlined">tune</span></div>
          <h4>User Control</h4>
          <p class="muted">You have full control over your investments with a clear understanding of how your assets are managed.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- ───────── SECURITY & CONTROL ───────── -->
  <section>
    <div class="container split">
      <video src="assets/videos/encryption.mp4" muted playsinline class="video-placeholder boomerang-video" data-aos="fade-right"></video>
      <div>
        <div class="sec-head" data-aos="fade-up">
          <h3>Security &amp; Control</h3>
        </div>
        <p data-aos="fade-up" data-aos-delay="100">At Pennieshares, <strong>your security is our priority</strong>. You maintain full control over your shares and money, with robust data protection in place.</p>
        <div class="grid-3" style="margin-top:12px">
          <div class="card" data-aos="fade-up" data-aos-delay="200"><div class="icon"><span class="material-icons-outlined">gpp_maybe</span></div><h4>Full Control</h4><p class="muted">No third‑party interference. You’re always in charge.</p></div>
          <div class="card" data-aos="fade-up" data-aos-delay="300"><div class="icon"><span class="material-icons-outlined">encrypted</span></div><h4>Advanced Encryption</h4><p class="muted">Modern cryptography protects your data and transactions.</p></div>
          <div class="card" data-aos="fade-up" data-aos-delay="400"><div class="icon"><span class="material-icons-outlined">dns</span></div><h4>Secure Servers</h4><p class="muted">Hardened infrastructure ensures integrity and uptime.</p></div>
        </div>
      </div>
    </div>
  </section>

  <!-- ───────── FEES & COMMISSIONS ───────── -->
  <section id="pricing" class="alt">
    <div class="container">
      <div class="sec-head" data-aos="fade-up">
        <h3>Transparent &amp; Competitive Pricing</h3>
        <p>Clear fees with flexible options to suit your investment style.</p>
      </div>

      <div class="pricing">
        <div class="price-tile" data-aos="fade-up" data-aos-delay="100">
          <div class="tag"><span class="material-icons-outlined">task_alt</span> No Hidden Fees</div>
          <div class="sp-16"></div>
          <p class="muted">Simple, straightforward pricing — what you see is what you pay.</p>
        </div>
        <div class="price-tile" data-aos="fade-up" data-aos-delay="200">
          <div class="tag" style="background:var(--bg-tertiary);border-color:var(--border-color);color:var(--accent-color)">
            <span class="material-icons-outlined">speed</span> Competitive Rates
          </div>
          <div class="sp-16"></div>
          <p class="muted">Get excellent value across trades and services.</p>
        </div>
        <div class="price-tile" data-aos="fade-up" data-aos-delay="300">
          <div class="tag" style="background:var(--bg-tertiary);border-color:var(--border-color);color:var(--text-secondary)">
            <span class="material-icons-outlined">tune</span> Flexible Options
          </div>
          <div class="sp-16"></div>
          <p class="muted">Choose a plan tailored to your volume and strategy.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- ───────── RESOURCES & SUPPORT ───────── -->
  <section id="resources">
    <div class="container">
      <div class="sec-head" data-aos="fade-up">
        <h3>Investment Advice &amp; Support</h3>
        <p>We don’t offer personalized advice, but we provide the tools and knowledge to help you invest with confidence.</p>
      </div>

      <div class="res-grid">
        <div class="res" data-aos="fade-up" data-aos-delay="100">
          <h4><span class="material-icons-outlined" aria-hidden="true">menu_book</span> Educational Resources</h4>
          <p class="muted">Access articles, tutorials, and guides to grow your investing skills.</p>
        </div>
        <div class="res" data-aos="fade-up" data-aos-delay="200">
          <h4><span class="material-icons-outlined" aria-hidden="true">forum</span> Community Forums</h4>
          <p class="muted">Engage with other investors — share insights, ask questions, learn faster.</p>
        </div>
        <div class="res" data-aos="fade-up" data-aos-delay="300">
          <h4><span class="material-icons-outlined" aria-hidden="true">support_agent</span> Email &amp; Chat Support</h4>
          <p class="muted">Need platform help? Our team is available via email and chat.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- ───────── ACCOUNT MANAGEMENT ───────── -->
  <section class="alt">
    <div class="container">
      <div class="sec-head" data-aos="fade-up">
        <h3>Convenient Account Management</h3>
        <p>Manage your Pennieshares account anywhere — online or through our mobile app. Monitor activity, automate transactions, and stay in control.</p>
      </div>

      <div class="grid-3">
        <div class="card" data-aos="fade-up" data-aos-delay="100">
          <div class="icon"><span class="material-icons-outlined">laptop_mac</span></div>
          <h4>Online Access</h4>
          <p class="muted">Access your account from any device with an internet connection.</p>
        </div>
        <div class="card" data-aos="fade-up" data-aos-delay="200">
          <div class="icon"><span class="material-icons-outlined">phone_iphone</span></div>
          <h4>Mobile App</h4>
          <p class="muted">On‑the‑go portfolio tracking and order management.</p>
        </div>
        <div class="card" data-aos="fade-up" data-aos-delay="300">
          <div class="icon"><span class="material-icons-outlined">autorenew</span></div>
          <h4>Automated Transactions</h4>
          <p class="muted">Set up recurring investments and withdrawals with ease.</p>
        </div>
      </div>

      <div class="sp-24"></div>
      <video src="assets/videos/webmobile.mp4" muted playsinline class="video-placeholder boomerang-video" data-aos="fade-up"></video>
    </div>
  </section>

  <!-- ───────── CONTENT PREVIEWS (for dropdown items) ───────── -->
  <section id="stocks">
    <div class="container">
      <div class="sec-head" data-aos="fade-up">
        <h3>Stocks &amp; ETFs</h3>
        <p>Discover, analyze, and invest in a wide range of assets with transparent execution.</p>
      </div>
      <video src="assets/videos/marketchart.mp4" muted playsinline class="video-placeholder boomerang-video" data-aos="fade-up"></video>
    </div>
  </section>

  <section id="api" class="alt">
    <div class="container">
      <div class="sec-head" data-aos="fade-up">
        <h3>Business &amp; API</h3>
        <p>Build on Pennieshares — integrate market data and trading with secure, well‑documented APIs.</p>
      </div>
      <video src="assets/videos/developersapi.mp4" muted playsinline class="video-placeholder boomerang-video" data-aos="fade-up"></video>
    </div>
  </section>

  <section id="blog">
    <div class="container">
      <div class="sec-head" data-aos="fade-up">
        <h3>Blog</h3>
        <p>Insights, platform updates, and long‑form explainers for smarter investing.</p>
      </div>
      <!-- Swiper -->
      <div class="swiper-container blog-swiper" data-aos="fade-up">
        <div class="swiper-wrapper">
          <!-- Blog Post 1 -->
          <div class="swiper-slide">
            <div class="blog-card">
              <video src="assets/videos/checkportfolio.mp4" muted playsinline class="blog-card-video"></video>
              <h4>The Future of Investing</h4>
              <p class="muted">Explore how technology is reshaping the investment landscape.</p>
              <a href="#" class="read-more">Read More &rarr;</a>
            </div>
          </div>
          <!-- Blog Post 2 -->
          <div class="swiper-slide">
            <div class="blog-card">
              <video src="assets/videos/developersapi.mp4" muted playsinline class="blog-card-video"></video>
              <h4>Understanding Market Trends</h4>
              <p class="muted">A deep dive into identifying and leveraging market trends.</p>
              <a href="#" class="read-more">Read More &rarr;</a>
            </div>
          </div>
          <!-- Blog Post 3 -->
          <div class="swiper-slide">
            <div class="blog-card">
              <video src="assets/videos/encryption.mp4" muted playsinline class="blog-card-video"></video>
              <h4>Security in Open-Source Platforms</h4>
              <p class="muted">How open-source models enhance security and transparency.</p>
              <a href="#" class="read-more">Read More &rarr;</a>
            </div>
          </div>
          <!-- Blog Post 4 -->
          <div class="swiper-slide">
            <div class="blog-card">
              <video src="assets/videos/hero.mp4" muted playsinline class="blog-card-video"></video>
              <h4>Your First Investment: A Guide</h4>
              <p class="muted">Everything you need to know to make your first investment.</p>
              <a href="#" class="read-more">Read More &rarr;</a>
            </div>
          </div>
        </div>
        <!-- Add Pagination -->
        <div class="swiper-pagination"></div>
        <!-- Add Navigation -->
        <div class="swiper-button-next"></div>
        <div class="swiper-button-prev"></div>
      </div>
    </div>
  </section>

  <section id="tips" class="alt">
    <div class="container">
      <div class="sec-head" data-aos="fade-up">
        <h3>Investment Tips</h3>
        <p>Actionable, evergreen tips to help you avoid common pitfalls and build better habits.</p>
      </div >
      <img src="assets/videos/tipschecklist.jpeg" alt="Tips / checklist illustration placeholder" class="img-ph" data-aos="fade-up">
    </div>
  </section>

  <section id="news">
    <div class="container">
      <div class="sec-head" data-aos="fade-up">
        <h3>News</h3>
        <p>Stay informed with curated market news and platform announcements.</p>
      </div>
      <video src="assets/videos/newsfeed.mp4" muted playsinline class="video-placeholder boomerang-video" data-aos="fade-up"></video>
    </div>
  </section>

  <section id="stock-month" class="alt">
    <div class="container">
      <div class="sec-head" data-aos="fade-up">
        <h3>Stock of the Month</h3>
        <p>A monthly deep‑dive into a notable company or ETF — thesis, risks, and key metrics.</p>
      </div>
      <video src="assets/videos/stockofmonth.mp4" muted playsinline class="video-placeholder boomerang-video" data-aos="fade-up"></video>
    </div>
  </section>

  <!-- ───────── CONTACT ───────── -->
  <section id="contact">
    <div class="container">
      <div class="sec-head" data-aos="fade-up">
        <h3>Get in Touch</h3>
        <p>Questions about the platform? Send us a message — we’d love to help.</p>
      </div>
      <div class="contact-grid">
        <form onsubmit="event.preventDefault(); alert('Thanks! We will reach out shortly.');" data-aos="fade-right">
          <div class="field">
            <label for="name">Name</label>
            <input id="name" name="name" placeholder="Your Name" required>
          </div>
          <div class="field">
            <label for="email">Email</label>
            <input id="email" name="email" placeholder="Your Email" type="email" required>
          </div>
          <div class="field">
            <label for="message">Message</label>
            <textarea id="message" name="message" placeholder="Your Message" required></textarea>
          </div>
          <button class="btn primary" type="submit"><span class="material-icons-outlined">send</span> Send Message</button>
        </form>

        <div data-aos="fade-left">
          <div class="res">
            <h4><span class="material-icons-outlined">phone_in_talk</span> Phone</h4>
            <p class="muted">+234 907 5174 301</p>
          </div>
          <div class="sp-16"></div>
          <div class="res">
            <h4><span class="material-icons-outlined">mail</span> Email</h4>
            <p class="muted">penniepoint@gmail.com</p>
          </div>
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

  <script>
    const themeToggle = document.getElementById('theme-toggle-mobile');
    const html = document.documentElement;

    const applyTheme = (theme) => {
        html.setAttribute('data-theme', theme);
        const sunIcon = themeToggle.querySelector('.sun-icon');
        const moonIcon = themeToggle.querySelector('.moon-icon');
        if (theme === 'dark') {
            sunIcon.style.display = 'none';
            moonIcon.style.display = 'block';
        } else {
            sunIcon.style.display = 'block';
            moonIcon.style.display = 'none';
        }
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

    themeToggle.addEventListener('click', () => {
      const currentTheme = html.getAttribute('data-theme');
      const newTheme = currentTheme === 'light' ? 'dark' : 'light';
      applyTheme(newTheme);
      localStorage.setItem('theme', newTheme);
    });

    // Simple interactions: dropdown, mobile menu, and outside-click close
    const burgerMenu = document.getElementById('burger-menu');
    const mobileNav = document.getElementById('nav-mobile');
    const closeMenuBtn = document.getElementById('close-menu-btn');
    const navOverlay = document.getElementById('nav-overlay');

    burgerMenu.addEventListener('click', () => {
      mobileNav.classList.add('is-open');
      navOverlay.classList.add('is-open');
      document.body.classList.add('body-no-scroll');
    });

    closeMenuBtn.addEventListener('click', () => {
      mobileNav.classList.remove('is-open');
      navOverlay.classList.remove('is-open');
      document.body.classList.remove('body-no-scroll');
    });

    navOverlay.addEventListener('click', () => {
      mobileNav.classList.remove('is-open');
      navOverlay.classList.remove('is-open');
      document.body.classList.remove('body-no-scroll');
    });

    // Smooth scroll for internal links and active link handling
    document.querySelectorAll('a[href^="#"]').forEach(a => {
      a.addEventListener('click', e => {
        const id = a.getAttribute('href').slice(1);
        const target = document.getElementById(id);
        if (target) {
          e.preventDefault();
          window.scrollTo({
            top: target.offsetTop - 70,
            behavior: 'smooth'
          });
          // close mobile panel after navigation
          if (mobileNav.classList.contains('is-open')) {
            mobileNav.classList.remove('is-open');
            navOverlay.classList.remove('is-open');
          }
        }
      });
    });

    // Active link highlighting on scroll
    const sections = document.querySelectorAll('section');
    const navLi = document.querySelectorAll(".nav-mobile-links a");
    window.onscroll = () => {
      var current = "";

      sections.forEach((section) => {
        const sectionTop = section.offsetTop;
        if (pageYOffset >= sectionTop - 71) {
          current = section.getAttribute("id");
        }
      });

      navLi.forEach((a) => {
        a.classList.remove("active");
        if (a.getAttribute('href').slice(1) === current) {
          a.classList.add("active");
        }
      });
    };
  </script>
  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
  <script src="script.js"></script>
  <script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>
  <script>
    // Initialize Swiper
    var swiper = new Swiper('.blog-swiper', {
      slidesPerView: 1,
      spaceBetween: 30,
      loop: true,
      pagination: {
        el: '.swiper-pagination',
        clickable: true,
      },
      navigation: {
        nextEl: '.swiper-button-next',
        prevEl: '.swiper-button-prev',
      },
      breakpoints: {
        640: {
          slidesPerView: 2,
          spaceBetween: 20,
        },
        1024: {
          slidesPerView: 3,
          spaceBetween: 30,
        },
      },
    });

    // Intersection Observer for videos
    const videoObserver = new IntersectionObserver((entries, observer) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          // Play video when it enters viewport
          entry.target.play();
        } else {
          // Pause and reset video when it leaves viewport
          entry.target.pause();
          entry.target.currentTime = 0;
        }
      });
    }, { threshold: 0.5 }); // Trigger when 50% of video is visible

    document.querySelectorAll('.boomerang-video').forEach(video => {
      videoObserver.observe(video);
    });
  </script>
</body>
</html>
