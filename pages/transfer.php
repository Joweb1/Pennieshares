<?php
require_once __DIR__ . '/../src/init.php';
require_once __DIR__ . '/../src/kyc_functions.php';

$kyc_status = getKycStatus($pdo, $user['id']);
if (!$kyc_status || $kyc_status['status'] !== 'verified') {
    $_SESSION['show_kyc_popup'] = true;
    header('Location: /wallet');
    exit;
}

require_once __DIR__ . '/../src/functions.php';
check_auth();

$currentUser = $_SESSION['user'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'user_transfer_wallet') {
    $senderId = $currentUser['id'];
    $receiverUsername = trim($_POST['receiver_username'] ?? '');
    $amount = filter_input(INPUT_POST, 'transfer_amount', FILTER_VALIDATE_FLOAT);

    // Default to error
    $_SESSION['transfer_status'] = 'error';

    if (empty($receiverUsername) || $amount === false || $amount <= 0) {
        $_SESSION['transfer_message'] = "Invalid recipient username or amount.";
    } else {
        $receiverUser = getUserByIdOrName($pdo, $receiverUsername);

        if (!$receiverUser) {
            $_SESSION['transfer_message'] = "Recipient user '{$receiverUsername}' not found.";
        } else {
            // --- DEBUGGING START ---
            error_log("DEBUG: Sender (currentUser) is_broker: " . ($currentUser['is_broker'] ? 'true' : 'false'));
            error_log("DEBUG: Receiver (receiverUser) is_broker: " . ($receiverUser['is_broker'] ? 'true' : 'false'));
            // --- DEBUGGING END ---

            if (!$currentUser['is_broker'] && $receiverUser['is_broker'] != 1) {
                $_SESSION['transfer_message'] = "You can only transfer funds to a Broker.";
            } else {
                $transferResult = transferWalletBalance($pdo, $senderId, $receiverUser['id'], $amount);
                if ($transferResult['success']) {
                    $_SESSION['transfer_status'] = 'success';
                    $_SESSION['transfer_amount'] = $amount;
                } else {
                    $_SESSION['transfer_message'] = $transferResult['message'];
                }
            }
        }
    }
    header("Location: transfer"); // Redirect to self to show modal
    exit();
}

// Check for the status flag from the session to trigger the modal
$transferStatus = $_SESSION['transfer_status'] ?? null;
$modalMessage = $_SESSION['transfer_message'] ?? '';
$transferredAmount = $_SESSION['transfer_amount'] ?? 0;

// Unset session variables so the modal doesn't show again on refresh
unset($_SESSION['transfer_status'], $_SESSION['transfer_message'], $_SESSION['transfer_amount']);


// Refresh current user data to show updated wallet balance
$currentUser = getUserByIdOrName($pdo, $currentUser['id']);
$_SESSION['user'] = $currentUser;


require_once __DIR__ . '/../assets/template/intro-template.php';
?>

