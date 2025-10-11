<?php
require_once __DIR__ . '/../src/init.php';
// 1. INCLUDES AND SESSION MANAGEMENT
require_once __DIR__ . '/../config/database.php'; // Initializes $pdo and constants
require_once __DIR__ . '/../src/functions.php';     // Your original functions (incl. secureSession, check_auth, wallet functions)
require_once __DIR__ . '/../src/assets_functions.php'; // Engine functions (incl. getAssetTypes, buyAsset)

$loggedInUser = $_SESSION['user'];
$loggedInUserId = $loggedInUser['id'];

$preselectedAssetTypeId = filter_input(INPUT_GET, 'asset_type_id', FILTER_VALIDATE_INT);

// 2. FETCH NECESSARY DATA FOR THE PAGE
$assetTypes = getAssetTypes($pdo); // Fetch all available asset types
$currentUserWalletBalance = getUserWalletBalance($pdo, $loggedInUserId);

// 3. HANDLE FORM SUBMISSION (PHP LOGIC)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'confirm_purchase') {
    $assetTypeId = filter_input(INPUT_POST, 'asset_type_id', FILTER_VALIDATE_INT);
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
    $pin = trim($_POST['transaction_pin'] ?? '');

    $_SESSION['purchase_status'] = 'error'; // Default to error

    if (empty($pin) || !preg_match('/^\d{4}$/', $pin)) {
        $_SESSION['purchase_message'] = "Please enter a valid 4-digit transaction PIN.";
    } elseif (!verifyTransactionPin($pdo, $loggedInUserId, $pin)) {
        $_SESSION['purchase_message'] = "Invalid transaction PIN.";
    } elseif ($assetTypeId && $quantity && $quantity > 0) {
        $selectedAssetType = null;
        foreach ($assetTypes as $type) {
            if ($type['id'] == $assetTypeId) {
                $selectedAssetType = $type;
                break;
            }
        }

        if ($selectedAssetType) {
            $totalCost = $selectedAssetType['price'] * $quantity;

            if ($currentUserWalletBalance >= $totalCost) {
                $pdo->beginTransaction();
                try {
                    $debitSuccess = debitUserWallet($pdo, $loggedInUserId, $totalCost, "Purchase of {$quantity} x {$selectedAssetType['name']}");
                    if (!$debitSuccess) throw new Exception("Wallet debit failed.");

                    $purchaseDetails = buyAsset($pdo, $loggedInUserId, $assetTypeId, $quantity);
                    if (isset($purchaseDetails['purchases'][0]['error'])) throw new Exception($purchaseDetails['purchases'][0]['error']);
                    
                    $pdo->commit();
                    $_SESSION['purchase_status'] = 'success';
                    $_SESSION['purchase_details'] = [
                        'asset_name' => $selectedAssetType['name'],
                        'quantity' => $quantity,
                        'total_cost' => $totalCost
                    ];

                } catch (Exception $e) {
                    $pdo->rollBack();
                    $_SESSION['purchase_message'] = "Purchase failed: " . $e->getMessage();
                }
            } else {
                $_SESSION['purchase_message'] = "Insufficient wallet balance.";
            }
        } else {
            $_SESSION['purchase_message'] = "Invalid asset type selected.";
        }
    } else {
        $_SESSION['purchase_message'] = "Invalid asset type or quantity.";
    }
    // Redirect back to the same asset page
    header("Location: buy_shares?asset_type_id=" . $assetTypeId);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'confirm_sell') {
    $assetTypeId = filter_input(INPUT_POST, 'asset_type_id', FILTER_VALIDATE_INT);
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
    $pin = trim($_POST['transaction_pin'] ?? '');

    $sellResult = sellCompletedAssets($pdo, $loggedInUserId, $assetTypeId, $quantity, $pin);

    if ($sellResult['success']) {
        $_SESSION['purchase_status'] = 'success';
        $_SESSION['purchase_message'] = $sellResult['message'];
    } else {
        $_SESSION['purchase_status'] = 'error';
        $_SESSION['purchase_message'] = $sellResult['message'];
    }

    header("Location: buy_shares?asset_type_id=" . $assetTypeId);
    exit();
}

// Check for status flag from session
$purchaseStatus = $_SESSION['purchase_status'] ?? null;
$modalMessage = $_SESSION['purchase_message'] ?? '';
$purchaseDetails = $_SESSION['purchase_details'] ?? [];

unset($_SESSION['purchase_status'], $_SESSION['purchase_message'], $_SESSION['purchase_details']);

// Refresh user balance for display
$currentUserWalletBalance = getUserWalletBalance($pdo, $loggedInUserId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Stock Asset</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet"/>
<script>
  tailwind.config = {
    darkMode: "class",
    theme: {
      extend: {
        colors: {
          primary: "#0c7ff2",
          "background-light": "#f0f2f5",
          "background-dark": "#111827",
          "green-900": "#10b981",
        },
        fontFamily: {
          display: ["Roboto", "sans-serif"],
        },
        borderRadius: {
          DEFAULT: "0.5rem",
        },
        fontSize: {
          'xs': '0.7rem',
          'sm': '0.8rem',
          'base': '0.9rem',
          'lg': '1rem',
          'xl': '1.1rem',
          '2xl': '1.3rem',
          '3xl': '1.5rem',
          '4xl': '1.8rem',
        }
      },
    },
  };
</script>

<style>
  body { 
    font-family: 'Roboto', sans-serif; 
    font-size: 0.9rem;
  }
  body { min-height: max(884px, 100dvh); }
  .fade-in { animation: fadeIn .4s ease; }
  @keyframes fadeIn { from { opacity: 0; transform: translateY(6px); } to { opacity:1; transform: translateY(0); } }
  .pill-transition { transition: all .22s cubic-bezier(.2,.8,.2,1); }
  .chart-wrap { min-height: 200px; }
  
  .highcharts-axis-labels, 
  .highcharts-grid-line, 
  .highcharts-axis-line,
  .highcharts-yaxis-grid .highcharts-grid-line,
  .highcharts-xaxis-grid .highcharts-grid-line {
    display: none !important;
  }
  
  .highcharts-container {
    overflow: hidden !important;
  }
  
  .highcharts-background {
    fill: transparent !important;
  }
  
  .log-container {
    max-height: 150px;
    overflow-y: auto;
  }
  .log-container::-webkit-scrollbar {
    width: 6px;
  }
  .log-container::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
  }
  .log-container::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
  }
  .dark .log-container::-webkit-scrollbar-track {
    background: #2d3748;
  }
  .dark .log-container::-webkit-scrollbar-thumb {
    background: #4a5568;
  }

  .loading-spinner {
    border: 4px solid rgba(255, 255, 255, 0.3);
    border-radius: 50%;
    border-top: 4px solid #fff;
    width: 20px;
    height: 20px;
    -webkit-animation: spin 1s linear infinite;
    animation: spin 1s linear infinite;
    display: inline-block;
    vertical-align: middle;
    margin-left: 8px;
  }

  @-webkit-keyframes spin {
    0% { -webkit-transform: rotate(0deg); }
    100% { -webkit-transform: rotate(360deg); }
  }

  @keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
  }

.purchase-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1000;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.4s ease, visibility 0.4s ease;
}
.purchase-modal-overlay.visible {
    opacity: 1;
    visibility: visible;
}
.purchase-modal-content {
    border-radius: 24px;
    padding: 2.5rem;
    width: 90%;
    max-width: 380px;
    text-align: center;
    transform: scale(0.9);
    transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}
html[data-theme="light"] .purchase-modal-content {
    background: rgba(255, 255, 255, 0.75);
    border: 1px solid rgba(255, 255, 255, 1);
    box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
}
html[data-theme="dark"] .purchase-modal-content {
     background: rgba(30, 41, 59, 0.6);
     border: 1px solid rgba(255, 255, 255, 0.15);
}

