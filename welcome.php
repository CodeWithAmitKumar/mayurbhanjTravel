<?php
session_start();

// Prevent browser from caching this protected page.
// Without these headers, pressing Back after logout shows a cached copy
// without re-running the session check.
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Sat, 01 Jan 2000 00:00:00 GMT');

require_once 'config.php';

if (!isset($_SESSION['frontend_user'])) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['frontend_user'];
$userName = trim((string) ($user['name'] ?? 'Traveler'));
$userEmail = trim((string) ($user['email'] ?? ''));
$userGender = trim((string) ($user['gender'] ?? ''));
$welcomeBackground = 'img/contactus.jpg';
$homePageResult = mysqli_query($conn, "SELECT bannerimage FROM home_page WHERE 1 LIMIT 1");
$homePage = $homePageResult ? mysqli_fetch_assoc($homePageResult) : null;
$mapSettingsResult = mysqli_query($conn, "SELECT map_url FROM header_settings WHERE 1 LIMIT 1");
$mapSettings = $mapSettingsResult ? mysqli_fetch_assoc($mapSettingsResult) : null;
$mapUrl = trim((string) ($mapSettings['map_url'] ?? ''));



$happyClients = [];
$reviewTableResult = mysqli_query($conn, "SHOW TABLES LIKE 'client_reviews'");
$reviewTableExists = $reviewTableResult && mysqli_num_rows($reviewTableResult) > 0;

if ($reviewTableExists) {
    $reviewResult = mysqli_query($conn, "SELECT client_name, image, tourtype, short_desc FROM client_reviews ORDER BY sort_order ASC, id ASC");
    if ($reviewResult) {
        while ($reviewRow = mysqli_fetch_assoc($reviewResult)) {
            $happyClients[] = [
                'name' => (string) ($reviewRow['client_name'] ?? ''),
                'role' => (string) ($reviewRow['tourtype'] ?? ''),
                'image' => (string) ($reviewRow['image'] ?? ''),
                'review' => (string) ($reviewRow['short_desc'] ?? ''),
            ];
        }
    }
} else {
    $happyClients = [
        [
            'name' => 'Aarav Mehta',
            'role' => 'Family Traveler',
            'image' => 'img/profile/admin_1_1774262130.png',
            'review' => 'The trip planning was smooth from start to finish. Everything felt organized, simple, and comfortable for my family.',
        ],
        [
            'name' => 'Priya Sharma',
            'role' => 'Solo Explorer',
            'image' => 'img/profile/admin_1_1774262130.png',
            'review' => 'I loved how easy it was to explore destinations and compare options. The whole experience felt friendly and stress free.',
        ],
        [
            'name' => 'Rohan Verma',
            'role' => 'Adventure Lover',
            'image' => 'img/profile/admin_1_1774262130.png',
            'review' => 'Great destination choices and clear pricing made it easy to decide quickly. It gave me confidence to plan my next holiday.',
        ],
    ];
}



function format_destination_price($price)
{
    $price = trim((string) $price);
    if ($price === '') {
        return 'Rs 0';
    }

    if (stripos($price, 'rs') === 0) {
        return $price;
    }

    return 'Rs ' . $price;
}

$featuredDestinations = [];
$destinationTableResult = mysqli_query($conn, "SHOW TABLES LIKE 'all_destinations'");
$destinationTableExists = $destinationTableResult && mysqli_num_rows($destinationTableResult) > 0;

if ($destinationTableExists) {
    $featuredDestinationResult = mysqli_query($conn, "SELECT destination_id, destinationimage, subheading, titel, price, sort_order, created_at, updated_at FROM all_destinations ORDER BY sort_order ASC, destination_id ASC LIMIT 4");
    if ($featuredDestinationResult) {
        while ($destinationRow = mysqli_fetch_assoc($featuredDestinationResult)) {
            $featuredDestinations[] = [
                'name' => trim((string) ($destinationRow['titel'] ?? '')),
                'price' => format_destination_price($destinationRow['price'] ?? ''),
                'tag' => trim((string) ($destinationRow['subheading'] ?? '')),
                'image' => trim((string) ($destinationRow['destinationimage'] ?? '')),
            ];
        }
    }
}

