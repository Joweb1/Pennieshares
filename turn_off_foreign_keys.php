<?php
// turn_off_foreign_keys.php
require_once __DIR__ . '/config/database.php';

try {
    $pdo->exec('PRAGMA foreign_keys = OFF;');
    echo "Foreign key checks have been turned OFF.\n";
} catch (PDOException $e) {
    die("Failed to turn off foreign key checks: " . $e->getMessage());
}
?>