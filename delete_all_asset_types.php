<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/src/assets_functions.php';

$assetTypes = getAssetTypes($pdo);

if (empty($assetTypes)) {
    echo "No asset types found to delete.\n";
    exit();
}

foreach ($assetTypes as $assetType) {
    echo "Attempting to delete asset type: " . $assetType['name'] . " (ID: " . $assetType['id'] . ")\n";
    if (deleteAssetType($pdo, $assetType['id'])) {
        echo "Successfully deleted asset type: " . $assetType['name'] . "\n";
    } else {
        echo "Failed to delete asset type: " . $assetType['name'] . "\n";
    }
}

?>