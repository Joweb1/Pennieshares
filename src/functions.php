<?php

require_once __DIR__ . '/../config/database.php';

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

    return $stmt->execute([
        ':fullname'      => $fullname,
        ':email'         => $email,
        ':username'      => $username,
        ':phone'         => $phone,
        ':referral'      => $referral,
        ':stage'		 => 1,
        ':partner_code'  => $partner_code,
        ':password'      => $hash
    ]);
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
// Function to validate CSRF Token
	function verifyCsrfToken($token) {
	if (!isset($_SESSION['csrf_token']) || $_SESSION['csrf_token'] !== $token) {
	die("CSRF validation failed.");
	}
	}

?>