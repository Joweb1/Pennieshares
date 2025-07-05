<?php
// Define paths
$old_db_path = __DIR__ . '/database/mydatabasebak.sqlite';
$new_db_path = __DIR__ . '/database/mydatabase.sqlite';

try {
    // Connect to the old (backup) database
    $old_pdo = new PDO("sqlite:" . $old_db_path);
    $old_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Connect to the new (current) database
    $new_pdo = new PDO("sqlite:" . $new_db_path);
    $new_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get column names from the old users table
    $old_columns_stmt = $old_pdo->query("PRAGMA table_info(users)");
    $old_columns_info = $old_columns_stmt->fetchAll(PDO::FETCH_ASSOC);
    $old_column_names = [];
    foreach ($old_columns_info as $col) {
        if ($col['name'] !== 'id') { // Exclude 'id' as it's auto-incremented in the new table
            $old_column_names[] = $col['name'];
        }
    }

    if (empty($old_column_names)) {
        echo "No columns found in the old 'users' table, or only 'id' exists. No data to import.\n";
        exit();
    }

    // Fetch data from the old users table
    $select_columns = implode(', ', $old_column_names);
    $users_data = $old_pdo->query("SELECT {$select_columns} FROM users")->fetchAll(PDO::FETCH_ASSOC);

    if (empty($users_data)) {
        echo "No user data found in the backup database to import.\n";
        exit();
    }

    // Prepare insert statement for the new users table
    $insert_columns = implode(', ', $old_column_names);
    $placeholders = implode(', ', array_fill(0, count($old_column_names), '?'));
    $insert_stmt = $new_pdo->prepare("INSERT INTO users ({$insert_columns}) VALUES ({$placeholders})");

    $new_pdo->beginTransaction();
    $imported_count = 0;
    foreach ($users_data as $user) {
        $values = [];
        foreach ($old_column_names as $col_name) {
            $values[] = $user[$col_name];
        }
        $insert_stmt->execute($values);
        $imported_count++;
    }
    $new_pdo->commit();

    echo "Successfully imported {$imported_count} users from backup to the new database.\n";

} catch (PDOException $e) {
    if (isset($new_pdo) && $new_pdo->inTransaction()) {
        $new_pdo->rollBack();
    }
    echo "Database import failed: " . $e->getMessage() . "\n";
}
?>