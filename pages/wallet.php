<?php
require_once __DIR__ . '/../src/init.php';
require_once __DIR__ . '/../src/kyc_functions.php';

$kyc_status = getKycStatus($pdo, $user['id']);
$show_kyc_popup = false;
if ((!$kyc_status || $kyc_status['status'] !== 'verified') && !isset($_SESSION['kyc_popup_shown'])) {
    $show_kyc_popup = true;
    $_SESSION['kyc_popup_shown'] = true;
}

if (isset($_SESSION['show_kyc_popup'])) {
    $show_kyc_popup = true;
    unset($_SESSION['show_kyc_popup']);
}

require_once __DIR__ . '/../src/functions.php';
require_once __DIR__ . '/../src/assets_functions.php';

check_auth();

$loggedInUser = $_SESSION['user'];
$loggedInUserId = $loggedInUser['id'];

// Fetch wallet balance
$walletBalanceSV = getUserWalletBalance($pdo, $loggedInUserId);

// --- Broker specific stats ---
if ($loggedInUser['is_broker']) {
    $brokerStats = getBrokerReferralStats($pdo, $loggedInUserId);
}

// Calculate Total Return from user's total_return column
$stmt = $pdo->prepare("SELECT total_return FROM users WHERE id = ?");
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

// --- Daily Random Performance Data Generation ---
$today = date('Y-m-d');

// Fetch current user data including new performance columns
$currentUserData = getUserByIdOrName($pdo, $loggedInUserId);

