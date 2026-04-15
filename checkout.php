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

// Check if booking data exists in session
if (!isset($_SESSION['checkout_data']) || empty($_SESSION['checkout_data']['destination_id'])) {
    header('Location: booktrip.php');
    exit;
}

$checkout = $_SESSION['checkout_data'];
$totalPrice = 0;

// Helper function to safely fetch one row
function fetchRecord($conn, $table, $idColumn, $idValue) {
    $id = (int)$idValue;
    if ($id === 0) return null;
    $result = mysqli_query($conn, "SELECT * FROM `$table` WHERE `$idColumn` = $id");
    return ($result && mysqli_num_rows($result) > 0) ? mysqli_fetch_assoc($result) : null;
}

// Fetch selected entities
$destination = fetchRecord($conn, 'all_destinations', 'destination_id', $checkout['destination_id']);
$guide       = fetchRecord($conn, 'guides', 'guide_id', $checkout['guide_id']);
$transport   = fetchRecord($conn, 'transports', 'transport_id', $checkout['transport_id']);
$hotel       = fetchRecord($conn, 'hotels', 'hotel_id', $checkout['hotel_id'] ?? 0);

// Calculate Totals
$destPrice  = $destination ? (float)preg_replace('/[^0-9.]/', '', $destination['price']) : 0;
$guidePrice = $guide ? (float)preg_replace('/[^0-9.]/', '', $guide['price']) : 0;
$transPrice = $transport ? (float)preg_replace('/[^0-9.]/', '', $transport['price']) : 0;
$hotelPrice = $hotel ? (float)preg_replace('/[^0-9.]/', '', $hotel['price']) : 0;

$totalPrice = $destPrice + $guidePrice + $transPrice + $hotelPrice;

// Save total price back to session for the payment page
$_SESSION['checkout_data']['total_price'] = $totalPrice;

include 'header2.php';
?>

