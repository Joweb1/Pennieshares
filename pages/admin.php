<?php
require_once __DIR__ . '/../src/functions.php';
require_once __DIR__ . '/../src/assets_functions.php';
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
$userPayoutsList = [];

// Pagination and Search settings for Users Table
$users_per_page = 20;
$current_user_page = isset($_GET['user_page']) && is_numeric($_GET['user_page']) ? (int)$_GET['user_page'] : 1;
$user_offset = ($current_user_page - 1) * $users_per_page;
$user_search_query = trim($_GET['user_search'] ?? '');

// Pagination and Search settings for Assets Table
$assets_per_page = 20;
$current_asset_page = isset($_GET['asset_page']) && is_numeric($_GET['asset_page']) ? (int)$_GET['asset_page'] : 1;
$asset_offset = ($current_asset_page - 1) * $assets_per_page;
$asset_search_query = trim($_GET['asset_search'] ?? '');

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
        $stmt = $db->prepare("SELECT id, wallet_balance FROM users WHERE username = ?");
        $stmt->execute([$userName]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $actionMessage .= "Error: User '{$userName}' not found. Asset purchase failed.";
        } else {
            $userId = $user['id'];
            $userWalletBalance = $user['wallet_balance'];

            $assetTypeStmt = $db->prepare("SELECT price FROM asset_types WHERE id = ?");
            $assetTypeStmt->execute([$assetTypeId]);
            $assetTypePrice = $assetTypeStmt->fetchColumn();

            if (!$assetTypePrice) {
                $actionMessage .= "Error: Asset type not found. Asset purchase failed.";
            } else {
                $totalCost = $assetTypePrice * $quantity;

                if ($userWalletBalance < $totalCost) {
                    $actionMessage .= "Error: User '{$userName}' has insufficient funds (₦" . number_format($userWalletBalance, 2) . ") to purchase assets costing ₦" . number_format($totalCost, 2) . ".";
                } else {
                    // Debit user wallet first
                    $debitSuccess = debitUserWallet($pdo, $userId, $totalCost, "Admin Asset Purchase: {$quantity} x Asset Type {$assetTypeId}");

                    if ($debitSuccess) {
                        $purchaseDetails = buyAsset($pdo, $userId, $assetTypeId, $quantity);
                        $actionMessage .= "User '{$userName}' found. Assets purchased successfully. Wallet debited by ₦" . number_format($totalCost, 2) . ".";
                    } else {
                        $actionMessage .= "Error: Failed to debit user '{$userName}' wallet. Asset purchase failed.";
                    }
                }
            }
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
    $username = trim($_POST['username']);
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
    $user = getUserByIdOrName($pdo, $username);

    if ($user && $amount) {
        $creditSuccess = creditUserWallet($user['id'], $amount);
        if ($creditSuccess) {
            $actionMessage = "Successfully credited user {$username} with ₦{$amount}.";
        } else {
            $actionMessage = "Error: Failed to credit wallet. Database operation failed.";
        }
    } else {
        $actionMessage = "Error: Invalid username or amount.";
    }
}

// Handle Admin Transfer Wallet form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'admin_transfer_wallet') {
    $sender_username = trim($_POST['sender_username']);
    $receiver_username = trim($_POST['receiver_username']);
    $amount = filter_input(INPUT_POST, 'transfer_amount', FILTER_VALIDATE_FLOAT);
    $sender = getUserByIdOrName($pdo, $sender_username);
    $receiver = getUserByIdOrName($pdo, $receiver_username);

    if ($sender && $receiver && $amount) {
        $transferResult = transferWalletBalance($pdo, $sender['id'], $receiver['id'], $amount);
        if ($transferResult['success']) {
            $actionMessage = "Successfully transferred ₦{$amount} from user {$sender_username} to user {$receiver_username}.";
        } else {
            $actionMessage = "Error: " . $transferResult['message'];
        }
    } else {
        $actionMessage = "Error: Invalid sender username, receiver username, or amount for transfer.";
    }
}

// Handle Assign Admin Role form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'assign_admin_role') {
    $username = trim($_POST['username']);
    $user = getUserByIdOrName($pdo, $username);

    if ($user) {
        if (assignAdminRole($pdo, $user['id'])) {
            $actionMessage = "User '{$username}' assigned admin role successfully.";
        } else {
            $actionMessage = "Error: Failed to assign admin role to user '{$username}'. It might not exist or already be an admin.";
        }
    } else {
        $actionMessage = "Error: Invalid Username.";
    }
}