.modal-state { display: none; }
.modal-state.active { display: block; }

.processing-animation .spinner {
    width: 80px;
    height: 80px;
    border: 6px solid rgba(var(--accent-color-rgb), 0.2);
    border-top-color: var(--accent-color);
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 1.5rem;
}
@keyframes spin {
    to { transform: rotate(360deg); }
}
.modal-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--text-primary);
    margin-bottom: 0.5rem;
}
.modal-text {
    font-size: 1rem;
    color: var(--text-secondary);
}

.success-animation .success-icon {
    width: 100px;
    height: 100px;
    margin: 0 auto 1rem;
}
.success-animation .checkmark__circle {
    stroke-dasharray: 166;
    stroke-dashoffset: 166;
    stroke-width: 2;
    stroke-miterlimit: 10;
    stroke: #7ac142;
    fill: none;
    animation: stroke 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
}
.success-animation .checkmark {
    stroke-width: 2;
    stroke-dasharray: 48;
    stroke-dashoffset: 48;
    stroke: #7ac142;
    animation: stroke 0.3s cubic-bezier(0.65, 0, 0.45, 1) 0.8s forwards;
}
@keyframes stroke {
    100% { stroke-dashoffset: 0; }
}
.modal-info {
    background-color: var(--bg-tertiary);
    border-radius: 12px;
    padding: 1rem;
    margin-top: 1.5rem;
}
.info-item {
    display: flex;
    justify-content: space-between;
    font-size: 0.9rem;
    padding: 0.5rem 0;
}
.info-item .label { color: var(--text-secondary); }
.info-item .value { font-weight: 600; }
.close-modal-btn {
    margin-top: 1.5rem;
    width: 100%;
}

