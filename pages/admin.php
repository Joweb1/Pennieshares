<?php
require_once __DIR__ . '/../src/functions.php';
check_auth();

// Admin Access Check
if (!isset($_SESSION['user']) || empty($_SESSION['user']['is_admin'])) {
    header("HTTP/1.1 403 Forbidden");
    exit("Access Denied: You do not have administrative privileges.");
}

$actionMessage = '';
$purchaseDetails = null;
$db = $pdo; 

$dashboardUser = null;
$userAssets = [];
$userPayoutsList = [];

// Handle Buy Asset form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'buy_asset') {
    $userName = trim($_POST['name'] ?? '');
    $assetTypeId = filter_input(INPUT_POST, 'asset_type_id', FILTER_VALIDATE_INT);
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT, ["options" => ["min_range"=>1, "max_range"=>10]]);

    if (empty($userName)) {
        $actionMessage = "Error: User name cannot be empty.";
    } elseif ($assetTypeId === false || $assetTypeId === null) {
        $actionMessage = "Error: Please select a valid asset type.";
    } elseif ($quantity === false || $quantity === null) {
        $actionMessage = "Error: Invalid quantity.";
    } else {
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$userName]);
        $userId = $stmt->fetchColumn();
        if (!$userId) {
            $actionMessage .= "User '{$userName}' not found. Asset purchase failed.";
        } else {
            $actionMessage .= "User '{$userName}' found. ";
            $purchaseDetails = buyAsset($userId, $assetTypeId, $quantity);
        }
    }
}

// Handle Register New User form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register_user') {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $referral = trim($_POST['referral']);

    if (registerUser($fullname, $email, $username, $phone, $referral, $password)) {
        $actionMessage = "User '{$username}' registered successfully.";
    } else {
        $actionMessage = "Error: Failed to register user.";
    }
}

// Handle Credit User Wallet form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'credit_wallet') {
    $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);

    if ($userId && $amount) {
        $creditSuccess = creditUserWallet($userId, $amount);
        if ($creditSuccess) {
            $actionMessage = "Successfully credited user {$userId} with ₦{$amount}.";
        } else {
            $actionMessage = "Error: Failed to credit wallet. Database operation failed.";
        }
    } else {
        $actionMessage = "Error: Invalid user ID or amount.";
    }
}

// Handle Admin Transfer Wallet form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'admin_transfer_wallet') {
    $senderId = filter_input(INPUT_POST, 'sender_user_id', FILTER_VALIDATE_INT);
    $receiverId = filter_input(INPUT_POST, 'receiver_user_id', FILTER_VALIDATE_INT);
    $amount = filter_input(INPUT_POST, 'transfer_amount', FILTER_VALIDATE_FLOAT);

    if ($senderId && $receiverId && $amount) {
        $transferResult = transferWalletBalance($pdo, $senderId, $receiverId, $amount);
        if ($transferResult['success']) {
            $actionMessage = "Successfully transferred ₦{$amount} from user {$senderId} to user {$receiverId}.";
        } else {
            $actionMessage = "Error: " . $transferResult['message'];
        }
    } else {
        $actionMessage = "Error: Invalid sender ID, receiver ID, or amount for transfer.";
    }
}

// Handle Assign Admin Role form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'assign_admin_role') {
    $userId = filter_input(INPUT_POST, 'user_id_admin', FILTER_VALIDATE_INT);

    if ($userId) {
        if (assignAdminRole($pdo, $userId)) {
            $actionMessage = "User #{$userId} assigned admin role successfully.";
        } else {
            $actionMessage = "Error: Failed to assign admin role to user #{$userId}. It might not exist or already be an admin.";
        }
    } else {
        $actionMessage = "Error: Invalid User ID.";
    }
}

// Handle Mark Asset as Expired form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_asset_expired') {
    $assetId = filter_input(INPUT_POST, 'asset_id_to_expire', FILTER_VALIDATE_INT);

    if ($assetId) {
        if (markAssetExpired($pdo, $assetId)) {
            $actionMessage = "Asset #{$assetId} marked as expired successfully.";
        } else {
            $actionMessage = "Error: Failed to mark asset #{$assetId} as expired. It might not exist or already be expired.";
        }
    } else {
        $actionMessage = "Error: Invalid Asset ID.";
    }
}

