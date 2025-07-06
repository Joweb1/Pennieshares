<?php

require_once __DIR__ . '/email_functions.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/assets_functions.php';

function getUserByEmail($email) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function registerUser($fullname, $email, $username, $phone, $referral, $password) {
    global $pdo;

    // Validate referral code if provided
    if (!empty($referral) && !validatePartnerCode($referral)) {
        return false;
    }

    // Generate user's own partner code
    $partner_code = generatePartnerCode($username);

    $hash = password_hash($password, PASSWORD_BCRYPT);

    $stmt = $pdo->prepare("
        INSERT INTO users (fullname, email, username, phone, referral, stage,
                          partner_code, password)
        VALUES (:fullname, :email, :username, :phone, :referral, 
                :stage, :partner_code, :password)
    ");

    $success = $stmt->execute([
        ':fullname'      => $fullname,
        ':email'         => $email,
        ':username'      => $username,
        ':phone'         => $phone,
        ':referral'      => $referral,
        ':stage'		 => 1,
        ':partner_code'  => $partner_code,
        ':password'      => $hash
    ]);

    if ($success) {
        // Send email to user
        $user_data = [
            'username' => $username,
            'login_url' => 'https://yourdomain.com/login' // Replace with your actual login URL
        ];
        sendNotificationEmail('new_user_registration_user', $user_data, $email, 'Welcome to Pennieshares!');

        // Send email to admin
        $admin_data = [
            'username' => $username,
            'email' => $email
        ];
        sendNotificationEmail('new_user_registration_admin', $admin_data, 'nahjonah00@gmail.com', 'New User Registration');
    }

    return $success;
}

// Generate unique partner code
function generatePartnerCode($username) {
    do {
        // Get first 2 letters (lowercase, handle short usernames)
        $prefix = strtolower(substr($username, 0, 2));
        if (strlen($prefix) < 2) {
            $prefix = str_pad($prefix, 2, 'x');
        }
        
        // Generate 5 random digits
        $suffix = str_pad(mt_rand(0, 99999), 5, '0', STR_PAD_LEFT);
        
        $partner_code = $prefix . $suffix;
        
        // Check if code exists
        global $pdo;
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE partner_code = ?");
        $stmt->execute([$partner_code]);
    } while ($stmt->fetchColumn() > 0);

    return $partner_code;
}

// Validate referral partner code
function validatePartnerCode($partner_code) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE partner_code = ?");
    $stmt->execute([$partner_code]);
    return $stmt->fetchColumn() > 0;
}

// Add session security settings
function secureSession() {
    ini_set('session.cookie_httponly', 1);
    //ini_set('session.cookie_secure', 1); // Enable if using HTTPS
    ini_set('session.use_strict_mode', 1);
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Regenerate session ID periodically
    if (!isset($_SESSION['generated']) || $_SESSION['generated'] < (time() - 3600)) {
        session_regenerate_id(true);
        $_SESSION['generated'] = time();
    }
}

// Update loginUser function to prevent timing attacks
function loginUser($email, $password) {
    $user = getUserByEmail($email);
    if ($user) {
        if (password_verify($password, $user['password'])) {
            if (password_needs_rehash($user['password'], PASSWORD_BCRYPT)) {
                $newHash = password_hash($password, PASSWORD_BCRYPT);
                // Update password hash in database
            }
            return $user;
        }
    }
    // Use constant time comparison to prevent timing attacks
    password_verify('dummy', '$2y$10$dummyhash');
    return false;
}

function generateResetToken() {
    return bin2hex(random_bytes(16));
}

function setResetToken($userId, $token, $expires) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE users SET reset_token = :token, reset_expires = :expires WHERE id = :id");
    return $stmt->execute(['token' => $token, 'expires' => $expires, 'id' => $userId]);
}

