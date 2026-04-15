<?php
session_start();

require_once '../config.php';

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

// Handle delete action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_booking') {
    $bookingId = isset($_POST['booking_id']) ? (int) $_POST['booking_id'] : 0;

    if ($bookingId > 0) {
        try {
            $stmt = mysqli_prepare($conn, "DELETE FROM bookings WHERE booking_id = ?");
            if (!$stmt) {
                throw new RuntimeException('Unable to prepare delete statement: ' . mysqli_error($conn));
            }
            mysqli_stmt_bind_param($stmt, 'i', $bookingId);
            if (!mysqli_stmt_execute($stmt)) {
                throw new RuntimeException(mysqli_error($conn));
            }
            header('Location: allbookings.php?message=deleted');
            exit();
        } catch (Throwable $e) {
            $message = $e->getMessage();
            $messageType = 'error';
        }
    }
}

// Flash messages
$messageMap = [
    'deleted' => ['Booking deleted successfully.', 'success'],
];

if (isset($_GET['message']) && isset($messageMap[$_GET['message']])) {
    $message    = $messageMap[$_GET['message']][0];
    $messageType = $messageMap[$_GET['message']][1];
}

// Fetch all bookings
$bookings = [];
$result = mysqli_query($conn, "SELECT `booking_id`, `user_identifier`, `trip_date`, `start_from`, `destination_id`, `guide_id`, `transport_id`, `hotel_id`, `total_price`, `payment_status`, `transaction_id`, `created_at`, `updated_at` FROM `bookings` WHERE 1 ORDER BY booking_id DESC");

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $bookings[] = $row;
    }
}

$totalBookings = count($bookings);
$paidBookings  = 0;
$pendingBookings = 0;

