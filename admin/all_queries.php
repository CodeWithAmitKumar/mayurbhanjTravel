<?php
session_start();

require_once '../config.php';
require_once '../lib/contact_query_helper.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit();
}

$message = '';
$message_type = '';

try {
    mbj_ensure_contact_queries_table($conn);
} catch (Throwable $throwable) {
    $message = 'Unable to load the queries inbox: ' . $throwable->getMessage();
    $message_type = 'error';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['query_id']) && $_POST['action'] === 'resend_reply') {
    $queryId = (int) $_POST['query_id'];
    $existingWasSent = false;

    try {
        $query = mbj_get_contact_query_by_id($conn, $queryId);

        if (!$query) {
            throw new RuntimeException('The selected query could not be found.');
        }

        $existingWasSent = (int) ($query['auto_reply_sent'] ?? 0) === 1;

        $replyResult = mbj_send_contact_auto_reply($conn, $query);
        mbj_update_contact_query_reply_status(
            $conn,
            $queryId,
            true,
            (string) ($replyResult['subject'] ?? ''),
            ''
        );

        $message = 'Auto reply sent successfully to ' . htmlspecialchars($query['email'], ENT_QUOTES, 'UTF-8') . '.';
        $message_type = 'success';
    } catch (Throwable $throwable) {
        if (!$existingWasSent) {
            try {
                mbj_update_contact_query_reply_status($conn, $queryId, false, '', $throwable->getMessage());
            } catch (Throwable $ignored) {
            }
        }

        $message = 'Reply could not be sent: ' . htmlspecialchars($throwable->getMessage(), ENT_QUOTES, 'UTF-8');
        $message_type = 'error';
    }
}

$stats = [
    'total_queries' => 0,
    'replied_queries' => 0,
    'pending_queries' => 0,
    'today_queries' => 0,
];

$statsResult = mysqli_query(
    $conn,
    "SELECT
        COUNT(*) AS total_queries,
        SUM(CASE WHEN auto_reply_sent = 1 THEN 1 ELSE 0 END) AS replied_queries,
        SUM(CASE WHEN auto_reply_sent = 0 THEN 1 ELSE 0 END) AS pending_queries,
        SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) AS today_queries
     FROM contact_queries"
);

if ($statsResult) {
    $statsData = mysqli_fetch_assoc($statsResult);
    if ($statsData) {
        $stats = [
            'total_queries' => (int) ($statsData['total_queries'] ?? 0),
            'replied_queries' => (int) ($statsData['replied_queries'] ?? 0),
            'pending_queries' => (int) ($statsData['pending_queries'] ?? 0),
            'today_queries' => (int) ($statsData['today_queries'] ?? 0),
        ];
    }
}

