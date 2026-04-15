<?php
session_start();

// Prevent caching
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['frontend_user'])) {
    header('Location: login.php');
    exit;
}

// Ensure a booking ID was provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: mybookings.php');
    exit;
}

$booking_id = (int)$_GET['id'];

// Safely get the user identifier
if (is_array($_SESSION['frontend_user'])) {
    $user_identifier = $_SESSION['frontend_user']['email'] ?? $_SESSION['frontend_user']['id'] ?? 'User';
} else {
    $user_identifier = $_SESSION['frontend_user'];
}

// ------------------------------------------------------------------
// FETCH BOOKING DETAILS
// ------------------------------------------------------------------
$query = "
    SELECT b.*, 
           d.titel AS dest_name, d.destinationimage AS dest_img, d.price AS dest_price,
           g.guide_name, g.guide_image, g.price AS guide_price,
           t.vehicle_details, t.vehicle_image, t.price AS transport_price,
           h.hotel_name, h.hotel_image, h.price AS hotel_price,
           p.payment_mode, p.utr_no, p.payment_date
    FROM bookings b
    LEFT JOIN all_destinations d ON b.destination_id = d.destination_id
    LEFT JOIN guides g ON b.guide_id = g.guide_id
    LEFT JOIN transports t ON b.transport_id = t.transport_id
    LEFT JOIN hotels h ON b.hotel_id = h.hotel_id
    LEFT JOIN payments p ON b.booking_id = p.booking_id
    WHERE b.booking_id = ? AND b.user_identifier = ?
";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "is", $booking_id, $user_identifier);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$booking = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// If no booking found, or user doesn't own it, redirect back
if (!$booking) {
    header('Location: mybookings.php');
    exit;
}

// Fetch Travelers
$members = [];
$mem_stmt = mysqli_prepare($conn, "SELECT * FROM booking_members WHERE booking_id = ?");
mysqli_stmt_bind_param($mem_stmt, "i", $booking_id);
mysqli_stmt_execute($mem_stmt);
$mem_res = mysqli_stmt_get_result($mem_stmt);
while ($row = mysqli_fetch_assoc($mem_res)) {
    $members[] = $row;
}
mysqli_stmt_close($mem_stmt);

// Calculate individual prices numerically for the summary display
function cleanPrice($priceStr) {
    return (float)preg_replace('/[^0-9.]/', '', (string)$priceStr);
}
$p_dest = cleanPrice($booking['dest_price']);
$p_guide = cleanPrice($booking['guide_price']);
$p_trans = cleanPrice($booking['transport_price']);
$p_hotel = cleanPrice($booking['hotel_price']);

include 'header2.php';
?>

