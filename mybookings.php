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

// Safely get the user identifier (handling the array-to-string issue just in case)
if (is_array($_SESSION['frontend_user'])) {
    $user_identifier = $_SESSION['frontend_user']['email'] ?? $_SESSION['frontend_user']['id'] ?? 'User';
} else {
    $user_identifier = $_SESSION['frontend_user'];
}

// Fetch user's bookings with destination names using a JOIN
// FIXED: Using b.transaction_id AS receipt_no to match the database table
$bookings = [];
$query = "
    SELECT b.booking_id, b.transaction_id AS receipt_no, b.trip_date, b.total_price, b.payment_status, b.created_at, d.titel AS destination_name
    FROM bookings b
    LEFT JOIN all_destinations d ON b.destination_id = d.destination_id
    WHERE b.user_identifier = ?
    ORDER BY b.created_at DESC
";

$stmt = mysqli_prepare($conn, $query);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "s", $user_identifier);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $bookings[] = $row;
    }
    mysqli_stmt_close($stmt);
}

include 'header2.php';
?>

<style>
    .my-bookings-page {
        background: radial-gradient(circle at top left, rgba(13, 110, 253, 0.08), transparent 30%), linear-gradient(180deg, #f7fbff 0%, #eef5fb 100%);
        min-height: calc(100vh - 84px);
        padding: 64px 0 96px;
    }

    .bookings-container {
        max-width: 1100px;
        margin: 0 auto;
        background: rgba(255, 255, 255, 0.96);
        border: 1px solid #dce7f3;
        border-radius: 28px;
        box-shadow: 0 22px 46px rgba(17, 37, 63, 0.08);
        padding: 40px;
    }

    .page-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 32px;
        padding-bottom: 20px;
        border-bottom: 2px solid #eef5fb;
    }

    .page-title {
        color: #15314b;
        font-size: 32px;
        font-weight: 800;
        margin: 0;
    }

    .page-subtitle {
        color: #607488;
        font-size: 16px;
        margin-top: 8px;
    }

    /* Modern Table Styles */
    .table-responsive {
        overflow-x: auto;
        border-radius: 16px;
        border: 1px solid #e9ecef;
    }

    .modern-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
        white-space: nowrap;
    }

    .modern-table th {
        background: #f8f9fa;
        color: #607488;
        font-size: 13px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        padding: 18px 24px;
        border-bottom: 2px solid #e9ecef;
    }

    .modern-table td {
        padding: 20px 24px;
        color: #15314b;
        font-size: 15px;
        font-weight: 600;
        border-bottom: 1px solid #eef5fb;
        vertical-align: middle;
    }

    .modern-table tbody tr {
        transition: background-color 0.2s ease;
    }

    .modern-table tbody tr:hover {
        background-color: #fcfdfe;
    }

    .modern-table tbody tr:last-child td {
        border-bottom: none;
    }

    /* Status Badges */
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 700;
    }

    .status-success {
        background: rgba(25, 135, 84, 0.1);
        color: #198754;
    }

    .status-pending {
        background: rgba(253, 126, 20, 0.1);
        color: #fd7e14;
    }

    .status-failed {
        background: rgba(220, 53, 69, 0.1);
        color: #dc3545;
    }

    /* Action Buttons */
    .action-group {
        display: flex;
        gap: 10px;
    }

    .btn-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border-radius: 10px;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
    }

    .btn-view {
        background: rgba(13, 110, 253, 0.1);
        color: #0d6efd;
    }

    .btn-view:hover {
        background: #0d6efd;
        color: white;
        transform: translateY(-2px);
    }

    .btn-print {
        background: rgba(108, 117, 125, 0.1);
        color: #6c757d;
    }

    .btn-print:hover {
        background: #6c757d;
        color: white;
        transform: translateY(-2px);
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 64px 20px;
    }

    .empty-state i {
        font-size: 48px;
        color: #a8cbfb;
        margin-bottom: 20px;
    }

    .empty-state h3 {
        color: #15314b;
        font-size: 24px;
        font-weight: 800;
        margin-bottom: 12px;
    }

    .empty-state p {
        color: #607488;
        font-size: 16px;
        margin-bottom: 24px;
    }

    .btn-primary {
        background: linear-gradient(135deg, #0d6efd, #0b5ed7);
        color: white;
        padding: 14px 28px;
        border-radius: 14px;
        font-weight: 700;
        text-decoration: none;
        display: inline-block;
        box-shadow: 0 10px 20px rgba(13, 110, 253, 0.2);
        transition: transform 0.2s;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        color: white;
    }

    @media (max-width: 768px) {
        .bookings-container { padding: 24px; }
        .page-header { flex-direction: column; align-items: flex-start; gap: 16px; }
    }
</style>

<main class="my-bookings-page">
    <div class="container">
        <div class="bookings-container">
            
            <div class="page-header">
                <div>
                    <h1 class="page-title">My Bookings</h1>
                    <p class="page-subtitle">Manage and view all your travel itineraries in one place.</p>
                </div>
                <a href="alldestinations.php" class="btn-primary"><i class="fa fa-plus"></i> Book New Trip</a>
            </div>

            <?php if (!empty($bookings)): ?>
                <div class="table-responsive">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Receipt No</th>
                                <th>Destination</th>
                                <th>Journey Date</th>
                                <th>Total Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $booking): ?>
                                <tr>
                                    <td>
                                        <strong style="color: #0d6efd;"><?php echo htmlspecialchars($booking['receipt_no']); ?></strong><br>
                                        <span style="font-size: 12px; color: #607488; font-weight: 500;">Booked: <?php echo date('d M Y', strtotime($booking['created_at'])); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($booking['destination_name'] ?: 'Custom Tour'); ?></td>
                                    <td><?php echo date('d M Y', strtotime($booking['trip_date'])); ?></td>
                                    <td>Rs <?php echo number_format($booking['total_price'], 2); ?></td>
                                    <td>
                                        <?php 
                                            $status = strtolower($booking['payment_status']);
                                            if ($status === 'success') {
                                                echo '<span class="status-badge status-success"><i class="fa fa-check-circle"></i> Success</span>';
                                            } elseif ($status === 'pending') {
                                                echo '<span class="status-badge status-pending"><i class="fa fa-clock"></i> Pending</span>';
                                            } else {
                                                echo '<span class="status-badge status-failed"><i class="fa fa-times-circle"></i> Failed</span>';
                                            }
                                        ?>
                                    </td>
                                    <td>
                                        <div class="action-group">
                                            <a href="view_receipt.php?id=<?php echo $booking['booking_id']; ?>" class="btn-action btn-view" title="View Details">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                            <a href="print_receipt.php?id=<?php echo $booking['booking_id']; ?>" target="_blank" class="btn-action btn-print" title="Print Receipt">
                                                <i class="fa fa-print"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fa fa-suitcase-rolling"></i>
                    <h3>No Bookings Found</h3>
                    <p>It looks like you haven't booked any trips with us yet. Start exploring amazing destinations today!</p>
                    <a href="alldestinations.php" class="btn-primary" style="margin-top: 10px;">Explore Destinations</a>
                </div>
            <?php endif; ?>

        </div>
    </div>
</main>

<?php include 'footer.php'; ?>