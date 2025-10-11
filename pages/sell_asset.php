<?php
require_once __DIR__ . '/../src/functions.php';
require_once __DIR__ . '/../src/assets_functions.php';

check_auth(); // Ensure user is logged in

$loggedInUser = $_SESSION['user'];
$loggedInUserId = $loggedInUser['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $assetId = filter_input(INPUT_POST, 'asset_id', FILTER_VALIDATE_INT);
    $pin = trim($_POST['transaction_pin'] ?? '');

    if (!$assetId) {
        $_SESSION['sell_asset_message'] = "Error: Invalid asset ID.";
        $_SESSION['sell_asset_status'] = 'error';
    } elseif (empty($pin) || !preg_match('/^\d{4}$/', $pin)) {
        $_SESSION['sell_asset_message'] = "Error: Please enter a valid 4-digit transaction PIN.";
        $_SESSION['sell_asset_status'] = 'error';
    } else {
        $result = sellCompletedAsset($pdo, $loggedInUserId, $assetId, $pin);
        if ($result['success']) {
            $_SESSION['sell_asset_message'] = $result['message'];
            $_SESSION['sell_asset_status'] = 'success';
        } else {
            $_SESSION['sell_asset_message'] = $result['message'];
            $_SESSION['sell_asset_status'] = 'error';
        }
    }
} else {
    $_SESSION['sell_asset_message'] = "Error: Invalid request method.";
    $_SESSION['sell_asset_status'] = 'error';
}

header('Location: shares'); // Redirect back to the shares page
exit();
?>