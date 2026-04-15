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

$success_receipt = false;
$receipt_no = 'REC-' . strtoupper(uniqid()); // Auto-generate Receipt/Transaction ID

// ------------------------------------------------------------------
// PROCESS PAYMENT & SAVE TO DATABASE
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay_now'])) {
    
    // Ensure data exists
    if (!isset($_SESSION['checkout_data'])) {
        header('Location: booktrip.php');
        exit;
    }

    $cd = $_SESSION['checkout_data'];
    
    // Fix for Array to String conversion
    if (is_array($_SESSION['frontend_user'])) {
        $user_identifier = $_SESSION['frontend_user']['email'] ?? $_SESSION['frontend_user']['id'] ?? 'User';
    } else {
        $user_identifier = $_SESSION['frontend_user'];
    }

    $payment_mode = $_POST['payment_mode'] ?? 'offline';
    $utr_no = ($payment_mode === 'online') ? ($_POST['utr_no'] ?? '') : 'OFFLINE';
    $total_price = $cd['total_price'] ?? 0;
    
    // Convert empty hotel_id to NULL for the database
    $hotel_id = !empty($cd['hotel_id']) ? (int)$cd['hotel_id'] : NULL;
    $payment_status = 'Success'; 

    // 1. Insert into Main `bookings` table
    $stmt = mysqli_prepare($conn, "INSERT INTO bookings (user_identifier, trip_date, start_from, destination_id, guide_id, transport_id, hotel_id, total_price, payment_status, transaction_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    mysqli_stmt_bind_param($stmt, "sssiiiidss", 
        $user_identifier, 
        $cd['trip_date'], 
        $cd['start_from'], 
        $cd['destination_id'], 
        $cd['guide_id'], 
        $cd['transport_id'], 
        $hotel_id, 
        $total_price, 
        $payment_status, 
        $receipt_no
    );
    
    if (mysqli_stmt_execute($stmt)) {
        $booking_id = mysqli_insert_id($conn); 

        // 2. Insert into `booking_members` table
        if (!empty($cd['members'])) {
            $mem_stmt = mysqli_prepare($conn, "INSERT INTO booking_members (booking_id, member_name, member_age, member_gender, member_id_proof) VALUES (?, ?, ?, ?, ?)");
            
            foreach ($cd['members'] as $member) {
                mysqli_stmt_bind_param($mem_stmt, "isiss", 
                    $booking_id, 
                    $member['name'], 
                    $member['age'], 
                    $member['gender'], 
                    $member['id_proof']
                );
                mysqli_stmt_execute($mem_stmt);
            }
            mysqli_stmt_close($mem_stmt);
        }

        // 3. Insert into `payments` table
        $pay_stmt = mysqli_prepare($conn, "INSERT INTO payments (booking_id, user_identifier, receipt_no, amount, payment_mode, utr_no, payment_status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($pay_stmt, "issdsss", 
            $booking_id, 
            $user_identifier, 
            $receipt_no, 
            $total_price, 
            $payment_mode, 
            $utr_no, 
            $payment_status
        );
        mysqli_stmt_execute($pay_stmt);
        mysqli_stmt_close($pay_stmt);

        // --- PREPARE DATA FOR THE RECEIPT BEFORE CLEARING SESSION ---
        $receipt_member_count = !empty($cd['members']) ? count($cd['members']) : 0;
        $receipt_start = $cd['start_from'];
        $receipt_date = $cd['trip_date'];
        
        // Fetch destination name
        $dest_stmt = mysqli_prepare($conn, "SELECT titel FROM all_destinations WHERE destination_id = ?");
        mysqli_stmt_bind_param($dest_stmt, "i", $cd['destination_id']);
        mysqli_stmt_execute($dest_stmt);
        $dest_res = mysqli_stmt_get_result($dest_stmt);
        $dest_row = mysqli_fetch_assoc($dest_res);
        $receipt_destination = $dest_row ? $dest_row['titel'] : 'Destination Tour';
        mysqli_stmt_close($dest_stmt);

        // Clear session & trigger success
        $saved_total = $total_price;
        unset($_SESSION['checkout_data']);
        $success_receipt = true;
    } else {
        $error_message = "Something went wrong. Please try again.";
    }
    
    mysqli_stmt_close($stmt);
} else {
    // If arriving normally via GET, ensure session data exists
    if (!isset($_SESSION['checkout_data']) || empty($_SESSION['checkout_data']['destination_id'])) {
        header('Location: booktrip.php');
        exit;
    }
}

include 'header2.php';
?>

