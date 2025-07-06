<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

function sendEmail($to, $subject, $body) {
    $adminEmail = 'nahjonah00@gmail.com';
    $password = 'xvwq kydb wilk gbir';

    $mail = new PHPMailer(true);

    try {
        //Server settings
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
            $template = str_replace("{{{$key}}}", $value, $template);
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

    sendEmail($to, $subject, $emailContent);
}
?>