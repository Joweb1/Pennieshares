<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

function sendEmail($to, $subject, $body) {
    $adminEmail = $_ENV['MAIL_USERNAME'] ?? null;
    $password = $_ENV['MAIL_PASSWORD'] ?? null;

    if (empty($adminEmail) || empty($password)) {
        error_log("Email credentials (MAIL_USERNAME, MAIL_PASSWORD) are not set in the environment variables.");
        return false;
    }

    $mail = new PHPMailer(true);

    try {
        //Server settings
        // $mail->SMTPDebug = 2; // Enable verbose debug output for development
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $adminEmail;
        $mail->Password   = $password;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        //Recipients
        $mail->setFrom($adminEmail, 'Pennieshares');
        $mail->addAddress($to);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

function getEmailTemplate($templateName, $data) {
    $templatePath = __DIR__ . "/../email_templates/{$templateName}.html";
    if (file_exists($templatePath)) {
        $template = file_get_contents($templatePath);
        foreach ($data as $key => $value) {
            $template = str_replace("{{{$key}}}", $value ?? '', $template);
        }
        return $template;
    } else {
        return "<p>Email template not found.</p>";
    }
}

function sendNotificationEmail($template, $data, $to, $subject) {
    $genericTemplate = file_get_contents(__DIR__ . '/../email_templates/generic_template.html');
    $body = getEmailTemplate($template, $data);
    
    $emailContent = str_replace('{{header}}', $subject, $genericTemplate);
    $emailContent = str_replace('{{body}}', $body, $emailContent);

    return sendEmail($to, $subject, $emailContent);
}

function send_broker_credit_email($to, $username, $amount, $broker_name) {
    $subject = "You've Received Funds from a Broker";
    $data = [
        'username' => $username,
        'amount' => $amount,
        'broker_name' => $broker_name
    ];
    return sendNotificationEmail('broker_credit_user', $data, $to, $subject);
}

function send_admin_wallet_transaction_email($to, $admin_name, $user_name, $transaction_type, $amount) {
    $subject = "Admin Wallet Transaction Notification";
    $data = [
        'admin_name' => $admin_name,
        'user_name' => $user_name,
        'transaction_type' => $transaction_type,
        'amount' => $amount,
        'date' => date('Y-m-d H:i:s')
    ];
    return sendNotificationEmail('admin_wallet_transaction_admin', $data, $to, $subject);
}

function send_user_transfer_email($to, $sender_name, $receiver_name, $amount) {
    $subject = "User Transfer Notification";
    $data = [
        'sender_name' => $sender_name,
        'receiver_name' => $receiver_name,
        'amount' => $amount,
        'date' => date('Y-m-d H:i:s')
    ];
    return sendNotificationEmail('user_transfer_admin', $data, $to, $subject);
}

function sendBrokerApplicationEmails($pdo, $user, $formData) {
    $adminEmail = $_ENV['MAIL_USERNAME'];

    // Data for admin email
    $admin_data = array_merge($formData, [
        'username' => $user['username'],
        'email' => $user['email']
    ]);
    
    // Send to admin
    $admin_subject = "New Broker Application from " . $user['username'];
    $admin_body = getEmailTemplate('broker_application_admin', $admin_data);
    $admin_email_sent = sendEmail($adminEmail, $admin_subject, $admin_body);

    // Data for user email
    $user_data = [
        'username' => $user['username']
    ];

    // Send to user
    $user_subject = "Your Broker Application has been Received";
    $user_body = getEmailTemplate('broker_application_user', $user_data);
    $user_email_sent = sendEmail($user['email'], $user_subject, $user_body);

    return $admin_email_sent && $user_email_sent;
}
?>