function getUserByResetToken($token) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE reset_token = :token");
    $stmt->execute(['token' => $token]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function updatePassword($userId, $newPassword) {
    global $pdo;
    $hash = password_hash($newPassword, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("UPDATE users SET password = :password WHERE id = :id");
    return $stmt->execute(['password' => $hash, 'id' => $userId]);
}

function clearResetToken($userId) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE users SET reset_token = NULL, reset_expires = NULL WHERE id = :id");
    return $stmt->execute(['id' => $userId]);
}
// Function to check if user is authenticated
function check_auth() {
    if (!isset($_SESSION['user'])) {
        header("Location: login");
        exit;
    }
}
function verify_auth() {
    if (isset($_SESSION['user'])) {
        header("Location: dashboard");
        exit;
    }
}
function generateCsrfToken(){
	// Generate CSRF Token if not set
	if(!isset($_SESSION['csrf_token'])){
	$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
	}
}


function deleteUser($user_id){
	global $pdo;
	$stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
	if ($stmt->execute([$user_id])) {
	session_destroy(); // Logout user after deletion
	header("Location: register"); // Redirect to registration
	exit;
	} else {
	echo "Error deleting account.";
	}
}

function deleteUserAccount($pdo, $userId) {
    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("Error deleting user account: " . $e->getMessage());
        return false;
    }
}
// Function to validate CSRF Token
	function verifyCsrfToken($token) {
	if (!isset($_SESSION['csrf_token']) || $_SESSION['csrf_token'] !== $token) {
	die("CSRF validation failed.");
	}
	}

function creditUserWallet($userId, $amount, $description = 'Broker Credited You') {
    global $pdo;
    if (!is_numeric($amount) || $amount <= 0) {
        return false;
    }
    try {
        $stmt = $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance + :amount WHERE id = :id");
        $result = $stmt->execute([':amount' => $amount, ':id' => $userId]);
        
        if ($result) {
            // Log the transaction
            $logStmt = $pdo->prepare("INSERT INTO wallet_transactions (user_id, type, amount, description) VALUES (?, ?, ?, ?)");
            $logStmt->execute([$userId, 'credit', $amount, $description]);

            // Send email to user
            $user = getUserByIdOrName($pdo, $userId);
            $transaction_data = [
                'username' => $user['username'],
                'transaction_type' => 'Credit',
                'amount' => $amount,
                'description' => $description,
                'date' => date('Y-m-d H:i:s')
            ];
            sendNotificationEmail('wallet_transaction_user', $transaction_data, $user['email'], 'Wallet Credit Notification');
        }
        return $result;
    } catch (PDOException $e) {
        error_log("Error crediting user wallet: " . $e->getMessage());
        return false;
    }
}

function updateUserProfile($pdo, $userId, $fullname, $phone) {
    try {
        $stmt = $pdo->prepare("UPDATE users SET fullname = ?, phone = ? WHERE id = ?");
        $stmt->execute([$fullname, $phone, $userId]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("Error updating user profile: " . $e->getMessage());
        return false;
    }
}

function updateUserPassword($pdo, $userId, $oldPassword, $newPassword) {
    try {
        // Verify old password first
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $hashedPassword = $stmt->fetchColumn();

        if (!password_verify($oldPassword, $hashedPassword)) {
            return false; // Old password does not match
        }

        // Hash new password and update
        $newHashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$newHashedPassword, $userId]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("Error updating user password: " . $e->getMessage());
        return false;
    }
}