.error-animation .error-icon {
    width: 100px;
    height: 100px;
    margin: 0 auto 1rem;
}
.error-animation .x-mark__circle {
    stroke-dasharray: 166;
    stroke-dashoffset: 166;
    stroke-width: 2;
    stroke-miterlimit: 10;
    stroke: #ef4444;
    fill: none;
    animation: stroke 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
}
.error-animation .x-mark {
    stroke-width: 2;
    stroke-dasharray: 48;
    stroke-dashoffset: 48;
    stroke: #ef4444;
    animation: stroke 0.3s cubic-bezier(0.65, 0, 0.45, 1) 0.8s forwards;
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    height: 48px;
    border-radius: 0.75rem;
    font-size: 1rem;
    font-weight: 600;
    padding: 0 1.5rem;
    cursor: pointer;
    border: none;
    text-decoration: none;
    transition: background-color 0.2s, opacity 0.2s;
}
.btn-primary {
    background-color: #0c7ff2;
    color: white;
}
.btn-primary:hover { background-color: #0a6cce; }
.btn-full-width { width: 100%; }
</style>

<script src="https://code.highcharts.com/stock/highstock.js"></script>
</head>
<body class="bg-background-light dark:bg-background-dark text-gray-900 dark:text-gray-100">
<div class="max-w-md mx-auto pb-20">
  <header class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
    <a href="/market" class="material-icons text-gray-800 dark:text-gray-200">arrow_back_ios_new</a>
    <h1 class="font-bold text-lg text-primary">Stock Asset</h1>
    <span id="openAssetModalBtn" class="material-icons text-gray-800 dark:text-gray-200 cursor-pointer">add_circle_outline</span>
  </header>

  <main class="p-3">
    <div class="flex items-center mb-2">
      <img alt="Asset Logo" id="assetLogo" class="h-20 w-20 mr-4 rounded-md" src=""/>
      <div>
        <h2 class="font-bold text-2xl" id="assetName"></h2>
      </div>
    </div>

    <div class="mb-3">
      <p id="priceDisplay" class="text-4xl font-bold">SV0.00</p>
      <p id="volumeDisplay" class="text-xl font-bold text-gray-600">₦0.00 × 0</p>
      <div id="changeRow" class="flex items-center text-red-500 mt-1">
        <span id="changeIcon" class="material-icons text-sm">arrow_downward</span>
        <p id="changeText" class="ml-1 text-sm">-₦0.00 (-0.00%) <span class="text-gray-500 dark:text-gray-400 ml-1">Last closing quote</span></p>
      </div>
    </div>

    <div class="flex items-center text-sm mb-3">
      <span id="marketBadge" class="bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-2 py-1 rounded-full text-xs font-semibold pill-transition">Market Closed</span>
      <p id="marketCountdown" class="ml-2 text-gray-600 dark:text-gray-400 text-xs">Opens in -- at 10:00</p>
      <span class="material-icons text-gray-500 ml-1 text-xs">info</span>
    </div>

    <div class="mb-3 bg-white dark:bg-gray-800 rounded-lg p-2 shadow-sm fade-in chart-wrap">
      <div id="stockChart" class="w-full" style="height:200px;"></div>
    </div>

    <div class="flex justify-between items-center mb-3 text-sm text-gray-600 dark:text-gray-400 gap-2">
      <div class="flex gap-2" id="rangeButtons">
        <button data-range="1D" class="px-2 py-1 rounded-full text-xs font-semibold pill-transition">1D</button>
        <button data-range="1W" class="px-2 py-1 rounded-full text-xs font-semibold pill-transition">1W</button>
        <button data-range="1M" class="px-2 py-1 rounded-full text-xs font-semibold pill-transition">1M</button>
        <button data-range="3M" class="px-2 py-1 rounded-full bg-green-700 text-white text-xs font-semibold pill-transition">3M</button>
        <button data-range="1Y" class="px-2 py-1 rounded-full text-xs font-semibold pill-transition">1Y</button>
      </div>
      <div class="flex items-center gap-3">
        <span id="chartToggleIcon" class="material-icons text-primary cursor-pointer text-base">candlestick_chart</span>
      </div>
    </div>

    <div class="bg-yellow-100 dark:bg-yellow-900 border-l-4 border-yellow-500 text-yellow-700 dark:text-yellow-300 p-3 rounded mb-4" role="alert">
      <div class="flex">
        <div class="py-1"><span class="material-icons text-yellow-500 mr-2 text-sm">warning</span></div>
        <div>
          <p class="text-xs">Please be aware that while trading stocks, the value of a stock may fluctuate, and past performance is not indicative of future results.</p>
        </div>
      </div>
    </div>

    <div class="mb-4">
      <h3 class="text-lg font-bold mb-3">Key Stats</h3>
      <div id="statsGrid" class="grid grid-cols-3 gap-3 text-sm">
        <div>
          <p class="text-gray-500 dark:text-gray-400 text-xs">Open</p>
          <p id="statOpen" class="font-semibold text-base">₦0.00</p>
        </div>
        <div>
          <p class="text-gray-500 dark:text-gray-400 text-xs">High</p>
          <p id="statHigh" class="font-semibold text-base">₦0.00</p>
        </div>
        <div>
          <p class="text-gray-500 dark:text-gray-400 text-xs">Low</p>
          <p id="statLow" class="font-semibold text-base">₦0.00</p>
        </div>
        <div>
          <p class="text-gray-500 dark:text-gray-400 text-xs">Prev Close</p>
          <p id="statPrev" class="font-semibold text-base">₦0.00</p>
        </div>
        <div>
          <p class="text-gray-500 dark:text-gray-400 text-xs">52 Wk High</p>
          <p id="stat52High" class="font-semibold text-base">₦0.00</p>
        </div>
        <div>
          <p class="text-gray-500 dark:text-gray-400 text-xs">52 Wk Low</p>
          <p id="stat52Low" class="font-semibold text-base">₦0.00</p>
        </div>
      </div>
    </div>
  </main>

  <footer class="fixed bottom-0 left-0 right-0 max-w-md mx-auto bg-background-light dark:bg-background-dark p-3 border-t border-gray-200 dark:border-gray-700">
    <div class="flex justify-between items-center">
      <p id="wallet" class="text-xs text-gray-500 dark:text-gray-400">₦<?php echo number_format($currentUserWalletBalance * 100, 2); ?> - NGN Wallet</p>
      <div class="flex gap-2">
        <button id="buyBtn" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg shadow-md transform hover:-translate-y-0.5 transition text-sm">BUY</button>
        <button id="sellBtn" class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded-lg shadow-md transform hover:-translate-y-0.5 transition text-sm">SELL</button>
      </div>
    </div>
  </footer>
</div>

<!-- Asset Selection Modal -->
<div id="assetSelectionModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 dark:bg-opacity-80 flex items-center justify-center z-50 hidden">
    <div class="bg-background-light dark:bg-background-dark rounded-lg shadow-xl max-w-sm w-full m-4 max-h-[80vh] flex flex-col">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
            <h2 class="text-lg font-bold">Select Asset</h2>
            <button id="closeAssetModal" class="material-icons">close</button>
        </div>
        <div class="p-4 overflow-y-auto">
            <ul id="assetList" class="space-y-2"></ul>
        </div>
    </div>
</div>

<!-- Purchase Modal -->
<div id="purchaseModal" class="fixed inset-0 bg-background-light dark:bg-background-dark text-gray-900 dark:text-gray-100 h-screen flex-col p-4 hidden z-50">
    <div id="quantityStep" class="flex flex-col h-full">
        <header class="flex justify-between items-center mb-4">
            <button id="closePurchaseModal" class="text-gray-900 dark:text-gray-100">
                <span class="material-icons">arrow_back</span>
            </button>
            <div class="flex items-center space-x-2">
                <a href="/market" class="bg-primary text-white px-4 py-2 rounded-full text-sm font-medium flex items-center">
                    Market Buy - Shares
                    <span class="material-icons text-sm ml-2">arrow_drop_down</span>
                </a>
            </div>
        </header>
        <main class="flex-grow flex flex-col justify-center items-center">
            <div class="text-center">
                <p id="quantityModalAssetName" class="text-lg text-gray-500 dark:text-gray-400">ASSET NAME</p>
                <div class="flex items-center justify-center my-2">
                    <span id="quantityDisplay" class="text-6xl font-bold text-gray-900 dark:text-gray-100">0</span>
                </div>
                <p id="averageQuantityEst" class="text-sm text-gray-400 dark:text-gray-500">Estimated quantity: +0.00</p>
            </div>
        </main>
        <div class="pb-12">
            <div class="flex justify-between mb-4 px-2">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Wallet Balance SV</p>
                    <p id="quantityModalBuyingPower" class="font-medium text-gray-900 dark:text-gray-100">₦0.00</p>
                </div>
                <div class="text-right">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Share price</p>
                    <p id="quantityModalSharePrice" class="font-medium text-gray-900 dark:text-gray-100">SV 0.00</p>
                </div>
            </div>
            <button id="goToPinStepBtn" class="w-full bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 py-4 rounded-full text-lg font-medium mb-6">
                Review cost
            </button>
            <div id="numpad" class="grid grid-cols-3 gap-y-6 text-center text-3xl font-light">
                <button class="text-green-500">1</button>
                <button class="text-green-500">2</button>
                <button class="text-green-500">3</button>
                <button class="text-green-500">4</button>
                <button class="text-green-500">5</button>
                <button class="text-green-500">6</button>
                <button class="text-green-500">7</button>
                <button class="text-green-500">8</button>
                <button class="text-green-500">9</button>
                <div></div>
                <button class="text-green-500">0</button>
                <button id="backspaceBtn" class="text-red-500"><span class="material-icons">backspace</span></button>
            </div>
        </div>
    </div>

    <div id="pinStep" class="hidden flex-col h-full">
        <header class="flex justify-between items-center mb-4">
            <button id="backToQuantityStepBtn" class="text-gray-900 dark:text-gray-100">
                <span class="material-icons">arrow_back</span>
            </button>
            <h2 class="text-lg font-bold">Confirm Order</h2>
            <div></div>
        </header>
        <main class="flex-grow flex flex-col justify-center items-center">
            <div class="text-center">
                <p class="text-lg text-gray-500 dark:text-gray-400">Enter PIN to confirm purchase of</p>
                <p class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-2"><strong id="confirmQuantityPin">X</strong> shares of <strong id="confirmAssetNamePin">ASSET</strong></p>
                
                <div class="my-4 flex justify-center">
                    <div id="pinDisplayContainer" class="flex space-x-2">
                        <input type="password" id="pinInput1" maxlength="1" class="w-12 h-12 text-center text-2xl font-bold bg-gray-100 dark:bg-gray-800 rounded-lg border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-primary" readonly>
                        <input type="password" id="pinInput2" maxlength="1" class="w-12 h-12 text-center text-2xl font-bold bg-gray-100 dark:bg-gray-800 rounded-lg border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-primary" readonly>
                        <input type="password" id="pinInput3" maxlength="1" class="w-12 h-12 text-center text-2xl font-bold bg-gray-100 dark:bg-gray-800 rounded-lg border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-primary" readonly>
                        <input type="password" id="pinInput4" maxlength="1" class="w-12 h-12 text-center text-2xl font-bold bg-gray-100 dark:bg-gray-800 rounded-lg border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-2 focus:ring-primary" readonly>
                    </div>
                </div>

                <p class="text-sm text-gray-400 dark:text-gray-500">Total Cost: <strong id="confirmTotalCostPin">SV Y.YY</strong></p>
            </div>
        </main>
        <div class="pb-12">
            <form id="purchaseForm" method="post">
                <input type="hidden" name="action" value="confirm_purchase">
                <input type="hidden" name="asset_type_id" id="form_asset_type_id_pin">
                <input type="hidden" name="quantity" id="form_quantity_pin">
                <input type="hidden" name="transaction_pin" id="transaction_pin_hidden">
                
                <button type="submit" id="confirmPurchaseBtn" class="w-full bg-green-600 hover:bg-green-700 text-white py-4 rounded-full text-lg font-medium mb-6" disabled>
                    Confirm Purchase
                </button>
            </form>
            <div id="pinNumpad" class="grid grid-cols-3 gap-y-4 text-center text-3xl font-light">
                <button type="button">1</button>
                <button type="button">2</button>
                <button type="button">3</button>
                <button type="button">4</button>
                <button type="button">5</button>
                <button type="button">6</button>
                <button type="button">7</button>
                <button type="button">8</button>
                <button type="button">9</button>
                <button type="button" id="togglePinVisibility" class="p-2 rounded-full text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 focus:outline-none">
                    <span class="material-icons" id="pinVisibilityIcon">visibility_off</span>
                </button>
                <button type="button">0</button>
                <button type="button" id="pinBackspaceBtn"><span class="material-icons">backspace</span></button>
            </div>
        </div>
    </div>
</div>

<!-- Sell Modal -->
<div id="sellModal" class="fixed inset-0 bg-background-light dark:bg-background-dark text-gray-900 dark:text-gray-100 h-screen flex-col p-4 hidden z-50">
    <div id="sellQuantityStep" class="flex flex-col h-full">
        <header class="flex justify-between items-center mb-4">
            <button id="closeSellModal" class="text-gray-900 dark:text-gray-100">
                <span class="material-icons">arrow_back</span>
            </button>
            <div class="flex items-center space-x-2">
                <a href="#" class="bg-red-500 text-white px-4 py-2 rounded-full text-sm font-medium flex items-center">
                    Market Sell - Shares
                </a>
            </div>
        </header>
        <main class="flex-grow flex flex-col justify-center items-center">
            <div class="text-center">
                <p id="sellModalAssetName" class="text-lg text-gray-500 dark:text-gray-400">ASSET NAME</p>
                <div class="flex items-center justify-center my-2">
                    <span id="sellQuantityDisplay" class="text-6xl font-bold text-gray-900 dark:text-gray-100">0</span>
                </div>
                <p class="text-sm text-red-500" id="sellErrorMessage"></p>
            </div>
        </main>
        <div class="pb-12">
            <div class="flex justify-between mb-2 px-2">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Selling Price</p>
                    <p id="sellingPrice" class="font-medium text-gray-900 dark:text-gray-100">SV 0.00</p>
                </div>
                <div class="text-right">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Available Completed</p>
                    <p id="completedAssetsCount" class="font-medium text-gray-900 dark:text-gray-100">0</p>
                </div>
            </div>
            <div class="flex justify-between mb-4 px-2">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Selling Fee</p>
                    <p class="font-medium text-gray-900 dark:text-gray-100">17.99%</p>
                </div>
                <div class="text-right">
                    <p class="text-sm text-gray-500 dark:text-gray-400">You Get</p>
                    <p id="userGets" class="font-medium text-green-500">SV 0.00</p>
                </div>
            </div>
            <button id="goToSellPinStepBtn" class="w-full bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 py-4 rounded-full text-lg font-medium mb-6" disabled>
                Review Sale
            </button>
            <div id="sellNumpad" class="grid grid-cols-3 gap-y-6 text-center text-3xl font-light">
                <button class="text-red-500">1</button>
                <button class="text-red-500">2</button>
                <button class="text-red-500">3</button>
                <button class="text-red-500">4</button>
                <button class="text-red-500">5</button>
                <button class="text-red-500">6</button>
                <button class="text-red-500">7</button>
                <button class="text-red-500">8</button>
                <button class="text-red-500">9</button>
                <div></div>
                <button class="text-red-500">0</button>
                <button id="sellBackspaceBtn" class="text-red-500"><span class="material-icons">backspace</span></button>
            </div>
        </div>
    </div>

    <div id="sellPinStep" class="hidden flex-col h-full">
        <header class="flex justify-between items-center mb-4">
            <button id="backToSellStepBtn" class="text-gray-900 dark:text-gray-100">
                <span class="material-icons">arrow_back</span>
            </button>
            <h2 class="text-lg font-bold">Confirm Sale</h2>
            <div></div>
        </header>
        <main class="flex-grow flex flex-col justify-center items-center">
            <div class="text-center">
                <p class="text-lg text-gray-500 dark:text-gray-400">Enter PIN to confirm sale of</p>
                <p class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-2"><strong id="sellConfirmQuantityPin">X</strong> shares of <strong id="sellConfirmAssetNamePin">ASSET</strong></p>
                
                <div class="my-4 flex justify-center">
                    <div id="sellPinDisplayContainer" class="flex space-x-2">
                        <input type="password" id="sellPinInput1" maxlength="1" class="w-12 h-12 text-center text-2xl font-bold bg-gray-100 dark:bg-gray-800 rounded-lg border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-primary" readonly>
                        <input type="password" id="sellPinInput2" maxlength="1" class="w-12 h-12 text-center text-2xl font-bold bg-gray-100 dark:bg-gray-800 rounded-lg border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-primary" readonly>
                        <input type="password" id="sellPinInput3" maxlength="1" class="w-12 h-12 text-center text-2xl font-bold bg-gray-100 dark:bg-gray-800 rounded-lg border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-primary" readonly>
                        <input type="password" id="sellPinInput4" maxlength="1" class="w-12 h-12 text-center text-2xl font-bold bg-gray-100 dark:bg-gray-800 rounded-lg border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-primary" readonly>
                    </div>
                </div>

                <p class="text-sm text-gray-400 dark:text-gray-500">You will receive: <strong id="sellConfirmUserGetsPin">SV Y.YY</strong></p>
            </div>
        </main>
        <div class="pb-12">
            <form id="sellForm" method="post">
                <input type="hidden" name="action" value="confirm_sell">
                <input type="hidden" name="asset_type_id" id="sell_form_asset_type_id_pin">
                <input type="hidden" name="quantity" id="sell_form_quantity_pin">
                <input type="hidden" name="transaction_pin" id="sell_transaction_pin_hidden">
                
                <button type="submit" id="confirmSellBtn" class="w-full bg-red-500 hover:bg-red-600 text-white py-4 rounded-full text-lg font-medium mb-6" disabled>
                    Confirm Sale
                </button>
            </form>
            <div id="sellPinNumpad" class="grid grid-cols-3 gap-y-4 text-center text-3xl font-light">
                <button type="button">1</button>
                <button type="button">2</button>
                <button type="button">3</button>
                <button type="button">4</button>
                <button type="button">5</button>
                <button type="button">6</button>
                <button type="button">7</button>
                <button type="button">8</button>
                <button type="button">9</button>
                <button type="button" id="sellTogglePinVisibility" class="p-2 rounded-full text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 focus:outline-none">
                    <span class="material-icons" id="sellPinVisibilityIcon">visibility_off</span>
                </button>
                <button type="button">0</button>
                <button type="button" id="sellPinBackspaceBtn"><span class="material-icons">backspace</span></button>
            </div>
        </div>
    </div>
</div>

<!-- Purchase Animation Modal -->
<div class="purchase-modal-overlay" id="purchaseStatusModal">
    <div class="purchase-modal-content">
        <div class="modal-state" id="processingState">
            <div class="processing-animation">
                <div class="spinner"></div>
            </div>
            <h3 class="modal-title">Processing Transaction</h3>
            <p class="modal-text">Please wait while we securely process your transaction.</p>
        </div>
        <div class="modal-state" id="successState">
            <div class="success-animation">
                <svg class="success-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                    <circle class="checkmark__circle" cx="26" cy="26" r="25" fill="none"/>
                    <path class="checkmark" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
                </svg>
            </div>
            <h3 class="modal-title">Purchase Successful!</h3>
            <p class="modal-text">Your assets have been added to your portfolio.</p>
            <div class="modal-info" id="modal-info">
                <div class="info-item">
                    <span class="label">Asset Purchased:</span>
                    <span class="value" id="purchased-asset-name"></span>
                </div>
                <div class="info-item">
                    <span class="label">Quantity:</span>
                    <span class="value" id="purchased-quantity"></span>
                </div>
                <div class="info-item">
                    <span class="label">Total Cost:</span>
                    <span class="value" id="purchased-total-cost"></span>
                </div>
            </div>
            <button class="btn btn-primary btn-full-width close-modal-btn">Done</button>
        </div>
        <div class="modal-state" id="errorState">
            <div class="error-animation">
                <svg class="error-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                    <circle class="x-mark__circle" cx="26" cy="26" r="25" fill="none"/>
                    <path class="x-mark" fill="none" d="M16 16 36 36 M36 16 16 36"/>
                </svg>
            </div>
            <h3 class="modal-title">Transaction Failed</h3>
            <p class="modal-text" id="error-message"></p>
            <button class="btn btn-primary btn-full-width close-modal-btn">Try Again</button>
        </div>
    </div>
</div>

<script>
// Constants and State
const ASSET_TYPES = <?php echo json_encode($assetTypes); ?>;
const CURRENT_USER_WALLET_BALANCE = parseFloat(<?php echo json_encode($currentUserWalletBalance); ?>);
const PRESELECTED_ASSET_TYPE_ID = <?php echo json_encode($preselectedAssetTypeId); ?>;

let currentAssetTypeId = PRESELECTED_ASSET_TYPE_ID || (ASSET_TYPES.length > 0 ? ASSET_TYPES[0].id : null);
let currentAssetType = ASSET_TYPES.find(asset => asset.id == currentAssetTypeId);

const state = {
  currentDividingPrice: 0,
  currentAssetPrice: 0,
  chartType: 'candlestick',
  chart: null,
  activeRange: '3M',
  historicalData: [],
  marketOpen: false,
  prevClose: 0
};

// Utility Functions
const formatCurrency = v => '₦' + Number(v).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
const formatSv = v => 'SV' + Number(v).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});

