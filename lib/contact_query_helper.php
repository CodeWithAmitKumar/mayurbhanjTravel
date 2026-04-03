<?php

declare(strict_types=1);

require_once __DIR__ . '/email_helper.php';

if (!function_exists('mbj_ensure_contact_queries_table')) {
    function mbj_ensure_contact_queries_table(mysqli $conn): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS contact_queries (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(150) NOT NULL,
            email VARCHAR(190) NOT NULL,
            subject VARCHAR(255) DEFAULT '',
            message TEXT NOT NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'new',
            auto_reply_sent TINYINT(1) NOT NULL DEFAULT 0,
            auto_reply_subject VARCHAR(255) DEFAULT NULL,
            auto_reply_error TEXT DEFAULT NULL,
            auto_reply_sent_at DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_contact_queries_status (status),
            INDEX idx_contact_queries_created_at (created_at)
        )";

        if (!mysqli_query($conn, $sql)) {
            throw new RuntimeException('Unable to create contact queries table: ' . mysqli_error($conn));
        }
    }
}

if (!function_exists('mbj_store_contact_query')) {
    function mbj_store_contact_query(mysqli $conn, array $data): int
    {
        mbj_ensure_contact_queries_table($conn);

        $name = trim((string) ($data['name'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));
        $subject = trim((string) ($data['subject'] ?? ''));
        $message = trim((string) ($data['message'] ?? ''));

        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO contact_queries (name, email, subject, message, status)
             VALUES (?, ?, ?, ?, 'new')"
        );

        if (!$stmt) {
            throw new RuntimeException('Unable to prepare contact query insert: ' . mysqli_error($conn));
        }

        mysqli_stmt_bind_param($stmt, 'ssss', $name, $email, $subject, $message);

        if (!mysqli_stmt_execute($stmt)) {
            $error = mysqli_stmt_error($stmt);
            mysqli_stmt_close($stmt);
            throw new RuntimeException('Unable to save contact query: ' . $error);
        }

        $queryId = (int) mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);

        return $queryId;
    }
}

if (!function_exists('mbj_get_contact_query_by_id')) {
    function mbj_get_contact_query_by_id(mysqli $conn, int $queryId): ?array
    {
        mbj_ensure_contact_queries_table($conn);

        $stmt = mysqli_prepare($conn, "SELECT * FROM contact_queries WHERE id = ? LIMIT 1");

        if (!$stmt) {
            throw new RuntimeException('Unable to prepare contact query fetch: ' . mysqli_error($conn));
        }

        mysqli_stmt_bind_param($stmt, 'i', $queryId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $query = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);

        return $query ?: null;
    }
}

if (!function_exists('mbj_update_contact_query_reply_status')) {
    function mbj_update_contact_query_reply_status(
        mysqli $conn,
        int $queryId,
        bool $sent,
        string $replySubject = '',
        string $replyError = ''
    ): void {
        mbj_ensure_contact_queries_table($conn);

        if ($sent) {
            $status = 'replied';
            $stmt = mysqli_prepare(
                $conn,
                "UPDATE contact_queries
                 SET status = ?, auto_reply_sent = 1, auto_reply_subject = ?, auto_reply_error = NULL, auto_reply_sent_at = NOW()
                 WHERE id = ?"
            );

            if (!$stmt) {
                throw new RuntimeException('Unable to prepare reply status update: ' . mysqli_error($conn));
            }

            mysqli_stmt_bind_param($stmt, 'ssi', $status, $replySubject, $queryId);
        } else {
            $status = 'pending';
            $stmt = mysqli_prepare(
                $conn,
                "UPDATE contact_queries
                 SET status = ?, auto_reply_sent = 0, auto_reply_subject = ?, auto_reply_error = ?, auto_reply_sent_at = NULL
                 WHERE id = ?"
            );

            if (!$stmt) {
                throw new RuntimeException('Unable to prepare reply status update: ' . mysqli_error($conn));
            }

            mysqli_stmt_bind_param($stmt, 'sssi', $status, $replySubject, $replyError, $queryId);
        }

        if (!mysqli_stmt_execute($stmt)) {
            $error = mysqli_stmt_error($stmt);
            mysqli_stmt_close($stmt);
            throw new RuntimeException('Unable to update reply status: ' . $error);
        }

        mysqli_stmt_close($stmt);
    }
}

if (!function_exists('mbj_send_contact_auto_reply')) {
    function mbj_send_contact_auto_reply(mysqli $conn, array $query): array
    {
        $settings = mbj_get_email_settings($conn) ?? [];
        $siteName = trim((string) ($settings['from_name'] ?? ''));
        $siteName = $siteName !== '' ? $siteName : 'Mayurbhanj Tourism Planner';

        $customerName = trim((string) ($query['name'] ?? ''));
        $customerEmail = trim((string) ($query['email'] ?? ''));
        $querySubject = trim((string) ($query['subject'] ?? ''));
        $queryMessage = trim((string) ($query['message'] ?? ''));

        if ($customerEmail === '' || !filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Customer email address is missing or invalid.');
        }

        $subjectLine = 'We received your query - ' . $siteName;
        $safeCustomerName = htmlspecialchars($customerName !== '' ? $customerName : 'Traveler', ENT_QUOTES, 'UTF-8');
        $safeSiteName = htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8');
        $safeQuerySubject = htmlspecialchars($querySubject !== '' ? $querySubject : 'General inquiry', ENT_QUOTES, 'UTF-8');
        $safeQueryMessage = nl2br(htmlspecialchars($queryMessage, ENT_QUOTES, 'UTF-8'));

        $htmlBody = '
            <div style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.7;">
                <h2 style="margin-bottom: 12px; color: #14141F;">Hello ' . $safeCustomerName . ',</h2>
                <p>Thank you for contacting <strong>' . $safeSiteName . '</strong>.</p>
                <p>We have received your query and our team will get back to you soon.</p>
                <div style="margin: 24px 0; padding: 18px; background: #f3f4f6; border-radius: 12px;">
                    <p style="margin: 0 0 8px;"><strong>Subject:</strong> ' . $safeQuerySubject . '</p>
                    <p style="margin: 0;"><strong>Your message:</strong><br>' . $safeQueryMessage . '</p>
                </div>
                <p>If your request is urgent, please reply to this email or contact us directly.</p>
                <p style="margin-top: 24px;">Regards,<br><strong>' . $safeSiteName . '</strong></p>
            </div>
        ';

        $textBody = "Hello " . ($customerName !== '' ? $customerName : 'Traveler') . ",\n\n"
            . "Thank you for contacting " . $siteName . ".\n"
            . "We have received your query and our team will get back to you soon.\n\n"
            . "Subject: " . ($querySubject !== '' ? $querySubject : 'General inquiry') . "\n"
            . "Your message:\n" . $queryMessage . "\n\n"
            . "If your request is urgent, please reply to this email or contact us directly.\n\n"
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