if (empty($featuredDestinations)) {
    $featuredDestinations = [
        [
            'name' => 'Thailand',
            'price' => 'Rs 24,999',
            'tag' => 'Tropical Escape',
            'image' => 'uploads/image1_1775142400.jpg',
        ],
        [
            'name' => 'Malaysia',
            'price' => 'Rs 29,499',
            'tag' => 'City And Nature',
            'image' => 'uploads/image2_1775142400.jpg',
        ],
        [
            'name' => 'Australia',
            'price' => 'Rs 54,999',
            'tag' => 'Adventure Trail',
            'image' => 'uploads/image3_1775142400.jpg',
        ],
        [
            'name' => 'Indonesia',
            'price' => 'Rs 27,999',
            'tag' => 'Island Retreat',
            'image' => 'uploads/image4_1775142400.jpg',
        ],
    ];
}




if (!empty($homePage['bannerimage'])) {
    $welcomeBackground = $homePage['bannerimage'];
}

include 'header2.php';
?>

<style>
    .welcome-page {
        position: relative;
        min-height: calc(100vh - 84px);
        padding: 0;
        display: flex;
        align-items: stretch;
        overflow: hidden;
        isolation: isolate;
    }

    .welcome-page::before {
        content: "";
        position: absolute;
        inset: -24px;
        background-image: url('<?php echo htmlspecialchars($welcomeBackground, ENT_QUOTES, 'UTF-8'); ?>');
        background-size: cover;
        background-position: center;
        filter: blur(5px);
        transform: scale(1.08);
        z-index: -2;
    }

    .welcome-page::after {
        content: "";
        position: absolute;
        inset: 0;
        background:
            linear-gradient(120deg, rgba(10, 26, 46, 0.78), rgba(13, 110, 253, 0.35)),
            radial-gradient(circle at top left, rgba(255, 255, 255, 0.1), transparent 28%);
        z-index: -1;
    }

    .welcome-page .container {
        display: flex;
        align-items: stretch;
    }

    .welcome-hero {
        width: 100%;
        min-height: calc(100vh - 84px);
        display: flex;
        align-items: center;
        padding: 64px 0;
    }

    .welcome-copy {
        max-width: 720px;
        padding: 0;
        color: #fff;
    }

    .welcome-label {
        display: inline-block;
        margin-bottom: 18px;
        padding: 8px 14px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.18);
        color: #f8fbff;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: 0.12em;
        text-transform: uppercase;
    }

    .welcome-title {
        margin: 0 0 18px;
        display: inline-flex;
        align-items: baseline;
        gap: 0.18em;
        flex-wrap: nowrap;
        white-space: nowrap;
        color: #fff;
        font-size: clamp(2.3rem, 5vw, 4.3rem);
        font-weight: 800;
        line-height: 1.08;
        text-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
    }

    .welcome-title span {
        color: #9fd0ff;
    }

    .welcome-text {
        max-width: 620px;
        margin: 0;
        color: rgba(255, 255, 255, 0.88);
        font-size: 18px;
        line-height: 1.75;
        text-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
    }

    .welcome-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-top: 26px;
    }

    .welcome-chip {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 16px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.16);
        color: #fff;
        font-size: 14px;
        font-weight: 800;
    }

    .featured-destinations {
        padding: 88px 0 96px;
        background:
            radial-gradient(circle at top left, rgba(13, 110, 253, 0.08), transparent 28%),
            linear-gradient(180deg, #f8fbff 0%, #eef5fb 100%);
    }

    .featured-destinations-header {
        max-width: 640px;
        margin: 0 auto 42px;
        text-align: center;
    }

    .featured-destinations-label {
        display: inline-block;
        margin-bottom: 14px;
        padding: 8px 16px;
        border-radius: 999px;
        background: rgba(13, 110, 253, 0.1);
        color: #0d6efd;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: 0.12em;
        text-transform: uppercase;
    }

    .featured-destinations-title {
        margin-bottom: 12px;
        color: #15314b;
        font-size: clamp(2rem, 4vw, 3rem);
        font-weight: 800;
    }

    .featured-destinations-text {
        margin: 0;
        color: #607488;
        font-size: 17px;
        line-height: 1.7;
    }

    .destination-card-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 24px;
    }

    .destination-card {
        position: relative;
        overflow: hidden;
        border: 1px solid #dce7f3;
        border-radius: 24px;
        background: #fff;
        box-shadow: 0 18px 40px rgba(17, 37, 63, 0.08);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .destination-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 24px 48px rgba(17, 37, 63, 0.14);
    }

    .destination-card-image-wrap {
        position: relative;
        height: 220px;
        overflow: hidden;
    }

    .destination-card-image-wrap::after {
        content: "";
        position: absolute;
        inset: 0;
        background: linear-gradient(180deg, rgba(8, 19, 34, 0.1) 0%, rgba(8, 19, 34, 0.5) 100%);
    }

    .destination-card-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.35s ease;
    }

    .destination-card:hover .destination-card-image {
        transform: scale(1.06);
    }

    .destination-card-body {
        padding: 24px;
    }

    .destination-card-tag {
        display: block;
        margin-bottom: 10px;
        color: #6d8196;
        font-size: 13px;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .destination-card-name {
        margin: 0 0 18px;
        color: #15314b;
        font-size: 24px;
        font-weight: 800;
    }

    .destination-card-price {
        display: inline-flex;
        align-items: baseline;
        gap: 8px;
        color: #17324d;
        font-size: 15px;
        font-weight: 700;
    }

    .destination-card-price strong {
        color: #0d6efd;
        font-size: 28px;
        line-height: 1;
    }

    .featured-destinations-action {
        margin-top: 36px;
        text-align: center;
    }

    .featured-destinations-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 48px;
        padding: 0 26px;
        border-radius: 999px;
        background: #0d6efd;
        color: #fff;
        font-size: 15px;
        font-weight: 700;
        text-decoration: none;
        transition: background 0.2s ease, transform 0.2s ease;
    }

    .featured-destinations-button:hover {
        background: #0b5ed7;
        color: #fff;
        transform: translateY(-1px);
    }

    .happy-clients-section {
        padding: 88px 0 96px;
        background:
            radial-gradient(circle at bottom left, rgba(255, 127, 80, 0.08), transparent 30%),
            linear-gradient(180deg, #ffffff 0%, #f6faff 100%);
    }

    .happy-clients-header {
        max-width: 680px;
        margin: 0 auto 42px;
        text-align: center;
    }

    .happy-clients-label {
        display: inline-block;
        margin-bottom: 14px;
        padding: 8px 16px;
        border-radius: 999px;
        background: rgba(255, 127, 80, 0.12);
        color: #ef6b3b;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: 0.12em;
        text-transform: uppercase;
    }

    .happy-clients-title {
        margin-bottom: 12px;
        color: #15314b;
        font-size: clamp(2rem, 4vw, 3rem);
        font-weight: 800;
    }

    .happy-clients-text {
        margin: 0;
        color: #607488;
        font-size: 17px;
        line-height: 1.7;
    }

    .happy-clients-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 24px;
    }

    .happy-client-card {
        padding: 30px 26px;
        border: 1px solid #e1ebf4;
        border-radius: 26px;
        background: #fff;
        box-shadow: 0 18px 40px rgba(17, 37, 63, 0.08);
    }

    .happy-client-top {
        display: flex;
        align-items: center;
        gap: 16px;
        margin-bottom: 20px;
    }

    .happy-client-avatar {
        width: 68px;
        height: 68px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid #eef5fb;
        box-shadow: 0 12px 24px rgba(17, 37, 63, 0.1);
        flex-shrink: 0;
    }

    .happy-client-name {
        margin: 0 0 4px;
        color: #15314b;
        font-size: 20px;
        font-weight: 800;
    }

    .happy-client-role {
        display: block;
        color: #6c8095;
        font-size: 14px;
        font-weight: 700;
    }

    .happy-client-review {
        margin: 0;
        color: #516679;
        font-size: 16px;
        line-height: 1.8;
    }

    .welcome-map-section {
        padding: 88px 0 96px;
        background:
            radial-gradient(circle at top right, rgba(13, 110, 253, 0.1), transparent 24%),
            linear-gradient(180deg, #f4f9fe 0%, #e9f2fb 100%);
    }

    .welcome-map-header {
        max-width: 720px;
        margin: 0 auto 40px;
        text-align: center;
    }

    .welcome-map-label {
        display: inline-block;
        margin-bottom: 14px;
        padding: 8px 16px;
        border-radius: 999px;
        background: rgba(13, 110, 253, 0.1);
        color: #0d6efd;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: 0.12em;
        text-transform: uppercase;
    }

    .welcome-map-title {
        margin-bottom: 12px;
        color: #15314b;
        font-size: clamp(2rem, 4vw, 3rem);
        font-weight: 800;
    }

    .welcome-map-text {
        margin: 0;
        color: #607488;
        font-size: 17px;
        line-height: 1.7;
    }

    .welcome-map-shell {
        position: relative;
        padding: 16px;
        border: 1px solid rgba(204, 220, 236, 0.95);
        border-radius: 32px;
        background: rgba(255, 255, 255, 0.88);
        box-shadow: 0 24px 60px rgba(17, 37, 63, 0.12);
        backdrop-filter: blur(10px);
        overflow: hidden;
    }

    .welcome-map-shell::before {
        content: "";
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, rgba(13, 110, 253, 0.08), transparent 35%);
        pointer-events: none;
    }

    .welcome-map-frame {
        position: relative;
        display: block;
        width: 100%;
        height: 500px;
        border: 0;
        border-radius: 22px;
    }

    @media (max-width: 767.98px) {
        .welcome-page {
            min-height: calc(100vh - 72px);
        }

        .welcome-hero {
            min-height: calc(100vh - 72px);
            padding: 24px 0;
        }

        .welcome-copy {
            padding: 0;
        }

        .welcome-text {
            font-size: 16px;
        }

        .welcome-title {
            font-size: clamp(1.9rem, 8vw, 2.8rem);
        }

        .featured-destinations {
            padding: 60px 0 72px;
        }

        .featured-destinations-text {
            font-size: 16px;
        }

        .happy-clients-section {
            padding: 60px 0 72px;
        }

        .happy-clients-text,
        .happy-client-review {
            font-size: 16px;
        }

        .welcome-map-section {
            padding: 60px 0 72px;
        }

        .welcome-map-text {
            font-size: 16px;
        }

        .welcome-map-shell {
            padding: 12px;
            border-radius: 24px;
        }

        .welcome-map-frame {
            height: 320px;
            border-radius: 18px;
        }
    }

    @media (max-width: 991.98px) {
        .destination-card-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .happy-clients-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 575.98px) {
        .destination-card-grid {
            grid-template-columns: 1fr;
        }

        .happy-clients-grid {
            grid-template-columns: 1fr;
        }

        .destination-card {
            border-radius: 20px;
        }

        .destination-card-body {
            padding: 22px 20px;
        }

        .destination-card-image-wrap {
            height: 200px;
        }

        .happy-client-card {
            padding: 24px 20px;
            border-radius: 22px;
        }
    }