// Highcharts Configuration
function applyHighchartsTheme() {
  const isDarkMode = document.documentElement.classList.contains('dark');
  const textColor = isDarkMode ? '#e5e7eb' : '#374151';
  const gridLineColor = isDarkMode ? '#374151' : '#e5e7eb';
  const tooltipBg = isDarkMode ? 'rgba(31, 41, 55, 0.85)' : 'rgba(255, 255, 255, 0.85)';

  Highcharts.theme = {
    chart: { backgroundColor: 'transparent' },
    title: { style: { color: textColor } },
    xAxis: {
      labels: { style: { color: textColor } },
      lineColor: gridLineColor,
      tickColor: gridLineColor,
      gridLineColor: gridLineColor,
    },
    yAxis: {
      labels: { style: { color: textColor } },
      lineColor: gridLineColor,
      tickColor: gridLineColor,
      gridLineColor: gridLineColor,
    },
    legend: { itemStyle: { color: textColor } },
    tooltip: {
      backgroundColor: tooltipBg,
      borderColor: gridLineColor,
      style: { color: textColor }
    },
    plotOptions: {
      series: { dataLabels: { color: textColor } },
      candlestick: {
        color: '#ef4444',
        upColor: '#10b981',
        lineColor: '#ef4444',
        upLineColor: '#10b981'
      },
      line: {
        color: '#ef4444',
        lineWidth: 2
      }
    }
  };
  Highcharts.setOptions(Highcharts.theme);
}