function getUserByIdOrName($pdo, $identifier) {
    if (is_numeric($identifier)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$identifier]);
    } else {
        // Try username first
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$identifier]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            return $user;
        }
        // If not found by username, try partner_code
        $stmt = $pdo->prepare("SELECT * FROM users WHERE partner_code = ?");
        $stmt->execute([$identifier]);
    }
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function transferWalletBalance($pdo, $senderId, $receiverId, $amount) {
    if (!is_numeric($amount) || $amount <= 0) {
        return ['success' => false, 'message' => "Invalid transfer amount."];
    }

    if ($senderId == $receiverId) {
        return ['success' => false, 'message' => "Cannot transfer to yourself."];
    }

    try {
        // Check sender's balance
        $stmt = $pdo->prepare("SELECT wallet_balance FROM users WHERE id = ?");
        $stmt->execute([$senderId]);
        $senderBalance = $stmt->fetchColumn();

        if ($senderBalance < $amount) {
            return ['success' => false, 'message' => "Insufficient funds."];
        }

        // Deduct from sender
        $stmt = $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?");
        $stmt->execute([$amount, $senderId]);
        
        // Log sender's transaction
        $logStmt = $pdo->prepare("INSERT INTO wallet_transactions (user_id, type, amount, description) VALUES (?, ?, ?, ?)");
        $receiverUser = getUserByIdOrName($pdo, $receiverId);
        $payoutDescription = 'Payout to ' . $receiverUser['username'] . '/' . $receiverUser['partner_code'];
        $logStmt->execute([$senderId, 'payout', -$amount, $payoutDescription]);

        // Add to receiver
        $stmt = $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?");
        $stmt->execute([$amount, $receiverId]);

        // Log receiver's transaction
        $logStmt->execute([$receiverId, 'transfer_in', $amount, 'Transfer from user ID ' . $senderId]);

        // Send email to sender
        $sender = getUserByIdOrName($pdo, $senderId);
        $sender_data = [
            'username' => $sender['username'],
            'transaction_type' => 'Transfer Out',
            'amount' => $amount,
            'description' => $payoutDescription,
            'date' => date('Y-m-d H:i:s')
        ];
        sendNotificationEmail('wallet_transaction_user', $sender_data, $sender['email'], 'Wallet Transfer Notification');

        // Send email to receiver
        $receiver = getUserByIdOrName($pdo, $receiverId);
        $receiver_data = [
            'username' => $receiver['username'],
            'transaction_type' => 'Transfer In',
            'amount' => $amount,
            'description' => 'Transfer from user ' . $sender['username'],
            'date' => date('Y-m-d H:i:s')
        ];
        sendNotificationEmail('wallet_transaction_user', $receiver_data, $receiver['email'], 'Wallet Transfer Notification');

        return ['success' => true, 'message' => "Transfer successful."];

    } catch (PDOException $e) {
        error_log("Wallet transfer failed: " . $e->getMessage());
        return ['success' => false, 'message' => "Database error during transfer."];
    }
}

