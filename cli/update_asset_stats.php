<?php
// This script is intended to be run from the command line (CLI)
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from the command line.');
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/functions.php'; // For simulateMinutePriceChange
require_once __DIR__ . '/../src/assets_functions.php';

echo "Cron Job: Updating Asset Stats...\n";

// 1. Check Market Hours (10:00 AM to 6:00 PM)
$now = new DateTime();
$marketOpenTime = (new DateTime())->setTime(10, 0);
$marketCloseTime = (new DateTime())->setTime(18, 0);

if ($now >= $marketOpenTime && $now < $marketCloseTime) {
    // Market is open, update status in DB
    $pdo->exec("UPDATE settings SET value = 'open' WHERE key = 'market_status'");
    echo "Market is OPEN. Proceeding with updates.\n";
} else {
    // Market is closed, update status in DB and exit
    $pdo->exec("UPDATE settings SET value = 'closed' WHERE key = 'market_status'");
    echo "Market is CLOSED. No updates will be made.\n";
    exit;
}

try {
    // 2. Get all asset types
    $assetTypes = getAssetTypes($pdo);

    foreach ($assetTypes as $assetType) {
        $assetTypeId = $assetType['id'];
        $currentDividingPrice = $assetType['dividing_price'];

        // 3. Simulate the next minute's price
        $newDividingPrice = simulateMinutePriceChange($currentDividingPrice);

        // 4. Update the dividing_price in the asset_types table
        $updateStmt = $pdo->prepare("UPDATE asset_types SET dividing_price = ? WHERE id = ?");
        $updateStmt->execute([$newDividingPrice, $assetTypeId]);

        // 5. Create the new OHLC record for the last minute
        $timestamp = time() * 1000; // Current time in milliseconds
        $open = (float)$currentDividingPrice;
        $close = (float)$newDividingPrice;
        $high = max($open, $close);
        $low = min($open, $close);
        $volume = mt_rand(100, 1000); // Simulate some volume

        $insertStmt = $pdo->prepare(
            "INSERT INTO asset_type_stats (asset_type_id, timestamp, open_price, high_price, low_price, close_price, volume) VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $insertStmt->execute([$assetTypeId, $timestamp, $open, $high, $low, $close, $volume]);

        echo "  - Updated asset type #{$assetTypeId} ({$assetType['name']}): Price changed from {$currentDividingPrice} to {$newDividingPrice}\n";
    }

    echo "Update process completed successfully.\n";

} catch (Exception $e) {
    error_log("Cron job failed: " . $e->getMessage());
    echo "An error occurred: " . $e->getMessage() . "\n";
}

?>