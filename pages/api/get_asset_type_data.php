<?php
// pages/api/get_asset_type_data.php
error_reporting(0); // Suppress notices and warnings
ini_set('display_errors', 0);

header('Content-Type: application/json');

// Minimal includes to avoid authentication checks
require_once __DIR__ . '/../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/functions.php';

try {
    // Allow access from any origin for development purposes.
    header("Access-Control-Allow-Origin: *");

    $assetTypeId = filter_input(INPUT_GET, 'asset_type_id', FILTER_VALIDATE_INT);
    $range = filter_input(INPUT_GET, 'range', FILTER_SANITIZE_STRING) ?? '3M';

    if (!$assetTypeId) {
        http_response_code(400);
        echo json_encode(['error' => 'Asset Type ID is required.']);
        exit;
    }

    // Fetch asset type details
    $assetTypeStmt = $pdo->prepare("SELECT * FROM asset_types WHERE id = ?");
    $assetTypeStmt->execute([$assetTypeId]);
    $assetType = $assetTypeStmt->fetch(PDO::FETCH_ASSOC);

    if (!$assetType) {
        http_response_code(404);
        echo json_encode(['error' => 'Asset type not found.']);
        exit;
    }

    // Fetch historical data
    $historicalData = getAssetTypeStats($pdo, $assetTypeId, $range);

    // Check market status
    $marketStatusStmt = $pdo->query("SELECT value FROM settings WHERE key = 'market_status'");
    $marketStatus = $marketStatusStmt->fetchColumn();
    $isMarketOpen = ($marketStatus === 'open');

    // Simulate minute-by-minute price change if market is open
    if ($isMarketOpen) {
        $assetType['dividing_price'] = simulateMinutePriceChange($assetType['dividing_price']);
    }

    // Combine and return data
    $response = [
        'asset_type' => $assetType,
        'historical_data' => $historicalData,
        'is_market_open' => $isMarketOpen
    ];

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'An internal server error occurred.', 'details' => $e->getMessage()]);
    error_log("API Error in get_asset_type_data.php: " . $e->getMessage());
}
?>