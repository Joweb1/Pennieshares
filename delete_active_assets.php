<?php
require_once __DIR__ . '/config/database.php';

try {
    // Delete records from dependent tables first
    $pdo->exec("DELETE FROM payouts;");
    $pdo->exec("DELETE FROM pending_profits;");

    // Now delete all active assets
    $pdo->exec("DELETE FROM assets WHERE is_completed = 0 AND is_manually_expired = 0;");
    echo "All active assets and related records deleted successfully.";
} catch (PDOException $e) {
    echo "Error deleting active assets and related records: " . $e->getMessage();
}
?>