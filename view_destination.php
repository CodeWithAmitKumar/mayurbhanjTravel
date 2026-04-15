<?php
session_start();

require_once 'config.php';

if (!isset($_SESSION['frontend_user'])) {
    header('Location: login.php');
    exit;
}

$destination_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($destination_id === 0) {
    header('Location: alldestinations.php');
    exit;
}

// Fetch Destination Details safely using Prepared Statements
$stmt = mysqli_prepare($conn, "SELECT * FROM all_destinations WHERE destination_id = ?");
mysqli_stmt_bind_param($stmt, "i", $destination_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$destination = mysqli_fetch_assoc($result);

if (!$destination) {
    header('Location: alldestinations.php');
    exit;
}

// Fetch Nearby Places using the destination_id
$nearbyPlaces = [];
$nearbyCheck = mysqli_query($conn, "SHOW TABLES LIKE 'all_nearbyplaces'");
if ($nearbyCheck && mysqli_num_rows($nearbyCheck) > 0) {
    $nearbyStmt = mysqli_prepare($conn, "SELECT * FROM all_nearbyplaces WHERE destination_id = ?");
    mysqli_stmt_bind_param($nearbyStmt, "i", $destination_id);
    mysqli_stmt_execute($nearbyStmt);
    $nearbyResult = mysqli_stmt_get_result($nearbyStmt);
    
    while ($row = mysqli_fetch_assoc($nearbyResult)) {
        $nearbyPlaces[] = $row;
    }
}

function format_destination_price($price)
{
    $price = trim((string) $price);
    if ($price === '') return 'Rs 0';
    if (stripos($price, 'rs') === 0) return $price;
    return 'Rs ' . $price;
}

include 'header2.php';
?>

<style>
    /* Utilizing the same UI system for consistency */
    .view-destination-page {
        background: radial-gradient(circle at top left, rgba(13, 110, 253, 0.08), transparent 30%), linear-gradient(180deg, #f7fbff 0%, #eef5fb 100%);
        min-height: calc(100vh - 84px);
        padding-bottom: 96px;
    }
    
    .detail-hero {
        position: relative;
        padding: 64px 0 40px;
    }

    .detail-container {
        max-width: 900px;
        margin: 0 auto;
        background: rgba(255, 255, 255, 0.96);
        border: 1px solid #dce7f3;
        border-radius: 28px;
        box-shadow: 0 22px 46px rgba(17, 37, 63, 0.08);
        overflow: hidden;
    }

    .detail-image-wrapper {
        position: relative;
        width: 100%;
        height: 450px;
        background: #dbeafe;
    }

    .detail-image-wrapper img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .detail-content {
        padding: 40px;
    }

    .detail-header-flex {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 24px;
        gap: 20px;
    }

    .detail-title {
        color: #15314b;
        font-size: 36px;
        font-weight: 800;
        margin: 0 0 8px 0;
    }

    .detail-subheading {
        color: #0d6efd;
        font-size: 14px;
        font-weight: 800;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        margin: 0;
    }

    .detail-price-box {
        background: linear-gradient(135deg, #0d6efd, #0b5ed7);
        color: white;
        padding: 16px 24px;
        border-radius: 20px;
        text-align: right;
        box-shadow: 0 14px 28px rgba(13, 110, 253, 0.22);
    }

    .detail-description {
        color: #607488;
        font-size: 17px;
        line-height: 1.8;
        white-space: pre-line; /* preserves line breaks from database */
    }

    /* Nearby Places Section Styles (Inherited Grid styling) */
    .nearby-section {
        max-width: 1200px;
        margin: 64px auto 0;
        padding: 0 15px;
    }

    .nearby-title {
        color: #15314b;
        font-size: 28px;
        font-weight: 800;
        margin-bottom: 30px;
        text-align: center;
    }

    .destinations-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 26px;
    }

    .destination-card {
        overflow: hidden;
        border: 1px solid #dce7f3;
        border-radius: 28px;
        background: rgba(255, 255, 255, 0.96);
        box-shadow: 0 12px 24px rgba(17, 37, 63, 0.05);
        height: 100%;
    }

    .destination-card-media {
        position: relative;
        height: 200px;
        overflow: hidden;
    }

    .destination-card-media img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .destination-card-body {
        padding: 24px;
    }

    .destination-card-title {
        margin: 0 0 10px;
        color: #15314b;
        font-size: 20px;
        font-weight: 800;
    }

    .destination-card-description {
        margin: 0;
        color: #607488;
        font-size: 15px;
        line-height: 1.6;
    }

    @media (max-width: 767.98px) {
        .detail-header-flex {
            flex-direction: column;
        }
        .detail-image-wrapper { height: 300px; }
        .detail-content { padding: 24px; }
        .destinations-grid { grid-template-columns: 1fr; }
    }
</style>

<main class="view-destination-page">
    <section class="detail-hero container">
        <div class="detail-container">
            <div class="detail-image-wrapper">
                <img src="<?php echo htmlspecialchars($destination['destinationimage'] !== '' ? $destination['destinationimage'] : 'img/destination-1.jpg', ENT_QUOTES, 'UTF-8'); ?>" alt="Destination Image">
            </div>
            <div class="detail-content">
                <div class="detail-header-flex">
                    <div>
                        <p class="detail-subheading"><?php echo htmlspecialchars($destination['subheading'] !== '' ? $destination['subheading'] : 'Destination', ENT_QUOTES, 'UTF-8'); ?></p>
                        <h1 class="detail-title"><?php echo htmlspecialchars($destination['titel'], ENT_QUOTES, 'UTF-8'); ?></h1>
                    </div>
                    <div class="detail-price-box">
                        <span style="display:block; font-size:12px; font-weight:700; opacity:0.9; text-transform:uppercase;">Price</span>
                        <span style="display:block; font-size:26px; font-weight:800;"><?php echo htmlspecialchars(format_destination_price($destination['price']), ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                </div>
                <div class="detail-description">
                    <?php echo htmlspecialchars($destination['description'], ENT_QUOTES, 'UTF-8'); ?>
                </div>
            </div>
        </div>
    </section>

    <?php if (!empty($nearbyPlaces)): ?>
        <section class="nearby-section">
            <h2 class="nearby-title">Nearby Places to Explore</h2>
            <div class="destinations-grid">
                <?php foreach ($nearbyPlaces as $place): ?>
                    <article class="destination-card">
                        <div class="destination-card-media">
                            <img src="<?php echo htmlspecialchars($place['place_image'] ?? 'img/default-place.jpg', ENT_QUOTES, 'UTF-8'); ?>" alt="Nearby Place">
                        </div>
                        <div class="destination-card-body">
                            <h3 class="destination-card-title"><?php echo htmlspecialchars($place['place_name'] ?? 'Nearby Place', ENT_QUOTES, 'UTF-8'); ?></h3>
                            <p class="destination-card-description">
                               <p>Explore this amazing location nearby and enjoy the beauty of nature in Mayurbhanj.</p>
                            </p>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</main>

<?php include 'footer.php'; ?>