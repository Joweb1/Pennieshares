<?php
$db_file = __DIR__ . '/../database/mydatabase.sqlite';

try {
    $pdo = new PDO("sqlite:" . $db_file);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    fullname TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    username TEXT NOT NULL UNIQUE,
    phone TEXT NOT NULL,
    referral TEXT NOT NULL,
    stage INTEGER DEFAULT 1,
    partner_code TEXT UNIQUE,
    password TEXT NOT NULL,
    reset_token TEXT,
    reset_expires DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    status INTEGER DEFAULT 1 NOT NULL
    )
    ");
    
    // In your database.php or refresh script
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS payment_proofs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL UNIQUE,
    file_path TEXT NOT NULL,
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
    status INTEGER DEFAULT 1,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
    )
    ");
    
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>