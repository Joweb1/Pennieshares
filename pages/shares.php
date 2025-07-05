<?php
require_once __DIR__ . '/../src/functions.php';
check_auth();

$user = $_SESSION['user'];
$userId = $user['id'];

// Fetch all of the user's assets with their type details
$userAssets = getUserAssets($pdo, $userId);

// --- CALCULATIONS ---

$totalAssetWorth = 0;
$totalReturn = 0;
$assetAllocation = [];
$activeAssetsCount = 0;

// Calculate Total Return from all asset_profit transactions
$returnStmt = $pdo->prepare("SELECT SUM(amount) FROM wallet_transactions WHERE user_id = ? AND type = 'asset_profit'");
$returnStmt->execute([$userId]);
$totalReturn = $returnStmt->fetchColumn() ?? 0;

// Process each asset
foreach ($userAssets as $asset) {
    if ($asset['current_status'] === 'Active') {
        $totalAssetWorth += $asset['type_payout_cap'];
        $category = $asset['asset_type_name']; // Using asset name as category for this example
        if (!isset($assetAllocation[$category])) {
            $assetAllocation[$category] = 0;
        }
        $assetAllocation[$category]++;
        $activeAssetsCount++;
    }
}

// Sort allocation by count, descending
arsort($assetAllocation);

// Calculate percentages for allocation
if ($activeAssetsCount > 0) {
    foreach ($assetAllocation as $category => &$count) {
        $count = round(($count / $activeAssetsCount) * 100);
    }
}


// Include the header template
require_once __DIR__ . '/../assets/template/intro-template.php';
?>