function assignAdminRole($pdo, $userId) {
    try {
        $stmt = $pdo->prepare("UPDATE users SET is_admin = 1 WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("Error assigning admin role: " . $e->getMessage());
        return false;
    }
}

function getUserWalletBalance($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT wallet_balance FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetchColumn();
}

function debitUserWallet($pdo, $userId, $amount, $transactionDescription = '') {
    if (!is_numeric($amount) || $amount <= 0) {
        return false;
    }
    // Check sender's balance
    $stmt = $pdo->prepare("SELECT wallet_balance FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $currentBalance = $stmt->fetchColumn();

    if ($currentBalance < $amount) {
        return false; // Insufficient funds
    }

    // Deduct from wallet
    $stmt = $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?");
    $stmt->execute([$amount, $userId]);

    // Log the transaction
    $logStmt = $pdo->prepare("INSERT INTO wallet_transactions (user_id, type, amount, description) VALUES (?, ?, ?, ?)");
    $logStmt->execute([$userId, 'debit', -$amount, $transactionDescription]);

    // Send email to user
    $user = getUserByIdOrName($pdo, $userId);
    $transaction_data = [
        'username' => $user['username'],
        'transaction_type' => 'Debit',
        'amount' => $amount,
        'description' => $transactionDescription,
        'date' => date('Y-m-d H:i:s')
    ];
    sendNotificationEmail('wallet_transaction_user', $transaction_data, $user['email'], 'Wallet Debit Notification');

    return true;
}

function getPaginatedWalletTransactions($pdo, $userId, $limit, $offset, $type = null) {
    $sql = "SELECT * FROM wallet_transactions WHERE user_id = ?";
    $params = [$userId];

    if ($type && $type !== 'all') {
        // Handle special cases for 'payout' and 'credit' as they map to multiple types
        if ($type === 'payout') {
            $sql .= " AND (type = 'payout' OR type = 'transfer_out')";
        } elseif ($type === 'credit') {
            $sql .= " AND (type = 'credit' OR type = 'transfer_in')";
        } else {
            $sql .= " AND type = ?";
            $params[] = $type;
        }
    }

    $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getTotalWalletTransactionCount($pdo, $userId, $type = null) {
    $sql = "SELECT COUNT(*) FROM wallet_transactions WHERE user_id = ?";
    $params = [$userId];

    if ($type && $type !== 'all') {
        // Handle special cases for 'payout' and 'credit' as they map to multiple types
        if ($type === 'payout') {
            $sql .= " AND (type = 'payout' OR type = 'transfer_out')";
        } elseif ($type === 'credit') {
            $sql .= " AND (type = 'credit' OR type = 'transfer_in')";
        } else {
            $sql .= " AND type = ?";
            $params[] = $type;
        }
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

function findBrokerStatus($pdo, $identifier) {
    // Try to find by username
    $stmt = $pdo->prepare("SELECT is_admin FROM users WHERE username = ?");
    $stmt->execute([$identifier]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        return $user['is_admin'] == 1 ? 'certified_broker' : 'not_certified_broker';
    }

    // If not found by username, try by partner_code
    $stmt = $pdo->prepare("SELECT is_admin FROM users WHERE partner_code = ?");
    $stmt->execute([$identifier]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        return $user['is_admin'] == 1 ? 'certified_broker' : 'not_certified_broker';
    }

    return 'not_found';
}

function verifyUserAccount($pdo, $userId) {
    try {
        $stmt = $pdo->prepare("UPDATE users SET status = 2 WHERE id = ? AND status != 2");
        $stmt->execute([$userId]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("Error verifying user account: " . $e->getMessage());
        return false;
    }
}

function deleteExpiredOrCompletedAssets($pdo) {
    try {
        // First, select the assets to be deleted to count them
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM assets WHERE is_completed = 1 OR (expires_at IS NOT NULL AND expires_at < datetime('now'))");
        $stmt->execute();
        $count = $stmt->fetchColumn();

        // If there are assets to delete, proceed with deletion
        if ($count > 0) {
            $deleteStmt = $pdo->prepare("DELETE FROM assets WHERE is_completed = 1 OR (expires_at IS NOT NULL AND expires_at < datetime('now'))");
            $deleteStmt->execute();
            return $deleteStmt->rowCount();
        }
        return 0; // No assets were deleted
    } catch (PDOException $e) {
        error_log("Error deleting expired or completed assets: " . $e->getMessage());
        return false;
    }
}

function deletePaymentProof($pdo, $proofId) {
    try {
        // Get the file path before deleting the record
        $stmt = $pdo->prepare("SELECT file_path FROM payment_proofs WHERE id = ?");
        $stmt->execute([$proofId]);
        $filePath = $stmt->fetchColumn();

        // Delete the record from the database
        $deleteStmt = $pdo->prepare("DELETE FROM payment_proofs WHERE id = ?");
        $deleteStmt->execute([$proofId]);

        // If the record was deleted and a file path exists, delete the file
        if ($deleteStmt->rowCount() > 0 && $filePath && file_exists($filePath)) {
            unlink($filePath);
        }

        return true;
    } catch (PDOException $e) {
        error_log("Error deleting payment proof: " . $e->getMessage());
        return false;
    }
}

function getPaginatedUsers($pdo, $limit, $offset, $searchQuery = '') {
    $sql = "SELECT * FROM users";
    $params = [];

    if (!empty($searchQuery)) {
        $sql .= " WHERE username LIKE ?";
        $params[] = '%' . $searchQuery . '%';
    }

    $sql .= " ORDER BY id ASC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getTotalUserCount($pdo, $searchQuery = '') {
    $sql = "SELECT COUNT(*) FROM users";
    $params = [];

    if (!empty($searchQuery)) {
        $sql .= " WHERE username LIKE ?";
        $params[] = '%' . $searchQuery . '%';
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

function checkAndSendDailyLoginEmail($pdo, $userId) {
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT last_login_email_sent FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $lastSentDate = $stmt->fetchColumn();

    if ($lastSentDate != $today) {
        $user = getUserByIdOrName($pdo, $userId);
        $data = ['username' => $user['username']];
        sendNotificationEmail('first_daily_login_user', $data, $user['email'], 'Daily Login');

        $updateStmt = $pdo->prepare("UPDATE users SET last_login_email_sent = ? WHERE id = ?");
        $updateStmt->execute([$today, $userId]);
    }
}

?>