<style>
    /* All existing CSS from before */
    body {
        font-family: var(--font-primary);
        background-color: var(--bg-primary);
        color: var(--text-primary);
        min-height: 100vh;
    }
    .container {
        max-width: 600px;
        margin: 0 auto;
        padding: 2rem 1rem;
    }
    .main-title {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 1.5rem;
        text-align: center;
    }
    .form-section {
        background-color: var(--bg-secondary);
        padding: 1.5rem;
        border-radius: 0.75rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        margin-bottom: 2rem;
    }
    .form-group {
        margin-bottom: 1.25rem;
    }
    .form-group label {
        display: block;
        font-size: 0.9rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
        color: var(--text-secondary);
    }
    .form-input {
        width: 100%;
        height: 48px;
        padding: 0 1rem;
        border-radius: 0.75rem;
        background-color: var(--bg-tertiary);
        border: 1px solid var(--border-color);
        color: var(--text-primary);
        font-size: 1rem;
        font-weight: 500;
    }
    .form-input:focus {
        outline: none;
        border-color: var(--accent-color);
        box-shadow: 0 0 0 2px rgba(var(--accent-color), 0.2);
    }
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
        background-color: var(--accent-color);
        color: var(--accent-text);
    }
    .btn-primary:hover { background-color: #0a6cce; }
    .btn-full-width { width: 100%; }
    .message-box {
        padding: 1rem;
        margin-bottom: 1.5rem;
        border-radius: 0.75rem;
        border: 1px solid transparent;
        font-size: 0.95rem;
    }
    .message-box.info {
        background-color: #e0f2fe;
        border-color: #0c7ff2;
        color: #0c7ff2;
    }
    .message-box.success {
        background-color: #dcfce7;
        border-color: #22c55e;
        color: #22c55e;
    }
        .message-box.error {
            background-color: #fee2e2;
            border-color: #ef4444;
            color: #ef4444;
        }

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
            background: rgba(255, 255, 255, 0.75);
            border: 1px solid rgba(255, 255, 255, 1);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
        }
        html[data-theme="dark"] .purchase-modal-content {
             background: rgba(30, 41, 59, 0.6);
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
</style>

<main>
    <div class="container">
        <h1 class="main-title">Transfer Funds</h1>

        <div class="form-section">
            <div class="wallet-balance-display">
                Your Wallet Balance: <strong>SV <?php echo number_format($currentUser['wallet_balance'], 2); ?></strong>
            </div>

            <form method="post" id="transferForm">
                <input type="hidden" name="action" value="user_transfer_wallet">
                <div class="form-group">
                    <label for="receiver_username">Recipient Username or Partner Code:</label>
                    <input type="text" name="receiver_username" id="receiver_username" class="form-input" required>
                </div>
                <div class="form-group">
                    <label for="transfer_amount">Amount (SV):</label>
                    <input type="number" step="0.01" name="transfer_amount" id="transfer_amount" class="form-input" required>
                </div>
                <button type="submit" class="btn btn-primary btn-full-width">Transfer Funds</button>
            </form>
        </div>
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
            <h3 class="modal-title">Processing Transfer</h3>
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
            <h3 class="modal-title">Transfer Successful!</h3>
            <p class="modal-text">The funds have been transferred.</p>
            <div class="modal-info">
                <div class="info-item">
                    <span class="label">Amount Transferred:</span>
                    <span class="value" id="success-amount"></span>
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
            <h3 class="modal-title">Transfer Failed</h3>
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
    const errorCallSound = new Audio('../assets/sound/error-call.mp3'); // Add this line
    successSound.preload = 'auto';

    const transferStatus = <?php echo json_encode($transferStatus); ?>;

    if (transferStatus) {
        // Show processing modal first
        processingState.classList.add('active');
        successState.classList.remove('active');
        errorState.classList.remove('active');
        purchaseModal.classList.add('visible');

        setTimeout(() => {
            processingState.classList.remove('active');

            if (transferStatus === 'success') {
                const transferredAmount = <?php echo json_encode($transferredAmount); ?>;
                document.getElementById('success-amount').textContent = "SV " + parseFloat(transferredAmount).toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                successState.classList.add('active');

                if (window.navigator && window.navigator.vibrate) {
                    navigator.vibrate(200);
                }
                successSound.play().catch(e => console.error("Sound play failed:", e));

            } else if (transferStatus === 'error') {
                const errorMessage = <?php echo json_encode($modalMessage); ?>;
                document.getElementById('error-message').textContent = errorMessage;
                errorState.classList.add('active');
                if (window.navigator && window.navigator.vibrate) {
                    navigator.vibrate([100, 50, 100, 50, 100]); // Three short vibrations
                }
                errorCallSound.play().catch(e => console.error("Sound play failed:", e));
            }
        }, 2500); // 2.5-second processing simulation
    }

    document.querySelectorAll('.close-modal-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            purchaseModal.classList.remove('visible');
        });
    });
});
</script>

<?php
require_once __DIR__ . '/../assets/template/end-template.php';
?>
