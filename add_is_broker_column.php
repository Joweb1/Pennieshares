<?php
require_once __DIR__ . '/src/init.php';

try {
    $pdo->exec("ALTER TABLE users ADD COLUMN is_broker INTEGER DEFAULT 0");
    echo "'is_broker' column added successfully.";
} catch (PDOException $e) {
    die("Error adding 'is_broker' column: " . $e->getMessage());
}
?>