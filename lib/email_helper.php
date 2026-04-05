<?php

declare(strict_types=1);

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

if (!function_exists('mbj_ensure_email_settings_table')) {
    function mbj_ensure_email_settings_table(mysqli $conn): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS email_settings (
            id INT PRIMARY KEY,
            smtp_host VARCHAR(255) DEFAULT '',
            smtp_port VARCHAR(10) DEFAULT '587',
            smtp_username VARCHAR(255) DEFAULT '',
            smtp_password VARCHAR(255) DEFAULT '',
            smtp_encryption VARCHAR(20) DEFAULT 'tls',
            from_email VARCHAR(255) DEFAULT '',
            from_name VARCHAR(255) DEFAULT '',
            enable_email TINYINT(1) DEFAULT 1,
            booking_notification TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";

        if (!mysqli_query($conn, $sql)) {
            throw new RuntimeException('Unable to prepare email settings table: ' . mysqli_error($conn));
        }

        if (!mysqli_query($conn, "INSERT IGNORE INTO email_settings (id) VALUES (1)")) {
            throw new RuntimeException('Unable to initialize email settings: ' . mysqli_error($conn));
        }
    }
}

if (!function_exists('mbj_get_email_settings')) {
    /**
     * Fetch the single email settings record.
     */
    function mbj_get_email_settings(mysqli $conn): ?array
    {
        mbj_ensure_email_settings_table($conn);

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
            $mail->Timeout = 10; // fail fast — do not hang for 30+ seconds

            if ($smtpEncryption === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($smtpEncryption === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } else {
                $mail->SMTPSecure = false;
                $mail->SMTPAutoTLS = false;
            }

            // When using Gmail SMTP, the From address must be the authenticated Gmail
            // account or an approved alias. Fall back to smtp_username if from_email
            // belongs to a different domain so Gmail does not reject the message.
            $effectiveFromEmail = $fromEmail;
            $smtpDomain = substr(strrchr($smtpUsername, '@'), 1);
            $fromDomain  = substr(strrchr($fromEmail, '@'), 1);
            if (
                $smtpDomain !== '' &&
                $fromDomain  !== '' &&
                strtolower($smtpDomain) !== strtolower($fromDomain)
            ) {
                // smtp username domain differs from from_email domain — use smtp username
                $effectiveFromEmail = $smtpUsername;
            }

            $mail->setFrom($effectiveFromEmail, $fromName !== '' ? $fromName : $effectiveFromEmail);
            $mail->addAddress($emailData['to_email'], $emailData['to_name'] ?? '');
            $mail->addReplyTo($effectiveFromEmail, $fromName !== '' ? $fromName : $effectiveFromEmail);
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

if (!function_exists('mbj_send_registration_email')) {
    function mbj_send_registration_email(mysqli $conn, array $user, string $plainPassword): array
    {
        $settings = mbj_get_email_settings($conn) ?? [];
        $siteName = trim((string) ($settings['from_name'] ?? ''));
        $siteName = $siteName !== '' ? $siteName : 'Mayurbhanj Tourism Planner';

        $customerName = trim((string) ($user['name'] ?? ''));
        $customerEmail = trim((string) ($user['email'] ?? ''));
        $customerGender = trim((string) ($user['gender'] ?? ''));

        if ($customerEmail === '' || !filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Registration email address is missing or invalid.');
        }

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = trim((string) ($_SERVER['HTTP_HOST'] ?? ''));
        $scriptDir = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/login.php'))), '/.');
        $loginUrl = $host !== '' ? $scheme . '://' . $host . ($scriptDir !== '' ? $scriptDir : '') . '/login.php' : 'login.php';

        $subjectLine = 'Welcome to ' . $siteName . ' - Your login details';
        $safeSiteName = htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8');
        $safeName = htmlspecialchars($customerName !== '' ? $customerName : 'Traveler', ENT_QUOTES, 'UTF-8');
        $safeEmail = htmlspecialchars($customerEmail, ENT_QUOTES, 'UTF-8');
        $safeGender = htmlspecialchars($customerGender !== '' ? $customerGender : 'Not specified', ENT_QUOTES, 'UTF-8');
        $safePassword = htmlspecialchars($plainPassword, ENT_QUOTES, 'UTF-8');
        $safeLoginUrl = htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8');

        $htmlBody = '
            <div style="margin:0; padding:32px 18px; background:#f4f7fb; font-family:Arial, sans-serif; color:#1f2937;">
                <div style="max-width:640px; margin:0 auto; background:#ffffff; border-radius:18px; overflow:hidden; box-shadow:0 10px 30px rgba(15, 61, 98, 0.12);">
                    <div style="padding:28px 32px; background:linear-gradient(135deg, #0f3d62 0%, #1b6ca8 60%, #ff7f50 100%); color:#ffffff;">
                        <div style="font-size:13px; letter-spacing:1.2px; text-transform:uppercase; opacity:0.85;">Account Created</div>
                        <h1 style="margin:12px 0 0; font-size:28px; line-height:1.2;">Welcome to ' . $safeSiteName . '</h1>
                    </div>
                    <div style="padding:32px;">
                        <p style="margin:0 0 16px; font-size:16px;">Hello <strong>' . $safeName . '</strong>,</p>
                        <p style="margin:0 0 18px; font-size:15px; line-height:1.7;">Your traveler account has been created successfully. Here are your registration details:</p>
                        <div style="margin:24px 0; padding:22px; background:#f7f9fc; border:1px solid #e4ebf3; border-radius:14px;">
                            <p style="margin:0 0 10px;"><strong>Name:</strong> ' . $safeName . '</p>
                            <p style="margin:0 0 10px;"><strong>Email:</strong> ' . $safeEmail . '</p>
                            <p style="margin:0 0 10px;"><strong>Gender:</strong> ' . $safeGender . '</p>
                            <p style="margin:0;"><strong>Password:</strong> ' . $safePassword . '</p>
                        </div>
                        <p style="margin:0 0 20px; font-size:15px; line-height:1.7;">You can now log in using the button below.</p>
                        <p style="margin:0 0 26px;">
                            <a href="' . $safeLoginUrl . '" style="display:inline-block; padding:14px 28px; border-radius:999px; background:linear-gradient(135deg, #0d6efd, #ff7f50); color:#ffffff; text-decoration:none; font-weight:700;">Login To Your Account</a>
                        </p>
                        <p style="margin:0; font-size:14px; color:#6b7280; line-height:1.7;">For security, please keep this email private and change your password later if needed.</p>
                    </div>
                </div>
            </div>
        ';

        $textBody = "Hello " . ($customerName !== '' ? $customerName : 'Traveler') . ",\n\n"
            . "Welcome to " . $siteName . ". Your traveler account has been created successfully.\n\n"
            . "Registration details:\n"
            . "Name: " . ($customerName !== '' ? $customerName : 'Traveler') . "\n"
            . "Email: " . $customerEmail . "\n"
            . "Gender: " . ($customerGender !== '' ? $customerGender : 'Not specified') . "\n"
            . "Password: " . $plainPassword . "\n\n"
            . "Login here: " . $loginUrl . "\n\n"
            . "For security, please keep this email private and change your password later if needed.\n\n"
            . "Regards,\n" . $siteName;

        mbj_send_smtp_email($conn, [
            'to_email' => $customerEmail,
            'to_name' => $customerName,
            'subject' => $subjectLine,
            'html_body' => $htmlBody,
            'text_body' => $textBody,
        ]);

        return [
            'sent' => true,
            'subject' => $subjectLine,
            'error' => '',
        ];
    }
}
