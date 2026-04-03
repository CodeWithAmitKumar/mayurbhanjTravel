<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['frontend_user'])) {
    header('Location: login.php');
    exit;
}

include 'header2.php';

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$guides = [];
$guidesResult = mysqli_query(
    $conn,
    "SELECT guide_id, guide_name, guide_image, price, guide_address, guide_phone_no, is_active, created_at, updated_at
     FROM guides
     WHERE is_active = 1
     ORDER BY guide_id DESC"
);

if ($guidesResult) {
    while ($row = mysqli_fetch_assoc($guidesResult)) {
        $guides[] = $row;
    }
}

?>
<style>
    .all-guides-page {
        background:
            radial-gradient(circle at top left, rgba(13, 110, 253, 0.08), transparent 30%),
            linear-gradient(180deg, #f7fbff 0%, #eef5fb 100%);
        min-height: calc(100vh - 84px);
    }

    .guides-hero {
        position: relative;
        overflow: hidden;
        padding: 88px 0 56px;
    }

    .guides-hero::before {
        content: "";
        position: absolute;
        inset: 0;
        background:
            radial-gradient(circle at top right, rgba(255, 127, 80, 0.18), transparent 24%),
            radial-gradient(circle at bottom left, rgba(13, 110, 253, 0.14), transparent 28%);
        pointer-events: none;
    }

    .guides-hero-copy {
        position: relative;
        max-width: 760px;
        text-align: center;
        margin: 0 auto;
    }

    .guides-label {
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

    .guides-title {
        margin: 0 0 14px;
        color: #15314b;
        font-size: clamp(2.2rem, 4vw, 3.6rem);
        font-weight: 800;
    }

    .guides-text {
        margin: 0;
        color: #607488;
        font-size: 17px;
        line-height: 1.8;
    }

    .guides-section {
        padding: 0 0 96px;
    }

    .guides-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 26px;
    }

    .guide-card {
        overflow: hidden;
        border: 1px solid #dce7f3;
        border-radius: 28px;
        background: rgba(255, 255, 255, 0.96);
        box-shadow: 0 22px 46px rgba(17, 37, 63, 0.08);
        transition: transform 0.25s ease, box-shadow 0.25s ease;
    }

    .guide-card:hover {
        transform: translateY(-7px);
        box-shadow: 0 28px 54px rgba(17, 37, 63, 0.14);
    }

    .guide-card-media {
        position: relative;
        height: 240px;
        overflow: hidden;
        background: linear-gradient(135deg, #dbeafe, #eff6ff);
    }

    .guide-card-media::after {
        content: "";
        position: absolute;
        inset: 0;
        background: linear-gradient(180deg, rgba(8, 19, 34, 0.08) 0%, rgba(8, 19, 34, 0.56) 100%);
        pointer-events: none;
    }

    .guide-card-media img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.35s ease;
    }

    .guide-card:hover .guide-card-media img {
        transform: scale(1.06);
    }

    .guide-card-label {
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

    .guide-card-body {
        padding: 26px 24px 24px;
    }

    .guide-card-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 18px;
        margin-bottom: 14px;
    }

    .guide-card-name {
        margin: 0;
        color: #15314b;
        font-size: 25px;
        font-weight: 800;
        line-height: 1.2;
    }

    .guide-price {
        flex-shrink: 0;
        min-width: 136px;
        padding: 12px 14px;
        border-radius: 20px;
        background: linear-gradient(135deg, #0d6efd, #0b5ed7);
        color: #ffffff;
        box-shadow: 0 14px 28px rgba(13, 110, 253, 0.22);
        text-align: right;
    }

    .guide-price-label {
        display: block;
        margin-bottom: 4px;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        opacity: 0.82;
    }

    .guide-price-value {
        display: block;
        font-size: 24px;
        font-weight: 800;
        line-height: 1.1;
    }

    .guide-detail {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        color: #607488;
        margin-bottom: 12px;
        line-height: 1.8;
    }

    .guide-detail i {
        color: #0d6efd;
        margin-top: 4px;
    }

    .guide-actions {
        margin-top: 20px;
    }

    .guide-actions .btn {
        border-radius: 16px;
        padding: 12px 22px;
        box-shadow: 0 14px 28px rgba(13, 110, 253, 0.18);
    }

    .guides-empty {
        max-width: 720px;
        margin: 0 auto;
        padding: 44px 32px;
        border: 1px solid #dce7f3;
        border-radius: 28px;
        background: rgba(255, 255, 255, 0.92);
        box-shadow: 0 22px 46px rgba(17, 37, 63, 0.08);
        text-align: center;
    }

    .guides-empty i {
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

    .guides-empty h2 {
        margin-bottom: 12px;
        color: #15314b;
        font-size: 28px;
        font-weight: 800;
    }

    .guides-empty p {
        margin: 0;
        color: #607488;
        font-size: 16px;
        line-height: 1.8;
    }

    @media (max-width: 991.98px) {
        .guides-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 767.98px) {
        .guides-hero {
            padding: 64px 0 42px;
        }

        .guides-text {
            font-size: 16px;
        }

        .guides-section {
            padding-bottom: 72px;
        }
    }

    @media (max-width: 575.98px) {
        .guides-grid {
            grid-template-columns: 1fr;
        }

        .guide-card {
            border-radius: 22px;
        }

        .guide-card-media {
            height: 220px;
        }

        .guide-card-top {
            flex-direction: column;
            gap: 10px;
        }
    }
</style>

<main class="all-guides-page">
    <section class="guides-hero">
        <div class="container">
            <div class="guides-hero-copy">
                <span class="guides-label">Our Guides</span>
                <h1 class="guides-title">Choose the right local guide for your journey</h1>
                <p class="guides-text">
                    Browse all active guides added from the admin panel, with guide photo, contact details, address, and pricing in one place.
                </p>
            </div>
        </div>
    </section>
    <!-- Navbar & Hero End -->

    <section class="guides-section">
        <div class="container">
            <?php if (!empty($guides)): ?>
                <div class="guides-grid">
                    <?php foreach ($guides as $guide): ?>
                        <article class="guide-card">
                            <div class="guide-card-media">
                                <span class="guide-card-label">Local Guide</span>
                                <img
                                    src="<?php echo !empty($guide['guide_image']) ? h($guide['guide_image']) : 'img/contactus.jpg'; ?>"
                                    alt="<?php echo h($guide['guide_name']); ?>">
                            </div>
                            <div class="guide-card-body">
                                <div class="guide-card-top">
                                    <h2 class="guide-card-name"><?php echo h($guide['guide_name']); ?></h2>
                                    <div class="guide-price">
                                        <span class="guide-price-label">Price</span>
                                        <span class="guide-price-value"><?php echo h($guide['price']); ?></span>
                                    </div>
                                </div>

                                <div class="guide-detail">
                                    <i class="fa fa-map-marker-alt"></i>
                                    <span><?php echo h($guide['guide_address']); ?></span>
                                </div>

                                <div class="guide-detail">
                                    <i class="fa fa-phone-alt"></i>
                                    <span><?php echo h($guide['guide_phone_no']); ?></span>
                                </div>

                                <div class="guide-actions">
                                    <a href="tel:<?php echo preg_replace('/[^0-9+]/', '', (string) $guide['guide_phone_no']); ?>" class="btn btn-primary">
                                        <i class="fa fa-phone-alt me-2"></i>Call Guide
                                    </a>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="guides-empty">
                    <i class="fa fa-user-tie"></i>
                    <h2>No guides available yet</h2>
                    <p>Guide cards will appear here automatically after you add them from the admin panel and keep them active.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php include 'footer.php'; ?>
