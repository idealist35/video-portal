<?php
/**
 * Email Sending Module
 * 
 * Uses PHPMailer via SMTP. Loads HTML templates from emails/ directory.
 */

require_once __DIR__ . '/config.php';
require_once BASE_PATH . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Send an email using a template file.
 *
 * @param string $to        Recipient email
 * @param string $subject   Email subject
 * @param string $template  Template filename (e.g. 'welcome.html')
 * @param array  $vars      Variables to replace in template: {{KEY}} => value
 */
function sendEmail(string $to, string $subject, string $template, array $vars = []): bool
{
    // Load and render template
    $templatePath = EMAILS_PATH . '/' . $template;
    if (!file_exists($templatePath)) {
        error_log("Email template not found: $templatePath");
        return false;
    }

    $body = file_get_contents($templatePath);
    foreach ($vars as $key => $value) {
        $body = str_replace('{{' . $key . '}}', htmlspecialchars($value), $body);
    }

    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->Port       = SMTP_PORT;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        // Plain-text fallback
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $body));

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email send failed to $to: " . $e->getMessage());
        return false;
    }
}

/**
 * Send welcome / verification email.
 */
function sendWelcomeEmail(string $to, string $verifyToken): bool
{
    $verifyUrl = SITE_URL . '/verify?token=' . urlencode($verifyToken);
    return sendEmail($to, 'Welcome to ' . SITE_TITLE, 'welcome.html', [
        'SITE_TITLE'  => SITE_TITLE,
        'VERIFY_URL'  => $verifyUrl,
        'EMAIL'       => $to,
    ]);
}

/**
 * Send password reset email.
 */
function sendResetEmail(string $to, string $resetToken): bool
{
    $resetUrl = SITE_URL . '/reset-password?token=' . urlencode($resetToken);
    return sendEmail($to, 'Password Reset — ' . SITE_TITLE, 'reset-password.html', [
        'SITE_TITLE' => SITE_TITLE,
        'RESET_URL'  => $resetUrl,
        'EMAIL'      => $to,
    ]);
}