// Data Fetching
async function fetchAssetData(assetTypeId, range) {
  try {
    const response = await fetch(`api/get_asset_type_data.php?asset_type_id=${assetTypeId}&range=${range}`);
    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
    const data = await response.json();
    if (data.error) throw new Error(data.error);
    return data;
  } catch (error) {
    console.error("Error fetching asset data:", error);
    if(state.chart) state.chart.showLoading(error.message);
    return null;
  }
}

// Chart Management
function createChart(dataPoints) {
  if (state.chart) state.chart.destroy();
  
  state.chart = Highcharts.stockChart('stockChart', {
    chart: {
      backgroundColor: 'transparent',
      height: 200,
      zoomType: null
    },
    rangeSelector: { enabled: false },
    navigator: { enabled: false },
    scrollbar: { enabled: false },
    accessibility:{ enabled: false },
    title: { text: '' },
    yAxis: {
      opposite: false,
      gridLineWidth: 0,
      labels: { enabled: false },
      title: { text: null }
    },
    xAxis: {
      type: 'datetime',
      gridLineWidth: 0,
      labels: { enabled: false },
      title: { text: null }
    },
    tooltip: {
      split: false,
      shared: true,
      valueDecimals: 2
    },
    series: [{
      type: state.chartType === 'candlestick' ? 'candlestick' : 'line',
      name: currentAssetType ? currentAssetType.name : 'Asset',
      data: state.chartType === 'candlestick' ? dataPoints : dataPoints.map(d => [d[0], d[4]]),
    }]
  });
}

function updatePriceAndStats(data) {
  currentAssetType = data.asset_type;
  const currentDividingPrice = parseFloat(data.asset_type.dividing_price);
  const assetPrice = parseFloat(data.asset_type.price);
  const historicalData = data.historical_data;

  state.currentDividingPrice = currentDividingPrice;
  state.currentAssetPrice = assetPrice;
  state.historicalData = historicalData;

  document.getElementById('assetName').textContent = data.asset_type.name;
  document.getElementById('assetLogo').src = (data.asset_type.image_link || 'assets/images/logo.png').replace('../','');

  document.getElementById('priceDisplay').textContent = formatSv(assetPrice);

  const currentAssetPriceInNaira = assetPrice * 100;
  const averageAssetQuantity = currentDividingPrice > 0 ? Math.round(currentAssetPriceInNaira / currentDividingPrice) : 0;
  document.getElementById('volumeDisplay').textContent = `${formatCurrency(currentDividingPrice)} × ${averageAssetQuantity}`;

  if (historicalData && historicalData.length > 0) {
    const lastClose = historicalData[historicalData.length - 1][4];
    const prevClose = historicalData.length > 1 ? historicalData[historicalData.length - 2][4] : lastClose;
    state.prevClose = prevClose;

    const diff = (currentDividingPrice - prevClose);
    const pct = (prevClose === 0) ? 0 : (diff / prevClose * 100);
    const changeRow = document.getElementById('changeRow');
    const changeIcon = document.getElementById('changeIcon');
    const changeText = document.getElementById('changeText');

    changeRow.classList.remove('text-green-500', 'text-red-500');
    if (diff >= 0) {
      changeRow.classList.add('text-green-500');
      changeIcon.textContent = 'arrow_upward';
      changeText.innerHTML = `+${formatCurrency(Math.abs(diff))} (+${Math.abs(pct).toFixed(2)}%) <span class="text-gray-500 dark:text-gray-400 ml-1">Since prev</span>`;
    } else {
      changeRow.classList.add('text-red-500');
      changeIcon.textContent = 'arrow_downward';
      changeText.innerHTML = `${formatCurrency(diff)} (${pct.toFixed(2)}%) <span class="text-gray-500 dark:text-gray-400 ml-1">Since prev</span>`;
    }

    document.getElementById('statOpen').textContent = formatCurrency(historicalData[historicalData.length - 1][1]);
    document.getElementById('statHigh').textContent = formatCurrency(Math.max(...historicalData.map(d => d[2])));
    document.getElementById('statLow').textContent = formatCurrency(Math.min(...historicalData.map(d => d[3])));
    document.getElementById('statPrev').textContent = formatCurrency(prevClose);

    const allCloses = historicalData.map(d => d[4]);
    const wk52High = Math.max(...allCloses);
    const wk52Low = Math.min(...allCloses);
    document.getElementById('stat52High').textContent = formatCurrency(wk52High);
    document.getElementById('stat52Low').textContent = formatCurrency(wk52Low);
  } else {
    document.getElementById('changeText').textContent = 'N/A';
    document.getElementById('statOpen').textContent = formatCurrency(currentDividingPrice);
    document.getElementById('statHigh').textContent = formatCurrency(currentDividingPrice);
    document.getElementById('statLow').textContent = formatCurrency(currentDividingPrice);
    document.getElementById('statPrev').textContent = formatCurrency(currentDividingPrice);
    document.getElementById('stat52High').textContent = formatCurrency(currentDividingPrice);
    document.getElementById('stat52Low').textContent = formatCurrency(currentDividingPrice);
  }

  createChart(historicalData);
}

async function updateAllData(assetTypeId, range) {
  if (!assetTypeId) {
      console.log("No asset type selected.");
      if(state.chart) state.chart.showLoading('Please select an asset to view stats.');
      return;
  }
  if(state.chart) state.chart.showLoading();
  const data = await fetchAssetData(assetTypeId, range);
  if(state.chart) state.chart.hideLoading();
  if (data) {
    state.marketOpen = data.is_market_open;
    updatePriceAndStats(data);
    updateMarketBadge();
  } 
}

// Market Status Functions
function marketStatusNow(isMarketOpen) {
  const d = new Date();
  const openHour = 10, closeHour = 18;
  const open = new Date(d.getFullYear(), d.getMonth(), d.getDate(), openHour, 0, 0);
  const close = new Date(d.getFullYear(), d.getMonth(), d.getDate(), closeHour, 0, 0);

  if (isMarketOpen) {
    const msLeft = close - d;
    return { open: true, msLeft, nextEvent: close, label: 'Market Open' };
  } else {
    if (d < open) {
      const msLeft = open - d;
      return { open: false, msLeft, nextEvent: open, label: 'Market Closed' };
    } else {
      const nextOpen = new Date(d.getFullYear(), d.getMonth(), d.getDate() + 1, openHour, 0, 0);
      const msLeft = nextOpen - d;
      return { open: false, msLeft, nextEvent: nextOpen, label: 'Market Closed' };
    }
  }
}

