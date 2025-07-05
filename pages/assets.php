<?php
require_once __DIR__ . '/../src/functions.php';
check_auth();

$user = $_SESSION['user'];
$userAssets = getUserAssets($pdo, $user['id']);
$assetTypes = getAssetTypes($pdo);

$actionMessage = '';
$purchaseDetails = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'buy_asset') {
    $assetTypeId = filter_input(INPUT_POST, 'asset_type_id', FILTER_VALIDATE_INT);
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT, ["options" => ["min_range"=>1, "max_range"=>10]]);

    if ($assetTypeId === false || $assetTypeId === null) {
        $actionMessage = "Error: Please select a valid asset type.";
    } elseif ($quantity === false || $quantity === null) {
        $actionMessage = "Error: Invalid quantity.";
    } else {
        $purchaseDetails = buyAsset($user['id'], $assetTypeId, $quantity);
    }
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Assets</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f7f6; color: #333; margin: 0; padding: 20px; }
        .container { max-width: 1000px; margin: auto; }
        .wallet-balance { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 15px; text-align: center; margin-bottom: 20px; }
        .wallet-balance h2 { margin: 0; font-size: 1.5em; }        
        .wallet-balance p { margin: 5px 0 0; font-size: 2.5em; font-weight: bold; }
        .card { background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 15px rgba(0,0,0,0.1); margin-bottom: 20px; }
        h2, h3 { color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px; margin-top: 0; }
        table { border-collapse: collapse; width: 100%; font-size: 0.9em; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #3498db; color: white; }
        tr:nth-child(even) { background-color: #ecf0f1; }
        .status-completed { background-color: #d4efdf !important; }
        .status-expired { background-color: #fdedec !important; }
        .form-section { margin-top: 20px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        select, input[type="number"] { width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px; }
        button { padding: 12px 20px; background-color: #3498db; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 1em; }
        button:hover { background-color: #2980b9; }
        .message { padding: 15px; margin-bottom:20px; border-radius: 5px; border-left: 5px solid; }
        .message-success { background-color: #e8f8f5; color: #1abc9c; border-left-color: #1abc9c;}
        .message-error { background-color: #fdedec; color: #e74c3c; border-left-color: #e74c3c;}
    </style>
</head>
<body>
<div class="container">
    <div class="wallet-balance">
        <h2>My Wallet Balance</h2>
        <p>₦<?php echo number_format($user['wallet_balance'], 2); ?></p>
    </div>

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

    <div class="card">
        <h3>My Assets</h3>
        <table>
            <thead>
                <tr><th>ID</th><th>Type</th><th>Gen. Received</th><th>Shared Rec.</th><th>Total Earned</th><th>Status</th><th>Expires</th></tr>
            </thead>
            <tbody>
            <?php foreach($userAssets as $ua):
                $status_class = '';
                if($ua['current_status']==='Completed') $status_class = 'status-completed';
                elseif($ua['current_status']==='Expired') $status_class = 'status-expired';
            ?>
            <tr class="<?php echo $status_class; ?>">
                <td><?php echo $ua['id']; ?></td>
                <td><?php echo htmlspecialchars($ua['asset_type_name']); ?></td>
                <td>₦<?php echo number_format($ua['total_generational_received'],2);?></td>
                <td>₦<?php echo number_format($ua['total_shared_received'],2);?></td>
                <td>₦<?php echo number_format($ua['total_earned'],2);?></td>
                <td><?php echo htmlspecialchars($ua['current_status']);?></td>
                <td><?php echo $ua['expires_at'] ?: 'Unlimited';?></td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($userAssets)): ?> <tr><td colspan="7">You have not purchased any assets yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="card form-section">
        <h3>Buy New Asset</h3>
        <form method="post">
            <input type="hidden" name="action" value="buy_asset">
            <div>
                <label for="asset_type_id">Select Asset Type:</label>
                <select name="asset_type_id" id="asset_type_id" required>
                    <option value="">-- Select Type --</option>
                    <?php foreach ($assetTypes as $type): ?>
                        <option value="<?php echo $type['id']; ?>">
                            <?php echo htmlspecialchars($type['name']); ?> (Price: SV<?php echo number_format($type['price'],2); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="quantity">Quantity:</label>
                <input type="number" name="quantity" id="quantity" value="1" min="1" max="10">
            </div>
            <button type="submit">Purchase Asset</button>
        </form>
    </div>

</div>
</body>
</html>
