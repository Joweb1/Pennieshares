<?php
require_once __DIR__ . '/../src/functions.php';
check_auth();

$actionMessage = '';
$currentUser = $_SESSION['user'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'user_transfer_wallet') {
    $senderId = $currentUser['id'];
    $receiverUsername = trim($_POST['receiver_username'] ?? '');
    $amount = filter_input(INPUT_POST, 'transfer_amount', FILTER_VALIDATE_FLOAT);

    if (empty($receiverUsername) || $amount === false || $amount <= 0) {
        $actionMessage = "Error: Invalid recipient username or amount.";
    } else {
        // Get receiver user details
        $receiverUser = getUserByIdOrName($pdo, $receiverUsername);

        if (!$receiverUser) {
            $actionMessage = "Error: Recipient user '{$receiverUsername}' not found.";
        } elseif ($receiverUser['is_admin'] != 1) {
            $actionMessage = "Error: You can only transfer funds to an admin user.";
        } else {
            $transferResult = transferWalletBalance($pdo, $senderId, $receiverUser['id'], $amount);
            if ($transferResult['success']) {
                $actionMessage = "Successfully transferred ₦{$amount} to admin user {$receiverUsername}.";
                // Update session balance after successful transfer
                $_SESSION['user']['wallet_balance'] = getUserByIdOrName($pdo, $senderId)['wallet_balance'];
            } else {
                $actionMessage = "Error: " . $transferResult['message'];
            }
        }
    }
}

// Refresh current user data to show updated wallet balance
$currentUser = getUserByIdOrName($pdo, $currentUser['id']);
$_SESSION['user'] = $currentUser;

?>
<!DOCTYPE html>
<html>
<head>
    <title>Transfer Funds</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 20px; line-height: 1.6; background-color: #f4f7f6; color: #333; }
        .container { max-width: 800px; margin: 0 auto; background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 15px rgba(0,0,0,0.1); }
        h1, h2 { color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px; margin-top: 30px; }
        .message { padding: 15px; margin-bottom:20px; border-radius: 5px; border-left: 5px solid; }
        .message-success { background-color: #e8f8f5; color: #1abc9c; border-left-color: #1abc9c;}
        .message-error { background-color: #fdedec; color: #e74c3c; border-left-color: #e74c3c;}
        .form-section { background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin-bottom: 30px; box-shadow: 0 2px 3px rgba(0,0,0,0.05); }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        input[type="text"], input[type="number"] { width: calc(100% - 22px); padding: 10px; margin-bottom:15px; border: 1px solid #ccc; border-radius:4px; box-sizing: border-box; }
        button { padding: 10px 18px; background-color: #3498db; color: white; border:none; border-radius:4px; cursor:pointer; font-size: 1em; transition: background-color 0.3s ease; }
        button:hover { background-color: #2980b9;}
    </style>
</head>
<body>
<div class="container">
    <h1>Transfer Funds</h1>

    <?php if ($actionMessage): ?>
        <div class="message <?php echo strpos(strtolower($actionMessage), 'error') !== false ? 'message-error' : 'message-success'; ?>">
            <?php echo htmlspecialchars($actionMessage); ?>
        </div>
    <?php endif; ?>

    <h2>Your Wallet Balance: ₦<?php echo number_format($currentUser['wallet_balance'], 2); ?></h2>

    <div class="form-section">
        <h2>Transfer to Admin</h2>
        <form method="post">
            <input type="hidden" name="action" value="user_transfer_wallet">
            <div><label for="receiver_username">Admin Username:</label><input type="text" name="receiver_username" id="receiver_username" required></div>
            <div><label for="transfer_amount">Amount (₦):</label><input type="number" step="0.01" name="transfer_amount" id="transfer_amount" required></div>
            <button type="submit">Transfer Funds</button>
        </form>
    </div>

    <p><a href="dashboard.php">Back to Dashboard</a></p>
</div>
</body>
</html>