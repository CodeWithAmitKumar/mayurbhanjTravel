<?php
session_start();

require_once '../config.php';
require_once '../lib/guide_helper.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit();
}

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$message = '';
$messageType = 'success';
$guides = [];
$setupOk = true;

try {
    mbj_ensure_guides_table($conn);
    mbj_ensure_guides_upload_dir();
} catch (Throwable $throwable) {
    $message = $throwable->getMessage();
    $messageType = 'error';
    $setupOk = false;
}

$messageMap = [
    'created' => ['Guide saved successfully.', 'success'],
    'updated' => ['Guide updated successfully.', 'success'],
    'deleted' => ['Guide deleted successfully.', 'success'],
    'status_changed' => ['Guide status updated successfully.', 'success'],
];

if (isset($_GET['message']) && isset($messageMap[$_GET['message']])) {
    $message = $messageMap[$_GET['message']][0];
    $messageType = $messageMap[$_GET['message']][1];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $setupOk) {
    $action = trim((string) ($_POST['action'] ?? ''));
    $guideId = isset($_POST['guide_id']) ? (int) $_POST['guide_id'] : 0;

    try {
        $guide = mbj_get_guide_by_id($conn, $guideId);
        if (!$guide) {
            throw new RuntimeException('The selected guide could not be found.');
        }

        if ($action === 'toggle_status') {
            $newStatus = (int) ($guide['is_active'] ?? 0) === 1 ? 0 : 1;
            $stmt = mysqli_prepare($conn, "UPDATE guides SET is_active = ? WHERE guide_id = ?");

            if (!$stmt) {
                throw new RuntimeException('Unable to prepare guide status update.');
            }

            mysqli_stmt_bind_param($stmt, 'ii', $newStatus, $guideId);
            if (!mysqli_stmt_execute($stmt)) {
                throw new RuntimeException(mysqli_error($conn));
            }

            header('Location: allguides.php?message=status_changed');
            exit();
        }

        if ($action === 'delete_guide') {
            mysqli_begin_transaction($conn);

            try {
                $stmt = mysqli_prepare($conn, "DELETE FROM guides WHERE guide_id = ?");

                if (!$stmt) {
                    throw new RuntimeException('Unable to prepare guide delete.');
                }

                mysqli_stmt_bind_param($stmt, 'i', $guideId);
                if (!mysqli_stmt_execute($stmt)) {
                    throw new RuntimeException(mysqli_error($conn));
                }

                mysqli_commit($conn);

                if (!empty($guide['guide_image'])) {
                    mbj_remove_guide_image($guide['guide_image']);
                }

                header('Location: allguides.php?message=deleted');
                exit();
            } catch (Throwable $throwable) {
                mysqli_rollback($conn);
                throw $throwable;
            }
        }

        throw new RuntimeException('Invalid action requested.');
    } catch (Throwable $throwable) {
        $message = $throwable->getMessage();
        $messageType = 'error';
    }
}

if ($setupOk) {
    try {
        $guides = mbj_get_all_guides($conn);
    } catch (Throwable $throwable) {
        $message = $throwable->getMessage();
        $messageType = 'error';
    }
}

$totalGuides = count($guides);
$activeGuides = 0;
$inactiveGuides = 0;

