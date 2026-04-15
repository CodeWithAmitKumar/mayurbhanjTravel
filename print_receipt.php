<?php
session_start();

// Prevent caching
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['frontend_user'])) {
    die("Unauthorized access. Please log in first.");
}

// Ensure a booking ID was provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Invalid Booking ID.");
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
           d.titel AS dest_name, d.price AS dest_price,
           g.guide_name, g.price AS guide_price,
           t.vehicle_details, t.price AS transport_price,
           h.hotel_name, h.price AS hotel_price,
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

if (!$booking) {
    die("Booking not found or you do not have permission to view it.");
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

// Helper function to clean prices
function cleanPrice($priceStr) {
    return (float)preg_replace('/[^0-9.]/', '', (string)$priceStr);
}

$p_dest = cleanPrice($booking['dest_price']);
$p_guide = cleanPrice($booking['guide_price']);
$p_trans = cleanPrice($booking['transport_price']);
$p_hotel = cleanPrice($booking['hotel_price']);

// Determine exact booking date
$booking_date = !empty($booking['payment_date']) ? $booking['payment_date'] : $booking['created_at'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - <?php echo htmlspecialchars($booking['transaction_id']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --text-main: #111827;
            --text-muted: #4b5563;
            --border-color: #e5e7eb;
        }

        body {
            font-family: 'Inter', Arial, sans-serif;
            color: var(--text-main);
            background: #f3f4f6;
            margin: 0;
            padding: 40px 20px;
        }

        .receipt-container {
            max-width: 800px;
            margin: 0 auto;
            background: #ffffff;
            padding: 50px;
            border: 1px solid var(--border-color);
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        }

        .receipt-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 40px;
            border-bottom: 2px solid var(--text-main);
            padding-bottom: 20px;
        }

        .company-info h1 {
            margin: 0 0 5px 0;
            font-size: 28px;
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .company-info p {
            margin: 0;
            color: var(--text-muted);
            font-size: 14px;
        }

        .receipt-title {
            text-align: right;
        }

        .receipt-title h2 {
            margin: 0 0 5px 0;
            font-size: 24px;
            color: var(--text-muted);
            text-transform: uppercase;
        }

        .receipt-title p {
            margin: 0;
            font-size: 14px;
            font-weight: 600;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }

        .info-box {
            padding: 20px;
            background: #f9fafb;
            border: 1px solid var(--border-color);
            border-radius: 8px;
        }

        .info-box h3 {
            margin: 0 0 15px 0;
            font-size: 14px;
            text-transform: uppercase;
            color: var(--text-muted);
            letter-spacing: 0.05em;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .info-row span:last-child {
            font-weight: 600;
        }

        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .invoice-table th {
            background: #f9fafb;
            text-align: left;
            padding: 12px 15px;
            font-size: 13px;
            text-transform: uppercase;
            color: var(--text-muted);
            border-bottom: 2px solid var(--border-color);
            border-top: 2px solid var(--border-color);
        }

        .invoice-table td {
            padding: 15px;
            font-size: 14px;
            border-bottom: 1px solid var(--border-color);
        }

        .text-right {
            text-align: right !important;
        }

        .total-row td {
            font-size: 18px;
            font-weight: 800;
            border-bottom: 2px solid var(--text-main);
            border-top: 2px solid var(--text-main);
            background: #f9fafb;
        }

        .travelers-section {
            margin-top: 40px;
        }

        .travelers-section h3 {
            font-size: 16px;
            margin-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 8px;
        }

        .footer-note {
            margin-top: 50px;
            text-align: center;
            font-size: 13px;
            color: var(--text-muted);
        }

        /* Screen-only Action Buttons */
        .print-actions {
            text-align: center;
            margin-bottom: 20px;
        }

        .btn {
            padding: 10px 20px;
            margin: 0 10px;
            font-size: 14px;
            font-weight: 600;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        .btn-print { background: #111827; color: white; }
        .btn-close { background: #e5e7eb; color: #111827; }

        /* Print Specific Styles */
        @media print {
            body {
                background: white;
                padding: 0;
            }
            .receipt-container {
                box-shadow: none;
                border: none;
                padding: 0;
                width: 100%;
                max-width: 100%;
            }
            .print-actions {
                display: none !important;
            }
            /* Force exact colors in print */
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            @page {
                size: A4;
                margin: 20mm;
            }
        }
    </style>
</head>
<body>

    <div class="print-actions">
        <button class="btn btn-print" onclick="window.print()">Print Receipt</button>
        <button class="btn btn-close" onclick="window.close()">Close Window</button>
    </div>

    <div class="receipt-container">
        
        <div class="receipt-header">
            <div class="company-info">
                <h1>MBJ TRAVEL</h1>
                <p>123 Travel Boulevard, Odyssey City</p>
                <p>support@mbjtravel.com | +91 98765 43210</p>
            </div>
            <div class="receipt-title">
                <h2>INVOICE / RECEIPT</h2>
                <p>No: <?php echo htmlspecialchars($booking['transaction_id']); ?></p>
                <p>Date: <?php echo date('d M Y', strtotime($booking_date)); ?></p>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-box">
                <h3>Bill To / Customer</h3>
                <div class="info-row">
                    <span>User Account:</span>
                    <span><?php echo htmlspecialchars($user_identifier); ?></span>
                </div>
                <div class="info-row">
                    <span>Status:</span>
                    <span style="color: <?php echo (strtolower($booking['payment_status']) === 'success') ? '#16a34a' : '#111827'; ?>;">
                        <?php echo htmlspecialchars(strtoupper($booking['payment_status'])); ?>
                    </span>
                </div>
                <?php if(($booking['payment_mode'] ?? '') === 'online'): ?>
                <div class="info-row">
                    <span>Payment Mode:</span>
                    <span>Online (UPI)</span>
                </div>
                <div class="info-row">
                    <span>UTR/Ref No:</span>
                    <span><?php echo htmlspecialchars($booking['utr_no']); ?></span>
                </div>
                <?php else: ?>
                <div class="info-row">
                    <span>Payment Mode:</span>
                    <span>Pay on Arrival</span>
                </div>
                <?php endif; ?>
            </div>

            <div class="info-box">
                <h3>Trip Itinerary</h3>
                <div class="info-row">
                    <span>Journey Date:</span>
                    <span><?php echo date('d M Y', strtotime($booking['trip_date'])); ?></span>
                </div>
                <div class="info-row">
                    <span>Start Location:</span>
                    <span><?php echo htmlspecialchars($booking['start_from']); ?></span>
                </div>
                <div class="info-row">
                    <span>Destination:</span>
                    <span><?php echo htmlspecialchars($booking['dest_name']); ?></span>
                </div>
                <div class="info-row">
                    <span>Total Travelers:</span>
                    <span><?php echo count($members); ?> Person(s)</span>
                </div>
            </div>
        </div>

        <table class="invoice-table">
            <thead>
                <tr>
                    <th>Item Description</th>
                    <th class="text-right">Amount (Rs)</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($booking['dest_name']): ?>
                <tr>
                    <td>Destination Package: <strong><?php echo htmlspecialchars($booking['dest_name']); ?></strong></td>
                    <td class="text-right"><?php echo number_format($p_dest, 2); ?></td>
                </tr>
                <?php endif; ?>

                <?php if ($booking['guide_name']): ?>
                <tr>
                    <td>Tour Guide Fee: <strong><?php echo htmlspecialchars($booking['guide_name']); ?></strong></td>
                    <td class="text-right"><?php echo number_format($p_guide, 2); ?></td>
                </tr>
                <?php endif; ?>

                <?php if ($booking['vehicle_details']): ?>
                <tr>
                    <td>Transportation: <strong><?php echo htmlspecialchars($booking['vehicle_details']); ?></strong></td>
                    <td class="text-right"><?php echo number_format($p_trans, 2); ?></td>
                </tr>
                <?php endif; ?>

                <?php if ($booking['hotel_name']): ?>
                <tr>
                    <td>Accommodation: <strong><?php echo htmlspecialchars($booking['hotel_name']); ?></strong></td>
                    <td class="text-right"><?php echo number_format($p_hotel, 2); ?></td>
                </tr>
                <?php endif; ?>

                <tr class="total-row">
                    <td class="text-right">TOTAL AMOUNT PAID</td>
                    <td class="text-right">Rs <?php echo number_format($booking['total_price'], 2); ?></td>
                </tr>
            </tbody>
        </table>

        <div class="travelers-section">
            <h3>Traveler Details</h3>
            <table class="invoice-table" style="margin-bottom: 0;">
                <thead>
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>Name</th>
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

        <div class="footer-note">
            <p>Thank you for choosing MBJ Travel! This is a computer-generated invoice and does not require a physical signature.</p>
            <p style="margin-top: 5px;">If you have any questions concerning this receipt, please contact our support team.</p>
        </div>

    </div>

    <script>
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500); // 500ms delay ensures fonts/styles load completely before printing
        };
    </script>
</body>
</html>