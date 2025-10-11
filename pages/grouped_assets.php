<?php
require_once __DIR__ . '/../src/init.php';

$user = $_SESSION['user'];
$userId = $user['id'];

// Get asset_type_id from URL
$assetTypeId = $_GET['asset_type_id'] ?? null;

if (!$assetTypeId) {
    // Redirect or show an error if no asset type is specified
    header('Location: /shares');
    exit();
}

// Fetch the details of the asset type
$assetTypeStmt = $pdo->prepare("SELECT * FROM asset_types WHERE id = ?");
$assetTypeStmt->execute([$assetTypeId]);
$assetType = $assetTypeStmt->fetch(PDO::FETCH_ASSOC);

if (!$assetType) {
    // Redirect or show an error if asset type is invalid
    header('Location: /shares');
    exit();
}

// Fetch all individual assets for this user and asset type
$assetsStmt = $pdo->prepare(
    "SELECT *, (total_generational_received + total_shared_received) as total_earned
     FROM assets 
     WHERE user_id = ? AND asset_type_id = ? 
     ORDER BY created_at DESC"
);
$assetsStmt->execute([$userId, $assetTypeId]);
$individualAssets = $assetsStmt->fetchAll(PDO::FETCH_ASSOC);

$completedAssetsCount = 0;
foreach ($individualAssets as $asset) {
    if ($asset['is_completed'] && !$asset['is_sold']) {
        $completedAssetsCount++;
    }
}