// Handle Mark Asset as Completed form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_asset_completed') {
    $assetId = filter_input(INPUT_POST, 'asset_id_to_complete', FILTER_VALIDATE_INT);

    if ($assetId) {
        if (markAssetCompleted($pdo, $assetId)) {
            $actionMessage = "Asset #{$assetId} marked as completed successfully.";
        } else {
            $actionMessage = "Error: Failed to mark asset #{$assetId} as completed. It might not exist or already be completed.";
        }
    } else {
        $actionMessage = "Error: Invalid Asset ID.";
    }
}

// Handle Add New Asset Type form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_asset_type') {
    $name = trim($_POST['new_asset_name']);
    $price = filter_input(INPUT_POST, 'new_asset_price', FILTER_VALIDATE_FLOAT);
    $payoutCap = filter_input(INPUT_POST, 'new_asset_payout_cap', FILTER_VALIDATE_FLOAT);
    $durationMonths = filter_input(INPUT_POST, 'new_asset_duration_months', FILTER_VALIDATE_INT);
    $imageLink = null;

    // Handle image upload
    if (isset($_FILES['new_asset_image']) && $_FILES['new_asset_image']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['new_asset_image']['tmp_name'];
        $fileName = $_FILES['new_asset_image']['name'];
        $fileSize = $_FILES['new_asset_image']['size'];
        $fileType = $_FILES['new_asset_image']['type'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
        $uploadFileDir = __DIR__ . '/../assets/images/';
        $dest_path = $uploadFileDir . $newFileName;

        $allowedfileExtensions = array('jpg', 'gif', 'png', 'jpeg');

        if (in_array($fileExtension, $allowedfileExtensions)) {
            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $imageLink = '../assets/images/' . $newFileName;
            } else {
                $actionMessage = "Error: There was an error moving the uploaded file.";
            }
        } else {
            $actionMessage = "Error: Upload failed. Allowed file types: " . implode(',', $allowedfileExtensions);
        }
    }

    if (empty($name) || $price === false || $payoutCap === false || $durationMonths === false) {
        $actionMessage = "Error: Invalid input for new asset type.";
    } else if ($actionMessage === '') { // Only proceed if no file upload error occurred
        if (addAssetType($pdo, $name, $price, $payoutCap, $durationMonths, $imageLink)) {
            $actionMessage = "Asset type '{$name}' added successfully.";
        } else {
            $actionMessage = "Error: Failed to add asset type.";
        }
    }
}

// Handle Delete Asset Type form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_asset_type') {
    $assetTypeId = filter_input(INPUT_POST, 'asset_type_id_delete', FILTER_VALIDATE_INT);

    if ($assetTypeId) {
        if (deleteAssetType($pdo, $assetTypeId)) {
            $actionMessage = "Asset Type #{$assetTypeId} deleted successfully.";
        } else {
            $actionMessage = "Error: Failed to delete asset type #{$assetTypeId}. It might not exist or has associated assets.";
        }
    } else {
        $actionMessage = "Error: Invalid Asset Type ID.";
    }
}

// Handle Delete User Account form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_user_account') {
    $userId = filter_input(INPUT_POST, 'user_id_delete', FILTER_VALIDATE_INT);

    if ($userId) {
        if (deleteUserAccount($pdo, $userId)) {
            $actionMessage = "User #{$userId} deleted successfully.";
        } else {
            $actionMessage = "Error: Failed to delete user #{$userId}. It might not exist.";
        }
    } else {
        $actionMessage = "Error: Invalid User ID.";
    }
}


