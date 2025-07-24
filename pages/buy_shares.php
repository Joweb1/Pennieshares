<?php
// 1. INCLUDES AND SESSION MANAGEMENT
require_once __DIR__ . '/../config/database.php'; // Initializes $pdo and constants
require_once __DIR__ . '/../src/functions.php';     // Your original functions (incl. secureSession, check_auth, wallet functions)
require_once __DIR__ . '/../src/assets_functions.php'; // Engine functions (incl. getAssetTypes, buyAsset)

check_auth();    // Ensure user is logged in

$loggedInUser = $_SESSION['user'];
$loggedInUserId = $loggedInUser['id'];

$preselectedAssetTypeId = filter_input(INPUT_GET, 'asset_type_id', FILTER_VALIDATE_INT);

// 2. FETCH NECESSARY DATA FOR THE PAGE
$assetTypes = getAssetTypes($pdo); // Fetch all available asset types
$currentUserWalletBalance = getUserWalletBalance($pdo, $loggedInUserId); // You'll need to create this function

// 3. HANDLE FORM SUBMISSION (PHP LOGIC)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'confirm_purchase') {
    $assetTypeId = filter_input(INPUT_POST, 'asset_type_id', FILTER_VALIDATE_INT);
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);

    $_SESSION['purchase_status'] = 'error'; // Default to error

    if ($assetTypeId && $quantity && $quantity > 0) {
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
    header("Location: buy_shares");
    exit();
}

// Check for status flag from session
$purchaseStatus = $_SESSION['purchase_status'] ?? null;
$modalMessage = $_SESSION['purchase_message'] ?? '';
$purchaseDetails = $_SESSION['purchase_details'] ?? [];

unset($_SESSION['purchase_status'], $_SESSION['purchase_message'], $_SESSION['purchase_details']);

// Refresh user balance for display
$currentUserWalletBalance = getUserWalletBalance($pdo, $loggedInUserId);

