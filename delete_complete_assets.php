<?php
// delete_complete_assets.php
require_once __DIR__ . '/config/database.php';

try {
    // Temporarily disable foreign key checks
    $pdo->exec('PRAGMA foreign_keys = OFF;');

    // Start a transaction
    $pdo->beginTransaction();

    // Delete the complete assets
    $stmt_assets = $pdo->prepare("DELETE FROM assets WHERE is_completed = 1");
    $stmt_assets->execute();
    $deleted_assets = $stmt_assets->rowCount();
    echo "Successfully deleted $deleted_assets complete assets.\n";

    // Commit the transaction
    $pdo->commit();

} catch (PDOException $e) {
    // Rollback the transaction on error
    $pdo->rollBack();
    die("Failed to delete complete assets: " . $e->getMessage());
} finally {
    // Re-enable foreign key checks, regardless of success or failure
    $pdo->exec('PRAGMA foreign_keys = ON;');
}
?>