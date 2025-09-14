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

  
  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js" defer></script>
  <script src="script.js" defer></script>
  <script src="https://unpkg.com/swiper/swiper-bundle.min.js" defer></script>
  
</body>
</html>
