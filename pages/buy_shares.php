<?php 
    require_once __DIR__ . '/../src/functions.php';
    check_auth();

    $assetTypeId = filter_input(INPUT_GET, 'asset_type_id', FILTER_VALIDATE_INT);
    if (!$assetTypeId) {
        header("Location: market.php");
        exit;
    }

    $assetTypes = getAssetTypes($pdo);
    $selectedAsset = null;
    foreach ($assetTypes as $type) {
        if ($type['id'] == $assetTypeId) {
            $selectedAsset = $type;
            break;
        }
    }

    if (!$selectedAsset) {
        header("Location: market.php");
        exit;
    }

    $branding = getAssetBranding($selectedAsset['id']);
    $user = $_SESSION['user'];

    $actionMessage = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'buy_shares') {
        $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT, ["options" => ["min_range"=>1]]);
        if ($quantity) {
            $result = buyAsset($user['id'], $assetTypeId, $quantity);
            $actionMessage = implode(" ", $result['summary']);
        } else {
            $actionMessage = "Invalid quantity specified.";
        }
    }

    require_once __DIR__ . '/../assets/template/intro-template.php';
?>
<style>
    :root { --color-bg: #f8fafc; --color-surface: #ffffff; --color-primary: #0c7ff2; --color-primary-text: #ffffff; --color-text-primary: #0d141c; --color-text-secondary: #49739c; --color-border: #e7edf4; --border-radius: 0.75rem; }
    body { font-family: 'Inter', sans-serif; background-color: var(--color-bg); color: var(--color-text-primary); margin: 0; }
    .container { max-width: 560px; margin: 0 auto; padding: 2rem 1rem; }
    .main-title { font-size: 2rem; font-weight: 700; margin-bottom: 1rem; }
    .card { background-color: var(--color-surface); border-radius: var(--border-radius); padding: 1.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
    .asset-header { display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem; }
    .asset-logo { width: 64px; height: 64px; border-radius: 50%; background-size: cover; background-position: center; }
    .asset-name { font-size: 1.5rem; font-weight: 700; }
    .form-group { margin-bottom: 1.5rem; }
    label { display: block; font-weight: 500; margin-bottom: 0.5rem; }
    .input-wrapper { position: relative; }
    .input-icon { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--color-text-secondary); }
    .form-input { width: 100%; height: 52px; padding: 0 1rem 0 2.5rem; border-radius: var(--border-radius); background-color: var(--color-bg); border: 1px solid var(--color-border); font-size: 1rem; }
    .btn { display: inline-flex; align-items: center; justify-content: center; height: 52px; border-radius: var(--border-radius); font-size: 1rem; font-weight: 700; padding: 0 2rem; cursor: pointer; border: none; }
    .btn-primary { background-color: var(--color-primary); color: var(--color-primary-text); width: 100%; }
    .message { padding: 1rem; margin-bottom: 1rem; border-radius: var(--border-radius); }
    .message-success { background-color: #d1fae5; color: #065f46; }
    .message-error { background-color: #fee2e2; color: #991b1b; }
</style>
<main>
    <div class="container">
        <h2 class="main-title">Buy Shares</h2>
        <?php if ($actionMessage): ?>
            <div class="message <?php echo strpos($actionMessage, 'Error') !== false ? 'message-error' : 'message-success'; ?>">
                <?php echo htmlspecialchars($actionMessage); ?>
            </div>
        <?php endif; ?>
        <div class="card">
            <div class="asset-header">
                <div class="asset-logo" style="background-image: url('<?php echo htmlspecialchars($branding['image']); ?>');"></div>
                <h3 class="asset-name"><?php echo htmlspecialchars($branding['name']); ?></h3>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="buy_shares">
                <div class="form-group">
                    <label for="quantity">Quantity</label>
                    <div class="input-wrapper">
                        <span class="input-icon">#</span>
                        <input id="quantity" name="quantity" class="form-input" type="number" value="1" min="1">
                    </div>
                </div>
                <div class="form-group">
                    <label>Price per Share</label>
                    <p>₦<?php echo number_format($selectedAsset['price'], 2); ?></p>
                </div>
                <div class="form-group">
                    <label>Your Wallet Balance</label>
                    <p>₦<?php echo number_format($user['wallet_balance'], 2); ?></p>
                </div>
                <button type="submit" class="btn btn-primary">Confirm Purchase</button>
            </form>
        </div>
    </div>
</main>
<?php 
    require_once __DIR__ . '/../assets/template/end-template.php';
?>