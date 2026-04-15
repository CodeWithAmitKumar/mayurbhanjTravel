<?php
session_start();
include '../config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit();
}

$admin_id = (int) $_SESSION['admin_id'];
$username = $_SESSION['username'] ?? 'Admin';

// Fetch admin name from admin_profile table (filtered by admin_id)
$_profileRes = mysqli_query($conn, "SELECT `name` FROM `admin_profile` WHERE `admin_id` = $admin_id LIMIT 1");
if ($_profileRes && mysqli_num_rows($_profileRes) > 0) {
    $_profileRow = mysqli_fetch_assoc($_profileRes);
    if (!empty($_profileRow['name'])) {
        $username = $_profileRow['name'];
    }
} else {
    // Fallback: try getting any profile row if no admin_id match
    $_fallbackRes = mysqli_query($conn, "SELECT `name` FROM `admin_profile` LIMIT 1");
    if ($_fallbackRes && mysqli_num_rows($_fallbackRes) > 0) {
        $_fallbackRow = mysqli_fetch_assoc($_fallbackRes);
        if (!empty($_fallbackRow['name'])) {
            $username = $_fallbackRow['name'];
        }
    }
}

// ── Live Stats ──────────────────────────────────────────────────────────────

// Total Bookings
$totalBookings = 0;
$r = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM bookings");
if ($r) $totalBookings = (int) mysqli_fetch_assoc($r)['cnt'];

// Paid Bookings
$paidBookings = 0;
$r = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM bookings WHERE LOWER(payment_status) IN ('paid','success','completed')");
if ($r) $paidBookings = (int) mysqli_fetch_assoc($r)['cnt'];

// Total Revenue
$totalRevenue = 0;
$r = mysqli_query($conn, "SELECT SUM(total_price) AS rev FROM bookings WHERE LOWER(payment_status) IN ('paid','success','completed')");
if ($r) $totalRevenue = (float) (mysqli_fetch_assoc($r)['rev'] ?? 0);

// Total Users
$totalUsers = 0;
$r = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM frontend_users");
if ($r) $totalUsers = (int) mysqli_fetch_assoc($r)['cnt'];

// Total Destinations (destination_page table)
$totalDestinations = 0;
$r = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM destination_page");
if ($r) $totalDestinations = (int) mysqli_fetch_assoc($r)['cnt'];

// Total Hotels
$totalHotels = 0;
$r = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM hotels");
if ($r) $totalHotels = (int) mysqli_fetch_assoc($r)['cnt'];

// Active Hotels
$activeHotels = 0;
$r = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM hotels WHERE is_active = 1");
if ($r) $activeHotels = (int) mysqli_fetch_assoc($r)['cnt'];