// 4. INCLUDE HEADER TEMPLATE
require_once __DIR__ . '/../assets/template/intro-template.php';
?>

    <style>
        /* --- Purchase Modal --- */
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
            background: rgba(255, 255, 255, 0.75); /* Adjusted for better light mode glassmorphism */
            border: 1px solid rgba(255, 255, 255, 1);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
        }
        html[data-theme="dark"] .purchase-modal-content {
             background: rgba(30, 41, 59, 0.6); /* Adjusted for better dark mode glassmorphism */
             border: 1px solid rgba(255, 255, 255, 0.15);
        }

        /* --- Animation States --- */
        .modal-state { display: none; }
        .modal-state.active { display: block; }

        /* Processing Animation */
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

        /* Success Animation */
        .success-animation .success-icon {
            width: 100px;
            height: 100px;
            margin: 0 auto 1rem;
        }
        .success-animation .checkmark {
            stroke: var(--accent-color);
            stroke-dasharray: 166;
            stroke-dashoffset: 166;
            animation: stroke 0.6s cubic-bezier(0.65, 0, 0.45, 1) 0.5s forwards;
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

        /* Error Animation */
        .error-animation .error-icon {
            width: 100px;
            height: 100px;
            margin: 0 auto 1rem;
        }
        .error-animation .x-mark {
            stroke: #ef4444; /* Red color for error */
            stroke-dasharray: 166;
            stroke-dashoffset: 166;
            animation: stroke 0.6s cubic-bezier(0.65, 0, 0.45, 1) 0.5s forwards;
        }

        /* Keep your existing well-structured CSS, with minor adjustments */
        /* Root variables from buy_shares.php can be kept or merged with your site's global styles */
        :root { /* If not globally defined */
            --font-family-main: 'Inter', 'Noto Sans', sans-serif;
            --border-radius: 0.75rem;
            --accent-color-rgb: 12, 127, 242;
        }
        /* ... (rest of your CSS, removing styles for payment methods, file upload) ... */
        body {
            font-family: var(--font-family-main);
            background-color: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
        }
        .container {
            max-width: 600px; /* Adjusted width slightly */
            margin: 0 auto;
            padding: 2rem 1rem;
        }
        .main-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .form-section, .summary-section, .result-section {
            background-color: var(--bg-secondary);
            padding: 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }
        .form-group {
            margin-bottom: 1.25rem;
        }
        .form-group label {
            display: block;
            font-size: 0.9rem; /* Slightly smaller label */
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-secondary);
        }
        .form-input, .form-select {
            width: 100%;
            height: 48px; /* Adjusted height */
            padding: 0 1rem;
            border-radius: var(--border-radius);
            background-color: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            font-size: 1rem;
            font-weight: 500;
        }
        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 2px rgba(var(--accent-color), 0.2);
        }
        .asset-info-box {
            background-color: var(--bg-tertiary);
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-top: 0.5rem;
            font-size: 0.9rem;
            border: 1px solid var(--border-color);
        }
        .asset-info-box p { margin-bottom: 0.5rem; }
        .asset-info-box p:last-child { margin-bottom: 0; }
        .info-value { font-weight: 600; color: var(--text-primary); }

        .wallet-balance-display {
            text-align: right;
            font-size: 0.9rem;
            color: var(--text-secondary);
            margin-bottom: 1rem;
        }
        .wallet-balance-display strong {
            font-weight: 700;
            color: var(--accent-color);
            font-size: 1.1rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 48px;
            border-radius: var(--border-radius);
            font-size: 1rem;
            font-weight: 600;
            padding: 0 1.5rem;
            cursor: pointer;
            border: none;
            text-decoration: none;
            transition: background-color 0.2s, opacity 0.2s;
        }
        .btn-primary {
            background-color: var(--accent-color);
            color: var(--accent-text);
        }
        .btn-primary:hover { background-color: #0a6cce; } /* Darken primary */
        .btn-full-width { width: 100%; }

        .message-box {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: var(--border-radius);
            border: 1px solid transparent;
            font-size: 0.95rem;
        }
        .message-box.info {
            background-color: var(--color-info-bg);
            border-color: var(--accent-color);
            color: var(--accent-color);
        }
        .message-box.success {
            background-color: var(--color-success-bg);
            border-color: var(--color-success);
            color: var(--color-success);
        }
        .message-box.error {
            background-color: var(--color-error-bg);
            border-color: var(--color-error);
            color: var(--color-error);
        }
        .purchase-log-details {
            background-color: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            padding: 10px;
            margin-top:10px;
            font-size:0.85em;
            border-radius: 5px;
            max-height: 300px;
            overflow-y:auto;
            white-space: pre-wrap; /* To show newlines from logs */
        }
        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 0.6rem 0;
            border-bottom: 1px solid var(--border-color);
        }
        .summary-item:last-child { border-bottom: none; }
        .summary-item .label { color: var(--text-secondary); }
        .summary-item .value { font-weight: 600; }

    </style>

    <main>
        <div class="container">
            <h1 class="main-title">Invest in Assets</h1>

            <?php if (isset($actionMessage) && $actionMessage): // Display PHP messages if any, before modal takes over ?>
                <div class="message-box <?php echo htmlspecialchars($messageType); ?>">
                    <?php echo htmlspecialchars($actionMessage); ?>
                </div>
            <?php endif; ?>

            <form id="buyAssetForm" method="POST" action="buy_shares">
                <input type="hidden" name="action" value="buy_asset_form_step_1"> <!-- Initial action for step 1 display -->

                <!-- Step 1: Selection -->
                <div id="step-1-selection" class="form-section">
                    <div class="wallet-balance-display">
                        Your Wallet Balance: <strong>SV <?php echo number_format($currentUserWalletBalance, 2); ?></strong>
                    </div>

                    <div class="form-group">
                        <label for="asset_type_id">Select Asset Type</label>
                        <select name="asset_type_id" id="asset_type_id" class="form-select" required>
                            <option value="">-- Select an Asset --</option>
                            <?php foreach ($assetTypes as $type): ?>
                                <option value="<?php echo $type['id']; ?>"
                                        data-price="<?php echo $type['price']; ?>"
                                        data-payout-cap="<?php echo $type['payout_cap']; ?>"
                                        data-duration-months="<?php echo $type['duration_months']; ?>"
                                        data-name="<?php echo htmlspecialchars($type['name']); ?>"
                                        <?php echo (isset($_POST['asset_type_id']) && $_POST['asset_type_id'] == $type['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($type['name']); ?> (Price: SV <?php echo number_format($type['price'], 2); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div id="asset-info-box" class="asset-info-box" style="display: none;">
                        <p><strong>Selected:</strong> <span id="info-name" class="info-value"></span></p>
                        <p><strong>Price: </strong><span id="info-price" class="info-value"></span></p>
                        <p><strong>Payout Cap:</strong> <span id="info-payout-cap" class="info-value"></span></p>
                        <p><strong>Duration:</strong> <span id="info-duration-months" class="info-value"></span></p>
                    </div>

                    <div class="form-group">
                        <label for="quantity">Quantity</label>
                        <input type="number" name="quantity" id="quantity" class="form-input" value="<?php echo htmlspecialchars($_POST['quantity'] ?? '1'); ?>" min="1" required>
                    </div>

                    <div class="form-group">
                        <label>Total Cost</label>
                        <input type="text" id="total_cost" class="form-input" value="SV 0.00" readonly style="font-weight: bold; background-color: var(--color-surface); border: 1px dashed var(--color-border);">
                    </div>

                    <button type="button" id="proceedToConfirmation" class="btn btn-primary btn-full-width">Review Purchase</button>
                </div>

                <!-- Step 2: Confirmation -->
                <div id="step-2-confirmation" class="summary-section" style="display: none;">
                    <h3>Confirm Your Purchase</h3>
                    <div class="summary-item">
                        <span class="label">Asset Type:</span>
                        <span id="confirm-asset-name" class="value"></span>
                    </div>
                    <div class="summary-item">
                        <span class="label">Quantity:</span>
                        <span id="confirm-quantity" class="value"></span>
                    </div>
                    <div class="summary-item">
                        <span class="label">Price Per Unit:</span>
                        <span id="confirm-unit-price" class="value"></span>
                    </div>
                    <div class="summary-item">
                        <span class="label">Total Cost:</span>
                        <span id="confirm-total-cost" class="value" style="color: var(--color-primary); font-size: 1.1rem;"></span>
                    </div>
                    <hr style="margin: 1rem 0; border-color: var(--color-border);">
                    <div class="summary-item">
                        <span class="label">Current Wallet Balance:</span>
                        <span id="confirm-wallet-before" class="value"></span>
                    </div>
                    <div class="summary-item">
                        <span class="label">Estimated Wallet Balance After:</span>
                        <span id="confirm-wallet-after" class="value"></span>
                    </div>

                    <div style="display:flex; justify-content: space-between; margin-top: 1.5rem;">
                         <button type="button" id="backToSelection" class="btn btn-secondary">Back</button>
                         <button type="submit" name="action" value="confirm_purchase" class="btn btn-primary" id="rollin">Confirm & Buy</button>
                    </div>
                </div>
            </form>
        </div>
    </main>

<!-- Purchase Animation Modal -->
<div class="purchase-modal-overlay" id="purchaseModal">
    <div class="purchase-modal-content">
        <!-- Processing State -->
        <div class="modal-state" id="processingState">
            <div class="processing-animation">
                <div class="spinner"></div>
            </div>
            <h3 class="modal-title">Processing Purchase</h3>
            <p class="modal-text">Please wait while we securely process your transaction.</p>
        </div>
        <!-- Success State -->
        <div class="modal-state" id="successState">
            <div class="success-animation">
                <svg class="success-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                    <circle class="checkmark__circle" cx="26" cy="26" r="25" fill="none"/>
                    <path class="checkmark" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
                </svg>
            </div>
            <h3 class="modal-title">Purchase Successful!</h3>
            <p class="modal-text">Your assets have been added to your portfolio.</p>
            <div class="modal-info">
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
        <!-- Error State -->
        <div class="modal-state" id="errorState">
            <div class="error-animation">
                <svg class="error-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                    <circle class="x-mark__circle" cx="26" cy="26" r="25" fill="none"/>
                    <path class="x-mark" fill="none" d="M16 16 36 36 M36 16 16 36"/>
                </svg>
            </div>
            <h3 class="modal-title">Purchase Failed</h3>
            <p class="modal-text" id="error-message"></p>
            <button class="btn btn-primary btn-full-width close-modal-btn">Try Again</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const purchaseModal = document.getElementById('purchaseModal');
    const processingState = document.getElementById('processingState');
    const successState = document.getElementById('successState');
    const errorState = document.getElementById('errorState');
    const successSound = new Audio('../assets/sound/new-notification-07-210334.mp3');
    successSound.preload = 'auto';
    const rollin = document.getElementById('buyAssetForm');
    
    rollin.addEventListener('submit', () => {
        // Hide other states just in case
        successState.classList.remove('active');
        errorState.classList.remove('active');

        // Show processing state and modal
        processingState.classList.add('active');
        purchaseModal.classList.add('visible');
    });

    const purchaseStatus = <?php echo json_encode($purchaseStatus); ?>;

    if (purchaseStatus) {
        processingState.classList.add('active');
        successState.classList.remove('active');
        errorState.classList.remove('active');
        purchaseModal.classList.add('visible');

        setTimeout(() => {
            processingState.classList.remove('active');

            if (purchaseStatus === 'success') {
                const details = <?php echo json_encode($purchaseDetails); ?>;
                document.getElementById('purchased-asset-name').textContent = details.asset_name;
                document.getElementById('purchased-quantity').textContent = details.quantity;
                document.getElementById('purchased-total-cost').textContent = "SV " + parseFloat(details.total_cost).toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                successState.classList.add('active');

                if (window.navigator && window.navigator.vibrate) {
                    navigator.vibrate(200);
                }
                successSound.play().catch(e => console.error("Sound play failed:", e));

            } else if (purchaseStatus === 'error') {
                const errorMessage = <?php echo json_encode($modalMessage); ?>;
                document.getElementById('error-message').textContent = errorMessage;
                errorState.classList.add('active');
            }
        }, 2500);
    }

    document.querySelectorAll('.close-modal-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            purchaseModal.classList.remove('visible');
        });
    });

    // --- Existing form logic ---
    const assetSelect = document.getElementById('asset_type_id');
    const quantityInput = document.getElementById('quantity');
    const totalCostDisplay = document.getElementById('total_cost');
    const assetInfoBox = document.getElementById('asset-info-box');

    // Info box elements
    const infoName = document.getElementById('info-name');
    const infoPrice = document.getElementById('info-price');
    const infoPayoutCap = document.getElementById('info-payout-cap');
    const infoDuration = document.getElementById('info-duration-months');

    // Step handling elements
    const form = document.getElementById('buyAssetForm');
    const step1Selection = document.getElementById('step-1-selection');
    const step2Confirmation = document.getElementById('step-2-confirmation');
    const proceedButton = document.getElementById('proceedToConfirmation');
    const backButton = document.getElementById('backToSelection');

    // Confirmation screen elements
    const confirmAssetName = document.getElementById('confirm-asset-name');
    const confirmQuantity = document.getElementById('confirm-quantity');
    const confirmUnitPrice = document.getElementById('confirm-unit-price');
    const confirmTotalCost = document.getElementById('confirm-total-cost');
    const confirmWalletBefore = document.getElementById('confirm-wallet-before');
    const confirmWalletAfter = document.getElementById('confirm-wallet-after');

    const currentUserBalance = parseFloat(<?php echo json_encode($currentUserWalletBalance); ?>);
    const preselectedAssetId = <?php echo json_encode($preselectedAssetTypeId); ?>;

    function formatCurrency(amount) {
        return 'SV' + parseFloat(amount).toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function updateAssetInfo() {
        const selectedOption = assetSelect.options[assetSelect.selectedIndex];
        if (selectedOption && selectedOption.value) {
            assetInfoBox.style.display = 'block';
            infoName.textContent = selectedOption.dataset.name;
            infoPrice.textContent = formatCurrency(selectedOption.dataset.price);
            infoPayoutCap.textContent = formatCurrency(selectedOption.dataset.payoutCap);
            const duration = parseInt(selectedOption.dataset.durationMonths);
            infoDuration.textContent = duration > 0 ? duration + ' months' : 'Unlimited';
        } else {
            assetInfoBox.style.display = 'none';
        }
        updateTotalCost();
    }

    function updateTotalCost() {
        const selectedOption = assetSelect.options[assetSelect.selectedIndex];
        const quantity = parseInt(quantityInput.value) || 0;
        if (selectedOption && selectedOption.value && quantity > 0) {
            const price = parseFloat(selectedOption.dataset.price);
            totalCostDisplay.value = formatCurrency(price * quantity);
        } else {
            totalCostDisplay.value = formatCurrency(0);
        }
    }

    assetSelect.addEventListener('change', updateAssetInfo);
    quantityInput.addEventListener('input', updateTotalCost);

    proceedButton.addEventListener('click', () => {
        const selectedOption = assetSelect.options[assetSelect.selectedIndex];
        const quantity = parseInt(quantityInput.value);

        if (!selectedOption || !selectedOption.value) {
            alert('Please select an asset type.');
            return;
        }
        if (isNaN(quantity) || quantity < 1) {
            alert('Please enter a valid quantity (minimum 1).');
            return;
        }

        const price = parseFloat(selectedOption.dataset.price);
        const totalCost = price * quantity;

        if (currentUserBalance < totalCost) {
             // Show error modal instead of alert
             document.getElementById('error-message').textContent = 'Insufficient wallet balance to proceed. Required: ' + formatCurrency(totalCost) + ', Available: ' + formatCurrency(currentUserBalance);
             processingState.classList.remove('active'); // Ensure processing is off
             successState.classList.remove('active'); // Ensure success is off
             errorState.classList.add('active');
             purchaseModal.classList.add('visible');
             return;
        }

        confirmAssetName.textContent = selectedOption.dataset.name;
        confirmQuantity.textContent = quantity;
        confirmUnitPrice.textContent = formatCurrency(price);
        confirmTotalCost.textContent = formatCurrency(totalCost);
        confirmWalletBefore.textContent = formatCurrency(currentUserBalance);
        confirmWalletAfter.textContent = formatCurrency(currentUserBalance - totalCost);

        step1Selection.style.display = 'none';
        step2Confirmation.style.display = 'block';
    });

    backButton.addEventListener('click', () => {
        step1Selection.style.display = 'block';
        step2Confirmation.style.display = 'none';
    });

    if (preselectedAssetId) {
        assetSelect.value = preselectedAssetId;
    }
    updateAssetInfo();
});
</script>

<?php
// 5. INCLUDE FOOTER TEMPLATE
require_once __DIR__ . '/../assets/template/end-template.php';
?>
