<?php
require_once __DIR__ . '/../src/functions.php';
require_once __DIR__ . '/../src/kyc_functions.php';

check_auth();

$currentUser = $_SESSION['user'];
$loggedInUserId = $currentUser['id'];

// Check KYC status
$kyc_status = getKycStatus($pdo, $loggedInUserId);
if (!$kyc_status || $kyc_status['status'] !== 'verified') {
    $_SESSION['show_kyc_popup'] = true;
    header('Location: /wallet');
    exit;
}

// Initialize transfer step
$transferStep = 1; // 1: Enter Broker Code, 2: Enter Amount, 3: Confirm PIN

$brokerDetails = null;
$transferAmount = null;
$transferRemark = null;

// Read flash messages and then unset them
$transferMessage = $_SESSION['transfer_message'] ?? '';
$transferStatus = $_SESSION['transfer_status'] ?? null;
$transferredAmount = $_SESSION['transferred_amount'] ?? 0; // Use transferred_amount for final display



// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'verify_broker_code') {
        $partnerCode = trim($_POST['partner_code'] ?? '');
        if (!empty($partnerCode)) {
            $foundBroker = getBrokerDetailsByPartnerCode($pdo, $partnerCode);
            if ($foundBroker) {
                $_SESSION['transfer_broker_id'] = $foundBroker['id'];
                $_SESSION['transfer_broker_username'] = $foundBroker['username'];
                $_SESSION['transfer_broker_partner_code'] = $foundBroker['partner_code'];
                $transferStep = 2; // Move to next step
            } else {
                $_SESSION['transfer_message'] = "Broker with partner code '{$partnerCode}' not found or is not a certified broker.";
                $_SESSION['transfer_status'] = 'error';
            }
        } else {
            $_SESSION['transfer_message'] = "Please enter a broker partner code.";
            $_SESSION['transfer_status'] = 'error';
        }
        header("Location: transfer"); // Redirect after processing step 1
        exit();
    } elseif (isset($_POST['action']) && $_POST['action'] === 'submit_amount') {
        $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
        $remark = trim($_POST['remark'] ?? '');

        if ($amount === false || $amount <= 0) {
            $_SESSION['transfer_message'] = "Please enter a valid transfer amount.";
            $_SESSION['transfer_status'] = 'error';
        } elseif ($amount > $currentUser['wallet_balance']) {
            $_SESSION['transfer_message'] = "Insufficient funds in your wallet.";
            $_SESSION['transfer_status'] = 'error';
        } else {
            $_SESSION['transfer_amount'] = $amount;
            $_SESSION['transfer_remark'] = $remark;
        }
        header("Location: transfer"); // Redirect after processing step 2
        exit();
    } elseif (isset($_POST['action']) && $_POST['action'] === 'user_transfer_wallet') {
        // This is the final transfer submission from the PIN modal
        $senderId = $loggedInUserId;
        $receiverId = $_SESSION['transfer_broker_id'] ?? null;
        $amount = $_SESSION['transfer_amount'] ?? null;
        $remark = $_SESSION['transfer_remark'] ?? '';
        $pin = trim($_POST['transaction_pin'] ?? '');

        if (!$receiverId || $amount === null || $amount <= 0 || empty($pin)) {
            $_SESSION['transfer_message'] = "Invalid transfer details. Please try again.";
            $_SESSION['transfer_status'] = 'error';
        } else {
            $transferResult = transferWalletBalance($pdo, $senderId, $receiverId, $amount, $pin);
            $_SESSION['transfer_message'] = $transferResult['message'];
            $_SESSION['transfer_status'] = $transferResult['success'] ? 'success' : 'error';
            $_SESSION['transferred_amount'] = $amount; // Store for status modal

            // Clear all transfer-related session data after final processing
            unset($_SESSION['transfer_broker_id'], $_SESSION['transfer_broker_username'], $_SESSION['transfer_broker_partner_code'], $_SESSION['transfer_amount'], $_SESSION['transfer_remark']);
        }
        header("Location: transfer"); // Redirect after final processing
        exit();
    } elseif (isset($_POST['action']) && $_POST['action'] === 'toggle_favorite') {
        $brokerId = filter_input(INPUT_POST, 'broker_id', FILTER_VALIDATE_INT);
        if ($brokerId) {
            $success = toggleFavoriteBroker($pdo, $loggedInUserId, $brokerId);
            header('Content-Type: application/json');
            echo json_encode(['success' => $success]);
            exit();
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid broker ID.']);
            exit();
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'go_back_to_step1') {
        unset($_SESSION['transfer_broker_id'], $_SESSION['transfer_broker_username'], $_SESSION['transfer_broker_partner_code'], $_SESSION['transfer_amount'], $_SESSION['transfer_remark']);
        header("Location: transfer"); // Redirect to ensure clean state
        exit();
    } elseif (isset($_POST['action']) && $_POST['action'] === 'reset_transfer_session') {
        unset($_SESSION['transfer_broker_id'], $_SESSION['transfer_broker_username'], $_SESSION['transfer_broker_partner_code'], $_SESSION['transfer_amount'], $_SESSION['transfer_remark']);
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit();
    } elseif (isset($_POST['action']) && $_POST['action'] === 'go_back_to_step2') {
        unset($_SESSION['transfer_amount'], $_SESSION['transfer_remark']);
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit();
    }
}

