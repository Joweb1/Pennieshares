<?php
require_once __DIR__ . '/../src/init.php';

// Ensure user is authenticated
if (!isset($_SESSION['user'])) {
    header('Location: /login');
    exit();
}

$user = $_SESSION['user'];
$userId = $user['id'];

// Check if it's a POST request and asset_type_id is set
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['asset_type_id'])) {
    header('Location: /shares');
    exit();
}

$assetTypeId = $_POST['asset_type_id'];

// Redirect URL in case of success or failure
$redirectUrl = "/grouped_assets?asset_type_id={$assetTypeId}";

try {
    $pdo->beginTransaction();

    // 1. Find all completed, unsold assets of the specified type for the user
    $stmt = $pdo->prepare(
        "SELECT a.id, at.price as original_price, at.name as asset_name
         FROM assets a
         JOIN asset_types at ON a.asset_type_id = at.id
         WHERE a.user_id = :user_id 
           AND a.asset_type_id = :asset_type_id
           AND a.is_completed = 1 
           AND a.is_sold = 0"
    );
    $stmt->execute([':user_id' => $userId, ':asset_type_id' => $assetTypeId]);
    $assetsToSell = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($assetsToSell)) {
        $_SESSION['sell_asset_message'] = 'No completed assets available to sell for this type.';
        $_SESSION['sell_asset_status'] = 'error';
        header("Location: {$redirectUrl}");
        exit();
    }

    // 2. Calculate total sale price (70% of original price)
    $totalSalePrice = 0;
    $assetIdsToMarkSold = [];
    $assetName = '';
    foreach ($assetsToSell as $asset) {
        $totalSalePrice += $asset['original_price'] * 0.70;
        $assetIdsToMarkSold[] = $asset['id'];
        $assetName = $asset['asset_name']; // All assets are the same type
    }

    // 3. Credit user's wallet
    if ($totalSalePrice > 0) {
        $description = count($assetsToSell) . "x '" . htmlspecialchars($assetName) . "' assets sold.";
        creditUserWallet($userId, $totalSalePrice, $description);
    }

    // 4. Mark assets as sold
    if (!empty($assetIdsToMarkSold)) {
        $placeholders = implode(',', array_fill(0, count($assetIdsToMarkSold), '?'));
        $updateStmt = $pdo->prepare("UPDATE assets SET is_sold = 1 WHERE id IN ({$placeholders})");
        $updateStmt->execute($assetIdsToMarkSold);
    }

    $pdo->commit();

    // 5. Set success message
    $_SESSION['sell_asset_message'] = count($assetsToSell) . " asset(s) sold successfully for a total of SV " . number_format($totalSalePrice, 2) . ".";
    $_SESSION['sell_asset_status'] = 'success';

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error selling all completed assets: " . $e->getMessage());
    $_SESSION['sell_asset_message'] = 'An error occurred during the sale. Please try again.';
    $_SESSION['sell_asset_status'] = 'error';
}

// 6. Redirect back
header("Location: {$redirectUrl}");
exit();
?>