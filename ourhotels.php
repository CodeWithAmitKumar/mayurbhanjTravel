<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['frontend_user'])) {
    header('Location: login.php');
    exit;
}

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$hotels = [];
$hotelsResult = mysqli_query(
    $conn,
    "SELECT hotel_id, hotel_image, hotel_name, location, manager_name, contact_no, facility, price, is_active, created_at, updated_at
     FROM hotels
     WHERE is_active = 1
     ORDER BY hotel_id DESC"
);

if ($hotelsResult) {
    while ($row = mysqli_fetch_assoc($hotelsResult)) {
        $hotels[] = $row;
    }
}

include 'header2.php';
?>

<style>
    .all-hotels-page {
        background:
            radial-gradient(circle at top left, rgba(13, 110, 253, 0.08), transparent 30%),
            linear-gradient(180deg, #f7fbff 0%, #eef5fb 100%);
        min-height: calc(100vh - 84px);
    }

    .hotels-hero {
        position: relative;
        overflow: hidden;
        padding: 88px 0 56px;
    }

    .hotels-hero::before {
        content: "";
        position: absolute;
        inset: 0;
        background:
            radial-gradient(circle at top right, rgba(255, 127, 80, 0.18), transparent 24%),
            radial-gradient(circle at bottom left, rgba(13, 110, 253, 0.14), transparent 28%);
        pointer-events: none;
    }

    .hotels-hero-copy {
        position: relative;
        max-width: 760px;
        text-align: center;
        margin: 0 auto;
    }

    .hotels-label {
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

    .hotels-title {
        margin: 0 0 14px;
        color: #15314b;
        font-size: clamp(2.2rem, 4vw, 3.6rem);
        font-weight: 800;
    }

    .hotels-text {
        margin: 0;
        color: #607488;
        font-size: 17px;
        line-height: 1.8;
    }

    .hotels-section {
        padding: 0 0 96px;
    }

    .hotels-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 26px;
    }

    .hotel-card {
        overflow: hidden;
        border: 1px solid #dce7f3;
        border-radius: 28px;
        background: rgba(255, 255, 255, 0.96);
        box-shadow: 0 22px 46px rgba(17, 37, 63, 0.08);
        transition: transform 0.25s ease, box-shadow 0.25s ease;
    }

    .hotel-card:hover {
        transform: translateY(-7px);
        box-shadow: 0 28px 54px rgba(17, 37, 63, 0.14);
    }

    .hotel-card-media {
        position: relative;
        height: 240px;
        overflow: hidden;
        background: linear-gradient(135deg, #dbeafe, #eff6ff);
    }

    .hotel-card-media::after {
        content: "";
        position: absolute;
        inset: 0;
        background: linear-gradient(180deg, rgba(8, 19, 34, 0.08) 0%, rgba(8, 19, 34, 0.56) 100%);
        pointer-events: none;
    }

    .hotel-card-media img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.35s ease;
    }

    .hotel-card:hover .hotel-card-media img {
        transform: scale(1.06);
    }

    .hotel-card-label {
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

    .hotel-card-body {
        padding: 26px 24px 24px;
    }

    .hotel-card-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 18px;
        margin-bottom: 14px;
    }

    .hotel-card-title {
        margin: 0;
        color: #15314b;
        font-size: 25px;
        font-weight: 800;
        line-height: 1.2;
    }

    .hotel-price {
        flex-shrink: 0;
        min-width: 136px;
        padding: 12px 14px;
        border-radius: 20px;
        background: linear-gradient(135deg, #0d6efd, #0b5ed7);
        color: #ffffff;
        box-shadow: 0 14px 28px rgba(13, 110, 253, 0.22);
        text-align: right;
    }

    .hotel-price-label {
        display: block;
        margin-bottom: 4px;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        opacity: 0.82;
    }

    .hotel-price-value {
        display: block;
        font-size: 24px;
        font-weight: 800;
        line-height: 1.1;
    }

    .hotel-detail {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        color: #607488;
        margin-bottom: 12px;
        line-height: 1.8;
    }

    .hotel-detail i {
        color: #0d6efd;
        margin-top: 4px;
    }

    .hotel-facility {
        margin: 0;
        color: #607488;
        font-size: 15px;
        line-height: 1.8;
    }

    .hotel-facility.is-collapsed {
        display: -webkit-box;
        line-clamp: 3;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .hotel-readmore {
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

    .hotel-readmore:hover {
        color: #0b5ed7;
        text-decoration: underline;
    }

    .hotel-actions {
        margin-top: 20px;
    }

    .hotel-actions .btn {
        border-radius: 16px;
        padding: 12px 22px;
        box-shadow: 0 14px 28px rgba(13, 110, 253, 0.18);
    }

    .hotels-empty {
        max-width: 720px;
        margin: 0 auto;
        padding: 44px 32px;
        border: 1px solid #dce7f3;
        border-radius: 28px;
        background: rgba(255, 255, 255, 0.92);
        box-shadow: 0 22px 46px rgba(17, 37, 63, 0.08);
        text-align: center;
    }

    .hotels-empty i {
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

    .hotels-empty h2 {
        margin-bottom: 12px;
        color: #15314b;
        font-size: 28px;
        font-weight: 800;
    }

    .hotels-empty p {
        margin: 0;
        color: #607488;
        font-size: 16px;
        line-height: 1.8;
    }

    @media (max-width: 991.98px) {
        .hotels-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 767.98px) {
        .hotels-hero {
            padding: 64px 0 42px;
        }

        .hotels-text {
            font-size: 16px;
        }

        .hotels-section {
            padding-bottom: 72px;
        }
    }

    @media (max-width: 575.98px) {
        .hotels-grid {
            grid-template-columns: 1fr;
        }

        .hotel-card {
            border-radius: 22px;
        }

        .hotel-card-media {
            height: 220px;
        }

        .hotel-card-top {
            flex-direction: column;
            gap: 10px;
        }
    }
</style>

<main class="all-hotels-page">
    <section class="hotels-hero">
        <div class="container">
            <div class="hotels-hero-copy">
                <span class="hotels-label">Our Hotels</span>
                <h1 class="hotels-title">Stay at handpicked hotels prepared for your trip</h1>
                <p class="hotels-text">
                    Browse all active hotels added from the admin panel, with hotel photo, location, manager details, facilities, and pricing in one place.
                </p>
            </div>
        </div>
    </section>

    <section class="hotels-section">
        <div class="container">
            <?php if (!empty($hotels)): ?>
                <div class="hotels-grid">
                    <?php foreach ($hotels as $hotel): ?>
                        <article class="hotel-card">
                            <div class="hotel-card-media">
                                <span class="hotel-card-label">Hotel Stay</span>
                                <img
                                    src="<?php echo !empty($hotel['hotel_image']) ? h($hotel['hotel_image']) : 'img/contactus.jpg'; ?>"
                                    alt="<?php echo h($hotel['hotel_name']); ?>">
                            </div>
                            <div class="hotel-card-body">
                                <div class="hotel-card-top">
                                    <h2 class="hotel-card-title"><?php echo h($hotel['hotel_name']); ?></h2>
                                    <div class="hotel-price">
                                        <span class="hotel-price-label">Price</span>
                                        <span class="hotel-price-value"><?php echo h($hotel['price']); ?></span>
                                    </div>
                                </div>

                                <div class="hotel-detail">
                                    <i class="fa fa-map-marker-alt"></i>
                                    <span><?php echo h($hotel['location']); ?></span>
                                </div>

                                <div class="hotel-detail">
                                    <i class="fa fa-user-tie"></i>
                                    <span><?php echo h($hotel['manager_name']); ?></span>
                                </div>

                                <div class="hotel-detail">
                                    <i class="fa fa-phone-alt"></i>
                                    <span><?php echo h($hotel['contact_no']); ?></span>
                                </div>

                                <div class="hotel-detail">
                                    <i class="fa fa-concierge-bell"></i>
                                    <div>
                                        <p class="hotel-facility"><?php echo h($hotel['facility']); ?></p>
                                        <?php if (strlen((string) $hotel['facility']) > 140): ?>
                                            <button type="button" class="hotel-readmore" onclick="toggleFacility(this)">Read More</button>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="hotel-actions">
                                    <a href="tel:<?php echo preg_replace('/[^0-9+]/', '', (string) $hotel['contact_no']); ?>" class="btn btn-primary">
                                        <i class="fa fa-phone-alt me-2"></i>Call Hotel
                                    </a>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="hotels-empty">
                    <i class="fa fa-hotel"></i>
                    <h2>No hotels available yet</h2>
                    <p>Hotel cards will appear here automatically after you add them from the admin panel and keep them active.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.hotel-facility').forEach(function(node) {
            const toggleButton = node.parentElement ? node.parentElement.querySelector('.hotel-readmore') : null;
            if (toggleButton) {
                node.classList.add('is-collapsed');
            }
        });
    });

    function toggleFacility(button) {
        const wrapper = button.parentElement;
        const facility = wrapper ? wrapper.querySelector('.hotel-facility') : null;
        if (!facility) {
            return;
        }

        const isCollapsed = facility.classList.contains('is-collapsed');
        if (isCollapsed) {
            facility.classList.remove('is-collapsed');
            button.textContent = 'Read Less';
        } else {
            facility.classList.add('is-collapsed');
            button.textContent = 'Read More';
        }
    }
</script>

<?php include 'footer.php'; ?>
