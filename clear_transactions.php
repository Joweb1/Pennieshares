<?php
require_once __DIR__ . '/config/database.php';

try {
    $pdo->exec("DELETE FROM wallet_transactions;");
    echo "All transactions cleared successfully.";
} catch (PDOException $e) {
    echo "Error clearing transactions: " . $e->getMessage();
}
?>