// Handle Verify User Account form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'verify_user_account') {
    $username = trim($_POST['username']);
    $user = getUserByIdOrName($pdo, $username);

    if ($user) {
        if (verifyUserAccount($pdo, $user['id'])) {
            $actionMessage = "User '{$username}' verified successfully.";
        } else {
            $actionMessage = "Error: Failed to verify user '{$username}'. It might not exist or already be verified.";
        }
    } else {
        $actionMessage = "Error: Invalid Username.";
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
    $category = trim($_POST['new_asset_category']);
    $imageLink = null;

    // Server-side validation
    if ($price < 18 || $price > 34) {
        $actionMessage = "Error: Price must be between ₦18 and ₦34.";
    } elseif (preg_match('/^[A-Z][a-zA-Z\s]*$/', $category) !== 1) {
        $actionMessage = "Error: Category must start with a capital letter and contain only letters and spaces.";
    } else {
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
            if (addAssetType($pdo, $name, $price, $payoutCap, $durationMonths, $imageLink, $category)) {
                $actionMessage = "Asset type '{$name}' added successfully.";
            } else {
                $actionMessage = "Error: Failed to add asset type.";
            }
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
    $username = trim($_POST['username']);
    $user = getUserByIdOrName($pdo, $username);

    if ($user) {
        if (deleteUserAccount($pdo, $user['id'])) {
            $actionMessage = "User '{$username}' deleted successfully.";
        } else {
            $actionMessage = "Error: Failed to delete user '{$username}'. It might not exist.";
        }
    } else {
        $actionMessage = "Error: Invalid Username.";
    }
}

// Handle Delete Expired or Completed Assets form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_expired_or_completed_assets') {
    $deletedCount = deleteExpiredOrCompletedAssets($pdo);
    if ($deletedCount !== false) {
        $actionMessage = "Successfully deleted {$deletedCount} expired or completed assets.";
    } else {
        $actionMessage = "Error: Failed to delete expired or completed assets.";
    }
}


// Fetch data for display
$users = getPaginatedUsers($db, $users_per_page, $user_offset, $user_search_query);
$total_users = getTotalUserCount($db, $user_search_query);
$total_user_pages = ceil($total_users / $users_per_page);

$assetTypes = getAssetTypes($db);
$companyFunds = getCompanyFunds($db);

$assets = getPaginatedAssets($db, $assets_per_page, $asset_offset, $asset_search_query);
$total_assets = getTotalAssetCount($db, $asset_search_query);
$total_asset_pages = ceil($total_assets / $assets_per_page);

$payoutsQuery = "SELECT p.*, 
                ra.id as receiving_asset_display_id, ru.username as receiving_username,
                ta.id as triggering_asset_display_id, tu.username as triggering_username
                FROM payouts p 
                LEFT JOIN assets ra ON p.receiving_asset_id = ra.id
                LEFT JOIN users ru ON ra.user_id = ru.id
                JOIN assets ta ON p.triggering_asset_id = ta.id
                JOIN users tu ON ta.user_id = tu.id
                ORDER BY p.id DESC LIMIT 10";
$payouts = $db->query($payoutsQuery)->fetchAll(PDO::FETCH_ASSOC);
$now_for_display = date('Y-m-d H:i:s');

// Data for charts
$overallIncomeStats = getOverallIncomeStats($db);
$assetStatusDistribution = getAssetStatusDistribution($db);

$pageTitle = "Admin Panel";
include __DIR__ . '/../assets/template/intro-template.php';
?>

