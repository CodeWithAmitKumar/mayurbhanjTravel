<?php
session_start();

// Prevent caching
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

require_once 'config.php';

if (!isset($_SESSION['frontend_user'])) {
    header('Location: login.php');
    exit;
}

// ------------------------------------------------------------------
// FORM SUBMISSION LOGIC
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Save all form data into a session variable to use in checkout.php
    $_SESSION['checkout_data'] = [
        'trip_date'      => $_POST['trip_date'] ?? '',
        'start_from'     => $_POST['start_from'] ?? 'Baripada Bus Stop',
        'destination_id' => $_POST['destination_id'] ?? '',
        'guide_id'       => $_POST['guide_id'] ?? '',
        'transport_id'   => $_POST['transport_id'] ?? '',
        'hotel_id'       => $_POST['hotel_id'] ?? '', // Optional
        'members'        => []
    ];

    // Format Members array
    if (isset($_POST['member_name']) && is_array($_POST['member_name'])) {
        for ($i = 0; $i < count($_POST['member_name']); $i++) {
            if (!empty($_POST['member_name'][$i])) {
                $_SESSION['checkout_data']['members'][] = [
                    'name'     => $_POST['member_name'][$i],
                    'age'      => $_POST['member_age'][$i],
                    'gender'   => $_POST['member_gender'][$i],
                    'id_proof' => $_POST['member_id_proof'][$i]
                ];
            }
        }
    }

    // Redirect to checkout
    header("Location: checkout.php");
    exit;
}

// ------------------------------------------------------------------
// FETCH DATA FOR DROPDOWNS
// ------------------------------------------------------------------
$destinations = [];
$destQ = mysqli_query($conn, "SELECT destination_id, destinationimage, titel, price FROM all_destinations ORDER BY titel ASC");
if ($destQ) while ($row = mysqli_fetch_assoc($destQ)) $destinations[] = $row;

$guides = [];
$guideQ = mysqli_query($conn, "SELECT guide_id, guide_name, guide_image, price FROM guides WHERE is_active=1 ORDER BY guide_name ASC");
if ($guideQ) while ($row = mysqli_fetch_assoc($guideQ)) $guides[] = $row;

$transports = [];
$transQ = mysqli_query($conn, "SELECT transport_id, vehicle_image, vehicle_details, price FROM transports WHERE is_active=1");
if ($transQ) while ($row = mysqli_fetch_assoc($transQ)) $transports[] = $row;

$hotels = [];
$hotelQ = mysqli_query($conn, "SELECT hotel_id, hotel_image, hotel_name, price FROM hotels WHERE is_active=1");
if ($hotelQ) while ($row = mysqli_fetch_assoc($hotelQ)) $hotels[] = $row;

include 'header2.php';
?>

