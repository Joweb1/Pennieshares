<?php
require_once __DIR__ . '/../../src/functions.php';
check_auth();

header('Content-Type: application/json');

$loggedInUser = $_SESSION['user'];
$loggedInUserId = $loggedInUser['id'];

// Fetch all transactions for the logged-in user
function getAllUserTransactions($pdo, $userId, $limit = null) {
    $sql = "SELECT * FROM wallet_transactions WHERE user_id = ? ORDER BY created_at DESC";
    if ($limit !== null) {
        $sql .= " LIMIT ?";
    }
    $stmt = $pdo->prepare($sql);
    if ($limit !== null) {
        $stmt->execute([$userId, $limit]);
    } else {
        $stmt->execute([$userId]);
    }
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$limit = $_GET['limit'] ?? 100; // Default to 100 transactions
$transactions = getAllUserTransactions($pdo, $loggedInUserId, $limit);

// Company Info (replace with actual company data)
$companyName = "Pennieshares";
$companyLogo = "../assets/images/logo.png"; // Adjust path as needed

// Start building the HTML content
$html = '<div class="export-history-container">';

// Company and User Info Header
$html .= '<div class="company-info">';
$html .= '<img src="' . $companyLogo . '" alt="Company Logo" class="company-logo">';
$html .= '<h2>' . htmlspecialchars($companyName) . '</h2>';
$html .= '<p>Transaction History</p>';
$html .= '</div>';

$html .= '<div class="user-info">';
$html .= '<p><strong>User:</strong> ' . htmlspecialchars($loggedInUser['fullname']) . ' (' . htmlspecialchars($loggedInUser['username']) . ')</p>';
$html .= '<p><strong>Email:</strong> ' . htmlspecialchars($loggedInUser['email']) . '</p>';
$html .= '<p><strong>Current Date:</strong> ' . date('Y-m-d H:i:s') . '</p>';
$html .= '</div>';

// Group transactions by date for better readability
$groupedTransactions = [];
foreach ($transactions as $transaction) {
    $date = date('Y-m-d', strtotime($transaction['created_at']));
    if (!isset($groupedTransactions[$date])) {
        $groupedTransactions[$date] = [];
    }
    $groupedTransactions[$date][] = $transaction;
}

if (empty($transactions)) {
    $html .= '<p style="text-align: center; margin-top: 20px;">No transactions found for this user.</p>';
} else {
    foreach ($groupedTransactions as $date => $dailyTransactions) {
        $html .= '<h3>Transactions on ' . htmlspecialchars($date) . '</h3>';
        $html .= '<table class="history-table">';
        $html .= '<thead><tr><th>Time</th><th>Type</th><th>Description</th><th>Amount</th></tr></thead>';
        $html .= '<tbody>';
        foreach ($dailyTransactions as $transaction) {
            $type = htmlspecialchars(ucwords(str_replace(['_', 'transfer_in', 'transfer_out'], [' ', 'credit', 'payout'], $transaction['type'])));
            $amountSign = ($transaction['type'] === 'debit' || $transaction['type'] === 'transfer_out' || $transaction['type'] === 'payout') ? '-' : '+';
            $amountFormatted = number_format(abs($transaction['amount']), 2);
            $time = date('H:i', strtotime($transaction['created_at']));

            $html .= '<tr>';
            $html .= '<td>' . $time . '</td>';
            $html .= '<td>' . $type . '</td>';
            $html .= '<td>' . htmlspecialchars($transaction['description']) . '</td>';
            $html .= '<td>' . $amountSign . 'SV' . $amountFormatted . '</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody>';
        $html .= '</table>';
    }
}

$html .= '</div>';

echo json_encode(['html' => $html]);
?>