$queriesResult = mysqli_query($conn, "SELECT * FROM contact_queries ORDER BY created_at DESC, id DESC");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>All Queries - Mayurbhanj Tourism Planner</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="" name="keywords">
    <meta content="" name="description">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600;700&family=Nunito:wght@600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        :root {
            --primary-color: #86B817;
            --primary-dark: #6a9612;
            --secondary-color: #14141F;
            --text-dark: #1f2937;
            --text-light: #6b7280;
            --white: #ffffff;
            --bg-light: #f3f4f6;
            --border-color: #e5e7eb;
            --danger: #c62828;
            --danger-bg: #ffebee;
            --success: #2e7d32;
            --success-bg: #e8f5e9;
            --warning: #b26a00;
            --warning-bg: #fff8e1;
            --sidebar-width: 260px;
            --header-height: 70px;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.1);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Heebo', sans-serif;
            background: var(--bg-light);
            min-height: 100vh;
            overflow-x: hidden;
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: linear-gradient(180deg, var(--secondary-color) 0%, #2d2d3a 100%);
            padding: 0;
            z-index: 1000;
            box-shadow: 4px 0 20px rgba(0,0,0,0.15);
            overflow-y: auto;
        }

        .sidebar-brand {
            height: var(--header-height);
            display: flex;
            align-items: center;
            padding: 0 25px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-brand h1 {
            color: var(--white);
            font-size: 20px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-brand h1 i {
            color: var(--primary-color);
            font-size: 24px;
        }

        .sidebar-menu {
            padding: 20px 0;
        }

        .menu-section {
            padding: 0 20px;
            margin-bottom: 10px;
        }

        .menu-section-title {
            color: rgba(255,255,255,0.4);
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .menu-item {
            display: flex;
            align-items: center;
            padding: 14px 20px;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            border-radius: 10px;
            margin: 5px 10px;
            transition: var(--transition);
            font-size: 14px;
            font-weight: 500;
        }

        .menu-item:hover {
            background: rgba(255,255,255,0.1);
            color: var(--white);
            transform: translateX(5px);
        }

        .menu-item.active {
            background: var(--primary-color);
            color: var(--white);
            box-shadow: 0 4px 15px rgba(134, 184, 23, 0.4);
        }

        .menu-item i {
            width: 20px;
            margin-right: 12px;
            font-size: 16px;
        }

        .menu-item .badge {
            margin-left: auto;
            background: rgba(255,255,255,0.2);
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 11px;
        }

        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
        }

        .header {
            height: var(--header-height);
            background: var(--white);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
            box-shadow: var(--shadow-sm);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .toggle-sidebar {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--bg-light);
            border-radius: 10px;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            font-size: 18px;
            color: var(--text-dark);
        }

        .toggle-sidebar:hover {
            background: var(--primary-color);
            color: var(--white);
        }

        .page-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-dark);
        }

        .header-right {
            display: flex;
            align-items: center;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: var(--primary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
        }

        .content {
            padding: 30px;
        }

        .page-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 30px;
            color: var(--white);
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 360px;
            height: 360px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
        }

        .page-header h1 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .page-header p {
            font-size: 16px;
            opacity: 0.92;
        }

        .alert-box {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 500;
        }

        .alert-box.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-box.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--white);
            border-radius: 18px;
            padding: 24px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }

        .stat-card .label {
            color: var(--text-light);
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            margin-bottom: 8px;
        }

        .stat-card .value {
            color: var(--text-dark);
            font-size: 32px;
            font-weight: 700;
        }

        .table-card {
            background: var(--white);
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .table-card-header {
            padding: 24px 28px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
        }

        .table-card-header h2 {
            font-size: 22px;
            color: var(--text-dark);
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 18px 20px;
            text-align: left;
            vertical-align: top;
            border-bottom: 1px solid var(--border-color);
        }

        th {
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: var(--text-light);
            background: #fafafa;
        }

        td {
            color: var(--text-dark);
            font-size: 14px;
        }

        .customer-name {
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .muted {
            color: var(--text-light);
            font-size: 13px;
        }

        .message-box {
            max-width: 340px;
            white-space: pre-line;
            line-height: 1.6;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            padding: 8px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .status-pill.success {
            color: var(--success);
            background: var(--success-bg);
        }

        .status-pill.warning {
            color: var(--warning);
            background: var(--warning-bg);
        }

        .status-pill.danger {
            color: var(--danger);
            background: var(--danger-bg);
        }

        .error-text {
            margin-top: 10px;
            color: var(--danger);
            font-size: 12px;
            line-height: 1.5;
        }

        .reply-time {
            margin-top: 8px;
            color: var(--text-light);
            font-size: 12px;
        }

        .btn-action {
            border: none;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: var(--white);
            padding: 10px 16px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: var(--transition);
        }

        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(134, 184, 23, 0.3);
        }

        .btn-action.secondary {
            background: #eef2f7;
            color: var(--text-dark);
            box-shadow: none;
        }

        .empty-state {
            padding: 50px 30px;
            text-align: center;
            color: var(--text-light);
        }

        .empty-state i {
            font-size: 42px;
            color: var(--primary-color);
            margin-bottom: 15px;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .main-content {
                margin-left: 0;
            }

            .content {
                padding: 20px;
            }

            .page-header {
                padding: 28px;
            }

            .page-header h1 {
                font-size: 24px;
            }
        }
    </style>
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <?php include 'header.php'; ?>

        <div class="content">
            <?php if ($message !== ''): ?>
            <div class="alert-box <?php echo $message_type === 'success' ? 'success' : 'error'; ?>">
                <?php echo $message; ?>
            </div>
            <?php endif; ?>

            <div class="page-header">
                <h1><i class="fa fa-inbox"></i> Customer Queries</h1>
                <p>Every message from the contact form is stored here, and auto replies are tracked with each record.</p>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="label">Total Queries</div>
                    <div class="value"><?php echo $stats['total_queries']; ?></div>
                </div>
                <div class="stat-card">
                    <div class="label">Auto Replies Sent</div>
                    <div class="value"><?php echo $stats['replied_queries']; ?></div>
                </div>
                <div class="stat-card">
                    <div class="label">Pending Replies</div>
                    <div class="value"><?php echo $stats['pending_queries']; ?></div>
                </div>
                <div class="stat-card">
                    <div class="label">Received Today</div>
                    <div class="value"><?php echo $stats['today_queries']; ?></div>
                </div>
            </div>

            <div class="table-card">
                <div class="table-card-header">
                    <h2>Inbox</h2>
                    <div class="muted">Newest queries appear first</div>
                </div>

                <?php if ($queriesResult && mysqli_num_rows($queriesResult) > 0): ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Subject</th>
                                <th>Message</th>
                                <th>Received</th>
                                <th>Email Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($query = mysqli_fetch_assoc($queriesResult)): ?>
                            <?php
                                $hasReplyError = !empty($query['auto_reply_error']);
                                $replySent = (int) ($query['auto_reply_sent'] ?? 0) === 1;
                                $statusClass = $replySent ? 'success' : ($hasReplyError ? 'danger' : 'warning');
                                $statusText = $replySent ? 'Sent' : ($hasReplyError ? 'Failed' : 'Pending');
                            ?>
                            <tr>
                                <td>
                                    <div class="customer-name"><?php echo htmlspecialchars($query['name'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    <div class="muted"><?php echo htmlspecialchars($query['email'], ENT_QUOTES, 'UTF-8'); ?></div>
                                </td>
                                <td>
                                    <div><?php echo htmlspecialchars($query['subject'], ENT_QUOTES, 'UTF-8'); ?></div>
                                </td>
                                <td>
                                    <div class="message-box"><?php echo htmlspecialchars($query['message'], ENT_QUOTES, 'UTF-8'); ?></div>
                                </td>
                                <td>
                                    <div><?php echo htmlspecialchars((string) $query['created_at'], ENT_QUOTES, 'UTF-8'); ?></div>
                                </td>
                                <td>
                                    <span class="status-pill <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                    <?php if ($replySent && !empty($query['auto_reply_sent_at'])): ?>
                                    <div class="reply-time">Sent on <?php echo htmlspecialchars((string) $query['auto_reply_sent_at'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    <?php endif; ?>
                                    <?php if ($hasReplyError): ?>
                                    <div class="error-text"><?php echo htmlspecialchars($query['auto_reply_error'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($replySent): ?>
                                    <div class="muted">Already sent</div>
                                    <?php else: ?>
                                    <form method="POST">
                                        <input type="hidden" name="action" value="resend_reply">
                                        <input type="hidden" name="query_id" value="<?php echo (int) $query['id']; ?>">
                                        <button type="submit" class="btn-action">Send Reply</button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fa fa-envelope-open-text"></i>
                    <p>No customer queries have been received yet.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            if (window.innerWidth <= 768) {
                sidebar.style.transform = sidebar.style.transform === 'translateX(0px)' ? 'translateX(-100%)' : 'translateX(0)';
            }
        }
    </script>
</body>

</html>
