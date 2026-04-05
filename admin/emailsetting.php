<?php
session_start();

require_once '../config.php';
require_once '../lib/email_helper.php';

$conn->query("CREATE TABLE IF NOT EXISTS email_settings (
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
)");

$conn->query("INSERT IGNORE INTO email_settings (id) VALUES (1)");

if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit();
}

function normalize_email_setting(string $value): string
{
    return trim($value);
}

$message = '';
$message_type = '';
$test_email = '';
$phpmailer_installed = file_exists(__DIR__ . '/../vendor/autoload.php');
$settings = mbj_get_email_settings($conn) ?? [
    'smtp_host' => '',
    'smtp_port' => '587',
    'smtp_username' => '',
    'smtp_password' => '',
    'smtp_encryption' => 'tls',
    'from_email' => '',
    'from_name' => '',
    'enable_email' => 1,
    'booking_notification' => 1,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';
    $existing_password = (string) ($settings['smtp_password'] ?? '');

    $smtp_host = normalize_email_setting($_POST['smtp_host'] ?? '');
    $smtp_port = normalize_email_setting($_POST['smtp_port'] ?? '587');
    $smtp_username = normalize_email_setting($_POST['smtp_username'] ?? '');
    $smtp_password_input = trim((string) ($_POST['smtp_password'] ?? ''));
    $smtp_encryption = strtolower(normalize_email_setting($_POST['smtp_encryption'] ?? 'tls'));
    $from_email = normalize_email_setting($_POST['from_email'] ?? '');
    $from_name = normalize_email_setting($_POST['from_name'] ?? '');
    $enable_email = isset($_POST['enable_email']) ? 1 : 0;
    $booking_notification = isset($_POST['booking_notification']) ? 1 : 0;
    $test_email = normalize_email_setting($_POST['test_email'] ?? '');
    $smtp_password = $smtp_password_input !== '' ? $smtp_password_input : $existing_password;

    $allowed_encryptions = ['tls', 'ssl', 'none'];

    $settings = array_merge($settings, [
        'smtp_host' => $smtp_host,
        'smtp_port' => $smtp_port,
        'smtp_username' => $smtp_username,
        'smtp_encryption' => $smtp_encryption,
        'from_email' => $from_email,
        'from_name' => $from_name,
        'enable_email' => $enable_email,
        'booking_notification' => $booking_notification,
    ]);

    if ($smtp_host === '' || $smtp_port === '' || $smtp_username === '' || $from_email === '') {
        $message = 'SMTP host, port, username, and from email are required.';
        $message_type = 'error';
    } elseif (!ctype_digit($smtp_port) || (int) $smtp_port < 1 || (int) $smtp_port > 65535) {
        $message = 'SMTP port must be a valid number between 1 and 65535.';
        $message_type = 'error';
    } elseif (!in_array($smtp_encryption, $allowed_encryptions, true)) {
        $message = 'Please choose a valid encryption method.';
        $message_type = 'error';
    } elseif (!filter_var($from_email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid from email address.';
        $message_type = 'error';
    } elseif ($smtp_password === '') {
        $message = 'SMTP password is required. Enter a password or keep the existing saved one.';
        $message_type = 'error';
    } else {
        $stmt = mysqli_prepare(
            $conn,
            "UPDATE email_settings
             SET smtp_host = ?, smtp_port = ?, smtp_username = ?, smtp_password = ?, smtp_encryption = ?,
                 from_email = ?, from_name = ?, enable_email = ?, booking_notification = ?
             WHERE id = 1"
        );

        if (!$stmt) {
            $message = 'Unable to prepare email settings update: ' . mysqli_error($conn);
            $message_type = 'error';
        } else {
            mysqli_stmt_bind_param(
                $stmt,
                'sssssssii',
                $smtp_host,
                $smtp_port,
                $smtp_username,
                $smtp_password,
                $smtp_encryption,
                $from_email,
                $from_name,
                $enable_email,
                $booking_notification
            );

            if (!mysqli_stmt_execute($stmt)) {
                $message = 'Error saving settings: ' . mysqli_stmt_error($stmt);
                $message_type = 'error';
            } else {
                $settings = mbj_get_email_settings($conn) ?? $settings;
                $settings['smtp_password'] = $smtp_password;

                if ($action === 'send_test') {
                    if (!$phpmailer_installed) {
                        $message = 'PHPMailer is not installed. Run composer install before sending a test email.';
                        $message_type = 'error';
                    } elseif ($test_email === '' || !filter_var($test_email, FILTER_VALIDATE_EMAIL)) {
                        $message = 'Enter a valid test email address before sending a test message.';
                        $message_type = 'error';
                    } else {
                        try {
                            mbj_send_smtp_email($conn, [
                                'to_email' => $test_email,
                                'to_name' => 'Test Recipient',
                                'subject' => 'SMTP Test Email - Mayurbhanj Tourism Planner',
                                'html_body' => '<h2>SMTP configuration successful</h2><p>This test email confirms your SMTP settings are working correctly.</p>',
                                'text_body' => 'SMTP configuration successful. This test email confirms your SMTP settings are working correctly.',
                            ]);

                            $message = 'Settings saved and test email sent successfully to ' . htmlspecialchars($test_email, ENT_QUOTES, 'UTF-8') . '.';
                            $message_type = 'success';
                        } catch (Throwable $throwable) {
                            $message = 'Settings saved, but the test email failed: ' . $throwable->getMessage();
                            $message_type = 'error';
                        }
                    }
                } else {
                    $message = 'Email settings saved successfully!';
                    $message_type = 'success';
                }
            }

            mysqli_stmt_close($stmt);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Email Settings - Mayurbhanj Tourism Planner</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600;700&family=Nunito:wght@600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root { --primary-color: #86B817; --primary-dark: #6a9612; --secondary-color: #14141F; --text-dark: #1f2937; --text-light: #6b7280; --white: #ffffff; --bg-light: #f3f4f6; --sidebar-width: 260px; --header-height: 70px; --shadow-sm: 0 1px 3px rgba(0,0,0,0.1); --transition: all 0.3s ease; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Heebo', sans-serif; background: var(--bg-light); min-height: 100vh; overflow-x: hidden; }
        .sidebar { position: fixed; left: 0; top: 0; width: var(--sidebar-width); height: 100vh; background: linear-gradient(180deg, var(--secondary-color) 0%, #2d2d3a 100%); z-index: 1000; box-shadow: 4px 0 20px rgba(0,0,0,0.15); overflow-y: auto; }
        .sidebar-brand { height: var(--header-height); display: flex; align-items: center; padding: 0 25px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-brand h1 { color: var(--white); font-size: 20px; font-weight: 700; display: flex; align-items: center; gap: 10px; }
        .sidebar-brand h1 i { color: var(--primary-color); font-size: 24px; }
        .sidebar-menu { padding: 20px 0; }
        .menu-section { padding: 0 20px; margin-bottom: 10px; }
        .menu-section-title { color: rgba(255,255,255,0.4); font-size: 11px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px; font-weight: 600; }
        .menu-item { display: flex; align-items: center; padding: 14px 20px; color: rgba(255,255,255,0.7); text-decoration: none; border-radius: 10px; margin: 5px 10px; transition: var(--transition); font-size: 14px; font-weight: 500; }
        .menu-item:hover { background: rgba(255,255,255,0.1); color: var(--white); transform: translateX(5px); }
        .menu-item.active { background: var(--primary-color); color: var(--white); box-shadow: 0 4px 15px rgba(134, 184, 23, 0.4); }
        .menu-item i { width: 20px; margin-right: 12px; font-size: 16px; }
        .main-content { margin-left: var(--sidebar-width); min-height: 100vh; }
        .header { height: var(--header-height); background: var(--white); display: flex; align-items: center; justify-content: space-between; padding: 0 30px; box-shadow: var(--shadow-sm); position: sticky; top: 0; z-index: 100; }
        .header-left { display: flex; align-items: center; gap: 20px; }
        .toggle-sidebar { width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; background: var(--bg-light); border-radius: 10px; cursor: pointer; transition: var(--transition); border: none; font-size: 18px; color: var(--text-dark); }
        .toggle-sidebar:hover { background: var(--primary-color); color: var(--white); }
        .page-title { font-size: 18px; font-weight: 600; color: var(--text-dark); }
        .header-right { display: flex; align-items: center; }
        .user-avatar { width: 40px; height: 40px; background: var(--primary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--white); font-weight: 600; font-size: 16px; cursor: pointer; }
        .content { padding: 30px; }
        .page-header { background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%); border-radius: 20px; padding: 40px; margin-bottom: 30px; position: relative; overflow: hidden; color: var(--white); }
        .page-header::before { content: ''; position: absolute; top: -50%; right: -20%; width: 400px; height: 400px; background: rgba(255,255,255,0.1); border-radius: 50%; }
        .page-header h1 { font-size: 32px; font-weight: 700; margin-bottom: 10px; }
        .page-header p { font-size: 16px; opacity: 0.9; }
        .form-card { background: var(--white); border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); overflow: hidden; margin-bottom: 30px; }
        .form-card-header { background: linear-gradient(135deg, var(--secondary-color) 0%, #2d2d3a 100%); padding: 20px 25px; display: flex; align-items: center; gap: 12px; }
        .form-card-header i { color: var(--primary-color); font-size: 20px; }
        .form-card-header h3 { color: var(--white); font-size: 16px; font-weight: 600; }
        .form-card-body { padding: 25px; }
        .form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
        .form-group { position: relative; }
        .form-group.full-width { grid-column: span 2; }
        .form-label { display: block; font-size: 13px; font-weight: 600; color: var(--text-dark); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
        .form-label i { color: var(--primary-color); margin-right: 5px; }
        .form-control { width: 100%; padding: 12px 15px; font-size: 14px; font-family: 'Heebo', sans-serif; border: 2px solid #e5e7eb; border-radius: 10px; background: var(--bg-light); color: var(--text-dark); transition: var(--transition); }
        .form-control:focus { outline: none; border-color: var(--primary-color); background: var(--white); box-shadow: 0 0 0 4px rgba(134, 184, 23, 0.15); }
        select.form-control { cursor: pointer; }
        .form-actions { display: flex; justify-content: flex-end; gap: 15px; margin-top: 30px; padding-top: 20px; border-top: 2px solid var(--bg-light); }
        .btn { padding: 14px 32px; font-size: 15px; font-weight: 600; font-family: 'Heebo', sans-serif; border-radius: 12px; cursor: pointer; transition: var(--transition); border: none; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary { background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%); color: var(--white); box-shadow: 0 4px 15px rgba(134, 184, 23, 0.4); }
        .btn-primary:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(134, 184, 23, 0.5); }
        .btn-secondary { background: var(--bg-light); color: var(--text-dark); padding: 14px 24px; }
        .btn-secondary:hover { background: #e5e7eb; }
        .checkbox-group { display: flex; align-items: center; gap: 10px; margin-top: 8px; }
        .checkbox-group input[type="checkbox"] { width: 20px; height: 20px; cursor: pointer; accent-color: var(--primary-color); }
        .checkbox-group label { font-size: 14px; font-weight: 500; color: var(--text-dark); cursor: pointer; }
        .info-box { background: #e8f5e9; border: 1px solid #c8e6c9; border-radius: 10px; padding: 15px 20px; margin-bottom: 20px; }
        .info-box i { color: #2e7d32; margin-right: 8px; }
        .info-box p { font-size: 14px; color: #2e7d32; margin: 0; }
        .alert-info { background: #e3f2fd; border: 1px solid #bbdefb; border-radius: 10px; padding: 15px 20px; margin-bottom: 20px; }
        .alert-info i { color: #1565c0; margin-right: 8px; }
        .alert-info p { font-size: 14px; color: #1565c0; margin: 0; }
        .status-strip { display: flex; flex-wrap: wrap; align-items: center; gap: 12px; margin-bottom: 20px; }
        .status-pill { display: inline-flex; align-items: center; gap: 8px; padding: 10px 14px; border-radius: 999px; font-size: 13px; font-weight: 600; }
        .status-pill.success { background: #e8f5e9; color: #2e7d32; }
        .status-pill.warning { background: #fff8e1; color: #b26a00; }
        .form-hint { font-size: 12px; color: var(--text-light); margin-top: 8px; line-height: 1.5; }
        @media (max-width: 768px) { .sidebar { transform: translateX(-100%); } .main-content { margin-left: 0; } .form-grid { grid-template-columns: 1fr; } .form-group.full-width { grid-column: span 1; } }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <?php include 'header.php'; ?>
        <div class="content">
            <?php if (!empty($message)): ?>
                <div style="padding: 15px 20px; border-radius: 10px; margin-bottom: 20px; background: <?php echo $message_type == 'success' ? '#d4edda' : '#f8d7da'; ?>; color: <?php echo $message_type == 'success' ? '#155724' : '#721c24'; ?>; border: 1px solid <?php echo $message_type == 'success' ? '#c3e6cb' : '#f5c6cb'; ?>;">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <div class="page-header">
                <h1><i class="fa fa-envelope"></i> Email Settings</h1>
                <p>Configure SMTP settings for sending emails on booking and notifications</p>
            </div>
            
            <div class="alert-info">
                <i class="fa fa-info-circle"></i>
                <p>These settings are used to send emails when users make bookings or other notifications. Make sure your SMTP credentials are correct.</p>
            </div>

            <div class="status-strip">
                <span class="status-pill <?php echo $phpmailer_installed ? 'success' : 'warning'; ?>">
                    <i class="fa <?php echo $phpmailer_installed ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>"></i>
                    <?php echo $phpmailer_installed ? 'PHPMailer installed' : 'PHPMailer missing'; ?>
                </span>
                <span class="status-pill <?php echo !empty($settings['smtp_password']) ? 'success' : 'warning'; ?>">
                    <i class="fa <?php echo !empty($settings['smtp_password']) ? 'fa-lock' : 'fa-unlock-alt'; ?>"></i>
                    <?php echo !empty($settings['smtp_password']) ? 'SMTP password saved' : 'SMTP password not saved'; ?>
                </span>
            </div>
            
            <form method="POST" action="" id="email_settings_form">
            <div class="form-card">
                <div class="form-card-header">
                    <i class="fa fa-cog"></i>
                    <h3>SMTP Configuration</h3>
                </div>
                <div class="form-card-body">
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label"><i class="fa fa-server"></i> SMTP Host</label>
                            <input type="text" name="smtp_host" class="form-control" value="<?php echo isset($settings['smtp_host']) ? htmlspecialchars($settings['smtp_host']) : ''; ?>" placeholder="e.g., smtp.gmail.com">
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fa fa-plug"></i> SMTP Port</label>
                            <input type="text" name="smtp_port" class="form-control" value="<?php echo isset($settings['smtp_port']) ? htmlspecialchars($settings['smtp_port']) : '587'; ?>" placeholder="e.g., 587">
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fa fa-user"></i> SMTP Username</label>
                            <input type="text" name="smtp_username" class="form-control" value="<?php echo isset($settings['smtp_username']) ? htmlspecialchars($settings['smtp_username']) : ''; ?>" placeholder="Your email address">
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fa fa-lock"></i> SMTP Password</label>
                            <input type="password" name="smtp_password" class="form-control" value="" placeholder="Enter a new password or app password">
                            <p class="form-hint">
                                <?php echo !empty($settings['smtp_password']) ? 'Leave this blank to keep the currently saved password.' : 'Enter the SMTP password or app password for this account.'; ?>
                            </p>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fa fa-shield-alt"></i> Encryption</label>
                            <select name="smtp_encryption" class="form-control">
                                <option value="tls" <?php echo (isset($settings['smtp_encryption']) && $settings['smtp_encryption'] == 'tls') ? 'selected' : ''; ?>>TLS (Recommended)</option>
                                <option value="ssl" <?php echo (isset($settings['smtp_encryption']) && $settings['smtp_encryption'] == 'ssl') ? 'selected' : ''; ?>>SSL</option>
                                <option value="none" <?php echo (isset($settings['smtp_encryption']) && $settings['smtp_encryption'] == 'none') ? 'selected' : ''; ?>>None</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-card">
                <div class="form-card-header">
                    <i class="fa fa-paper-plane"></i>
                    <h3>Sender Information</h3>
                </div>
                <div class="form-card-body">
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label"><i class="fa fa-envelope"></i> From Email</label>
                            <input type="email" name="from_email" class="form-control" value="<?php echo isset($settings['from_email']) ? htmlspecialchars($settings['from_email']) : ''; ?>" placeholder="noreply@yourdomain.com">
                            <p class="form-hint">
                                <i class="fa fa-exclamation-triangle" style="color:#b26a00"></i>
                                <strong>Gmail users:</strong> This must match your Gmail address (e.g. <em><?php echo htmlspecialchars($settings['smtp_username'] ?? '', ENT_QUOTES, 'UTF-8'); ?></em>).
                                Gmail SMTP does not allow sending from unrelated domains.
                            </p>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fa fa-user"></i> From Name</label>
                            <input type="text" name="from_name" class="form-control" value="<?php echo isset($settings['from_name']) ? htmlspecialchars($settings['from_name']) : ''; ?>" placeholder="Your Company Name">
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-card">
                <div class="form-card-header">
                    <i class="fa fa-bell"></i>
                    <h3>Email Notifications</h3>
                </div>
                <div class="form-card-body">
                    <div class="form-grid">
                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" id="enable_email" name="enable_email" <?php echo (isset($settings['enable_email']) && $settings['enable_email'] == 1) ? 'checked' : ''; ?>>
                                <label for="enable_email">Enable Email System</label>
                            </div>
                            <p style="font-size: 12px; color: var(--text-light); margin-top: 8px;">Turn on/off all email notifications</p>
                        </div>
                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" id="booking_notification" name="booking_notification" <?php echo (isset($settings['booking_notification']) && $settings['booking_notification'] == 1) ? 'checked' : ''; ?>>
                                <label for="booking_notification">Booking Notifications</label>
                            </div>
                            <p style="font-size: 12px; color: var(--text-light); margin-top: 8px;">Send email when a new booking is made</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-card">
                <div class="form-card-header">
                    <i class="fa fa-vial"></i>
                    <h3>Test Email</h3>
                </div>
                <div class="form-card-body">
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label class="form-label"><i class="fa fa-paper-plane"></i> Test Recipient Email</label>
                            <input type="email" name="test_email" class="form-control" value="<?php echo htmlspecialchars($test_email); ?>" placeholder="Enter an email address to receive a test message">
                            <p class="form-hint">Use this after saving your SMTP details to confirm email is working. Save settings first, then click <strong>Save &amp; Send Test</strong>.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <a href="page_setting.php" class="btn btn-secondary"><i class="fa fa-arrow-left"></i> Back</a>
                <button type="submit" form="email_settings_form" name="action" value="send_test" class="btn btn-secondary"><i class="fa fa-paper-plane"></i> Save &amp; Send Test</button>
                <button type="submit" form="email_settings_form" name="action" value="save" class="btn btn-primary"><i class="fa fa-save"></i> Save Settings</button>
            </div>
            </form>
        </div>
    </div>
    <script>
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            if (window.innerWidth <= 768) {
                sidebar.style.transform = sidebar.style.transform === 'translateX(0)' ? 'translateX(-100%)' : 'translateX(0)';
            }
        }
    </script>
</body>
</html>
