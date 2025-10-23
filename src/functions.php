<?php

require_once __DIR__ . '/email_functions.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/assets_functions.php';

function getUserByEmail($pdo, $email) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT *, is_broker, is_verified FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function registerUser($pdo, $fullname, $email, $username, $phone, $referral, $password) {
    global $pdo;

    // Validate referral code if provided
    if (!empty($referral) && !validatePartnerCode($pdo, $referral)) {
        return false;
    }

    // Generate user's own partner code
    $partner_code = generatePartnerCode($username);

    $hash = password_hash($password, PASSWORD_BCRYPT);

    $stmt = $pdo->prepare(
        "INSERT INTO users (fullname, email, username, phone, referral, stage," .
        "                  partner_code, password, is_verified)" .
        " VALUES (:fullname, :email, :username, :phone, :referral, " .
        "                :stage, :partner_code, :password, :is_verified)"
    );

    $success = $stmt->execute([
        ':fullname'      => $fullname,
        ':email'         => $email,
        ':username'      => $username,
        ':phone'         => $phone,
        ':referral'      => $referral,
        ':stage'		 => 1,
        ':partner_code'  => $partner_code,
        ':password'      => $hash,
        ':is_verified'   => 0 // Set to 0 for unverified
    ]);

    if ($success) {
        $user_id = $pdo->lastInsertId();
        $otp = generateAndStoreOtp($pdo, $user_id); // Generate and store OTP

        if ($otp) {
            // Send OTP email
            $otp_data = [
                'username' => $username,
                'otp_code' => $otp
            ];
            sendNotificationEmail('otp_email', $otp_data, $email, 'Verify Your Pennieshares Account');

            // Store email in session for verification page
            $_SESSION['registration_email_for_otp'] = $email;
            return true; // Indicate success for redirection
        } else {
            // Handle OTP generation/storage failure
            error_log("Failed to generate/store OTP for user: " . $email);
            return false;
        }
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
function validatePartnerCode($pdo, $partner_code) {
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
function loginUser($pdo, $email, $password) {
    $user = getUserByEmail($pdo, $email);
    if ($user) {
        if (password_verify($password, $user['password'])) {
            if ($user['is_verified'] == 0) {
                $_SESSION['registration_email_for_otp'] = $user['email']; // Store email for OTP page
                header("Location: verify_registration_otp");
                exit();
            }
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

function generateAndStoreOtp($pdo, $userId) {
    $otp = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $expiresAt = date('Y-m-d H:i:s', strtotime('+1 minute')); // OTP expires in 1 minute

    $stmt = $pdo->prepare("UPDATE users SET otp_code = ?, otp_expires_at = ? WHERE id = ?");
    if ($stmt->execute([$otp, $expiresAt, $userId])) {
        return $otp;
    }
    return false;
}

function verifyOtp($pdo, $userId, $otp) {
    $stmt = $pdo->prepare("SELECT otp_code, otp_expires_at FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && $user['otp_code'] === $otp && strtotime($user['otp_expires_at']) > time()) {
        // OTP is valid and not expired, clear it
        $clearStmt = $pdo->prepare("UPDATE users SET otp_code = NULL, otp_expires_at = NULL WHERE id = ?");
        $clearStmt->execute([$userId]);
        return true;
    }
    return false;
}

function resetUserPassword($pdo, $userId, $newPassword) {
    $hash = password_hash($newPassword, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    return $stmt->execute([$hash, $userId]);
}

// Function to check if user is authenticated
function check_auth() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['user'])) {
        header("Location: login");
        exit;
    }
    if (isset($_SESSION['user']) && $_SESSION['user']['status'] === 1 ){
        header("Location: payment");
    }

    // Check for session expiration (1 hour inactivity)
    $session_lifetime = 3600; // 1 hour in seconds
    if (isset($_SESSION['_last_activity']) && (time() - $_SESSION['_last_activity'] > $session_lifetime)) {
        session_unset();     // Unset all of the session variables
        session_destroy();   // Destroy the session
        header("Location: login?session_expired=true");
        exit;
    }

    // Update last activity time
    $_SESSION['_last_activity'] = time();

    // Check if user is verified
    if (($_SESSION['user']['is_verified'] ?? 0) == 0) {
        $_SESSION['registration_email_for_otp'] = $_SESSION['user']['email']; // Store email for OTP page
        header("Location: verify_registration_otp");
        exit;
    }
    if (($_SESSION['user']['status'] ?? 0) == 0) {
        // Redirect to payment page
        header("Location: payment");
        exit;
    }
}
function verify_auth() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (isset($_SESSION['user'])) {
        header("Location: wallet");
        exit;
    }
}
function generateCsrfToken(){
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

function creditUserWallet($userId, $amount, $description = 'Broker Credited You', $assetDetails = null) {
    global $pdo;
    if (!is_numeric($amount) || $amount <= 0) {
        return false;
    }
    try {
        $stmt = $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance + :amount WHERE id = :id");
        $result = $stmt->execute(['amount' => $amount, 'id' => $userId]);
        
        if ($result) {
            // Log the transaction
            $logStmt = $pdo->prepare("INSERT INTO wallet_transactions (user_id, type, amount, description) VALUES (?, ?, ?, ?)");
            $logStmt->execute([$userId, 'credit', $amount, $description]);

            // Send email to user only if not in CLI context
            if ($description !== 'Asset Profit') {
                $user = getUserByIdOrName($pdo, $userId);
                $transaction_data = [
                    'username' => $user['username'],
                    'transaction_type' => 'Credit',
                    'amount' => $amount,
                    'description' => $description,
                    'date' => date('Y-m-d H:i:s'),
                    'asset_name' => $assetDetails ? $assetDetails['name'] : null,
                    'asset_image' => $assetDetails ? $assetDetails['image_link'] : null
                ];
                sendNotificationEmail('wallet_transaction_user', $transaction_data, $user['email'], 'Wallet Credit Notification');

                // Send push notification for credit
                $payload = [
                    'title' => 'Wallet Credited!',
                    'body' => 'Your wallet has been credited with SV' . number_format($amount, 4) . '. Reason: ' . $description,
                    'icon' => 'assets/images/logo.png',
                ];
                sendPushNotification($userId, $payload);
            }
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
#        return $stmt->rowCount() > 0;
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

function setTransactionPin($pdo, $userId, $newPin, $password) {
    try {
        // Verify user's main password first
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $hashedPassword = $stmt->fetchColumn();

        if (!password_verify($password, $hashedPassword)) {
            return ['success' => false, 'message' => 'Incorrect password.'];
        }

        // Hash new PIN and update
        $newHashedPin = password_hash($newPin, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE users SET transaction_pin = ? WHERE id = ?");
        $stmt->execute([$newHashedPin, $userId]);
        return ['success' => true, 'message' => 'Transaction PIN set successfully.'];
    } catch (PDOException $e) {
        error_log("Error setting transaction PIN: " . $e->getMessage());
        return ['success' => false, 'message' => 'A database error occurred.'];
    }
}

function verifyTransactionPin($pdo, $userId, $pin) {
    try {
        $stmt = $pdo->prepare("SELECT transaction_pin FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $hashedPin = $stmt->fetchColumn();

        if (!$hashedPin) {
            return false; // No PIN set
        }

        return password_verify($pin, $hashedPin);
    } catch (PDOException $e) {
        error_log("Error verifying transaction PIN: " . $e->getMessage());
        return false;
    }
}

function getUserByIdOrName($pdo, $identifier) {
    if (is_numeric($identifier)) {
        $stmt = $pdo->prepare("SELECT *, is_broker, is_verified FROM users WHERE id = ?");
        $stmt->execute([$identifier]);
    } else {
        // Try username first
        $stmt = $pdo->prepare("SELECT *, is_broker, is_verified FROM users WHERE username = ?");
        $stmt->execute([$identifier]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            return $user;
        }
        // If not found by username, try partner_code
        $stmt = $pdo->prepare("SELECT *, is_broker, is_verified FROM users WHERE partner_code = ?");
        $stmt->execute([$identifier]);
    }
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function transferWalletBalance($pdo, $senderId, $receiverId, $amount, $pin) {
    if (!is_numeric($amount) || $amount <= 0) {
        return ['success' => false, 'message' => "Invalid transfer amount."];
    }

    if ($senderId == $receiverId) {
        return ['success' => false, 'message' => "Cannot transfer to yourself."];
    }

    // Verify transaction PIN
    if (!verifyTransactionPin($pdo, $senderId, $pin)) {
        return ['success' => false, 'message' => "Invalid transaction PIN."];
    }

    try {
        // Check sender's role
        $senderUser = getUserByIdOrName($pdo, $senderId);
        $isSenderBroker = $senderUser['is_broker'] == 1;

        // Check receiver's role if sender is not a broker
        if (!$isSenderBroker) {
            $receiverUser = getUserByIdOrName($pdo, $receiverId);
            if ($receiverUser['is_broker'] != 1) {
                return ['success' => false, 'message' => "You can only transfer to a broker."];
            }
        }

        

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
        $sender = getUserByIdOrName($pdo, $senderId);
        $receiverDescription = 'Transfer from user ' . $sender['username'];
        if ($isSenderBroker) {
            $receiverDescription = 'Credit from Broker: ' . $sender['username'];
        }
        $logStmt->execute([$receiverId, 'transfer_in', $amount, $receiverDescription]);

        // Send email to sender
        $sender_data = [
            'username' => $sender['username'],
            'transaction_type' => 'Transfer Out',
            'amount' => $amount,
            'description' => $payoutDescription,
            'date' => date('Y-m-d H:i:s')
        ];
        sendNotificationEmail('wallet_transaction_user', $sender_data, $sender['email'], 'Wallet Transfer Notification');
        // Send push notification to sender
        $sender_payload = [
            'title' => 'Funds Transferred!',
            'body' => 'You have successfully transferred SV' . number_format($amount, 2) . ' to ' . $receiverUser['username'] . '.',
            'icon' => 'assets/images/logo.png',
        ];
        sendPushNotification($senderId, $sender_payload);

        // Send email to receiver
        $receiver = getUserByIdOrName($pdo, $receiverId);
        $receiver_data = [
            'username' => $receiver['username'],
            'transaction_type' => 'Transfer In',
            'amount' => $amount,
            'description' => $receiverDescription,
            'date' => date('Y-m-d H:i:s')
        ];
        if ($isSenderBroker) {
            send_broker_credit_email($receiver['email'], $receiver['username'], $amount, $sender['username']);
        } else {
            sendNotificationEmail('wallet_transaction_user', $receiver_data, $receiver['email'], 'Wallet Transfer Notification');
        }
        // Send push notification to receiver
        $receiver_payload = [
            'title' => 'Funds Received!',
            'body' => 'You have received SV' . number_format($amount, 2) . ' from ' . $sender['username'] . '.',
            'icon' => 'assets/images/logo.png',
        ];
        sendPushNotification($receiverId, $receiver_payload);

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

function assignBrokerRole($pdo, $userId) {
    try {
        $stmt = $pdo->prepare("UPDATE users SET is_broker = 1 WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("Error assigning broker role: " . $e->getMessage());
        return false;
    }
}

function toggleUserEarningsPause($pdo, $userId, $pauseStatus) {
    try {
        $stmt = $pdo->prepare("UPDATE users SET earnings_paused = ? WHERE id = ?");
        $stmt->execute([$pauseStatus ? 1 : 0, $userId]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("Error toggling user earnings pause status: " . $e->getMessage());
        return false;
    }
}

function getUserWalletBalance($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT wallet_balance FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetchColumn();
}

function getTotalUsersWalletBalance($pdo) {
    $stmt = $pdo->prepare("SELECT SUM(wallet_balance) FROM users");
    $stmt->execute();
    return $stmt->fetchColumn() ?? 0;
}

function getTotalAssetsCost($pdo) {
    $stmt = $pdo->prepare("SELECT SUM(at.price) FROM assets a JOIN asset_types at ON a.asset_type_id = at.id");
    $stmt->execute();
    return $stmt->fetchColumn() ?? 0;
}

function getTotalUsersProfit($pdo) {
    $stmt = $pdo->prepare("SELECT SUM(total_return) FROM users");
    $stmt->execute();
    return $stmt->fetchColumn() ?? 0;
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

    // Send push notification for debit
    $payload = [
        'title' => 'Wallet Debited!',
        'body' => 'Your wallet has been debited by SV' . number_format($amount, 2) . '. Reason: ' . $transactionDescription,
        'icon' => 'assets/images/logo.png',
    ];
    sendPushNotification($userId, $payload);

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
    $stmt = $pdo->prepare("SELECT is_broker FROM users WHERE username = ?");
    $stmt->execute([$identifier]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        return $user['is_broker'] == 1 ? 'certified_broker' : 'not_certified_broker';
    }

    // If not found by username, try by partner_code
    $stmt = $pdo->prepare("SELECT is_broker FROM users WHERE partner_code = ?");
    $stmt->execute([$identifier]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        return $user['is_broker'] == 1 ? 'certified_broker' : 'not_certified_broker';
    }

    return 'not_found';
}

function verifyUserAccount($pdo, $userId) {
    try {
        $stmt = $pdo->prepare("UPDATE users SET status = 2 WHERE id = ? AND status != 2");
        $success = $stmt->execute([$userId]);

        if ($success && $stmt->rowCount() > 0) {
            $user = getUserByIdOrName($pdo, $userId);
            if ($user) {
                // Send email to user
                $user_data = [
                    'username' => $user['username']
                ];
                sendNotificationEmail('account_verified_user', $user_data, $user['email'], 'Congratulations! Your Pennieshares Account is Verified!');

                // Send email to admin
                $admin_data = [
                    'username' => $user['username'],
                    'email' => $user['email']
                ];
                sendNotificationEmail('account_verified_admin', $admin_data, 'penniepoint@gmail.com', 'New User Account Verified');
            }
            return true;
        }
        return false;
    } catch (PDOException $e) {
        error_log("Error verifying user account: " . $e->getMessage());
        return false;
    }
}

function verifyUserEmail($pdo, $userId) {
    try {
        $stmt = $pdo->prepare("UPDATE users SET is_verified = 1 WHERE id = ? AND is_verified != 1");
        $success = $stmt->execute([$userId]);

        if ($success && $stmt->rowCount() > 0) {
            $user = getUserByIdOrName($pdo, $userId);
            if ($user) {
                // Send email to user
                $user_data = [
                    'username' => $user['username']
                ];
                sendNotificationEmail('email_verified_user', $user_data, $user['email'], 'Congratulations! Your Email has been Verified!');
          
            }
            return true;
        }
        return false;
    } catch (PDOException $e) {
        error_log("Error verifying user email: " . $e->getMessage());
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

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

function sendPushNotification($userId, $payload) {
    global $pdo;

    // Send Web Push Notifications
    $stmt = $pdo->prepare("SELECT * FROM push_subscriptions WHERE user_id = ?");
    $stmt->execute([$userId]);
    $webSubscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $config = require __DIR__ . '/../config/push.php';
    $auth = [
        'VAPID' => [
            'subject' => $config['vapid']['subject'],
            'publicKey' => $config['vapid']['publicKey'],
            'privateKey' => $config['vapid']['privateKey'],
        ],
    ];

    $webPush = new WebPush($auth);

    foreach ($webSubscriptions as $sub) {
        $subscription = Subscription::create([
            'endpoint' => $sub['endpoint'],
            'publicKey' => $sub['p256dh'],
            'authToken' => $sub['auth'],
        ]);
        $webPush->queueNotification($subscription, json_encode($payload));
    }

    foreach ($webPush->flush() as $report) {
        $endpoint = $report->getRequest()->getUri()->__toString();

        if ($report->isSuccess()) {
            error_log("[v] Web Push: Message sent successfully for subscription {\$endpoint}.");
        } else {
            error_log("[x] Web Push: Message failed to sent for subscription {\$endpoint}: {\$report->getReason()}");
        }
    }

    // Send Expo Push Notifications
    $stmt = $pdo->prepare("SELECT token FROM expo_push_tokens WHERE user_id = ?");
    $stmt->execute([$userId]);
    $expoTokens = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($expoTokens)) {
        $expoPushUrl = 'https://exp.host/--/api/v2/push/send';
        $headers = [
            'Accept: application/json',
            'Accept-Encoding: gzip, deflate',
            'Content-Type: application/json',
        ];

        foreach ($expoTokens as $token) {
            $expoPayload = [
                'to' => $token,
                'title' => $payload['title'] ?? '',
                'body' => $payload['body'] ?? '',
                'data' => $payload['data'] ?? [],
                'sound' => 'default',
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $expoPushUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($expoPayload));

            $response = curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($response === false) {
                error_log("[x] Expo Push: cURL error for token {\$token}: {\$error}");
            } elseif ($httpcode !== 200) {
                error_log("[x] Expo Push: HTTP error {\$httpcode} for token {\$token}: {\$response}");
            } else {
                error_log("[v] Expo Push: Message sent successfully for token {\$token}. Response: {\$response}");
            }
        }
    }
}

function processPendingProfits($pdo) {
    try {
        $now = date('Y-m-d H:i:s');
        $stmt = $pdo->prepare("SELECT * FROM pending_profits WHERE is_credited = 0 AND credit_at <= :now");
        $stmt->execute(['now' => $now]);
        $pendingProfits = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($pendingProfits as $profit) {
            // Check if the receiving asset is still active
            $assetStatusStmt = $pdo->prepare("
                SELECT is_completed, is_manually_expired, expires_at 
                FROM assets 
                WHERE id = ?
            ");
            $assetStatusStmt->execute([$profit['receiving_asset_id']]);
            $assetStatus = $assetStatusStmt->fetch(PDO::FETCH_ASSOC);

            $is_expired = ($assetStatus['expires_at'] && $assetStatus['expires_at'] < $now) || $assetStatus['is_manually_expired'] == 1;

            if ($assetStatus && $assetStatus['is_completed'] == 0 && !$is_expired) {
                // Asset is still active, proceed with crediting

                // Get user details to check earnings_paused status
                $user = getUserByIdOrName($pdo, $profit['user_id']);

                if ($user && $user['earnings_paused'] == 1) {
                    // If earnings are paused, redirect to reservation fund
                    $pdo->prepare("UPDATE company_funds SET total_reservation_fund = total_reservation_fund + ? WHERE id = 1")->execute([$profit['fractional_amount']]);
                    // Log the event
                    $logStmt = $pdo->prepare("INSERT INTO payouts (receiving_asset_id, triggering_asset_id, company_fund_type, amount, payout_type, created_at) VALUES (?, ?, ?, ?, ?, ?)");
                    $logStmt->execute([$profit['receiving_asset_id'], 0, 'reservation_fund', $profit['fractional_amount'], 'paused_earnings', date('Y-m-d H:i:s')]);
                    error_log("User #{$profit['user_id']} earnings paused. Payout redirected to reservation fund.");
                } else {
                    // Get asset details
                    $assetStmt = $pdo->prepare("SELECT at.name, at.image_link FROM assets a JOIN asset_types at ON a.asset_type_id = at.id WHERE a.id = ?");
                    $assetStmt->execute([$profit['receiving_asset_id']]);
                    $assetDetails = $assetStmt->fetch(PDO::FETCH_ASSOC);

                    // Credit user wallet
                    $credit_success = creditUserWallet($profit['user_id'], $profit['fractional_amount'], 'Asset Profit', $assetDetails);

                    if ($credit_success) {
                        // Also update total_return when profit is actually credited
                        $updateTotalReturnStmt = $pdo->prepare("UPDATE users SET total_return = total_return + ? WHERE id = ?");
                        $updateTotalReturnStmt->execute([$profit['fractional_amount'], $profit['user_id']]);

                        // Send push notification
                        $payload = [
                            'title' => 'Profit Credited!',
                            'body' => 'You have received a profit of ' . number_format($profit['fractional_amount'], 4) . ' from your asset: ' . $assetDetails['name'],
                            'icon' => 'assets/images/logo.png',
                        ];
                        sendPushNotification($profit['user_id'], $payload);
                    }
                }
                // Mark as credited and delete pending profit regardless of where it went
                $deleteStmt = $pdo->prepare("DELETE FROM pending_profits WHERE id = ?");
                $deleteStmt->execute([$profit['id']]);
            } else {
                // Asset is completed or expired, so we just delete the pending profit
                $deleteStmt = $pdo->prepare("DELETE FROM pending_profits WHERE id = ?");
                $deleteStmt->execute([$profit['id']]);
                error_log("Pending profit for asset #{$profit['receiving_asset_id']} was not credited because the asset is completed or expired.");
            }
        }
    } catch (Exception $e) {
        error_log("Error during profit processing: " . $e->getMessage());
    }
}

function getPaginatedPendingProfits($pdo, $limit, $offset, $searchQuery = '') {
    $sql = "SELECT pp.*, u.username FROM pending_profits pp JOIN users u ON pp.user_id = u.id";
    $params = [];

    if (!empty($searchQuery)) {
        $sql .= " WHERE u.username LIKE ? OR pp.payout_type LIKE ?";
        $params[] = '%' . $searchQuery . '%';
        $params[] = '%' . $searchQuery . '%';
    }

    $sql .= " ORDER BY pp.credit_at ASC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getTotalPendingProfitsCount($pdo, $searchQuery = '') {
    $sql = "SELECT COUNT(*) FROM pending_profits pp JOIN users u ON pp.user_id = u.id";
    $params = [];

    if (!empty($searchQuery)) {
        $sql .= " WHERE u.username LIKE ? OR pp.payout_type LIKE ?";
        $params[] = '%' . $searchQuery . '%';
        $params[] = '%' . $searchQuery . '%';
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

function getTotalPendingProfitsSum($pdo, $searchQuery = '') {
    $sql = "SELECT SUM(fractional_amount) FROM pending_profits pp JOIN users u ON pp.user_id = u.id";
    $params = [];

    if (!empty($searchQuery)) {
        $sql .= " WHERE u.username LIKE ? OR pp.payout_type LIKE ?";
        $params[] = '%' . $searchQuery . '%';
        $params[] = '%' . $searchQuery . '%';
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn() ?? 0;
}

function deletePaymentProofForUser($pdo, $userId) {
    try {
        // Get the file path before deleting the record
        $stmt = $pdo->prepare("SELECT file_path FROM payment_proofs WHERE user_id = ?");
        $stmt->execute([$userId]);
        $filePath = $stmt->fetchColumn();

        // Delete the record from the database
        $deleteStmt = $pdo->prepare("DELETE FROM payment_proofs WHERE user_id = ?");
        $deleteStmt->execute([$userId]);

        // If the record was deleted and a file path exists, delete the file
        if ($deleteStmt->rowCount() > 0 && $filePath) {
            $absoluteFilePath = __DIR__ . '/../' . $filePath; // Construct absolute path from project root
            if (file_exists($absoluteFilePath)) {
                unlink($absoluteFilePath);
            }
        }

        return true;
    } catch (PDOException $e) {
        error_log("Error deleting payment proof for user {$userId}: " . $e->getMessage());
        return false;
    }
}

function getBrokerReferralStats($pdo, $brokerId) {
    $stats = [
        'total_referred_users' => 0,
        'total_referral_bonus' => 0,
        'total_assets_of_referred_users' => 0,
    ];

    // Get the broker's partner code
    $stmt = $pdo->prepare("SELECT partner_code FROM users WHERE id = ?");
    $stmt->execute([$brokerId]);
    $partnerCode = $stmt->fetchColumn();

    if (!$partnerCode) {
        return $stats;
    }

    // 1. Get total referred users
    $stmt = $pdo->prepare("SELECT id FROM users WHERE referral = ?");
    $stmt->execute([$partnerCode]);
    $referredUsers = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $stats['total_referred_users'] = count($referredUsers);

    // 2. Get total referral bonus earned
    $stmt = $pdo->prepare("SELECT SUM(amount) FROM wallet_transactions WHERE user_id = ? AND type = 'asset_partner_bonus'");
    $stmt->execute([$brokerId]);
    $stats['total_referral_bonus'] = $stmt->fetchColumn() ?? 0;

    // 3. Get total assets of referred users
    if (!empty($referredUsers)) {
        $placeholders = rtrim(str_repeat('?,', count($referredUsers)), ',');
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM assets WHERE user_id IN ($placeholders)");
        $stmt->execute($referredUsers);
        $stats['total_assets_of_referred_users'] = $stmt->fetchColumn() ?? 0;
    }

    return $stats;
}

function unassignBrokerRole($pdo, $userId) {
    try {
        $stmt = $pdo->prepare("UPDATE users SET is_broker = 0 WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("Error unassigning broker role: " . $e->getMessage());
        return false;
    }
}

?>