// Determine current step based on session data if not explicitly set by POST
if (isset($_SESSION['transfer_broker_id']) && !isset($_SESSION['transfer_amount'])) {
    $transferStep = 2;
} elseif (isset($_SESSION['transfer_broker_id']) && isset($_SESSION['transfer_amount'])) {
    $transferStep = 3;
}

// If we are on step 2 or 3, retrieve broker details from session
if (($transferStep === 2 || $transferStep === 3) && isset($_SESSION['transfer_broker_id'])) {
    $brokerDetails = [
        'id' => $_SESSION['transfer_broker_id'],
        'username' => $_SESSION['transfer_broker_username'],
        'partner_code' => $_SESSION['transfer_broker_partner_code']
    ];
    $transferAmount = $_SESSION['transfer_amount'] ?? null;
    $transferRemark = $_SESSION['transfer_remark'] ?? null;
}

// Fetch recent and favorite brokers for the first step UI
$recentBrokers = getRecentBrokers($pdo, $loggedInUserId, 3);
$favoriteBrokers = getFavoriteBrokers($pdo, $loggedInUserId);

// Re-fetch current user data to ensure wallet balance is up-to-date
$currentUser = getUserByIdOrName($pdo, $loggedInUserId);
$_SESSION['user'] = $currentUser; // Update session user data

// Include the intro template
require_once __DIR__ . '/../assets/template/intro-template.php';
?>

<script>
  tailwind.config = {
    darkMode: "class",
    theme: {
      extend: {
        colors: {
          primary: "#10B981", // Emerald 500 - green from Recents tab
          "background-light": "#F3F4F6", // Gray 100
          "background-dark": "#111827", // Gray 900
          "surface-light": "#FFFFFF",
          "surface-dark": "#1F2937", // Gray 800
          "text-primary-light": "#1F2937", // Gray 800
          "text-primary-dark": "#F9FAFB", // Gray 50
          "text-secondary-light": "#6B7280", // Gray 500
          "text-secondary-dark": "#9CA3AF", // Gray 400
          "border-light": "#E5E7EB",
          "border-dark": "#374151",
          "white": "#FFFFFF",
        },
        fontFamily: {
          sans: ["Roboto", "sans-serif"],
        },
        borderRadius: {
          DEFAULT: "0.5rem",
        },
        fontSize: {
          'xs': '0.7rem',
          'sm': '0.8rem',
          'base': '0.9rem',
          'lg': '1.2rem',
          'xl': '1.5rem',
          '2xl': '1.5rem',
          '3xl': '1.8rem',
          '4xl': '1.8rem',
        }
      },
    },
  };