function formatMs(ms){
  if (ms <= 0) return '0s';
  const totalSec = Math.floor(ms/1000);
  const h = Math.floor(totalSec/3600);
  const m = Math.floor((totalSec%3600)/60);
  const s = totalSec%60;
  if (h>0) return `${h}h ${m}m ${s}s`;
  if (m>0) return `${m}m ${s}s`;
  return `${s}s`;
}

function updateMarketBadge() {
  const status = marketStatusNow(state.marketOpen);
  const badge = document.getElementById('marketBadge');
  const countdown = document.getElementById('marketCountdown');

  if (status.open) {
    badge.textContent = 'Market Open';
    badge.classList.remove('bg-gray-200','dark:bg-gray-700','text-gray-700','dark:text-gray-300');
    badge.classList.add('bg-green-100','text-green-800','dark:bg-green-900','dark:text-green-300');
    countdown.textContent = `Closes in ${formatMs(status.msLeft)} (closes at 18:00)`;
  } else {
    badge.textContent = 'Market Closed';
    badge.classList.remove('bg-green-100','text-green-800','dark:bg-green-900','dark:text-green-300');
    badge.classList.add('bg-gray-200','dark:bg-gray-700','text-gray-700','dark:text-gray-300');
    const next = status.nextEvent;
    const opts = { hour: '2-digit', minute: '2-digit' };
    countdown.textContent = `Opens in ${formatMs(status.msLeft)} at ${next.toLocaleTimeString([], opts)}`;
  }
}

