<?php

declare(strict_types=1);

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

if (!function_exists('mbj_get_email_settings')) {
    /**
     * Fetch the single email settings record.
     */
    function mbj_get_email_settings(mysqli $conn): ?array
    {
        $result = mysqli_query($conn, "SELECT * FROM email_settings WHERE id = 1 LIMIT 1");

        if (!$result) {
            return null;
        }

        $settings = mysqli_fetch_assoc($result);

        return $settings ?: null;
    }
}

if (!function_exists('mbj_email_is_enabled')) {
    function mbj_email_is_enabled(?array $settings): bool
    {
        if (!$settings) {
            return false;
        }

        return (int) ($settings['enable_email'] ?? 0) === 1;
    }
}

if (!function_exists('mbj_send_smtp_email')) {
    /**
     * Send an email using the saved SMTP settings.
     *
     * @throws RuntimeException
     */
    function mbj_send_smtp_email(mysqli $conn, array $emailData): bool
    {
        $autoloadPath = __DIR__ . '/../vendor/autoload.php';

        if (!file_exists($autoloadPath)) {
            throw new RuntimeException('PHPMailer is not installed. Run composer install or composer require phpmailer/phpmailer.');
        }

        require_once $autoloadPath;

        $settings = mbj_get_email_settings($conn);

        if (!mbj_email_is_enabled($settings)) {
            throw new RuntimeException('Email sending is disabled in the admin email settings.');
        }

        $smtpHost = trim((string) ($settings['smtp_host'] ?? ''));
        $smtpPort = (int) ($settings['smtp_port'] ?? 0);
        $smtpUsername = trim((string) ($settings['smtp_username'] ?? ''));
        $smtpPassword = (string) ($settings['smtp_password'] ?? '');
        $smtpEncryption = strtolower(trim((string) ($settings['smtp_encryption'] ?? 'tls')));
        $fromEmail = trim((string) ($settings['from_email'] ?? ''));
        $fromName = trim((string) ($settings['from_name'] ?? ''));

        if ($smtpHost === '' || $smtpPort <= 0 || $smtpUsername === '' || $smtpPassword === '' || $fromEmail === '') {
            throw new RuntimeException('SMTP settings are incomplete. Please fill in host, port, username, password, and from email.');
        }

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = $smtpHost;
            $mail->Port = $smtpPort;
            $mail->SMTPAuth = true;
            $mail->Username = $smtpUsername;
            $mail->Password = $smtpPassword;
            $mail->CharSet = 'UTF-8';

            if ($smtpEncryption === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($smtpEncryption === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } else {
                $mail->SMTPSecure = false;
                $mail->SMTPAutoTLS = false;
            }

            $mail->setFrom($fromEmail, $fromName !== '' ? $fromName : $fromEmail);
            $mail->addAddress($emailData['to_email'], $emailData['to_name'] ?? '');
            $mail->addReplyTo($fromEmail, $fromName !== '' ? $fromName : $fromEmail);
            $mail->isHTML(true);
            $mail->Subject = (string) ($emailData['subject'] ?? '');
            $mail->Body = (string) ($emailData['html_body'] ?? '');
            $mail->AltBody = (string) ($emailData['text_body'] ?? strip_tags((string) ($emailData['html_body'] ?? '')));

            return $mail->send();
        } catch (Exception $exception) {
            throw new RuntimeException('Mailer error: ' . $mail->ErrorInfo, 0, $exception);
        }
    }
}