<style>
    .view-receipt-page {
        background: radial-gradient(circle at top left, rgba(13, 110, 253, 0.08), transparent 30%), linear-gradient(180deg, #f7fbff 0%, #eef5fb 100%);
        min-height: calc(100vh - 84px);
        padding: 64px 0 96px;
    }

    .receipt-container {
        max-width: 1000px;
        margin: 0 auto;
    }

    .header-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
    }

    .btn-back {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: #607488;
        font-weight: 700;
        text-decoration: none;
        transition: color 0.2s;
    }
    .btn-back:hover { color: #0d6efd; }

    .btn-print {
        background: rgba(13, 110, 253, 0.1);
        color: #0d6efd;
        padding: 10px 20px;
        border-radius: 12px;
        font-weight: 700;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s;
    }
    .btn-print:hover { background: #0d6efd; color: white; }

    .card-block {
        background: rgba(255, 255, 255, 0.96);
        border: 1px solid #dce7f3;
        border-radius: 28px;
        box-shadow: 0 22px 46px rgba(17, 37, 63, 0.08);
        padding: 40px;
        margin-bottom: 32px;
    }

    .receipt-title {
        color: #15314b;
        font-size: 28px;
        font-weight: 800;
        margin: 0 0 8px 0;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .status-badge {
        font-size: 14px;
        padding: 6px 16px;
        border-radius: 20px;
        font-weight: 800;
        letter-spacing: 0.05em;
        text-transform: uppercase;
    }
    .status-success { background: rgba(25, 135, 84, 0.1); color: #198754; }
    .status-pending { background: rgba(253, 126, 20, 0.1); color: #fd7e14; }

    .receipt-subtitle { color: #607488; font-size: 15px; margin: 0; }

    .divider { height: 2px; background: #eef5fb; margin: 24px 0; }

    .grid-layout {
        display: grid;
        grid-template-columns: 1.5fr 1fr;
        gap: 40px;
    }

    .section-title {
        color: #15314b;
        font-size: 18px;
        font-weight: 800;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .info-list { display: flex; flex-direction: column; gap: 16px; }
    .info-row { display: flex; justify-content: space-between; font-size: 15px; }
    .info-label { color: #607488; font-weight: 600; }
    .info-val { color: #15314b; font-weight: 800; text-align: right; }

    .selected-items { display: flex; flex-direction: column; gap: 12px; }
    .item-card {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 12px;
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 16px;
    }
    .item-card img { width: 48px; height: 48px; border-radius: 10px; object-fit: cover; }
    .item-details h4 { margin: 0 0 2px 0; font-size: 15px; color: #15314b; font-weight: 800; }
    .item-details p { margin: 0; font-size: 12px; color: #607488; font-weight: 600; text-transform: uppercase; }

    .total-box {
        background: linear-gradient(135deg, #f8f9fa, #eef5fb);
        border-radius: 16px;
        padding: 24px;
        margin-top: 24px;
        border: 1px solid #dce7f3;
    }
    .total-row { display: flex; justify-content: space-between; align-items: center; }
    .total-label { color: #15314b; font-size: 18px; font-weight: 800; }
    .total-amount { color: #0d6efd; font-size: 28px; font-weight: 900; }

    table { width: 100%; border-collapse: collapse; }
    th { background: #f8f9fa; color: #607488; font-size: 13px; font-weight: 700; padding: 14px; text-align: left; }
    td { padding: 16px 14px; color: #15314b; font-weight: 600; border-bottom: 1px solid #eef5fb; }
    tr:last-child td { border-bottom: none; }

    @media (max-width: 768px) {
        .grid-layout { grid-template-columns: 1fr; }
        .receipt-title { flex-direction: column; align-items: flex-start; gap: 12px; }
        .card-block { padding: 24px; }
    }
</style>

<main class="view-receipt-page">
    <div class="container receipt-container">
        
        <div class="header-actions">
            <a href="mybookings.php" class="btn-back"><i class="fa fa-arrow-left"></i> Back to Bookings</a>
            <a href="print_receipt.php?id=<?php echo $booking_id; ?>" target="_blank" class="btn-print"><i class="fa fa-print"></i> Print Itinerary</a>
        </div>

        <div class="card-block">
            <div class="receipt-title">
                <span>Trip to <?php echo htmlspecialchars($booking['dest_name']); ?></span>
                <?php if(strtolower($booking['payment_status']) === 'success'): ?>
                    <span class="status-badge status-success"><i class="fa fa-check-circle"></i> Confirmed</span>
                <?php else: ?>
                    <span class="status-badge status-pending"><i class="fa fa-clock"></i> Pending</span>
                <?php endif; ?>
            </div>
            <p class="receipt-subtitle">Transaction ID: <strong><?php echo htmlspecialchars($booking['transaction_id']); ?></strong> • Booked on <?php echo date('d M Y, h:i A', strtotime($booking['created_at'])); ?></p>
            
            <div class="divider"></div>

            <div class="grid-layout">
                <div>
                    <h3 class="section-title"><i class="fa fa-map-marked-alt" style="color:#0d6efd;"></i> Itinerary Details</h3>
                    
                    <div class="info-list" style="margin-bottom: 24px;">
                        <div class="info-row">
                            <span class="info-label">Journey Date</span>
                            <span class="info-val"><?php echo date('l, d F Y', strtotime($booking['trip_date'])); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Start From</span>
                            <span class="info-val"><?php echo htmlspecialchars($booking['start_from']); ?></span>
                        </div>
                    </div>

                    <h3 class="section-title"><i class="fa fa-box-open" style="color:#0d6efd;"></i> Package Inclusions</h3>
                    <div class="selected-items">
                        <?php if ($booking['dest_name']): ?>
                        <div class="item-card">
                            <img src="<?php echo htmlspecialchars($booking['dest_img'] ?: 'img/default-dest.jpg'); ?>" alt="Dest">
                            <div class="item-details">
                                <p>Destination</p>
                                <h4><?php echo htmlspecialchars($booking['dest_name']); ?></h4>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ($booking['guide_name']): ?>
                        <div class="item-card">
                            <img src="<?php echo htmlspecialchars($booking['guide_image'] ?: 'img/default-guide.jpg'); ?>" alt="Guide">
                            <div class="item-details">
                                <p>Tour Guide</p>
                                <h4><?php echo htmlspecialchars($booking['guide_name']); ?></h4>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ($booking['vehicle_details']): ?>
                        <div class="item-card">
                            <img src="<?php echo htmlspecialchars($booking['vehicle_image'] ?: 'img/default-transport.jpg'); ?>" alt="Transport">
                            <div class="item-details">
                                <p>Transportation</p>
                                <h4><?php echo htmlspecialchars($booking['vehicle_details']); ?></h4>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ($booking['hotel_name']): ?>
                        <div class="item-card">
                            <img src="<?php echo htmlspecialchars($booking['hotel_image'] ?: 'img/default-hotel.jpg'); ?>" alt="Hotel">
                            <div class="item-details">
                                <p>Accommodation</p>
                                <h4><?php echo htmlspecialchars($booking['hotel_name']); ?></h4>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div>
                    <h3 class="section-title"><i class="fa fa-receipt" style="color:#0d6efd;"></i> Payment Summary</h3>
                    
                    <div class="info-list">
                        <div class="info-row">
                            <span class="info-label">Destination Fee</span>
                            <span class="info-val">Rs <?php echo number_format($p_dest, 2); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Guide Fee</span>
                            <span class="info-val">Rs <?php echo number_format($p_guide, 2); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Transport Fee</span>
                            <span class="info-val">Rs <?php echo number_format($p_trans, 2); ?></span>
                        </div>
                        <?php if($p_hotel > 0): ?>
                        <div class="info-row">
                            <span class="info-label">Hotel Fee</span>
                            <span class="info-val">Rs <?php echo number_format($p_hotel, 2); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="divider" style="margin: 10px 0;"></div>

                        <div class="info-row">
                            <span class="info-label">Payment Mode</span>
                            <span class="info-val" style="text-transform: capitalize;"><?php echo htmlspecialchars($booking['payment_mode'] ?? 'N/A'); ?></span>
                        </div>
                        <?php if(($booking['payment_mode'] ?? '') === 'online'): ?>
                        <div class="info-row">
                            <span class="info-label">UTR No</span>
                            <span class="info-val"><?php echo htmlspecialchars($booking['utr_no']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="total-box">
                        <div class="total-row">
                            <span class="total-label">Total Amount</span>
                            <span class="total-amount">Rs <?php echo number_format($booking['total_price'], 2); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-block">
            <h3 class="section-title"><i class="fa fa-users" style="color:#0d6efd;"></i> Traveler Details (<?php echo count($members); ?>)</h3>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Full Name</th>
                            <th>Age</th>
                            <th>Gender</th>
                            <th>ID Proof Number</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($members as $index => $member): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($member['member_name']); ?></td>
                                <td><?php echo htmlspecialchars($member['member_age']); ?></td>
                                <td><?php echo htmlspecialchars($member['member_gender']); ?></td>
                                <td><?php echo htmlspecialchars($member['member_id_proof']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</main>

<?php include 'footer.php'; ?>