<style>
    .payment-page {
        background: radial-gradient(circle at top left, rgba(13, 110, 253, 0.08), transparent 30%), linear-gradient(180deg, #f7fbff 0%, #eef5fb 100%);
        min-height: calc(100vh - 84px);
        padding: 64px 0 96px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .payment-container {
        width: 100%;
        max-width: 600px;
        background: rgba(255, 255, 255, 0.96);
        border: 1px solid #dce7f3;
        border-radius: 28px;
        box-shadow: 0 22px 46px rgba(17, 37, 63, 0.08);
        padding: 48px;
        position: relative;
        overflow: hidden;
    }

    .payment-header { text-align: center; margin-bottom: 32px; }
    .payment-title { color: #15314b; font-size: 28px; font-weight: 800; margin-bottom: 8px; }
    .amount-display { font-size: 36px; color: #0d6efd; font-weight: 800; margin: 16px 0; }

    /* Payment Mode Selector */
    .payment-modes { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 32px; }
    .mode-card { border: 2px solid #e9ecef; border-radius: 16px; padding: 20px; text-align: center; cursor: pointer; transition: all 0.3s ease; background: #f8f9fa; }
    .mode-card:hover { border-color: #a8cbfb; background: #f1f6fe; }
    .mode-card.active { border-color: #0d6efd; background: rgba(13, 110, 253, 0.05); }
    .mode-card i { font-size: 24px; color: #607488; margin-bottom: 10px; display: block; transition: color 0.3s; }
    .mode-card.active i { color: #0d6efd; }
    .mode-card span { font-size: 15px; font-weight: 700; color: #15314b; }

    /* Online Details Section */
    .online-details { display: none; background: #f8f9fa; border: 1px solid #dce7f3; border-radius: 16px; padding: 24px; text-align: center; margin-bottom: 32px; animation: fadeIn 0.4s ease; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
    .qr-box { width: 180px; height: 180px; margin: 0 auto 16px; background: white; padding: 10px; border-radius: 12px; border: 1px solid #dce7f3; box-shadow: 0 8px 16px rgba(17, 37, 63, 0.05); }
    .qr-box img { width: 100%; height: 100%; object-fit: cover; }
    .form-group label { display: block; color: #15314b; font-weight: 700; font-size: 14px; margin-bottom: 8px; text-align: left; }
    .form-control { width: 100%; padding: 14px 18px; border: 2px solid #e9ecef; border-radius: 12px; font-size: 15px; color: #15314b; transition: all 0.3s ease; }
    .form-control:focus { border-color: #0d6efd; outline: none; box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.1); }
    
    .btn-submit { background: linear-gradient(135deg, #0d6efd, #0b5ed7); color: white; border: none; padding: 18px 32px; width: 100%; border-radius: 16px; font-size: 18px; font-weight: 800; cursor: pointer; box-shadow: 0 14px 28px rgba(13, 110, 253, 0.22); transition: transform 0.3s, box-shadow 0.3s; display: inline-block; text-align: center; text-decoration: none; }
    .btn-submit:hover { transform: translateY(-3px); box-shadow: 0 20px 40px rgba(13, 110, 253, 0.3); color: white; }

    /* Modern Premium Receipt Styles */
    .success-screen { text-align: center; }
    .success-icon { width: 80px; height: 80px; background: #198754; color: white; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 36px; margin-bottom: 24px; box-shadow: 0 12px 24px rgba(25, 135, 84, 0.2); }
    
    .premium-receipt {
        background: #ffffff;
        border-radius: 20px;
        margin: 32px auto;
        text-align: left;
        max-width: 450px;
        box-shadow: 0 12px 32px rgba(17, 37, 63, 0.08);
        overflow: hidden;
        border: 1px solid #e9ecef;
    }

    .receipt-header-banner {
        background: linear-gradient(135deg, #15314b, #0d6efd);
        color: white;
        padding: 24px;
        text-align: center;
        border-bottom: 4px dashed #e9ecef;
    }

    .receipt-company { font-size: 24px; font-weight: 900; letter-spacing: 1px; margin: 0 0 4px 0; }
    .receipt-subtitle { font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 2px; margin: 0; opacity: 0.9; }

    .receipt-body { padding: 28px 24px; }
    
    .receipt-item { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
    .receipt-label { color: #607488; font-size: 14px; font-weight: 600; }
    .receipt-value { color: #15314b; font-size: 15px; font-weight: 800; text-align: right; }
    .receipt-value.highlight { color: #0d6efd; }
    .receipt-value.success { color: #198754; background: rgba(25, 135, 84, 0.1); padding: 4px 12px; border-radius: 20px; font-size: 13px; }
    
    .receipt-divider { height: 1px; background: #e9ecef; margin: 20px 0; }
    
    .receipt-total-row { display: flex; justify-content: space-between; align-items: center; background: #f8f9fa; padding: 20px 24px; border-top: 1px solid #e9ecef; }
    .receipt-total-label { color: #15314b; font-size: 18px; font-weight: 800; }
    .receipt-total-value { color: #0d6efd; font-size: 26px; font-weight: 900; }
    
    @media (max-width: 576px) { .payment-container { padding: 24px; } }
</style>

<main class="payment-page">
    <div class="payment-container">
        
        <?php if ($success_receipt): ?>
            <div class="success-screen">
                <div class="success-icon"><i class="fa fa-check"></i></div>
                <h2 class="payment-title">Payment Successful!</h2>
                <p style="color: #607488;">Your trip has been secured and confirmed.</p>
                
                <div class="premium-receipt">
                    <div class="receipt-header-banner">
                        <h3 class="receipt-company"><i class="fa fa-plane-departure" style="margin-right: 8px;"></i> MBJ TRAVEL</h3>
                        <p class="receipt-subtitle">Booking Confirmation</p>
                    </div>
                    
                    <div class="receipt-body">
                        <div class="receipt-item">
                            <span class="receipt-label">Receipt No</span>
                            <span class="receipt-value highlight"><?php echo htmlspecialchars($receipt_no); ?></span>
                        </div>
                        <div class="receipt-item">
                            <span class="receipt-label">Date of Booking</span>
                            <span class="receipt-value"><?php echo date('d M Y'); ?></span>
                        </div>
                        <div class="receipt-item">
                            <span class="receipt-label">Payment Status</span>
                            <span class="receipt-value success"><i class="fa fa-check-circle" style="margin-right:4px;"></i> Paid</span>
                        </div>
                        
                        <div class="receipt-divider"></div>
                        
                        <div class="receipt-item">
                            <span class="receipt-label">Destination</span>
                            <span class="receipt-value"><?php echo htmlspecialchars($receipt_destination); ?></span>
                        </div>
                        <div class="receipt-item">
                            <span class="receipt-label">Start From</span>
                            <span class="receipt-value"><?php echo htmlspecialchars($receipt_start); ?></span>
                        </div>
                        <div class="receipt-item">
                            <span class="receipt-label">Journey Date</span>
                            <span class="receipt-value"><?php echo date('d M Y', strtotime($receipt_date)); ?></span>
                        </div>
                        <div class="receipt-item">
                            <span class="receipt-label">Travelers</span>
                            <span class="receipt-value"><i class="fa fa-user-friends" style="color:#607488; margin-right:4px;"></i> <?php echo $receipt_member_count; ?> Person(s)</span>
                        </div>
                    </div>
                    
                    <div class="receipt-total-row">
                        <span class="receipt-total-label">Total Paid</span>
                        <span class="receipt-total-value">Rs <?php echo number_format($saved_total, 2); ?></span>
                    </div>
                </div>

                <a href="welcome.php" class="btn-submit">Return to Dashboard</a>
                <p style="margin-top: 16px; color: #607488; font-size: 13px;">A copy of this receipt has been saved to your account.</p>
            </div>

        <?php else: ?>
            <div class="payment-header">
                <h1 class="payment-title">Complete Payment</h1>
                <p style="color: #607488;">Receipt No: <strong><?php echo $receipt_no; ?></strong></p>
                <div class="amount-display">
                    Rs <?php echo number_format($_SESSION['checkout_data']['total_price'], 2); ?>
                </div>
            </div>

            <?php if(isset($error_message)): ?>
                <div style="background:#f8d7da; color:#842029; padding:12px; border-radius:8px; margin-bottom:20px; text-align:center;">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <form action="payment.php" method="POST" id="paymentForm">
                <input type="hidden" name="payment_mode" id="selectedMode" value="online">

                <div class="payment-modes">
                    <div class="mode-card active" onclick="selectMode('online', this)">
                        <i class="fa fa-qrcode"></i>
                        <span>Pay Online (UPI)</span>
                    </div>
                    <div class="mode-card" onclick="selectMode('offline', this)">
                        <i class="fa fa-money-bill-wave"></i>
                        <span>Pay on Arrival</span>
                    </div>
                </div>

                <div id="onlineSection" class="online-details" style="display: block;">
                    <p style="color:#607488; margin-bottom:16px; font-size:14px;">Scan the QR code below using any UPI app (GPay, PhonePe, Paytm).</p>
                    
                    <div class="qr-box">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=upi://pay?pa=demo@upi&pn=MBJTravel&am=<?php echo $_SESSION['checkout_data']['total_price']; ?>" alt="Scan to Pay">
                    </div>

                    <div class="form-group" style="margin-top: 20px;">
                        <label>Enter UTR / Transaction Reference No <span style="color:red;">*</span></label>
                        <input type="text" name="utr_no" id="utrInput" class="form-control" placeholder="12-digit UPI Ref Number" required>
                    </div>
                </div>

                <button type="submit" name="pay_now" class="btn-submit" id="submitBtn">Confirm & Pay Securely <i class="fa fa-lock" style="margin-left:8px;"></i></button>
            </form>
        <?php endif; ?>

    </div>
</main>

<script>
    function selectMode(mode, element) {
        document.getElementById('selectedMode').value = mode;
        document.querySelectorAll('.mode-card').forEach(card => card.classList.remove('active'));
        element.classList.add('active');

        const onlineSection = document.getElementById('onlineSection');
        const utrInput = document.getElementById('utrInput');
        const submitBtn = document.getElementById('submitBtn');

        if (mode === 'online') {
            onlineSection.style.display = 'block';
            utrInput.setAttribute('required', 'required');
            submitBtn.innerHTML = 'Confirm & Pay Securely <i class="fa fa-lock" style="margin-left:8px;"></i>';
        } else {
            onlineSection.style.display = 'none';
            utrInput.removeAttribute('required');
            submitBtn.innerHTML = 'Confirm Booking <i class="fa fa-check-circle" style="margin-left:8px;"></i>';
        }
    }
</script>

<?php include 'footer.php'; ?>