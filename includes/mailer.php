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
function send_shipment_notification_new($to, $name, $password, $tracking_number)
{
    $subject = "Welcome to ConsignX - Your Shipment is Ready! ($tracking_number)";
    $html = "
        <div style='font-family: Arial, sans-serif; color: #333; line-height: 1.6;'>
            <h2 style='color: #0d6efd;'>Hello $name,</h2>
            <p>Welcome to <strong>ConsignX</strong>! A new shipment has been created for you.</p>
            <div style='background: #f8f9fa; padding: 20px; border-radius: 10px; border: 1px solid #dee2e6;'>
                <p><strong>Tracking ID:</strong> <span style='font-size: 1.2rem; color: #0d6efd;'>$tracking_number</span></p>
                <hr>
                <p>An account has been created for you to track your shipments.</p>
                <p><strong>Login URL:</strong> " . APP_URL . "/auth/login.php</p>
                <p><strong>Username:</strong> $to</p>
                <p><strong>Password:</strong> $password</p>
            </div>
            <p style='margin-top: 20px;'>Please log in and change your password for security.</p>
            <p>Thank you for choosing ConsignX!</p>
            <p>Best Regards,<br><strong>The ConsignX Team</strong></p>
        </div>
    ";
    return send_email($to, $subject, $html);
}

/**
 * Sends a shipment notification to an existing customer
 */
function send_shipment_notification_existing($to, $name, $tracking_number)
{
    $subject = "Your Shipment is on its way! ($tracking_number)";
    $html = "
        <div style='font-family: Arial, sans-serif; color: #333; line-height: 1.6;'>
            <h2 style='color: #198754;'>Hello $name,</h2>
            <p>Thank you for choosing <strong>ConsignX</strong> again!</p>
            <div style='background: #f8f9fa; padding: 20px; border-radius: 10px; border: 1px solid #dee2e6;'>
                <p><strong>Tracking ID:</strong> <span style='font-size: 1.2rem; color: #198754;'>$tracking_number</span></p>
                <p>You can track your shipment status by logging into your account.</p>
            </div>
            <p style='margin-top: 20px;'>We appreciate your continued trust in our service.</p>
            <p>Best Regards,<br><strong>The ConsignX Team</strong></p>
        </div>
    ";
    return send_email($to, $subject, $html);
}

/**
 * Sends a welcome email to a new agent
 */
function send_agent_welcome_email($to, $company, $status,$password)
{
    $subject = "Welcome to the ConsignX Network - $company";
    $html = "
        <h2>Welcome aboard, $company, $password!</h2>
        <p>Your agent account has been successfully registered.</p>
        <p>Current Status: <strong>" . strtoupper($status) . "</strong></p>
        <p>You can now log in at " . APP_URL . "/auth/login.php and start managing shipments.</p>
        <br>
        <p>Best Regards,<br>ConsignX Administrator</p>
    ";
    return send_email($to, $subject, $html);
}
?>