// Fetch data for display
$users = $db->query("SELECT * FROM users ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
$assetTypes = getAssetTypes($db);
$companyFunds = getCompanyFunds($db);

$assetsQuery = "SELECT a.*, u.username as username, at.name as asset_type_name, at.payout_cap as type_payout_cap,
                (a.total_generational_received + a.total_shared_received) as total_earned
                FROM assets a 
                JOIN users u ON a.user_id = u.id 
                JOIN asset_types at ON a.asset_type_id = at.id
                ORDER BY a.id ASC";
$assets = $db->query($assetsQuery)->fetchAll(PDO::FETCH_ASSOC);

$payoutsQuery = "SELECT p.*, 
                ra.id as receiving_asset_display_id, ru.username as receiving_username,
                ta.id as triggering_asset_display_id, tu.username as triggering_username
                FROM payouts p 
                LEFT JOIN assets ra ON p.receiving_asset_id = ra.id
                LEFT JOIN users ru ON ra.user_id = ru.id
                JOIN assets ta ON p.triggering_asset_id = ta.id
                JOIN users tu ON ta.user_id = tu.id
                ORDER BY p.id ASC";
$payouts = $db->query($payoutsQuery)->fetchAll(PDO::FETCH_ASSOC);
$now_for_display = date('Y-m-d H:i:s');

// Data for charts
$overallIncomeStats = getOverallIncomeStats($db);
$assetStatusDistribution = getAssetStatusDistribution($db);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Panel - Asset System</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 20px; line-height: 1.6; background-color: #f4f7f6; color: #333; }
        .container { max-width: 1300px; margin: 0 auto; background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 15px rgba(0,0,0,0.1); }
        h1, h2, h3, h4 { color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px; margin-top: 30px; }
        h1 { text-align: center; margin-bottom: 30px; font-size: 2.2em;}
        table { border-collapse: collapse; width: 100%; font-size: 0.85em; margin-bottom: 20px; box-shadow: 0 2px 3px rgba(0,0,0,0.1); }
        th, td { border: 1px solid #ddd; padding: 8px 10px; text-align: left; vertical-align: middle; }
        th { background-color: #3498db; color: white; font-weight: bold; }
        tr:nth-child(even) { background-color: #ecf0f1; }
        .status-completed { background-color: #d4efdf !important; color: #1e8449; }
        .status-expired { background-color: #fdedec !important; color: #c0392b; }
        .status-active { color: #27ae60; }
        .message { padding: 15px; margin-bottom:20px; border-radius: 5px; border-left: 5px solid; }
        .message-success { background-color: #e8f8f5; color: #1abc9c; border-left-color: #1abc9c;}
        .message-error { background-color: #fdedec; color: #e74c3c; border-left-color: #e74c3c;}
        .details-box { background-color: #e9ecef; border: 1px solid #ced4da; padding: 10px; margin-top:10px; font-size:0.85em; border-radius: 5px; max-height: 200px; overflow-y:auto; }
        .form-section { background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin-bottom: 30px; box-shadow: 0 2px 3px rgba(0,0,0,0.05); }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        input[type="text"], input[type="number"], select { width: calc(100% - 22px); padding: 10px; margin-bottom:15px; border: 1px solid #ccc; border-radius:4px; box-sizing: border-box; }
        button { padding: 10px 18px; background-color: #3498db; color: white; border:none; border-radius:4px; cursor:pointer; font-size: 1em; transition: background-color 0.3s ease; }
        button:hover { background-color: #2980b9;}
        .info-bar { display: flex; flex-wrap: wrap; justify-content: space-around; background-color: #2c3e50; color: white; padding: 15px; border-radius: 5px; margin-bottom: 30px; }
        .info-bar div { text-align: center; margin: 5px 10px; }
        .info-bar h4 { margin-top: 0; margin-bottom: 5px; font-size: 0.8em; opacity: 0.8; text-transform: uppercase; }
        .info-bar p { margin: 0; font-size: 1.3em; font-weight: bold; }
        .chart-container { width: 100%; max-width: 450px; margin: 10px auto; }
        .flex-container { display: flex; flex-wrap: wrap; justify-content: space-around; }
    </style>
</head>
<body>
<div class="container">
    <h1>Admin Panel: Asset Management</h1>

    <?php if ($actionMessage || ($purchaseDetails && isset($purchaseDetails['summary']))): ?>
        <div class="message <?php echo strpos(strtolower($actionMessage), 'error') !== false || (isset($purchaseDetails['purchases'][0]['error'])) ? 'message-error' : 'message-success'; ?>">
            <?php echo htmlspecialchars($actionMessage); ?>
            <?php if ($purchaseDetails && isset($purchaseDetails['summary'])):
                foreach($purchaseDetails['summary'] as $summaryLine) {
                    echo "<p>" . htmlspecialchars($summaryLine) . "</p>";
                }
            endif; ?>
        </div>
    <?php endif; ?>
    
    <div class="info-bar">
        <div><h4>Company Profit</h4><p>₦<?php echo number_format($companyFunds['total_company_profit'], 2); ?></p></div>
        <div><h4>Reservation Fund</h4><p>₦<?php echo number_format($companyFunds['total_reservation_fund'], 2); ?></p></div>
        <div><h4>Total Live Assets</h4><p><?php echo htmlspecialchars($assetStatusDistribution['active_count'] ?? 0); ?></p></div>
         <div><h4>Total Users</h4><p><?php echo count($users); ?></p></div>
        <div><h4>System Time</h4><p><?php echo $now_for_display; ?></p></div>
    </div>

    <div class="charts-section">
        <h3>System Statistics</h3>
        <div class="flex-container">
            <div class="chart-container"><canvas id="incomeDistributionChart"></canvas></div>
            <div class="chart-container"><canvas id="assetStatusChart"></canvas></div>
        </div>
    </div>

    <div class="form-section">
        <h2>Manually Buy Asset for User</h2>
        <form method="post">
            <input type="hidden" name="action" value="buy_asset">
            <div><label for="name">Username:</label><input type="text" name="name" id="name" required placeholder="Enter username"></div>
            <div>
                <label for="asset_type_id">Select Asset Type:</label>
                <select name="asset_type_id" id="asset_type_id" required>
                    <option value="">-- Select Type --</option>
                    <?php foreach ($assetTypes as $type): ?>
                        <option value="<?php echo $type['id']; ?>">
                            <?php echo htmlspecialchars(getAssetBranding($type['id'])['name']); ?> (Price: ₦<?php echo number_format($type['price'],2); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div><label for="quantity">Quantity:</label><input type="number" name="quantity" id="quantity" value="1" min="1" max="10"></div>
            <button type="submit">Buy Asset(s)</button>
        </form>
    </div>

    <div class="form-section">
        <h2>Register New User</h2>
        <form method="post">
            <input type="hidden" name="action" value="register_user">
            <div><label for="fullname">Full Name:</label><input type="text" name="fullname" required></div>
            <div><label for="email">Email:</label><input type="email" name="email" required></div>
            <div><label for="username">Username:</label><input type="text" name="username" required></div>
            <div><label for="phone">Phone:</label><input type="text" name="phone" required></div>
            <div><label for="password">Password:</label><input type="password" name="password" required></div>
            <div><label for="referral">Referral Code:</label><input type="text" name="referral"></div>
            <button type="submit">Register User</button>
        </form>
    </div>

    <div class="form-section">
        <h2>Credit User Wallet</h2>
        <form method="post">
            <input type="hidden" name="action" value="credit_wallet">
            <div><label for="user_id">User ID:</label><input type="number" name="user_id" required></div>
            <div><label for="amount">Amount (₦):</label><input type="number" step="0.01" name="amount" required></div>
            <button type="submit">Credit Wallet</button>
        </form>
    </div>

    <div class="form-section">
        <h2>Transfer Wallet Balance (Admin)</h2>
        <form method="post">
            <input type="hidden" name="action" value="admin_transfer_wallet">
            <div><label for="sender_user_id">Sender User ID:</label><input type="number" name="sender_user_id" id="sender_user_id" required></div>
            <div><label for="receiver_user_id">Receiver User ID:</label><input type="number" name="receiver_user_id" id="receiver_user_id" required></div>
            <div><label for="transfer_amount">Amount (₦):</label><input type="number" step="0.01" name="transfer_amount" id="transfer_amount" required></div>
            <button type="submit">Transfer Funds</button>
        </form>
    </div>

    <div class="form-section">
        <h2>Assign Admin Role</h2>
        <form method="post">
            <input type="hidden" name="action" value="assign_admin_role">
            <div><label for="user_id_admin">User ID:</label><input type="number" name="user_id_admin" id="user_id_admin" required></div>
            <button type="submit">Make Admin</button>
        </form>
    </div>

    <div class="form-section">
        <h2>Mark Asset as Expired</h2>
        <form method="post">
            <input type="hidden" name="action" value="mark_asset_expired">
            <div><label for="asset_id_to_expire">Asset ID:</label><input type="number" name="asset_id_to_expire" id="asset_id_to_expire" required></div>
            <button type="submit">Mark Expired</button>
        </form>
    </div>

    <div class="form-section">
        <h2>Mark Asset as Completed</h2>
        <form method="post">
            <input type="hidden" name="action" value="mark_asset_completed">
            <div><label for="asset_id_to_complete">Asset ID:</label><input type="number" name="asset_id_to_complete" id="asset_id_to_complete" required></div>
            <button type="submit">Mark Completed</button>
        </form>
    </div>

    <div class="form-section">
        <h2>Add New Asset Type</h2>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add_asset_type">
            <div><label for="new_asset_name">Asset Name (Company Name):</label><input type="text" name="new_asset_name" id="new_asset_name" required></div>
            <div><label for="new_asset_price">Price (₦):</label><input type="number" step="0.01" name="new_asset_price" id="new_asset_price" required></div>
            <div><label for="new_asset_payout_cap">Payout Cap (₦):</label><input type="number" step="0.01" name="new_asset_payout_cap" id="new_asset_payout_cap" required></div>
            <div><label for="new_asset_duration_months">Duration (Months, 0 for unlimited):</label><input type="number" name="new_asset_duration_months" id="new_asset_duration_months" value="0" min="0" required></div>
            <div><label for="new_asset_image">Asset Image (Optional):</label><input type="file" name="new_asset_image" id="new_asset_image" accept="image/*"></div>
            <button type="submit">Add Asset Type</button>
        </form>
    </div>

    <div class="form-section">
        <h2>Delete Asset Type</h2>
        <form method="post">
            <input type="hidden" name="action" value="delete_asset_type">
            <div><label for="asset_type_id_delete">Asset Type ID:</label><input type="number" name="asset_type_id_delete" id="asset_type_id_delete" required></div>
            <button type="submit">Delete Asset Type</button>
        </form>
    </div>

    <div class="form-section">
        <h2>Delete User Account</h2>
        <form method="post">
            <input type="hidden" name="action" value="delete_user_account">
            <div><label for="user_id_delete">User ID:</label><input type="number" name="user_id_delete" id="user_id_delete" required></div>
            <button type="submit">Delete User</button>
        </form>
    </div>

    

    

    <h2>All Assets</h2>
    <table>
        <thead><tr><th>ID</th><th>Owner</th><th>Type</th><th>Image</th><th>Parent</th><th>Gen.</th><th>Children</th><th>Progress to Cap</th><th>Total Earned</th><th>Status</th><th>Created</th><th>Expires</th></tr></thead>
        <tbody>
        <?php foreach ($assets as $a):
            $total_earned_for_cap = $a['total_generational_received'] + $a['total_shared_received'];
            $percentage = ($a['type_payout_cap'] > 0) ? ($total_earned_for_cap / $a['type_payout_cap']) * 100 : 0;
            $percentage = min(100, $percentage);
            $bar_class = 'progress-bar';
            if ($percentage > 75) $bar_class .= ' orange';
            if ($percentage >= 100 || $a['is_completed']) $percentage = 100;
            if ($a['is_manually_expired']) $bar_class = 'progress-bar red';
            $assetBranding = getAssetBranding($a['asset_type_id']);
        ?>
            <tr class="<?php echo $a['is_completed'] ? 'status-completed' : ($a['is_manually_expired'] ? 'status-expired' : ''); ?>">
                <td><?php echo $a['id']; ?></td>
                <td><?php echo htmlspecialchars($a['username']); ?></td>
                <td><?php echo htmlspecialchars($assetBranding['name']); ?></td>
                <td>
                    <?php if (!empty($assetBranding['image'])): ?>
                        <img src="<?php echo htmlspecialchars($assetBranding['image']); ?>" alt="<?php echo htmlspecialchars($assetBranding['name']); ?>" style="width: 50px; height: 50px; object-fit: cover;">
                    <?php else: ?>
                        N/A
                    <?php endif; ?>
                </td>
                <td><?php echo $a['parent_id'] ?: '-'; ?></td>
                <td><?php echo $a['generation']; ?></td>
                <td><?php echo $a['children_count']; ?>/3</td>
                <td>
                    ₦<?php echo number_format($total_earned_for_cap, 2); ?> / ₦<?php echo number_format($a['type_payout_cap'], 2); ?>
                    <div class="progress-bar-container" title="<?php echo number_format($percentage, 1); ?>%">
                        <div class="<?php echo $bar_class; ?>" style="width: <?php echo $percentage; ?>%;"><?php echo number_format($percentage, 0); ?>%</div>
                    </div>
                </td>
                <td>₦<?php echo number_format($a['total_earned'], 2); ?></td>
                <td><?php echo $a['is_completed'] ? 'Completed' : ($a['is_manually_expired'] ? 'Expired' : 'Active'); ?></td>
                <td><?php echo $a['created_at']; ?></td>
                <td><?php echo $a['expires_at'] ?: 'Unlimited'; ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <h2>All Users</h2>
    <table><thead><tr><th>User ID</th><th>Username</th><th>Full Name</th><th>Email</th><th>Wallet Balance</th><th>Is Admin?</th></tr></thead><tbody>
        <?php foreach ($users as $user): ?>
            <tr>
                <td><?php echo $user['id']; ?></td>
                <td><?php echo htmlspecialchars($user['username']); ?></td>
                <td><?php echo htmlspecialchars($user['fullname']); ?></td>
                <td><?php echo htmlspecialchars($user['email']); ?></td>
                <td>₦<?php echo number_format($user['wallet_balance'], 2); ?></td>
                <td><?php echo $user['is_admin'] ? 'Yes' : 'No'; ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody></table>

    <h2>All Payouts</h2>
     <table><thead><tr><th>ID</th><th>Destination</th><th>Triggering Asset (#User)</th><th>Amount</th><th>Type</th><th>Timestamp</th></tr></thead><tbody>
        <?php foreach ($payouts as $p): ?>
            <tr>
                <td><?php echo $p['id']; ?></td>
                <td><?php echo ($p['receiving_asset_id'] ? "Asset #{$p['receiving_asset_display_id']} (" . htmlspecialchars($p['receiving_username'] ?? 'N/A') . ")" : "Company " . ucfirst($p['company_fund_type'])); ?></td>
                <td>#<?php echo $p['triggering_asset_display_id']; ?> (<?php echo htmlspecialchars($p['triggering_username']); ?>)</td>
                <td>₦<?php echo number_format($p['amount'], 2); ?></td>
                <td><?php echo htmlspecialchars(str_replace('_', ' ', ucfirst($p['payout_type']))); ?></td>
                <td><?php echo $p['created_at']; ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody></table>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const incomeCtx = document.getElementById('incomeDistributionChart');
    if (incomeCtx) {
        new Chart(incomeCtx, {
            type: 'bar',
            data: {
                labels: ['Company Profit', 'Reservation Fund', 'Total Generational Paid', 'Total Shared Paid'],
                datasets: [{
                    label: 'Amount (₦)',
                    data: [<?php echo $overallIncomeStats['company_profit']; ?>, <?php echo $overallIncomeStats['reservation_fund']; ?>, <?php echo $overallIncomeStats['total_generational_paid']; ?>, <?php echo $overallIncomeStats['total_shared_paid']; ?>],
                    backgroundColor: ['#2ecc71', '#f1c40f', '#3498db', '#9b59b6']
                }]
            }
        });
    }

    const statusCtx = document.getElementById('assetStatusChart');
    if (statusCtx) {
        new Chart(statusCtx, {
            type: 'pie',
            data: {
                labels: ['Active', 'Completed', 'Expired'],
                datasets: [{
                    label: 'Asset Statuses',
                    data: [<?php echo $assetStatusDistribution['active_count'] ?? 0; ?>, <?php echo $assetStatusDistribution['completed_count'] ?? 0; ?>, <?php echo $assetStatusDistribution['expired_count'] ?? 0; ?>],
                    backgroundColor: ['#27ae60', '#3498db', '#e74c3c']
                }]
            }
        });
    }
});
</script>
</body>
</html>