foreach ($bookings as $b) {
    $status = strtolower((string) ($b['payment_status'] ?? ''));
    if ($status === 'paid' || $status === 'completed' || $status === 'success') {
        $paidBookings++;
    } else {
        $pendingBookings++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>All Bookings - Mayurbhanj Tourism Planner</title>
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
            --info: #1e40af;
            --info-bg: #dbeafe;
            --sidebar-width: 260px;
            --header-height: 70px;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        * { box-sizing: border-box; }

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

        .sidebar-brand i { color: var(--primary); }

        .sidebar-menu { padding: 20px 0; }

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

        .content { padding: 30px; }

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

        .table-wrap { overflow-x: auto; }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 15px 14px;
            text-align: left;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }

        th {
            background: #fafafa;
            color: var(--muted);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            white-space: nowrap;
        }

        td { font-size: 14px; }

        .booking-id {
            font-weight: 700;
            font-size: 15px;
        }

        .muted {
            color: var(--muted);
            font-size: 13px;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            white-space: nowrap;
        }

        .status-pill.paid    { background: var(--success-bg); color: var(--success); }
        .status-pill.pending { background: var(--warning-bg); color: var(--warning); }
        .status-pill.failed  { background: var(--danger-bg);  color: var(--danger);  }
        .status-pill.info    { background: var(--info-bg);    color: var(--info);    }

        .action-group {
            display: flex;
            flex-wrap: nowrap;
            gap: 8px;
            min-width: 140px;
        }

        .inline-form { margin: 0; }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            border: none;
            border-radius: 10px;
            padding: 9px 14px;
            font: inherit;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            white-space: nowrap;
        }

        .btn-view   { background: #e8f0fe; color: #1d4ed8; }
        .btn-delete { background: var(--danger-bg); color: var(--danger); }

        .price-tag {
            font-weight: 700;
            color: var(--primary-dark);
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
            display: block;
        }

        /* Search bar */
        .search-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .search-input {
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 9px 14px;
            font-family: inherit;
            font-size: 14px;
            outline: none;
            width: 240px;
            transition: border-color 0.2s;
        }

        .search-input:focus { border-color: var(--primary); }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .main-content { margin-left: 0; }
            .content { padding: 20px; }
            .page-header h1 { font-size: 24px; }
            .table-header { flex-direction: column; align-items: flex-start; }
            .search-input { width: 100%; }
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

            <!-- Page Header -->
            <div class="page-header">
                <h1><i class="fa fa-calendar-check"></i> Booking Management</h1>
                <p>View and manage all customer bookings, payment statuses, and trip details.</p>
            </div>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Total Bookings</div>
                    <div class="stat-value"><?php echo $totalBookings; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Paid Bookings</div>
                    <div class="stat-value" style="color:var(--success);"><?php echo $paidBookings; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Pending / Other</div>
                    <div class="stat-value" style="color:var(--warning);"><?php echo $pendingBookings; ?></div>
                </div>
            </div>

            <!-- Table -->
            <div class="table-card">
                <div class="table-header">
                    <div>
                        <h2>All Bookings</h2>
                        <div class="table-note">View details or remove booking records from the table below.</div>
                    </div>
                    <div class="search-wrap">
                        <input type="text" id="bookingSearch" class="search-input" placeholder="&#128269; Search bookings…" onkeyup="filterTable()">
                    </div>
                </div>

                <?php if (!empty($bookings)): ?>
                    <div class="table-wrap">
                        <table id="bookingsTable">
                            <thead>
                                <tr>
                                    <th>Sl No</th>
                                    <th>User</th>
                                    <th>Trip Date</th>
                                    <th>Destination</th>
                                    <th>Total Price</th>
                                    <th>Payment Status</th>
                                    <th>Transaction ID</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bookings as $index => $b): ?>
                                    <?php
                                        $status    = strtolower((string) ($b['payment_status'] ?? 'pending'));
                                        $pillClass = 'pending';
                                        if (in_array($status, ['paid', 'completed', 'success'], true)) {
                                            $pillClass = 'paid';
                                        } elseif (in_array($status, ['failed', 'cancelled', 'refunded'], true)) {
                                            $pillClass = 'failed';
                                        } elseif (in_array($status, ['processing', 'initiated'], true)) {
                                            $pillClass = 'info';
                                        }
                                    ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo h($b['user_identifier']); ?></td>
                                        <td><?php echo h($b['trip_date']); ?></td>
                                        <td><?php echo h($b['destination_id']); ?></td>
                                        <td><span class="price-tag">₹<?php echo number_format((float) $b['total_price'], 2); ?></span></td>
                                        <td>
                                            <span class="status-pill <?php echo $pillClass; ?>">
                                                <i class="fa fa-circle" style="font-size:8px;"></i>
                                                <?php echo h(ucfirst($b['payment_status'] ?? 'Pending')); ?>
                                            </span>
                                        </td>
                                        <td class="muted"><?php echo $b['transaction_id'] ? h($b['transaction_id']) : '—'; ?></td>
                                        <td>
                                            <div class="action-group">
                                                <a href="view_booking.php?id=<?php echo (int) $b['booking_id']; ?>" class="btn btn-view">
                                                    <i class="fa fa-eye"></i> View
                                                </a>
                                                <form method="POST" class="inline-form" onsubmit="return confirm('Delete this booking permanently? This action cannot be undone.');">
                                                    <input type="hidden" name="action" value="delete_booking">
                                                    <input type="hidden" name="booking_id" value="<?php echo (int) $b['booking_id']; ?>">
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
                        <i class="fa fa-calendar-times"></i>
                        <p>No bookings found yet. Bookings placed by customers will appear here.</p>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <script>
        function filterTable() {
            const input  = document.getElementById('bookingSearch');
            const filter = input.value.toLowerCase();
            const rows   = document.querySelectorAll('#bookingsTable tbody tr');

            rows.forEach(function (row) {
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        }

        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            if (window.innerWidth <= 768) {
                sidebar.style.transform = sidebar.style.transform === 'translateX(0px)' ? 'translateX(-100%)' : 'translateX(0)';
            }
        }
    </script>
</body>

</html>