// Event Handlers and DOM Setup
function initializeEventListeners() {
  // Range buttons
  document.getElementById('rangeButtons').addEventListener('click', (e) => {
    const btn = e.target.closest('button[data-range]');
    if (!btn) return;
    const range = btn.getAttribute('data-range');
    
    document.querySelectorAll('#rangeButtons button').forEach(b => {
      b.classList.remove('bg-green-900', 'text-white');
    });
    btn.classList.add('bg-green-900', 'text-white');

    state.activeRange = range;
    updateAllData(currentAssetTypeId, state.activeRange);
  });

  // Chart toggle
  document.getElementById('chartToggleIcon').addEventListener('click', () => {
    if (state.chartType === 'candlestick') {
      state.chartType = 'line';
      document.getElementById('chartToggleIcon').textContent = 'show_chart';
    } else {
      state.chartType = 'candlestick';
      document.getElementById('chartToggleIcon').textContent = 'candlestick_chart';
    }
    createChart(state.historicalData);
  });

  // Asset selection modal
  const assetModal = document.getElementById('assetSelectionModal');
  const openModalBtn = document.getElementById('openAssetModalBtn');
  const closeModalBtn = document.getElementById('closeAssetModal');
  const assetList = document.getElementById('assetList');

  openModalBtn.addEventListener('click', () => {
    assetList.innerHTML = '';
    ASSET_TYPES.forEach(asset => {
      const li = document.createElement('li');
      li.className = 'flex items-center p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer';
      li.dataset.assetId = asset.id;
      li.innerHTML = `
        <img src="${(asset.image_link || 'assets/images/logo.png').replace('../','')}" alt="${asset.name}" class="h-10 w-10 rounded-md mr-3">
        <div class="flex-grow">
          <p class="font-bold">${asset.name}</p>
          <p class="text-sm text-gray-500 dark:text-gray-400">SV ${asset.price}</p>
        </div>
        ${asset.id == currentAssetTypeId ? '<span class="material-icons text-green-500">check_circle</span>' : ''}
      `;
      li.addEventListener('click', () => {
        currentAssetTypeId = asset.id;
        const url = new URL(window.location);
        url.searchParams.set('asset_type_id', currentAssetTypeId);
        window.history.pushState({}, '', url);
        updateAllData(currentAssetTypeId, state.activeRange);
        assetModal.classList.add('hidden');
      });
      assetList.appendChild(li);
    });
    assetModal.classList.remove('hidden');
  });

  closeModalBtn.addEventListener('click', () => assetModal.classList.add('hidden'));
  assetModal.addEventListener('click', (e) => {
    if (e.target === assetModal) assetModal.classList.add('hidden');
  });

  // Purchase modal elements
  const purchaseModal = document.getElementById('purchaseModal');
  const quantityStep = document.getElementById('quantityStep');
  const pinStep = document.getElementById('pinStep');
  const closePurchaseModal = document.getElementById('closePurchaseModal');
  const backToQuantityStepBtn = document.getElementById('backToQuantityStepBtn');
  const quantityDisplay = document.getElementById('quantityDisplay');
  const numpad = document.getElementById('numpad');
  const backspaceBtn = document.getElementById('backspaceBtn');
  const goToPinStepBtn = document.getElementById('goToPinStepBtn');
  const pinInputs = [
    document.getElementById('pinInput1'),
    document.getElementById('pinInput2'),
    document.getElementById('pinInput3'),
    document.getElementById('pinInput4')
  ];
  const pinNumpad = document.getElementById('pinNumpad');
  const pinBackspaceBtn = document.getElementById('pinBackspaceBtn');
  const confirmPurchaseBtn = document.getElementById('confirmPurchaseBtn');
  const transactionPinHidden = document.getElementById('transaction_pin_hidden');
  const togglePinVisibility = document.getElementById('togglePinVisibility');
  const pinVisibilityIcon = document.getElementById('pinVisibilityIcon');

  // Buy button click
  document.getElementById('buyBtn').addEventListener('click', () => {
    if(currentAssetType) {
      document.getElementById('quantityModalAssetName').textContent = currentAssetType.name;
      document.getElementById('quantityModalBuyingPower').textContent = `SV ${CURRENT_USER_WALLET_BALANCE.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
      document.getElementById('quantityModalSharePrice').textContent = `SV ${currentAssetType.price}`;
      
      quantityDisplay.textContent = '0';
      updateAverageQuantityEst();
      
      quantityStep.classList.remove('hidden');
      quantityStep.classList.add('flex');
      pinStep.classList.add('hidden');
      pinStep.classList.remove('flex');
      purchaseModal.classList.remove('hidden');
      purchaseModal.classList.add('flex');
    }
  });

  // Close purchase modal
  closePurchaseModal.addEventListener('click', () => {
    purchaseModal.classList.add('hidden');
    purchaseModal.classList.remove('flex');
  });

  // Back to quantity step
  backToQuantityStepBtn.addEventListener('click', () => {
    quantityStep.classList.remove('hidden');
    quantityStep.classList.add('flex');
    pinStep.classList.add('hidden');
    pinStep.classList.remove('flex');
  });

  // Quantity numpad
  numpad.addEventListener('click', (e) => {
    if (e.target.tagName === 'BUTTON' && e.target.id !== 'backspaceBtn') {
      const num = e.target.textContent;
      if (quantityDisplay.textContent === '0') {
        quantityDisplay.textContent = num;
      } else {
        quantityDisplay.textContent += num;
      }
      updateAverageQuantityEst();
    }
  });

  // Quantity backspace
  backspaceBtn.addEventListener('click', () => {
    if (quantityDisplay.textContent.length > 1) {
      quantityDisplay.textContent = quantityDisplay.textContent.slice(0, -1);
    } else {
      quantityDisplay.textContent = '0';
    }
    updateAverageQuantityEst();
  });

  // Go to PIN step
  goToPinStepBtn.addEventListener('click', () => {
    const quantity = parseInt(quantityDisplay.textContent, 10);
    if (quantity > 0 && currentAssetType) {
      const totalCost = quantity * currentAssetType.price;

      document.getElementById('confirmAssetNamePin').textContent = currentAssetType.name;
      document.getElementById('confirmQuantityPin').textContent = quantity;
      document.getElementById('confirmTotalCostPin').textContent = `SV ${totalCost.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

      document.getElementById('form_asset_type_id_pin').value = currentAssetTypeId;
      document.getElementById('form_quantity_pin').value = quantity;
      
      pinInputs.forEach(input => input.value = '');
      transactionPinHidden.value = '';
      confirmPurchaseBtn.disabled = true;
      pinInputs[0].focus();

      quantityStep.classList.add('hidden');
      quantityStep.classList.remove('flex');
      pinStep.classList.remove('hidden');
      pinStep.classList.add('flex');
    } else {
      alert('Please enter a quantity greater than 0.');
    }
  });

  // PIN numpad
  pinNumpad.addEventListener('click', (e) => {
    if (e.target.tagName === 'BUTTON' && e.target.id !== 'pinBackspaceBtn') {
      const num = e.target.textContent;
      let currentPin = transactionPinHidden.value;

      if (currentPin.length < 4) {
        pinInputs[currentPin.length].value = num;
        transactionPinHidden.value += num;

        if (currentPin.length < 3) {
          pinInputs[currentPin.length + 1].focus();
        } else {
          pinInputs[currentPin.length].blur();
        }
      }
      if (transactionPinHidden.value.length === 4) {
        confirmPurchaseBtn.disabled = false;
      }
    }
  });

  // PIN backspace
  pinBackspaceBtn.addEventListener('click', () => {
    let currentPin = transactionPinHidden.value;
    if (currentPin.length > 0) {
      pinInputs[currentPin.length - 1].value = '';
      transactionPinHidden.value = currentPin.slice(0, -1);
      if (currentPin.length > 1) {
        pinInputs[currentPin.length - 2].focus();
      } else {
        pinInputs[0].focus();
      }
    }
    confirmPurchaseBtn.disabled = true;
  });

  // PIN visibility toggle
  togglePinVisibility.addEventListener('click', () => {
    const isHidden = pinInputs[0].type === 'password';
    pinInputs.forEach(input => {
      input.type = isHidden ? 'text' : 'password';
    });
    pinVisibilityIcon.textContent = isHidden ? 'visibility' : 'visibility_off';
  });

  // Confirm purchase
  confirmPurchaseBtn.addEventListener('click', () => {
    if (!confirmPurchaseBtn.disabled) {
      confirmPurchaseBtn.innerHTML = 'Processing... <span class="loading-spinner"></span>';
      localStorage.setItem('purchaseInProgress', 'true');
      document.getElementById('purchaseForm').submit();
      setTimeout(() => {
        confirmPurchaseBtn.disabled = true;
      }, 0);
    }
  });

  // Sell modal elements
  const sellModal = document.getElementById('sellModal');
  const sellQuantityStep = document.getElementById('sellQuantityStep');
  const sellPinStep = document.getElementById('sellPinStep');
  const closeSellModal = document.getElementById('closeSellModal');
  const backToSellStepBtn = document.getElementById('backToSellStepBtn');
  const sellQuantityDisplay = document.getElementById('sellQuantityDisplay');
  const sellNumpad = document.getElementById('sellNumpad');
  const sellBackspaceBtn = document.getElementById('sellBackspaceBtn');
  const goToSellPinStepBtn = document.getElementById('goToSellPinStepBtn');
  const sellPinInputs = [
    document.getElementById('sellPinInput1'),
    document.getElementById('sellPinInput2'),
    document.getElementById('sellPinInput3'),
    document.getElementById('sellPinInput4')
  ];
  const sellPinNumpad = document.getElementById('sellPinNumpad');
  const sellPinBackspaceBtn = document.getElementById('sellPinBackspaceBtn');
  const confirmSellBtn = document.getElementById('confirmSellBtn');
  const sellTransactionPinHidden = document.getElementById('sell_transaction_pin_hidden');
  const sellTogglePinVisibility = document.getElementById('sellTogglePinVisibility');
  const sellPinVisibilityIcon = document.getElementById('sellPinVisibilityIcon');

  // Sell button click
  document.getElementById('sellBtn').addEventListener('click', async () => {
    if(currentAssetType) {
      // Fetch completed assets
      const response = await fetch(`api/get_completed_assets.php?asset_type_id=${currentAssetTypeId}`);
      const data = await response.json();

      if (data.error) {
        alert(data.error);
        return;
      }

      const completedAssetsCount = data.completed_assets_count;
      const sellingPrice = parseFloat(currentAssetType.price);

      if (completedAssetsCount === 0) {
          alert("You have no completed assets of this type to sell.");
          return;
      }

      document.getElementById('sellModalAssetName').textContent = currentAssetType.name;
      document.getElementById('completedAssetsCount').textContent = completedAssetsCount;
      document.getElementById('sellingPrice').textContent = formatSv(sellingPrice);
      
      sellQuantityDisplay.textContent = '0';
      document.getElementById('userGets').textContent = formatSv(0);
      document.getElementById('sellErrorMessage').textContent = '';
      goToSellPinStepBtn.disabled = true;

      sellQuantityStep.classList.remove('hidden');
      sellQuantityStep.classList.add('flex');
      sellPinStep.classList.add('hidden');
      sellPinStep.classList.remove('flex');
      sellModal.classList.remove('hidden');
      sellModal.classList.add('flex');
    }
  });

  // Close sell modal
  closeSellModal.addEventListener('click', () => {
    sellModal.classList.add('hidden');
    sellModal.classList.remove('flex');
  });

  // Back to sell quantity step
  backToSellStepBtn.addEventListener('click', () => {
    sellQuantityStep.classList.remove('hidden');
    sellQuantityStep.classList.add('flex');
    sellPinStep.classList.add('hidden');
    sellPinStep.classList.remove('flex');
  });

  // Sell numpad
  sellNumpad.addEventListener('click', (e) => {
    if (e.target.tagName === 'BUTTON' && e.target.id !== 'sellBackspaceBtn') {
      const num = e.target.textContent;
      if (sellQuantityDisplay.textContent === '0') {
        sellQuantityDisplay.textContent = num;
      } else {
        sellQuantityDisplay.textContent += num;
      }
      updateSellInfo();
    }
  });

  // Sell backspace
  sellBackspaceBtn.addEventListener('click', () => {
    if (sellQuantityDisplay.textContent.length > 1) {
      sellQuantityDisplay.textContent = sellQuantityDisplay.textContent.slice(0, -1);
    } else {
      sellQuantityDisplay.textContent = '0';
    }
    updateSellInfo();
  });

  function updateSellInfo() {
    const quantity = parseInt(sellQuantityDisplay.textContent, 10);
    const completedAssetsCount = parseInt(document.getElementById('completedAssetsCount').textContent, 10);
    const sellingPrice = parseFloat(currentAssetType.price);
    const sellingFee = 0.1799;
    const userGets = (sellingPrice * quantity) * (1 - sellingFee);

    document.getElementById('userGets').textContent = formatSv(userGets);

    if (quantity > completedAssetsCount) {
      document.getElementById('sellErrorMessage').textContent = 'Quantity cannot be greater than available completed assets.';
      goToSellPinStepBtn.disabled = true;
    } else if (quantity === 0) {
        goToSellPinStepBtn.disabled = true;
    } else {
      document.getElementById('sellErrorMessage').textContent = '';
      goToSellPinStepBtn.disabled = false;
    }
  }

  // Go to Sell PIN step
  goToSellPinStepBtn.addEventListener('click', () => {
    const quantity = parseInt(sellQuantityDisplay.textContent, 10);
    if (quantity > 0) {
      const userGets = (parseFloat(currentAssetType.price) * quantity) * (1 - 0.1799);

      document.getElementById('sellConfirmAssetNamePin').textContent = currentAssetType.name;
      document.getElementById('sellConfirmQuantityPin').textContent = quantity;
      document.getElementById('sellConfirmUserGetsPin').textContent = formatSv(userGets);

      document.getElementById('sell_form_asset_type_id_pin').value = currentAssetTypeId;
      document.getElementById('sell_form_quantity_pin').value = quantity;
      
      sellPinInputs.forEach(input => input.value = '');
      sellTransactionPinHidden.value = '';
      confirmSellBtn.disabled = true;
      sellPinInputs[0].focus();

      sellQuantityStep.classList.add('hidden');
      sellQuantityStep.classList.remove('flex');
      sellPinStep.classList.remove('hidden');
      sellPinStep.classList.add('flex');
    }
  });

  // Sell PIN numpad
  sellPinNumpad.addEventListener('click', (e) => {
    if (e.target.tagName === 'BUTTON' && e.target.id !== 'sellPinBackspaceBtn') {
      const num = e.target.textContent;
      let currentPin = sellTransactionPinHidden.value;

      if (currentPin.length < 4) {
        sellPinInputs[currentPin.length].value = num;
        sellTransactionPinHidden.value += num;

        if (currentPin.length < 3) {
          sellPinInputs[currentPin.length + 1].focus();
        } else {
          sellPinInputs[currentPin.length].blur();
        }
      }
      if (sellTransactionPinHidden.value.length === 4) {
        confirmSellBtn.disabled = false;
      }
    }
  });

  // Sell PIN backspace
  sellPinBackspaceBtn.addEventListener('click', () => {
    let currentPin = sellTransactionPinHidden.value;
    if (currentPin.length > 0) {
      sellPinInputs[currentPin.length - 1].value = '';
      sellTransactionPinHidden.value = currentPin.slice(0, -1);
      if (currentPin.length > 1) {
        sellPinInputs[currentPin.length - 2].focus();
      } else {
        sellPinInputs[0].focus();
      }
    }
    confirmSellBtn.disabled = true;
  });

  // Sell PIN visibility toggle
  sellTogglePinVisibility.addEventListener('click', () => {
    const isHidden = sellPinInputs[0].type === 'password';
    sellPinInputs.forEach(input => {
      input.type = isHidden ? 'text' : 'password';
    });
    sellPinVisibilityIcon.textContent = isHidden ? 'visibility' : 'visibility_off';
  });

  // Confirm sell
  confirmSellBtn.addEventListener('click', () => {
    if (!confirmSellBtn.disabled) {
      confirmSellBtn.innerHTML = 'Processing... <span class="loading-spinner"></span>';
      localStorage.setItem('purchaseInProgress', 'true'); // We can reuse the same status modal logic
      document.getElementById('sellForm').submit();
      setTimeout(() => {
        confirmSellBtn.disabled = true;
      }, 0);
    }
  });
}

// Helper Functions
function updateAverageQuantityEst() {
  const quantity = parseInt(document.getElementById('quantityDisplay').textContent, 10);
  if (quantity > 0 && currentAssetType) {
    const assetPrice = parseFloat(currentAssetType.price);
    const dividingPrice = parseFloat(currentAssetType.dividing_price);
    if (dividingPrice > 0) {
      const averageAssetQuantity = (assetPrice * 100) / dividingPrice;
      const estimatedQuantity = averageAssetQuantity * quantity;
      document.getElementById('averageQuantityEst').textContent = `Estimated quantity: +${estimatedQuantity.toFixed(2)}`;
    }
  } else {
    document.getElementById('averageQuantityEst').textContent = 'Estimated quantity: +0.00';
  }
}

// Initialize Application
async function bootstrap() {
  applyHighchartsTheme();
  initializeEventListeners();
  
  if (currentAssetTypeId) {
    await updateAllData(currentAssetTypeId, state.activeRange);
  }

  setInterval(() => {
    updateMarketBadge();
    if (state.marketOpen) {
      updateAllData(currentAssetTypeId, state.activeRange);
    }
  }, 60000);

  const observer = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
      if (mutation.attributeName === 'class') { 
        applyHighchartsTheme();
        createChart(state.historicalData);
      }
    });
  });
  observer.observe(document.documentElement, { attributes: true });

  updateMarketBadge();

  // Check for purchase in progress
  if (localStorage.getItem('purchaseInProgress') === 'true') {
    const confirmPurchaseBtn = document.getElementById('confirmPurchaseBtn');
    if (confirmPurchaseBtn) {
      confirmPurchaseBtn.disabled = true;
      confirmPurchaseBtn.innerHTML = 'Processing... <span class="loading-spinner"></span>';
    }
    localStorage.removeItem('purchaseInProgress');
  }
}

