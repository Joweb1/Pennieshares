<?php
require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Start session and check authentication
require_once __DIR__ . '/functions.php';

// Only perform authentication checks and session-dependent logic if not running from CLI
if (php_sapi_name() !== 'cli') {
    check_auth();

    // Process any pending profits
    processPendingProfits($pdo);

    // Get current user data
    $user = $_SESSION['user'];
    $partner_code = $user['partner_code'];

    // Count referrals
    function countReferrals($partner_code) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE referral = ?");
        $stmt->execute([$partner_code]);
        return $stmt->fetchColumn();
    }

    $referral_count = countReferrals($partner_code);

    // Check and send daily login email
    checkAndSendDailyLoginEmail($pdo, $user['id']);
} else {
    // In CLI context, ensure $user and $partner_code are defined if needed by other included files
    // For this script, they are not directly used outside the web context, but good practice.
    $user = null;
    $partner_code = null;
    $referral_count = 0;
}

?>