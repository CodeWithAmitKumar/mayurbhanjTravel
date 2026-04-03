<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['frontend_user'])) {
    header('Location: login.php');
    exit;
}

$destinationRows = [];
$tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'all_destinations'");
$tableExists = $tableCheck && mysqli_num_rows($tableCheck) > 0;

if ($tableExists) {
    $result = mysqli_query($conn, "SELECT destination_id, destinationimage, subheading, titel, price, description FROM all_destinations ORDER BY sort_order ASC, destination_id ASC");
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $destinationRows[] = [
                'destination_id' => (int) ($row['destination_id'] ?? 0),
                'destinationimage' => trim((string) ($row['destinationimage'] ?? '')),
                'subheading' => trim((string) ($row['subheading'] ?? '')),
                'titel' => trim((string) ($row['titel'] ?? '')),
                'price' => trim((string) ($row['price'] ?? '')),
                'description' => trim((string) ($row['description'] ?? '')),
            ];
        }
    }
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

include 'header2.php';
?>

<style>
    .all-destinations-page {
        background:
            radial-gradient(circle at top left, rgba(13, 110, 253, 0.08), transparent 30%),
            linear-gradient(180deg, #f7fbff 0%, #eef5fb 100%);
        min-height: calc(100vh - 84px);
    }

    .destinations-hero {
        position: relative;
        overflow: hidden;
        padding: 88px 0 56px;
    }

    .destinations-hero::before {
        content: "";
        position: absolute;
        inset: 0;
        background:
            radial-gradient(circle at top right, rgba(255, 127, 80, 0.18), transparent 24%),
            radial-gradient(circle at bottom left, rgba(13, 110, 253, 0.14), transparent 28%);
        pointer-events: none;
    }

    .destinations-hero-copy {
        position: relative;
        max-width: 760px;
        text-align: center;
        margin: 0 auto;
    }

    .destinations-label {
        display: inline-block;
        margin-bottom: 16px;
        padding: 8px 16px;
        border-radius: 999px;
        background: rgba(13, 110, 253, 0.1);
        color: #0d6efd;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: 0.12em;
        text-transform: uppercase;
    }

    .destinations-title {
        margin: 0 0 14px;
        color: #15314b;
        font-size: clamp(2.2rem, 4vw, 3.6rem);
        font-weight: 800;
    }

    .destinations-text {
        margin: 0;
        color: #607488;
        font-size: 17px;
        line-height: 1.8;
    }

    .destinations-section {
        padding: 0 0 96px;
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
        box-shadow: 0 22px 46px rgba(17, 37, 63, 0.08);
        transition: transform 0.25s ease, box-shadow 0.25s ease;
    }

    .destination-card:hover {
        transform: translateY(-7px);
        box-shadow: 0 28px 54px rgba(17, 37, 63, 0.14);
    }

    .destination-card-media {
        position: relative;
        height: 240px;
        overflow: hidden;
        background: linear-gradient(135deg, #dbeafe, #eff6ff);
    }

    .destination-card-media::after {
        content: "";
        position: absolute;
        inset: 0;
        background: linear-gradient(180deg, rgba(8, 19, 34, 0.08) 0%, rgba(8, 19, 34, 0.56) 100%);
        pointer-events: none;
    }

    .destination-card-media img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.35s ease;
    }

    .destination-card:hover .destination-card-media img {
        transform: scale(1.06);
    }

    .destination-card-subheading {
        position: absolute;
        top: 18px;
        left: 18px;
        z-index: 1;
        display: inline-flex;
        align-items: center;
        padding: 8px 14px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.9);
        color: #0d6efd;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .destination-card-body {
        padding: 26px 24px 24px;
    }

    .destination-card-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 18px;
        margin-bottom: 14px;
    }

    .destination-card-title {
        margin: 0;
        color: #15314b;
        font-size: 25px;
        font-weight: 800;
        line-height: 1.2;
    }

    .destination-card-price {
        flex-shrink: 0;
        min-width: 136px;
        padding: 12px 14px;
        border-radius: 20px;
        background: linear-gradient(135deg, #0d6efd, #0b5ed7);
        color: #ffffff;
        box-shadow: 0 14px 28px rgba(13, 110, 253, 0.22);
        text-align: right;
    }

    .destination-card-price-label {
        display: block;
        margin-bottom: 4px;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        opacity: 0.82;
    }

    .destination-card-price-value {
        display: block;
        font-size: 24px;
        font-weight: 800;
        line-height: 1.1;
    }

    .destination-card-description {
        margin: 0;
        color: #607488;
        font-size: 15px;
        line-height: 1.8;
    }

    .destination-card-description.is-collapsed {
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .destination-card-readmore {
        margin-top: 14px;
        padding: 0;
        border: none;
        background: transparent;
        color: #0d6efd;
        font-size: 14px;
        font-weight: 800;
        letter-spacing: 0.03em;
        cursor: pointer;
    }

    .destination-card-readmore:hover {
        color: #0b5ed7;
        text-decoration: underline;
    }

    .empty-state {
        max-width: 720px;
        margin: 0 auto;
        padding: 44px 32px;
        border: 1px solid #dce7f3;
        border-radius: 28px;
        background: rgba(255, 255, 255, 0.92);
        box-shadow: 0 22px 46px rgba(17, 37, 63, 0.08);
        text-align: center;
    }

    .empty-state i {
        display: inline-flex;
        width: 72px;
        height: 72px;
        margin-bottom: 20px;
        border-radius: 50%;
        align-items: center;
        justify-content: center;
        background: rgba(13, 110, 253, 0.1);
        color: #0d6efd;
        font-size: 28px;
    }

    .empty-state h2 {
        margin-bottom: 12px;
        color: #15314b;
        font-size: 28px;
        font-weight: 800;
    }

    .empty-state p {
        margin: 0;
        color: #607488;
        font-size: 16px;
        line-height: 1.8;
    }

    @media (max-width: 991.98px) {
        .destinations-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 767.98px) {
        .destinations-hero {
            padding: 64px 0 42px;
        }

        .destinations-text {
            font-size: 16px;
        }

        .destinations-section {
            padding-bottom: 72px;
        }
    }

    @media (max-width: 575.98px) {
        .destinations-grid {
            grid-template-columns: 1fr;
        }

        .destination-card {
            border-radius: 22px;
        }

        .destination-card-media {
            height: 220px;
        }

        .destination-card-top {
            flex-direction: column;
            gap: 10px;
        }
    }
</style>

<main class="all-destinations-page">
    <section class="destinations-hero">
        <div class="container">
            <div class="destinations-hero-copy">
                <span class="destinations-label">Destinations</span>
                <h1 class="destinations-title">Explore every destination we have prepared for you</h1>
                <p class="destinations-text">
                    Browse all destination cards added from the admin panel, with price, details, and travel inspiration in one place.
                </p>
            </div>
        </div>
    </section>

    <section class="destinations-section">
        <div class="container">
            <?php if (!empty($destinationRows)): ?>
                <div class="destinations-grid">
                    <?php foreach ($destinationRows as $destination): ?>
                        <article class="destination-card">
                            <div class="destination-card-media">
                                <span class="destination-card-subheading">
                                    <?php echo htmlspecialchars($destination['subheading'] !== '' ? $destination['subheading'] : 'Destination', ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                                <img
                                    src="<?php echo htmlspecialchars($destination['destinationimage'] !== '' ? $destination['destinationimage'] : 'img/destination-1.jpg', ENT_QUOTES, 'UTF-8'); ?>"
                                    alt="<?php echo htmlspecialchars($destination['titel'] !== '' ? $destination['titel'] : 'Destination', ENT_QUOTES, 'UTF-8'); ?>"
                                >
                            </div>
                            <div class="destination-card-body">
                                <div class="destination-card-top">
                                    <h2 class="destination-card-title">
                                        <?php echo htmlspecialchars($destination['titel'], ENT_QUOTES, 'UTF-8'); ?>
                                    </h2>
                                    <div class="destination-card-price">
                                        <span class="destination-card-price-label">Starting From</span>
                                        <span class="destination-card-price-value"><?php echo htmlspecialchars(format_destination_price($destination['price']), ENT_QUOTES, 'UTF-8'); ?></span>
                                    </div>
                                </div>
                                <p class="destination-card-description">
                                    <?php echo htmlspecialchars($destination['description'], ENT_QUOTES, 'UTF-8'); ?>
                                </p>
                                <?php if (strlen($destination['description']) > 140): ?>
                                    <button type="button" class="destination-card-readmore" onclick="toggleDescription(this)">Read More</button>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fa fa-map-signs"></i>
                    <h2>No destinations available yet</h2>
                    <p>The destination cards will appear here automatically after you add them from the admin panel.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.destination-card-description').forEach(function(node) {
            if (node.nextElementSibling && node.nextElementSibling.classList.contains('destination-card-readmore')) {
                node.classList.add('is-collapsed');
            }
        });
    });

    function toggleDescription(button) {
        const description = button.previousElementSibling;
        if (!description) {
            return;
        }

        const isCollapsed = description.classList.contains('is-collapsed');
        if (isCollapsed) {
            description.classList.remove('is-collapsed');
            button.textContent = 'Read Less';
        } else {
            description.classList.add('is-collapsed');
            button.textContent = 'Read More';
        }
    }
</script>

<?php include 'footer.php'; ?>
