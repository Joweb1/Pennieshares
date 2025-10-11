<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../src/init.php';

if (!isset($_SESSION['user'])) {
    echo json_encode(['error' => 'User not authenticated']);
    exit;
}

$userId = $_SESSION['user']['id'];
$assetTypeId = filter_input(INPUT_GET, 'asset_type_id', FILTER_VALIDATE_INT);

if (!$assetTypeId) {
    echo json_encode(['error' => 'Invalid asset type ID']);
    exit;
}

try {
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM assets 
         WHERE user_id = :user_id 
           AND asset_type_id = :asset_type_id 
           AND is_completed = 1 
           AND is_sold = 0"
    );
    $stmt->execute([':user_id' => $userId, ':asset_type_id' => $assetTypeId]);
    $completedAssetsCount = $stmt->fetchColumn();

    echo json_encode(['completed_assets_count' => $completedAssetsCount]);

} catch (PDOException $e) {
    error_log("Error fetching completed assets: " . $e->getMessage());
    echo json_encode(['error' => 'A database error occurred.']);
}
