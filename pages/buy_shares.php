<?php
// 1. INCLUDES AND SESSION MANAGEMENT
require_once __DIR__ . '/../config/database.php'; // Initializes $pdo and constants
require_once __DIR__ . '/../src/functions.php';     // Your original functions (incl. secureSession, check_auth, wallet functions)
require_once __DIR__ . '/../src/assets_functions.php'; // Engine functions (incl. getAssetTypes, buyAsset)

// secureSession(); // Start/secure session
check_auth();    // Ensure user is logged in

$loggedInUser = $_SESSION['user'];
$loggedInUserId = $loggedInUser['id'];

$preselectedAssetTypeId = filter_input(INPUT_GET, 'asset_type_id', FILTER_VALIDATE_INT);

// 2. FETCH NECESSARY DATA FOR THE PAGE
$assetTypes = getAssetTypes($pdo); // Fetch all available asset types
$currentUserWalletBalance = getUserWalletBalance($pdo, $loggedInUserId); // You'll need to create this function

$actionMessage = '';
$messageType = 'info'; // 'info', 'success', 'error'
$purchaseDetailsLog = null; // To store logs from buyAsset

// 3. HANDLE FORM SUBMISSION (PHP LOGIC)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'confirm_purchase') {
    $assetTypeId = filter_input(INPUT_POST, 'asset_type_id', FILTER_VALIDATE_INT);
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);

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
                // -------- CORE PURCHASE LOGIC -----------
                $pdo->beginTransaction();
                try {
                    // 1. Debit User's Wallet
                    // This function needs to update users.wallet_balance AND log to wallet_transactions
                    $debitSuccess = debitUserWallet(
                        $pdo,
                        $loggedInUserId,
                        $totalCost,
                        "Purchase of {$quantity} x {$selectedAssetType['name']} (Asset Type ID: {$assetTypeId})"
                    );

                    if (!$debitSuccess) {
                        throw new Exception("Wallet debit failed. Insufficient balance or other issue.");
                    }

                    // 2. Call the Engine's buyAsset function
                    // $purchaseDetails will contain logs and success/error info from the engine
                    $purchaseDetails = buyAsset($pdo, $loggedInUserId, $assetTypeId, $quantity);
                    $purchaseDetailsLog = $purchaseDetails; // Store for display

                    // Check if buyAsset itself reported an error (e.g., in its 'purchases' array or 'summary')
                    $engineError = false;
                    if (isset($purchaseDetails['purchases'][0]['error'])) {
                        $engineError = true;
                        throw new Exception("Asset purchase engine error: " . $purchaseDetails['purchases'][0]['error']);
                    }
                    foreach ($purchaseDetails['summary'] as $summaryLine) {
                        if (stripos($summaryLine, 'error') !== false || stripos($summaryLine, 'failed') !== false) {
                             $engineError = true;
                             throw new Exception("Asset purchase failed: " . $summaryLine);
                        }
                    }


                    $pdo->commit();
                    $actionMessage = "Successfully purchased {$quantity} of '{$selectedAssetType['name']}'.";
                    $messageType = 'success';
                    // Refresh wallet balance after successful transaction
                    $currentUserWalletBalance = getUserWalletBalance($pdo, $loggedInUserId);

                } catch (Exception $e) {
                    $pdo->rollBack();
                    $actionMessage = "Purchase failed: " . $e->getMessage();
                    $messageType = 'error';
                    // If $purchaseDetailsLog was set before error, it might still be useful
                    if (isset($purchaseDetails) && empty($purchaseDetailsLog)) $purchaseDetailsLog = $purchaseDetails;
                }
                // -------- END CORE PURCHASE LOGIC --------
            } else {
                $actionMessage = "Insufficient wallet balance. Required: " . number_format($totalCost, 2) . ", Available: " . number_format($currentUserWalletBalance, 2);
                $messageType = 'error';
            }
        } else {
            $actionMessage = "Please select a valid asset type and quantity.";
            $messageType = 'error';
        }
    } else {
        $actionMessage = "Please select a valid asset type and quantity.";
        $messageType = 'error';
    }
}

