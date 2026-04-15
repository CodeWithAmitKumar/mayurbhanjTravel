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

$bookingId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($bookingId <= 0) {
    header('Location: allbookings.php');
    exit();
}

// Fetch full booking with JOINs
$query = "
    SELECT b.*,
           d.titel        AS dest_name,  d.price  AS dest_price,
           g.guide_name,                 g.price  AS guide_price,
           t.vehicle_details,            t.price  AS transport_price,
           h.hotel_name,                 h.price  AS hotel_price,
           p.payment_mode, p.utr_no, p.payment_date
    FROM bookings b
    LEFT JOIN all_destinations d ON b.destination_id = d.destination_id
    LEFT JOIN guides           g ON b.guide_id        = g.guide_id
    LEFT JOIN transports       t ON b.transport_id    = t.transport_id
    LEFT JOIN hotels           h ON b.hotel_id        = h.hotel_id
    LEFT JOIN payments         p ON b.booking_id      = p.booking_id
    WHERE b.booking_id = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'i', $bookingId);
mysqli_stmt_execute($stmt);
$result  = mysqli_stmt_get_result($stmt);
$booking = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$booking) {
    header('Location: allbookings.php');
    exit();
}

// Fetch travelers
$members = [];
$mStmt = mysqli_prepare($conn, "SELECT * FROM booking_members WHERE booking_id = ? ORDER BY member_id ASC");
mysqli_stmt_bind_param($mStmt, 'i', $bookingId);
mysqli_stmt_execute($mStmt);
$mResult = mysqli_stmt_get_result($mStmt);
while ($row = mysqli_fetch_assoc($mResult)) {
    $members[] = $row;
}
mysqli_stmt_close($mStmt);

function cleanPrice($val)
{
    return (float) preg_replace('/[^0-9.]/', '', (string) $val);
}

$pDest  = cleanPrice($booking['dest_price']      ?? 0);
$pGuide = cleanPrice($booking['guide_price']     ?? 0);
$pTrans = cleanPrice($booking['transport_price'] ?? 0);
$pHotel = cleanPrice($booking['hotel_price']     ?? 0);

