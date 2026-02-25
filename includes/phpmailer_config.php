<?php
// PHPMailer Configuration
// Path: C:\xampp\htdocs\GitHub\Human-Resource-Management-System\includes\phpmailer_config.php

// Load PHPMailer classes via Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Send email using PHPMailer
 * 
 * @param string $to_email Recipient email
 * @param string $to_name Recipient name
 * @param string $subject Email subject
 * @param string $body HTML email body
 * @param string $alt_body Plain text alternative (optional)
 * @return bool True if sent successfully, false otherwise
 */
function sendEmailPHPMailer($to_email, $to_name, $subject, $body, $alt_body = '') {
    try {
        // Create a new PHPMailer instance
        $mail = new PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        
        // Enable debugging (0 = off, 1 = client messages, 2 = client and server messages)
        $mail->SMTPDebug = 0;
        
        // Recipients
        $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
        $mail->addAddress($to_email, $to_name);
        
        // Reply-to address
        $mail->addReplyTo(SMTP_FROM, SMTP_FROM_NAME);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        
        // Set plain text alternative
        if (!empty($alt_body)) {
            $mail->AltBody = $alt_body;
        }
        
        // Send email
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        // Log error for debugging
        error_log("PHPMailer Error: " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Send password reset verification code email
 * 
 * @param string $email Recipient email
 * @param string $code Verification code
 * @param string $name Recipient name
 * @param bool $isResend Whether this is a resend
 * @return bool True if sent successfully, false otherwise
 */
function sendVerificationCodeEmail($email, $code, $name, $isResend = false) {
    $subject = ($isResend ? 'New Password Reset Verification Code - ' : 'Password Reset Verification Code - ') . SITE_NAME;
    
    $body = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { padding: 30px; background: #f9f9f9; border-radius: 0 0 10px 10px; }
            .code { 
                display: inline-block; 
                padding: 15px 25px; 
                background: #e3f2fd; 
                color: #667eea; 
                font-size: 32px; 
                font-weight: bold; 
                letter-spacing: 5px; 
                border-radius: 5px; 
                margin: 20px 0; 
                border: 2px dashed #667eea;
            }
            .footer { 
                text-align: center; 
                padding: 20px; 
                color: #666; 
                font-size: 12px; 
                margin-top: 20px;
                border-top: 1px solid #eee; 
            }
            .warning { 
                background: #fff3cd; 
                border: 1px solid #ffc107; 
                color: #856404; 
                padding: 10px; 
                border-radius: 5px; 
                margin: 15px 0;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>" . SITE_NAME . " - Password Reset</h2>
            </div>
            <div class='content'>
                <h3>Hello " . htmlspecialchars($name) . ",</h3>
                " . ($isResend ? "<p>A new verification code has been generated for your password reset request.</p>" : "<p>You have requested to reset your password for " . SITE_NAME . ".</p>") . "
                <p>Your verification code is:</p>
                <div class='code'>" . $code . "</div>
                <p>Enter this 6-digit code in the password reset page to continue.</p>
                
                <div class='warning'>
                    <strong>⚠️ IMPORTANT:</strong>
                    <ul style='margin: 10px 0 0 20px;'>
                        <li>This code will expire in 15 minutes</li>
                        <li>Do not share this code with anyone</li>
                        <li>If you didn't request this, please ignore this email</li>
                    </ul>
                </div>
                
                <p>Best regards,<br>
                <strong>" . SITE_NAME . " Support Team</strong></p>
            </div>
            <div class='footer'>
                <p>" . SITE_NAME . "<br>
                <small>This is an automated email, please do not reply.</small></p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $alt_body = ($isResend ? "New Password Reset Verification Code\n\n" : "Password Reset Verification Code\n\n") .
                 "Hello " . $name . ",\n\n" .
                 ($isResend ? "A new verification code has been generated for your password reset request.\n\n" : "You have requested to reset your password for " . SITE_NAME . ".\n\n") .
                 "Your verification code is: " . $code . "\n\n" .
                 "Enter this 6-digit code in the password reset page to continue.\n\n" .
                 "This code will expire in 15 minutes.\n\n" .
                 "If you didn't request this, please ignore this email.\n\n" .
                 "Best regards,\n" . SITE_NAME . " Support Team";
    
    return sendEmailPHPMailer($email, $name, $subject, $body, $alt_body);
}
?>