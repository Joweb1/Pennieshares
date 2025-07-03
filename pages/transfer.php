<?php
require_once __DIR__ . '/../src/functions.php';
check_auth();

$actionMessage = '';
$messageType = 'info'; // 'info', 'success', 'error'
$currentUser = $_SESSION['user'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'user_transfer_wallet') {
    $senderId = $currentUser['id'];
    $receiverUsername = trim($_POST['receiver_username'] ?? '');
    $amount = filter_input(INPUT_POST, 'transfer_amount', FILTER_VALIDATE_FLOAT);

    if (empty($receiverUsername) || $amount === false || $amount <= 0) {
        $actionMessage = "Error: Invalid recipient username or amount.";
        $messageType = 'error';
    } else {
        // Get receiver user details
        $receiverUser = getUserByIdOrName($pdo, $receiverUsername);

        if (!$receiverUser) {
            $actionMessage = "Error: Recipient user '{$receiverUsername}' not found.";
            $messageType = 'error';
        } elseif ($receiverUser['is_admin'] != 1) {
            $actionMessage = "Error: You can only transfer funds to an admin user.";
            $messageType = 'error';
        } else {
            $transferResult = transferWalletBalance($pdo, $senderId, $receiverUser['id'], $amount);
            if ($transferResult['success']) {
                $actionMessage = "Successfully transferred ₦{$amount} to admin user {$receiverUsername}.";
                $messageType = 'success';
                // Update session balance after successful transfer
                $_SESSION['user']['wallet_balance'] = getUserByIdOrName($pdo, $senderId)['wallet_balance'];
            } else {
                $actionMessage = "Error: " . $transferResult['message'];
                $messageType = 'error';
            }
        }
    }
}

// Refresh current user data to show updated wallet balance
$currentUser = getUserByIdOrName($pdo, $currentUser['id']);
$_SESSION['user'] = $currentUser;

require_once __DIR__ . '/../assets/template/intro-template.php';
?>

<style>
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
</style>

<main>
    <div class="container">
        <h1 class="main-title">Transfer Funds</h1>

        <?php if ($actionMessage): ?>
            <div class="message-box <?php echo htmlspecialchars($messageType); ?>">
                <?php echo htmlspecialchars($actionMessage); ?>
            </div>
        <?php endif; ?>

        <div class="form-section">
            <div class="wallet-balance-display">
                Your Wallet Balance: <strong>₦<?php echo number_format($currentUser['wallet_balance'], 2); ?></strong>
            </div>

            <form method="post">
                <input type="hidden" name="action" value="user_transfer_wallet">
                <div class="form-group">
                    <label for="receiver_username">Admin Username:</label>
                    <input type="text" name="receiver_username" id="receiver_username" class="form-input" required>
                </div>
                <div class="form-group">
                    <label for="transfer_amount">Amount (₦):</label>
                    <input type="number" step="0.01" name="transfer_amount" id="transfer_amount" class="form-input" required>
                </div>
                <button type="submit" class="btn btn-primary btn-full-width">Transfer Funds</button>
            </form>
        </div>
    </div>
</main>

<?php
require_once __DIR__ . '/../assets/template/end-template.php';
?>