foreach ($guides as $guide) {
    if ((int) ($guide['is_active'] ?? 0) === 1) {
        $activeGuides++;
    } else {
        $inactiveGuides++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>All Guides - Mayurbhanj Tourism Planner</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">

    <style>
        :root {
            --primary: #86B817;
            --primary-dark: #6a9612;
            --dark: #14141F;
            --text: #1f2937;
            --muted: #6b7280;
            --bg: #f3f4f6;
            --white: #ffffff;
            --border: #e5e7eb;
            --success: #166534;
            --success-bg: #dcfce7;
            --warning: #9a3412;
            --warning-bg: #ffedd5;
            --danger: #b91c1c;
            --danger-bg: #fee2e2;
            --sidebar-width: 260px;
            --header-height: 70px;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Heebo', sans-serif;
            background: var(--bg);
            color: var(--text);
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: linear-gradient(180deg, var(--dark), #2d2d3a);
            z-index: 1000;
            overflow-y: auto;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.15);
        }

        .sidebar-brand {
            height: var(--header-height);
            display: flex;
            align-items: center;
            padding: 0 25px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-brand h1 {
            color: var(--white);
            font-size: 20px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-brand i {
            color: var(--primary);
        }

        .sidebar-menu {
            padding: 20px 0;
        }

        .menu-section {
            padding: 0 20px;
            margin-bottom: 10px;
        }

        .menu-section-title {
            color: rgba(255, 255, 255, 0.4);
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
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            border-radius: 10px;
            margin: 5px 10px;
            font-size: 14px;
            font-weight: 500;
            transition: 0.3s ease;
        }

        .menu-item:hover {
            background: rgba(255, 255, 255, 0.1);
            color: var(--white);
            transform: translateX(5px);
        }

        .menu-item.active {
            background: var(--primary);
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
            background: rgba(255, 255, 255, 0.2);
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 11px;
        }

        .header {
            height: var(--header-height);
            background: var(--white);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
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
            background: var(--bg);
            border-radius: 10px;
            cursor: pointer;
            border: none;
            font-size: 18px;
            color: var(--text);
        }

        .page-title {
            font-size: 18px;
            font-weight: 600;
        }

        .header-right {
            display: flex;
            align-items: center;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-weight: 600;
        }

        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
        }

        .content {
            padding: 30px;
        }

        .page-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: var(--white);
            border-radius: 20px;
            padding: 34px;
            margin-bottom: 24px;
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            width: 320px;
            height: 320px;
            right: -90px;
            top: -120px;
            background: rgba(255, 255, 255, 0.12);
            border-radius: 50%;
        }

        .page-header h1 {
            margin: 0 0 10px;
            font-size: 30px;
        }

        .page-header p {
            margin: 0;
            opacity: 0.92;
        }

        .page-header-actions {
            margin-top: 22px;
        }

        .btn-add {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(20, 20, 31, 0.16);
            color: var(--white);
            text-decoration: none;
            border-radius: 12px;
            padding: 13px 18px;
            font-weight: 700;
        }

        .alert {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 20px;
        }

        .alert.success {
            background: var(--success-bg);
            color: var(--success);
        }

        .alert.error {
            background: var(--danger-bg);
            color: var(--danger);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: var(--white);
            border-radius: 18px;
            padding: 24px;
            box-shadow: var(--shadow);
        }

        .stat-label {
            color: var(--muted);
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
        }

        .table-card {
            background: var(--white);
            border-radius: 20px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .table-header {
            padding: 24px 28px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .table-header h2 {
            margin: 0;
            font-size: 22px;
        }

        .table-note {
            color: var(--muted);
            font-size: 14px;
        }

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 18px 16px;
            text-align: left;
            border-bottom: 1px solid var(--border);
            vertical-align: top;
        }

        th {
            background: #fafafa;
            color: var(--muted);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }

        td {
            font-size: 14px;
        }

        .thumb {
            width: 72px;
            height: 72px;
            border-radius: 14px;
            object-fit: cover;
            background: #e5e7eb;
            display: block;
        }

        .guide-name {
            font-weight: 700;
            font-size: 15px;
            margin-bottom: 4px;
        }

        .muted {
            color: var(--muted);
            font-size: 13px;
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

        .status-pill.active {
            background: var(--success-bg);
            color: var(--success);
        }

        .status-pill.inactive {
            background: var(--warning-bg);
            color: var(--warning);
        }

        .action-group {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            min-width: 220px;
        }

        .inline-form {
            margin: 0;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            border: none;
            border-radius: 10px;
            padding: 10px 14px;
            font: inherit;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
        }

        .btn-edit {
            background: #e8f0fe;
            color: #1d4ed8;
        }

        .btn-toggle {
            background: #edfdf3;
            color: #166534;
        }

        .btn-toggle.inactive {
            background: #fff7ed;
            color: #c2410c;
        }

        .btn-delete {
            background: var(--danger-bg);
            color: var(--danger);
        }

        .empty-state {
            padding: 60px 24px;
            text-align: center;
            color: var(--muted);
        }

        .empty-state i {
            font-size: 44px;
            color: var(--primary);
            margin-bottom: 14px;
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

            .page-header h1 {
                font-size: 24px;
            }

            .table-header {
                flex-direction: column;
                align-items: flex-start;
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
                <div class="alert <?php echo $messageType === 'error' ? 'error' : 'success'; ?>">
                    <i class="fa <?php echo $messageType === 'error' ? 'fa-exclamation-circle' : 'fa-check-circle'; ?>"></i>
                    <?php echo h($message); ?>
                </div>
            <?php endif; ?>

            <div class="page-header">
                <h1><i class="fa fa-user-tie"></i> Guide Management</h1>
                <p>Add guides, manage guide records, and control which guides stay active on the site.</p>
                <div class="page-header-actions">
                    <a href="add_guides.php" class="btn-add"><i class="fa fa-plus"></i> Add New Guide</a>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Total Guides</div>
                    <div class="stat-value"><?php echo $totalGuides; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Active Guides</div>
                    <div class="stat-value"><?php echo $activeGuides; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Inactive Guides</div>
                    <div class="stat-value"><?php echo $inactiveGuides; ?></div>
                </div>
            </div>

            <div class="table-card">
                <div class="table-header">
                    <h2>All Guides</h2>
                    <div class="table-note">Manage guide records, status, and actions from this table.</div>
                </div>

                <?php if (!empty($guides)): ?>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Sl No</th>
                                    <th>Guide Image</th>
                                    <th>Guide Name</th>
                                    <th>Price</th>
                                    <th>Guide Address</th>
                                    <th>Guide Phone No</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($guides as $index => $guide): ?>
                                    <?php $isActive = (int) ($guide['is_active'] ?? 0) === 1; ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td>
                                            <?php if (!empty($guide['guide_image'])): ?>
                                                <img src="../<?php echo h($guide['guide_image']); ?>" alt="<?php echo h($guide['guide_name']); ?>" class="thumb">
                                            <?php else: ?>
                                                <div class="thumb"></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="guide-name"><?php echo h($guide['guide_name']); ?></div>
                                        </td>
                                        <td><?php echo h($guide['price']); ?></td>
                                        <td><?php echo h($guide['guide_address']); ?></td>
                                        <td><?php echo h($guide['guide_phone_no']); ?></td>
                                        <td>
                                            <span class="status-pill <?php echo $isActive ? 'active' : 'inactive'; ?>">
                                                <?php echo $isActive ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-group">
                                                <a href="add_guides.php?id=<?php echo (int) $guide['guide_id']; ?>" class="btn btn-edit">
                                                    <i class="fa fa-edit"></i> Edit
                                                </a>

                                                <form method="POST" class="inline-form">
                                                    <input type="hidden" name="action" value="toggle_status">
                                                    <input type="hidden" name="guide_id" value="<?php echo (int) $guide['guide_id']; ?>">
                                                    <button type="submit" class="btn btn-toggle <?php echo $isActive ? '' : 'inactive'; ?>">
                                                        <i class="fa fa-toggle-on"></i>
                                                        <?php echo $isActive ? 'Set Inactive' : 'Set Active'; ?>
                                                    </button>
                                                </form>

                                                <form method="POST" class="inline-form" onsubmit="return confirm('Delete this guide permanently?');">
                                                    <input type="hidden" name="action" value="delete_guide">
                                                    <input type="hidden" name="guide_id" value="<?php echo (int) $guide['guide_id']; ?>">
                                                    <button type="submit" class="btn btn-delete">
                                                        <i class="fa fa-trash"></i> Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fa fa-user-tie"></i>
                        <p>No guide records found yet. Use the Add New Guide button to create the first guide.</p>
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