</script>
<style>
    .material-icons {
      font-size: inherit;
    }
    body {
      min-height: max(884px, 100dvh);
      display: flex;
      flex-direction: column;
    }
    .main-content {
      flex-grow: 1;
    }
    .fade-in { animation: fadeIn .4s ease; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(6px); } to { opacity:1; transform: translateY(0); } }
    .purchase-modal-overlay {
        position: fixed; /* Changed to fixed for full viewport coverage */
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background-color: rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        display: flex;
        padding:0 !important;
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
        display:flex;
        flex-direction:column;
        border-radius: 24px;
        padding: 2.5rem;
        margin:0 !important;
        width: 100%;
        max-width: 580px;
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
        font-size: 1.8rem;
        font-weight: 700;
        color: var(--text-primary-light);
        margin-bottom: 0.5rem;
    }
    html[data-theme="dark"] .modal-title {
        color: var(--text-primary-dark);
    }
    .modal-text {
        font-size: 1.3rem;
        color: var(--text-secondary-light);
    }
    html[data-theme="dark"] .modal-text {
        color: var(--text-secondary-dark);
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
        background-color: #f0f2f5; /* Adjusted for consistency */
        border-radius: 12px;
        padding: 1rem;
        margin-top: 1.5rem;
    }
    html[data-theme="dark"] .modal-info {
        background-color: #1f2937; /* Adjusted for consistency */
    }
    .info-item {
        display: flex;
        justify-content: space-between;
        font-size: 0.9rem;
        padding: 0.5rem 0;
    }
    .info-item .label { color: #6B7280; } /* Adjusted for consistency */
    html[data-theme="dark"] .info-item .label { color: #9CA3AF; } /* Adjusted for consistency */
    .info-item .value { font-weight: 600; }
    html[data-theme="dark"] .info-item .value { color: #F9FAFB; }
    .close-modal-btn {
        margin-top: 1.5rem;
        width: 100%;
    }
    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        height: 48px;
        border-radius: 0.75rem;
        font-size: 1.2rem;
        font-weight: 700;
        padding: 0 1.5rem;
        cursor: pointer;
        border: none;
        text-decoration: none;
        transition: background-color 0.2s, opacity 0.2s;
    }
    .btn-primary {
        background-color: #10B981; /* Primary color from Tailwind config */
        color: white;
    }
    .btn-primary:hover { background-color: #0e9f71; } /* Darker shade of primary */
    .btn-full-width { width: 100%; }
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
</style>

<div class="container mx-auto p-2 max-w-md">
    <?php if ($transferStep === 1): ?>
        <!-- Step 1: Enter Broker Partner Code -->
        <div class="mb-6">
            <h1 class="text-xl font-bold text-text-primary-light dark:text-text-primary-dark text-center">Broker Partner Code</h1>
        </div>
        <?php if (!empty($transferMessage) && $transferStatus === 'error'): ?>
            <div class="bg-red-100 dark:bg-red-900 border border-red-400 dark:border-red-700 text-red-700 dark:text-red-300 px-4 py-3 rounded relative mb-4 text-center mx-auto" role="alert">
                <strong class="font-bold">Error!</strong>
                <span class="block sm:inline"><?php echo htmlspecialchars($transferMessage); ?></span>
            </div>
        <?php endif; ?>
        <form method="POST" action="transfer">
            <input type="hidden" name="action" value="verify_broker_code">
            <div class="relative mb-2">
                <input id="partner_code_input" name="partner_code" class="w-full bg-surface-light dark:bg-surface-dark border-none rounded-lg py-3 pl-4 pr-12 text-text-primary-light dark:text-text-primary-dark placeholder-text-secondary-light dark:placeholder-text-secondary-dark focus:ring-2 focus:ring-primary" placeholder="Enter Broker Partner Code" type="text" required value="<?php echo htmlspecialchars($_POST['partner_code'] ?? ''); ?>"/>
                <span class="material-icons absolute right-4 top-1/2 -translate-y-1/2 text-text-secondary-light dark:text-text-secondary-dark text-2xl">
                    qr_code_scanner
                </span>
            </div>
            <p class="text-sm text-center text-text-secondary-light dark:text-text-secondary-dark mb-6">
                Ask a certified broker for his/her partner code or check their ID Card.
            </p>
            <div class="bg-surface-light dark:bg-surface-dark rounded-xl p-4">
                <div class="flex border-b border-gray-200 dark:border-gray-700 mb-4">
                    <button type="button" class="tab-button flex-1 py-2 text-center text-primary border-b-2 border-primary font-semibold" data-tab="recents">Recents</button>
                    <button type="button" class="tab-button flex-1 py-2 text-center text-text-secondary-light dark:text-text-secondary-dark font-semibold" data-tab="favourites">Favourites</button>
                </div>
                <div id="recents-tab" class="tab-content space-y-4 active flex flex-col">
                    <?php if (!empty($recentBrokers)): ?>
                        <?php foreach ($recentBrokers as $broker): ?>
                            <div class="flex items-center space-x-4 broker-item w-full" data-partner-code="<?php echo htmlspecialchars($broker['partner_code']); ?>">
                                <div class="relative">
                                    <div class="w-12 h-12 bg-gray-200 dark:bg-gray-600 rounded-full flex items-center justify-center">
                                        <span class="material-icons text-white text-3xl">person</span>
                                    </div>
                                    <div class="absolute -bottom-1 -right-1 bg-green-500 rounded-full p-0.5 flex items-center text-white text-[8px] leading-none">
                                        <span class="material-icons text-xs !mr-0.5">store</span>
                                        <span class="font-bold">Broker</span>
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <p class="font-medium text-text-primary-light dark:text-text-primary-dark"><?php echo htmlspecialchars($broker['username']); ?></p>
                                    <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark"><?php echo htmlspecialchars($broker['partner_code']); ?></p>
                                </div>
                                <span class="material-icons text-<?php echo $broker['is_favorite'] ? 'red-500 dark:text-red-400' : 'text-secondary-light dark:text-text-secondary-dark'; ?> toggle-favorite" data-broker-id="<?php echo htmlspecialchars($broker['id']); ?>">
                                    <?php echo $broker['is_favorite'] ? 'favorite' : 'favorite_border'; ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-center text-text-secondary-light dark:text-text-secondary-dark">No recent brokers.</p>
                    <?php endif; ?>
                </div>
                <div id="favourites-tab" class="tab-content space-y-4 hidden flex flex-col">
                    <?php if (!empty($favoriteBrokers)): ?>
                        <?php foreach ($favoriteBrokers as $broker): ?>
                            <div class="flex items-center space-x-4 broker-item w-full" data-partner-code="<?php echo htmlspecialchars($broker['partner_code']); ?>">
                                <div class="relative">
                                    <div class="w-12 h-12 bg-gray-200 dark:bg-gray-600 rounded-full flex items-center justify-center">
                                        <span class="material-icons text-white text-3xl">person</span>
                                    </div>
                                    <div class="absolute -bottom-1 -right-1 bg-green-500 rounded-full p-0.5 flex items-center text-white text-[8px] leading-none">
                                        <span class="material-icons text-xs !mr-0.5">store</span>
                                        <span class="font-bold">Broker</span>
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <p class="font-medium text-text-primary-light dark:text-text-primary-dark"><?php echo htmlspecialchars($broker['username']); ?></p>
                                    <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark"><?php echo htmlspecialchars($broker['partner_code']); ?></p>
                                </div>
                                <span class="material-icons text-red-500 toggle-favorite" data-broker-id="<?php echo htmlspecialchars($broker['id']); ?>">favorite</span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-center text-text-secondary-light dark:text-text-secondary-dark">No favorite brokers.</p>
                    <?php endif; ?>
                </div>
                <div class="mt-6 text-center">
                    <button type="button" class="bg-gray-200 dark:bg-gray-700 text-text-primary-light dark:text-text-primary-dark font-medium py-2 px-6 rounded-full text-sm">
                        View All &gt;
                    </button>
                </div>
            </div>
            <div class="container mx-auto p-4 max-w-md mt-auto">
                <button type="submit" class="w-full bg-primary text-white font-bold py-3 rounded-lg">Continue</button>
            </div>
        </form>
    <?php elseif ($transferStep === 2): ?>
        <!-- Step 2: Enter Amount and Review -->
        <div class="max-w-md mx-auto flex flex-col">
            <header class="p-4 flex items-center justify-between text-text-primary-light dark:text-text-primary-dark">
                <div class="flex items-center">
                    <form method="POST" action="transfer" id="backToStep1Form">
                        <input type="hidden" name="action" value="go_back_to_step1">
                        <button type="submit" class="material-icons">arrow_back_ios</button>
                    </form>
                    <h1 class="text-lg font-medium ml-2">Transfer to Broker</h1>
                </div>
                <a class="text-primary text-sm font-medium" href="#">Transactions</a>
            </header>
            <main class="flex-grow p-0 space-y-6 relative">
                <?php if (!empty($transferMessage) && $transferStatus === 'error'): ?>
                    <div class="bg-red-100 dark:bg-red-900 border border-red-400 dark:border-red-700 text-red-700 dark:text-red-300 px-4 py-3 rounded relative mb-4 text-center mx-auto" role="alert">
                        <strong class="font-bold">Error!</strong>
                        <span class="block sm:inline"><?php echo htmlspecialchars($transferMessage); ?></span>
                    </div>
                <?php endif; ?>
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 bg-surface-light dark:bg-surface-dark rounded-full flex items-center justify-center">
                        <span class="material-icons text-text-secondary-light dark:text-text-secondary-dark text-3xl">person</span>
                    </div>
                    <div>
                        <p class="font-semibold text-text-primary-light dark:text-text-primary-dark"><?php echo htmlspecialchars($brokerDetails['username'] ?? 'N/A'); ?></p>
                        <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark"><?php echo htmlspecialchars($brokerDetails['partner_code'] ?? 'N/A'); ?></p>
                    </div>
                </div>
                <form method="POST" action="transfer">
                    <input type="hidden" name="action" value="submit_amount">
                    <div class="bg-surface-light dark:bg-surface-dark p-4 rounded-lg">
                        <div class="flex justify-between items-center mb-2">
                            <label class="text-sm font-medium text-text-secondary-light dark:text-text-secondary-dark" for="amount">Amount</label>
                            <span class="text-xs text-primary">18.748% transaction fee</span>
                        </div>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 pr-2 flex items-center text-2xl font-bold text-text-primary-light dark:text-text-primary-dark">SV</span>
                            <input class="w-full bg-transparent pl-12 pr-4 py-2 text-2xl font-bold border-0 border-b border-border-light dark:border-border-dark focus:ring-0 focus:border-primary text-text-primary-light dark:text-text-primary-dark" id="amount" name="amount" type="number" step="0.01" value="<?php echo htmlspecialchars($transferAmount ?? ''); ?>"/>
                        </div>
                        <div class="grid grid-cols-3 gap-3 mt-4">
                            <button type="button" class="amount-quick-select bg-background-light dark:bg-background-dark py-2 px-4 rounded-lg text-sm text-text-primary-light dark:text-text-primary-dark" data-amount="20">SV20</button>
                            <button type="button" class="amount-quick-select bg-background-light dark:bg-background-dark py-2 px-4 rounded-lg text-sm text-text-primary-light dark:text-text-primary-dark" data-amount="50">SV50</button>
                            <button type="button" class="amount-quick-select bg-background-light dark:bg-background-dark py-2 px-4 rounded-lg text-sm text-text-primary-light dark:text-text-primary-dark" data-amount="100">SV100</button>
                            <button type="button" class="amount-quick-select bg-background-light dark:bg-background-dark py-2 px-4 rounded-lg text-sm text-text-primary-light dark:text-text-primary-dark" data-amount="200">SV200</button>
                            <button type="button" class="amount-quick-select bg-background-light dark:bg-background-dark py-2 px-4 rounded-lg text-sm text-text-primary-light dark:text-text-primary-dark" data-amount="500">SV500</button>
                            <button type="button" class="amount-quick-select bg-background-light dark:bg-background-dark py-2 px-4 rounded-lg text-sm text-text-primary-light dark:text-text-primary-dark" data-amount="1000">SV1000</button>
                        </div>
                    </div>
                    <div class="bg-surface-light dark:bg-surface-dark p-4 rounded-lg mt-6">
                        <label class="text-sm font-medium text-text-secondary-light dark:text-text-secondary-dark" for="remark">Remark</label>
                        <input class="w-full bg-transparent border-0 border-b border-border-light dark:border-border-dark focus:ring-0 focus:border-primary mt-2 p-0 text-text-primary-light dark:text-text-primary-dark" id="remark" name="remark" placeholder="What's this for? (Optional)" type="text" value="<?php echo htmlspecialchars($transferRemark ?? ''); ?>"/>
                        <div class="flex space-x-3 mt-4">
                            <button type="button" class="remark-quick-select flex-1 bg-background-light dark:bg-background-dark py-2 px-4 rounded-lg text-sm text-text-primary-light dark:text-text-primary-dark" data-remark="Purchase">Purchase</button>
                            <button type="button" class="remark-quick-select flex-1 bg-background-light dark:bg-background-dark py-2 px-4 rounded-lg text-sm text-text-primary-light dark:text-text-primary-dark" data-remark="Personal">Personal</button>
                        </div>
                    </div>
                <div class="fixed bottom-0 left-0 right-0 p-4 pb-[50px] bg-background-light dark:bg-background-dark z-10">
                    <button type="submit" class="w-full bg-primary text-white py-3 rounded-lg font-semibold">Confirm</button>
                </div>
            </main>
        </form>
        </div>
    <?php endif; ?>
</div>

<!-- PIN Modal -->
<div id="pinModal" class="bg-background-light dark:bg-surface-dark text-gray-900 dark:text-white h-screen flex-col hidden z-50 purchase-modal-overlay">
    <div id="pinStep" class="flex flex-col h-full purchase-modal-content">
        <div class="flex justify-between items-center mb-4">
            <button id="closePinModal" class="text-text-primary-light dark:text-text-primary-dark">
                <span class="material-icons">arrow_back</span>
            </button>
            <h2 class="modal-title">Confirm Transfer</h2>
        <div>
           </div></div>
        <div class="flex-grow flex flex-col justify-center items-center">
            <div class="text-center">
                <p class="modal-text">Enter PIN to confirm transfer of</p>
                <p class="modal-text mb-2"><strong id="confirmAmountPin">SV X.XX</strong> to <strong id="confirmRecipientPin">RECIPIENT</strong></p>
                
                <div class="my-4 flex justify-center">
                    <div id="pinDisplayContainer" class="flex space-x-2">
                        <input type="password" id="pinInput1" maxlength="1" class="w-12 h-12 text-center text-2xl font-bold bg-surface-light dark:bg-surface-dark rounded-lg border border-border-light dark:border-border-dark focus:outline-none focus:ring-2 focus:ring-primary" readonly>
                        <input type="password" id="pinInput2" maxlength="1" class="w-12 h-12 text-center text-2xl font-bold bg-surface-light dark:bg-surface-dark rounded-lg border border-border-light dark:border-border-dark focus:outline-none focus:ring-2 focus:ring-primary" readonly>
                        <input type="password" id="pinInput3" maxlength="1" class="w-12 h-12 text-center text-2xl font-bold bg-surface-light dark:bg-surface-dark rounded-lg border border-border-light dark:border-border-dark focus:outline-none focus:ring-2 focus:ring-primary" readonly>
                        <input type="password" id="pinInput4" maxlength="1" class="w-12 h-12 text-center text-2xl font-bold bg-surface-light dark:bg-surface-dark rounded-lg border border-border-light dark:border-border-dark focus:outline-none focus:ring-2 focus:ring-primary" readonly>
                    </div>
                </div>
            </div>
        </div>
        <div class="pb-12">
            <form id="transferForm" method="post">
                <input type="hidden" name="action" value="user_transfer_wallet">
                <input type="hidden" name="receiver_username" id="form_receiver_username_pin" value="<?php echo htmlspecialchars($brokerDetails['username'] ?? ''); ?>">
                <input type="hidden" name="transfer_amount" id="form_transfer_amount_pin" value="<?php echo htmlspecialchars($transferAmount ?? ''); ?>">
                <input type="hidden" name="transaction_pin" id="transaction_pin_hidden">
                
                <button type="submit" id="confirmTransferBtn" class="w-full bg-primary hover:bg-primary/90 text-white py-4 rounded-full text-xl font-medium mb-6" disabled>
                    Confirm Transfer
                </button>
            </form>
            <div id="pinNumpad" class="grid grid-cols-3 gap-y-4 text-center text-3xl font-light">
                <button type="button" class="text-text-primary-light dark:text-text-primary-dark">1</button>
                <button type="button" class="text-text-primary-light dark:text-text-primary-dark">2</button>
                <button type="button" class="text-text-primary-light dark:text-text-primary-dark">3</button>
                <button type="button" class="text-text-primary-light dark:text-text-primary-dark">4</button>
                <button type="button" class="text-text-primary-light dark:text-text-primary-dark">5</button>
                <button type="button" class="text-text-primary-light dark:text-text-primary-dark">6</button>
                <button type="button" class="text-text-primary-light dark:text-text-primary-dark">7</button>
                <button type="button" class="text-text-primary-light dark:text-text-primary-dark">8</button>
                <button type="button" class="text-text-primary-light dark:text-text-primary-dark">9</button>
                <button type="button" id="togglePinVisibility" class="p-2 rounded-full text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 focus:outline-none">
                    <span class="material-icons" id="pinVisibilityIcon">visibility_off</span>
                </button>
                <button type="button" class="text-text-primary-light dark:text-text-primary-dark">0</button>
                <button type="button" id="pinBackspaceBtn" class="text-red-500"><span class="material-icons">backspace</span></button>
            </div>
        </div>
    </div>
</div>

<!-- Status Modal -->
<div class="purchase-modal-overlay" id="statusModal">
    <div class="purchase-modal-content">
        <div class="modal-state" id="processingState">
            <div class="processing-animation">
                <div class="loading-spinner"></div>
            </div>
            <h3 class="modal-title">Processing Transfer</h3>
            <p class="modal-text">Please wait while we securely process your transaction.</p>
        </div>
        <div class="modal-state" id="successState">
            <div class="success-animation">
                <svg class="success-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                    <circle class="checkmark__circle" cx="26" cy="26" r="25" fill="none"/>
                    <path class="checkmark" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
                </svg>
            </div>
            <h3 class="modal-title">Transfer Successful!</h3>
            <p class="modal-text">The funds have been transferred.</p>
            <div class="modal-info" id="modal-info">
                <div class="info-item">
                    <span class="label">Amount Transferred:</span>
                    <span class="value" id="successAmount"></span>
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
            <h3 class="modal-title">Transfer Failed</h3>
            <p class="modal-text" id="errorMessage"></p>
            <button class="btn btn-primary btn-full-width close-modal-btn">Try Again</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    function applyTheme(theme) {
        if (theme === 'dark') {
            document.documentElement.classList.add('dark');
            document.documentElement.setAttribute('data-theme', 'dark');
        } else {
            document.documentElement.classList.remove('dark');
            document.documentElement.setAttribute('data-theme', 'light');
        }
    }

    // Listen for theme changes from local storage
    window.addEventListener('storage', function (e) {
        if (e.key === 'theme') {
            applyTheme(e.newValue);
        }
    });

    // Apply initial theme
    applyTheme(localStorage.getItem('theme') || 'light');

    // --- Step 1: Broker Code Input ---
    const partnerCodeInput = document.getElementById('partner_code_input');
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');
    const brokerItems = document.querySelectorAll('.broker-item');
    const toggleFavoriteButtons = document.querySelectorAll('.toggle-favorite');

    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            tabButtons.forEach(btn => {
                btn.classList.remove('text-primary', 'border-primary');
                btn.classList.add('text-text-secondary-light', 'dark:text-text-secondary-dark');
                btn.style.borderBottomWidth = '0px';
            });
            button.classList.add('text-primary', 'border-primary');
            button.classList.remove('text-text-secondary-light', 'dark:text-text-secondary-dark');
            button.style.borderBottomWidth = '2px';

            tabContents.forEach(content => content.classList.add('hidden'));
            document.getElementById(`${button.dataset.tab}-tab`).classList.remove('hidden');
        });
    });

    brokerItems.forEach(item => {
        item.addEventListener('click', (event) => {
            // Prevent click on favorite icon from triggering item click
            if (event.target.classList.contains('toggle-favorite')) {
                return;
            }
            partnerCodeInput.value = item.dataset.partnerCode;
        });
    });

    toggleFavoriteButtons.forEach(button => {
        button.addEventListener('click', async (event) => {
            event.stopPropagation(); // Prevent parent broker-item click
            const brokerId = button.dataset.brokerId;
            const isFavorite = button.textContent.trim() === 'favorite';

            // Optimistic UI update
            button.textContent = isFavorite ? 'favorite_border' : 'favorite';
            button.classList.toggle('text-red-500', !isFavorite);
            button.classList.toggle('text-text-secondary-light', isFavorite);
            button.classList.toggle('dark:text-text-secondary-dark', isFavorite);

            try {
                const response = await fetch('transfer', { // Assuming 'transfer' handles AJAX for favorites
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=toggle_favorite&broker_id=${brokerId}`
                });
                const result = await response.json();
                if (!result.success) {
                    // Revert UI on error
                    button.textContent = isFavorite ? 'favorite' : 'favorite_border';
                    button.classList.toggle('text-red-500', isFavorite);
                    button.classList.toggle('text-text-secondary-light', !isFavorite);
                    button.classList.toggle('dark:text-text-secondary-dark', !isFavorite);
                    alert('Failed to update favorite status.');
                }
            } catch (error) {
                console.error('Error toggling favorite:', error);
                // Revert UI on error
                button.textContent = isFavorite ? 'favorite' : 'favorite_border';
                button.classList.toggle('text-red-500', isFavorite);
                button.classList.toggle('text-text-secondary-light', !isFavorite);
                button.classList.toggle('dark:text-text-secondary-dark', !isFavorite);
                alert('Network error or server issue.');
            }
        });
    });


    // --- Step 2: Amount Input and Review ---
    const amountInput = document.getElementById('amount');
    const amountQuickSelects = document.querySelectorAll('.amount-quick-select');
    const remarkInput = document.getElementById('remark');
    const remarkQuickSelects = document.querySelectorAll('.remark-quick-select');
    const backToStep1Form = document.getElementById('backToStep1Form');

    if (amountInput) {
        amountQuickSelects.forEach(button => {
            button.addEventListener('click', () => {
                amountInput.value = button.dataset.amount;
            });
        });
    }

    if (remarkInput) {
        remarkQuickSelects.forEach(button => {
            button.addEventListener('click', () => {
                remarkInput.value = button.dataset.remark;
            });
        });
    }

    // --- PIN Modal (Step 3) and Status Modal (Step 4) ---
    const pinModal = document.getElementById('pinModal');
    const closePinModal = document.getElementById('closePinModal');
    const pinInputs = [document.getElementById('pinInput1'), document.getElementById('pinInput2'), document.getElementById('pinInput3'), document.getElementById('pinInput4')];
    const pinNumpad = document.getElementById('pinNumpad');
    const pinBackspaceBtn = document.getElementById('pinBackspaceBtn');
    const confirmTransferBtn = document.getElementById('confirmTransferBtn');
    const transactionPinHidden = document.getElementById('transaction_pin_hidden');
    const togglePinVisibility = document.getElementById('togglePinVisibility');

    // Check if we should show the PIN modal immediately (i.e., $transferStep === 3)
    const currentTransferStep = <?php echo json_encode($transferStep); ?>;
    const sessionTransferAmount = <?php echo json_encode($_SESSION['transfer_amount'] ?? null); ?>;
    const sessionTransferRemark = <?php echo json_encode($_SESSION['transfer_remark'] ?? null); ?>;
    const sessionBrokerUsername = <?php echo json_encode($_SESSION['transfer_broker_username'] ?? null); ?>;

    if (currentTransferStep === 3 && sessionTransferAmount !== null && sessionBrokerUsername !== null) {
        document.getElementById('confirmRecipientPin').textContent = sessionBrokerUsername;
        document.getElementById('confirmAmountPin').textContent = `SV ${parseFloat(sessionTransferAmount).toFixed(2)}`;
        document.getElementById('form_transfer_amount_pin').value = sessionTransferAmount;
        // The form_receiver_username_pin is already set by PHP for step 3
        pinModal.classList.add('visible');
    }

    if (closePinModal) {
        closePinModal.addEventListener('click', async () => {
            pinModal.classList.remove('visible');
            // Make an AJAX call to clear amount/remark session data and go back to step 2
            try {
                const response = await fetch('transfer', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=go_back_to_step2'
                });
                const result = await response.json();
                if (result.success) {
                    window.location.href = 'transfer'; // Reload page to render step 2
                } else {
                    console.error('Failed to go back to step 2:', result.message);
                    window.location.href = 'transfer'; // Still reload even on error
                }
            } catch (error) {
                console.error('Network error during back to step 2:', error);
                window.location.href = 'transfer'; // Reload page even on network error
            }
        });
    }

    if (pinNumpad) {
        pinNumpad.addEventListener('click', (e) => {
            if (e.target.tagName === 'BUTTON' && e.target.id !== 'pinBackspaceBtn' && e.target.id !== 'togglePinVisibility') {
                const num = e.target.textContent;
                let currentPin = transactionPinHidden.value;
                if (currentPin.length < 4) {
                    pinInputs[currentPin.length].value = num;
                    transactionPinHidden.value += num;
                }
                if (currentPin.length >= 3) { // Enable button after 4 digits
                    confirmTransferBtn.disabled = false;
                }
            }
        });
    }

    if (pinBackspaceBtn) {
        pinBackspaceBtn.addEventListener('click', () => {
            let currentPin = transactionPinHidden.value;
            if (currentPin.length > 0) {
                pinInputs[currentPin.length - 1].value = '';
                transactionPinHidden.value = currentPin.slice(0, -1);
            }
            confirmTransferBtn.disabled = true;
        });
    }

    if (togglePinVisibility) {
        togglePinVisibility.addEventListener('click', (e) => {
            const isHidden = pinInputs[0].type === 'password';
            pinInputs.forEach(input => input.type = isHidden ? 'text' : 'password');
            e.currentTarget.querySelector('.material-icons').textContent = isHidden ? 'visibility' : 'visibility_off';
        });
    }

    // --- Status Modal Handling (Existing logic) ---
    const statusModal = document.getElementById('statusModal');
    const processingState = document.getElementById('processingState');
    const successState = document.getElementById('successState');
    const errorState = document.getElementById('errorState');
    const successSound = new Audio('../assets/sound/new-notification-07-210334.mp3');
    const errorCallSound = new Audio('../assets/sound/error-call.mp3');
    successSound.preload = 'auto';

    const transferStatus = <?php echo json_encode($transferStatus); ?>;
    const modalMessage = <?php echo json_encode($transferMessage); ?>;
    const transferredAmount = <?php echo json_encode($_SESSION['transferred_amount'] ?? 0); ?>;

    <?php unset($_SESSION['transfer_message'], $_SESSION['transfer_status'], $_SESSION['transferred_amount']); ?>

    if (transferStatus) {
        statusModal.classList.add('visible');
        processingState.classList.add('active');

        setTimeout(() => {
            processingState.classList.remove('active');

            if (transferStatus === 'success') {
                document.getElementById('successAmount').textContent = `SV ${parseFloat(transferredAmount).toFixed(2)}`;
                document.querySelector('#successState .modal-text').textContent = modalMessage || 'The funds have been transferred.';
                document.getElementById('modal-info').style.display = 'block';
                successState.classList.add('active');

                if (window.navigator && window.navigator.vibrate) {
                    navigator.vibrate(200);
                }
                successSound.play().catch(e => console.error("Sound play failed:", e));

            } else if (transferStatus === 'error') {
                document.getElementById('errorMessage').textContent = modalMessage || 'An unknown error occurred.';
                errorState.classList.add('active');
                if (window.navigator && window.navigator.vibrate) {
                    navigator.vibrate([100, 50, 100, 50, 100]);
                }
                errorCallSound.play().catch(e => console.error("Sound play failed:", e));
            }
        }, 1500);
    }

    document.querySelectorAll('.close-modal-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            statusModal.classList.remove('visible');
            // Reset states
            successState.classList.remove('active');
            errorState.classList.remove('active');
            processingState.classList.remove('active');
            // Optionally, redirect to reset the form or go back to step 1
            window.location.href = 'transfer'; 
        });
    });
});
</script>

<?php
// Include the footer template
require_once __DIR__ . '/../assets/template/end-template.php';
?>