<style>
    .checkout-page {
        background: radial-gradient(circle at top left, rgba(13, 110, 253, 0.08), transparent 30%), linear-gradient(180deg, #f7fbff 0%, #eef5fb 100%);
        min-height: calc(100vh - 84px);
        padding: 64px 0 96px;
    }

    .checkout-header {
        text-align: center;
        margin-bottom: 48px;
    }

    .checkout-title {
        color: #15314b;
        font-size: 36px;
        font-weight: 800;
        margin-bottom: 8px;
    }

    .checkout-subtitle {
        color: #607488;
        font-size: 16px;
    }

    .checkout-layout {
        display: grid;
        grid-template-columns: 1fr 380px;
        gap: 32px;
        align-items: start;
    }

    /* Left Column: Summary Cards */
    .summary-card {
        background: rgba(255, 255, 255, 0.96);
        border: 1px solid #dce7f3;
        border-radius: 24px;
        box-shadow: 0 12px 24px rgba(17, 37, 63, 0.04);
        padding: 32px;
        margin-bottom: 24px;
    }

    .summary-title {
        color: #15314b;
        font-size: 20px;
        font-weight: 800;
        margin-bottom: 24px;
        padding-bottom: 12px;
        border-bottom: 2px solid #eef5fb;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .trip-info-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 24px;
    }

    .info-block {
        background: #f8f9fa;
        padding: 16px;
        border-radius: 14px;
        border: 1px solid #e9ecef;
    }

    .info-label {
        font-size: 12px;
        color: #607488;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 4px;
    }

    .info-value {
        font-size: 16px;
        color: #15314b;
        font-weight: 800;
    }

    .selected-items-list {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .selected-item {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 16px;
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 16px;
    }

    .item-img {
        width: 64px;
        height: 64px;
        border-radius: 12px;
        object-fit: cover;
    }

    .item-details {
        flex: 1;
    }

    .item-type {
        font-size: 12px;
        color: #0d6efd;
        font-weight: 700;
        text-transform: uppercase;
        margin-bottom: 2px;
    }

    .item-name {
        font-size: 16px;
        color: #15314b;
        font-weight: 800;
        margin: 0;
    }

    .item-price {
        font-size: 16px;
        color: #15314b;
        font-weight: 800;
    }

    /* Right Column: Price Breakdown */
    .price-card {
        background: rgba(255, 255, 255, 0.96);
        border: 1px solid #dce7f3;
        border-radius: 24px;
        box-shadow: 0 22px 46px rgba(17, 37, 63, 0.08);
        padding: 32px;
        position: sticky;
        top: 100px;
    }

    .price-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 16px;
        color: #607488;
        font-size: 15px;
    }

    .price-row span:last-child {
        font-weight: 700;
        color: #15314b;
    }

    .price-divider {
        height: 1px;
        background: #dce7f3;
        margin: 20px 0;
    }

    .total-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 32px;
    }

    .total-label {
        font-size: 18px;
        font-weight: 800;
        color: #15314b;
    }

    .total-value {
        font-size: 28px;
        font-weight: 800;
        color: #0d6efd;
    }

    .btn-pay {
        display: block;
        width: 100%;
        background: linear-gradient(135deg, #0d6efd, #0b5ed7);
        color: white;
        text-align: center;
        padding: 18px 24px;
        border-radius: 16px;
        font-size: 18px;
        font-weight: 800;
        text-decoration: none;
        box-shadow: 0 14px 28px rgba(13, 110, 253, 0.22);
        transition: transform 0.3s, box-shadow 0.3s;
    }

    .btn-pay:hover {
        transform: translateY(-3px);
        box-shadow: 0 20px 40px rgba(13, 110, 253, 0.3);
        color: white;
    }

    @media (max-width: 992px) {
        .checkout-layout {
            grid-template-columns: 1fr;
        }
        .price-card {
            position: static;
        }
    }

    @media (max-width: 576px) {
        .trip-info-grid {
            grid-template-columns: 1fr;
        }
        .summary-card { padding: 24px; }
    }
</style>

<main class="checkout-page">
    <div class="container">
        <div class="checkout-header">
            <h1 class="checkout-title">Review Your Booking</h1>
            <p class="checkout-subtitle">Please verify your selections and traveler details before proceeding to payment.</p>
        </div>

        <div class="checkout-layout">
            
            <div class="checkout-main">
                
                <div class="summary-card">
                    <h2 class="summary-title"><i class="fa fa-map-marker-alt" style="color:#0d6efd;"></i> Itinerary Summary</h2>
                    
                    <div class="trip-info-grid">
                        <div class="info-block">
                            <div class="info-label">Journey Date</div>
                            <div class="info-value"><?php echo date('d M, Y', strtotime($checkout['trip_date'])); ?></div>
                        </div>
                        <div class="info-block">
                            <div class="info-label">Starting Point</div>
                            <div class="info-value"><?php echo htmlspecialchars($checkout['start_from']); ?></div>
                        </div>
                    </div>

                    <div class="selected-items-list">
                        <?php if ($destination): ?>
                        <div class="selected-item">
                            <img src="<?php echo htmlspecialchars($destination['destinationimage'] ?: 'img/default-dest.jpg'); ?>" class="item-img" alt="Destination">
                            <div class="item-details">
                                <div class="item-type">Destination</div>
                                <h3 class="item-name"><?php echo htmlspecialchars($destination['titel']); ?></h3>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ($guide): ?>
                        <div class="selected-item">
                            <img src="<?php echo htmlspecialchars($guide['guide_image'] ?: 'img/default-guide.jpg'); ?>" class="item-img" alt="Guide">
                            <div class="item-details">
                                <div class="item-type">Tour Guide</div>
                                <h3 class="item-name"><?php echo htmlspecialchars($guide['guide_name']); ?></h3>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ($transport): ?>
                        <div class="selected-item">
                            <img src="<?php echo htmlspecialchars($transport['vehicle_image'] ?: 'img/default-transport.jpg'); ?>" class="item-img" alt="Transport">
                            <div class="item-details">
                                <div class="item-type">Transportation</div>
                                <h3 class="item-name"><?php echo htmlspecialchars($transport['vehicle_details']); ?></h3>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ($hotel): ?>
                        <div class="selected-item">
                            <img src="<?php echo htmlspecialchars($hotel['hotel_image'] ?: 'img/default-hotel.jpg'); ?>" class="item-img" alt="Hotel">
                            <div class="item-details">
                                <div class="item-type">Accommodation</div>
                                <h3 class="item-name"><?php echo htmlspecialchars($hotel['hotel_name']); ?></h3>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="summary-card">
                    <h2 class="summary-title"><i class="fa fa-users" style="color:#0d6efd;"></i> Traveler Details (<?php echo count($checkout['members']); ?>)</h2>
                    
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; text-align: left; border-collapse: collapse;">
                            <thead>
                                <tr style="border-bottom: 2px solid #eef5fb;">
                                    <th style="padding: 12px; color: #607488; font-size: 14px;">Name</th>
                                    <th style="padding: 12px; color: #607488; font-size: 14px;">Age</th>
                                    <th style="padding: 12px; color: #607488; font-size: 14px;">Gender</th>
                                    <th style="padding: 12px; color: #607488; font-size: 14px;">ID Proof</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($checkout['members'] as $index => $member): ?>
                                <tr style="border-bottom: 1px solid #eef5fb;">
                                    <td style="padding: 16px 12px; color: #15314b; font-weight: 700;">
                                        <?php echo htmlspecialchars($member['name']); ?>
                                    </td>
                                    <td style="padding: 16px 12px; color: #607488;">
                                        <?php echo htmlspecialchars($member['age']); ?>
                                    </td>
                                    <td style="padding: 16px 12px; color: #607488;">
                                        <?php echo htmlspecialchars($member['gender']); ?>
                                    </td>
                                    <td style="padding: 16px 12px; color: #607488;">
                                        <?php echo htmlspecialchars($member['id_proof']); ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            <aside>
                <div class="price-card">
                    <h2 class="summary-title" style="margin-bottom: 32px;">Payment Details</h2>
                    
                    <div class="price-row">
                        <span>Destination Package</span>
                        <span>Rs <?php echo number_format($destPrice, 2); ?></span>
                    </div>
                    
                    <div class="price-row">
                        <span>Tour Guide Fee</span>
                        <span>Rs <?php echo number_format($guidePrice, 2); ?></span>
                    </div>
                    
                    <div class="price-row">
                        <span>Transportation</span>
                        <span>Rs <?php echo number_format($transPrice, 2); ?></span>
                    </div>
                    
                    <?php if ($hotel): ?>
                    <div class="price-row">
                        <span>Accommodation</span>
                        <span>Rs <?php echo number_format($hotelPrice, 2); ?></span>
                    </div>
                    <?php endif; ?>

                    <div class="price-divider"></div>

                    <div class="total-row">
                        <span class="total-label">Total Amount</span>
                        <span class="total-value">Rs <?php echo number_format($totalPrice, 2); ?></span>
                    </div>

                    <a href="payment.php" class="btn-pay">Proceed to Pay <i class="fa fa-lock" style="margin-left:8px;"></i></a>
                    
                    <div style="text-align: center; margin-top: 16px; font-size: 12px; color: #607488;">
                        <i class="fa fa-shield-alt"></i> Secure Payment Gateway
                    </div>
                </div>
            </aside>

        </div>
    </div>
</main>

<?php include 'footer.php'; ?>