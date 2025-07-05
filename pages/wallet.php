<?php
require_once __DIR__ . '/../src/functions.php';
require_once __DIR__ . '/../src/assets_functions.php';

check_auth();

$loggedInUser = $_SESSION['user'];
$loggedInUserId = $loggedInUser['id'];

// Fetch wallet balance
$walletBalanceSV = getUserWalletBalance($pdo, $loggedInUserId);

// Calculate Total Return (sum of asset_profit transactions)
$stmt = $pdo->prepare("SELECT SUM(amount) FROM wallet_transactions WHERE user_id = ? AND type = 'asset_profit'");
$stmt->execute([$loggedInUserId]);
$totalReturnSV = $stmt->fetchColumn() ?? 0;

// Calculate Assets Worth (sum of payout_cap for active assets)
$now = date('Y-m-d H:i:s');
$stmt = $pdo->prepare("
    SELECT SUM(at.payout_cap) 
    FROM assets a
    JOIN asset_types at ON a.asset_type_id = at.id
    WHERE a.user_id = :user_id
    AND a.is_completed = 0
    AND a.is_manually_expired = 0
    AND (a.expires_at IS NULL OR a.expires_at > :now)
");
$stmt->execute([':user_id' => $loggedInUserId, ':now' => $now]);
$assetsWorthSV = $stmt->fetchColumn() ?? 0;

// Include the intro template
require_once __DIR__ . '/../assets/template/intro-template.php';
?>

  <style>
    /* CSS Variables for Theming */
    :root {
      --bg-color: var(--bg-primary-light);
      --card-bg-color: var(--bg-secondary-light); /* Changed to secondary for consistency */
      --card-bg-hover: var(--bg-tertiary-light); /* Changed to tertiary for consistency */
      --text-primary: var(--text-primary-light);
      --text-secondary: var(--text-secondary-light);
      --border-color: var(--border-color-light);
      --header-bg: var(--bg-primary-light); /* Changed to primary for consistency */
      --shadow-color: rgba(0, 0, 0, 0.1);
      --positive-color: #22c55e; /* Green */
      --negative-color: #ef4444; /* Red */
      --chart-line-color: var(--accent-color-light);
      --chart-gradient-start: #e7edf4; /* Light blue-grey */
      --chart-gradient-end: rgba(231, 237, 244, 0);
      --glow-color: rgba(59, 130, 246, 0.5);
    }

    html[data-theme="dark"] {
      --bg-color: var(--bg-primary-dark);
      --card-bg-color: var(--bg-secondary-dark); /* Changed to secondary for consistency */
      --card-bg-hover: var(--bg-tertiary-dark); /* Changed to tertiary for consistency */
      --text-primary: var(--text-primary-dark);
      --text-secondary: var(--text-secondary-dark);
      --border-color: var(--border-color-dark);
      --header-bg: var(--bg-primary-dark); /* Changed to primary for consistency */
      --shadow-color: rgba(255, 255, 255, 0.1);
      --positive-color: #4ade80; /* Lighter Green */
      --negative-color: #f87171; /* Lighter Red */
      --chart-line-color: var(--accent-color-dark);
      --chart-gradient-start: #2a4365;
      --chart-gradient-end: rgba(42, 67, 101, 0);
      --glow-color: rgba(96, 165, 250, 0.6);
    }

    /* Base styles */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Inter', sans-serif;
      background-color: var(--bg-color);
      color: var(--text-primary);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      overflow-x: hidden;
      transition: background-color 0.3s ease, color 0.3s ease;
    }

    /* Icon styles */
    .icon {
        width: 20px;
        height: 20px;
        flex-shrink: 0;
    }

    /* Layout */
    .layout-container {
      display: flex;
      flex-direction: column;
      flex-grow: 1;
      min-height: 100vh;
    }

    .layout-content-container {
      display: flex;
      flex-direction: column;
      max-width: 960px;
      width: 100%;
      margin: 0 auto;
      flex-grow: 1;
      padding: 0 16px;
    }
    
    /* Header (from intro-template, adjusted for wallet page) */
    /* Removed header styles as they are now handled by intro-template */
    
    /* Portfolio header */
    .portfolio-header {
      padding: 24px 0;
    }
    
    .portfolio-title {
      color: var(--text-primary);
      font-size: 32px;
      font-weight: 800;
      letter-spacing: -0.015em;
      line-height: 1.2;
    }
    
    .portfolio-subtitle {
      color: var(--text-secondary);
      font-size: 14px;
      font-weight: 400;
      line-height: 1.5;
      margin-top: 4px;
    }
    
    /* Stats grid */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 16px;
    }
    
    .stat-card {
      padding: 24px;
      background-color: var(--card-bg-color);
      border-radius: 12px;
      transition: background-color 0.3s ease;
    }

    .stat-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        color: var(--text-secondary);
    }
    
    .stat-title {
      color: var(--text-primary);
      font-size: 16px;
      font-weight: 500;
      line-height: 1.5;
    }
    
    .stat-value {
      color: var(--text-primary);
      font-size: 24px;
      font-weight: 800;
      line-height: 1.2;
      letter-spacing: -0.015em;
      margin-top: 8px;
    }
    .stat-naira {
    opacity:.95;
    font-weight:800;
    font-size:16px;
    line-height:35px;
    width:100%;
    text-align:right;
    }
    
    /* Performance section */
    .performance-section {
      padding-bottom: 16px;
    }
    
    .performance-title {
      color: var(--text-primary);
      font-size: 22px;
      font-weight: 800;
      line-height: 1.2;
      letter-spacing: -0.015em;
      padding: 16px 0 12px;
    }
    
    .performance-container {
      background: var(--header-bg);
      border: 1px solid var(--border-color);
      border-radius: 12px;
      padding: 24px 16px;
      display: flex;
      flex-wrap: wrap;
      gap: 16px;
      transition: background-color 0.3s ease, border-color 0.3s ease;
    }
    
    .performance-content {
      flex: 1;
      min-width: 288px;
    }
    
    .performance-label {
      color: var(--text-primary);
      font-size: 16px;
      font-weight: 500;
      line-height: 1.5;
    }
    
    .performance-value {
      color: var(--text-primary);
      font-size: 32px;
      font-weight: 800;
      line-height: 1.2;
      letter-spacing: -0.015em;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      margin-top: 4px;
    }
    
    .performance-change {
      display: flex;
      gap: 8px;
      margin-top: 4px;
    }
    
    .timeframe-label {
      color: var(--text-secondary);
      font-size: 16px;
      font-weight: 400;
      line-height: 1.5;
    }
    
    .change-value {
      font-size: 16px;
      font-weight: 500;
      line-height: 1.5;
    }
    
    .chart-container {
      min-height: 180px;
      flex-grow: 1;
      padding: 16px 0;
    }
    
    .months-container {
      display: flex;
      justify-content: space-around;
      margin-top: 16px;
    }
    
    .month-label {
      color: var(--text-secondary);
      font-size: 13px;
      font-weight: 700;
      line-height: 1.5;
      letter-spacing: 0.015em;
    }
    
    /* Timeframe selector */
    .timeframe-selector {
      padding: 12px 0;
    }
    
    .timeframe-tabs {
      display: flex;
      height: 40px;
      background-color: var(--card-bg-color);
      border-radius: 8px;
      padding: 4px;
      transition: background-color 0.3s ease;
    }
    
    .timeframe-tab {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      height: 100%;
      overflow: hidden;
      border-radius: 8px;
      color: var(--text-secondary);
      font-size: 14px;
      font-weight: 500;
      line-height: 1.5;
      cursor: pointer;
      transition: all 0.3s ease;
    }
    
    .timeframe-tab input {
      display: none;
    }
    
    .timeframe-tab.active {
      background-color: var(--header-bg);
      color: var(--text-primary);
      box-shadow: 0 1px 3px var(--shadow-color);
    }
    
    /* Chart styling */
    .chart-svg {
      width: 100%;
      height: 148px;
    }
    
    .chart-line {
      stroke: var(--chart-line-color);
      stroke-width: 3;
      stroke-linecap: round;
      fill: none;
      transition: d 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    }
    
    .chart-area {
      fill: url(#chart-gradient);
      transition: d 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    }
    
    #chart-gradient-start {
      stop-color: var(--chart-gradient-start);
    }
    
    #chart-gradient-end {
      stop-color: var(--chart-gradient-end);
    }

    /* Floating Profit Widget */
    .profit-widget {
      position: fixed;
      bottom: 24px;
      right: 24px;
      width: 140px;
      height: 140px;
      background-color: var(--header-bg);
      border-radius: 50%;
      border: 1px solid var(--border-color);
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      text-align: center;
      z-index: 999;
      padding: 16px;
      box-shadow: 0 4px 12px var(--shadow-color), 0 0 0px 4px var(--bg-color), 0 0 20px -5px var(--glow-color);
      animation: pulse-glow 3s infinite ease-in-out;
      transition: all 0.3s ease;
    }

    .widget-label {
      font-size: 12px;
      font-weight: 500;
      color: var(--text-secondary);
    }

    .widget-value {
      font-size: 24px;
      font-weight: 700;
      margin-top: 4px;
      transition: color 0.3s ease;
    }
    
    /* Blinking effect on update */
    .profit-widget.updated {
      animation: pulse-glow 3s infinite ease-in-out, quick-blink 0.6s 1;
    }

    @keyframes pulse-glow {
      0%, 100% {
        box-shadow: 0 4px 12px var(--shadow-color), 0 0 0px 4px var(--bg-color), 0 0 20px -5px var(--glow-color);
      }
      50% {
        box-shadow: 0 4px 16px var(--shadow-color), 0 0 0px 4px var(--bg-color), 0 0 30px 0px var(--glow-color);
      }
    }
    
    @keyframes quick-blink {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.05); }
    }


    /* Animation */
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }
    
    .fade-in {
      animation: fadeIn 0.5s ease forwards;
    }

    /* Mobile Responsive Styles */
    @media (max-width: 900px) {
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 768px) {
       
        .portfolio-title {
            font-size: 28px;
        }

        .performance-container {
            flex-direction: column;
        }
        
        .profit-widget {
          width: 110px;
          height: 110px;
          bottom: 16px;
          right: 16px;
        }
        .widget-value {
          font-size: 20px;
        }
    }
     @media (max-width: 480px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }

        .portfolio-title {
            font-size: 24px;
        }

        .performance-value {
            font-size: 28px;
        }
    }
  </style>
      <div class="content-wrapper">
      <div class="portfolio-header">
        <p class="portfolio-title">My Wallet</p>
        <p class="portfolio-subtitle">Track your investments and performance</p>
      </div>
      
      <div class="stats-grid">
      
        <div class="stat-card">
            <div class="stat-header">
                <p class="stat-title">Wallet Balance</p>
                <svg class="icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 256 256"><path d="M216,48H48A24,24,0,0,0,24,72V184a24,24,0,0,0,24,24H216a16,16,0,0,0,16-16V168a16,16,0,0,0-16-16H64V136h24a8,8,0,0,0,0-16H64V104h88a8,8,0,0,0,0-16H64V72H216a8,8,0,0,1,8,8v8a8,8,0,0,0,16,0V80A32.09,32.09,0,0,0,208,48Z"></path></svg>
            </div>
            <p class="stat-value" id="wallet-balance">SV <?php echo number_format($walletBalanceSV, 2); ?></p>
            <p class="stat-naira" id="wallet-naira"></p>
        </div>
        <div class="stat-card">
            <div class="stat-header">
                <p class="stat-title">Total Return</p>
                <svg class="icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 256 256"><path d="M224,144v56a16,16,0,0,1-16,16H48a16,16,0,0,1-16-16V56A16,16,0,0,1,48,40h56a8,8,0,0,1,0,16H48v88a8,8,0,0,0,16,0V88a8,8,0,0,1,16,0v48a8,8,0,0,0,16,0V88a8,8,0,0,1,16,0v24a8,8,0,0,0,16,0V96a8,8,0,0,1,16-16h11.23a8,8,0,0,1,6.65,3.56L224,124.77V144Z"></path></svg>
            </div>
          <p class="stat-value" id="total-return">SV <?php echo number_format($totalReturnSV, 2); ?></p>
        </div>
        <div class="stat-card">
            <div class="stat-header">
                <p class="stat-title">Assets Worth</p>
                <svg class="icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 256 256"><path d="M224,40H32A16,16,0,0,0,16,56V80a8,8,0,0,0,16,0V56H224V80a8,8,0,0,0,16,0V56A16,16,0,0,0,224,40ZM128,88,32,128v64a16,16,0,0,0,16,16H208a16,16,0,0,0,16-16V128Zm80,104H48V136.65l80-32,80,32Z"></path></svg>
            </div>
          <p class="stat-value" id="assets-worth">SV <?php echo number_format($assetsWorthSV, 2); ?></p>
          <p class="stat-naira" id="assets-worth-naira"></p>
        </div>
      </div>
      
      <div class="performance-section">
        <h2 class="performance-title">Performance</h2>
        <div class="performance-container">
          <div class="performance-content">
            <p class="performance-label">Investment Performance</p>
            <p class="performance-value" id="performance-value">+0.00%</p>
            <div class="performance-change">
              <p class="timeframe-label" id="selected-timeframe">6D</p>
              <p class="change-value" id="performance-change">+0.00%</p>
            </div>
            <div class="chart-container">
              <svg class="chart-svg" viewBox="-3 0 478 150" fill="none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
                <defs>
                  <linearGradient id="chart-gradient" x1="50%" y1="0%" x2="50%" y2="100%">
                    <stop id="chart-gradient-start" offset="0%" />
                    <stop id="chart-gradient-end" offset="100%" />
                  </linearGradient>
                </defs>
                <path id="chart-area" class="chart-area" d=""></path>
                <path id="chart-line" class="chart-line" d=""></path>
              </svg>
              <div class="months-container" id="chart-labels"></div>
            </div>
          </div>
        </div>
      </div>
      
      <div class="timeframe-selector">
        <div class="timeframe-tabs">
          <label class="timeframe-tab active" data-timeframe="6D">
            <span>6D</span>
            <input type="radio" name="timeframe" value="6D" checked>
          </label>
          <label class="timeframe-tab" data-timeframe="6W">
            <span>6W</span>
            <input type="radio" name="timeframe" value="6W">
          </label>
          <label class="timeframe-tab" data-timeframe="6M">
            <span>6M</span>
            <input type="radio" name="timeframe" value="6M">
          </label>
        </div>
      </div>
  
  <!-- Floating Profit Widget -->
  <div id="profit-widget" class="profit-widget">
    <span class="widget-label">Real-time Profit</span>
    <span id="profit-rate" class="widget-value">+0.00%</span>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', () => {
        // --- DOM Elements ---
        const body = document.body;
        const themeToggleButtons = document.querySelectorAll('.theme-toggle');
        const menuToggleButton = document.getElementById('menu-toggle');
        const navPanel = document.getElementById('nav-mobile'); // Corrected ID
        const overlay = document.getElementById('nav-overlay'); // Corrected ID

        const chartArea = document.getElementById('chart-area');
        const chartLine = document.getElementById('chart-line');
        const performanceValue = document.getElementById('performance-value');
        const performanceChangeEl = document.getElementById('performance-change');
        const selectedTimeframeEl = document.getElementById('selected-timeframe');
        const timeframeTabs = document.querySelectorAll('.timeframe-tab');
        const chartLabelsContainer = document.getElementById('chart-labels');

        const profitWidget = document.getElementById('profit-widget');
        const profitRateEl = document.getElementById('profit-rate');
        
        // --- Sample Data (for Investment Performance - leave as is) ---
        const chartData = {
            '6D': { points: [109, 21, 41, 93, 33, 101, 61], performance: 12.34, change: 12.34 },
            '6W': { points: [90, 45, 75, 110, 60, 95, 70], performance: 8.75, change: 8.75 },
            '6M': { points: [70, 100, 50, 120, 80, 60, 90], performance: -3.20, change: -3.20 }
        };

        // --- Theme Toggle ---
        const applyTheme = (theme) => {
            document.documentElement.setAttribute('data-theme', theme); // Apply to html element
            document.querySelectorAll('.icon-sun').forEach(icon => icon.style.display = theme === 'light' ? 'block' : 'none');
            document.querySelectorAll('.icon-moon').forEach(icon => icon.style.display = theme === 'dark' ? 'block' : 'none');
            localStorage.setItem('theme', theme);
        };

        themeToggleButtons.forEach(button => {
            button.addEventListener('click', () => {
                const currentTheme = document.documentElement.getAttribute('data-theme');
                const newTheme = currentTheme === 'light' ? 'dark' : 'light';
                applyTheme(newTheme);
            });
        });


        // --- Chart Logic ---
        const generatePath = (points) => {
            const width = 472;
            const step = width / (points.length - 1);
            let path = `M0 ${points[0]}`;
            for (let i = 1; i < points.length; i++) {
                const x = i * step;
                const prevX = (i - 1) * step;
                const controlX1 = prevX + step / 2;
                const controlX2 = x - step / 2;
                path += ` C ${controlX1} ${points[i-1]}, ${controlX2} ${points[i]}, ${x} ${points[i]}`;
            }
            return path;
        };
        
        const generateAreaPath = (points) => {
            const path = generatePath(points);
            const width = 472;
            return `${path} L ${width} 150 L 0 150 Z`;
        };

        const getChartLabels = (timeframe) => {
            if (timeframe === '6D') {
                const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                const today = new Date().getDay();
                return Array.from({ length: 6 }, (_, i) => days[(today - 5 + i + 7) % 7]);
            }
            if (timeframe === '6W') {
                return ['5W', '4W', '3W', '2W', 'Last Wk', 'This Wk'];
            }
            if (timeframe === '6M') {
                const monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
                const currentMonth = new Date().getMonth();
                return Array.from({ length: 6 }, (_, i) => monthNames[(currentMonth - 5 + i + 12) % 12]);
            }
            return [];
        };

        const updateChart = (timeframe) => {
            const data = chartData[timeframe];
            if (!data) return;

            // Update text elements
            const performanceSign = data.performance >= 0 ? '+' : '';
            performanceValue.textContent = `${performanceSign}${data.performance.toFixed(2)}%`;
            performanceValue.style.color = data.performance >= 0 ? 'var(--positive-color)' : 'var(--negative-color)';

            const changeSign = data.change >= 0 ? '+' : '';
            performanceChangeEl.textContent = `${changeSign}${data.change.toFixed(2)}%`;
            performanceChangeEl.style.color = data.change >= 0 ? 'var(--positive-color)' : 'var(--negative-color)';
            
            selectedTimeframeEl.textContent = timeframe;
            
            // Update chart paths with transition
            const newLinePath = generatePath(data.points);
            const newAreaPath = generateAreaPath(data.points);
            chartLine.setAttribute('d', newLinePath);
            chartArea.setAttribute('d', newAreaPath);

            // Update chart labels
            chartLabelsContainer.innerHTML = '';
            const labels = getChartLabels(timeframe);
            labels.forEach(label => {
                const p = document.createElement('p');
                p.className = 'month-label';
                p.textContent = label;
                chartLabelsContainer.appendChild(p);
            });
        };

        // --- Timeframe Selection ---
        timeframeTabs.forEach(tab => {
            tab.addEventListener('click', () => {
                timeframeTabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                const timeframe = tab.dataset.timeframe;
                updateChart(timeframe);
            });
        });

        // --- Wallet Balance and Assets Worth Conversion ---
        const SV_TO_NAIRA_RATE = 100; // 1 SV = 100 Naira

        const walletBalanceSVEl = document.getElementById('wallet-balance');
        const walletNairaEl = document.getElementById('wallet-naira');
        const assetsWorthSVEl = document.getElementById('assets-worth');
        const assetsWorthNairaEl = document.getElementById('assets-worth-naira');

        const convertAndDisplay = () => {
            const walletBalanceSV = parseFloat(walletBalanceSVEl.textContent.replace('SV ', '').replace(/,/g, ''));
            const assetsWorthSV = parseFloat(assetsWorthSVEl.textContent.replace('SV ', '').replace(/,/g, ''));

            const walletBalanceNaira = walletBalanceSV * SV_TO_NAIRA_RATE;
            const assetsWorthNaira = assetsWorthSV * SV_TO_NAIRA_RATE;

            walletNairaEl.textContent = `₦ ${walletBalanceNaira.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
            assetsWorthNairaEl.textContent = `₦ ${assetsWorthNaira.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        };

        // --- Profit Widget Logic ---
        const updateProfitWidget = () => {
            // Simulate a new profit rate
            const newRate = (Math.random() * 2.5 - 1).toFixed(2); // Random rate from -1.00 to +1.50
            const sign = newRate >= 0 ? '+' : '';

            // Update text and color
            profitRateEl.textContent = `${sign}${newRate}%`;
            profitRateEl.style.color = newRate >= 0 ? 'var(--positive-color)' : 'var(--negative-color)';

            // Trigger "blink" animation
            profitWidget.classList.add('updated');
            setTimeout(() => {
                profitWidget.classList.remove('updated');
            }, 600);
        };
        
        // --- Initialization ---
        const savedTheme = localStorage.getItem('theme') || 'light';
        applyTheme(savedTheme);
        
        updateChart('6D'); // Initial chart load
        convertAndDisplay(); // Initial conversion and display

        // No need for randomizeValues as data comes from PHP now
        // setInterval(randomizeValues, 7000); 

        setInterval(updateProfitWidget, 6000); // Update profit widget every 6 seconds
        updateProfitWidget(); // Initial call
    });
  </script>

<?php
// Include the end template
require_once __DIR__ . '/../assets/template/end-template.php';
?>
