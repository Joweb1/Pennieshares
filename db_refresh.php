<?php
// db_refresh.php
require_once __DIR__ . '/config/database.php';

try {
    // Drop existing tables
    $pdo->exec("DROP TABLE IF EXISTS users");
    $pdo->exec("DROP TABLE IF EXISTS payment_proofs");

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
            status INTEGER DEFAULT 1 NOT NULL
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

    // Add sample user
    $stmt = $pdo->prepare("
        INSERT INTO users (fullname, email, username, phone, referral, password, partner_code, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $sampleUsers = [
        [
            'Jonadab Uroh',
            'supervisor@penniepoint.com',
            'jonadab00',
            '09135580911',
            'so12345',
            password_hash('password123', PASSWORD_BCRYPT),
            'jo12345',
            2
        ],
        [
            'Admin User',
            'admin@penniepoint.com', // Add your admin email here
            'admin_user',
            '0987654321',
            'jo12345',
            password_hash('admin123', PASSWORD_BCRYPT),
            'ad54321',
            1
        ]
    ];

    foreach ($sampleUsers as $user) {
        $stmt->execute($user);
    }

    echo "Database refreshed successfully!";
    
} catch (PDOException $e) {
    die("Database refresh failed: " . $e->getMessage());
}