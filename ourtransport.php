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

$transports = [];
$transportsResult = mysqli_query(
    $conn,
    "SELECT transport_id, vehicle_image, driver_image, vehicle_details, price, driver_details, driver_phone_no, driver_address, is_active, created_at, updated_at
     FROM transports
     WHERE is_active = 1
     ORDER BY transport_id DESC"
);

if ($transportsResult) {
    while ($row = mysqli_fetch_assoc($transportsResult)) {
        $transports[] = $row;
    }
}

include 'header2.php';
?>

<style>
    .all-transport-page {
        background:
            radial-gradient(circle at top left, rgba(13, 110, 253, 0.08), transparent 30%),
            linear-gradient(180deg, #f7fbff 0%, #eef5fb 100%);
        min-height: calc(100vh - 84px);
    }

    .transport-hero {
        position: relative;
        overflow: hidden;
        padding: 88px 0 56px;
    }

    .transport-hero::before {
        content: "";
        position: absolute;
        inset: 0;
        background:
            radial-gradient(circle at top right, rgba(255, 127, 80, 0.18), transparent 24%),
            radial-gradient(circle at bottom left, rgba(13, 110, 253, 0.14), transparent 28%);
        pointer-events: none;
    }

    .transport-hero-copy {
        position: relative;
        max-width: 760px;
        text-align: center;
        margin: 0 auto;
    }

    .transport-label {
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

    .transport-title {
        margin: 0 0 14px;
        color: #15314b;
        font-size: clamp(2.2rem, 4vw, 3.6rem);
        font-weight: 800;
    }

    .transport-text {
        margin: 0;
        color: #607488;
        font-size: 17px;
        line-height: 1.8;
    }

    .transport-section {
        padding: 0 0 96px;
    }

    .transport-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 26px;
    }

    .transport-card {
        overflow: hidden;
        border: 1px solid #dce7f3;
        border-radius: 28px;
        background: rgba(255, 255, 255, 0.96);
        box-shadow: 0 22px 46px rgba(17, 37, 63, 0.08);
        transition: transform 0.25s ease, box-shadow 0.25s ease;
    }

    .transport-card:hover {
        transform: translateY(-7px);
        box-shadow: 0 28px 54px rgba(17, 37, 63, 0.14);
    }

    .transport-card-gallery {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0;
    }

    .transport-card-media {
        position: relative;
        height: 240px;
        overflow: hidden;
        background: linear-gradient(135deg, #dbeafe, #eff6ff);
    }

    .transport-card-media::after {
        content: "";
        position: absolute;
        inset: 0;
        background: linear-gradient(180deg, rgba(8, 19, 34, 0.08) 0%, rgba(8, 19, 34, 0.56) 100%);
        pointer-events: none;
    }

    .transport-card-media img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.35s ease;
    }

    .transport-card:hover .transport-card-media img {
        transform: scale(1.06);
    }

    .transport-card-badge {
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

    .transport-card-body {
        padding: 26px 24px 24px;
    }

    .transport-card-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 18px;
        margin-bottom: 14px;
    }

    .transport-card-title {
        margin: 0;
        color: #15314b;
        font-size: 25px;
        font-weight: 800;
        line-height: 1.2;
    }

    .transport-price {
        flex-shrink: 0;
        min-width: 136px;
        padding: 12px 14px;
        border-radius: 20px;
        background: linear-gradient(135deg, #0d6efd, #0b5ed7);
        color: #ffffff;
        box-shadow: 0 14px 28px rgba(13, 110, 253, 0.22);
        text-align: right;
    }

    .transport-price-label {
        display: block;
        margin-bottom: 4px;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        opacity: 0.82;
    }

    .transport-price-value {
        display: block;
        font-size: 24px;
        font-weight: 800;
        line-height: 1.1;
    }

    .transport-detail {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        color: #607488;
        margin-bottom: 12px;
        line-height: 1.8;
    }

    .transport-detail i {
        color: #0d6efd;
        margin-top: 4px;
    }

    .transport-copy {
        margin: 0;
        color: #607488;
        font-size: 15px;
        line-height: 1.8;
    }

    .transport-copy.is-collapsed {
        display: -webkit-box;
        line-clamp: 3;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .transport-readmore {
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

    .transport-readmore:hover {
        color: #0b5ed7;
        text-decoration: underline;
    }

    .transport-actions {
        margin-top: 20px;
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
    }

    .transport-actions .btn {
        border-radius: 16px;
        padding: 12px 22px;
        box-shadow: 0 14px 28px rgba(13, 110, 253, 0.18);
    }

    .transport-empty {
        max-width: 720px;
        margin: 0 auto;
        padding: 44px 32px;
        border: 1px solid #dce7f3;
        border-radius: 28px;
        background: rgba(255, 255, 255, 0.92);
        box-shadow: 0 22px 46px rgba(17, 37, 63, 0.08);
        text-align: center;
    }

    .transport-empty i {
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

    .transport-empty h2 {
        margin-bottom: 12px;
        color: #15314b;
        font-size: 28px;
        font-weight: 800;
    }

    .transport-empty p {
        margin: 0;
        color: #607488;
        font-size: 16px;
        line-height: 1.8;
    }

    @media (max-width: 991.98px) {
        .transport-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 767.98px) {
        .transport-hero {
            padding: 64px 0 42px;
        }

        .transport-text {
            font-size: 16px;
        }

        .transport-section {
            padding-bottom: 72px;
        }
    }

    @media (max-width: 575.98px) {
        .transport-card {
            border-radius: 22px;
        }

        .transport-card-gallery {
            grid-template-columns: 1fr;
        }

        .transport-card-media {
            height: 220px;
        }

        .transport-card-top {
            flex-direction: column;
            gap: 10px;
        }
    }
</style>

<main class="all-transport-page">
    <section class="transport-hero">
        <div class="container">
            <div class="transport-hero-copy">
                <span class="transport-label">Our Transport</span>
                <h1 class="transport-title">Travel with ready transport and trusted drivers</h1>
                <p class="transport-text">
                    Browse all active transport options added from the admin panel, including vehicle photos, driver photos, pricing, and contact details.
                </p>
            </div>
        </div>
    </section>

    <section class="transport-section">
        <div class="container">
            <?php if (!empty($transports)): ?>
                <div class="transport-grid">
                    <?php foreach ($transports as $transport): ?>
                        <article class="transport-card">
                            <div class="transport-card-gallery">
                                <div class="transport-card-media">
                                    <span class="transport-card-badge">Vehicle</span>
                                    <img
                                        src="<?php echo !empty($transport['vehicle_image']) ? h($transport['vehicle_image']) : 'img/contactus.jpg'; ?>"
                                        alt="Vehicle image">
                                </div>
                                <div class="transport-card-media">
                                    <span class="transport-card-badge">Driver</span>
                                    <img
                                        src="<?php echo !empty($transport['driver_image']) ? h($transport['driver_image']) : 'img/contactus.jpg'; ?>"
                                        alt="Driver image">
                                </div>
                            </div>
                            <div class="transport-card-body">
                                <div class="transport-card-top">
                                    <h2 class="transport-card-title">Transport Option</h2>
                                    <div class="transport-price">
                                        <span class="transport-price-label">Price</span>
                                        <span class="transport-price-value"><?php echo h($transport['price']); ?></span>
                                    </div>
                                </div>

                                <div class="transport-detail">
                                    <i class="fa fa-car"></i>
                                    <div>
                                        <p class="transport-copy"><?php echo h($transport['vehicle_details']); ?></p>
                                        <?php if (strlen((string) $transport['vehicle_details']) > 140): ?>
                                            <button type="button" class="transport-readmore" onclick="toggleTransportCopy(this)">Read More</button>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="transport-detail">
                                    <i class="fa fa-user"></i>
                                    <div>
                                        <p class="transport-copy"><?php echo h($transport['driver_details']); ?></p>
                                        <?php if (strlen((string) $transport['driver_details']) > 140): ?>
                                            <button type="button" class="transport-readmore" onclick="toggleTransportCopy(this)">Read More</button>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="transport-detail">
                                    <i class="fa fa-phone-alt"></i>
                                    <span><?php echo h($transport['driver_phone_no']); ?></span>
                                </div>

                                <div class="transport-detail">
                                    <i class="fa fa-map-marker-alt"></i>
                                    <span><?php echo h($transport['driver_address']); ?></span>
                                </div>

                                <div class="transport-actions">
                                    <a href="tel:<?php echo preg_replace('/[^0-9+]/', '', (string) $transport['driver_phone_no']); ?>" class="btn btn-primary">
                                        <i class="fa fa-phone-alt me-2"></i>Call Driver
                                    </a>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="transport-empty">
                    <i class="fa fa-car"></i>
                    <h2>No transport available yet</h2>
                    <p>Transport cards will appear here automatically after you add them from the admin panel and keep them active.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.transport-copy').forEach(function(node) {
            const toggleButton = node.parentElement ? node.parentElement.querySelector('.transport-readmore') : null;
            if (toggleButton) {
                node.classList.add('is-collapsed');
            }
        });
    });

    function toggleTransportCopy(button) {
        const wrapper = button.parentElement;
        const copy = wrapper ? wrapper.querySelector('.transport-copy') : null;
        if (!copy) {
            return;
        }

        const isCollapsed = copy.classList.contains('is-collapsed');
        if (isCollapsed) {
            copy.classList.remove('is-collapsed');
            button.textContent = 'Read Less';
        } else {
            copy.classList.add('is-collapsed');
            button.textContent = 'Read More';
        }
    }
</script>

<?php include 'footer.php'; ?>