if ($currentUserData['last_performance_update'] !== $today) {
    // It's a new day, generate new random data
    $newChartData = [
        '6D' => [
            'points' => [],
            'performance' => round(mt_rand(-100, 100) / 10, 2), // -10.00 to 10.00
            'change' => round(mt_rand(-50, 50) / 10, 2) // -5.00 to 5.00
        ],
        '6W' => [
            'points' => [],
            'performance' => round(mt_rand(-100, 100) / 10, 2),
            'change' => round(mt_rand(-50, 50) / 10, 2)
        ],
        '6M' => [
            'points' => [],
            'performance' => round(mt_rand(-100, 100) / 10, 2),
            'change' => round(mt_rand(-50, 50) / 10, 2)
        ]
    ];

    // Generate random points for each timeframe
    foreach ($newChartData as $timeframe => &$data) {
        for ($i = 0; $i < 7; $i++) { // 7 points for the chart
            $data['points'][] = mt_rand(20, 130); // Values between 20 and 130 for chart Y-axis
        }
    }

    // Store the new data in the database
    $updateStmt = $pdo->prepare("
        UPDATE users SET 
            performance_chart_data = :chart_data,
            performance_value = :performance_value,
            performance_change = :performance_change,
            last_performance_update = :last_update
        WHERE id = :user_id
    ");

    $updateStmt->execute([
        ':chart_data' => json_encode($newChartData['6D']), // Store 6D data as default
        ':performance_value' => $newChartData['6D']['performance'],
        ':performance_change' => $newChartData['6D']['change'],
        ':last_update' => $today,
        ':user_id' => $loggedInUserId
    ]);

    $chartDataForJs = $newChartData;
} else {
    // Same day, use stored data
    $chartDataForJs = [
        '6D' => json_decode($currentUserData['performance_chart_data'], true),
        '6W' => [
            'points' => [], // These will need to be generated or stored separately if needed
            'performance' => $currentUserData['performance_value'],
            'change' => $currentUserData['performance_change']
        ],
        '6M' => [
            'points' => [], // These will need to be generated or stored separately if needed
            'performance' => $currentUserData['performance_value'],
            'change' => $currentUserData['performance_change']
        ]
    ];
    // For 6W and 6M, if you want dynamic data, you'd need to store them or generate them based on a seed
    foreach (['6W', '6M'] as $timeframe) {
        for ($i = 0; $i < 7; $i++) {
            $chartDataForJs[$timeframe]['points'][] = mt_rand(20, 130);
        }
    }
}

// Include the intro template
require_once __DIR__ . '/../assets/template/intro-template.php';
?>

  <style>
    /* CSS Variables for Theming */
    :root {
      --bg-color: var(--bg-primary-light);
      --card-bg-color: var(--bg-secondary-light);
      --card-bg-hover: var(--bg-tertiary-light);
      --text-primary: var(--text-primary-light);
      --text-secondary: var(--text-secondary-light);
      --border-color: var(--border-color-light);
      --header-bg: var(--bg-primary-light);
      --shadow-color: rgba(0, 0, 0, 0.1);
      --positive-color: #22c55e;
      --negative-color: #ef4444;
      --chart-line-color: var(--accent-color-light);
      --chart-gradient-start: #e7edf4;
      --chart-gradient-end: rgba(231, 237, 244, 0);
      --glow-color: rgba(59, 130, 246, 0.5);
    }

    html[data-theme="dark"] {
      --bg-color: var(--bg-primary-dark);
      --card-bg-color: var(--bg-secondary-dark);
      --card-bg-hover: var(--bg-tertiary-dark);
      --text-primary: var(--text-primary-dark);
      --text-secondary: var(--text-secondary-dark);
      --border-color: var(--border-color-dark);
      --header-bg: var(--bg-primary-dark);
      --shadow-color: rgba(255, 255, 255, 0.1);
      --positive-color: #4ade80;
      --negative-color: #f87171;
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
      max-width: 1200px;
      width: 100%;
      margin: 0 auto;
      flex-grow: 1;
      padding: 0 16px;
    }
    
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
      display: flex;
      flex-wrap: wrap;
      gap: 16px;
      margin:0;
      padding;0;
    }
    
    .stat-card {
      /* flex: 1 1 280px; Responsive wrapping for desktop */
      padding: 24px;
      background-color: var(--card-bg-color);
      border-radius: 12px;
      transition: background-color 0.3s ease;
      height: auto; /* Ensure height fits content */
    }

    /* On small and medium screens, stack the cards vertically */
    @media (max-width: 768px) {
        .stats-grid {
            flex-direction: column;
        }
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
    position:relative;
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

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }
    
    .fade-in {
      animation: fadeIn 0.5s ease forwards;
    }

    /* KYC Popup Styles */
    .kyc-popup-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 1000;
    }

    .kyc-popup-content {
        background-color: var(--card-bg-color);
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        text-align: center;
        max-width: 350px;
        width: 90%;
        border: 1px solid var(--border-color);
        transition: background-color 0.3s ease, border-color 0.3s ease;
    }

    .kyc-popup-message {
        color: var(--text-primary);
        margin-bottom: 20px;
        font-size: 1.1em;
        transition: color 0.3s ease;
    }

    .kyc-popup-actions {
        display: flex;
        justify-content: center;
        gap: 15px;
    }

    .kyc-popup-button {
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-weight: bold;
        transition: background-color 0.3s ease, color 0.3s ease;
    }

    .kyc-popup-button-primary {
        background-color: var(--accent-color);
        color: var(--accent-text);
    }

    .kyc-popup-button-primary:hover {
        opacity: 0.9;
    }

    .kyc-popup-button-secondary {
        background-color: var(--bg-tertiary);
        color: var(--text-primary);
    }

    .kyc-popup-button-secondary:hover {
        background-color: var(--border-color);
    }

    /* Broker Stats Card V2 Styles */
    .broker-stats-card-v2 {
        position: relative;
        overflow: hidden;
        background: linear-gradient(135deg, #3B82F6, #EF4444);
        color: #ffffff;
        padding: 1.5rem;
    }
    .broker-stats-card-v2 .stat-title {
        color: #ffffff;
    }
    .broker-card-bg-circle-1, .broker-card-bg-circle-2 {
        position: absolute;
        background-color: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        z-index: 0;
    }
    .broker-card-bg-circle-1 { top: -3rem; right: -3rem; width: 12rem; height: 12rem; }
    .broker-card-bg-circle-2 { bottom: -4rem; left: -4rem; width: 16rem; height: 16rem; opacity: 0.5; }

    .broker-stats-v2-content {
        position: relative;
        z-index: 1;
        margin-top: 1.5rem;
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    .broker-stats-v2-row {
        display: flex;
        gap: 1rem;
    }
    .broker-stat-v2-item, .broker-stat-v2-item-full {
        background-color: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(4px);
        border-radius: 0.75rem;
        padding: 1rem;
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    .broker-stat-v2-item { width: 50%; }
    .broker-stat-v2-item-full { width: 100%; }

    .broker-stat-v2-icon-wrapper {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 1.5rem;
        color: #ffffff;
    }
    .broker-stat-v2-icon-wrapper.users { background-color: #3B82F6; }
    .broker-stat-v2-icon-wrapper.assets { background-color: #10b981; }
    .broker-stat-v2-icon-wrapper.bonus { background-color: #EF4444; }

    .broker-stat-v2-text .broker-stat-v2-label {
        font-size: 0.875rem;
        color: rgba(255, 255, 255, 0.8);
        margin: 0;
    }
    .broker-stat-v2-text .broker-stat-v2-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: #ffffff;
        margin: 0;
    }

    /* Portfolio Summary Card Styles */
    .portfolio-summary-content {
        display: flex;
        flex-direction: column;        
        gap: 1rem;        
        margin-top: 1rem;
    }
    .portfolio-summary-item {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    .portfolio-summary-icon-wrapper {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        color: #fff;
        font-size: 1.25rem;
    }
    .portfolio-summary-icon-wrapper.total-return { background-color: #22c55e; } /* Green */
    .portfolio-summary-icon-wrapper.assets-worth { background-color: #8b5cf6; } /* Violet */

    .portfolio-summary-text .label {
        font-size: 0.875rem;
        color: var(--text-secondary);
        margin: 0;
    }
    .portfolio-summary-text .value {
        font-size: 1.125rem;
        font-weight: 600;
        color: var(--text-primary);
        margin: 0;
    }

  </style>
      <div class="content-wrapper">
      <div class="portfolio-header">
        <p class="portfolio-title">My Wallet</p>
        <p class="portfolio-subtitle">Track your investments and performance</p>
      </div>
      
      <div class="stats-grid">

        <?php if ($loggedInUser['is_broker']): ?>
        <div class="stat-card broker-stats-card-v2">
            <div class="broker-card-bg-circle-1"></div>
            <div class="broker-card-bg-circle-2"></div>
            <div class="stat-header">
                <p class="stat-title">Broker Referrals</p>
                <span class="material-icons" style="color: rgba(255,255,255,0.8);">card_travel</span>
            </div>
            <div class="broker-stats-v2-content">
                <div class="broker-stats-v2-row">
                    <div class="broker-stat-v2-item">
                        <div class="broker-stat-v2-icon-wrapper users">
                            <span class="material-icons">group</span>
                        </div>
                        <div class="broker-stat-v2-text">
                            <p class="broker-stat-v2-label">Clients</p>
                            <p class="broker-stat-v2-value"><?php echo $brokerStats['total_referred_users']; ?></p>
                        </div>
                    </div>
                    <div class="broker-stat-v2-item">
                        <div class="broker-stat-v2-icon-wrapper assets">
                            <span class="material-icons">account_balance_wallet</span>
                        </div>
                        <div class="broker-stat-v2-text">
                            <p class="broker-stat-v2-label">Assets</p>
                            <p class="broker-stat-v2-value"><?php echo $brokerStats['total_assets_of_referred_users']; ?></p>
                        </div>
                    </div>
                </div>
                <div class="broker-stats-v2-row">
                    <div class="broker-stat-v2-item-full">
                        <div class="broker-stat-v2-icon-wrapper bonus">
                            <span class="material-icons">card_giftcard</span>
                        </div>
                        <div class="broker-stat-v2-text">
                            <p class="broker-stat-v2-label">Bonus</p>
                            <p class="broker-stat-v2-value">SV <?php echo number_format($brokerStats['total_referral_bonus'], 2); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

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
                <p class="stat-title">Portfolio Summary</p>
                <span class="material-icons" >calendar_today</span>
            </div>
            <div class="portfolio-summary-content">
                <div class="portfolio-summary-item">
                    <div class="portfolio-summary-icon-wrapper total-return">
                        <span class="material-icons">trending_up</span>
                    </div>
                    <div class="portfolio-summary-text">
                        <p class="label">Total Return</p>
                        <p class="value" id="total-return">SV <?php echo number_format($totalReturnSV, 2); ?></p>
                    </div>
                </div>
                <div class="portfolio-summary-item">
                    <div class="portfolio-summary-icon-wrapper assets-worth">
                        <span class="material-icons">diamond</span>
                    </div>
                    <div class="portfolio-summary-text">
                        <p class="label">Assets Worth</p>
                        <p class="value" id="assets-worth">SV <?php echo number_format($assetsWorthSV, 2); ?></p>
                    </div>
                </div>
            </div>
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
<?php if ($show_kyc_popup): ?>
<div class="kyc-popup-overlay" id="kyc-popup-overlay">
    <div class="kyc-popup-content">
        <p class="kyc-popup-message">Please complete your KYC verification to access all features.</p>
        <div class="kyc-popup-actions">
            <a href="/kyc" class="kyc-popup-button kyc-popup-button-primary">Go to KYC</a>
            <button class="kyc-popup-button kyc-popup-button-secondary" onclick="document.getElementById('kyc-popup-overlay').style.display='none';">Later</button>
        </div>
    </div>
</div>
<?php endif; ?>
  <script>
document.addEventListener('DOMContentLoaded', () => {
    // --- DOM Elements ---
    const themeToggleButtons = document.querySelectorAll('.theme-toggle');
    const chartArea = document.getElementById('chart-area');
    const chartLine = document.getElementById('chart-line');
    const performanceValue = document.getElementById('performance-value');
    const performanceChangeEl = document.getElementById('performance-change');
    const selectedTimeframeEl = document.getElementById('selected-timeframe');
    const timeframeTabs = document.querySelectorAll('.timeframe-tab');
    const chartLabelsContainer = document.getElementById('chart-labels');

    const profitWidget = document.getElementById('profit-widget');
    const profitRateEl = document.getElementById('profit-rate');
    
    // --- PHP-generated Chart Data ---
    const chartData = <?php echo json_encode($chartDataForJs); ?>; // Must be inside .php file

    // --- Theme Toggle ---
    const applyTheme = (theme) => {
        document.documentElement.setAttribute('data-theme', theme);
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
        if (timeframe === '6W') return ['5W', '4W', '3W', '2W', 'Last Wk', 'This Wk'];
        if (timeframe === '6M') {
            const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
            const currentMonth = new Date().getMonth();
            return Array.from({ length: 6 }, (_, i) => months[(currentMonth - 5 + i + 12) % 12]);
        }
        return [];
    };

    const updateChart = (timeframe) => {
        const data = chartData[timeframe];
        if (!data) return;

        const performanceSign = data.performance >= 0 ? '+' : '';
        performanceValue.textContent = `${performanceSign}${data.performance.toFixed(2)}%`;
        performanceValue.style.color = data.performance >= 0 ? 'var(--positive-color)' : 'var(--negative-color)';

        const changeSign = data.change >= 0 ? '+' : '';
        performanceChangeEl.textContent = `${changeSign}${data.change.toFixed(2)}%`;
        performanceChangeEl.style.color = data.change >= 0 ? 'var(--positive-color)' : 'var(--negative-color)';
        
        selectedTimeframeEl.textContent = timeframe;
        
        chartLine.setAttribute('d', generatePath(data.points));
        chartArea.setAttribute('d', generateAreaPath(data.points));

        chartLabelsContainer.innerHTML = '';
        getChartLabels(timeframe).forEach(label => {
            const p = document.createElement('p');
            p.className = 'month-label';
            p.textContent = label;
            chartLabelsContainer.appendChild(p);
        });
    };

    timeframeTabs.forEach(tab => {
        tab.addEventListener('click', () => {
            timeframeTabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            updateChart(tab.dataset.timeframe);
        });
    });

    // --- Wallet Conversion ---
    const SV_TO_NAIRA_RATE = 100;
    const walletBalanceSVEl = document.getElementById('wallet-balance');
    const walletNairaEl = document.getElementById('wallet-naira');
    const assetsWorthSVEl = document.getElementById('assets-worth');
    const totalReturnEl = document.getElementById('total-return');

    const convertAndDisplay = () => {
        const walletBalanceSV = parseFloat(walletBalanceSVEl.textContent.replace('SV ', '').replace(/,/g, '')) || 0;
        const assetsWorthSV =  parseFloat(assetsWorthSVEl.textContent.replace('SV ', '').replace(/,/g, '')) || 0;
        const totalReturnSV = parseFloat(totalReturnEl.textContent.replace('SV ', '').replace(/,/g, '')) || 0;

        const walletBalanceNaira = walletBalanceSV * SV_TO_NAIRA_RATE;
        
        walletNairaEl.textContent = `₦ ${walletBalanceNaira.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    };

    // --- Profit Widget ---
    const updateProfitWidget = () => {
        const newRate = parseFloat((Math.random() * 2.5 - 1).toFixed(2)); // number
        const sign = newRate >= 0 ? '+' : '';
        profitRateEl.textContent = `${sign}${newRate}%`;
        profitRateEl.style.color = newRate >= 0 ? 'var(--positive-color)' : 'var(--negative-color)';

        profitWidget.classList.add('updated');
        setTimeout(() => profitWidget.classList.remove('updated'), 600);
    };
    
    // --- Init ---
    const savedTheme = localStorage.getItem('theme') || 'light';
    applyTheme(savedTheme);
    
    updateChart('6D');
    convertAndDisplay();
    updateProfitWidget();
    setInterval(updateProfitWidget, 6000);
});
</script>

<?php
// Include the end template
require_once __DIR__ . '/../assets/template/end-template.php';
?>