<style>
    /* CSS Variables for consistent theming */
    :root {
      --primary-text: #0d141c;
      --secondary-text: #49739c;
      --positive: #078838;
      --negative: #d93025;
      --background: #f8fafc;
      --card-bg: #e7edf4;
      --card-bg-hover: #dfe6f0;
      --border: #cedbe8;
      --accent: #0c7ff2;
      --header-bg: #ffffff;
      --shadow-color: rgba(0,0,0,0.1);
    }

    html[data-theme="dark"] {
      --primary-text: #e1e8f0;
      --secondary-text: #7b94b1;
      --positive: #2ddc71;
      --negative: #ff5252;
      --background: #0d141c;
      --card-bg: #1a2635;
      --card-bg-hover: #223041;
      --border: #2c3e53;
      --accent: #1da1f2;
      --header-bg: #15202b;
      --shadow-color: rgba(0,0,0,0.4);
    }

    /* Base styles */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    html, body {
      min-height: 100vh;
    }

    body {
      font-family: 'Inter', sans-serif;
      background-color: var(--background);
      color: var(--primary-text);
      display: flex;
      flex-direction: column;
      overflow-x: hidden;
      -webkit-font-smoothing: antialiased;
      -moz-osx-font-smoothing: grayscale;
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
      max-width: 960px;
      width: 100%;
      margin: 0 auto;
      flex-grow: 1;
    }

    /* Main content styles */
    .main-content {
      padding: 24px;
      display: flex;
      justify-content: center;
      flex-grow: 1;
    }

    .portfolio-header {
      padding: 16px 0;
    }

    .portfolio-title {
      font-size: 32px;
      font-weight: 800;
      line-height: 1.2;
      letter-spacing: -0.02em;
    }

    .portfolio-subtitle {
      color: var(--secondary-text);
      font-size: 16px;
      font-weight: 400;
      margin-top: 4px;
    }

    /* Cards section */
    .cards-container {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 16px;
      padding: 16px 0;
    }

    .card {
      background-color: var(--card-bg);
      border-radius: 12px;
      padding: 24px;
      display: flex;
      flex-direction: column;
      gap: 8px;
      transition: background-color 0.3s ease;
    }
    .card-header {
        display: flex;
        align-items: center;
        gap: 8px;
        color: var(--secondary-text);
    }
    .card-title {
      font-size: 14px;
      font-weight: 500;
      color: var(--primary-text);
    }
    .card-value {
      font-size: 24px;
      font-weight: 700;
      letter-spacing: -0.01em;
    }
    .card-change {
      font-size: 14px;
      font-weight: 500;
    }
    .positive { color: var(--positive); }
    .negative { color: var(--negative); }

    /* Table styles */
    .table-section {
      padding: 16px 0;
    }

    .section-title {
      font-size: 22px;
      font-weight: 700;
      letter-spacing: -0.02em;
      padding-bottom: 12px;
    }

    .table-container {
      overflow-x: auto;
      border-radius: 12px;
      border: 1px solid var(--border);
      background-color: var(--header-bg);
      transition: background-color 0.3s ease, border-color 0.3s ease;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      min-width: 600px;
    }
    th, td {
      text-align: left;
      padding: 16px;
      font-size: 14px;
      vertical-align: middle;
      white-space: nowrap;
    }
    th {
      font-weight: 500;
      color: var(--secondary-text);
      border-bottom: 1px solid var(--border);
      transition: border-color 0.3s ease;
    }
    tr:not(:last-child) td {
      border-bottom: 1px solid var(--border);
      transition: border-color 0.3s ease;
    }
    .asset-name { font-weight: 500; }
    .asset-value { color: var(--secondary-text); }

    .progress-container {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .progress-bar-bg {
      width: 88px;
      height: 4px;
      border-radius: 2px;
      background-color: var(--border);
      transition: background-color 0.3s ease;
    }
    .progress-bar {
      height: 100%;
      border-radius: 2px;
      background-color: var(--accent);
    }
    .status-btn {
      min-width: 84px;
      cursor: pointer;
      border-radius: 8px;
      height: 32px;
      padding: 0 12px;
      color: var(--primary-text);
      font-weight: 500;
      border: none;
      transition: background-color 0.3s ease;
    }
    .status-active { background-color: rgba(34, 197, 94, 0.2); color: #22c55e; }
    .status-completed { background-color: rgba(59, 130, 246, 0.2); color: #3b82f6; }
    .status-expired { background-color: rgba(239, 68, 68, 0.2); color: #ef4444; }


    /* Responsive styles */
    @media (max-width: 768px) {
        .main-content {
            padding: 16px;
        }
      
        .cards-container {
            grid-template-columns: 1fr;
        }

        .portfolio-title { font-size: 28px; }
    }
</style>
    
<main class="main-content">
    <div class="layout-content-container">
        <div class="portfolio-header">
            <h1 class="portfolio-title">Assets</h1>
            <p class="portfolio-subtitle">Track your shares and assets allocation</p>
        </div>

        <div class="cards-container">
            <div class="card">
                <div class="card-header">
                    <svg class="icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 256 256"><path d="M224,40H32A16,16,0,0,0,16,56V192a16,16,0,0,0,16,16H224a16,16,0,0,0,16-16V56A16,16,0,0,0,224,40Zm-40,96a16,16,0,1,1,16-16A16,16,0,0,1,184,136Z"></path></svg>
                    <p class="card-title">Total Asset Worth</p>
                </div>
                <p class="card-value">SV <?php echo number_format($totalAssetWorth, 2); ?></p>
            </div>
            <div class="card">
                <div class="card-header">
                    <svg class="icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 256 256"><path d="M235.15,114.85l-96-96a8,8,0,0,0-11.3,0l-96,96a8,8,0,0,0,11.3,11.3L128,42.3,223.85,138.15a8,8,0,0,0,11.3-11.3ZM128,144a24,24,0,1,0-24-24A24,24,0,0,0,128,144Zm86.63,60.89-28.52-19a64.07,64.07,0,1,0-72.22,0l-28.52,19A8,8,0,0,0,88,224H216a8,8,0,0,0,2.63-15.11Z"></path></svg>
                    <p class="card-title">Total Return</p>
                </div>
                <p class="card-value positive">+SV <?php echo number_format($totalReturn, 2); ?></p>
            </div>
            <div class="card">
                <div class="card-header">
                    <svg class="icon" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 256 256"><path d="M128,24a104,104,0,1,0,104,104A104.11,104.11,0,0,0,128,24Zm0,192a88,88,0,1,1,88-88A88.1,88.1,0,0,1,128,216Zm44-88a44,44,0,0,1-75.16,31.13,8,8,0,0,1,11.32-11.32A28,28,0,1,0,128,100a8,8,0,0,1-16,0,44,44,0,0,1,60-31.13,8,8,0,1,1,11.32,11.32A43.89,43.89,0,0,1,172,128Z"></path></svg>
                    <p class="card-title">Asset Allocation</p>
                </div>
                <?php
                    $mainCategory = "No active assets";
                    $subCategories = "N/A";
                    if (!empty($assetAllocation)) {
                        $mainCategory = key($assetAllocation) . ' ' . current($assetAllocation) . '%';
                        array_shift($assetAllocation); // Remove the main category
                        $subCategories = implode(', ', array_map(function($cat, $perc) {
                            return "$cat $perc%";
                        }, array_keys($assetAllocation), $assetAllocation));
                    }
                ?>
                <p class="card-value"><?php echo htmlspecialchars($mainCategory); ?></p>
                <p class="card-change"><?php echo htmlspecialchars($subCategories); ?></p>
            </div>
        </div>

        <div class="table-section">
            <h2 class="section-title">My Assets</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Asset</th>
                            <th>Asset Worth</th>
                            <th>Total Profit</th>
                            <th>Progress</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($userAssets)): ?>
                            <tr><td colspan="5" style="text-align: center;">You do not own any assets yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($userAssets as $asset): ?>
                                <?php
                                    $totalProfit = $asset['total_earned'];
                                    $assetWorth = $asset['type_payout_cap'];
                                    $progress = ($assetWorth > 0) ? ($totalProfit / $assetWorth) * 100 : 0;
                                    $progress = min(100, $progress); // Cap progress at 100%
                                ?>
                                <tr>
                                    <td>
                                        <div class="asset-name"><?php echo htmlspecialchars($asset['asset_type_name']); ?></div>
                                        <div class="asset-value"><?php echo htmlspecialchars(getAssetBranding($asset['asset_type_id'])['category']); ?></div>
                                    </td>
                                    <td>
                                        <div class="asset-name">SV <?php echo number_format($assetWorth, 2); ?></div>
                                    </td>
                                    <td>
                                        <div class="card-change positive">+SV <?php echo number_format($totalProfit, 2); ?></div>
                                    </td>
                                    <td>
                                        <div class="progress-container">
                                            <div class="progress-bar-bg">
                                                <div class="progress-bar" style="width: <?php echo $progress; ?>%;"></div>
                                            </div>
                                            <span><?php echo number_format($progress, 0); ?>%</span>
                                        </div>
                                    </td>
                                    <td>
                                        <button class="status-btn status-<?php echo strtolower($asset['current_status']); ?>">
                                            <?php echo htmlspecialchars($asset['current_status']); ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<?php
// Include the footer template
require_once __DIR__ . '/../assets/template/end-template.php';
?>