// Purchase Status Modal Handling
document.addEventListener('DOMContentLoaded', () => {
  const purchaseModal = document.getElementById('purchaseStatusModal');
  const processingState = document.getElementById('processingState');
  const successState = document.getElementById('successState');
  const errorState = document.getElementById('errorState');
  const successSound = new Audio('../assets/sound/new-notification-07-210334.mp3');
  const errorCallSound = new Audio('../assets/sound/error-call.mp3');
  successSound.preload = 'auto';

  const purchaseStatus = <?php echo json_encode($purchaseStatus); ?>;

  if (purchaseStatus) {
    processingState.classList.add('active');
    successState.classList.remove('active');
    errorState.classList.remove('active');
    purchaseModal.classList.add('visible');

    setTimeout(() => {
      processingState.classList.remove('active');

      if (purchaseStatus === 'success') {
        const purchaseDetails = <?php echo json_encode($purchaseDetails); ?>;
        const modalMessage = <?php echo json_encode($modalMessage); ?>;

        if (purchaseDetails && Object.keys(purchaseDetails).length > 0) {
            // It's a purchase
            document.getElementById('purchased-asset-name').textContent = purchaseDetails.asset_name;
            document.getElementById('purchased-quantity').textContent = purchaseDetails.quantity;
            document.getElementById('purchased-total-cost').textContent = "SV " + parseFloat(purchaseDetails.total_cost).toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            document.querySelector('#successState .modal-title').textContent = 'Purchase Successful!';
            document.querySelector('#successState .modal-text').textContent = 'Your assets have been added to your portfolio.';
            document.getElementById('modal-info').style.display = 'block';
        } else {
            // It's a sale
            document.querySelector('#successState .modal-title').textContent = 'Sale Successful!';
            document.querySelector('#successState .modal-text').textContent = modalMessage;
            document.getElementById('modal-info').style.display = 'none';
        }
        
        successState.classList.add('active');

        if (window.navigator && window.navigator.vibrate) {
          navigator.vibrate(200);
        }
        successSound.play().catch(e => console.error("Sound play failed:", e));

      } else if (purchaseStatus === 'error') {
        const errorMessage = <?php echo json_encode($modalMessage); ?>;
        document.getElementById('error-message').textContent = errorMessage;
        document.querySelector('#errorState .modal-title').textContent = 'Transaction Failed';
        errorState.classList.add('active');
        if (window.navigator && window.navigator.vibrate) {
          navigator.vibrate([100, 50, 100, 50, 100]);
        }
        errorCallSound.play().catch(e => console.error("Sound play failed:", e));
      }
    }, 2500);
  }

  document.querySelectorAll('.close-modal-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      purchaseModal.classList.remove('visible');
    });
  });
});  

// Theme Management
const themeObserver = new MutationObserver((mutations) => {
  mutations.forEach((mutation) => {
    if (mutation.attributeName === 'data-theme') {
      const theme = document.documentElement.getAttribute('data-theme');
      if (theme === 'dark') {
        document.documentElement.classList.add('dark');
      } else {
        document.documentElement.classList.remove('dark');
      }
    }
  });
});

themeObserver.observe(document.documentElement, { attributes: true });

if (document.documentElement.getAttribute('data-theme') === 'dark') {
  document.documentElement.classList.add('dark');
}

// Initialize the application
bootstrap();
</script>

<?php
// 5. INCLUDE FOOTER TEMPLATE
require_once __DIR__ . '/../assets/template/end-template.php';
?>