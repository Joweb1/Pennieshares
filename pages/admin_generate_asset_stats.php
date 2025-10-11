<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/assets_functions.php';
require_once __DIR__ . '/../../src/functions.php'; // For check_auth and potentially admin check

// Optional: Add admin authentication check here
// check_auth();
// if (!isAdmin($_SESSION['user']['id'])) { // Assuming an isAdmin function exists
//     header("Location: /login");
//     exit;
// }

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'generate_stats') {
    try {
        $assetTypesStmt = $pdo->query("SELECT id, dividing_price FROM asset_types");
        $assetTypes = $assetTypesStmt->fetchAll(PDO::FETCH_ASSOC);
        $updatedCount = 0;

        foreach ($assetTypes as $assetType) {
            $assetTypeId = $assetType['id'];

            // Check if asset_type_stats already has data for this asset type
            $statsCountStmt = $pdo->prepare("SELECT COUNT(*) FROM asset_type_stats WHERE asset_type_id = ?");
            $statsCountStmt->execute([$assetTypeId]);
            $hasStats = $statsCountStmt->fetchColumn() > 0;

            if (!$hasStats) {
                // Generate a random dividing price between 40 and 100
                $randomDividingPrice = mt_rand(4000, 10000) / 100; // Between 40.00 and 100.00

                // Update asset_types table with the new random dividing_price
                $updateDividingPriceStmt = $pdo->prepare("UPDATE asset_types SET dividing_price = ? WHERE id = ?");
                $updateDividingPriceStmt->execute([$randomDividingPrice, $assetTypeId]);

                // Generate initial historical data
                generateInitialAssetTypeStats($pdo, $assetTypeId, $randomDividingPrice);
                $updatedCount++;
            }
        }
        $message = "Successfully generated stats for {$updatedCount} asset types.";
    } catch (PDOException $e) {
        error_log("Admin Generate Stats Database Error: " . $e->getMessage());
        $message = "Database error: " . $e->getMessage();
    } catch (Exception $e) {
        error_log("Admin Generate Stats General Error: " . $e->getMessage());
        $message = "An unexpected error occurred: " . $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Asset Stats</title>
    <style>
        body { font-family: sans-serif; margin: 20px; background-color: #f4f4f4; }
        .container { background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); max-width: 600px; margin: auto; }
        h1 { color: #333; }
        .message { padding: 10px; margin-bottom: 15px; border-radius: 5px; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        button { background-color: #007bff; color: white; padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        button:hover { background-color: #0056b3; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Generate Asset Stats for Existing Types</h1>
        <?php if ($message): ?>
            <div class="message <?php echo (strpos($message, 'Error') !== false) ? 'error' : 'success'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        <p>This tool will generate 3 months of historical data and a random dividing price (between 40 and 100) for any existing asset types that currently lack this data.</p>
        <form method="POST">
            <input type="hidden" name="action" value="generate_stats">
            <button type="submit">Generate Missing Stats</button>
        </form>
    </div>
</body>
</html>