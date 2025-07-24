<?php
require_once __DIR__ . '/config/database.php';

try {
    $pdo->exec("UPDATE users SET wallet_balance = 0;");
    echo "All user wallet balances cleared successfully.";
} catch (PDOException $e) {
    echo "Error clearing user wallet balances: " . $e->getMessage();
}
?>