// Recent 6 Bookings
$recentBookings = [];
$r = mysqli_query($conn, "
    SELECT b.booking_id, b.user_identifier, b.trip_date, b.total_price, b.payment_status, b.created_at,
           d.titel AS dest_name
    FROM bookings b
    LEFT JOIN all_destinations d ON b.destination_id = d.destination_id
    ORDER BY b.booking_id DESC
    LIMIT 6
");
if ($r) {
    while ($row = mysqli_fetch_assoc($r)) $recentBookings[] = $row;
}

// Recent 5 Users
$recentUsers = [];
$r = mysqli_query($conn, "SELECT id, name, email, gender, created_at FROM frontend_users ORDER BY id DESC LIMIT 5");
if ($r) {
    while ($row = mysqli_fetch_assoc($r)) $recentUsers[] = $row;
}

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Admin Dashboard </title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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
            --shadow: 0 4px 20px rgba(0,0,0,.08);
            --sidebar-width: 260px;
            --header-height: 70px;

            /* accent palette */
            --blue:   #3b82f6;
            --purple: #8b5cf6;
            --orange: #f97316;
            --teal:   #14b8a6;
            --rose:   #f43f5e;
            --indigo: #6366f1;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Heebo', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
        }

        /* ── Sidebar ── */
        .sidebar {
            position: fixed; left: 0; top: 0;
            width: var(--sidebar-width); height: 100vh;
            background: linear-gradient(180deg, var(--dark) 0%, #2d2d3a 100%);
            z-index: 1000; overflow-y: auto;
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
        .menu-item { display: flex; align-items: center; padding: 14px 20px; color: rgba(255,255,255,.7); text-decoration: none; border-radius: 10px; margin: 5px 10px; font-size: 14px; font-weight: 500; transition: .3s; }
        .menu-item:hover { background: rgba(255,255,255,.1); color: var(--white); transform: translateX(5px); }
        .menu-item.active { background: var(--primary); color: var(--white); box-shadow: 0 4px 15px rgba(134,184,23,.4); }
        .menu-item i { width: 20px; margin-right: 12px; font-size: 16px; }
        .menu-item .badge { margin-left: auto; background: rgba(255,255,255,.2); padding: 2px 8px; border-radius: 20px; font-size: 11px; }

        /* ── Header ── */
        .header {
            height: var(--header-height); background: var(--white);
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 30px; box-shadow: 0 1px 3px rgba(0,0,0,.1);
            position: sticky; top: 0; z-index: 100;
        }
        .header-left { display: flex; align-items: center; gap: 20px; }
        .toggle-sidebar { width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; background: var(--bg); border-radius: 10px; cursor: pointer; border: none; font-size: 18px; color: var(--text); }
        .page-title { font-size: 18px; font-weight: 600; }
        .header-right { display: flex; align-items: center; }
        .user-avatar { width: 40px; height: 40px; background: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--white); font-weight: 700; font-size: 15px; }

        /* ── Layout ── */
        .main-content { margin-left: var(--sidebar-width); min-height: 100vh; }
        .content { padding: 30px; }

        /* ── Hero Banner ── */
        .hero {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 60%, #4d7009 100%);
            border-radius: 24px; padding: 40px 44px;
            margin-bottom: 28px; position: relative; overflow: hidden;
            color: var(--white);
            display: flex; align-items: center; justify-content: space-between; gap: 20px;
        }
        .hero::before {
            content: ''; position: absolute;
            width: 380px; height: 380px; right: -100px; top: -140px;
            background: rgba(255,255,255,.1); border-radius: 50%;
        }
        .hero::after {
            content: ''; position: absolute;
            width: 200px; height: 200px; right: 120px; bottom: -80px;
            background: rgba(255,255,255,.06); border-radius: 50%;
        }
        .hero-text { position: relative; z-index: 1; }
        .hero-text h1 { font-size: 32px; font-weight: 800; margin-bottom: 10px; letter-spacing: -.3px; }
        .hero-text p  { font-size: 15px; opacity: .9; max-width: 480px; line-height: 1.6; }
        .hero-badge {
            position: relative; z-index: 1;
            background: rgba(255,255,255,.18);
            border-radius: 20px; padding: 18px 28px;
            text-align: center; white-space: nowrap;
            backdrop-filter: blur(6px);
        }
        .hero-badge .big { font-size: 38px; font-weight: 800; display: block; line-height: 1; }
        .hero-badge .lbl { font-size: 13px; opacity: .85; margin-top: 4px; }

        /* ── Stat Cards ── */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 28px;
        }

        .stat-card {
            background: var(--white);
            border-radius: 20px;
            padding: 26px 24px;
            box-shadow: var(--shadow);
            position: relative; overflow: hidden;
            transition: transform .25s, box-shadow .25s;
            cursor: default;
        }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 12px 32px rgba(0,0,0,.12); }

        .stat-card .accent-bar {
            position: absolute; top: 0; left: 0;
            width: 100%; height: 4px;
            border-radius: 20px 20px 0 0;
        }

        .stat-top { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 18px; }

        .stat-icon-wrap {
            width: 52px; height: 52px; border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 22px;
        }

        .stat-num { font-size: 38px; font-weight: 800; line-height: 1; margin-bottom: 6px; }
        .stat-label { font-size: 13px; color: var(--muted); font-weight: 500; }

        .stat-footer {
            display: flex; align-items: center; gap: 6px;
            margin-top: 14px; padding-top: 14px;
            border-top: 1px solid var(--border);
            font-size: 12px; color: var(--muted);
        }
        .stat-footer .up   { color: #22c55e; font-weight: 700; }
        .stat-footer .down { color: #ef4444; font-weight: 700; }

        /* color variants */
        .c-green  .accent-bar { background: linear-gradient(90deg, var(--primary), var(--primary-dark)); }
        .c-green  .stat-icon-wrap { background: rgba(134,184,23,.12); color: var(--primary); }
        .c-green  .stat-num { color: var(--primary-dark); }

        .c-blue   .accent-bar { background: linear-gradient(90deg, var(--blue), #2563eb); }
        .c-blue   .stat-icon-wrap { background: rgba(59,130,246,.12); color: var(--blue); }
        .c-blue   .stat-num { color: var(--blue); }

        .c-purple .accent-bar { background: linear-gradient(90deg, var(--purple), #7c3aed); }
        .c-purple .stat-icon-wrap { background: rgba(139,92,246,.12); color: var(--purple); }
        .c-purple .stat-num { color: var(--purple); }

        .c-orange .accent-bar { background: linear-gradient(90deg, var(--orange), #ea580c); }
        .c-orange .stat-icon-wrap { background: rgba(249,115,22,.12); color: var(--orange); }
        .c-orange .stat-num { color: var(--orange); }

        .c-teal   .accent-bar { background: linear-gradient(90deg, var(--teal), #0d9488); }
        .c-teal   .stat-icon-wrap { background: rgba(20,184,166,.12); color: var(--teal); }
        .c-teal   .stat-num { color: var(--teal); }

        .c-rose   .accent-bar { background: linear-gradient(90deg, var(--rose), #e11d48); }
        .c-rose   .stat-icon-wrap { background: rgba(244,63,94,.12); color: var(--rose); }
        .c-rose   .stat-num { color: var(--rose); }

        .c-indigo .accent-bar { background: linear-gradient(90deg, var(--indigo), #4f46e5); }
        .c-indigo .stat-icon-wrap { background: rgba(99,102,241,.12); color: var(--indigo); }
        .c-indigo .stat-num { color: var(--indigo); }

        /* ── Two-Column Section ── */
        .two-col { display: grid; grid-template-columns: 1.6fr 1fr; gap: 20px; margin-bottom: 28px; }

        /* ── Table Card ── */
        .card {
            background: var(--white); border-radius: 20px;
            box-shadow: var(--shadow); overflow: hidden;
        }
        .card-head {
            padding: 20px 24px; border-bottom: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between;
        }
        .card-head h2 { font-size: 17px; font-weight: 700; display: flex; align-items: center; gap: 10px; }
        .card-head h2 i { color: var(--primary); }
        .view-link { color: var(--primary); text-decoration: none; font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 5px; }
        .view-link:hover { color: var(--primary-dark); }

        table.dash-table { width: 100%; border-collapse: collapse; }
        table.dash-table th,
        table.dash-table td { padding: 13px 16px; text-align: left; border-bottom: 1px solid var(--border); font-size: 13px; }
        table.dash-table th { background: #fafafa; color: var(--muted); font-size: 11px; text-transform: uppercase; letter-spacing: .6px; font-weight: 700; }
        table.dash-table tbody tr:hover { background: #fafff0; }
        table.dash-table tbody tr:last-child td { border-bottom: none; }

        .pill {
            display: inline-flex; align-items: center; gap: 4px;
            padding: 4px 10px; border-radius: 999px;
            font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .3px;
        }
        .pill.paid    { background: #dcfce7; color: #166534; }
        .pill.pending { background: #ffedd5; color: #9a3412; }
        .pill.failed  { background: #fee2e2; color: #b91c1c; }
        .pill.other   { background: #dbeafe; color: #1e40af; }

        .price-val { font-weight: 700; color: var(--primary-dark); }

        /* ── User list ── */
        .user-list { padding: 12px 16px; display: flex; flex-direction: column; gap: 4px; }
        .user-row {
            display: flex; align-items: center; gap: 12px;
            padding: 10px 10px; border-radius: 12px; transition: .2s;
        }
        .user-row:hover { background: var(--bg); }
        .u-avatar {
            width: 38px; height: 38px; border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-weight: 700; font-size: 14px; flex-shrink: 0;
        }
        .u-info { flex: 1; min-width: 0; }
        .u-name  { font-size: 13px; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .u-email { font-size: 11px; color: var(--muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .u-date  { font-size: 11px; color: var(--muted); flex-shrink: 0; }

        /* ── Quick Links ── */
        .quick-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 16px; margin-bottom: 28px; }
        .quick-card {
            background: var(--white); border-radius: 18px;
            padding: 22px 18px; text-align: center;
            box-shadow: var(--shadow); text-decoration: none; color: var(--text);
            border: 2px solid transparent; transition: .25s;
        }
        .quick-card:hover { border-color: var(--primary); transform: translateY(-4px); box-shadow: 0 12px 30px rgba(134,184,23,.18); }
        .quick-card .q-icon {
            width: 56px; height: 56px; border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            margin: 0 auto 14px; display: flex; align-items: center; justify-content: center;
            font-size: 22px; color: #fff;
            transition: transform .25s, box-shadow .25s;
        }
        .quick-card:hover .q-icon { transform: scale(1.1); box-shadow: 0 8px 24px rgba(134,184,23,.35); }
        .quick-card .q-title { font-size: 14px; font-weight: 700; }
        .quick-card .q-sub   { font-size: 12px; color: var(--muted); margin-top: 4px; }

        /* ── Responsive ── */
        @media (max-width: 1200px) {
            .stats-grid { grid-template-columns: repeat(2,1fr); }
            .quick-grid  { grid-template-columns: repeat(2,1fr); }
        }
        @media (max-width: 900px) {
            .two-col { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .main-content { margin-left: 0; }
            .content { padding: 16px; }
            .hero { flex-direction: column; }
            .hero-text h1 { font-size: 24px; }
            .stats-grid { grid-template-columns: repeat(2,1fr); gap: 14px; }
            .quick-grid  { grid-template-columns: repeat(2,1fr); }
        }
        @media (max-width: 480px) {
            .stats-grid { grid-template-columns: 1fr; }
            .quick-grid  { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <?php include 'header.php'; ?>

        <div class="content">

            <!-- ── Hero ── -->
            <div class="hero">
                <div class="hero-text">
                    <h1>Welcome back, <?php echo h($username); ?>! 👋</h1>
                    
                </div>
                <div class="hero-badge">
                    <span class="big"><?php echo $totalBookings; ?></span>
                    <div class="lbl">Total Bookings</div>
                </div>
            </div>

            <!-- ── Stat Cards ── -->
            <div class="stats-grid">

                <!-- Total Bookings -->
                <div class="stat-card c-green">
                    <div class="accent-bar"></div>
                    <div class="stat-top">
                        <div>
                            <div class="stat-num"><?php echo $totalBookings; ?></div>
                            <div class="stat-label">Total Bookings</div>
                        </div>
                        <div class="stat-icon-wrap"><i class="fa fa-calendar-check"></i></div>
                    </div>
                    <div class="stat-footer">
                        <span class="up"><i class="fa fa-check-circle"></i> <?php echo $paidBookings; ?> Paid</span>
                        &nbsp;·&nbsp; <?php echo $totalBookings - $paidBookings; ?> Pending/Other
                    </div>
                </div>

                <!-- Total Revenue -->
                <div class="stat-card c-teal">
                    <div class="accent-bar"></div>
                    <div class="stat-top">
                        <div>
                            <div class="stat-num" style="font-size:28px;">₹<?php echo number_format($totalRevenue, 0); ?></div>
                            <div class="stat-label">Total Revenue (Paid)</div>
                        </div>
                        <div class="stat-icon-wrap"><i class="fa fa-rupee-sign"></i></div>
                    </div>
                    <div class="stat-footer">
                        <i class="fa fa-info-circle"></i> From <?php echo $paidBookings; ?> paid bookings
                    </div>
                </div>

                <!-- Total Users -->
                <div class="stat-card c-blue">
                    <div class="accent-bar"></div>
                    <div class="stat-top">
                        <div>
                            <div class="stat-num"><?php echo $totalUsers; ?></div>
                            <div class="stat-label">Registered Users</div>
                        </div>
                        <div class="stat-icon-wrap"><i class="fa fa-users"></i></div>
                    </div>
                    <div class="stat-footer">
                        <i class="fa fa-user-plus"></i> Frontend user accounts
                    </div>
                </div>

                <!-- Total Destinations -->
                <div class="stat-card c-purple">
                    <div class="accent-bar"></div>
                    <div class="stat-top">
                        <div>
                            <div class="stat-num"><?php echo $totalDestinations; ?></div>
                            <div class="stat-label">Destinations</div>
                        </div>
                        <div class="stat-icon-wrap"><i class="fa fa-map-marker-alt"></i></div>
                    </div>
                    <div class="stat-footer">
                        <i class="fa fa-globe"></i> Destination page records
                    </div>
                </div>

                <!-- Total Hotels -->
                <div class="stat-card c-orange">
                    <div class="accent-bar"></div>
                    <div class="stat-top">
                        <div>
                            <div class="stat-num"><?php echo $totalHotels; ?></div>
                            <div class="stat-label">Total Hotels</div>
                        </div>
                        <div class="stat-icon-wrap"><i class="fa fa-hotel"></i></div>
                    </div>
                    <div class="stat-footer">
                        <span class="up"><?php echo $activeHotels; ?> Active</span>
                        &nbsp;·&nbsp; <?php echo $totalHotels - $activeHotels; ?> Inactive
                    </div>
                </div>



                <!-- Active Hotels repeated as quick ref -->
                <div class="stat-card c-green" style="background: linear-gradient(135deg,#f0fdf4,#dcfce7);">
                    <div class="accent-bar" style="background:linear-gradient(90deg,#22c55e,#16a34a);"></div>
                    <div class="stat-top">
                        <div>
                            <div class="stat-num" style="color:#166534;"><?php echo $activeHotels; ?></div>
                            <div class="stat-label">Active Hotels</div>
                        </div>
                        <div class="stat-icon-wrap" style="background:rgba(34,197,94,.15);color:#22c55e;"><i class="fa fa-bed"></i></div>
                    </div>
                    <div class="stat-footer" style="border-color:#bbf7d0;">
                        <i class="fa fa-hotel"></i> Out of <?php echo $totalHotels; ?> total hotels
                    </div>
                </div>

            </div>

            <!-- ── Quick Links ── -->
            <div class="quick-grid">
                <a href="allbookings.php" class="quick-card">
                    <div class="q-icon"><i class="fa fa-calendar-check"></i></div>
                    <div class="q-title">All Bookings</div>
                    <div class="q-sub">Manage & view bookings</div>
                </a>
                <a href="allguides.php" class="quick-card">
                    <div class="q-icon"><i class="fa fa-user-tie"></i></div>
                    <div class="q-title">Guides</div>
                    <div class="q-sub">Manage tour guides</div>
                </a>
                <a href="allhotels.php" class="quick-card">
                    <div class="q-icon"><i class="fa fa-hotel"></i></div>
                    <div class="q-title">Hotels</div>
                    <div class="q-sub">Manage hotel listings</div>
                </a>
                <a href="alltransport.php" class="quick-card">
                    <div class="q-icon"><i class="fa fa-bus"></i></div>
                    <div class="q-title">Transport</div>
                    <div class="q-sub">Manage vehicles</div>
                </a>
            </div>

            <!-- ── Recent Bookings + Recent Users ── -->
            <div class="two-col">

                <!-- Recent Bookings Table -->
                <div class="card">
                    <div class="card-head">
                        <h2><i class="fa fa-calendar-alt"></i> Recent Bookings</h2>
                        <a href="allbookings.php" class="view-link">View All <i class="fa fa-arrow-right"></i></a>
                    </div>
                    <div style="overflow-x:auto;">
                        <table class="dash-table">
                            <thead>
                                <tr>
                                    <th>Sl No</th>
                                    <th>User</th>
                                    <th>Destination</th>
                                    <th>Trip Date</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($recentBookings)): ?>
                                    <?php foreach ($recentBookings as $i => $b): ?>
                                        <?php
                                            $ps = strtolower((string)($b['payment_status'] ?? ''));
                                            $pc = in_array($ps, ['paid','success','completed']) ? 'paid'
                                                : (in_array($ps, ['failed','cancelled','refunded']) ? 'failed'
                                                : (in_array($ps, ['processing','initiated']) ? 'other' : 'pending'));
                                        ?>
                                        <tr>
                                            <td><?php echo $i + 1; ?></td>
                                            <td style="max-width:130px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?php echo h($b['user_identifier']); ?></td>
                                            <td><?php echo $b['dest_name'] ? h($b['dest_name']) : '<em style="color:var(--muted)">—</em>'; ?></td>
                                            <td><?php echo h(date('d M Y', strtotime($b['trip_date']))); ?></td>
                                            <td class="price-val">₹<?php echo number_format((float)$b['total_price'], 0); ?></td>
                                            <td>
                                                <span class="pill <?php echo $pc; ?>">
                                                    <?php echo h(ucfirst($b['payment_status'] ?? 'Pending')); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" style="text-align:center;padding:30px;color:var(--muted);">No bookings yet.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Recent Users -->
                <div class="card">
                    <div class="card-head">
                        <h2><i class="fa fa-users"></i> Recent Users</h2>
                        <span style="font-size:13px;color:var(--muted);"><?php echo $totalUsers; ?> total</span>
                    </div>
                    <div class="user-list">
                        <?php if (!empty($recentUsers)): ?>
                            <?php foreach ($recentUsers as $u): ?>
                                <div class="user-row">
                                    <div class="u-avatar"><?php echo strtoupper(mb_substr($u['name'] ?? 'U', 0, 1)); ?></div>
                                    <div class="u-info">
                                        <div class="u-name"><?php echo h($u['name']); ?></div>
                                        <div class="u-email"><?php echo h($u['email']); ?></div>
                                    </div>
                                    <div class="u-date"><?php echo date('d M', strtotime($u['created_at'])); ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div style="padding:30px;text-align:center;color:var(--muted);">No users yet.</div>
                        <?php endif; ?>
                    </div>
                </div>

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

        // Animate stat counters
        document.querySelectorAll('.stat-num').forEach(function(el) {
            const raw  = el.textContent.trim();
            const isRs = raw.startsWith('₹');
            const num  = parseInt(raw.replace(/[^0-9]/g, ''), 10);
            if (isNaN(num) || num === 0) return;

            let start = 0;
            const duration = 900;
            const step = Math.ceil(num / (duration / 16));
            const timer = setInterval(function() {
                start = Math.min(start + step, num);
                el.textContent = (isRs ? '₹' : '') + start.toLocaleString('en-IN');
                if (start >= num) clearInterval(timer);
            }, 16);
        });
    </script>

</body>
</html>