$paymentStatus = strtolower((string) ($booking['payment_status'] ?? 'pending'));
$pillClass = 'pending';
if (in_array($paymentStatus, ['paid', 'success', 'completed'], true)) {
    $pillClass = 'paid';
} elseif (in_array($paymentStatus, ['failed', 'cancelled', 'refunded'], true)) {
    $pillClass = 'failed';
} elseif (in_array($paymentStatus, ['processing', 'initiated'], true)) {
    $pillClass = 'info';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Booking #<?php echo $bookingId; ?> - Mayurbhanj Tourism Planner</title>
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

        /* ───── Sidebar ───── */
        .sidebar {
            position: fixed;
            left: 0; top: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: linear-gradient(180deg, var(--dark), #2d2d3a);
            z-index: 1000;
            overflow-y: auto;
            box-shadow: 4px 0 20px rgba(0,0,0,.15);
        }
        .sidebar-brand {
            height: var(--header-height);
            display: flex; align-items: center;
            padding: 0 25px;
            border-bottom: 1px solid rgba(255,255,255,.1);
        }
        .sidebar-brand h1 { color: var(--white); font-size: 20px; font-weight: 700; display: flex; align-items: center; gap: 10px; }
        .sidebar-brand i  { color: var(--primary); }
        .sidebar-menu     { padding: 20px 0; }
        .menu-section     { padding: 0 20px; margin-bottom: 10px; }
        .menu-section-title { color: rgba(255,255,255,.4); font-size: 11px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px; font-weight: 600; }
        .menu-item {
            display: flex; align-items: center;
            padding: 14px 20px; color: rgba(255,255,255,.7);
            text-decoration: none; border-radius: 10px;
            margin: 5px 10px; font-size: 14px; font-weight: 500; transition: .3s ease;
        }
        .menu-item:hover { background: rgba(255,255,255,.1); color: var(--white); transform: translateX(5px); }
        .menu-item.active { background: var(--primary); color: var(--white); box-shadow: 0 4px 15px rgba(134,184,23,.4); }
        .menu-item i { width: 20px; margin-right: 12px; font-size: 16px; }
        .menu-item .badge { margin-left: auto; background: rgba(255,255,255,.2); padding: 2px 8px; border-radius: 20px; font-size: 11px; }

        /* ───── Header ───── */
        .header {
            height: var(--header-height);
            background: var(--white);
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 30px;
            box-shadow: 0 1px 3px rgba(0,0,0,.1);
            position: sticky; top: 0; z-index: 100;
        }
        .header-left { display: flex; align-items: center; gap: 20px; }
        .toggle-sidebar { width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; background: var(--bg); border-radius: 10px; cursor: pointer; border: none; font-size: 18px; color: var(--text); }
        .page-title { font-size: 18px; font-weight: 600; }
        .header-right { display: flex; align-items: center; }
        .user-avatar { width: 40px; height: 40px; background: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--white); font-weight: 600; }

        /* ───── Layout ───── */
        .main-content { margin-left: var(--sidebar-width); min-height: 100vh; }
        .content { padding: 30px; }

        /* ───── Page Header ───── */
        .page-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: var(--white); border-radius: 20px; padding: 30px 34px;
            margin-bottom: 24px; position: relative; overflow: hidden;
            display: flex; align-items: center; justify-content: space-between; gap: 20px;
        }
        .page-header::before {
            content: ''; position: absolute;
            width: 320px; height: 320px; right: -90px; top: -120px;
            background: rgba(255,255,255,.12); border-radius: 50%;
        }
        .page-header h1 { margin: 0 0 6px; font-size: 26px; }
        .page-header p  { margin: 0; opacity: .9; font-size: 14px; }
        .btn-back {
            display: inline-flex; align-items: center; gap: 8px;
            background: rgba(20,20,31,.18); color: var(--white);
            text-decoration: none; border-radius: 12px; padding: 12px 18px;
            font-weight: 700; font-size: 14px; white-space: nowrap; z-index: 1;
        }
        .btn-back:hover { background: rgba(20,20,31,.32); }

        /* ───── Cards / Grids ───── */
        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .detail-card {
            background: var(--white);
            border-radius: 18px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .card-heading {
            background: #fafafa;
            border-bottom: 1px solid var(--border);
            padding: 16px 22px;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: var(--muted);
            display: flex; align-items: center; gap: 10px;
        }
        .card-heading i { color: var(--primary); font-size: 15px; }

        .card-body { padding: 20px 22px; }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            padding: 10px 0;
            border-bottom: 1px solid var(--border);
            font-size: 14px;
            gap: 16px;
        }
        .info-row:last-child { border-bottom: none; }
        .info-label { color: var(--muted); white-space: nowrap; flex-shrink: 0; }
        .info-value { font-weight: 600; text-align: right; word-break: break-word; }

        /* ───── Status pill ───── */
        .status-pill {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 5px 12px; border-radius: 999px;
            font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px;
        }
        .status-pill.paid    { background: var(--success-bg); color: var(--success); }
        .status-pill.pending { background: var(--warning-bg); color: var(--warning); }
        .status-pill.failed  { background: var(--danger-bg);  color: var(--danger);  }
        .status-pill.info    { background: var(--info-bg);    color: var(--info);    }

        /* ───── Price breakdown card (full width) ───── */
        .full-card {
            background: var(--white);
            border-radius: 18px;
            box-shadow: var(--shadow);
            overflow: hidden;
            margin-bottom: 20px;
        }

        .price-table {
            width: 100%;
            border-collapse: collapse;
        }
        .price-table th, .price-table td {
            padding: 14px 22px;
            text-align: left;
            border-bottom: 1px solid var(--border);
            font-size: 14px;
        }
        .price-table th { background: #fafafa; color: var(--muted); font-size: 12px; text-transform: uppercase; letter-spacing: .6px; }
        .price-table td { font-weight: 500; }
        .price-table .total-row td { font-size: 16px; font-weight: 700; background: #f0fdf4; color: var(--success); border-top: 2px solid var(--border); border-bottom: none; }
        .text-right { text-align: right !important; }

        /* ───── Travelers table ───── */
        .travelers-table { width: 100%; border-collapse: collapse; }
        .travelers-table th, .travelers-table td { padding: 13px 18px; text-align: left; border-bottom: 1px solid var(--border); font-size: 14px; }
        .travelers-table th { background: #fafafa; color: var(--muted); font-size: 12px; text-transform: uppercase; letter-spacing: .6px; }
        .travelers-table tbody tr:last-child td { border-bottom: none; }

        /* ───── Print button ───── */
        .btn-print {
            display: inline-flex; align-items: center; gap: 8px;
            background: var(--dark); color: var(--white);
            border: none; border-radius: 12px; padding: 11px 18px;
            font: 700 14px 'Heebo', sans-serif; cursor: pointer; text-decoration: none;
        }
        .btn-print:hover { background: #2d2d3a; }

        /* ───── Responsive ───── */
        @media (max-width: 900px) {
            .detail-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .main-content { margin-left: 0; }
            .content { padding: 20px; }
            .page-header { flex-direction: column; align-items: flex-start; }
            .page-header h1 { font-size: 22px; }
        }
    </style>
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <?php include 'header.php'; ?>

        <div class="content">

            <!-- Page Header -->
            <div class="page-header">
                <div>
                    <h1><i class="fa fa-calendar-check"></i> Booking #<?php echo h($booking['booking_id']); ?></h1>
                    <p>Full details for booking by <strong><?php echo h($booking['user_identifier']); ?></strong></p>
                </div>
                <div style="display:flex;gap:10px;z-index:1;">
                    <!-- <a href="print_receipt_admin.php?id=<?php echo $bookingId; ?>" target="_blank" class="btn-print">
                        <i class="fa fa-print"></i> Print Receipt
                    </a> -->
                    <a href="allbookings.php" class="btn-back"><i class="fa fa-arrow-left"></i> Back to Bookings</a>
                </div>
            </div>

            <!-- ── Row 1: Booking Info + Payment Info ── -->
            <div class="detail-grid">

                <!-- Booking Info -->
                <div class="detail-card">
                    <div class="card-heading"><i class="fa fa-info-circle"></i> Booking Information</div>
                    <div class="card-body">
                        <div class="info-row">
                            <span class="info-label">Booking ID</span>
                            <span class="info-value">#<?php echo h($booking['booking_id']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Transaction ID</span>
                            <span class="info-value"><?php echo $booking['transaction_id'] ? h($booking['transaction_id']) : '—'; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">User / Email</span>
                            <span class="info-value"><?php echo h($booking['user_identifier']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Trip Date</span>
                            <span class="info-value"><?php echo h(date('d M Y', strtotime($booking['trip_date']))); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Start From</span>
                            <span class="info-value"><?php echo h($booking['start_from']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Booked On</span>
                            <span class="info-value"><?php echo h(date('d M Y, H:i', strtotime($booking['created_at']))); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Last Updated</span>
                            <span class="info-value"><?php echo h(date('d M Y, H:i', strtotime($booking['updated_at']))); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Payment Info -->
                <div class="detail-card">
                    <div class="card-heading"><i class="fa fa-credit-card"></i> Payment Information</div>
                    <div class="card-body">
                        <div class="info-row">
                            <span class="info-label">Payment Status</span>
                            <span class="info-value">
                                <span class="status-pill <?php echo $pillClass; ?>">
                                    <i class="fa fa-circle" style="font-size:8px;"></i>
                                    <?php echo h(ucfirst($booking['payment_status'] ?? 'Pending')); ?>
                                </span>
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Total Price</span>
                            <span class="info-value" style="color:var(--primary-dark);font-size:18px;">₹<?php echo number_format((float)$booking['total_price'], 2); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Payment Mode</span>
                            <span class="info-value">
                                <?php
                                    $mode = strtolower((string)($booking['payment_mode'] ?? ''));
                                    echo $mode === 'online' ? '<i class="fa fa-wifi"></i> Online (UPI)' : ($mode === '' ? '—' : h(ucfirst($mode)));
                                ?>
                            </span>
                        </div>
                        <?php if (!empty($booking['utr_no'])): ?>
                        <div class="info-row">
                            <span class="info-label">UTR / Ref No</span>
                            <span class="info-value"><?php echo h($booking['utr_no']); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($booking['payment_date'])): ?>
                        <div class="info-row">
                            <span class="info-label">Payment Date</span>
                            <span class="info-value"><?php echo h(date('d M Y', strtotime($booking['payment_date']))); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- ── Row 2: Services Info ── -->
            <div class="detail-grid">

                <!-- Destination & Guide -->
                <div class="detail-card">
                    <div class="card-heading"><i class="fa fa-map-marker-alt"></i> Destination & Guide</div>
                    <div class="card-body">
                        <div class="info-row">
                            <span class="info-label">Destination</span>
                            <span class="info-value"><?php echo $booking['dest_name'] ? h($booking['dest_name']) : '<em style="color:var(--muted)">Not assigned</em>'; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Destination Price</span>
                            <span class="info-value">₹<?php echo number_format($pDest, 2); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Guide</span>
                            <span class="info-value"><?php echo $booking['guide_name'] ? h($booking['guide_name']) : '<em style="color:var(--muted)">No guide</em>'; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Guide Fee</span>
                            <span class="info-value">₹<?php echo number_format($pGuide, 2); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Transport & Hotel -->
                <div class="detail-card">
                    <div class="card-heading"><i class="fa fa-car"></i> Transport & Hotel</div>
                    <div class="card-body">
                        <div class="info-row">
                            <span class="info-label">Vehicle</span>
                            <span class="info-value"><?php echo $booking['vehicle_details'] ? h($booking['vehicle_details']) : '<em style="color:var(--muted)">No transport</em>'; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Transport Price</span>
                            <span class="info-value">₹<?php echo number_format($pTrans, 2); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Hotel</span>
                            <span class="info-value"><?php echo $booking['hotel_name'] ? h($booking['hotel_name']) : '<em style="color:var(--muted)">No hotel</em>'; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Hotel Price</span>
                            <span class="info-value">₹<?php echo number_format($pHotel, 2); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Price Breakdown ── -->
            <div class="full-card">
                <div class="card-heading" style="padding:16px 22px;"><i class="fa fa-receipt"></i> Price Breakdown</div>
                <table class="price-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th class="text-right">Amount (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($booking['dest_name']): ?>
                        <tr>
                            <td>Destination Package — <strong><?php echo h($booking['dest_name']); ?></strong></td>
                            <td class="text-right">₹<?php echo number_format($pDest, 2); ?></td>
                        </tr>
                        <?php endif; ?>

                        <?php if ($booking['guide_name']): ?>
                        <tr>
                            <td>Tour Guide Fee — <strong><?php echo h($booking['guide_name']); ?></strong></td>
                            <td class="text-right">₹<?php echo number_format($pGuide, 2); ?></td>
                        </tr>
                        <?php endif; ?>

                        <?php if ($booking['vehicle_details']): ?>
                        <tr>
                            <td>Transportation — <strong><?php echo h($booking['vehicle_details']); ?></strong></td>
                            <td class="text-right">₹<?php echo number_format($pTrans, 2); ?></td>
                        </tr>
                        <?php endif; ?>

                        <?php if ($booking['hotel_name']): ?>
                        <tr>
                            <td>Accommodation — <strong><?php echo h($booking['hotel_name']); ?></strong></td>
                            <td class="text-right">₹<?php echo number_format($pHotel, 2); ?></td>
                        </tr>
                        <?php endif; ?>

                        <tr class="total-row">
                            <td><i class="fa fa-check-circle"></i> Total Amount</td>
                            <td class="text-right">₹<?php echo number_format((float) $booking['total_price'], 2); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- ── Travelers ── -->
            <div class="full-card">
                <div class="card-heading" style="padding:16px 22px;">
                    <i class="fa fa-users"></i> Traveler Details
                    <span style="margin-left:auto;background:var(--primary);color:#fff;padding:2px 10px;border-radius:20px;font-size:12px;">
                        <?php echo count($members); ?> Person(s)
                    </span>
                </div>
                <?php if (!empty($members)): ?>
                    <div style="overflow-x:auto;">
                        <table class="travelers-table">
                            <thead>
                                <tr>
                                    <th>Slno</th>
                                    <th>Name</th>
                                    <th>Age</th>
                                    <th>Gender</th>
                                    <th>ID Proof Number</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($members as $i => $m): ?>
                                    <tr>
                                        <td><?php echo $i + 1; ?></td>
                                        <td><strong><?php echo h($m['member_name']); ?></strong></td>
                                        <td><?php echo h($m['member_age']); ?></td>
                                        <td><?php echo h($m['member_gender']); ?></td>
                                        <td><?php echo h($m['member_id_proof']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div style="padding:30px 22px;color:var(--muted);font-size:14px;">
                        <i class="fa fa-info-circle"></i> No traveler / member records found for this booking.
                    </div>
                <?php endif; ?>
            </div>

        </div><!-- /content -->
    </div><!-- /main-content -->

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