$page_title = htmlspecialchars($assetType['name']) . " Details";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title><?php echo $page_title; ?></title>
<script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet"/>
<style>
    :root {
      --primary-text: #1f2937;
      --secondary-text: #6b7280;
      --positive: #16a34a;
      --negative: #dc2626;
      --background: #f9fafb;
      --card-bg: #ffffff;
      --card-bg-hover: #f9fafb;
      --border: #e5e7eb;
      --accent: #2563eb;
      --header-bg: #ffffff;
      --shadow-color: rgba(0,0,0,0.05);
    }

    html[data-theme="dark"] {
      --primary-text: #f9fafb;
      --secondary-text: #9ca3af;
      --positive: #4ade80;
      --negative: #f87171;
      --background: #111827;
      --card-bg: #1f2937;
      --card-bg-hover: #374151;
      --border: #374151;
      --accent: #3b82f6;
      --header-bg: #1f2937;
      --shadow-color: rgba(0,0,0,0.2);
    }
    body {
        font-family: 'Roboto', sans-serif;
        background-color: var(--background);
        color: var(--primary-text);
    }
    .main-content {
      padding: 1rem;
    }
    .table-section {
      padding: 1rem 0;
    }
    .section-title {
      font-size: 1rem;
      font-weight: 700;
      letter-spacing: -0.02em;
      padding-bottom: 0.75rem;
      color: var(--primary-text);
    }
    .table-container {
      overflow-x: auto;
      border-radius: 0.75rem;
      border: 1px solid var(--border);
      background-color: var(--header-bg);
    }
    table {
      width: 100%;
      border-collapse: collapse;
      min-width: 600px;
    }
    th, td {
      text-align: left;
      padding: 1rem;
      font-size: 0.7rem;
      vertical-align: middle;
      white-space: nowrap;
    }
    th {
      font-weight: 500;
      color: var(--secondary-text);
      border-bottom: 1px solid var(--border);
    }
    tr:not(:last-child) td {
      border-bottom: 1px solid var(--border);
    }
    .asset-value {
        color: var(--secondary-text);
    }
    .positive {
        color: var(--positive);
    }
    .progress-container {
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }
    .progress-bar-bg {
      width: 5.5rem;
      height: 0.25rem;
      border-radius: 0.125rem;
      background-color: var(--border);
    }
    .progress-bar {
      height: 100%;
      border-radius: 0.125rem;
      background-color: var(--accent);
    }
    .status-btn {
      min-width: 5.25rem;
      cursor: pointer;
      border-radius: 0.5rem;
      height: 2rem;
      padding: 0 0.75rem;
      color: var(--primary-text);
      font-weight: 500;
      border: none;
      font-size: 0.7rem;
    }
    .status-active { background-color: rgba(34, 197, 94, 0.2); color: #22c55e; }
    .status-completed { background-color: rgba(59, 130, 246, 0.2); color: #3b82f6; }
    .status-expired { background-color: rgba(239, 68, 68, 0.2); color: #ef4444; }
    .back-chevron {
        color: var(--primary-text);
    }
</style>
</head>
<body>
<div class="max-w-md mx-auto pb-20">
  <header class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
    <a href="/shares" class="material-icons back-chevron">chevron_left</a>
    <h1 class="font-bold text-base text-primary"><?php echo htmlspecialchars($assetType['name']); ?></h1>
    <div></div>
  </header>

  <main class="main-content">
    <div class="flex items-center mb-2">
      <img alt="Asset Logo" id="assetLogo" class="h-20 w-20 mr-4 rounded-md" src="<?php echo htmlspecialchars($assetType['image_link']); ?>"/>
      <div>
        <h2 class="font-bold text-xl" id="assetName"><?php echo htmlspecialchars($assetType['name']); ?></h2>
      </div>
    </div>

    <div class="mb-3">
      <p id="priceDisplay" class="text-2xl font-bold">SV<?php echo number_format($assetType['price'], 2); ?></p>
    </div>

    <div class="flex justify-between items-center mb-3 text-xs text-gray-600 dark:text-gray-400 gap-2">
        <a href="/buy_shares?asset_type_id=<?php echo $assetTypeId; ?>" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg shadow-md transform hover:-translate-y-0.5 transition text-xs">Buy More</a>
        <?php if ($completedAssetsCount > 0): ?>
            <a href="/buy_shares?asset_type_id=<?php echo $assetTypeId; ?>" class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded-lg shadow-md transform hover:-translate-y-0.5 transition text-xs">Sell Completed</a>
        <?php endif; ?>
    </div>

    <div class="table-section">
        <h2 class="section-title">My Assets</h2>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Date Purchased</th>
                        <th>Total Earned</th>
                        <th>Estimated Quantity</th>
                        <th>Progress</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($individualAssets)): ?>
                        <tr><td colspan="5" style="text-align: center;">You do not have any assets of this type yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($individualAssets as $asset): ?>
                            <?php
                                $payoutCap = $assetType['payout_cap'];
                                $progress = ($payoutCap > 0) ? ($asset['total_earned'] / $payoutCap) * 100 : 0;
                                $progress = min(100, $progress); // Cap progress at 100%
                                $estimatedQuantity = ($assetType['dividing_price'] > 0) ? (($assetType['price'] * 100) / $assetType['dividing_price']) : 0;
                                $status = 'Active';
                                $statusClass = 'status-active';
                                if ($asset['is_sold']) {
                                    $status = 'Sold';
                                    $statusClass = 'status-expired';
                                } elseif ($asset['is_completed']) {
                                    $status = 'Completed';
                                    $statusClass = 'status-completed';
                                } elseif ($asset['is_manually_expired']) {
                                    $status = 'Expired';
                                    $statusClass = 'status-expired';
                                }
                            ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($asset['created_at'])); ?></td>
                                <td>
                                    <div class="asset-value positive">+SV <?php echo number_format($asset['total_earned'], 2); ?></div>
                                </td>
                                <td>
                                    <div class="asset-value"><?php echo number_format($estimatedQuantity, 2); ?></div>
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
                                    <button class="status-btn <?php echo $statusClass; ?>"><?php echo $status; ?></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
  </main>
</div>
<script>
  (function() {
      const savedTheme = localStorage.getItem('theme') || 'light';
      document.documentElement.setAttribute('data-theme', savedTheme);
  })();
</script>
</body>
</html>
