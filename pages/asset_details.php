<?php
require_once __DIR__ . '/../src/init.php';

$user = $_SESSION['user'];
$assetTypeId = filter_input(INPUT_GET, 'asset_type_id', FILTER_VALIDATE_INT);

if (!$assetTypeId) {
    header("Location: grouped_assets");
    exit;
}

// Fetch asset type details
$stmt = $pdo->prepare("SELECT * FROM asset_types WHERE id = ?");
$stmt->execute([$assetTypeId]);
$assetType = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$assetType) {
    header("Location: grouped_assets");
    exit;
}

// Fetch all assets for the user of this specific type
$stmt = $pdo->prepare(
    "SELECT * FROM assets WHERE user_id = ? AND asset_type_id = ? ORDER BY created_at DESC"
);
$stmt->execute([$user['id'], $assetTypeId]);
$assets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Helper function to calculate progress ---
function calculateProgress($totalReceived, $payoutCap) {
    if ($payoutCap <= 0) return 0;
    $progress = ($totalReceived / $payoutCap) * 100;
    return min(100, $progress); // Cap at 100%
}

$completedAssetsCount = 0;
foreach ($assets as $asset) {
    if ($asset['is_completed']) {
        $completedAssetsCount++;
    }
}

$page_title = "Asset Details: " . htmlspecialchars($assetType['name']);
include_once __DIR__ . '/../assets/template/intro-template.php';
?>

<div class="container mt-4">
    <div class="row mb-4 align-items-center">
        <div class="col-md-8">
            <h1 class="text-primary"><?php echo htmlspecialchars($assetType['name']); ?></h1>
            <a href="grouped_assets" class="text-decoration-none"><i class="fas fa-chevron-left"></i> Back to Grouped Assets</a>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <a href="buy_shares?asset_type_id=<?php echo $assetTypeId; ?>" class="btn btn-success me-2">
                <i class="fas fa-plus"></i> Buy More
            </a>
            <?php if ($completedAssetsCount > 0): ?>
                <a href="sell_all_completed_assets?asset_type_id=<?php echo $assetTypeId; ?>" class="btn btn-warning" onclick="return confirm('Are you sure you want to sell all <?php echo $completedAssetsCount; ?> completed assets of this type?');">
                    <i class="fas fa-hand-holding-usd"></i> Sell <?php echo $completedAssetsCount; ?> Completed
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (isset($_SESSION['sell_asset_message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['sell_asset_status'] ?? 'info'; ?> alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['sell_asset_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['sell_asset_message'], $_SESSION['sell_asset_status']); ?>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="mb-0">Your <?php echo count($assets); ?> Asset<?php echo count($assets) > 1 ? 's' : ''; ?></h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Created</th>
                            <th>Total Earned</th>
                            <th>Progress</th>
                            <th>Status</th>
                            <th>Expires</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($assets)): ?>
                            <tr>
                                <td colspan="6" class="text-center">No assets of this type found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($assets as $asset): ?>
                                <?php
                                $totalEarned = $asset['total_generational_received'] + $asset['total_shared_received'];
                                $progress = calculateProgress($totalEarned, $assetType['payout_cap']);
                                $status = 'Active';
                                $statusClass = '';
                                if ($asset['is_completed']) {
                                    $status = 'Completed';
                                    $statusClass = 'table-success';
                                } elseif ($asset['is_manually_expired'] || ($asset['expires_at'] && new DateTime($asset['expires_at']) < new DateTime())) {
                                    $status = 'Expired';
                                    $statusClass = 'table-danger';
                                }
                                ?>
                                <tr class="<?php echo $statusClass; ?>">
                                    <td>#<?php echo $asset['id']; ?></td>
                                    <td><?php echo (new DateTime($asset['created_at']))->format('M d, Y'); ?></td>
                                    <td>SV <?php echo number_format($totalEarned, 2); ?></td>
                                    <td>
                                        <div class="progress" style="height: 18px;">
                                            <div class="progress-bar" role="progressbar" style="width: <?php echo $progress; ?>%;" aria-valuenow="<?php echo $progress; ?>" aria-valuemin="0" aria-valuemax="100"><?php echo round($progress); ?>%</div>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-<?php echo $status === 'Active' ? 'primary' : ($status === 'Completed' ? 'success' : 'danger'); ?>"><?php echo $status; ?></span></td>
                                    <td><?php echo $asset['expires_at'] ? (new DateTime($asset['expires_at']))->format('M d, Y') : 'N/A'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/../assets/template/end-template.php'; ?>