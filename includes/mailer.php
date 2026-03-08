<?php
// FILE: /consignxAnti/includes/mailer.php

// Required PHPMailer files
require_once __DIR__ . '/../vendor/src/Exception.php';
require_once __DIR__ . '/../vendor/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'config.php';

/**
 * Sends a transactional email using Gmail SMTP
 * 
 * @param string $to Email address
 * @param string $subject Email subject
 * @param string $htmlBody HTML content of the email
 * @return bool True on success, false on failure
 */
function send_email($to, $subject, $htmlBody)
{
    // Gmail SMTP sender
    $sender_email = 'sufyanfortech810@gmail.com'; // Gmail address
    $sender_name = 'ConsignX Team';

    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $sender_email;
        $mail->Password = 'rjrsdxzhsjsgrbnp'; // <-- Replace with your Gmail App Password later
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // TLS encryption
        $mail->Port = 587; // TLS port for Gmail

        // Recipients
        $mail->setFrom($sender_email, $sender_name);
        $mail->addAddress($to);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '</p>'], "\n", $htmlBody));

        return $mail->send();
    } catch (Exception $e) {
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Sends a welcome email to a new customer
 */
function send_customer_welcome_email($to, $name, $password, $tracking_number)
{
    $subject = "Welcome to ConsignX - Track Your Shipment ($tracking_number)";
    $html = "
        <h2>Hello $name,</h2>
        <p>A new shipment has been created for you with tracking number: <strong>$tracking_number</strong>.</p>
        <p>An account was automatically created for you to manage your deliveries.</p>
        <p>
           <strong>Login URL:</strong> " . APP_URL . "/auth/login.php<br>
           <strong>Email:</strong> $to<br>
           <strong>Temporary Password:</strong> $password
        </p>
        <p>Please log in and change your password immediately.</p>
        <br>
        <p>Thank you,<br>The ConsignX Team</p>
    ";
    return send_email($to, $subject, $html);
}

/**
 * Sends a welcome email to a new agent
 */
function send_agent_welcome_email($to, $company, $status)
{
    $subject = "Welcome to the ConsignX Network - $company";
    $html = "
        <h2>Welcome aboard, $company!</h2>
        <p>Your agent account has been successfully registered.</p>
        <p>Current Status: <strong>" . strtoupper($status) . "</strong></p>
        <p>You can now log in at " . APP_URL . "/auth/login.php and start managing shipments.</p>
        <br>
        <p>Best Regards,<br>ConsignX Administrator</p>
    ";
    return send_email($to, $subject, $html);
}
?>