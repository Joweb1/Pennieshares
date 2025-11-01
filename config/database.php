<?php

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Define BASE_URL
if (isset($_ENV['APP_URL'])) {
    define('BASE_URL', $_ENV['APP_URL']);
} else {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $script_name = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    $base_path = ($script_name == '/') ? '' : $script_name;
    define('BASE_URL', $protocol . $host . $base_path);
}

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
    if (!in_array('is_broker', $user_columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN is_broker INTEGER DEFAULT 0");
    }
    if (!in_array('is_verified', $user_columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN is_verified INTEGER NOT NULL DEFAULT 0");
    }
    if (!in_array('last_login_email_sent', $user_columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN last_login_email_sent DATE");
    }
    if (!in_array('otp_code', $user_columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN otp_code TEXT");
    }
    if (!in_array('otp_expires_at', $user_columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN otp_expires_at DATETIME");
    }
    if (!in_array('total_return', $user_columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN total_return DECIMAL(10, 2) DEFAULT 0.00");
    }
    if (!in_array('performance_chart_data', $user_columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN performance_chart_data TEXT");
    }
    if (!in_array('performance_value', $user_columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN performance_value DECIMAL(10, 2)");
    }
    if (!in_array('performance_change', $user_columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN performance_change DECIMAL(10, 2)");
    }
    if (!in_array('last_performance_update', $user_columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN last_performance_update DATE");
    }
    if (!in_array('transaction_pin', $user_columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN transaction_pin TEXT");
    }
    if (!in_array('earnings_paused', $user_columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN earnings_paused INTEGER DEFAULT 0");
    }
    if (!in_array('has_received_referral_bonus', $user_columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN has_received_referral_bonus INTEGER DEFAULT 0");
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
        image_link TEXT,
        category TEXT
    );");

    // Add image_link column to asset_types if it doesn't exist
    $asset_type_columns = $pdo->query("PRAGMA table_info(asset_types)")->fetchAll(PDO::FETCH_COLUMN, 1);
    if (!in_array('image_link', $asset_type_columns)) {
        $pdo->exec("ALTER TABLE asset_types ADD COLUMN image_link TEXT");
    }
    if (!in_array('dividing_price', $asset_type_columns)) {
        $pdo->exec("ALTER TABLE asset_types ADD COLUMN dividing_price DECIMAL(10, 2)");
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
        is_sold INTEGER DEFAULT 0,
        created_at TEXT NOT NULL,
        expires_at TEXT,
        completed_at TEXT,
        FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY(asset_type_id) REFERENCES asset_types(id) ON DELETE CASCADE,
        FOREIGN KEY(parent_id) REFERENCES assets(id) ON DELETE SET NULL
    );");
    
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_assets_active_for_parenting ON assets (is_completed, is_manually_expired, children_count, created_at, id) WHERE is_completed = 0 AND is_manually_expired = 0 AND children_count < 3;");

    // --- Add columns to assets table if they don't exist ---
    $asset_columns = $pdo->query("PRAGMA table_info(assets)")->fetchAll(PDO::FETCH_COLUMN, 1);
    if (!in_array('is_sold', $asset_columns)) {
        $pdo->exec("ALTER TABLE assets ADD COLUMN is_sold INTEGER DEFAULT 0");
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS payouts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        receiving_asset_id INTEGER, 
        triggering_asset_id INTEGER NOT NULL,
        company_fund_type TEXT, 
        amount DECIMAL(10, 2) NOT NULL,
        payout_type TEXT NOT NULL, 
        created_at TEXT NOT NULL,
        FOREIGN KEY(receiving_asset_id) REFERENCES assets(id) ON DELETE CASCADE,
        FOREIGN KEY(triggering_asset_id) REFERENCES assets(id) ON DELETE CASCADE
    );");

    $pdo->exec("CREATE TABLE IF NOT EXISTS company_funds (
        id INTEGER PRIMARY KEY CHECK (id = 1), 
        total_company_profit DECIMAL(10, 2) DEFAULT 0.00,
        total_reservation_fund DECIMAL(10, 2) DEFAULT 0.00,
        total_generational_pot DECIMAL(10, 2) DEFAULT 0.00,
        total_shared_pot DECIMAL(10, 2) DEFAULT 0.00,
        last_updated TEXT
    );");

    // --- Create Wallet Transactions Table ---
    $pdo->exec("CREATE TABLE IF NOT EXISTS wallet_transactions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        type TEXT NOT NULL, -- e.g., 'credit', 'debit', 'transfer_in', 'transfer_out', 'asset_profit'
        amount DECIMAL(10, 2) NOT NULL,
        description TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    // --- Create Pending Profits Table ---
    $pdo->exec("CREATE TABLE IF NOT EXISTS pending_profits (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        receiving_asset_id INTEGER NOT NULL,
        fractional_amount DECIMAL(10, 2) NOT NULL,
        payout_type TEXT NOT NULL, -- 'generational' or 'shared'
        credit_at DATETIME NOT NULL,
        is_credited INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY(receiving_asset_id) REFERENCES assets(id) ON DELETE CASCADE
    )");

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

    // --- Create KYC Verifications Table ---
    $pdo->exec("CREATE TABLE IF NOT EXISTS kyc_verifications (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL UNIQUE,
        full_name TEXT,
        dob TEXT,
        address TEXT,
        state TEXT,
        bvn TEXT,
        nin TEXT,
        passport_path TEXT,
        national_id_path TEXT,
        proof_of_address_path TEXT,
        selfie_path TEXT,
        status TEXT DEFAULT 'pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME,
        FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    // --- Create Asset Type Stats Table ---
    $pdo->exec("CREATE TABLE IF NOT EXISTS asset_type_stats (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        asset_type_id INTEGER NOT NULL,
        timestamp INTEGER NOT NULL,
        open_price DECIMAL(10, 2) NOT NULL,
        high_price DECIMAL(10, 2) NOT NULL,
        low_price DECIMAL(10, 2) NOT NULL,
        close_price DECIMAL(10, 2) NOT NULL,
        volume INTEGER,
        FOREIGN KEY(asset_type_id) REFERENCES asset_types(id) ON DELETE CASCADE
    )");

    // --- Create Settings Table ---
    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
        key TEXT PRIMARY KEY,
        value TEXT
    )");
    $pdo->exec("INSERT OR IGNORE INTO settings (key, value) VALUES ('market_status', 'closed')");

    // --- Create Expo Push Tokens Table ---
    // --- Create Expo Push Tokens Table ---
    $pdo->exec("CREATE TABLE IF NOT EXISTS expo_push_tokens (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL, token TEXT NOT NULL UNIQUE, FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE);");

    // --- Create Push Subscriptions Table (for web push) ---
    $pdo->exec("CREATE TABLE IF NOT EXISTS push_subscriptions (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL, endpoint TEXT NOT NULL, p256dh TEXT NOT NULL, auth TEXT NOT NULL, FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE);");

    // --- Create User Broker Interactions Table ---
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_broker_interactions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        broker_user_id INTEGER NOT NULL,
        is_favorite INTEGER DEFAULT 0,
        last_transfer_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(user_id, broker_user_id),
        FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY(broker_user_id) REFERENCES users(id) ON DELETE CASCADE
    );");

    // --- Create Trigger to Update `updated_at` timestamp for user_broker_interactions ---
    $pdo->exec("
        CREATE TRIGGER IF NOT EXISTS update_user_broker_interactions_updated_at
        AFTER UPDATE ON user_broker_interactions
        FOR EACH ROW
        BEGIN
            UPDATE user_broker_interactions SET updated_at = datetime('now') WHERE id = OLD.id;
        END;
    ");

    // --- Create Trigger to Update `updated_at` timestamp ---
    $pdo->exec("
        CREATE TRIGGER IF NOT EXISTS update_kyc_verifications_updated_at
        AFTER UPDATE ON kyc_verifications
        FOR EACH ROW
        BEGIN
            UPDATE kyc_verifications SET updated_at = datetime('now') WHERE id = OLD.id;
        END;
    ");
    
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
