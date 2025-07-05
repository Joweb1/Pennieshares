<?php
// Start session and check authentication
require 'functions.php';
check_auth();

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
?>