<style>
    .booktrip-page {
        background: radial-gradient(circle at top left, rgba(13, 110, 253, 0.08), transparent 30%), linear-gradient(180deg, #f7fbff 0%, #eef5fb 100%);
        min-height: calc(100vh - 84px);
        padding: 64px 0 96px;
    }

    .form-container {
        max-width: 900px;
        margin: 0 auto;
        background: rgba(255, 255, 255, 0.96);
        border: 1px solid #dce7f3;
        border-radius: 28px;
        box-shadow: 0 22px 46px rgba(17, 37, 63, 0.08);
        padding: 48px;
    }

    .form-title {
        color: #15314b;
        font-size: 32px;
        font-weight: 800;
        margin-bottom: 8px;
        text-align: center;
    }

    .form-subtitle {
        color: #607488;
        font-size: 16px;
        text-align: center;
        margin-bottom: 40px;
    }

    .form-section {
        background: #ffffff;
        border: 1px solid #eef5fb;
        border-radius: 20px;
        padding: 32px;
        margin-bottom: 32px;
        box-shadow: 0 8px 24px rgba(17, 37, 63, 0.03);
    }

    .form-section-title {
        color: #15314b;
        font-size: 20px;
        font-weight: 800;
        margin-bottom: 24px;
        padding-bottom: 12px;
        border-bottom: 2px solid #eef5fb;
    }

    .form-row {
        display: flex;
        gap: 24px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }

    .form-group {
        flex: 1;
        min-width: 250px;
    }

    label {
        display: block;
        color: #15314b;
        font-weight: 700;
        font-size: 14px;
        margin-bottom: 8px;
    }

    .form-control, .form-select {
        width: 100%;
        padding: 14px 18px;
        border: 2px solid #e9ecef;
        border-radius: 12px;
        font-size: 15px;
        color: #15314b;
        background: #f8f9fa;
        transition: all 0.3s ease;
    }

    .form-control:focus, .form-select:focus {
        border-color: #0d6efd;
        background: #ffffff;
        outline: none;
        box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.1);
    }

    .form-control[readonly] {
        background: #eef5fb;
        cursor: not-allowed;
    }

    /* Selection Preview Cards */
    .preview-card {
        display: none;
        align-items: center;
        gap: 16px;
        padding: 16px;
        margin-top: 12px;
        background: #f8f9fa;
        border: 1px solid #dce7f3;
        border-radius: 14px;
    }

    .preview-card.active {
        display: flex;
        animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-5px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .preview-img {
        width: 64px;
        height: 64px;
        border-radius: 10px;
        object-fit: cover;
    }

    .preview-info {
        flex: 1;
    }

    .preview-title {
        color: #15314b;
        font-weight: 800;
        font-size: 16px;
        margin: 0 0 4px 0;
    }

    .preview-price {
        color: #0d6efd;
        font-weight: 700;
        font-size: 14px;
        margin: 0;
    }

    /* Member Repeater Styles */
    .member-row {
        position: relative;
        padding: 24px;
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 16px;
        margin-bottom: 16px;
    }
    
    .remove-member {
        position: absolute;
        top: -12px;
        right: -12px;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: #dc3545;
        color: white;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);
        transition: transform 0.2s;
    }

    .remove-member:hover { transform: scale(1.1); }

    .btn-add-member {
        background: rgba(13, 110, 253, 0.1);
        color: #0d6efd;
        border: none;
        padding: 12px 24px;
        border-radius: 12px;
        font-weight: 700;
        font-size: 14px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s;
    }

    .btn-add-member:hover {
        background: #0d6efd;
        color: #fff;
    }

    .btn-submit {
        background: linear-gradient(135deg, #0d6efd, #0b5ed7);
        color: white;
        border: none;
        padding: 18px 32px;
        width: 100%;
        border-radius: 16px;
        font-size: 18px;
        font-weight: 800;
        cursor: pointer;
        box-shadow: 0 14px 28px rgba(13, 110, 253, 0.22);
        transition: transform 0.3s, box-shadow 0.3s;
    }

    .btn-submit:hover {
        transform: translateY(-3px);
        box-shadow: 0 20px 40px rgba(13, 110, 253, 0.3);
    }

    @media (max-width: 768px) {
        .form-container { padding: 24px; }
        .form-section { padding: 20px; }
    }
</style>

<main class="booktrip-page">
    <div class="container">
        <div class="form-container">
            <h1 class="form-title">Plan Your Perfect Trip</h1>
            <p class="form-subtitle">Fill in the details below to customize and book your experience.</p>

            <form action="booktrip.php" method="POST" id="bookingForm">
                
                <div class="form-section">
                    <h2 class="form-section-title"><i class="fa fa-calendar-alt" style="margin-right:8px; color:#0d6efd;"></i> Trip Details</h2>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Start Location</label>
                            <input type="text" name="start_from" class="form-control" value="Baripada Bus Stop" readonly>
                        </div>
                        <div class="form-group">
                            <label>Date of Travel</label>
                            <input type="date" name="trip_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h2 class="form-section-title"><i class="fa fa-map-marked-alt" style="margin-right:8px; color:#0d6efd;"></i> Choose Services</h2>
                    
                    <div class="form-group" style="margin-bottom: 24px;">
                        <label>Select Destination</label>
                        <select name="destination_id" class="form-select preview-trigger" required>
                            <option value="">-- Choose a Destination --</option>
                            <?php foreach ($destinations as $d): ?>
                                <option value="<?php echo $d['destination_id']; ?>" 
                                        data-img="<?php echo htmlspecialchars($d['destinationimage'] ?: 'img/default-dest.jpg'); ?>"
                                        data-title="<?php echo htmlspecialchars($d['titel']); ?>"
                                        data-price="<?php echo htmlspecialchars($d['price']); ?>">
                                    <?php echo htmlspecialchars($d['titel']); ?> (Rs <?php echo htmlspecialchars($d['price']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="preview-card">
                            <img src="" class="preview-img" alt="Preview">
                            <div class="preview-info">
                                <h4 class="preview-title"></h4>
                                <p class="preview-price"></p>
                            </div>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 24px;">
                        <label>Select Guide</label>
                        <select name="guide_id" class="form-select preview-trigger" required>
                            <option value="">-- Choose a Guide --</option>
                            <?php foreach ($guides as $g): ?>
                                <option value="<?php echo $g['guide_id']; ?>" 
                                        data-img="<?php echo htmlspecialchars($g['guide_image'] ?: 'img/default-guide.jpg'); ?>"
                                        data-title="<?php echo htmlspecialchars($g['guide_name']); ?>"
                                        data-price="<?php echo htmlspecialchars($g['price']); ?>">
                                    <?php echo htmlspecialchars($g['guide_name']); ?> (Rs <?php echo htmlspecialchars($g['price']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="preview-card">
                            <img src="" class="preview-img" alt="Preview">
                            <div class="preview-info">
                                <h4 class="preview-title"></h4>
                                <p class="preview-price"></p>
                            </div>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 24px;">
                        <label>Select Transport</label>
                        <select name="transport_id" class="form-select preview-trigger" required>
                            <option value="">-- Choose Transport --</option>
                            <?php foreach ($transports as $t): ?>
                                <option value="<?php echo $t['transport_id']; ?>" 
                                        data-img="<?php echo htmlspecialchars($t['vehicle_image'] ?: 'img/default-transport.jpg'); ?>"
                                        data-title="<?php echo htmlspecialchars($t['vehicle_details']); ?>"
                                        data-price="<?php echo htmlspecialchars($t['price']); ?>">
                                    <?php echo htmlspecialchars($t['vehicle_details']); ?> (Rs <?php echo htmlspecialchars($t['price']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="preview-card">
                            <img src="" class="preview-img" alt="Preview">
                            <div class="preview-info">
                                <h4 class="preview-title"></h4>
                                <p class="preview-price"></p>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Select Hotel <span style="color:#607488; font-weight:400; font-size:12px;">(Optional)</span></label>
                        <select name="hotel_id" class="form-select preview-trigger">
                            <option value="">-- No Hotel Needed --</option>
                            <?php foreach ($hotels as $h): ?>
                                <option value="<?php echo $h['hotel_id']; ?>" 
                                        data-img="<?php echo htmlspecialchars($h['hotel_image'] ?: 'img/default-hotel.jpg'); ?>"
                                        data-title="<?php echo htmlspecialchars($h['hotel_name']); ?>"
                                        data-price="<?php echo htmlspecialchars($h['price']); ?>">
                                    <?php echo htmlspecialchars($h['hotel_name']); ?> (Rs <?php echo htmlspecialchars($h['price']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="preview-card">
                            <img src="" class="preview-img" alt="Preview">
                            <div class="preview-info">
                                <h4 class="preview-title"></h4>
                                <p class="preview-price"></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h2 class="form-section-title"><i class="fa fa-users" style="margin-right:8px; color:#0d6efd;"></i> Traveler Details</h2>
                    
                    <div id="membersContainer">
                        <div class="member-row">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Full Name</label>
                                    <input type="text" name="member_name[]" class="form-control" required placeholder="John Doe">
                                </div>
                                <div class="form-group" style="min-width: 100px; flex: 0.3;">
                                    <label>Age</label>
                                    <input type="number" name="member_age[]" class="form-control" required min="1" max="120">
                                </div>
                            </div>
                            <div class="form-row" style="margin-bottom:0;">
                                <div class="form-group">
                                    <label>Gender</label>
                                    <select name="member_gender[]" class="form-select" required>
                                        <option value="">Select</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>ID Proof Number (Aadhar/Voter ID)</label>
                                    <input type="text" name="member_id_proof[]" class="form-control" required placeholder="Ex: 1234 5678 9012">
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="button" class="btn-add-member" onclick="addMember()">
                        <i class="fa fa-plus"></i> Add Another Traveler
                    </button>
                </div>

                <button type="submit" class="btn-submit">Save & Proceed to Checkout <i class="fa fa-arrow-right" style="margin-left:8px;"></i></button>
            </form>
        </div>
    </div>
</main>

<script>
    // Handle Dropdown Previews
    document.querySelectorAll('.preview-trigger').forEach(select => {
        select.addEventListener('change', function() {
            const card = this.nextElementSibling; // The .preview-card div
            const option = this.options[this.selectedIndex];
            
            if (this.value === "") {
                card.classList.remove('active');
            } else {
                const img = option.getAttribute('data-img');
                const title = option.getAttribute('data-title');
                const price = option.getAttribute('data-price');

                card.querySelector('.preview-img').src = img;
                card.querySelector('.preview-title').textContent = title;
                card.querySelector('.preview-price').textContent = "Price: Rs " + price;
                
                card.classList.add('active');
            }
        });
    });

    // Handle Adding/Removing Members dynamically
    function addMember() {
        const container = document.getElementById('membersContainer');
        const memberCount = container.children.length + 1;
        
        const row = document.createElement('div');
        row.className = 'member-row';
        row.innerHTML = `
            <button type="button" class="remove-member" onclick="this.parentElement.remove()" title="Remove Traveler">
                <i class="fa fa-times"></i>
            </button>
            <div class="form-row">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="member_name[]" class="form-control" required placeholder="Traveler Name">
                </div>
                <div class="form-group" style="min-width: 100px; flex: 0.3;">
                    <label>Age</label>
                    <input type="number" name="member_age[]" class="form-control" required min="1" max="120">
                </div>
            </div>
            <div class="form-row" style="margin-bottom:0;">
                <div class="form-group">
                    <label>Gender</label>
                    <select name="member_gender[]" class="form-select" required>
                        <option value="">Select</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>ID Proof Number (Aadhar/Voter ID)</label>
                    <input type="text" name="member_id_proof[]" class="form-control" required placeholder="Ex: 1234 5678 9012">
                </div>
            </div>
        `;
        
        // Add a tiny animation delay
        row.style.opacity = '0';
        row.style.transform = 'translateY(-10px)';
        row.style.transition = 'all 0.3s ease';
        
        container.appendChild(row);
        
        // Trigger reflow for animation
        setTimeout(() => {
            row.style.opacity = '1';
            row.style.transform = 'translateY(0)';
        }, 10);
    }
</script>

<?php include 'footer.php'; ?>