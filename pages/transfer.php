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
    $pin = trim($_POST['transaction_pin'] ?? '');

    $_SESSION['transfer_status'] = 'error';

    if (empty($receiverUsername) || $amount === false || $amount <= 0) {
        $_SESSION['transfer_message'] = "Invalid recipient username or amount.";
    } elseif (empty($pin) || !preg_match('/^\d{4}$/', $pin)) {
        $_SESSION['transfer_message'] = "Please enter a valid 4-digit transaction PIN.";
    } else {
        $receiverUser = getUserByIdOrName($pdo, $receiverUsername);

        if (!$receiverUser) {
            $_SESSION['transfer_message'] = "Recipient user '{$receiverUsername}' not found.";
        } else {
            if (!$currentUser['is_broker'] && $receiverUser['is_broker'] != 1) {
                $_SESSION['transfer_message'] = "You can only transfer funds to a Broker.";
            } else {
                $transferResult = transferWalletBalance($pdo, $senderId, $receiverUser['id'], $amount, $pin);
                if ($transferResult['success']) {
                    $_SESSION['transfer_status'] = 'success';
                    $_SESSION['transfer_message'] = $transferResult['message'];
                    $_SESSION['transfer_amount'] = $amount;
                    send_user_transfer_email('penniepoint@gmail.com', $currentUser['username'], $receiverUser['username'], $amount);
                } else {
                    $_SESSION['transfer_message'] = $transferResult['message'];
                }
            }
        }
    }
    header("Location: transfer");
    exit();
}

$transferStatus = $_SESSION['transfer_status'] ?? null;
$modalMessage = $_SESSION['transfer_message'] ?? '';
$transferredAmount = $_SESSION['transfer_amount'] ?? 0;

unset($_SESSION['transfer_status'], $_SESSION['transfer_message'], $_SESSION['transfer_amount']);

$currentUser = getUserByIdOrName($pdo, $currentUser['id']);
$_SESSION['user'] = $currentUser;