</style>

<main class="welcome-page">
    <div class="container">
        <section class="welcome-hero">
            <div class="welcome-copy">
               
                <h1 class="welcome-title">Welcome, <span><?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?></span></h1>
                <p class="welcome-text">
                    Your next journey starts here. Explore destinations, plan memorable trips, and enjoy a calm travel space with everything ready for you.
                </p>
               
            </div>
        </section>
    </div>
</main>

<section class="featured-destinations">
    <div class="container">
        <div class="featured-destinations-header">
            <span class="featured-destinations-label">Destinations</span>
            <h2 class="featured-destinations-title">Featured destinations with fixed prices</h2>
            <p class="featured-destinations-text">
                Explore these handpicked destinations and discover simple starting prices for your next travel plan.
            </p>
        </div>

        <div class="destination-card-grid">
            <?php foreach ($featuredDestinations as $destination): ?>
                <article class="destination-card">
                    <div class="destination-card-image-wrap">
                        <img
                            class="destination-card-image"
                            src="<?php echo htmlspecialchars($destination['image'], ENT_QUOTES, 'UTF-8'); ?>"
                            alt="<?php echo htmlspecialchars($destination['name'], ENT_QUOTES, 'UTF-8'); ?>"
                        >
                    </div>
                    <div class="destination-card-body">
                        <span class="destination-card-tag"><?php echo htmlspecialchars($destination['tag'], ENT_QUOTES, 'UTF-8'); ?></span>
                        <h3 class="destination-card-name"><?php echo htmlspecialchars($destination['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                        <div class="destination-card-price">
                            <span>Starting from</span>
                            <strong><?php echo htmlspecialchars($destination['price'], ENT_QUOTES, 'UTF-8'); ?></strong>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <div class="featured-destinations-action">
            <a href="alldestination.php" class="featured-destinations-button">View More</a>
        </div>
    </div>
</section>

<?php if (!empty($happyClients)): ?>
<section class="happy-clients-section">
    <div class="container">
        <div class="happy-clients-header">
            <span class="happy-clients-label">Happy Clients</span>
            <h2 class="happy-clients-title">What our travelers say</h2>
            <p class="happy-clients-text">
                Real feedback from clients who enjoyed smooth planning, clear pricing, and memorable travel experiences.
            </p>
        </div>

        <div class="happy-clients-grid">
            <?php foreach ($happyClients as $client): ?>
                <article class="happy-client-card">
                    <div class="happy-client-top">
                        <img
                            class="happy-client-avatar"
                            src="<?php echo htmlspecialchars($client['image'], ENT_QUOTES, 'UTF-8'); ?>"
                            alt="<?php echo htmlspecialchars($client['name'], ENT_QUOTES, 'UTF-8'); ?>"
                        >
                        <div>
                            <h3 class="happy-client-name"><?php echo htmlspecialchars($client['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <span class="happy-client-role"><?php echo htmlspecialchars($client['role'], ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                    </div>
                    <p class="happy-client-review">
                        <?php echo htmlspecialchars($client['review'], ENT_QUOTES, 'UTF-8'); ?>
                    </p>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($mapUrl !== ''): ?>
<section class="welcome-map-section">
    <div class="container">
        <div class="welcome-map-header">
            <span class="welcome-map-label">Map</span>
            <h2 class="welcome-map-title">Find Us On The Map</h2>
            <p class="welcome-map-text">
                Explore our location and get a better feel for where your next travel planning journey begins.
            </p>
        </div>
        <div class="welcome-map-shell">
            <iframe
                class="welcome-map-frame"
                src="<?php echo htmlspecialchars($mapUrl, ENT_QUOTES, 'UTF-8'); ?>"
                allowfullscreen=""
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
                title="Travel Location Map"
            ></iframe>
        </div>
    </div>
</section>
<?php endif; ?>

<?php include 'footer.php'; ?>
