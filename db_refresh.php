<?php
// db_refresh.php
require_once __DIR__ . '/config/database.php';

try {
    // Drop existing tables in correct order to avoid foreign key constraints
    $pdo->exec("DROP TABLE IF EXISTS payouts");
    $pdo->exec("DROP TABLE IF EXISTS assets");
    $pdo->exec("DROP TABLE IF EXISTS asset_types");
    $pdo->exec("DROP TABLE IF EXISTS payment_proofs");
    $pdo->exec("DROP TABLE IF EXISTS users");
    $pdo->exec("DROP TABLE IF EXISTS company_funds");

    // Create users table with latest schema
    $pdo->exec("
        CREATE TABLE users (
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
            status INTEGER DEFAULT 1 NOT NULL,
            wallet_balance DECIMAL(10, 2) DEFAULT 0.00,
            is_admin INTEGER DEFAULT 0
        )
    ");

    // Create payment_proofs table
    $pdo->exec("
        CREATE TABLE payment_proofs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL UNIQUE,
            file_path TEXT NOT NULL,
            uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
            status INTEGER DEFAULT 1,
            FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");

    echo "Database refreshed successfully!";
    
} catch (PDOException $e) {
    die("Database refresh failed: " . $e->getMessage());
}