<style>
    /* General Styling */
    .admin-container {
        width: 95vw; /* Increased max-width for wider desktop view */
        margin: 2rem auto;
        margin-left:-9rem;
        padding: 1rem; /* Reduced padding for wider content area */
        background-color: var(--bg-secondary);
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        animation: fadeIn 0.5s ease-out;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    h1 {
        color: var(--text-primary);
        text-align: center;
        margin-bottom: 1.5rem;
        font-size: 2.2rem;
        font-weight: 700;
        background: linear-gradient(45deg, var(--accent-color), #60efff);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .message {
        padding: 1rem;
        margin-bottom: 1.5rem;
        border-radius: 8px;
        font-weight: 500;
        text-align: center;
        animation: slideIn 0.4s ease-out;
    }

    @keyframes slideIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .message.success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .message.error {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .info-bar {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-around;
        background-color: var(--bg-tertiary);
        color: var(--text-primary);
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 30px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    .info-bar div {
        text-align: center;
        margin: 5px 10px;
    }
    .info-bar h4 {
        margin-top: 0;
        margin-bottom: 5px;
        font-size: 0.8em;
        opacity: 0.8;
        text-transform: uppercase;
        color: var(--text-secondary);
    }
    .info-bar p {
        margin: 0;
        font-size: 1.3em;
        font-weight: bold;
    }

    .charts-section {
        margin-bottom: 2rem;
        background-color: var(--bg-tertiary);
        padding: 1.5rem;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    .charts-section h3 {
        text-align: center;
        margin-bottom: 1.5rem;
        color: var(--text-primary);
        border-bottom: none;
        padding-bottom: 0;
    }
    .flex-container {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-around;
        gap: 1.5rem;
    }
    .chart-container {
        width: 100%;
        max-width: 450px;
        margin: 0 auto;
        background-color: var(--bg-secondary);
        padding: 1rem;
        border-radius: 8px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }

    .form-section {
        background-color: var(--bg-tertiary);
        padding: 20px;
        border-radius: 12px;
        margin-bottom: 30px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    .form-section h2 {
        text-align: center;
        margin-bottom: 1.5rem;
        color: var(--text-primary);
        border-bottom: none;
        padding-bottom: 0;
    }
    .form-section form div {
        margin-bottom: 1rem;
    }
    .form-section label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 600;
        color: var(--text-secondary);
    }
    .form-section input[type="text"],
    .form-section input[type="number"],
    .form-section input[type="email"],
    .form-section input[type="password"],
    .form-section select {
        width: 100%;
        padding: 10px;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        background-color: var(--bg-secondary);
        color: var(--text-primary);
        box-sizing: border-box;
        transition: border-color 0.2s, box-shadow 0.2s;
    }
    .form-section input[type="text"]:focus,
    .form-section input[type="number"]:focus,
    .form-section input[type="email"]:focus,
    .form-section input[type="password"]:focus,
    .form-section select:focus {
        outline: none;
        border-color: var(--accent-color);
        box-shadow: 0 0 0 3px rgba(12, 127, 242, 0.2);
    }
    .form-section button[type="submit"] {
        display: block;
        width: 100%;
        padding: 12px 20px;
        background-color: var(--accent-color);
        color: var(--accent-text);
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 1.1em;
        font-weight: 700;
        transition: background-color 0.3s ease, transform 0.2s ease;
    }
    .form-section button[type="submit"]:hover {
        background-color: #0a69c4;
        transform: translateY(-2px);
    }

    /* Table Styling */
    .table-responsive {
        overflow-x: auto;
        margin-bottom: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    table {
        width: 100%;
        border-collapse: collapse;
        border-radius: 8px;
        overflow: hidden; /* Ensures rounded corners apply to table */
    }
    th, td {
        border: 1px solid var(--border-color);
        padding: 12px 15px;
        text-align: left;
        vertical-align: middle;
        color: var(--text-secondary);
    }
    th {
        background-color: var(--bg-tertiary);
        color: var(--text-primary);
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.9em;
    }
    tr:nth-child(even) {
        background-color: var(--bg-tertiary);
    }
    tr:hover {
        background-color: var(--border-color);
    }
    .status-completed { background-color: #d4efdf !important; color: #1e8449; }
    .status-expired { background-color: #fdedec !important; color: #c0392b; }
    .status-active { color: #27ae60; }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .admin-container {
            padding: 1rem;
            margin-left:auto;
        }
        .info-bar {
            flex-direction: column;
            align-items: center;
        }
        .chart-container {
            max-width: 100%;
        }
        table, thead, tbody, th, td, tr {
            display: block;
        }
        thead tr {
            position: absolute;
            top: -9999px;
            left: -9999px;
        }
        tr {
            border: 1px solid var(--border-color);
            margin-bottom: 1rem;
            border-radius: 8px;
        }
        td {
            border: none;
            border-bottom: 1px solid var(--border-color);
            position: relative;
            padding-left: 50%;
            text-align: right;
        }
        td:before {
            position: absolute;
            top: 6px;
            left: 6px;
            width: 45%;
            padding-right: 10px;
            white-space: nowrap;
            content: attr(data-label);
            font-weight: 600;
            color: var(--text-primary);
            text-align: left;
        }
    }

    /* Search Bar Styles */
    .search-bar {
        margin-bottom: 1.5rem;
        display: flex;
        gap: 10px;
    }

    .search-bar input[type="text"] {
        flex-grow: 1;
        padding: 10px;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        background-color: var(--bg-secondary);
        color: var(--text-primary);
    }

    .search-bar button {
        padding: 10px 15px;
        background-color: var(--accent-color);
        color: var(--accent-text);
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    .search-bar button:hover {
        background-color: #0a69c4;
    }

    /* Pagination Styles */
    .pagination {
        display: flex;
        justify-content: center;
        margin-top: 1.5rem;
        gap: 5px;
    }

    .pagination .page-link {
        padding: 8px 12px;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        text-decoration: none;
        color: var(--text-primary);
        transition: background-color 0.3s ease;
    }

    .pagination .page-link:hover {
        background-color: var(--bg-tertiary);
    }

    .pagination .page-link.active {
        background-color: var(--accent-color);
        color: var(--accent-text);
        border-color: var(--accent-color);
    }
</style>

<div class="admin-container">
    <h1>Admin Dashboard</h1>

    <?php if ($actionMessage || ($purchaseDetails && isset($purchaseDetails['summary']))): ?>
        <div class="message <?php echo strpos(strtolower($actionMessage), 'error') !== false || (isset($purchaseDetails['purchases'][0]['error'])) ? 'error' : 'success'; ?>">
            <?php echo htmlspecialchars($actionMessage); ?>
            <?php if ($purchaseDetails && isset($purchaseDetails['summary'])):
                foreach($purchaseDetails['summary'] as $summaryLine) {
                    echo "<p>" . htmlspecialchars($summaryLine) . "</p>";
                }
            endif; ?>
            <?php if ($purchaseDetails && isset($purchaseDetails['purchases'])): ?>
                <h4>Individual Purchase Details:</h4>
                <ul>
                    <?php foreach($purchaseDetails['purchases'] as $purchase) : ?>
                        <li><?php echo htmlspecialchars($purchase['message']); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
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
            <div><label for="username_credit">Username:</label><input type="text" name="username" id="username_credit" required></div>
            <div><label for="amount">Amount (₦):</label><input type="number" step="0.01" name="amount" required></div>
            <button type="submit">Credit Wallet</button>
        </form>
    </div>

    <div class="form-section">
        <h2>Transfer Wallet Balance (Admin)</h2>
        <form method="post">
            <input type="hidden" name="action" value="admin_transfer_wallet">
            <div><label for="sender_username">Sender Username:</label><input type="text" name="sender_username" id="sender_username" required></div>
            <div><label for="receiver_username">Receiver Username:</label><input type="text" name="receiver_username" id="receiver_username" required></div>
            <div><label for="transfer_amount">Amount (₦):</label><input type="number" step="0.01" name="transfer_amount" id="transfer_amount" required></div>
            <button type="submit">Transfer Funds</button>
        </form>
    </div>

    <div class="form-section">
        <h2>Assign Admin Role</h2>
        <form method="post">
            <input type="hidden" name="action" value="assign_admin_role">
            <div><label for="username_admin">Username:</label><input type="text" name="username" id="username_admin" required></div>
            <button type="submit">Make Admin</button>
        </form>
    </div>

    <div class="form-section">
        <h2>Verify User Account</h2>
        <form method="post">
            <input type="hidden" name="action" value="verify_user_account">
            <div><label for="username_verify">Username:</label><input type="text" name="username" id="username_verify" required></div>
            <button type="submit">Verify User</button>
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
            <div><label for="new_asset_price">Price (₦):</label><input type="number" step="any" min="18" max="34" name="new_asset_price" id="new_asset_price" required></div>
            <div><label for="new_asset_payout_cap">Payout Cap (₦):</label><input type="number" step="0.01" name="new_asset_payout_cap" id="new_asset_payout_cap" required></div>
            <div><label for="new_asset_duration_months">Duration (Months, 0 for unlimited):</label><input type="number" name="new_asset_duration_months" id="new_asset_duration_months" value="0" min="0" required></div>
            <div><label for="new_asset_image">Asset Image (Optional):</label><input type="file" name="new_asset_image" id="new_asset_image" accept="image/*"></div>
            <div><label for="new_asset_category">Category:</label><input type="text" name="new_asset_category" id="new_asset_category" value="General" pattern="[A-Z].*" title="Category must start with a capital letter." required></div>
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
            <div><label for="username_delete">Username:</label><input type="text" name="username" id="username_delete" required></div>
            <button type="submit">Delete User</button>
        </form>
    </div>

    <div class="form-section">
        <h2>Delete Expired or Completed Assets</h2>
        <form method="post">
            <input type="hidden" name="action" value="delete_expired_or_completed_assets">
            <p>This action will permanently delete all assets that are either marked as 'completed' or whose expiration date has passed. This cannot be undone.</p>
            <button type="submit" onclick="return confirm('Are you sure you want to delete all expired and completed assets?');">Delete Assets</button>
        </form>
    </div>

    <h2>All Assets</h2>
    <div class="search-bar">
        <form method="GET" action="admin">
            <input type="hidden" name="user_page" value="<?php echo $current_user_page; ?>">
            <input type="hidden" name="user_search" value="<?php echo htmlspecialchars($user_search_query); ?>">
            <input type="text" name="asset_search" placeholder="Search by owner username..." value="<?php echo htmlspecialchars($asset_search_query); ?>">
            <button type="submit">Search</button>
        </form>
    </div>
    <div class="table-responsive">
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
                    <td data-label="ID"><?php echo $a['id']; ?></td>
                    <td data-label="Owner"><?php echo htmlspecialchars($a['username']); ?></td>
                    <td data-label="Type"><?php echo htmlspecialchars($assetBranding['name']); ?></td>
                    <td data-label="Image">
                        <?php if (!empty($assetBranding['image'])): ?>
                            <img src="<?php echo htmlspecialchars($assetBranding['image']); ?>" alt="<?php echo htmlspecialchars($assetBranding['name']); ?>" style="width: 50px; height: 50px; object-fit: cover;">
                        <?php else: ?>
                            N/A
                        <?php endif; ?>
                    </td>
                    <td data-label="Parent"><?php echo $a['parent_id'] ?: '-'; ?></td>
                    <td data-label="Gen."><?php echo $a['generation']; ?></td>
                    <td data-label="Children"><?php echo $a['children_count']; ?>/3</td>
                    <td data-label="Progress to Cap">
                        ₦<?php echo number_format($total_earned_for_cap, 2); ?> / ₦<?php echo number_format($a['type_payout_cap'], 2); ?>
                        <div class="progress-bar-container" title="<?php echo number_format($percentage, 1); ?>%">
                            <div class="<?php echo $bar_class; ?>" style="width: <?php echo $percentage; ?>%;"><?php echo number_format($percentage, 0); ?>%</div>
                        </div>
                    </td>
                    <td data-label="Total Earned">₦<?php echo number_format($a['total_earned'], 2); ?></td>
                    <td data-label="Status"><?php echo $a['is_completed'] ? 'Completed' : ($a['is_manually_expired'] ? 'Expired' : 'Active'); ?></td>
                    <td data-label="Created"><?php echo $a['created_at']; ?></td>
                    <td data-label="Expires"><?php echo $a['expires_at'] ?: 'Unlimited'; ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="pagination">
        <?php if ($total_asset_pages > 1): ?>
            <?php for ($i = 1; $i <= $total_asset_pages; $i++): ?>
                <a href="?asset_page=<?php echo $i; ?>&asset_search=<?php echo htmlspecialchars($asset_search_query); ?>&user_page=<?php echo $current_user_page; ?>&user_search=<?php echo htmlspecialchars($user_search_query); ?>" class="page-link <?php echo ($i == $current_asset_page) ? 'active' : ''; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
        <?php endif; ?>
    </div>

    <h2>All Users</h2>
    <div class="search-bar">
        <form method="GET" action="admin">
            <input type="hidden" name="asset_page" value="<?php echo $current_asset_page; ?>">
            <input type="hidden" name="asset_search" value="<?php echo htmlspecialchars($asset_search_query); ?>">
            <input type="text" name="user_search" placeholder="Search by username..." value="<?php echo htmlspecialchars($user_search_query); ?>">
            <button type="submit">Search</button>
        </form>
    </div>
    <div class="table-responsive">
        <table><thead><tr><th>User ID</th><th>Username</th><th>Full Name</th><th>Email</th><th>Wallet Balance</th><th>Is Admin?</th><th>Status</th></tr></thead><tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td data-label="User ID"><?php echo $user['id']; ?></td>
                    <td data-label="Username"><?php echo htmlspecialchars($user['username']); ?></td>
                    <td data-label="Full Name"><?php echo htmlspecialchars($user['fullname']); ?></td>
                    <td data-label="Email"><?php echo htmlspecialchars($user['email']); ?></td>
                    <td data-label="Wallet Balance">₦<?php echo number_format($user['wallet_balance'], 2); ?></td>
                    <td data-label="Is Admin?"><?php echo $user['is_admin'] ? 'Yes' : 'No'; ?></td>
                    <td data-label="Status"><?php echo $user['status'] == 2 ? 'Verified' : 'Not Verified'; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody></table>
    </div>
    <div class="pagination">
        <?php if ($total_user_pages > 1): ?>
            <?php for ($i = 1; $i <= $total_user_pages; $i++): ?>
                <a href="?user_page=<?php echo $i; ?>&user_search=<?php echo htmlspecialchars($user_search_query); ?>&asset_page=<?php echo $current_asset_page; ?>&asset_search=<?php echo htmlspecialchars($asset_search_query); ?>" class="page-link <?php echo ($i == $current_user_page) ? 'active' : ''; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
        <?php endif; ?>
    </div>

    <h2>All Payouts</h2>
    <div class="table-responsive">
        <table><thead><tr><th>ID</th><th>Destination</th><th>Triggering Asset (#User)</th><th>Amount</th><th>Type</th><th>Timestamp</th></tr></thead><tbody>
            <?php foreach ($payouts as $p): ?>
                <tr>
                    <td data-label="ID"><?php echo $p['id']; ?></td>
                    <td data-label="Destination"><?php echo ($p['receiving_asset_id'] ? "Asset #{$p['receiving_asset_display_id']} (" . htmlspecialchars($p['receiving_username'] ?? 'N/A') . ")" : "Company " . ucfirst($p['company_fund_type'])); ?></td>
                    <td data-label="Triggering Asset">#<?php echo $p['triggering_asset_display_id']; ?> (<?php echo htmlspecialchars($p['triggering_username']); ?>)</td>
                    <td data-label="Amount">₦<?php echo number_format($p['amount'], 2); ?></td>
                    <td data-label="Type"><?php echo htmlspecialchars(str_replace('_', ' ', ucfirst($p['payout_type']))); ?></td>
                    <td data-label="Timestamp"><?php echo $p['created_at']; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody></table>
    </div>
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
                    data: [<?php echo json_encode($overallIncomeStats['total_company_profit'] ?? 0); ?>, <?php echo json_encode($overallIncomeStats['total_reservation_fund'] ?? 0); ?>, <?php echo json_encode($overallIncomeStats['total_generational_pot'] ?? 0); ?>, <?php echo json_encode($overallIncomeStats['total_shared_pot'] ?? 0); ?>],
                    backgroundColor: ['#2ecc71', '#f1c40f', '#3498db', '#9b59b6']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
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
                    data: [<?php echo json_encode($assetStatusDistribution['active_count'] ?? 0); ?>, <?php echo json_encode($assetStatusDistribution['completed_count'] ?? 0); ?>, <?php echo json_encode($assetStatusDistribution['expired_count'] ?? 0); ?>],
                    backgroundColor: ['#27ae60', '#3498db', '#e74c3c']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
            }
        });
    }

    const priceInput = document.getElementById('new_asset_price');
    const payoutCapInput = document.getElementById('new_asset_payout_cap');

    if (priceInput && payoutCapInput) {
        priceInput.addEventListener('input', function() {
            const price = parseFloat(this.value);
            if (!isNaN(price) && price > 0) {
                const percentage = (price / 35) * 100;
                const payoutCap = (726 * percentage) / 100;
                payoutCapInput.value = payoutCap.toFixed(2);
            } else {
                payoutCapInput.value = '';
            }
        });
    }

    const categoryInput = document.getElementById('new_asset_category');
    if(categoryInput) {
        categoryInput.addEventListener('input', function() {
            if (this.value.length > 0 && this.value[0] !== this.value[0].toUpperCase()) {
                this.setCustomValidity('Category must start with a capital letter.');
            } else {
                this.setCustomValidity('');
            }
        });
    }
});
</script>
<?php include __DIR__ . '/../assets/template/end-template.php'; ?>
