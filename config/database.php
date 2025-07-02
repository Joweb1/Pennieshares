<?php
$db_file = __DIR__ . '/../database/mydatabase.sqlite';

try {
    $pdo = new PDO("sqlite:" . $db_file);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('PRAGMA foreign_keys = ON;');

    // --- Create Users Table (if not exists) ---
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
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
    )");

    // --- Add columns to users table if they don't exist ---
    $user_columns = $pdo->query("PRAGMA table_info(users)")->fetchAll(PDO::FETCH_COLUMN, 1);
    if (!in_array('wallet_balance', $user_columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN wallet_balance DECIMAL(10, 2) DEFAULT 0.00");
    }
    if (!in_array('is_admin', $user_columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN is_admin INTEGER DEFAULT 0");
    }

    // --- Create Payment Proofs Table ---
    $pdo->exec("CREATE TABLE IF NOT EXISTS payment_proofs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL UNIQUE,
        file_path TEXT NOT NULL,
        uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
        status INTEGER DEFAULT 1,
        FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    // --- Asset System Tables ---
    $pdo->exec("CREATE TABLE IF NOT EXISTS asset_types (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT UNIQUE NOT NULL,
        price DECIMAL(10, 2) NOT NULL,
        payout_cap DECIMAL(10, 2) NOT NULL,
        duration_months INTEGER NOT NULL,
        reservation_fund_contribution DECIMAL(10, 2) NOT NULL,
        image_link TEXT
    );");

    // Add image_link column to asset_types if it doesn't exist
    $asset_type_columns = $pdo->query("PRAGMA table_info(asset_types)")->fetchAll(PDO::FETCH_COLUMN, 1);
    if (!in_array('image_link', $asset_type_columns)) {
        $pdo->exec("ALTER TABLE asset_types ADD COLUMN image_link TEXT");
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS assets (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        asset_type_id INTEGER NOT NULL,
        parent_id INTEGER,
        generation INTEGER DEFAULT 1,
        children_count INTEGER DEFAULT 0,
        total_generational_received DECIMAL(10, 2) DEFAULT 0.00,
        total_shared_received DECIMAL(10, 2) DEFAULT 0.00,
        is_completed INTEGER DEFAULT 0,
        is_manually_expired INTEGER DEFAULT 0,
        created_at TEXT NOT NULL,
        expires_at TEXT,
        completed_at TEXT,
        FOREIGN KEY(user_id) REFERENCES users(id),
        FOREIGN KEY(asset_type_id) REFERENCES asset_types(id),
        FOREIGN KEY(parent_id) REFERENCES assets(id) ON DELETE SET NULL
    );");
    
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_assets_active_for_parenting ON assets (is_completed, is_manually_expired, children_count, created_at, id) WHERE is_completed = 0 AND is_manually_expired = 0 AND children_count < 3;");

    $pdo->exec("CREATE TABLE IF NOT EXISTS payouts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        receiving_asset_id INTEGER, 
        triggering_asset_id INTEGER NOT NULL,
        company_fund_type TEXT, 
        amount DECIMAL(10, 2) NOT NULL,
        payout_type TEXT NOT NULL, 
        created_at TEXT NOT NULL,
        FOREIGN KEY(receiving_asset_id) REFERENCES assets(id),
        FOREIGN KEY(triggering_asset_id) REFERENCES assets(id)
    );");

    $pdo->exec("CREATE TABLE IF NOT EXISTS company_funds (
        id INTEGER PRIMARY KEY CHECK (id = 1), 
        total_company_profit DECIMAL(10, 2) DEFAULT 0.00,
        total_reservation_fund DECIMAL(10, 2) DEFAULT 0.00,
        total_generational_pot DECIMAL(10, 2) DEFAULT 0.00,
        total_shared_pot DECIMAL(10, 2) DEFAULT 0.00,
        last_updated TEXT
    );");

    // Add total_generational_pot and total_shared_pot columns to company_funds if they don't exist
    $company_funds_columns = $pdo->query("PRAGMA table_info(company_funds)")->fetchAll(PDO::FETCH_COLUMN, 1);
    if (!in_array('total_generational_pot', $company_funds_columns)) {
        $pdo->exec("ALTER TABLE company_funds ADD COLUMN total_generational_pot DECIMAL(10, 2) DEFAULT 0.00");
    }
    if (!in_array('total_shared_pot', $company_funds_columns)) {
        $pdo->exec("ALTER TABLE company_funds ADD COLUMN total_shared_pot DECIMAL(10, 2) DEFAULT 0.00");
    }

    // --- Initial Data Seeding ---
    $pdo->exec("INSERT OR IGNORE INTO company_funds (id, total_company_profit, total_reservation_fund, last_updated) VALUES (1, 0.00, 0.00, datetime('now'))");

    $stmt = $pdo->query("SELECT COUNT(*) FROM asset_types");
    if ($stmt->fetchColumn() == 0) {
        $base_price = 20.00;
        $price_increment = 1.50;
        $base_cap = 140.00;
        $cap_increment = 10.00;
        $base_duration_months = 3;
        $duration_increment = 1;
        define('FIXED_ALLOCATIONS_TOTAL_FOR_SEED', 18);

        for ($i = 1; $i <= 10; $i++) {
            $name = "Asset Type " . $i;
            $price = $base_price + (($i - 1) * $price_increment);
            $payout_cap = $base_cap + (($i - 1) * $cap_increment);
            $duration_months = ($i == 10) ? 0 : $base_duration_months + (($i - 1) * $duration_increment);
            $reservation_contribution = $price - FIXED_ALLOCATIONS_TOTAL_FOR_SEED;
            $stmt = $pdo->prepare("INSERT INTO asset_types (id, name, price, payout_cap, duration_months, reservation_fund_contribution) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$i, $name, $price, $payout_cap, $duration_months, $reservation_contribution]);
        }
    }
    
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>