// Include the intro template
require_once __DIR__ . '/../assets/template/intro-template.php';
?>
<script>
  tailwind.config = {
    darkMode: ['class', '[data-theme="dark"]'],
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
  body { 
    font-family: 'Roboto', sans-serif; 
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
    position: absolute;
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
    color: var(--text-primary);
    margin-bottom: 0.5rem;
}
.modal-text {
    font-size: 1.3rem;
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
    background-color: #0c7ff2;
    color: white;
}
.btn-primary:hover { background-color: #0a6cce; }
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

<div class="max-w-md mx-auto pb-20">
    <h1 class="font-bold text-2xl text-primary">Transfer Funds</h1>

  <main class="p-3">
    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-md mb-4">
        <div class="text-right text-base text-gray-600 dark:text-gray-400 mb-2">
            Your Wallet Balance: <strong class="text-primary">SV <?php echo number_format($currentUser['wallet_balance'], 2); ?></strong>
        </div>

        <form id="transferFormTrigger">
            <div id="formError" class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <strong class="font-bold">Error!</strong>
                <span class="block sm:inline" id="formErrorMessage"></span>
            </div>
            <div class="mb-4">
                <label for="receiver_username" class="block text-base font-medium text-gray-700 dark:text-gray-300">Recipient Username or Partner Code:</label>
                <input type="text" id="receiver_username" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
            </div>
            <div class="mb-4">
                <label for="transfer_amount" class="block text-base font-medium text-gray-700 dark:text-gray-300">Amount (SV):</label>
                <input type="number" step="0.01" id="transfer_amount" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
            </div>
            <button type="button" id="reviewTransferBtn" class="w-full bg-primary text-white py-2 px-4 rounded-md hover:bg-primary/90">Review Transfer</button>
        </form>
    </div>
  </main>
</div>

<!-- PIN Modal -->
<div id="pinModal" class="bg-background-light dark:bg-background-dark text-gray-900 dark:text-gray-100 h-screen flex-col hidden z-50 purchase-modal-overlay">
    <div id="pinStep" class="flex flex-col h-full purchase-modal-content">
        <div class="flex justify-between items-center mb-4">
            <button id="closePinModal" class="text-gray-900 dark:text-gray-100">
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
                        <input type="password" id="pinInput1" maxlength="1" class="w-12 h-12 text-center text-2xl font-bold bg-gray-100 dark:bg-gray-800 rounded-lg border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-primary" readonly>
                        <input type="password" id="pinInput2" maxlength="1" class="w-12 h-12 text-center text-2xl font-bold bg-gray-100 dark:bg-gray-800 rounded-lg border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-primary" readonly>
                        <input type="password" id="pinInput3" maxlength="1" class="w-12 h-12 text-center text-2xl font-bold bg-gray-100 dark:bg-gray-800 rounded-lg border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-primary" readonly>
                        <input type="password" id="pinInput4" maxlength="1" class="w-12 h-12 text-center text-2xl font-bold bg-gray-100 dark:bg-gray-800 rounded-lg border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-primary" readonly>
                    </div>
                </div>
            </div>
        </div>
        <div class="pb-12">
            <form id="transferForm" method="post">
                <input type="hidden" name="action" value="user_transfer_wallet">
                <input type="hidden" name="receiver_username" id="form_receiver_username_pin">
                <input type="hidden" name="transfer_amount" id="form_transfer_amount_pin">
                <input type="hidden" name="transaction_pin" id="transaction_pin_hidden">
                
                <button type="submit" id="confirmTransferBtn" class="w-full bg-green-600 hover:bg-green-700 text-white py-4 rounded-full text-xl font-medium mb-6" disabled>
                    Confirm Transfer
                </button>
            </form>
            <div id="pinNumpad" class="grid grid-cols-3 gap-y-4 text-center text-3xl font-light">
                <button type="button" class="text-green-500">1</button>
                <button type="button" class="text-green-500">2</button>
                <button type="button" class="text-green-500">3</button>
                <button type="button" class="text-green-500">4</button>
                <button type="button" class="text-green-500">5</button>
                <button type="button" class="text-green-500">6</button>
                <button type="button" class="text-green-500">7</button>
                <button type="button" class="text-green-500">8</button>
                <button type="button" class="text-green-500">9</button>
                <button type="button" id="togglePinVisibility" class="p-2 rounded-full text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 focus:outline-none">
                    <span class="material-icons" id="pinVisibilityIcon">visibility_off</span>
                </button>
                <button type="button" class="text-green-500">0</button>
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
    const reviewTransferBtn = document.getElementById('reviewTransferBtn');
    const pinModal = document.getElementById('pinModal');
    const closePinModal = document.getElementById('closePinModal');
    const pinInputs = [document.getElementById('pinInput1'), document.getElementById('pinInput2'), document.getElementById('pinInput3'), document.getElementById('pinInput4')];
    const pinNumpad = document.getElementById('pinNumpad');
    const pinBackspaceBtn = document.getElementById('pinBackspaceBtn');
    const confirmTransferBtn = document.getElementById('confirmTransferBtn');
    const transactionPinHidden = document.getElementById('transaction_pin_hidden');
    const togglePinVisibility = document.getElementById('togglePinVisibility');
    const formError = document.getElementById('formError');
    const formErrorMessage = document.getElementById('formErrorMessage');

    reviewTransferBtn.addEventListener('click', () => {
        const receiverUsername = document.getElementById('receiver_username').value;
        const transferAmount = document.getElementById('transfer_amount').value;

        if (!receiverUsername || !transferAmount || transferAmount <= 0) {
            formErrorMessage.textContent = 'Please fill in all fields correctly.';
            formError.classList.remove('hidden');
            formError.classList.add('fade-in');
            return;
        }

        formError.classList.add('hidden');
        document.getElementById('confirmRecipientPin').textContent = receiverUsername;
        document.getElementById('confirmAmountPin').textContent = `SV ${parseFloat(transferAmount).toFixed(2)}`;
        
        document.getElementById('form_receiver_username_pin').value = receiverUsername;
        document.getElementById('form_transfer_amount_pin').value = transferAmount;

        pinInputs.forEach(input => input.value = '');
        transactionPinHidden.value = '';
        confirmTransferBtn.disabled = true;

        pinModal.classList.add('visible');
    });

    closePinModal.addEventListener('click', () => pinModal.classList.remove('visible'));

    pinNumpad.addEventListener('click', (e) => {
        if (e.target.tagName === 'BUTTON' && e.target.id !== 'pinBackspaceBtn' && e.target.id !== 'togglePinVisibility') {
            const num = e.target.textContent;
            let currentPin = transactionPinHidden.value;
            if (currentPin.length < 4) {
                pinInputs[currentPin.length].value = num;
                transactionPinHidden.value += num;
            }
            if (currentPin.length >= 3) {
                confirmTransferBtn.disabled = false;
            }
        }
    });

    pinBackspaceBtn.addEventListener('click', () => {
        let currentPin = transactionPinHidden.value;
        if (currentPin.length > 0) {
            pinInputs[currentPin.length - 1].value = '';
            transactionPinHidden.value = currentPin.slice(0, -1);
        }
        confirmTransferBtn.disabled = true;
    });

    togglePinVisibility.addEventListener('click', (e) => {
        const isHidden = pinInputs[0].type === 'password';
        pinInputs.forEach(input => input.type = isHidden ? 'text' : 'password');
        e.currentTarget.querySelector('.material-icons').textContent = isHidden ? 'visibility' : 'visibility_off';
    });

    document.getElementById('transferForm').addEventListener('submit', function() {
        pinModal.classList.remove('visible');
        statusModal.classList.add('visible');
        processingState.classList.add('active');
    });
    
    // Transfer Status Modal Handling
    const statusModal = document.getElementById('statusModal');
    const processingState = document.getElementById('processingState');
    const successState = document.getElementById('successState');
    const errorState = document.getElementById('errorState');
    const successSound = new Audio('../assets/sound/new-notification-07-210334.mp3');
    const errorCallSound = new Audio('../assets/sound/error-call.mp3');
    successSound.preload = 'auto';

    const transferStatus = <?php echo json_encode($transferStatus); ?>;
    const modalMessage = <?php echo json_encode($modalMessage); ?>;
    const transferredAmount = <?php echo json_encode($transferredAmount); ?>;

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
        });
    });
});
</script>

<?php
require_once __DIR__ . '/../assets/template/end-template.php';
?>