// 4. INCLUDE HEADER TEMPLATE
require_once __DIR__ . '/../assets/template/intro-template.php';
?>

    <style>
        /* Keep your existing well-structured CSS, with minor adjustments */
        /* Root variables from buy_shares.php can be kept or merged with your site's global styles */
        :root { /* If not globally defined */
            --font-family-main: 'Inter', 'Noto Sans', sans-serif;
            --border-radius: 0.75rem;
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

            <?php if ($actionMessage): ?>
                <div class="message-box <?php echo htmlspecialchars($messageType); ?>">
                    <?php echo htmlspecialchars($actionMessage); ?>
                </div>
            <?php endif; ?>

            <?php if ($purchaseDetailsLog && ($messageType === 'success' || $messageType === 'error')): // Show logs on result ?>
                <div class="result-section">
                    <h4>Transaction Details:</h4>
                    <div class="purchase-log-details">
                        <?php
                        // Enhanced logging display
                        if (isset($purchaseDetailsLog['summary'])) {
                            echo "<strong>Summary:</strong>
";
                            foreach ($purchaseDetailsLog['summary'] as $line) {
                                echo htmlspecialchars($line) . "
";
                            }
                        }
                        if (isset($purchaseDetailsLog['purchases'])) {
                            foreach ($purchaseDetailsLog['purchases'] as $idx => $details) {
                                echo "
<strong>Asset Buy Attempt " . ($idx + 1) . ":</strong>
";
                                if (isset($details['error'])) {
                                    echo "  <span style='color:red;'>Error: " . htmlspecialchars($details['error']) . "</span>
";
                                } else {
                                    echo "  Message: " . htmlspecialchars($details['message'] ?? 'N/A') . "
";
                                    if(!empty($details['parent_update'])) echo "  Parent Update: " . htmlspecialchars($details['parent_update']) . "
";
                                    if(!empty($details['company_profit_log'])) echo "  Company Profit: " . htmlspecialchars($details['company_profit_log']) . "
";
                                    if(!empty($details['reservation_direct_log'])) echo "  Reservation Fund: " . htmlspecialchars($details['reservation_direct_log']) . "
";
                                    if(!empty($details['generational_payouts_log'])) {
                                        echo "  Generational Payouts:
";
                                        foreach($details['generational_payouts_log'] as $log) echo "    - " . htmlspecialchars($log) . "
";
                                    }
                                    if(!empty($details['shared_payouts_log'])) {
                                        echo "  Shared Payouts:
";
                                        foreach($details['shared_payouts_log'] as $log) echo "    - " . htmlspecialchars($log) . "
";
                                    }
                                }
                            }
                        }
                        if (isset($purchaseDetailsLog['expired_check_count']) && $purchaseDetailsLog['expired_check_count'] > 0) {
                            echo "
Assets newly marked as expired during operation: " . htmlspecialchars($purchaseDetailsLog['expired_check_count']) . "
";
                        }
                        ?>
                    </div>
                </div>
            <?php endif; ?>

            <form id="buyAssetForm" method="POST" action="buy_shares">
                <input type="hidden" name="action" value="buy_asset_form_step_1"> <!-- Initial action for step 1 display -->

                <!-- Step 1: Selection -->
                <div id="step-1-selection" class="form-section">
                    <div class="wallet-balance-display">
                        Your Wallet Balance: <strong>₦ <?php echo number_format($currentUserWalletBalance, 2); ?></strong>
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
                                    <?php echo htmlspecialchars($type['name']); ?> (Price: ₦ <?php echo number_format($type['price'], 2); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div id="asset-info-box" class="asset-info-box" style="display: none;">
                        <p><strong>Selected:</strong> <span id="info-name" class="info-value"></span></p>
                        <p><strong>Price:</strong> ₦ <span id="info-price" class="info-value"></span></p>
                        <p><strong>Payout Cap (Generational):</strong> ₦ <span id="info-payout-cap" class="info-value"></span></p>
                        <p><strong>Duration:</strong> <span id="info-duration-months" class="info-value"></span> months</p>
                    </div>

                    <div class="form-group">
                        <label for="quantity">Quantity</label>
                        <input type="number" name="quantity" id="quantity" class="form-input" value="<?php echo htmlspecialchars($_POST['quantity'] ?? '1'); ?>" min="1" required>
                    </div>

                    <div class="form-group">
                        <label>Total Cost</label>
                        <input type="text" id="total_cost" class="form-input" value="₦ 0.00" readonly style="font-weight: bold; background-color: var(--color-surface); border: 1px dashed var(--color-border);">
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
                         <button type="submit" name="action" value="confirm_purchase" class="btn btn-primary">Confirm & Buy</button>
                    </div>
                </div>
            </form>
        </div>
    </main>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
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
            return '₦ ' + parseFloat(amount).toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function updateAssetInfo() {
            const selectedOption = assetSelect.options[assetSelect.selectedIndex];
            if (selectedOption && selectedOption.value) {
                assetInfoBox.style.display = 'block';
                infoName.textContent = selectedOption.dataset.name;
                infoPrice.textContent = formatCurrency(selectedOption.dataset.price);
                infoPayoutCap.textContent = formatCurrency(selectedOption.dataset.payoutCap);
                const duration = parseInt(selectedOption.dataset.durationMonths);
                infoDuration.textContent = duration > 0 ? duration : 'Unlimited';
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
                 alert('Insufficient wallet balance to proceed. Required: ' + formatCurrency(totalCost) + ', Available: ' + formatCurrency(currentUserBalance));
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

        // Initialize on page load (if form was submitted and reloaded with errors)
        if (preselectedAssetId) {
            assetSelect.value = preselectedAssetId;
        }
        updateAssetInfo();
        // Check if there's a message indicating a completed transaction, then hide the form.
        <?php if ($actionMessage && ($messageType === 'success' || $messageType === 'error') && $purchaseDetailsLog): ?>
            step1Selection.style.display = 'none';
            step2Confirmation.style.display = 'none';
        <?php endif; ?>
    });
    </script>

<?php
// 5. INCLUDE FOOTER TEMPLATE
require_once __DIR__ . '/../assets/template/end-template.php';
?>
