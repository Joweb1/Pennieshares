<?php
require_once __DIR__ . '/src/init.php';
require_once __DIR__ . '/src/functions.php';
require_once __DIR__ . '/src/email_functions.php';

$paystackSecretKey = $_ENV['PAYSTACK_SECRET_KEY'];

$reference = isset($_GET['reference']) ? $_GET['reference'] : '';

if (!$reference) {
    die('No reference supplied');
}

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, 'https://api.paystack.co/transaction/verify/' . rawurlencode($reference));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $paystackSecretKey,
    'Cache-Control: no-cache',
]);

$result = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);

if ($err) {
    // Handle cURL error
    die('cURL Error: ' . $err);
}

$response = json_decode($result, true);

$adminEmail = $_ENV['MAIL_USERNAME'];

if ($response['status'] && $response['data']['status'] === 'success') {
    // Payment was successful
    $customer_email = $response['data']['customer']['email'];
    
    // Fetch user from database
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$customer_email]);
    $user = $stmt->fetch();

    if ($user) {
        // Update user status to 'paid' (status = 2)
        $updateStmt = $pdo->prepare("UPDATE users SET status = 2 WHERE id = ?");
        $updateStmt->execute([$user['id']]);

        // Send success emails
        $user_data = [
            'username' => $user['username'],
            'email' => $user['email'],
            'payment_date' => date('Y-m-d H:i:s'),
            'amount' => $response['data']['amount'] / 100,
            'currency' => $response['data']['currency'],
            'reference' => $reference,
        ];

        // Send email to user
        sendNotificationEmail('payment_success_user', $user_data, $user['email'], 'Payment Successful');
        sendNotificationEmail('account_verified_user', $user_data, $user['email'], 'Account Verified Successfully');

        // Send email to admin
        sendNotificationEmail('payment_success_admin', $user_data, $adminEmail, 'New Successful Payment');
    }

    // Redirect to a success page
    header('Location: payment_success');
    exit;
} else {
    // Payment was not successful
    $customer_email = $response['data']['customer']['email'] ?? 'N/A';
    
    // Fetch user from database
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$customer_email]);
    $user = $stmt->fetch();

    if ($user) {
        $user_data = [
            'username' => $user['username'],
            'email' => $user['email'],
            'payment_date' => date('Y-m-d H:i:s'),
            'amount' => ($response['data']['amount'] ?? 0) / 100,
            'currency' => $response['data']['currency'] ?? 'N/A',
            'reference' => $reference,
            'error_message' => $response['data']['gateway_response'] ?? 'N/A',
        ];

        // Send email to user
        sendNotificationEmail('payment_failure_user', $user_data, $user['email'], 'Payment Failed');

        // Send email to admin
        sendNotificationEmail('payment_failure_admin', $user_data, $adminEmail, 'A Payment Failed');
    }
    
    // Redirect to a failure page
    header('Location: payment_failure');
    exit;
}
